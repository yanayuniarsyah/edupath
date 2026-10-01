const ftp = require("basic-ftp");
const dotenv = require("dotenv");
const path = require("path");

dotenv.config();

async function deploy() {
    const client = new ftp.Client();

    const host = process.env.FTP_HOST;
    const user = process.env.FTP_USER;
    const password = process.env.FTP_PASS;
    // Sinkronisasi otomatis ke kedua target domain (edupath.co.id & edupath.elyana.biz.id)
    const targetDirs = ["edupath.co.id", "edupath.elyana.biz.id"];

    if (!host || !user || !password) {
        console.error("\x1b[31m[ERROR]\x1b[0m Kredensial FTP di file .env belum lengkap!");
        process.exit(1);
    }

    try {
        console.log(`\x1b[36m[1/3]\x1b[0m Menghubungkan ke server FTP ${host}...`);
        await client.access({
            host: host,
            user: user,
            password: password,
            secure: false
        });
        console.log(`\x1b[32m[OK]\x1b[0m Berhasil terhubung ke server FTP!`);

        const filesToUpload = [
            ".htaccess", 
            "install_db.php", 
            "create_uat_accounts.php", 
            "schema_dump.json", 
            "import_tryout_questions.php", 
            "update_questions_schema.php", 
            "update_aff_schema.php",
            "tryout_data.txt",
            "test_db.php"
        ];

        for (const remoteDir of targetDirs) {
            console.log(`\n\x1b[36m---> Memproses domain /${remoteDir}...<---\x1b[0m`);
            await client.ensureDir("/" + remoteDir);
            await client.cd("/" + remoteDir);

            console.log(`  [+] Mengunggah Frontend (isi folder dist)...`);
            await client.uploadFromDir(path.join(__dirname, "dist"));

            console.log(`  [+] Mengunggah Backend (folder api)...`);
            await client.ensureDir("api");
            await client.uploadFromDir(path.join(__dirname, "api"), "api");

            console.log(`  [+] Mengunggah file konfigurasi tambahan...`);
            for (const file of filesToUpload) {
                try {
                    await client.uploadFrom(path.join(__dirname, file), file);
                    console.log(`      -> ${file} terunggah.`);
                } catch (err) {
                    // Abaikan jika file tidak ada
                }
            }
            console.log(`  \x1b[32m[OK]\x1b[0m Domain ${remoteDir} berhasil diperbarui!`);
        }

        console.log("\n\x1b[32m===================================================\x1b[0m");
        console.log("\x1b[32m🚀 DEPLOYMENT FTP KE SEMUA DOMAIN SELESAI DENGAN SUKSES!\x1b[0m");
        console.log("\x1b[32m===================================================\x1b[0m");
    }
    catch (err) {
        console.error("\x1b[31m[ERROR]\x1b[0m Terjadi kesalahan saat upload FTP:");
        console.error(err);
    }
    client.close();
}

deploy();
