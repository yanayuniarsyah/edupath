<template>
  <!-- ===== LOGIN GATE ===== -->
  <div v-if="!isAuthenticated" class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #050a18 0%, #0d1224 60%, #050a18 100%);">
    <!-- Glow decorations -->
    <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #c0ff00 0%, transparent 70%);"></div>
    <div class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full opacity-15 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #6366f1 0%, transparent 70%);"></div>

    <div class="relative z-10 w-full max-w-sm mx-4">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-black text-black text-2xl mx-auto mb-4 shadow-lg" style="background: #c0ff00;">E</div>
        <h1 class="text-2xl font-black text-white tracking-tight">EduPath<span style="color:#c0ff00;">.ai</span></h1>
        <p class="text-white/40 text-xs font-semibold mt-1 uppercase tracking-widest">Admin Panel</p>
      </div>

      <!-- Login Card -->
      <div class="rounded-3xl p-8 border" style="background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); backdrop-filter: blur(20px);">
        <h2 class="text-white font-black text-lg mb-6">Masuk sebagai Admin</h2>

        <!-- Error Message -->
        <div v-if="loginError" class="mb-4 px-4 py-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs font-bold flex items-center gap-2">
          <i class="ph-bold ph-warning-circle"></i>
          {{ loginError }}
        </div>

        <form @submit.prevent="doLogin" class="space-y-4">
          <div>
            <label class="block text-white/60 text-xs font-bold mb-1.5 uppercase tracking-wider">Username</label>
            <div class="relative">
              <i class="ph-bold ph-user absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
              <input
                v-model="loginForm.username"
                type="text"
                placeholder="admin"
                autocomplete="username"
                class="w-full pl-9 pr-4 py-3 rounded-xl text-sm font-medium text-white outline-none transition-all"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
                :style="loginError ? 'border-color: rgba(239,68,68,0.5);' : ''"
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
                placeholder="••••••••"
                autocomplete="current-password"
                class="w-full pl-9 pr-10 py-3 rounded-xl text-sm font-medium text-white outline-none transition-all"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
                :style="loginError ? 'border-color: rgba(239,68,68,0.5);' : ''"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                <i :class="['ph-bold text-sm', showPassword ? 'ph-eye-slash' : 'ph-eye']"></i>
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
          <p class="text-white/30 text-[11px] font-medium">Akses terbatas. Hanya untuk administrator.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== MAIN ADMIN DASHBOARD ===== -->
  <div v-else class="flex h-screen bg-[#070b14] font-body overflow-hidden text-white">

    <!-- ===== SIDEBAR ===== -->
    <aside
      class="flex flex-col h-full shrink-0 transition-all duration-300 ease-out overflow-hidden border-r border-white/10 shadow-2xl"
      :class="sidebarOpen ? 'w-52' : 'w-[56px]'"
      style="background: linear-gradient(160deg, #050a18 0%, #0d1224 60%, #050a18 100%);"
    >
      <!-- Logo -->
      <div class="flex items-center gap-2.5 px-3 py-3 border-b border-white/10 shrink-0 cursor-pointer select-none" @click="sidebarOpen = !sidebarOpen">
        <div class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center font-black text-black text-base transition-all duration-300" style="background: #c0ff00;">E</div>
        <div v-show="sidebarOpen" class="min-w-0">
          <span class="font-black text-sm tracking-tight text-white whitespace-nowrap">EduPath<span style="color:#c0ff00;">.ai</span></span>
          <p class="text-[9px] text-white/40 font-semibold uppercase tracking-widest whitespace-nowrap">Admin Panel</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex flex-col gap-0.5 p-2 flex-grow overflow-y-auto">
        <button
          v-for="item in navItems" :key="item.id"
          @click="activeTab = item.id"
          :title="item.label"
          :class="['flex items-center rounded-lg text-[11px] font-bold transition-all text-left group relative',
            sidebarOpen ? 'gap-2.5 px-2.5 py-2' : 'justify-center px-0 py-2',
            activeTab === item.id
              ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00]'
              : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
          ]"
        >
          <i :class="['ph-bold shrink-0 text-base', item.icon]"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">{{ item.label }}</span>
          <span v-if="item.badge && sidebarOpen" class="ml-auto bg-rose-500 text-white text-[8px] font-black px-1 py-0.5 rounded-full">{{ item.badge }}</span>
        </button>
      </nav>

      <!-- Footer -->
      <div class="shrink-0 border-t border-white/10 p-2 space-y-1.5">
        <div class="flex items-center gap-2" :class="sidebarOpen ? '' : 'justify-center'">
          <div class="w-7 h-7 shrink-0 rounded-md bg-[#c0ff00]/20 flex items-center justify-center font-bold text-[#c0ff00] text-[10px] border border-[#c0ff00]/30">AD</div>
          <div v-show="sidebarOpen" class="flex-grow min-w-0">
            <h4 class="text-[11px] font-black text-white whitespace-nowrap">Admin</h4>
            <span class="text-[9px] text-[#c0ff00] font-bold uppercase tracking-wider">Super Admin</span>
          </div>
        </div>
        <button
          @click="goToStudentSide"
          :title="'Lihat Tampilan Siswa'"
          :class="['bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 hover:text-white rounded-lg font-bold transition-all flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 text-[11px]',
            sidebarOpen ? 'w-full py-1.5' : 'w-8 h-8 text-xs'
          ]"
        >
          <i class="ph-bold ph-arrow-square-out shrink-0"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">Tampilan Siswa</span>
        </button>
        <button
          @click="doLogout"
          :title="'Keluar'"
          :class="['bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-400 rounded-lg font-bold transition-all flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 text-[11px]',
            sidebarOpen ? 'w-full py-1.5' : 'w-8 h-8 text-xs'
          ]"
        >
          <i class="ph-bold ph-sign-out shrink-0"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">Keluar</span>
        </button>
      </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex flex-col flex-grow min-w-0 overflow-hidden">

      <!-- Top Bar -->
      <header class="flex items-center justify-between px-4 py-2 bg-[#0b1329]/90 backdrop-blur-md border-b border-white/10 shrink-0">
        <div class="flex items-center gap-2">
          <button @click="sidebarOpen = !sidebarOpen" class="w-7 h-7 rounded-md bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-colors text-white/70">
            <i class="ph-bold ph-list text-sm"></i>
          </button>
          <h1 class="text-xs font-black text-white">{{ currentNavItem?.label }}</h1>
        </div>
        <div class="flex items-center gap-2">
          <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Aktif
          </div>
          <div class="text-[10px] text-white/40 font-semibold">{{ new Date().toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) }}</div>
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

        <!-- ===== TAB: TRANSAKSI ===== -->
        <div v-if="activeTab === 'transactions'" class="space-y-4 animate-fade-in">
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="px-3.5 py-2 border-b border-white/10 flex items-center justify-between bg-black/20">
              <h3 class="font-bold text-white text-xs">Riwayat Pembayaran Midtrans</h3>
              <button @click="fetchAdminOrders" class="text-[10px] text-[#c0ff00] font-bold hover:underline">
                <i class="ph-bold ph-arrows-clockwise mr-1"></i> Refresh
              </button>
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Order ID</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Siswa</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Paket</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Nominal</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Tanggal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="o in adminOrders" :key="o.order_id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 font-mono text-white/60">{{ o.order_id }}</td>
                    <td class="px-3 py-2">
                      <div class="font-bold text-white">{{ o.student_name }}</div>
                      <div class="text-white/40 font-medium text-[10px]">{{ o.student_email }}</div>
                    </td>
                    <td class="px-3 py-2 font-bold text-indigo-400 capitalize">{{ o.plan_name }}</td>
                    <td class="px-3 py-2 font-mono text-emerald-400 font-bold">Rp {{ o.amount.toLocaleString('id-ID') }}</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px] uppercase border"
                            :class="o.status === 'paid' || o.status === 'settlement' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400'">
                        {{ o.status }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-white/40">{{ new Date(o.created_at).toLocaleString('id-ID') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN SISWA ===== -->
        <div v-if="activeTab === 'students'" class="space-y-4 animate-fade-in">
          <!-- Search + Filter Bar -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl p-2.5 border border-white/10 flex flex-wrap items-center gap-2">
            <div class="relative flex-grow min-w-[180px]">
              <i class="ph-bold ph-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-white/30 text-xs"></i>
              <input v-model="studentSearch" type="text" placeholder="Cari nama atau email siswa..." class="w-full pl-8 pr-3 py-1.5 text-[11px] font-medium text-white placeholder-white/30 bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-colors" />
            </div>
            <select v-model="studentPlanFilter" class="text-[11px] font-bold text-white bg-black/40 border border-white/10 rounded-lg px-2.5 py-1.5 outline-none cursor-pointer focus:border-[#c0ff00]/50">
              <option value="all" class="bg-[#0d1427]">Semua Paket</option>
              <option value="free" class="bg-[#0d1427]">Free</option>
              <option value="mandiri" class="bg-[#0d1427]">Mandiri</option>
              <option value="utama" class="bg-[#0d1427]">Utama</option>
              <option value="vip" class="bg-[#0d1427]">VIP</option>
            </select>
            <button @click="showStudentModal = true" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Siswa
            </button>
          </div>

          <!-- Students Table -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Siswa</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Paket</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="s in filteredStudents" :key="s.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2">
                      <div class="font-bold text-white">{{ s.name }}</div>
                      <div class="text-white/40 font-medium text-[10px]">{{ s.email }}</div>
                    </td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px] bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 uppercase">{{ s.plan || 'free' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px] bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">{{ s.is_active !== false ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: BANK SOAL ===== -->
        <div v-if="activeTab === 'questions'" class="space-y-4 animate-fade-in">
          <div class="flex items-center justify-between">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Bank Soal</h2>
            <div class="flex items-center gap-2">
              <button @click="showImportModal = true" class="px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold rounded-lg hover:bg-emerald-500/20 text-[11px] transition-colors">
                <i class="ph-bold ph-file-csv mr-1"></i> Import CSV
              </button>
              <button @click="openQuestionModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors">
                + Tambah Soal
              </button>
            </div>
          </div>

          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="p-2.5 border-b border-white/10 flex gap-2 bg-black/20">
              <input v-model="qSearch" type="text" placeholder="Cari soal..." class="flex-grow px-3 py-1.5 text-[11px] text-white placeholder-white/30 bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50" />
              <select v-model="qSubMateri" class="px-2.5 py-1.5 text-[11px] font-bold text-white bg-black/40 border border-white/10 rounded-lg outline-none cursor-pointer">
                <option value="all" class="bg-[#0d1427]">Semua Subtes</option>
                <option value="Penalaran Umum" class="bg-[#0d1427]">Penalaran Umum</option>
                <option value="Penalaran Matematika" class="bg-[#0d1427]">Penalaran Matematika</option>
                <option value="Literasi B. Indonesia" class="bg-[#0d1427]">Literasi B. Indonesia</option>
                <option value="Literasi B. Inggris" class="bg-[#0d1427]">Literasi B. Inggris</option>
              </select>
            </div>
            
            <div v-if="questionsLoading" class="p-6 text-center text-white/40">
              <i class="ph-bold ph-spinner animate-spin text-xl mb-1"></i>
              <p class="text-[10px]">Memuat bank soal...</p>
            </div>
            <div v-else-if="filteredQuestions.length === 0" class="p-6 text-center text-white/40">
              <p class="text-[10px]">Tidak ada soal yang ditemukan.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider w-1/2">Soal</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Sub Materi</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Kategori & Level</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="q in filteredQuestions" :key="q.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2">
                      <div class="line-clamp-2 text-white/80">{{ q.question }}</div>
                    </td>
                    <td class="px-3 py-2 font-bold text-white">{{ q.sub_materi }}</td>
                    <td class="px-3 py-2">
                      <span v-if="q.is_qc_passed == 1" class="px-2 py-0.5 text-[9px] font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-md">Lolos QC</span>
                      <span v-else class="px-2 py-0.5 text-[9px] font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-md">Belum QC</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex flex-col gap-0.5 items-start">
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 uppercase">{{ q.usage_type || 'Latihan' }}</span>
                        <span class="text-[9px] text-white/40 font-bold">{{ q.cognitive_level || 'C3' }}</span>
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px]" :class="q.is_active !== false ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/30 text-rose-400'">
                        {{ q.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openQuestionModal(q)" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deleteQuestion(q.id)" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN MATERI ===== -->
        <div v-if="activeTab === 'materials'" class="space-y-4 animate-fade-in">
          <div class="flex items-center justify-between">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Materi</h2>
            <button @click="openMaterialModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors">
              + Tambah Materi
            </button>
          </div>

          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="p-2.5 border-b border-white/10 flex gap-2 bg-black/20">
              <input v-model="mSearch" type="text" placeholder="Cari materi..." class="flex-grow px-3 py-1.5 text-[11px] text-white placeholder-white/30 bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50" />
            </div>
            
            <div v-if="materialsLoading" class="p-6 text-center text-white/40">
              <i class="ph-bold ph-spinner animate-spin text-xl mb-1"></i>
              <p class="text-[10px]">Memuat materi...</p>
            </div>
            <div v-else-if="filteredMaterials.length === 0" class="p-6 text-center text-white/40">
              <p class="text-[10px]">Tidak ada materi yang ditemukan.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Judul Materi</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Sub Materi</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Guru/PJ</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="m in filteredMaterials" :key="m.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 font-bold text-white">{{ m.title }}</td>
                    <td class="px-3 py-2 font-bold text-white/80">{{ m.sub_materi }}</td>
                    <td class="px-3 py-2 font-bold text-white/40 text-[10px]">{{ m.teacher_name || '-' }}</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px]" :class="m.is_active !== false ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/30 text-rose-400'">
                        {{ m.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openMaterialModal(m)" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deleteMaterial(m.id)" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Paket -->
        <div v-if="activeTab === 'packages'" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Paket</h2>
            <button @click="openPlanModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Paket
            </button>
          </div>
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Nama Paket</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Harga</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Durasi (Hari)</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="p in plans" :key="p.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 font-bold text-white">{{ p.name }}</td>
                    <td class="px-3 py-2 font-bold text-emerald-400 font-mono">Rp {{ p.price.toLocaleString('id-ID') }}</td>
                    <td class="px-3 py-2 font-bold text-white/80">{{ p.duration }} Hari</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px]" :class="p.is_active !== false ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/30 text-rose-400'">
                        {{ p.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openPlanModal(p)" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deletePlan(p.id)" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Afiliasi -->
        <div v-if="activeTab === 'affiliates'" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">Afiliasi & Komisi</h2>
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 text-white/50 border-b border-white/10">
                    <th class="py-2 px-3 text-left font-bold">Mitra</th>
                    <th class="py-2 px-3 text-left font-bold">Rekening Bank</th>
                    <th class="py-2 px-3 text-left font-bold">Nominal (Rp)</th>
                    <th class="py-2 px-3 text-left font-bold">Status</th>
                    <th class="py-2 px-3 text-right font-bold">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingPayouts">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-[10px]">Memuat data payout...</td>
                  </tr>
                  <tr v-else-if="payoutsList.length === 0">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-[10px]">Belum ada permintaan pencairan</td>
                  </tr>
                  <tr v-for="pay in payoutsList" :key="pay.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-2.5 px-3">
                      <div class="font-bold text-white">{{ pay.affiliate_name }}</div>
                      <div class="font-mono text-[9px] text-indigo-400 font-bold">{{ pay.referral_code }}</div>
                    </td>
                    <td class="py-2.5 px-3">
                      <div class="font-bold text-white/90">{{ pay.bank_name }} - {{ pay.bank_account }}</div>
                      <div class="text-[9px] text-white/40">A.n {{ pay.bank_owner }}</div>
                    </td>
                    <td class="py-2.5 px-3 font-mono font-bold text-emerald-400">
                      {{ formatCurrency(pay.amount) }}
                    </td>
                    <td class="py-2.5 px-3">
                      <span :class="['px-2 py-0.5 rounded-md text-[9px] font-bold uppercase border', pay.status === 'paid' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400']">
                        {{ pay.status }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3 text-right">
                      <button v-if="pay.status === 'pending'" @click="approvePayoutReq(pay.id)" class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/30 font-bold rounded-md transition-colors flex items-center justify-center gap-1 ml-auto w-28 text-[10px]">
                        <i class="ph-bold ph-check-circle"></i> Tandai Ditransfer
                      </button>
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 text-white/50 border-b border-white/10">
                    <th class="py-2 px-3 text-left font-bold w-1/3">Mitra (Email)</th>
                    <th class="py-2 px-3 text-left font-bold">Kode Referral</th>
                    <th class="py-2 px-3 text-left font-bold">Komisi Default</th>
                    <th class="py-2 px-3 text-left font-bold">Terdaftar Pada</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingAffiliates">
                    <td colspan="4" class="py-6 text-center text-white/40 font-medium text-[10px]">Memuat data mitra...</td>
                  </tr>
                  <tr v-else-if="affiliatesList.length === 0">
                    <td colspan="4" class="py-6 text-center text-white/40 font-medium text-[10px]">Belum ada mitra afiliasi</td>
                  </tr>
                  <tr v-for="aff in affiliatesList" :key="aff.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-2.5 px-3 font-semibold text-white/90">{{ aff.identity_key }}</td>
                    <td class="py-2.5 px-3 font-mono text-indigo-400 font-bold">{{ aff.referral_code }}</td>
                    <td class="py-2.5 px-3 text-amber-400 font-bold">{{ aff.commission_rate }}%</td>
                    <td class="py-2.5 px-3 text-white/40">{{ new Date(aff.created_at).toLocaleDateString('id-ID') }}</td>
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 text-white/50 border-b border-white/10">
                    <th class="py-2 px-3 text-left font-bold w-1/4">Siswa (Paket)</th>
                    <th class="py-2 px-3 text-left font-bold w-1/4">Mitra (Kode)</th>
                    <th class="py-2 px-3 text-left font-bold">Nominal (Rp)</th>
                    <th class="py-2 px-3 text-left font-bold">Status</th>
                    <th class="py-2 px-3 text-right font-bold w-28">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingCommissions">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-[10px]">Memuat data komisi...</td>
                  </tr>
                  <tr v-else-if="commissionsList.length === 0">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-[10px]">Belum ada data komisi</td>
                  </tr>
                  <tr v-for="comm in commissionsList" :key="comm.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-2.5 px-3">
                      <div class="font-bold text-white">{{ comm.student_name }}</div>
                      <div class="text-[9px] text-white/40">{{ comm.order_plan_name }}</div>
                    </td>
                    <td class="py-2.5 px-3">
                      <div class="font-bold text-white/90">{{ comm.affiliate_email }}</div>
                      <div class="font-mono text-[9px] text-indigo-400 font-bold">{{ comm.referral_code }}</div>
                    </td>
                    <td class="py-2.5 px-3 font-mono font-bold text-emerald-400">
                      {{ formatCurrency(comm.amount) }}
                    </td>
                    <td class="py-2.5 px-3">
                      <span :class="['px-2 py-0.5 rounded-md text-[9px] font-bold uppercase border', comm.status === 'paid' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400']">
                        {{ comm.status }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3 text-right">
                      <button v-if="comm.status === 'pending'" @click="openPayoutModal(comm)" class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/30 font-bold rounded-md transition-colors flex items-center justify-center gap-1 w-full text-[10px]">
                        <i class="ph-bold ph-check-circle"></i> Bayar
                      </button>
                      <div v-else class="text-[9px] text-white/40 text-center flex flex-col items-center">
                        <i class="ph-bold ph-check-circle text-emerald-400 mb-0.5 text-xs"></i>
                        Telah Dibayar
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Staff -->
        <div v-if="activeTab === 'staff'" class="space-y-4 animate-fade-in">
          <div class="flex justify-between items-center">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Pengguna Internal</h2>
            <button @click="openStaffModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1.5">
              <i class="ph-bold ph-plus"></i> Tambah Staff
            </button>
          </div>
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Username</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Nama</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Peran (Role)</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="s in staffMembers" :key="s.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 font-bold text-white">{{ s.username }}</td>
                    <td class="px-3 py-2 font-bold text-white/80">{{ s.name || '-' }}</td>
                    <td class="px-3 py-2 font-bold text-indigo-400 uppercase">{{ s.role || 'admin' }}</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px]" :class="s.is_active !== false ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/30 text-rose-400'">
                        {{ s.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openStaffModal(s)" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button v-if="s.username !== 'admin'" @click="deleteStaff(s.id)" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </main>
    </div>

    <!-- Modals -->
    
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
            <label class="block text-white/50 text-[10px] font-bold mb-1 uppercase tracking-wider">Referensi Pembayaran (Opsional)</label>
            <input v-model="payoutReference" type="text" placeholder="Misal: TRX-BCA-123" class="w-full px-3 py-1.5 rounded-lg border border-white/10 bg-black/40 focus:border-[#c0ff00]/50 outline-none text-xs font-medium text-white placeholder-white/20 transition-colors" />
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

    <!-- Student Modal -->
    <div v-if="showStudentModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">Tambah Siswa Baru</h3>
          <button @click="showStudentModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveStudent" class="p-4 space-y-3">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Lengkap</label>
            <input v-model="studentForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email</label>
            <input v-model="studentForm.email" required type="email" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password</label>
            <input v-model="studentForm.password" required type="password" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Paket</label>
            <select v-model="studentForm.plan" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="free" class="bg-[#0d1427] text-white">Free</option>
              <option value="mandiri" class="bg-[#0d1427] text-white">Mandiri</option>
              <option value="utama" class="bg-[#0d1427] text-white">Utama</option>
              <option value="vip" class="bg-[#0d1427] text-white">VIP</option>
            </select>
          </div>
          <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
            <button type="button" @click="showStudentModal = false" class="px-3 py-1.5 text-xs font-bold text-white/60 hover:text-white rounded-lg transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black rounded-lg transition-all shadow-md shadow-[#c0ff00]/20 disabled:opacity-50">
              Simpan
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
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Paket</label>
            <input v-model="pForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Harga Asli</label>
              <input v-model="pForm.price" required type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Diskon</label>
              <input v-model="pForm.discount" type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Durasi (Hr)</label>
              <input v-model="pForm.duration" required type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1.5 uppercase tracking-wider">Entitlement (Fitur Akses)</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <label v-for="ent in entitlementsDict" :key="ent.key" class="flex items-center gap-2 p-2 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5 transition-colors text-xs" :class="{'bg-[#c0ff00]/10 border-[#c0ff00]/40 text-white': pForm.features.includes(ent.key)}">
                <input type="checkbox" :value="ent.key" v-model="pForm.features" class="w-3.5 h-3.5 text-[#c0ff00] rounded border-white/20 bg-black/40 focus:ring-[#c0ff00]" />
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
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Username</label>
            <input v-model="sForm.username" :disabled="isEditingStaff && sForm.username === 'admin'" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium disabled:opacity-50" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Lengkap</label>
            <input v-model="sForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Peran (Role)</label>
            <select v-model="sForm.role" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" :disabled="sForm.username === 'admin'">
              <option value="teacher" class="bg-[#0d1427] text-white">Guru / Tutor</option>
              <option value="admin" class="bg-[#0d1427] text-white">Administrator Utama</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password {{ isEditingStaff ? '(Kosongkan jika tidak ubah)' : '' }}</label>
            <input v-model="sForm.password" :required="!isEditingStaff" type="password" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
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
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Sub Materi</label>
              <select v-model="qForm.sub_materi" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="Penalaran Umum" class="bg-[#0d1427] text-white">Penalaran Umum</option>
                <option value="Penalaran Matematika" class="bg-[#0d1427] text-white">Penalaran Matematika</option>
                <option value="Literasi B. Indonesia" class="bg-[#0d1427] text-white">Literasi B. Indonesia</option>
                <option value="Literasi B. Inggris" class="bg-[#0d1427] text-white">Literasi B. Inggris</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tingkat Kesulitan</label>
              <select v-model="qForm.difficulty" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="easy" class="bg-[#0d1427] text-white">Mudah</option>
                <option value="medium" class="bg-[#0d1427] text-white">Sedang</option>
                <option value="hard" class="bg-[#0d1427] text-white">Sulit</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Kategori Penggunaan</label>
              <select v-model="qForm.usage_type" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="latihan" class="bg-[#0d1427] text-white">Latihan Harian</option>
                <option value="tryout" class="bg-[#0d1427] text-white">Tryout Resmi</option>
                <option value="diagnostik" class="bg-[#0d1427] text-white">Asesmen Diagnostik</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Level Kognitif</label>
              <select v-model="qForm.cognitive_demand" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="C1" class="bg-[#0d1427] text-white">C1 - Mengingat</option>
                <option value="C2" class="bg-[#0d1427] text-white">C2 - Memahami</option>
                <option value="C3" class="bg-[#0d1427] text-white">C3 - Aplikasi</option>
                <option value="C4" class="bg-[#0d1427] text-white">C4 - Analisis</option>
                <option value="C5" class="bg-[#0d1427] text-white">C5 - Evaluasi</option>
                <option value="C6" class="bg-[#0d1427] text-white">C6 - Mencipta</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Sumber Soal</label>
              <select v-model="qForm.source_type" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="author_created" class="bg-[#0d1427] text-white">Dibuat Sendiri (Author)</option>
                <option value="official_source" class="bg-[#0d1427] text-white">Sumber Resmi</option>
                <option value="licensed" class="bg-[#0d1427] text-white">Lisensi Pihak Ketiga</option>
                <option value="adapted" class="bg-[#0d1427] text-white">Diadaptasi / Dimodifikasi</option>
                <option value="unknown" class="bg-[#0d1427] text-white">Tidak Diketahui</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Hak Cipta</label>
              <select v-model="qForm.rights_status" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="unknown" class="bg-[#0d1427] text-white">Belum Di-review (Unknown)</option>
                <option value="owned" class="bg-[#0d1427] text-white">Milik Sendiri</option>
                <option value="fair_use" class="bg-[#0d1427] text-white">Fair Use (Edukasi)</option>
                <option value="licensed" class="bg-[#0d1427] text-white">Berlisensi Sah</option>
                <option value="restricted" class="bg-[#0d1427] text-white">Restricted / Tidak Boleh Dipublish</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Sumber (Opsional)</label>
              <input v-model="qForm.source_name" type="text" placeholder="Misal: UTBK 2023" class="w-full p-2 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 placeholder-white/20 transition-all font-medium" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tahun (Opsional)</label>
              <input v-model="qForm.source_year" type="number" placeholder="2023" class="w-full p-2 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 placeholder-white/20 transition-all font-medium" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Referensi URL/Buku</label>
              <input v-model="qForm.source_reference" type="text" placeholder="URL atau hal." class="w-full p-2 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 placeholder-white/20 transition-all font-medium" />
            </div>
          </div>
          <div class="flex items-center gap-2 pt-1">
            <input type="checkbox" id="qc_passed" v-model="qForm.is_qc_passed" :true-value="1" :false-value="0" class="w-3.5 h-3.5 text-[#c0ff00] bg-black/40 border-white/20 rounded focus:ring-[#c0ff00]">
            <label for="qc_passed" class="text-xs font-bold text-white/80 cursor-pointer">Telah Melewati Proses QC (Guru)</label>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pertanyaan</label>
            <textarea v-model="qForm.question" required rows="2" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium"></textarea>
          </div>
          <div class="space-y-2">
            <label class="block text-[10px] font-bold text-white/50 uppercase tracking-wider">Opsi Jawaban</label>
            <div v-for="opt in ['a','b','c','d','e']" :key="opt" class="flex items-center gap-1.5">
              <input type="radio" v-model="qForm.correct" :value="opt" name="correctOpt" required class="w-3.5 h-3.5 text-[#c0ff00]" />
              <span class="text-xs font-bold uppercase w-5 text-white/80">{{ opt }}.</span>
              <input v-model="qForm['option_' + opt]" :required="opt !== 'e'" type="text" placeholder="..." class="flex-grow p-2 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 placeholder-white/20 transition-all font-medium" />
            </div>
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
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Judul Materi</label>
            <input v-model="mForm.title" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Sub Materi</label>
            <select v-model="mForm.sub_materi" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="Penalaran Umum" class="bg-[#0d1427] text-white">Penalaran Umum</option>
              <option value="Penalaran Matematika" class="bg-[#0d1427] text-white">Penalaran Matematika</option>
              <option value="Literasi B. Indonesia" class="bg-[#0d1427] text-white">Literasi B. Indonesia</option>
              <option value="Literasi B. Inggris" class="bg-[#0d1427] text-white">Literasi B. Inggris</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Guru / Penanggung Jawab</label>
            <select v-model="mForm.teacher_name" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="" class="bg-[#0d1427] text-white">-- Pilih Guru / Tidak Ada --</option>
              <option v-for="s in staffMembers" :key="s.id" :value="s.name" class="bg-[#0d1427] text-white">{{ s.name }} ({{ s.role }})</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Isi / Konten Materi</label>
            <textarea v-model="mForm.content" required rows="5" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium"></textarea>
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
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api';

const router = useRouter()

// ── Data ──
const sidebarOpen = ref(true);
const activeTab = ref('overview');
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

// ── Current Nav Item ──
const currentNavItem = computed(() => navItems.find(item => item.id === activeTab.value));

// ── Format Currency ──
const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
};

// ── Auth ──
const isAuthenticated = ref(sessionStorage.getItem('admin_token') !== null);
const loginForm = ref({ username: '', password: '' });
const loginError = ref('');
const loginLoading = ref(false);
const showPassword = ref(false);

const doLogin = async () => {
  loginError.value = '';
  loginLoading.value = true;
  try {
    const res = await api.adminLogin(loginForm.value.username, loginForm.value.password);
    sessionStorage.setItem('admin_token', res.token);
    if (res.csrf_token) sessionStorage.setItem('ep_admin_csrf', res.csrf_token);
    isAuthenticated.value = true;
    // Fetch dashboard data after successful login
    fetchDashboard();
    fetchStudents();
    if (activeTab.value === 'transactions') fetchAdminOrders();
  } catch (err) {
    loginError.value = err.message || 'Login gagal';
  } finally {
    loginLoading.value = false;
  }
};

const doLogout = () => {
  api.clearAdminTokens();
  sessionStorage.removeItem('admin_token');
  isAuthenticated.value = false;
  location.reload();
}

// ── Dashboard Stats ──
const fetchDashboard = async () => {
  try {
    const res = await api.getAdminDashboard();
    if (res.stats) {
      Object.assign(stats, res.stats);
    }
  } catch (err) {
    console.error("Gagal mengambil dashboard:", err);
  }
};

// ── Students ──
const allStudents = ref([]);

const fetchStudents = async () => {
  try {
    const res = await api.getAdminStudents(1, '', '');
    allStudents.value = Array.isArray(res) ? res : (res.students || []);
  } catch (err) {
    console.error("Gagal mengambil data siswa:", err);
  }
};

const filteredStudents = computed(() => {
  return allStudents.value.filter(s => {
    const matchSearch = !studentSearch.value ||
      (s.name || '').toLowerCase().includes(studentSearch.value.toLowerCase()) ||
      (s.email || '').toLowerCase().includes(studentSearch.value.toLowerCase());
    const matchPlan = studentPlanFilter.value === 'all' || s.plan === studentPlanFilter.value;
    return matchSearch && matchPlan;
  });
});

// ── Admin Orders ──
const adminOrders = ref([]);
const ordersLoading = ref(false);

const fetchAdminOrders = async () => {
  ordersLoading.value = true;
  try {
    const res = await api.getAdminOrders();
    adminOrders.value = res.orders || [];
  } catch (err) {
    console.error("Gagal mengambil transaksi:", err);
  } finally {
    ordersLoading.value = false;
  }
};

watch(activeTab, (newTab) => {
  if (newTab === 'transactions') {
    fetchAdminOrders();
  }
  if (newTab === 'students') {
    fetchStudents();
  }
  if (newTab === 'questions') {
    fetchQuestions();
  }
  if (newTab === 'materials') {
    fetchMaterials();
  }
});

const navItems = [
  { id: 'overview',     label: 'Overview',            icon: 'ph-squares-four',  badge: null },
  { id: 'transactions', label: 'Riwayat Transaksi',   icon: 'ph-receipt',       badge: null },
  { id: 'students',     label: 'Manajemen Siswa',     icon: 'ph-users-three',   badge: null },
  { id: 'questions',    label: 'Bank Soal',           icon: 'ph-books',         badge: null },
  { id: 'materials',    label: 'Manajemen Materi',    icon: 'ph-file-text',     badge: null },
  { id: 'packages',     label: 'Manajemen Paket',     icon: 'ph-package',       badge: null },
  { id: 'affiliates',   label: 'Afiliasi & Komisi',   icon: 'ph-hand-coins',    badge: null },
  { id: 'staff',        label: 'Manajemen Staff',     icon: 'ph-users-three',   badge: null },
  { id: 'reports',      label: 'Laporan & Analitik',  icon: 'ph-chart-bar',     badge: null },
];

const goToStudentSide = () => router.push('/');

// ── Fetch data on mount if already authenticated ──
onMounted(() => {
  if (isAuthenticated.value) {
    fetchDashboard();
    fetchStudents();
    loadPlansAndStaff();
  }
});

// ── Questions ──
const serverQuestions = ref([]);
const questionsLoading = ref(false);

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
  if (qSearch.value) {
    const s = qSearch.value.toLowerCase();
    list = list.filter(q => q.question && q.question.toLowerCase().includes(s));
  }
  return list;
});

const showQuestionModal = ref(false);

const showImportModal = ref(false);
const stagingData = ref([]);
const currentBatchId = ref('');
const hasErrors = computed(() => stagingData.value.some(r => r.status === 'error'));


const handleFileUpload = async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  
  try {
    const data = await file.arrayBuffer();
    const workbook = XLSX.read(data, { type: 'array' });
    const firstSheetName = workbook.SheetNames[0];
    const worksheet = workbook.Sheets[firstSheetName];
    const jsonData = XLSX.utils.sheet_to_json(worksheet);
    
    if (jsonData.length === 0) {
      alert("File kosong atau format salah.");
      return;
    }

    const res = await fetch('/api/importer.php?action=upload', {
      method: 'POST',
      headers: { 
        'Authorization': 'Bearer ' + localStorage.getItem('edupath_token'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(jsonData)
    }).then(r => r.json());
    
    if (res.error) throw new Error(res.error);
    
    currentBatchId.value = res.batch_id;
    stagingData.value = await fetch('/api/importer.php?action=preview&batch_id=' + res.batch_id, {
      headers: { 'Authorization': 'Bearer ' + localStorage.getItem('edupath_token') }
    }).then(r => r.json());
  } catch (err) {
    alert(err.message);
  }
};

const commitImport = async () => {
  try {
    const res = await fetch('/api/importer.php?action=commit', {
      method: 'POST',
      headers: { 
        'Authorization': 'Bearer ' + localStorage.getItem('edupath_token'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ batch_id: currentBatchId.value })
    }).then(r => r.json());
    
    if (res.error) throw new Error(res.error);
    alert('Berhasil mengimpor ' + res.inserted + ' soal!');
    showImportModal.value = false;
    stagingData.value = [];
    loadQuestions();
  } catch (err) {
    alert(err.message);
  }
};

const isEditingQuestion = ref(false);
const isSaving = ref(false);
const qForm = reactive({ id: null, sub_materi: 'Penalaran Umum', difficulty: 'medium', question: '', option_a: '', option_b: '', option_c: '', option_d: '', option_e: '', correct: 'a', usage_type: 'latihan', cognitive_demand: 'C3', source_type: 'author_created', rights_status: 'unknown', source_name: '', source_year: null, source_reference: '', is_qc_passed: 0 });

const openQuestionModal = (q = null) => {
  if (q) {
    isEditingQuestion.value = true;
    Object.assign(qForm, q);
  } else {
    isEditingQuestion.value = false;
    Object.assign(qForm, { id: null, sub_materi: 'Penalaran Umum', difficulty: 'medium', question: '', option_a: '', option_b: '', option_c: '', option_d: '', option_e: '', correct: 'a', usage_type: 'latihan', cognitive_demand: 'C3', source_type: 'author_created', rights_status: 'unknown', source_name: '', source_year: null, source_reference: '', is_qc_passed: 0 });
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
  } catch (err) {
    alert("Gagal menghapus soal");
  }
};

// ── Materials ──
const mSearch = ref('');
const serverMaterials = ref([]);
const materialsLoading = ref(false);

const fetchMaterials = async () => {
  materialsLoading.value = true;
  try {
    const res = await api.getAdminMaterials();
    serverMaterials.value = res.materials || [];
  } catch (err) {
    console.error("Gagal memuat materi", err);
  } finally {
    materialsLoading.value = false;
  }
};

const filteredMaterials = computed(() => {
  let list = serverMaterials.value;
  if (mSearch.value) {
    const s = mSearch.value.toLowerCase();
    list = list.filter(m => (m.title && m.title.toLowerCase().includes(s)) || (m.sub_materi && m.sub_materi.toLowerCase().includes(s)));
  }
  return list;
});

const showMaterialModal = ref(false);
const isEditingMaterial = ref(false);
const mForm = reactive({ id: null, title: '', content: '', sub_materi: 'Penalaran Umum', teacher_name: '' });

const openMaterialModal = (m = null) => {
  if (m) {
    isEditingMaterial.value = true;
    Object.assign(mForm, m);
  } else {
    isEditingMaterial.value = false;
    Object.assign(mForm, { id: null, title: '', content: '', sub_materi: 'Penalaran Umum', teacher_name: '' });
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

// ── Packages ──
const packages = [
  { name: 'Free Trial',   price: 'Rp 0',      color: '#94a3b8', active: true,  subscribers: 16,  features: ['Tes Potensi (SPP)', 'Kalkulator Peluang', 'Akses terbatas bank soal'] },
  { name: 'Mandiri',      price: 'Rp 100.000', color: '#6366f1', active: true,  subscribers: 62,  features: ['Semua fitur Free', 'Bank soal penuh', '2x simulasi IRT/bulan', 'Report mingguan'] },
  { name: 'Utama',        price: 'Rp 200.000', color: '#c0ff00', active: true,  subscribers: 89,  features: ['Semua fitur Mandiri', 'Unlimited simulasi IRT', 'AI Tutor 24/7', 'WA laporan orang tua'] },
  { name: 'VIP Mentoring',price: 'Rp 450.000', color: '#f59e0b', active: true,  subscribers: 24,  features: ['Semua fitur Utama', 'Sesi 1-on-1 live mentor', 'Jalur belajar super personal'] },
]

// ── Reports ──
const revenueBreakdown = [
  { label: 'Paket Utama',    amount: 'Rp 2.100.000', color: '#c0ff00' },
  { label: 'Paket Mandiri',  amount: 'Rp 1.200.000', color: '#6366f1' },
  { label: 'VIP Mentoring',  amount: 'Rp 900.000',   color: '#f59e0b' },
]

const topStudents = [
  { name: 'Rani Kusuma',  score: 742 },
  { name: 'Siti Rahayu',  score: 715 },
  { name: 'Fajar Nugraha',score: 698 },
  { name: 'Budi Santoso', score: 681 },
  { name: 'Dimas Pratama',score: 623 },
]

const exportOptions = [
  { label: 'Laporan Siswa (Excel)', icon: 'ph-microsoft-excel-logo' },
  { label: 'Rekap Soal (PDF)',      icon: 'ph-file-pdf'              },
  { label: 'Statistik Platform',    icon: 'ph-chart-pie'             },
  { label: 'Blast WhatsApp Orang Tua', icon: 'ph-whatsapp-logo'     },
]

// --- Scripts for Plans & Staff ---
const plans = ref([]);
const staffMembers = ref([]);
const entitlementsDict = ref([]);

// Modals State
// ── Affiliates & Commissions ──
const affiliatesList = ref([]);
const commissionsList = ref([]);
const payoutsList = ref([]);
const isLoadingAffiliates = ref(false);
const isLoadingCommissions = ref(false);
const isLoadingPayouts = ref(false);

const loadAdminAffiliates = async () => {
  isLoadingAffiliates.value = true;
  try {
    affiliatesList.value = await api.getAdminAffiliates();
  } catch (err) {
    alert('Gagal memuat daftar afiliasi');
  } finally {
    isLoadingAffiliates.value = false;
  }
};

const loadAdminCommissions = async () => {
  isLoadingCommissions.value = true;
  try {
    commissionsList.value = await api.getAdminCommissions();
  } catch (err) {
    alert('Gagal memuat daftar komisi');
  } finally {
    isLoadingCommissions.value = false;
  }
};

const loadAdminPayouts = async () => {
  isLoadingPayouts.value = true;
  try {
    payoutsList.value = await api.getAdminPayouts();
  } catch (err) {
    alert('Gagal memuat daftar payout');
  } finally {
    isLoadingPayouts.value = false;
  }
};

const approvePayoutReq = async (id) => {
  if (!confirm("Tandai payout ini sebagai 'Telah Ditransfer'?")) return;
  try {
    await api.approvePayout(id);
    loadAdminPayouts();
  } catch (err) {
    alert(err.message || 'Gagal memproses payout');
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

// ── Watch activeTab to load data ──
watch(activeTab, (newTab) => {
  if (newTab === 'affiliates') {
    loadAdminAffiliates();
    loadAdminCommissions();
    loadAdminPayouts();
  }
});

const showStudentModal = ref(false);
const studentForm = reactive({ name: '', email: '', password: '', plan: 'free' });

const showPlanModal = ref(false);
const isEditingPlan = ref(false);
const pForm = reactive({ id: null, name: '', price: '', discount: 0, duration: '', features: [] });

const showStaffModal = ref(false);
const isEditingStaff = ref(false);
const sForm = reactive({ id: null, username: '', name: '', role: 'teacher', password: '' });

const loadPlansAndStaff = async () => {
  try {
    const [pRes, sRes, eRes] = await Promise.all([
      api.getAdminPlans(),
      api.getAdminStaff(),
      api.getEntitlementsDictionary()
    ]);
    plans.value = pRes || [];
    // Ensure features is parsed from JSON if it comes as string from DB
    plans.value.forEach(p => {
      if (typeof p.features === 'string') {
        try { p.features = JSON.parse(p.features); } catch (e) { p.features = []; }
      }
      if (!Array.isArray(p.features)) p.features = [];
    });
    
    staffMembers.value = sRes.staff || [];
    entitlementsDict.value = eRes || [];
  } catch (err) {
    console.error(err);
  }
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

const openStaffModal = (s = null) => {
  if (s) {
    isEditingStaff.value = true;
    Object.assign(sForm, { ...s, password: '' });
  } else {
    isEditingStaff.value = false;
    Object.assign(sForm, { id: null, username: '', name: '', role: 'teacher', password: '' });
  }
  showStaffModal.value = true;
};

const closeStaffModal = () => showStaffModal.value = false;

const saveStudent = async () => {
  isSaving.value = true;
  try {
    await api.createAdminStudent(studentForm);
    await fetchStudents();
    showStudentModal.value = false;
    Object.assign(studentForm, { name: '', email: '', password: '', plan: 'free' });
  } catch (err) {
    alert(err.message || "Gagal menyimpan siswa");
  } finally {
    isSaving.value = false;
  }
};

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
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.35s ease both; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
