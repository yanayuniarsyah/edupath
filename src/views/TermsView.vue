<template>
  <div class="min-h-screen bg-[#070b14] text-white py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-body">
    <!-- Ambient Glows -->
    <div class="fixed top-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-purple-600/15 blur-[120px] pointer-events-none z-0"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[600px] h-[600px] rounded-full bg-emerald-500/15 blur-[130px] pointer-events-none z-0"></div>

    <div class="max-w-4xl mx-auto relative z-10">
      <!-- Header Bar -->
      <div class="flex items-center justify-between pb-6 mb-8 border-b border-white/10">
        <router-link to="/" class="flex items-center gap-2.5 group select-none">
          <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20">
            E
          </div>
          <span class="font-heading font-black text-2xl tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
        </router-link>
        <router-link 
          to="/" 
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sm font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-all"
        >
          <i class="ph-bold ph-arrow-left"></i>
          Kembali ke Beranda
        </router-link>
      </div>

      <!-- Hero Header -->
      <div class="mb-10">
        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 mb-3">
          Legal & Compliance
        </span>
        <h1 class="text-3xl sm:text-4xl font-extrabold font-heading text-white tracking-tight mb-3">
          Syarat dan Ketentuan
        </h1>
        <p class="text-sm text-slate-400 flex items-center gap-2">
          <span><i class="ph-bold ph-buildings text-emerald-400"></i> EduPath Platform Pembelajaran Digital</span>
          <span>•</span>
          <span><i class="ph-bold ph-calendar-blank"></i> Terakhir diperbarui: 1 Oktober 2026</span>
        </p>
      </div>

      <!-- Main Document Card -->
      <div class="glass-card bg-slate-900/75 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-10 shadow-2xl space-y-8 text-slate-300 text-sm sm:text-base leading-relaxed">
        
        <div class="p-4 rounded-xl bg-white/[0.03] border-l-4 border-emerald-500 text-slate-200">
          <p class="font-bold text-white mb-1">Selamat datang di EduPath.</p>
          <p>Syarat dan Ketentuan ini mengatur penggunaan situs, akun, fitur pembelajaran, paket belajar, tryout, materi digital, dan layanan lain yang disediakan oleh EduPath.</p>
          <p class="mt-2 text-xs text-slate-400">Dengan membuat akun, melakukan pembelian, atau menggunakan layanan EduPath, Anda menyatakan telah membaca, memahami, dan menyetujui Syarat dan Ketentuan ini.</p>
        </div>

        <!-- 1. DEFINISI -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">1</span>
            DEFINISI
          </h2>
          <ul class="list-disc pl-5 space-y-1.5 text-slate-400">
            <li><strong class="text-white">EduPath:</strong> Platform pembelajaran digital yang menyediakan layanan pendampingan belajar, latihan, tryout, analitik pembelajaran, materi digital, dan fitur terkait persiapan seleksi perguruan tinggi.</li>
            <li><strong class="text-white">Pengguna:</strong> Setiap orang yang membuat akun atau menggunakan layanan EduPath.</li>
            <li><strong class="text-white">Pembayar:</strong> Orang yang melakukan pembayaran atas paket atau layanan EduPath (Pengguna sendiri atau orang tua/wali).</li>
            <li><strong class="text-white">Paket:</strong> Produk atau layanan EduPath yang dapat dibeli oleh Pengguna atau Pembayar (berlangganan maupun paket masa akses tertentu).</li>
            <li><strong class="text-white">Konten:</strong> Seluruh materi, soal, pembahasan, video, teks, gambar, desain, data, fitur, perangkat lunak, dan materi pembelajaran lain di EduPath.</li>
          </ul>
        </section>

        <!-- 2. TENTANG EDUPATH -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">2</span>
            TENTANG EDUPATH
          </h2>
          <p class="text-slate-400">EduPath merupakan platform pembelajaran digital yang ditujukan untuk membantu siswa dalam proses belajar dan persiapan menghadapi seleksi perguruan tinggi melalui: latihan soal, tryout dan simulasi, materi pembelajaran, pembahasan, analitik hasil belajar, rekomendasi belajar berbasis AI, serta fitur-fitur inovatif lainnya.</p>
        </section>

        <!-- 3. PENDAFTARAN DAN AKUN -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">3</span>
            PENDAFTARAN DAN AKUN
          </h2>
          <ol class="list-decimal pl-5 space-y-1.5 text-slate-400">
            <li>Pengguna wajib memberikan informasi yang benar, akurat, dan dapat digunakan untuk keperluan layanan.</li>
            <li>Pengguna bertanggung jawab penuh menjaga kerahasiaan kata sandi, keamanan perangkat, dan seluruh aktivitas akun.</li>
            <li>Satu akun diperuntukkan bagi Pengguna yang terdaftar dan tidak boleh diperjualbelikan, dipindahtangankan, atau digunakan bersama secara tidak sah.</li>
          </ol>
        </section>

        <!-- 4. PENGGUNA DAN PEMBAYAR -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">4</span>
            PENGGUNA DAN PEMBAYAR
          </h2>
          <p class="text-slate-400">EduPath memungkinkan pembayaran dilakukan oleh pihak yang berbeda dari Pengguna (misal: orang tua atau wali untuk siswa). Pembayar menyatakan memiliki kewenangan sah dalam melakukan transaksi tersebut. Hak akses diberikan kepada akun yang ditentukan saat transaksi dan tidak dapat dipindahtangankan secara sepihak.</p>
        </section>

        <!-- 5. PAKET DAN MASA BERLAKU -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">5</span>
            PAKET DAN MASA BERLAKU
          </h2>
          <p class="text-slate-400">Setiap paket memiliki cakupan fitur dan masa akses sebagaimana ditampilkan pada halaman rincian produk saat transaksi dilakukan. Pembelian paket tidak memberikan hak akses permanen terhadap seluruh layanan kecuali dinyatakan secara khusus.</p>
        </section>

        <!-- 6. HARGA DAN PEMBAYARAN -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">6</span>
            HARGA DAN PEMBAYARAN
          </h2>
          <p class="text-slate-400">Harga yang berlaku adalah harga yang ditampilkan saat transaksi. Pembayaran diproses secara aman melalui penyedia layanan pembayaran resmi berizin (Payment Gateway). Perubahan harga yang dilakukan kemudian hari tidak berlaku surut terhadap transaksi yang telah berhasil.</p>
        </section>

        <!-- 7. KONFIRMASI PEMBAYARAN DAN AKTIVASI -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">7</span>
            KONFIRMASI PEMBAYARAN DAN AKTIVASI
          </h2>
          <p class="text-slate-400">Pembayaran dianggap berhasil setelah status dikonfirmasi oleh sistem EduPath dari penyedia pembayaran, dan hak akses paket langsung diberikan secara otomatis. Pengguna dapat menghubungi saluran bantuan bila mendapati kendala aktivasi.</p>
        </section>

        <!-- 8. PENGGUNAAN LAYANAN -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">8</span>
            PENGGUNAAN LAYANAN
          </h2>
          <p class="text-slate-400">Dilarang keras menyalin, merekam, mengunduh, mendistribusikan, menjual kembali Konten, melakukan web scraping/crawling otomatis, atau mencoba menembus mekanisme keamanan dan pembatasan akses sistem.</p>
        </section>

        <!-- 9. HAK KEKAYAAN INTELEKTUAL -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">9</span>
            HAK KEKAYAAN INTELEKTUAL
          </h2>
          <p class="text-slate-400">Seluruh bank soal, modul, pembahasan, teknologi AI, desain, dan merek dagang adalah hak milik eksklusif EduPath dan dilindungi oleh undang-undang kekayaan intelektual Republik Indonesia.</p>
        </section>

        <!-- 10. FITUR ANALITIK, SIMULASI, DAN AI -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">10</span>
            FITUR ANALITIK, SIMULASI, DAN AI
          </h2>
          <div class="p-4 rounded-xl bg-[#c0ff00]/5 border border-[#c0ff00]/20 text-slate-300">
            <p class="font-bold text-[#c0ff00] mb-1">Batasan Hasil Analisis AI:</p>
            <p class="text-xs sm:text-sm">Estimasi skor, simulasi tryout berbasis IRT, dan peluang kelulusan PTN adalah instrumen analitik bantu belajar. Hasil tersebut <strong>bukan merupakan hasil resmi penyelenggara seleksi, bukan jaminan nilai ujian, dan tidak menjamin kelulusan pada UTBK/SNPMB</strong> resmi.</p>
          </div>
        </section>

        <!-- 11. PERUBAHAN LAYANAN -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">11</span>
            PERUBAHAN LAYANAN
          </h2>
          <p class="text-slate-400">EduPath berhak memperbarui atau meningkatkan fitur dan materi sewaktu-waktu demi penyempurnaan kualitas layanan.</p>
        </section>

        <!-- 12. PEMBATALAN DAN PENGEMBALIAN DANA -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">12</span>
            PEMBATALAN DAN PENGEMBALIAN DANA (REFUND)
          </h2>
          <p class="text-slate-400">Karena layanan EduPath berupa produk digital dengan akses seketika, <strong>seluruh transaksi pada dasarnya bersifat final dan non-refundable</strong>. Pengembalian dana hanya dipertimbangkan untuk kejadian khusus seperti pembayaran ganda (double charge), kesalahan sistem pembayaran, atau gangguan server berkepanjangan.</p>
        </section>

        <!-- 13 - 20 (Ringkasan Ketentuan Operasional) -->
        <section class="space-y-3">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">13-20</span>
            KETENTUAN OPERASIONAL, PRIVASI & HUKUM
          </h2>
          <ul class="list-disc pl-5 space-y-2 text-slate-400">
            <li><strong>Gangguan Layanan & Tanggung Jawab:</strong> EduPath berusaha menjaga ketersediaan layanan 24/7 dan tidak bertanggung jawab atas gangguan di luar kendali wajar (koneksi internet pengguna, pihak ketiga, atau force majeure).</li>
            <li><strong>Penangguhan Akun:</strong> Pelanggaran terhadap integritas sistem atau penyalahgunaan akun dapat berakibat pada penangguhan akses akun secara sepihak.</li>
            <li><strong>Privasi Data:</strong> Data Pengguna dijaga kerahasiaannya dan tidak dijual kepada pihak luar.</li>
            <li><strong>Hukum yang Berlaku:</strong> Ketentuan ini tunduk pada hukum Negara Republik Indonesia.</li>
          </ul>
        </section>

        <!-- 21. KONTAK -->
        <section class="space-y-3 pt-4 border-t border-white/10">
          <h2 class="text-xl font-bold font-heading text-white flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black inline-flex items-center justify-center">21</span>
            KONTAK DAN BANTUAN
          </h2>
          <p class="text-slate-400">Pertanyaan atau kendala seputar Syarat dan Ketentuan dapat dikirimkan ke:</p>
          <div class="flex flex-col sm:flex-row gap-4 pt-2">
            <div class="p-3 rounded-xl bg-white/5 border border-white/10 flex items-center gap-3">
              <i class="ph-bold ph-envelope text-emerald-400 text-xl"></i>
              <span class="text-white font-medium">admin@edupath.co.id</span>
            </div>
            <div class="p-3 rounded-xl bg-white/5 border border-white/10 flex items-center gap-3">
              <i class="ph-bold ph-globe text-emerald-400 text-xl"></i>
              <span class="text-white font-medium">https://edupath.co.id</span>
            </div>
          </div>
        </section>

        <!-- 22. PERSETUJUAN -->
        <section class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-center">
          <p class="text-emerald-300 font-medium">
            Dengan mendaftar akun atau membeli paket di EduPath, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh isi Syarat dan Ketentuan ini.
          </p>
        </section>

      </div>

      <!-- Footer Note -->
      <div class="mt-8 text-center text-xs text-slate-500">
        &copy; 2026 EduPath. Seluruh hak cipta dilindungi undang-undang.
      </div>
    </div>
  </div>
</template>

<script setup>
// View Terms and Conditions
</script>
