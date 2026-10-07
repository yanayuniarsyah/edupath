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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Order ID</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Siswa</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Paket</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Nominal</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Tanggal</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="o in pagedOrders" :key="o.order_id || o.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 font-mono text-white/60">{{ o.order_id || o.id }}</td>
                    <td class="px-3 py-2">
                      <div class="font-bold text-white">{{ o.student_name || '-' }}</div>
                      <div class="text-white/40 font-medium text-[10px]">{{ o.student_email || '-' }}</div>
                    </td>
                    <td class="px-3 py-2 font-bold text-indigo-400 capitalize">{{ o.plan_name || o.plan_id }}</td>
                    <td class="px-3 py-2 font-mono text-emerald-400 font-bold">Rp {{ Number(o.amount || 0).toLocaleString('id-ID') }}</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px] uppercase border"
                            :class="o.status === 'paid' || o.status === 'settlement' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (o.status === 'pending' ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400')">
                        {{ o.status }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-white/40">{{ o.created_at ? new Date(o.created_at).toLocaleString('id-ID') : '-' }}</td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openEditOrderStatusModal(o)" title="Ubah Status" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deleteOrder(o.order_id || o.id)" title="Hapus Transaksi" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
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
              <input v-model="studentSearch" type="text" placeholder="Cari nama atau email siswa..." class="w-full pl-8 pr-3 py-1.5 text-[11px] font-medium text-white placeholder-white/30 bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-colors" />
            </div>
            <select v-model="studentPlanFilter" class="text-[11px] font-bold text-white bg-black/40 border border-white/10 rounded-lg px-2.5 py-1.5 outline-none cursor-pointer focus:border-[#c0ff00]/50">
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-center px-3 py-2 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('name')">Siswa <i v-if="sortKey === 'name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('plan')">Paket <i v-if="sortKey === 'plan'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('is_active')">Status <i v-if="sortKey === 'is_active'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(s, index) in pagedStudents" :key="s.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 text-center text-white/50 font-bold">{{ (studentPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-3 py-2">
                      <div class="font-bold text-white">{{ s.name }}</div>
                      <div class="text-white/40 font-medium text-[10px]">{{ s.email }}</div>
                    </td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px] bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 uppercase">{{ s.plan || 'free' }}</span>
                    </td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px]" :class="s.is_active != 0 ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/30 text-rose-400'">
                        {{ s.is_active != 0 ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openEditStudentModal(s)" title="Edit Siswa" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deleteStudent(s.id)" title="Hapus Siswa" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
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
                    <th class="text-center px-3 py-2 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider w-4/12 cursor-pointer hover:text-white" @click="setSort('question')">Pertanyaan <i v-if="sortKey === 'question'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('test_component')">Komponen & Subtes <i v-if="sortKey === 'test_component'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('topic')">Topik & Subtopik <i v-if="sortKey === 'topic'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('skill')">Skill / Kompetensi <i v-if="sortKey === 'skill'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('question_type')">Tipe & Level <i v-if="sortKey === 'question_type'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('is_qc_passed')">Status QC <i v-if="sortKey === 'is_qc_passed'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(q, index) in pagedQuestions" :key="q.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 text-center text-white/50 font-bold">{{ (questionPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-3 py-2">
                      <div class="line-clamp-2 text-white/80 font-medium">{{ q.question }}</div>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex flex-col gap-0.5">
                        <span class="text-[9px] font-black uppercase tracking-wider text-[#c0ff00]">{{ q.test_component || 'TPS' }}</span>
                        <span class="font-bold text-white text-[11px]">{{ q.subtest || q.sub_materi || q.subtes }}</span>
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex flex-col gap-0.5">
                        <span v-if="q.topic" class="text-[10px] font-bold text-indigo-300">{{ q.topic }}</span>
                        <span v-if="q.subtopic" class="text-[9px] text-white/50">{{ q.subtopic }}</span>
                        <span v-if="!q.topic && !q.subtopic" class="text-[9px] text-white/30 italic">-</span>
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <span v-if="q.skill" class="text-[10px] font-medium text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">{{ q.skill }}</span>
                      <span v-else class="text-[9px] text-white/30 italic">-</span>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex flex-col gap-0.5 items-start">
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 uppercase">{{ q.usage_type || q.classification || 'Latihan' }}</span>
                        <span class="text-[9px] text-white/40 font-bold uppercase">{{ q.difficulty || 'medium' }} • {{ q.cognitive_demand || 'C3' }}</span>
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <span v-if="q.is_qc_passed == 1" class="px-2 py-0.5 text-[9px] font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-md">Lolos QC</span>
                      <span v-else class="px-2 py-0.5 text-[9px] font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-md">Belum QC</span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openQuestionModal(q)" class="text-indigo-400 hover:text-indigo-300 font-bold" title="Edit Soal"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deleteQuestion(q.id)" class="text-rose-400 hover:text-rose-300 font-bold" title="Hapus Soal"><i class="ph-bold ph-trash text-sm"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Questions -->
            <div v-if="filteredQuestions.length > PAGE_SIZE" class="px-3.5 py-2.5 border-t border-white/10 flex items-center justify-between text-[11px] bg-black/20">
              <span class="text-white/40 font-medium">
                Menampilkan {{ (questionPage - 1) * PAGE_SIZE + 1 }}-{{ Math.min(questionPage * PAGE_SIZE, filteredQuestions.length) }} dari {{ filteredQuestions.length }} soal
              </span>
              <div class="flex items-center gap-1.5">
                <button :disabled="questionPage <= 1" @click="questionPage--" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  &laquo; Prev
                </button>
                <span class="px-2 font-mono font-bold text-[#c0ff00]">{{ questionPage }} / {{ questionTotalPages }}</span>
                <button :disabled="questionPage >= questionTotalPages" @click="questionPage++" class="px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none text-white font-bold transition-all">
                  Next &raquo;
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TAB: MANAJEMEN MATERI ===== -->
        <div v-if="activeTab === 'materials'" class="space-y-4 animate-fade-in">
          <div class="flex items-center justify-between">
            <h2 class="text-xs font-black text-white uppercase tracking-wider">Manajemen Materi Belajar</h2>
            <button @click="openMaterialModal()" class="px-3 py-1.5 bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-[11px] font-black rounded-lg transition-colors flex items-center gap-1">
              <i class="ph-bold ph-plus"></i> Tambah Materi
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
                    <th class="text-center px-3 py-2 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('title')">Judul Materi <i v-if="sortKey === 'title'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('sub_materi')">Sub Materi <i v-if="sortKey === 'sub_materi'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('teacher_name')">Guru / PJ <i v-if="sortKey === 'teacher_name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('is_active')">Status <i v-if="sortKey === 'is_active'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(m, index) in pagedMaterials" :key="m.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 text-center text-white/50 font-bold">{{ (materialPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-3 py-2 font-bold text-white">{{ m.title }}</td>
                    <td class="px-3 py-2 font-bold text-white/80">{{ m.sub_materi }}</td>
                    <td class="px-3 py-2 font-bold text-white/40 text-[10px]">{{ m.teacher_name || '-' }}</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px]" :class="m.is_active != 0 ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/30 text-rose-400'">
                        {{ m.is_active != 0 ? 'Aktif' : 'Nonaktif' }}
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-center px-3 py-2 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('name')">Nama Paket <i v-if="sortKey === 'name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('price')">Harga Resmi <i v-if="sortKey === 'price'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('duration')">Durasi <i v-if="sortKey === 'duration'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Entitlements</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(p, index) in pagedPlans" :key="p.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 text-center text-white/50 font-bold">{{ (planPage - 1) * PAGE_SIZE + index + 1 }}</td>
                    <td class="px-3 py-2 font-bold text-white">{{ p.name }}</td>
                    <td class="px-3 py-2 font-bold text-emerald-400 font-mono">Rp {{ Number(p.price || 0).toLocaleString('id-ID') }}</td>
                    <td class="px-3 py-2 font-bold text-white/80">{{ p.duration }} Hari</td>
                    <td class="px-3 py-2">
                      <span class="text-[10px] text-indigo-300 font-medium">{{ Array.isArray(p.features) ? p.features.length : 0 }} fitur</span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                      <button @click="openPlanModal(p)" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deletePlan(p.id)" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
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

        <!-- ===== TAB: AFILIASI & KOMISI ===== -->
        <div v-if="activeTab === 'affiliates'" class="space-y-4 animate-fade-in">
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
                      <span :class="['px-2 py-0.5 rounded-md text-[9px] font-bold uppercase border', pay.status === 'paid' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (pay.status === 'rejected' ? 'bg-rose-500/10 border-rose-500/30 text-rose-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400')]">
                        {{ pay.status }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3 text-right space-x-1.5">
                      <template v-if="pay.status === 'pending'">
                        <button @click="approvePayoutReq(pay.id)" class="px-2 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/30 font-bold rounded-md transition-colors text-[10px]">
                          <i class="ph-bold ph-check"></i> Ditransfer
                        </button>
                        <button @click="openRejectPayoutModal(pay)" class="px-2 py-1 bg-rose-500/20 border border-rose-500/30 text-rose-400 hover:bg-rose-500/30 font-bold rounded-md transition-colors text-[10px]">
                          <i class="ph-bold ph-x"></i> Tolak
                        </button>
                      </template>
                      <span v-else class="text-[9px] text-white/40">Selesai</span>
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
                    <th class="py-2 px-3 text-left font-bold">Mitra</th>
                    <th class="py-2 px-3 text-left font-bold">Kode Referral</th>
                    <th class="py-2 px-3 text-left font-bold">Komisi (%)</th>
                    <th class="py-2 px-3 text-left font-bold">Bank Info</th>
                    <th class="py-2 px-3 text-right font-bold">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="isLoadingAffiliates">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-[10px]">Memuat data mitra...</td>
                  </tr>
                  <tr v-else-if="affiliatesList.length === 0">
                    <td colspan="5" class="py-6 text-center text-white/40 font-medium text-[10px]">Belum ada mitra afiliasi</td>
                  </tr>
                  <tr v-for="aff in affiliatesList" :key="aff.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-2.5 px-3">
                      <div class="font-semibold text-white/90">{{ aff.affiliate_name || aff.identity_key }}</div>
                      <div class="text-[9px] text-white/40">{{ aff.email || aff.identity_key }}</div>
                    </td>
                    <td class="py-2.5 px-3 font-mono text-indigo-400 font-bold">{{ aff.referral_code }}</td>
                    <td class="py-2.5 px-3 text-amber-400 font-bold">{{ aff.commission_rate }}%</td>
                    <td class="py-2.5 px-3 text-white/60">
                      {{ aff.bank_name ? `${aff.bank_name} (${aff.bank_account})` : '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-right space-x-2">
                      <button @click="openEditAffiliateModal(aff)" class="text-indigo-400 hover:text-indigo-300 font-bold"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                      <button @click="deleteAffiliate(aff.id)" class="text-rose-400 hover:text-rose-300 font-bold"><i class="ph-bold ph-trash text-sm"></i></button>
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 text-white/50 border-b border-white/10">
                    <th class="py-2 px-3 text-left font-bold">Siswa</th>
                    <th class="py-2 px-3 text-left font-bold">Mitra</th>
                    <th class="py-2 px-3 text-left font-bold">Nominal (Rp)</th>
                    <th class="py-2 px-3 text-left font-bold">Status</th>
                    <th class="py-2 px-3 text-right font-bold">Aksi</th>
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
                      <button v-if="comm.status === 'pending'" @click="openPayoutModal(comm)" class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/30 font-bold rounded-md transition-colors text-[10px]">
                        <i class="ph-bold ph-check-circle"></i> Bayar
                      </button>
                      <div v-else class="text-[9px] text-white/40">
                        <i class="ph-bold ph-check-circle text-emerald-400 mr-0.5"></i> Telah Dibayar
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
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
              <table class="w-full text-[11px]">
                <thead>
                  <tr class="bg-black/40 border-b border-white/10 text-white/50">
                    <th class="text-center px-3 py-2 font-black uppercase tracking-wider w-12 cursor-pointer hover:text-white" @click="setSort('id')">No</th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('username')">Username <i v-if="sortKey === 'username'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('name')">Nama <i v-if="sortKey === 'name'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider cursor-pointer hover:text-white" @click="setSort('role')">Peran (Role) <i v-if="sortKey === 'role'" :class="sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down'"></i></th>
                    <th class="text-left px-3 py-2 font-black uppercase tracking-wider">Status</th>
                    <th class="text-right px-3 py-2 font-black uppercase tracking-wider">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-for="(s, index) in sortedStaffMembers" :key="s.id" class="hover:bg-white/5 transition-colors">
                    <td class="px-3 py-2 text-center text-white/50 font-bold">{{ index + 1 }}</td>
                    <td class="px-3 py-2 font-bold text-white">{{ s.username }}</td>
                    <td class="px-3 py-2 font-bold text-white/80">{{ s.name || '-' }}</td>
                    <td class="px-3 py-2 font-bold text-indigo-400 uppercase">{{ s.role || 'admin' }}</td>
                    <td class="px-3 py-2">
                      <span class="px-2 py-0.5 rounded-md font-bold text-[9px] bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                        Aktif
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

            <button @click="blastTelegramParentReport" class="p-3.5 rounded-xl bg-[#0e1726]/80 border border-white/10 hover:border-emerald-500/50 transition-all text-left group">
              <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg mb-2">
                <i class="ph-bold ph-telegram-logo"></i>
              </div>
              <h4 class="font-bold text-white text-xs">Blast WA Orang Tua</h4>
              <p class="text-[10px] text-white/40 mt-0.5">Template laporan progres belajar</p>
            </button>
          </div>

          <!-- Revenue Breakdown by Official Packages -->
          <div class="bg-[#0e1726]/80 backdrop-blur-md rounded-xl border border-white/10 p-4">
            <h3 class="font-bold text-white text-xs mb-3">Distribusi Produk Resmi EduPath</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div class="p-3 rounded-lg bg-black/40 border border-white/10">
                <div class="text-[10px] text-white/40 uppercase font-bold">Paket Mandiri</div>
                <div class="text-sm font-mono font-black text-indigo-400 mt-1">Rp 180.000 / bln</div>
                <div class="text-[10px] text-white/60 mt-1">500+ Micro-lessons, 50.000+ Soal IRT, 5x TO Nasional</div>
              </div>
              <div class="p-3 rounded-lg bg-black/40 border border-[#c0ff00]/30">
                <div class="text-[10px] text-[#c0ff00] uppercase font-bold">Paket Utama (Terpopuler)</div>
                <div class="text-sm font-mono font-black text-[#c0ff00] mt-1">Rp 450.000 / bln</div>
                <div class="text-[10px] text-white/60 mt-1">AI Companion 24/7, Unlimited IRT, Rasionalisasi Prodi, WA Report</div>
              </div>
              <div class="p-3 rounded-lg bg-black/40 border border-amber-500/30">
                <div class="text-[10px] text-amber-400 uppercase font-bold">Paket VIP</div>
                <div class="text-sm font-mono font-black text-amber-400 mt-1">Rp 1.100.000 / bln</div>
                <div class="text-[10px] text-white/60 mt-1">1-on-1 Zoom Mentoring, Grup WA VIP bareng Mentor Senior</div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>

    <!-- Modals -->

    <!-- Manual Order Modal -->
    <div v-if="showOrderModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">Tambah Transaksi Manual / Offline</h3>
          <button @click="showOrderModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveManualOrder" class="p-4 space-y-3">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pilih Siswa</label>
            <select v-model="orderForm.student_id" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="" disabled>-- Pilih Siswa --</option>
              <option v-for="s in allStudents" :key="s.id" :value="s.id" class="bg-[#0d1427] text-white">{{ s.name }} ({{ s.email }})</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Paket Belajar</label>
            <select v-model="orderForm.plan_id" @change="syncOrderPlanPrice" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="plan-mandiri" class="bg-[#0d1427] text-white">Paket Mandiri (Rp 180.000)</option>
              <option value="plan-utama" class="bg-[#0d1427] text-white">Paket Utama (Rp 450.000)</option>
              <option value="plan-vip" class="bg-[#0d1427] text-white">Paket VIP (Rp 1.100.000)</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nominal Pembayaran (Rp)</label>
            <input v-model="orderForm.amount" required type="number" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Transaksi</label>
            <select v-model="orderForm.status" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
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

    <!-- Reject Payout Modal -->
    <div v-if="showRejectPayoutModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-xs shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
          <h3 class="font-black text-sm text-white">Tolak Permintaan Payout</h3>
          <button @click="showRejectPayoutModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <div class="p-4 space-y-3">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Alasan Penolakan</label>
            <textarea v-model="rejectReason" rows="3" placeholder="Misal: Nomor rekening tidak valid / tidak cocok dengan nama akun" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-rose-500/50 transition-all font-medium"></textarea>
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
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email Mitra</label>
            <input v-model="affiliateForm.email" :disabled="isEditingAffiliate" required type="email" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium disabled:opacity-50" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Kode Referral</label>
            <input v-model="affiliateForm.referral_code" required type="text" placeholder="MISAL: BELAJARHEMAT" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium uppercase font-mono" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Komisi (%)</label>
            <input v-model="affiliateForm.commission_rate" required type="number" step="0.5" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Bank</label>
              <input v-model="affiliateForm.bank_name" placeholder="BCA / BRI / Mandiri" type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nomor Rekening</label>
              <input v-model="affiliateForm.bank_account" placeholder="1234567890" type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Pemilik Rekening</label>
            <input v-model="affiliateForm.bank_owner" placeholder="Nama sesuai buku tabungan" type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
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

    <!-- Student Modal (Tambah / Edit) -->
    <div v-if="showStudentModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-50 p-4">
      <div class="bg-[#0d1427] border border-white/15 rounded-2xl w-full max-w-sm max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in text-white">
        <div class="p-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-[#0d1427] z-10">
          <h3 class="font-black text-sm text-white">{{ isEditingStudent ? 'Edit Siswa' : 'Tambah Siswa Baru' }}</h3>
          <button @click="showStudentModal = false" class="text-white/40 hover:text-white transition-colors"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        <form @submit.prevent="saveStudent" class="p-4 space-y-3">
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Nama Lengkap</label>
            <input v-model="studentForm.name" required type="text" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div v-if="isSuperadmin && tenantsList.length > 1">
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Cabang / Lembaga Bimbel (Opsional)</label>
            <select v-model="studentForm.tenant_id" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" :disabled="isEditingStudent">
              <option value="" class="bg-[#0d1427] text-white">-- Bimbel Utama (Default) --</option>
              <option v-for="t in tenantsList" :key="t.id" :value="t.id" class="bg-[#0d1427] text-white">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email</label>
            <input v-model="studentForm.email" required type="email" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Password {{ isEditingStudent ? '(Kosongkan jika tidak ubah)' : '' }}</label>
            <input v-model="studentForm.password" :required="!isEditingStudent" type="password" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Paket Belajar</label>
            <select v-model="studentForm.plan" required class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option value="free" class="bg-[#0d1427] text-white">Free</option>
              <option value="mandiri" class="bg-[#0d1427] text-white">Mandiri</option>
              <option value="utama" class="bg-[#0d1427] text-white">Utama</option>
              <option value="vip" class="bg-[#0d1427] text-white">VIP</option>
            </select>
          </div>
          <div v-if="isEditingStudent">
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Status Akun</label>
            <select v-model="studentForm.is_active" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
              <option :value="1" class="bg-[#0d1427] text-white">Aktif</option>
              <option :value="0" class="bg-[#0d1427] text-white">Nonaktif</option>
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
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Email (sebagai Username)</label>
            <input v-model="sForm.username" :disabled="isEditingStaff && sForm.username === 'admin'" required type="text" placeholder="nama@bimbel.com" class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium disabled:opacity-50" />
          </div>
          <div v-if="isSuperadmin && tenantsList.length > 1">
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Cabang / Lembaga Bimbel (Opsional)</label>
            <select v-model="sForm.tenant_id" class="w-full p-2.5 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" :disabled="isEditingStaff">
              <option value="" class="bg-[#0d1427] text-white">-- Bimbel Utama (Default) --</option>
              <option v-for="t in tenantsList" :key="t.id" :value="t.id" class="bg-[#0d1427] text-white">{{ t.name }}</option>
            </select>
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
          <!-- Row 1: Ujian & Komponen -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Ujian</label>
              <input v-model="qForm.exam" readonly class="w-full p-2 text-xs text-white/70 bg-black/40 border border-white/10 rounded-lg outline-none cursor-not-allowed font-medium" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Komponen Ujian</label>
              <select v-model="qForm.test_component" @change="onQComponentChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="c in qAvailableComponents" :key="c.name" :value="c.name" class="bg-[#0d1427] text-white">{{ c.label || c.name }}</option>
              </select>
            </div>
          </div>

          <!-- Row 2: Subtes & Topik -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtes</label>
              <select v-model="qForm.subtest" @change="onQSubtestChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="s in qAvailableSubtests" :key="s.name" :value="s.name" class="bg-[#0d1427] text-white">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Topik</label>
              <select v-model="qForm.topic" @change="onQTopicChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="t in qAvailableTopics" :key="t.name" :value="t.name" class="bg-[#0d1427] text-white">{{ t.name }}</option>
              </select>
            </div>
          </div>

          <!-- Row 3: Subtopik & Skill -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtopik</label>
              <input v-model="qForm.subtopic" list="qSubtopicDatalist" placeholder="e.g. Pola Bilangan..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="qSubtopicDatalist">
                <option v-for="st in (qCurrentTopicObj?.subtopics || [])" :key="st" :value="st"></option>
              </datalist>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Skill / Kompetensi</label>
              <input v-model="qForm.skill" list="qSkillDatalist" placeholder="e.g. Identifikasi Pola..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="qSkillDatalist">
                <option v-for="sk in (qCurrentTopicObj?.skills || [])" :key="sk" :value="sk"></option>
              </datalist>
            </div>
          </div>

          <!-- Row 4: Indikator & Tipe Soal -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Indikator Soal</label>
              <input v-model="qForm.indicator" list="qIndicatorDatalist" placeholder="e.g. Menentukan pola berikutnya..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="qIndicatorDatalist">
                <option v-for="ind in (qCurrentTopicObj?.indicators || [])" :key="ind" :value="ind"></option>
              </datalist>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tipe Soal</label>
              <select v-model="qForm.question_type" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
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
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Tingkat Kesulitan</label>
              <select v-model="qForm.difficulty" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="easy" class="bg-[#0d1427] text-white">Mudah</option>
                <option value="medium" class="bg-[#0d1427] text-white">Sedang</option>
                <option value="hard" class="bg-[#0d1427] text-white">Sulit</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Penggunaan</label>
              <select v-model="qForm.usage_type" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option value="latihan" class="bg-[#0d1427] text-white">Latihan Harian</option>
                <option value="tryout" class="bg-[#0d1427] text-white">Tryout Resmi</option>
                <option value="diagnostik" class="bg-[#0d1427] text-white">Diagnostik</option>
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

          <div class="flex items-center gap-2 pt-1">
            <input type="checkbox" id="qc_passed" v-model="qForm.is_qc_passed" :true-value="1" :false-value="0" class="w-3.5 h-3.5 text-[#c0ff00] bg-black/40 border-white/20 rounded focus:ring-[#c0ff00]">
            <label for="qc_passed" class="text-xs font-bold text-white/80 cursor-pointer">Telah Melewati Proses QC (Guru / Superadmin)</label>
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
          <div>
            <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Pembahasan Lengkap</label>
            <textarea v-model="qForm.explanation" rows="2" placeholder="Uraian langkah penyelesaian..." class="w-full p-2.5 text-xs text-white bg-black/40 border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium"></textarea>
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
          <!-- Komponen & Subtes -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Komponen Ujian</label>
              <select v-model="mForm.test_component" @change="onMComponentChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="c in mAvailableComponents" :key="c.name" :value="c.name" class="bg-[#0d1427] text-white">{{ c.label || c.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtes</label>
              <select v-model="mForm.subtest" @change="onMSubtestChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="s in mAvailableSubtests" :key="s.name" :value="s.name" class="bg-[#0d1427] text-white">{{ s.name }}</option>
              </select>
            </div>
          </div>

          <!-- Topik & Subtopik -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Topik</label>
              <select v-model="mForm.topic" @change="onMTopicChange" required class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium">
                <option v-for="t in mAvailableTopics" :key="t.name" :value="t.name" class="bg-[#0d1427] text-white">{{ t.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/50 mb-1 uppercase tracking-wider">Subtopik</label>
              <input v-model="mForm.subtopic" list="mSubtopicDatalist" placeholder="e.g. Pola Bilangan..." class="w-full p-2 text-xs text-white bg-[#0d1427] border border-white/10 rounded-lg outline-none focus:border-[#c0ff00]/50 transition-all font-medium" />
              <datalist id="mSubtopicDatalist">
                <option v-for="st in (mCurrentTopicObj?.subtopics || [])" :key="st" :value="st"></option>
              </datalist>
            </div>
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
import { SNBT_TAXONOMY } from '../EduData.js';

const router = useRouter()

// ── Data ──
const sidebarOpen = ref(true);
const activeTab = ref(sessionStorage.getItem('admin_active_tab') || 'overview');

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

// ── Nav Items ──
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

const currentNavItem = computed(() => navItems.find(item => item.id === activeTab.value));

const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
};

// ── Auth ──
const isAuthenticated = ref(sessionStorage.getItem('admin_token') !== null);
const loginForm = ref({ username: '', password: '' });
const loginError = ref('');
const loginLoading = ref(false);

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
  extractRoleFromToken(sessionStorage.getItem('admin_token') || sessionStorage.getItem('ep_admin_token') || localStorage.getItem('auth_token')) ||
  localStorage.getItem('user_role') ||
  sessionStorage.getItem('admin_role') ||
  ''
);

const isSuperadmin = computed(() => {
  return currentRole.value === 'superadmin' ||
         localStorage.getItem('user_role') === 'superadmin' ||
         sessionStorage.getItem('admin_role') === 'superadmin';
});

const tenantsList = ref([]);
const showPassword = ref(false);

const doLogin = async () => {
  loginError.value = '';
  loginLoading.value = true;
  try {
    const u = loginForm.value.username?.trim() || '';
    const p = loginForm.value.password?.trim() || '';
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
    fetchDashboard();
    fetchStudents();
    fetchAdminOrders();
    loadPlansAndStaff();
    fetchQuestions();
    fetchMaterials();
  } catch (err) {
    loginError.value = err.message || 'Login gagal';
  } finally {
    loginLoading.value = false;
  }
};

const goToStudentSide = () => {
  window.location.hash = '#/';
};

const doLogout = async () => {
  // Update UI optimistically to give instant feedback
  isAuthenticated.value = false;
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
  currentRole.value = '';
  
  document.body.style.pointerEvents = '';
  window.location.hash = '#/';
  location.reload();
}

onMounted(async () => {
  const adminToken = sessionStorage.getItem('admin_token') || sessionStorage.getItem('ep_admin_token');
  const mainToken = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
  
  const tokenToInspect = adminToken || mainToken;
  if (tokenToInspect) {
    const roleFromTok = extractRoleFromToken(tokenToInspect);
    if (roleFromTok) {
      currentRole.value = roleFromTok;
      localStorage.setItem('user_role', roleFromTok);
      sessionStorage.setItem('admin_role', roleFromTok);
    }
  }

  if (adminToken) {
    isAuthenticated.value = true;
    fetchDashboard();
    fetchStudents();
    fetchAdminOrders();
    loadPlansAndStaff();
    fetchQuestions();
    fetchMaterials();
  } else if (mainToken) {
    try {
      const profile = await api.getProfile();
      if (profile?.user?.role === 'admin' || profile?.user?.role === 'superadmin' || profile?.user?.role === 'teacher') {
        sessionStorage.setItem('admin_token', mainToken);
        currentRole.value = profile.user.role;
        localStorage.setItem('user_role', profile.user.role);
        sessionStorage.setItem('admin_role', profile.user.role);
        isAuthenticated.value = true;
        fetchDashboard();
        fetchStudents();
        fetchAdminOrders();
        loadPlansAndStaff();
        fetchQuestions();
        fetchMaterials();
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
  isEditingStudent.value = false;
  const defTenant = tenantsList.value?.[0]?.id || '';
  Object.assign(studentForm, { id: null, tenant_id: defTenant, name: '', email: '', password: '', plan: 'free', is_active: 1 });
  showStudentModal.value = true;
};

const openEditStudentModal = (s) => {
  isEditingStudent.value = true;
  Object.assign(studentForm, { id: s.id, tenant_id: s.tenant_id || tenantsList.value?.[0]?.id || '', name: s.name, email: s.email, password: '', plan: s.plan || 'free', is_active: s.is_active != 0 ? 1 : 0 });
  showStudentModal.value = true;
};

const saveStudent = async () => {
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
    orderForm.amount = 180000;
  } else if (orderForm.plan_id === 'plan-utama') {
    orderForm.plan_name = 'Paket Utama';
    orderForm.amount = 450000;
  } else if (orderForm.plan_id === 'plan-vip') {
    orderForm.plan_name = 'Paket VIP';
    orderForm.amount = 1100000;
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

// ── Affiliates & Commissions ──
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

const blastTelegramParentReport = () => {
  const message = `Halo Bapak/Ibu Wali Siswa EduPath,\n\nBerikut ringkasan progres belajar ananda di EduPath:\n- Tryout Terselesaikan: 5x\n- Rata-rata Skor SNBT: 685 (Target 700+)\n- Status Paket: Aktif\n\nTerus dukung ananda meraih PTN Impian bersama EduPath.ai!`;
  const url = `https://wa.me/?text=${encodeURIComponent(message)}`;
  window.open(url, '_blank');
};

// ── Watch activeTab to load data ──
watch(activeTab, (newTab) => {
  sessionStorage.setItem('admin_active_tab', newTab);
  if (newTab === 'overview') {
    fetchDashboard();
  } else if (newTab === 'students') {
    fetchStudents();
  } else if (newTab === 'questions') {
    fetchQuestions();
  } else if (newTab === 'materials') {
    fetchMaterials();
  } else if (newTab === 'transactions') {
    fetchAdminOrders();
  } else if (newTab === 'packages' || newTab === 'plans' || newTab === 'staff') {
    loadPlansAndStaff();
  } else if (newTab === 'affiliates') {
    loadAdminAffiliates();
    loadAdminCommissions();
    loadAdminPayouts();
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
