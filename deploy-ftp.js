const ftp = require("basic-ftp");
const dotenv = require("dotenv");
const path = require("path");
const fs = require("fs");

dotenv.config();

function getAllFiles(dirPath, arrayOfFiles = []) {
    if (!fs.existsSync(dirPath)) return arrayOfFiles;
    const files = fs.readdirSync(dirPath);
    files.forEach((file) => {
        const fullPath = path.join(dirPath, file);
        if (fs.statSync(fullPath).isDirectory()) {
            arrayOfFiles = getAllFiles(fullPath, arrayOfFiles);
        } else {
            arrayOfFiles.push(fullPath);
        }
    });
    return arrayOfFiles;
}

async function getConnectedClient() {
    const host = process.env.FTP_HOST;
    const user = process.env.FTP_USER;
    const password = process.env.FTP_PASS;

    const client = new ftp.Client(30000);
    await client.access({
        host: host,
        user: user,
        password: password,
        secure: false
    });
    return client;
}

async function uploadDirectory(localBaseDir, remoteBaseDir) {
    const allFiles = getAllFiles(localBaseDir);
    console.log(`  [i] Total file untuk diunggah ke ${remoteBaseDir}: ${allFiles.length}`);
    
    let client = await getConnectedClient();

    for (let i = 0; i < allFiles.length; i++) {
        const fullPath = allFiles[i];
        const relPath = path.relative(localBaseDir, fullPath).replace(/\\/g, "/");
        const remoteFilePath = `${remoteBaseDir}/${relPath}`.replace(/\/+/g, "/");
        const remoteParent = path.posix.dirname(remoteFilePath);
        const fileName = path.posix.basename(remoteFilePath);

        let uploaded = false;
        for (let retries = 0; retries < 3; retries++) {
            try {
                if (client.closed) {
                    client = await getConnectedClient();
                }
                await client.ensureDir(remoteParent);
                await client.uploadFrom(fullPath, fileName);
                uploaded = true;
                break;
            } catch (err) {
                console.warn(`    [!] Retry (${retries + 1}/3) file ${relPath}: ${err.message}`);
                try { client.close(); } catch (e) {}
                await new Promise(r => setTimeout(r, 1500));
                try {
                    client = await getConnectedClient();
                } catch (connErr) {
                    console.warn(`    [!] Gagal reconnect: ${connErr.message}`);
                }
            }
        }

        if (uploaded) {
            console.log(`    [${i + 1}/${allFiles.length}] ${relPath} OK`);
        } else {
            console.error(`    [FAIL] Gagal mengunggah ${relPath}`);
        }
    }

    try { client.close(); } catch(e) {}
}

async function deployDomain(remoteDir, filesToUpload) {
    console.log(`\n\x1b[36m===================================================\x1b[0m`);
    console.log(`\x1b[36m---> Memproses domain: /${remoteDir}... <---\x1b[0m`);
    console.log(`\x1b[36m===================================================\x1b[0m`);

    // 1. Upload Frontend (dist) ke root domain
    console.log(`\n  [1/3] Mengunggah Frontend (isi folder dist)...`);
    await uploadDirectory(path.join(__dirname, "dist"), "/" + remoteDir);
    console.log(`  \x1b[32m[OK]\x1b[0m Frontend dist selesai.`);

    // 2. Upload Backend (api) ke folder /api
    console.log(`\n  [2/3] Mengunggah Backend (folder api)...`);
    await uploadDirectory(path.join(__dirname, "api"), "/" + remoteDir + "/api");
    console.log(`  \x1b[32m[OK]\x1b[0m Backend api selesai.`);

    // 3. Upload file konfigurasi tambahan ke root domain
    console.log(`\n  [3/3] Mengunggah file konfigurasi tambahan ke root...`);
    let client = await getConnectedClient();
    try {
        await client.ensureDir("/" + remoteDir);
        for (const file of filesToUpload) {
            const localFile = path.join(__dirname, file);
            if (fs.existsSync(localFile)) {
                try {
                    if (client.closed) {
                        client = await getConnectedClient();
                        await client.ensureDir("/" + remoteDir);
                    }
                    await client.uploadFrom(localFile, file);
                    console.log(`      -> ${file} terunggah.`);
                } catch (err) {
                    console.warn(`      -> Gagal upload ${file}: ${err.message}`);
                }
            }
        }
    } finally {
        try { client.close(); } catch(e) {}
    }

    console.log(`\n  \x1b[32m[OK]\x1b[0m Domain ${remoteDir} berhasil diperbarui!`);
}

async function deploy() {
    const host = process.env.FTP_HOST;
    const user = process.env.FTP_USER;
    const password = process.env.FTP_PASS;
    const targetDirs = ["edupath.co.id"];

    if (!host || !user || !password) {
        console.error("\x1b[31m[ERROR]\x1b[0m Kredensial FTP di file .env belum lengkap!");
        process.exit(1);
    }

    const filesToUpload = [
        ".htaccess", 
        "install_db.php", 
        "create_uat_accounts.php", 
        "create_uat_v2.php",
        "schema_dump.json", 
        "import_tryout_questions.php", 
        "update_questions_schema.php", 
        "update_aff_schema.php",
        "tryout_data.txt",
        "test_db.php"
    ];

    try {
        for (const remoteDir of targetDirs) {
            await deployDomain(remoteDir, filesToUpload);
            console.log("  [i] Menunggu 3 detik sebelum domain berikutnya...");
            await new Promise(r => setTimeout(r, 3000));
        }

        console.log("\n\x1b[32m===================================================\x1b[0m");
        console.log("\x1b[32m🚀 DEPLOYMENT FTP KE SEMUA DOMAIN SELESAI DENGAN SUKSES!\x1b[0m");
        console.log("\x1b[32m===================================================\x1b[0m");
    } catch (err) {
        console.error("\x1b[31m[ERROR]\x1b[0m Terjadi kesalahan saat upload FTP:");
        console.error(err);
        process.exit(1);
    }
}

deploy();
