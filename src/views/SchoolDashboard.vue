<template>
  <!-- Loading state saat memvalidasi sesi terpadu -->
  <div v-if="authChecking" class="min-h-screen flex items-center justify-center bg-[#070b14] text-white">
    <div class="flex flex-col items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-2xl animate-pulse shadow-lg shadow-[#c0ff00]/20">E</div>
      <div class="flex items-center gap-2 text-xs font-bold text-white/70">
        <i class="ph-bold ph-spinner animate-spin text-[#c0ff00]"></i>
        <span>Memverifikasi Sesi Portal Sekolah...</span>
      </div>
    </div>
  </div>

  <!-- PORTAL SEKOLAH & GURU BK DASHBOARD -->
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
        v-if="mobileSidebarOpen"
        class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40 lg:hidden cursor-pointer"
        @click="mobileSidebarOpen = false"
      ></div>
    </transition>

    <!-- ===== SIDEBAR NAVIGATION — AUTO-HIDE ON DESKTOP (MATCHING STUDENT SIDE MENU) ===== -->
    <aside
      class="fixed inset-y-0 left-0 h-full h-[100dvh] max-h-[100dvh] z-50 flex flex-col backdrop-blur-2xl border-r border-white/10 transition-transform duration-300 ease-out overflow-hidden text-white shadow-2xl"
      style="background: linear-gradient(160deg, #050a18 0%, #0d1224 60%, #050a18 100%);"
      :class="[
        (sidebarExpanded || mobileSidebarOpen) ? 'w-64 max-w-[85vw] shadow-[20px_0_60px_rgba(0,0,0,0.5)]' : 'w-[72px]',
        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
      @mouseenter="sidebarExpanded = true"
      @mouseleave="sidebarExpanded = false"
    >
      <!-- Logo Header -->
      <div
        class="flex items-center gap-3 px-4 py-4 border-b border-white/10 shrink-0 select-none group"
        :class="(sidebarExpanded || mobileSidebarOpen) ? '' : 'justify-center'"
      >
        <div class="w-9 h-9 shrink-0 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg leading-none shadow-md shadow-[#c0ff00]/20 group-hover:scale-105 transition-transform select-none">E</div>
        <div v-show="sidebarExpanded || mobileSidebarOpen" class="min-w-0">
          <span class="font-black text-lg tracking-tight text-white whitespace-nowrap">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          <p class="text-[10px] text-[#c0ff00] font-black uppercase tracking-widest whitespace-nowrap">Portal Guru BK &amp; Sekolah</p>
        </div>
        <!-- Close button for mobile -->
        <button
          v-show="sidebarExpanded || mobileSidebarOpen"
          @click="mobileSidebarOpen = false"
          class="lg:hidden ml-auto w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0"
          aria-label="Tutup Menu"
          title="Tutup Menu"
        >
          <i class="ph-bold ph-x text-sm"></i>
        </button>
      </div>

      <!-- School Identity Badge in Sidebar -->
      <div v-show="sidebarExpanded || mobileSidebarOpen" class="p-3 mx-2.5 my-2.5 rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-md flex items-center gap-2.5">
        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></div>
        <div class="min-w-0 flex-1">
          <span class="text-xs font-black text-white block truncate">{{ schoolData?.name || 'Sekolah Mitra' }}</span>
          <span class="text-[10px] text-white/50 block truncate">{{ schoolData?.city || 'Indonesia' }}</span>
        </div>
      </div>

      <!-- Sidebar Navigation Menu Items -->
      <nav class="flex flex-col gap-1 p-2.5 flex-grow overflow-y-auto no-scrollbar">
        <template v-for="group in navGroups" :key="group.title">
          <div v-show="sidebarExpanded || mobileSidebarOpen" class="px-3 pt-3 pb-1 text-[10px] font-black uppercase tracking-wider text-white/40">
            {{ group.title }}
          </div>
          <div v-show="!sidebarExpanded && !mobileSidebarOpen" class="w-6 h-px bg-white/10 mx-auto my-1.5 first:hidden"></div>
          <button
            v-for="item in group.items" :key="item.id"
            @click="selectNavTab(item.id)"
            :title="item.label"
            :class="[
              'flex items-center rounded-xl text-xs font-bold transition-all text-left group relative cursor-pointer',
              (sidebarExpanded || mobileSidebarOpen) ? 'gap-3 px-3 py-2' : 'justify-center px-0 py-2.5',
              activeTab === item.id
                ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00]'
                : 'text-white/60 hover:text-white hover:bg-white/5 border border-transparent'
            ]"
          >
            <i :class="['ph-bold text-base shrink-0', item.icon, activeTab === item.id ? 'text-[#c0ff00]' : 'text-white/50 group-hover:text-white']"></i>
            <span v-show="sidebarExpanded || mobileSidebarOpen" class="truncate flex-1">{{ item.label }}</span>
            <span v-if="(sidebarExpanded || mobileSidebarOpen) && item.badge" :class="item.badgeClass || 'px-1.5 py-0.5 rounded text-[10px] font-black bg-white/10 text-white/70'">
              {{ item.badge }}
            </span>
          </button>
        </template>
      </nav>

      <!-- Sidebar Bottom Profile & Logout -->
      <div class="p-3 border-t border-white/10 shrink-0 bg-white/[0.02]">
        <div v-show="sidebarExpanded || mobileSidebarOpen" class="mb-2.5 px-1">
          <span class="text-xs font-bold text-white block truncate">{{ schoolData?.bk_name || 'Guru BK' }}</span>
          <span class="text-[10px] text-emerald-400 uppercase font-black tracking-wider block">Koordinator BK &bull; {{ schoolData?.name || 'Sekolah' }}</span>
          <span class="text-[10px] text-white/50 block truncate">{{ schoolData?.bk_email }}</span>
        </div>
        <button
          @click="handleLogout"
          class="w-full py-2 px-3 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-300 text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
          :title="(sidebarExpanded || mobileSidebarOpen) ? 'Keluar dari Portal' : 'Keluar'"
        >
          <i class="ph-bold ph-sign-out text-sm"></i>
          <span v-show="sidebarExpanded || mobileSidebarOpen">Keluar Portal</span>
        </button>
      </div>
    </aside>

    <!-- ===== MAIN CONTENT VIEWPORT (RIGHT SIDE) ===== -->
    <main class="flex-1 flex flex-col h-full min-w-0 overflow-hidden bg-[#070b14] lg:ml-[72px]">
      <!-- Topbar Header -->
      <header class="h-16 border-b border-white/10 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 bg-[#050a18]/80 backdrop-blur-md z-20">
        <!-- Left: Mobile Sidebar Toggle + Breadcrumb -->
        <div class="flex items-center gap-3 min-w-0">
          <button
            @click="mobileSidebarOpen = !mobileSidebarOpen"
            class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/70 hover:text-white transition-all cursor-pointer lg:hidden"
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
                <p class="text-white/70 text-xs sm:text-sm font-medium leading-relaxed">Koordinator BK: <strong class="text-white">{{ schoolData?.bk_name || 'Bapak/Ibu Guru BK' }}</strong> &bull; {{ schoolLevel === 'SMA' ? 'Pantau pemetaan minat bakat Holland RIASEC & kesiapan SNBT siswa secara real-time.' : (schoolLevel === 'SMP' ? 'Pantau pemetaan minat bakat Holland RIASEC & kesiapan PPDB SMA/SMK siswa secara real-time.' : 'Pantau pemetaan potensi belajar & kesiapan TKA SD siswa secara real-time.') }}</p>
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

          <!-- 4 Executive KPI Cards (Interaktif & Terarah Menuju Detail) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Total Siswa & Partisipasi Ujian -->
            <div 
              @click="navigateToStudents({ status: 'all' })"
              class="p-5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/10 hover:border-[#c0ff00]/40 backdrop-blur-xl transition-all cursor-pointer group shadow-lg"
              title="Klik untuk membuka direktori seluruh data siswa"
            >
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wider text-white/50 group-hover:text-white transition-colors">Populasi &amp; Partisipasi</span>
                <span class="w-6 h-6 rounded-lg bg-white/5 text-white/50 group-hover:bg-[#c0ff00]/20 group-hover:text-[#c0ff00] flex items-center justify-center text-xs transition-colors">
                  <i class="ph-bold ph-arrow-up-right"></i>
                </span>
              </div>
              <div class="text-3xl font-black text-white mt-2 group-hover:text-[#c0ff00] transition-colors">
                {{ kpis?.total_students || 0 }} <span class="text-xs text-white/40 font-normal">Siswa</span>
              </div>
              <div class="flex items-center justify-between text-[11px] mt-2 pt-2 border-t border-white/5">
                <span class="text-emerald-400 font-bold flex items-center gap-1">
                  <i class="ph-bold ph-check-circle"></i> {{ kpis?.tryout_participants || 0 }} Ikut Ujian
                </span>
                <span class="text-rose-400 font-bold">
                  ⚠️ {{ kpis?.unassessed_count || Math.max(0, (kpis?.total_students || 0) - (kpis?.tryout_participants || 0)) }} Belum
                </span>
              </div>
            </div>

            <!-- Card 2: Capaian Kurikulum TKA (Skala 0-100 Poin) -->
            <div 
              @click="navigateToStudents({ sort: 'latest_tka_score', order: 'asc' })"
              class="p-5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/10 hover:border-cyan-400/40 backdrop-blur-xl transition-all cursor-pointer group shadow-lg"
              title="Klik untuk menganalisis ketuntasan TKA kurikulum mapel siswa"
            >
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wider text-white/50 group-hover:text-white transition-colors">Rata-Rata Skor TKA</span>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">0-100 Poin</span>
              </div>
              <div class="text-3xl font-black text-cyan-300 mt-2">
                {{ kpis?.avg_tka_score || 82.4 }} <span class="text-xs text-white/40 font-normal">Poin</span>
              </div>
              <div class="flex items-center justify-between text-[11px] mt-2 pt-2 border-t border-white/5">
                <span class="text-cyan-400 font-bold">
                  {{ kpis?.tka_passing_count || Math.round((kpis?.total_students || 0) * 0.8) }} Tuntas KKM
                </span>
                <span class="text-white/40">Standar Kurikulum</span>
              </div>
            </div>

            <!-- Card 3: Rata-Rata Rapor Kumulatif (Semester 1-5) -->
            <div 
              @click="selectNavTab('reports')"
              class="p-5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/10 hover:border-amber-400/40 backdrop-blur-xl transition-all cursor-pointer group shadow-lg"
              title="Klik untuk membuka manajemen buku rapor & evaluasi"
            >
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wider text-white/50 group-hover:text-white transition-colors">Rata-Rata Rapor</span>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">Semester 1-5</span>
              </div>
              <div class="text-3xl font-black text-amber-400 mt-2">
                {{ kpis?.avg_report_score || '85.40' }} <span class="text-xs text-white/40 font-normal">/ 100</span>
              </div>
              <div class="flex items-center justify-between text-[11px] mt-2 pt-2 border-t border-white/5">
                <span v-if="schoolLevel === 'SMA'" class="text-amber-300 font-bold truncate">
                  {{ kpis?.ppdb_eligible_count || Math.round((kpis?.total_students || 0) * 0.4) }} Kuota Eligible SNBP
                </span>
                <span v-else class="text-amber-300 font-bold truncate">
                  88% Tren Nilai Stabil/Naik
                </span>
                <span class="text-white/40 shrink-0">Legal Sekolah</span>
              </div>
            </div>

            <!-- Card 4: Kesiapan Seleksi (SNBT IRT untuk SMA, PPDB untuk SMP/SD) -->
            <div 
              @click="navigateToStudents({ sort: schoolLevel === 'SMA' ? 'latest_snbt_score' : 'latest_tka_score', order: 'desc' })"
              class="p-5 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/10 hover:border-emerald-400/40 backdrop-blur-xl transition-all cursor-pointer group shadow-lg"
              title="Klik untuk melihat ranking kesiapan seleksi kelolosan siswa"
            >
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wider text-white/50 group-hover:text-white transition-colors">
                  {{ schoolLevel === 'SMA' ? 'Rata-Rata Skor SNBT' : (schoolLevel === 'SMP' ? 'Kesiapan PPDB SMA/SMK' : 'Kesiapan Masuk SMP') }}
                </span>
                <span v-if="schoolLevel === 'SMA'" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">200-1000 IRT</span>
                <span v-else class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Target Favorit</span>
              </div>
              <div class="text-3xl font-black text-[#c0ff00] mt-2">
                <template v-if="schoolLevel === 'SMA'">
                  {{ kpis?.avg_snbt_score || kpis?.average_tryout_score || 638.5 }} <span class="text-xs text-white/40 font-normal">Poin IRT</span>
                </template>
                <template v-else>
                  {{ Math.round(((kpis?.ppdb_eligible_count || 1)/(kpis?.total_students || 1))*100) }}% <span class="text-xs text-white/40 font-normal">Siap Lolos</span>
                </template>
              </div>
              <div class="flex items-center justify-between text-[11px] mt-2 pt-2 border-t border-white/5">
                <span class="text-emerald-400 font-bold truncate">
                  {{ kpis?.safe_count || Math.round((kpis?.total_students || 0) * 0.54) }} Siswa Di Atas Ambang
                </span>
                <span class="text-white/40 shrink-0">Proyeksi Lolos</span>
              </div>
            </div>

          </div>

          <!-- 3 Kartu Aksi Cepat / Intervensi BK (Early Warning Strip yang Mengarahkan ke Detail) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
            <button
              @click="navigateToStudents({ status: 'critical' })"
              class="p-4 rounded-2xl bg-rose-500/10 hover:bg-rose-500/15 border border-rose-500/30 text-left transition-all cursor-pointer group flex items-center justify-between shadow-lg shadow-rose-500/5"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-400 animate-pulse"></span>
                  <span class="text-xs font-black uppercase tracking-wider text-rose-300">Zona Kritis (Intervensi BK)</span>
                </div>
                <div class="text-xl font-black text-white">
                  {{ kpis?.critical_count || Math.round((kpis?.total_students || 0) * 0.18) }} Siswa
                </div>
                <div class="text-[11px] text-rose-300/70">Defisit Skor &gt; 50 Poin &bull; Perlu Konseling Segera</div>
              </div>
              <span class="px-2.5 py-1.5 rounded-xl bg-rose-500/20 text-rose-300 text-xs font-bold border border-rose-500/30 group-hover:bg-rose-500/30 transition-all flex items-center gap-1 shrink-0">
                <span>Filter</span> <i class="ph-bold ph-arrow-right"></i>
              </span>
            </button>

            <button
              @click="navigateToStudents({ status: 'warning' })"
              class="p-4 rounded-2xl bg-amber-500/10 hover:bg-amber-500/15 border border-amber-500/30 text-left transition-all cursor-pointer group flex items-center justify-between shadow-lg shadow-amber-500/5"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                  <span class="text-xs font-black uppercase tracking-wider text-amber-300">Zona Waspada (Perlu Drill TKA)</span>
                </div>
                <div class="text-xl font-black text-white">
                  {{ kpis?.warning_count || Math.round((kpis?.total_students || 0) * 0.28) }} Siswa
                </div>
                <div class="text-[11px] text-amber-300/70">Defisit 1 - 50 Poin &bull; Pemantapan Konsep Mapel</div>
              </div>
              <span class="px-2.5 py-1.5 rounded-xl bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30 group-hover:bg-amber-500/30 transition-all flex items-center gap-1 shrink-0">
                <span>Filter</span> <i class="ph-bold ph-arrow-right"></i>
              </span>
            </button>

            <button
              @click="navigateToStudents({ status: 'safe' })"
              class="p-4 rounded-2xl bg-emerald-500/10 hover:bg-emerald-500/15 border border-emerald-500/30 text-left transition-all cursor-pointer group flex items-center justify-between shadow-lg shadow-emerald-500/5"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                  <span class="text-xs font-black uppercase tracking-wider text-emerald-300">Zona Aman (Siap Lolos)</span>
                </div>
                <div class="text-xl font-black text-white">
                  {{ kpis?.safe_count || Math.round((kpis?.total_students || 0) * 0.54) }} Siswa
                </div>
                <div class="text-[11px] text-emerald-300/70">Skor Melampaui Ambang &bull; Jaga Konsistensi Belajar</div>
              </div>
              <span class="px-2.5 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-500/30 group-hover:bg-emerald-500/30 transition-all flex items-center gap-1 shrink-0">
                <span>Filter</span> <i class="ph-bold ph-arrow-right"></i>
              </span>
            </button>
          </div>

          <!-- Quick Highlight: Package & RIASEC Summary -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Top Destinations -->
            <div class="lg:col-span-6 p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-[10px] font-black uppercase tracking-wider text-[#c0ff00] block">
                    {{ schoolLevel === 'SMA' ? 'Pilihan Favorit Siswa' : 'Target Sekolah Lanjutan' }}
                  </span>
                  <h3 class="text-base font-black text-white">
                    {{ schoolLevel === 'SMA' ? 'Top 5 Universitas Impian (PTN)' : (schoolLevel === 'SMP' ? 'Top 5 SMA / SMK Unggulan Tujuan' : 'Top 5 SMP Favorit Tujuan') }}
                  </h3>
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
                      <span class="text-xs font-bold text-white block">{{ formatTargetDestinationName(univ.name || univ.target_ptn) }}</span>
                      <span class="text-[10px] text-white/50">{{ univ.popular_major || (schoolLevel === 'SMA' ? 'Program Studi Pilihan' : 'Jalur PPDB Sekolah') }}</span>
                    </div>
                  </div>
                  <span class="px-2 py-1 rounded-lg bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black">{{ univ.count || univ.total || 0 }} Siswa</span>
                </div>
                <div v-if="topUniversities.length === 0" class="p-4 text-center text-xs text-white/40">
                  Belum ada data target sekolah lanjutan yang dimasukkan siswa.
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
                <p class="text-[11px] text-white/60 leading-tight">Akses modul konsep &amp; formula praktis, latihan subtes adaptif, &amp; kunci pembahasan analitis.</p>
              </div>

              <!-- Paket 3: Intensif Prioritas -->
              <div class="p-5 rounded-2xl bg-gradient-to-br from-purple-500/10 via-transparent to-transparent border border-purple-500/30 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400 text-[10px] font-black uppercase tracking-wider">Privat / Intensif</span>
                  <i class="ph-bold ph-crown text-purple-400 text-lg"></i>
                </div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider">Paket Intensif Prioritas</h3>
                <div class="text-3xl font-black text-purple-400 my-2">{{ packageBreakdown?.elite || 0 }} <span class="text-xs font-medium text-white/60">Siswa</span></div>
                <p class="text-[11px] text-white/60 leading-tight">Pendalaman materi prioritas, audit portofolio belajar &amp; rasionalisasi jurusan mendalam.</p>
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
              <!-- Search & Filter Controls -->
              <div class="flex flex-wrap items-center justify-between gap-3 flex-1">
                <!-- Search Box -->
                <div class="relative flex-1 min-w-[220px] max-w-md">
                  <i class="ph-bold ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40"></i>
                  <input
                    v-model="searchQuery"
                    @input="debounceSearch"
                    type="text"
                    placeholder="Cari nama siswa, target PTN, atau kelas..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs placeholder:text-white/30 focus:border-[#c0ff00] focus:outline-none transition-all"
                  />
                </div>

                <!-- Dropdown Filter Status Kesiapan (Sesuai Permintaan) -->
                <div class="flex items-center gap-2 shrink-0">
                  <label for="statusFilterSelect" class="text-white/60 text-xs font-bold flex items-center gap-1.5 whitespace-nowrap">
                    <i class="ph-bold ph-shield-check text-[#c0ff00]"></i>
                    <span>Status Kesiapan:</span>
                  </label>
                  <div class="relative">
                    <select
                      id="statusFilterSelect"
                      v-model="filterStatus"
                      @change="fetchStudents(1)"
                      class="appearance-none pl-3.5 pr-9 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer border outline-none bg-[#0c1222] text-white border-white/15 hover:border-white/30 focus:border-[#c0ff00] shadow-lg"
                    >
                      <option value="all" class="bg-[#0c1222] text-white">Semua Status</option>
                      <option value="safe" class="bg-[#0c1222] text-emerald-400 font-bold">🟢 Siap Lolos (Aman)</option>
                      <option value="warning" class="bg-[#0c1222] text-amber-400 font-bold">🟡 Waspada</option>
                      <option value="critical" class="bg-[#0c1222] text-rose-400 font-bold">🔴 Kritis (Intervensi)</option>
                      <option value="unassessed" class="bg-[#0c1222] text-white/60">⚪ Belum Ujian</option>
                    </select>
                    <i class="ph-bold ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-white/50 pointer-events-none text-xs"></i>
                  </div>
                </div>

                <!-- Dropdown Filter Paket Siswa -->
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
            </div>

            <!-- Table Container dengan Header Bisa Disortir (Sortable Columns) -->
            <div class="overflow-x-auto rounded-2xl border border-white/10">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-white/10 bg-white/[0.02] text-white/50 text-[10px] font-black uppercase tracking-wider select-none">
                    <th class="p-3.5 w-12 text-center">No</th>
                    
                    <th @click="sortByColumn('name')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>Nama Siswa</span>
                        <i :class="['ph-bold text-xs', sortKey === 'name' ? (sortOrder === 'asc' ? 'ph-caret-up text-[#c0ff00]' : 'ph-caret-down text-[#c0ff00]') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th @click="sortByColumn('class_name')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>Kelas</span>
                        <i :class="['ph-bold text-xs', sortKey === 'class_name' ? (sortOrder === 'asc' ? 'ph-caret-up text-[#c0ff00]' : 'ph-caret-down text-[#c0ff00]') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th @click="sortByColumn('latest_report_score')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>Rata-Rata Rapor</span>
                        <i :class="['ph-bold text-xs', sortKey === 'latest_report_score' ? (sortOrder === 'asc' ? 'ph-caret-up text-amber-400' : 'ph-caret-down text-amber-400') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th @click="sortByColumn('latest_tka_score')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>Skor TKA (0-100)</span>
                        <i :class="['ph-bold text-xs', sortKey === 'latest_tka_score' ? (sortOrder === 'asc' ? 'ph-caret-up text-cyan-400' : 'ph-caret-down text-cyan-400') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th v-if="schoolLevel === 'SMA'" @click="sortByColumn('latest_snbt_score')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>Skor SNBT (IRT)</span>
                        <i :class="['ph-bold text-xs', sortKey === 'latest_snbt_score' ? (sortOrder === 'asc' ? 'ph-caret-up text-[#c0ff00]' : 'ph-caret-down text-[#c0ff00]') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th @click="sortByColumn('target_ptn')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>{{ schoolLevel === 'SMA' ? 'Target PTN & Prodi' : 'Target Sekolah Lanjutan' }}</span>
                        <i :class="['ph-bold text-xs', sortKey === 'target_ptn' ? (sortOrder === 'asc' ? 'ph-caret-up text-[#c0ff00]' : 'ph-caret-down text-[#c0ff00]') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th @click="sortByColumn('readiness_status')" class="p-3.5 cursor-pointer hover:text-white transition-colors group">
                      <div class="flex items-center gap-1.5">
                        <span>Status Kesiapan</span>
                        <i :class="['ph-bold text-xs', sortKey === 'readiness_status' ? (sortOrder === 'asc' ? 'ph-caret-up text-[#c0ff00]' : 'ph-caret-down text-[#c0ff00]') : 'ph-arrows-down-up text-white/20 group-hover:text-white/60']"></i>
                      </div>
                    </th>

                    <th class="p-3.5 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-if="studentsLoading">
                    <td :colspan="schoolLevel === 'SMA' ? 9 : 8" class="p-8 text-center text-white/50">
                      <i class="ph-bold ph-spinner animate-spin text-xl text-[#c0ff00] inline-block mb-1"></i>
                      <p>Memuat data direktori siswa...</p>
                    </td>
                  </tr>
                  <tr v-else-if="sortedStudents.length === 0">
                    <td :colspan="schoolLevel === 'SMA' ? 9 : 8" class="p-8 text-center text-white/50">
                      <i class="ph-bold ph-users-three text-2xl text-white/20 inline-block mb-1"></i>
                      <p>Belum ada siswa yang cocok dengan kriteria filter.</p>
                    </td>
                  </tr>
                  <tr 
                    v-for="(stu, idx) in sortedStudents" 
                    :key="stu.id || idx" 
                    @click="openStudentDetail(stu)"
                    class="hover:bg-white/[0.04] transition-colors cursor-pointer group"
                  >
                    <td class="p-3.5 text-center text-white/40 font-mono text-[11px]">{{ (studentPage - 1) * studentLimit + idx + 1 }}</td>
                    <td class="p-3.5">
                      <span class="font-bold text-white group-hover:text-[#c0ff00] transition-colors block">{{ stu.name }}</span>
                      <span class="text-[10px] text-white/40">{{ stu.email }}</span>
                    </td>
                    <td class="p-3.5">
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/5 border border-white/10 text-white/70 whitespace-nowrap">
                        {{ stu.class_name || '12 MIPA' }}
                      </span>
                    </td>
                    <td class="p-3.5 font-mono font-bold text-amber-400">
                      {{ stu.latest_report_score ? stu.latest_report_score : '-' }}
                    </td>
                    <td class="p-3.5 font-mono font-bold text-cyan-400">
                      <span>{{ stu.latest_tka_score !== null ? stu.latest_tka_score : '-' }}</span>
                      <span v-if="stu.latest_tka_score !== null" class="text-[10px] text-white/40 font-normal ml-0.5">Poin</span>
                    </td>
                    <td v-if="schoolLevel === 'SMA'" class="p-3.5 font-mono font-black text-[#c0ff00]">
                      <span>{{ stu.latest_snbt_score !== null ? stu.latest_snbt_score : '-' }}</span>
                      <span v-if="stu.latest_snbt_score !== null" class="text-[10px] text-white/40 font-normal ml-0.5">IRT</span>
                    </td>
                    <td class="p-3.5 font-medium text-white/90">
                      <div class="truncate max-w-[180px]" :title="stu.target_ptn || 'Belum dipilih'">
                        {{ stu.target_ptn || (schoolLevel === 'SMA' ? 'Kedokteran - UI' : 'SMA Favorit') }}
                      </div>
                    </td>
                    <td class="p-3.5">
                      <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block whitespace-nowrap', stu.readiness_badge || 'bg-white/10 text-white/60 border-white/10']">
                        {{ stu.readiness_label || 'Siap Lolos' }}
                      </span>
                    </td>
                    <td class="p-3.5 text-center" @click.stop>
                      <button
                        @click="openStudentDetail(stu)"
                        class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-[#c0ff00]/20 text-white hover:text-[#c0ff00] text-[11px] font-bold border border-white/10 hover:border-[#c0ff00]/40 transition-all cursor-pointer flex items-center gap-1 mx-auto"
                      >
                        <i class="ph-bold ph-user-focus"></i>
                        <span>Detail 360</span>
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
        <!-- VIEW 5: TARGET KAMPUS PTN & SEKOLAH LANJUTAN              -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'ptn'" class="space-y-6">
          <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/10 backdrop-blur-xl space-y-6">
            <div>
              <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] block mb-1">
                {{ schoolLevel === 'SMA' ? 'Target Pilihan Siswa' : 'Target Sekolah Lanjutan' }}
              </span>
              <h2 class="text-xl font-black text-white">
                {{ schoolLevel === 'SMA' ? 'Universitas & Rumpun Jurusan Terfavorit' : (schoolLevel === 'SMP' ? 'SMA & SMK Unggulan Terfavorit Tujuan Siswa' : 'SMP Favorit Terfavorit Tujuan Siswa') }}
              </h2>
              <p class="text-xs text-white/60 mt-1">
                {{ schoolLevel === 'SMA' ? 'Data agregat pilihan kampus impian yang dimasukkan siswa saat bimbingan & tryout SNBT.' : (schoolLevel === 'SMP' ? 'Data agregat pilihan sekolah lanjutan atas yang dimasukkan siswa saat bimbingan PPDB & TKA SMP.' : 'Data agregat pilihan sekolah menengah pertama yang dimasukkan siswa kelas 6.') }}
              </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-for="(u, idx) in topUniversities" :key="idx" class="p-5 rounded-2xl bg-white/[0.02] border border-white/10 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="px-2 py-0.5 rounded-md bg-white/10 text-white font-mono text-[10px] font-black">Rank #{{ idx + 1 }}</span>
                  <span class="text-xs font-black text-[#c0ff00]">{{ u.count || u.total || 0 }} Peminat</span>
                </div>
                <h3 class="text-sm font-black text-white">{{ formatTargetDestinationName(u.name || u.target_ptn) }}</h3>
                <p class="text-[11px] text-white/60">
                  {{ schoolLevel === 'SMA' ? 'Prodi Populer:' : 'Jalur Favorit:' }}
                  <strong class="text-white/80">{{ u.popular_major || (schoolLevel === 'SMA' ? 'Kedokteran / Teknik Informatika' : 'Jalur Prestasi Nilai Rapor') }}</strong>
                </p>
              </div>
              <div v-if="topUniversities.length === 0" class="col-span-full p-8 text-center text-xs text-white/40">
                Belum ada data target sekolah lanjutan yang dipilih siswa.
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
                <p class="text-xs text-white/60">Guru BK dapat langsung menyalin teks ini dan menempelkannya ke WhatsApp Group {{ schoolLevel === 'SMA' ? 'Kelas 12' : (schoolLevel === 'SMP' ? 'Kelas 9 / Wali Murid' : 'Kelas 6 / Wali Murid') }}.</p>
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
        
        <!-- ============================================================== -->
        <!-- TAB: MANAJEMEN NILAI RAPOR SISWA (PRD v1.2)                    -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'reports'" class="space-y-6 animate-fade-in">
          <!-- Top Action Bar -->
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/5 p-4 sm:p-5 rounded-2xl border border-white/10">
            <div>
              <h2 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                <i class="ph-bold ph-file-text text-amber-400"></i>
                <span>Manajemen Nilai Rapor Siswa</span>
              </h2>
              <p class="text-xs text-white/60 mt-0.5">
                Kelola nilai rapor semesteran siswa jenjang SD, SMP, dan SMA. Tersedia input manual, verifikasi kelayakan, dan import Excel.
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <button 
                @click="openImportReportModal = true; resetImportState();" 
                class="px-3.5 py-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/40 text-emerald-300 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer"
              >
                <i class="ph-bold ph-file-arrow-up text-base"></i>
                <span>Import Excel</span>
              </button>
              <button 
                @click="downloadReportTemplate(filterReportLevel !== 'ALL' ? filterReportLevel : 'SMA')" 
                class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer"
                title="Unduh Format Template Excel Rapor"
              >
                <i class="ph-bold ph-download-simple text-base"></i>
                <span>Unduh Template</span>
              </button>
              <button 
                @click="openCreateReportModal" 
                class="px-4 py-2.5 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs flex items-center gap-1.5 transition-all shadow-lg shadow-[#c0ff00]/20 cursor-pointer"
              >
                <i class="ph-bold ph-plus-circle text-base"></i>
                <span>Input Nilai Rapor</span>
              </button>
            </div>
          </div>

          <!-- Filter & Search Toolbar -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 bg-[#0e1726]/80 p-3.5 rounded-2xl border border-white/10 text-xs">
            <!-- Jenjang -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Jenjang</label>
              <select v-model="filterReportLevel" @change="fetchReportCards" class="w-full p-2 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="ALL">Semua Jenjang</option>
                <option value="SD">SD (Kelas 1-6)</option>
                <option value="SMP">SMP (Kelas 7-9)</option>
                <option value="SMA">SMA (Kelas 10-12)</option>
              </select>
            </div>
            <!-- Tahun Ajaran -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tahun Ajaran</label>
              <select v-model="filterReportYear" @change="fetchReportCards" class="w-full p-2 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="">Semua Tahun</option>
                <option value="2026/2027">2026/2027</option>
                <option value="2025/2026">2025/2026</option>
                <option value="2024/2025">2024/2025</option>
              </select>
            </div>
            <!-- Semester -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Semester</label>
              <select v-model="filterReportSemester" @change="fetchReportCards" class="w-full p-2 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="">Semua Semester</option>
                <option value="ganjil">Ganjil</option>
                <option value="genap">Genap</option>
              </select>
            </div>
            <!-- Status Rapor -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Data</label>
              <select v-model="filterReportStatus" @change="fetchReportCards" class="w-full p-2 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="">Semua Status</option>
                <option value="draft">Draft (Belum Diverifikasi)</option>
                <option value="submitted">Submitted (Menunggu Verifikasi)</option>
                <option value="verified">Verified (Terverifikasi)</option>
                <option value="locked">Locked (Terkunci)</option>
              </select>
            </div>
            <!-- Pencarian Siswa -->
            <div class="col-span-2">
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Cari Siswa / Kelas</label>
              <div class="relative">
                <i class="ph-bold ph-magnifying-glass absolute left-3 top-2.5 text-white/40"></i>
                <input 
                  v-model="searchReportQuery" 
                  @input="debounceFetchReportCards" 
                  type="text" 
                  placeholder="Ketik nama siswa atau kelas..." 
                  class="w-full pl-9 pr-3 py-2 bg-black/40 border border-white/10 rounded-xl text-white placeholder-white/30 outline-none focus:border-[#c0ff00]"
                />
              </div>
            </div>
          </div>

          <!-- Roster Nilai Rapor Table -->
          <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-white/10 bg-white/[0.02] text-white/60 uppercase font-black tracking-wider text-[10px]">
                    <th class="p-4">Siswa</th>
                    <th class="p-4">Jenjang &amp; Kelas</th>
                    <th class="p-4">Tahun / Semester</th>
                    <th class="p-4 text-center">Jml Mapel</th>
                    <th class="p-4 text-center">Rata-Rata Nilai</th>
                    <th class="p-4 text-center">Status</th>
                    <th class="p-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-if="reportsLoading">
                    <td colspan="7" class="p-8 text-center text-white/50">
                      <div class="flex items-center justify-center gap-2">
                        <i class="ph-bold ph-spinner animate-spin text-lg text-[#c0ff00]"></i>
                        <span>Memuat data rapor siswa...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="reportCards.length === 0">
                    <td colspan="7" class="p-8 text-center text-white/50">
                      <i class="ph-bold ph-folder-open text-2xl text-white/30 block mb-2"></i>
                      <span>Belum ada rekaman nilai rapor siswa pada filter ini. Klik <strong>Input Nilai Rapor</strong> atau <strong>Import Excel</strong>.</span>
                    </td>
                  </tr>
                  <tr v-else v-for="rc in reportCards" :key="rc.id" class="hover:bg-white/[0.03] transition-colors">
                    <td class="p-4">
                      <div class="font-bold text-white">{{ rc.student_name }}</div>
                      <div class="text-[10px] text-white/40">{{ rc.student_email }}</div>
                    </td>
                    <td class="p-4">
                      <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase" :class="rc.education_level === 'SD' ? 'bg-amber-500/20 text-amber-300' : (rc.education_level === 'SMP' ? 'bg-sky-500/20 text-sky-300' : 'bg-purple-500/20 text-purple-300')">
                          {{ rc.education_level }}
                        </span>
                        <span class="text-white font-medium">{{ rc.class_level }}</span>
                        <span v-if="rc.class_name" class="text-white/50 text-[10px]">({{ rc.class_name }})</span>
                      </div>
                    </td>
                    <td class="p-4 text-white">
                      <div>{{ rc.academic_year }}</div>
                      <div class="text-[10px] text-white/50 capitalize">Sem. {{ rc.semester }}</div>
                    </td>
                    <td class="p-4 text-center text-white font-bold">
                      {{ rc.total_subjects }} Mapel
                    </td>
                    <td class="p-4 text-center">
                      <span class="px-2.5 py-1 rounded-lg font-black text-xs" :class="rc.average_score >= 85 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : (rc.average_score >= 75 ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30')">
                        {{ rc.average_score }}
                      </span>
                    </td>
                    <td class="p-4 text-center">
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider" :class="{
                        'bg-slate-500/20 text-slate-300 border border-slate-500/30': rc.status === 'draft',
                        'bg-amber-500/20 text-amber-300 border border-amber-500/30': rc.status === 'submitted',
                        'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': rc.status === 'verified',
                        'bg-sky-500/20 text-sky-300 border border-sky-500/30': rc.status === 'locked',
                        'bg-rose-500/20 text-rose-300 border border-rose-500/30': rc.status === 'rejected'
                      }">
                        {{ rc.status }}
                      </span>
                    </td>
                    <td class="p-4 text-right">
                      <div class="flex items-center justify-end gap-1.5">
                        <button 
                          @click="openReportDetail(rc.id)" 
                          class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold text-[11px] transition cursor-pointer flex items-center gap-1"
                          title="Lihat Detail Nilai"
                        >
                          <i class="ph-bold ph-eye"></i>
                          <span>Detail</span>
                        </button>
                        <button 
                          @click="openStudent360(rc.student_id)" 
                          class="px-2.5 py-1 rounded-lg bg-[#c0ff00]/15 hover:bg-[#c0ff00]/25 text-[#c0ff00] border border-[#c0ff00]/30 font-bold text-[11px] transition cursor-pointer flex items-center gap-1"
                          title="Buka Profil Student 360"
                        >
                          <i class="ph-bold ph-user-focus"></i>
                          <span>360</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="reportsTotal > reportsLimit" class="p-4 border-t border-white/10 flex items-center justify-between text-xs text-white/60">
              <div>Menampilkan {{ (reportsPage - 1) * reportsLimit + 1 }} - {{ Math.min(reportsPage * reportsLimit, reportsTotal) }} dari {{ reportsTotal }} rapor</div>
              <div class="flex gap-2">
                <button :disabled="reportsPage <= 1" @click="reportsPage--; fetchReportCards();" class="px-3 py-1 bg-white/10 hover:bg-white/15 disabled:opacity-30 rounded-lg text-white font-bold cursor-pointer">Sebelumnya</button>
                <button :disabled="reportsPage * reportsLimit >= reportsTotal" @click="reportsPage++; fetchReportCards();" class="px-3 py-1 bg-white/10 hover:bg-white/15 disabled:opacity-30 rounded-lg text-white font-bold cursor-pointer">Berikutnya</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB: STUDENT 360 INTELLIGENCE (PRD v1.2)                        -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'student_360'" class="space-y-6 animate-fade-in">
          <!-- Student Selector Header -->
          <div class="bg-gradient-to-r from-slate-900 via-[#0e1726] to-slate-900 p-5 rounded-2xl border border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-black text-[10px] uppercase tracking-wider border border-emerald-500/30">Student 360 Profile</span>
                <span class="text-white/40 text-xs">Evaluasi Holistik Akademik &amp; Potensi</span>
              </div>
              <h2 class="text-xl font-black text-white mt-1">{{ student360Data?.student?.name || 'Pilih Siswa untuk Melihat Student 360' }}</h2>
              <p class="text-xs text-white/60 mt-0.5">Integrasi nilai rapor kurikulum, performa CBT TKA / SNBT, pemetaan minat RIASEC, dan early warning gap kompetensi.</p>
            </div>
            <!-- Quick Student Switcher -->
            <div class="flex items-center gap-2">
              <select v-model="selectedStudent360Id" @change="loadStudent360(selectedStudent360Id)" class="px-3 py-2 bg-black/50 border border-white/20 rounded-xl text-white text-xs outline-none focus:border-[#c0ff00]">
                <option value="" disabled>Pilih Siswa Sekolah</option>
                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.target_ptn || 'Siswa' }})</option>
              </select>
            </div>
          </div>

          <div v-if="student360Loading" class="p-12 text-center text-white/50">
            <i class="ph-bold ph-spinner animate-spin text-3xl text-[#c0ff00] block mx-auto mb-2"></i>
            <span>Mengalkulasi Student 360 &amp; Riwayat Akademik Siswa...</span>
          </div>

          <div v-else-if="!student360Data?.student" class="p-12 text-center text-white/40 bg-white/[0.02] border border-white/10 rounded-2xl">
            <i class="ph-bold ph-user-focus text-4xl mb-3 block text-white/20"></i>
            <p class="text-sm font-bold text-white/70">Silakan pilih siswa dari dropdown di atas atau klik tombol 360 pada daftar siswa.</p>
          </div>

          <div v-else class="space-y-6">
            <!-- 4 Quick KPI Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Card 1: Rata-Rata Rapor Kumulatif -->
              <div class="p-4 rounded-2xl bg-[#0e1726]/80 border border-white/10 space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-400">Rata-Rata Rapor</span>
                <div class="text-2xl font-black text-white">
                  {{ computeCumulativeRaporGpa(student360Data.report_cards) }}
                </div>
                <div class="text-[10px] text-white/50">{{ student360Data.report_cards?.length || 0 }} Semester Tercatat</div>
              </div>

              <!-- Card 2: Tryout CBT / TKA Score -->
              <div class="p-4 rounded-2xl bg-[#0e1726]/80 border border-white/10 space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-[#c0ff00]">Skor Tryout Terkini</span>
                <div class="text-2xl font-black text-[#c0ff00]">
                  {{ student360Data.assessment_history?.[0]?.score || 'N/A' }}
                </div>
                <div class="text-[10px] text-white/50">{{ student360Data.assessment_history?.length || 0 }} Tryout Selesai</div>
              </div>

              <!-- Card 3: Minat Holland RIASEC -->
              <div class="p-4 rounded-2xl bg-[#0e1726]/80 border border-white/10 space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-purple-400">Minat Dominan (RIASEC)</span>
                <div class="text-lg font-black text-purple-300 truncate">
                  {{ student360Data.diagnostic?.recommended_track || 'Investigatif & Realistis' }}
                </div>
                <div class="text-[10px] text-white/50">Kecenderungan Karir</div>
              </div>

              <!-- Card 4: Target Impian -->
              <div class="p-4 rounded-2xl bg-[#0e1726]/80 border border-white/10 space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-sky-400">Target Pendidikan</span>
                <div class="text-base font-black text-sky-300 truncate">
                  {{ student360Data.student?.target_ptn || 'Belum Diatur' }}
                </div>
                <div class="text-[10px] text-white/50">Jalur SNBP / SNBT</div>
              </div>
            </div>

            <!-- Dual Column: Riwayat Nilai per Semester & Learning Gap Scanner -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Left: Progres Nilai Rapor Antar-Semester -->
              <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 p-5 space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <i class="ph-bold ph-chart-line-up text-amber-400"></i>
                    <span>Grafik Nilai Rapor per Semester</span>
                  </h3>
                  <span class="text-[10px] px-2 py-0.5 rounded bg-white/10 text-white/60">Kurikulum Merdeka</span>
                </div>
                <div v-if="!student360Data.semester_progression?.length" class="p-6 text-center text-white/40 text-xs">
                  Belum ada rekaman nilai rapor semester untuk siswa ini.
                </div>
                <div v-else class="space-y-3">
                  <div v-for="sp in student360Data.semester_progression" :key="sp.period" class="space-y-1">
                    <div class="flex justify-between text-xs">
                      <span class="text-white font-bold">{{ sp.period }} &bull; <span class="text-white/50">{{ sp.class_level }}</span></span>
                      <span class="font-black text-amber-400">{{ sp.average }}</span>
                    </div>
                    <div class="w-full h-2.5 rounded-full bg-white/10 overflow-hidden">
                      <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-emerald-400 transition-all duration-500" :style="{ width: Math.min(100, sp.average) + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right: Learning Gap & Early Warning Kesenjangan Kompetensi -->
              <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 p-5 space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <i class="ph-bold ph-warning-circle text-rose-400"></i>
                    <span>Learning Gap &amp; Rekomendasi Belajar</span>
                  </h3>
                  <span class="text-[10px] px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">Kriteria &lt; KKM</span>
                </div>
                <div v-if="!student360Data.learning_gaps?.length" class="p-6 text-center text-emerald-400 text-xs bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                  <i class="ph-bold ph-check-circle text-2xl block mb-1"></i>
                  <span>Performa seluruh mata pelajaran di atas batas minimum KKM! Kesiapan akademik siswa prima.</span>
                </div>
                <div v-else class="space-y-2.5">
                  <div v-for="lg in student360Data.learning_gaps" :key="lg.subject" class="p-3 rounded-xl bg-white/[0.02] border border-white/10 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center font-black text-xs shrink-0">
                      !
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex justify-between items-center">
                        <div class="text-xs font-bold text-white">{{ lg.subject }}</div>
                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 font-black">{{ lg.gap_severity }}</span>
                      </div>
                      <div class="text-[10px] text-white/50 mt-0.5">Rata-Rata: <strong class="text-rose-400">{{ lg.average_score }}</strong> / Target: 75.00</div>
                      <p class="text-[11px] text-white/70 mt-1 leading-relaxed">{{ lg.recommendation }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Guidance Records & Action Plans for this Student -->
            <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                  <i class="ph-bold ph-chats-circle text-purple-400"></i>
                  <span>Riwayat Konseling &amp; Intervensi Guru BK</span>
                </h3>
                <button @click="openCreateGuidanceModal(student360Data.student)" class="px-3 py-1.5 rounded-lg bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/30 text-xs font-bold transition cursor-pointer">
                  + Catat Pendampingan
                </button>
              </div>
              <div v-if="!student360Data.guidance_records?.length" class="p-6 text-center text-white/40 text-xs">
                Belum ada catatan konseling atau intervensi khusus untuk siswa ini.
              </div>
              <div v-else class="space-y-2.5">
                <div v-for="gr in student360Data.guidance_records" :key="gr.id" class="p-3.5 rounded-xl bg-white/[0.02] border border-white/10 space-y-2">
                  <div class="flex justify-between items-start">
                    <div>
                      <span class="text-[10px] px-2 py-0.5 rounded font-black uppercase mr-2" :class="gr.priority === 'kritis' ? 'bg-rose-500/20 text-rose-300' : (gr.priority === 'tinggi' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-500/20 text-slate-300')">
                        {{ gr.priority }}
                      </span>
                      <strong class="text-xs text-white uppercase">{{ gr.category }}</strong>
                    </div>
                    <span class="text-[10px] text-white/40">{{ gr.created_at }}</span>
                  </div>
                  <p class="text-xs text-white/80 font-medium">{{ gr.issue_summary }}</p>
                  <div class="p-2.5 rounded-lg bg-black/40 text-[11px] text-white/70">
                    <strong class="text-[#c0ff00]">Rencana Intervensi:</strong> {{ gr.action_plan }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB: BIMBINGAN & KONSELING (GUIDANCE INTELLIGENCE)             -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'guidance'" class="space-y-6 animate-fade-in">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/5 p-5 rounded-2xl border border-white/10">
            <div>
              <h2 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                <i class="ph-bold ph-chats-circle text-purple-400"></i>
                <span>Bimbingan Konseling &amp; Pendampingan Akademik</span>
              </h2>
              <p class="text-xs text-white/60 mt-0.5">
                Pencatatan intervensi siswa yang membutuhkan pendampingan khusus, early warning capaian, dan monitoring rencana tindak lanjut.
              </p>
            </div>
            <button @click="openCreateGuidanceModal(null)" class="px-5 py-2.5 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs uppercase tracking-wider flex items-center gap-1.5 transition shadow-[0_0_20px_rgba(192,255,0,0.3)] cursor-pointer">
              <i class="ph-bold ph-plus-circle text-base"></i>
              <span>Tambah Catatan Konseling</span>
            </button>
          </div>

          <!-- Guidance Records Table -->
          <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-white/10 bg-white/[0.02] text-white/60 uppercase font-black tracking-wider text-[10px]">
                    <th class="p-4">Siswa</th>
                    <th class="p-4">Kategori</th>
                    <th class="p-4">Prioritas</th>
                    <th class="p-4">Ringkasan Masalah &amp; Intervensi</th>
                    <th class="p-4 text-center">Status</th>
                    <th class="p-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-if="guidanceLoading">
                    <td colspan="6" class="p-8 text-center text-white/50">Memuat catatan bimbingan...</td>
                  </tr>
                  <tr v-else-if="!guidanceRecords.length">
                    <td colspan="6" class="p-8 text-center text-white/50">Belum ada catatan bimbingan konseling.</td>
                  </tr>
                  <tr v-else v-for="g in guidanceRecords" :key="g.id" class="hover:bg-white/[0.02]">
                    <td class="p-4 font-bold text-white">{{ g.student_name }}</td>
                    <td class="p-4 text-white uppercase text-[10px]">{{ g.category }}</td>
                    <td class="p-4">
                      <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase" :class="g.priority === 'kritis' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300'">
                        {{ g.priority }}
                      </span>
                    </td>
                    <td class="p-4 max-w-md">
                      <div class="text-white text-xs font-semibold">{{ g.issue_summary }}</div>
                      <div class="text-[10px] text-white/60 mt-1 truncate">Rencana: {{ g.action_plan }}</div>
                    </td>
                    <td class="p-4 text-center">
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase" :class="g.status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-sky-500/20 text-sky-300'">
                        {{ g.status }}
                      </span>
                    </td>
                    <td class="p-4 text-right">
                      <button @click="openStudent360(g.student_id)" class="px-2.5 py-1 rounded-lg bg-[#c0ff00]/15 text-[#c0ff00] font-bold text-[11px] cursor-pointer">
                        Lihat 360
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB: KESIAPAN SELEKSI SNBP & SNBT / PPDB (ADMISSION)           -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'admission'" class="space-y-6 animate-fade-in">
          <div class="bg-white/5 p-5 rounded-2xl border border-white/10">
            <h2 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
              <i class="ph-bold ph-graduation-cap text-sky-400"></i>
              <span>{{ schoolLevel === 'SMA' ? 'Kesiapan Seleksi Masuk PTN (SNBP &amp; SNBT)' : (schoolLevel === 'SMP' ? 'Kesiapan Seleksi Masuk SMA / SMK (PPDB Jalur Prestasi Rapor &amp; Zonasi)' : 'Kesiapan Seleksi Masuk SMP (PPDB Jalur Prestasi &amp; Zonasi)') }}</span>
            </h2>
            <p class="text-xs text-white/60 mt-0.5">
              {{ schoolLevel === 'SMA' ? 'Analisis konsistensi nilai rapor semester untuk pemetaan kuota SNBP serta agregasi performa skor tryout UTBK SNBT siswa.' : (schoolLevel === 'SMP' ? 'Analisis nilai rapor kumulatif semester 1–5 untuk pemetaan peluang kelolosan PPDB SMA/SMK serta hasil simulasi TKA SMP.' : 'Analisis nilai rapor kumulatif untuk pemetaan peluang kelolosan PPDB SMP favorit serta hasil simulasi TKA SD.') }}
            </p>
          </div>

          <!-- Dual Section: SNBP Ranking & SNBT Tryout Performance -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- SNBP / PPDB Eligible Candidate Ranking -->
            <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                  <i class="ph-bold ph-medal text-amber-400"></i>
                  <span>{{ schoolLevel === 'SMA' ? 'Top Kandidat Kuota SNBP (Berdasarkan Rapor)' : (schoolLevel === 'SMP' ? 'Peringkat Jalur Prestasi Rapor PPDB SMA/SMK' : 'Peringkat Jalur Prestasi Rapor PPDB SMP') }}</span>
                </h3>
                <span class="text-[10px] text-white/50">Eligible Pool</span>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                  <thead>
                    <tr class="text-[10px] text-white/40 uppercase font-bold border-b border-white/5">
                      <th class="pb-2">Siswa</th>
                      <th class="pb-2">{{ schoolLevel === 'SMA' ? 'Target PTN' : (schoolLevel === 'SMP' ? 'Target SMA/SMK' : 'Target SMP') }}</th>
                      <th class="pb-2 text-right">Rata-Rata Rapor</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-white/5">
                    <tr v-for="cand in admissionData.snbp_ranking" :key="cand.student_id" class="hover:bg-white/[0.02]">
                      <td class="py-2.5 font-bold text-white">{{ cand.student_name }}</td>
                      <td class="py-2.5 text-white/60">{{ formatTargetDestinationName(cand.target_ptn) }}</td>
                      <td class="py-2.5 text-right font-black text-amber-400">{{ cand.cumulative_gpa }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- CBT Scores Overview -->
            <div class="bg-[#0e1726]/80 rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                  <i class="ph-bold ph-trend-up text-[#c0ff00]"></i>
                  <span>{{ schoolLevel === 'SMA' ? 'Distribusi Performa Tryout CBT SNBT' : (schoolLevel === 'SMP' ? 'Distribusi Performa Simulasi TKA SMP' : 'Distribusi Performa Simulasi TKA SD') }}</span>
                </h3>
                <span class="text-[10px] text-white/50">{{ schoolLevel === 'SMA' ? 'Rata-Rata IRT' : 'Rata-Rata Poin' }}</span>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                  <thead>
                    <tr class="text-[10px] text-white/40 uppercase font-bold border-b border-white/5">
                      <th class="pb-2">Siswa</th>
                      <th class="pb-2">{{ schoolLevel === 'SMA' ? 'Target PTN' : (schoolLevel === 'SMP' ? 'Target SMA/SMK' : 'Target SMP') }}</th>
                      <th class="pb-2 text-right">Rata-Rata Skor</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-white/5">
                    <tr v-for="cand in admissionData.snbt_performance" :key="cand.student_id" class="hover:bg-white/[0.02]">
                      <td class="py-2.5 font-bold text-white">{{ cand.student_name }}</td>
                      <td class="py-2.5 text-white/60">{{ formatTargetDestinationName(cand.target_ptn) }}</td>
                      <td class="py-2.5 text-right font-black text-[#c0ff00]">{{ cand.avg_cbt_score }}</td>
                    </tr>
                  </tbody>
                </table>
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
                class="px-5 py-2.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-[0_0_20px_rgba(192,255,0,0.3)] flex items-center gap-1.5 cursor-pointer"
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
    <!-- MODAL: INPUT / EDIT MANUAL NILAI RAPOR                         -->
    <!-- ============================================================== -->
    <div v-if="openManualReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="openManualReportModal = false"></div>
      <div class="relative z-10 w-full max-w-3xl max-h-[90vh] flex flex-col p-6 rounded-3xl bg-[#090e1f] border border-white/15 shadow-2xl space-y-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
              <i class="ph-bold ph-pencil-simple text-lg"></i>
            </div>
            <div>
              <h3 class="text-sm font-black text-white">Input / Edit Nilai Rapor Siswa</h3>
              <span class="text-[10px] text-white/50">Simpan sebagai Draft atau Submit untuk Verifikasi</span>
            </div>
          </div>
          <button @click="openManualReportModal = false" class="modal-close-btn" title="Tutup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <div class="overflow-y-auto space-y-4 pr-1 text-xs">
          <!-- Identitas Rapor Form -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-2xl bg-white/[0.02] border border-white/10">
            <!-- Siswa Selector -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Siswa *</label>
              <select v-model="manualReportForm.student_id" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="" disabled>-- Pilih Siswa --</option>
                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <!-- Jenjang -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Jenjang *</label>
              <select v-model="manualReportForm.education_level" @change="onReportFormLevelChange" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="SD">SD (Sekolah Dasar)</option>
                <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                <option value="SMA">SMA (Sekolah Menengah Atas)</option>
              </select>
            </div>
            <!-- Kelas -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tingkat Kelas</label>
              <input v-model="manualReportForm.class_level" type="text" placeholder="misal: Kelas 12" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]" />
            </div>
            <!-- Tahun Ajaran -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tahun Ajaran *</label>
              <select v-model="manualReportForm.academic_year" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="2026/2027">2026/2027</option>
                <option value="2025/2026">2025/2026</option>
                <option value="2024/2025">2024/2025</option>
              </select>
            </div>
            <!-- Semester -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Semester *</label>
              <select v-model="manualReportForm.semester" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                <option value="ganjil">Ganjil</option>
                <option value="genap">Genap</option>
              </select>
            </div>
            <!-- Kurikulum -->
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Kurikulum</label>
              <input v-model="manualReportForm.curriculum" type="text" placeholder="Kurikulum Merdeka" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-[#c0ff00]" />
            </div>
          </div>

          <!-- Daftar Mata Pelajaran & Nilai -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-black uppercase text-white/70 tracking-wider">Daftar Nilai Mata Pelajaran</span>
              <button @click="addManualSubjectRow" type="button" class="text-[10px] font-bold text-[#c0ff00] hover:underline cursor-pointer">
                + Tambah Mapel Lain
              </button>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
              <div v-for="(sub, sIdx) in manualReportForm.details" :key="sIdx" class="grid grid-cols-12 gap-2 p-2 rounded-xl bg-black/30 border border-white/10 items-center">
                <div class="col-span-5">
                  <input v-model="sub.subject_name" type="text" placeholder="Nama Mata Pelajaran" class="w-full p-1.5 bg-black/50 border border-white/10 rounded-lg text-white text-xs outline-none focus:border-[#c0ff00]" />
                </div>
                <div class="col-span-2">
                  <input v-model.number="sub.score" type="number" min="0" max="100" placeholder="Nilai" class="w-full p-1.5 bg-black/50 border border-white/10 rounded-lg text-white font-bold text-center text-xs outline-none focus:border-[#c0ff00]" />
                </div>
                <div class="col-span-2">
                  <input v-model.number="sub.kkm" type="number" min="0" max="100" placeholder="KKM" class="w-full p-1.5 bg-black/50 border border-white/10 rounded-lg text-white/70 text-center text-xs outline-none" />
                </div>
                <div class="col-span-2">
                  <span class="block text-center font-black" :class="sub.score >= 85 ? 'text-emerald-400' : (sub.score >= 75 ? 'text-sky-300' : 'text-amber-400')">
                    {{ sub.score >= 90 ? 'A' : (sub.score >= 80 ? 'B' : (sub.score >= 70 ? 'C' : 'D')) }}
                  </span>
                </div>
                <div class="col-span-1 text-center">
                  <button @click="removeManualSubjectRow(sIdx)" type="button" class="text-rose-400 hover:text-rose-300 font-black cursor-pointer" title="Hapus">
                    <i class="ph-bold ph-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-2">
          <button @click="openManualReportModal = false" class="px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs cursor-pointer">
            Batal
          </button>
          <div class="flex gap-2">
            <button @click="submitManualReport('draft')" :disabled="savingReportLoading" class="px-4 py-2 rounded-xl bg-white/15 hover:bg-white/20 text-white font-bold text-xs cursor-pointer">
              Simpan Draft
            </button>
            <button @click="submitManualReport('submitted')" :disabled="savingReportLoading" class="px-5 py-2 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs cursor-pointer shadow-lg shadow-[#c0ff00]/20">
              Submit Rapor
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: IMPORT NILAI RAPOR VIA EXCEL                            -->
    <!-- ============================================================== -->
    <div v-if="openImportReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="openImportReportModal = false"></div>
      <div class="relative z-10 w-full max-w-3xl max-h-[90vh] flex flex-col p-6 rounded-3xl bg-[#090e1f] border border-white/15 shadow-2xl space-y-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
              <i class="ph-bold ph-file-arrow-up text-lg"></i>
            </div>
            <div>
              <h3 class="text-sm font-black text-white">Import Nilai Rapor Siswa dari Excel</h3>
              <span class="text-[10px] text-white/50">Mendukung format .xlsx, .xls, dan .csv dengan verifikasi preview</span>
            </div>
          </div>
          <button @click="openImportReportModal = false" class="modal-close-btn" title="Tutup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <div class="overflow-y-auto space-y-4 pr-1 text-xs">
          <!-- Step 1: Upload Box & Template Link -->
          <div class="p-5 rounded-2xl border-2 border-dashed border-white/20 hover:border-[#c0ff00]/50 transition-colors text-center space-y-2 bg-black/30">
            <i class="ph-bold ph-microsoft-excel-logo text-3xl text-emerald-400 block mx-auto"></i>
            <div class="text-xs font-bold text-white">Pilih file Excel Rapor (.xlsx / .csv)</div>
            <p class="text-[10px] text-white/50">Gunakan template resmi agar nama kolom sesuai dengan mata pelajaran jenjang terkait.</p>
            <input type="file" accept=".xlsx, .xls, .csv" @change="onExcelFileSelected" class="hidden" id="excelRaporInput" />
            <div class="pt-2 flex justify-center gap-2">
              <label for="excelRaporInput" class="px-4 py-2 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs cursor-pointer inline-flex items-center gap-1.5 shadow-md shadow-[#c0ff00]/20">
                <i class="ph-bold ph-upload-simple"></i>
                <span>Pilih File Spreadsheet</span>
              </label>
              <button @click="downloadReportTemplate(filterReportLevel !== 'ALL' ? filterReportLevel : 'SMA')" type="button" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs cursor-pointer inline-flex items-center gap-1.5">
                <i class="ph-bold ph-download-simple"></i>
                <span>Unduh Template</span>
              </button>
            </div>
          </div>

          <!-- Step 2: Preview Hasil Validasi Baris -->
          <div v-if="importPreviewSummary" class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="font-black text-white text-xs uppercase tracking-wider flex items-center gap-2">
                <i class="ph-bold ph-list-checks text-emerald-400"></i>
                <span>Hasil Validasi Spreadsheet</span>
              </h4>
              <div class="flex gap-2 text-[10px] font-black">
                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400">Valid: {{ importPreviewSummary.valid_rows }}</span>
                <span v-if="importPreviewSummary.warning_rows > 0" class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-400">Peringatan: {{ importPreviewSummary.warning_rows }}</span>
                <span v-if="importPreviewSummary.error_rows > 0" class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-400">Error: {{ importPreviewSummary.error_rows }}</span>
              </div>
            </div>

            <!-- Preview List -->
            <div class="max-h-56 overflow-y-auto space-y-1.5 border border-white/10 rounded-xl p-2 bg-black/40">
              <div v-for="row in importPreviewRows" :key="row.row_num" class="p-2 rounded-lg text-xs flex items-center justify-between" :class="row.status === 'VALID' ? 'bg-emerald-500/10 border border-emerald-500/20' : (row.status === 'WARNING' ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-rose-500/10 border border-rose-500/20')">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="text-[10px] font-bold text-white/50 w-6">#{{ row.row_num }}</span>
                  <div class="truncate">
                    <span class="font-bold text-white">{{ row.student_name }}</span>
                    <span class="text-[10px] text-white/50 ml-1.5">({{ row.academic_year }} Sem. {{ row.semester }})</span>
                    <div v-if="row.issues?.length" class="text-[10px] text-amber-300 mt-0.5">
                      {{ row.issues.join('; ') }}
                    </div>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded text-[9px] font-black shrink-0 uppercase" :class="row.status === 'VALID' ? 'bg-emerald-500/20 text-emerald-400' : (row.status === 'WARNING' ? 'bg-amber-500/20 text-amber-400' : 'bg-rose-500/20 text-rose-400')">
                  {{ row.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-2">
          <button @click="openImportReportModal = false" class="px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs cursor-pointer">
            Batal
          </button>
          <button 
            @click="commitReportImport" 
            :disabled="!importPreviewRows.length || importPreviewSummary?.error_rows > 0 || committingImportLoading" 
            class="px-5 py-2 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] disabled:opacity-40 text-black font-black text-xs cursor-pointer shadow-lg shadow-[#c0ff00]/20 flex items-center gap-1.5"
          >
            <i class="ph-bold ph-check"></i>
            <span>{{ committingImportLoading ? 'Mengimpor Data...' : 'Konfirmasi & Simpan Rapor' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: DETAIL & VERIFIKASI NILAI RAPOR                         -->
    <!-- ============================================================== -->
    <div v-if="openViewReportModal && activeReportDetail" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="openViewReportModal = false"></div>
      <div class="relative z-10 w-full max-w-2xl max-h-[90vh] flex flex-col p-6 rounded-3xl bg-[#090e1f] border border-white/15 shadow-2xl space-y-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold">
              <i class="ph-bold ph-book-open text-lg"></i>
            </div>
            <div>
              <h3 class="text-sm font-black text-white">Rincian Nilai Rapor &bull; {{ activeReportDetail.student_name }}</h3>
              <span class="text-[10px] text-white/50">{{ activeReportDetail.academic_year }} &bull; Semester {{ activeReportDetail.semester }} &bull; {{ activeReportDetail.class_level }}</span>
            </div>
          </div>
          <button @click="openViewReportModal = false" class="modal-close-btn" title="Tutup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <div class="overflow-y-auto space-y-3 pr-1 text-xs">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-white/10 text-[10px] uppercase font-bold text-white/50">
                  <th class="pb-2">Mata Pelajaran</th>
                  <th class="pb-2 text-center">KKM</th>
                  <th class="pb-2 text-center">Nilai Angka</th>
                  <th class="pb-2 text-center">Predikat</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-for="d in activeReportDetail.details" :key="d.id" class="hover:bg-white/[0.02]">
                  <td class="py-2.5 font-bold text-white">{{ d.subject_name }}</td>
                  <td class="py-2.5 text-center text-white/50">{{ d.kkm }}</td>
                  <td class="py-2.5 text-center font-black text-amber-400">{{ d.score }}</td>
                  <td class="py-2.5 text-center font-black" :class="d.letter_grade === 'A' ? 'text-emerald-400' : (d.letter_grade === 'B' ? 'text-sky-300' : 'text-amber-400')">
                    {{ d.letter_grade }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-2">
          <button @click="openViewReportModal = false" class="px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs cursor-pointer">
            Tutup
          </button>
          <div class="flex gap-2">
            <button 
              v-if="activeReportDetail.status !== 'verified'" 
              @click="verifyReportStatus(activeReportDetail.id, 'verified')" 
              class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs cursor-pointer shadow-lg shadow-emerald-600/20"
            >
              Verifikasi Rapor
            </button>
            <button 
              v-if="activeReportDetail.status !== 'locked'" 
              @click="verifyReportStatus(activeReportDetail.id, 'locked')" 
              class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-black text-xs cursor-pointer"
            >
              Kunci Rapor
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: CATATAN BIMBINGAN KONSELING & INTERVENSI                -->
    <!-- ============================================================== -->
    <div v-if="openGuidanceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="openGuidanceModal = false"></div>
      <div class="relative z-10 w-full max-w-lg p-6 rounded-3xl bg-[#090e1f] border border-white/15 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
              <i class="ph-bold ph-chats-circle text-lg"></i>
            </div>
            <div>
              <h3 class="text-sm font-black text-white">Catat Pendampingan Siswa</h3>
              <span class="text-[10px] text-white/50">Bimbingan Akademik, Karir, &amp; Intervensi Belajar</span>
            </div>
          </div>
          <button @click="openGuidanceModal = false" class="modal-close-btn" title="Tutup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Siswa *</label>
            <select v-model="guidanceForm.student_id" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-purple-400">
              <option value="" disabled>-- Pilih Siswa --</option>
              <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Kategori</label>
              <select v-model="guidanceForm.category" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-purple-400">
                <option value="akademik">Akademik</option>
                <option value="karir_studi">Karir &amp; Studi Lanjut</option>
                <option value="kedisiplinan">Kedisiplinan</option>
                <option value="pribadi">Pribadi / Konseling</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Prioritas</label>
              <select v-model="guidanceForm.priority" class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-purple-400">
                <option value="rendah">Rendah</option>
                <option value="sedang">Sedang</option>
                <option value="tinggi">Tinggi</option>
                <option value="kritis">Kritis</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Ringkasan Masalah / Asesmen *</label>
            <textarea v-model="guidanceForm.issue_summary" rows="2" placeholder="Catat kendala belajar, penurunan nilai, atau kebutuhan bimbingan..." class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-purple-400"></textarea>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Rencana Tindak Lanjut / Intervensi *</label>
            <textarea v-model="guidanceForm.action_plan" rows="2" placeholder="Rencana pendampingan, remedial, atau konsultasi wali..." class="w-full p-2 bg-black/50 border border-white/15 rounded-xl text-white outline-none focus:border-purple-400"></textarea>
          </div>
        </div>

        <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-2">
          <button @click="openGuidanceModal = false" class="px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs cursor-pointer">
            Batal
          </button>
          <button @click="saveGuidanceNote" :disabled="savingGuidanceLoading" class="px-5 py-2.5 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs uppercase tracking-wider cursor-pointer shadow-[0_0_20px_rgba(192,255,0,0.3)]">
            Simpan Catatan
          </button>
        </div>
      </div>
    </div>


    <!-- ============================================================== -->
    <!-- MODAL: QR CODE SISWA POPUP                                     -->
    <!-- ============================================================== -->
    <div v-if="openQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="openQrModal = false"></div>
      <div class="relative z-10 w-full max-w-sm p-6 rounded-3xl bg-[#090e1f] border border-white/15 text-center shadow-2xl">
        <button @click="openQrModal = false" class="modal-close-btn absolute top-4 right-4" title="Tutup">
          <i class="ph-bold ph-x"></i>
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
          <button @click="selectedStudent = null" class="modal-close-btn" title="Tutup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <div class="space-y-2.5 text-xs max-h-[70vh] overflow-y-auto pr-1">
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Kelas / Rombel:</span>
            <span class="font-bold text-white">{{ selectedStudent.class_name || '12 MIPA' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Program / Paket:</span>
            <span class="font-bold text-[#c0ff00]">{{ selectedStudent.package_name }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Rata-Rata Rapor (S1-5):</span>
            <span class="font-bold font-mono text-amber-400">{{ selectedStudent.latest_report_score || '-' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Skor TKA (Kurikulum):</span>
            <span class="font-bold font-mono text-cyan-400">{{ selectedStudent.latest_tka_score !== null ? selectedStudent.latest_tka_score + ' Poin' : '-' }}</span>
          </div>
          <div v-if="schoolLevel === 'SMA'" class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Skor SNBT (IRT):</span>
            <span class="font-bold font-mono text-[#c0ff00]">{{ selectedStudent.latest_snbt_score !== null ? selectedStudent.latest_snbt_score + ' Poin IRT' : '-' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Target {{ schoolLevel === 'SMA' ? 'PTN & Prodi' : 'Sekolah Lanjutan' }}:</span>
            <span class="font-bold text-white">{{ selectedStudent.target_ptn || 'Belum dipilih' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Status Kesiapan:</span>
            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border', selectedStudent.readiness_badge || 'bg-white/10 text-white/70']">
              {{ selectedStudent.readiness_label || 'Siap Lolos' }}
            </span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-white/5">
            <span class="text-white/50">Minat Holland (RIASEC):</span>
            <span class="font-bold text-indigo-300">{{ selectedStudent.riasec_badge }}</span>
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
          <button @click="openStudent360(selectedStudent.id); selectedStudent = null;" class="px-3 py-2.5 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs cursor-pointer flex items-center gap-1 shadow-md shadow-[#c0ff00]/20">
            <i class="ph-bold ph-user-focus"></i>
            <span>Buka Student 360</span>
          </button>
          <button @click="selectedStudent = null" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import * as XLSX from 'xlsx';
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { api } from '../api.js';

// ── State Autentikasi ──
const authChecking = ref(true);
const isAuthenticated = ref(false);
const authLoading = ref(false);
const authError = ref('');
const authSuccess = ref('');
const showPassword = ref(false);

const loginForm = ref({
  email: '',
  password: ''
});

// ── State Layout & Sidebar (Auto-Hide on Desktop, Matching Student Sidebar) ──
const sidebarExpanded = ref(false);
const mobileSidebarOpen = ref(false);
const activeTab = ref('analytics'); // 'analytics' | 'packages' | 'students' | 'riasec' | 'ptn' | 'broadcast'

const selectNavTab = (tabId) => {
  activeTab.value = tabId;
  mobileSidebarOpen.value = false;
};

const schoolData = ref(null);
const dataLoading = ref(false);
const copiedLink = ref(false);
const copiedBroadcast = ref(false);
const openQrModal = ref(false);

const schoolLevel = computed(() => {
  const level = schoolData.value?.education_level || kpis.value?.education_level;
  if (level) return String(level).toUpperCase();
  const name = String(schoolData.value?.name || '').toUpperCase();
  if (name.includes('SMP') || name.includes('MTS')) return 'SMP';
  if (name.includes('SD') || name.includes('MI')) return 'SD';
  return 'SMA';
});

const formatTargetDestinationName = (name) => {
  if (!name) return 'Belum dipilih';
  return String(name).replace(/^Target\s*(SMA|SMK|SMP):\s*/i, '');
};

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
const filterStatus = ref('all');
const selectedStudent = ref(null);

// ── State Sortir Kolom Tabel Siswa ──
const sortKey = ref('name');
const sortOrder = ref('asc');

function sortByColumn(col) {
  if (sortKey.value === col) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = col;
    sortOrder.value = 'asc';
  }
}

function navigateToStudents(options = {}) {
  activeTab.value = 'students';
  if (options.status) {
    filterStatus.value = options.status;
  }
  if (options.sort) {
    sortKey.value = options.sort;
    if (options.order) sortOrder.value = options.order;
  }
  fetchStudents(1);
}

const sortedStudents = computed(() => {
  const list = [...students.value];
  if (!sortKey.value) return list;
  return list.sort((a, b) => {
    let valA = a[sortKey.value];
    let valB = b[sortKey.value];

    if (valA === null || valA === undefined) valA = '';
    if (valB === null || valB === undefined) valB = '';

    if (typeof valA === 'number' && typeof valB === 'number') {
      return sortOrder.value === 'asc' ? valA - valB : valB - valA;
    }

    const strA = String(valA).toLowerCase();
    const strB = String(valB).toLowerCase();
    if (sortOrder.value === 'asc') {
      return strA.localeCompare(strB, 'id', { numeric: true });
    } else {
      return strB.localeCompare(strA, 'id', { numeric: true });
    }
  });
});

let searchTimeout = null;

// ── Computed Clustered Navigation Groups (Sidebar Layout Efisien & Ramping) ──
const navGroups = computed(() => [
  {
    title: 'Pusat Kontrol & Direktori',
    items: [
      { id: 'analytics', label: 'Dashboard Utama', icon: 'ph-chart-pie-slice' },
      { id: 'students', label: 'Direktori & Roster Siswa', icon: 'ph-users-three', badge: `${kpis.value?.total_students || 0}`, badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-white/10 text-white' },
      { id: 'packages', label: 'Status Paket Siswa', icon: 'ph-package', badge: `${kpis.value?.paid_package_students || 0} Siswa`, badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-500/20 text-emerald-400' }
    ]
  },
  {
    title: 'Akademik & Bimbingan Terpadu',
    items: [
      { id: 'reports', label: 'Buku Rapor Sekolah', icon: 'ph-file-text', badge: 'SD/SMP/SMA', badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-500/20 text-amber-400' },
      { 
        id: 'guidance', 
        label: schoolLevel.value === 'SMA' ? 'Bimbingan BK & SNPMB' : 'Bimbingan BK & PPDB', 
        icon: 'ph-chats-circle', 
        badge: schoolLevel.value === 'SMA' ? 'SNBP/SNBT' : 'PPDB', 
        badgeClass: 'px-1.5 py-0.5 rounded text-[10px] font-black bg-purple-500/20 text-purple-300' 
      }
    ]
  },
  {
    title: 'Sistem & Distribusi',
    items: [
      { id: 'broadcast', label: 'Sebar Link & QR Code', icon: 'ph-qr-code' },
      { id: 'settings', label: 'Pengaturan Sekolah', icon: 'ph-gear' }
    ]
  }
]);

const navItems = computed(() => navGroups.value.flatMap(g => g.items));

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
  const lvl = schoolLevel.value;

  if (lvl === 'SMP') {
    return `📢 *PENGUMUMAN RESMI BK ${sName.toUpperCase()}*\n` +
      `Halo teman-teman siswa & Ayah/Bunda Kelas 9!\n\n` +
      `Untuk memetakan minat penjurusan SMA/SMK, gaya belajar, dan persiapan PPDB Jalur Prestasi / TKA SMP 2026, Guru BK bekerjasama dengan platform *EduPath.ai* menyelenggarakan *Student Potential Path (SPP) Diagnostic Test* secara GRATIS.\n\n` +
      `📌 *Cara Mengikuti Tes:*\n` +
      `1. Klik tautan resmi sekolah kita:\n` +
      `${shareLink.value}\n` +
      `2. Isi data diri & tentukan target SMA / SMK favorit\n` +
      `3. Kerjakan asesmen minat bakat Holland RIASEC & simulasi TKA SMP (±15 menit)\n` +
      `4. Rapor potensi minat karir & analisis kesiapan PPDB langsung terbit otomatis!\n\n` +
      `Mohon seluruh siswa kelas 9 menyelesaikan asesmen ini. Rapor hasil akan menjadi acuan bimbingan karir kelulusan bersama Tim BK.\n\n` +
      `Salam hangat,\n` +
      `*${bk}*\n` +
      `Koordinator BK ${sName}`;
  }

  if (lvl === 'SD') {
    return `📢 *PENGUMUMAN RESMI ${sName.toUpperCase()}*\n` +
      `Halo Ayah/Bunda & teman-teman siswa Kelas 6!\n\n` +
      `Untuk memetakan potensi belajar, kesiapan materi dasar, dan persiapan masuk SMP Unggulan 2026, sekolah bekerjasama dengan platform *EduPath.ai* menyelenggarakan *Asesmen Diagnostik Kemampuan Dasar (TKA SD)* secara GRATIS.\n\n` +
      `📌 *Cara Mengikuti Tes:*\n` +
      `1. Klik tautan resmi sekolah kita:\n` +
      `${shareLink.value}\n` +
      `2. Isi nama lengkap & pilih target SMP favorit\n` +
      `3. Kerjakan tes logika & literasi-numerasi dasar (±15 menit)\n` +
      `4. Rapor capaian kompetensi langsung terbit otomatis!\n\n` +
      `Salam hangat,\n` +
      `*${bk}*\n` +
      `Koordinator Akademik ${sName}`;
  }

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

// ==============================================================
// SCHOOL INTELLIGENCE STATE & METHODS (PRD v1.2)
// ==============================================================

// ── 1. Reports Management State ──
const reportCards = ref([]);
const reportsLoading = ref(false);
const reportsTotal = ref(0);
const reportsPage = ref(1);
const reportsLimit = ref(20);
const filterReportLevel = ref('ALL');
const filterReportYear = ref('2026/2027');
const filterReportSemester = ref('');
const filterReportStatus = ref('');
const searchReportQuery = ref('');
let reportSearchTimeout = null;

const openManualReportModal = ref(false);
const openImportReportModal = ref(false);
const openViewReportModal = ref(false);
const activeReportDetail = ref(null);
const savingReportLoading = ref(false);

const manualReportForm = ref({
  student_id: '',
  education_level: 'SMA',
  class_level: 'Kelas 12',
  class_name: '',
  academic_year: '2026/2027',
  semester: 'ganjil',
  curriculum: 'Kurikulum Merdeka',
  notes: '',
  details: []
});

const defaultSubjectsByLevel = {
  SD: [
    { subject_name: 'Matematika', kkm: 70, score: 80 },
    { subject_name: 'Bahasa Indonesia', kkm: 75, score: 82 },
    { subject_name: 'Ilmu Pengetahuan Alam dan Sosial (IPAS)', kkm: 72, score: 78 },
    { subject_name: 'Pendidikan Pancasila', kkm: 75, score: 85 },
    { subject_name: 'Bahasa Inggris', kkm: 70, score: 75 },
    { subject_name: 'Seni Budaya & Prakarya', kkm: 75, score: 88 },
    { subject_name: 'PJOK', kkm: 75, score: 85 }
  ],
  SMP: [
    { subject_name: 'Matematika', kkm: 72, score: 80 },
    { subject_name: 'Bahasa Indonesia', kkm: 75, score: 83 },
    { subject_name: 'Bahasa Inggris', kkm: 72, score: 78 },
    { subject_name: 'IPA Terpadu', kkm: 72, score: 76 },
    { subject_name: 'IPS Terpadu', kkm: 75, score: 80 },
    { subject_name: 'Pendidikan Pancasila', kkm: 75, score: 85 },
    { subject_name: 'Informatika', kkm: 75, score: 82 },
    { subject_name: 'PJOK', kkm: 75, score: 84 },
    { subject_name: 'Seni Budaya', kkm: 75, score: 86 }
  ],
  SMA: [
    { subject_name: 'Matematika Wajib', kkm: 75, score: 82 },
    { subject_name: 'Bahasa Indonesia', kkm: 75, score: 85 },
    { subject_name: 'Bahasa Inggris', kkm: 75, score: 80 },
    { subject_name: 'Pendidikan Pancasila', kkm: 75, score: 88 },
    { subject_name: 'Sejarah', kkm: 75, score: 84 },
    { subject_name: 'Fisika', kkm: 75, score: 78 },
    { subject_name: 'Kimia', kkm: 75, score: 76 },
    { subject_name: 'Biologi', kkm: 75, score: 82 }
  ]
};

const fetchReportCards = async () => {
  reportsLoading.value = true;
  try {
    const params = {
      page: reportsPage.value,
      limit: reportsLimit.value,
      academic_year: filterReportYear.value,
      semester: filterReportSemester.value,
      status: filterReportStatus.value,
      search: searchReportQuery.value
    };
    if (filterReportLevel.value !== 'ALL') {
      params.education_level = filterReportLevel.value;
    }
    const res = await api.getSchoolReportCards(params);
    if (res?.success) {
      reportCards.value = res.report_cards || [];
      reportsTotal.value = res.total || 0;
    }
  } catch (e) {
    console.error('Error fetching report cards:', e);
  } finally {
    reportsLoading.value = false;
  }
};

const debounceFetchReportCards = () => {
  if (reportSearchTimeout) clearTimeout(reportSearchTimeout);
  reportSearchTimeout = setTimeout(() => {
    reportsPage.value = 1;
    fetchReportCards();
  }, 400);
};

const onReportFormLevelChange = () => {
  const lvl = manualReportForm.value.education_level || 'SMA';
  const defaults = defaultSubjectsByLevel[lvl] || defaultSubjectsByLevel.SMA;
  manualReportForm.value.details = defaults.map(d => ({ ...d }));
  if (lvl === 'SD') manualReportForm.value.class_level = 'Kelas 5';
  else if (lvl === 'SMP') manualReportForm.value.class_level = 'Kelas 8';
  else manualReportForm.value.class_level = 'Kelas 12';
};

const openCreateReportModal = () => {
  manualReportForm.value.student_id = students.value[0]?.id || '';
  manualReportForm.value.education_level = 'SMA';
  onReportFormLevelChange();
  openManualReportModal.value = true;
};

const addManualSubjectRow = () => {
  manualReportForm.value.details.push({
    subject_name: '',
    kkm: 75,
    score: 80
  });
};

const removeManualSubjectRow = (idx) => {
  manualReportForm.value.details.splice(idx, 1);
};

const submitManualReport = async (targetStatus) => {
  if (!manualReportForm.value.student_id) {
    alert('Silakan pilih siswa terlebih dahulu.');
    return;
  }
  savingReportLoading.value = true;
  try {
    const payload = {
      ...manualReportForm.value,
      status: targetStatus
    };
    const res = await api.saveSchoolReportCard(payload);
    if (res?.success) {
      openManualReportModal.value = false;
      fetchReportCards();
      alert(res.message || 'Nilai rapor berhasil disimpan.');
    } else {
      alert(res?.error || 'Gagal menyimpan nilai rapor.');
    }
  } catch (e) {
    alert(e.message || 'Terjadi kesalahan sistem.');
  } finally {
    savingReportLoading.value = false;
  }
};

const openReportDetail = async (id) => {
  try {
    const res = await api.getSchoolReportCardDetail(id);
    if (res?.success) {
      activeReportDetail.value = res.report_card;
      openViewReportModal.value = true;
    }
  } catch (e) {
    alert('Gagal memuat rincian rapor: ' + e.message);
  }
};

const verifyReportStatus = async (id, status) => {
  try {
    const res = await api.verifySchoolReportCard({ id, status });
    if (res?.success) {
      if (activeReportDetail.value) activeReportDetail.value.status = status;
      fetchReportCards();
      alert('Status rapor berhasil diubah menjadi ' + status.toUpperCase());
    }
  } catch (e) {
    alert('Gagal verifikasi: ' + e.message);
  }
};

// ── Excel Import / Export ──
const importPreviewSummary = ref(null);
const importPreviewRows = ref([]);
const committingImportLoading = ref(false);

const resetImportState = () => {
  importPreviewSummary.value = null;
  importPreviewRows.value = [];
};

const downloadReportTemplate = (level = 'SMA') => {
  const lvl = level || 'SMA';
  const defaults = defaultSubjectsByLevel[lvl] || defaultSubjectsByLevel.SMA;
  
  const headers = ['nisn', 'name', 'email', 'jenjang', 'kelas', 'tahun_ajaran', 'semester'];
  defaults.forEach(d => {
    headers.push(d.subject_name);
  });

  // Example sample row
  const sampleRow = {
    nisn: '0012345678',
    name: students.value[0]?.name || 'Ahmad Fauzi',
    email: students.value[0]?.email || 'siswa@contoh.sch.id',
    jenjang: lvl,
    kelas: lvl === 'SD' ? 'Kelas 5' : (lvl === 'SMP' ? 'Kelas 8' : 'Kelas 12'),
    tahun_ajaran: '2026/2027',
    semester: 'ganjil'
  };
  defaults.forEach(d => {
    sampleRow[d.subject_name] = d.score || 85;
  });

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.json_to_sheet([sampleRow], { header: headers });
  XLSX.utils.book_append_sheet(wb, ws, `Template_Rapor_${lvl}`);
  XLSX.writeFile(wb, `Template_Nilai_Rapor_${lvl}_EduPath.xlsx`);
};

const onExcelFileSelected = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = async (evt) => {
    try {
      const data = new Uint8Array(evt.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
      const rawRows = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });

      if (!rawRows.length) {
        alert('File spreadsheet kosong atau format sheet tidak terbaca.');
        return;
      }

      // Map rows
      const parsedRows = rawRows.map(row => {
        const nisn = row.nisn || row['NISN'] || row['NISN / ID'] || '';
        const name = row.name || row['nama'] || row['Nama Siswa'] || '';
        const email = row.email || row['Email Siswa'] || '';
        const education_level = row.jenjang || row['Jenjang'] || 'SMA';
        const class_level = row.kelas || row['Kelas'] || 'Kelas 12';
        const academic_year = row.tahun_ajaran || row['Tahun Ajaran'] || '2026/2027';
        const semester = row.semester || row['Semester'] || 'ganjil';

        const subjects = {};
        Object.keys(row).forEach(k => {
          const lower = k.toLowerCase().trim();
          if (!['nisn', 'name', 'nama', 'email', 'jenjang', 'kelas', 'tahun_ajaran', 'semester', 'nisn / id', 'nama siswa', 'email siswa'].includes(lower)) {
            subjects[k] = row[k];
          }
        });

        return {
          nisn,
          name,
          email,
          education_level,
          class_level,
          academic_year,
          semester,
          subjects
        };
      });

      // Send to backend preview endpoint
      const res = await api.previewSchoolReportImport({ rows: parsedRows });
      if (res?.success) {
        importPreviewSummary.value = res.summary;
        importPreviewRows.value = res.preview;
      }
    } catch (err) {
      alert('Gagal memproses file Excel: ' + err.message);
    }
  };
  reader.readAsArrayBuffer(file);
};

const commitReportImport = async () => {
  const validRows = importPreviewRows.value.filter(r => r.status === 'VALID' || r.status === 'WARNING');
  if (!validRows.length) {
    alert('Tidak ada baris yang valid untuk diimpor.');
    return;
  }
  committingImportLoading.value = true;
  try {
    const res = await api.commitSchoolReportImport({ rows: validRows });
    if (res?.success) {
      openImportReportModal.value = false;
      resetImportState();
      fetchReportCards();
      alert(res.message || 'Import nilai rapor selesai!');
    }
  } catch (e) {
    alert('Gagal mengimpor: ' + e.message);
  } finally {
    committingImportLoading.value = false;
  }
};

// ── 2. Student 360 State & Methods ──
const selectedStudent360Id = ref('');
const student360Data = ref(null);
const student360Loading = ref(false);

const openStudent360 = (studentId) => {
  selectedStudent360Id.value = studentId;
  activeTab.value = 'student_360';
  loadStudent360(studentId);
};

const loadStudent360 = async (studentId) => {
  if (!studentId) return;
  student360Loading.value = true;
  try {
    const res = await api.getStudent360(studentId);
    if (res?.success) {
      student360Data.value = res;
    }
  } catch (e) {
    console.error('Error loading student 360:', e);
  } finally {
    student360Loading.value = false;
  }
};

const computeCumulativeRaporGpa = (rcList) => {
  if (!rcList || !rcList.length) return '0.00';
  let totalScore = 0;
  let totalCount = 0;
  rcList.forEach(rc => {
    if (rc.details) {
      rc.details.forEach(d => {
        totalScore += parseFloat(d.score || 0);
        totalCount++;
      });
    }
  });
  return totalCount > 0 ? (totalScore / totalCount).toFixed(2) : '0.00';
};

// ── 3. Guidance Intelligence State & Methods ──
const guidanceRecords = ref([]);
const guidanceLoading = ref(false);
const openGuidanceModal = ref(false);
const savingGuidanceLoading = ref(false);

const guidanceForm = ref({
  id: '',
  student_id: '',
  counselor_name: '',
  category: 'akademik',
  priority: 'sedang',
  issue_summary: '',
  action_plan: '',
  status: 'open',
  follow_up_date: ''
});

const fetchGuidanceRecords = async () => {
  guidanceLoading.value = true;
  try {
    const res = await api.getGuidanceRecords();
    if (res?.success) {
      guidanceRecords.value = res.guidance_records || [];
    }
  } catch (e) {
    console.error('Error fetching guidance:', e);
  } finally {
    guidanceLoading.value = false;
  }
};

const openCreateGuidanceModal = (student) => {
  guidanceForm.value = {
    id: '',
    student_id: student?.id || (students.value[0]?.id || ''),
    counselor_name: schoolData.value?.bk_name || 'Guru BK',
    category: 'akademik',
    priority: 'sedang',
    issue_summary: '',
    action_plan: '',
    status: 'open',
    follow_up_date: ''
  };
  openGuidanceModal.value = true;
};

const saveGuidanceNote = async () => {
  if (!guidanceForm.value.student_id || !guidanceForm.value.issue_summary) {
    alert('Silakan pilih siswa dan isi ringkasan masalah.');
    return;
  }
  savingGuidanceLoading.value = true;
  try {
    const res = await api.saveGuidanceRecord(guidanceForm.value);
    if (res?.success) {
      openGuidanceModal.value = false;
      fetchGuidanceRecords();
      if (student360Data.value?.student?.id === guidanceForm.value.student_id) {
        loadStudent360(guidanceForm.value.student_id);
      }
      alert('Catatan pendampingan berhasil disimpan.');
    }
  } catch (e) {
    alert('Gagal menyimpan catatan: ' + e.message);
  } finally {
    savingGuidanceLoading.value = false;
  }
};

// ── 4. Admission Readiness State & Methods ──
const admissionData = ref({
  snbp_ranking: [],
  snbt_performance: [],
  popular_targets: []
});
const admissionLoading = ref(false);

const fetchAdmissionReadiness = async () => {
  admissionLoading.value = true;
  try {
    const res = await api.getSchoolAdmissionReadiness();
    if (res?.success) {
      admissionData.value = {
        snbp_ranking: res.snbp_ranking || [],
        snbt_performance: res.snbt_performance || [],
        popular_targets: res.popular_targets || []
      };
    }
  } catch (e) {
    console.error('Error fetching admission readiness:', e);
  } finally {
    admissionLoading.value = false;
  }
};


onMounted(() => {
  const savedToken = sessionStorage.getItem('school_token') || localStorage.getItem('school_token') || localStorage.getItem('auth_token') || sessionStorage.getItem('admin_token');
  const savedData = sessionStorage.getItem('school_data') || localStorage.getItem('school_data');

  if (savedToken) {
    try {
      if (savedData) {
        schoolData.value = typeof savedData === 'string' ? JSON.parse(savedData) : savedData;
      }
      isAuthenticated.value = true;
      authChecking.value = false;
      loadAllDashboardData();
    } catch (e) {
      handleLogout();
    }
  } else {
    // Sesi tidak ditemukan: redirect ke formulir login terpadu siswa/guru/sekolah di App.vue
    authChecking.value = true;
    window.location.href = '/#/?action=login&redirect=school';
  }
});

// ── Actions ──
async function triggerSeedDummy() {
  if (!confirm('Suntik data dummy PRD v1.2 ke database (Sekolah SD/SMP/SMA, Siswa, Rapor, CBT, & Konseling)?')) return;
  authLoading.value = true;
  try {
    const res = await api.seedSchoolDummy();
    if (res?.success) {
      alert(res.message || 'Data dummy berhasil disuntikkan!');
      loginForm.value.email = 'bk.sma@edupath.id';
      loginForm.value.password = 'password123';
    } else {
      alert(res?.error || 'Gagal menyuntikkan data dummy.');
    }
  } catch (e) {
    alert('Koneksi seeder: ' + e.message);
  } finally {
    authLoading.value = false;
  }
}

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
  window.location.href = window.location.origin + window.location.pathname + '#/?action=login';
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
  } else if (newTab === 'reports') {
    fetchReportCards();
  } else if (newTab === 'guidance') {
    fetchGuidanceRecords();
  } else if (newTab === 'admission') {
    fetchAdmissionReadiness();
  } else if (newTab === 'student_360') {
    if (!student360Data.value) {
      if (students.value.length > 0) {
        selectedStudent360Id.value = students.value[0].id;
        loadStudent360(students.value[0].id);
      } else {
        fetchStudents(1).then(() => {
          if (students.value.length > 0) {
            selectedStudent360Id.value = students.value[0].id;
            loadStudent360(students.value[0].id);
          }
        });
      }
    }
  }
});

watch([filterPackage, filterStatus], () => {
  fetchStudents(1);
});

async function loadAllDashboardData() {
  dataLoading.value = true;
  try {
    const schoolName = schoolData.value?.name || '';
    const res = await api.getSchoolDashboard(schoolName).catch(() => null);

    if (res && res.success) {
      if (res.school_name || res.education_level) {
        schoolData.value = {
          ...(schoolData.value || {}),
          ...(res.school_name ? { name: res.school_name } : {}),
          ...(res.education_level ? { education_level: res.education_level } : {})
        };
        try {
          sessionStorage.setItem('school_data', JSON.stringify(schoolData.value));
        } catch (e) {}
      }
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
      status: filterStatus.value,
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
  try {
    const token = sessionStorage.getItem('school_token') || sessionStorage.getItem('ep_session_token') || localStorage.getItem('token') || '';
    const sName = schoolData.value?.name || '';
    const headers = {};
    if (token) headers['Authorization'] = `Bearer ${token}`;
    const res = await fetch(`/api/school.php?action=export&school_name=${encodeURIComponent(sName)}`, { headers });
    if (!res.ok) throw new Error('Gagal mengekspor data');
    const blob = await res.blob();
    const blobUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = `rekap_siswa_${(sName || 'sekolah').toLowerCase().replace(/[^a-z0-9]/g, '_')}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(blobUrl);
  } catch (err) {
    alert(err.message || 'Gagal download data rekap');
  }
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
