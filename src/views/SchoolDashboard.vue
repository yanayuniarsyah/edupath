<template>
  <!-- ============================================================== -->
  <!-- GATE 1: UNAUTHENTICATED (LOGIN GATE MIRRORING ADMIN/GURU DESIGN) -->
  <!-- ============================================================== -->
  <div v-if="!isAuthenticated" class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #050a18 0%, #0d1224 60%, #050a18 100%);">
    <!-- Glow decorations -->
    <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #c0ff00 0%, transparent 70%);"></div>
    <div class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full opacity-15 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #6366f1 0%, transparent 70%);"></div>

    <div class="relative z-10 w-full max-w-md mx-4">
      <!-- Login Card -->
      <div class="rounded-3xl p-8 border relative backdrop-blur-2xl shadow-2xl" style="background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08);">
        <!-- Close button to home -->
        <button @click="$router.push('/')" class="!absolute top-5 right-5 z-50 w-8 h-8 flex items-center justify-center rounded-full bg-white/5 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition-colors border border-transparent hover:border-rose-500/30 text-sm cursor-pointer" title="Kembali ke Beranda">
          <i class="ph-bold ph-x"></i>
        </button>

        <!-- Logo & Header -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center gap-2.5 group select-none mb-2">
            <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20">E</div>
            <span class="font-black text-2xl tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          </div>
          <h2 class="text-white font-black text-lg">Portal Sekolah &amp; Guru BK</h2>
          <p class="text-white/40 text-xs font-semibold uppercase tracking-widest mt-1">Dashboard Analitik Agregat &amp; Minat Bakat Siswa</p>
        </div>

        <!-- Error Message -->
        <div v-if="authError" class="mb-4 px-4 py-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs font-bold flex items-center gap-2">
          <i class="ph-bold ph-warning-circle text-base shrink-0"></i>
          <span>{{ authError }}</span>
        </div>

        <!-- Success Message -->
        <div v-if="authSuccess" class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center gap-2">
          <i class="ph-bold ph-check-circle text-base shrink-0"></i>
          <span>{{ authSuccess }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-white/60 text-xs font-bold mb-1.5 uppercase tracking-wider">Email Guru BK / Sekolah</label>
            <div class="relative">
              <i class="ph-bold ph-envelope absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                v-model="loginForm.email"
                type="email"
                required
                placeholder="bk.sekolah@sch.id"
                class="w-full pl-9 pr-4 py-3 rounded-xl text-sm font-medium text-white outline-none transition-all placeholder:text-white/20"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
              />
            </div>
          </div>

          <div>
            <label class="block text-white/60 text-xs font-bold mb-1.5 uppercase tracking-wider">Password</label>
            <div class="relative">
              <i class="ph-bold ph-lock absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                v-model="loginForm.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full pl-9 pr-10 py-3 rounded-xl text-sm font-medium text-white outline-none transition-all placeholder:text-white/20"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors cursor-pointer select-none p-1">
                <i :class="['ph-bold', showPassword ? 'ph-eye-slash text-[#c0ff00]' : 'ph-eye']"></i>
              </button>
            </div>
          </div>

          <!-- Quick Fill Demo School Account -->
          <div class="pt-1">
            <span class="block text-[10px] uppercase font-black text-white/40 tracking-wider mb-1.5">Pilih Akun Demo Sekolah:</span>
            <button
              type="button"
              @click="loginForm.email = 'bk.smansaciamis@edupath.co.id'; loginForm.password = 'EduPathSchool01!2026'"
              class="w-full px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/25 text-xs font-bold text-emerald-300 transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <i class="ph-bold ph-chalkboard-teacher"></i>
              <span>Isi Akun Demo: SMANSA Ciamis</span>
            </button>
          </div>

          <button
            type="submit"
            :disabled="authLoading"
            class="w-full py-3 rounded-xl font-black text-sm text-black transition-all hover:opacity-90 active:scale-95 mt-2 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
            style="background: #c0ff00;"
          >
            <span v-if="!authLoading">Masuk ke Portal Sekolah</span>
            <span v-else class="flex items-center gap-2">
              <i class="ph-bold ph-spinner animate-spin"></i> Memverifikasi...
            </span>
          </button>
        </form>

        <div class="mt-5 pt-4 border-t border-white/8 text-center space-y-1">
          <p class="text-white/30 text-[11px] font-medium">Akses khusus Koordinator BK &amp; Tim Bimbingan Karir Sekolah Mitra.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================== -->
  <!-- GATE 2: AUTHENTICATED DASHBOARD WITH SIDEBAR NAVIGATION        -->
  <!-- ============================================================== -->
  <div v-else class="flex h-screen bg-[#070b14] font-body overflow-hidden text-white select-none">
    <!-- Backdrop Overlay for Mobile Drawer -->
    <transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40 lg:hidden cursor-pointer"
        @click="sidebarOpen = false"
      ></div>
    </transition>

    <!-- ===== SIDEBAR (SLIDE-OVER DRAWER ON MOBILE, STATIC ON DESKTOP) ===== -->
    <aside
      class="fixed inset-y-0 left-0 z-50 lg:static lg:z-auto flex flex-col h-full shrink-0 transition-transform lg:transition-all duration-300 ease-out overflow-hidden border-r border-white/10 shadow-2xl"
      :class="[
        sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0 lg:w-[72px]'
      ]"
      style="background: linear-gradient(160deg, #050a18 0%, #0d1224 60%, #050a18 100%);"
    >
      <!-- Logo Header with Collapser -->
      <div
        class="flex items-center gap-3 px-4 py-4 border-b border-white/10 shrink-0 cursor-pointer select-none group"
        @click="sidebarOpen = !sidebarOpen"
        :title="sidebarOpen ? 'Ciutkan Sidebar' : 'Perluas Sidebar'"
      >
        <div class="w-9 h-9 shrink-0 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20 group-hover:scale-105 transition-transform">E</div>
        <div v-show="sidebarOpen" class="min-w-0">
          <span class="font-black text-lg tracking-tight text-white whitespace-nowrap">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          <p class="text-[10px] text-[#c0ff00] font-black uppercase tracking-widest whitespace-nowrap">Portal Guru BK &amp; Sekolah</p>
        </div>
      </div>

      <!-- School Identity Badge in Sidebar -->
      <div v-show="sidebarOpen" class="p-3 mx-2.5 my-2.5 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md flex items-center gap-2.5">
        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></div>
        <div class="min-w-0 flex-1">
          <span class="text-xs font-black text-white block truncate">{{ schoolData?.name || 'Sekolah Mitra' }}</span>
          <span class="text-[10px] text-white/50 block truncate">{{ schoolData?.city || 'Indonesia' }}</span>
        </div>
      </div>

      <!-- Sidebar Navigation Menu Items -->
      <nav class="flex flex-col gap-1 p-2.5 flex-grow overflow-y-auto no-scrollbar">
        <button
          v-for="item in navItems" :key="item.id"
          @click="selectNavTab(item.id)"
          :title="item.label"
          :class="[
            'flex items-center rounded-xl text-xs font-bold transition-all text-left group relative cursor-pointer',
            sidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center px-0 py-2.5',
            activeTab === item.id
              ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00]'
              : 'text-white/60 hover:text-white hover:bg-white/5 border border-transparent'
          ]"
        >
          <i :class="['ph-bold text-base shrink-0', item.icon, activeTab === item.id ? 'text-[#c0ff00]' : 'text-white/50 group-hover:text-white']"></i>
          <span v-show="sidebarOpen" class="truncate flex-1">{{ item.label }}</span>
          <span v-if="sidebarOpen && item.badge" :class="item.badgeClass || 'px-1.5 py-0.5 rounded text-[10px] font-black bg-white/10 text-white/70'">
            {{ item.badge }}
          </span>
        </button>
      </nav>

      <!-- Sidebar Bottom Profile & Logout -->
      <div class="p-3 border-t border-white/10 shrink-0 bg-white/[0.02]">
        <div v-show="sidebarOpen" class="mb-2.5 px-1">
          <span class="text-xs font-bold text-white block truncate">{{ schoolData?.bk_name || 'Guru BK' }}</span>
          <span class="text-[10px] text-emerald-400 uppercase font-black tracking-wider block">Koordinator BK &bull; {{ schoolData?.name || 'Sekolah' }}</span>
          <span class="text-[10px] text-white/50 block truncate">{{ schoolData?.bk_email }}</span>
        </div>
        <button
          @click="handleLogout"
          class="w-full py-2 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-300 text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
          :title="sidebarOpen ? 'Keluar dari Portal' : 'Keluar'"
        >
          <i class="ph-bold ph-sign-out text-sm"></i>
          <span v-show="sidebarOpen">Keluar Portal</span>
        </button>
      </div>
    </aside>

    <!-- ===== MAIN CONTENT VIEWPORT (RIGHT SIDE) ===== -->
    <main class="flex-1 flex flex-col h-full min-w-0 overflow-hidden bg-[#070b14]">
      <!-- Topbar Header -->
      <header class="h-16 border-b border-white/10 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 bg-[#050a18]/80 backdrop-blur-md z-20">
        <!-- Left: Mobile Sidebar Toggle + Breadcrumb -->
        <div class="flex items-center gap-3 min-w-0">
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/70 hover:text-white transition-all cursor-pointer"
            title="Buka / Tutup Menu Samping"
          >
            <i class="ph-bold ph-list text-lg"></i>
          </button>
          <div class="min-w-0">
            <div class="text-[10px] font-bold text-white/40 uppercase tracking-widest truncate">Portal BK &bull; {{ schoolData?.name || 'Sekolah' }}</div>
            <h1 class="text-sm sm:text-base font-black text-white leading-tight capitalize truncate">{{ activeTabTitle }}</h1>
          </div>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 shrink-0">
          <button
            @click="openQrModal = true"
            class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
            title="Tampilkan QR Code Siswa"
          >
            <i class="ph-bold ph-qr-code text-[#c0ff00]"></i>
            <span class="hidden md:inline">QR Code Siswa</span>
          </button>

          <button
            @click="copyShareLink"
            class="px-3 py-1.5 rounded-xl bg-[#c0ff00]/15 hover:bg-[#c0ff00]/25 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
            title="Salin tautan asesmen siswa"
          >
            <i :class="['ph-bold', copiedLink ? 'ph-check' : 'ph-link-simple']"></i>
            <span class="hidden md:inline">{{ copiedLink ? 'Tersalin!' : 'Salin Link Siswa' }}</span>
          </button>

          <button
            @click="exportCsv"
            class="px-3 py-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
            title="Ekspor rekap siswa ke CSV"
          >
            <i class="ph-bold ph-microsoft-excel-logo"></i>
            <span class="hidden sm:inline">Unduh CSV</span>
          </button>

          <button
            @click="loadAllDashboardData"
            class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 hover:text-white flex items-center justify-center transition-all cursor-pointer"
            title="Segarkan Data"
          >
            <i :class="['ph-bold ph-arrows-clockwise text-sm', dataLoading ? 'animate-spin text-[#c0ff00]' : '']"></i>
          </button>
        </div>
      </header>

      <!-- Scrollable Tab Content Area -->
      <div class="flex-1 overflow-y-auto p-3 sm:p-5 lg:p-6 space-y-4 sm:space-y-6">

        <!-- ========================================================== -->
        <!-- VIEW 1: RINGKASAN & KPI EKSEKUTIF                          -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'analytics'" class="space-y-4 sm:space-y-6">

          <!-- ✨ Banner Gambar Dashboard Sekolah (Gambar 2.jpeg Identik dengan Dashboard Siswa) -->
          <div class="glass-card rounded-2xl sm:rounded-3xl overflow-hidden border border-white/10 shadow-[0_16px_50px_rgba(0,0,0,0.5)] relative group">
            <img src="/2.jpeg" onerror="this.src='/1.jpeg';" alt="EduPath School Banner" class="w-full h-32 sm:h-44 md:h-56 lg:h-64 object-cover object-center transition-transform duration-700 group-hover:scale-105" />
            <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-[#c0ff00] to-transparent opacity-90 z-10"></div>
          </div>

          <!-- ✨ Text & Tombol Greeting Info Sekolah -->
          <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-white/10 shadow-[0_16px_40px_rgba(0,0,0,0.5)] bg-gradient-to-r from-slate-900/95 via-slate-900/85 to-slate-950 flex flex-col lg:flex-row lg:items-center justify-between gap-5 sm:gap-6">
            <!-- Left: Avatar & School Info -->
            <div class="flex items-start sm:items-center gap-3.5 sm:gap-4 flex-1 min-w-0">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-[#c0ff00]/25 via-emerald-500/15 to-transparent border border-[#c0ff00]/40 shadow-[0_0_25px_rgba(192,255,0,0.3)] flex items-center justify-center shrink-0 backdrop-blur-md">
                <i class="ph-bold ph-buildings text-xl sm:text-2xl text-[#c0ff00]"></i>
              </div>
              <div class="space-y-1 sm:space-y-1.5 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[#c0ff00] animate-pulse"></span>
                  <p class="text-[#c0ff00] font-black text-[10px] sm:text-xs tracking-widest uppercase">Portal Analitik Mitra EduPath</p>
                </div>
                <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-white tracking-tight">Selamat Datang, <span class="bg-gradient-to-r from-white via-slate-100 to-white/70 bg-clip-text text-transparent">{{ schoolData?.name || 'Sekolah Mitra' }}</span></h2>
                <p class="text-white/70 text-xs sm:text-sm font-medium leading-relaxed">Koordinator BK: <strong class="text-white">{{ schoolData?.bk_name || 'Bapak/Ibu Guru BK' }}</strong> · Pantau pemetaan minat bakat Holland RIASEC &amp; kesiapan SNBT siswa secara real-time.</p>
              </div>
            </div>

            <!-- Right: Shortcut Actions -->
            <div class="grid grid-cols-1 sm:flex sm:flex-wrap items-center gap-2.5 sm:gap-3 shrink-0 w-full lg:w-auto">
              <button @click="copyShareLink" class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 sm:px-6 py-3 sm:py-3.5 rounded-2xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black uppercase tracking-wider transition-all active:scale-95 shadow-[0_0_20px_rgba(192,255,0,0.3)] cursor-pointer">
                <i :class="['ph-bold text-base', copiedLink ? 'ph-check' : 'ph-link-simple']"></i>
                <span>{{ copiedLink ? 'Tautan Tersalin!' : 'Bagikan Link SPP' }}</span>
              </button>
              <button @click="openQrModal = true" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 sm:px-5 py-3 sm:py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-bold transition-all active:scale-95 cursor-pointer backdrop-blur-md">
                <i class="ph-bold ph-qr-code text-base text-[#c0ff00]"></i>
                <span>QR Code</span>
              </button>
              <button @click="activeTab = 'students'" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 sm:px-5 py-3 sm:py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-bold transition-all active:scale-95 cursor-pointer backdrop-blur-md">
                <i class="ph-bold ph-users-three text-base text-white/70"></i>
                <span>Roster Siswa</span>
              </button>
            </div>
          </div>

          <!-- 6 Executive KPI Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Total Siswa</span>
              <div class="text-2xl font-black text-white mt-1">{{ kpis?.total_students || 0 }}</div>
              <span class="text-[10px] text-emerald-400 flex items-center gap-1 mt-1 font-bold">
                <i class="ph-bold ph-user-check"></i> Terdata
              </span>
            </div>

            <div class="p-4 rounded-2xl bg-gradient-to-b from-emerald-500/10 to-transparent border border-emerald-500/30 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400 block">Siswa Ikut Bimbel</span>
              <div class="text-2xl font-black text-[#c0ff00] mt-1">{{ kpis?.paid_package_students || 0 }}</div>
              <span class="text-[10px] text-emerald-300 font-semibold mt-1 block">Paket EduPath Aktif</span>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Siswa Free SPP</span>
              <div class="text-2xl font-black text-white mt-1">{{ kpis?.free_diagnostic_students || 0 }}</div>
              <span class="text-[10px] text-white/40 font-semibold mt-1 block">Diagnostik Mandiri</span>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Peserta Tryout</span>
              <div class="text-2xl font-black text-sky-400 mt-1">{{ kpis?.tryout_participants || 0 }}</div>
              <span class="text-[10px] text-sky-300 font-semibold mt-1 block">Simulasi CBT SNBT</span>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Rata-rata Skor</span>
              <div class="text-2xl font-black text-amber-400 mt-1">{{ kpis?.average_tryout_score || 0 }}</div>
              <span class="text-[10px] text-amber-300 font-semibold mt-1 block">Skala SNBT 0 - 1000</span>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-xl">
              <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Selesai SPP</span>
              <div class="text-2xl font-black text-purple-400 mt-1">{{ kpis?.spp_completed_students || 0 }}</div>
              <span class="text-[10px] text-purple-300 font-semibold mt-1 block">Rapor Minat Lengkap</span>
            </div>
          </div>

          <!-- Quick Highlight: Package & RIASEC Summary -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Top Universities & Majors -->
            <div class="lg:col-span-6 p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-[10px] font-black uppercase tracking-wider text-[#c0ff00] block">Pilihan Favorit Siswa</span>
                  <h3 class="text-base font-black text-white">Top 5 Universitas Impian</h3>
                </div>
                <button @click="activeTab = 'ptn'" class="text-xs text-[#c0ff00] font-bold hover:underline cursor-pointer">
                  Lihat Selengkapnya &rarr;
                </button>
              </div>

              <div class="space-y-3">
                <div v-for="(univ, idx) in topUniversities.slice(0, 5)" :key="idx" class="p-3.5 rounded-2xl bg-white/[0.02] border border-white/5 flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span class="w-6 h-6 rounded-lg bg-white/5 text-white/60 font-black text-xs flex items-center justify-center">{{ idx + 1 }}</span>
                    <div>
                      <span class="text-xs font-bold text-white block">{{ univ.name }}</span>
                      <span class="text-[10px] text-white/50">{{ univ.popular_major || 'Program Studi Pilihan' }}</span>
                    </div>
                  </div>
                  <span class="px-2 py-1 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black">{{ univ.count }} Siswa</span>
                </div>
              </div>
            </div>

            <!-- Right: RIASEC Summary Bar Chart -->
            <div class="lg:col-span-6 p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-[10px] font-black uppercase tracking-wider text-purple-400 block">Psikometri Holland Code</span>
                  <h3 class="text-base font-black text-white">Distribusi Karakter Minat (RIASEC)</h3>
                </div>
                <button @click="activeTab = 'riasec'" class="text-xs text-purple-300 font-bold hover:underline cursor-pointer">
                  Detail 6 Dimensi &rarr;
                </button>
              </div>

              <div class="space-y-2.5">
                <div v-for="r in riasecList" :key="r.key" class="space-y-1">
                  <div class="flex justify-between text-xs">
                    <span class="font-bold text-white/80 flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full" :style="{ background: r.color }"></span>
                      {{ r.name }}
                    </span>
                    <span class="font-black text-white">{{ r.count }} siswa ({{ r.pct }}%)</span>
                  </div>
                  <div class="w-full h-2 rounded-full bg-white/5 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500" :style="{ width: r.pct + '%', background: r.color }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- VIEW 2: MONITORING PAKET BELAJAR EDUPATH                   -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'packages'" class="space-y-6">
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
                class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition-all flex items-center gap-1.5 cursor-pointer"
              >
                <span>Lihat Siswa Bimbingan</span>
                <i class="ph-bold ph-arrow-right text-[#c0ff00]"></i>
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Paket 1: Pejuang PTN -->
              <div class="p-5 rounded-2xl bg-gradient-to-br from-[#c0ff00]/10 via-transparent to-transparent border border-[#c0ff00]/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-[#c0ff00] text-black text-[10px] font-black uppercase tracking-wider">Terpopuler</span>
                  <i class="ph-bold ph-trophy text-[#c0ff00] text-lg"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Pejuang PTN</h3>
                <div class="text-3xl font-black text-[#c0ff00] my-2">{{ packageBreakdown?.pejuang_ptn || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Bimbel intensif UTBK, modul drilling soal, tryout berkala IRT, &amp; grup diskusi.</p>
              </div>

              <!-- Paket 2: Mandiri Starter -->
              <div class="p-5 rounded-2xl bg-gradient-to-br from-sky-500/10 via-transparent to-transparent border border-sky-500/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-400 text-[10px] font-black uppercase tracking-wider">Self-Paced</span>
                  <i class="ph-bold ph-book-open text-sky-400 text-lg"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Mandiri Starter</h3>
                <div class="text-3xl font-black text-sky-400 my-2">{{ packageBreakdown?.mandiri || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Akses video materi lengkap, latihan subtes adaptif, &amp; kunci pembahasan analitis.</p>
              </div>

              <!-- Paket 3: Elite Mentoring -->
              <div class="p-5 rounded-2xl bg-gradient-to-br from-purple-500/10 via-transparent to-transparent border border-purple-500/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400 text-[10px] font-black uppercase tracking-wider">Privat / Intensif</span>
                  <i class="ph-bold ph-crown text-purple-400 text-lg"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Elite Mentoring</h3>
                <div class="text-3xl font-black text-purple-400 my-2">{{ packageBreakdown?.elite || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Mentoring 1-on-1 bersama alumni UI/ITB/UGM, review target rasionalisasi jurusan.</p>
              </div>

              <!-- Paket 4: Free SPP Diagnostic -->
              <div class="p-5 rounded-2xl bg-white/[0.02] border border-white/10 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-white/10 text-white/60 text-[10px] font-black uppercase tracking-wider">Diagnostik Bebas Biaya</span>
                  <i class="ph-bold ph-sparkle text-white/40 text-lg"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Free SPP Diagnostic</h3>
                <div class="text-3xl font-black text-white my-2">{{ packageBreakdown?.free_spp || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Hasil asesmen minat bakat Holland Code gratis &amp; pemetaan awal peluang PTN.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- VIEW 3: ROSTER DATA SISWA & REKAP INTERAKTIF              -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'students'" class="space-y-6">
          <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-4">
            <!-- Header Controls: Search & Package Filters -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
              <!-- Search Box -->
              <div class="relative flex-1 max-w-md">
                <i class="ph-bold ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40"></i>
                <input
                  v-model="searchQuery"
                  @input="debounceSearch"
                  type="text"
                  placeholder="Cari nama siswa, target PTN, atau minat..."
                  class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-all"
                />
              </div>

              <!-- Pull-down Dropdown Menu Filter Siswa (Sesuai Permintaan) -->
              <div class="flex items-center gap-2 shrink-0">
                <label for="studentFilterSelect" class="text-white/60 text-xs font-bold flex items-center gap-1.5 whitespace-nowrap">
                  <i class="ph-bold ph-funnel text-[#c0ff00]"></i>
                  <span>Sortir Paket:</span>
                </label>
                <div class="relative">
                  <select
                    id="studentFilterSelect"
                    v-model="filterPackage"
                    @change="fetchStudents(1)"
                    class="appearance-none pl-3.5 pr-9 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer border outline-none bg-[#0c1222] text-white border-white/15 hover:border-white/30 focus:border-[#c0ff00] shadow-lg"
                  >
                    <option value="all" class="bg-[#0c1222] text-white">Semua Siswa</option>
                    <option value="paid" class="bg-[#0c1222] text-[#c0ff00] font-bold">⭐ Ikut Bimbel</option>
                    <option value="pejuang" class="bg-[#0c1222] text-white">Pejuang PTN</option>
                    <option value="mandiri" class="bg-[#0c1222] text-white">Mandiri</option>
                    <option value="elite" class="bg-[#0c1222] text-white">Elite</option>
                    <option value="free" class="bg-[#0c1222] text-white">Free SPP</option>
                  </select>
                  <i class="ph-bold ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-white/50 pointer-events-none text-xs"></i>
                </div>
              </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto rounded-2xl border border-white/10">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-white/10 bg-white/[0.02] text-white/50 text-[10px] font-black uppercase tracking-wider">
                    <th class="p-3.5">No</th>
                    <th class="p-3.5">Nama Siswa</th>
                    <th class="p-3.5">Program / Paket</th>
                    <th class="p-3.5">Minat Holland (RIASEC)</th>
                    <th class="p-3.5">Target PTN</th>
                    <th class="p-3.5">Skor Tryout</th>
                    <th class="p-3.5 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-if="studentsLoading">
                    <td colspan="7" class="p-8 text-center text-white/50">
                      <i class="ph-bold ph-spinner animate-spin text-xl text-[#c0ff00] inline-block mb-1"></i>
                      <p>Memuat data siswa...</p>
                    </td>
                  </tr>
                  <tr v-else-if="students.length === 0">
                    <td colspan="7" class="p-8 text-center text-white/50">
                      <i class="ph-bold ph-users-three text-2xl text-white/20 inline-block mb-1"></i>
                      <p>Belum ada siswa yang cocok dengan kriteria filter.</p>
                    </td>
                  </tr>
                  <tr v-for="(stu, idx) in students" :key="stu.id || idx" class="hover:bg-white/[0.02] transition-colors">
                    <td class="p-3.5 text-white/40 font-mono">{{ (studentPage - 1) * studentLimit + idx + 1 }}</td>
                    <td class="p-3.5">
                      <span class="font-bold text-white block">{{ stu.name }}</span>
                      <span class="text-[10px] text-white/40">{{ stu.email }}</span>
                    </td>
                    <td class="p-3.5">
                      <span :class="['px-2 py-0.5 rounded-md text-[10px] font-black tracking-wider uppercase inline-block', getPackageBadgeClass(stu.package_code)]">
                        {{ stu.package_name }}
                      </span>
                    </td>
                    <td class="p-3.5">
                      <span class="font-mono font-bold text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
                        {{ stu.riasec_badge }}
                      </span>
                    </td>
                    <td class="p-3.5 font-medium text-white/90">
                      {{ stu.target_ptn || 'UI / ITB' }}
                    </td>
                    <td class="p-3.5 font-mono font-bold text-amber-400">
                      {{ stu.latest_tryout_score || '-' }}
                    </td>
                    <td class="p-3.5 text-center">
                      <button
                        @click="openStudentDetail(stu)"
                        class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/15 text-white text-[11px] font-bold border border-white/10 transition-all cursor-pointer"
                      >
                        Detail &amp; WA
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Bar -->
            <div class="flex items-center justify-between text-xs text-white/60 pt-2">
              <span>Menampilkan {{ students.length }} dari {{ studentTotal }} total siswa</span>
              <div class="flex gap-2">
                <button
                  :disabled="studentPage <= 1"
                  @click="fetchStudents(studentPage - 1)"
                  class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 disabled:opacity-40 border border-white/10 cursor-pointer"
                >
                  &larr; Sebelumnya
                </button>
                <button
                  :disabled="studentPage * studentLimit >= studentTotal"
                  @click="fetchStudents(studentPage + 1)"
                  class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 disabled:opacity-40 border border-white/10 cursor-pointer"
                >
                  Selanjutnya &rarr;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- VIEW 4: PEMETAAN RIASEC (HOLLAND CODE)                     -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'riasec'" class="space-y-6">
          <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-6">
            <div>
              <span class="text-xs font-black uppercase tracking-wider text-purple-400 block mb-1">Psikometri Ilmiah 6 Dimensi</span>
              <h2 class="text-xl font-black text-white">Pemetaan Minat Karir &amp; Jurusan Siswa (Holland Code RIASEC)</h2>
              <p class="text-xs text-white/60 mt-1">Gunakan hasil agregat ini untuk memandu sesi bimbingan karir, wali kelas, dan rasionalisasi SNBP/SNBT.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-for="r in riasecList" :key="r.key" class="p-5 rounded-2xl bg-white/[0.02] border border-white/10 space-y-3">
                <div class="flex items-center justify-between">
                  <span class="w-8 h-8 rounded-xl font-black text-black flex items-center justify-center text-sm" :style="{ background: r.color }">
                    {{ r.key }}
                  </span>
                  <span class="text-xs font-black text-white">{{ r.count }} Siswa ({{ r.pct }}%)</span>
                </div>
                <h3 class="text-sm font-black text-white">{{ r.name }}</h3>
                <div class="w-full h-2 rounded-full bg-white/5 overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-500" :style="{ width: r.pct + '%', background: r.color }"></div>
                </div>
                <p class="text-[11px] text-white/60 leading-relaxed">
                  Cocok untuk program studi sains &amp; teknologi, kedokteran, teknik, atau rumpun keilmuan yang linear dengan kepribadian {{ r.key }}.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- VIEW 5: TARGET KAMPUS PTN & PRODI FAVORIT                 -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'ptn'" class="space-y-6">
          <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-6">
            <div>
              <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] block mb-1">Target Pilihan Siswa</span>
              <h2 class="text-xl font-black text-white">Universitas &amp; Rumpun Jurusan Terfavorit</h2>
              <p class="text-xs text-white/60 mt-1">Data agregat pilihan kampus impian yang dimasukkan siswa saat pendaftaran &amp; tryout SNBT.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-for="(u, idx) in topUniversities" :key="idx" class="p-5 rounded-2xl bg-white/[0.02] border border-white/10 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="px-2 py-0.5 rounded-md bg-white/10 text-white font-mono text-[10px] font-black">Rank #{{ idx + 1 }}</span>
                  <span class="text-xs font-black text-[#c0ff00]">{{ u.count }} Peminat</span>
                </div>
                <h3 class="text-sm font-black text-white">{{ u.name }}</h3>
                <p class="text-[11px] text-white/60">Prodi Populer: <strong class="text-white/80">{{ u.popular_major || 'Kedokteran / Teknik Informatika' }}</strong></p>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- VIEW 6: SEBAR LINK & QR CODE SISWA                        -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'broadcast'" class="space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: QR Code & Link Card -->
            <div class="lg:col-span-5 p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl flex flex-col justify-between space-y-4">
              <div>
                <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] block mb-1">Akses Langsung Siswa</span>
                <h3 class="text-lg font-black text-white">Tautan &amp; QR Khusus {{ schoolData?.name }}</h3>
                <p class="text-xs text-white/60 mt-1">Siswa yang mendaftar melalui barcode ini otomatis tercatat di dashboard Anda.</p>
              </div>

              <div class="p-4 bg-white rounded-2xl inline-block mx-auto shadow-2xl my-4">
                <img :src="qrCodeUrl" alt="QR Code Sekolah" class="w-48 h-48 object-contain" />
              </div>

              <div class="space-y-2">
                <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-[11px] font-mono text-white/70 break-all select-all">
                  {{ shareLink }}
                </div>
                <div class="flex gap-2">
                  <button
                    @click="copyShareLink"
                    class="flex-1 py-2 rounded-xl bg-[#c0ff00] text-black font-black text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-lg shadow-[#c0ff00]/20"
                  >
                    <i :class="['ph-bold', copiedLink ? 'ph-check' : 'ph-copy']"></i>
                    <span>{{ copiedLink ? 'Tersalin!' : 'Salin Tautan' }}</span>
                  </button>
                  <a
                    :href="qrCodeUrl"
                    download="qrcode_sekolah.png"
                    target="_blank"
                    class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all flex items-center justify-center gap-1.5"
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
                <h3 class="text-lg font-black text-white">Pesan WhatsApp untuk Grup Kelas / Wali Murid</h3>
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
                  <strong>Tips Bimbingan:</strong> Sebar link ini pada jam wali kelas atau bimbingan karir sekolah. Rapor potensi dan minat langsung terbit otomatis begitu siswa selesai mengisi!
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB: PENGATURAN PROFIL SEKOLAH & GURU BK                       -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'settings'" class="space-y-6 max-w-4xl animate-fade-in">
          <!-- Top Header Bar with Edit / Action Buttons -->
          <div class="flex flex-wrap justify-between items-center gap-3 bg-white/5 p-4 rounded-2xl border border-white/10">
            <div>
              <h2 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                <i class="ph-bold ph-gear text-[#c0ff00]"></i>
                <span>Pengaturan Akun Sekolah &amp; Koordinator BK</span>
              </h2>
              <p class="text-xs text-white/50 mt-0.5">
                {{ isEditingSchoolSettings ? 'Perbarui informasi profil sekolah, kontak koordinator BK, atau kata sandi login di bawah ini.' : 'Informasi identitas sekolah dan kontak resmi guru BK yang terdaftar.' }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button 
                v-if="!isEditingSchoolSettings"
                @click="startEditSchoolSettings" 
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-1.5 cursor-pointer"
              >
                <i class="ph-bold ph-pencil-simple text-sm"></i>
                <span>Edit Pengaturan</span>
              </button>
              <template v-else>
                <button 
                  @click="cancelEditSchoolSettings" 
                  class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl transition-all cursor-pointer"
                >
                  Batal
                </button>
                <button 
                  @click="saveSchoolSettings" 
                  :disabled="isSavingSchoolSettings" 
                  class="px-4 py-2 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-xl transition-all shadow-lg shadow-[#c0ff00]/20 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                  <i class="ph-bold ph-floppy-disk"></i>
                  <span>{{ isSavingSchoolSettings ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
              </template>
            </div>
          </div>

          <!-- 1. VIEW MODE (Profil Sekolah) -->
          <div v-if="!isEditingSchoolSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- View Card 1: Data Koordinator BK -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-user-circle text-indigo-400 text-lg"></i>
                  <h3 class="font-black text-white text-xs uppercase tracking-wider">Koordinator Guru BK</h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Aktif</span>
              </div>
              <div class="space-y-3.5 text-xs">
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Nama Guru BK</span>
                  <div class="text-sm font-bold text-white">{{ schoolData?.bk_name || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Email Login Guru BK</span>
                  <div class="text-sm font-mono text-white/90">{{ schoolData?.bk_email || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Nomor WhatsApp / HP</span>
                  <div class="text-sm font-mono text-[#c0ff00] font-bold">{{ schoolData?.bk_phone || '-' }}</div>
                </div>
              </div>
            </div>

            <!-- View Card 2: Data Lembaga Sekolah -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-graduation-cap text-emerald-400 text-lg"></i>
                  <h3 class="font-black text-white text-xs uppercase tracking-wider">Identitas Sekolah Mitra</h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Mitra Resmi</span>
              </div>
              <div class="space-y-3.5 text-xs">
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Nama Sekolah</span>
                  <div class="text-sm font-bold text-white">{{ schoolData?.name || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Kota / Kabupaten</span>
                  <div class="text-sm font-medium text-white">{{ schoolData?.city || '-' }}</div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">NPSN</span>
                    <div class="text-xs font-mono text-white/80">{{ schoolData?.npsn || '-' }}</div>
                  </div>
                  <div>
                    <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Estimasi Siswa</span>
                    <div class="text-xs font-bold text-white">{{ schoolData?.estimated_students || '200' }} Siswa</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- View Card 3: Keamanan Akun -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-3 md:col-span-2">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-lock-key text-amber-400 text-lg"></i>
                  <h3 class="font-black text-white text-xs uppercase tracking-wider">Keamanan &amp; Kata Sandi</h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">Terenkripsi BCRYPT</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-white/70">Password akun portal BK sekolah Anda terlindungi dengan enkripsi aman standar perbankan.</span>
                <button @click="startEditSchoolSettings" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold underline cursor-pointer">
                  Ubah Kata Sandi &rarr;
                </button>
              </div>
            </div>
          </div>

          <!-- 2. EDIT MODE (Form Profil Sekolah) -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Edit Card 1: Data Koordinator BK -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center gap-2 border-b border-white/10 pb-3">
                <i class="ph-bold ph-user-circle text-indigo-400 text-lg"></i>
                <h3 class="font-black text-white text-xs uppercase tracking-wider">Edit Data Koordinator BK</h3>
              </div>
              <div class="space-y-3 text-xs">
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Koordinator BK</label>
                  <input v-model="schoolSettingsForm.bk_name" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Cth: Dra. H. Nuraeni, M.Pd" />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email Login Koordinator BK</label>
                  <input v-model="schoolSettingsForm.email" type="email" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="bk@sekolah.sch.id" />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nomor WhatsApp / HP</label>
                  <input v-model="schoolSettingsForm.phone" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="08123456789" />
                </div>
              </div>
            </div>

            <!-- Edit Card 2: Identitas Sekolah -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center gap-2 border-b border-white/10 pb-3">
                <i class="ph-bold ph-graduation-cap text-emerald-400 text-lg"></i>
                <h3 class="font-black text-white text-xs uppercase tracking-wider">Edit Identitas Sekolah</h3>
              </div>
              <div class="space-y-3 text-xs">
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Sekolah</label>
                  <input v-model="schoolSettingsForm.name" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Cth: SMAN 1 Ciamis" />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Kota / Kabupaten</label>
                  <input v-model="schoolSettingsForm.city" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Cth: Kabupaten Ciamis" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">NPSN</label>
                    <input v-model="schoolSettingsForm.npsn" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="NPSN Sekolah" />
                  </div>
                  <div>
                    <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Estimasi Siswa</label>
                    <input v-model.number="schoolSettingsForm.estimated_students" type="number" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="200" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Edit Card 3: Keamanan / Ganti Password -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4 md:col-span-2">
              <div class="flex items-center gap-2 border-b border-white/10 pb-3">
                <i class="ph-bold ph-lock-key text-amber-400 text-lg"></i>
                <h3 class="font-black text-white text-xs uppercase tracking-wider">Keamanan &amp; Ganti Password</h3>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password Baru (Kosongkan jika tidak diubah)</label>
                  <input v-model="schoolSettingsForm.new_password" type="password" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Minimal 6 karakter" />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Konfirmasi Password Baru</label>
                  <input v-model="schoolSettingsForm.confirm_password" type="password" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Ulangi password baru" />
                </div>
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
        <button @click="openQrModal = false" class="w-8 h-8 rounded-full bg-white/5 hover:bg-rose-500/20 text-white/50 hover:text-rose-300 border border-white/10 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer absolute top-4 right-4" title="Tutup">
          <i class="ph-bold ph-x text-sm"></i>
        </button>
        <div class="w-10 h-10 rounded-2xl bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center mx-auto mb-3">
          <i class="ph-bold ph-qr-code text-xl"></i>
        </div>
        <h3 class="text-base font-black text-white">{{ schoolData?.name }}</h3>
        <p class="text-xs text-white/60 mb-4">Pindai untuk asesmen minat bakat &amp; diagnostik SNBT</p>

        <div class="p-3 bg-white rounded-2xl inline-block shadow-lg">
          <img :src="qrCodeUrl" alt="QR Code" class="w-48 h-48 object-contain" />
        </div>

        <div class="mt-4 flex gap-2">
          <button @click="copyShareLink" class="flex-1 py-2 rounded-xl bg-[#c0ff00] text-black font-black text-xs cursor-pointer">
            {{ copiedLink ? 'Tersalin!' : 'Salin Link' }}
          </button>
          <button @click="openQrModal = false" class="px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs cursor-pointer">
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
          <button @click="selectedStudent = null" class="w-8 h-8 rounded-full bg-white/5 hover:bg-rose-500/20 text-white/50 hover:text-rose-300 border border-white/10 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
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
          <button @click="selectedStudent = null" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { api } from '../api.js';

// ── State Autentikasi ──
const isAuthenticated = ref(false);
const authLoading = ref(false);
const authError = ref('');
const authSuccess = ref('');
const showPassword = ref(false);

const loginForm = ref({
  email: '',
  password: ''
});

// ── State Layout & Sidebar (Matching AdminPanel.vue) ──
const sidebarOpen = ref(typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);
const activeTab = ref('analytics'); // 'analytics' | 'packages' | 'students' | 'riasec' | 'ptn' | 'broadcast'

const selectNavTab = (tabId) => {
  activeTab.value = tabId;
  if (typeof window !== 'undefined' && window.innerWidth < 1024) {
    sidebarOpen.value = false;
  }
};

const schoolData = ref(null);
const dataLoading = ref(false);
const copiedLink = ref(false);
const copiedBroadcast = ref(false);
const openQrModal = ref(false);

// ── Data Agregat Dashboard ──
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

// ── Computed Navigation Items (Sidebar Layout) ──
const navItems = computed(() => [
  {
    id: 'analytics',
    label: 'Dashboard',
    icon: 'ph-chart-pie-slice'
  },
  {
    id: 'packages',
    label: 'Monitoring Paket',
    icon: 'ph-package',
    badge: `${kpis.value?.paid_package_students || 0} Siswa`,
    badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-500/20 text-emerald-400'
  },
  {
    id: 'students',
    label: 'Roster & Data Siswa',
    icon: 'ph-users-three',
    badge: `${kpis.value?.total_students || 0}`,
    badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-white/10 text-white'
  },
  {
    id: 'riasec',
    label: 'Peminatan RIASEC',
    icon: 'ph-compass',
    badge: '6 Tipe',
    badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-purple-500/20 text-purple-300'
  },
  {
    id: 'ptn',
    label: 'Target Kampus PTN',
    icon: 'ph-buildings',
    badge: 'Top 5',
    badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-sky-500/20 text-sky-300'
  },
  {
    id: 'broadcast',
    label: 'Sebar Link & QR Code',
    icon: 'ph-qr-code'
  },
  {
    id: 'settings',
    label: 'Pengaturan',
    icon: 'ph-gear'
  }
]);

const activeTabTitle = computed(() => {
  const item = navItems.value.find(n => n.id === activeTab.value);
  return item ? item.label : 'Dashboard Sekolah';
});

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
  const savedToken = sessionStorage.getItem('school_token') || localStorage.getItem('auth_token');
  const savedData = sessionStorage.getItem('school_data') || localStorage.getItem('school_data');

  if (savedToken) {
    try {
      if (savedData) {
        schoolData.value = typeof savedData === 'string' ? JSON.parse(savedData) : savedData;
      }
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

async function handleLogout() {
  try {
    if (api && typeof api.logout === 'function') {
      await api.logout().catch(() => {});
    }
  } catch (e) {}

  // Pembersihan menyeluruh semua token sesi dan peran
  sessionStorage.clear();
  const storageKeys = [
    'auth_token', 'ep_session_token', 'ep_csrf', 'ep_token', 'ep_refresh',
    'ep_admin_token', 'ep_admin_csrf', 'admin_token', 'admin_role', 'admin_active_tab',
    'school_token', 'school_data', 'user_role', 'user_email', 'user_name', 'ep_user', 'user'
  ];
  storageKeys.forEach(k => {
    try {
      localStorage.removeItem(k);
      sessionStorage.removeItem(k);
    } catch (e) {}
  });

  isAuthenticated.value = false;
  schoolData.value = null;

  // Arahkan ke beranda dan reload agar state reaktif bersih
  window.location.href = window.location.origin + window.location.pathname + '#/';
  window.location.reload();
}

// ── Settings State & Handlers ──
const isSavingSchoolSettings = ref(false);
const isEditingSchoolSettings = ref(false);
const schoolSettingsForm = reactive({
  name: '',
  bk_name: '',
  email: '',
  phone: '',
  city: '',
  npsn: '',
  estimated_students: 200,
  new_password: '',
  confirm_password: ''
});

function loadSchoolSettings() {
  const s = schoolData.value || {};
  schoolSettingsForm.name = s.name || '';
  schoolSettingsForm.bk_name = s.bk_name || '';
  schoolSettingsForm.email = s.bk_email || '';
  schoolSettingsForm.phone = s.bk_phone || '';
  schoolSettingsForm.city = s.city || '';
  schoolSettingsForm.npsn = s.npsn || '';
  schoolSettingsForm.estimated_students = s.estimated_students || 200;
  schoolSettingsForm.new_password = '';
  schoolSettingsForm.confirm_password = '';
}

function startEditSchoolSettings() {
  loadSchoolSettings();
  isEditingSchoolSettings.value = true;
}

function cancelEditSchoolSettings() {
  isEditingSchoolSettings.value = false;
  loadSchoolSettings();
}

async function saveSchoolSettings() {
  if (!schoolSettingsForm.name.trim() || !schoolSettingsForm.bk_name.trim() || !schoolSettingsForm.email.trim()) {
    alert('Nama Sekolah, Koordinator BK, dan Email wajib diisi.');
    return;
  }
  if (schoolSettingsForm.new_password) {
    if (schoolSettingsForm.new_password.length < 6) {
      alert('Password minimal 6 karakter.');
      return;
    }
    if (schoolSettingsForm.new_password !== schoolSettingsForm.confirm_password) {
      alert('Konfirmasi password tidak cocok.');
      return;
    }
  }

  isSavingSchoolSettings.value = true;
  try {
    const res = await api.updateSchoolProfile({
      name: schoolSettingsForm.name.trim(),
      bk_name: schoolSettingsForm.bk_name.trim(),
      email: schoolSettingsForm.email.trim(),
      phone: schoolSettingsForm.phone.trim(),
      city: schoolSettingsForm.city.trim(),
      npsn: schoolSettingsForm.npsn.trim(),
      estimated_students: schoolSettingsForm.estimated_students,
      password: schoolSettingsForm.new_password
    });
    alert('Pengaturan sekolah & akun BK berhasil disimpan!');
    isEditingSchoolSettings.value = false;
    schoolSettingsForm.new_password = '';
    schoolSettingsForm.confirm_password = '';

    if (res && res.school) {
      schoolData.value = {
        ...(schoolData.value || {}),
        ...res.school
      };
      sessionStorage.setItem('school_data', JSON.stringify(schoolData.value));
      localStorage.setItem('user_name', res.school.bk_name || res.school.name);
    }
  } catch (err) {
    alert(err.message || 'Gagal menyimpan pengaturan.');
  } finally {
    isSavingSchoolSettings.value = false;
  }
}

watch(activeTab, (newTab) => {
  if (newTab === 'settings') {
    loadSchoolSettings();
  }
});

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
