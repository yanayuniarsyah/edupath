<template>
  <!-- ===== LOGOUT TRANSITION OVERLAY ===== -->
  <div v-if="isLoggingOut" class="fixed inset-0 z-[999] bg-[#050a18] flex flex-col items-center justify-center text-white space-y-3">
    <div class="w-9 h-9 rounded-full border-2 border-[#c0ff00] border-t-transparent animate-spin"></div>
    <span class="text-xs font-black text-white/70 tracking-widest uppercase">Mengalihkan ke Halaman Utama...</span>
  </div>

  <!-- ===== LOGIN GATE ===== -->
  <div v-if="!isAuthenticated && !isLoggingOut" class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #050a18 0%, #0d1224 60%, #050a18 100%);">
    <!-- Glow decorations -->
    <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #c0ff00 0%, transparent 70%);"></div>
    <div class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full opacity-15 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #6366f1 0%, transparent 70%);"></div>

    <div class="relative z-10 w-full max-w-sm mx-4">
      <!-- Login Card -->
      <div class="rounded-3xl p-8 border relative" style="background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); backdrop-filter: blur(20px);">
        <!-- Close button -->
        <button @click="$router.push('/')" class="!absolute top-5 right-5 z-50 w-8 h-8 flex items-center justify-center rounded-full bg-white/5 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition-colors border border-transparent hover:border-rose-500/30 text-sm">
          <i class="ph-bold ph-x"></i>
        </button>

        <!-- Logo & Header -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center gap-2.5 group select-none mb-2">
            <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20">E</div>
            <span class="font-black text-2xl tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          </div>
          <h2 class="text-white font-black text-lg">Masuk sebagai Admin</h2>
          <p class="text-white/40 text-xs font-semibold uppercase tracking-widest mt-1">Admin Panel Portal</p>
        </div>

        <!-- Error Message -->
        <div v-if="loginError" class="mb-4 px-4 py-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs font-bold flex items-center gap-2">
          <i class="ph-bold ph-warning-circle"></i>
          {{ loginError }}
        </div>

        <form @submit.prevent="doLogin" class="space-y-4">
          <div>
            <label for="admin_login_username" class="block text-white/60 text-xs font-bold mb-1.5 uppercase tracking-wider">Email atau Username</label>
            <div class="relative">
              <i class="ph-bold ph-user absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                id="admin_login_username"
                name="username"
                v-model="loginForm.username"
                type="text"
                placeholder="superadmin@uat.edupath.local / username"
                autocomplete="username"
                class="w-full pl-9 pr-4 py-3 rounded-xl text-sm font-medium text-white outline-none transition-all"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
                :style="loginError ? 'border-color: rgba(239,68,68,0.5);' : ''"
              />
            </div>
          </div>
          <div class="mt-4">
            <label for="admin_login_password" class="block text-white/60 text-xs font-bold mb-1.5 uppercase tracking-wider">Password</label>
            <div class="relative">
              <i class="ph-bold ph-lock absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                id="admin_login_password"
                name="password"
                v-model="loginForm.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                autocomplete="current-password"
                class="w-full pl-9 pr-10 py-3 rounded-xl text-sm font-medium text-white outline-none transition-all"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
                :style="loginError ? 'border-color: rgba(239,68,68,0.5);' : ''"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors cursor-pointer select-none p-1" :title="showPassword ? 'Sembunyikan password' : 'Lihat password'">
                <svg v-if="!showPassword" class="w-5 h-5 text-white/60 hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5 text-[#c0ff00] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Quick Fill Demo Accounts -->
          <div class="pt-1">
            <span class="block text-[10px] uppercase font-black text-white/40 tracking-wider mb-1.5">Pilih Akun Demo UAT:</span>
            <div class="grid grid-cols-3 gap-1.5">
              <button
                type="button"
                @click="fillCredentials('superadmin@uat.edupath.local', 'EduPathSuperAdmin01!2026')"
                class="px-2 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 hover:border-[#c0ff00]/40 text-[10px] font-bold text-white/80 hover:text-[#c0ff00] transition-all text-center truncate"
                title="Super Admin: superadmin@uat.edupath.local"
              >
                👑 Superadmin
              </button>
              <button
                type="button"
                @click="fillCredentials('admin@uat.edupath.local', 'EduPathAdmin01!2026')"
                class="px-2 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 hover:border-sky-400/40 text-[10px] font-bold text-white/80 hover:text-sky-300 transition-all text-center truncate"
                title="Admin Tenant: admin@uat.edupath.local"
              >
                🏢 Admin
              </button>
              <button
                type="button"
                @click="fillCredentials('tutor@uat.edupath.local', 'EduPathTutor01!2026')"
                class="px-2 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 hover:border-emerald-400/40 text-[10px] font-bold text-white/80 hover:text-emerald-300 transition-all text-center truncate"
                title="Guru / Tutor: tutor@uat.edupath.local"
              >
                👨‍🏫 Guru
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loginLoading"
            class="w-full py-3 rounded-xl font-black text-sm text-black transition-all hover:opacity-90 active:scale-95 mt-2 disabled:opacity-50"
            style="background: #c0ff00;"
          >
            <span v-if="!loginLoading">Masuk ke Admin Panel</span>
            <span v-else class="flex items-center justify-center gap-2"><i class="ph-bold ph-spinner animate-spin"></i> Memverifikasi...</span>
          </button>

        </form>

        <div class="mt-5 pt-4 border-t border-white/8 text-center">
          <p class="text-white/30 text-[11px] font-medium">Akses terbatas untuk administrator & guru pengajar.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== MAIN ADMIN DASHBOARD ===== -->
  <div v-else class="flex h-screen bg-[#070b14] font-body overflow-hidden text-white">

    <!-- ===== SIDEBAR ===== -->
    <aside
      class="flex flex-col h-full shrink-0 transition-all duration-300 ease-out overflow-hidden border-r border-white/10 shadow-2xl"
      :class="sidebarOpen ? 'w-60' : 'w-[64px]'"
      style="background: linear-gradient(160deg, #050a18 0%, #0d1224 60%, #050a18 100%);"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-4 border-b border-white/10 shrink-0 cursor-pointer select-none" @click="sidebarOpen = !sidebarOpen">
        <div class="w-9 h-9 shrink-0 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20">E</div>
        <div v-show="sidebarOpen" class="min-w-0">
          <span class="font-black text-lg tracking-tight text-white whitespace-nowrap">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          <p class="text-[11px] text-white/50 font-bold uppercase tracking-widest whitespace-nowrap">{{ isAffiliate ? 'Portal Afiliasi' : (isTeacher ? 'Portal Pendidik' : 'Admin Portal') }}</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex flex-col gap-1 p-2.5 flex-grow overflow-y-auto">
        <button
          v-for="item in navItems" :key="item.id"
          @click="activeTab = item.id"
          :title="item.label"
          :class="['flex items-center rounded-xl text-xs font-bold transition-all text-left group relative',
            sidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center px-0 py-2.5',
            activeTab === item.id
              ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00]'
              : 'text-white/60 hover:text-white hover:bg-white/5 border border-transparent'
          ]"
        >
          <i :class="['ph-bold shrink-0 text-lg', item.icon]"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">{{ item.label }}</span>
          <span v-if="item.badge && sidebarOpen" class="ml-auto bg-rose-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full">{{ item.badge }}</span>
        </button>
      </nav>

      <!-- Footer -->
      <div class="shrink-0 border-t border-white/10 p-3 space-y-2">
        <div class="flex items-center gap-2.5" :class="sidebarOpen ? '' : 'justify-center'">
          <div class="w-8 h-8 shrink-0 rounded-lg bg-[#c0ff00]/20 flex items-center justify-center font-black text-[#c0ff00] text-xs border border-[#c0ff00]/30">
            {{ isAffiliate ? 'AF' : (currentRole === 'teacher' ? 'TC' : (currentRole === 'superadmin' ? 'SA' : 'AD')) }}
          </div>
          <div v-show="sidebarOpen" class="flex-grow min-w-0">
            <h4 class="text-xs font-black text-white truncate">{{ currentAdminUser?.name || affiliateSettingsForm.name || (isAffiliate ? 'Mitra Afiliasi' : (currentRole === 'teacher' ? 'Guru / Tutor' : (currentRole === 'superadmin' ? 'Superadmin' : 'Admin Bimbel'))) }}</h4>
            <span class="text-[10px] text-[#c0ff00] font-bold uppercase tracking-wider">{{ isAffiliate ? 'Mitra Afiliasi' : (currentRole === 'teacher' ? 'Pendidik / Tutor' : (currentRole === 'superadmin' ? 'Super Admin' : 'Tenant Admin')) }}</span>
          </div>
        </div>
        <button
          @click="doLogout"
          :title="'Keluar'"
          :class="['bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-400 rounded-xl font-bold transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 text-xs',
            sidebarOpen ? 'w-full py-2' : 'w-9 h-9 text-sm'
          ]"
        >
          <i class="ph-bold ph-sign-out shrink-0 text-base"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">Keluar</span>
        </button>
      </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex flex-col flex-grow min-w-0 overflow-hidden">

      <!-- Top Bar -->
      <header class="flex items-center justify-between px-6 py-3 bg-[#0b1329]/95 backdrop-blur-md border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
          <button @click="sidebarOpen = !sidebarOpen" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-colors text-white/80">
            <i class="ph-bold ph-list text-base"></i>
          </button>
          <h1 class="text-sm font-black text-white tracking-wide uppercase">{{ currentNavItem?.label }}</h1>
        </div>
        <div class="flex items-center gap-3">
          <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Aktif
          </div>
          <div class="text-xs text-white/50 font-medium">{{ new Date().toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) }}</div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-grow overflow-y-auto p-4">

        <!-- ===== TAB: OVERVIEW ===== -->
        <div v-if="activeTab === 'overview'" class="space-y-4 animate-fade-in">
          <!-- Stats Row -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold text-white/50 uppercase tracking-wider">Total Siswa</span>
              </div>
              <div class="text-xl font-black font-mono text-[#8b5cf6]">{{ stats.totalStudents }}</div>
            </div>
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold text-white/50 uppercase tracking-wider">Siswa Aktif</span>
              </div>
              <div class="text-xl font-black font-mono text-[#10b981]">{{ stats.activeStudents }}</div>
            </div>
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold text-white/50 uppercase tracking-wider">Total Soal</span>
              </div>
              <div class="text-xl font-black font-mono text-[#0ea5e9]">{{ stats.totalQuizzes }}</div>
            </div>
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold text-white/50 uppercase tracking-wider">Pendapatan</span>
              </div>
              <div class="text-xl font-black font-mono text-[#c0ff00]">{{ formatCurrency(stats.totalRevenue) }}</div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: TEACHER HUB (Khusus Guru) ===== -->
        <div v-if="activeTab === 'teacher_hub'" class="space-y-4 animate-fade-in">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl">
              <i class="ph-bold ph-chalkboard-teacher"></i>
            </div>
            <div>
              <h2 class="text-xs font-black text-white uppercase tracking-wider">Panel Guru — Siswa & Jadwal Saya</h2>
              <p class="text-[10px] text-white/40">Kelola informasi mengajar, siswa bimbingan, dan jadwal kelas Anda.</p>
            </div>
          </div>

          <!-- Stats Guru -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-emerald-400/40 transition-all">
              <div class="text-[10px] font-bold text-white/50 uppercase tracking-wider mb-1">Siswa Saya</div>
              <div class="text-xl font-black font-mono text-emerald-400">{{ teacherStudents.length }}</div>
            </div>
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-sky-400/40 transition-all">
              <div class="text-[10px] font-bold text-white/50 uppercase tracking-wider mb-1">Mata Pelajaran</div>
              <div class="text-xl font-black font-mono text-sky-400">{{ teacherSubjects.length }}</div>
            </div>
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-amber-400/40 transition-all">
              <div class="text-[10px] font-bold text-white/50 uppercase tracking-wider mb-1">Jam Mengajar/Minggu</div>
              <div class="text-xl font-black font-mono text-amber-400">{{ teacherSchedule.length * 2 }}j</div>
            </div>
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-3.5 border border-white/10 hover:border-purple-400/40 transition-all">
              <div class="text-[10px] font-bold text-white/50 uppercase tracking-wider mb-1">Soal Dibuat</div>
              <div class="text-xl font-black font-mono text-[#8b5cf6]">{{ serverQuestions.filter(q => q.teacher_name === (currentAdminUser?.name || '')).length }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Daftar Siswa yang Diajar -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
              <div class="px-3.5 py-2.5 border-b border-white/10 bg-black/20 flex items-center gap-2">
                <i class="ph-bold ph-users text-emerald-400"></i>
                <h3 class="font-bold text-white text-xs">Daftar Siswa yang Saya Ajar</h3>
              </div>
              <div v-if="teacherStudents.length === 0" class="p-6 text-center text-white/40">
                <i class="ph-bold ph-users text-2xl mb-2 block"></i>
                <p class="text-[10px]">Belum ada siswa yang ditugaskan ke Anda.</p>
                <p class="text-[10px] text-white/30 mt-1">Hubungi Admin Bimbel untuk penugasan siswa.</p>
              </div>
              <div v-else class="divide-y divide-white/5">
                <div v-for="s in teacherStudents" :key="s.id" class="px-3.5 py-2.5 flex items-center gap-3 hover:bg-white/5 transition-colors">
                  <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-black shrink-0">
                    {{ (s.name || s.username || 'S').charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0 flex-grow">
                    <div class="text-xs font-bold text-white truncate">{{ s.name || s.username }}</div>
                    <div class="text-[10px] text-white/40 truncate">{{ s.email }}</div>
                  </div>
                  <span :class="['text-[9px] px-2 py-0.5 rounded-full font-black uppercase', s.is_active != 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400']">
                    {{ s.is_active != 0 ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Jadwal Mengajar & Mata Pelajaran -->
            <div class="space-y-4">
              <!-- Mata Pelajaran -->
              <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-white/10 bg-black/20 flex items-center gap-2">
                  <i class="ph-bold ph-books text-sky-400"></i>
                  <h3 class="font-bold text-white text-xs">Mata Pelajaran yang Saya Ampu</h3>
                </div>
                <div v-if="teacherSubjects.length === 0" class="p-4 text-center text-white/40 text-[10px]">Belum ada mata pelajaran.</div>
                <div v-else class="p-3 flex flex-wrap gap-2">
                  <span v-for="(subj, idx) in teacherSubjects" :key="idx" class="px-2.5 py-1 rounded-lg bg-sky-500/15 border border-sky-500/30 text-sky-300 text-[10px] font-bold">
                    {{ subj }}
                  </span>
                </div>
              </div>

              <!-- Jadwal Mengajar -->
              <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-white/10 bg-black/20 flex items-center gap-2">
                  <i class="ph-bold ph-calendar text-amber-400"></i>
                  <h3 class="font-bold text-white text-xs">Jadwal Mengajar Saya</h3>
                </div>
                <div v-if="teacherSchedule.length === 0" class="p-4 text-center text-white/40 text-[10px]">
                  Jadwal belum diatur oleh Admin Bimbel.
                </div>
                <div v-else class="divide-y divide-white/5">
                  <div v-for="(sched, idx) in teacherSchedule" :key="idx" class="px-3.5 py-2.5 flex items-center gap-3">
                    <div class="w-14 text-center py-1 rounded-lg bg-amber-500/15 border border-amber-500/20 text-amber-300 text-[10px] font-black shrink-0">{{ sched.day }}</div>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-white">{{ sched.subject }}</div>
                      <div class="text-[10px] text-white/40">{{ sched.time }} • {{ sched.room || 'Online' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: TRANSAKSI ===== -->
        <div v-if="activeTab === 'transactions'" class="space-y-4 animate-fade-in">
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="px-3.5 py-2.5 border-b border-white/10 flex items-center justify-between bg-black/20">
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-white text-xs">Riwayat Transaksi & Pembayaran</h3>
                <span class="text-[9px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-mono font-bold">{{ adminOrders.length }} Order</span>
              </div>
              <div class="flex items-center gap-2">
                <button @click="openCreateOrderModal" class="px-2.5 py-1 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[10px] font-black rounded-lg transition-colors flex items-center gap-1">
                  <i class="ph-bold ph-plus"></i> Transaksi Manual
                </button>
                <button @click="fetchAdminOrders" class="text-[10px] text-[#c0ff00] font-bold hover:underline flex items-center gap-1">
                  <i class="ph-bold ph-arrows-clockwise" :class="{'animate-spin': ordersLoading}"></i> Refresh
                </button>
              </div>
            </div>
            <div v-if="ordersLoading" class="p-6 text-center text-white/40">
              <i class="ph-bold ph-spinner animate-spin text-xl mb-1"></i>
              <p class="text-[10px]">Memuat data transaksi...</p>
            </div>
            <div v-else-if="adminOrders.length === 0" class="p-6 text-center text-white/40">
              <i class="ph-bold ph-receipt text-2xl mb-1"></i>
              <p class="text-[10px]">Belum ada transaksi tercatat.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/60">
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Order ID</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Siswa</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Paket</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Nominal</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Tanggal</th>
                    <th class="text-right px-4 py-3 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="o in pagedOrders" :key="o.order_id || o.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-4 py-3 font-mono text-white/60 text-xs">{{ o.order_id || o.id }}</td>
                    <td class="px-4 py-3">
                      <div class="font-bold text-white text-sm leading-snug">{{ o.student_name || '-' }}</div>
                      <div class="text-white/50 font-medium text-xs mt-0.5">{{ o.student_email || '-' }}</div>
                    </td>
                    <td class="px-4 py-3 font-bold text-indigo-400 capitalize text-xs">{{ o.plan_name || o.plan_id }}</td>
                    <td class="px-4 py-3 font-mono text-emerald-400 font-bold text-sm">Rp {{ Number(o.amount || 0).toLocaleString('id-ID') }}</td>
                    <td class="px-4 py-3">
                      <span class="px-2.5 py-1 rounded-md font-bold text-xs uppercase border inline-flex items-center gap-1"
                            :class="o.status === 'paid' || o.status === 'settlement' ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' : (o.status === 'pending' ? 'bg-amber-500/15 border-amber-500/30 text-amber-400' : 'bg-rose-500/15 border-rose-500/30 text-rose-400')">
                        {{ o.status }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-white/50 text-xs">{{ o.created_at ? new Date(o.created_at).toLocaleString('id-ID') : '-' }}</td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                      <button @click="openEditOrderStatusModal(o)" title="Ubah Status" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all"><i class="ph-bold ph-pencil-simple"></i> Status</button>
                      <button @click="deleteOrder(o.order_id || o.id)" title="Hapus Transaksi" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all"><i class="ph-bold ph-trash"></i> Hapus</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Orders -->
            <div v-if="adminOrders.length > PAGE_SIZE" class="px-3.5 py-2.5 border-t border-white/10 flex items-center justify-between text-[11px] bg-black/20">
              <span class="text-white/40 font-medium">
                Menampilkan {{ (orderPage - 1) * PAGE_SIZE + 1 }}-{{ Math.min(orderPage * PAGE_SIZE, adminOrders.length) }} dari {{ adminOrders.length }} transaksi
              </span>
              <div class="flex items-center gap-1.5">
                <button :disabled="orderPage <= 1" @click="orderPage--" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  &laquo; Prev
                </button>
                <span class="px-2 font-mono font-bold text-[#c0ff00]">{{ orderPage }} / {{ orderTotalPages }}</span>
                <button :disabled="orderPage >= orderTotalPages" @click="orderPage++" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  Next &raquo;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN SISWA ===== -->
        <div v-if="activeTab === 'students'" class="space-y-4 animate-fade-in">
          <!-- Search + Filter Bar -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-2.5 border border-white/10 flex flex-wrap items-center gap-2">
            <div class="relative flex-grow min-w-[180px]">
              <i class="ph-bold ph-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-white/30 text-xs"></i>
              <input id="student_search_input" name="student_search" aria-label="Cari nama atau email siswa" v-model="studentSearch" type="text" placeholder="Cari nama atau email siswa..." class="w-full pl-8 pr-3 py-1.5 text-[11px] font-medium text-white placeholder-white/30 bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-colors" />
            </div>
            <select id="student_plan_filter" name="student_plan_filter" aria-label="Filter Paket Belajar" v-model="studentPlanFilter" class="text-[11px] font-bold text-white bg-black/40 border border-white/10 rounded-lg px-2.5 py-1.5 outline-none cursor-pointer focus:border-[#c0ff00]/50">
              <option value="all" class="bg-[#0d1427]">Semua Paket</option>
              <option value="free" class="bg-[#0d1427]">Free</option>
              <option value="mandiri" class="bg-[#0d1427]">Mandiri</option>
              <option value="utama" class="bg-[#0d1427]">Utama</option>
              <option value="vip" class="bg-[#0d1427]">VIP</option>
            </select>
            <button @click="openCreateStudentModal" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Siswa
            </button>
          </div>

          <!-- Students Table -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/60">
                    <th class="text-center px-4 py-3 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('name')">Siswa <i v-if="sortKey === 'name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('plan')">Paket <i v-if="sortKey === 'plan'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('is_active')">Status <i v-if="sortKey === 'is_active'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-right px-4 py-3 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(s, index) in pagedStudents" :key="s.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-4 py-3 text-center text-white/50 font-bold text-xs">{{ (studentPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-4 py-3">
                      <div class="font-bold text-white text-sm leading-snug">{{ s.name }}</div>
                      <div class="text-white/50 font-medium text-xs mt-0.5">{{ s.email }}</div>
                    </td>
                    <td class="px-4 py-3">
                      <span class="px-2.5 py-1 rounded-md font-bold text-xs bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 uppercase tracking-wide">{{ s.plan || 'free' }}</span>
                    </td>
                    <td class="px-4 py-3">
                      <span class="px-2.5 py-1 rounded-md font-bold text-xs inline-flex items-center gap-1" :class="s.is_active != 0 ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/15 border border-rose-500/30 text-rose-400'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="s.is_active != 0 ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                        {{ s.is_active != 0 ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                      <button @click="openEditStudentModal(s)" title="Edit Siswa" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                      <button @click="deleteStudent(s.id)" title="Hapus Siswa" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm"><i class="ph-bold ph-trash"></i> Hapus</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Students -->
            <div v-if="filteredStudents.length > PAGE_SIZE" class="px-3.5 py-2.5 border-t border-white/10 flex items-center justify-between text-[11px] bg-black/20">
              <span class="text-white/40 font-medium">
                Menampilkan {{ (studentPage - 1) * PAGE_SIZE + 1 }}-{{ Math.min(studentPage * PAGE_SIZE, filteredStudents.length) }} dari {{ filteredStudents.length }} siswa
              </span>
              <div class="flex items-center gap-1.5">
                <button :disabled="studentPage <= 1" @click="studentPage--" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  &laquo; Prev
                </button>
                <span class="px-2 font-mono font-bold text-[#c0ff00]">{{ studentPage }} / {{ studentTotalPages }}</span>
                <button :disabled="studentPage >= studentTotalPages" @click="studentPage++" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  Next &raquo;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: BANK SOAL ===== -->
        <div v-if="activeTab === 'questions'" class="space-y-4 animate-fade-in">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-black text-white uppercase tracking-wider">Manajemen Bank Soal</h2>
              <p class="text-xs text-white/50 mt-0.5">Kelola soal latihan, tryout, dan pembahasan resmi SNBT / TPS</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="showImportModal = true" class="px-3.5 py-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold rounded-xl hover:bg-emerald-500/20 text-xs transition-colors flex items-center gap-1.5">
                <i class="ph-bold ph-file-csv text-base"></i> Import CSV
              </button>
              <button @click="openQuestionModal()" class="px-4 py-2 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-xl transition-all shadow-md shadow-[#c0ff00]/20 flex items-center gap-1.5">
                <i class="ph-bold ph-plus text-base"></i> Tambah Soal
              </button>
            </div>
          </div>

          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden shadow-xl">
            <div class="p-3 border-b border-white/10 flex flex-wrap items-center gap-2.5 bg-black/20">
              <div class="flex items-center gap-1 bg-black/40 p-1 rounded-xl border border-white/10 shrink-0">
                <button
                  type="button"
                  @click="questionFilterTeacher = 'all'"
                  :class="questionFilterTeacher === 'all' ? 'bg-[#c0ff00] text-black font-black' : 'text-white/60 hover:text-white font-bold'"
                  class="px-3 py-1.5 rounded-lg text-xs transition-colors"
                >
                  Semua Soal
                </button>
                <button
                  type="button"
                  @click="questionFilterTeacher = 'mine'"
                  :class="questionFilterTeacher === 'mine' ? 'bg-[#c0ff00] text-black font-black' : 'text-white/60 hover:text-white font-bold'"
                  class="px-3 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1"
                >
                  <span>👨‍🏫 Soal Saya</span>
                </button>
              </div>

              <input id="q_search_input" name="q_search" aria-label="Cari soal / materi / topik" v-model="qSearch" type="text" placeholder="Cari soal / materi / topik..." class="flex-grow min-w-[180px] px-3.5 py-2 text-xs text-white placeholder-white/40 bg-black/40 border border-white/10 rounded-xl outline-none focus:border-[#c0ff00]/50 transition-colors" />

              <select id="q_sub_materi_select" name="q_sub_materi" aria-label="Semua Komponen & Subtes" v-model="qSubMateri" class="px-3 py-2 text-xs font-bold text-white bg-black/40 border border-white/10 rounded-xl outline-none cursor-pointer">
                <option value="all" class="bg-[#0d1427]">Semua Komponen & Subtes</option>
                <optgroup label="Komponen: TPS" class="bg-[#0d1427] font-bold text-[#c0ff00]">
                  <option value="Penalaran Umum" class="bg-[#0d1427]">Penalaran Umum (PU)</option>
                  <option value="Pengetahuan & Pemahaman Umum" class="bg-[#0d1427]">Pengetahuan & Pemahaman Umum (PPU)</option>
                  <option value="Pemahaman Bacaan & Menulis" class="bg-[#0d1427]">Pemahaman Bacaan & Menulis (PBM)</option>
                  <option value="Pengetahuan Kuantitatif" class="bg-[#0d1427]">Pengetahuan Kuantitatif (PK)</option>
                </optgroup>
                <optgroup label="Komponen: TES LITERASI" class="bg-[#0d1427] font-bold text-sky-400">
                  <option value="Literasi Bahasa Indonesia" class="bg-[#0d1427]">Literasi Bahasa Indonesia (LBI)</option>
                  <option value="Literasi Bahasa Inggris" class="bg-[#0d1427]">Literasi Bahasa Inggris (LBE)</option>
                  <option value="Penalaran Matematika" class="bg-[#0d1427]">Penalaran Matematika (PM)</option>
                </optgroup>
                <optgroup label="Topik Resmi SNBT" class="bg-[#0d1427] font-bold text-indigo-400">
                  <option value="Induktif" class="bg-[#0d1427]">Topik: Induktif</option>
                  <option value="Deduktif" class="bg-[#0d1427]">Topik: Deduktif</option>
                  <option value="Kuantitatif" class="bg-[#0d1427]">Topik: Kuantitatif</option>
                  <option value="Kosakata" class="bg-[#0d1427]">Topik: Kosakata</option>
                  <option value="Makna Kontekstual" class="bg-[#0d1427]">Topik: Makna Kontekstual</option>
                  <option value="Hubungan Informasi" class="bg-[#0d1427]">Topik: Hubungan Informasi</option>
                  <option value="Pemahaman Bacaan" class="bg-[#0d1427]">Topik: Pemahaman Bacaan</option>
                  <option value="Menulis" class="bg-[#0d1427]">Topik: Menulis</option>
                  <option value="Bilangan" class="bg-[#0d1427]">Topik: Bilangan</option>
                  <option value="Aljabar" class="bg-[#0d1427]">Topik: Aljabar</option>
                  <option value="Geometri" class="bg-[#0d1427]">Topik: Geometri</option>
                  <option value="Statistika" class="bg-[#0d1427]">Topik: Statistika</option>
                  <option value="Perbandingan" class="bg-[#0d1427]">Topik: Perbandingan</option>
                  <option value="Pemahaman" class="bg-[#0d1427]">Topik: Pemahaman (LBI)</option>
                  <option value="Analisis" class="bg-[#0d1427]">Topik: Analisis (LBI)</option>
                  <option value="Evaluasi" class="bg-[#0d1427]">Topik: Evaluasi (LBI)</option>
                  <option value="Integrasi Informasi" class="bg-[#0d1427]">Topik: Integrasi Informasi</option>
                  <option value="Vocabulary" class="bg-[#0d1427]">Topik: Vocabulary (LBE)</option>
                  <option value="Reading Comprehension" class="bg-[#0d1427]">Topik: Reading Comprehension</option>
                  <option value="Text Analysis" class="bg-[#0d1427]">Topik: Text Analysis</option>
                  <option value="Critical Reading" class="bg-[#0d1427]">Topik: Critical Reading</option>
                  <option value="Data & Ketidakpastian" class="bg-[#0d1427]">Topik: Data & Ketidakpastian</option>
                  <option value="Problem Solving" class="bg-[#0d1427]">Topik: Problem Solving</option>
                </optgroup>
              </select>
            </div>
            
            <div v-if="questionsLoading" class="p-8 text-center text-white/40">
              <i class="ph-bold ph-spinner animate-spin text-2xl mb-2 text-[#c0ff00]"></i>
              <p class="text-xs font-semibold">Memuat bank soal...</p>
            </div>
            <div v-else-if="filteredQuestions.length === 0" class="p-8 text-center text-white/40">
              <i class="ph-bold ph-books text-3xl mb-2 text-white/20"></i>
              <p class="text-xs font-bold text-white/60">Tidak ada soal yang ditemukan.</p>
              <p class="text-[11px] text-white/40 mt-1">Coba sesuaikan kata kunci pencarian atau filter guru/subtes.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/60">
                    <th class="text-center px-3 py-3 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider w-5/12 cursor-pointer hover:text-white" @click="setSort('question')">Pertanyaan <i v-if="sortKey === 'question'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('test_component')">Komponen & Subtes <i v-if="sortKey === 'test_component'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('topic')">Topik & Subtopik <i v-if="sortKey === 'topic'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('skill')">Skill / Kompetensi <i v-if="sortKey === 'skill'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('question_type')">Tipe & Level <i v-if="sortKey === 'question_type'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('is_qc_passed')">Status QC <i v-if="sortKey === 'is_qc_passed'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-right px-4 py-3 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(q, index) in pagedQuestions" :key="q.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-3 text-center text-white/50 font-bold">{{ (questionPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-4 py-3">
                      <div class="line-clamp-2 text-sm text-white/90 font-medium leading-relaxed" :title="stripHtml(q.question)">
                        {{ stripHtml(q.question) }}
                      </div>
                      <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <button type="button" @click="openQuestionPreview(q)" class="text-xs text-sky-400 hover:text-sky-300 font-bold inline-flex items-center gap-1 transition-colors">
                          <i class="ph-bold ph-eye"></i> Format Penuh
                        </button>
                        <span v-if="q.teacher_name" class="text-[11px] text-amber-300 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20 font-bold">
                          👨‍🏫 {{ q.teacher_name }}
                        </span>
                      </div>
                    </td>
                    <td class="px-3 py-3">
                      <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-black uppercase tracking-wider text-[#c0ff00]">{{ q.test_component || 'TPS' }}</span>
                        <span class="font-bold text-white text-xs">{{ q.subtest || q.sub_materi || q.subtes }}</span>
                      </div>
                    </td>
                    <td class="px-3 py-3">
                      <div class="flex flex-col gap-0.5">
                        <span v-if="q.topic" class="text-xs font-bold text-indigo-300">{{ q.topic }}</span>
                        <span v-if="q.subtopic" class="text-[11px] text-white/50">{{ q.subtopic }}</span>
                        <span v-if="!q.topic && !q.subtopic" class="text-xs text-white/30 italic">-</span>
                      </div>
                    </td>
                    <td class="px-3 py-3">
                      <span v-if="q.skill" class="text-xs font-medium text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md border border-emerald-500/20">{{ q.skill }}</span>
                      <span v-else class="text-xs text-white/30 italic">-</span>
                    </td>
                    <td class="px-3 py-3">
                      <div class="flex flex-col gap-1 items-start">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 uppercase">{{ q.usage_type || q.classification || 'Latihan' }}</span>
                        <span class="text-[11px] text-white/50 font-bold uppercase">{{ q.difficulty || 'medium' }} • {{ q.cognitive_demand || 'C3' }}</span>
                      </div>
                    </td>
                    <td class="px-3 py-3">
                      <span v-if="q.is_qc_passed == 1" class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-md">Lolos QC</span>
                      <span v-else class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-md">Belum QC</span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-1.5 whitespace-nowrap">
                      <template v-if="canManageQuestion(q)">
                        <button @click="openQuestionModal(q)" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-sm" title="Edit Soal"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                        <button @click="deleteQuestion(q.id)" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-sm" title="Hapus Soal"><i class="ph-bold ph-trash"></i> Hapus</button>
                      </template>
                      <span v-else class="text-xs text-white/40 italic px-2.5 py-1 bg-white/5 rounded-lg border border-white/10">Hanya Pemilik</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Questions -->
            <div v-if="filteredQuestions.length > PAGE_SIZE" class="px-4 py-3 border-t border-white/10 flex items-center justify-between text-xs bg-black/20">
              <span class="text-white/50 font-medium">
                Menampilkan {{ (questionPage - 1) * PAGE_SIZE + 1 }}-{{ Math.min(questionPage * PAGE_SIZE, filteredQuestions.length) }} dari {{ filteredQuestions.length }} soal
              </span>
              <div class="flex items-center gap-2">
                <button :disabled="questionPage <= 1" @click="questionPage--" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  &laquo; Prev
                </button>
                <span class="px-2.5 font-mono font-bold text-[#c0ff00]">{{ questionPage }} / {{ questionTotalPages }}</span>
                <button :disabled="questionPage >= questionTotalPages" @click="questionPage++" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  Next &raquo;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN MATERI ===== -->
        <div v-if="activeTab === 'materials'" class="space-y-4 animate-fade-in">
          <div class="flex items-center justify-between">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">{{ isTeacher ? 'Materi yang Saya Ajar' : 'Manajemen Materi Belajar' }}</h2>
            <button v-if="!isTeacher" @click="openMaterialModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1">
              <i class="ph-bold ph-plus"></i> Tambah Materi
            </button>
          </div>

          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="p-2.5 border-b border-white/10 flex gap-2 bg-black/20">
              <input id="m_search_input" name="m_search" aria-label="Cari materi" v-model="mSearch" type="text" placeholder="Cari materi..." class="flex-grow px-3 py-1.5 text-[11px] text-white placeholder-white/30 bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50" />
            </div>
            
            <div v-if="materialsLoading" class="p-6 text-center text-white/40">
              <i class="ph-bold ph-spinner animate-spin text-xl mb-1"></i>
              <p class="text-[10px]">Memuat materi...</p>
            </div>
            <div v-else-if="filteredMaterials.length === 0" class="p-6 text-center text-white/40">
              <p class="text-[10px]">Tidak ada materi yang ditemukan.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/60">
                    <th class="text-center px-4 py-3 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('title')">Judul Materi <i v-if="sortKey === 'title'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('sub_materi')">Sub Materi <i v-if="sortKey === 'sub_materi'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('teacher_name')">Guru / PJ <i v-if="sortKey === 'teacher_name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('is_active')">Status <i v-if="sortKey === 'is_active'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-right px-4 py-3 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(m, index) in pagedMaterials" :key="m.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-4 py-3 text-center text-white/50 font-bold text-xs">{{ (materialPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-4 py-3 font-bold text-white text-sm leading-snug">{{ m.title }}</td>
                    <td class="px-4 py-3 font-bold text-white/80 text-xs">{{ m.sub_materi }}</td>
                    <td class="px-4 py-3 font-medium text-white/60 text-xs">{{ m.teacher_name || '-' }}</td>
                    <td class="px-4 py-3">
                      <span class="px-2.5 py-1 rounded-md font-bold text-xs inline-flex items-center gap-1" :class="m.is_active != 0 ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/15 border border-rose-500/30 text-rose-400'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="m.is_active != 0 ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                        {{ m.is_active != 0 ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                      <a v-if="m.file_url" :href="m.file_url" target="_blank" class="px-3 py-1.5 bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Buka File Materi"><i class="ph-bold ph-file-arrow-down"></i> File</a>
                      <template v-if="canManageMaterial(m)">
                        <button @click="openMaterialModal(m)" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Edit Materi"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                        <button @click="deleteMaterial(m.id)" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Hapus Materi"><i class="ph-bold ph-trash"></i> Hapus</button>
                      </template>
                      <span v-else class="text-xs text-white/40 italic px-2 py-1 bg-white/5 rounded-lg border border-white/10">Hanya Pemilik</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Materials -->
            <div v-if="filteredMaterials.length > PAGE_SIZE" class="px-3.5 py-2.5 border-t border-white/10 flex items-center justify-between text-[11px] bg-black/20">
              <span class="text-white/40 font-medium">
                Menampilkan {{ (materialPage - 1) * PAGE_SIZE + 1 }}-{{ Math.min(materialPage * PAGE_SIZE, filteredMaterials.length) }} dari {{ filteredMaterials.length }} materi
              </span>
              <div class="flex items-center gap-1.5">
                <button :disabled="materialPage <= 1" @click="materialPage--" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  &laquo; Prev
                </button>
                <span class="px-2 font-mono font-bold text-[#c0ff00]">{{ materialPage }} / {{ materialTotalPages }}</span>
                <button :disabled="materialPage >= materialTotalPages" @click="materialPage++" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  Next &raquo;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN PAKET ===== -->
        <div v-if="activeTab === 'packages'" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Paket & Langganan</h2>
              <p class="text-[10px] text-white/40">Paket resmi EduPath sesuai spesifikasi landing page</p>
            </div>
            <button @click="openPlanModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Paket
            </button>
          </div>
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/60">
                    <th class="text-center px-4 py-3 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('name')">Nama Paket <i v-if="sortKey === 'name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('price')">Harga Resmi <i v-if="sortKey === 'price'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('duration')">Durasi <i v-if="sortKey === 'duration'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Entitlements</th>
                    <th class="text-right px-4 py-3 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(p, index) in pagedPlans" :key="p.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-4 py-3 text-center text-white/50 font-bold text-xs">{{ (planPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-4 py-3 font-bold text-white text-sm leading-snug">{{ p.name }}</td>
                    <td class="px-4 py-3 font-bold text-emerald-400 font-mono text-sm">Rp {{ Number(p.price || 0).toLocaleString('id-ID') }}</td>
                    <td class="px-4 py-3 font-bold text-white/80 text-xs">{{ p.duration }} Hari</td>
                    <td class="px-4 py-3">
                      <span class="text-xs text-indigo-300 font-medium px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20">{{ Array.isArray(p.features) ? p.features.length : 0 }} fitur</span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                      <button @click="openPlanModal(p)" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Edit Paket"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                      <button @click="deletePlan(p.id)" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Hapus Paket"><i class="ph-bold ph-trash"></i> Hapus</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Plans -->
            <div v-if="sortedPlans.length > PAGE_SIZE" class="px-3.5 py-2.5 border-t border-white/10 flex items-center justify-between text-[11px] bg-black/20">
              <span class="text-white/40 font-medium">
                Menampilkan {{ (planPage - 1) * PAGE_SIZE + 1 }}-{{ Math.min(planPage * PAGE_SIZE, sortedPlans.length) }} dari {{ sortedPlans.length }} paket
              </span>
              <div class="flex items-center gap-1.5">
                <button :disabled="planPage <= 1" @click="planPage--" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  &laquo; Prev
                </button>
                <span class="px-2 font-mono font-bold text-[#c0ff00]">{{ planPage }} / {{ planTotalPages }}</span>
                <button :disabled="planPage >= planTotalPages" @click="planPage++" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  Next &raquo;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- ===== PORTAL MITRA AFILIASI (AFFILIATE VIEW) ===== -->
        <!-- ============================================== -->

        <!-- 1. TAB: AFFILIATE DASHBOARD -->
        <div v-if="activeTab === 'affiliate_dashboard' && isAffiliate" class="space-y-6 animate-fade-in">
          <!-- ✨ Banner Gambar EduPath Partner (Gambar 1.jpeg Wanita EduPath Partner) -->
          <div class="rounded-3xl overflow-hidden border border-amber-500/30 shadow-[0_16px_50px_rgba(0,0,0,0.5)] relative group">
            <img src="/1.jpeg" alt="EduPath Partner Banner" class="w-full h-44 md:h-56 lg:h-64 object-cover object-center transition-transform duration-700 group-hover:scale-105" />
            <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-90 z-10"></div>
          </div>

          <!-- Hero Banner & Referral Link -->
          <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0c1836] via-[#0e1f4d] to-[#0a1226] border border-amber-500/30 p-6 shadow-2xl">
            <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
              <div class="space-y-2 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-[11px] font-black uppercase tracking-wider">
                  <i class="ph-bold ph-crown text-amber-400"></i>
                  <span>Portal Resmi Mitra Afiliasi EduPath</span>
                </div>
                <h2 class="text-xl md:text-2xl font-black text-white tracking-tight">
                  Halo, <span class="text-[#c0ff00]">{{ currentAdminUser?.name || affiliateSettingsForm.name || 'Mitra Hebat' }}</span>! 🚀
                </h2>
                <p class="text-white/70 text-xs leading-relaxed">
                  Bagikan tautan referral Anda kepada calon siswa, teman, atau jejaring Anda. Dapatkan komisi pasif sebesar <span class="text-amber-300 font-bold font-mono">20%</span> setiap kali siswa rujukan Anda berlangganan paket belajar EduPath!
                </p>
              </div>

              <!-- Referral Link Card -->
              <div class="w-full lg:w-auto shrink-0 bg-black/60 border border-white/15 p-4 rounded-xl backdrop-blur-xl space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-black uppercase tracking-wider text-white/50">Tautan Afiliasi Unik Anda</span>
                  <span class="text-[9px] font-mono text-[#c0ff00] font-bold">KODE: {{ myAffiliateData?.referral_code || '...' }}</span>
                </div>
                <div class="flex items-center gap-2 bg-black/80 border border-white/20 rounded-lg p-1.5 focus-within:border-[#c0ff00] transition-colors">
                  <input
                    type="text"
                    readonly
                    :value="referralLinkText"
                    class="w-full lg:w-64 bg-transparent border-none text-xs text-white font-mono outline-none px-2 select-all"
                  />
                  <button
                    @click="copyMyReferralLink"
                    class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs rounded-md transition-all shadow-md flex items-center gap-1.5 shrink-0 active:scale-95 cursor-pointer"
                  >
                    <i class="ph-bold ph-copy"></i>
                    <span>Salin</span>
                  </button>
                </div>
                <div class="flex items-center gap-2 pt-1">
                  <button
                    @click="shareWhatsApp"
                    class="w-full py-1.5 px-3 bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/30 text-emerald-400 font-bold text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    <i class="ph-bold ph-whatsapp-logo text-sm"></i>
                    <span>Bagikan ke WhatsApp</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 4 Stats Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total Rujukan -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-4 relative overflow-hidden group hover:border-[#c0ff00]/40 transition-all">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/50">Total Rujukan</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/15 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                  <i class="ph-bold ph-users text-base"></i>
                </div>
              </div>
              <div class="text-2xl font-black font-mono text-white tracking-tight">{{ myAffiliateData?.stats?.total_referrals || 0 }}</div>
              <p class="text-[10px] text-white/40 mt-1">Siswa mendaftar lewat link</p>
            </div>

            <!-- Stat 2: Rujukan Aktif -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-4 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/50">Siswa Aktif</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                  <i class="ph-bold ph-user-check text-base"></i>
                </div>
              </div>
              <div class="text-2xl font-black font-mono text-emerald-400 tracking-tight">{{ myAffiliateData?.stats?.active_referrals || 0 }}</div>
              <p class="text-[10px] text-white/40 mt-1">Berlangganan paket belajar</p>
            </div>

            <!-- Stat 3: Komisi Siap Tarik (Pending) -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-amber-500/30 p-4 relative overflow-hidden group hover:border-amber-400/60 transition-all shadow-lg shadow-amber-500/5">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Saldo Siap Tarik</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                  <i class="ph-bold ph-wallet text-base"></i>
                </div>
              </div>
              <div class="text-xl font-black font-mono text-amber-300 tracking-tight">
                {{ formatCurrency(myAffiliateData?.stats?.total_pending || 0) }}
              </div>
              <div class="mt-2 pt-2 border-t border-white/10 flex items-center justify-between">
                <span class="text-[9px] text-white/40">Min. Rp 50.000</span>
                <button
                  @click="openAffiliatePayoutModal"
                  :disabled="(myAffiliateData?.stats?.total_pending || 0) < 50000"
                  class="px-2.5 py-1 bg-amber-400 hover:bg-amber-300 disabled:opacity-40 disabled:pointer-events-none text-black text-[10px] font-black rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                >
                  <i class="ph-bold ph-paper-plane-tilt"></i> Tarik
                </button>
              </div>
            </div>

            <!-- Stat 4: Komisi Telah Ditransfer -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-4 relative overflow-hidden group hover:border-[#c0ff00]/40 transition-all">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/50">Sudah Dicairkan</span>
                <div class="w-8 h-8 rounded-lg bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center border border-[#c0ff00]/20">
                  <i class="ph-bold ph-check-circle text-base"></i>
                </div>
              </div>
              <div class="text-xl font-black font-mono text-[#c0ff00] tracking-tight">
                {{ formatCurrency(myAffiliateData?.stats?.total_paid || 0) }}
              </div>
              <p class="text-[10px] text-white/40 mt-1">Total transfer berhasil</p>
            </div>
          </div>

          <!-- Quick Preview Split -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Siswa Rujukan Terbaru -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden">
              <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-users text-indigo-400"></i>
                  <h3 class="font-bold text-white text-xs uppercase tracking-wider">Rujukan Terbaru</h3>
                </div>
                <button @click="activeTab = 'affiliate_referrals'" class="text-indigo-400 hover:text-indigo-300 text-[10px] font-bold flex items-center gap-1 cursor-pointer">
                  Lihat Semua <i class="ph-bold ph-arrow-right"></i>
                </button>
              </div>
              <div class="p-3">
                <div v-if="myAffiliateData?.referrals?.length > 0" class="divide-y divide-white/5">
                  <div v-for="(ref, idx) in myAffiliateData.referrals.slice(0, 5)" :key="idx" class="py-2.5 px-2 flex items-center justify-between">
                    <div>
                      <div class="text-xs font-bold text-white">{{ ref.name }}</div>
                      <div class="text-[10px] text-white/40 font-mono">{{ ref.school || ref.target_ptn || 'Siswa' }} • {{ new Date(ref.created_at).toLocaleDateString('id-ID') }}</div>
                    </div>
                    <span :class="['px-2 py-0.5 rounded text-[9px] font-bold uppercase border', ref.status === 'Aktif' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-white/5 border-white/10 text-white/50']">
                      {{ ref.status }}
                    </span>
                  </div>
                </div>
                <div v-else class="py-8 text-center text-white/40 text-xs">
                  Belum ada siswa yang mendaftar melalui tautan Anda.
                </div>
              </div>
            </div>

            <!-- Riwayat Komisi & Payout -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden">
              <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-receipt text-amber-400"></i>
                  <h3 class="font-bold text-white text-xs uppercase tracking-wider">Riwayat Komisi Terbaru</h3>
                </div>
                <button @click="activeTab = 'affiliate_payouts'" class="text-amber-400 hover:text-amber-300 text-[10px] font-bold flex items-center gap-1 cursor-pointer">
                  Kelola Pencairan <i class="ph-bold ph-arrow-right"></i>
                </button>
              </div>
              <div class="p-3">
                <div v-if="myAffiliateData?.history?.length > 0" class="divide-y divide-white/5">
                  <div v-for="(hist, idx) in myAffiliateData.history.slice(0, 5)" :key="idx" class="py-2.5 px-2 flex items-center justify-between">
                    <div>
                      <div class="text-xs font-bold font-mono text-emerald-400">{{ formatCurrency(hist.amount) }}</div>
                      <div class="text-[10px] text-white/40 font-mono">{{ new Date(hist.created_at).toLocaleDateString('id-ID') }}</div>
                    </div>
                    <span :class="['px-2 py-0.5 rounded text-[9px] font-bold uppercase border', hist.status === 'paid' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400']">
                      {{ hist.status === 'paid' ? 'Ditransfer' : (hist.status === 'requested' ? 'Diproses' : 'Pending') }}
                    </span>
                  </div>
                </div>
                <div v-else class="py-8 text-center text-white/40 text-xs">
                  Belum ada komisi tercatat.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. TAB: SISWA RUJUKAN SAYA -->
        <div v-if="activeTab === 'affiliate_referrals' && isAffiliate" class="space-y-4 animate-fade-in">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-xs font-black text-white uppercase tracking-wider">Daftar Siswa Rujukan Saya</h2>
              <p class="text-[10px] text-white/40">Siswa yang mendaftar di EduPath melalui kode referral unik Anda</p>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="referralSearchQuery"
                type="text"
                placeholder="Cari nama / email siswa..."
                class="px-3 py-1.5 text-xs bg-black/40 border border-white/10 rounded-lg text-white outline-none focus:border-[#c0ff00]"
              />
              <button @click="loadMyAffiliateData" class="px-2.5 py-1.5 bg-white/5 hover:bg-white/10 text-white rounded-lg text-xs font-bold transition-all cursor-pointer">
                <i class="ph-bold ph-arrows-clockwise" :class="{'animate-spin': isLoadingMyAffiliate}"></i>
              </button>
            </div>
          </div>

          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 text-white/60 border-b border-white/10">
                    <th class="py-3 px-4 text-left font-bold">#</th>
                    <th class="py-3 px-4 text-left font-bold">Nama Siswa</th>
                    <th class="py-3 px-4 text-left font-bold">Email</th>
                    <th class="py-3 px-4 text-left font-bold">Sekolah / Target</th>
                    <th class="py-3 px-4 text-left font-bold">Paket Belajar</th>
                    <th class="py-3 px-4 text-left font-bold">Status</th>
                    <th class="py-3 px-4 text-left font-bold">Tanggal Gabung</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingMyAffiliate">
                    <td colspan="7" class="py-8 text-center text-white/40">Memuat data rujukan...</td>
                  </tr>
                  <tr v-else-if="filteredMyReferrals.length === 0">
                    <td colspan="7" class="py-12 text-center text-white/40">
                      <div class="space-y-2">
                        <i class="ph-bold ph-users text-3xl text-white/20"></i>
                        <p>Belum ada data siswa rujukan yang cocok.</p>
                        <button @click="copyMyReferralLink" class="px-3 py-1.5 bg-[#c0ff00] text-black font-black text-xs rounded-lg inline-flex items-center gap-1.5 cursor-pointer">
                          <i class="ph-bold ph-copy"></i> Salin Link Referral Saya
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="(ref, idx) in filteredMyReferrals" :key="idx" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4 text-white/50 font-mono">{{ idx + 1 }}</td>
                    <td class="py-3 px-4 font-bold text-white text-sm leading-snug">{{ ref.name }}</td>
                    <td class="py-3 px-4 text-white/70 font-mono text-xs">{{ ref.email }}</td>
                    <td class="py-3 px-4 text-white/60 text-xs">{{ ref.school || ref.target_ptn || '-' }}</td>
                    <td class="py-3 px-4">
                      <span class="px-2.5 py-1 rounded text-xs font-bold uppercase bg-indigo-500/15 border border-indigo-500/30 text-indigo-300">
                        {{ ref.plan }}
                      </span>
                    </td>
                    <td class="py-3 px-4">
                      <span :class="['px-2.5 py-1 rounded text-xs font-bold uppercase border inline-flex items-center gap-1', ref.status === 'Aktif' ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' : 'bg-white/5 border-white/10 text-white/40']">
                        <span class="w-1.5 h-1.5 rounded-full" :class="ref.status === 'Aktif' ? 'bg-emerald-400' : 'bg-white/40'"></span>
                        {{ ref.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-white/50 font-mono text-xs">
                      {{ new Date(ref.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 3. TAB: PENCAIRAN KOMISI (PAYOUT) -->
        <div v-if="activeTab === 'affiliate_payouts' && isAffiliate" class="space-y-6 animate-fade-in">
          <!-- Saldo Card & Request Action -->
          <div class="bg-gradient-to-r from-amber-500/15 via-[#0e1726] to-[#0e1726] border border-amber-500/30 rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-amber-300/80">Saldo Komisi Tersedia</span>
              <div class="text-3xl font-black font-mono text-amber-300 mt-1">
                {{ formatCurrency(myAffiliateData?.stats?.total_pending || 0) }}
              </div>
              <p class="text-xs text-white/50 mt-1">
                Rekening Pencairan: <span class="font-bold text-white">{{ affiliateSettingsForm.bank_name || myAffiliateData?.bank_info?.bank_name || '-' }}</span> • <span class="font-mono text-white">{{ affiliateSettingsForm.bank_account || myAffiliateData?.bank_info?.bank_account || '-' }}</span> (a.n {{ affiliateSettingsForm.bank_owner || myAffiliateData?.bank_info?.bank_owner || '-' }})
              </p>
            </div>
            <div class="flex items-center gap-3">
              <button
                @click="activeTab = 'settings'"
                class="px-3 py-2 bg-white/5 hover:bg-white/10 border border-white/15 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
              >
                <i class="ph-bold ph-gear"></i> Ubah Rekening
              </button>
              <button
                @click="openAffiliatePayoutModal"
                :disabled="(myAffiliateData?.stats?.total_pending || 0) < 50000"
                class="px-4 py-2 bg-amber-400 hover:bg-amber-300 disabled:opacity-40 disabled:pointer-events-none text-black text-xs font-black rounded-xl transition-all shadow-lg shadow-amber-400/20 flex items-center gap-1.5 cursor-pointer"
              >
                <i class="ph-bold ph-paper-plane-tilt"></i> Ajukan Pencairan Dana
              </button>
            </div>
          </div>

          <!-- Payout History Table -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
              <div>
                <h3 class="font-black text-white text-xs uppercase tracking-wider">Riwayat Pengajuan Pencairan Dana</h3>
                <p class="text-[10px] text-white/40">Status pengajuan penarikan dana ke rekening Anda</p>
              </div>
              <button @click="loadMyAffiliateData" class="text-[#c0ff00] text-xs font-bold flex items-center gap-1 cursor-pointer">
                <i class="ph-bold ph-arrows-clockwise" :class="{'animate-spin': isLoadingMyAffiliate}"></i> Refresh
              </button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 text-white/60 border-b border-white/10">
                    <th class="py-3 px-4 text-left font-bold">Tanggal Pengajuan</th>
                    <th class="py-3 px-4 text-left font-bold">Nominal (Rp)</th>
                    <th class="py-3 px-4 text-left font-bold">Rekening Bank</th>
                    <th class="py-3 px-4 text-left font-bold">Status</th>
                    <th class="py-3 px-4 text-left font-bold">Catatan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingMyAffiliate">
                    <td colspan="5" class="py-8 text-center text-white/40">Memuat riwayat pencairan...</td>
                  </tr>
                  <tr v-else-if="!myAffiliateData?.payouts || myAffiliateData.payouts.length === 0">
                    <td colspan="5" class="py-10 text-center text-white/40">
                      Belum ada riwayat pengajuan penarikan dana.
                    </td>
                  </tr>
                  <tr v-for="pay in myAffiliateData.payouts" :key="pay.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4 text-white/60 font-mono text-xs">
                      {{ new Date(pay.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                    </td>
                    <td class="py-3 px-4 font-mono font-bold text-amber-300 text-sm">
                      {{ formatCurrency(pay.amount) }}
                    </td>
                    <td class="py-3 px-4 text-white/90">
                      <div class="font-bold text-white text-xs">{{ pay.bank_name }} - {{ pay.bank_account }}</div>
                      <div class="text-[11px] text-white/50">a.n {{ pay.bank_owner }}</div>
                    </td>
                    <td class="py-3 px-4">
                      <span :class="['px-2.5 py-1 rounded text-xs font-bold uppercase border inline-flex items-center gap-1',
                        pay.status === 'paid' ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' :
                        (pay.status === 'rejected' ? 'bg-rose-500/15 border-rose-500/30 text-rose-400' : 'bg-amber-500/15 border-amber-500/30 text-amber-400')
                      ]">
                        {{ pay.status === 'paid' ? 'Ditransfer' : (pay.status === 'rejected' ? 'Ditolak' : 'Menunggu Transfer Admin') }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-white/60 text-xs">
                      {{ pay.notes || '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: AFILIASI & KOMISI (ADMIN MANAGEMENT VIEW) ===== -->
        <div v-if="activeTab === 'affiliates' && !isAffiliate" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xs font-black text-white uppercase tracking-wider">Afiliasi & Komisi</h2>
              <p class="text-[10px] text-white/40">Kelola mitra kemitraan, kode referral, dan pencairan komisi</p>
            </div>
            <button @click="openCreateAffiliateModal" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Mitra
            </button>
          </div>

          <!-- Payouts List -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden mb-4">
            <div class="p-2.5 border-b border-white/10 bg-amber-500/10 flex justify-between items-center">
              <h3 class="font-bold text-amber-400 text-xs">Permintaan Pencairan Dana (Payout)</h3>
              <button @click="loadAdminPayouts" class="text-amber-400 hover:text-amber-300 text-[10px] font-bold flex items-center gap-1">
                <i class="ph-bold ph-arrows-clockwise" :class="{'animate-spin': isLoadingPayouts}"></i> Refresh
              </button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 text-white/60 border-b border-white/10">
                    <th class="py-3 px-4 text-left font-bold">Mitra</th>
                    <th class="py-3 px-4 text-left font-bold">Rekening Bank</th>
                    <th class="py-3 px-4 text-left font-bold">Nominal (Rp)</th>
                    <th class="py-3 px-4 text-left font-bold">Status</th>
                    <th class="py-3 px-4 text-right font-bold">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingPayouts">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-xs">Memuat data payout...</td>
                  </tr>
                  <tr v-else-if="payoutsList.length === 0">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-xs">Belum ada permintaan pencairan</td>
                  </tr>
                  <tr v-for="pay in payoutsList" :key="pay.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4">
                      <div class="font-bold text-white text-sm leading-snug">{{ pay.affiliate_name }}</div>
                      <div class="font-mono text-xs text-indigo-400 font-bold">{{ pay.referral_code }}</div>
                    </td>
                    <td class="py-3 px-4">
                      <div class="font-bold text-white/90 text-xs">{{ pay.bank_name }} - {{ pay.bank_account }}</div>
                      <div class="text-[11px] text-white/50">A.n {{ pay.bank_owner }}</div>
                    </td>
                    <td class="py-3 px-4 font-mono font-bold text-emerald-400 text-sm">
                      {{ formatCurrency(pay.amount) }}
                    </td>
                    <td class="py-3 px-4">
                      <span :class="['px-2.5 py-1 rounded-md text-xs font-bold uppercase border inline-flex items-center gap-1', pay.status === 'paid' ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' : (pay.status === 'rejected' ? 'bg-rose-500/15 border-rose-500/30 text-rose-400' : 'bg-amber-500/15 border-amber-500/30 text-amber-400')]">
                        {{ pay.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-right space-x-2">
                      <template v-if="pay.status === 'pending'">
                        <button @click="approvePayoutReq(pay.id)" class="px-2.5 py-1.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/30 font-bold rounded-lg transition-colors text-xs">
                          <i class="ph-bold ph-check"></i> Ditransfer
                        </button>
                        <button @click="openRejectPayoutModal(pay)" class="px-2.5 py-1.5 bg-rose-500/20 border border-rose-500/30 text-rose-400 hover:bg-rose-500/30 font-bold rounded-lg transition-colors text-xs">
                          <i class="ph-bold ph-x"></i> Tolak
                        </button>
                      </template>
                      <span v-else class="text-xs text-white/50">Selesai</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Affiliates List -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden mb-4">
            <div class="p-2.5 border-b border-white/10 bg-black/20 flex justify-between items-center">
              <h3 class="font-bold text-white text-xs">Daftar Mitra Afiliasi</h3>
              <button @click="loadAdminAffiliates" class="text-[#c0ff00] text-[10px] font-bold flex items-center gap-1">
                <i class="ph-bold ph-arrows-clockwise" :class="{'animate-spin': isLoadingAffiliates}"></i> Refresh
              </button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 text-white/60 border-b border-white/10">
                    <th class="py-3 px-4 text-left font-bold">Mitra</th>
                    <th class="py-3 px-4 text-left font-bold">Kode Referral</th>
                    <th class="py-3 px-4 text-left font-bold">Komisi (%)</th>
                    <th class="py-3 px-4 text-left font-bold">Bank Info</th>
                    <th class="py-3 px-4 text-right font-bold">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingAffiliates">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-xs">Memuat data mitra...</td>
                  </tr>
                  <tr v-else-if="affiliatesList.length === 0">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-xs">Belum ada mitra afiliasi</td>
                  </tr>
                  <tr v-for="aff in affiliatesList" :key="aff.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4">
                      <div class="font-bold text-white text-sm leading-snug">{{ aff.affiliate_name || aff.identity_key }}</div>
                      <div class="text-xs text-white/50">{{ aff.email || aff.identity_key }}</div>
                    </td>
                    <td class="py-3 px-4 font-mono text-indigo-400 font-bold text-xs">{{ aff.referral_code }}</td>
                    <td class="py-3 px-4 text-amber-400 font-bold text-xs">{{ aff.commission_rate }}%</td>
                    <td class="py-3 px-4 text-white/70 text-xs">
                      {{ aff.bank_name ? `${aff.bank_name} (${aff.bank_account})` : '-' }}
                    </td>
                    <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                      <button @click="openEditAffiliateModal(aff)" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Edit Mitra"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                      <button @click="deleteAffiliate(aff.id)" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Hapus Mitra"><i class="ph-bold ph-trash"></i> Hapus</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Commissions List -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="p-2.5 border-b border-white/10 bg-black/20 flex justify-between items-center">
              <h3 class="font-bold text-white text-xs">Riwayat Komisi</h3>
              <button @click="loadAdminCommissions" class="text-[#c0ff00] text-[10px] font-bold flex items-center gap-1">
                <i class="ph-bold ph-arrows-clockwise" :class="{'animate-spin': isLoadingCommissions}"></i> Refresh
              </button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 text-white/60 border-b border-white/10">
                    <th class="py-3 px-4 text-left font-bold">Siswa</th>
                    <th class="py-3 px-4 text-left font-bold">Mitra</th>
                    <th class="py-3 px-4 text-left font-bold">Nominal (Rp)</th>
                    <th class="py-3 px-4 text-left font-bold">Status</th>
                    <th class="py-3 px-4 text-right font-bold">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingCommissions">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-xs">Memuat data komisi...</td>
                  </tr>
                  <tr v-else-if="commissionsList.length === 0">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-xs">Belum ada data komisi</td>
                  </tr>
                  <tr v-for="comm in commissionsList" :key="comm.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4">
                      <div class="font-bold text-white text-sm leading-snug">{{ comm.student_name }}</div>
                      <div class="text-xs text-white/50">{{ comm.order_plan_name }}</div>
                    </td>
                    <td class="py-3 px-4">
                      <div class="font-bold text-white/90 text-xs">{{ comm.affiliate_email }}</div>
                      <div class="font-mono text-xs text-indigo-400 font-bold">{{ comm.referral_code }}</div>
                    </td>
                    <td class="py-3 px-4 font-mono font-bold text-emerald-400 text-sm">
                      {{ formatCurrency(comm.amount) }}
                    </td>
                    <td class="py-3 px-4">
                      <span :class="['px-2.5 py-1 rounded-md text-xs font-bold uppercase border inline-flex items-center gap-1', comm.status === 'paid' ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/15 border-amber-500/30 text-amber-400']">
                        {{ comm.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <button v-if="comm.status === 'pending'" @click="openPayoutModal(comm)" class="px-3 py-1.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/30 font-bold rounded-lg transition-colors text-xs">
                        <i class="ph-bold ph-check-circle"></i> Bayar
                      </button>
                      <div v-else class="text-xs text-white/50">
                        <i class="ph-bold ph-check-circle text-emerald-400 mr-1"></i> Telah Dibayar
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: PENGATURAN (AFILIATOR & PROFIL) ===== -->
        <div v-if="activeTab === 'settings'" class="space-y-6 animate-fade-in max-w-4xl">
          <!-- Header Bar with Edit / Action Buttons -->
          <div class="flex flex-wrap justify-between items-center gap-3 bg-white/5 p-4 rounded-2xl border border-white/10">
            <div>
              <h2 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                <i class="ph-bold ph-gear text-[#c0ff00]"></i>
                <span>Pengaturan Akun &amp; Rekening Pencairan</span>
              </h2>
              <p class="text-xs text-white/50 mt-0.5">
                {{ isEditingAffiliateSettings ? 'Perbarui informasi profil, rekening pencairan, atau password Anda di bawah ini.' : 'Informasi profil terdaftar dan rekening tujuan pencairan komisi Anda.' }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button 
                v-if="!isEditingAffiliateSettings"
                @click="isEditingAffiliateSettings = true" 
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-1.5 cursor-pointer"
              >
                <i class="ph-bold ph-pencil-simple text-sm"></i>
                <span>Edit Pengaturan</span>
              </button>
              <template v-else>
                <button 
                  @click="cancelEditAffiliateSettings" 
                  class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl transition-all cursor-pointer"
                >
                  Batal
                </button>
                <button 
                  @click="saveAffiliateSettings" 
                  :disabled="isSavingSettings" 
                  class="px-4 py-2 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-xl transition-all shadow-lg shadow-[#c0ff00]/20 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                  <i class="ph-bold ph-floppy-disk"></i>
                  <span>{{ isSavingSettings ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
              </template>
            </div>
          </div>

          <!-- 1. VIEW MODE (Data Ditampilkan Dulu Sesuai Permintaan) -->
          <div v-if="!isEditingAffiliateSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- View Card 1: Data Mitra & Profil -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-user-circle text-indigo-400 text-lg"></i>
                  <h3 class="font-black text-white text-xs uppercase tracking-wider">Informasi Profil Mitra</h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">Aktif</span>
              </div>
              <div class="space-y-3.5 text-xs">
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Nama Lengkap</span>
                  <div class="text-sm font-bold text-white">{{ affiliateSettingsForm.name || currentAdminUser?.name || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Email Akun (Identity)</span>
                  <div class="text-sm font-mono text-white/90">{{ affiliateSettingsForm.email || currentAdminUser?.email || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Peran Akun</span>
                  <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-500/15 border border-indigo-500/30 rounded-lg text-indigo-300 font-bold text-xs mt-1">
                    <i class="ph-bold ph-shield-check"></i>
                    <span>Mitra Afiliasi Resmi EduPath</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- View Card 2: Rekening Bank & E-Wallet Penarikan -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                  <i class="ph-bold ph-bank text-emerald-400 text-lg"></i>
                  <h3 class="font-black text-white text-xs uppercase tracking-wider">Rekening Pencairan Komisi</h3>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Siap Transfer</span>
              </div>
              <div class="space-y-3.5 text-xs">
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Bank / E-Wallet</span>
                  <div class="text-sm font-bold text-white">{{ affiliateSettingsForm.bank_name || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Nomor Rekening / No. HP E-Wallet</span>
                  <div class="text-sm font-mono font-bold text-[#c0ff00]">{{ affiliateSettingsForm.bank_account || '-' }}</div>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-white/40 uppercase tracking-wider mb-0.5">Nama Pemilik Rekening</span>
                  <div class="text-sm font-bold text-white uppercase">{{ affiliateSettingsForm.bank_owner || '-' }}</div>
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
                <div>
                  <span class="text-xs text-white/70">Password akun terlindungi dengan enkripsi aman standar perbankan.</span>
                </div>
                <button @click="isEditingAffiliateSettings = true" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold underline cursor-pointer">
                  Ubah Kata Sandi &rarr;
                </button>
              </div>
            </div>
          </div>

          <!-- 2. EDIT MODE (Tampil saat tombol Edit ditekan) -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Edit Card 1: Data Mitra & Profil -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center gap-2 border-b border-white/10 pb-3">
                <i class="ph-bold ph-user-circle text-indigo-400 text-lg"></i>
                <h3 class="font-black text-white text-xs uppercase tracking-wider">Edit Profil Mitra</h3>
              </div>
              <div class="space-y-3 text-xs">
                <div>
                  <label for="aff_set_name" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Lengkap</label>
                  <input id="aff_set_name" name="name" v-model="affiliateSettingsForm.name" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Nama Lengkap Mitra" />
                </div>
                <div>
                  <label for="aff_set_email" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email Akun (Identity)</label>
                  <input id="aff_set_email" name="email" v-model="affiliateSettingsForm.email" type="email" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="email@edupath.id" />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Peran Akun</label>
                  <div class="p-2.5 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-300 font-bold flex items-center gap-2">
                    <i class="ph-bold ph-shield-check"></i>
                    <span>Mitra Afiliasi Resmi EduPath</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Edit Card 2: Rekening Bank & E-Wallet Penarikan -->
            <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-2xl border border-white/10 p-5 space-y-4">
              <div class="flex items-center gap-2 border-b border-white/10 pb-3">
                <i class="ph-bold ph-bank text-emerald-400 text-lg"></i>
                <h3 class="font-black text-white text-xs uppercase tracking-wider">Edit Rekening Pencairan</h3>
              </div>
              <div class="space-y-3 text-xs">
                <div>
                  <label for="aff_set_bank_name" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Bank / E-Wallet</label>
                  <select id="aff_set_bank_name" name="bank_name" v-model="affiliateSettingsForm.bank_name" class="w-full p-2.5 bg-[#0d1427] border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]">
                    <option value="BCA">Bank Central Asia (BCA)</option>
                    <option value="MANDIRI">Bank Mandiri</option>
                    <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
                    <option value="BNI">Bank Negara Indonesia (BNI)</option>
                    <option value="BSI">Bank Syariah Indonesia (BSI)</option>
                    <option value="GOPAY">GoPay</option>
                    <option value="OVO">OVO</option>
                    <option value="DANA">DANA</option>
                  </select>
                </div>
                <div>
                  <label for="aff_set_bank_account" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nomor Rekening / No. HP E-Wallet</label>
                  <input id="aff_set_bank_account" name="bank_account" v-model="affiliateSettingsForm.bank_account" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Cth: 8830192831" />
                </div>
                <div>
                  <label for="aff_set_bank_owner" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Pemilik Rekening</label>
                  <input id="aff_set_bank_owner" name="bank_owner" v-model="affiliateSettingsForm.bank_owner" type="text" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Cth: Budi Santoso" />
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
                  <label for="aff_set_new_password" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password Baru (Kosongkan jika tidak diubah)</label>
                  <input id="aff_set_new_password" name="new_password" v-model="affiliateSettingsForm.new_password" type="password" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Minimal 6 karakter" />
                </div>
                <div>
                  <label for="aff_set_confirm_password" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Konfirmasi Password Baru</label>
                  <input id="aff_set_confirm_password" name="confirm_password" v-model="affiliateSettingsForm.confirm_password" type="password" class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white outline-none focus:border-[#c0ff00]" placeholder="Ulangi password baru" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN STAFF ===== -->
        <div v-if="activeTab === 'staff'" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Tim & Pengguna Internal</h2>
              <p class="text-[10px] text-white/40">Kelola akses tutor, admin operasional, dan role sistem</p>
            </div>
            <button @click="openStaffModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Staff
            </button>
          </div>
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/60">
                    <th class="text-center px-4 py-3 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('username')">Username <i v-if="sortKey === 'username'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('name')">Nama <i v-if="sortKey === 'name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('role')">Peran (Role) <i v-if="sortKey === 'role'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-4 py-3 font-black uppercase tracking-wider">Status</th>
                    <th class="text-right px-4 py-3 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(s, index) in sortedStaffMembers" :key="s.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-4 py-3 text-center text-white/50 font-bold text-xs">{{ index + 1 }}</td>
                    <td class="px-4 py-3 font-bold text-white text-sm leading-snug">{{ s.username }}</td>
                    <td class="px-4 py-3 font-bold text-white/80 text-xs">{{ s.name || '-' }}</td>
                    <td class="px-4 py-3 font-bold text-indigo-400 uppercase text-xs">{{ s.role || 'admin' }}</td>
                    <td class="px-4 py-3">
                      <span class="px-2.5 py-1 rounded-md font-bold text-xs bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Aktif
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                      <button @click="openStaffModal(s)" class="px-3 py-1.5 bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 hover:bg-indigo-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Edit Staff"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                      <button v-if="s.username !== 'admin'" @click="deleteStaff(s.id)" class="px-3 py-1.5 bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 hover:text-white rounded-lg text-xs font-bold inline-flex items-center gap-1 transition-all shadow-sm" title="Hapus Staff"><i class="ph-bold ph-trash"></i> Hapus</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: LAPORAN & ANALITIK ===== -->
        <div v-if="activeTab === 'reports'" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xs font-black text-white uppercase tracking-wider">Laporan & Ekspor Data Platform</h2>
              <p class="text-[10px] text-white/40">Audit operasional, ekspor CSV/Excel real-time, dan otomasi laporan</p>
            </div>
          </div>

          <!-- Quick Action Exporters -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <button @click="exportStudentsCSV" class="p-3.5 rounded-xl bg-[#0e1726]/80 border border-white/10 hover:border-[#c0ff00]/50 transition-all text-left group">
              <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg mb-2">
                <i class="ph-bold ph-file-csv"></i>
              </div>
              <h4 class="font-bold text-white text-xs">Ekspor Siswa (CSV)</h4>
              <p class="text-[10px] text-white/40 mt-0.5">Unduh data seluruh siswa aktif & paket</p>
            </button>

            <button @click="exportTransactionsCSV" class="p-3.5 rounded-xl bg-[#0e1726]/80 border border-white/10 hover:border-[#c0ff00]/50 transition-all text-left group">
              <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-lg mb-2">
                <i class="ph-bold ph-receipt"></i>
              </div>
              <h4 class="font-bold text-white text-xs">Ekspor Transaksi (CSV)</h4>
              <p class="text-[10px] text-white/40 mt-0.5">Rekap order & omset Midtrans</p>
            </button>

            <button @click="exportQuestionsCSV" class="p-3.5 rounded-xl bg-[#0e1726]/80 border border-white/10 hover:border-[#c0ff00]/50 transition-all text-left group">
              <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-lg mb-2">
                <i class="ph-bold ph-books"></i>
              </div>
              <h4 class="font-bold text-white text-xs">Ekspor Bank Soal (CSV)</h4>
              <p class="text-[10px] text-white/40 mt-0.5">Daftar bank soal & status QC</p>
            </button>

            <button @click="blastTelegramParentReport" class="p-3.5 rounded-xl bg-[#0e1726]/80 border border-white/10 hover:border-sky-400/50 transition-all text-left group">
              <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center text-lg mb-2">
                <i class="ph-bold ph-telegram-logo"></i>
              </div>
              <h4 class="font-bold text-white text-xs">Blast Telegram Orang Tua</h4>
              <p class="text-[10px] text-white/40 mt-0.5">Kirim laporan progres belajar via Telegram</p>
            </button>
          </div>

          <!-- Revenue Breakdown by Official Packages -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 p-4">
            <h3 class="font-bold text-white text-xs mb-3">Distribusi Produk Resmi EduPath</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div class="p-3 rounded-lg bg-black/40 border border-white/10">
                <div class="text-[10px] text-white/40 uppercase font-bold">Paket Mandiri</div>
                <div class="text-sm font-mono font-black text-indigo-400 mt-1">Rp 180.000 / bln</div>
                <div class="text-[10px] text-indigo-300 font-bold mt-0.5">Rp 1.296.000 / tahun (hemat 40%)</div>
                <div class="text-[10px] text-white/60 mt-1">500+ Micro-lessons, 50.000+ Soal IRT, 5x TO Nasional</div>
              </div>
              <div class="p-3 rounded-lg bg-black/40 border border-[#c0ff00]/30">
                <div class="text-[10px] text-[#c0ff00] uppercase font-bold">Paket Pejuang PTN (Terpopuler)</div>
                <div class="text-sm font-mono font-black text-[#c0ff00] mt-1">Rp 480.000 / bln</div>
                <div class="text-[10px] text-[#c0ff00]/70 font-bold mt-0.5">Rp 2.304.000 / tahun (hemat 60%)</div>
                <div class="text-[10px] text-white/60 mt-1">AI Companion 24/7, Unlimited IRT, Rasionalisasi Prodi, Telegram Report</div>
              </div>
              <div class="p-3 rounded-lg bg-black/40 border border-amber-500/30">
                <div class="text-[10px] text-amber-400 uppercase font-bold">Paket Elite Mentoring</div>
                <div class="text-sm font-mono font-black text-amber-400 mt-1">Rp 980.000 / bln</div>
                <div class="text-[10px] text-amber-300/70 font-bold mt-0.5">Rp 8.232.000 / tahun (hemat 30%)</div>
                <div class="text-[10px] text-white/60 mt-1">1-on-1 Zoom Mentoring Private, SOS Live Tutor Senior Mentor UI/ITB</div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>

    <!-- Modals -->

    <!-- Telegram Blast Delivery Status Modal -->
    <div v-if="showTelegramBlastModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-md max-h-[80vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <div class="flex items-center gap-2">
            <i class="ph-bold ph-telegram-logo text-sky-400"></i>
            <h3 class="font-black text-sm text-white">Monitor Pengiriman Telegram</h3>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-[10px] text-white/40">
              ✅ {{ telegramBlastStatuses.filter(s => s.status === 'sent').length }} / {{ telegramBlastStatuses.length }} terkirim
            </span>
            <button @click="showTelegramBlastModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
          </div>
        </div>
        <div class="p-3 space-y-2">
          <div v-for="item in telegramBlastStatuses" :key="item.chat_id" class="flex items-center gap-3 p-2.5 rounded-lg bg-black/30 border border-white/5">
            <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-sm shrink-0', item.status === 'sent' ? 'bg-emerald-500/20 text-emerald-400' : item.status === 'failed' ? 'bg-red-500/20 text-red-400' : item.status === 'sending' ? 'bg-sky-500/20 text-sky-400' : 'bg-white/10 text-white/40']">
              <i :class="item.status === 'sent' ? 'ph-bold ph-check' : item.status === 'failed' ? 'ph-bold ph-x' : item.status === 'sending' ? 'ph-bold ph-spinner animate-spin' : 'ph-bold ph-clock'"></i>
            </div>
            <div class="min-w-0 flex-grow">
              <div class="text-xs font-bold text-white truncate">{{ item.name }}</div>
              <div class="text-[10px] text-white/40 truncate">{{ item.chat_id }}</div>
              <div v-if="item.error" class="text-[10px] text-red-400 truncate">⚠ {{ item.error }}</div>
            </div>
            <span :class="['text-[9px] px-2 py-0.5 rounded-full font-black uppercase shrink-0', item.status === 'sent' ? 'bg-emerald-500/20 text-emerald-400' : item.status === 'failed' ? 'bg-red-500/20 text-red-400' : item.status === 'sending' ? 'bg-sky-500/20 text-sky-400' : 'bg-white/10 text-white/40']">
              {{ item.status === 'sent' ? 'Terkirim' : item.status === 'failed' ? 'Gagal' : item.status === 'sending' ? 'Mengirim...' : 'Antrian' }}
            </span>
          </div>
        </div>
        <div v-if="telegramBlastStatuses.every(s => s.status !== 'sending' && s.status !== 'pending')" class="p-4 border-t border-white/10 text-center">
          <p class="text-xs text-white/60 mb-3">Proses selesai: {{ telegramBlastStatuses.filter(s => s.status === 'sent').length }} terkirim, {{ telegramBlastStatuses.filter(s => s.status === 'failed').length }} gagal.</p>
          <button @click="showTelegramBlastModal = false" class="px-5 py-2 bg-[#c0ff00] text-black text-xs font-black rounded-xl hover:bg-[#b0ef00] transition-colors">Tutup</button>
        </div>
      </div>
    </div>

    <!-- Manual Order Modal -->
    <div v-if="showOrderModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">Tambah Transaksi Manual / Offline</h3>
          <button @click="showOrderModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveManualOrder" class="p-4 space-y-3">
          <div>
            <label for="order_student_id" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pilih Siswa</label>
            <select id="order_student_id" name="student_id" v-model="orderForm.student_id" @change="syncOrderPlanPrice" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="" disabled>-- Pilih Siswa --</option>
              <option v-for="s in allStudents" :key="s.id" :value="s.id" class="bg-[#0d1427] text-white">{{ s.name }} ({{ s.email }})</option>
            </select>
          </div>
          <div>
            <label for="order_plan_id" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Paket Belajar</label>
            <select id="order_plan_id" name="plan_id" v-model="orderForm.plan_id" @change="syncOrderPlanPrice" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="plan-mandiri" class="bg-[#0d1427] text-white">Paket Mandiri (Rp 100.000)</option>
              <option value="plan-utama" class="bg-[#0d1427] text-white">Paket Utama (Rp 200.000)</option>
              <option value="plan-vip" class="bg-[#0d1427] text-white">Paket VIP (Rp 450.000)</option>
            </select>
          </div>
          <div>
            <label for="order_amount" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nominal Pembayaran (Rp)</label>
            <input id="order_amount" name="amount" v-model="orderForm.amount" required type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label for="order_status" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Transaksi</label>
            <select id="order_status" name="status" v-model="orderForm.status" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="paid" class="bg-[#0d1427] text-white">Lunas / Paid (Auto-Aktifkan Paket)</option>
              <option value="pending" class="bg-[#0d1427] text-white">Pending</option>
            </select>
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="showOrderModal = false" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50">
              Simpan Transaksi
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Order Status Modal -->
    <div v-if="showOrderStatusModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-xs shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
          <h3 class="font-black text-sm text-white">Ubah Status Transaksi</h3>
          <button @click="showOrderStatusModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <div class="p-4 space-y-3">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Order ID</label>
            <div class="font-mono text-xs font-bold text-white">{{ selectedOrder?.order_id || selectedOrder?.id }}</div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Baru</label>
            <select v-model="orderStatusNew" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="paid" class="bg-[#0d1427] text-white">Paid / Settlement (Lunas)</option>
              <option value="pending" class="bg-[#0d1427] text-white">Pending</option>
              <option value="cancelled" class="bg-[#0d1427] text-white">Cancelled (Dibatalkan)</option>
              <option value="refunded" class="bg-[#0d1427] text-white">Refunded (Dikembalikan)</option>
            </select>
          </div>
        </div>
        <div class="p-4 border-t border-white/10 flex gap-2">
          <button @click="showOrderStatusModal = false" class="flex-1 py-2 rounded-lg font-bold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-colors text-xs">Batal</button>
          <button @click="submitOrderStatusUpdate" :disabled="isSaving" class="flex-1 py-2 rounded-lg font-black text-black bg-[#c0ff00] hover:bg-[#b0ef00] transition-colors text-xs flex items-center justify-center gap-1.5">
            Update Status
          </button>
        </div>
      </div>
    </div>
    
    <!-- Payout Modal -->
    <div v-if="showPayoutModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-xs shadow-2xl animate-fade-in text-white overflow-hidden">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
          <h3 class="font-black text-sm text-white">Cairkan Komisi</h3>
          <button @click="closePayoutModal" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <div class="p-4 space-y-3">
          <div class="bg-amber-500/10 text-amber-300 p-2.5 rounded-lg text-[10px] font-medium border border-amber-500/20">
            Pastikan Anda telah mentransfer dana ke rekening mitra sebelum menandai komisi ini sebagai lunas.
          </div>
          <div>
            <label class="block text-white/50 text-[10px] font-bold mb-1 uppercase tracking-wider">Mitra</label>
            <div class="font-bold text-white text-xs">{{ selectedCommission?.affiliate_email }}</div>
          </div>
          <div>
            <label class="block text-white/50 text-[10px] font-bold mb-1 uppercase tracking-wider">Nominal</label>
            <div class="font-black text-lg text-[#c0ff00]">{{ formatCurrency(selectedCommission?.amount) }}</div>
          </div>
          <div>
            <label for="payout_ref_input" class="block text-white/50 text-[10px] font-bold mb-1 uppercase tracking-wider">Referensi Pembayaran (Opsional)</label>
            <input id="payout_ref_input" name="payout_reference" v-model="payoutReference" type="text" placeholder="Misal: TRX-BCA-123" class="w-full px-3 py-1.5 rounded-lg border border-white/10 bg-black/40 focus:border-[#c0ff00]/50 outline-none text-xs font-medium text-white placeholder-white/20 transition-colors" />
          </div>
        </div>
        <div class="p-4 border-t border-white/10 flex gap-2">
          <button @click="closePayoutModal" class="flex-1 py-2 rounded-lg font-bold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-colors text-xs">Batal</button>
          <button @click="submitPayout" :disabled="isPayingOut" class="flex-1 py-2 rounded-lg font-black text-black bg-[#c0ff00] hover:bg-[#b0ef00] transition-colors text-xs flex items-center justify-center gap-1.5">
            <i v-if="isPayingOut" class="ph-bold ph-spinner animate-spin"></i>
            <span v-else>Tandai Lunas</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Reject Payout Modal -->
    <div v-if="showRejectPayoutModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-xs shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
          <h3 class="font-black text-sm text-white">Tolak Permintaan Payout</h3>
          <button @click="showRejectPayoutModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <div class="p-4 space-y-3">
          <div>
            <label for="reject_reason_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Alasan Penolakan</label>
            <textarea id="reject_reason_input" name="reject_reason" v-model="rejectReason" rows="3" placeholder="Misal: Nomor rekening tidak valid / tidak cocok dengan nama akun" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-rose-500/50 transition-all font-medium"></textarea>
          </div>
        </div>
        <div class="p-4 border-t border-white/10 flex gap-2">
          <button @click="showRejectPayoutModal = false" class="flex-1 py-2 rounded-lg font-bold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 transition-colors text-xs">Batal</button>
          <button @click="submitRejectPayout" :disabled="isSaving" class="flex-1 py-2 rounded-lg font-black text-white bg-rose-600 hover:bg-rose-500 transition-colors text-xs flex items-center justify-center gap-1.5">
            Konfirmasi Tolak
          </button>
        </div>
      </div>
    </div>

    <!-- Affiliate Modal (Tambah / Edit) -->
    <div v-if="showAffiliateModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingAffiliate ? 'Edit Mitra Afiliasi' : 'Tambah Mitra Afiliasi' }}</h3>
          <button @click="showAffiliateModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveAffiliate" class="p-4 space-y-3">
          <div>
            <label for="aff_form_email" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email Mitra</label>
            <input id="aff_form_email" name="email" v-model="affiliateForm.email" :disabled="isEditingAffiliate" required type="email" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium disabled:opacity-50" />
          </div>
          <div>
            <label for="aff_form_ref" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Kode Referral</label>
            <input id="aff_form_ref" name="referral_code" v-model="affiliateForm.referral_code" required type="text" placeholder="MISAL: BELAJARHEMAT" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium uppercase font-mono" />
          </div>
          <div>
            <label for="aff_form_comm" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Komisi (%)</label>
            <input id="aff_form_comm" name="commission_rate" v-model="affiliateForm.commission_rate" required type="number" step="0.5" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label for="aff_form_bname" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Bank</label>
              <input id="aff_form_bname" name="bank_name" v-model="affiliateForm.bank_name" placeholder="BCA / BRI / Mandiri" type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
            <div>
              <label for="aff_form_bacc" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nomor Rekening</label>
              <input id="aff_form_bacc" name="bank_account" v-model="affiliateForm.bank_account" placeholder="1234567890" type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
          </div>
          <div>
            <label for="aff_form_bowner" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Pemilik Rekening</label>
            <input id="aff_form_bowner" name="bank_owner" v-model="affiliateForm.bank_owner" placeholder="Nama sesuai buku tabungan" type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="showAffiliateModal = false" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50">
              Simpan Mitra
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Ajukan Penarikan Dana (Payout) -->
    <div v-if="showPayoutRequestModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0e1726] border border-white/10 rounded-2xl w-full max-w-md p-6 space-y-5 shadow-2xl animate-fade-in text-white">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <i class="ph-bold ph-wallet text-amber-400 text-lg"></i>
            <h3 class="font-black text-sm text-white">Tarik Saldo Komisi</h3>
          </div>
          <button @click="showPayoutRequestModal = false" class="text-white/40 hover:text-white transition-colors cursor-pointer">
            <i class="ph-bold ph-x text-base"></i>
          </button>
        </div>

        <div class="space-y-4 text-xs">
          <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl space-y-1">
            <span class="text-[10px] text-amber-300 font-bold uppercase tracking-wider">Saldo Komisi Tersedia</span>
            <div class="text-2xl font-black font-mono text-amber-300">
              {{ formatCurrency(myAffiliateData?.stats?.total_pending || 0) }}
            </div>
            <p class="text-[10px] text-white/60">Minimal penarikan dana adalah Rp 50.000</p>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nominal Penarikan (Rp)</label>
            <input
              v-model.number="payoutRequestAmount"
              type="number"
              min="50000"
              :max="myAffiliateData?.stats?.total_pending || 0"
              class="w-full p-2.5 bg-black/40 border border-white/10 rounded-xl text-white font-mono font-bold text-sm outline-none focus:border-amber-400"
            />
          </div>

          <div class="p-3 bg-white/5 rounded-xl border border-white/10 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-white/50 font-bold uppercase tracking-wider">Rekening Tujuan</span>
              <button @click="showPayoutRequestModal = false; activeTab = 'settings'" class="text-[10px] text-indigo-400 hover:underline cursor-pointer">
                Ubah di Pengaturan
              </button>
            </div>
            <div class="font-bold text-white">{{ affiliateSettingsForm.bank_name || myAffiliateData?.bank_info?.bank_name }} - {{ affiliateSettingsForm.bank_account || myAffiliateData?.bank_info?.bank_account }}</div>
            <div class="text-[10px] text-white/50">a.n {{ affiliateSettingsForm.bank_owner || myAffiliateData?.bank_info?.bank_owner }}</div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
          <button
            type="button"
            @click="showPayoutRequestModal = false"
            class="px-4 py-2 text-xs font-bold text-white/60 hover:text-white rounded-xl transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="submitPayoutRequest"
            :disabled="isSubmittingPayout || payoutRequestAmount < 50000 || payoutRequestAmount > (myAffiliateData?.stats?.total_pending || 0)"
            class="px-5 py-2 bg-amber-400 hover:bg-amber-300 disabled:opacity-50 text-black text-xs font-black rounded-xl transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
          >
            <i class="ph-bold ph-paper-plane-tilt" :class="{'animate-spin': isSubmittingPayout}"></i>
            <span>{{ isSubmittingPayout ? 'Memproses...' : 'Konfirmasi Penarikan' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Student Modal (Tambah / Edit) -->
    <div v-if="showStudentModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingStudent ? 'Edit Siswa' : 'Tambah Siswa Baru' }}</h3>
          <button @click="showStudentModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveStudent" class="p-4 space-y-3">
          <div>
            <label for="student_form_name" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Lengkap</label>
            <input id="student_form_name" name="name" v-model="studentForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div v-if="isSuperadmin && tenantsList.length > 1">
            <label for="student_form_tenant" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Cabang / Lembaga Bimbel (Opsional)</label>
            <select id="student_form_tenant" name="tenant_id" v-model="studentForm.tenant_id" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" :disabled="isEditingStudent">
              <option value="" class="bg-[#0d1427] text-white">-- Bimbel Utama (Default) --</option>
              <option v-for="t in tenantsList" :key="t.id" :value="t.id" class="bg-[#0d1427] text-white">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label for="student_form_email" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email</label>
            <input id="student_form_email" name="email" v-model="studentForm.email" required type="email" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label for="student_form_password" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password {{ isEditingStudent ? '(Kosongkan jika tidak ubah)' : '' }}</label>
            <input id="student_form_password" name="password" v-model="studentForm.password" :required="!isEditingStudent" type="password" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label for="student_form_plan" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Paket Belajar</label>
            <select id="student_form_plan" name="plan" v-model="studentForm.plan" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="free" class="bg-[#0d1427] text-white">Free / Trial</option>
              <option value="mandiri" class="bg-[#0d1427] text-white">Paket Mandiri</option>
              <option value="utama" class="bg-[#0d1427] text-white">Paket Utama (Pejuang PTN)</option>
              <option value="vip" class="bg-[#0d1427] text-white">Paket VIP (Elite Mentoring)</option>
            </select>
          </div>
          <div v-if="isEditingStudent">
            <label for="student_form_status" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Akun</label>
            <select id="student_form_status" name="is_active" v-model="studentForm.is_active" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option :value="1" class="bg-[#0d1427] text-white">Aktif</option>
              <option :value="0" class="bg-[#0d1427] text-white">Nonaktif</option>
            </select>
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="showStudentModal = false" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors cursor-pointer">Batal</button>
            <button type="button" @click="saveStudent" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
              <i v-if="isSaving" class="ph-bold ph-spinner animate-spin"></i>
              <span>{{ isSaving ? 'Menyimpan...' : 'Simpan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Plan Modal -->
    <div v-if="showPlanModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingPlan ? 'Edit Paket' : 'Tambah Paket Baru' }}</h3>
          <button @click="closePlanModal" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="savePlan" class="p-4 space-y-3">
          <div>
            <label for="plan_form_name" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Paket</label>
            <input id="plan_form_name" name="name" v-model="pForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label for="plan_form_price" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Harga Asli</label>
              <input id="plan_form_price" name="price" v-model="pForm.price" required type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
            <div>
              <label for="plan_form_discount" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Diskon</label>
              <input id="plan_form_discount" name="discount" v-model="pForm.discount" type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
            <div>
              <label for="plan_form_duration" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Durasi (Hr)</label>
              <input id="plan_form_duration" name="duration" v-model="pForm.duration" required type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1.5 uppercase tracking-wider">Entitlement (Fitur Akses)</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <label v-for="ent in entitlementsDict" :key="ent.key" class="flex items-center gap-2 p-2 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5 transition-colors text-xs" :class="{'bg-[#c0ff00]/10 border-[#c0ff00]/40 text-white': pForm.features.includes(ent.key)}">
                <input type="checkbox" :id="'plan_ent_' + ent.key" :name="'plan_ent_' + ent.key" :value="ent.key" v-model="pForm.features" class="w-3.5 h-3.5 text-[#c0ff00] rounded border-white/20 bg-black/40 focus:ring-[#c0ff00]" />
                <span class="text-xs font-medium">{{ ent.label }}</span>
              </label>
            </div>
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="closePlanModal" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Staff Modal -->
    <div v-if="showStaffModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingStaff ? 'Edit Staff' : 'Tambah Staff Baru' }}</h3>
          <button @click="closeStaffModal" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveStaff" class="p-4 space-y-3">
          <div>
            <label for="staff_form_username" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email (sebagai Username)</label>
            <input id="staff_form_username" name="username" v-model="sForm.username" :disabled="isEditingStaff && sForm.username === 'admin'" required type="text" placeholder="nama@bimbel.com" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium disabled:opacity-50" />
          </div>
          <div v-if="isSuperadmin && tenantsList.length > 1">
            <label for="staff_form_tenant" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Cabang / Lembaga Bimbel (Opsional)</label>
            <select id="staff_form_tenant" name="tenant_id" v-model="sForm.tenant_id" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" :disabled="isEditingStaff">
              <option value="" class="bg-[#0d1427] text-white">-- Bimbel Utama (Default) --</option>
              <option v-for="t in tenantsList" :key="t.id" :value="t.id" class="bg-[#0d1427] text-white">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label for="staff_form_name" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Lengkap</label>
            <input id="staff_form_name" name="name" v-model="sForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label for="staff_form_role" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Peran (Role)</label>
            <select id="staff_form_role" name="role" v-model="sForm.role" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" :disabled="sForm.username === 'admin'">
              <option value="teacher" class="bg-[#0d1427] text-white">Guru / Tutor</option>
              <option value="admin" class="bg-[#0d1427] text-white">Administrator Utama</option>
            </select>
          </div>
          <div>
            <label for="staff_form_password" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password {{ isEditingStaff ? '(Kosongkan jika tidak ubah)' : '' }}</label>
            <input id="staff_form_password" name="password" v-model="sForm.password" :required="!isEditingStaff" type="password" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="closeStaffModal" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Question Modal -->
    <div v-if="showQuestionModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingQuestion ? 'Edit Soal' : 'Tambah Soal Baru' }}</h3>
          <button @click="closeQuestionModal" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveQuestion" class="p-4 space-y-3">
          <!-- Row 1: Ujian & Komponen -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="q_exam_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Ujian</label>
              <input id="q_exam_input" name="exam" v-model="qForm.exam" readonly class="w-full p-2 text-xs text-white/70 bg-black/40 border border-white/10 rounded-lg outline-none cursor-not-allowed font-medium" />
            </div>
            <div>
              <label for="q_component_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Komponen Ujian</label>
              <select id="q_component_select" name="test_component" v-model="qForm.test_component" @change="onQComponentChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="c in qAvailableComponents" :key="c.name" :value="c.name" class="bg-[#0d1427] text-white">{{ c.label || c.name }}</option>
              </select>
            </div>
          </div>

          <!-- Row 2: Subtes & Topik -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="q_subtest_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtes</label>
              <select id="q_subtest_select" name="subtest" v-model="qForm.subtest" @change="onQSubtestChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="s in qAvailableSubtests" :key="s.name" :value="s.name" class="bg-[#0d1427] text-white">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label for="q_topic_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Topik</label>
              <select id="q_topic_select" name="topic" v-model="qForm.topic" @change="onQTopicChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="t in qAvailableTopics" :key="t.name" :value="t.name" class="bg-[#0d1427] text-white">{{ t.name }}</option>
              </select>
            </div>
          </div>

          <!-- Row 3: Subtopik & Skill -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="q_subtopic_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtopik</label>
              <input id="q_subtopic_input" name="subtopic" v-model="qForm.subtopic" list="qSubtopicDatalist" placeholder="e.g. Pola Bilangan..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="qSubtopicDatalist">
                <option v-for="st in (qCurrentTopicObj?.subtopics || [])" :key="st" :value="st"></option>
              </datalist>
            </div>
            <div>
              <label for="q_skill_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Skill / Kompetensi</label>
              <input id="q_skill_input" name="skill" v-model="qForm.skill" list="qSkillDatalist" placeholder="e.g. Identifikasi Pola..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="qSkillDatalist">
                <option v-for="sk in (qCurrentTopicObj?.skills || [])" :key="sk" :value="sk"></option>
              </datalist>
            </div>
          </div>

          <!-- Row 4: Indikator & Tipe Soal -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="q_indicator_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Indikator Soal</label>
              <input id="q_indicator_input" name="indicator" v-model="qForm.indicator" list="qIndicatorDatalist" placeholder="e.g. Menentukan pola berikutnya..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="qIndicatorDatalist">
                <option v-for="ind in (qCurrentTopicObj?.indicators || [])" :key="ind" :value="ind"></option>
              </datalist>
            </div>
            <div>
              <label for="q_type_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tipe Soal</label>
              <select id="q_type_select" name="question_type" v-model="qForm.question_type" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="multiple_choice" class="bg-[#0d1427] text-white">Pilihan Ganda (A-E)</option>
                <option value="complex_mcq" class="bg-[#0d1427] text-white">Pilihan Ganda Kompleks</option>
                <option value="short_answer" class="bg-[#0d1427] text-white">Isian Singkat</option>
                <option value="true_false" class="bg-[#0d1427] text-white">Pernyataan Benar / Salah</option>
              </select>
            </div>
          </div>

          <!-- Row 5: Kesulitan, Penggunaan, Kognitif & QC -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label for="q_diff_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tingkat Kesulitan</label>
              <select id="q_diff_select" name="difficulty" v-model="qForm.difficulty" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="easy" class="bg-[#0d1427] text-white">Mudah</option>
                <option value="medium" class="bg-[#0d1427] text-white">Sedang</option>
                <option value="hard" class="bg-[#0d1427] text-white">Sulit</option>
              </select>
            </div>
            <div>
              <label for="q_usage_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Penggunaan</label>
              <select id="q_usage_select" name="usage_type" v-model="qForm.usage_type" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="latihan" class="bg-[#0d1427] text-white">Latihan Harian</option>
                <option value="tryout" class="bg-[#0d1427] text-white">Tryout Resmi</option>
                <option value="diagnostik" class="bg-[#0d1427] text-white">Diagnostik</option>
              </select>
            </div>
            <div>
              <label for="q_cog_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Level Kognitif</label>
              <select id="q_cog_select" name="cognitive_demand" v-model="qForm.cognitive_demand" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="C1" class="bg-[#0d1427] text-white">C1 - Mengingat</option>
                <option value="C2" class="bg-[#0d1427] text-white">C2 - Memahami</option>
                <option value="C3" class="bg-[#0d1427] text-white">C3 - Aplikasi</option>
                <option value="C4" class="bg-[#0d1427] text-white">C4 - Analisis</option>
                <option value="C5" class="bg-[#0d1427] text-white">C5 - Evaluasi</option>
                <option value="C6" class="bg-[#0d1427] text-white">C6 - Mencipta</option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input type="checkbox" id="qc_passed" name="is_qc_passed" v-model="qForm.is_qc_passed" :true-value="1" :false-value="0" class="w-3.5 h-3.5 text-[#c0ff00] bg-black/40 border-white/20 rounded focus:ring-[#c0ff00]">
            <label for="qc_passed" class="text-xs font-bold text-white/80 cursor-pointer">Telah Melewati Proses QC (Guru / Superadmin)</label>
          </div>
          <div>
            <label for="q_question_textarea" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pertanyaan</label>
            <textarea id="q_question_textarea" name="question" v-model="qForm.question" required rows="2" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium"></textarea>
          </div>
          <div class="space-y-2">
            <label class="block text-[10px] font-bold text-white/50 uppercase tracking-wider">Opsi Jawaban</label>
            <div v-for="opt in ['a','b','c','d','e']" :key="opt" class="flex items-center gap-1.5">
              <input type="radio" :id="'correct_opt_' + opt" v-model="qForm.correct" :value="opt" name="correctOpt" required class="w-3.5 h-3.5 text-[#c0ff00]" />
              <label :for="'correct_opt_' + opt" class="text-xs font-bold uppercase w-5 text-white/80 cursor-pointer">{{ opt }}.</label>
              <input :id="'q_opt_' + opt" :name="'option_' + opt" v-model="qForm['option_' + opt]" :required="opt !== 'e'" type="text" placeholder="..." class="flex-grow p-2 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 placeholder-white/20 transition-all font-medium" />
            </div>
          </div>
          <div>
            <label for="q_explanation_textarea" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pembahasan Lengkap</label>
            <textarea id="q_explanation_textarea" name="explanation" v-model="qForm.explanation" rows="2" placeholder="Uraian langkah penyelesaian..." class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium"></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="closeQuestionModal" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50 flex items-center gap-1.5">
              <i v-if="isSaving" class="ph-bold ph-spinner animate-spin"></i> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Material Modal -->
    <div v-if="showMaterialModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingMaterial ? 'Edit Materi' : 'Tambah Materi Baru' }}</h3>
          <button @click="closeMaterialModal" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveMaterial" class="p-4 space-y-3">
          <div>
            <label for="m_title_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Judul Materi</label>
            <input id="m_title_input" name="title" v-model="mForm.title" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <!-- Komponen & Subtes -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="m_component_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Komponen Ujian</label>
              <select id="m_component_select" name="test_component" v-model="mForm.test_component" @change="onMComponentChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="c in mAvailableComponents" :key="c.name" :value="c.name" class="bg-[#0d1427] text-white">{{ c.label || c.name }}</option>
              </select>
            </div>
            <div>
              <label for="m_subtest_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtes</label>
              <select id="m_subtest_select" name="subtest" v-model="mForm.subtest" @change="onMSubtestChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="s in mAvailableSubtests" :key="s.name" :value="s.name" class="bg-[#0d1427] text-white">{{ s.name }}</option>
              </select>
            </div>
          </div>

          <!-- Topik & Subtopik -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="m_topic_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Topik</label>
              <select id="m_topic_select" name="topic" v-model="mForm.topic" @change="onMTopicChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="t in mAvailableTopics" :key="t.name" :value="t.name" class="bg-[#0d1427] text-white">{{ t.name }}</option>
              </select>
            </div>
            <div>
              <label for="m_subtopic_input" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtopik</label>
              <input id="m_subtopic_input" name="subtopic" v-model="mForm.subtopic" list="mSubtopicDatalist" placeholder="e.g. Pola Bilangan..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="mSubtopicDatalist">
                <option v-for="st in (mCurrentTopicObj?.subtopics || [])" :key="st" :value="st"></option>
              </datalist>
            </div>
          </div>
          <div>
            <label for="m_teacher_select" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Guru / Penanggung Jawab</label>
            <select id="m_teacher_select" name="teacher_name" v-model="mForm.teacher_name" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="" class="bg-[#0d1427] text-white">-- Pilih Guru / Tidak Ada --</option>
              <option v-for="s in staffMembers" :key="s.id" :value="s.name" class="bg-[#0d1427] text-white">{{ s.name }} ({{ s.role }})</option>
            </select>
          </div>
          <div>
            <label for="m_file_upload" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Lampiran Dokumen / File Materi (PDF, DOCX, PPT)</label>
            <div class="flex items-center gap-2">
              <input id="m_file_upload" name="file" type="file" ref="materialFileInput" @change="handleMaterialFileUpload" class="text-xs text-white/70 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#c0ff00] file:text-black hover:file:bg-[#b0ef00] cursor-pointer" />
              <span v-if="isUploadingFile" class="text-xs text-[#c0ff00] animate-pulse">Mengunggah...</span>
            </div>
            <input id="m_file_url_input" name="file_url" aria-label="URL File / Lampiran" v-model="mForm.file_url" type="text" placeholder="URL File / Lampiran (otomatis terisi setelah unggah)" class="mt-1.5 w-full p-2 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none font-mono" />
          </div>
          <div>
            <label for="m_content_textarea" class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Isi / Konten Materi</label>
            <textarea id="m_content_textarea" name="content" v-model="mForm.content" required rows="5" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium"></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="closeMaterialModal" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50 flex items-center gap-1.5">
              <i v-if="isSaving" class="ph-bold ph-spinner animate-spin"></i> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Question Full Preview Modal -->
    <div v-if="showQuestionPreviewModal && selectedQuestionForPreview" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded bg-[#c0ff00]/15 text-[#c0ff00] font-black text-xs uppercase">{{ selectedQuestionForPreview.test_component || 'TPS' }}</span>
            <span class="text-xs font-bold text-white/70">{{ selectedQuestionForPreview.subtest || selectedQuestionForPreview.sub_materi }}</span>
            <span v-if="selectedQuestionForPreview.teacher_name" class="text-xs text-amber-300 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20 font-bold">
              👨‍🏫 {{ selectedQuestionForPreview.teacher_name }}
            </span>
          </div>
          <button @click="closeQuestionPreview" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <div class="p-6 space-y-6">
          <div class="space-y-2">
            <span class="text-xs font-black uppercase tracking-wider text-sky-400">Pertanyaan Soal:</span>
            <div class="p-4 rounded-xl bg-white/5 border border-white/10 text-base leading-relaxed text-white font-medium" v-html="selectedQuestionForPreview.question"></div>
          </div>
          <div class="space-y-2">
            <span class="text-xs font-black uppercase tracking-wider text-white/50">Pilihan Jawaban:</span>
            <div class="space-y-2">
              <div
                v-for="opt in ['a','b','c','d','e']" :key="opt"
                v-show="selectedQuestionForPreview['option_' + opt]"
                class="p-3 rounded-xl border flex items-start gap-3 text-sm transition-all"
                :class="(selectedQuestionForPreview.correct || '').toLowerCase() === opt ? 'bg-emerald-500/15 border-emerald-500/50 text-emerald-200' : 'bg-white/5 border-white/10 text-white/80'"
              >
                <span class="w-6 h-6 rounded-lg flex items-center justify-center font-black text-xs shrink-0 uppercase"
                      :class="(selectedQuestionForPreview.correct || '').toLowerCase() === opt ? 'bg-emerald-400 text-black' : 'bg-white/10 text-white/70'">
                  {{ opt }}
                </span>
                <div class="pt-0.5 flex-grow" v-html="selectedQuestionForPreview['option_' + opt]"></div>
                <span v-if="(selectedQuestionForPreview.correct || '').toLowerCase() === opt" class="text-xs font-black text-emerald-400 shrink-0">✓ Kunci Jawaban</span>
              </div>
            </div>
          </div>
          <div v-if="selectedQuestionForPreview.explanation" class="space-y-2 pt-2 border-t border-white/10">
            <span class="text-xs font-black uppercase tracking-wider text-amber-400">📖 Pembahasan Lengkap:</span>
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-sm text-amber-100 leading-relaxed font-medium" v-html="selectedQuestionForPreview.explanation"></div>
          </div>
        </div>
        <div class="p-4 border-t border-white/10 flex justify-end">
          <button @click="closeQuestionPreview" class="px-5 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl transition-all">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api';
import { SNBT_TAXONOMY } from '../EduData.js';

const router = useRouter()

// ── Data ──
const isAuthenticated = ref(false);
const isLoggingOut = ref(false);
const sidebarOpen = ref(true);
const initialTab = () => {
  const stored = sessionStorage.getItem('admin_active_tab');
  const role = sessionStorage.getItem('admin_role') || localStorage.getItem('user_role') || '';
  if (['affiliate', 'afiliator'].includes(role)) {
    return (['affiliate_dashboard', 'affiliate_referrals', 'affiliate_payouts', 'settings'].includes(stored)) ? stored : 'affiliate_dashboard';
  }
  return stored || 'overview';
};
const activeTab = ref(initialTab());

// Sorting state
const sortKey = ref('');
const sortOrder = ref('asc');

const setSort = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
};

const doSort = (list) => {
  if (!sortKey.value || !Array.isArray(list)) return list;
  return [...list].sort((a, b) => {
    let valA = a[sortKey.value] !== undefined && a[sortKey.value] !== null ? a[sortKey.value] : '';
    let valB = b[sortKey.value] !== undefined && b[sortKey.value] !== null ? b[sortKey.value] : '';
    if (typeof valA === 'string') valA = valA.toLowerCase();
    if (typeof valB === 'string') valB = valB.toLowerCase();
    if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
    if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
};
const studentSearch = ref('');
const studentPlanFilter = ref('all');
const qSearch = ref('');
const qSubMateri = ref('all');

// ── Stats (Overview Dashboard) ──
const stats = reactive({
  totalStudents: 0,
  activeStudents: 0,
  paidStudents: 0,
  totalQuizzes: 0,
  avgScore: '0',
  totalRevenue: 0,
});

function extractUserIdFromToken(token) {
  try {
    if (!token) return '';
    const parts = token.split('.');
    if (parts.length < 2) return '';
    const base64Url = parts[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)).join(''));
    const p = JSON.parse(jsonPayload);
    return p.user_id || p.id || '';
  } catch (e) {
    return '';
  }
}

function extractRoleFromToken(token) {
  try {
    if (!token) return '';
    const parts = token.split('.');
    if (parts.length < 2) return '';
    const base64Url = parts[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)).join(''));
    return JSON.parse(jsonPayload).role || '';
  } catch (e) {
    return '';
  }
}

const currentRole = ref(
  sessionStorage.getItem('admin_role') ||
  extractRoleFromToken(sessionStorage.getItem('admin_token') || sessionStorage.getItem('ep_admin_token')) ||
  localStorage.getItem('user_role') ||
  ''
);

const getStoredAdminUser = () => {
  try {
    const raw = sessionStorage.getItem('ep_user') || localStorage.getItem('user');
    if (raw) return JSON.parse(raw);
  } catch (e) {}
  const name = localStorage.getItem('user_name') || '';
  const email = localStorage.getItem('user_email') || '';
  if (name || email) return { name, email };
  return null;
};
const currentAdminUser = ref(getStoredAdminUser());

const isSuperadmin = computed(() => {
  return currentRole.value === 'superadmin' ||
         localStorage.getItem('user_role') === 'superadmin' ||
         sessionStorage.getItem('admin_role') === 'superadmin';
});

const isTeacher = computed(() => ['teacher', 'guru', 'tutor'].includes(currentRole.value));
const isAffiliate = computed(() => ['affiliate', 'afiliator'].includes(currentRole.value));

// ── Nav Items (Role-filtered) ──
const navItems = computed(() => {
  if (isAffiliate.value) {
    return [
      { id: 'affiliate_dashboard', label: 'Dashboard Afiliasi', icon: 'ph-chart-pie-slice', roles: ['affiliate'] },
      { id: 'affiliate_referrals', label: 'Siswa Rujukan Saya', icon: 'ph-users-three',      roles: ['affiliate'] },
      { id: 'affiliate_payouts',   label: 'Pencairan Komisi',    icon: 'ph-wallet',           roles: ['affiliate'] },
      { id: 'settings',            label: 'Pengaturan',          icon: 'ph-gear',             roles: ['affiliate'] }
    ];
  }

  const isT = isTeacher.value;
  const isSuper = isSuperadmin.value || currentRole.value === 'superadmin';
  const role = currentRole.value || 'admin';

  const items = [
    { id: 'overview',     label: 'Dashboard',             icon: 'ph-squares-four',  roles: ['superadmin', 'admin', 'teacher'] },
    ...(isT ? [{ id: 'teacher_hub', label: 'Siswa & Jadwal Saya', icon: 'ph-chalkboard-teacher', roles: ['teacher'] }] : []),
    { id: 'questions',   label: isT ? 'Bank Soal Saya' : 'Soal', icon: 'ph-books', roles: ['superadmin', 'admin', 'teacher'] },
    { id: 'materials',   label: isT ? 'Materi Saya' : 'Materi', icon: 'ph-file-text', roles: ['superadmin', 'admin', 'teacher'] },
    { id: 'students',    label: 'Siswa',                 icon: 'ph-users-three',   roles: ['superadmin', 'admin'] },
    { id: 'transactions',label: 'Transaksi',             icon: 'ph-receipt',       roles: ['superadmin', 'admin'] },
    { id: 'packages',    label: 'Paket',                 icon: 'ph-package',       roles: ['superadmin', 'admin'] },
    { id: 'affiliates',  label: 'Afiliasi & Komisi',     icon: 'ph-hand-coins',    roles: ['superadmin', 'admin'] },
    { id: 'staff',       label: 'Staff',                 icon: 'ph-users-three',   roles: ['superadmin'] },
    { id: 'reports',     label: 'Laporan & Analitik',    icon: 'ph-chart-bar',     roles: ['superadmin', 'admin'] },
  ];

  if (isSuper) return items;
  return items.filter(item => !item.roles || item.roles.includes(role));
});

const currentNavItem = computed(() => navItems.value.find(item => item.id === activeTab.value));

const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
};

const stripHtml = (html) => {
  if (!html) return '';
  return String(html).replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, ' ').trim();
};

// ── Question Preview & Teacher Ownership State ──
const showQuestionPreviewModal = ref(false);
const selectedQuestionForPreview = ref(null);
const teacherQuestionFilter = ref('all');
const questionFilterTeacher = teacherQuestionFilter;
const teacherMaterialFilter = ref('all');

const openQuestionPreview = (q) => {
  selectedQuestionForPreview.value = q;
  showQuestionPreviewModal.value = true;
};

const closeQuestionPreview = () => {
  showQuestionPreviewModal.value = false;
  selectedQuestionForPreview.value = null;
};

const canManageQuestion = (q) => {
  if (isSuperadmin.value || currentRole.value === 'admin') return true;
  if (isTeacher.value) {
    if (!q.teacher_id && !q.teacher_name) return true;
    return q.teacher_id === currentUserId.value || (currentAdminUser.value?.name && q.teacher_name === currentAdminUser.value.name);
  }
  return false;
};

const canManageMaterial = (m) => {
  if (isSuperadmin.value || currentRole.value === 'admin') return true;
  if (isTeacher.value) {
    if (!m.teacher_id && !m.teacher_name) return true;
    return m.teacher_id === currentUserId.value || (currentAdminUser.value?.name && m.teacher_name === currentAdminUser.value.name);
  }
  return false;
};

const isUploadingFile = ref(false);
const handleMaterialFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  isUploadingFile.value = true;
  try {
    const formData = new FormData();
    formData.append('file', file);
    const token = sessionStorage.getItem('admin_token') || localStorage.getItem('auth_token');
    const apiUrl = import.meta.env.VITE_API_URL || '/api';
    const res = await fetch(`${apiUrl}/admin.php?action=upload_material_file`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formData
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Gagal mengunggah file');
    mForm.file_url = data.file_url;
  } catch (err) {
    alert(err.message || 'Gagal mengunggah file');
  } finally {
    isUploadingFile.value = false;
  }
};

const tenantsList = ref([]);
const showPassword = ref(false);
const loginForm = ref({ username: '', password: '' });
const loginError = ref('');
const loginLoading = ref(false);

const fillCredentials = (user, pass) => {
  loginForm.value.username = user;
  loginForm.value.password = pass;
  loginError.value = '';
};

const loadRoleData = () => {
  if (isAffiliate.value) {
    if (!['affiliate_dashboard', 'affiliate_referrals', 'affiliate_payouts', 'settings'].includes(activeTab.value)) {
      activeTab.value = 'affiliate_dashboard';
    }
    loadMyAffiliateData();
    loadAffiliateSettings();
  } else if (isTeacher.value) {
    if (!['questions', 'materials', 'teacher_hub'].includes(activeTab.value)) {
      activeTab.value = 'questions';
    }
    fetchQuestions();
    fetchMaterials();
  } else {
    fetchDashboard();
    fetchStudents();
    fetchAdminOrders();
    loadPlansAndStaff();
    fetchQuestions();
    fetchMaterials();
  }
};

const doLogin = async () => {
  loginError.value = '';
  loginLoading.value = true;
  try {
    const u = (loginForm.value.username || loginForm.value.email || '').trim();
    const p = (loginForm.value.password || '').trim();
    const res = await api.adminLogin(u, p);
    sessionStorage.setItem('admin_token', res.token);
    if (res.csrf_token) sessionStorage.setItem('ep_admin_csrf', res.csrf_token);
    const roleFound = res.user?.role || extractRoleFromToken(res.token) || '';
    if (roleFound) {
      currentRole.value = roleFound;
      localStorage.setItem('user_role', roleFound);
      sessionStorage.setItem('admin_role', roleFound);
    }
    isAuthenticated.value = true;
    loadRoleData();
  } catch (err) {
    loginError.value = err.message || 'Login gagal';
  } finally {
    loginLoading.value = false;
  }
};

const doLogout = async () => {
  isLoggingOut.value = true;
  document.body.style.pointerEvents = 'none'; // Prevent further clicks
  
  try {
    await api.logout();
  } catch (e) {
    console.warn("Logout error:", e);
  }
  
  sessionStorage.clear();
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user_role');
  localStorage.removeItem('user_name');
  localStorage.removeItem('user_email');
  localStorage.removeItem('user');
  sessionStorage.removeItem('admin_token');
  sessionStorage.removeItem('admin_role');
  sessionStorage.removeItem('ep_user');
  currentRole.value = '';
  
  document.body.style.pointerEvents = '';
  window.location.href = window.location.origin + window.location.pathname + '#/';
  window.location.reload();
};

onMounted(async () => {
  const adminToken = sessionStorage.getItem('admin_token') || sessionStorage.getItem('ep_admin_token');
  const mainToken = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
  
  const tokenToInspect = adminToken || mainToken;
  if (tokenToInspect) {
    const roleFromTok = extractRoleFromToken(tokenToInspect);
    const storedRole = sessionStorage.getItem('admin_role') || localStorage.getItem('user_role') || '';
    const roleToUse = (storedRole === 'affiliate' || roleFromTok === 'affiliate') ? 'affiliate' : (roleFromTok || storedRole);
    if (roleToUse) {
      currentRole.value = roleToUse;
      localStorage.setItem('user_role', roleToUse);
      sessionStorage.setItem('admin_role', roleToUse);
    }
  }

  if (adminToken) {
    isAuthenticated.value = true;
    loadRoleData();
  } else if (mainToken) {
    try {
      const profile = await api.getProfile();
      if (['admin', 'superadmin', 'teacher', 'affiliate'].includes(profile?.user?.role)) {
        sessionStorage.setItem('admin_token', mainToken);
        currentRole.value = profile.user.role;
        localStorage.setItem('user_role', profile.user.role);
        sessionStorage.setItem('admin_role', profile.user.role);
        isAuthenticated.value = true;
        loadRoleData();
      }
    } catch(e) {
      console.warn("Admin auto-auth check:", e);
    }
  }
});

// ── Dashboard Stats ──
const fetchDashboard = async () => {
  try {
    const res = await api.getAdminDashboard();
    if (res?.stats) {
      Object.assign(stats, res.stats);
    } else if (res && typeof res === 'object') {
      Object.assign(stats, res);
    }
  } catch (err) {
    console.error("Gagal mengambil dashboard:", err);
  }
};

// ── Students ──
const allStudents = ref([]);
const isEditingStudent = ref(false);
const showStudentModal = ref(false);
const isSaving = ref(false);
const studentForm = reactive({ id: null, tenant_id: '', name: '', email: '', password: '', plan: 'free', is_active: 1 });

// ── Pagination helpers ──
const PAGE_SIZE = 10;
const studentPage = ref(1);
const questionPage = ref(1);
const materialPage = ref(1);
const planPage = ref(1);
const orderPage = ref(1);

const paginate = (list, page) => {
  const start = (page - 1) * PAGE_SIZE;
  return list.slice(start, start + PAGE_SIZE);
};
const totalPages = (list) => Math.max(1, Math.ceil((list?.length || 0) / PAGE_SIZE));

const fetchStudents = async () => {
  try {
    const res = await api.getAdminStudents(1, '', '');
    allStudents.value = Array.isArray(res) ? res : (res.students || []);
    studentPage.value = 1;
  } catch (err) {
    console.error("Gagal mengambil data siswa:", err);
  }
};

const filteredStudents = computed(() => {
  const filtered = allStudents.value.filter(s => {
    const matchSearch = !studentSearch.value ||
      (s.name || '').toLowerCase().includes(studentSearch.value.toLowerCase()) ||
      (s.email || '').toLowerCase().includes(studentSearch.value.toLowerCase());
    const matchPlan = studentPlanFilter.value === 'all' || s.plan === studentPlanFilter.value;
    return matchSearch && matchPlan;
  });
  return doSort(filtered);
});
const pagedStudents = computed(() => paginate(filteredStudents.value, studentPage.value));
const studentTotalPages = computed(() => totalPages(filteredStudents.value));

const openCreateStudentModal = () => {
  isSaving.value = false;
  isEditingStudent.value = false;
  const defTenant = tenantsList.value?.[0]?.id || '';
  Object.assign(studentForm, { id: null, tenant_id: defTenant, name: '', email: '', password: '', plan: 'free', is_active: 1 });
  showStudentModal.value = true;
};

const openEditStudentModal = (s) => {
  isSaving.value = false;
  isEditingStudent.value = true;
  Object.assign(studentForm, { id: s.id, tenant_id: s.tenant_id || tenantsList.value?.[0]?.id || '', name: s.name, email: s.email, password: '', plan: s.plan || 'free', is_active: s.is_active != 0 ? 1 : 0 });
  showStudentModal.value = true;
};

const saveStudent = async () => {
  if (!studentForm.name || !studentForm.name.trim()) {
    alert("Nama lengkap siswa wajib diisi!");
    return;
  }
  if (!studentForm.email || !studentForm.email.trim()) {
    alert("Email siswa wajib diisi!");
    return;
  }
  isSaving.value = true;
  try {
    if (isEditingStudent.value) {
      await api.updateAdminStudent(studentForm.id, studentForm);
    } else {
      await api.createAdminStudent(studentForm);
    }
    await fetchStudents();
    await fetchDashboard();
    showStudentModal.value = false;
    alert("Data siswa berhasil disimpan!");
  } catch (err) {
    alert(err.message || "Gagal menyimpan data siswa");
  } finally {
    isSaving.value = false;
  }
};

const deleteStudent = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus akun siswa ini? Data tryout dan nilai akan ikut terhapus.')) return;
  try {
    await api.deleteAdminStudent(id);
    await fetchStudents();
    await fetchDashboard();
  } catch (err) {
    alert(err.message || "Gagal menghapus siswa");
  }
};

// ── Admin Orders / Transactions ──
const adminOrders = ref([]);
const ordersLoading = ref(false);
const showOrderModal = ref(false);
const showOrderStatusModal = ref(false);
const selectedOrder = ref(null);
const orderStatusNew = ref('paid');
const orderForm = reactive({ student_id: '', plan_id: 'plan-utama', plan_name: 'Paket Utama', amount: 450000, status: 'paid' });

const syncOrderPlanPrice = () => {
  if (orderForm.plan_id === 'plan-mandiri') {
    orderForm.plan_name = 'Paket Mandiri';
    orderForm.amount = 100000;
  } else if (orderForm.plan_id === 'plan-utama') {
    orderForm.plan_name = 'Paket Utama';
    orderForm.amount = 200000;
  } else if (orderForm.plan_id === 'plan-vip') {
    orderForm.plan_name = 'Paket VIP';
    orderForm.amount = 450000;
  }
};

const fetchAdminOrders = async () => {
  ordersLoading.value = true;
  try {
    const res = await api.getAdminOrders();
    adminOrders.value = res.orders || (Array.isArray(res) ? res : []);
  } catch (err) {
    console.error("Gagal mengambil transaksi:", err);
  } finally {
    ordersLoading.value = false;
  }
};

const pagedOrders = computed(() => paginate(adminOrders.value, orderPage.value));
const orderTotalPages = computed(() => totalPages(adminOrders.value));

const openCreateOrderModal = () => {
  if (allStudents.value.length === 0) fetchStudents();
  orderForm.student_id = allStudents.value[0]?.id || '';
  orderForm.plan_id = 'plan-utama';
  orderForm.plan_name = 'Paket Utama';
  orderForm.amount = 450000;
  orderForm.status = 'paid';
  showOrderModal.value = true;
};

const saveManualOrder = async () => {
  isSaving.value = true;
  try {
    await api.createAdminOrder(orderForm);
    showOrderModal.value = false;
    await fetchAdminOrders();
    await fetchStudents();
    await fetchDashboard();
  } catch (err) {
    alert(err.message || "Gagal membuat transaksi manual");
  } finally {
    isSaving.value = false;
  }
};

const openEditOrderStatusModal = (order) => {
  selectedOrder.value = order;
  orderStatusNew.value = order.status || 'paid';
  showOrderStatusModal.value = true;
};

const submitOrderStatusUpdate = async () => {
  if (!selectedOrder.value) return;
  isSaving.value = true;
  try {
    await api.updateAdminOrderStatus(selectedOrder.value.order_id || selectedOrder.value.id, orderStatusNew.value);
    showOrderStatusModal.value = false;
    await fetchAdminOrders();
    await fetchStudents();
    await fetchDashboard();
  } catch (err) {
    alert(err.message || "Gagal update status transaksi");
  } finally {
    isSaving.value = false;
  }
};

const deleteOrder = async (orderId) => {
  if (!confirm('Apakah Anda yakin ingin menghapus catatan transaksi ini?')) return;
  try {
    await api.deleteAdminOrder(orderId);
    await fetchAdminOrders();
    await fetchDashboard();
  } catch (err) {
    alert(err.message || "Gagal menghapus transaksi");
  }
};

// ── Questions ──
const serverQuestions = ref([]);
const questionsLoading = ref(false);
const showQuestionModal = ref(false);
const isEditingQuestion = ref(false);

const qForm = reactive({
  id: null,
  exam: 'SNBT',
  test_component: 'TPS',
  subtest: 'Penalaran Umum',
  sub_materi: 'Penalaran Umum',
  topic: 'Induktif',
  subtopic: 'Pola Bilangan',
  skill: 'Identifikasi Pola',
  indicator: 'Menentukan pola berikutnya',
  question_type: 'multiple_choice',
  difficulty: 'medium',
  question: '',
  option_a: '',
  option_b: '',
  option_c: '',
  option_d: '',
  option_e: '',
  correct: 'a',
  usage_type: 'latihan',
  cognitive_demand: 'C3',
  source_type: 'author_created',
  rights_status: 'verified',
  is_qc_passed: 1,
  explanation: ''
});

// Cascading helpers for Question Form
const qAvailableComponents = computed(() => SNBT_TAXONOMY?.components || []);

const qAvailableSubtests = computed(() => {
  const comp = qAvailableComponents.value.find(c => c.name === qForm.test_component);
  return comp ? comp.subtests : [];
});

const qAvailableTopics = computed(() => {
  const sub = qAvailableSubtests.value.find(s => s.name === qForm.subtest);
  return sub ? sub.topics : [];
});

const qCurrentTopicObj = computed(() => {
  return qAvailableTopics.value.find(t => t.name === qForm.topic) || null;
});

const onQComponentChange = () => {
  const firstSub = qAvailableSubtests.value[0]?.name || '';
  qForm.subtest = firstSub;
  onQSubtestChange();
};

const onQSubtestChange = () => {
  qForm.sub_materi = qForm.subtest;
  const firstTopic = qAvailableTopics.value[0]?.name || '';
  qForm.topic = firstTopic;
  onQTopicChange();
};

const onQTopicChange = () => {
  const topObj = qCurrentTopicObj.value;
  qForm.subtopic = topObj?.subtopics?.[0] || '';
  qForm.skill = topObj?.skills?.[0] || '';
  qForm.indicator = topObj?.indicators?.[0] || '';
};

const fetchQuestions = async () => {
  questionsLoading.value = true;
  try {
    const res = await api.getAdminQuestions(1, qSubMateri.value === 'all' ? '' : qSubMateri.value);
    serverQuestions.value = Array.isArray(res) ? res : (res.questions || []);
  } catch (err) {
    console.error("Gagal memuat soal", err);
  } finally {
    questionsLoading.value = false;
  }
};

const filteredQuestions = computed(() => {
  let list = serverQuestions.value;
  if (teacherQuestionFilter.value === 'mine') {
    list = list.filter(q => canManageQuestion(q));
  }
  if (qSubMateri.value && qSubMateri.value !== 'all') {
    const sM = qSubMateri.value.toLowerCase();
    list = list.filter(q => 
      (q.sub_materi && q.sub_materi.toLowerCase().includes(sM)) || 
      (q.subtest && q.subtest.toLowerCase().includes(sM)) ||
      (q.topic && q.topic.toLowerCase().includes(sM))
    );
  }
  if (qSearch.value) {
    const s = qSearch.value.toLowerCase();
    list = list.filter(q => 
      (q.question && q.question.toLowerCase().includes(s)) ||
      (q.sub_materi && q.sub_materi.toLowerCase().includes(s)) ||
      (q.subtest && q.subtest.toLowerCase().includes(s)) ||
      (q.topic && q.topic.toLowerCase().includes(s)) ||
      (q.skill && q.skill.toLowerCase().includes(s))
    );
  }
  return doSort(list);
});
const pagedQuestions = computed(() => paginate(filteredQuestions.value, questionPage.value));
const questionTotalPages = computed(() => totalPages(filteredQuestions.value));

const openQuestionModal = (q = null) => {
  if (q) {
    isEditingQuestion.value = true;
    let comp = q.test_component || '';
    const sub = q.subtest || q.sub_materi || 'Penalaran Umum';
    if (!comp) {
      const isLiterasi = ['Literasi Bahasa Indonesia', 'Literasi Bahasa Inggris', 'Penalaran Matematika'].includes(sub);
      comp = isLiterasi ? 'TES LITERASI' : 'TPS';
    }
    Object.assign(qForm, {
      ...q,
      exam: q.exam || 'SNBT',
      test_component: comp,
      subtest: sub,
      sub_materi: sub,
      topic: q.topic || q.bab || 'Induktif',
      subtopic: q.subtopic || '',
      skill: q.skill || '',
      indicator: q.indicator || '',
      question_type: q.question_type || 'multiple_choice',
      usage_type: q.usage_type || 'latihan',
      cognitive_demand: q.cognitive_demand || 'C3',
      is_qc_passed: q.is_qc_passed ? 1 : 0
    });
  } else {
    isEditingQuestion.value = false;
    Object.assign(qForm, {
      id: null,
      exam: 'SNBT',
      test_component: 'TPS',
      subtest: 'Penalaran Umum',
      sub_materi: 'Penalaran Umum',
      topic: 'Induktif',
      subtopic: 'Pola Bilangan',
      skill: 'Identifikasi Pola',
      indicator: 'Menentukan pola berikutnya',
      question_type: 'multiple_choice',
      difficulty: 'medium',
      question: '',
      option_a: '',
      option_b: '',
      option_c: '',
      option_d: '',
      option_e: '',
      correct: 'a',
      usage_type: 'latihan',
      cognitive_demand: 'C3',
      source_type: 'author_created',
      rights_status: 'verified',
      is_qc_passed: 1,
      explanation: ''
    });
  }
  showQuestionModal.value = true;
};

const closeQuestionModal = () => showQuestionModal.value = false;

const saveQuestion = async () => {
  isSaving.value = true;
  try {
    if (isEditingQuestion.value) {
      await api.updateAdminQuestion(qForm.id, qForm);
    } else {
      await api.createAdminQuestion(qForm);
    }
    closeQuestionModal();
    fetchQuestions();
    fetchDashboard();
  } catch (err) {
    alert("Gagal menyimpan soal");
  } finally {
    isSaving.value = false;
  }
};

const deleteQuestion = async (id) => {
  if (!confirm('Yakin ingin menghapus soal ini?')) return;
  try {
    await api.deleteAdminQuestion(id);
    fetchQuestions();
    fetchDashboard();
  } catch (err) {
    alert("Gagal menghapus soal");
  }
};

// ── Materials ──
const mSearch = ref('');
const serverMaterials = ref([]);
const materialsLoading = ref(false);
const showMaterialModal = ref(false);
const isEditingMaterial = ref(false);

const mForm = reactive({
  id: null,
  title: '',
  content: '',
  exam: 'SNBT',
  test_component: 'TPS',
  subtest: 'Penalaran Umum',
  sub_materi: 'Penalaran Umum',
  topic: 'Induktif',
  subtopic: '',
  teacher_name: ''
});

// Cascading helpers for Material Form
const mAvailableComponents = computed(() => SNBT_TAXONOMY?.components || []);

const mAvailableSubtests = computed(() => {
  const comp = mAvailableComponents.value.find(c => c.name === mForm.test_component);
  return comp ? comp.subtests : [];
});

const mAvailableTopics = computed(() => {
  const sub = mAvailableSubtests.value.find(s => s.name === mForm.subtest);
  return sub ? sub.topics : [];
});

const mCurrentTopicObj = computed(() => {
  return mAvailableTopics.value.find(t => t.name === mForm.topic) || null;
});

const onMComponentChange = () => {
  const firstSub = mAvailableSubtests.value[0]?.name || '';
  mForm.subtest = firstSub;
  onMSubtestChange();
};

const onMSubtestChange = () => {
  mForm.sub_materi = mForm.subtest;
  const firstTopic = mAvailableTopics.value[0]?.name || '';
  mForm.topic = firstTopic;
  onMTopicChange();
};

const onMTopicChange = () => {
  const topObj = mCurrentTopicObj.value;
  mForm.subtopic = topObj?.subtopics?.[0] || '';
};

const fetchMaterials = async () => {
  materialsLoading.value = true;
  try {
    const res = await api.getAdminMaterials();
    serverMaterials.value = res.materials || (Array.isArray(res) ? res : []);
  } catch (err) {
    console.error("Gagal memuat materi", err);
  } finally {
    materialsLoading.value = false;
  }
};

const filteredMaterials = computed(() => {
  let list = serverMaterials.value;
  if (teacherMaterialFilter.value === 'mine') {
    list = list.filter(m => canManageMaterial(m));
  }
  if (mSearch.value) {
    const s = mSearch.value.toLowerCase();
    list = list.filter(m => 
      (m.title && m.title.toLowerCase().includes(s)) || 
      (m.sub_materi && m.sub_materi.toLowerCase().includes(s)) ||
      (m.subtest && m.subtest.toLowerCase().includes(s)) ||
      (m.topic && m.topic.toLowerCase().includes(s))
    );
  }
  return doSort(list);
});
const pagedMaterials = computed(() => paginate(filteredMaterials.value, materialPage.value));
const materialTotalPages = computed(() => totalPages(filteredMaterials.value));

const openMaterialModal = (m = null) => {
  if (m) {
    isEditingMaterial.value = true;
    let comp = m.test_component || '';
    const sub = m.subtest || m.sub_materi || 'Penalaran Umum';
    if (!comp) {
      const isLiterasi = ['Literasi Bahasa Indonesia', 'Literasi Bahasa Inggris', 'Penalaran Matematika'].includes(sub);
      comp = isLiterasi ? 'TES LITERASI' : 'TPS';
    }
    Object.assign(mForm, {
      ...m,
      exam: m.exam || 'SNBT',
      test_component: comp,
      subtest: sub,
      sub_materi: sub,
      topic: m.topic || 'Induktif',
      subtopic: m.subtopic || '',
      teacher_name: m.teacher_name || '',
      title: m.title || '',
      content: m.content || ''
    });
  } else {
    isEditingMaterial.value = false;
    Object.assign(mForm, {
      id: null,
      exam: 'SNBT',
      test_component: 'TPS',
      subtest: 'Penalaran Umum',
      sub_materi: 'Penalaran Umum',
      topic: 'Induktif',
      subtopic: '',
      teacher_name: '',
      title: '',
      content: ''
    });
  }
  showMaterialModal.value = true;
};

const closeMaterialModal = () => showMaterialModal.value = false;

const saveMaterial = async () => {
  isSaving.value = true;
  try {
    if (isEditingMaterial.value) {
      await api.updateAdminMaterial(mForm.id, mForm);
    } else {
      await api.createAdminMaterial(mForm);
    }
    closeMaterialModal();
    fetchMaterials();
  } catch (err) {
    alert("Gagal menyimpan materi");
  } finally {
    isSaving.value = false;
  }
};

const deleteMaterial = async (id) => {
  if (!confirm('Yakin ingin menghapus materi ini?')) return;
  try {
    await api.deleteAdminMaterial(id);
    fetchMaterials();
  } catch (err) {
    alert("Gagal menghapus materi");
  }
};

// ── Plans / Packages ──
const plans = ref([]);
const sortedPlans = computed(() => doSort(plans.value));
const pagedPlans = computed(() => paginate(sortedPlans.value, planPage.value));
const planTotalPages = computed(() => totalPages(sortedPlans.value));
const showPlanModal = ref(false);
const isEditingPlan = ref(false);
const pForm = reactive({ id: null, name: '', price: '', discount: 0, duration: '', features: [] });
const entitlementsDict = ref([]);

const loadPlansAndStaff = async () => {
  try {
    const pRes = await api.getAdminPlans().catch(err => { console.error('Gagal fetch plans', err); return []; });
    const rawPlans = Array.isArray(pRes) ? pRes : (pRes?.plans || []);
    plans.value = rawPlans;
    plans.value.forEach(p => {
      if (typeof p.features === 'string') {
        try { p.features = JSON.parse(p.features); } catch (e) { p.features = []; }
      }
      if (!Array.isArray(p.features)) p.features = [];
    });
  } catch (err) {
    console.error('Error load plans:', err);
  }

  try {
    const sRes = await api.getAdminStaff().catch(() => ({ staff: [] }));
    staffMembers.value = sRes?.staff || (Array.isArray(sRes) ? sRes : []);
  } catch (err) { console.error('Error load staff:', err); }

  try {
    const eRes = await api.getEntitlementsDictionary().catch(() => []);
    entitlementsDict.value = Array.isArray(eRes) ? eRes : [];
  } catch (err) { console.error('Error load entitlements:', err); }

  try {
    const tRes = await api.getAdminTenants().catch(() => []);
    tenantsList.value = Array.isArray(tRes) ? tRes : [];
  } catch (err) { console.error('Gagal fetch tenants', err); }
};

const openPlanModal = (p = null) => {
  if (p) {
    isEditingPlan.value = true;
    Object.assign(pForm, { ...p, features: [...(p.features || [])] });
  } else {
    isEditingPlan.value = false;
    Object.assign(pForm, { id: null, name: '', price: '', discount: 0, duration: '', features: [] });
  }
  showPlanModal.value = true;
};

const closePlanModal = () => showPlanModal.value = false;

const savePlan = async () => {
  try {
    isSaving.value = true;
    const payload = { ...pForm };
    if (isEditingPlan.value) await api.updateAdminPlan(pForm.id, payload);
    else await api.createAdminPlan(payload);
    await loadPlansAndStaff();
    closePlanModal();
  } catch (err) { alert(err.message); } finally { isSaving.value = false; }
};

const deletePlan = async (id) => {
  if (confirm('Nonaktifkan paket ini?')) {
    try { await api.deleteAdminPlan(id); await loadPlansAndStaff(); } catch (err) { alert(err.message); }
  }
};

// ── Personal Affiliator Portal State ──
const myAffiliateData = ref({
  referral_code: '',
  commission_rate: 20,
  bank_info: { bank_name: '', bank_account: '', bank_owner: '' },
  stats: { total_referrals: 0, active_referrals: 0, total_paid: 0, total_pending: 0 },
  referrals: [],
  history: [],
  payouts: []
});
const isLoadingMyAffiliate = ref(false);
const showPayoutRequestModal = ref(false);
const payoutRequestAmount = ref(50000);
const isSubmittingPayout = ref(false);
const referralSearchQuery = ref('');

const referralLinkText = computed(() => {
  const code = myAffiliateData.value?.referral_code;
  return code ? `${window.location.origin}/ref/${code}` : 'Memuat tautan...';
});

const filteredMyReferrals = computed(() => {
  const list = myAffiliateData.value?.referrals || [];
  if (!referralSearchQuery.value) return list;
  const q = referralSearchQuery.value.toLowerCase();
  return list.filter(r => 
    (r.name && r.name.toLowerCase().includes(q)) ||
    (r.email && r.email.toLowerCase().includes(q)) ||
    (r.school && r.school.toLowerCase().includes(q)) ||
    (r.target_ptn && r.target_ptn.toLowerCase().includes(q))
  );
});

const loadMyAffiliateData = async () => {
  isLoadingMyAffiliate.value = true;
  try {
    const res = await api.getAffiliateDashboardData();
    if (res && res.status === 'joined') {
      myAffiliateData.value = res;
      if (res.bank_info) {
        affiliateSettingsForm.bank_name = res.bank_info.bank_name || affiliateSettingsForm.bank_name;
        affiliateSettingsForm.bank_account = res.bank_info.bank_account || affiliateSettingsForm.bank_account;
        affiliateSettingsForm.bank_owner = res.bank_info.bank_owner || affiliateSettingsForm.bank_owner;
      }
    }
  } catch (err) {
    console.error('Gagal memuat data afiliasi:', err);
  } finally {
    isLoadingMyAffiliate.value = false;
  }
};

const copyMyReferralLink = () => {
  if (!myAffiliateData.value?.referral_code) return;
  const link = `${window.location.origin}/ref/${myAffiliateData.value.referral_code}`;
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(link);
  }
  alert('Link referral berhasil disalin ke clipboard!\n' + link);
};

const shareWhatsApp = () => {
  if (!myAffiliateData.value?.referral_code) return;
  const link = `${window.location.origin}/ref/${myAffiliateData.value.referral_code}`;
  const text = encodeURIComponent(`Halo! Yuk gabung persiapan UTBK-SNBT bersama Bimbel EduPath. Dapatkan tryout IRT dan materi terlengkap di sini: ${link}`);
  window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
};

const openAffiliatePayoutModal = () => {
  const pending = myAffiliateData.value.stats?.total_pending || 0;
  if (pending < 50000) {
    alert('Saldo komisi belum mencapai batas minimal penarikan (Rp 50.000). Saldo saat ini: ' + formatCurrency(pending));
    return;
  }
  payoutRequestAmount.value = pending;
  showPayoutRequestModal.value = true;
};

const submitPayoutRequest = async () => {
  const pending = myAffiliateData.value.stats?.total_pending || 0;
  if (payoutRequestAmount.value < 50000) {
    alert('Nominal penarikan minimal Rp 50.000');
    return;
  }
  if (payoutRequestAmount.value > pending) {
    alert('Nominal penarikan melebihi saldo komisi yang tersedia (' + formatCurrency(pending) + ')');
    return;
  }
  const bank = myAffiliateData.value.bank_info || {};
  if (!bank.bank_account || !bank.bank_name) {
    alert('Harap lengkapi rekening bank tujuan pencairan terlebih dahulu di Pengaturan Rekening.');
    showPayoutRequestModal.value = false;
    activeTab.value = 'settings';
    return;
  }

  isSubmittingPayout.value = true;
  try {
    const res = await api.requestAffiliatePayout(payoutRequestAmount.value, bank);
    alert(res.message || 'Pengajuan penarikan dana berhasil dikirim! Menunggu verifikasi admin.');
    showPayoutRequestModal.value = false;
    await loadMyAffiliateData();
  } catch (err) {
    alert(err.message || 'Gagal mengajukan penarikan');
  } finally {
    isSubmittingPayout.value = false;
  }
};

// ── Admin Affiliates & Commissions Management ──
const affiliatesList = ref([]);
const commissionsList = ref([]);
const payoutsList = ref([]);
const isLoadingAffiliates = ref(false);
const isLoadingCommissions = ref(false);
const isLoadingPayouts = ref(false);
const showAffiliateModal = ref(false);
const isEditingAffiliate = ref(false);
const affiliateForm = reactive({ id: null, email: '', name: '', referral_code: '', commission_rate: 20.0, bank_name: '', bank_account: '', bank_owner: '' });
const showRejectPayoutModal = ref(false);
const selectedPayoutToReject = ref(null);
const rejectReason = ref('');

const loadAdminAffiliates = async () => {
  isLoadingAffiliates.value = true;
  try {
    affiliatesList.value = await api.getAdminAffiliates();
  } catch (err) {
    console.error('Gagal memuat afiliasi', err);
  } finally {
    isLoadingAffiliates.value = false;
  }
};

const loadAdminCommissions = async () => {
  isLoadingCommissions.value = true;
  try {
    commissionsList.value = await api.getAdminCommissions();
  } catch (err) {
    console.error('Gagal memuat komisi', err);
  } finally {
    isLoadingCommissions.value = false;
  }
};

const loadAdminPayouts = async () => {
  isLoadingPayouts.value = true;
  try {
    payoutsList.value = await api.getAdminPayouts();
  } catch (err) {
    console.error('Gagal memuat payout', err);
  } finally {
    isLoadingPayouts.value = false;
  }
};

const openCreateAffiliateModal = () => {
  isEditingAffiliate.value = false;
  Object.assign(affiliateForm, { id: null, email: '', name: '', referral_code: '', commission_rate: 20.0, bank_name: '', bank_account: '', bank_owner: '' });
  showAffiliateModal.value = true;
};

const openEditAffiliateModal = (aff) => {
  isEditingAffiliate.value = true;
  Object.assign(affiliateForm, {
    id: aff.id,
    email: aff.email || aff.identity_key,
    name: aff.affiliate_name || '',
    referral_code: aff.referral_code,
    commission_rate: aff.commission_rate || 20.0,
    bank_name: aff.bank_name || '',
    bank_account: aff.bank_account || '',
    bank_owner: aff.bank_owner || ''
  });
  showAffiliateModal.value = true;
};

const saveAffiliate = async () => {
  isSaving.value = true;
  try {
    if (isEditingAffiliate.value) {
      await api.updateAdminAffiliate(affiliateForm.id, affiliateForm);
    } else {
      await api.createAdminAffiliate(affiliateForm);
    }
    showAffiliateModal.value = false;
    loadAdminAffiliates();
  } catch (err) {
    alert(err.message || "Gagal menyimpan mitra");
  } finally {
    isSaving.value = false;
  }
};

const deleteAffiliate = async (id) => {
  if (!confirm("Hapus mitra afiliasi ini?")) return;
  try {
    await api.deleteAdminAffiliate(id);
    loadAdminAffiliates();
  } catch (err) {
    alert(err.message || "Gagal menghapus mitra");
  }
};

const approvePayoutReq = async (id) => {
  if (!confirm("Tandai payout ini sebagai 'Telah Ditransfer'?")) return;
  try {
    await api.approvePayout(id);
    loadAdminPayouts();
    loadAdminCommissions();
  } catch (err) {
    alert(err.message || 'Gagal memproses payout');
  }
};

const openRejectPayoutModal = (pay) => {
  selectedPayoutToReject.value = pay;
  rejectReason.value = 'Nomor rekening tidak valid atau dana gagal diproses bank';
  showRejectPayoutModal.value = true;
};

const submitRejectPayout = async () => {
  if (!selectedPayoutToReject.value) return;
  isSaving.value = true;
  try {
    await api.rejectPayout(selectedPayoutToReject.value.id, rejectReason.value);
    showRejectPayoutModal.value = false;
    loadAdminPayouts();
    loadAdminCommissions();
  } catch (err) {
    alert(err.message || "Gagal menolak payout");
  } finally {
    isSaving.value = false;
  }
};

// Payout Modal
const showPayoutModal = ref(false);
const selectedCommission = ref(null);
const payoutReference = ref('');
const isPayingOut = ref(false);

const openPayoutModal = (comm) => {
  selectedCommission.value = comm;
  payoutReference.value = '';
  showPayoutModal.value = true;
};

const closePayoutModal = () => {
  showPayoutModal.value = false;
  selectedCommission.value = null;
  payoutReference.value = '';
};

const submitPayout = async () => {
  if (!selectedCommission.value) return;
  isPayingOut.value = true;
  try {
    await api.payoutCommission(selectedCommission.value.id, payoutReference.value);
    closePayoutModal();
    loadAdminCommissions();
  } catch (err) {
    alert(err.message || 'Gagal mencairkan komisi');
  } finally {
    isPayingOut.value = false;
  }
};

// ── Affiliate Portal Settings ──
const isSavingSettings = ref(false);
const isEditingAffiliateSettings = ref(false);
const cancelEditAffiliateSettings = () => {
  isEditingAffiliateSettings.value = false;
  loadAffiliateSettings();
};

const affiliateSettingsForm = reactive({
  name: '',
  email: '',
  bank_name: '',
  bank_account: '',
  bank_owner: '',
  new_password: '',
  confirm_password: ''
});

const loadAffiliateSettings = async () => {
  try {
    const res = await api.getAffiliateSettings();
    if (res && res.settings) {
      affiliateSettingsForm.name = res.settings.name || '';
      affiliateSettingsForm.email = res.settings.email || '';
      affiliateSettingsForm.bank_name = res.settings.bank_name || '';
      affiliateSettingsForm.bank_account = res.settings.bank_account || '';
      affiliateSettingsForm.bank_owner = res.settings.bank_owner || '';

      if (res.settings.name) {
        currentAdminUser.value = {
          ...(currentAdminUser.value || {}),
          name: res.settings.name,
          email: res.settings.email || currentAdminUser.value?.email || ''
        };
        sessionStorage.setItem('ep_user', JSON.stringify(currentAdminUser.value));
        localStorage.setItem('user_name', res.settings.name);
      }
    }
  } catch (err) {
    console.error('Gagal memuat pengaturan afiliasi:', err);
  }
};

const saveAffiliateSettings = async () => {
  if (affiliateSettingsForm.new_password) {
    if (affiliateSettingsForm.new_password.length < 6) {
      alert('Password minimal 6 karakter.');
      return;
    }
    if (affiliateSettingsForm.new_password !== affiliateSettingsForm.confirm_password) {
      alert('Konfirmasi password tidak cocok.');
      return;
    }
  }

  isSavingSettings.value = true;
  try {
    await api.saveAffiliateSettings({
      name: affiliateSettingsForm.name,
      email: affiliateSettingsForm.email,
      bank_name: affiliateSettingsForm.bank_name,
      bank_account: affiliateSettingsForm.bank_account,
      bank_owner: affiliateSettingsForm.bank_owner,
      new_password: affiliateSettingsForm.new_password
    });
    alert('Pengaturan berhasil disimpan!');
    isEditingAffiliateSettings.value = false;
    affiliateSettingsForm.new_password = '';
    affiliateSettingsForm.confirm_password = '';
    if (affiliateSettingsForm.name) {
      currentAdminUser.value = {
        ...(currentAdminUser.value || {}),
        name: affiliateSettingsForm.name,
        email: affiliateSettingsForm.email || currentAdminUser.value?.email || ''
      };
      sessionStorage.setItem('ep_user', JSON.stringify(currentAdminUser.value));
      localStorage.setItem('user_name', affiliateSettingsForm.name);
    }
    loadAffiliateSettings();
  } catch (err) {
    alert(err.message || 'Gagal menyimpan pengaturan.');
  } finally {
    isSavingSettings.value = false;
  }
};

// ── Staff ──
const staffMembers = ref([]);
const sortedStaffMembers = computed(() => doSort(staffMembers.value));
const showStaffModal = ref(false);
const isEditingStaff = ref(false);
const sForm = reactive({ id: null, tenant_id: '', username: '', name: '', role: 'teacher', password: '' });

const openStaffModal = (s = null) => {
  const defTenant = tenantsList.value?.[0]?.id || '';
  if (s) {
    isEditingStaff.value = true;
    Object.assign(sForm, { ...s, password: '', tenant_id: s.tenant_id || defTenant });
  } else {
    isEditingStaff.value = false;
    Object.assign(sForm, { id: null, tenant_id: defTenant, username: '', name: '', role: 'teacher', password: '' });
  }
  showStaffModal.value = true;
};

const closeStaffModal = () => showStaffModal.value = false;

const saveStaff = async () => {
  try {
    isSaving.value = true;
    if (isEditingStaff.value) await api.updateAdminStaff(sForm.id, sForm);
    else await api.createAdminStaff(sForm);
    await loadPlansAndStaff();
    closeStaffModal();
  } catch (err) { alert(err.message); } finally { isSaving.value = false; }
};

const deleteStaff = async (id) => {
  if (confirm('Nonaktifkan staff ini?')) {
    try { await api.deleteAdminStaff(id); await loadPlansAndStaff(); } catch (err) { alert(err.message); }
  }
};

// ── Reports Exporters ──
const downloadCSV = (filename, rows) => {
  const processRow = (row) => {
    return row.map(val => {
      if (val === null || val === undefined) return '""';
      let text = String(val).replace(/"/g, '""');
      return `"${text}"`;
    }).join(',');
  };
  const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + rows.map(processRow).join("\n");
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const exportStudentsCSV = () => {
  if (allStudents.value.length === 0) {
    alert("Belum ada data siswa untuk diekspor");
    return;
  }
  const headers = ["ID", "Nama Lengkap", "Email", "Paket Belajar", "Status Aktif", "Terdaftar Pada"];
  const rows = [headers];
  allStudents.value.forEach(s => {
    rows.push([
      s.id,
      s.name,
      s.email,
      s.plan || 'free',
      s.is_active != 0 ? 'Aktif' : 'Nonaktif',
      s.created_at || '-'
    ]);
  });
  downloadCSV(`EduPath_Siswa_${new Date().toISOString().slice(0,10)}.csv`, rows);
};

const exportTransactionsCSV = () => {
  if (adminOrders.value.length === 0) {
    alert("Belum ada data transaksi untuk diekspor");
    return;
  }
  const headers = ["Order ID", "Nama Siswa", "Email Siswa", "Paket Belajar", "Nominal (Rp)", "Status", "Metode Bayar", "Tanggal"];
  const rows = [headers];
  adminOrders.value.forEach(o => {
    rows.push([
      o.order_id || o.id,
      o.student_name || '-',
      o.student_email || '-',
      o.plan_name || o.plan_id || '-',
      o.amount || 0,
      o.status || 'pending',
      o.payment_type || '-',
      o.created_at || '-'
    ]);
  });
  downloadCSV(`EduPath_Transaksi_${new Date().toISOString().slice(0,10)}.csv`, rows);
};

const exportQuestionsCSV = () => {
  if (serverQuestions.value.length === 0) {
    alert("Belum ada data bank soal untuk diekspor");
    return;
  }
  const headers = ["ID", "Sub Materi", "Tingkat Kesulitan", "Pertanyaan", "Opsi A", "Opsi B", "Opsi C", "Opsi D", "Opsi E", "Kunci Jawaban", "Status QC", "Level Kognitif"];
  const rows = [headers];
  serverQuestions.value.forEach(q => {
    rows.push([
      q.id,
      q.sub_materi || q.subtes,
      q.difficulty || 'medium',
      q.question,
      q.option_a,
      q.option_b,
      q.option_c,
      q.option_d,
      q.option_e || '',
      (q.correct || 'a').toUpperCase(),
      q.is_qc_passed == 1 ? 'Lolos QC' : 'Belum QC',
      q.cognitive_demand || 'C3'
    ]);
  });
  downloadCSV(`EduPath_BankSoal_${new Date().toISOString().slice(0,10)}.csv`, rows);
};

// ── Telegram Blast with Delivery Status Monitor ──
const telegramBlastStatuses = ref([]); // [{name, chat_id, status: 'sending'|'sent'|'failed', error}]
const showTelegramBlastModal = ref(false);

const blastTelegramParentReport = async () => {
  const botToken = import.meta.env.VITE_TELEGRAM_BOT_TOKEN || '';
  if (!botToken || botToken === 'YOUR_TELEGRAM_BOT_TOKEN') {
    showToast('⚠️ Telegram Bot Token belum dikonfigurasi di .env (VITE_TELEGRAM_BOT_TOKEN).');
    return;
  }

  // Kumpulkan siswa dengan telegram_chat_id
  const targets = allStudents.value.filter(s => s.telegram_chat_id || s.parent_telegram_id);
  if (targets.length === 0) {
    showToast('Tidak ada nomor Telegram orang tua yang terdaftar.');
    return;
  }

  telegramBlastStatuses.value = targets.map(s => ({ name: s.name || s.username, chat_id: s.telegram_chat_id || s.parent_telegram_id, status: 'pending', error: null }));
  showTelegramBlastModal.value = true;

  for (let i = 0; i < telegramBlastStatuses.value.length; i++) {
    const item = telegramBlastStatuses.value[i];
    item.status = 'sending';
    const message = `📚 *Laporan Belajar EduPath*\n\nHalo Bapak/Ibu Wali dari *${item.name}*,\n\nBerikut ringkasan progres belajar ananda:\n✅ Tryout Terselesaikan: 5x\n📊 Rata-rata Skor SNBT: 685 (Target 700+)\n💎 Status Paket: Aktif\n\nTerus dukung ananda meraih PTN Impian bersama *EduPath.ai*!`;
    try {
      const res = await fetch(`https://api.telegram.org/bot${botToken}/sendMessage`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chat_id: item.chat_id, text: message, parse_mode: 'Markdown' })
      });
      const data = await res.json();
      if (res.ok && data.ok) {
        item.status = 'sent';
        item.message_id = data.result?.message_id;
      } else {
        item.status = 'failed';
        item.error = data.description || 'Gagal kirim';
      }
    } catch (e) {
      item.status = 'failed';
      item.error = e.message || 'Network error';
    }
    await new Promise(r => setTimeout(r, 300)); // rate limit delay
  }
};

// Removed: blastWAParentReport (replaced by Telegram)

// ── Teacher Hub reactive data ──
const teacherStudents = computed(() => {
  if (!isTeacher.value) return [];
  const myName = currentAdminUser.value?.name || '';
  // Siswa yang punya teacher_name = nama guru ini
  const fromQuestions = allStudents.value.filter(s => s.teacher_name === myName || s.assigned_teacher === myName);
  if (fromQuestions.length > 0) return fromQuestions;
  // Fallback: tampilkan semua siswa aktif jika belum ada assignment
  return allStudents.value.slice(0, 20);
});

const teacherSubjects = computed(() => {
  if (!isTeacher.value) return [];
  const myName = currentAdminUser.value?.name || '';
  const subjects = new Set();
  serverQuestions.value.filter(q => q.teacher_name === myName || !q.teacher_name).forEach(q => {
    if (q.sub_materi) subjects.add(q.sub_materi);
    if (q.subtes) subjects.add(q.subtes);
  });
  serverMaterials.value.filter(m => m.teacher_name === myName || !m.teacher_name).forEach(m => {
    if (m.subtest) subjects.add(m.subtest);
    if (m.sub_materi) subjects.add(m.sub_materi);
  });
  return [...subjects].filter(Boolean);
});

const teacherSchedule = ref([
  // Data dummy jadwal — idealnya dari API admin/teacher-schedule endpoint
  { day: 'Senin', subject: 'Penalaran Matematika', time: '14.00–16.00', room: 'Kelas A' },
  { day: 'Rabu', subject: 'Literasi Bahasa Indonesia', time: '13.00–15.00', room: 'Kelas B' },
  { day: 'Jumat', subject: 'Tes Potensi Skolastik', time: '09.00–11.00', room: 'Online' },
]);

// ── Watch activeTab to load data ──
watch(activeTab, (newTab) => {
  if (isAffiliate.value && !['affiliate_dashboard', 'affiliate_referrals', 'affiliate_payouts', 'settings'].includes(newTab)) {
    activeTab.value = 'affiliate_dashboard';
    return;
  }
  if (isTeacher.value && ['packages', 'plans', 'transactions', 'affiliates', 'staff', 'students', 'settings'].includes(newTab)) {
    activeTab.value = 'questions';
    return;
  }
  sessionStorage.setItem('admin_active_tab', newTab);
  if (newTab === 'overview' && !isAffiliate.value && !isTeacher.value) {
    fetchDashboard();
  } else if (newTab === 'students' && !isTeacher.value && !isAffiliate.value) {
    fetchStudents();
  } else if (newTab === 'questions' && !isAffiliate.value) {
    fetchQuestions();
  } else if (newTab === 'materials' && !isAffiliate.value) {
    fetchMaterials();
  } else if (newTab === 'transactions' && !isTeacher.value && !isAffiliate.value) {
    fetchAdminOrders();
  } else if ((newTab === 'packages' || newTab === 'plans' || newTab === 'staff') && !isTeacher.value && !isAffiliate.value) {
    loadPlansAndStaff();
  } else if (newTab === 'affiliates' && !isAffiliate.value) {
    loadAdminAffiliates();
    loadAdminCommissions();
    loadAdminPayouts();
  } else if (isAffiliate.value && ['affiliate_dashboard', 'affiliate_referrals', 'affiliate_payouts'].includes(newTab)) {
    loadMyAffiliateData();
  } else if (newTab === 'settings' && isAffiliate.value) {
    loadAffiliateSettings();
  }
}, { immediate: true });
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.35s ease both; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
/* Fallback: tampilkan emoji hanya jika Phosphor Icons gagal dimuat */
.ph-bold:not(:empty) + .ph-fallback { display: none; }
.ph-bold[class*="ph-eye"]::before { content: ""; }
.ph-fallback { font-size: 16px; line-height: 1; }
</style>
