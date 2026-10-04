const ftp = require('basic-ftp');
const dotenv = require('dotenv');
dotenv.config();
async function run() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: process.env.FTP_HOST,
            user: process.env.FTP_USER,
            password: process.env.FTP_PASS,
            secure: true,
            secureOptions: { rejectUnauthorized: false }
        });
        await client.cd('/edupath.co.id/api');
        console.log('List before:');
        console.log((await client.list()).map(i => i.name).join(', '));
        
        await client.uploadFrom('api/run_uat.php', 'run_uat.php');
        
        console.log('List after:');
        console.log((await client.list()).map(i => i.name).join(', '));
        
    } catch(err) {
        console.error(err);
    }
    client.close();
}
run();
