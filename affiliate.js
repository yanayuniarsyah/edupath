function openRegisterModal() {
    document.getElementById('register-modal').classList.remove('hidden');
}

function closeRegisterModal() {
    document.getElementById('register-modal').classList.add('hidden');
}

function openUnifiedLoginModal() {
    document.getElementById('login-modal').classList.remove('hidden');
}

function closeUnifiedLoginModal() {
    document.getElementById('login-modal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-register-submit');
            const originalText = btn.textContent;
            btn.textContent = 'Memproses...';
            btn.disabled = true;

            const formData = new FormData(registerForm);
            const data = Object.fromEntries(formData.entries());

            try {
                // 1. Daftar sebagai student
                const regRes = await fetch('/api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: data.name,
                        email: data.email,
                        password: data.password,
                        whatsapp: data.whatsapp,
                        target_ptn: 'Affiliate Partner'
                    })
                });

                const regData = await regRes.json();
                if (!regRes.ok) {
                    throw new Error(regData.error || 'Gagal mendaftar');
                }

                // 2. Jika sukses, token didapat. Gabung program affiliate.
                const token = regData.token;
                
                const joinRes = await fetch('/api/affiliate.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        bank_name: data.bank_name,
                        bank_account: data.bank_account,
                        bank_owner: data.bank_owner
                    })
                });

                if (!joinRes.ok) {
                    console.error("Gagal join afiliasi otomatis, bisa diabaikan atau ditangani di dashboard");
                }

                // Simpan token ke localStorage
                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(regData.user));

                alert('Pendaftaran berhasil! Anda akan diarahkan ke Dashboard Affiliate.');
                window.location.href = 'affiliate_dashboard.html';

            } catch (err) {
                alert('Pendaftaran gagal: ' + err.message);
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-login-submit');
            const originalText = btn.textContent;
            btn.textContent = 'Memproses...';
            btn.disabled = true;

            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('/api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: data.email,
                        password: data.password
                    })
                });

                const resData = await res.json();
                if (!res.ok) {
                    throw new Error(resData.message || resData.error || 'Gagal login');
                }

                localStorage.setItem('token', resData.token);
                localStorage.setItem('user', JSON.stringify(resData.user));

                window.location.href = 'affiliate_dashboard.html';

            } catch (err) {
                alert('Login gagal: ' + err.message);
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }
});
