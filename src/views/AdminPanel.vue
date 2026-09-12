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
  <div v-else class="flex h-screen bg-[#f0f2f7] font-body overflow-hidden">

    <!-- ===== SIDEBAR ===== -->
    <aside
      class="flex flex-col h-full shrink-0 transition-all duration-300 ease-out overflow-hidden border-r border-white/5 shadow-2xl"
      :class="sidebarOpen ? 'w-64' : 'w-[68px]'"
      style="background: linear-gradient(160deg, #0a0f1e 0%, #0d1224 60%, #0a0f1e 100%);"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-5 border-b border-white/8 shrink-0 cursor-pointer select-none" @click="sidebarOpen = !sidebarOpen">
        <div class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center font-black text-black text-lg transition-all duration-300" style="background: #c0ff00;">E</div>
        <div v-show="sidebarOpen" class="min-w-0">
          <span class="font-black text-lg tracking-tight text-white whitespace-nowrap">EduPath<span style="color:#c0ff00;">.ai</span></span>
          <p class="text-[10px] text-white/40 font-semibold uppercase tracking-widest whitespace-nowrap">Admin Panel</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex flex-col gap-1 p-3 flex-grow overflow-y-auto">
        <button
          v-for="item in navItems" :key="item.id"
          @click="activeTab = item.id"
          :title="item.label"
          :class="['flex items-center rounded-xl text-xs font-bold transition-all text-left group relative',
            sidebarOpen ? 'gap-3 px-3.5 py-2.5' : 'justify-center px-0 py-2.5',
            activeTab === item.id
              ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00]'
              : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
          ]"
        >
          <i :class="['ph-bold shrink-0 text-lg', item.icon]"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">{{ item.label }}</span>
          <span v-if="item.badge && sidebarOpen" class="ml-auto bg-rose-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full">{{ item.badge }}</span>
        </button>
      </nav>

      <!-- Footer -->
      <div class="shrink-0 border-t border-white/10 p-3 space-y-2">
        <div class="flex items-center gap-3" :class="sidebarOpen ? '' : 'justify-center'">
          <div class="w-8 h-8 shrink-0 rounded-lg bg-[#c0ff00]/20 flex items-center justify-center font-bold text-[#c0ff00] text-xs border border-[#c0ff00]/30">AD</div>
          <div v-show="sidebarOpen" class="flex-grow min-w-0">
            <h4 class="text-xs font-black text-white whitespace-nowrap">Admin</h4>
            <span class="text-[10px] text-[#c0ff00] font-bold uppercase tracking-wider">Super Admin</span>
          </div>
        </div>
        <button
          @click="goToStudentSide"
          :title="'Lihat Tampilan Siswa'"
          :class="['bg-white/5 hover:bg-white/10 border border-white/10 text-white/70 hover:text-white rounded-xl font-bold transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 text-xs',
            sidebarOpen ? 'w-full py-2' : 'w-9 h-9 text-sm'
          ]"
        >
          <i class="ph-bold ph-arrow-square-out shrink-0"></i>
          <span v-show="sidebarOpen" class="whitespace-nowrap">Tampilan Siswa</span>
        </button>
        <button
          @click="doLogout"
          :title="'Keluar'"
          :class="['bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-400 rounded-xl font-bold transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 text-xs',
            sidebarOpen ? 'w-full py-2' : 'w-9 h-9 text-sm'
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
      <header class="flex items-center justify-between px-6 py-3.5 bg-white border-b border-slate-200 shrink-0 shadow-sm">
        <div class="flex items-center gap-3">
          <button @click="sidebarOpen = !sidebarOpen" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors text-slate-600">
            <i class="ph-bold ph-list text-base"></i>
          </button>
          <h1 class="text-sm font-black text-slate-900">{{ currentNavItem?.label }}</h1>
        </div>
        <div class="flex items-center gap-3">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Platform Aktif
          </div>
          <div class="text-xs text-slate-500 font-semibold">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }) }}</div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-grow overflow-y-auto p-6">

        <!-- ===== TAB: OVERVIEW ===== -->
        <div v-if="activeTab === 'overview'" class="space-y-6 animate-fade-in">
          <!-- Stats Row -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Siswa</span>
              </div>
              <div class="text-3xl font-black font-mono text-[#6366f1]">{{ stats.totalStudents }}</div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa Aktif</span>
              </div>
              <div class="text-3xl font-black font-mono text-[#10b981]">{{ stats.activeStudents }}</div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Soal</span>
              </div>
              <div class="text-3xl font-black font-mono text-[#f59e0b]">{{ stats.totalQuizzes }}</div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pendapatan</span>
              </div>
              <div class="text-3xl font-black font-mono text-[#c0ff00]">{{ formatCurrency(stats.totalRevenue) }}</div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: TRANSAKSI ===== -->
        <div v-if="activeTab === 'transactions'" class="space-y-6 animate-fade-in">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
              <h3 class="font-bold text-slate-800">Riwayat Pembayaran Midtrans</h3>
              <button @click="fetchAdminOrders" class="text-xs text-indigo-600 font-bold hover:underline">
                <i class="ph-bold ph-arrows-clockwise mr-1"></i> Refresh
              </button>
            </div>
            <div v-if="ordersLoading" class="p-8 text-center text-slate-400">
              <i class="ph-bold ph-spinner animate-spin text-2xl mb-2"></i>
              <p class="text-xs">Memuat data transaksi...</p>
            </div>
            <div v-else-if="adminOrders.length === 0" class="p-8 text-center text-slate-400">
              <i class="ph-bold ph-receipt text-3xl mb-2"></i>
              <p class="text-xs">Belum ada transaksi tercatat.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Order ID</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Siswa</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Paket</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Nominal</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Tanggal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="o in adminOrders" :key="o.order_id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3.5 font-mono text-slate-600">{{ o.order_id }}</td>
                    <td class="px-5 py-3.5">
                      <div class="font-bold text-slate-800">{{ o.student_name }}</div>
                      <div class="text-slate-400 font-medium">{{ o.student_email }}</div>
                    </td>
                    <td class="px-5 py-3.5 font-bold text-indigo-700 capitalize">{{ o.plan_name }}</td>
                    <td class="px-5 py-3.5 font-mono text-emerald-600 font-bold">Rp {{ o.amount.toLocaleString('id-ID') }}</td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px] uppercase"
                            :class="o.status === 'paid' || o.status === 'settlement' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                        {{ o.status }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-slate-500">{{ new Date(o.created_at).toLocaleString('id-ID') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN SISWA ===== -->
        <div v-if="activeTab === 'students'" class="space-y-6 animate-fade-in">
          <!-- Search + Filter Bar -->
          <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-wrap items-center gap-3">
            <div class="relative flex-grow min-w-[200px]">
              <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input v-model="studentSearch" type="text" placeholder="Cari nama atau email siswa..." class="w-full pl-9 pr-4 py-2 text-xs font-medium text-slate-800 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-indigo-400 focus:bg-white transition-colors" />
            </div>
            <select v-model="studentPlanFilter" class="text-xs font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none cursor-pointer focus:border-indigo-400">
              <option value="all">Semua Paket</option>
              <option value="free">Free</option>
              <option value="mandiri">Mandiri</option>
              <option value="utama">Utama</option>
              <option value="vip">VIP</option>
            </select>
            <button @click="showStudentModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center gap-2">
              <i class="ph-bold ph-plus"></i> Tambah Siswa
            </button>
          </div>

          <!-- Students Table -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Siswa</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Paket</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="s in filteredStudents" :key="s.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3.5">
                      <div class="font-bold text-slate-800">{{ s.name }}</div>
                      <div class="text-slate-400 font-medium">{{ s.email }}</div>
                    </td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px] bg-indigo-50 text-indigo-700 uppercase">{{ s.plan || 'free' }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px] bg-emerald-100 text-emerald-700">{{ s.is_active !== false ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: BANK SOAL ===== -->
        <div v-if="activeTab === 'questions'" class="space-y-6 animate-fade-in">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-black text-slate-800">Manajemen Bank Soal</h2>
            
            <button @click="showImportModal = true" class="px-4 py-2 bg-emerald-50 text-emerald-600 font-bold rounded-xl hover:bg-emerald-100 mr-2">
              <i class="ph-bold ph-file-csv mr-1"></i> Import CSV
            </button>

            <button @click="openQuestionModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
              + Tambah Soal
            </button>
          </div>

          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex gap-3">
              <input v-model="qSearch" type="text" placeholder="Cari soal..." class="flex-grow px-4 py-2 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
              <select v-model="qSubMateri" class="px-3 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl outline-none cursor-pointer">
                <option value="all">Semua Subtes</option>
                <option value="Penalaran Umum">Penalaran Umum</option>
                <option value="Penalaran Matematika">Penalaran Matematika</option>
                <option value="Literasi B. Indonesia">Literasi B. Indonesia</option>
                <option value="Literasi B. Inggris">Literasi B. Inggris</option>
              </select>
            </div>
            
            <div v-if="questionsLoading" class="p-8 text-center text-slate-400">
              <i class="ph-bold ph-spinner animate-spin text-2xl mb-2"></i>
              <p class="text-xs">Memuat bank soal...</p>
            </div>
            <div v-else-if="filteredQuestions.length === 0" class="p-8 text-center text-slate-400">
              <p class="text-xs">Tidak ada soal yang ditemukan.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider w-1/2">Soal</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Sub Materi</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Kategori & Level</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="q in filteredQuestions" :key="q.id" class="hover:bg-slate-50">
                    <td class="px-5 py-3.5">
                      <div class="line-clamp-2 text-slate-700">{{ q.question }}</div>
                    </td>
                    <td class="px-5 py-3.5 font-bold text-slate-600">{{ q.sub_materi }}</td>
                    <td class="px-5 py-3.5">
                      <div class="flex flex-col gap-1 items-start">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 uppercase">{{ q.usage_type || 'Latihan' }}</span>
                        <span class="text-[10px] text-slate-500 font-bold">{{ q.cognitive_level || 'C3' }}</span>
                      </div>
                    </td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px]" :class="q.is_active !== false ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        {{ q.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                      <button @click="openQuestionModal(q)" class="text-indigo-600 hover:text-indigo-800 font-bold"><i class="ph-bold ph-pencil-simple"></i></button>
                      <button @click="deleteQuestion(q.id)" class="text-rose-500 hover:text-rose-700 font-bold"><i class="ph-bold ph-trash"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN MATERI ===== -->
        <div v-if="activeTab === 'materials'" class="space-y-6 animate-fade-in">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-black text-slate-800">Manajemen Materi</h2>
            <button @click="openMaterialModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
              + Tambah Materi
            </button>
          </div>

          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex gap-3">
              <input v-model="mSearch" type="text" placeholder="Cari materi..." class="flex-grow px-4 py-2 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
            
            <div v-if="materialsLoading" class="p-8 text-center text-slate-400">
              <i class="ph-bold ph-spinner animate-spin text-2xl mb-2"></i>
              <p class="text-xs">Memuat materi...</p>
            </div>
            <div v-else-if="filteredMaterials.length === 0" class="p-8 text-center text-slate-400">
              <p class="text-xs">Tidak ada materi yang ditemukan.</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Judul Materi</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Sub Materi</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Guru/PJ</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="m in filteredMaterials" :key="m.id" class="hover:bg-slate-50">
                    <td class="px-5 py-3.5 font-bold text-slate-700">{{ m.title }}</td>
                    <td class="px-5 py-3.5 font-bold text-slate-600">{{ m.sub_materi }}</td>
                    <td class="px-5 py-3.5 font-bold text-slate-500 text-xs">{{ m.teacher_name || '-' }}</td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px]" :class="m.is_active !== false ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        {{ m.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                      <button @click="openMaterialModal(m)" class="text-indigo-600 hover:text-indigo-800 font-bold"><i class="ph-bold ph-pencil-simple"></i></button>
                      <button @click="deleteMaterial(m.id)" class="text-rose-500 hover:text-rose-700 font-bold"><i class="ph-bold ph-trash"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Paket -->
        <div v-if="activeTab === 'packages'" class="space-y-6 animate-fade-in">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-black text-slate-800">Manajemen Paket</h2>
            <button @click="openPlanModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center gap-2 shadow-sm shadow-indigo-200">
              <i class="ph-bold ph-plus"></i> Tambah Paket
            </button>
          </div>
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Nama Paket</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Harga</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Durasi (Hari)</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="p in plans" :key="p.id" class="hover:bg-slate-50">
                    <td class="px-5 py-3.5 font-bold text-slate-700">{{ p.name }}</td>
                    <td class="px-5 py-3.5 font-bold text-slate-600">Rp {{ p.price.toLocaleString('id-ID') }}</td>
                    <td class="px-5 py-3.5 font-bold text-slate-600">{{ p.duration }} Hari</td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px]" :class="p.is_active !== false ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        {{ p.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                      <button @click="openPlanModal(p)" class="text-indigo-600 hover:text-indigo-800 font-bold"><i class="ph-bold ph-pencil-simple"></i></button>
                      <button @click="deletePlan(p.id)" class="text-rose-500 hover:text-rose-700 font-bold"><i class="ph-bold ph-trash"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Staff -->
        <div v-if="activeTab === 'staff'" class="space-y-6 animate-fade-in">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-black text-slate-800">Manajemen Pengguna Internal</h2>
            <button @click="openStaffModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center gap-2 shadow-sm shadow-indigo-200">
              <i class="ph-bold ph-plus"></i> Tambah Staff
            </button>
          </div>
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Username</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Nama</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Peran (Role)</th>
                    <th class="text-left px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3 font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="s in staffMembers" :key="s.id" class="hover:bg-slate-50">
                    <td class="px-5 py-3.5 font-bold text-slate-700">{{ s.username }}</td>
                    <td class="px-5 py-3.5 font-bold text-slate-600">{{ s.name || '-' }}</td>
                    <td class="px-5 py-3.5 font-bold text-slate-600 uppercase">{{ s.role || 'admin' }}</td>
                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded-full font-black text-[10px]" :class="s.is_active !== false ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        {{ s.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                      <button @click="openStaffModal(s)" class="text-indigo-600 hover:text-indigo-800 font-bold"><i class="ph-bold ph-pencil-simple"></i></button>
                      <button v-if="s.username !== 'admin'" @click="deleteStaff(s.id)" class="text-rose-500 hover:text-rose-700 font-bold"><i class="ph-bold ph-trash"></i></button>
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
    
    <!-- Student Modal -->
    <div v-if="showStudentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="font-black text-lg text-slate-800">Tambah Siswa Baru</h3>
          <button @click="showStudentModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph-bold ph-x text-xl"></i></button>
        </div>
        <form @submit.prevent="saveStudent" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
            <input v-model="studentForm.name" required type="text" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Email</label>
            <input v-model="studentForm.email" required type="email" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Password</label>
            <input v-model="studentForm.password" required type="password" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Paket</label>
            <select v-model="studentForm.plan" required class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
              <option value="free">Free</option>
              <option value="mandiri">Mandiri</option>
              <option value="utama">Utama</option>
              <option value="vip">VIP</option>
            </select>
          </div>
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="showStudentModal = false" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Plan Modal -->
    <div v-if="showPlanModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="font-black text-lg text-slate-800">{{ isEditingPlan ? 'Edit Paket' : 'Tambah Paket Baru' }}</h3>
          <button @click="closePlanModal" class="text-slate-400 hover:text-slate-600"><i class="ph-bold ph-x text-xl"></i></button>
        </div>
        <form @submit.prevent="savePlan" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Nama Paket</label>
            <input v-model="pForm.name" required type="text" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Harga Asli (Rp)</label>
              <input v-model="pForm.price" required type="number" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Diskon (Rp) opsional</label>
              <input v-model="pForm.discount" type="number" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Durasi (Hari)</label>
              <input v-model="pForm.duration" required type="number" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-2">Entitlement (Fitur Akses)</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label v-for="ent in entitlementsDict" :key="ent.key" class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors" :class="{'bg-indigo-50 border-indigo-200': pForm.features.includes(ent.key)}">
                <input type="checkbox" :value="ent.key" v-model="pForm.features" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
                <span class="text-sm text-slate-700 font-medium">{{ ent.label }}</span>
              </label>
            </div>
          </div>
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="closePlanModal" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Staff Modal -->
    <div v-if="showStaffModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="font-black text-lg text-slate-800">{{ isEditingStaff ? 'Edit Staff' : 'Tambah Staff Baru' }}</h3>
          <button @click="closeStaffModal" class="text-slate-400 hover:text-slate-600"><i class="ph-bold ph-x text-xl"></i></button>
        </div>
        <form @submit.prevent="saveStaff" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Username</label>
            <input v-model="sForm.username" :disabled="isEditingStaff && sForm.username === 'admin'" required type="text" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400 disabled:opacity-50" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Nama Lengkap</label>
            <input v-model="sForm.name" required type="text" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Peran (Role)</label>
            <select v-model="sForm.role" required class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" :disabled="sForm.username === 'admin'">
              <option value="teacher">Guru / Tutor</option>
              <option value="admin">Administrator Utama</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Password {{ isEditingStaff ? '(Kosongkan jika tidak ingin mengubah)' : '' }}</label>
            <input v-model="sForm.password" :required="!isEditingStaff" type="password" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="closeStaffModal" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Question Modal -->
    <div v-if="showQuestionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="font-black text-lg text-slate-800">{{ isEditingQuestion ? 'Edit Soal' : 'Tambah Soal Baru' }}</h3>
          <button @click="closeQuestionModal" class="text-slate-400 hover:text-slate-600"><i class="ph-bold ph-x text-xl"></i></button>
        </div>
        <form @submit.prevent="saveQuestion" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Sub Materi</label>
              <select v-model="qForm.sub_materi" required class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
                <option value="Penalaran Umum">Penalaran Umum</option>
                <option value="Penalaran Matematika">Penalaran Matematika</option>
                <option value="Literasi B. Indonesia">Literasi B. Indonesia</option>
                <option value="Literasi B. Inggris">Literasi B. Inggris</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Tingkat Kesulitan</label>
              <select v-model="qForm.difficulty" required class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
                <option value="easy">Mudah</option>
                <option value="medium">Sedang</option>
                <option value="hard">Sulit</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Kategori Penggunaan</label>
              <select v-model="qForm.usage_type" required class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
                <option value="latihan">Latihan Harian</option>
                <option value="tryout">Tryout Resmi</option>
                <option value="diagnostik">Asesmen Diagnostik</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Level Kognitif</label>
              <select v-model="qForm.cognitive_demand" required class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
                <option value="C1">C1 - Mengingat</option>
                <option value="C2">C2 - Memahami</option>
                <option value="C3">C3 - Aplikasi</option>
                <option value="C4">C4 - Analisis</option>
                <option value="C5">C5 - Evaluasi</option>
                <option value="C6">C6 - Mencipta</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Sumber Soal</label>
              <select v-model="qForm.source_type" required class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
                <option value="author_created">Dibuat Sendiri (Author)</option>
                <option value="official_source">Sumber Resmi</option>
                <option value="licensed">Lisensi Pihak Ketiga</option>
                <option value="adapted">Diadaptasi / Dimodifikasi</option>
                <option value="unknown">Tidak Diketahui</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Status Hak Cipta</label>
              <select v-model="qForm.rights_status" required class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
                <option value="unknown">Belum Di-review (Unknown)</option>
                <option value="owned">Milik Sendiri</option>
                <option value="fair_use">Fair Use (Edukasi)</option>
                <option value="licensed">Berlisensi Sah</option>
                <option value="restricted">Restricted / Tidak Boleh Dipublish</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Nama Sumber (Opsional)</label>
              <input v-model="qForm.source_name" type="text" placeholder="Misal: UTBK 2023" class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Tahun (Opsional)</label>
              <input v-model="qForm.source_year" type="number" placeholder="2023" class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Referensi URL/Buku</label>
              <input v-model="qForm.source_reference" type="text" placeholder="URL atau hal." class="w-full p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Pertanyaan</label>

            <textarea v-model="qForm.question" required rows="3" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400"></textarea>
          </div>
          <div class="space-y-3">
            <label class="block text-xs font-bold text-slate-500">Opsi Jawaban</label>
            <div v-for="opt in ['a','b','c','d','e']" :key="opt" class="flex items-center gap-2">
              <input type="radio" v-model="qForm.correct" :value="opt" name="correctOpt" required class="w-4 h-4 text-indigo-600" />
              <span class="text-sm font-bold uppercase w-6">{{ opt }}.</span>
              <input v-model="qForm['option_' + opt]" :required="opt !== 'e'" type="text" placeholder="..." class="flex-grow p-2 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
            </div>
          </div>
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="closeQuestionModal" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50 flex items-center gap-2">
              <i v-if="isSaving" class="ph-bold ph-spinner animate-spin"></i> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Material Modal -->
    <div v-if="showMaterialModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="font-black text-lg text-slate-800">{{ isEditingMaterial ? 'Edit Materi' : 'Tambah Materi Baru' }}</h3>
          <button @click="closeMaterialModal" class="text-slate-400 hover:text-slate-600"><i class="ph-bold ph-x text-xl"></i></button>
        </div>
        <form @submit.prevent="saveMaterial" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Judul Materi</label>
            <input v-model="mForm.title" required type="text" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Sub Materi</label>
            <select v-model="mForm.sub_materi" required class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
              <option value="Penalaran Umum">Penalaran Umum</option>
              <option value="Penalaran Matematika">Penalaran Matematika</option>
              <option value="Literasi B. Indonesia">Literasi B. Indonesia</option>
              <option value="Literasi B. Inggris">Literasi B. Inggris</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Guru / Penanggung Jawab</label>
            
            <select v-model="mForm.teacher_name" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400">
              <option value="">-- Pilih Guru / Tidak Ada --</option>
              <option v-for="s in staffMembers" :key="s.id" :value="s.name">{{ s.name }} ({{ s.role }})</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Isi / Konten Materi</label>
            <textarea v-model="mForm.content" required rows="6" class="w-full p-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-400"></textarea>
          </div>
          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="closeMaterialModal" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-50 flex items-center gap-2">
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
    allStudents.value = res.students || [];
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
const qForm = reactive({ id: null, sub_materi: 'Penalaran Umum', difficulty: 'medium', question: '', option_a: '', option_b: '', option_c: '', option_d: '', option_e: '', correct: 'a', usage_type: 'latihan', cognitive_demand: 'C3', source_type: 'author_created', rights_status: 'unknown', source_name: '', source_year: null, source_reference: '' });

const openQuestionModal = (q = null) => {
  if (q) {
    isEditingQuestion.value = true;
    Object.assign(qForm, q);
  } else {
    isEditingQuestion.value = false;
    Object.assign(qForm, { id: null, sub_materi: 'Penalaran Umum', difficulty: 'medium', question: '', option_a: '', option_b: '', option_c: '', option_d: '', option_e: '', correct: 'a', usage_type: 'latihan', cognitive_demand: 'C3', source_type: 'author_created', rights_status: 'unknown', source_name: '', source_year: null, source_reference: '' });
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
