document.addEventListener('DOMContentLoaded', () => {
    // Cek status login
    const token = localStorage.getItem('token');
    const userString = localStorage.getItem('user');
    
    if (!token || !userString) {
        // Jika tidak ada sesi, arahkan ke index/login (di sini disesuaikan)
        // window.location.href = 'index.html';
        console.warn("Tidak ada token ditemukan. Anda mungkin perlu login.");
        document.getElementById('user-name').textContent = "Guest Mode";
        initDashboardWithDummyData();
    } else {
        try {
            const user = JSON.parse(userString);
            document.getElementById('user-name').textContent = user.name || "Siswa";
            fetchAffiliateData(token);
        } catch (e) {
            console.error("Gagal parsing user info", e);
            initDashboardWithDummyData();
        }
    }
});

function switchTab(tabId, element) {
    // Hapus kelas aktif dari semua tombol tab
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // Sembunyikan semua konten tab
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.classList.remove('active');
    });

    // Tambahkan kelas aktif pada tombol yang diklik
    element.classList.add('active');

    // Tampilkan konten tab yang sesuai
    const activeContent = document.getElementById(tabId);
    if (activeContent) {
        activeContent.classList.add('active');
    }
}

function copyReferralLink() {
    const linkInput = document.getElementById('referral-link');
    if (!linkInput.value || linkInput.value === 'Memuat link...') return;
    
    // Select the text field
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text inside the text field
    navigator.clipboard.writeText(linkInput.value).then(() => {
        showToast("Link berhasil disalin!");
    }).catch(err => {
        console.error('Gagal menyalin text: ', err);
    });
}

function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toast-message');
    
    toastMsg.textContent = message;
    
    // Show toast
    toast.classList.remove('translate-y-20', 'opacity-0');
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 3000);
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(number);
}

async function fetchAffiliateData(token) {
    try {
        const response = await fetch('/api/affiliate.php', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.status === 'joined') {
            updateDashboardUI(data);
        } else if (data.status === 'not_joined') {
            // Jika belum join, mungkin tampilkan modal join atau daftar otomatis
            console.warn("User belum mendaftar afiliasi.");
            // Otomatis join untuk testing, bisa dihapus di prod
            joinAffiliate(token);
        }
        
    } catch (error) {
        console.error("Gagal mengambil data afiliasi:", error);
        // Fallback to dummy data for demonstration
        initDashboardWithDummyData();
    }
}

