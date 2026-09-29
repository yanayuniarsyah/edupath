const ftp = require("basic-ftp");
const dotenv = require("dotenv");
const path = require("path");

dotenv.config();

async function deploy() {
    const client = new ftp.Client();
    // client.ftp.verbose = true; // Uncomment ini jika ingin lihat log detail koneksi

    const host = process.env.FTP_HOST;
    const user = process.env.FTP_USER;
    const password = process.env.FTP_PASS;
    const remoteDir = process.env.FTP_REMOTE_DIR || "edupath.co.id"; 

    if (!host || !user || !password) {
        console.error("\x1b[31m[ERROR]\x1b[0m Kredensial FTP di file .env belum lengkap!");
        console.error("Pastikan Anda sudah mengisi variabel berikut di file .env Anda:");
        console.error("FTP_HOST=ftp.domainanda.com (atau IP)");
        console.error("FTP_USER=username_cpanel_anda");
        console.error("FTP_PASS=password_anda");
        console.error("FTP_REMOTE_DIR=edupath.co.id (opsional, sesuaikan nama folder webnya)");
        process.exit(1);
    }

    try {
        console.log(`\x1b[36m[1/4]\x1b[0m Menghubungkan ke server FTP ${host}...`);
        await client.access({
            host: host,
            user: user,
            password: password,
            secure: false
        });
        
        console.log(`\x1b[32m[OK]\x1b[0m Berhasil terhubung! Membuka folder /${remoteDir}...`);
        
        // Pindah ke folder remote
        await client.ensureDir(remoteDir);
        await client.cd("/" + remoteDir);

        console.log(`\x1b[36m[2/4]\x1b[0m Mengunggah Frontend (isi folder dist)...`);
        await client.uploadFromDir(path.join(__dirname, "dist"));

        console.log(`\x1b[36m[3/4]\x1b[0m Mengunggah Backend (folder api)...`);
        await client.ensureDir("api");
        await client.uploadFromDir(path.join(__dirname, "api"), "api");

        console.log(`\x1b[36m[4/4]\x1b[0m Mengunggah file konfigurasi tambahan...`);
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

        for (const file of filesToUpload) {
            try {
                await client.uploadFrom(path.join(__dirname, file), file);
                console.log(`  -> ${file} terunggah.`);
            } catch (err) {
                // Abaikan jika file tidak ada
            }
        }

        console.log("\x1b[32m===================================================\x1b[0m");
        console.log("\x1b[32m🚀 DEPLOYMENT FTP SELESAI DENGAN SUKSES!\x1b[0m");
        console.log("\x1b[32m===================================================\x1b[0m");
    }
    catch (err) {
        console.error("\x1b[31m[ERROR]\x1b[0m Terjadi kesalahan saat upload FTP:");
        console.error(err);
    }
    client.close();
}

deploy();
