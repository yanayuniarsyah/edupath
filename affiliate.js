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

function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
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
                        telegram: data.telegram,
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
                        name: data.name,
                        bank_name: data.bank_name,
                        bank_account: data.bank_account,
                        bank_owner: data.bank_owner || data.name
                    })
                });

                let finalToken = token;
                if (joinRes.ok) {
                    try {
                        const joinData = await joinRes.json();
                        if (joinData.token) finalToken = joinData.token;
                    } catch (e) {}
                }

                // Simpan token ke localStorage & sessionStorage (untuk kompatibilitas)
                localStorage.setItem('auth_token', finalToken);
                localStorage.setItem('token', finalToken);
                sessionStorage.setItem('ep_session_token', finalToken);
                sessionStorage.setItem('admin_token', finalToken);
                sessionStorage.setItem('admin_role', 'affiliate');
                localStorage.setItem('user_role', 'affiliate');
                localStorage.setItem('user', JSON.stringify({ ...regData.user, role: 'affiliate' }));

                // Tampilkan pesan sukses dan delay redirect
                const btn = document.getElementById('btn-register-submit');
                btn.textContent = 'Berhasil! Mengalihkan...';
                btn.classList.add('bg-green-500');
                
                // Coba gunakan fungsi showToast jika ada di halaman, jika tidak gunakan alert
                if (typeof showToast === 'function') {
                    showToast('Pendaftaran berhasil! Anda akan diarahkan ke Dashboard Affiliate.');
                } else {
                    alert('Pendaftaran berhasil! Anda akan diarahkan ke Dashboard Affiliate.');
                }

                setTimeout(() => {
                    window.location.href = '/#/admin';
                }, 1200);

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

                localStorage.setItem('auth_token', resData.token);
                localStorage.setItem('token', resData.token);
                sessionStorage.setItem('ep_session_token', resData.token);
                sessionStorage.setItem('admin_token', resData.token);
                sessionStorage.setItem('admin_role', 'affiliate');
                localStorage.setItem('user_role', 'affiliate');
                localStorage.setItem('user', JSON.stringify(resData.user));

                window.location.href = '/#/admin';

            } catch (err) {
                alert('Login gagal: ' + err.message);
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }
});
