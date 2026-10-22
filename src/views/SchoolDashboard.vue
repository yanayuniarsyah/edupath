<template>
  <div class="min-h-screen text-white relative overflow-x-hidden" style="background: linear-gradient(145deg, #050a18 0%, #0a1128 50%, #050a18 100%);">
    <!-- Ambient Glows -->
    <div class="fixed top-[-10%] left-[-10%] w-[600px] h-[600px] rounded-full opacity-20 blur-[130px] pointer-events-none" style="background: radial-gradient(circle, #c0ff00 0%, transparent 70%);"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[600px] h-[600px] rounded-full opacity-15 blur-[130px] pointer-events-none" style="background: radial-gradient(circle, #3b82f6 0%, transparent 70%);"></div>

    <!-- ============================================================== -->
    <!-- GATE 1: UNAUTHENTICATED (LOGIN / REGISTRATION SEKOLAH & BK)    -->
    <!-- ============================================================== -->
    <div v-if="!isAuthenticated" class="min-h-screen flex flex-col justify-between p-4 sm:p-6 md:p-10 relative z-10">
      <!-- Navbar Brand -->
      <div class="max-w-6xl w-full mx-auto flex items-center justify-between pb-6 border-b border-white/10">
        <a href="#/" class="flex items-center gap-2.5 group">
          <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-[0_0_20px_rgba(192,255,0,0.35)] group-hover:scale-105 transition-transform">E</div>
          <span class="font-black text-2xl tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
        </a>
        <div class="flex items-center gap-3">
          <a href="#/" class="px-4 py-2 rounded-xl text-xs font-bold text-white/70 hover:text-white hover:bg-white/5 transition-all">
            &larr; Halaman Siswa
          </a>
          <a href="#/admin" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-amber-400 bg-amber-400/10 border border-amber-400/20 hover:bg-amber-400/20 transition-all">
            Admin Panel &rarr;
          </a>
        </div>
      </div>

      <!-- Main Container: Hero & Auth Card -->
      <div class="max-w-6xl w-full mx-auto my-auto py-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        <!-- Left: Value Proposition for Guru BK -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-black uppercase tracking-wider">
            <i class="ph-bold ph-chalkboard-teacher text-sm"></i>
            Portal Khusus Sekolah &amp; Guru BK
          </div>
          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white leading-tight">
            Pantau Kesiapan SNBT &amp; Minat Bakat Siswa <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#c0ff00] via-emerald-300 to-teal-400">Secara Objektif</span>
          </h1>
          <p class="text-white/70 text-sm sm:text-base leading-relaxed max-w-xl">
            Bantu Guru BK memetakan minat jurusan, pola belajar, paket bimbingan yang diikuti, dan area penguatan ratusan siswa dalam satu dashboard analitik agregat yang terpusat dan instan.
          </p>

          <!-- 4 Core Features -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md hover:border-[#c0ff00]/30 transition-all">
              <div class="w-8 h-8 rounded-lg bg-[#c0ff00]/20 text-[#c0ff00] flex items-center justify-center mb-2.5">
                <i class="ph-bold ph-users-three text-lg"></i>
              </div>
              <h3 class="text-xs font-black uppercase tracking-wider text-white">Monitoring Paket Belajar</h3>
              <p class="text-[11px] text-white/60 mt-1">Lacak status siswa yang mengikuti Paket Pejuang PTN, Mandiri, Elite Mentoring, atau Free SPP.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md hover:border-sky-400/30 transition-all">
              <div class="w-8 h-8 rounded-lg bg-sky-400/20 text-sky-400 flex items-center justify-center mb-2.5">
                <i class="ph-bold ph-compass text-lg"></i>
              </div>
              <h3 class="text-xs font-black uppercase tracking-wider text-white">RIASEC Holland Code</h3>
              <p class="text-[11px] text-white/60 mt-1">Distribusi minat jurusan siswa berdasarkan riset psikometri ilmiah 6 tipe kepribadian.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md hover:border-purple-400/30 transition-all">
              <div class="w-8 h-8 rounded-lg bg-purple-400/20 text-purple-400 flex items-center justify-center mb-2.5">
                <i class="ph-bold ph-chart-bar text-lg"></i>
              </div>
              <h3 class="text-xs font-black uppercase tracking-wider text-white">Rapor Tryout CBT Agregat</h3>
              <p class="text-[11px] text-white/60 mt-1">Rata-rata skor SNBT sekolah, subtes paling menantang, dan peluang lolos PTN.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md hover:border-emerald-400/30 transition-all">
              <div class="w-8 h-8 rounded-lg bg-emerald-400/20 text-emerald-400 flex items-center justify-center mb-2.5">
                <i class="ph-bold ph-qr-code text-lg"></i>
              </div>
              <h3 class="text-xs font-black uppercase tracking-wider text-white">QR Code &amp; Link Sekolah</h3>
              <p class="text-[11px] text-white/60 mt-1">Cukup sebar 1 tautan khusus, siswa otomatis terdata ke dalam dashboard sekolah Anda.</p>
            </div>
          </div>
        </div>

        <!-- Right: Auth Box (Login / Register Tabs) -->
        <div class="lg:col-span-5">
          <div class="p-6 sm:p-8 rounded-3xl bg-white/[0.04] border border-white/10 backdrop-blur-2xl shadow-2xl relative">
            <!-- Tab Switcher -->
            <div class="flex p-1 rounded-2xl bg-black/40 border border-white/10 mb-6">
              <button
                @click="authTab = 'login'; authError = '';"
                :class="['flex-1 py-2 text-xs font-black rounded-xl transition-all', authTab === 'login' ? 'bg-[#c0ff00] text-black shadow-lg shadow-[#c0ff00]/20' : 'text-white/60 hover:text-white']"
              >
                Masuk Guru BK
              </button>
              <button
                @click="authTab = 'register'; authError = '';"
                :class="['flex-1 py-2 text-xs font-black rounded-xl transition-all', authTab === 'register' ? 'bg-[#c0ff00] text-black shadow-lg shadow-[#c0ff00]/20' : 'text-white/60 hover:text-white']"
              >
                Daftar Sekolah Baru
              </button>
            </div>

            <!-- Error Banner -->
            <div v-if="authError" class="mb-4 p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs font-bold flex items-center gap-2">
              <i class="ph-bold ph-warning-circle text-base shrink-0"></i>
              <span>{{ authError }}</span>
            </div>

            <!-- Success Banner -->
            <div v-if="authSuccess" class="mb-4 p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center gap-2">
              <i class="ph-bold ph-check-circle text-base shrink-0"></i>
              <span>{{ authSuccess }}</span>
            </div>

            <!-- FORM 1: LOGIN GURU BK -->
            <form v-if="authTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">
              <div>
                <label class="block text-[11px] font-black uppercase tracking-wider text-white/60 mb-1.5">Email Guru BK / Sekolah</label>
                <div class="relative">
                  <i class="ph-bold ph-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40"></i>
                  <input
                    v-model="loginForm.email"
                    type="email"
                    required
                    placeholder="bk.sman1@sekolah.sch.id"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="block text-[11px] font-black uppercase tracking-wider text-white/60">Password</label>
                </div>
                <div class="relative">
                  <i class="ph-bold ph-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40"></i>
                  <input
                    v-model="loginForm.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                    class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                  <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-white/40 hover:text-white">
                    <i :class="['ph-bold', showPassword ? 'ph-eye-slash' : 'ph-eye']"></i>
                  </button>
                </div>
              </div>

              <button
                type="submit"
                :disabled="authLoading"
                class="w-full py-3 rounded-xl bg-[#c0ff00] hover:bg-[#b0f000] text-black font-black text-xs uppercase tracking-wider transition-all shadow-lg shadow-[#c0ff00]/20 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span v-if="authLoading" class="w-4 h-4 border-2 border-black border-t-transparent rounded-full animate-spin"></span>
                <span v-else>Masuk ke Dashboard Sekolah</span>
                <i v-if="!authLoading" class="ph-bold ph-arrow-right"></i>
              </button>

              <!-- Quick Demo Login Button for instant evaluation -->
              <div class="pt-3 border-t border-white/10 text-center">
                <span class="text-[10px] uppercase font-black text-white/40 tracking-wider block mb-2">Akses Cepat UAT / Demo Sekolah:</span>
                <button
                  type="button"
                  @click="quickDemoLogin('SMANSA Ciamis')"
                  class="w-full py-2 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-[11px] font-bold text-white/80 hover:text-[#c0ff00] transition-all flex items-center justify-center gap-2"
                >
                  <i class="ph-bold ph-buildings text-emerald-400"></i>
                  <span>Jelajahi Demo: SMAN 1 Ciamis</span>
                </button>
              </div>
            </form>

            <!-- FORM 2: REGISTRATION SEKOLAH & GURU BK -->
            <form v-else @submit.prevent="handleRegister" class="space-y-3.5">
              <div>
                <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">Nama Resmi Sekolah</label>
                <input
                  v-model="registerForm.name"
                  type="text"
                  required
                  placeholder="Contoh: SMAN 1 Ciamis"
                  class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                />
              </div>

              <div class="grid grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">NPSN (Opsional)</label>
                  <input
                    v-model="registerForm.npsn"
                    type="text"
                    placeholder="202xxxxx"
                    class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">Kota / Kabupaten</label>
                  <input
                    v-model="registerForm.city"
                    type="text"
                    required
                    placeholder="Kab. Ciamis"
                    class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">Nama Koordinator BK</label>
                  <input
                    v-model="registerForm.bk_name"
                    type="text"
                    required
                    placeholder="Dra. Siti Rahmah, M.Pd"
                    class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">No. WhatsApp BK</label>
                  <input
                    v-model="registerForm.phone"
                    type="tel"
                    required
                    placeholder="081234567890"
                    class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">Email Resmi Sekolah / BK</label>
                <input
                  v-model="registerForm.email"
                  type="email"
                  required
                  placeholder="bk.sman1ciamis@gmail.com"
                  class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                />
              </div>

              <div class="grid grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">Password</label>
                  <input
                    v-model="registerForm.password"
                    type="password"
                    required
                    placeholder="Min. 6 Karakter"
                    class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-wider text-white/60 mb-1">Estimasi Siswa Kls 12</label>
                  <input
                    v-model.number="registerForm.estimated_students"
                    type="number"
                    min="10"
                    placeholder="Contoh: 350"
                    class="w-full px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
                  />
                </div>
              </div>

              <button
                type="submit"
                :disabled="authLoading"
                class="w-full py-3 mt-2 rounded-xl bg-[#c0ff00] hover:bg-[#b0f000] text-black font-black text-xs uppercase tracking-wider transition-all shadow-lg shadow-[#c0ff00]/20 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span v-if="authLoading" class="w-4 h-4 border-2 border-black border-t-transparent rounded-full animate-spin"></span>
                <span v-else>Daftarkan Sekolah &amp; Dapatkan Link Siswa</span>
                <i v-if="!authLoading" class="ph-bold ph-sparkle"></i>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Footer Sub -->
      <div class="max-w-6xl w-full mx-auto text-center text-xs text-white/40 pt-6 border-t border-white/5">
        &copy; 2026 PT Kreasi Hasanah Indonesia &bull; EduPath Adaptive Learning Platform &bull; Portal Khusus Sekolah &amp; Guru BK
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- GATE 2: AUTHENTICATED SCHOOL DASHBOARD                         -->
    <!-- ============================================================== -->
    <div v-else class="min-h-screen flex flex-col relative z-10 pb-16">
      <!-- TOP NAV / EXECUTIVE HEADER BAR -->
      <header class="border-b border-white/10 bg-[#080d1d]/80 backdrop-blur-xl sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-4">
          <!-- Left: Brand & School Badge -->
          <div class="flex items-center gap-3">
            <a href="#/" class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-base shadow-[0_0_15px_rgba(192,255,0,0.3)]">E</div>
              <span class="font-black text-lg text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
            </a>
            <div class="h-5 w-[1px] bg-white/10 hidden sm:block"></div>
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
              <div>
                <span class="font-extrabold text-sm text-white block leading-tight">{{ schoolData?.name || 'Sekolah Mitra' }}</span>
                <span class="text-[10px] text-white/50 block font-semibold">Koordinator: {{ schoolData?.bk_name || 'Guru BK' }} &bull; {{ schoolData?.city || 'Indonesia' }}</span>
              </div>
            </div>
          </div>

          <!-- Right: Action Buttons -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              @click="openQrModal = true"
              class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/90 hover:text-white text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
              title="Tampilkan QR Code untuk disebar ke siswa"
            >
              <i class="ph-bold ph-qr-code text-[#c0ff00]"></i>
              <span>QR Code Siswa</span>
            </button>

            <button
              @click="copyShareLink"
              class="px-3 py-1.5 rounded-xl bg-[#c0ff00]/15 hover:bg-[#c0ff00]/25 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
              title="Salin link asesmen siswa"
            >
              <i :class="['ph-bold', copiedLink ? 'ph-check' : 'ph-link-simple']"></i>
              <span>{{ copiedLink ? 'Tersalin!' : 'Salin Link Siswa' }}</span>
            </button>

            <button
              @click="exportCsv"
              class="px-3 py-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
              title="Ekspor rekap siswa ke file CSV/Excel"
            >
              <i class="ph-bold ph-microsoft-excel-logo"></i>
              <span>Unduh CSV</span>
            </button>

            <button
              @click="loadAllDashboardData"
              class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 hover:text-white flex items-center justify-center transition-all cursor-pointer"
              title="Segarkan Data"
            >
              <i :class="['ph-bold ph-arrows-clockwise text-sm', dataLoading ? 'animate-spin text-[#c0ff00]' : '']"></i>
            </button>

            <button
              @click="handleLogout"
              class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-300 text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
              title="Keluar dari Portal BK"
            >
              <i class="ph-bold ph-sign-out"></i>
              <span class="hidden sm:inline">Keluar</span>
            </button>
          </div>
        </div>

        <!-- SUB NAV TABS -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center gap-2 overflow-x-auto no-scrollbar border-t border-white/5">
          <button
            @click="activeTab = 'analytics'"
            :class="['px-4 py-2.5 text-xs font-black uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 whitespace-nowrap', activeTab === 'analytics' ? 'border-[#c0ff00] text-[#c0ff00] bg-[#c0ff00]/5' : 'border-transparent text-white/60 hover:text-white']"
          >
            <i class="ph-bold ph-chart-donut text-sm"></i>
            Ringkasan &amp; Analitik Agregat
          </button>

          <button
            @click="activeTab = 'students'"
            :class="['px-4 py-2.5 text-xs font-black uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 whitespace-nowrap', activeTab === 'students' ? 'border-[#c0ff00] text-[#c0ff00] bg-[#c0ff00]/5' : 'border-transparent text-white/60 hover:text-white']"
          >
            <i class="ph-bold ph-student text-sm"></i>
            Daftar &amp; Status Siswa
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/10 text-white font-bold">{{ kpis?.total_students || 0 }}</span>
          </button>

          <button
            @click="activeTab = 'packages'"
            :class="['px-4 py-2.5 text-xs font-black uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 whitespace-nowrap', activeTab === 'packages' ? 'border-[#c0ff00] text-[#c0ff00] bg-[#c0ff00]/5' : 'border-transparent text-white/60 hover:text-white']"
          >
            <i class="ph-bold ph-package text-sm"></i>
            Informasi Paket EduPath
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-500/20 text-emerald-400 font-bold">{{ kpis?.paid_package_students || 0 }} Terdaftar</span>
          </button>

          <button
            @click="activeTab = 'broadcast'"
            :class="['px-4 py-2.5 text-xs font-black uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 whitespace-nowrap', activeTab === 'broadcast' ? 'border-[#c0ff00] text-[#c0ff00] bg-[#c0ff00]/5' : 'border-transparent text-white/60 hover:text-white']"
          >
            <i class="ph-bold ph-megaphone-simple text-sm"></i>
            Kit Sosialisasi &amp; WhatsApp
          </button>
        </div>
      </header>

      <!-- MAIN CONTENT WRAPPER -->
      <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 pt-6 flex-grow">
        <!-- ============================================================== -->
        <!-- TAB 1: ANALYTICS & AGGREGATE DASHBOARD                         -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'analytics'" class="space-y-6">
          <!-- 1. EXECUTIVE KPIS (6 CARDS) -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <!-- KPI 1: Total Siswa -->
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Total Siswa</span>
              <div class="text-2xl font-black text-white mt-1">{{ kpis?.total_students || 0 }}</div>
              <span class="text-[10px] text-emerald-400 flex items-center gap-1 mt-1 font-bold">
                <i class="ph-bold ph-user-check"></i> Terdata
              </span>
            </div>

            <!-- KPI 2: Siswa Paket Bimbel (Paid) -->
            <div class="p-4 rounded-2xl bg-gradient-to-b from-emerald-500/10 to-transparent border border-emerald-500/30 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400 block">Siswa Ikut Bimbel</span>
              <div class="text-2xl font-black text-[#c0ff00] mt-1">{{ kpis?.paid_package_students || 0 }}</div>
              <span class="text-[10px] text-emerald-300 font-semibold mt-1 block">Paket EduPath Aktif</span>
            </div>

            <!-- KPI 3: Siswa Free SPP -->
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Siswa Free SPP</span>
              <div class="text-2xl font-black text-white mt-1">{{ kpis?.free_diagnostic_students || 0 }}</div>
              <span class="text-[10px] text-white/40 font-semibold mt-1 block">Diagnostik Mandiri</span>
            </div>

            <!-- KPI 4: Peserta Tryout -->
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Peserta Tryout</span>
              <div class="text-2xl font-black text-sky-400 mt-1">{{ kpis?.tryout_participants || 0 }}</div>
              <span class="text-[10px] text-sky-300 font-semibold mt-1 block">Simulasi CBT SNBT</span>
            </div>

            <!-- KPI 5: Rata-rata Skor -->
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Rata-rata Skor</span>
              <div class="text-2xl font-black text-amber-400 mt-1">{{ kpis?.average_tryout_score || 0 }}</div>
              <span class="text-[10px] text-amber-300 font-semibold mt-1 block">Skala SNBT 0 - 1000</span>
            </div>

            <!-- KPI 6: Selesai SPP -->
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Selesai SPP</span>
              <div class="text-2xl font-black text-purple-400 mt-1">{{ kpis?.spp_completed_students || 0 }}</div>
              <span class="text-[10px] text-purple-300 font-semibold mt-1 block">Rapor Minat Lengkap</span>
            </div>
          </div>

          <!-- 2. STUDENT PACKAGE BREAKDOWN (INFORMASI PROGRAM/PAKET SISWA) -->
          <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
              <div>
                <div class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-[#c0ff00]">
                  <i class="ph-bold ph-package"></i>
                  Status Partisipasi Program Belajar EduPath
                </div>
                <h2 class="text-lg font-black text-white mt-1">Distribusi Siswa Menurut Paket Bimbingan</h2>
                <p class="text-xs text-white/60">Data real-time siswa sekolah yang mengambil program intensif berbayar vs diagnostik gratis.</p>
              </div>
              <button
                @click="activeTab = 'students'; filterPackage = 'paid'"
                class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition-all flex items-center gap-1.5"
              >
                <span>Lihat Siswa Bimbingan</span>
                <i class="ph-bold ph-arrow-right text-[#c0ff00]"></i>
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Paket 1: Pejuang PTN -->
              <div class="p-4 rounded-2xl bg-gradient-to-br from-[#c0ff00]/10 via-transparent to-transparent border border-[#c0ff00]/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-[#c0ff00] text-black text-[10px] font-black uppercase tracking-wider">Terpopuler</span>
                  <i class="ph-bold ph-trophy text-[#c0ff00] text-base"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Pejuang PTN</h3>
                <div class="text-2xl font-black text-[#c0ff00] my-1">{{ packageBreakdown?.pejuang_ptn || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Bimbel intensif UTBK, modul drilling soal, tryout berkala IRT, &amp; grup diskusi.</p>
              </div>

              <!-- Paket 2: Mandiri Starter -->
              <div class="p-4 rounded-2xl bg-gradient-to-br from-sky-500/10 via-transparent to-transparent border border-sky-500/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-400 text-[10px] font-black uppercase tracking-wider">Self-Paced</span>
                  <i class="ph-bold ph-book-open text-sky-400 text-base"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Mandiri Starter</h3>
                <div class="text-2xl font-black text-sky-400 my-1">{{ packageBreakdown?.mandiri || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Akses video materi lengkap, latihan subtes adaptif, &amp; kunci pembahasan analitis.</p>
              </div>

              <!-- Paket 3: Elite Mentoring -->
              <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-500/10 via-transparent to-transparent border border-purple-500/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400 text-[10px] font-black uppercase tracking-wider">Privat / Intensif</span>
                  <i class="ph-bold ph-crown text-purple-400 text-base"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Elite Mentoring</h3>
                <div class="text-2xl font-black text-purple-400 my-1">{{ packageBreakdown?.elite || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Mentoring 1-on-1 bersama alumni UI/ITB/UGM, review target rasionalisasi jurusan.</p>
              </div>

              <!-- Paket 4: Free SPP Diagnostic -->
              <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-white/10 text-white/60 text-[10px] font-black uppercase tracking-wider">Diagnostik Bebas Biaya</span>
                  <i class="ph-bold ph-sparkle text-white/40 text-base"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Free SPP Diagnostic</h3>
                <div class="text-2xl font-black text-white my-1">{{ packageBreakdown?.free_spp || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Tes diagnostik minat bakat Holland Code &amp; evaluasi kemampuan fondasi SNBT.</p>
              </div>
            </div>
          </div>

          <!-- 3. DUA KOLOM: RIASEC HOLLAND CODE & TARGET PTN POPULER -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- KIRI: DISTRIBUSI RIASEC HOLLAND CODE -->
            <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-sm font-black uppercase tracking-wider text-white flex items-center gap-2">
                    <i class="ph-bold ph-compass text-[#c0ff00]"></i>
                    Distribusi Minat Holland Code (RIASEC)
                  </h3>
                  <p class="text-xs text-white/60 mt-0.5">Pemetaan kecenderungan karir siswa berdasarkan 6 domain psikometri</p>
                </div>
              </div>

              <!-- Progress Bars RIASEC -->
              <div class="space-y-3.5 mt-4">
                <div v-for="item in riasecList" :key="item.key">
                  <div class="flex items-center justify-between text-xs font-bold mb-1">
                    <span class="flex items-center gap-1.5 text-white">
                      <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }"></span>
                      {{ item.name }} ({{ item.key }})
                    </span>
                    <span class="text-white/70">{{ item.count }} Siswa ({{ item.pct }}%)</span>
                  </div>
                  <div class="w-full h-2 rounded-full bg-white/10 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-700"
                      :style="{ width: item.pct + '%', backgroundColor: item.color }"
                    ></div>
                  </div>
                </div>
              </div>

              <div class="mt-5 p-3 rounded-xl bg-white/5 border border-white/10 text-xs text-white/70 flex items-start gap-2.5">
                <i class="ph-bold ph-lightbulb-filament text-amber-400 text-base shrink-0 mt-0.5"></i>
                <div class="text-[11px] leading-relaxed">
                  <strong>Insight Guru BK:</strong> Dominasi tipe <strong>Social &amp; Enterprising</strong> mengindikasikan tingginya minat siswa pada jurusan Manajemen, Hukum, Komunikasi, dan Pendidikan.
                </div>
              </div>
            </div>

            <!-- KANAN: TOP 5 TARGET UNIVERSITAS -->
            <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-sm font-black uppercase tracking-wider text-white flex items-center gap-2">
                    <i class="ph-bold ph-buildings text-sky-400"></i>
                    Top 5 Target Perguruan Tinggi Negeri (PTN)
                  </h3>
                  <p class="text-xs text-white/60 mt-0.5">Universitas impian yang paling banyak dipilih siswa sekolah Anda</p>
                </div>
              </div>

              <div v-if="topUniversities && topUniversities.length > 0" class="space-y-3 mt-4">
                <div
                  v-for="(univ, idx) in topUniversities"
                  :key="idx"
                  class="p-3 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-between"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-[#c0ff00]/15 text-[#c0ff00] font-black text-xs flex items-center justify-center">
                      #{{ idx + 1 }}
                    </div>
                    <div>
                      <h4 class="text-xs font-black text-white">{{ univ.target_ptn }}</h4>
                      <span class="text-[10px] text-white/50">Target Pilihan Utama Siswa</span>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-sm font-black text-white">{{ univ.total }}</span>
                    <span class="text-[10px] text-white/50 block">Peminat</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-xs text-white/40">
                Belum ada data target PTN yang diinput siswa.
              </div>

              <!-- Growth Areas Highlight -->
              <div class="mt-5 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20">
                <div class="text-xs font-black text-rose-300 uppercase tracking-wider flex items-center gap-1.5 mb-1.5">
                  <i class="ph-bold ph-warning text-base"></i>
                  Fokus Penguatan Bersama (Tantangan Belajar)
                </div>
                <div class="space-y-1.5 text-[11px] text-white/70">
                  <div v-for="(ga, i) in growthAreas" :key="i" class="flex items-start gap-1.5">
                    <span class="text-rose-400 font-bold">&bull;</span>
                    <div><strong>{{ ga.title }}:</strong> {{ ga.desc }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: ROSTER DAFTAR SISWA & STATUS PAKET                     -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'students'" class="space-y-4">
          <!-- Filter & Search Toolbar -->
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl flex flex-wrap items-center justify-between gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[240px]">
              <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-white/40 text-sm"></i>
              <input
                v-model="searchQuery"
                @input="debounceSearch"
                type="text"
                placeholder="Cari nama siswa, email, atau target PTN..."
                class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-medium placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-colors"
              />
            </div>

            <!-- Filter Paket -->
            <div class="flex items-center gap-2">
              <span class="text-xs text-white/50 font-bold hidden sm:inline">Paket:</span>
              <select
                v-model="filterPackage"
                @change="fetchStudents(1)"
                class="px-3 py-2 rounded-xl bg-slate-900 border border-white/10 text-white text-xs font-medium focus:border-[#c0ff00] focus:outline-none cursor-pointer"
              >
                <option value="all">Semua Program Siswa</option>
                <option value="paid">Hanya Siswa Bimbel (Berbayar)</option>
                <option value="pejuang_ptn">Paket Pejuang PTN</option>
                <option value="mandiri">Paket Mandiri Starter</option>
                <option value="elite">Paket Elite Mentoring</option>
                <option value="free">Free SPP Diagnostic</option>
              </select>
            </div>

            <!-- Export Button -->
            <button
              @click="exportCsv"
              class="px-3 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
            >
              <i class="ph-bold ph-file-csv text-base"></i>
              <span>Ekspor Rekap CSV</span>
            </button>
          </div>

          <!-- Students Table -->
          <div class="rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-white/10 bg-white/[0.02] text-white/50 uppercase tracking-wider text-[10px] font-black">
                    <th class="py-3 px-4">Nama Siswa</th>
                    <th class="py-3 px-4">Program / Paket EduPath</th>
                    <th class="py-3 px-4">Target PTN</th>
                    <th class="py-3 px-4">Holland Code (RIASEC)</th>
                    <th class="py-3 px-4 text-center">Skor Tryout Terakhir</th>
                    <th class="py-3 px-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-if="studentsLoading">
                    <td colspan="6" class="py-12 text-center text-white/50">
                      <div class="w-6 h-6 border-2 border-[#c0ff00] border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                      Memuat daftar siswa...
                    </td>
                  </tr>
                  <tr v-else-if="students.length === 0">
                    <td colspan="6" class="py-12 text-center text-white/40">
                      <i class="ph-bold ph-folder-open text-3xl mb-2 block opacity-40"></i>
                      Tidak ada siswa ditemukan dengan filter saat ini.
                    </td>
                  </tr>
                  <tr v-for="stu in students" :key="stu.id" class="hover:bg-white/[0.02] transition-colors">
                    <!-- Siswa -->
                    <td class="py-3 px-4">
                      <div class="font-bold text-white">{{ stu.name }}</div>
                      <div class="text-[11px] text-white/40">{{ stu.email }} &bull; {{ stu.phone }}</div>
                    </td>

                    <!-- Program / Paket EduPath -->
                    <td class="py-3 px-4">
                      <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider" :class="getPackageBadgeClass(stu.package_code)">
                        <i class="ph-bold ph-package text-xs"></i>
                        <span>{{ stu.package_name }}</span>
                      </div>
                      <div v-if="stu.package_status === 'active'" class="text-[10px] text-emerald-400 font-semibold mt-0.5">
                        Aktif s/d {{ stu.package_expires_at ? formatDate(stu.package_expires_at) : 'Selesai SNBT' }}
                      </div>
                      <div v-else class="text-[10px] text-white/30 mt-0.5">Akses Diagnostik</div>
                    </td>

                    <!-- Target PTN -->
                    <td class="py-3 px-4">
                      <span class="font-medium text-white/90">{{ stu.target_ptn || '-' }}</span>
                    </td>

                    <!-- Holland Code -->
                    <td class="py-3 px-4">
                      <span v-if="stu.riasec_badge !== 'Belum Tes'" class="px-2 py-0.5 rounded-lg bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 font-extrabold text-[10px]">
                        {{ stu.riasec_badge }}
                      </span>
                      <span v-else class="text-[10px] text-white/30 italic">Belum Tes</span>
                    </td>

                    <!-- Skor Tryout -->
                    <td class="py-3 px-4 text-center">
                      <span v-if="stu.latest_tryout_score !== null" class="font-black text-amber-400 text-sm">
                        {{ stu.latest_tryout_score }}
                      </span>
                      <span v-else class="text-[10px] text-white/30 italic">-</span>
                    </td>

                    <!-- Aksi -->
                    <td class="py-3 px-4 text-right">
                      <button
                        @click="openStudentDetail(stu)"
                        class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-white/80 hover:text-[#c0ff00] text-[11px] font-bold transition-all inline-flex items-center gap-1 cursor-pointer"
                      >
                        <i class="ph-bold ph-eye"></i>
                        <span>Detail</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Bar -->
            <div class="p-4 border-t border-white/5 flex items-center justify-between text-xs text-white/50">
              <div>
                Menampilkan halaman {{ studentPage }} dari {{ Math.ceil(studentTotal / studentLimit) || 1 }} (Total {{ studentTotal }} siswa)
              </div>
              <div class="flex items-center gap-2">
                <button
                  :disabled="studentPage <= 1"
                  @click="fetchStudents(studentPage - 1)"
                  class="px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-white font-bold disabled:opacity-30 disabled:cursor-not-allowed"
                >
                  &larr; Prev
                </button>
                <button
                  :disabled="studentPage >= Math.ceil(studentTotal / studentLimit)"
                  @click="fetchStudents(studentPage + 1)"
                  class="px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-white font-bold disabled:opacity-30 disabled:cursor-not-allowed"
                >
                  Next &rarr;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: INFORMASI PAKET BELAJAR (KATALOG UNTUK GURU BK)         -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'packages'" class="space-y-6">
          <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
            <div class="max-w-2xl">
              <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] block mb-1">Panduan Program EduPath untuk Guru BK</span>
              <h2 class="text-2xl font-black text-white">Struktur Paket Bimbingan Siswa</h2>
              <p class="text-xs text-white/70 mt-1 leading-relaxed">
                Sekolah mitra dapat merekomendasikan program intensif berikut kepada siswa kelas 12 untuk mendongkrak peluang kelolosan SNBT dan Seleksi Mandiri.
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
              <!-- Card 1 -->
              <div class="p-6 rounded-3xl bg-white/[0.02] border border-white/10 flex flex-col justify-between">
                <div>
                  <div class="inline-flex px-2.5 py-1 rounded-full bg-sky-500/20 text-sky-400 text-[10px] font-black uppercase tracking-wider mb-3">Akses Mandiri</div>
                  <h3 class="text-lg font-black text-white">Paket Mandiri Starter</h3>
                  <div class="text-2xl font-black text-sky-400 my-2">Rp 99.000 <span class="text-xs text-white/50 font-normal">/ musim</span></div>
                  <ul class="text-xs text-white/70 space-y-2 mt-4">
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-sky-400"></i> Bank Soal 5.000+ Soal HOTS</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-sky-400"></i> Video Materi TPS &amp; Literasi</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-sky-400"></i> 5x Simulasi CBT IRT Nasional</li>
                  </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-white/5">
                  <div class="text-[11px] text-white/40">Siswa Anda terdaftar: <strong>{{ packageBreakdown?.mandiri || 0 }} Siswa</strong></div>
                </div>
              </div>

              <!-- Card 2: Featured -->
              <div class="p-6 rounded-3xl bg-gradient-to-b from-[#c0ff00]/15 via-white/[0.03] to-transparent border-2 border-[#c0ff00]/40 flex flex-col justify-between shadow-xl shadow-[#c0ff00]/10">
                <div>
                  <div class="inline-flex px-2.5 py-1 rounded-full bg-[#c0ff00] text-black text-[10px] font-black uppercase tracking-wider mb-3">Pilihan Utama Siswa</div>
                  <h3 class="text-lg font-black text-white">Paket Pejuang PTN (All-In)</h3>
                  <div class="text-2xl font-black text-[#c0ff00] my-2">Rp 249.000 <span class="text-xs text-white/50 font-normal">/ musim</span></div>
                  <ul class="text-xs text-white/80 space-y-2 mt-4">
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-[#c0ff00]"></i> <strong>Semua Fitur Paket Mandiri</strong></li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-[#c0ff00]"></i> 15x Tryout Akbar IRT Nasional</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-[#c0ff00]"></i> Live Class Interaktif &amp; Drilling Soal</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-[#c0ff00]"></i> Komunitas Belajar &amp; Tanya Tutor</li>
                  </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-white/10">
                  <div class="text-[11px] text-[#c0ff00]">Siswa Anda terdaftar: <strong>{{ packageBreakdown?.pejuang_ptn || 0 }} Siswa</strong></div>
                </div>
              </div>

              <!-- Card 3 -->
              <div class="p-6 rounded-3xl bg-white/[0.02] border border-white/10 flex flex-col justify-between">
                <div>
                  <div class="inline-flex px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-400 text-[10px] font-black uppercase tracking-wider mb-3">Intensif &amp; Kedinasan</div>
                  <h3 class="text-lg font-black text-white">Paket Elite Mentoring</h3>
                  <div class="text-2xl font-black text-purple-400 my-2">Rp 599.000 <span class="text-xs text-white/50 font-normal">/ musim</span></div>
                  <ul class="text-xs text-white/70 space-y-2 mt-4">
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-purple-400"></i> <strong>Semua Fitur Pejuang PTN</strong></li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-purple-400"></i> Mentoring 1-on-1 Rasionalisasi SNBT</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-purple-400"></i> Modul Khusus Kedinasan &amp; Mandiri</li>
                    <li class="flex items-center gap-2"><i class="ph-bold ph-check text-purple-400"></i> Jalur Konsultasi Khusus Ortu &amp; Siswa</li>
                  </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-white/5">
                  <div class="text-[11px] text-white/40">Siswa Anda terdaftar: <strong>{{ packageBreakdown?.elite || 0 }} Siswa</strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 4: KIT SOSIALISASI & BROADCAST WHATSAPP                    -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'broadcast'" class="space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: QR Code Display -->
            <div class="lg:col-span-5 p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl text-center">
              <h3 class="text-sm font-black uppercase tracking-wider text-white mb-1">QR Code Asesmen Resmi</h3>
              <p class="text-xs text-white/60 mb-4">{{ schoolData?.name }}</p>

              <div class="p-4 bg-white rounded-2xl inline-block shadow-2xl">
                <img
                  :src="qrCodeUrl"
                  alt="QR Code Sekolah"
                  class="w-48 h-48 sm:w-56 sm:h-56 object-contain"
                />
              </div>

              <div class="mt-4 space-y-2">
                <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-[11px] text-white/70 font-mono break-all">
                  {{ shareLink }}
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                  <button
                    @click="copyShareLink"
                    class="px-4 py-2 rounded-xl bg-[#c0ff00] text-black font-black text-xs transition-all flex items-center gap-1.5 cursor-pointer shadow-lg shadow-[#c0ff00]/20"
                  >
                    <i :class="['ph-bold', copiedLink ? 'ph-check' : 'ph-copy']"></i>
                    <span>{{ copiedLink ? 'Tautan Tersalin' : 'Salin Tautan' }}</span>
                  </button>
                  <a
                    :href="qrCodeUrl"
                    download="qrcode_sekolah.png"
                    target="_blank"
                    class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all flex items-center gap-1.5"
                  >
                    <i class="ph-bold ph-download-simple"></i>
                    <span>Unduh Gambar</span>
                  </a>
                </div>
              </div>
            </div>

            <!-- Right: Broadcast Template Generator -->
            <div class="lg:col-span-7 p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-4">
              <div>
                <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] block mb-1">Templat Siap Sebar</span>
                <h3 class="text-lg font-black text-white">Pesan WhatsApp untuk Grup Kelas / Orang Tua</h3>
                <p class="text-xs text-white/60">Guru BK dapat langsung menyalin teks ini dan menempelkannya ke WhatsApp Group Kelas 12.</p>
              </div>

              <div class="relative">
                <textarea
                  v-model="broadcastText"
                  rows="10"
                  readonly
                  class="w-full p-4 rounded-2xl bg-white/5 border border-white/10 text-white/90 text-xs font-mono leading-relaxed focus:outline-none select-all"
                ></textarea>
                <button
                  @click="copyBroadcastText"
                  class="absolute top-3 right-3 px-3 py-1.5 rounded-xl bg-[#c0ff00] text-black font-black text-xs transition-all flex items-center gap-1 cursor-pointer shadow-lg shadow-[#c0ff00]/20"
                >
                  <i :class="['ph-bold', copiedBroadcast ? 'ph-check' : 'ph-copy']"></i>
                  <span>{{ copiedBroadcast ? 'Tersalin!' : 'Salin Pesan WA' }}</span>
                </button>
              </div>

              <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-300 leading-relaxed flex items-start gap-2.5">
                <i class="ph-bold ph-whatsapp-logo text-lg text-emerald-400 shrink-0 mt-0.5"></i>
                <div>
                  <strong>Tips Distribusi Efektif:</strong> Sebar link ini pada jam wali kelas atau bimbingan karir sekolah. Dalam 15 menit, seluruh siswa akan otomatis terdaftar dan grafik agregat sekolah langsung terisi!
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- ============================================================== -->
      <!-- MODAL: QR CODE SISWA POPUP                                     -->
      <!-- ============================================================== -->
      <div v-if="openQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="openQrModal = false"></div>
        <div class="relative z-10 w-full max-w-sm p-6 rounded-3xl bg-[#090e1f] border border-white/15 text-center shadow-2xl">
          <button @click="openQrModal = false" class="absolute top-4 right-4 text-white/40 hover:text-white">
            <i class="ph-bold ph-x text-lg"></i>
          </button>
          <div class="w-10 h-10 rounded-2xl bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center mx-auto mb-3">
            <i class="ph-bold ph-qr-code text-xl"></i>
          </div>
          <h3 class="text-base font-black text-white">{{ schoolData?.name }}</h3>
          <p class="text-xs text-white/60 mb-4">Pindai untuk mengikuti Student Potential Path &amp; Diagnostik SNBT</p>

          <div class="p-3 bg-white rounded-2xl inline-block shadow-lg">
            <img :src="qrCodeUrl" alt="QR Code" class="w-48 h-48 object-contain" />
          </div>

          <div class="mt-4 flex gap-2">
            <button @click="copyShareLink" class="flex-1 py-2 rounded-xl bg-[#c0ff00] text-black font-black text-xs">
              {{ copiedLink ? 'Tersalin!' : 'Salin Link' }}
            </button>
            <button @click="openQrModal = false" class="px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs">
              Tutup
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- MODAL: DETAIL SISWA POPUP                                      -->
      <!-- ============================================================== -->
      <div v-if="selectedStudent" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="selectedStudent = null"></div>
        <div class="relative z-10 w-full max-w-md p-6 rounded-3xl bg-[#090e1f] border border-white/15 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-xl bg-[#c0ff00]/20 text-[#c0ff00] flex items-center justify-center font-bold">
                {{ selectedStudent.name.charAt(0) }}
              </div>
              <div>
                <h3 class="text-sm font-black text-white">{{ selectedStudent.name }}</h3>
                <span class="text-[10px] text-white/50">{{ selectedStudent.email }}</span>
              </div>
            </div>
            <button @click="selectedStudent = null" class="text-white/40 hover:text-white">
              <i class="ph-bold ph-x text-lg"></i>
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div class="flex justify-between py-1.5 border-b border-white/5">
              <span class="text-white/50">Program / Paket:</span>
              <span class="font-bold text-[#c0ff00]">{{ selectedStudent.package_name }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-white/5">
              <span class="text-white/50">Status Paket:</span>
              <span class="font-bold uppercase" :class="selectedStudent.package_status === 'active' ? 'text-emerald-400' : 'text-white/60'">
                {{ selectedStudent.package_status }}
              </span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-white/5">
              <span class="text-white/50">Target PTN:</span>
              <span class="font-bold text-white">{{ selectedStudent.target_ptn || 'Belum dipilih' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-white/5">
              <span class="text-white/50">Minat Holland (RIASEC):</span>
              <span class="font-bold text-indigo-300">{{ selectedStudent.riasec_badge }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-white/5">
              <span class="text-white/50">Skor Tryout Terbaru:</span>
              <span class="font-bold text-amber-400">{{ selectedStudent.latest_tryout_score || 'Belum pernah tryout' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-white/5">
              <span class="text-white/50">No. WhatsApp Siswa:</span>
              <a :href="'https://wa.me/' + cleanPhone(selectedStudent.phone)" target="_blank" class="font-bold text-emerald-400 hover:underline flex items-center gap-1">
                <i class="ph-bold ph-whatsapp-logo"></i>
                {{ selectedStudent.phone }}
              </a>
            </div>
          </div>

          <div class="pt-2 flex gap-2">
            <a
              :href="'https://wa.me/' + cleanPhone(selectedStudent.phone) + '?text=' + encodeURIComponent('Halo ' + selectedStudent.name + ', ini dari Guru BK ' + (schoolData?.name || 'Sekolah') + ' seputar hasil pemetaan minat SNBT EduPath kamu.')"
              target="_blank"
              class="flex-1 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs flex items-center justify-center gap-1.5"
            >
              <i class="ph-bold ph-chat-circle-dots"></i>
              <span>Hubungi via WhatsApp</span>
            </a>
            <button @click="selectedStudent = null" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../api.js';

// ── State Autentikasi ──
const isAuthenticated = ref(false);
const authTab = ref('login'); // 'login' | 'register'
const authLoading = ref(false);
const authError = ref('');
const authSuccess = ref('');
const showPassword = ref(false);

const loginForm = ref({
  email: '',
  password: ''
});

const registerForm = ref({
  name: '',
  npsn: '',
  city: '',
  bk_name: '',
  phone: '',
  email: '',
  password: '',
  estimated_students: 250
});

// ── State Dashboard & Sekolah ──
const schoolData = ref(null);
const activeTab = ref('analytics'); // 'analytics' | 'students' | 'packages' | 'broadcast'
const dataLoading = ref(false);
const copiedLink = ref(false);
const copiedBroadcast = ref(false);
const openQrModal = ref(false);

// ── Data Agregat ──
const kpis = ref({
  total_students: 0,
  paid_package_students: 0,
  free_diagnostic_students: 0,
  tryout_participants: 0,
  average_tryout_score: 0,
  spp_completed_students: 0
});

const packageBreakdown = ref({
  pejuang_ptn: 0,
  mandiri: 0,
  elite: 0,
  free_spp: 0
});

const topUniversities = ref([]);
const growthAreas = ref([]);
const riasecRaw = ref({});

// ── Data Roster Siswa ──
const students = ref([]);
const studentsLoading = ref(false);
const studentTotal = ref(0);
const studentPage = ref(1);
const studentLimit = ref(20);
const searchQuery = ref('');
const filterPackage = ref('all');
const selectedStudent = ref(null);

let searchTimeout = null;

// ── Computed Properties ──
const shareLink = computed(() => {
  const schoolName = schoolData.value?.name || 'Sekolah';
  return `https://edupath.co.id/?school=${encodeURIComponent(schoolName)}#/spp`;
});

const qrCodeUrl = computed(() => {
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(shareLink.value)}`;
});

const broadcastText = computed(() => {
  const sName = schoolData.value?.name || 'Sekolah';
  const bk = schoolData.value?.bk_name || 'Guru BK';
  return `📢 *PENGUMUMAN RESMI BK ${sName.toUpperCase()}*\n` +
    `Halo teman-teman siswa kelas 12!\n\n` +
    `Untuk memetakan minat jurusan kuliah, pola belajar, dan persiapan SNBT/UTBK 2026, Guru BK bekerjasama dengan platform adaptif *EduPath.ai* menyelenggarakan *Student Potential Path (SPP) Diagnostic Test* secara GRATIS.\n\n` +
    `📌 *Cara Mengikuti Tes:*\n` +
    `1. Klik tautan resmi sekolah kita:\n` +
    `${shareLink.value}\n` +
    `2. Isi data diri & pilih target kampus impian\n` +
    `3. Kerjakan asesmen minat bakat & diagnostik SNBT (±15 menit)\n` +
    `4. Rapor potensi & rekomendasi jurusan langsung terbit otomatis!\n\n` +
    `Mohon seluruh siswa kelas 12 menyelesaikan asesmen ini sebelum akhir pekan. Rapor hasil akan menjadi acuan bimbingan karir bersama Tim BK.\n\n` +
    `Salam hangat,\n` +
    `*${bk}*\n` +
    `Koordinator BK ${sName}`;
});

const riasecList = computed(() => {
  const raw = riasecRaw.value || {};
  const total = Object.values(raw).reduce((a, b) => a + (Number(b) || 0), 0) || 1;

  const defs = [
    { key: 'R', name: 'Realistic (Praktikal & Teknis)', color: '#38bdf8' },
    { key: 'I', name: 'Investigative (Analitis & Sains)', color: '#818cf8' },
    { key: 'A', name: 'Artistic (Kreatif & Desain)', color: '#ec4899' },
    { key: 'S', name: 'Social (Interpersonal & Pendidikan)', color: '#10b981' },
    { key: 'E', name: 'Enterprising (Bisnis & Kepemimpinan)', color: '#f59e0b' },
    { key: 'C', name: 'Conventional (Terstruktur & Finansial)', color: '#94a3b8' }
  ];

  return defs.map(d => {
    const cnt = Number(raw[d.key]) || 0;
    const pct = Math.round((cnt / total) * 100);
    return { ...d, count: cnt, pct };
  });
});

// ── Lifecycle & Init ──
onMounted(() => {
  const savedToken = sessionStorage.getItem('school_token');
  const savedData = sessionStorage.getItem('school_data');

  if (savedToken) {
    try {
      if (savedData) schoolData.value = JSON.parse(savedData);
      isAuthenticated.value = true;
      loadAllDashboardData();
    } catch (e) {
      handleLogout();
    }
  }
});

// ── Actions ──
async function handleLogin() {
  authLoading.value = true;
  authError.value = '';
  try {
    const res = await api.loginSchool(loginForm.value);
    if (res.token) {
      sessionStorage.setItem('school_token', res.token);
      sessionStorage.setItem('school_data', JSON.stringify(res.school));
      schoolData.value = res.school;
      isAuthenticated.value = true;
      await loadAllDashboardData();
    } else {
      throw new Error(res.error || 'Gagal masuk.');
    }
  } catch (err) {
    authError.value = err.message || 'Email atau password salah.';
  } finally {
    authLoading.value = false;
  }
}

async function quickDemoLogin(demoSchoolName) {
  authLoading.value = true;
  authError.value = '';
  try {
    // Generate mock school session for instant UAT verification
    const demoData = {
      id: 'demo-school-01',
      name: demoSchoolName || 'SMANSA Ciamis',
      slug: 'smansa-ciamis',
      bk_name: 'Dra. H. Nuraeni, M.Pd',
      bk_email: 'bk.smansaciamis@edupath.co.id',
      bk_phone: '081223344556',
      city: 'Kabupaten Ciamis'
    };
    // Fetch mock or live data
    schoolData.value = demoData;
    sessionStorage.setItem('school_data', JSON.stringify(demoData));
    sessionStorage.setItem('school_token', 'demo_token_authenticated');
    isAuthenticated.value = true;
    await loadAllDashboardData();
  } catch (err) {
    authError.value = 'Mode demo tidak dapat diinisialisasi.';
  } finally {
    authLoading.value = false;
  }
}

async function handleRegister() {
  authLoading.value = true;
  authError.value = '';
  authSuccess.value = '';
  try {
    const res = await api.registerSchool(registerForm.value);
    if (res.success && res.token) {
      sessionStorage.setItem('school_token', res.token);
      sessionStorage.setItem('school_data', JSON.stringify(res.school));
      schoolData.value = res.school;
      isAuthenticated.value = true;
      await loadAllDashboardData();
    } else {
      throw new Error(res.error || 'Pendaftaran gagal.');
    }
  } catch (err) {
    authError.value = err.message || 'Pendaftaran gagal. Periksa data kembali.';
  } finally {
    authLoading.value = false;
  }
}

function handleLogout() {
  sessionStorage.removeItem('school_token');
  sessionStorage.removeItem('school_data');
  isAuthenticated.value = false;
  schoolData.value = null;
  authTab.value = 'login';
}

async function loadAllDashboardData() {
  dataLoading.value = true;
  try {
    const schoolName = schoolData.value?.name || '';
    const res = await api.getSchoolDashboard(schoolName).catch(() => null);

    if (res && res.success) {
      kpis.value = res.kpis || kpis.value;
      packageBreakdown.value = res.package_breakdown || packageBreakdown.value;
      topUniversities.value = res.top_universities || [];
      growthAreas.value = res.growth_areas || [];
      riasecRaw.value = res.riasec_distribution || {};
    } else {
      // Sediakan default analytics untuk sekolah baru
      growthAreas.value = [
        { title: "Penalaran Matematika & Kuantitatif", desc: "Perlu penguatan aljabar & interpretasi tabel data." },
        { title: "Literasi Bahasa Inggris", desc: "Perlu pembiasaan inferensi ide pokok dari teks panjang." }
      ];
    }

    await fetchStudents(1);
  } catch (err) {
    console.error('Error loading school dashboard:', err);
  } finally {
    dataLoading.value = false;
  }
}

async function fetchStudents(page = 1) {
  studentsLoading.value = true;
  studentPage.value = page;
  try {
    const params = {
      page,
      limit: studentLimit.value,
      search: searchQuery.value,
      package: filterPackage.value,
      school_name: schoolData.value?.name || ''
    };
    const res = await api.getSchoolStudents(params).catch(() => null);

    if (res && res.success) {
      students.value = res.data || [];
      studentTotal.value = res.total || 0;
    } else {
      students.value = [];
      studentTotal.value = 0;
    }
  } catch (err) {
    console.error('Error loading students:', err);
  } finally {
    studentsLoading.value = false;
  }
}

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchStudents(1);
  }, 350);
}

function openStudentDetail(student) {
  selectedStudent.value = student;
}

function copyShareLink() {
  navigator.clipboard.writeText(shareLink.value);
  copiedLink.value = true;
  setTimeout(() => (copiedLink.value = false), 2500);
}

function copyBroadcastText() {
  navigator.clipboard.writeText(broadcastText.value);
  copiedBroadcast.value = true;
  setTimeout(() => (copiedBroadcast.value = false), 2500);
}

async function exportCsv() {
  const token = sessionStorage.getItem('school_token') || '';
  const sName = schoolData.value?.name || '';
  const url = `/api/school.php?action=export&token=${encodeURIComponent(token)}&school_name=${encodeURIComponent(sName)}`;
  window.open(url, '_blank');
}

function getPackageBadgeClass(code) {
  const c = String(code || '').toUpperCase();
  if (c === 'UTBK' || c.includes('PEJUANG')) {
    return 'bg-[#c0ff00]/15 text-[#c0ff00] border border-[#c0ff00]/40';
  } else if (c === 'STARTER' || c.includes('MANDIRI')) {
    return 'bg-sky-400/15 text-sky-400 border border-sky-400/40';
  } else if (c === 'ELITE' || c.includes('MENTORING')) {
    return 'bg-purple-400/15 text-purple-400 border border-purple-400/40';
  }
  return 'bg-white/5 text-white/50 border border-white/10';
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  } catch (e) {
    return dateStr;
  }
}

function cleanPhone(phone) {
  if (!phone) return '';
  let clean = phone.replace(/[^0-9]/g, '');
  if (clean.startsWith('0')) clean = '62' + clean.slice(1);
  return clean;
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