async function joinAffiliate(token) {
    try {
        const response = await fetch('/api/affiliate.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        const data = await response.json();
        if (data.success) {
            // Berhasil join, reload data
            fetchAffiliateData(token);
        }
    } catch (e) {
        console.error("Gagal join afiliasi", e);
    }
}

function updateDashboardUI(data) {
    // Update Stats
    document.getElementById('stat-total-referrals').textContent = data.stats.total_referrals;
    // Asumsi rujukan aktif = total rujukan di API saat ini (karena blm ada kolom active di db)
    document.getElementById('stat-active-referrals').textContent = data.stats.total_referrals;
    document.getElementById('stat-pending-commission').textContent = formatRupiah(data.stats.total_pending);
    document.getElementById('stat-paid-commission').textContent = formatRupiah(data.stats.total_paid);

    // Update Links & Code
    const baseUrl = window.location.origin;
    const refCode = data.referral_code || "KODE-ERROR";
    const refLink = `${baseUrl}/register.html?ref=${refCode}`; // Sesuaikan dengan route register yang ada
    
    document.getElementById('referral-link').value = refLink;
    document.getElementById('referral-code').textContent = refCode;

    // Update Riwayat Komisi
    const tbodyKomisi = document.getElementById('commissions-table-body');
    if (data.history && data.history.length > 0) {
        tbodyKomisi.innerHTML = '';
        data.history.forEach(item => {
            const date = new Date(item.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
            let statusBadge = '';
            if (item.status === 'paid') {
                statusBadge = '<span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-md">Berhasil</span>';
            } else if (item.status === 'pending') {
                statusBadge = '<span class="px-2 py-1 bg-amber-500/20 text-amber-400 text-xs rounded-md">Pending</span>';
            } else {
                statusBadge = `<span class="px-2 py-1 bg-slate-500/20 text-slate-400 text-xs rounded-md">${item.status}</span>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-3 px-4">${date}</td>
                <td class="py-3 px-4">Komisi Referral</td>
                <td class="py-3 px-4 text-right font-medium text-emerald-400">+${formatRupiah(item.amount)}</td>
                <td class="py-3 px-4 text-center">${statusBadge}</td>
            `;
            tbodyKomisi.appendChild(tr);
        });
    } else {
        tbodyKomisi.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-slate-500">Belum ada riwayat komisi.</td></tr>`;
    }

    // Untuk tabel rujukan, api saat ini belum mengembalikan list user yg dirujuk, kita beri dummy jika kosong
    renderDummyReferrals();
}

function initDashboardWithDummyData() {
    // Mode demo jika backend tidak tersedia atau tidak ada sesi
    document.getElementById('stat-total-referrals').textContent = "12";
    document.getElementById('stat-active-referrals').textContent = "8";
    document.getElementById('stat-pending-commission').textContent = "Rp 150.000";
    document.getElementById('stat-paid-commission').textContent = "Rp 600.000";
    
    document.getElementById('referral-link').value = "https://edupath.biz.id/register?ref=EP-2026-X";
    document.getElementById('referral-code').textContent = "EP-2026-X";

    renderDummyReferrals();

    const tbodyKomisi = document.getElementById('commissions-table-body');
    tbodyKomisi.innerHTML = `
        <tr>
            <td class="py-3 px-4 text-white">01 Sep 2026</td>
            <td class="py-3 px-4">Pencairan Komisi Agustus</td>
            <td class="py-3 px-4 text-right font-medium text-white">Rp 450.000</td>
            <td class="py-3 px-4 text-center"><span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-md">Berhasil</span></td>
        </tr>
        <tr>
            <td class="py-3 px-4 text-white">01 Okt 2026</td>
            <td class="py-3 px-4">Pencairan Komisi September</td>
            <td class="py-3 px-4 text-right font-medium text-white">Rp 150.000</td>
            <td class="py-3 px-4 text-center"><span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-md">Berhasil</span></td>
        </tr>
    `;
}

function renderDummyReferrals() {
    const tbody = document.getElementById('referrals-table-body');
    tbody.innerHTML = `
        <tr class="hover:bg-slate-800/30 transition-colors">
            <td class="py-3 px-4 text-slate-300">10 Okt 2026</td>
            <td class="py-3 px-4 font-semibold text-white">Budi Santoso</td>
            <td class="py-3 px-4 text-slate-400">SMAN 1 Jakarta</td>
            <td class="py-3 px-4 text-sky-400">Paket Mandiri</td>
            <td class="py-3 px-4 text-center"><span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-medium rounded-full">Aktif</span></td>
        </tr>
        <tr class="hover:bg-slate-800/30 transition-colors">
            <td class="py-3 px-4 text-slate-300">12 Okt 2026</td>
            <td class="py-3 px-4 font-semibold text-white">Siti Aminah</td>
            <td class="py-3 px-4 text-slate-400">SMAN 3 Bandung</td>
            <td class="py-3 px-4 text-purple-400">Paket Utama</td>
            <td class="py-3 px-4 text-center"><span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-medium rounded-full">Aktif</span></td>
        </tr>
        <tr class="hover:bg-slate-800/30 transition-colors">
            <td class="py-3 px-4 text-slate-300">15 Okt 2026</td>
            <td class="py-3 px-4 font-semibold text-white">Andi Wijaya</td>
            <td class="py-3 px-4 text-slate-400">SMAN 5 Surabaya</td>
            <td class="py-3 px-4 text-slate-500">Starter Pass</td>
            <td class="py-3 px-4 text-center"><span class="px-2.5 py-1 bg-rose-500/10 text-rose-400 border border-rose-500/20 text-xs font-medium rounded-full">Nonaktif</span></td>
        </tr>
    `;
}
