<template>
  <div :class="['relative min-h-screen antialiased font-body flex overflow-hidden transition-colors duration-300', 
    'bg-[#070b14] text-white',
    mobileSidebarOpen ? 'mobile-sidebar-open' : ''
  ]">
    <!-- Ambient Glow (Global) -->
    <div id="ambient-glow" ref="ambientGlowRef" class="opacity-80"></div>
    
    <!-- Background Blobs & Neural Canvas (Global) -->
    <div class="fixed top-[-20%] left-[-10%] w-[500px] h-[500px] rounded-full bg-purple-600/10 blur-[120px] pointer-events-none z-0 animate-pulse"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[600px] h-[600px] rounded-full bg-emerald-500/10 blur-[130px] pointer-events-none z-0" style="animation: float 10s ease-in-out infinite alternate"></div>
    <canvas id="bg-canvas" :style="{ opacity: canvasOpacity }" class="fixed inset-0 w-full h-full z-0 pointer-events-none transition-opacity duration-700"></canvas>

    <!-- Mobile Drawer Backdrop for Logged In User -->
    <transition name="fade">
      <div 
        v-if="isLoggedIn && mobileSidebarOpen" 
        class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40 lg:hidden cursor-pointer"
        @click="mobileSidebarOpen = false"
      ></div>
    </transition>

    <!-- Sidebar Navigation — auto-hide on desktop -->
    <aside
      v-if="isLoggedIn"
      class="fixed inset-y-0 left-0 h-full h-[100dvh] max-h-[100dvh] z-50 flex flex-col backdrop-blur-2xl border-r border-white/10 transition-transform duration-300 ease-out overflow-hidden text-white shadow-2xl"
      style="background: linear-gradient(160deg, #070c1a 0%, #0d1428 60%, #070c1a 100%);"
      :class="[
        (sidebarExpanded || mobileSidebarOpen) ? 'w-72 max-w-[85vw] shadow-[20px_0_60px_rgba(0,0,0,0.5)]' : 'w-[72px]',
        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
      @mouseenter="sidebarExpanded = true"
      @mouseleave="sidebarExpanded = false"
    >
      <!-- Top: Logo + Nav (scrollable) -->
      <div class="flex flex-col flex-1 min-h-0 overflow-y-auto" :class="(sidebarExpanded || mobileSidebarOpen) ? 'p-4 sm:p-6 pb-2' : 'p-3 pb-2'">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2.5 group cursor-pointer shrink-0" :class="(sidebarExpanded || mobileSidebarOpen) ? '' : 'justify-center w-full'" @click="goToHomeTop">
            <div class="w-8 h-8 shrink-0 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-base group-hover:rotate-12 transition-all duration-300 shadow-md shadow-[#c0ff00]/20">E</div>
            <div v-show="(sidebarExpanded || mobileSidebarOpen)" class="min-w-0">
              <span class="font-black text-xl tracking-tight text-white whitespace-nowrap">EduPath<span class="text-[#c0ff00]">.ai</span></span>
            </div>
          </div>
          <!-- Close button for mobile -->
          <button v-show="(sidebarExpanded || mobileSidebarOpen)" @click="mobileSidebarOpen = false" class="lg:hidden w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" aria-label="Tutup Menu" title="Tutup Menu">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>
        <p v-show="(sidebarExpanded || mobileSidebarOpen)" class="text-xs text-white/40 font-semibold mb-3 uppercase tracking-wider whitespace-nowrap shrink-0">Adaptive Learning Platform</p>
        <p v-show="!(sidebarExpanded || mobileSidebarOpen)" class="mb-3 shrink-0"></p>

        <!-- Mode Pratinjau Siswa Banner (Hanya jika Staff sedang Pratinjau Tampilan Siswa) -->
        <div v-if="isStaff && isStudentPreviewMode" class="mb-4 shrink-0">
          <div v-show="(sidebarExpanded || mobileSidebarOpen)" class="p-2.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
              <i class="ph-fill ph-eye text-amber-400 text-base shrink-0"></i>
              <div class="min-w-0">
                <span class="text-[10px] font-black uppercase tracking-wider block truncate">
                  Pratinjau Siswa
                </span>
                <span class="text-[9px] text-white/60 block truncate">
                  Tampilan simulasi siswa
                </span>
              </div>
            </div>
            <button 
              @click="window.location.hash = '#/admin'" 
              class="px-2 py-1 rounded bg-amber-500 hover:bg-amber-400 text-black font-extrabold text-[9px] shrink-0 transition-all cursor-pointer"
              title="Kembali ke Portal Admin/Guru"
            >
              Kembali
            </button>
          </div>
          <div v-show="!(sidebarExpanded || mobileSidebarOpen)" class="flex justify-center">
            <div class="w-8 h-8 rounded-lg bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 cursor-pointer" title="Kembali ke Admin" @click="window.location.hash = '#/admin'">
              <i class="ph-bold ph-arrow-left text-base"></i>
            </div>
          </div>
        </div>
        
        <nav class="flex flex-col gap-1.5">
          <template v-for="tab in filteredTabs" :key="tab.id">
            <button 
              :title="tab.label"
              :class="['flex items-center rounded-xl text-xs font-bold transition-all text-left group overflow-hidden shrink-0', 
                (sidebarExpanded || mobileSidebarOpen) ? 'gap-3 px-3.5 py-2.5' : 'justify-center px-0 py-2.5',
                currentTab === tab.id 
                  ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00] shadow-[0_0_20px_rgba(192,255,0,0.15)]' 
                  : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
              ]"
              @click="handleTabClick(tab.id); mobileSidebarOpen = false;"
            >
              <!-- Menampilkan huruf awal tab jika ikon gagal dimuat (fallback statis) -->
              <div class="relative shrink-0 flex items-center justify-center bg-white/5 rounded-lg group-hover:bg-white/10" :class="(sidebarExpanded || mobileSidebarOpen) ? 'w-6 h-6' : 'w-8 h-8'">
                <span class="absolute text-xs font-black opacity-100 text-white">{{ tab.label.charAt(0) }}</span>
                <i :class="['ph-bold relative z-10', tab.icon, (sidebarExpanded || mobileSidebarOpen) ? 'text-lg' : 'text-xl', currentTab === tab.id ? 'text-[#c0ff00]' : 'text-white/70 group-hover:text-white']"></i>
                <i v-if="['diagnostic', 'learning', 'simulator'].includes(tab.id) && isLoggedIn && !isPremiumUser && !isStaff" class="ph-fill ph-lock-key absolute -top-1 -right-1 text-[10px] text-rose-400 bg-slate-900 rounded-full p-[2px] z-20"></i>
              </div>
              <span v-show="(sidebarExpanded || mobileSidebarOpen)" class="font-bold tracking-tight whitespace-nowrap" :class="currentTab === tab.id ? 'text-white' : ''">{{ tab.label }}</span>
            </button>

            <!-- Sub Menu Materi di Side Menu (Auto Hide) -->
            <div v-if="tab.id === 'learning' && (sidebarExpanded || mobileSidebarOpen) && materiSubMenuOpen" class="ml-4 pl-3 border-l border-white/10 flex flex-col gap-1 my-1 animate-fade-in shrink-0">
              <!-- Group TPS -->
              <div class="px-2 pt-1 pb-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-400/80">TPS (Skolastik)</div>
              <button
                v-for="subtes in materiUtbk.filter(m => (m.test_component === 'TPS' || (!m.test_component && ['Penalaran Umum', 'Pengetahuan Kuantitatif', 'Pengetahuan & Pemahaman Umum', 'Pemahaman Bacaan & Menulis'].includes(m.subtes || m.subtest))))"
                :key="subtes.id"
                :class="['text-left text-[11px] font-semibold px-3 py-1.5 rounded-lg transition-all flex items-center justify-between gap-2', 
                  currentTab === 'learning' && selectedSubtes?.id === subtes.id
                    ? 'bg-[#c0ff00]/15 text-[#c0ff00] font-bold border border-[#c0ff00]/30'
                    : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
                @click.stop="selectSubtesFromSidebar(subtes)"
              >
                <div class="flex items-center gap-2 overflow-hidden">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#c0ff00]/70 shrink-0"></span>
                  <span class="truncate">{{ subtes.subtes }}</span>
                </div>
                <i v-if="isLoggedIn && !isPremiumUser && !isStaff" class="ph-fill ph-lock-key text-rose-400 shrink-0"></i>
              </button>

              <!-- Group TES LITERASI -->
              <div class="px-2 pt-2 pb-0.5 text-[9px] font-black uppercase tracking-wider text-sky-400/80">TES LITERASI</div>
              <button
                v-for="subtes in materiUtbk.filter(m => (m.test_component === 'TES LITERASI' || ['Literasi Bahasa Indonesia', 'Literasi Bahasa Inggris', 'Penalaran Matematika'].includes(m.subtes || m.subtest)))"
                :key="subtes.id"
                :class="['text-left text-[11px] font-semibold px-3 py-1.5 rounded-lg transition-all flex items-center justify-between gap-2', 
                  currentTab === 'learning' && selectedSubtes?.id === subtes.id
                    ? 'bg-sky-400/15 text-sky-400 font-bold border border-sky-400/30'
                    : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
                @click.stop="selectSubtesFromSidebar(subtes)"
              >
                <div class="flex items-center gap-2 overflow-hidden">
                  <span class="w-1.5 h-1.5 rounded-full bg-sky-400/70 shrink-0"></span>
                  <span class="truncate">{{ subtes.subtes }}</span>
                </div>
                <i v-if="isLoggedIn && !isPremiumUser && !isStaff" class="ph-fill ph-lock-key text-rose-400 shrink-0"></i>
              </button>

              <!-- Group TKA SAINTEK -->
              <template v-if="materiUtbk.some(m => (m.assessment_type === 'TKA' && m.track === 'saintek') || ['Matematika IPA', 'Fisika', 'Kimia', 'Biologi'].includes(m.subtes || m.subtest))">
                <div class="px-2 pt-2 pb-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-400/80">TKA SAINTEK</div>
                <button
                  v-for="subtes in materiUtbk.filter(m => (m.assessment_type === 'TKA' && m.track === 'saintek') || ['Matematika IPA', 'Fisika', 'Kimia', 'Biologi'].includes(m.subtes || m.subtest))"
                  :key="subtes.id"
                  :class="['text-left text-[11px] font-semibold px-3 py-1.5 rounded-lg transition-all flex items-center justify-between gap-2', 
                    currentTab === 'learning' && selectedSubtes?.id === subtes.id
                      ? 'bg-emerald-400/15 text-emerald-400 font-bold border border-emerald-400/30'
                      : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
                  ]"
                  @click.stop="selectSubtesFromSidebar(subtes)"
                >
                  <div class="flex items-center gap-2 overflow-hidden">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/70 shrink-0"></span>
                    <span class="truncate">{{ subtes.subtes }}</span>
                  </div>
                  <i v-if="isLoggedIn && !isPremiumUser && !isStaff" class="ph-fill ph-lock-key text-rose-400 shrink-0"></i>
                </button>
              </template>

              <!-- Group TKA SOSHUM -->
              <template v-if="materiUtbk.some(m => (m.assessment_type === 'TKA' && m.track === 'soshum') || ['Sosiologi', 'Ekonomi', 'Geografi', 'Sejarah'].includes(m.subtes || m.subtest))">
                <div class="px-2 pt-2 pb-0.5 text-[9px] font-black uppercase tracking-wider text-amber-400/80">TKA SOSHUM</div>
                <button
                  v-for="subtes in materiUtbk.filter(m => (m.assessment_type === 'TKA' && m.track === 'soshum') || ['Sosiologi', 'Ekonomi', 'Geografi', 'Sejarah'].includes(m.subtes || m.subtest))"
                  :key="subtes.id"
                  :class="['text-left text-[11px] font-semibold px-3 py-1.5 rounded-lg transition-all flex items-center justify-between gap-2', 
                    currentTab === 'learning' && selectedSubtes?.id === subtes.id
                      ? 'bg-amber-400/15 text-amber-400 font-bold border border-amber-400/30'
                      : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
                  ]"
                  @click.stop="selectSubtesFromSidebar(subtes)"
                >
                  <div class="flex items-center gap-2 overflow-hidden">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400/70 shrink-0"></span>
                    <span class="truncate">{{ subtes.subtes }}</span>
                  </div>
                  <i v-if="isLoggedIn && !isPremiumUser && !isStaff" class="ph-fill ph-lock-key text-rose-400 shrink-0"></i>
                </button>
              </template>
            </div>
          </template>
        </nav>
      </div>

      <!-- Bottom: User + Logout (Always pinned inside viewport, safe-area aware) -->
      <div 
        class="shrink-0 border-t border-white/10 bg-[#060a16]/95 backdrop-blur-xl" 
        :class="(sidebarExpanded || mobileSidebarOpen) ? 'p-3 sm:p-4' : 'p-2 mb-2'"
        style="padding-bottom: max(0.875rem, env(safe-area-inset-bottom));"
      >
        <div class="flex items-center gap-2.5 sm:gap-3 mb-2.5" :class="(sidebarExpanded || mobileSidebarOpen) ? '' : 'justify-center'">
          <div class="w-9 h-9 shrink-0 rounded-xl bg-gradient-to-br from-[#c0ff00] to-emerald-400 text-black flex items-center justify-center font-black text-xs border border-[#c0ff00]/40 shadow-[0_0_12px_rgba(192,255,0,0.3)]">
            <i class="ph-bold ph-user text-base text-black"></i>
          </div>
          <div v-show="(sidebarExpanded || mobileSidebarOpen)" class="flex-grow min-w-0">
            <h4 class="text-xs font-black text-white whitespace-nowrap truncate">{{ getStudentDisplayName(currentUser) }}</h4>
            <div v-if="isTeacher" @click="window.location.hash = '#/admin'" class="cursor-pointer group flex items-center gap-1 mt-0.5" title="Buka Portal Guru & Bank Soal">
              <span class="text-[10px] font-black uppercase tracking-wider whitespace-nowrap text-emerald-400">
                Pendidik / Tutor
              </span>
              <span class="text-[9px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 px-1 py-0.2 rounded font-extrabold group-hover:bg-emerald-400 group-hover:text-black transition-all">Portal Guru ➔</span>
            </div>
            <div v-else-if="isAdmin" @click="window.location.hash = '#/admin'" class="cursor-pointer group flex items-center gap-1 mt-0.5" title="Buka Admin Panel">
              <span class="text-[10px] font-black uppercase tracking-wider whitespace-nowrap text-indigo-400">
                {{ currentUser?.role === 'superadmin' ? 'Super Admin' : 'Administrator' }}
              </span>
              <span class="text-[9px] bg-indigo-500/20 text-indigo-400 border border-indigo-500/40 px-1 py-0.2 rounded font-extrabold group-hover:bg-indigo-400 group-hover:text-black transition-all">Panel ➔</span>
            </div>
            <div v-else-if="isSchoolBk" @click="window.location.hash = '#/school'" class="cursor-pointer group flex items-center gap-1 mt-0.5" title="Buka Portal Sekolah / Guru BK">
              <span class="text-[10px] font-black uppercase tracking-wider whitespace-nowrap text-amber-400">
                Guru BK / Mitra
              </span>
              <span class="text-[9px] bg-amber-500/20 text-amber-400 border border-amber-500/40 px-1 py-0.2 rounded font-extrabold group-hover:bg-amber-400 group-hover:text-black transition-all">Portal BK ➔</span>
            </div>
            <div v-else-if="isAffiliate" @click="window.location.hash = '#/admin'" class="cursor-pointer group flex items-center gap-1 mt-0.5" title="Buka Portal Afiliasi">
              <span class="text-[10px] font-black uppercase tracking-wider whitespace-nowrap text-amber-400">
                Mitra Afiliasi
              </span>
              <span class="text-[9px] bg-amber-500/20 text-amber-400 border border-amber-500/40 px-1 py-0.2 rounded font-extrabold group-hover:bg-amber-400 group-hover:text-black transition-all">Portal Afiliasi ➔</span>
            </div>
            <div v-else @click="openPlanSelector" class="cursor-pointer group flex items-center gap-1 mt-0.5" title="Klik untuk Lihat Pilihan Paket">
              <span class="text-[10px] font-black uppercase tracking-wider whitespace-nowrap text-[#c0ff00]">
                Status: {{ isPremiumUser ? (currentUser?.plan_name || 'Premium') : 'Trial' }}
              </span>
              <span v-if="!isPremiumUser" class="text-[9px] bg-[#c0ff00]/20 text-[#c0ff00] border border-[#c0ff00]/40 px-1 py-0.2 rounded font-extrabold group-hover:bg-[#c0ff00] group-hover:text-black transition-all">Pilih Paket ⚡</span>
            </div>
          </div>
        </div>
        <!-- Tombol Logout -->
        <button
          @click="logout"
          :title="'Keluar Akun'"
          :class="[
            'bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/35 text-rose-300 hover:text-rose-200 rounded-xl font-bold transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 shrink-0 cursor-pointer shadow-sm', 
            (sidebarExpanded || mobileSidebarOpen) ? 'w-full py-2.5 text-xs' : 'w-9 h-9 text-sm'
          ]"
        >
          <i class="ph-bold ph-sign-out shrink-0 text-base text-rose-400"></i>
          <span v-show="(sidebarExpanded || mobileSidebarOpen)" class="whitespace-nowrap font-bold">Keluar Akun</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main @scroll="handleScroll" class="relative z-10 flex-grow overflow-y-auto overflow-x-hidden max-h-screen flex flex-col w-full" :class="[
      isLoggedIn ? 'lg:ml-[72px]' : '',
      (isLoggedIn && currentTab !== 'home') ? 'p-3 sm:p-5 lg:p-6 lg:pl-6 bg-transparent' : ''
    ]">
      
      <!-- Public Top Navbar (Only visible when Logged Out) -->
      <header v-if="!isLoggedIn" class="w-full flex justify-center pt-3 px-3 sm:pt-4 sm:px-4 shrink-0 z-30 fixed top-0 left-0 right-0">
        <div class="glass-pill rounded-full px-3 sm:px-6 py-2 sm:py-2.5 flex items-center justify-between shadow-[0_8px_32px_rgba(0,0,0,0.5)] w-full sm:w-[90%] max-w-5xl border border-white/15 gap-2">
          <!-- Hamburger hanya di mobile -->
          <button @click="toggleMobileSidebar" class="lg:hidden flex-shrink-0 w-9 h-9 rounded-xl bg-white/10 border border-white/15 text-white flex items-center justify-center hover:bg-white/20 active:scale-95 transition-all cursor-pointer" aria-label="Buka Menu Navigasi">
            <i class="ph-bold ph-list text-lg"></i>
          </button>

          <div id="nav-logo" class="flex items-center gap-2.5 group cursor-pointer select-none flex-shrink-0" @click="goToHomeTop">
            <div class="w-8 h-8 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-base group-hover:rotate-12 transition-all duration-300 shadow-md shadow-[#c0ff00]/20">E</div>
            <span class="font-black text-lg sm:text-xl tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          </div>

          <nav class="hidden lg:flex items-center gap-6 text-xs font-semibold text-white/75">
            <!-- 1. SPP Free -->
            <button @click="scrollToSection('spp')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#c0ff00]/10 text-[#c0ff00] border border-[#c0ff00]/30 hover:bg-[#c0ff00]/20 transition-all font-bold whitespace-nowrap group shadow-[0_0_15px_rgba(192,255,0,0.15)]">
              <i class="ph-bold ph-compass text-sm"></i>
              <span>Tes Potensi</span>
              <span class="text-[9px] bg-[#c0ff00] text-black px-1.5 py-0.5 rounded-full font-black uppercase">Free</span>
            </button>

            <!-- 2. Fitur -->
            <button @click="scrollToSection('features')" class="hover:text-[#c0ff00] transition-colors py-1 whitespace-nowrap">
              Fitur
            </button>

            <!-- 3. Tools AI Dropdown -->
            <div class="relative py-1" @mouseenter="toolsDropdownOpen = true" @mouseleave="toolsDropdownOpen = false">
              <button 
                @click="toolsDropdownOpen = !toolsDropdownOpen" 
                class="flex items-center gap-1 hover:text-[#c0ff00] transition-colors whitespace-nowrap"
                :class="toolsDropdownOpen ? 'text-[#c0ff00]' : 'text-white/75'"
              >
                <span>Tools AI</span>
                <i class="ph-bold ph-caret-down text-[10px] transition-transform duration-200" :class="toolsDropdownOpen ? 'rotate-180 text-[#c0ff00]' : ''"></i>
              </button>

              <!-- Dropdown Menu -->
              <transition name="fade">
                <div 
                  v-show="toolsDropdownOpen" 
                  class="absolute top-full left-1/2 -translate-x-1/2 pt-2 w-56 z-50 animate-fade-in"
                >
                  <div class="rounded-2xl p-2 bg-[#0c121e]/95 backdrop-blur-xl border border-white/15 shadow-[0_20px_50px_rgba(0,0,0,0.7)] space-y-1">
                    <button 
                      @click="scrollToSection('calculator'); toolsDropdownOpen = false" 
                      class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-left hover:bg-white/10 text-white/80 hover:text-white transition-all group"
                    >
                      <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="ph-bold ph-calculator text-sm"></i>
                      </span>
                      <div>
                        <div class="text-xs font-bold text-white leading-tight">Kalkulator AI</div>
                        <div class="text-[10px] text-white/40">Hitung peluang lolos PTN</div>
                      </div>
                    </button>

                    <button 
                      @click="scrollToSection('quiz-demo'); toolsDropdownOpen = false" 
                      class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-left hover:bg-white/10 text-white/80 hover:text-white transition-all group"
                    >
                      <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="ph-bold ph-lightning text-sm"></i>
                      </span>
                      <div>
                        <div class="text-xs font-bold text-white leading-tight">Kuis HOTS</div>
                        <div class="text-[10px] text-white/40">Uji coba penalaran IRT</div>
                      </div>
                    </button>

                    <button 
                      @click="scrollToSection('comparison'); toolsDropdownOpen = false" 
                      class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-left hover:bg-white/10 text-white/80 hover:text-white transition-all group"
                    >
                      <span class="w-7 h-7 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="ph-bold ph-scales text-sm"></i>
                      </span>
                      <div>
                        <div class="text-xs font-bold text-white leading-tight">Komparasi</div>
                        <div class="text-[10px] text-white/40">EduPath vs Bimbel Tradisional</div>
                      </div>
                    </button>
                  </div>
                </div>
              </transition>
            </div>

            <!-- 4. Testimoni -->
            <button @click="scrollToSection('testimonials')" class="hover:text-[#c0ff00] transition-colors py-1 whitespace-nowrap">
              Testimoni
            </button>

            <!-- 5. Harga -->
            <button @click="scrollToSection('pricing')" class="hover:text-[#c0ff00] transition-colors py-1 whitespace-nowrap">
              Harga
            </button>

            <!-- 6. Afiliasi -->
            <button @click="scrollToSection('affiliate-section')" class="hover:text-amber-300 transition-colors py-1 flex items-center gap-1 whitespace-nowrap text-amber-400 font-bold">
              <span>Afiliasi</span>
              <i class="ph-bold ph-arrow-up-right text-[10px]"></i>
            </button>
          </nav>

          <div class="flex items-center gap-2">
            <button @click="showLoginModal = true" class="h-9 px-4 sm:px-5 rounded-full border border-white/20 bg-white/5 hover:bg-white/10 text-white font-bold text-xs transition-all flex items-center justify-center hover:scale-105 active:scale-95 whitespace-nowrap flex-shrink-0 cursor-pointer">
              Masuk
            </button>
          </div>
        </div>

        <!-- Public Mobile Navigation Drawer (Logged Out) -->
        <transition name="fade">
          <div 
            v-if="!isLoggedIn && mobileSidebarOpen"
            class="fixed inset-0 z-50 lg:hidden flex flex-col bg-[#070b14]/98 backdrop-blur-2xl p-5 overflow-y-auto"
          >
            <!-- Top Bar: Logo & Close Button -->
            <div class="flex items-center justify-between pb-4 border-b border-white/10 shrink-0">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-base shadow-md shadow-[#c0ff00]/20">E</div>
                <span class="font-black text-lg tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
              </div>
              <button 
                @click="mobileSidebarOpen = false" 
                class="w-9 h-9 rounded-xl bg-white/10 border border-white/15 text-white flex items-center justify-center hover:bg-white/20 active:scale-95 transition-all cursor-pointer"
                aria-label="Tutup Menu"
              >
                <i class="ph-bold ph-x text-lg"></i>
              </button>
            </div>

            <!-- Navigation Links -->
            <div class="py-5 space-y-2 flex-grow">
              <div class="text-[10px] uppercase tracking-wider font-extrabold text-white/40 px-2 pb-1">Navigasi Utama</div>
              
              <button 
                @click="scrollToSection('spp'); mobileSidebarOpen = false" 
                class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-white font-extrabold text-sm hover:bg-[#c0ff00]/20 transition-all text-left cursor-pointer"
              >
                <span class="flex items-center gap-3">
                  <span class="text-base">🎯</span>
                  <span>Tes Potensi Siswa (SPP)</span>
                </span>
                <span class="text-[10px] font-black bg-[#c0ff00] text-black px-2 py-0.5 rounded-full uppercase">Free</span>
              </button>

              <button 
                @click="scrollToSection('calculator'); mobileSidebarOpen = false" 
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all text-left cursor-pointer"
              >
                <span class="text-base">🏛️</span>
                <span>Kalkulator Peluang PTN AI</span>
              </button>

              <button 
                @click="scrollToSection('quiz-demo'); mobileSidebarOpen = false" 
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all text-left cursor-pointer"
              >
                <span class="text-base">🧪</span>
                <span>Mini Kuis HOTS (IRT)</span>
              </button>

              <button 
                @click="scrollToSection('comparison'); mobileSidebarOpen = false" 
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all text-left cursor-pointer"
              >
                <span class="text-base">⚖️</span>
                <span>Komparasi EduPath vs Bimbel</span>
              </button>

              <button 
                @click="scrollToSection('features'); mobileSidebarOpen = false" 
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all text-left cursor-pointer"
              >
                <span class="text-base">⚡</span>
                <span>Fitur Platform</span>
              </button>

              <button 
                @click="scrollToSection('testimonials'); mobileSidebarOpen = false" 
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all text-left cursor-pointer"
              >
                <span class="text-base">💬</span>
                <span>Testimoni Pengguna</span>
              </button>

              <button 
                @click="scrollToSection('pricing'); mobileSidebarOpen = false" 
                class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all text-left cursor-pointer"
              >
                <span class="flex items-center gap-3">
                  <span class="text-base">💎</span>
                  <span>Pilihan Paket &amp; Investasi</span>
                </span>
                <span class="text-[10px] font-black text-[#c0ff00] bg-[#c0ff00]/10 border border-[#c0ff00]/30 px-2 py-0.5 rounded-full">Hemat 60%</span>
              </button>

              <button 
                @click="scrollToSection('affiliate-section'); mobileSidebarOpen = false" 
                class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 font-bold text-sm hover:bg-amber-500/20 transition-all text-left cursor-pointer"
              >
                <span class="flex items-center gap-3">
                  <span class="text-base">🤝</span>
                  <span>Program Afiliasi Mitra</span>
                </span>
                <span class="text-[10px] font-black text-amber-400 bg-amber-500/20 px-2 py-0.5 rounded-full">Komisi 20%</span>
              </button>
            </div>

            <!-- Bottom Action Buttons in Drawer -->
            <div class="pt-4 border-t border-white/10 space-y-3 shrink-0">
              <button 
                @click="showLoginModal = true; isLoginMode = true; mobileSidebarOpen = false" 
                class="w-full py-3.5 rounded-2xl border border-white/20 bg-white/10 text-white font-extrabold text-sm flex items-center justify-center gap-2 hover:bg-white/20 active:scale-95 transition-all cursor-pointer"
              >
                <i class="ph-bold ph-sign-in text-base"></i>
                <span>Masuk ke Akun</span>
              </button>

              <button 
                @click="showLoginModal = true; isLoginMode = false; mobileSidebarOpen = false" 
                class="w-full py-3.5 rounded-2xl font-black text-sm text-black flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(192,255,0,0.3)] active:scale-95 transition-all cursor-pointer"
                style="background: #c0ff00;"
              >
                <span>Daftar Uji Coba Gratis ⚡</span>
              </button>
            </div>
          </div>
        </transition>
      </header>

      <!-- App Header Utility (Only visible on Mobile when Logged In) -->
      <header v-if="isLoggedIn" class="lg:hidden flex justify-between items-center px-3.5 sm:px-5 py-3 border-b border-white/10 mb-4 shrink-0 glass-card -mx-3 -mt-3 sm:-mx-5 sm:-mt-5 rounded-b-2xl z-20 backdrop-blur-xl">
        <div class="flex items-center gap-2.5">
          <!-- Mobile Hamburger untuk Menu Siswa -->
          <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 text-white flex items-center justify-center hover:bg-white/10 active:scale-95 transition-all cursor-pointer" aria-label="Buka Menu Navigasi Siswa">
            <i class="ph-bold ph-list text-lg"></i>
          </button>
          <div class="flex items-center gap-1.5 cursor-pointer" @click="handleTabClick('dashboard')">
            <span class="font-black text-sm text-white tracking-tight">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl border border-white/10 bg-white/5 text-xs font-bold text-white shadow-sm">
            <span class="text-purple-400 font-black">🔥 {{ streakCount }}d</span>
          </div>
          <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl border border-white/10 bg-white/5 text-xs font-bold text-white shadow-sm">
            <span class="text-amber-400 font-black">🪙 {{ coins }}</span>
          </div>
          <!-- Avatar Quick Button to open Student Sidebar -->
          <button 
            @click="mobileSidebarOpen = true" 
            class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#c0ff00] to-emerald-400 text-black flex items-center justify-center font-black text-xs border border-[#c0ff00]/40 shadow-sm active:scale-95 transition-all cursor-pointer"
            title="Buka Menu Akun Siswa"
            aria-label="Menu Akun Siswa"
          >
            <i class="ph-bold ph-user text-sm text-black"></i>
          </button>
        </div>
      </header>


      <!-- Main Panel content wrap -->
      <div class="flex-grow relative">
        <!-- TAB 0: LANDING PAGE -->
        <section v-if="currentTab === 'home'" id="landing-top" class="animate-fade-in pt-20 md:pt-24 pb-16 relative w-full">
          
          <!-- Hero Section -->
          <div class="hero-bg -mt-[80px] md:-mt-[96px] min-h-[100svh] md:min-h-[115vh] flex flex-col justify-end pt-[150px] sm:pt-[200px] md:pt-[280px] lg:pt-[380px] pb-20 md:pb-32 relative z-0 overflow-hidden">
            <!-- Inner container: TERIKAT ke viewport -->
            <div class="w-full max-w-4xl mx-auto px-5 sm:px-8 text-center space-y-4" style="box-sizing: border-box;">

              <!-- Punchy Headline -->
              <h1 class="font-black font-heading leading-[1.1] text-white tracking-tight flex flex-col justify-center"
                  style="font-size: clamp(1.8rem, 7vw, 3.75rem); min-height: clamp(90px, 20vw, 140px);">
                <span>Tembus PTN Impian</span>
                <transition name="fade-word" mode="out-in">
                  <span :key="currentWordIdx" style="display: block; word-break: break-word;">
                    dengan <span style="color: #c0ff00; font-style: italic;">{{ rotatingWords[currentWordIdx] }}</span>
                  </span>
                </transition>
              </h1>

              <p class="text-sm sm:text-base md:text-lg text-white/70 mx-auto font-medium leading-relaxed"
                 style="max-width: min(36rem, calc(100vw - 2.5rem));">
                EduPath membantu siswa menemukan kelemahan belajar, menyusun jalur belajar adaptif, dan meningkatkan kesiapan menghadapi SNBT secara terukur.
              </p>

              <!-- Countdown Timer -->
              <div class="pt-1 flex justify-center">
                <div class="inline-flex flex-wrap items-center justify-center gap-1.5 px-3 py-2 rounded-full border border-white/15 bg-black/50 backdrop-blur-md shadow-lg"
                     style="max-width: calc(100vw - 2.5rem);">
                  <span class="flex items-center gap-1 text-white/70 font-bold uppercase tracking-wider"
                        style="font-size: clamp(8px, 2.5vw, 11px);">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#c0ff00] animate-pulse"></span>
                    UTBK 2027
                  </span>
                  <span class="text-white/20">•</span>
                  <div class="flex items-center gap-1 font-mono"
                       style="font-size: clamp(10px, 3vw, 13px);">
                    <span class="px-1.5 py-0.5 rounded-md bg-white/5 border border-white/10 font-bold text-white flex items-baseline">
                      <span class="font-black">{{ countdownDays }}</span>
                      <span class="text-white/50 font-bold ml-0.5" style="font-size: 9px;">h</span>
                    </span>
                    <span class="px-1.5 py-0.5 rounded-md bg-white/5 border border-white/10 font-bold text-white flex items-baseline">
                      <span class="font-black">{{ countdownHours.toString().padStart(2, '0') }}</span>
                      <span class="text-white/50 font-bold ml-0.5" style="font-size: 9px;">j</span>
                    </span>
                    <span class="px-1.5 py-0.5 rounded-md bg-white/5 border border-white/10 font-bold text-white flex items-baseline">
                      <span class="font-black">{{ countdownMinutes.toString().padStart(2, '0') }}</span>
                      <span class="text-white/50 font-bold ml-0.5" style="font-size: 9px;">m</span>
                    </span>
                    <span class="px-1.5 py-0.5 rounded-md bg-[#c0ff00]/10 border border-[#c0ff00]/30 font-bold text-[#c0ff00] flex items-baseline">
                      <span class="font-black">{{ countdownSeconds.toString().padStart(2, '0') }}</span>
                      <span class="text-[#c0ff00]/70 font-bold ml-0.5" style="font-size: 9px;">d</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- CTA Buttons -->
              <div class="flex flex-col sm:flex-row justify-center items-center gap-3 pt-6 sm:pt-10 md:pt-14 relative z-20 w-full max-w-sm sm:max-w-none mx-auto px-4 sm:px-0">
                <button class="w-full sm:w-auto h-11 sm:h-12 px-6 sm:px-8 rounded-xl sm:rounded-2xl border border-[#c0ff00]/60 bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-sm sm:text-base transition-all flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(192,255,0,0.35)] hover:scale-105 active:scale-95 cursor-pointer" @click="startLearning">
                  <span>Mulai Belajar Sekarang</span>
                  <i class="ph-bold ph-arrow-right text-base sm:text-lg"></i>
                </button>
                <button class="w-full sm:w-auto h-11 sm:h-12 px-5 sm:px-6 rounded-xl sm:rounded-2xl border border-white/20 bg-white/5 hover:bg-white/10 text-white font-bold text-sm sm:text-base transition-all flex items-center justify-center gap-2 hover:scale-105 active:scale-95 cursor-pointer" @click="scrollToSection('calculator')">
                  <i class="ph-bold ph-calculator text-base sm:text-lg"></i>
                  <span>Hitung Peluang PTN</span>
                </button>
              </div>

              <div class="text-xs sm:text-sm text-slate-300 font-medium pb-2 text-center">
                Untuk Siswa SMA &amp; Pejuang Gap Year yang serius meraih PTN impian.
              </div>
            </div>
          </div>

          <!-- Trust Bar — Premium Ticker (Mobile-safe) -->
          <div class="w-full relative -mt-4 sm:-mt-14 md:-mt-20 my-8 sm:my-14 z-10 overflow-hidden" style="transform: rotate(-2deg) scale(1.05);">
            <div class="bg-[#c0ff00] text-black py-2.5 sm:py-3 border-y border-[#c0ff00]/30 shadow-[0_10px_40px_rgba(192,255,0,0.15)] overflow-hidden">
              <div class="flex gap-0 animate-marquee whitespace-nowrap" style="width: max-content;">
                <div class="flex gap-12 items-center px-8 font-black text-sm md:text-base uppercase tracking-widest shrink-0">
                  <span>Belajar Lebih Terarah</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>Temukan Blind Spot-mu</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>Latihan Soal Terstruktur</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>Pantau Progresmu</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>AI Adaptive Path™</span>
                  <span class="text-xl opacity-40">✦</span>
                </div>
                <!-- Duplicate for seamless loop -->
                <div class="flex gap-12 items-center px-8 font-black text-sm md:text-base uppercase tracking-widest shrink-0" aria-hidden="true">
                  <span>Belajar Lebih Terarah</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>Temukan Blind Spot-mu</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>Latihan Soal Terstruktur</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>Pantau Progresmu</span>
                  <span class="text-xl opacity-40">✦</span>
                  <span>AI Adaptive Path™</span>
                  <span class="text-xl opacity-40">✦</span>
                </div>
              </div>
            </div>
          </div>



          <div id="problems" class="section-wrapper space-y-20 scroll-mt-24">
            <!-- Section Heading -->
            <div class="text-center max-w-3xl mx-auto space-y-4 reveal px-2">
              <span class="text-[#c0ff00] text-xs sm:text-sm font-black uppercase tracking-widest block">TANTANGAN NYATA</span>
              <h2 class="text-3xl sm:text-5xl md:text-7xl font-black font-heading text-white leading-tight">
                Belajar Keras<br />Tapi <span style="color: #c0ff00;">Tanpa Arah?</span>
              </h2>
              <p class="text-sm sm:text-lg text-slate-200 font-medium leading-relaxed">
                Banyak pejuang PTN belajar penuh semangat, tapi tanpa peta yang jelas. Inilah 3 jebakan paling umum.
              </p>
            </div>

            <!-- 3 Cards — Ukuran panjang dan lebar sama persis -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative items-stretch">
              <!-- Card 1 -->
              <div class="reveal reveal-delay-1 h-full">
                <div class="bouncy-card group rounded-3xl p-6 sm:p-8 pt-14 sm:pt-16 relative hover:-translate-y-4 hover:scale-[1.03] hover:border-[#c0ff00]/50 hover:shadow-[0_20px_50px_-10px_rgba(192,255,0,0.2)] hover:z-20 transition-spring h-full flex flex-col justify-between">
                  <div class="absolute -top-7 left-6 sm:left-8 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-2xl sm:text-3xl shadow-[0_0_30px_rgba(192,255,0,0.4)]">01</div>
                  <div class="space-y-3 flex-grow">
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-black text-white">Blind Spot Belajar</h3>
                    <p class="text-slate-300 font-normal leading-relaxed text-sm sm:text-base">Belajar acak tanpa tahu materi mana yang paling nahan kenaikan skormu. Gak terarah, gak efisien.</p>
                  </div>
                </div>
              </div>

              <!-- Card 2 -->
              <div class="reveal reveal-delay-2 h-full">
                <div class="bouncy-card group rounded-3xl p-6 sm:p-8 pt-14 sm:pt-16 relative hover:-translate-y-4 hover:scale-[1.03] hover:border-[#c0ff00]/50 hover:shadow-[0_20px_50px_-10px_rgba(192,255,0,0.2)] hover:z-20 transition-spring h-full flex flex-col justify-between">
                  <div class="absolute -top-7 left-6 sm:left-8 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-white flex items-center justify-center font-black text-black text-2xl sm:text-3xl">02</div>
                  <div class="space-y-3 flex-grow">
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-black text-white">Hafalan Buta Rumus</h3>
                    <p class="text-slate-300 font-normal leading-relaxed text-sm sm:text-base">Hafal rumus mati tanpa ngerti logikanya. Pas ketemu soal HOTS variasi baru — blank total.</p>
                  </div>
                </div>
              </div>

              <!-- Card 3 -->
              <div class="reveal reveal-delay-3 h-full">
                <div class="bouncy-card group rounded-3xl p-6 sm:p-8 pt-14 sm:pt-16 relative hover:-translate-y-4 hover:scale-[1.03] hover:border-[#c0ff00]/50 hover:shadow-[0_20px_50px_-10px_rgba(192,255,0,0.2)] hover:z-20 transition-spring h-full flex flex-col justify-between">
                  <div class="absolute -top-7 left-6 sm:left-8 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-2xl sm:text-3xl shadow-[0_0_30px_rgba(192,255,0,0.4)]">03</div>
                  <div class="space-y-3 flex-grow">
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-black text-white">Skor Tryout Mandek</h3>
                    <p class="text-slate-300 font-normal leading-relaxed text-sm sm:text-base">Latihan terlalu mudah atau susah tanpa kalibrasi IRT. Skor stuck di angka yang sama berbulan-bulan.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>



          <!-- STUDENT POTENTIAL PATH (SPP) SECTION -->
          <div class="section-wrapper">
            <StudentPotentialPath @take-readiness="handleTakeReadinessFromSpp" />
          </div>

          <!-- 3. Dynamic Section: KALKULATOR PREDIKSI SKOR & PELUANG LOLOS PTN AI -->
          <div id="calculator" class="section-wrapper space-y-12 scroll-mt-24 relative">
            <div class="text-center max-w-3xl mx-auto space-y-3 reveal px-2">
              <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border border-[#c0ff00]/30 bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black uppercase tracking-widest">
                🎯 SIMULASI INTERAKTIF AI
              </span>
              <h2 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black font-heading text-white leading-tight break-words">Hitung Peluang <span style="color: #c0ff00;">Lolos PTN-mu</span></h2>
              <p class="text-sm md:text-base text-white/60 font-medium max-w-2xl mx-auto leading-relaxed">
                Geser slider sesuai kondisimu saat ini. Sistem AI EduPath akan mengkalkulasi proyeksi lonjakan nilai dan peluang kelulusanmu secara real-time!
              </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-stretch max-w-5xl mx-auto">
              
              <!-- Left: Inputs & Sliders (7 cols) -->
              <div class="lg:col-span-7 glass-card rounded-3xl p-5 sm:p-7 md:p-8 space-y-6 sm:space-y-8 relative overflow-hidden border-white/10">
                <div class="space-y-3.5 sm:space-y-4">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <label class="text-xs font-black uppercase tracking-wider text-white/70 flex items-center gap-1.5">
                      <span>🏛️</span> Target Universitas &amp; Jurusan:
                    </label>
                    <span class="inline-flex items-center self-start sm:self-auto text-xs font-bold text-[#c0ff00] bg-[#c0ff00]/10 px-3 py-1 rounded-xl border border-[#c0ff00]/30 font-mono whitespace-nowrap">
                      Target: {{ simTargetPtn.targetScore }} Poin
                    </span>
                  </div>
                  
                  <!-- Dropdown Selector for all 50 PTNs -->
                  <div class="relative">
                    <select
                      v-model="simTargetPtn"
                      class="w-full bg-slate-900/90 border border-white/20 text-white text-xs md:text-sm font-bold py-3 px-3.5 pr-8 rounded-xl appearance-none outline-none focus:border-[#c0ff00] transition-colors cursor-pointer shadow-inner"
                    >
                      <option v-for="ptn in targetPtnList" :key="ptn.id" :value="ptn" class="bg-slate-900 text-white font-medium py-1">
                        ⭐ [{{ ptn.targetScore }} Poin] {{ ptn.name }}
                      </option>
                    </select>
                    <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#c0ff00] text-xs">
                      <i class="ph-bold ph-caret-down"></i>
                    </div>
                  </div>

                  <!-- Quick-Pick Tabs for Top 8 High Score PTNs (Mobile-optimized horizontal scroll / desktop grid) -->
                  <div class="space-y-2 pt-1">
                    <div class="text-[10px] uppercase tracking-wider font-bold text-white/40 flex items-center justify-between">
                      <span>Pilihan Cepat Terpopuler:</span>
                      <span class="text-[9px] text-[#c0ff00]">50 Lengkap di Dropdown ↑</span>
                    </div>
                    <div class="flex sm:grid sm:grid-cols-4 gap-2 overflow-x-auto pb-1.5 sm:pb-0 no-scrollbar">
                      <button
                        v-for="ptn in targetPtnList.slice(0, 8)"
                        :key="ptn.id"
                        type="button"
                        :class="['p-2.5 rounded-xl text-xs font-bold text-left transition-colors duration-150 border flex flex-col justify-between shrink-0 min-w-[130px] sm:min-w-0 h-[60px] sm:h-[68px] cursor-pointer',
                          simTargetPtn.id === ptn.id
                            ? 'bg-[#c0ff00] text-black border-[#c0ff00] shadow-[0_0_15px_rgba(192,255,0,0.3)]'
                            : 'bg-white/5 text-white/70 border-white/10 hover:border-white/30 hover:bg-white/10'
                        ]"
                        @click="simTargetPtn = ptn"
                      >
                        <span class="truncate font-black text-[11px]">{{ ptn.name.split(' (')[0] }}</span>
                        <span :class="simTargetPtn.id === ptn.id ? 'text-black/80 font-mono text-[10px] font-bold' : 'text-[#c0ff00] font-mono text-[10px]'">
                          {{ ptn.targetScore }} Poin
                        </span>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Slider 1: Skor Tryout Terakhir -->
                <div class="space-y-3 pt-1">
                  <div class="flex justify-between items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-white/70 flex items-center gap-1.5">
                      <span>📊</span> Skor Tryout Terakhir Kamu:
                    </span>
                    <span class="text-base sm:text-xl font-black font-heading text-white font-mono bg-black/60 px-3 py-1 rounded-xl border border-white/10 shrink-0">
                      {{ simCurrentScore }} <span class="text-[10px] sm:text-xs text-white/40 font-normal">Poin</span>
                    </span>
                  </div>
                  <input
                    type="range"
                    v-model.number="simCurrentScore"
                    min="400"
                    max="750"
                    step="5"
                    class="w-full h-2.5 cursor-pointer"
                  />
                  <div class="flex justify-between text-[10px] sm:text-[11px] font-bold text-white/30">
                    <span>400 (Pemula)</span>
                    <span>550 (Menengah)</span>
                    <span>750 (Master)</span>
                  </div>
                </div>

                <!-- Slider 2: Komitmen Belajar Harian -->
                <div class="space-y-3 pt-1">
                  <div class="flex justify-between items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-white/70 flex items-center gap-1.5">
                      <span>⏱️</span> Komitmen Belajar Harian:
                    </span>
                    <span class="text-base sm:text-xl font-black font-heading text-[#c0ff00] font-mono bg-black/60 px-3 py-1 rounded-xl border border-[#c0ff00]/30 shrink-0">
                      {{ simDailyHours }} <span class="text-[10px] sm:text-xs text-[#c0ff00]/70 font-normal">Jam/Hari</span>
                    </span>
                  </div>
                  <input
                    type="range"
                    v-model.number="simDailyHours"
                    min="1"
                    max="6"
                    step="0.5"
                    class="w-full h-2.5 cursor-pointer"
                  />
                  <div class="flex justify-between text-[10px] sm:text-[11px] font-bold text-white/30">
                    <span>1 Jam (Santai)</span>
                    <span>3 Jam (Disiplin)</span>
                    <span>6 Jam (Ambis)</span>
                  </div>
                </div>
              </div>

              <!-- Right: Realtime Dynamic AI Result Card (5 cols) -->
              <div class="lg:col-span-5 rounded-3xl p-5 sm:p-7 md:p-8 flex flex-col justify-between relative overflow-hidden border border-white/15 bg-gradient-to-b from-[#141414] to-[#0a0a0a] shadow-2xl">
                <div class="absolute -right-12 -top-12 w-44 h-44 rounded-full bg-[#c0ff00]/10 blur-3xl pointer-events-none"></div>

                <div class="space-y-5 sm:space-y-6 relative z-10">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="text-xs font-black tracking-wider uppercase text-white/50">PROYEKSI ANALITIK AI</span>
                    <span class="inline-flex items-center self-start sm:self-auto px-3 py-1 rounded-xl text-xs font-bold border" :class="simChanceStatus.badge">
                      {{ simChanceStatus.text }}
                    </span>
                  </div>

                  <!-- Probability Meter -->
                  <div class="space-y-1.5">
                    <div class="flex justify-between items-end">
                      <span class="text-xs font-bold text-white/60">Peluang Kelulusan:</span>
                      <span class="text-3xl sm:text-4xl font-black font-heading tracking-tighter" :style="{ color: simChanceStatus.color }">
                        {{ simChancePercentage }}%
                      </span>
                    </div>
                    <div class="w-full bg-black/80 rounded-full h-3.5 p-0.5 border border-white/10 overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-500 ease-out shadow-[0_0_15px_rgba(192,255,0,0.5)]"
                        :style="{ width: simChancePercentage + '%', backgroundColor: simChanceStatus.color }"
                      ></div>
                    </div>
                  </div>

                  <!-- Score Calculation Matrix -->
                  <div class="grid grid-cols-2 gap-3 pt-1">
                    <div class="p-3 sm:p-3.5 rounded-2xl bg-white/[0.03] border border-white/10 text-center">
                      <div class="text-[10px] font-bold text-white/40 uppercase">Prediksi Skor AI</div>
                      <div class="text-xl sm:text-2xl font-black text-white font-heading mt-1">{{ simPredictedGain }}</div>
                      <div class="text-[10px] font-bold text-[#c0ff00] mt-0.5">+{{ simScoreIncrease }} Poin Lonjakan</div>
                    </div>
                    <div class="p-3 sm:p-3.5 rounded-2xl bg-white/[0.03] border border-white/10 text-center min-w-0">
                      <div class="text-[10px] font-bold text-white/40 uppercase truncate">Target {{ (simTargetPtn?.name || String(simTargetPtn || '')).split(' (')[0] }}</div>
                      <div class="text-xl sm:text-2xl font-black text-white/80 font-heading mt-1">{{ simTargetPtn?.targetScore || 700 }}</div>
                      <div class="text-[10px] font-bold mt-0.5 truncate" :class="(simPredictedGain >= (simTargetPtn?.targetScore || 700)) ? 'text-[#c0ff00]' : 'text-rose-400'">
                        {{ (simPredictedGain >= (simTargetPtn?.targetScore || 700)) ? '✓ Lolos Passing' : `Sisa ${(simTargetPtn?.targetScore || 700) - simPredictedGain} Poin` }}
                      </div>
                    </div>
                  </div>

                  <!-- AI Subtest Focus Tip -->
                  <div class="p-3.5 sm:p-4 rounded-2xl bg-black/50 border border-white/10 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#c0ff00]">
                      <span>⚡</span> Rekomendasi Subtes Prioritas:
                    </div>
                    <p class="text-xs text-white/70 leading-relaxed font-medium">
                      Fokus pada: <strong class="text-white">{{ simTargetPtn?.focus || 'TPS & Literasi' }}</strong> untuk akselerasi skor maksimal di {{ (simTargetPtn?.name || String(simTargetPtn || '')).split(' (')[0] }}.
                    </p>
                  </div>
                </div>

                <div class="pt-5 sm:pt-6 relative z-10">
                  <button
                    class="w-full min-h-[50px] py-3.5 sm:py-4 rounded-2xl font-black text-sm text-black transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(192,255,0,0.3)] cursor-pointer"
                    style="background: #c0ff00;"
                    @click="startLearning"
                  >
                    <span>Kunci Target &amp; Buat Jadwal AI</span>
                    <i class="ph-bold ph-arrow-right"></i>
                  </button>
                </div>
              </div>

            </div>
          </div>

          <!-- 4. Dynamic Section: MINI KUIS HOTS INTERAKTIF LANGSUNG DI LANDING PAGE -->
          <div id="quiz-demo" class="section-wrapper space-y-12 scroll-mt-24 relative">
            <div class="text-center max-w-3xl mx-auto space-y-3 reveal px-2">
              <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border border-sky-500/30 bg-sky-500/10 text-sky-400 text-xs font-black uppercase tracking-widest">
                🧪 UJI DIAGNOSTIK INSTAN
              </span>
              <h2 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black font-heading text-white leading-tight break-words">
                Coba 1 Soal HOTS &amp; <span style="color: #c0ff00;">Lihat Analisa AI</span>
              </h2>
              <p class="text-sm md:text-base text-white/60 font-medium max-w-2xl mx-auto leading-relaxed">
                Buktikan sendiri bagaimana AI EduPath membongkar jebakan dan rumus cepat 30 detik tanpa registrasi.
              </p>
            </div>

            <div class="max-w-4xl mx-auto glass-card rounded-3xl p-5 sm:p-8 md:p-10 border-white/15 relative overflow-hidden">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-white/10 pb-4 sm:pb-6 mb-4 sm:mb-6">
                <!-- Tabs Question Switcher (Segmented Control on mobile) -->
                <div class="grid grid-cols-3 gap-1.5 p-1 rounded-2xl bg-white/[0.06] border border-white/10 w-full sm:w-auto">
                  <button
                    v-for="(q, idx) in miniQuizQuestions"
                    :key="idx"
                    :class="['px-2.5 sm:px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                      miniQuizActiveIdx === idx
                        ? 'bg-[#c0ff00] text-black shadow-[0_0_15px_rgba(192,255,0,0.3)]'
                        : 'text-white/60 hover:text-white hover:bg-white/10'
                    ]"
                    @click="miniQuizActiveIdx = idx; miniQuizUserAnswer = null; miniQuizShowExplanation = false;"
                  >
                    <span class="text-xs sm:text-sm">{{ q.icon }}</span>
                    <span class="truncate">Soal {{ idx + 1 }}</span>
                  </button>
                </div>

                <div class="inline-flex items-center gap-1.5 self-start sm:self-auto px-2.5 py-1 rounded-xl bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-mono font-bold whitespace-nowrap">
                  <span>✦</span> Bobot Skor IRT: <strong class="text-white">+{{ miniQuizQuestions[miniQuizActiveIdx].irtScore }} Poin</strong>
                </div>
              </div>

              <!-- Active Question -->
              <div class="space-y-5 sm:space-y-6">
                <div class="space-y-1.5 sm:space-y-2">
                  <span class="text-xs font-black uppercase tracking-wider text-white/40 block">PERTANYAAN #{{ miniQuizActiveIdx + 1 }}: {{ miniQuizQuestions[miniQuizActiveIdx].category }}</span>
                  <h3 class="text-base sm:text-xl md:text-2xl font-bold text-white leading-relaxed">
                    {{ miniQuizQuestions[miniQuizActiveIdx].question }}
                  </h3>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                  <button
                    v-for="(opt, oIdx) in miniQuizQuestions[miniQuizActiveIdx].options"
                    :key="oIdx"
                    :disabled="miniQuizShowExplanation"
                    :class="['min-h-[50px] p-3.5 sm:p-4 rounded-2xl text-left text-xs sm:text-sm font-semibold transition-all border flex items-center justify-between group cursor-pointer active:scale-98',
                      miniQuizUserAnswer === oIdx
                        ? (opt.isCorrect 
                            ? 'bg-emerald-500/20 border-emerald-400 text-emerald-300 shadow-[0_0_20px_rgba(16,185,129,0.3)]'
                            : 'bg-rose-500/20 border-rose-400 text-rose-300 shadow-[0_0_20px_rgba(244,63,94,0.3)]')
                        : (miniQuizShowExplanation && opt.isCorrect
                            ? 'bg-emerald-500/20 border-emerald-400 text-emerald-300'
                            : 'bg-white/5 border-white/10 hover:border-[#c0ff00]/50 hover:bg-white/10 text-white')
                    ]"
                    @click="selectMiniQuizOption(oIdx)"
                  >
                    <span class="flex-grow pr-2">{{ opt.text }}</span>
                    <span v-if="miniQuizUserAnswer === oIdx" class="text-base sm:text-lg shrink-0">
                      {{ opt.isCorrect ? '✅' : '❌' }}
                    </span>
                  </button>
                </div>

                <!-- AI Breakdown Card (Shows up after answering) -->
                <div v-if="miniQuizShowExplanation" class="mt-4 sm:mt-6 p-4 sm:p-6 rounded-2xl bg-black/60 border border-[#c0ff00]/30 space-y-3.5 sm:space-y-4 animate-fade-in">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-full bg-[#c0ff00] text-black flex items-center justify-center font-black text-xs">AI</div>
                      <span class="text-xs sm:text-sm font-black text-white">Analisis Logika &amp; Trik AI EduPath</span>
                    </div>
                    <span class="text-xs font-bold self-start sm:self-auto" :class="miniQuizQuestions[miniQuizActiveIdx].options[miniQuizUserAnswer]?.isCorrect ? 'text-[#c0ff00]' : 'text-amber-400'">
                      {{ miniQuizQuestions[miniQuizActiveIdx].options[miniQuizUserAnswer]?.isCorrect ? '🎉 Jawaban Tepat! +Bobot IRT Tercapai' : '💡 Belum Tepat! Simak Trik Cepat Berikut:' }}
                    </span>
                  </div>

                  <div class="space-y-3 text-xs md:text-sm text-white/80 leading-relaxed">
                    <p><strong>📖 Pembahasan Konseptual:</strong> {{ miniQuizQuestions[miniQuizActiveIdx].explanation }}</p>
                    <div class="p-3 sm:p-3.5 rounded-xl bg-[#c0ff00]/10 border border-[#c0ff00]/20 text-[#c0ff00] font-medium flex items-start gap-2.5">
                      <span class="text-base shrink-0">⚡</span>
                      <span><strong>Trik Kilat EduPath:</strong> {{ miniQuizQuestions[miniQuizActiveIdx].trick }}</span>
                    </div>
                  </div>

                  <div class="flex justify-end pt-2">
                    <button
                      class="px-5 py-3 rounded-xl bg-white text-black font-black text-xs hover:bg-[#c0ff00] transition-all flex items-center gap-2 cursor-pointer shadow-md"
                      @click="nextMiniQuiz"
                    >
                      <span>Coba Soal Berikutnya</span>
                      <i class="ph-bold ph-arrow-right"></i>
                    </button>
                  </div>
                </div>

                <div v-else class="text-center text-xs text-white/40 pt-2 font-medium">
                  👆 Klik salah satu pilihan jawaban di atas untuk melihat respon instan AI!
                </div>
              </div>
            </div>
          </div>

          <!-- 5. Dynamic Section: HEAD-TO-HEAD COMPARISON MATRIX -->
          <div id="comparison" class="section-wrapper space-y-12 scroll-mt-24 relative">
            <div class="text-center max-w-4xl mx-auto space-y-3 reveal px-2">
              <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border border-purple-500/30 bg-purple-500/10 text-purple-400 text-xs font-black uppercase tracking-widest">
                ⚖️ PERBANDINGAN STRATEGIS
              </span>
              <h2 class="text-2xl sm:text-3xl md:text-5xl font-black font-heading text-white leading-snug">
                Bimbel Biasa vs <span class="text-sky-400">Aplikasi Video Massal</span> vs <span style="color: #c0ff00;">EduPath.ai</span>
              </h2>
              <p class="text-sm md:text-base text-white/60 font-medium max-w-3xl mx-auto leading-relaxed">
                Bandingkan secara objektif antara bimbel tatap muka konvensional, aplikasi video rekaman satu arah, dan teknologi Adaptive Learning Path EduPath.
              </p>
            </div>

            <!-- 3-Way Head-to-Head Cards -->
            <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
              
              <!-- Card 1: Bimbel Konvensional -->
              <div class="glass-card rounded-3xl p-5 sm:p-7 md:p-8 space-y-6 border-rose-500/25 bg-rose-950/[0.08] hover:border-rose-500/40 transition-all flex flex-col justify-between">
                <div class="space-y-5">
                  <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                      <span class="text-[11px] font-black text-rose-400 uppercase tracking-wider block">METODE LAMA 🏫</span>
                      <h3 class="text-lg sm:text-xl md:text-2xl font-black text-white">Bimbel Konvensional</h3>
                      <p class="text-[11px] text-white/40 font-medium">Tatap Muka &amp; Kelas Massal</p>
                    </div>
                    <div class="text-3xl shrink-0">❌</div>
                  </div>

                  <ul class="space-y-3.5 text-xs md:text-sm text-white/70">
                    <li class="flex items-start gap-2.5">
                      <span class="text-rose-400 font-bold shrink-0 mt-0.5">✕</span>
                      <span><strong>1 Buku untuk Semua:</strong> Siswa beda kemampuan dipaksa belajar bab yang sama di kelas ramai 20–30 anak.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-rose-400 font-bold shrink-0 mt-0.5">✕</span>
                      <span><strong>Buang Waktu 2–3 Jam/Hari:</strong> Terjebak macet dan duduk pasif menyimak materi yang sudah dikuasai.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-rose-400 font-bold shrink-0 mt-0.5">✕</span>
                      <span><strong>Hafalan Rumus Cepat:</strong> Rawan panik &amp; blank begitu variasi soal nalar HOTS baru muncul di SNBT.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-rose-400 font-bold shrink-0 mt-0.5">✕</span>
                      <span><strong>Konsultasi Guru Terbatas:</strong> Harus antre nomor atau menunggu jadwal kelas tatap muka pekan depan.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-rose-400 font-bold shrink-0 mt-0.5">✕</span>
                      <span><strong>Biaya Selangit:</strong> Rp 8.000.000 – Rp 25.000.000 per tahun ajaran.</span>
                    </li>
                  </ul>
                </div>

                <div class="pt-4 border-t border-white/10 text-center">
                  <span class="inline-block text-[11px] font-bold text-rose-400/80 bg-rose-500/10 px-3 py-1 rounded-full border border-rose-500/20">
                    Kurikulum seragam &amp; tidak adaptif
                  </span>
                </div>
              </div>

              <!-- Card 2: Aplikasi Video Massal (Bimbel Video Online) -->
              <div class="glass-card rounded-3xl p-5 sm:p-7 md:p-8 space-y-6 border-sky-500/30 bg-sky-950/[0.12] hover:border-sky-500/50 transition-all flex flex-col justify-between">
                <div class="space-y-5">
                  <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                      <span class="text-[11px] font-black text-sky-400 uppercase tracking-wider block">VIDEO ON-DEMAND 📱</span>
                      <h3 class="text-lg sm:text-xl md:text-2xl font-black text-white">Aplikasi Video Massal</h3>
                      <p class="text-[11px] text-sky-400/70 font-medium">Bimbel Online &amp; Video Rekaman</p>
                    </div>
                    <div class="text-3xl shrink-0">📺</div>
                  </div>

                  <ul class="space-y-3.5 text-xs md:text-sm text-white/70">
                    <li class="flex items-start gap-2.5">
                      <span class="text-sky-400 font-bold shrink-0 mt-0.5">⚠️</span>
                      <span><strong>Ribuan Video Satu Arah:</strong> Materi berlimpah tapi pasif, memicu <em>information overload</em> &amp; bingung mulai dari mana.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-sky-400 font-bold shrink-0 mt-0.5">⚠️</span>
                      <span><strong>Durasi Panjang (15–30 Mnt):</strong> Waktu habis menatap video rekaman, bukan drill aktif eliminasi opsi HOTS.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-sky-400 font-bold shrink-0 mt-0.5">⚠️</span>
                      <span><strong>Tanya Soal Terbatas:</strong> Fitur bot tanya soal umum sering berupa teks template dan respons terbatas.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-sky-400 font-bold shrink-0 mt-0.5">⚠️</span>
                      <span><strong>Bukan Real-Time Adaptive IRT:</strong> Rekomendasi belajar modul umum, belum memetakan probabilitas lolos PTN per butir soal.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-sky-400 font-bold shrink-0 mt-0.5">⚠️</span>
                      <span><strong>Komitmen Paket di Depan:</strong> Rp 1.500.000 – Rp 6.000.000 per tahun (sering wajib bayar paket panjang).</span>
                    </li>
                  </ul>
                </div>

                <div class="pt-4 border-t border-white/10 text-center">
                  <span class="inline-block text-[11px] font-bold text-sky-300/80 bg-sky-500/10 px-3 py-1 rounded-full border border-sky-500/20">
                    Banyak konten pasif, personalisasi terbatas
                  </span>
                </div>
              </div>

              <!-- Card 3: EduPath.ai Adaptive -->
              <div class="rounded-3xl p-5 sm:p-7 md:p-8 space-y-6 border-2 border-[#c0ff00] bg-[#10160a] shadow-[0_0_50px_rgba(192,255,0,0.18)] relative flex flex-col justify-between lg:scale-105 lg:-translate-y-2 z-10">
                <span class="absolute top-0 right-0 bg-[#c0ff00] text-black text-[10px] font-black px-4 py-1.5 rounded-bl-2xl uppercase tracking-wider">
                  Pilihan Cerdas Pejuang PTN ⚡
                </span>

                <div class="space-y-5">
                  <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                      <span class="text-[11px] font-black text-[#c0ff00] uppercase tracking-wider block">TEKNOLOGI AI 2026 🚀</span>
                      <h3 class="text-lg sm:text-xl md:text-2xl font-black text-white">EduPath.ai Adaptive</h3>
                      <p class="text-[11px] text-[#c0ff00]/70 font-medium">Dynamic Learning Path &amp; AI Tutor 24/7</p>
                    </div>
                    <div class="text-3xl shrink-0">⚡</div>
                  </div>

                  <ul class="space-y-3.5 text-xs md:text-sm text-white">
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] font-black shrink-0 mt-0.5">✓</span>
                      <span><strong>Dynamic Learning Path:</strong> AI mendeteksi titik lemah spesifik dalam 10 menit &amp; menyusun materi yang paling menaikkan skor.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] font-black shrink-0 mt-0.5">✓</span>
                      <span><strong>Micro-Lessons 5 Menit:</strong> Belajar padat konsep inti + trik eliminasi instan, efisien tanpa rasa overwhelmed.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] font-black shrink-0 mt-0.5">✓</span>
                      <span><strong>AI Tutor Companion 24/7:</strong> Dialog interaktif Sokratik tanpa batas kuota + opsi SOS Tutor ITB/UI.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] font-black shrink-0 mt-0.5">✓</span>
                      <span><strong>Evaluasi Model IRT Terkalibrasi:</strong> Perhitungan bobot butir soal menggunakan model Item Response Theory yang dikalibrasi untuk simulasi latihan adaptif.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] font-black shrink-0 mt-0.5">✓</span>
                      <span><strong>Investasi Super Terjangkau:</strong> Mulai Rp 119.000 / bulan tanpa biaya tersembunyi &amp; tanpa kontrak menjerat.</span>
                    </li>
                  </ul>
                </div>

                <div class="space-y-3 pt-2">
                  <button
                    class="w-full min-h-[50px] py-3.5 md:py-4 rounded-2xl font-black text-xs md:text-sm text-black transition-all hover:scale-105 active:scale-95 shadow-[0_0_30px_rgba(192,255,0,0.4)] flex items-center justify-center gap-2 cursor-pointer"
                    style="background: #c0ff00;"
                    @click="startLearning"
                  >
                    <span>Mulai Jalur Belajar Adaptif ⚡</span>
                  </button>
                </div>
              </div>

            </div>

            <!-- Detailed Comparison Table Matrix -->
            <div class="max-w-5xl mx-auto glass-card rounded-3xl p-6 md:p-8 border border-white/10 overflow-hidden space-y-6">
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                <div>
                  <h3 class="text-xl md:text-2xl font-black text-white flex items-center gap-2.5">
                    <span>📊</span> Matriks Komparasi Fitur Lengkap
                  </h3>
                  <p class="text-xs md:text-sm text-white/50 mt-1">
                    Bandingkan aspek personalisasi, efisiensi waktu belajar, dukungan tutor, dan biaya secara transparan.
                  </p>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-bold shrink-0">
                  <span>Standar SNBT 2026/2027</span>
                </div>
              </div>

              <div class="overflow-x-auto -mx-2 sm:mx-0">
                <table class="w-full text-left text-xs md:text-sm min-w-[620px]">
                  <thead>
                    <tr class="border-b border-white/10 text-white/60 font-bold uppercase tracking-wider text-[10px] md:text-[11px]">
                      <th class="py-3 px-4 w-[28%]">Fitur &amp; Kemampuan</th>
                      <th class="py-3 px-4 w-[24%] text-rose-400">Bimbel Konvensional</th>
                      <th class="py-3 px-4 w-[24%] text-sky-400">Aplikasi Video Massal</th>
                      <th class="py-3 px-4 w-[24%] text-[#c0ff00] bg-[#c0ff00]/10 rounded-t-xl font-black">EduPath.ai Adaptive</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-white/5">
                    <tr class="hover:bg-white/[0.02] transition-colors">
                      <td class="py-3.5 px-4 font-bold text-white">Personalisasi Rute Belajar</td>
                      <td class="py-3.5 px-4 text-white/60">❌ 1 Buku seragam untuk sekelas</td>
                      <td class="py-3.5 px-4 text-white/60">⚠️ Cari video sendiri di katalog besar</td>
                      <td class="py-3.5 px-4 font-bold text-[#c0ff00] bg-[#c0ff00]/5">✅ AI Adaptive Path otomatis (titik lemah spesifik)</td>
                    </tr>
                    <tr class="hover:bg-white/[0.02] transition-colors">
                      <td class="py-3.5 px-4 font-bold text-white">Format &amp; Efisiensi Waktu</td>
                      <td class="py-3.5 px-4 text-white/60">❌ 90–120 mnt/sesi + macet di jalan</td>
                      <td class="py-3.5 px-4 text-white/60">⚠️ Video rekaman panjang 15–30 mnt</td>
                      <td class="py-3.5 px-4 font-bold text-[#c0ff00] bg-[#c0ff00]/5">✅ Micro-lessons 5 menit padat trik HOTS</td>
                    </tr>
                    <tr class="hover:bg-white/[0.02] transition-colors">
                      <td class="py-3.5 px-4 font-bold text-white">Bantuan Tanya Soal &amp; PR</td>
                      <td class="py-3.5 px-4 text-white/60">❌ Terbatas jam les &amp; antre pengajar</td>
                      <td class="py-3.5 px-4 text-white/60">⚠️ Terbatas kuota koin &amp; teks template</td>
                      <td class="py-3.5 px-4 font-bold text-[#c0ff00] bg-[#c0ff00]/5">✅ AI Tutor 24/7 Tanpa Batas + SOS Mentor UI/ITB</td>
                    </tr>
                    <tr class="hover:bg-white/[0.02] transition-colors">
                      <td class="py-3.5 px-4 font-bold text-white">Akurasi Skoring Tryout</td>
                      <td class="py-3.5 px-4 text-white/60">⚠️ Tryout bulanan skor persentase biasa</td>
                      <td class="py-3.5 px-4 text-white/60">⚠️ Tryout berkala terjadwal</td>
                      <td class="py-3.5 px-4 font-bold text-[#c0ff00] bg-[#c0ff00]/5">✅ Model Estimasi IRT Terkalibrasi untuk Latihan</td>
                    </tr>
                    <tr class="hover:bg-white/[0.02] transition-colors">
                      <td class="py-3.5 px-4 font-bold text-white">Fasilitas Drill &amp; Retensi</td>
                      <td class="py-3.5 px-4 text-white/60">❌ Latihan kertas manual</td>
                      <td class="py-3.5 px-4 text-white/60">⚠️ Bank soal statis dengan kunci teks</td>
                      <td class="py-3.5 px-4 font-bold text-[#c0ff00] bg-[#c0ff00]/5">✅ Spaced Repetition + Analitik IRT per subtopik</td>
                    </tr>
                    <tr class="hover:bg-white/[0.02] transition-colors">
                      <td class="py-3.5 px-4 font-bold text-white">Estimasi Biaya / Tahun</td>
                      <td class="py-3.5 px-4 text-rose-400 font-semibold">Rp 8 – 25 Juta</td>
                      <td class="py-3.5 px-4 text-sky-400 font-semibold">Rp 1,5 – 6 Juta (kontrak tahunan)</td>
                      <td class="py-3.5 px-4 font-bold text-[#c0ff00] bg-[#c0ff00]/5 rounded-b-xl">Mulai Rp 119.000 / bln (hemat s.d 90%)</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 6. Solution & Value Proposition -->
          <div class="section-wrapper">
            <div class="absolute inset-0 pointer-events-none bg-gradient-to-r from-primary/10 to-secondary/10 blur-3xl opacity-30 rounded-3xl"></div>
            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
              <div class="space-y-6">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary/15 border border-primary/20 text-xs font-bold text-[#c0ff00] rounded-md uppercase tracking-wider">
                  ⚡ SOLUSI ADAPTIF
                </div>
                <h3 class="text-3xl md:text-5xl font-black font-heading text-white leading-tight">Persiapan Terukur Dengan <span class="text-[#c0ff00]">Analitik Presisi</span></h3>
                <p class="text-base md:text-lg text-slate-300 leading-relaxed font-medium">
                  EduPath mendeteksi kelemahan spesifik Anda dalam waktu 10 menit, membuat jalur belajar khusus, dan memandu Anda menyelesaikannya materi per materi.
                </p>
                
                <ul class="space-y-3 pt-2 text-sm md:text-base text-slate-300 font-light">
                  <li class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-[#c0ff00] shrink-0"></span>
                    Sistem evaluasi berbasis model IRT terkalibrasi untuk latihan
                  </li>
                  <li class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-[#c0ff00] shrink-0"></span>
                    Rekomendasi prioritas materi otomatis
                  </li>
                </ul>
              </div>

              <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-secondary to-primary rounded-2xl blur opacity-30"></div>
                <div class="relative bg-slate-900 border border-slate-800 p-8 rounded-2xl space-y-6">
                  <div class="flex justify-between items-center text-xs md:text-sm">
                    <span class="text-slate-450 font-bold tracking-wider">ALUR DIAGNOSIS AI</span>
                    <span class="text-[#c0ff00] font-bold">AKTIF</span>
                  </div>
                  <div class="space-y-3.5">
                    <div class="flex justify-between text-xs md:text-sm">
                      <span class="text-slate-300 font-medium">Kemampuan Aljabar Dasar</span>
                      <span class="text-[#c0ff00] font-bold">85% Sukses</span>
                    </div>
                    <div class="w-full bg-slate-950 rounded-full h-3">
                      <div class="bg-emerald-400 h-3 rounded-full w-[85%]"></div>
                    </div>
                  </div>
                  <div class="space-y-3.5">
                    <div class="flex justify-between text-xs md:text-sm">
                      <span class="text-slate-300 font-medium">Bangun Ruang Geometri</span>
                      <span class="text-[#c0ff00] font-bold">35% Kritis</span>
                    </div>
                    <div class="w-full bg-slate-950 rounded-full h-3">
                      <div class="bg-rose-500 h-3 rounded-full w-[35%]"></div>
                    </div>
                  </div>
                  <p class="text-xs text-slate-500 italic pt-2 border-t border-slate-850">
                    *Rekomendasi AI: Sistem otomatis mengarahkan siswa ke sub-kelas Geometri 3D.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- 7. Core Benefits -->
          <div id="benefits" class="section-wrapper grid grid-cols-1 lg:grid-cols-5 gap-12 items-center scroll-mt-24">
            <div class="lg:col-span-3 space-y-8">
              <div class="space-y-2">
                <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">MANFAAT PLATFORM</span>
                <h3 class="text-3xl md:text-5xl font-black font-heading text-white">Efisiensi Belajar <span class="text-[#c0ff00]">Level Tertinggi</span></h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 pt-4">
                <div class="space-y-3">
                  <span class="text-3xl">⏱️</span>
                  <h4 class="text-lg md:text-xl font-black text-white">Hemat Waktu Belajar</h4>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-medium">Eliminasi materi yang sudah dikuasai. Hanya pelajari materi kritis yang menahan kenaikan skor Anda.</p>
                </div>
                <div class="space-y-3">
                  <span class="text-3xl">🎯</span>
                  <h4 class="text-lg md:text-xl font-black text-white">Simulasi Penilaian IRT</h4>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-medium">Setiap tryout memberikan konversi persentil kelulusan yang riil sesuai bobot skor SNBT terbaru.</p>
                </div>
                <div class="space-y-3">
                  <span class="text-3xl">🤖</span>
                  <h4 class="text-lg md:text-xl font-black text-white">AI Tutor Siaga 24 Jam</h4>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-medium">Klinik tugas aktif setiap saat yang menuntun logika berpikir Anda langkah demi langkah.</p>
                </div>
                <div class="space-y-3">
                  <span class="text-3xl">📈</span>
                  <h4 class="text-lg md:text-xl font-black text-white">Grafik Kenaikan Riil</h4>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-medium">Pantau secara riil proyeksi peluang Anda menembus universitas target pilihan secara visual.</p>
                </div>
              </div>
            </div>

            <div class="lg:col-span-2 bg-gradient-to-b from-slate-900 to-slate-955 p-8 rounded-3xl border border-slate-800 relative">
              <div class="absolute top-2 right-2 w-32 h-32 bg-secondary/10 blur-[40px] rounded-full"></div>
              <h4 class="text-xs md:text-sm font-bold text-white uppercase tracking-wider mb-6">EduRank Leaderboard</h4>
              
              <div class="space-y-5">
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 rounded bg-amber-500/10 text-[#c0ff00] flex items-center justify-center font-bold text-xs">1</span>
                  <span class="text-xs md:text-sm text-slate-200 flex-grow font-semibold">Dewi (Aktuaria UGM)</span>
                  <span class="text-xs md:text-sm text-slate-400 font-bold">715 Poin</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 rounded bg-slate-800 text-slate-400 flex items-center justify-center font-bold text-xs">2</span>
                  <span class="text-xs md:text-sm text-slate-200 flex-grow font-semibold">Budi (Bisnis ITB)</span>
                  <span class="text-xs md:text-sm text-slate-400 font-bold">702 Poin</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 rounded bg-slate-800 text-slate-400 flex items-center justify-center font-bold text-xs">3</span>
                  <span class="text-xs md:text-sm text-slate-200 flex-grow font-semibold">Anda (Siswa Mandiri)</span>
                  <span class="text-xs md:text-sm text-[#c0ff00] font-bold">648 Poin</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 8. Bento Grid Feature Showcase -->
          <div id="features" class="section-wrapper space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2 reveal">
              <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">ETALASE FITUR</span>
              <h2 class="text-4xl md:text-6xl font-black font-heading text-white">Alat Tempur <span class="text-[#c0ff00]">Terlengkap</span></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-4 max-w-5xl mx-auto items-stretch">
              
              <!-- Feature 1: Adaptive Learning -->
              <div class="glass-card rounded-2xl p-4 lg:col-span-5 flex flex-col justify-start relative overflow-hidden group">
                <div class="!absolute top-0 right-0 w-48 h-48 bg-primary/10 blur-[60px] rounded-full group-hover:bg-primary/20 transition-all duration-700 pointer-events-none"></div>
                
                <div class="space-y-2 z-10">
                  <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-[#c0ff00] border border-primary/20">
                      <i class="ph-bold ph-cpu text-xl"></i>
                    </div>
                    <span class="text-[10px] bg-primary/20 text-[#c0ff00] font-mono px-2.5 py-1 rounded-full border border-primary/30 font-bold uppercase tracking-wider">
                      🎯 IRT Adaptive
                    </span>
                  </div>
                  <div>
                    <h3 class="text-xl md:text-2xl font-black text-white font-heading">AI Adaptive Assessment™</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium mt-2">
                      Sistem mendeteksi materi kelemahan, lalu reaktif mengubah urutan soal agar pas dengan porsi pemahaman Anda.
                    </p>
                  </div>
                </div>

                <div class="bg-slate-950/80 border border-slate-900 rounded-xl p-3 mt-4 z-10 relative overflow-hidden">
                  <div class="flex items-center justify-between text-xs font-mono mb-3">
                    <span class="text-white/60">Live Mastery</span>
                    <span class="text-[#c0ff00] font-bold flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full bg-[#c0ff00] animate-pulse"></span> Beradaptasi
                    </span>
                  </div>
                  <div class="flex items-end gap-1.5 h-12">
                    <div class="w-1/4 bg-slate-800 rounded-t h-[40%] group-hover:h-[60%] transition-all duration-500"></div>
                    <div class="w-1/4 bg-slate-800 rounded-t h-[60%] group-hover:h-[80%] transition-all duration-500 delay-75"></div>
                    <div class="w-1/4 bg-primary/60 rounded-t h-[75%] group-hover:h-[95%] transition-all duration-500 delay-100"></div>
                    <div class="w-1/4 bg-secondary/60 rounded-t h-[90%] group-hover:h-[110%] transition-all duration-500 delay-150"></div>
                  </div>
                </div>
              </div>

              <!-- Feature 2: Micro Lessons -->
              <div class="glass-card rounded-2xl p-4 lg:col-span-4 flex flex-col justify-start relative overflow-hidden group">
                <div class="!absolute -top-8 -right-8 w-24 h-24 bg-secondary/15 blur-xl rounded-full pointer-events-none"></div>
                
                <div class="space-y-2 z-10">
                  <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 rounded-lg bg-secondary/15 flex items-center justify-center text-[#c0ff00] border border-secondary/30">
                      <i class="ph-bold ph-video-camera text-xl"></i>
                    </div>
                    <span class="text-[10px] bg-secondary/25 text-[#c0ff00] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-secondary/30">
                      📚 Modul Kilat
                    </span>
                  </div>
                  <div>
                    <h3 class="text-xl md:text-2xl font-black font-heading text-white">Micro-Lessons Library</h3>
                    <p class="text-sm text-slate-300 leading-relaxed font-medium mt-2">
                      Koleksi video ringkas 3-7 menit yang langsung mengupas trik penyelesaian dan eliminasi opsi jawaban.
                    </p>
                  </div>
                </div>

                <div class="bg-black/40 border border-white/10 rounded-xl p-3 mt-4 space-y-2 z-10 flex-1 flex flex-col justify-end">
                  <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white/5 text-xs text-white/80 font-medium">
                    <span class="flex items-center gap-2"><i class="ph-fill ph-play-circle text-[#c0ff00] text-sm"></i> Trik Eliminasi Aljabar</span>
                    <span class="text-white/40 font-mono">04:15</span>
                  </div>
                  <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white/5 text-xs text-white/80 font-medium">
                    <span class="flex items-center gap-2"><i class="ph-fill ph-play-circle text-[#c0ff00] text-sm"></i> Taktik Analisis Wacana</span>
                    <span class="text-white/40 font-mono">05:10</span>
                  </div>
                </div>
              </div>

              <!-- Feature 3: AI Tutor -->
              <div class="glass-card rounded-2xl p-4 lg:col-span-3 flex flex-col justify-start group">
                <div class="space-y-2">
                  <div class="flex items-center justify-between mb-2">
                    <div class="w-10 h-10 rounded-lg bg-accent/15 border border-accent/30 flex items-center justify-center text-[#c0ff00] text-xl">
                      🤖
                    </div>
                    <span class="text-[10px] bg-emerald-500/15 text-emerald-400 px-2.5 py-1 rounded-full font-mono font-bold border border-emerald-500/30 flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> 24/7
                    </span>
                  </div>
                  <div>
                    <h3 class="text-xl md:text-2xl font-black text-white font-heading">AI Companion 24/7</h3>
                    <p class="text-sm text-slate-300 font-medium leading-relaxed mt-2">
                      Asisten interaktif cerdas membongkar kerumitan soal UTBK kapan pun kamu buntu.
                    </p>
                  </div>
                </div>

                <div class="bg-black/50 border border-white/10 rounded-xl p-3 mt-4 space-y-2 flex-1 flex flex-col justify-end">
                  <div class="flex items-start gap-2 max-w-[90%]">
                    <div class="w-5 h-5 rounded-full bg-slate-700 flex items-center justify-center text-[10px] shrink-0 mt-0.5">👤</div>
                    <div class="bg-slate-800 text-slate-200 text-xs p-2.5 rounded-xl rounded-tl-none font-medium leading-relaxed">Kak, trik cepat eliminasi silogisme ini gimana?</div>
                  </div>
                  <div class="flex items-start gap-2 max-w-[95%] ml-auto flex-row-reverse">
                    <div class="w-5 h-5 rounded-full bg-[#c0ff00] text-black font-black flex items-center justify-center text-[9px] shrink-0 mt-0.5">AI</div>
                    <div class="bg-[#c0ff00]/15 border border-[#c0ff00]/30 text-white text-xs p-2.5 rounded-xl rounded-tr-none font-medium leading-relaxed">Coret term penengahnya, jawabannya opsi B! 💡</div>
                  </div>
                </div>
              </div>

              <!-- Feature 4: Virtual Study Room -->
              <div class="glass-card rounded-2xl p-6 md:col-span-2 lg:col-span-4 flex flex-col justify-center text-center items-center gap-4 group">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-[#c0ff00] text-3xl">
                  ⏳
                </div>
                <div>
                  <h3 class="text-xl font-black text-white font-heading mb-2">Pomodoro Lofi Room</h3>
                  <p class="text-sm text-slate-300 font-medium leading-relaxed max-w-xs mx-auto">Ruang belajar fokus bersama iringan musik lofi ambient.</p>
                </div>
              </div>

              <!-- Feature 5: Parent Portal -->
              <div class="glass-card rounded-3xl p-6 md:col-span-2 lg:col-span-8 bg-gradient-to-r from-slate-950 to-slate-900 border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="space-y-2">
                  <h3 class="text-2xl md:text-3xl font-black font-heading text-white flex flex-wrap items-center gap-3">
                    Auto Report Telegram Orang Tua
                    <span class="bg-emerald-500/15 text-[#c0ff00] px-2.5 py-1 rounded text-[10px] font-bold border border-emerald-500/20 uppercase tracking-widest">Telegram</span>
                  </h3>
                  <p class="text-sm md:text-base text-slate-300 max-w-2xl font-medium leading-relaxed">
                    Hilangkan kekhawatiran orang tua secara transparan. Sistem otomatis meringkas total waktu belajar, perolehan skor target PTN, dan mengirimkannya langsung ke Telegram orang tua.
                  </p>
                </div>
                <button class="shrink-0 bg-white text-slate-950 hover:bg-slate-200 px-6 py-3.5 rounded-xl font-bold text-xs md:text-sm transition-all flex items-center gap-2" @click="currentTab = 'parent'">
                  Uji Coba Portal <i class="ph-bold ph-telegram-logo text-base"></i>
                </button>
              </div>

            </div>
          </div>

          <!-- 9. Dynamic Section: FILTERABLE TESTIMONIALS -->
          <div id="testimonials" class="section-wrapper space-y-8 sm:space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2 reveal px-4">
              <span class="text-[#c0ff00] text-xs sm:text-sm font-bold uppercase tracking-widest block">// FEEDBACK PENGGUNA AWAL</span>
              <h2 class="text-2xl sm:text-4xl md:text-5xl font-black font-heading text-white leading-tight">Apa Kata Pengguna Beta EduPath</h2>
              <p class="text-xs sm:text-sm text-white/50">Kesan pertama dari teman-teman yang mencoba EduPath lebih awal:</p>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap justify-center gap-1.5 sm:gap-2 max-w-2xl mx-auto px-4">
              <button
                v-for="flt in [
                  { id: 'all', name: 'Semua (3)' },
                  { id: 'health', name: '🩺 Saintek' },
                  { id: 'engineering', name: '💻 Teknik & MIPA' },
                  { id: 'soshum', name: '💼 Soshum' }
                ]"
                :key="flt.id"
                :class="['px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-xl sm:rounded-full text-xs font-bold transition-all border cursor-pointer active:scale-95',
                  testimonialFilter === flt.id
                    ? 'bg-[#c0ff00] text-black border-[#c0ff00] shadow-[0_0_20px_rgba(192,255,0,0.3)]'
                    : 'bg-white/5 text-white/60 border-white/10 hover:border-white/30 hover:bg-white/10'
                ]"
                @click="testimonialFilter = flt.id"
              >
                {{ flt.name }}
              </button>
            </div>

            <!-- Testimonial Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 md:gap-8 max-w-4xl mx-auto items-stretch px-4 sm:px-0">
              <div
                v-for="(t, idx) in filteredTestimonials"
                :key="t.name"
                class="glass-card rounded-2xl sm:rounded-3xl p-5 sm:p-7 md:p-8 relative overflow-hidden group flex flex-col justify-between border border-white/10"
              >
                <div class="absolute -right-4 -top-6 text-8xl text-white/[0.03] font-black font-serif italic pointer-events-none select-none">“</div>
                
                <div class="space-y-4 relative z-10">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-xl shrink-0 shadow-inner">
                        {{ t.avatar }}
                      </div>
                      <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                          <h4 class="font-black text-white text-sm sm:text-base">{{ t.name }}</h4>
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-[#c0ff00]/15 text-[#c0ff00] border border-[#c0ff00]/30 shrink-0">
                            Verified Beta
                          </span>
                        </div>
                        <p class="text-white/60 text-xs font-medium">{{ t.school }}</p>
                      </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-mono font-bold bg-white/5 text-white/70 border border-white/10 shrink-0 hidden sm:inline-block">
                      {{ t.clusterLabel }}
                    </span>
                  </div>

                  <!-- Target indicator -->
                  <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/[0.04] border border-white/10 text-xs w-auto">
                    <span class="text-white/40 text-[11px] font-mono uppercase tracking-wider">Target:</span>
                    <span class="text-[#c0ff00] font-bold text-xs">{{ t.target }}</span>
                  </div>

                  <p class="text-slate-200 font-normal text-xs sm:text-sm md:text-base leading-relaxed">
                    "{{ t.text }}"
                  </p>
                </div>

                <div class="pt-3 mt-4 border-t border-white/10 flex justify-between items-center text-[11px] text-white/40 font-mono">
                  <span class="flex items-center gap-1.5 text-white/50">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#c0ff00]"></span>
                    <span>Early Tester Feedback</span>
                  </span>
                  <span class="text-white/30 text-[10px]">UTBK 2026/2027</span>
                </div>
              </div>
            </div>
            <p class="text-center text-[10px] sm:text-[11px] text-white/40 italic pt-2 max-w-2xl mx-auto px-4">
              *Catatan: Ini adalah kesan awal dari pengguna yang mencoba EduPath pada tahap beta. EduPath saat ini masih dalam pengembangan aktif dan belum memiliki data kelulusan resmi.
            </p>
          </div>

          <!-- 10. Demonstration / Product Preview -->
          <div id="preview" class="section-wrapper space-y-6 sm:space-y-12 scroll-mt-24 relative overflow-visible px-4 sm:px-0">
            <div class="text-center max-w-xl mx-auto space-y-2 reveal">
              <span class="text-[#c0ff00] text-xs sm:text-sm font-bold uppercase tracking-widest block">// TAMPILAN INTERFACE</span>
              <h2 class="text-2xl sm:text-4xl md:text-5xl font-black font-heading text-white leading-tight">Eksplorasi Dashboard</h2>
              <p class="text-xs sm:text-sm text-white/50 max-w-md mx-auto">Pantau kesiapan UTBK, grafik probabilitas kelulusan, dan rekomendasi AI dalam satu tampilan presisi.</p>
            </div>

            <div class="relative max-w-4xl mx-auto">
              <div class="absolute -inset-1 bg-gradient-to-r from-[#c0ff00]/20 via-emerald-500/20 to-teal-500/20 rounded-3xl blur-xl opacity-30"></div>
              
              <div class="absolute -right-4 top-1/4 bg-slate-950/90 border border-white/10 p-3 rounded-2xl shadow-2xl hidden md:flex items-center gap-3 z-20 animate-[bounce_3s_infinite]">
                <span class="text-xl">💎</span>
                <div>
                  <div class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Skor Tryout</div>
                  <div class="text-xs md:text-sm font-bold text-white">780 Poin</div>
                </div>
              </div>
              <div class="absolute -left-4 bottom-1/4 bg-slate-950/90 border border-white/10 p-3 rounded-2xl shadow-2xl hidden md:flex items-center gap-3 z-20 animate-[bounce_4s_infinite]">
                <span class="text-xl">🎯</span>
                <div>
                  <div class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Rekomendasi AI</div>
                  <div class="text-xs md:text-sm font-bold text-white">Fokus: Geometri</div>
                </div>
              </div>

              <!-- Dashboard Preview Container (Mobile-flexible, no aspect-video clipping) -->
              <div class="relative bg-[#0c1222] rounded-2xl sm:rounded-3xl border border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden">
                <div class="h-9 bg-slate-900/90 border-b border-white/10 flex items-center px-4 gap-2">
                  <div class="w-2.5 h-2.5 rounded-full bg-red-500/80"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/80"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-green-500/80"></div>
                  <div class="ml-3 text-[9px] text-white/40 font-mono uppercase tracking-widest truncate">edupath.ai/dashboard</div>
                </div>
                
                <div class="p-4 sm:p-6 md:p-8 flex flex-col justify-between gap-4 sm:gap-6 relative bg-cover bg-center" style="background-image: linear-gradient(rgba(10,15,29,0.94), rgba(10,15,29,0.98)), url('https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=600&auto=format&fit=crop&q=60');">
                  <!-- Top row -->
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                      <span class="text-[9px] text-[#c0ff00] font-bold uppercase tracking-wider block">PROGRES BELAJAR</span>
                      <h4 class="text-sm sm:text-base md:text-lg font-black text-white">Peluang Kelulusan Kedokteran UI</h4>
                    </div>
                    <span class="self-start sm:self-auto text-[11px] sm:text-xs font-bold text-[#c0ff00] bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-xl shrink-0">
                      82.4% Sukses
                    </span>
                  </div>

                  <!-- Progress Bar & Score Matrix -->
                  <div class="space-y-2">
                    <div class="w-full bg-white/10 rounded-full h-3 border border-white/10 overflow-hidden p-0.5">
                      <div class="bg-gradient-to-r from-[#c0ff00] to-emerald-400 h-full rounded-full transition-all" style="width: 82%;"></div>
                    </div>
                    <div class="flex justify-between text-[10px] sm:text-xs text-white/60 font-medium font-mono">
                      <span>Estimasi Skor: <strong class="text-white">648</strong> / 720 Target</span>
                      <span class="text-[#c0ff00] font-bold">Sisa 72 Poin Lagi</span>
                    </div>
                  </div>

                  <!-- AI Tutor Coach Box -->
                  <div class="flex items-start gap-3 bg-white/[0.04] border border-white/10 p-3 sm:p-4 rounded-xl sm:rounded-2xl backdrop-blur-md">
                    <span class="text-xl sm:text-2xl shrink-0">🤖</span>
                    <p class="text-[11px] sm:text-xs text-slate-200 font-normal leading-relaxed">
                      <strong class="text-white font-bold">AI Tutor:</strong> "Siswa Mandiri telah meningkatkan kemampuan Aljabar Dasar sebesar 15% minggu ini. Pertahankan streak belajar Anda!"
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 11. Dynamic Section: PRICING WITH BILLING TOGGLE -->
          <div id="pricing" class="section-wrapper space-y-12 scroll-mt-24">
            <div class="text-center max-w-4xl xl:max-w-5xl mx-auto space-y-4 reveal px-2">
              <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-black uppercase tracking-widest">
                💎 INVESTASI LEHER KE ATAS • BEBAS RISIKO
              </span>
              <h2 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black font-heading text-white leading-tight break-words">
                Investasi Terbaik <br class="hidden sm:inline" />
                <span style="color: #c0ff00;">Menembus PTN Impian</span>
              </h2>
              <p class="text-sm md:text-base text-white/60 leading-relaxed font-medium flex flex-col gap-1 max-w-2xl mx-auto">
                <span>Bimbel konvensional memungut Rp 15–30 juta untuk metode satu buku yang sama bagi semua murid.</span>
                <span>Di EduPath, Anda berinvestasi pada kecerdasan AI adaptif yang melatih langsung titik lemah spesifik Anda.</span>
              </p>
              
              <!-- Interactive Billing Cycle Toggle with 50/50 balanced layout on mobile -->
              <div class="pt-2 flex justify-center">
                <div class="grid grid-cols-2 p-1.5 rounded-2xl bg-white/5 border border-white/15 backdrop-blur-md shadow-xl w-full max-w-sm sm:max-w-md">
                  <button
                    :class="['py-2.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center cursor-pointer', !isAnnualBilling ? 'bg-white text-black shadow-lg' : 'text-white/60 hover:text-white']"
                    @click="isAnnualBilling = false"
                  >
                    Bayar Bulanan
                  </button>
                  <button
                    :class="['py-2.5 px-3 rounded-xl text-xs font-black transition-all flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-2 cursor-pointer', isAnnualBilling ? 'bg-[#c0ff00] text-black shadow-[0_0_20px_rgba(192,255,0,0.4)]' : 'text-white/60 hover:text-white']"
                    @click="isAnnualBilling = true"
                  >
                    <span class="whitespace-nowrap">Bayar Tahunan</span>
                    <span :class="['text-[9px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-wider whitespace-nowrap', isAnnualBilling ? 'bg-black text-[#c0ff00]' : 'bg-[#c0ff00]/20 text-[#c0ff00]']">Hemat 60% 🔥</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 max-w-6xl mx-auto items-stretch">
              <!-- Dynamic Plans -->
              <div 
                v-for="(product, idx) in displayProducts" 
                :key="product.id"
                :class="[
                  'glass-card rounded-3xl p-6 sm:p-8 flex flex-col justify-between transition-all duration-300 relative z-10',
                  idx % 3 === 0 ? 'border border-white/10 hover:border-white/20 bg-white/[0.02] hover:bg-white/[0.04]' : '',
                  idx % 3 === 1 ? 'border-2 border-[#c0ff00] sm:scale-105 shadow-2xl hover:shadow-[0_0_50px_rgba(192,255,0,0.25)]' : '',
                  idx % 3 === 2 ? 'border border-purple-500/30 bg-purple-950/[0.08] hover:border-purple-500/50' : ''
                ]"
                :style="idx % 3 === 1 ? 'background: linear-gradient(180deg, #0d1626 0%, #080d16 100%);' : ''"
              >
                 <!-- Ribbon for Middle item -->
                 <div v-if="idx % 3 === 1" class="absolute top-0 right-0 bg-[#c0ff00] text-black text-[10px] font-black px-3.5 py-1 rounded-bl-2xl uppercase tracking-wider shadow-md">
                   👑 PILIHAN UTAMA
                 </div>

                 <!-- Title and Desc -->
                 <div class="space-y-4 sm:space-y-5">
                   <div class="flex justify-between items-center">
                     <span :class="[
                       'text-xs font-bold uppercase tracking-wider block',
                       idx % 3 === 0 ? 'text-slate-400' : '',
                       idx % 3 === 1 ? 'text-[#c0ff00]' : '',
                       idx % 3 === 2 ? 'text-purple-300' : ''
                     ]">{{ product.code || 'PAKET' }} PASS</span>
                     <span v-if="idx % 3 === 2" class="text-[10px] px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30">Premium</span>
                     <span v-else-if="idx % 3 === 0" class="text-[10px] px-2.5 py-1 rounded-full bg-white/10 text-white/80 font-bold">Dasar</span>
                   </div>
                   
                   <div>
                     <h3 class="text-xl sm:text-2xl font-black text-white font-heading">{{ product.name }}</h3>
                     <p :class="[
                       'text-xs mt-1 leading-relaxed',
                       idx % 3 === 1 ? 'text-white/60' : 'text-white/50'
                     ]">{{ product.description }}</p>
                   </div>

                   <!-- Price Framing dengan Sistem Coret -->
                   <div v-if="product.activePlan" :class="[
                     'p-4 rounded-2xl space-y-1',
                     idx % 3 === 0 ? 'bg-white/[0.03] border border-white/10' : '',
                     idx % 3 === 1 ? 'bg-[#c0ff00]/10 border border-[#c0ff00]/30' : '',
                     idx % 3 === 2 ? 'bg-purple-900/20 border border-purple-500/30' : ''
                   ]">
                     <!-- Jika Bayar Tahunan (Sistem Coret) -->
                     <div v-if="isAnnualBilling" class="space-y-1">
                       <div class="flex items-center gap-2">
                         <span class="text-xs sm:text-sm text-white/40 line-through font-mono">Rp {{ Number(product.monthlyBasePrice || 180000).toLocaleString('id-ID') }}</span>
                         <span :class="[
                           'text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider',
                           idx % 3 === 1 ? 'bg-[#c0ff00] text-black shadow-sm' : 'text-emerald-400 bg-emerald-500/15 border border-emerald-500/30'
                         ]">Diskon {{ product.annualDiscount || 40 }}%</span>
                       </div>
                       <div class="flex items-baseline gap-1">
                         <span :class="[
                           'text-3xl sm:text-5xl font-black font-heading',
                           idx % 3 === 1 ? 'text-[#c0ff00]' : 'text-white'
                         ]">Rp {{ Math.round((product.annualMonthlyEquivalent || (product.activePlan.price / 12)) / 1000).toLocaleString('id-ID') }}k</span>
                         <span :class="[
                           'font-bold text-xs',
                           idx % 3 === 1 ? 'text-white/60' : 'text-white/50'
                         ]">/bulan</span>
                       </div>
                       <div class="text-[10px] text-white/60 pt-1 font-medium">
                         Ditagih tahunan Rp {{ Number(product.activePlan.price).toLocaleString('id-ID') }} /tahun (Diskon {{ product.annualDiscount || 40 }}% dibayar per tahun)
                       </div>
                     </div>

                     <!-- Jika Bayar Bulanan -->
                     <div v-else class="space-y-1">
                       <div class="flex items-baseline gap-1">
                         <span :class="[
                           'text-3xl sm:text-5xl font-black font-heading',
                           idx % 3 === 1 ? 'text-[#c0ff00]' : 'text-white'
                         ]">Rp {{ Math.round(product.activePlan.price / 1000).toLocaleString('id-ID') }}k</span>
                         <span :class="[
                           'font-bold text-xs',
                           idx % 3 === 1 ? 'text-white/60' : 'text-white/50'
                         ]">/bulan</span>
                       </div>
                       <div class="text-[10px] text-white/50 pt-1 font-medium">
                         Ditagih bulanan, fleksibel tanpa komitmen
                       </div>
                       <div class="text-[10px] text-[#c0ff00] font-bold pt-0.5 cursor-pointer hover:underline" @click="isAnnualBilling = true">
                         Hemat {{ product.annualDiscount || 40 }}% jadi Rp {{ Math.round((product.annualMonthlyEquivalent || (product.monthlyBasePrice * 0.6)) / 1000) }}k/bln jika bayar per tahun &rarr;
                       </div>
                     </div>
                   </div>

                   <!-- Value Stack -->
                   <ul :class="[
                     'space-y-3 text-xs md:text-sm font-medium pt-2',
                     idx % 3 === 1 ? 'text-white' : 'text-white/80'
                   ]">
                     <li v-for="(feat, fIdx) in product.featuresList" :key="fIdx" class="flex items-start gap-2.5">
                       <i v-if="idx % 3 === 0" class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                       <span v-else-if="idx % 3 === 1" class="text-[#c0ff00] text-base shrink-0 font-black">⚡</span>
                       <i v-else-if="idx % 3 === 2" class="ph-bold ph-star text-purple-400 mt-0.5 shrink-0"></i>
                       <span>{{ feat }}</span>
                     </li>
                   </ul>
                 </div>

                 <!-- Action Button -->
                 <div class="pt-6 sm:pt-8" v-if="product.activePlan">
                   <button 
                     :class="[
                       'w-full min-h-[50px] py-3.5 rounded-2xl text-xs md:text-sm font-black transition-all shadow-[0_8px_32px_rgba(0,0,0,0.3)] active:scale-95 flex items-center justify-center gap-2 cursor-pointer',
                       idx % 3 === 1 ? 'bg-[#c0ff00] text-black hover:scale-105 hover:shadow-[0_0_35px_rgba(192,255,0,0.6)] py-4' : 'bg-white/10 hover:bg-white hover:text-black text-white border border-white/20'
                     ]"
                     @click="purchasePlan(product.name, product.activePlan.price)"
                   >
                     <span>Pilih {{ product.name }}</span>
                     <i v-if="idx % 3 === 1" class="ph-bold ph-arrow-right text-base"></i>
                   </button>
                 </div>
              </div>
            </div>

            <!-- Trust Strip -->
            <div class="max-w-4xl mx-auto pt-6 border-t border-white/10 grid grid-cols-1 md:grid-cols-2 gap-4 text-center">
              <div class="flex items-center justify-center gap-2 text-xs font-bold text-white/70">
                <i class="ph-bold ph-credit-card text-[#c0ff00] text-lg"></i>
                <span>QRIS, Transfer Bank, E-Wallet Resmi</span>
              </div>
              <button @click="showParentConsentModal = true" class="flex items-center justify-center gap-2 text-xs font-bold text-white/70 hover:text-[#c0ff00] transition-colors cursor-pointer">
                <i class="ph-bold ph-chats-circle text-[#c0ff00] text-lg"></i>
                <span>Laporan &amp; Dukungan Orang Tua</span>
              </button>
            </div>
          </div>

          <!-- 12. FAQ -->
          <div id="faq" class="section-wrapper space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">PERTANYAAN UMUM</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Masih Ragu?</h2>
            </div>

            <div class="space-y-1">
              <div v-for="(faq, idx) in faqs" :key="idx" class="border-b border-slate-900 py-2 transition-all">
                <button class="w-full text-left flex justify-between items-center font-bold text-sm md:text-base text-white focus:outline-none" @click="toggleFaq(idx)">
                  <span>{{ faq.q }}</span>
                  <span class="text-slate-500 text-lg transition-transform" :class="{ 'rotate-45': faq.open }">+</span>
                </button>
                <div v-if="faq.open" class="mt-2 text-xs md:text-sm text-slate-350 leading-relaxed animate-fade-in">
                  {{ faq.a }}
                </div>
              </div>
            </div>
          </div>

          <!-- Affiliate Section (Merged from affiliate.html) -->
          <div id="affiliate-section" class="section-wrapper scroll-mt-24 space-y-12 pb-24">
            <!-- Hero -->
            <section class="glass-panel rounded-3xl p-6 sm:p-12 relative overflow-hidden border border-amber-500/20 text-center space-y-6">
              <div class="absolute -right-20 -top-20 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-yellow-500/15 rounded-full blur-3xl pointer-events-none"></div>
              <div class="max-w-3xl mx-auto space-y-4 relative z-10">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-mono font-bold">
                  <i class="ph-bold ph-hand-coins"></i> PROGRAM KEMITRAAN &amp; AFILIASI EDUPATH
                </div>
                <h2 class="text-2xl sm:text-4xl md:text-5xl font-black font-heading text-white tracking-tight leading-tight">
                  Raih Komisi <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-400">20% Pembelian Paket</span> <br class="hidden sm:inline">+ <span class="text-emerald-400">10% Recurring</span> Tiap Perpanjangan
                </h2>
                <p class="text-white/70 text-xs sm:text-base leading-relaxed">
                  Dapatkan penghasilan berkelanjutan dengan merekomendasikan platform belajar adaptif &amp; simulasi UTBK EduPath kepada rekan pejuang PTN dan komunitas belajar. Tracking komisi transparan secara real-time, pencairan terjadwal langsung ke rekening Anda.
                </p>
                <div class="flex flex-wrap justify-center gap-3 pt-2">
                  <button v-if="!isLoggedIn || affiliateData?.status === 'not_joined'" @click="showAffiliateRegisterModal = true" class="w-full sm:w-auto min-h-[50px] px-6 py-3.5 rounded-2xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-300 hover:to-yellow-400 text-black font-black text-sm transition-all shadow-[0_0_35px_rgba(245,158,11,0.5)] flex items-center justify-center gap-2 active:scale-98">
                    <i class="ph-bold ph-rocket text-base"></i> Daftar Jadi Mitra (Gratis)
                  </button>
                  <button v-else @click="activeAffiliateTab = 'rujukan'; currentTab = 'affiliate'" class="w-full sm:w-auto min-h-[50px] px-6 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-black text-sm transition-all border border-white/20 flex items-center justify-center gap-2 active:scale-98">
                    <i class="ph-bold ph-chart-line-up text-base"></i> Lihat Dashboard Afiliasi
                  </button>
                </div>
              </div>
            </section>

            <!-- Products Grid -->
            <section class="space-y-4">
              <div class="text-center space-y-1">
                <span class="text-xs font-bold text-amber-400 uppercase">// PRODUK UNGGULAN KAMI</span>
                <h2 class="text-2xl font-black font-heading text-white">Solusi Belajar yang Banyak Diminati Siswa</h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                <div class="p-5 rounded-2xl space-y-3 bg-white/5 border border-white/10">
                  <div class="flex justify-between items-start">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                      <i class="ph-bold ph-graduation-cap"></i>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">20% Thn-1 | 10% Rec</span>
                  </div>
                  <div>
                    <h3 class="font-bold text-white text-sm">EduPath Premium</h3>
                    <p class="text-[11px] text-white/50 mt-1">Akses penuh ke sistem pembelajaran adaptif dengan ribuan konten latihan.</p>
                  </div>
                </div>
                <div class="p-5 rounded-2xl space-y-3 bg-white/5 border border-white/10">
                  <div class="flex justify-between items-start">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                      <i class="ph-bold ph-book"></i>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">20% Thn-1 | 10% Rec</span>
                  </div>
                  <div>
                    <h3 class="font-bold text-white text-sm">Paket Tryout SNBT</h3>
                    <p class="text-[11px] text-white/50 mt-1">Simulasi ujian SNBT terlengkap yang menggunakan sistem IRT dan evaluasi AI.</p>
                  </div>
                </div>
                <div class="p-5 rounded-2xl space-y-3 bg-white/5 border border-white/10">
                  <div class="flex justify-between items-start">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                      <i class="ph-bold ph-robot"></i>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">20% Thn-1 | 10% Rec</span>
                  </div>
                  <div>
                    <h3 class="font-bold text-white text-sm">Analisa Belajar AI</h3>
                    <p class="text-[11px] text-white/50 mt-1">Laporan komprehensif kelemahan siswa dan rekomendasi materi secara personal.</p>
                  </div>
                </div>
              </div>
            </section>

            <!-- Stats Section -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
              <div class="p-5 rounded-2xl text-center bg-white/5 border border-white/10">
                <span class="text-[10px] text-white/50 font-bold uppercase">Total Klik Referral</span>
                <span class="block text-4xl font-black font-mono text-amber-300 mt-2">1,248</span>
                <span class="text-[10px] text-emerald-400 font-bold mt-1">+12 hari ini</span>
              </div>
              <div class="p-5 rounded-2xl text-center bg-white/5 border border-white/10">
                <span class="text-[10px] text-white/50 font-bold uppercase">Referral Aktif</span>
                <span class="block text-4xl font-black font-mono text-emerald-400 mt-2">124</span>
                <span class="text-[10px] text-white/50 font-bold mt-1">18 Akuisisi + 6 Recurring (Bulan ini)</span>
              </div>
              <div class="p-5 rounded-2xl text-center bg-white/5 border border-white/10">
                <span class="text-[10px] text-white/50 font-bold uppercase">Total Komisi (IDR)</span>
                <span class="block text-4xl font-black font-mono text-[#c0ff00] mt-2">Rp 4.48M</span>
                <span class="text-[10px] text-amber-300 font-bold mt-1">Estimasi Global Payout</span>
              </div>
            </section>
          </div>


          <!-- 13. Final CTA — SAMPLE style -->
          <div class="section-wrapper">
          <div class="text-center py-12 sm:py-16 md:py-20 rounded-3xl p-6 sm:p-8 space-y-6 relative overflow-hidden" style="background: #121212; border: 1px solid rgba(255,255,255,0.08);">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(192,255,0,0.15) 0%, transparent 70%);"></div>
            </div>
            
            <h2 class="text-3xl sm:text-5xl md:text-6xl font-black font-heading text-white tracking-tighter relative z-10 leading-tight">
              Siap Tembus<br /><span style="color: #c0ff00;">PTN Impianmu?</span>
            </h2>
            <p class="text-base sm:text-lg text-slate-200 max-w-md mx-auto font-medium relative z-10">
              Daftar sekarang — assessment diagnostic pertamamu gratis, selamanya.
            </p>
            

            <div class="flex flex-col sm:flex-row justify-center items-center gap-3.5 pt-4 relative z-10 w-full max-w-md mx-auto px-4 sm:px-0">
              <button class="w-full sm:w-auto min-h-[50px] px-8 py-3.5 sm:py-4 rounded-2xl font-black text-sm sm:text-base transition-all hover:scale-105 active:scale-95 shadow-[0_0_30px_rgba(192,255,0,0.4)] flex items-center justify-center gap-2 cursor-pointer" style="background: #c0ff00; color: #000;" @click="startLearning">
                <span>Mulai Uji Coba Gratis</span>
                <span>⚡</span>
              </button>
              <button class="w-full sm:w-auto min-h-[50px] px-6 py-3.5 sm:py-4 rounded-2xl font-bold text-sm sm:text-base border border-white/20 bg-white/5 hover:bg-white/10 text-white transition-all flex items-center justify-center cursor-pointer active:scale-95" @click="currentTab = 'diagnostic'">
                <span>Coba Tryout Dulu</span>
              </button>
            </div>
          </div>
          </div><!-- /section-wrapper CTA -->

        </section>

        <!-- TAB: AFFILIATE -->
        <section v-if="currentTab === 'affiliate'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">

          <!-- ✨ Banner Gambar EduPath Partner (PALING ATAS - Gambar 1.jpeg Clean & Clear) -->
          <div class="glass-card rounded-2xl sm:rounded-3xl overflow-hidden border border-amber-500/30 shadow-[0_16px_50px_rgba(0,0,0,0.5)] relative group">
            <img src="/1.jpeg" alt="EduPath Partner Banner" class="w-full h-32 sm:h-44 md:h-56 lg:h-64 object-cover object-center transition-transform duration-700 group-hover:scale-105" />
            <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-90 z-10"></div>
          </div>

          <!-- ✨ Text & Tombol Afiliasi (DI LETAKAN DI BAWAH GAMBAR) -->
          <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-amber-500/30 shadow-[0_16px_40px_rgba(0,0,0,0.5)] bg-gradient-to-r from-slate-900/95 via-slate-900/85 to-slate-950 flex flex-col lg:flex-row gap-5 sm:gap-6 items-stretch lg:items-center justify-between">
            <!-- Left: Partner Badge & Text -->
            <div class="flex items-start gap-3.5 sm:gap-4 flex-grow w-full lg:w-auto text-left">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-amber-500/25 to-emerald-500/25 border border-amber-400/40 shadow-[0_0_25px_rgba(245,158,11,0.3)] flex items-center justify-center shrink-0 backdrop-blur-md">
                <i class="ph-fill ph-crown text-2xl sm:text-3xl text-amber-400"></i>
              </div>
              <div class="space-y-1.5 sm:space-y-2">
                <div class="inline-flex items-center gap-2 px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 text-[10px] sm:text-xs font-black uppercase tracking-widest backdrop-blur-md shadow-[0_0_15px_rgba(245,158,11,0.2)]">
                  <span>EduPath Partner Program</span>
                </div>
                <h3 class="text-lg sm:text-2xl lg:text-3xl font-black text-white tracking-tight">Cetak <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#c0ff00] to-emerald-400">Pendapatan Pasif</span> dari Jejaring Anda</h3>
                <p class="text-white/80 text-xs sm:text-sm max-w-lg font-medium leading-relaxed">Dapatkan komisi <span class="px-2 py-0.5 rounded-md bg-amber-400/15 border border-amber-400/40 text-amber-300 font-mono font-black">20%</span> per pengguna aktif baru selamanya.</p>
              </div>
            </div>

            <!-- Right: Link Generator (if joined) OR Join Button (if not joined) -->
            <div v-if="affiliateData.status !== 'not_joined'" class="w-full lg:w-auto shrink-0 bg-black/70 border border-white/15 p-3.5 sm:p-4 rounded-2xl relative overflow-hidden backdrop-blur-xl shadow-2xl">
              <label class="block text-[10px] sm:text-xs font-black text-white/60 uppercase tracking-widest mb-1.5 sm:mb-2">Tautan Afiliasi Unik Anda</label>
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-black/90 border border-white/15 rounded-xl p-1.5 focus-within:border-[#c0ff00]/60 transition-colors">
                <div class="pl-2 text-white/50 hidden sm:block"><i class="ph-bold ph-link text-base"></i></div>
                <input type="text" readonly :value="`${baseUrl}/ref/${affiliateData?.referral_code}`" class="w-full sm:w-56 bg-transparent border-none text-xs text-white font-mono outline-none px-2 py-1.5" id="refLinkInput" />
                <button @click="copyReferralLink" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black uppercase tracking-wider text-xs active:scale-95 transition-all shadow-[0_0_15px_rgba(192,255,0,0.3)] cursor-pointer whitespace-nowrap text-center">
                  Salin Tautan
                </button>
              </div>
            </div>
            <div v-else class="w-full lg:w-auto shrink-0">
              <button @click="joinAffiliateProgram" :disabled="isJoiningAffiliate" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black uppercase tracking-wider text-xs sm:text-sm hover:scale-105 active:scale-95 transition-all shadow-[0_0_20px_rgba(192,255,0,0.3)] cursor-pointer text-center">
                <span v-if="isJoiningAffiliate" class="flex items-center gap-2 justify-center"><i class="ph-bold ph-spinner animate-spin"></i> Memproses...</span>
                <span v-else>Aktifkan Program Afiliasi 🚀</span>
              </button>
            </div>
          </div>

          <!-- JOINED STATE DATA -->
          <div v-if="affiliateData.status !== 'not_joined'" class="space-y-4 sm:space-y-6">

            <!-- Dynamic Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
              <!-- Stat 1: Total Rujukan -->
              <div class="glass-card p-3.5 sm:p-5 rounded-2xl relative overflow-hidden border border-emerald-500/20 hover:border-emerald-500/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_10px_30px_rgba(16,185,129,0.15)] group backdrop-blur-xl bg-gradient-to-br from-white/5 to-white/[0.02]">
                <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-emerald-400 to-transparent opacity-60 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-emerald-500/10 opacity-0 group-hover:opacity-100 blur-xl transition-opacity"></div>

                <div class="flex items-center justify-between mb-2 sm:mb-3 relative z-10">
                  <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.2)] group-hover:scale-110 group-hover:rotate-3 transition-transform text-base sm:text-xl">
                    <i class="ph-bold ph-users"></i>
                  </div>
                  <span class="text-[8px] sm:text-[9px] font-black tracking-widest text-emerald-400/80 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full uppercase">Total</span>
                </div>
                <div class="space-y-0.5 relative z-10">
                  <div class="text-xl sm:text-2xl md:text-3xl font-black font-mono text-white tracking-tight group-hover:text-emerald-300 transition-colors">{{ affiliateData?.stats?.total_referrals || 0 }}</div>
                  <div class="text-[9px] sm:text-[10px] text-white/50 font-black uppercase tracking-wider">Total Rujukan</div>
                </div>
              </div>

              <!-- Stat 2: Rujukan Aktif -->
              <div class="glass-card p-3.5 sm:p-5 rounded-2xl relative overflow-hidden border border-sky-500/20 hover:border-sky-500/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_10px_30px_rgba(56,189,248,0.15)] group backdrop-blur-xl bg-gradient-to-br from-white/5 to-white/[0.02]">
                <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-sky-400 to-transparent opacity-60 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-sky-500/10 opacity-0 group-hover:opacity-100 blur-xl transition-opacity"></div>

                <div class="flex items-center justify-between mb-2 sm:mb-3 relative z-10">
                  <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 shadow-[0_0_15px_rgba(56,189,248,0.2)] group-hover:scale-110 group-hover:rotate-3 transition-transform text-base sm:text-xl">
                    <i class="ph-bold ph-user-check"></i>
                  </div>
                  <span class="text-[8px] sm:text-[9px] font-black tracking-widest text-sky-400/80 bg-sky-500/10 border border-sky-500/20 px-2 py-0.5 rounded-full uppercase">Aktif</span>
                </div>
                <div class="space-y-0.5 relative z-10">
                  <div class="text-xl sm:text-2xl md:text-3xl font-black font-mono text-white tracking-tight group-hover:text-sky-300 transition-colors">{{ affiliateData?.stats?.active_referrals || 0 }}</div>
                  <div class="text-[9px] sm:text-[10px] text-white/50 font-black uppercase tracking-wider">Rujukan Aktif</div>
                </div>
              </div>

              <!-- Stat 3: Komisi Pending -->
              <div class="glass-card p-3.5 sm:p-5 rounded-2xl relative overflow-hidden border border-amber-500/20 hover:border-amber-500/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_10px_30px_rgba(245,158,11,0.15)] group backdrop-blur-xl bg-gradient-to-br from-white/5 to-white/[0.02]">
                <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-60 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-amber-500/10 opacity-0 group-hover:opacity-100 blur-xl transition-opacity"></div>

                <div class="flex items-center justify-between mb-2 sm:mb-3 relative z-10">
                  <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.2)] group-hover:scale-110 group-hover:rotate-3 transition-transform text-base sm:text-xl">
                    <i class="ph-bold ph-hourglass-high"></i>
                  </div>
                  <span class="text-[8px] sm:text-[9px] font-black tracking-widest text-amber-400/80 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full uppercase">Pending</span>
                </div>
                <div class="space-y-0.5 relative z-10">
                  <div class="text-lg sm:text-xl md:text-2xl font-black font-mono text-amber-300 tracking-tight">Rp {{ new Intl.NumberFormat('id-ID').format(affiliateData?.stats?.total_pending || 0) }}</div>
                  <div class="text-[9px] sm:text-[10px] text-white/50 font-black uppercase tracking-wider">Komisi Pending</div>
                </div>
              </div>

              <!-- Stat 4: Komisi Dibayar -->
              <div class="glass-card p-3.5 sm:p-5 rounded-2xl relative overflow-hidden border border-[#c0ff00]/20 hover:border-[#c0ff00]/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_10px_30px_rgba(192,255,0,0.15)] group backdrop-blur-xl bg-gradient-to-br from-white/5 to-white/[0.02]">
                <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-[#c0ff00] to-transparent opacity-60 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-[#c0ff00]/10 opacity-0 group-hover:opacity-100 blur-xl transition-opacity"></div>

                <div class="flex items-center justify-between mb-2 sm:mb-3 relative z-10">
                  <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-[#c0ff00]/15 border border-[#c0ff00]/30 flex items-center justify-center text-[#c0ff00] shadow-[0_0_15px_rgba(192,255,0,0.2)] group-hover:scale-110 group-hover:rotate-3 transition-transform text-base sm:text-xl">
                    <i class="ph-bold ph-wallet"></i>
                  </div>
                  <span class="text-[8px] sm:text-[9px] font-black tracking-widest text-[#c0ff00] bg-[#c0ff00]/10 border border-[#c0ff00]/20 px-2 py-0.5 rounded-full uppercase">Lunas</span>
                </div>
                <div class="space-y-0.5 relative z-10">
                  <div class="text-lg sm:text-xl md:text-2xl font-black font-mono text-[#c0ff00] tracking-tight">Rp {{ new Intl.NumberFormat('id-ID').format(affiliateData?.stats?.total_paid || 0) }}</div>
                  <div class="text-[9px] sm:text-[10px] text-white/50 font-black uppercase tracking-wider">Komisi Dibayar</div>
                </div>
              </div>
            </div>
            
            <!-- TAB NAVIGATION -->
            <div class="mt-8 flex border-b border-white/10 gap-2 sm:gap-6 px-4 overflow-x-auto no-scrollbar">
              <button @click="activeAffiliateTab = 'rujukan'" class="pb-3 px-2 text-xs uppercase tracking-widest font-black border-b-2 transition-all whitespace-nowrap" :class="activeAffiliateTab === 'rujukan' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-white/40 hover:text-white hover:border-white/20'">
                Daftar Rujukan
              </button>
              <button @click="activeAffiliateTab = 'komisi'" class="pb-3 px-2 text-xs uppercase tracking-widest font-black border-b-2 transition-all whitespace-nowrap" :class="activeAffiliateTab === 'komisi' ? 'border-indigo-500 text-purple-400' : 'border-transparent text-white/40 hover:text-white hover:border-white/20'">
                Riwayat Komisi
              </button>
              <button @click="handleTabClick('settings')" class="pb-3 px-2 text-xs uppercase tracking-widest font-black border-b-2 border-transparent text-white/40 hover:text-amber-400 hover:border-amber-400 transition-all whitespace-nowrap flex items-center gap-1.5">
                <i class="ph-bold ph-user-gear text-sm"></i> Pengaturan
              </button>
            </div>

            <!-- TAB CONTENT -->
            <div class="mt-4 sm:mt-6 glass-card p-0 overflow-hidden border border-white/10 rounded-2xl sm:rounded-3xl shadow-[0_8px_32px_rgba(0,0,0,0.3)]">
              <!-- Daftar Rujukan -->
              <div v-show="activeAffiliateTab === 'rujukan'">
                <div v-if="affiliateData?.referrals?.length > 0" class="divide-y divide-white/5">
                  <div v-for="(refData, idx) in affiliateData.referrals" :key="idx" class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 sm:p-6 hover:bg-white/5 transition-colors gap-3 sm:gap-4">
                    <div class="flex items-center gap-3 sm:gap-4">
                      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-white/10 border border-white/10 flex items-center justify-center text-white/40 shrink-0">
                        <i class="ph-fill ph-user text-lg sm:text-xl"></i>
                      </div>
                      <div>
                        <div class="text-xs sm:text-sm font-black text-white">{{ refData.name }}</div>
                        <div class="text-[9px] sm:text-[10px] text-white/60 font-mono mt-0.5 sm:mt-1">{{ refData.date }}</div>
                      </div>
                    </div>
                    <div class="text-left sm:text-right">
                      <div class="text-[10px] sm:text-xs font-black uppercase tracking-wider px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full border inline-block" :class="refData.status === 'Aktif' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-white/10 border-white/10 text-white/60'">{{ refData.status }}</div>
                    </div>
                  </div>
                </div>
                <div v-else class="p-8 sm:p-12 text-center text-white/40 text-xs sm:text-sm font-bold uppercase tracking-widest">
                  Belum ada rujukan.
                </div>
              </div>

              <!-- Riwayat Komisi -->
              <div v-show="activeAffiliateTab === 'komisi'">
                <div v-if="affiliateData?.history?.length > 0" class="divide-y divide-white/5">
                  <div v-for="(hist, idx) in affiliateData.history" :key="idx" class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 sm:p-6 hover:bg-white/5 transition-colors gap-3 sm:gap-4">
                    <div class="flex items-center gap-3 sm:gap-4">
                      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center shrink-0 border" :class="hist.status === 'paid' ? 'bg-purple-500/10 border-purple-500/20 text-purple-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400'">
                        <i :class="['text-lg sm:text-xl', hist.status === 'paid' ? 'ph-bold ph-check' : 'ph-bold ph-hourglass']"></i>
                      </div>
                      <div>
                        <div class="text-sm sm:text-base font-black font-mono tracking-tight" :class="hist.status === 'paid' ? 'text-purple-400' : 'text-white'">Rp {{ new Intl.NumberFormat('id-ID').format(hist.amount) }}</div>
                        <div class="text-[9px] sm:text-[10px] text-white/60 mt-0.5 sm:mt-1 font-mono uppercase">{{ new Date(hist.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</div>
                      </div>
                    </div>
                    <div class="flex items-center gap-3">
                      <span :class="['px-3 sm:px-4 py-1 sm:py-1.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-widest border', hist.status === 'paid' ? 'bg-purple-500/10 border-purple-500/20 text-purple-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400']">
                        {{ hist.status === 'paid' ? 'Selesai' : 'Tertunda' }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-else class="p-8 sm:p-12 text-center text-white/40 text-xs sm:text-sm font-bold uppercase tracking-widest">
                  Belum ada riwayat komisi.
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB: PENGATURAN (SETTINGS) -->
        <section v-if="currentTab === 'settings'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">
          <!-- Header Banner -->
          <div class="glass-card p-4 sm:p-6 md:p-8 rounded-2xl sm:rounded-3xl border border-white/10 relative overflow-hidden bg-gradient-to-r from-slate-900/90 via-slate-900/60 to-slate-950 shadow-[0_12px_40px_rgba(0,0,0,0.5)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6 relative z-10">
              <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 text-xl sm:text-2xl shrink-0 shadow-[0_0_20px_rgba(245,158,11,0.2)]">
                  ⚙️
                </div>
                <div>
                  <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">Pengaturan Akun &amp; Langganan</h2>
                  <p class="text-xs text-white/60 font-medium">Kelola informasi profil, password, status paket, dan rekening pencairan komisi.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Status Paket (Role-aware & Upgrade Flow) -->
          <div class="glass-card p-4 sm:p-6 md:p-8 rounded-2xl sm:rounded-3xl border border-white/10 relative overflow-hidden bg-gradient-to-br from-white/5 to-white/0 shadow-[0_8px_32px_rgba(0,0,0,0.3)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 sm:gap-6">
              <div class="space-y-1.5 sm:space-y-2">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full" :class="(isPremiumUser || isStaff) ? 'bg-[#c0ff00] animate-pulse' : 'bg-amber-400'"></span>
                  <span class="text-xs font-black uppercase tracking-widest text-white/60">Status Akun &amp; Paket</span>
                </div>
                <div class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight" :class="(isPremiumUser || isStaff || isAffiliate) ? 'text-[#c0ff00]' : 'text-amber-400'">
                  {{ isTeacher ? 'Akun Pendidik / Guru (Akses Penuh)' : (isAdmin ? (currentUser?.role === 'superadmin' ? 'Super Administrator' : 'Administrator Tenant') : (isSchoolBk ? 'Akun Perwakilan Sekolah Mitra (Guru BK)' : (isAffiliate ? 'Mitra Afiliasi Resmi EduPath' : (isPremiumUser ? (currentUser?.plan_name || 'Premium UTBK (Aktif)') : 'Paket Gratis / Trial')))) }}
                </div>
                <p class="text-xs text-white/60 max-w-xl leading-relaxed">
                  {{ isTeacher 
                      ? 'Anda memiliki hak akses pengajar penuh untuk mengelola modul materi, kurikulum, bank soal, dan analitik siswa.' 
                      : (isAdmin 
                          ? 'Akses administrator sistem penuh untuk manajemen data, pesanan, analitik, dan pengguna platform.' 
                          : (isSchoolBk
                              ? 'Akses portal khusus Guru BK dan Sekolah Mitra untuk memantau agregat hasil asesmen SPP dan tryout siswa.'
                              : (isAffiliate
                                  ? 'Anda terdaftar sebagai Mitra Afiliasi resmi EduPath. Pantau referral, komisi, dan pencairan komisi Anda.'
                                  : (isPremiumUser 
                                      ? 'Anda memiliki akses penuh ke seluruh Fitur Simulasi UTBK, Asesmen Kesiapan AI, & Modul Drill HOTS.' 
                                      : 'Akun Anda saat ini pada mode Gratis / Trial. Upgrade ke Paket Premium untuk membuka akses tanpa batas ke Simulasi SNBT & Tryout.')))) }}
                </p>
              </div>

              <!-- Button Upgrade / Portal Guru -->
              <div class="shrink-0 w-full sm:w-auto">
                <button
                  v-if="isStaff"
                  @click="window.location.hash = '#/admin'"
                  class="w-full sm:w-auto justify-center px-5 sm:px-6 py-3 sm:py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-black font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(245,158,11,0.3)] hover:scale-105 active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
                >
                  <i class="ph-bold ph-chalkboard-teacher text-base" v-if="isTeacher"></i>
                  <i class="ph-bold ph-shield-check text-base" v-else></i>
                  <span>{{ isTeacher ? 'Buka Portal Guru / Bank Soal ➔' : 'Buka Admin Panel ➔' }}</span>
                </button>
                <button
                  v-else-if="isSchoolBk"
                  @click="window.location.hash = '#/school'"
                  class="w-full sm:w-auto justify-center px-5 sm:px-6 py-3 sm:py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-black font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(245,158,11,0.3)] hover:scale-105 active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
                >
                  <i class="ph-bold ph-buildings text-base"></i>
                  <span>Buka Portal Guru BK ➔</span>
                </button>
                <button
                  v-else-if="isAffiliate"
                  @click="window.location.hash = '#/admin'"
                  class="w-full sm:w-auto justify-center px-5 sm:px-6 py-3 sm:py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-black font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(245,158,11,0.3)] hover:scale-105 active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
                >
                  <i class="ph-bold ph-handshake text-base"></i>
                  <span>Buka Portal Afiliasi ➔</span>
                </button>
                <button
                  v-else
                  @click="openPlanSelector"
                  class="w-full sm:w-auto justify-center px-5 sm:px-6 py-3 sm:py-3.5 rounded-2xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(192,255,0,0.3)] hover:scale-105 active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
                >
                  <i class="ph-bold ph-lightning text-base"></i>
                  <span>{{ isPremiumUser ? 'Perpanjang / Ganti Paket' : 'Pilih Paket Belajar ⚡' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Informasi Atribut Data Akun (VIEW MODE & EDIT MODE) -->
          <div class="glass-card p-6 md:p-8 rounded-3xl border border-white/10 shadow-[0_8px_32px_rgba(0,0,0,0.3)]">
            
            <!-- View Mode -->
            <div v-if="!isEditingProfile" class="space-y-8">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-white/10 gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold text-lg">
                    👤
                  </div>
                  <div>
                    <h4 class="text-white font-black text-lg tracking-wide">Pengaturan Akun &amp; Rekening</h4>
                    <p class="text-xs text-white/50">Data atribut akun dan rekening pencairan komisi afiliasi Anda</p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="startEditingProfile"
                  class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-[#c0ff00] hover:text-black text-white font-black text-xs uppercase tracking-wider border border-white/20 transition-all flex items-center gap-2 cursor-pointer shadow-md shrink-0 self-start sm:self-auto"
                >
                  <i class="ph-bold ph-pencil-simple text-base"></i>
                  <span>Edit Data Pengaturan</span>
                </button>
              </div>

              <!-- Grid Informasi Atribut Profil -->
              <div class="space-y-4 max-w-4xl">
                <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-amber-400">
                  <span>👤 Data Profil Akun</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Nama Lengkap</span>
                    <div class="text-sm font-bold text-white">{{ getStudentDisplayName(currentUser) }}</div>
                  </div>
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Email Utama</span>
                    <div class="text-sm font-mono font-medium text-white">{{ currentUser?.email || profileEditForm.email || '-' }}</div>
                  </div>
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Peran / Status Pengguna</span>
                    <div class="text-sm font-bold text-[#c0ff00]">
                      {{ isTeacher ? 'Guru / Tutor (Pendidik)' : (isAdmin ? (currentUser?.role === 'superadmin' ? 'Super Administrator' : 'Administrator Tenant') : (isSchoolBk ? 'Guru BK / Sekolah Mitra' : (isAffiliate ? 'Mitra Afiliasi Resmi' : (isPremiumUser ? 'Siswa Premium (' + (currentUser?.plan_name || currentUser?.plan || 'Utama') + ')' : 'Siswa Mandiri (Trial)')))) }}
                    </div>
                  </div>
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Password</span>
                    <div class="text-sm font-mono text-white/70">•••••••• <span class="text-[10px] text-emerald-400 ml-1.5 font-bold font-sans">(Tersimpan aman)</span></div>
                  </div>
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 md:col-span-2">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Asal Sekolah</span>
                    <div class="text-sm font-bold text-white flex items-center gap-2">
                      <i class="ph-bold ph-graduation-cap text-[#c0ff00] text-base"></i>
                      <span>{{ currentUser?.school || profileEditForm.school || 'Belum ditautkan' }}</span>
                      <span v-if="currentUser?.school_id || profileEditForm.school_id" class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30">Mitra Resmi</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Grid Informasi Atribut Rekening -->
              <div class="space-y-4 max-w-4xl pt-2">
                <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-emerald-400">
                  <span>🏦 Rekening &amp; E-Wallet Pencairan Komisi Afiliasi</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Bank / E-Wallet</span>
                    <div class="text-sm font-bold text-white">{{ profileEditForm.bank_name || '-' }}</div>
                  </div>
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">No. Rek / No. HP</span>
                    <div class="text-sm font-mono font-medium text-white">{{ profileEditForm.bank_account || '-' }}</div>
                  </div>
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <span class="block text-[10px] font-black uppercase tracking-wider text-white/40 mb-1">Atas Nama (a.n)</span>
                    <div class="text-sm font-bold text-white">{{ profileEditForm.bank_owner || '-' }}</div>
                  </div>
                </div>
                <p v-if="!profileEditForm.bank_name && !profileEditForm.bank_account" class="text-xs text-white/40 italic">
                  * Data rekening belum diisi. Klik tombol "Edit Data Pengaturan" untuk melengkapi rekening penerimaan komisi afiliasi Anda.
                </p>
              </div>
            </div>

            <!-- Edit Mode (Form Input) -->
            <form v-else @submit.prevent="saveProfileAndBankSettings" class="space-y-8 max-w-3xl animate-fade-in">
              <div class="flex items-center justify-between pb-6 border-b border-white/10">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-lg">
                    ✏️
                  </div>
                  <div>
                    <h4 class="text-white font-black text-lg tracking-wide">Edit Data Akun &amp; Rekening</h4>
                    <p class="text-xs text-white/50">Perbarui informasi nama, email, password, dan rekening pencairan</p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="cancelEditingProfile"
                  class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all cursor-pointer"
                >
                  Batal
                </button>
              </div>

              <!-- Section 1: Profil Akun -->
              <div class="space-y-5">
                <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-amber-400">
                  <span>👤 Data Profil</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <!-- Nama Lengkap -->
                  <div>
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">Nama Lengkap</label>
                    <input
                      v-model="profileEditForm.name"
                      type="text"
                      placeholder="Masukkan nama lengkap"
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 focus:bg-white/10 transition-all font-medium"
                    />
                  </div>

                  <!-- Email -->
                  <div>
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">Email Utama</label>
                    <input
                      v-model="profileEditForm.email"
                      type="email"
                      placeholder="contoh@email.com"
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 focus:bg-white/10 transition-all font-mono"
                    />
                  </div>

                  <!-- Asal Sekolah -->
                  <div class="md:col-span-2">
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">Asal Sekolah</label>
                    <input
                      v-model="profileEditForm.school"
                      list="profileSchoolsDatalist"
                      type="text"
                      placeholder="Masukkan nama SMA / SMK / SMP Anda"
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 focus:bg-white/10 transition-all font-medium"
                    />
                    <datalist id="profileSchoolsDatalist">
                      <option v-for="sch in publicSchoolsList" :key="sch.id" :value="sch.name">
                        {{ sch.city ? sch.city : '' }}
                      </option>
                    </datalist>
                  </div>

                  <!-- Password Baru -->
                  <div class="md:col-span-2">
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">
                      Password Baru <span class="text-white/40 font-normal lowercase">(kosongkan jika tidak ingin diubah)</span>
                    </label>
                    <input
                      v-model="profileEditForm.password"
                      type="password"
                      placeholder="Minimal 6 karakter..."
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 focus:bg-white/10 transition-all font-mono"
                    />
                  </div>
                </div>
              </div>

              <!-- Section 2: Rekening Pencairan -->
              <div class="space-y-5 pt-4 border-t border-white/10">
                <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-emerald-400">
                  <span>🏦 Rekening &amp; E-Wallet Pencairan</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                  <!-- Bank / E-Wallet -->
                  <div>
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">Bank / E-Wallet</label>
                    <input
                      v-model="profileEditForm.bank_name"
                      type="text"
                      placeholder="Contoh: BCA, Mandiri, GoPay, OVO"
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-400 focus:bg-white/10 transition-all font-bold"
                    />
                  </div>

                  <!-- No Rekening -->
                  <div>
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">No. Rek / No. HP</label>
                    <input
                      v-model="profileEditForm.bank_account"
                      type="text"
                      placeholder="Contoh: 1234567890"
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-400 focus:bg-white/10 transition-all font-mono"
                    />
                  </div>

                  <!-- Nama Pemilik -->
                  <div>
                    <label class="block text-xs uppercase font-black tracking-wider text-white/60 mb-2">Atas Nama (a.n)</label>
                    <input
                      v-model="profileEditForm.bank_owner"
                      type="text"
                      placeholder="Nama Sesuai Rekening"
                      class="w-full bg-white/5 border border-white/15 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-400 focus:bg-white/10 transition-all font-medium"
                    />
                  </div>
                </div>
              </div>

              <!-- Save / Cancel Buttons -->
              <div class="pt-6 border-t border-white/10 flex items-center justify-end gap-3">
                <button
                  type="button"
                  @click="cancelEditingProfile"
                  class="px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs uppercase tracking-wider transition-all cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="isSavingProfile"
                  class="px-8 py-3.5 rounded-2xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-sm uppercase tracking-wider shadow-[0_0_20px_rgba(192,255,0,0.3)] hover:scale-105 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 cursor-pointer"
                >
                  <svg v-if="isSavingProfile" class="animate-spin -ml-1 mr-2 h-4 w-4 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ isSavingProfile ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Card Sesi Akun / Logout di Tab Pengaturan -->
          <div class="glass-card p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-rose-500/20 bg-rose-500/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                <i class="ph-bold ph-sign-out text-lg"></i>
              </div>
              <div>
                <h4 class="text-sm font-black text-white">Keluar dari Akun</h4>
                <p class="text-xs text-white/50">Akhiri sesi login siswa pada perangkat ini</p>
              </div>
            </div>
            <button
              @click="logout"
              class="px-5 py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-rose-300 hover:text-rose-200 font-bold text-xs transition-all flex items-center justify-center gap-2 active:scale-95 cursor-pointer shrink-0"
            >
              <i class="ph-bold ph-sign-out text-base"></i>
              <span>Keluar Akun</span>
            </button>
          </div>
        </section>


        <!-- TAB 1: DASHBOARD -->
        <section v-if="currentTab === 'dashboard'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">

          <!-- ✨ Banner Gambar Dashboard Belajar (PALING ATAS - Proporsional di HP & Desktop) -->
          <div class="glass-card rounded-2xl sm:rounded-3xl overflow-hidden border border-white/10 shadow-[0_16px_50px_rgba(0,0,0,0.5)] relative group">
            <img src="/2.jpeg" alt="EduPath Student Banner" class="w-full h-32 sm:h-44 md:h-56 lg:h-64 object-cover object-center transition-transform duration-700 group-hover:scale-105" />
            <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-[#c0ff00] to-transparent opacity-90 z-10"></div>
          </div>

          <!-- ✨ Text & Tombol Greeting (DI LETAKAN DI BAWAH GAMBAR) -->
          <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-white/10 shadow-[0_16px_40px_rgba(0,0,0,0.5)] bg-gradient-to-r from-slate-900/95 via-slate-900/85 to-slate-950 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sm:gap-6">
            <!-- Left: Avatar & Greeting Info -->
            <div class="flex items-start md:items-center gap-3 sm:gap-4 flex-1 min-w-0">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-gradient-to-br from-[#c0ff00]/25 via-emerald-500/15 to-transparent border border-[#c0ff00]/40 shadow-[0_0_25px_rgba(192,255,0,0.3)] flex items-center justify-center shrink-0 backdrop-blur-md">
                <i class="ph-fill ph-user text-xl sm:text-2xl text-[#c0ff00]"></i>
              </div>
              <div class="space-y-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[#c0ff00] animate-pulse"></span>
                  <p class="text-[#c0ff00] font-black text-[10px] sm:text-xs tracking-widest uppercase">Selamat belajar hari ini</p>
                </div>
                <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-white tracking-tight leading-snug">Halo, <span class="bg-gradient-to-r from-white via-slate-100 to-white/70 bg-clip-text text-transparent">{{ currentUser?.name || (isSchoolBk ? (currentUser?.school_name || 'Guru BK') : 'Siswa Mandiri') }}!</span></h2>
                <p class="text-white/70 text-xs md:text-sm font-medium leading-relaxed">Persiapkan SNBT 2027 dengan asesmen IRT adaptif &amp; latihan soal terfokus.</p>
              </div>
            </div>

            <!-- Right: Shortcut Actions & Quick Info -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full lg:w-auto shrink-0 pt-1 lg:pt-0">
              <button @click="handleTabClick('diagnostic')" class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black text-xs font-black uppercase tracking-wider transition-all active:scale-95 shadow-[0_0_20px_rgba(192,255,0,0.3)] cursor-pointer">
                <i class="ph-bold ph-exam text-base"></i>
                <span>Mulai Asesmen</span>
              </button>
              <div class="grid grid-cols-2 gap-2 w-full sm:w-auto sm:flex sm:items-center">
                <button @click="handleTabClick('learning')" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-bold transition-all active:scale-95 cursor-pointer backdrop-blur-md">
                  <i class="ph-bold ph-book-open text-base text-white/70"></i>
                  <span>Materi</span>
                </button>
                <button @click="handleTabClick('practice')" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-bold transition-all active:scale-95 cursor-pointer backdrop-blur-md">
                  <i class="ph-bold ph-pencil-simple text-base text-white/70"></i>
                  <span>Latihan Soal</span>
                </button>
              </div>
            </div>
          </div>

          <!-- ✨ Target PTN Selection & Goal Status -->
          <div class="glass-card p-4 md:p-5 rounded-2xl border border-sky-500/30 relative overflow-hidden bg-gradient-to-r from-slate-900/90 via-slate-900/70 to-slate-950 shadow-[0_8px_32px_rgba(0,0,0,0.4)]">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <!-- Left: Target Selector -->
              <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-400/30 flex items-center justify-center text-sky-400 text-lg shrink-0 shadow-[0_0_15px_rgba(56,189,248,0.2)]">
                  🎯
                </div>
                <div class="space-y-0.5 min-w-0">
                  <span class="block text-[9px] font-black uppercase tracking-widest text-sky-400">Target PTN Impian Anda</span>
                  <div class="relative inline-block max-w-full">
                    <select
                      v-model="selectedUniversity"
                      @change="recalcTargetGap"
                      class="bg-black/60 border border-white/15 text-white text-xs md:text-sm font-black py-1.5 px-3 pr-8 rounded-lg appearance-none outline-none focus:border-sky-400 shadow-inner transition-colors cursor-pointer max-w-[200px] xs:max-w-xs sm:max-w-sm truncate"
                    >
                      <option v-for="u in universities" :key="u.name" :value="u" class="bg-slate-900 text-white font-bold">
                        {{ u.name }} (Target: {{ u.targetScore }})
                      </option>
                    </select>
                    <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-sky-400 text-xs">
                      <i class="ph-bold ph-caret-down"></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right: Target Status Badge -->
              <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl bg-sky-500/10 border border-sky-500/25 text-sky-300 text-xs font-bold flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                  <span>Target: <strong class="text-white font-mono">{{ selectedUniversity.targetScore }}</strong></span>
                </span>
                <button @click="handleTabClick('diagnostic')" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center gap-1 cursor-pointer">
                  <span>Tes Ulang IRT</span>
                  <i class="ph-bold ph-arrow-clockwise"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- ✨ Executive KPI Strip (4 Kolom Proporsional & Presisi) -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Skor IRT Terkini -->
            <div class="glass-card p-4 md:p-5 rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-950/20 via-slate-900/80 to-slate-950 flex flex-col justify-between">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Skor IRT Terkini</span>
                <span class="px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-300 text-[10px] font-bold font-mono">TOP 12%</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-emerald-400 font-mono drop-shadow-[0_0_12px_rgba(52,211,153,0.3)]">{{ currentAbilityScore }}</span>
                <span class="text-xs text-white/50 font-bold">/ 1000</span>
              </div>
              <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between text-[10px] text-white/60">
                <span>Status Kemampuan</span>
                <span class="text-emerald-400 font-bold">Kompetitif</span>
              </div>
            </div>

            <!-- Card 2: Target Kampus Impian -->
            <div class="glass-card p-4 md:p-5 rounded-2xl border border-sky-500/30 bg-gradient-to-br from-sky-950/20 via-slate-900/80 to-slate-950 flex flex-col justify-between">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-sky-400">Target Skor PTN</span>
                <span class="px-2 py-0.5 rounded-md bg-sky-500/15 text-sky-300 text-[10px] font-bold">SNBT 2027</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-sky-400 font-mono drop-shadow-[0_0_12px_rgba(56,189,248,0.3)]">{{ selectedUniversity.targetScore }}</span>
                <span class="text-xs text-white/50 font-medium truncate max-w-[120px]">{{ selectedUniversity.name }}</span>
              </div>
              <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between text-[10px] text-white/60">
                <span>Peluang Masuk</span>
                <span class="text-sky-400 font-bold">Terjangkau</span>
              </div>
            </div>

            <!-- Card 3: Gap & Progres Capaian -->
            <div class="glass-card p-4 md:p-5 rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-950/20 via-slate-900/80 to-slate-950 flex flex-col justify-between">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-400">Selisih Gap Target</span>
                <span class="px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-300 text-[10px] font-mono font-bold">{{ progressPercentage }}%</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-rose-400 font-mono">{{ gapScore }}</span>
                <span class="text-xs text-white/50 font-bold">Poin tersisa</span>
              </div>
              <div class="w-full bg-white/10 h-1.5 rounded-full mt-2 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-400 to-[#c0ff00] h-full rounded-full transition-all duration-700" :style="{ width: progressPercentage + '%' }"></div>
              </div>
            </div>

            <!-- Card 4: Streak Belajar Konsisten -->
            <div class="glass-card p-4 md:p-5 rounded-2xl border border-indigo-500/30 bg-gradient-to-br from-indigo-950/20 via-slate-900/80 to-slate-950 flex flex-col justify-between">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Streak Konsistensi</span>
                <span class="px-2 py-0.5 rounded-md bg-indigo-500/15 text-indigo-300 text-[10px] font-bold">AKTIF</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-indigo-400 font-mono">🔥 {{ streakCount }}</span>
                <span class="text-xs text-white/50 font-bold">Hari Berturut</span>
              </div>
              <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between text-[10px] text-white/60">
                <span>Kebiasaan Belajar</span>
                <span class="text-[#c0ff00] font-bold">Pertahankan</span>
              </div>
            </div>
          </div>

          <!-- ✨ Dual Primary Cockpit (Grid 2 Kolom Seimbang: 7 / 5) -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Kolom Kiri (7/12): Skill Map Scanner & Misi Harian -->
            <div class="lg:col-span-7 space-y-6">
              
              <!-- Skill Map Scanner -->
              <div class="glass-card p-5 md:p-6 rounded-2xl border border-white/10">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-400 flex items-center justify-center border border-sky-500/30 shadow-sm">
                      <i class="ph-bold ph-radar text-xl"></i>
                    </div>
                    <div>
                      <h3 class="text-lg font-black font-heading text-white tracking-tight">Skill Map Scanner</h3>
                      <p class="text-xs text-white/60 font-medium">Pemetaan kekuatan &amp; kelemahan per domain UTBK berbasis AI.</p>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                  <div v-for="(skills, category) in skillMap" :key="category" class="bg-white/5 border border-white/10 p-4 rounded-2xl">
                    <h4 class="text-xs font-black text-white mb-3.5 border-b border-white/10 pb-2 flex items-center gap-2 uppercase tracking-widest">
                      <span class="w-2 h-2 bg-[#0ea5e9] rounded-full shadow-[0_0_8px_rgba(14,165,233,0.8)]"></span>
                      {{ category }}
                    </h4>
                    <div class="space-y-3">
                      <div v-for="(subskills, subtest) in skills" :key="subtest" class="space-y-1.5">
                        <div class="flex justify-between text-[11px] font-bold">
                          <span class="text-white/80">{{ subtest }}</span>
                          <span class="font-mono font-black" :style="{ color: getSkillColor(getSkillMastery(subtest)) }">{{ getSkillMastery(subtest) }}%</span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-1.5 overflow-hidden">
                          <div class="h-full rounded-full transition-all duration-700 ease-out" :style="{ width: getSkillMastery(subtest) + '%', backgroundColor: getSkillColor(getSkillMastery(subtest)), boxShadow: `0 0 10px ${getSkillColor(getSkillMastery(subtest))}` }"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Misi Harian Card -->
              <div class="glass-card p-5 md:p-6 rounded-2xl border border-amber-500/20">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-lg border border-amber-500/30">
                      <i class="ph-fill ph-sword"></i>
                    </span>
                    <div>
                      <h3 class="text-base font-black font-heading text-white tracking-tight">Misi Harian Belajar</h3>
                      <p class="text-[11px] text-white/50">Selesaikan misi untuk mendapatkan XP dan menaikkan level persiapan.</p>
                    </div>
                  </div>
                  <span class="px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-400 font-mono text-xs font-black">+{{ dailyMissions.reduce((acc, m) => acc + (m.completed ? m.reward : 0), 0) }} XP</span>
                </div>
                <ul class="space-y-2.5">
                  <li v-for="(m, index) in dailyMissions" :key="index" :class="['flex items-center gap-3 p-3.5 rounded-xl border transition-all duration-300', m.completed ? 'bg-emerald-500/10 border-emerald-500/25' : 'bg-white/5 border-white/10 hover:border-white/20']">
                    <input type="checkbox" v-model="m.completed" class="rounded-md border-white/20 bg-white text-emerald-400 focus:ring-emerald-500 focus:ring-offset-0 w-4 h-4 cursor-pointer appearance-none checked:bg-emerald-500/100 checked:border-emerald-500 relative checked:after:content-['✓'] checked:after:absolute checked:after:text-black checked:after:text-[10px] checked:after:font-black checked:after:left-[3px] checked:after:top-[0px]" @change="checkMissionReward(m)">
                    <span :class="['text-xs font-bold flex-grow text-white transition-all', { 'line-through opacity-40': m.completed }]">{{ m.title }}</span>
                    <span class="text-xs font-black font-mono shrink-0 text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">+{{ m.reward }} XP</span>
                  </li>
                </ul>
              </div>

            </div>

            <!-- Kolom Kanan (5/12): Radar Blind-Spot, Jalur AI & Peringkat Streak -->
            <div class="lg:col-span-5 space-y-6">

              <!-- Radar Blind-Spot & Kelemahan Card -->
              <div class="glass-card p-5 md:p-6 rounded-2xl border border-rose-500/25 bg-gradient-to-br from-slate-900/90 via-slate-900/70 to-rose-950/20">
                <div class="flex items-center justify-between mb-4 border-b border-white/10 pb-3">
                  <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-base border border-rose-500/30">
                      <i class="ph-bold ph-radar"></i>
                    </span>
                    <div>
                      <h4 class="text-sm font-black text-white tracking-tight uppercase">Radar Blind-Spot AI</h4>
                      <p class="text-[10px] text-white/50">Prioritas materi yang perlu diperbaiki segera</p>
                    </div>
                  </div>
                  <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30">1 Kritis</span>
                </div>

                <div class="space-y-3">
                  <!-- Skill 1: Geometri (Critical) -->
                  <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/25 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                      <span class="font-bold text-white">Geometri Bangun Ruang (3D)</span>
                      <span class="text-rose-400 font-mono font-black text-[11px]">35% · Kritis</span>
                    </div>
                    <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-rose-500 h-full rounded-full" style="width: 35%;"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                      <span class="text-white/60">Skor hilang: <strong class="text-rose-400">-35 Poin</strong></span>
                      <button @click="handleTabClick('learning')" class="font-black text-[#c0ff00] hover:underline cursor-pointer">
                        Drill 5 Menit &raquo;
                      </button>
                    </div>
                  </div>

                  <!-- Skill 2: Silogisme (Moderate) -->
                  <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/25 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                      <span class="font-bold text-white">Silogisme &amp; Logika Majemuk</span>
                      <span class="text-amber-400 font-mono font-black text-[11px]">60% · Pemantapan</span>
                    </div>
                    <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-amber-500 h-full rounded-full" style="width: 60%;"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                      <span class="text-white/60">Akurasi: <strong class="text-white/80">6/10 Soal</strong></span>
                      <button @click="handleTabClick('practice')" class="font-black text-amber-300 hover:underline cursor-pointer">
                        Latihan &raquo;
                      </button>
                    </div>
                  </div>

                  <!-- Skill 3: Aljabar (Strong) -->
                  <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/25 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                      <span class="font-bold text-white">Aljabar &amp; Persamaan Kuadrat</span>
                      <span class="text-emerald-400 font-mono font-black text-[11px]">85% · Kuasai</span>
                    </div>
                    <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-emerald-500 h-full rounded-full" style="width: 85%;"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                      <span class="text-white/60">Tingkat akurasi tinggi</span>
                      <span class="text-emerald-400 font-mono font-bold">AMAN</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Jalur AI Rekomendasi Belajar -->
              <div class="glass-card p-5 md:p-6 rounded-2xl border border-purple-500/25 bg-gradient-to-br from-slate-900/90 via-slate-900/70 to-purple-950/20">
                <div class="flex items-center gap-2.5 mb-3 pb-2 border-b border-white/10">
                  <i class="ph-bold ph-lightning text-xl text-[#8b5cf6]"></i>
                  <div>
                    <h3 class="text-base font-black font-heading text-white tracking-tight">Jalur AI Personal</h3>
                    <p class="text-[10px] text-white/50">Rekomendasi belajar adaptif otomatis</p>
                  </div>
                </div>
                
                <div class="space-y-4 pt-1">
                  <div v-for="(rec, i) in learningRecommendations" :key="i" class="flex gap-3 relative group">
                    <div v-if="i < learningRecommendations.length - 1" class="absolute left-3.5 top-8 bottom-0 w-[2px] bg-white/10 group-hover:bg-[#8b5cf6]/50 transition-colors"></div>
                    <div class="w-7 h-7 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center font-black font-mono text-xs text-[#8b5cf6] shrink-0 z-10 group-hover:bg-[#8b5cf6] group-hover:text-white transition-all">
                      {{ i + 1 }}
                    </div>
                    <div class="pt-0.5">
                      <h5 class="text-xs font-black text-white leading-tight mb-1">{{ rec.title }}</h5>
                      <p class="text-[11px] text-white/50 font-medium leading-relaxed">{{ rec.desc }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Peringkat Streak Belajar Card -->
              <div class="glass-card p-5 md:p-6 rounded-2xl border border-indigo-500/25">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-white/10">
                  <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-base border border-indigo-500/30">
                      <i class="ph-bold ph-fire"></i>
                    </span>
                    <div>
                      <h3 class="text-sm font-black font-heading text-white tracking-tight">Peringkat Streak Belajar</h3>
                      <p class="text-[10px] text-white/50">Konsistensi harian kunci kelulusan PTN</p>
                    </div>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 text-xs font-black font-mono">🔥 {{ streakCount }} Hari</span>
                </div>
                <ul class="space-y-2">
                  <li class="flex items-center gap-2.5 p-2 rounded-xl bg-gradient-to-r from-indigo-500/20 to-purple-500/10 border border-indigo-500/30">
                    <span class="w-6 h-6 rounded-full bg-[#c0ff00] text-black flex items-center justify-center font-mono font-black text-xs">1</span>
                    <div class="flex-grow min-w-0">
                      <div class="text-xs font-bold text-white truncate">{{ currentUser?.name || 'Anda (Siswa Mandiri)' }}</div>
                      <div class="text-[10px] text-[#c0ff00] font-semibold">Streak Aktif</div>
                    </div>
                    <span class="text-xs text-[#c0ff00] font-black font-mono shrink-0">{{ streakCount }} Hari</span>
                  </li>
                  <li class="flex items-center gap-2.5 p-2 rounded-xl bg-white/5 border border-white/5">
                    <span class="w-6 h-6 rounded-full bg-white/10 text-white/70 flex items-center justify-center font-mono font-bold text-xs">2</span>
                    <span class="text-xs font-medium text-white/80 flex-grow truncate">Budi Santoso</span>
                    <span class="text-xs text-white/50 font-mono shrink-0">6 Hari</span>
                  </li>
                  <li class="flex items-center gap-2.5 p-2 rounded-xl bg-white/5 border border-white/5">
                    <span class="w-6 h-6 rounded-full bg-white/10 text-white/70 flex items-center justify-center font-mono font-bold text-xs">3</span>
                    <span class="text-xs font-medium text-white/80 flex-grow truncate">Citra Lestari</span>
                    <span class="text-xs text-white/50 font-mono shrink-0">5 Hari</span>
                  </li>
                  <li class="flex items-center gap-2.5 p-2 rounded-xl bg-white/5 border border-white/5">
                    <span class="w-6 h-6 rounded-full bg-white/10 text-white/70 flex items-center justify-center font-mono font-bold text-xs">4</span>
                    <span class="text-xs font-medium text-white/80 flex-grow truncate">Dewi Setyowati</span>
                    <span class="text-xs text-white/50 font-mono shrink-0">5 Hari</span>
                  </li>
                </ul>
              </div>

            </div>

          </div>
        </section>

        <!-- TAB 2: DIAGNOSTIC & TRY OUT -->
        <section v-if="currentTab === 'diagnostic'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">
          <!-- Top Close & Back Bar -->
          <div class="bg-slate-900/95 border border-white/15 rounded-2xl p-3 sm:p-4 backdrop-blur-xl shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Top row on mobile / Left on desktop -->
            <div class="flex items-center justify-between w-full sm:w-auto">
              <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-500/15 text-purple-400 border border-purple-500/30 flex items-center justify-center text-lg sm:text-xl font-black shrink-0">
                  <i class="ph-bold ph-exam"></i>
                </div>
                <div class="min-w-0">
                  <span class="block text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-purple-400 truncate">Asesmen Diagnostik Adaptif</span>
                  <h2 class="text-sm sm:text-base md:text-lg font-black text-white leading-tight truncate">Asesmen Diagnostik &amp; Pemetaan Kemampuan</h2>
                </div>
              </div>
              <!-- Tutup button visible immediately on top right for mobile -->
              <button 
                @click="currentTab = (isLoggedIn ? 'dashboard' : 'home')" 
                class="sm:hidden w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all shadow-sm shrink-0 ml-2 cursor-pointer"
                title="Tutup Halaman & Kembali"
                aria-label="Tutup"
              >
                <i class="ph-bold ph-x text-sm"></i>
              </button>
            </div>

            <!-- Actions: Desktop & Mobile secondary bar -->
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t border-white/10 sm:border-0">
              <button 
                type="button"
                @click="showIrtInfo = !showIrtInfo" 
                class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 text-purple-300 border border-purple-500/30 text-xs font-bold transition-all shadow-md cursor-pointer"
                title="Sembunyikan / Tampilkan Kotak IRT"
              >
                <span>{{ showIrtInfo ? 'Sembunyikan Panel IRT' : 'Tampilkan Panel IRT' }}</span>
                <i :class="showIrtInfo ? 'ph-bold ph-eye-slash' : 'ph-bold ph-eye'"></i>
              </button>
              <button 
                @click="currentTab = (isLoggedIn ? 'dashboard' : 'home')" 
                class="hidden sm:flex w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 items-center justify-center transition-all shadow-sm cursor-pointer shrink-0"
                title="Tutup Halaman & Kembali"
                aria-label="Tutup"
              >
                <i class="ph-bold ph-x text-sm"></i>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-6" :class="showIrtInfo ? 'lg:grid-cols-3' : ''">
            <div class="light-mode-card rounded-2xl p-4 sm:p-6 lg:p-8" :class="showIrtInfo ? 'lg:col-span-2' : 'w-full'">
              <h3 class="text-xl font-black font-heading text-slate-900 mb-2">Diagnostic &amp; Adaptive Simulation Test</h3>
              <p v-if="!diagnosticFocus" class="text-xs text-slate-600 mb-5 font-medium">
                Sistem simulasi ujian dengan Item Response Theory (IRT). Kesulitan soal dinamis sesuai respons kemampuan Anda.
              </p>

              <!-- Panduan Komunikatif: Kapan & Berapa Kali Tes Ini Dilakukan? -->
              <div v-if="!diagnosticActive && !diagnosticFinished" class="mb-6 p-4 md:p-5 rounded-2xl bg-gradient-to-r from-indigo-50/90 via-purple-50/70 to-blue-50/90 border border-indigo-200/80 text-left space-y-3">
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs shrink-0 shadow-sm">
                    <i class="ph-bold ph-info"></i>
                  </span>
                  <h4 class="text-xs md:text-sm font-black text-slate-900 uppercase tracking-wide">Panduan Alur: Kapan &amp; Berapa Kali Tes Ini Dilakukan?</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div class="p-3 rounded-xl bg-white/95 border border-indigo-100 shadow-sm space-y-1">
                    <div class="flex items-center gap-1.5 text-indigo-700 font-black text-xs">
                      <span class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[11px] font-bold">1</span>
                      <span>Asesmen Awal (1x Saat Gabung)</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed font-normal">
                      Wajib diambil <strong>1 kali di awal</strong> untuk memetakan kekuatan, kelemahan, dan blind-spot materi Anda secara akurat.
                    </p>
                  </div>
                  <div class="p-3 rounded-xl bg-white/95 border border-purple-100 shadow-sm space-y-1">
                    <div class="flex items-center gap-1.5 text-purple-700 font-black text-xs">
                      <span class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center text-[11px] font-bold">2</span>
                      <span>Belajar Modul &amp; Drill Soal</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed font-normal">
                      Selesaikan materi &amp; drill latihan terfokus sesuai urutan rekomendasi AI yang otomatis disusun setelah tes awal.
                    </p>
                  </div>
                  <div class="p-3 rounded-xl bg-white/95 border border-blue-100 shadow-sm space-y-1">
                    <div class="flex items-center gap-1.5 text-blue-700 font-black text-xs">
                      <span class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-[11px] font-bold">3</span>
                      <span>Evaluasi Berkala (Tiap 2-4 Minggu)</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed font-normal">
                      Ulangi tes diagnostic secara berkala <strong>tiap 2-4 minggu</strong> untuk memantau lonjakan skor IRT dan peluang lolos PTN.
                    </p>
                  </div>
                </div>
              </div>

              <div v-if="!diagnosticActive && !diagnosticFinished" class="text-center py-12 bg-white/5 rounded-2xl border border-white/10">
                <div class="w-14 h-14 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 text-2xl mx-auto mb-4 font-black font-mono">
                  IRT
                </div>
                <h4 class="text-lg font-black font-heading text-slate-900 mb-2">Mulai Diagnostic Assessment</h4>
                <p class="text-xs text-slate-600 max-w-sm mx-auto mb-6 leading-relaxed font-medium">
                  Ukur kemampuan Aljabar, Geometri, dan Penalaran Logis awal Anda secara presisi dalam 4 soal terpilih.
                </p>
                <button class="px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition-all" @click="startDiagnostic">
                  Mulai Ujian
                </button>
              </div>

              <!-- Quiz Play state -->
              <div v-else-if="diagnosticActive && !diagnosticFinished" class="space-y-6">
                <div class="flex justify-between items-center border-b border-white/10 pb-3">
                  <span class="px-3 py-1 bg-white/10 border border-white/10 rounded-lg text-xs font-bold text-slate-700">
                    {{ currentDiagQuestion.category }} &raquo; {{ currentDiagQuestion.skill }}
                  </span>
                  <span :class="['px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider', 
                    currentDiagQuestion.difficulty === 'HOTS' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200'
                  ]">
                    {{ currentDiagQuestion.difficulty }}
                  </span>
                </div>

                <div class="space-y-3">
                  <strong class="text-xs text-purple-600 uppercase font-black tracking-widest">Pertanyaan {{ diagnosticIdx + 1 }} dari {{ diagnosticQuestions.length }}:</strong>
                  <div class="text-lg md:text-xl text-slate-900 font-bold leading-relaxed" v-html="currentDiagQuestion.question"></div>
                </div>

                <div class="grid grid-cols-1 gap-2.5">
                  <button 
                    v-for="(option, idx) in currentDiagQuestion.options" 
                    :key="idx"
                    :class="['w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all outline-none flex items-center', 
                      selectedDiagAnswer === idx 
                        ? 'bg-indigo-50 border-indigo-500 text-indigo-900 shadow-[0_8px_32px_rgba(0,0,0,0.3)]' 
                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-100'
                    ]" 
                    @click="selectedDiagAnswer = idx"
                  >
                    <span class="inline-block w-6 h-6 rounded-lg bg-white border border-slate-300 text-center leading-6 text-xs text-slate-700 font-black mr-2.5 shrink-0">{{ String.fromCharCode(65 + idx) }}</span>
                    <span class="text-sm md:text-base font-medium" v-html="option"></span>
                  </button>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-slate-200">
                  <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors" @click="skipDiagQuestion">
                    Lewati
                  </button>
                  <button class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-[0_8px_32px_rgba(0,0,0,0.3)]" :disabled="selectedDiagAnswer === null" @click="submitDiagAnswer">
                    Jawab &amp; Lanjut
                  </button>
                </div>
              </div>

              <!-- Quiz Result State -->
              <div v-else-if="diagnosticFinished" class="space-y-6">
                <div class="text-center py-6 bg-slate-50 border border-slate-200 rounded-2xl">
                  <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xl mx-auto mb-2 font-mono">✓</div>
                  <h4 class="text-lg font-black text-slate-900">Diagnostic Selesai!</h4>
                </div>

                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-950 leading-relaxed">
                  <strong class="text-rose-900 block mb-2">Analisa Gap Kelemahan:</strong> 
                  Anda memiliki akurasi <strong class="text-rose-600 font-bold font-mono">{{ diagnosticResultData?.gap_score || 0 }}%</strong> pada materi 
                  <strong class="text-rose-600">{{ diagnosticResultData?.gap_sub_materi || 'Data tidak tersedia' }}</strong> ({{ diagnosticResultData?.gap_domain || 'Data tidak tersedia' }}).
                </div>

                <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-950 leading-relaxed">
                  <strong class="text-indigo-900 block mb-2">Learning Path (Rekomendasi Belajar):</strong>
                  <ul class="list-disc pl-5 space-y-1">
                    <li v-for="(act, i) in (diagnosticResultData?.learning_path?.actions || [])" :key="i">
                      {{ act.title }}
                    </li>
                  </ul>
                </div>

                <div class="text-center">
                  <button class="px-5 py-2.5 bg-slate-100 border border-slate-200 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 transition-colors" @click="resetDiagnostic">
                    Ulangi Diagnostic Test
                  </button>
                </div>
              </div>
            </div>

            <!-- IRT Details Card (Hidden completely when showIrtInfo is false for 100% full monitor width) -->
            <div v-if="!diagnosticFocus && showIrtInfo" class="light-mode-card rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-black font-heading text-slate-900">Kenapa Memilih IRT Model?</h3>
                  <button 
                    type="button"
                    @click="showIrtInfo = false" 
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 border border-slate-200 hover:border-rose-300 flex items-center justify-center transition-all cursor-pointer shrink-0"
                    title="Tutup Kotak IRT"
                    aria-label="Tutup Kotak IRT"
                  >
                    <i class="ph-bold ph-x text-sm"></i>
                  </button>
                </div>
                <div class="space-y-3">
                  <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-xs text-slate-900 block mb-1">1. Estimasi Skor Presisi</strong>
                    <p class="text-xs text-slate-500 font-normal leading-relaxed">Ujian biasa menyamakan bobot semua soal. IRT memberi bobot lebih tinggi pada soal tersulit yang berhasil Anda jawab.</p>
                  </div>
                  <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-xs text-slate-900 block mb-1">2. Pendeteksian Misconception</strong>
                    <p class="text-xs text-slate-500 font-normal leading-relaxed">Mendeteksi pola kesalahan jawaban untuk melacak akar materi prasyarat yang terlewat.</p>
                  </div>
                  <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <strong class="text-xs text-slate-900 block mb-1">3. Umpan Balik Real-Time</strong>
                    <p class="text-xs text-slate-500 font-normal leading-relaxed">Dashboard rekomendasi Anda langsung berubah setiap kali ada peningkatan diagnostic.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 3: MATERI UTBK & MICRO-LESSONS -->
        <section v-if="currentTab === 'learning'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">
          <!-- Top Navigation Toolbar (Sleek, Compact, 1-Baris Toolbar) -->
          <div class="bg-slate-900/95 border border-white/15 rounded-2xl p-3 sm:px-4 sm:py-2.5 backdrop-blur-xl shadow-xl flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5">
            <!-- Left Side: Focus Pomodoro Timer (Menggantikan Menu Pulldown Sub Tes) -->
            <div class="flex items-center gap-2 shrink-0">
              <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 px-2.5 py-1.5 rounded-xl shadow-inner">
                <span class="w-2 h-2 rounded-full" :class="timerActive ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                <span class="text-[10px] uppercase font-black tracking-wider text-indigo-300 hidden md:inline">
                  {{ isBreak ? '☕ Break' : '🎯 Fokus' }}
                </span>
                <span class="font-mono font-black text-xs text-white tracking-wider px-0.5">{{ formattedTime }}</span>
                <button 
                  @click="toggleTimer"
                  :class="['px-2 py-0.5 rounded-lg text-xs font-black transition-all cursor-pointer flex items-center gap-1 shadow-sm active:scale-95', 
                    timerActive ? 'bg-amber-500 text-black hover:bg-amber-400' : 'bg-[#c0ff00] text-black hover:bg-[#b0ef00]']"
                  :title="timerActive ? 'Jeda Pomodoro' : 'Mulai Sesi Fokus'"
                >
                  <i :class="timerActive ? 'ph-bold ph-pause' : 'ph-bold ph-play'"></i>
                  <span class="hidden sm:inline">{{ timerActive ? 'Jeda' : 'Fokus' }}</span>
                </button>
                <button 
                  @click="resetTimer"
                  class="p-1 text-white/40 hover:text-white rounded-lg transition-colors cursor-pointer"
                  title="Reset Timer Pomodoro"
                >
                  <i class="ph-bold ph-arrows-counter-clockwise text-xs"></i>
                </button>
              </div>
            </div>

            <!-- Right Side: Pulldown Menu Sub Tes (Disamping Buka/Sembunyikan Bab) + Buka/Sembunyikan Bab + Tutup Button -->
            <div class="flex items-center gap-2 ml-auto shrink-0 flex-wrap sm:flex-nowrap">
              <!-- Subtest Selector Dropdown (Disamping Buka/Sembunyikan Bab) -->
              <div class="relative flex items-center min-w-0">
                <span class="absolute left-2.5 text-xs pointer-events-none select-none">
                  {{ selectedSubtes?.icon || '📚' }}
                </span>
                <select 
                  id="subtest-select"
                  :value="selectedSubtes ? (selectedSubtes.id || selectedSubtes.code || selectedSubtes.subtest || selectedSubtes.subtes) : ''"
                  @change="onSubtestDropdownChange($event)"
                  aria-label="Pilih Komponen & Subtes UTBK"
                  class="pl-7 pr-7 py-2 bg-slate-800/90 hover:bg-slate-800 text-[#c0ff00] font-black text-xs rounded-xl border border-white/20 focus:border-[#c0ff00] focus:ring-1 focus:ring-[#c0ff00] outline-none transition-all shadow-sm cursor-pointer appearance-none max-w-[190px] sm:max-w-xs truncate"
                >
                  <optgroup label="Tes Potensi Skolastik (TPS)" class="bg-slate-900 text-slate-100 font-bold">
                    <option 
                      v-for="sub in (materiUtbk || []).filter(m => (m.test_component === 'TPS' || (!m.test_component && ['Penalaran Umum', 'Pengetahuan Kuantitatif', 'Pengetahuan & Pemahaman Umum', 'Pemahaman Bacaan & Menulis'].includes(m.subtest || m.subtes))))" 
                      :key="sub.id" 
                      :value="sub.id || sub.code || sub.subtest || sub.subtes"
                      class="bg-slate-900 text-white py-1"
                    >
                      {{ sub.subtest || sub.subtes }} ({{ sub.babList ? sub.babList.length : 0 }} Bab)
                    </option>
                  </optgroup>
                  <optgroup label="Tes Literasi" class="bg-slate-900 text-slate-100 font-bold">
                    <option 
                      v-for="sub in (materiUtbk || []).filter(m => (m.test_component === 'TES LITERASI' || ['Literasi Bahasa Indonesia', 'Literasi Bahasa Inggris', 'Penalaran Matematika'].includes(m.subtest || m.subtes)))" 
                      :key="sub.id" 
                      :value="sub.id || sub.code || sub.subtest || sub.subtes"
                      class="bg-slate-900 text-white py-1"
                    >
                      {{ sub.subtest || sub.subtes }} ({{ sub.babList ? sub.babList.length : 0 }} Bab)
                    </option>
                  </optgroup>
                  <optgroup v-if="(materiUtbk || []).some(m => (m.assessment_type === 'TKA' && m.track === 'saintek') || ['Matematika IPA', 'Fisika', 'Kimia', 'Biologi'].includes(m.subtest || m.subtes))" label="TKA Saintek (IPA)" class="bg-slate-900 text-emerald-400 font-bold">
                    <option 
                      v-for="sub in (materiUtbk || []).filter(m => (m.assessment_type === 'TKA' && m.track === 'saintek') || ['Matematika IPA', 'Fisika', 'Kimia', 'Biologi'].includes(m.subtest || m.subtes))" 
                      :key="sub.id" 
                      :value="sub.id || sub.code || sub.subtest || sub.subtes"
                      class="bg-slate-900 text-white py-1"
                    >
                      {{ sub.subtest || sub.subtes }} ({{ sub.babList ? sub.babList.length : 0 }} Bab)
                    </option>
                  </optgroup>
                  <optgroup v-if="(materiUtbk || []).some(m => (m.assessment_type === 'TKA' && m.track === 'soshum') || ['Sosiologi', 'Ekonomi', 'Geografi', 'Sejarah'].includes(m.subtest || m.subtes))" label="TKA Soshum (IPS)" class="bg-slate-900 text-amber-400 font-bold">
                    <option 
                      v-for="sub in (materiUtbk || []).filter(m => (m.assessment_type === 'TKA' && m.track === 'soshum') || ['Sosiologi', 'Ekonomi', 'Geografi', 'Sejarah'].includes(m.subtest || m.subtes))" 
                      :key="sub.id" 
                      :value="sub.id || sub.code || sub.subtest || sub.subtes"
                      class="bg-slate-900 text-white py-1"
                    >
                      {{ sub.subtest || sub.subtes }} ({{ sub.babList ? sub.babList.length : 0 }} Bab)
                    </option>
                  </optgroup>
                </select>
                <i class="ph-bold ph-caret-down text-white/50 text-xs absolute right-2.5 pointer-events-none"></i>
              </div>

              <!-- Toggle Sembunyikan Bab Button -->
              <button 
                type="button"
                @click="hideBabSidebar = !hideBabSidebar" 
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-[#c0ff00] border border-[#c0ff00]/30 text-xs font-bold transition-all shadow-md cursor-pointer whitespace-nowrap shrink-0"
                :title="hideBabSidebar ? 'Tampilkan Daftar Bab' : 'Sembunyikan Bab (Area Materi Luas)'"
              >
                <i :class="hideBabSidebar ? 'ph-bold ph-sidebar-simple' : 'ph-bold ph-arrows-out-line-horizontal'"></i>
                <span class="hidden sm:inline">{{ hideBabSidebar ? 'Buka Daftar Bab' : 'Sembunyikan Bab' }}</span>
                <span class="sm:hidden">{{ hideBabSidebar ? 'Buka Bab' : 'Luas' }}</span>
              </button>

              <div class="h-5 w-px bg-white/15"></div>

              <!-- Tutup Button (Kanan Atas) -->
              <button 
                type="button"
                @click="currentTab = (isLoggedIn ? 'dashboard' : 'home')" 
                class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all shadow-sm cursor-pointer shrink-0"
                title="Tutup & Kembali ke Dashboard"
                aria-label="Tutup Materi"
              >
                <i class="ph-bold ph-x text-sm"></i>
              </button>
            </div>
          </div>

          <!-- Description & Bab List -->
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            <!-- Sidebar Bab list (Collapsible / Auto-Hide for wide reading area) -->
            <div v-show="!hideBabSidebar" class="lg:col-span-1 light-mode-card rounded-2xl p-4 space-y-3 animate-fade-in">
              <div class="flex items-center justify-between px-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Daftar Bab:</h4>
                <button 
                  type="button" 
                  @click="hideBabSidebar = true" 
                  class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-0.5 cursor-pointer"
                  title="Sembunyikan sidebar agar area materi luas"
                >
                  <span>Sembunyikan</span>
                  <i class="ph-bold ph-caret-left"></i>
                </button>
              </div>
              <div class="space-y-2 max-h-[70vh] overflow-y-auto pr-1">
                <button 
                  v-for="bab in (selectedSubtes?.babList || [])" 
                  :key="bab.id"
                  :class="['w-full text-left px-3.5 py-3 rounded-xl border text-xs font-bold transition-all outline-none flex flex-col gap-1', 
                    selectedBab && selectedBab.id === bab.id 
                      ? 'bg-emerald-50 border-emerald-400 text-emerald-950 shadow-[0_8px_32px_rgba(0,0,0,0.15)]' 
                      : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-100'
                  ]"
                  @click="selectBab(bab)"
                >
                  <span class="text-slate-900">{{ bab.judul }}</span>
                </button>
              </div>
            </div>

            <!-- Content Area: Teori Singkat -> Formula Praktis -->
            <div :class="hideBabSidebar ? 'lg:col-span-4' : 'lg:col-span-3'" class="space-y-6 transition-all duration-300">
              <div v-if="selectedBab" class="light-mode-card rounded-2xl p-6 lg:p-8 space-y-6">
                <!-- 1. HEADER & TEORI SINGKAT MATERI UTBK -->
                <div class="space-y-4 border-b border-slate-200 pb-6">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-md text-xs font-black text-emerald-700 uppercase tracking-wider">
                      <i class="ph-bold ph-tree-structure"></i> SNBT &bull; {{ selectedSubtes?.subtest || selectedSubtes?.subtes || '' }}
                    </span>
                    <span v-if="selectedBab.topic" class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 border border-blue-200 rounded-md text-xs font-bold text-blue-700">
                      Topik: {{ selectedBab.topic }}
                    </span>
                    <span v-if="selectedBab.subtopic" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 border border-emerald-200 rounded-md text-xs font-bold text-emerald-700">
                      Subtopik: {{ selectedBab.subtopic }}
                    </span>
                    <span v-if="selectedBab.skill" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-md text-xs font-bold text-amber-800">
                      Skill: {{ selectedBab.skill }}
                    </span>
                  </div>
                  <h3 class="text-2xl font-black font-heading text-slate-900">{{ selectedBab.judul }}</h3>
                  
                  <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                    <h5 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ringkasan Konsep Dasar:</h5>
                    <div 
                      v-if="selectedBab.teoriSingkat"
                      class="edupath-prose text-sm text-slate-800 leading-relaxed font-normal"
                      v-html="renderMaterialHtml(selectedBab.teoriSingkat)"
                    ></div>
                    <p v-else class="text-sm text-slate-500 italic">Konten materi belum ditambahkan.</p>
                    <div v-if="selectedBab.file_url" class="pt-3 flex flex-wrap items-center gap-2.5 border-t border-slate-200/60 mt-3">
                      <a :href="selectedBab.file_url" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-md">
                        <i class="ph-bold" :class="selectedBab.file_url.includes('youtu') ? 'ph-youtube-logo text-base' : 'ph-pencil-simple-line text-base'"></i>
                        <span>{{ selectedBab.file_url.includes('youtu') ? 'Tonton Video Penjelasan Materi' : 'Buka Latihan Soal Mandiri' }}</span>
                      </a>
                    </div>
                  </div>
                </div>

                <!-- 2. FORMULA PRAKTIS -->
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-md text-xs font-black text-emerald-700 uppercase tracking-wider">
                      Formula Praktis:
                    </div>
                  </div>

                  <h4 v-if="selectedBab.microLesson && selectedBab.microLesson.title" class="text-lg font-black text-slate-900">{{ selectedBab.microLesson.title }}</h4>

                  <!-- Formula Praktis summary card -->
                  <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl">
                    <div 
                      class="edupath-prose text-sm text-slate-800 font-medium leading-relaxed"
                      v-html="renderMaterialHtml(selectedBab.formula_praktis || (selectedBab.microLesson && selectedBab.microLesson.summary) || 'Formula praktis belum ditambahkan.')"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- Fallback Empty State (Never Blank) -->
              <div v-else class="light-mode-card rounded-2xl p-8 sm:p-12 text-center space-y-4">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-sm">
                  <i class="ph-bold ph-book-open"></i>
                </div>
                <div class="space-y-1">
                  <h4 class="text-base font-black text-slate-800">Pilih Bab untuk Membaca Materi</h4>
                  <p class="text-xs text-slate-500 max-w-sm mx-auto">Silakan pilih bab materi dari panel sebelah kiri untuk membaca teori dan formula praktis.</p>
                </div>
                <button 
                  v-if="(selectedSubtes?.babList || []).length > 0"
                  type="button"
                  @click="selectBab(selectedSubtes.babList[0])"
                  class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-all shadow-md inline-flex items-center gap-1.5 cursor-pointer"
                >
                  <i class="ph-bold ph-arrow-right"></i>
                  <span>Buka Bab Pertama ({{ selectedSubtes.babList[0].judul }})</span>
                </button>
              </div>
            </div>
          </div>
        </section>

        <section v-if="currentTab === 'practice'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">
          <!-- Top Close Bar -->
          <div class="flex items-center justify-between bg-slate-900/95 border border-white/15 rounded-2xl p-3 sm:p-4 backdrop-blur-xl shadow-xl gap-2">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
              <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg sm:text-xl font-black shrink-0">
                <i class="ph-bold ph-brain"></i>
              </div>
              <div class="min-w-0">
                <span class="block text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-indigo-400 truncate">Latihan Soal Adaptif</span>
                <h2 class="text-sm sm:text-base md:text-lg font-black text-white leading-tight truncate">Practice Engine &amp; AI Tutor</h2>
              </div>
            </div>
            <button 
              @click="currentTab = (isLoggedIn ? 'dashboard' : 'home')" 
              class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all shadow-sm cursor-pointer shrink-0"
              title="Tutup Practice Engine"
              aria-label="Tutup Practice Engine"
            >
              <i class="ph-bold ph-x text-sm"></i>
            </button>
          </div>
          <div class="grid grid-cols-1 gap-6" :class="showAICompanion ? 'lg:grid-cols-3' : ''">
            <!-- Main Interactive Problem Screen -->
            <div class="light-mode-card rounded-2xl p-4 sm:p-6 lg:p-8 flex flex-col justify-between" :class="showAICompanion ? 'lg:col-span-2' : ''">
              <div>
                <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-6">
                  <h3 class="text-xl font-black font-heading text-slate-900">Practice Engine &amp; AI Tutor</h3>
                  <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 text-xs font-bold text-indigo-600 rounded-md uppercase">Latihan Aktif</span>
                </div>

                <div class="space-y-4">
                  <span class="text-xs text-indigo-600 font-black uppercase tracking-widest block">SOAL LATIHAN</span>
                  <div class="text-lg md:text-xl font-bold text-slate-900 leading-relaxed" v-html="activePracticeQuestion.question"></div>

                  <div class="grid grid-cols-1 gap-2.5 pt-3">
                    <button 
                      v-for="(opt, idx) in activePracticeQuestion.options" 
                      :key="idx"
                      :class="['w-full text-left px-4 py-3.5 rounded-xl border text-sm font-semibold transition-all outline-none flex items-center', 
                        practiceUserAnswer === idx
                          ? (practiceEvaluated && idx === activePracticeQuestion.answer ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'bg-rose-50 border-rose-500 text-rose-900')
                          : (practiceEvaluated && idx === activePracticeQuestion.answer ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-800 hover:border-slate-300 hover:bg-slate-100')
                      ]"
                      @click="practiceUserAnswer = idx"
                    >
                      <span class="inline-block w-6 h-6 rounded-lg bg-white border border-slate-300 text-center leading-6 text-xs font-black text-slate-700 mr-2.5 shrink-0">{{ String.fromCharCode(65 + idx) }}</span>
                      <span class="text-sm md:text-base font-medium" v-html="opt"></span>
                    </button>
                  </div>
                </div>
              </div>

              <div class="mt-8 border-t border-slate-200 pt-6 space-y-4">
                <div class="flex flex-wrap gap-3">
                  <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" @click="triggerAIEscalation">
                    Tanya AI Tutor
                  </button>
                  <button class="px-4 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" @click="triggerSOSCall">
                    SOS Live Tutor
                  </button>
                  <button class="ml-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-[0_8px_32px_rgba(0,0,0,0.3)]" :disabled="practiceUserAnswer === null" @click="checkPracticeAnswer">
                    Kirim Jawaban
                  </button>
                </div>

                <!-- Remedial Notification -->
                <div v-if="practiceEvaluated && practiceUserAnswer !== activePracticeQuestion.answer" class="flex gap-3.5 p-4 bg-rose-50 border border-rose-200 rounded-2xl animate-fade-in">
                  <div class="text-rose-600 font-bold text-base">!</div>
                  <div>
                    <h5 class="text-xs font-bold text-slate-900 mb-0.5">Sistem Remedial Terdeteksi</h5>
                    <p class="text-[11px] text-slate-600 leading-relaxed">Jawaban Anda belum tepat. Sistem merekomendasikan untuk mereview sub-kompetensi prasyarat di tab <strong class="text-indigo-600 cursor-pointer hover:underline" @click="currentTab = 'learning'">Micro Lessons</strong> terlebih dahulu.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Interactive AI Tutor Companion Panel -->
            <div v-if="showAICompanion" class="light-mode-card rounded-2xl p-6 flex flex-col justify-between min-h-[420px]">
              <div>
                <div class="flex items-start justify-between gap-3 mb-1">
                  <h3 class="text-lg font-black font-heading text-slate-900">AI Tutor Companion</h3>
                  <button class="text-xs font-bold text-slate-400 hover:text-slate-700 shrink-0" @click="showAICompanion = false">Tutup</button>
                </div>
                <p class="text-xs text-slate-500 mb-4 font-medium">Gunakan tombol petunjuk di bawah untuk bantuan terpandu.</p>

                <div class="chat-area h-64 border border-slate-200 bg-slate-50 rounded-xl p-3 overflow-y-auto space-y-3">
                  <div v-for="(chat, i) in aiChatHistory" :key="i" :class="['p-3 rounded-xl text-xs leading-relaxed max-w-[85%]', 
                    chat.sender === 'ai' ? 'bg-white text-slate-800 border border-slate-200 self-start shadow-[0_8px_32px_rgba(0,0,0,0.3)]' : (chat.sender === 'system' ? 'bg-rose-50 border border-rose-200 text-rose-700 max-w-[95%] mx-auto' : 'bg-indigo-600 text-white self-end ml-auto shadow-[0_8px_32px_rgba(0,0,0,0.3)]')
                  ]">
                    <strong class="block font-bold text-[10px] uppercase tracking-wider mb-1" :class="chat.sender === 'user' ? 'text-indigo-200' : 'text-slate-500'">{{ chat.senderName }}</strong>
                    <p>{{ chat.text }}</p>
                    <ul v-if="chat.steps" class="mt-2 space-y-1 list-disc pl-4 text-slate-600">
                      <li v-for="step in chat.steps" :key="step">{{ step }}</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="mt-4">
                <div class="grid grid-cols-2 gap-2">
                  <button class="py-2 px-3 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(1)">Level 1: Hint</button>
                  <button class="py-2 px-3 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(2)">Level 2: Konsep</button>
                  <button class="py-2 px-3 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(3)">Level 3: Langkah</button>
                  <button class="py-2 px-3 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(4)">Level 4: Solusi</button>
                </div>
              </div>
            </div>
          </div>
        </section>



        <!-- TAB 6: PARENT PORTAL -->
        <section v-if="currentTab === 'parent'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">
          <!-- Top Close & Back Bar -->
          <div class="flex items-center justify-between bg-slate-900/95 border border-white/15 rounded-2xl p-3 sm:p-4 mb-4 backdrop-blur-xl shadow-xl gap-2">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
              <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg sm:text-xl font-black shrink-0">
                <i class="ph-bold ph-users"></i>
              </div>
              <div class="min-w-0">
                <span class="block text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-indigo-400 truncate">Portal EduPath Parenting</span>
                <h2 class="text-sm sm:text-base md:text-lg font-black text-white leading-tight truncate">Parent Portal &amp; Monitoring Belajar</h2>
              </div>
            </div>
            <button 
              @click="currentTab = (isLoggedIn ? 'dashboard' : 'home')" 
              class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all shadow-sm cursor-pointer shrink-0"
              title="Tutup Halaman & Kembali"
              aria-label="Tutup"
            >
              <i class="ph-bold ph-x text-sm"></i>
            </button>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Parent insight metrics card -->
            <div class="light-mode-card rounded-2xl p-6 lg:col-span-2">
              <h3 class="text-xl font-black font-heading text-slate-900 mb-2">Parent Portal &amp; Analisis Belajar</h3>
              <p class="text-xs text-slate-500 mb-6 font-medium">Laporan analisis keterbacaan belajar ananda untuk tinjauan berkala orang tua.</p>

              <div class="space-y-6">
                <div class="grid grid-cols-3 gap-3">
                  <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-center">
                    <span class="block text-xl font-black font-mono text-indigo-600">4j 35m</span>
                    <span class="text-[10px] text-slate-500 uppercase font-bold">Durasi Belajar</span>
                  </div>
                  <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-center">
                    <span class="block text-xl font-black font-mono text-emerald-600">+8%</span>
                    <span class="text-[10px] text-slate-500 uppercase font-bold">Progres</span>
                  </div>
                  <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-center">
                    <span class="block text-xl font-black font-mono text-indigo-600">5/7 Hari</span>
                    <span class="text-[10px] text-slate-500 uppercase font-bold">Login Konsisten</span>
                  </div>
                </div>

                <div class="p-5 bg-indigo-50 border border-indigo-200 rounded-xl">
                  <strong class="text-sm text-slate-900 block mb-1">Analisis Perilaku Belajar:</strong>
                  <p class="text-xs text-slate-600 leading-relaxed font-normal">
                    Ananda menunjukkan penyelesaian sangat baik pada pemecahan soal Aljabar dasar, namun mengalami pelambatan kecepatan pengerjaan pada materi Geometri Bangun Ruang. AI menyarankan pendampingan berupa latihan visualisasi geometri minggu ini.
                  </p>
                </div>
              </div>
            </div>

            <!-- Telegram Report Simulator -->
            <div class="light-mode-card rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-lg font-black font-heading text-slate-900 mb-2">Notifikasi Laporan Telegram</h3>
                <p class="text-xs text-slate-500 mb-4 font-medium">Laporan ringkas mingguan dikirimkan otomatis ke Telegram orang tua.</p>

                <div class="border border-sky-600 rounded-2xl overflow-hidden bg-[#0088cc]">
                  <div class="bg-[#0088cc] px-4 py-2 flex items-center gap-2 border-b border-black/10">
                    <span class="w-2.5 h-2.5 bg-sky-300 rounded-full"></span>
                    <span class="text-xs font-bold text-white">EduPath Parenting Bot</span>
                  </div>
                  <div class="bg-[#e4ebf5] p-3 h-44 overflow-y-auto">
                    <div class="bg-white text-slate-900 p-3 rounded-xl text-[10px] leading-relaxed shadow-[0_8px_32px_rgba(0,0,0,0.3)] max-w-[85%] border border-black/5">
                      <p class="font-bold text-slate-900 border-b border-slate-200 pb-1 mb-1">Laporan Belajar Mingguan EduPath</p>
                      <p>Ananda belajar selama 4 jam 35 menit.</p>
                      <p>Matematika: +8%</p>
                      <p>Literasi: +4%</p>
                      <p>Penalaran: Butuh latihan lanjutan</p>
                      <p>Target PTN UI: 720</p>
                      <p class="font-bold text-sky-700 mt-1">Estimasi Kemampuan: {{ currentAbilityScore }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 p-3 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 text-[11px] flex items-center gap-2.5">
                <i class="ph-bold ph-paper-plane-tilt text-sky-600 text-base shrink-0"></i>
                <span>Laporan dikirim otomatis oleh sistem bimbel setiap hari Minggu pukul 18.00 WIB langsung ke kontak orang tua.</span>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 7: 2027 AI INTELLIGENCE & PERFORMANCE COCKPIT (Ujian 2027 Simulasi) -->
        <section v-if="currentTab === 'simulator' || currentTab === 'projection2027'" class="animate-fade-in space-y-4 sm:space-y-6 w-full max-w-7xl mx-auto pb-10">
          <!-- Top Header Bar -->
          <div class="flex items-center justify-between bg-slate-900/95 border border-white/15 rounded-2xl p-3 sm:p-4 backdrop-blur-xl shadow-xl gap-2">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
              <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 flex items-center justify-center text-lg sm:text-xl font-black shrink-0">
                <i class="ph-bold ph-chart-polar"></i>
              </div>
              <div class="min-w-0">
                <span class="block text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-purple-400 truncate">Tryout UTBK SNBT 2027</span>
                <h2 class="text-sm sm:text-base md:text-lg font-black text-white leading-tight truncate">Tryout UTBK 2027 &amp; Proyeksi IRT Adaptif</h2>
              </div>
            </div>
            <button 
              @click="currentTab = (isLoggedIn ? 'dashboard' : 'home')" 
              class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all shadow-sm cursor-pointer shrink-0"
              title="Tutup Halaman & Kembali"
              aria-label="Tutup"
            >
              <i class="ph-bold ph-x text-sm"></i>
            </button>
          </div>

          <!-- Official CBT Exam Launcher Hub (SNBT vs TKA SMA Saintek vs TKA SMA Soshum) -->
          <div class="glass-card rounded-2xl sm:rounded-3xl p-5 sm:p-6 border border-white/10 shadow-2xl bg-gradient-to-r from-slate-900/90 via-slate-900/80 to-slate-950 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/10 pb-3">
              <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-[#c0ff00] block">Simulasi Ujian Resmi CBT EduPath</span>
                <h3 class="text-base sm:text-lg font-black text-white">Pilih Program Simulasi Ujian CBT</h3>
              </div>
              <span class="text-xs text-white/50 font-medium">Sistem CBT resmi dengan timer ketat &amp; analitik IRT</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Card 1: SNBT / SNPMB -->
              <div class="p-4 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-sky-500/30 hover:border-sky-400 transition-all flex flex-col justify-between group relative overflow-hidden shadow-lg">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-sky-500/20 text-sky-300 border border-sky-500/30">SNBT / SNPMB</span>
                    <span class="text-[10px] font-mono text-white/50">195 Menit</span>
                  </div>
                  <h4 class="text-sm font-black text-white group-hover:text-sky-300 transition-colors">UTBK SNBT 2027</h4>
                  <p class="text-[11px] text-white/60 leading-relaxed">
                    Tes Potensi Skolastik (PU, PK, PBM, PPU) &amp; Tes Literasi (Indo, Inggris, MTK).
                  </p>
                </div>
                <button
                  type="button"
                  @click="window.location.hash = '#/tryout?assessment_type=SNBT'"
                  class="mt-4 w-full py-2.5 px-3 rounded-xl bg-sky-500 hover:bg-sky-400 text-black font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 shadow-md shadow-sky-500/20 active:scale-95 cursor-pointer"
                >
                  <i class="ph-bold ph-play-circle text-base"></i>
                  <span>Mulai CBT SNBT</span>
                </button>
              </div>

              <!-- Card 2: TKA Saintek -->
              <div class="p-4 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-emerald-500/30 hover:border-emerald-400 transition-all flex flex-col justify-between group relative overflow-hidden shadow-lg">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">TKA SMA IPA</span>
                    <span class="text-[10px] font-mono text-white/50">90 Menit</span>
                  </div>
                  <h4 class="text-sm font-black text-white group-hover:text-emerald-300 transition-colors">TKA Saintek</h4>
                  <p class="text-[11px] text-white/60 leading-relaxed">
                    Matematika IPA, Fisika, Kimia, dan Biologi untuk Ujian Mandiri PTN &amp; Ujian Sekolah.
                  </p>
                </div>
                <button
                  type="button"
                  @click="window.location.hash = '#/tryout?assessment_type=TKA&track=saintek'"
                  class="mt-4 w-full py-2.5 px-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 shadow-md shadow-emerald-500/20 active:scale-95 cursor-pointer"
                >
                  <i class="ph-bold ph-play-circle text-base"></i>
                  <span>Mulai CBT Saintek</span>
                </button>
              </div>

              <!-- Card 3: TKA Soshum -->
              <div class="p-4 rounded-2xl bg-white/[0.03] hover:bg-white/[0.06] border border-amber-500/30 hover:border-amber-400 transition-all flex flex-col justify-between group relative overflow-hidden shadow-lg">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30">TKA SMA IPS</span>
                    <span class="text-[10px] font-mono text-white/50">90 Menit</span>
                  </div>
                  <h4 class="text-sm font-black text-white group-hover:text-amber-300 transition-colors">TKA Soshum</h4>
                  <p class="text-[11px] text-white/60 leading-relaxed">
                    Sosiologi, Ekonomi, Geografi, dan Sejarah untuk Ujian Mandiri PTN &amp; Ujian Sekolah.
                  </p>
                </div>
                <button
                  type="button"
                  @click="window.location.hash = '#/tryout?assessment_type=TKA&track=soshum'"
                  class="mt-4 w-full py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/20 active:scale-95 cursor-pointer"
                >
                  <i class="ph-bold ph-play-circle text-base"></i>
                  <span>Mulai CBT Soshum</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Main CBT Exam Simulation Grid (Official SNPMB Interface) -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            
            <!-- Left Workspace (Col 8): Official CBT Question Area -->
            <div class="lg:col-span-8 space-y-4">
              
              <!-- Subtest Selector Bar (Official SNPMB Subtests) -->
              <div class="bg-slate-900/90 border border-white/10 rounded-2xl p-3 shadow-lg">
                <div class="flex items-center justify-between gap-2 mb-2 pb-2 border-b border-white/10">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#c0ff00] animate-pulse shrink-0"></span>
                    <span class="text-xs font-black uppercase tracking-wider text-white shrink-0">Subtes:</span>
                    <span class="text-xs font-bold text-[#c0ff00] truncate">
                      {{ activeSubtest === 'PU' ? 'Penalaran Umum (PU)' : 
                         activeSubtest === 'PK' ? 'Pengetahuan Kuantitatif (PK)' : 
                         activeSubtest === 'PBM' ? 'Pemahaman Bacaan & Menulis (PBM)' : 
                         activeSubtest === 'PPU' ? 'Pengetahuan & Pemahaman Umum (PPU)' : 
                         activeSubtest === 'LIndo' ? 'Literasi Bahasa Indonesia' : 
                         activeSubtest === 'LEng' ? 'Literasi Bahasa Inggris' : 'Penalaran Matematika (PM)' }}
                    </span>
                  </div>
                  <span class="text-[11px] font-mono text-white/60 font-bold shrink-0 whitespace-nowrap">
                    {{ currentSubtestQuestions.length }} Soal
                  </span>
                </div>

                <div class="flex gap-2 overflow-x-auto pb-2 -mx-1 px-1 scroll-smooth no-scrollbar">
                  <button 
                    v-for="sub in [
                      { id: 'PU', name: 'PU', fullName: 'Penalaran Umum (30)' },
                      { id: 'PK', name: 'PK', fullName: 'Kuantitatif (15)' },
                      { id: 'PBM', name: 'PBM', fullName: 'PBM (20)' },
                      { id: 'PPU', name: 'PPU', fullName: 'PPU (20)' },
                      { id: 'LIndo', name: 'L.Indo', fullName: 'Literasi B.Indo (30)' },
                      { id: 'LEng', name: 'L.Ingg', fullName: 'Literasi B.Ingg (20)' },
                      { id: 'PM', name: 'PM', fullName: 'Penalaran MTK (20)' }
                    ]" 
                    :key="sub.id"
                    :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border cursor-pointer', 
                      activeSubtest === sub.id 
                        ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-500/30 font-black' 
                        : 'bg-white/5 border-white/10 text-white/70 hover:text-white hover:bg-white/10'
                    ]"
                    @click="setSubtest(sub.id)"
                    :title="sub.fullName"
                  >
                    {{ sub.name }}
                  </button>
                </div>
              </div>

              <!-- CBT Question Card (Official Paper Style) -->
              <div v-if="currentCbtQuestion" class="bg-white rounded-2xl p-4 sm:p-6 md:p-8 space-y-5 sm:space-y-6 shadow-2xl border border-slate-200">
                <!-- Question Header Strip -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3 sm:pb-4">
                  <!-- Row 1 on mobile: Soal No + Total Soal + IRT Badge -->
                  <div class="flex items-center justify-between sm:justify-start gap-2.5 w-full sm:w-auto">
                    <div class="flex items-center gap-2">
                      <span class="px-3 py-1 rounded-xl bg-slate-900 text-white font-mono text-xs sm:text-sm font-black shadow-sm shrink-0">
                        SOAL NO. {{ currentCbtQuestion.num || (cbtCurrentIndex + 1) }}
                      </span>
                      <span class="text-xs text-slate-500 font-bold whitespace-nowrap">
                        dari {{ currentSubtestQuestions.length }} Soal
                      </span>
                    </div>
                    <span class="sm:hidden px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold font-mono shrink-0">
                      IRT: +42
                    </span>
                  </div>

                  <!-- Row 2 on mobile: Font Size Switcher & Desktop IRT Weight -->
                  <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                    <div class="flex items-center bg-slate-100 rounded-lg p-0.5 border border-slate-200 text-xs font-bold text-slate-700 w-full sm:w-auto justify-between sm:justify-start">
                      <span class="text-[10px] text-slate-400 px-2 uppercase font-mono">Ukuran:</span>
                      <div class="flex items-center">
                        <button @click="cbtFontSize = 'sm'" :class="cbtFontSize === 'sm' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:text-slate-900'" class="px-2.5 py-1 rounded cursor-pointer font-bold transition-all">A-</button>
                        <button @click="cbtFontSize = 'normal'" :class="cbtFontSize === 'normal' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:text-slate-900'" class="px-2.5 py-1 rounded cursor-pointer font-bold transition-all">A</button>
                        <button @click="cbtFontSize = 'lg'" :class="cbtFontSize === 'lg' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:text-slate-900'" class="px-2.5 py-1 rounded cursor-pointer font-bold transition-all">A+</button>
                      </div>
                    </div>
                    <span class="hidden sm:inline-flex px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold font-mono whitespace-nowrap">
                      Bobot IRT: +42
                    </span>
                  </div>
                </div>

                <!-- Question Text / Stimulus -->
                <div 
                  :class="cbtFontSize === 'lg' ? 'text-lg md:text-xl' : (cbtFontSize === 'sm' ? 'text-sm md:text-base' : 'text-base md:text-lg')"
                  class="text-slate-900 font-medium leading-relaxed select-text" 
                  v-html="currentCbtQuestion.question"
                ></div>

                <!-- Answer Choices (Official CBT Radio Options) -->
                <div class="space-y-3 pt-2">
                  <button 
                    v-for="(opt, idx) in currentCbtQuestion.options" 
                    :key="idx"
                    :class="[
                      'w-full text-left p-3.5 sm:p-4 rounded-xl border transition-all flex items-start gap-3 sm:gap-4 cursor-pointer outline-none', 
                      userAnswersMap[currentCbtQuestion.id] === idx 
                        ? 'bg-indigo-50/80 border-indigo-600 text-slate-900 ring-2 ring-indigo-500/20 shadow-sm' 
                        : 'bg-slate-50/70 border-slate-200 text-slate-800 hover:border-slate-300 hover:bg-slate-100/60'
                    ]" 
                    @click="answerAnalisaQuestion(currentCbtQuestion.id, idx)"
                  >
                    <div 
                      :class="[
                        'w-7 h-7 rounded-full flex items-center justify-center font-mono font-black text-xs shrink-0 transition-all shadow-sm',
                        userAnswersMap[currentCbtQuestion.id] === idx 
                          ? 'bg-indigo-600 text-white' 
                          : 'bg-white border border-slate-300 text-slate-700'
                      ]"
                    >
                      {{ String.fromCharCode(65 + idx) }}
                    </div>
                    <div 
                      :class="cbtFontSize === 'lg' ? 'text-base' : (cbtFontSize === 'sm' ? 'text-xs' : 'text-sm')"
                      class="pt-0.5 leading-relaxed font-medium" 
                      v-html="opt"
                    ></div>
                  </button>
                </div>

                <!-- CBT Action Control Toolbar (Official SNPMB Style) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-200 pt-4">
                  <div class="grid grid-cols-3 gap-1.5 sm:flex sm:items-center sm:gap-2 w-full sm:w-auto">
                    <button 
                      :disabled="cbtCurrentIndex <= 0" 
                      @click="prevQuestion" 
                      class="px-2 sm:px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:pointer-events-none text-white font-bold text-xs flex items-center justify-center gap-1 transition-all cursor-pointer shadow-sm"
                    >
                      <i class="ph-bold ph-caret-left"></i>
                      <span class="hidden sm:inline">Sebelumnya</span>
                      <span class="sm:hidden">Sebelum</span>
                    </button>

                    <!-- Authentic Yellow Ragu-Ragu Button -->
                    <button 
                      @click="toggleRaguRagu(currentCbtQuestion.id)" 
                      :class="cbtRaguMap[currentCbtQuestion.id] 
                        ? 'bg-amber-400 hover:bg-amber-500 text-black border-amber-500 shadow-md ring-2 ring-amber-400/30' 
                        : 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-300'"
                      class="px-2 sm:px-4 py-2.5 rounded-xl border font-black text-xs flex items-center justify-center gap-1 transition-all cursor-pointer"
                      title="Tandai soal ini sebagai ragu-ragu"
                    >
                      <i class="ph-bold ph-flag"></i>
                      <span class="truncate">{{ cbtRaguMap[currentCbtQuestion.id] ? 'Ditandai Ragu' : 'Ragu-Ragu' }}</span>
                    </button>

                    <button 
                      :disabled="cbtCurrentIndex >= currentSubtestQuestions.length - 1" 
                      @click="nextQuestion" 
                      class="px-2 sm:px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-30 disabled:pointer-events-none text-white font-bold text-xs flex items-center justify-center gap-1 transition-all cursor-pointer shadow-sm"
                    >
                      <span class="hidden sm:inline">Berikutnya</span>
                      <span class="sm:hidden">Lanjut</span>
                      <i class="ph-bold ph-caret-right"></i>
                    </button>
                  </div>

                  <button 
                    @click="cbtShowAnalysis = !cbtShowAnalysis" 
                    class="w-full sm:w-auto justify-center px-3.5 py-2.5 rounded-xl bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-700 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer"
                  >
                    <i class="ph-bold ph-brain"></i>
                    <span>{{ cbtShowAnalysis ? 'Sembunyikan Bedah AI' : 'Bedah Solusi AI' }}</span>
                  </button>
                </div>

                <!-- AI Deep Solution Breakdown (Optional Review on Demand) -->
                <div v-if="cbtShowAnalysis" class="p-5 rounded-2xl bg-slate-900 text-white border border-slate-800 space-y-4 animate-fade-in shadow-xl">
                  <div class="flex items-center justify-between border-b border-white/10 pb-2.5">
                    <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full bg-[#c0ff00]"></span>
                      Bedah Solusi &amp; Konsep Kunci SNBT
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">
                      Kunci Jawaban: <strong class="text-[#c0ff00]">{{ String.fromCharCode(65 + currentCbtQuestion.answer) }}</strong>
                    </span>
                  </div>

                  <div class="space-y-3 text-xs leading-relaxed">
                    <div>
                      <strong class="text-white block font-bold mb-1">Analisis Jalur Solusi:</strong>
                      <p class="text-white/80">{{ currentCbtQuestion.explanation }}</p>
                    </div>

                    <div class="p-3 rounded-xl bg-[#c0ff00]/10 border border-[#c0ff00]/25 text-[#c0ff00]">
                      <strong class="block font-bold mb-0.5">Trik Cepat 30 Detik AI:</strong>
                      <p class="text-white/90">{{ currentCbtQuestion.concept }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="bg-white rounded-2xl p-12 text-center text-slate-500">
                <i class="ph-bold ph-info text-3xl mb-2 text-slate-400"></i>
                <p class="font-bold">Memuat soal subtes...</p>
              </div>
            </div>

            <!-- Right Sidebar (Col 4): Official CBT Navigation Grid & Timer -->
            <div class="lg:col-span-4 space-y-4">
              
              <!-- Countdown Timer Card (Official SNPMB Header) -->
              <div class="bg-slate-900/90 border border-white/10 rounded-2xl p-4 space-y-3 shadow-lg">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-white/60 uppercase tracking-wider">Sisa Waktu Ujian</span>
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                </div>
                <div class="flex items-center justify-center p-3 rounded-xl bg-black/60 border border-white/10 font-mono font-black text-2xl md:text-3xl text-[#c0ff00] tracking-widest">
                  {{ cbtFormattedTimer }}
                </div>
                <div class="flex items-center justify-between text-[10px] text-white/50 border-t border-white/10 pt-2 font-medium">
                  <span>Peserta: <strong class="text-white">{{ currentUser?.name || 'Siswa Mandiri' }}</strong></span>
                  <span>SNBT 2027</span>
                </div>
              </div>

              <!-- Question Grid Number Navigator (Official CBT 5-Column Grid) -->
              <div class="bg-slate-900/90 border border-white/10 rounded-2xl p-4 space-y-3 shadow-lg">
                <div class="flex items-center justify-between border-b border-white/10 pb-2.5">
                  <h4 class="text-xs font-black text-white uppercase tracking-wider">Daftar Nomor Soal</h4>
                  <span class="text-[10px] font-mono text-white/50">Subtes {{ activeSubtest }}</span>
                </div>

                <!-- CBT Status Legend -->
                <div class="grid grid-cols-3 gap-2 text-[10px] font-bold text-white/70 py-1 border-b border-white/10">
                  <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-emerald-500 shrink-0"></span>
                    <span>Dijawab</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-amber-400 shrink-0"></span>
                    <span>Ragu-Ragu</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-white/10 border border-white/20 shrink-0"></span>
                    <span>Belum</span>
                  </div>
                </div>

                <!-- Number Grid (Clickable) -->
                <div class="grid grid-cols-5 gap-2 max-h-72 overflow-y-auto pr-1 pt-1">
                  <button 
                    v-for="(q, idx) in currentSubtestQuestions" 
                    :key="q.id"
                    @click="jumpToQuestion(idx)"
                    :class="[
                      'h-10 rounded-xl font-mono text-xs font-black transition-all flex items-center justify-center cursor-pointer border',
                      cbtCurrentIndex === idx ? 'ring-2 ring-indigo-400 scale-105 z-10' : '',
                      cbtRaguMap[q.id] 
                        ? 'bg-amber-400 text-black border-amber-500 shadow-sm' 
                        : (userAnswersMap[q.id] !== undefined 
                            ? 'bg-emerald-500 text-white border-emerald-400 shadow-sm' 
                            : 'bg-white/5 border-white/10 text-white/80 hover:bg-white/15 hover:border-white/25')
                    ]"
                    :title="`Soal #${idx + 1}`"
                  >
                    {{ idx + 1 }}
                  </button>
                </div>

                <!-- Summary Counter -->
                <div class="pt-3 border-t border-white/10 space-y-2">
                  <div class="flex justify-between text-xs font-semibold text-white/70">
                    <span>Terjawab: <strong class="text-emerald-400 font-mono">{{ Object.keys(userAnswersMap).filter(id => id.startsWith(activeSubtest)).length }}</strong></span>
                    <span>Ragu: <strong class="text-amber-400 font-mono">{{ Object.keys(cbtRaguMap).filter(id => id.startsWith(activeSubtest) && cbtRaguMap[id]).length }}</strong></span>
                    <span>Total: <strong class="text-white font-mono">{{ currentSubtestQuestions.length }}</strong></span>
                  </div>

                  <button 
                    @click="currentTab = 'dashboard'"
                    class="w-full py-3 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-xs uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(192,255,0,0.3)] hover:scale-[1.02] active:scale-95 cursor-pointer mt-2 flex items-center justify-center gap-1.5"
                  >
                    <i class="ph-bold ph-check-circle text-base"></i>
                    <span>Selesai &amp; Kumpulkan Subtes</span>
                  </button>
                </div>
              </div>

              <!-- Official Structure Info Box -->
              <div class="bg-slate-900/90 border border-white/10 rounded-2xl p-4 text-xs space-y-2 shadow-lg">
                <span class="text-[10px] font-black uppercase tracking-wider text-white/50 block">Info Standar UTBK BPPP SNPMB</span>
                <p class="text-white/70 text-[11px] leading-relaxed">
                  Soal dirancang sesuai kisi-kisi resmi SNBT dengan bobot penskoran <em>Item Response Theory (IRT)</em> adaptif.
                </p>
              </div>

            </div>
          </div>
        </section>
      </div>

      <!-- 14. Footer (Hidden during diagnostic test, learning material, and tryout simulator for distraction-free focus) -->
      <footer v-if="!['diagnostic', 'learning', 'simulator'].includes(currentTab)" class="border-t border-white/10 pt-12 pb-8 text-sm text-white/50 font-medium">
        <div class="section-wrapper">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-10 text-left">

          <!-- Col 1: Brand & Company Entity -->
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-[0_0_15px_rgba(192,255,0,0.3)]">E</div>
              <span class="font-black text-white text-lg tracking-tight">EduPath<span style="color: #c0ff00;">.ai</span></span>
            </div>
            <p class="text-xs text-white/60 leading-relaxed">
              Platform teknologi belajar adaptif, diagnostic test IRT, dan persiapan SNBT terukur.
            </p>
            <div class="pt-2 border-t border-white/5">
              <div class="text-[11px] font-bold text-white/70 uppercase tracking-wider">Entitas Resmi:</div>
              <div class="text-xs font-black text-white mt-0.5">PT Kreasi Hasanah Indonesia</div>
            </div>
          </div>

          <!-- Col 3: Hubungi Kami & Layanan -->
          <div class="space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-1.5">
              <i class="ph-bold ph-headset text-[#c0ff00]"></i>
              <span>Kontak &amp; Bantuan</span>
            </h4>
            <ul class="text-xs text-white/70 space-y-2.5">
              <li>
                <a href="https://wa.me/6281234567890?text=Halo%20Tim%20PT%20Kreasi%20Hasanah%20Indonesia%20(EduPath),%20saya%20ingin%20tanya%20seputar%20platform%20belajar%20adaptif" target="_blank" rel="noopener noreferrer" class="hover:text-[#c0ff00] transition-colors flex items-center gap-2 group">
                  <i class="ph-bold ph-telegram-logo text-emerald-400 text-sm group-hover:scale-110 transition-transform"></i>
                  <span>Telegram CS &amp; Konsultasi</span>
                </a>
              </li>
              <li>
                <a href="mailto:kontak@elyana.biz.id" class="hover:text-[#c0ff00] transition-colors flex items-center gap-2">
                  <i class="ph-bold ph-envelope text-sky-400 text-sm"></i>
                  <span>kontak@elyana.biz.id</span>
                </a>
              </li>
              <li>
                <button @click="showContactModal = true" class="text-xs text-[#c0ff00] hover:underline font-bold flex items-center gap-1.5 cursor-pointer">
                  <i class="ph-bold ph-cards text-sm"></i>
                  <span>Lihat Info Kontak Lengkap</span>
                </button>
              </li>
              <li>
                <button @click="scrollToSection('affiliate-section'); sidebarExpanded = false" class="text-amber-400 hover:text-amber-300 transition-all font-bold flex items-center gap-1.5 cursor-pointer bg-transparent border-0 p-0 text-left">
                  <i class="ph-bold ph-hand-coins text-sm"></i>
                  <span>Afiliasi 20% + 10% Recurring</span>
                  <i class="ph-bold ph-arrow-up-right text-[10px]"></i>
                </button>
              </li>
            </ul>
          </div>

          <!-- Col 4: Legalitas & Kebijakan -->
          <div class="space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-1.5">
              <i class="ph-bold ph-shield-check text-[#c0ff00]"></i>
              <span>Legalitas &amp; Kebijakan</span>
            </h4>
            <ul class="text-xs text-white/70 space-y-2">
              <li class="flex items-center gap-2">
                <button @click="showTermsModal = true" class="hover:text-white transition-colors cursor-pointer flex items-center gap-1.5 text-white/70">
                  <i class="ph-bold ph-scales text-white/40"></i>
                  <span>Syarat &amp; Ketentuan</span>
                </button>
                <a href="terms.html" target="_blank" title="Buka di tab baru" class="text-white/40 hover:text-[#c0ff00] transition-colors flex items-center">
                  <i class="ph-bold ph-arrow-square-out text-xs"></i>
                </a>
              </li>
              <li>
                <button @click="showPrivacyModal = true" class="hover:text-white transition-colors cursor-pointer flex items-center gap-1.5">
                  <i class="ph-bold ph-lock-key text-white/40"></i>
                  <span>Kebijakan Privasi Data</span>
                </button>
              </li>
              <li>
                <button @click="showDisclaimerModal = true" class="hover:text-white transition-colors cursor-pointer flex items-center gap-1.5">
                  <i class="ph-bold ph-file-text text-white/40"></i>
                  <span>Disclaimer &amp; Metodologi</span>
                </button>
              </li>
              <li>
                <button @click="showParentConsentModal = true" class="hover:text-white transition-colors cursor-pointer flex items-center gap-1.5">
                  <i class="ph-bold ph-users-three text-white/40"></i>
                  <span>Panduan Wali &amp; Orang Tua</span>
                </button>
              </li>
              <li>
                <button @click="showContactModal = true" class="hover:text-white transition-colors cursor-pointer flex items-center gap-1.5">
                  <i class="ph-bold ph-identification-card text-white/40"></i>
                  <span>Profil &amp; Kontak Perusahaan</span>
                </button>
              </li>
            </ul>
          </div>

        </div>

        <!-- Bottom Sub-Footer Bar -->
        <div class="border-t border-white/10 pt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-white/40">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-[#c0ff00] animate-ping"></div>
            <span>© 2026 <strong class="text-white/80">PT Kreasi Hasanah Indonesia</strong> (EduPath). All rights reserved.</span>
          </div>
          <div class="flex flex-wrap items-center gap-4 text-xs">
            <button @click="showTermsModal = true" class="text-[#c0ff00] hover:underline font-bold">Syarat &amp; Ketentuan</button>
            <button @click="showContactModal = true" class="hover:underline">Detail Kontak Resmi</button>
          </div>
        </div>
        </div><!-- /section-wrapper -->
      </footer>

    </main>

    <!-- Modal: Pilihan Paket Belajar EduPath (Upgrade & Plan Selector Flow) -->
    <div v-if="showPlanSelectorModal" class="fixed inset-0 z-[95] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showPlanSelectorModal = false"></div>
      <div class="relative bg-[#090f1e] border border-white/15 rounded-3xl w-full max-w-4xl p-6 sm:p-8 shadow-2xl animate-fade-in text-white max-h-[90vh] overflow-y-auto">
        <!-- Close button -->
        <button @click="showPlanSelectorModal = false" class="!absolute top-5 right-5 z-20 w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer" title="Tutup" aria-label="Tutup">
          <i class="ph-bold ph-x text-sm"></i>
        </button>

        <!-- Header -->
        <div class="text-center max-w-xl mx-auto mb-8 space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-black uppercase tracking-widest">
            ⚡ INVESTASI MASA DEPAN
          </div>
          <h2 class="text-2xl sm:text-3xl font-black font-heading text-white">Pilih Paket Persiapan UTBK SNBT</h2>
          <p class="text-xs sm:text-sm text-white/60">Tingkatkan kesiapan ujian dengan ribuan bank soal HOTS terstandar IRT, tryout adaptif, dan analisis AI cerdas.</p>

          <!-- Billing Cycle Switch -->
          <div class="pt-3 flex items-center justify-center gap-3">
            <span class="text-xs font-bold" :class="!isAnnualBilling ? 'text-[#c0ff00]' : 'text-white/50'">Bulanan</span>
            <button 
              type="button"
              @click="isAnnualBilling = !isAnnualBilling"
              class="w-12 h-6 rounded-full bg-white/10 border border-white/20 p-1 transition-colors relative cursor-pointer"
            >
              <div 
                class="w-4 h-4 rounded-full bg-[#c0ff00] transition-transform duration-200"
                :class="isAnnualBilling ? 'translate-x-6' : 'translate-x-0'"
              ></div>
            </button>
            <span class="text-xs font-bold flex items-center gap-1.5" :class="isAnnualBilling ? 'text-[#c0ff00]' : 'text-white/50'">
              <span>Tahunan</span>
              <span class="text-[10px] bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 px-1.5 py-0.5 rounded-full font-black uppercase">Hemat s.d 60%</span>
            </span>
          </div>
        </div>

        <!-- Plan Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-stretch">
          <div 
            v-for="(product, idx) in displayProducts" 
            :key="product.id"
            :class="[
              'rounded-2xl p-6 flex flex-col justify-between transition-all duration-300 relative border',
              idx % 3 === 1 ? 'border-2 border-[#c0ff00] bg-gradient-to-b from-[#0e192f] to-[#090f1e] shadow-[0_0_40px_rgba(192,255,0,0.2)] md:-translate-y-2' : 'border-white/10 bg-white/[0.03] hover:border-white/20'
            ]"
          >
            <!-- Badge for Middle Plan -->
            <div v-if="idx % 3 === 1" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#c0ff00] text-black text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-lg">
              👑 PALING POPULER
            </div>

            <div>
              <!-- Plan Header -->
              <div class="flex justify-between items-center mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-[#c0ff00]">{{ product.code || 'PAKET' }}</span>
                <span v-if="isAnnualBilling" class="text-[9px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full uppercase">
                  Diskon {{ product.annualDiscount || 40 }}%
                </span>
              </div>
              <h3 class="text-xl font-black text-white">{{ product.name }}</h3>
              <p class="text-xs text-white/50 mt-1 min-h-[32px]">{{ product.description }}</p>

              <!-- Price Framing dengan Sistem Coret -->
              <div class="my-5 pb-4 border-b border-white/10" v-if="product.activePlan">
                <!-- Jika Bayar Tahunan -->
                <div v-if="isAnnualBilling" class="space-y-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-white/40 line-through font-mono">Rp {{ Number(product.monthlyBasePrice || 180000).toLocaleString('id-ID') }}</span>
                    <span class="text-[9px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full uppercase">
                      Diskon {{ product.annualDiscount || 40 }}%
                    </span>
                  </div>
                  <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-black font-heading text-white">
                      Rp {{ Math.round((product.annualMonthlyEquivalent || (product.activePlan.price / 12)) / 1000).toLocaleString('id-ID') }}k
                    </span>
                    <span class="text-xs text-white/50 font-bold">/bulan</span>
                  </div>
                  <div class="text-[10px] text-white/50 font-medium pt-0.5">
                    Ditagih Rp {{ Number(product.annualTotalPrice || product.activePlan.price).toLocaleString('id-ID') }} /tahun (Diskon {{ product.annualDiscount || 40 }}% dibayar per tahun)
                  </div>
                </div>

                <!-- Jika Bayar Bulanan -->
                <div v-else class="space-y-1">
                  <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-black font-heading text-white">
                      Rp {{ Math.round(product.activePlan.price / 1000).toLocaleString('id-ID') }}k
                    </span>
                    <span class="text-xs text-white/50 font-bold">/bulan</span>
                  </div>
                  <div class="text-[10px] text-white/40 font-medium pt-0.5">
                    Ditagih bulanan, fleksibel tanpa komitmen
                  </div>
                </div>
              </div>

              <!-- Features -->
              <ul class="space-y-2.5 text-xs text-white/80 font-medium">
                <li v-for="(feat, fIdx) in product.featuresList" :key="fIdx" class="flex items-start gap-2">
                  <i class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                  <span>{{ feat }}</span>
                </li>
              </ul>
            </div>

            <!-- Action Button -->
            <div class="pt-6 mt-4 border-t border-white/10" v-if="product.activePlan">
              <button
                @click="showPlanSelectorModal = false; purchasePlan(product.name, product.activePlan.price)"
                :class="[
                  'w-full py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-lg active:scale-95',
                  idx % 3 === 1 ? 'bg-[#c0ff00] hover:bg-[#b0ef00] text-black shadow-[0_0_20px_rgba(192,255,0,0.3)]' : 'bg-white/10 hover:bg-white hover:text-black text-white border border-white/20'
                ]"
              >
                <span>Pilih {{ product.name }}</span>
                <i class="ph-bold ph-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Trust Note -->
        <div class="mt-6 pt-4 border-t border-white/10 text-center text-xs text-white/40">
          Metode pembayaran resmi: QRIS (GoPay, OVO, Dana), Virtual Account BCA, Mandiri, BNI, BRI via Midtrans Payment Gateway.
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <div v-if="checkoutModalData.isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeCheckoutModal"></div>
      <div class="relative bg-slate-900 border border-white/10 rounded-2xl w-full max-w-md p-6 shadow-2xl animate-fade-in text-white">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-black font-heading">Invoice Pembelian</h2>
          <button @click="closeCheckoutModal" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>
        
        <div class="space-y-4">
          <div class="p-4 rounded-xl bg-white/5 border border-white/10">
            <div class="text-xs text-white/50 font-bold uppercase tracking-wider mb-1">Paket yang dipilih</div>
            <div class="text-lg font-black text-[#c0ff00]">Paket {{ checkoutModalData.planName }}</div>
          </div>
          
          <div class="space-y-2 text-sm font-medium pt-2">
            <div class="flex justify-between text-white/70">
              <span>Harga Dasar</span>
              <span>Rp {{ checkoutModalData.amount.toLocaleString('id-ID') }}</span>
            </div>
            <div class="flex justify-between text-white/70">
              <span>PPN (11%)</span>
              <span>Rp {{ checkoutModalData.tax.toLocaleString('id-ID') }}</span>
            </div>
            <div class="h-px w-full bg-white/10 my-2"></div>
            <div class="flex justify-between text-base font-black">
              <span>Total Tagihan</span>
              <span class="text-[#c0ff00]">Rp {{ checkoutModalData.total.toLocaleString('id-ID') }}</span>
            </div>
          </div>
          
          <!-- Mandatory Terms Checkbox -->
          <div class="pt-4 border-t border-white/10">
            <label class="flex items-start gap-2.5 text-xs text-white/80 cursor-pointer select-none">
              <input 
                type="checkbox" 
                v-model="checkoutTermsAgreed" 
                class="rounded border-white/20 bg-white/10 text-[#c0ff00] focus:ring-[#c0ff00] w-4 h-4 mt-0.5 cursor-pointer shrink-0"
              />
              <span>
                Saya telah membaca dan menyetujui 
                <button type="button" @click.stop="showTermsModal = true" class="text-[#c0ff00] font-bold underline hover:text-white">Syarat &amp; Ketentuan</button> 
                serta Kebijakan Privasi EduPath.
              </span>
            </label>
          </div>

          <div class="pt-3">
            <button 
              id="btn-midtrans"
              :disabled="!checkoutTermsAgreed"
              :class="[
                'w-full py-3.5 rounded-xl text-sm font-black transition-all flex items-center justify-center gap-2 cursor-pointer',
                checkoutTermsAgreed
                  ? 'bg-[#c0ff00] text-black hover:scale-105 active:scale-95 shadow-[0_0_20px_rgba(192,255,0,0.35)]'
                  : 'bg-white/10 text-white/40 cursor-not-allowed opacity-60'
              ]"
              @click="confirmCheckout"
            >
              <i class="ph-bold ph-credit-card"></i>
              Lanjutkan ke Midtrans
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Interactive Login Modal -->
    <div v-if="showLoginModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-md animate-fade-in">
      <div class="glass-card max-w-sm w-full p-8 rounded-3xl space-y-6 relative border-primary/30">
        <!-- Close button -->
        <button @click="showLoginModal = false" class="!absolute top-5 right-5 z-50 w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer" title="Tutup" aria-label="Tutup">
          <i class="ph-bold ph-x text-sm"></i>
        </button>

        <!-- Logo & Header (Consistent with Admin Portal) -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center gap-2.5 group select-none mb-2">
            <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20">E</div>
            <span class="font-black text-2xl tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          </div>
          <h3 class="text-lg font-bold font-heading text-white">
            {{ isForgotPasswordMode ? 'Lupa Password?' : (isResetPasswordMode ? 'Reset Password' : (isLoginMode ? 'Masuk ke EduPath' : 'Daftar Akun Siswa')) }}
          </h3>
          <p class="text-xs text-slate-400 font-light mt-1">
            {{ isForgotPasswordMode ? 'Masukkan email Anda untuk menerima token pemulihan.' : (isResetPasswordMode ? 'Masukkan token dan password baru Anda.' : (isLoginMode ? 'Silakan masuk dengan email/username dan password akun Anda.' : 'Mulai perjalanan belajarmu hari ini.')) }}
          </p>
        </div>

        <div v-if="isForgotPasswordMode">
          <form @submit.prevent="handleForgotPassword" class="space-y-4">
            <div v-if="authError" class="px-3 py-2 bg-rose-500/10 border border-rose-500/30 rounded-lg text-rose-400 text-[11px] font-bold text-center">
              {{ authError }}
            </div>
            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Email</label>
              <input v-model="authForm.email" type="email" required placeholder="demo@edupath.id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>
            <button type="submit" :disabled="authLoading" class="w-full py-3 btn-shimmer text-white rounded-xl text-xs font-bold transition-transform hover:scale-[1.02] mt-2 flex justify-center items-center">
              <span v-if="authLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
              Kirim Token Pemulihan &raquo;
            </button>
          </form>
          <p class="text-[10px] text-slate-500 text-center font-light mt-4">
            Ingat password Anda?
            <button @click="isForgotPasswordMode = false; isLoginMode = true" class="text-[#c0ff00] font-bold hover:underline ml-1">Masuk di sini</button>
          </p>
        </div>

        <div v-else-if="isResetPasswordMode">
          <form @submit.prevent="handleResetPassword" class="space-y-4">
            <div v-if="authError" class="px-3 py-2 bg-rose-500/10 border border-rose-500/30 rounded-lg text-rose-400 text-[11px] font-bold text-center">
              {{ authError }}
            </div>
            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Token Pemulihan (Simulasi)</label>
              <input v-model="resetToken" type="text" required placeholder="Masukkan 6 digit angka" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>
            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Password Baru</label>
              <input v-model="newPassword" type="password" required placeholder="Minimal 6 karakter" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>
            <button type="submit" :disabled="authLoading" class="w-full py-3 btn-shimmer text-white rounded-xl text-xs font-bold transition-transform hover:scale-[1.02] mt-2 flex justify-center items-center">
              <span v-if="authLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
              Simpan Password Baru &raquo;
            </button>
          </form>
          <p class="text-[10px] text-slate-500 text-center font-light mt-4">
            Batal ubah password?
            <button @click="isResetPasswordMode = false; isLoginMode = true" class="text-[#c0ff00] font-bold hover:underline ml-1">Kembali ke Login</button>
          </p>
        </div>

        <div v-else>
          <form @submit.prevent="doAuth" class="space-y-4">
            <div v-if="authError" class="px-3 py-2.5 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 text-[11px] font-bold text-center">
              <div>{{ authError }}</div>
              <div v-if="authError.includes('/admin') || authError.includes('Administrator')" class="mt-2 flex justify-center">
                <a href="#/admin" @click="showLoginModal = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-black font-black text-[10px] uppercase tracking-wider transition-all shadow-md shadow-amber-500/20 cursor-pointer">
                  <i class="ph-bold ph-shield-check text-xs"></i>
                  <span>Buka Portal Admin / Superadmin &rarr;</span>
                </a>
              </div>
            </div>

            <div v-if="!isLoginMode" class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Nama Lengkap</label>
              <input v-model="authForm.name" type="text" required placeholder="Cth: Budi Santoso" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>

            <div v-if="!isLoginMode" class="space-y-1">
              <div class="flex justify-between items-center">
                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Asal Sekolah</label>
                <span v-if="matchedPartnerSchool" class="text-[9px] font-bold text-emerald-400 flex items-center gap-1">
                  <i class="ph-bold ph-check-circle"></i> Mitra Terverifikasi
                </span>
              </div>
              <div class="relative">
                <input 
                  v-model="authForm.school" 
                  @input="onSchoolSelected"
                  list="publicSchoolsDatalist" 
                  type="text" 
                  placeholder="Ketik nama SMA/SMK/SMP asal..." 
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]"
                >
                <datalist id="publicSchoolsDatalist">
                  <option v-for="sch in publicSchoolsList" :key="sch.id" :value="sch.name">
                    {{ sch.city ? sch.city : '' }} {{ sch.npsn ? '(NPSN: ' + sch.npsn + ')' : '' }}
                  </option>
                </datalist>
              </div>
              <p class="text-[9px] text-slate-500">Guru BK sekolah asal Anda dapat memantau hasil tryout &amp; bimbingan PTN.</p>
            </div>

            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Email atau Username</label>
              <input v-model="authForm.email" type="text" required placeholder="nama@email.com / username" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>

            <div class="space-y-1">
              <div class="flex justify-between items-center">
                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Password</label>
                <button v-if="isLoginMode" type="button" @click="isForgotPasswordMode = true" class="text-[10px] text-[#c0ff00] hover:underline focus:outline-none">Lupa Password?</button>
              </div>
              <div class="relative">
                <input v-model="authForm.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00] pr-10">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white cursor-pointer select-none" :title="showPassword ? 'Sembunyikan password' : 'Lihat password'">
                  <svg v-if="!showPassword" class="w-4 h-4 text-slate-400 hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4 text-[#c0ff00] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                  </svg>
                </button>
              </div>
            </div>

            <button type="submit" :disabled="authLoading" class="w-full py-3 btn-shimmer text-white rounded-xl text-xs font-bold transition-transform hover:scale-[1.02] mt-2 flex justify-center items-center">
              <span v-if="authLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
              {{ isLoginMode ? 'Masuk Sekarang &raquo;' : 'Daftar Sekarang &raquo;' }}
            </button>
          </form>

          <p class="text-[10px] text-slate-500 text-center font-light mt-4">
            {{ isLoginMode ? 'Belum punya akun siswa?' : 'Sudah punya akun?' }}
            <button @click="isLoginMode = !isLoginMode" class="text-[#c0ff00] font-bold hover:underline ml-1">
              {{ isLoginMode ? 'Daftar siswa di sini' : 'Masuk di sini' }}
            </button>
          </p>

          <p v-if="!isLoginMode" class="text-[10px] text-slate-500 text-center font-light mt-2">
            Dengan mendaftar, Anda menyetujui 
            <button type="button" @click="showTermsModal = true" class="text-[#c0ff00] underline hover:text-white transition-colors cursor-pointer">Syarat &amp; Ketentuan</button> 
            layanan EduPath.
          </p>
        </div>
      </div>
    </div>

    <!-- Floating Real-Time Activity Notification (Discreet Social Proof on Desktop only) -->
    <div v-if="!isLoggedIn && showLiveActivity" class="hidden md:flex fixed bottom-6 left-6 z-40 max-w-xs md:max-w-sm glass-card p-3.5 rounded-2xl border-[#c0ff00]/30 shadow-[0_10px_40px_rgba(0,0,0,0.6)] animate-fade-in items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#c0ff00]/15 border border-[#c0ff00]/30 flex items-center justify-center text-xl shrink-0">
        {{ liveActivityList[currentActivityIdx].icon }}
      </div>
      <div class="flex-grow min-w-0 pr-2">
        <div class="flex items-center justify-between gap-1">
          <h5 class="text-xs font-black text-white truncate">{{ liveActivityList[currentActivityIdx].title }}</h5>
          <span class="text-[10px] text-[#c0ff00] font-mono shrink-0">{{ liveActivityList[currentActivityIdx].time }}</span>
        </div>
        <p class="text-[11px] text-white/60 truncate font-medium mt-0.5">{{ liveActivityList[currentActivityIdx].desc }}</p>
      </div>
      <button @click="showLiveActivity = false" class="w-7 h-7 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/60 hover:text-rose-300 border border-white/15 flex items-center justify-center transition-all cursor-pointer shrink-0 ml-1" title="Tutup" aria-label="Tutup">
        <i class="ph-bold ph-x text-xs"></i>
      </button>
    </div>

    <!-- Modal: Syarat & Ketentuan (Terms & Conditions) -->
    <div v-if="showTermsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-fade-in">
      <div class="relative w-full max-w-3xl max-h-[88vh] bg-[#0c121e] border border-white/15 rounded-3xl p-6 md:p-8 shadow-2xl overflow-y-auto text-left space-y-6">
        <div class="flex items-center justify-between border-b border-white/10 pb-4 sticky top-0 bg-[#0c121e]/95 backdrop-blur-md z-10 -mt-2 pt-2">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center text-xl font-black">
              <i class="ph-bold ph-scales"></i>
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Syarat dan Ketentuan (Terms &amp; Conditions)</h3>
              <p class="text-xs text-white/50">EduPath Platform Pembelajaran Digital • Terakhir diperbarui: 1 Oktober 2026</p>
            </div>
          </div>
          <button @click="showTermsModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/75 leading-relaxed">
          <div class="p-4 rounded-2xl bg-white/[0.03] border-l-4 border-[#c0ff00] space-y-1">
            <p class="text-white font-bold">Selamat datang di EduPath.</p>
            <p>Syarat dan Ketentuan ini mengatur penggunaan situs, akun, fitur pembelajaran, paket belajar, tryout, materi digital, dan layanan lain yang disediakan oleh EduPath. Dengan membuat akun, melakukan pembelian, atau menggunakan layanan EduPath, Anda menyatakan telah membaca, memahami, dan menyetujui ketentuan ini.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">1.</span> Definisi
            </h4>
            <p><strong>EduPath:</strong> Platform pembelajaran digital persiapan seleksi perguruan tinggi. <strong>Pengguna:</strong> Setiap orang yang membuat akun atau mengakses EduPath. <strong>Pembayar:</strong> Pihak yang melakukan transaksi (siswa atau orang tua/wali). <strong>Konten:</strong> Seluruh soal, modul, video, analitik, dan perangkat lunak.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">2.</span> Tentang EduPath
            </h4>
            <p>Menyediakan latihan soal, tryout dan simulasi IRT, modul belajar adaptif, analitik belajar, rekomendasi jalur belajar, serta fitur AI pendukung persiapan UTBK/SNBT.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">3.</span> Pendaftaran dan Akun
            </h4>
            <p>Pengguna wajib memberikan data yang benar dan menjaga kerahasiaan kata sandi akun. Satu akun diperuntukkan untuk 1 (satu) Pengguna terdaftar dan dilarang diperjualbelikan, dipindahtangankan, atau digunakan bersama secara tidak sah (account sharing).</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">4.</span> Pengguna dan Pembayar
            </h4>
            <p>Pembayaran dapat dilakukan oleh orang tua atau wali murid. Hak akses diberikan kepada akun yang ditentukan saat transaksi dan tidak secara otomatis dapat dipindahkan ke akun lain.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">5.</span> Paket dan Masa Berlaku
            </h4>
            <p>Masa aktif akses fitur berlaku sesuai paket yang dipilih saat pembelian. Pembelian paket tidak memberikan hak kepemilikan permanen atas seluruh layanan kecuali dinyatakan khusus.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">6.</span> Harga dan Pembayaran
            </h4>
            <p>Harga paket adalah harga yang tercantum saat checkout. Pembayaran diproses secara aman melalui payment gateway resmi berlisensi (Midtrans). Perubahan harga berikutnya tidak berlaku surut.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">7.</span> Konfirmasi Pembayaran dan Aktivasi
            </h4>
            <p>Setelah status pembayaran dikonfirmasi oleh sistem EduPath dari penyedia pembayaran, hak akses paket akan langsung diaktifkan secara otomatis.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">8.</span> Penggunaan Layanan
            </h4>
            <p>Dilarang keras menyalin, merekam layar untuk tujuan komersial, mengunduh massal, membagikan akun, scraping data otomatis, atau menembus mekanisme keamanan sistem.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">9.</span> Hak Kekayaan Intelektual
            </h4>
            <p>Seluruh materi, bank soal, video pembahasan, kode sumber, dan logo merupakan hak kekayaan intelektual milik EduPath yang dilindungi oleh hukum RI.</p>
          </div>

          <div class="p-4 rounded-2xl bg-[#c0ff00]/5 border border-[#c0ff00]/20 space-y-1.5">
            <h4 class="text-[#c0ff00] font-black text-sm flex items-center gap-2">
              <span>✦ 10.</span> Batasan Fitur Analitik, Simulasi &amp; AI
            </h4>
            <p class="text-white/85">Kalkulator prediksi skor AI dan peluang kelulusan PTN adalah instrumen simulasi dan evaluasi belajar. <strong>Hasil tersebut bukan jaminan mutlak kelulusan ujian resmi (SNPMB/UTBK)</strong>, melainkan panduan penentuan strategi belajar.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">11.</span> Perubahan Layanan
            </h4>
            <p>EduPath berhak mengembangkan, memperbarui, atau menyempurnakan fitur dan materi untuk meningkatkan kualitas layanan pembelajaran.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">12.</span> Pembatalan dan Pengembalian Dana (Refund)
            </h4>
            <p>Mengingat layanan berbentuk produk digital dengan akses seketika, <strong>pembelian pada umumnya bersifat final (non-refundable)</strong>. Refund hanya dipertimbangkan untuk kejadian khusus seperti pembayaran ganda (double payment) atau gangguan teknis permanen pada server kami.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">13-20.</span> Keandalan Sistem, Privasi &amp; Hukum yang Berlaku
            </h4>
            <p>Layanan tunduk pada hukum Republik Indonesia. Kami berkomitmen menjaga keamanan data sesuai UU Perlindungan Data Pribadi dan tidak menjual data pribadi kepada pihak ketiga.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">21-22.</span> Kontak Resmi &amp; Persetujuan
            </h4>
            <p>Email: <strong>admin@edupath.co.id</strong> • Website: <strong>https://edupath.co.id</strong></p>
          </div>
        </div>

        <div class="pt-4 border-t border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
          <a href="terms.html" target="_blank" class="text-xs text-[#c0ff00] hover:underline flex items-center gap-1 font-bold">
            <i class="ph-bold ph-arrow-square-out text-sm"></i>
            <span>Buka Versi Halaman Penuh (Cetak Dokumen)</span>
          </a>
          <button @click="showTermsModal = false" class="px-6 py-2.5 rounded-full bg-[#c0ff00] text-black font-black text-xs hover:scale-105 active:scale-95 transition-all">
            Saya Mengerti
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Kebijakan Privasi (Privacy Policy) -->
    <div v-if="showPrivacyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div class="relative w-full max-w-2xl max-h-[85vh] bg-[#0c121e] border border-white/15 rounded-3xl p-6 md:p-8 shadow-2xl overflow-y-auto text-left space-y-6">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center text-xl font-black">
              <i class="ph-bold ph-shield-check"></i>
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Kebijakan Privasi &amp; Perlindungan Data</h3>
              <p class="text-xs text-white/50">Komitmen keamanan data siswa, orang tua, dan sekolah di EduPath</p>
            </div>
          </div>
          <button @click="showPrivacyModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/70 leading-relaxed">
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">1.</span> Data yang Kami Kumpulkan
            </h4>
            <p>Kami hanya mengumpulkan data yang diperlukan untuk personalisasi pembelajaran dan laporan berkala, meliputi: nama/inisial siswa, tingkat sekolah, target jurusan/PTN impian, riwayat pengerjaan latihan asesmen, serta nomor kontak Telegram wali murid yang diberikan secara sukarela.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">2.</span> Prinsip Penggunaan &amp; AI Data Integrity
            </h4>
            <p>Data pribadi Anda <strong>TIDAK PERNAH diperjualbelikan</strong> kepada pihak ketiga mana pun. Data asesmen hanya digunakan oleh mesin analitik EduPath secara tertutup untuk memetakan kekuatan dan kelemahan materi belajar siswa.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">3.</span> Izin Wali (Parental Consent) &amp; Sekolah (DPA)
            </h4>
            <p>Bagi siswa di bawah umur, pendaftaran dan pengiriman nomor Telegram wali dianggap telah melalui persetujuan orang tua/wali resmi. Untuk integrasi B2B sekolah/bimbel, seluruh data agregat dilindungi oleh perjanjian pemrosesan data (DPA) yang terisolasi.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">4.</span> Hak Penghapusan Data (Right to Deletion) &amp; Pengelola
            </h4>
            <p>Pengguna berhak mengajukan permohonan koreksi atau penghapusan seluruh data riwayat belajar kapan saja melalui entitas pengelola resmi: <strong>PT Kreasi Hasanah Indonesia</strong>.</p>
          </div>
        </div>

        <div class="pt-4 border-t border-white/10 flex justify-end">
          <button @click="showPrivacyModal = false" class="px-6 py-2.5 rounded-full bg-[#c0ff00] text-black font-black text-xs hover:scale-105 active:scale-95 transition-all">
            Saya Mengerti
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Disclaimer & Metodologi Latihan -->
    <div v-if="showDisclaimerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div class="relative w-full max-w-2xl max-h-[85vh] bg-[#0c121e] border border-white/15 rounded-3xl p-6 md:p-8 shadow-2xl overflow-y-auto text-left space-y-6">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-400/15 text-amber-300 flex items-center justify-center text-xl font-black">
              <i class="ph-bold ph-info"></i>
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Disclaimer Edukatif &amp; Metodologi</h3>
              <p class="text-xs text-white/50">Transparansi model latihan IRT dan estimasi kesiapan belajar</p>
            </div>
          </div>
          <button @click="showDisclaimerModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/70 leading-relaxed">
          <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-200 text-xs">
            ⚠️ <strong>Catatan Penting:</strong> EduPath adalah platform latihan belajar mandiri dan pendampingan akademik adaptif yang dikembangkan dan dioperasikan oleh <strong>PT Kreasi Hasanah Indonesia</strong>. Simulasi dan skor proyeksi bukan merupakan penetapan resmi kelulusan PTN.
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">✦</span> Model Estimasi Berbasis IRT Terkalibrasi
            </h4>
            <p>Penilaian latihan di EduPath mengadopsi prinsip <em>Item Response Theory (IRT)</em> yang dikalibrasi berdasarkan tingkat kesulitan butir soal latihan. Model ini berfungsi sebagai tolok ukur edukatif agar siswa fokus menambal kelemahan pada materi berbobot tinggi.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">✦</span> Peran AI Sebagai Asisten Belajar
            </h4>
            <p>AI Tutor dan rekomendasi jalur belajar dirancang sebagai pemandu eksplorasi minat dan asisten pemecahan logika soal. Keputusan akhir pemilihan program studi dan strategi sepenuhnya berada pada pertimbangan siswa, orang tua, dan guru BK.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">✦</span> Independensi &amp; Non-Afiliasi Pemerintah
            </h4>
            <p>EduPath adalah platform teknologi pendidikan swasta independen naungan <strong>PT Kreasi Hasanah Indonesia</strong>. Platform ini tidak memiliki afiliasi dinas atau endorsement langsung dari Balai Pengelolaan Pengujian Pendidikan (BP3), Kemendikbudristek, maupun panitia SNPMB.</p>
          </div>
        </div>

        <div class="pt-4 border-t border-white/10 flex justify-end">
          <button @click="showDisclaimerModal = false" class="px-6 py-2.5 rounded-full bg-[#c0ff00] text-black font-black text-xs hover:scale-105 active:scale-95 transition-all">
            Saya Paham &amp; Lanjutkan
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Panduan Orang Tua & Parental Consent -->
    <div v-if="showParentConsentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div class="relative w-full max-w-2xl max-h-[85vh] bg-[#0c121e] border border-white/15 rounded-3xl p-6 md:p-8 shadow-2xl overflow-y-auto text-left space-y-6">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-300 flex items-center justify-center text-xl font-black">
              <i class="ph-bold ph-chats-circle"></i>
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Panduan &amp; Laporan Orang Tua</h3>
              <p class="text-xs text-white/50">Membantu orang tua memantau perkembangan belajar anak tanpa rasa cemas</p>
            </div>
          </div>
          <button @click="showParentConsentModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/70 leading-relaxed">
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-2">
            <h4 class="text-white font-black text-sm flex items-center gap-2 text-indigo-300">
              <i class="ph-bold ph-telegram-logo text-emerald-400 text-base"></i>
              Laporan Mingguan via Telegram
            </h4>
            <p>Orang tua tidak perlu repot menginstal aplikasi tambahan. Ringkasan topik yang sudah dipelajari anak, skor tryout berkala, dan rekomendasi fokus minggu depan akan dikirimkan otomatis ke nomor Telegram orang tua.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-2">
            <h4 class="text-white font-black text-sm flex items-center gap-2 text-indigo-300">
              <i class="ph-bold ph-users-three text-indigo-400 text-base"></i>
              Konsultasi Strategi Bersama Mentor (Paket VIP)
            </h4>
            <p>Bagi orang tua yang mengambil program pendampingan VIP, kami menyediakan sesi konsultasi langsung dengan Mentor Senior untuk membedah strategi rasionalisasi prodi dan portofolio anak.</p>
          </div>

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-2">
            <h4 class="text-white font-black text-sm flex items-center gap-2 text-indigo-300">
              <i class="ph-bold ph-hand-heart text-[#c0ff00] text-base"></i>
              Persetujuan Wali &amp; Pengelolaan Data
            </h4>
            <p>Penggunaan kontak Telegram orang tua semata-mata ditujukan untuk pelaporan progres edukatif di bawah naungan <strong>PT Kreasi Hasanah Indonesia</strong>. Orang tua berhak menghentikan pengiriman laporan kapan pun.</p>
          </div>
        </div>

        <div class="pt-4 border-t border-white/10 flex justify-end">
          <button @click="showParentConsentModal = false" class="px-6 py-2.5 rounded-full bg-[#c0ff00] text-black font-black text-xs hover:scale-105 active:scale-95 transition-all">
            Tutup Panduan
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Informasi Kontak & Legalitas Perusahaan -->
    <div v-if="showContactModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div class="relative w-full max-w-2xl max-h-[85vh] bg-[#0c121e] border border-white/15 rounded-3xl p-6 md:p-8 shadow-2xl overflow-y-auto text-left space-y-6">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center text-xl font-black">
              <i class="ph-bold ph-buildings"></i>
            </div>
            <div>
              <h3 class="text-xl font-black text-white">Informasi Kontak &amp; Perusahaan</h3>
              <p class="text-xs text-white/50">Layanan resmi operasional dan legalitas EduPath</p>
            </div>
          </div>
          <button @click="showContactModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/70 leading-relaxed">
          
          <!-- Company Identity Card -->
          <div class="p-5 rounded-2xl bg-white/[0.04] border border-white/10 space-y-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-[#c0ff00] text-black flex items-center justify-center font-black text-lg">E</div>
              <div>
                <div class="text-[11px] font-bold text-[#c0ff00] uppercase tracking-wider">Badan Usaha Resmi</div>
                <h4 class="text-base font-black text-white">PT Kreasi Hasanah Indonesia</h4>
              </div>
            </div>
            <p class="text-xs text-white/60">
              Pengembang dan pengelola resmi platform pembelajaran adaptif <strong>EduPath.ai</strong> untuk persiapan SNBT, asesmen potensi siswa (SPP), dan dashboard insight sekolah.
            </p>
          </div>

          <!-- Address Card -->
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/10 space-y-3">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <i class="ph-bold ph-map-pin text-[#c0ff00] text-base"></i>
              <span>Alamat Kantor Pusat</span>
            </h4>
            <div class="p-3.5 rounded-xl bg-black/40 border border-white/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div>
                <p class="font-bold text-white text-xs sm:text-sm">PT Kreasi Hasanah Indonesia</p>
              </div>
              <button 
                @click="copyCompanyAddress" 
                class="px-4 py-2 rounded-xl bg-white/10 hover:bg-[#c0ff00] hover:text-black text-white font-bold text-xs transition-all flex items-center gap-1.5 shrink-0 active:scale-95"
              >
                <i class="ph-bold ph-copy"></i>
                <span>Salin Alamat</span>
              </button>
            </div>
          </div>

          <!-- Direct Communication Channels -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a 
              href="https://wa.me/6281234567890?text=Halo%20Admin%20PT%20Kreasi%20Hasanah%20Indonesia%20(EduPath),%20saya%20ingin%20konsultasi" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 hover:border-emerald-400/40 transition-all space-y-1 block group"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                  <i class="ph-bold ph-telegram-logo text-base"></i> Telegram CS
                </span>
                <i class="ph-bold ph-arrow-up-right text-emerald-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform text-xs"></i>
              </div>
              <p class="font-black text-white text-sm">+62 812-3456-7890</p>
              <p class="text-[11px] text-white/50">Respon cepat via pesan Telegram</p>
            </a>

            <a 
              href="mailto:kontak@elyana.biz.id" 
              class="p-4 rounded-2xl bg-sky-500/10 border border-sky-500/20 hover:border-sky-400/40 transition-all space-y-1 block group"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-sky-400 flex items-center gap-1.5">
                  <i class="ph-bold ph-envelope text-base"></i> Email Resmi
                </span>
                <i class="ph-bold ph-arrow-up-right text-sky-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform text-xs"></i>
              </div>
              <p class="font-black text-white text-sm">kontak@elyana.biz.id</p>
              <p class="text-[11px] text-white/50">Kerjasama B2B sekolah &amp; permohonan</p>
            </a>
          </div>

          <!-- Working Hours -->
          <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-400/15 text-amber-300 flex items-center justify-center text-base shrink-0">
              <i class="ph-bold ph-clock"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-white">Jam Operasional Layanan</div>
              <div class="text-[11px] text-white/50">Senin – Sabtu: 08.00 – 20.00 WIB • Hari Minggu &amp; Libur Nasional: Melalui Pesan Otomatis</div>
            </div>
          </div>

        </div>

        <div class="pt-4 border-t border-white/10 flex items-center justify-between gap-3">
          <span class="text-[11px] text-white/40">© 2026 PT Kreasi Hasanah Indonesia</span>
          <button @click="showContactModal = false" class="px-6 py-2.5 rounded-full bg-[#c0ff00] text-black font-black text-xs hover:scale-105 active:scale-95 transition-all">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- Global Toast Notification -->
    <div v-if="toastMessage" class="fixed bottom-6 right-6 z-50 bg-slate-900 border border-slate-800/80 px-6 py-3.5 rounded-2xl shadow-2xl flex items-center gap-2 animate-fade-in">
      <span class="w-2 h-2 bg-primary rounded-full animate-ping"></span>
      <span class="text-xs font-semibold text-white">{{ toastMessage }}</span>
    </div>
  </div>
    <!-- Affiliate Register Modal -->
    <div v-if="showAffiliateRegisterModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div class="bg-[#121212] border border-amber-500/30 rounded-3xl w-full max-w-md overflow-hidden shadow-[0_0_50px_rgba(245,158,11,0.2)]">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-white/[0.02]">
          <h3 class="text-xl font-black font-heading text-white flex items-center gap-2">
            <i class="ph-bold ph-hand-coins text-amber-400"></i>
            Daftar Mitra Afiliasi
          </h3>
          <button @click="showAffiliateRegisterModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-rose-500/20 text-white/70 hover:text-rose-300 border border-white/15 hover:border-rose-500/30 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Tutup" aria-label="Tutup">
            <i class="ph-bold ph-x text-sm"></i>
          </button>
        </div>
        <div class="p-6">
          <form @submit.prevent="registerAffiliate" class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Nama Lengkap</label>
              <input v-model="affiliateForm.name" type="text" placeholder="Masukkan nama" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Email Aktif</label>
              <input v-model="affiliateForm.email" type="email" placeholder="nama@email.com" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Nomor Telegram</label>
              <input v-model="affiliateForm.whatsapp" type="tel" placeholder="08..." class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Password</label>
              <input v-model="affiliateForm.password" type="password" placeholder="Password" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required minlength="6">
            </div>
            
            <div class="pt-4 border-t border-white/10 mt-4 space-y-4">
              <span class="text-xs text-amber-400 uppercase font-bold tracking-wider block">Informasi Rekening Bank (Untuk Pencairan)</span>
              <div class="space-y-1.5">
                <input v-model="affiliateForm.bank_name" type="text" placeholder="Nama Bank (Misal: BCA, Mandiri)" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
              </div>
              <div class="space-y-1.5">
                <input v-model="affiliateForm.bank_account" type="text" placeholder="Nomor Rekening" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
              </div>
              <div class="space-y-1.5">
                <input v-model="affiliateForm.bank_owner" type="text" placeholder="Nama Pemilik Rekening" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
              </div>
            </div>

            <button type="submit" :disabled="isRegistering" class="w-full py-4 rounded-xl bg-[#c0ff00] hover:bg-[#b0ef00] text-black font-black text-sm uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(192,255,0,0.3)] hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 mt-2 disabled:opacity-50 cursor-pointer">
              <i v-if="!isRegistering" class="ph-bold ph-paper-plane-right"></i> 
              {{ isRegistering ? 'Memproses...' : 'Kirim Pendaftaran' }}
            </button>
          </form>
        </div>
      </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, defineAsyncComponent } from 'vue';
import { 
  SKILL_MAP, 
  TARGET_UNIVERSITIES, 
  MATERI_UTBK,
  MICRO_LESSONS, 
  DIAGNOSTIC_QUESTIONS,
  SIMULATOR_DATABASE
} from './EduData.js';
import api from './api';
import { initNeuralCanvas } from './neuralCanvas';

const StudentPotentialPath = defineAsyncComponent(() => import('./components/StudentPotentialPath.vue'));

export default {
  name: 'App',
  components: {
    StudentPotentialPath
  },
  setup() {
    const ambientGlowRef = ref(null);
    const canvasOpacity = ref(0);
    const handleScroll = (e) => { canvasOpacity.value = e.target.scrollTop > 300 ? 0.35 : 0; };
    const mobileSidebarOpen = ref(false);
    const toggleMobileSidebar = () => { mobileSidebarOpen.value = !mobileSidebarOpen.value; };

    // Basic User & Auth States
    const isLoggedIn = ref(false); 
    const showLoginModal = ref(false);
    const showTermsModal = ref(false);
    const showPrivacyModal = ref(false);
    const showDisclaimerModal = ref(false);
    const showParentConsentModal = ref(false);
    const showContactModal = ref(false);
    const showAffiliateRegisterModal = ref(false);
    const sidebarExpanded = ref(false);
    const toolsDropdownOpen = ref(false);

    const affiliateForm = ref({
      name: '',
      email: '',
      whatsapp: '',
      password: '',
      bank_name: '',
      bank_account: '',
      bank_owner: ''
    });
    const isRegistering = ref(false);

    const registerAffiliate = async () => {
      isRegistering.value = true;
      try {
        const formData = {
          name: affiliateForm.value.name,
          email: affiliateForm.value.email,
          whatsapp: affiliateForm.value.whatsapp,
          password: affiliateForm.value.password,
          target_ptn: 'Affiliate Partner'
        };

        const apiUrl = import.meta.env.VITE_API_URL || '/api';
        const regRes = await fetch(`${apiUrl}/auth.php?action=register`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });
        
        const regData = await regRes.json();
        if (!regRes.ok) throw new Error(regData.error || 'Gagal mendaftar akun');

        let token = regData.token;
        const joinRes = await fetch(`${apiUrl}/affiliate.php`, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({
            name: affiliateForm.value.name,
            bank_name: affiliateForm.value.bank_name,
            bank_account: affiliateForm.value.bank_account,
            bank_owner: affiliateForm.value.bank_owner || affiliateForm.value.name
          })
        });

        if (!joinRes.ok) {
          console.error("Gagal join afiliasi otomatis");
        } else {
          try {
            const joinData = await joinRes.json();
            if (joinData.token) token = joinData.token;
          } catch (e) {}
        }

        const affUserData = { 
          ...(regData.user || {}), 
          name: affiliateForm.value.name, 
          email: affiliateForm.value.email, 
          role: 'affiliate' 
        };
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user', JSON.stringify(affUserData));
        localStorage.setItem('user_name', affiliateForm.value.name);
        localStorage.setItem('user_email', affiliateForm.value.email);
        sessionStorage.setItem('ep_user', JSON.stringify(affUserData));
        sessionStorage.setItem('admin_token', token);
        sessionStorage.setItem('admin_role', 'affiliate');
        
        toastMessage.value = 'Pendaftaran Mitra Afiliasi Berhasil! Mengarahkan ke Dashboard Afiliasi...';
        showToast('Pendaftaran Mitra Afiliasi Berhasil! Mengarahkan ke Dashboard Afiliasi...');
        setTimeout(() => {
          window.location.hash = '#/admin';
        }, 1200);

      } catch (error) {
        alert(error.message);
      } finally {
        isRegistering.value = false;
      }
    };

    const baseUrl = window.location.origin;
    const copyReferralLink = () => {
      const link = `${baseUrl}/ref/${affiliateData.value?.referral_code}`;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link);
      }
      toastMessage.value = 'Link referral disalin!';
      setTimeout(() => {
        toastMessage.value = '';
      }, 3000);
    };

    const copyCompanyAddress = () => {
      const addressText = 'PT Kreasi Hasanah Indonesia';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(addressText);
      }
      toastMessage.value = 'Alamat kantor berhasil disalin ke clipboard!';
      setTimeout(() => {
        toastMessage.value = '';
      }, 3000);
    };

    const streakCount = ref(7);
    const coins = ref(250);
    const currentAbilityScore = ref(648);
    const toastMessage = ref('');
    const universities = ref(TARGET_UNIVERSITIES);
    const selectedUniversity = ref(TARGET_UNIVERSITIES[0]);

    // 2027 Question Projection variables
    const ansProj1 = ref(null);
    const ansProj2 = ref(null);
    const ansProj3 = ref(null);
    const ansProj4 = ref(null);
    const ansProj5 = ref(null);
    const activeSubtest = ref('PU');
    const userAnswersMap = ref({});
    const analisaWorking = ref(false);
    const answerAnalisaQuestion = (id, idx) => {
      userAnswersMap.value = { ...userAnswersMap.value, [id]: idx };
      analisaWorking.value = true;
    };

    // CBT Tryout SNPMB official engine
    const cbtCurrentIndex = ref(0);
    const cbtRaguMap = ref({});
    const cbtFontSize = ref('normal');
    const cbtShowAnalysis = ref(false);
    const cbtTimerSeconds = ref(5400);

    const cbtFormattedTimer = computed(() => {
      const h = Math.floor(cbtTimerSeconds.value / 3600);
      const m = Math.floor((cbtTimerSeconds.value % 3600) / 60);
      const s = cbtTimerSeconds.value % 60;
      return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    });

    const currentSubtestQuestions = computed(() => {
      return (SIMULATOR_DATABASE && SIMULATOR_DATABASE[activeSubtest.value]) || [];
    });

    const currentCbtQuestion = computed(() => {
      const qs = currentSubtestQuestions.value;
      if (!qs || !qs.length) return null;
      const idx = Math.min(Math.max(0, cbtCurrentIndex.value), qs.length - 1);
      return qs[idx];
    });

    const toggleRaguRagu = (qId) => {
      if (!qId) return;
      cbtRaguMap.value = { ...cbtRaguMap.value, [qId]: !cbtRaguMap.value[qId] };
    };

    const jumpToQuestion = (idx) => {
      cbtCurrentIndex.value = idx;
    };

    const prevQuestion = () => {
      if (cbtCurrentIndex.value > 0) cbtCurrentIndex.value--;
    };

    const nextQuestion = () => {
      if (cbtCurrentIndex.value < currentSubtestQuestions.value.length - 1) {
        cbtCurrentIndex.value++;
      }
    };

    const setSubtest = (subId) => {
      activeSubtest.value = subId;
      cbtCurrentIndex.value = 0;
    };

    // Live Countdown
    const countdownDays = ref(68);
    const countdownHours = ref(14);
    const countdownMinutes = ref(32);
    const countdownSeconds = ref(45);

    const updateCountdown = () => {
      const targetDate = new Date('2027-05-15T07:00:00+07:00').getTime();
      const now = new Date().getTime();
      const diff = Math.max(0, targetDate - now);
      
      countdownDays.value = Math.floor(diff / (1000 * 60 * 60 * 24));
      countdownHours.value = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      countdownMinutes.value = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      countdownSeconds.value = Math.floor((diff % (1000 * 60)) / 1000);
    };

    // PTN Chance Simulator — 50 Perguruan Tinggi & Program Studi Favorit
    const targetPtnList = TARGET_UNIVERSITIES;

    const simTargetPtn = ref(targetPtnList[0]);
    const simCurrentScore = ref(540);
    const simDailyHours = ref(3);

    const simPredictedGain = computed(() => {
      const baseHours = Number(simDailyHours.value);
      const potentialScore = Math.round(simCurrentScore.value + (baseHours * 38) + 48);
      return Math.min(800, potentialScore);
    });

    const simScoreIncrease = computed(() => {
      return simPredictedGain.value - simCurrentScore.value;
    });

    const simChancePercentage = computed(() => {
      const target = simTargetPtn.value.targetScore;
      const predicted = simPredictedGain.value;
      const diff = predicted - target;
      if (diff >= 30) return 96;
      if (diff >= 15) return 92;
      if (diff >= 0) return 86;
      if (diff >= -20) return 74;
      if (diff >= -40) return 58;
      if (diff >= -60) return 42;
      return 28;
    });

    const simChanceStatus = computed(() => {
      const pct = simChancePercentage.value;
      if (pct >= 85) return { text: 'Peluang Sangat Tinggi 🚀', color: '#c0ff00', badge: 'bg-[#c0ff00]/20 text-[#c0ff00] border-[#c0ff00]/40' };
      if (pct >= 70) return { text: 'Peluang Bagus (Dalam Jangkauan) ⚡', color: '#38bdf8', badge: 'bg-sky-500/20 text-sky-400 border-sky-500/40' };
      if (pct >= 50) return { text: 'Perlu Akselerasi Materi Kritis ⚠️', color: '#fbbf24', badge: 'bg-amber-500/20 text-amber-400 border-amber-500/40' };
      return { text: 'Butuh Jalur Intensif Khusus 🛑', color: '#f87171', badge: 'bg-rose-500/20 text-rose-400 border-rose-500/40' };
    });

    // Mini Interactive HOTS Quiz Widget on Landing Page
    const miniQuizQuestions = [
      {
        category: 'Pengetahuan Kuantitatif (PK)',
        icon: '📐',
        question: 'Jika x dan y adalah bilangan bulat positif yang memenuhi x² - y² = 31, berapakah nilai dari x² + y²?',
        options: [
          { text: 'A. 481', isCorrect: true },
          { text: 'B. 465', isCorrect: false },
          { text: 'C. 521', isCorrect: false },
          { text: 'D. 397', isCorrect: false }
        ],
        explanation: 'Faktorkan (x - y)(x + y) = 31. Karena 31 bilangan prima, x - y = 1 dan x + y = 31. Maka 2x = 32 -> x = 16, y = 15. Diperoleh x² + y² = 16² + 15² = 256 + 225 = 481.',
        trick: 'Trik Cepat Prima: Untuk a² - b² = P (prima), a = (P+1)/2 = 16 dan b = (P-1)/2 = 15. Langsung ketemu tanpa aljabar panjang!',
        irtScore: 42
      },
      {
        category: 'Penalaran Umum (PU)',
        icon: '🧠',
        question: 'Semua mahasiswa teknik rajin berhitung. Sebagian orang yang rajin berhitung gemar bermain catur. Kesimpulan yang PALING VALID adalah...',
        options: [
          { text: 'A. Semua mahasiswa teknik gemar bermain catur', isCorrect: false },
          { text: 'B. Sebagian orang yang gemar bermain catur adalah mahasiswa teknik', isCorrect: false },
          { text: 'C. Sebagian orang yang rajin berhitung adalah mahasiswa teknik', isCorrect: true },
          { text: 'D. Tidak ada mahasiswa teknik yang tidak gemar bermain catur', isCorrect: false }
        ],
        explanation: 'Dari "Semua A adalah B", konversi validnya adalah "Sebagian B adalah A" (Sebagian orang yang rajin berhitung adalah mahasiswa teknik).',
        trick: 'Jebakan Klasik UTBK: Jangan hubungkan dua term jika term perantara berkuantor "sebagian" (Fallacy of Undistributed Middle).',
        irtScore: 38
      },
      {
        category: 'Literasi Bahasa Indonesia',
        icon: '🇮🇩',
        question: 'Manakah penulisan kata serapan dan gabungan kata di bawah ini yang SELURUHNYA sesuai dengan Pedoman EYD Edisi V?',
        options: [
          { text: 'A. Mempertanggung jawabkan, pasca panen, analisa', isCorrect: false },
          { text: 'B. Mempertanggungjawabkan, pascapanen, analisis', isCorrect: true },
          { text: 'C. Mepertanggung jawabkan, pasca-panen, analisa', isCorrect: false },
          { text: 'D. Mempertanggungjawabkan, pasca panen, analisa', isCorrect: false }
        ],
        explanation: 'Bentuk terikat "pasca-" ditulis serangkai (pascapanen). Kata majemuk yang mendapat awalan dan akhiran sekaligus ditulis serangkai (mempertanggungjawabkan). Kata baku yang benar adalah "analisis".',
        trick: 'Trik EYD V: Awalan + Akhiran = Rapat (disambung). Awalan saja = Renggang. Bentuk terikat (pasca, antar, multi) selalu disambung!',
        irtScore: 35
      }
    ];

    const miniQuizActiveIdx = ref(0);
    const miniQuizUserAnswer = ref(null);
    const miniQuizShowExplanation = ref(false);

    const selectMiniQuizOption = (optIdx) => {
      miniQuizUserAnswer.value = optIdx;
      miniQuizShowExplanation.value = true;
    };

    const nextMiniQuiz = () => {
      miniQuizActiveIdx.value = (miniQuizActiveIdx.value + 1) % miniQuizQuestions.length;
      miniQuizUserAnswer.value = null;
      miniQuizShowExplanation.value = false;
    };

    // Testimonials Filterable
    const testimonialFilter = ref('all');
    const testimonialsList = [
      {
        name: 'Rafi A.',
        school: 'Siswa Kelas 12 SMA',
        avatar: '📚',
        cluster: 'health',
        clusterLabel: 'Saintek',
        target: 'Saintek / Kedokteran UI',
        badge: 'Pengguna Beta',
        text: 'Baru coba EduPath beberapa minggu dan udah kerasa bedanya. Fitur diagnostik blind spot-nya bikin aku tahu persis topik mana yang harus aku fokus duluan. Belajar jadi lebih terarah!'
      },
      {
        name: 'Dimas P.',
        school: 'Alumni / Pejuang Gap Year',
        avatar: '🎯',
        cluster: 'engineering',
        clusterLabel: 'Teknik',
        target: 'Teknik Informatika ITB',
        badge: 'Pengguna Beta',
        text: 'Latihan soalnya berbeda dari platform lain. Gak cuma asal kerjain soal, tapi ada analisis otomatis setelah TO. Aku jadi paham kenapa jawaban aku salah, bukan cuma lihat kunci.'
      },
      {
        name: 'Nadia R.',
        school: 'Siswa Kelas 12 SMA',
        avatar: '✨',
        cluster: 'soshum',
        clusterLabel: 'Soshum',
        target: 'Akuntansi / FEB UGM',
        badge: 'Pengguna Beta',
        text: 'Fitur laporan mingguan ke Telegram orang tua keren banget. Orang tua jadi bisa pantau perkembangan belajarku tanpa harus nanya-nanya terus. Akhirnya belajar tenang!'
      }
    ];

    const filteredTestimonials = computed(() => {
      if (testimonialFilter.value === 'all') return testimonialsList;
      return testimonialsList.filter(t => t.cluster === testimonialFilter.value);
    });

    // Pricing Billing Toggle
    const isAnnualBilling = ref(true);

    // Skill mastery (Subtes Resmi SNBT & Topik Spesifik)
    const skillMastery = ref({
      'Penalaran Umum': 78,
      'Pengetahuan & Pemahaman Umum': 74,
      'Pemahaman Bacaan & Menulis': 82,
      'Pengetahuan Kuantitatif': 65,
      'Literasi Bahasa Indonesia': 85,
      'Literasi Bahasa Inggris': 72,
      'Penalaran Matematika': 68,
      'Operasi Aljabar': 85,
      'Persamaan Linear': 78,
      'Faktorisasi': 70,
      'Fungsi Kuadrat': 40,
      'Geometri 2D': 65,
      'Geometri 3D': 35,
      'Trigonometri Dasar': 60,
      'Barisan & Deret': 80,
      'Peluang & Kombinatorika': 55,
      'Statistika Data': 75,
      'Logika Deduktif': 82,
      'Logika Induktif': 68,
      'Problem Solving': 58,
      'Analisis Grafik': 70,
      'Pemahaman Paragraf': 88,
      'Simpulan Bacaan': 80,
      'Kosa Kata Kontekstual': 75,
      'Grammar & Structure': 65,
      'Reading Comprehension': 72
    });

    const skillMap = ref(SKILL_MAP);

    const getSkillMastery = (name) => skillMastery.value[name] || 50;

    const getSkillColor = (val) => {
      if (val >= 75) return '#10b981'; // Green
      if (val >= 50) return '#f59e0b'; // Orange
      return '#ef4444'; // Red
    };

    const gapScore = computed(() => {
      const target = selectedUniversity.value ? selectedUniversity.value.targetScore : 720;
      return Math.max(0, target - currentAbilityScore.value);
    });

    const progressPercentage = computed(() => {
      const target = selectedUniversity.value ? selectedUniversity.value.targetScore : 720;
      const base = 400; 
      const current = currentAbilityScore.value - base;
      const total = target - base;
      return Math.min(100, Math.max(0, Math.round((current / total) * 100)));
    });

    const recalcTargetGap = () => {
      showToast(`Target diperbarui ke ${selectedUniversity.value.name} (Skor: ${selectedUniversity.value.targetScore})`);
    };

    const getInitialTab = () => {
      try {
        const hash = window.location.hash || '';
        if (hash.includes('tab=')) {
          const match = hash.match(/tab=([a-zA-Z0-9_]+)/);
          if (match && match[1]) return match[1];
        }
        if (hash === '#/learning' || hash.startsWith('#/learning') || hash === '#/materi' || hash.startsWith('#/materi')) {
          return 'learning';
        }
        if (hash === '#/diagnostic' || hash.startsWith('#/diagnostic')) {
          return 'diagnostic';
        }
        if (hash === '#/simulator' || hash.startsWith('#/simulator')) {
          return 'simulator';
        }
        if (hash === '#/dashboard' || hash.startsWith('#/dashboard')) {
          return 'dashboard';
        }
      } catch (e) {}

      const stored = sessionStorage.getItem('ep_active_tab');
      if (stored && stored !== 'home') return stored;

      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
      return token ? 'dashboard' : 'home';
    };

    const currentTab = ref(getInitialTab());
    const isStudentPreviewMode = ref(false);

    const studentTabs = [
      { id: 'home', label: 'Home', icon: 'ph-house' },
      { id: 'dashboard', label: 'Dashboard', icon: 'ph-gauge' },
      { id: 'diagnostic', label: 'Asesmen', icon: 'ph-brain' },
      { id: 'learning', label: 'Materi', icon: 'ph-books' },
      { id: 'simulator', label: 'Tryout', icon: 'ph-calculator' },
      { id: 'affiliate', label: 'Afiliasi', icon: 'ph-hand-coins' },
      { id: 'settings', label: 'Pengaturan', icon: 'ph-user-gear' }
    ];

    const adminTabs = [
      { id: 'admin_overview', label: 'Dashboard', icon: 'ph-squares-four', adminTab: 'overview' },
      { id: 'admin_students', label: 'Siswa', icon: 'ph-users-three', adminTab: 'students' },
      { id: 'admin_transactions', label: 'Transaksi', icon: 'ph-receipt', adminTab: 'transactions' },
      { id: 'admin_questions', label: 'Soal', icon: 'ph-book-open', adminTab: 'questions' },
      { id: 'admin_materials', label: 'Materi', icon: 'ph-books', adminTab: 'materials' },
      { id: 'admin_vouchers', label: 'Voucher Diskon', icon: 'ph-ticket', adminTab: 'vouchers' },
      { id: 'admin_affiliates', label: 'Program Afiliasi', icon: 'ph-hand-coins', adminTab: 'affiliates' },
      { id: 'admin_settings', label: 'Paket & Staff', icon: 'ph-gear', adminTab: 'settings' },
      { id: 'student_preview', label: 'Tampilan Siswa', icon: 'ph-eye', isPreview: true }
    ];

    const tabs = ref(studentTabs);

    // Computed: filter tab berdasarkan role user
    const isAdmin = computed(() => currentUser.value?.role === 'admin' || currentUser.value?.role === 'superadmin');
    const isTeacher = computed(() => ['teacher', 'guru', 'tutor'].includes(currentUser.value?.role));
    const isStaff = computed(() => isAdmin.value || isTeacher.value);
    const isAffiliate = computed(() => {
      const r = (currentUser.value?.role || localStorage.getItem('user_role') || '').toLowerCase();
      return r === 'affiliate' || r === 'afiliator';
    });
    const isSchoolBk = computed(() => {
      const r = (currentUser.value?.role || localStorage.getItem('user_role') || '').toLowerCase();
      return r === 'school_bk' || r === 'school' || r === 'bk';
    });
    const isPremiumUser = computed(() => {
      if (isStaff.value) return true;
      if (isAffiliate.value || isSchoolBk.value) return true;
      if (currentUser.value?.is_premium) return true;
      const p = (currentUser.value?.plan || currentUser.value?.plan_name || '').toLowerCase();
      if (!p || p === 'free' || p === 'trial' || p === 'paket free / trial' || p === 'paket free') return false;
      return true;
    });

    const getStudentDisplayName = (u) => {
      if (!u) return 'Siswa EduPath';
      if (u.display_name && u.display_name.trim()) return u.display_name.trim();
      if (u.name && u.name.trim() && !u.name.includes('@')) return u.name.trim();
      if (u.email && u.email.includes('@')) {
        const prefix = u.email.split('@')[0].replace(/[._]/g, ' ');
        return prefix.charAt(0).toUpperCase() + prefix.slice(1);
      }
      if (u.username) return u.username;
      if (u.name && u.name.trim()) return u.name.trim();
      if (u.role === 'superadmin') return 'Super Administrator';
      if (u.role === 'admin') return u.tenant_name ? `Admin ${u.tenant_name}` : 'Administrator';
      if (u.role === 'school_bk') return u.school_name ? `Guru BK ${u.school_name}` : 'Guru BK';
      if (u.role === 'affiliate') return 'Mitra Afiliasi';
      if (u.role === 'teacher') return 'Guru / Tutor';
      return 'Siswa EduPath';
    };

    const showPlanSelectorModal = ref(false);
    const openPlanSelector = () => {
      showPlanSelectorModal.value = true;
    };

    const filteredTabs = computed(() => {
      if (isAdmin.value && !isStudentPreviewMode.value) {
        return adminTabs;
      }
      // Hide Home tab for logged-in users
      if (isLoggedIn.value) {
        return studentTabs.filter(t => t.id !== 'home');
      }
      return studentTabs;
    });
    const isLoginMode = ref(true);
    
    const initScrollReveal = () => {
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('active');
            }
          });
        }, { threshold: 0.05 });
        setTimeout(() => {
          document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
        }, 100);
      }
    };

    watch(currentTab, (newTab) => {
      if (['simulator', 'studyroom', 'learning', 'diagnostic'].includes(newTab) && isLoggedIn.value && !isPremiumUser.value && !isStaff.value) {
        showToast('Fitur ini khusus pengguna Premium. Silakan pilih paket belajar Anda.');
        currentTab.value = 'dashboard';
        openPlanSelector();
        return;
      }
      try {
        sessionStorage.setItem('ep_active_tab', newTab);
      } catch (e) {}
      const tabInfo = tabs.value.find(t => t.id === newTab);
      if (tabInfo) {
        document.title = `${tabInfo.label} | EduPath`;
      } else {
        document.title = 'EduPath - Platform Belajar Adaptif & Persiapan SNBT Terukur';
      }
      initScrollReveal();
    });
    const showPassword = ref(false);
    const authForm = ref({ name: '', email: 'demo@edupath.id', password: 'demo123', school: '', school_id: '' });
    const publicSchoolsList = ref([]);
    const matchedPartnerSchool = computed(() => {
      const sName = (authForm.value.school || '').trim().toLowerCase();
      if (!sName) return null;
      return publicSchoolsList.value.find(s => (s.name || '').toLowerCase() === sName);
    });
    const onSchoolSelected = () => {
      if (matchedPartnerSchool.value) {
        authForm.value.school_id = matchedPartnerSchool.value.id;
      }
    };
    const authLoading = ref(false);
    const authError = ref('');
    const currentUser = ref(null);

    // Lupa Password states
    const isForgotPasswordMode = ref(false);
    const isResetPasswordMode = ref(false);
    const resetToken = ref('');
    const newPassword = ref('');

    const handleForgotPassword = async () => {
      authError.value = '';
      if (!authForm.value.email) {
        authError.value = 'Silakan isi email Anda terlebih dahulu.';
        return;
      }
      authLoading.value = true;
      try {
        const res = await api.forgotPassword(authForm.value.email);
        showToast(res.message);
        isForgotPasswordMode.value = false;
        isResetPasswordMode.value = true;
        if (res.token_simulasi) {
          console.log("SIMULASI TOKEN:", res.token_simulasi); // Untuk kemudahan testing
        }
      } catch (err) {
        authError.value = err.message || 'Gagal mengirim permintaan reset password.';
      } finally {
        authLoading.value = false;
      }
    };

    const publicPlans = ref([]);
    
    const displayProducts = computed(() => {
      const orderMap = { 'MANDIRI': 1, 'STARTER': 1, 'UTBK': 2, 'UTAMA': 2, 'VIP': 3, 'ELITE': 3 };
      const sorted = [...publicPlans.value].sort((a, b) => {
        const ordA = orderMap[a.code] || 99;
        const ordB = orderMap[b.code] || 99;
        return ordA - ordB;
      });

      return sorted.map((prod, idx) => {
        let activePlan = null;
        const cycle = isAnnualBilling.value ? 'yearly' : 'monthly';
        
        if (prod.plans && prod.plans.length > 0) {
          activePlan = prod.plans.find(p => p.billing_cycle === cycle || (cycle === 'yearly' && p.billing_cycle === 'annual'));
        }

        const monthlyPlan = prod.plans?.find(p => p.billing_cycle === 'monthly');
        const yearlyPlan = prod.plans?.find(p => p.billing_cycle === 'yearly' || p.billing_cycle === 'annual');

        const fallbackMonthly = (idx === 0 || prod.code === 'MANDIRI' || prod.code === 'STARTER') ? 180000 : ((idx === 1 || prod.code === 'UTBK') ? 480000 : 980000);
        const monthlyBasePrice = monthlyPlan ? Number(monthlyPlan.price) : fallbackMonthly;
        const discountPct = yearlyPlan?.discount ? Number(yearlyPlan.discount) : ((idx === 0 || prod.code === 'MANDIRI' || prod.code === 'STARTER') ? 40 : ((idx === 1 || prod.code === 'UTBK') ? 60 : 30));
        const annualTotalPrice = yearlyPlan ? Number(yearlyPlan.price) : Math.round(monthlyBasePrice * 12 * (1 - discountPct / 100));
        const annualMonthlyEquivalent = Math.round(annualTotalPrice / 12);

        // If no matching plan found for the current billing cycle, dynamically derive it
        if (!activePlan && prod.plans && prod.plans.length > 0) {
          const base = prod.plans[0];
          if (isAnnualBilling.value) {
            activePlan = {
              ...base,
              billing_cycle: 'yearly',
              price: annualTotalPrice,
              discount: discountPct
            };
          } else {
            activePlan = {
              ...base,
              billing_cycle: 'monthly',
              price: monthlyBasePrice,
              discount: 0
            };
          }
        }
        
        let featuresList = [];
        if (activePlan && activePlan.features) {
           try { 
             featuresList = JSON.parse(activePlan.features); 
             if (!Array.isArray(featuresList)) featuresList = [activePlan.features];
           } catch(e) { 
             featuresList = activePlan.features.split('\n').filter(Boolean); 
           }
        } else if (prod.description) {
           featuresList = [prod.description];
        }

        return {
          ...prod,
          activePlan,
          featuresList,
          monthlyBasePrice,
          annualMonthlyEquivalent,
          annualDiscount: discountPct,
          annualTotalPrice
        };
      });
    });

    const handleResetPassword = async () => {
      authError.value = '';
      if (!resetToken.value || !newPassword.value) {
        authError.value = 'Token dan password baru wajib diisi.';
        return;
      }
      authLoading.value = true;
      try {
        const res = await api.resetPassword(authForm.value.email, resetToken.value, newPassword.value);
        showToast(res.message);
        isResetPasswordMode.value = false;
        isLoginMode.value = true;
        resetToken.value = '';
        newPassword.value = '';
      } catch (err) {
        authError.value = err.message || 'Gagal mereset password.';
      } finally {
        authLoading.value = false;
      }
    };
    const fetchPlans = async () => {
      // Fallback pricing lokal (harga resmi)
      const FALLBACK_PRODUCTS = [
        {
          id: 1, code: 'STARTER', name: 'Paket Mandiri',
          description: 'Mulai belajar adaptif dengan IRT assessment & 50.000+ soal bank soal.',
          plans: [
            { billing_cycle: 'monthly', price: 180000, discount: 0, features: JSON.stringify(['500+ Micro-lessons & Video Ringkas','50.000+ Soal Bank Soal IRT','5x Tryout Nasional Simulasi SNBT','Diagnostic Assessment Awal','Dashboard Progres Belajar']) },
            { billing_cycle: 'yearly',  price: Math.round(180000 * 12 * 0.60), discount: 40, features: JSON.stringify(['500+ Micro-lessons & Video Ringkas','50.000+ Soal Bank Soal IRT','5x Tryout Nasional Simulasi SNBT','Diagnostic Assessment Awal','Dashboard Progres Belajar']) }
          ]
        },
        {
          id: 2, code: 'UTBK', name: 'Paket Pejuang PTN',
          description: 'AI Tutor 24/7, Adaptive IRT tanpa batas, & Rasionalisasi Prodi real-time.',
          plans: [
            { billing_cycle: 'monthly', price: 480000, discount: 0, features: JSON.stringify(['Semua fitur Paket Mandiri','AI Tutor Companion 24/7 Tanpa Batas','Adaptive IRT Unlimited + Remedial Otomatis','Rasionalisasi Prodi & Prediksi Kelulusan','Laporan Progres ke Orang Tua via Telegram','Grup Belajar VIP Komunitas PTN']) },
            { billing_cycle: 'yearly',  price: Math.round(480000 * 12 * 0.40), discount: 60, features: JSON.stringify(['Semua fitur Paket Mandiri','AI Tutor Companion 24/7 Tanpa Batas','Adaptive IRT Unlimited + Remedial Otomatis','Rasionalisasi Prodi & Prediksi Kelulusan','Laporan Progres ke Orang Tua via Telegram','Grup Belajar VIP Komunitas PTN']) }
          ]
        },
        {
          id: 3, code: 'ELITE', name: 'Paket Elite Mentoring',
          description: '1-on-1 Zoom Mentoring dengan Senior Mentor alumni UI/ITB + akses semua fitur.',
          plans: [
            { billing_cycle: 'monthly', price: 980000, discount: 0, features: JSON.stringify(['Semua fitur Paket Pejuang PTN','1-on-1 Zoom Mentoring Private (4x/bulan)','SOS Live Tutor Senior Mentor UI/ITB','Analisis Soal UTBK Spesifik per Individu','Garansi Score Improvement atau Refund','Akses Eksklusif Beta Fitur Terbaru']) },
            { billing_cycle: 'yearly',  price: Math.round(980000 * 12 * 0.70), discount: 30, features: JSON.stringify(['Semua fitur Paket Pejuang PTN','1-on-1 Zoom Mentoring Private (4x/bulan)','SOS Live Tutor Senior Mentor UI/ITB','Analisis Soal UTBK Spesifik per Individu','Garansi Score Improvement atau Refund','Akses Eksklusif Beta Fitur Terbaru']) }
          ]
        }
      ];
      try {
        const data = await api.getPlans();
        if (data.products && data.products.length >= 3) {
          publicPlans.value = data.products;
        } else {
          publicPlans.value = FALLBACK_PRODUCTS;
        }
      } catch (e) {
        console.warn('Gagal fetch plans dari API, pakai fallback lokal:', e);
        publicPlans.value = FALLBACK_PRODUCTS;
      }
    };



    const checkAuth = async () => {
      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
      if (token) {
        try {
          const res = await api.getProfile();
          if (res?.user) {
            const p = (res.user.plan || res.user.plan_name || '').toLowerCase();
            const isPaid = (p !== 'free' && p !== 'trial' && p !== '') && ['mandiri', 'starter', 'utama', 'pejuang', 'vip', 'elite', 'utbk', 'premium'].some(k => p.includes(k));
            res.user.is_premium = Boolean(res.user.is_premium || isPaid || res.user.role === 'affiliate' || res.user.role === 'school_bk');
            currentUser.value = res.user;
            isLoggedIn.value = true;
            // Update global states based on user data
            currentAbilityScore.value = res.user.total_score || 0;
            streakCount.value = res.user.streak || 0;
            if (res.user.target_ptn) {
              const matchedPtn = targetPtnList.find(p => p.name === res.user.target_ptn || p.id === res.user.target_ptn || (typeof res.user.target_ptn === 'string' && p.name.toLowerCase().includes(res.user.target_ptn.toLowerCase())));
              simTargetPtn.value = matchedPtn || (typeof res.user.target_ptn === 'object' ? res.user.target_ptn : { id: 'custom', name: String(res.user.target_ptn), targetScore: 700, focus: 'TPS & Literasi' });
            }
            
            // Sync profileEditForm
            profileEditForm.value.name = res.user.name || '';
            profileEditForm.value.email = res.user.email || '';
            profileEditForm.value.school = res.user.school || '';
            profileEditForm.value.school_id = res.user.school_id || '';
            profileEditForm.value.target_ptn = res.user.target_ptn || '';
            profileEditForm.value.phone = res.user.phone || '';

            const currentHash = window.location.hash || '';
            const isExplicitContentRoute = currentHash === '#/learning' || currentHash.startsWith('#/learning') || currentHash === '#/materi' || currentHash.startsWith('#/materi');
            if (isExplicitContentRoute) {
              isStudentPreviewMode.value = true;
              currentTab.value = 'learning';
            } else {
              const savedTab = sessionStorage.getItem('ep_active_tab');
              if (!savedTab && currentTab.value === 'home') {
                currentTab.value = 'dashboard';
              } else if (savedTab && currentTab.value !== savedTab) {
                currentTab.value = savedTab;
              }
            }

            // SMART REDIRECT: Guru BK / Perwakilan Sekolah Mitra
            if (res.user.role === 'school_bk') {
              sessionStorage.setItem('school_token', token);
              const sData = res.user.school || {
                id: res.user.school_id || res.user.reference_id,
                name: res.user.school_name || res.user.name,
                bk_name: res.user.bk_name || res.user.name,
                bk_email: res.user.email,
                city: res.user.school_city || ''
              };
              sessionStorage.setItem('school_data', JSON.stringify(sData));
              window.location.hash = '#/school';
              return;
            }

            if (res.user.role === 'affiliate') {
              sessionStorage.setItem('admin_token', token);
              sessionStorage.setItem('admin_role', 'affiliate');
              sessionStorage.setItem('admin_active_tab', 'affiliate_dashboard');
              localStorage.setItem('user_role', 'affiliate');
              window.location.hash = '#/admin';
              return;
            }

            const userRoleLower = (res.user.role || '').toLowerCase();
            if (['admin', 'superadmin', 'teacher', 'guru', 'tutor'].includes(userRoleLower) && !isStudentPreviewMode.value && !isExplicitContentRoute) {
              sessionStorage.setItem('admin_token', token);
              sessionStorage.setItem('admin_role', res.user.role);
              window.location.hash = '#/admin';
              return;
            }

            // Guard trial student from staying on premium tabs
            if (['simulator', 'studyroom', 'learning', 'diagnostic'].includes(currentTab.value) && !res.user.is_premium && !['admin', 'superadmin', 'teacher', 'guru', 'tutor'].includes(userRoleLower)) {
              currentTab.value = 'dashboard';
            }
          }
        } catch (e) {
          console.error("Session expired", e);
          logout();
        }
      }
    };

    const renderMaterialHtml = (raw) => {
      if (!raw) return '';
      const str = String(raw).trim();
      
      // If content already contains HTML block tags, return as-is
      const hasHtml = /<\/?(h[1-6]|p|div|ul|ol|li|table|blockquote|span|strong|b|em|i|br)\b/i.test(str);
      if (hasHtml) {
        return str;
      }

      // Convert Markdown/plain text into clean HTML
      let formatted = str
        .replace(/^### (.*$)/gim, '<h3 class="text-base font-bold text-slate-800 mt-4 mb-1.5">$1</h3>')
        .replace(/^## (.*$)/gim, '<h2 class="text-lg font-black text-emerald-600 mt-5 mb-2">$1</h2>')
        .replace(/^# (.*$)/gim, '<h1 class="text-xl font-black text-slate-900 border-b border-slate-200 pb-1 mt-6 mb-2">$1</h1>')
        .replace(/\*\*(.*?)\*\*/gim, '<strong class="font-bold text-slate-900">$1</strong>')
        .replace(/\*(.*?)\*/gim, '<em class="italic">$1</em>')
        .replace(/^\s*[-*]\s+(.*$)/gim, '<li class="ml-4 list-disc text-slate-700">$1</li>')
        .replace(/^\s*(\d+)\.\s+(.*$)/gim, '<li class="ml-4 list-decimal text-slate-700">$2</li>')
        .replace(/\n\n+/g, '</p><p class="mb-3 text-slate-700 leading-relaxed">')
        .replace(/\n/g, '<br>');

      return `<div class="prose-content"><p class="mb-3 text-slate-700 leading-relaxed">${formatted}</p></div>`;
    };

    const fetchMaterialsFromDb = async () => {
      try {
        const res = await api.getMaterials();
        const serverList = Array.isArray(res) ? res : (res?.materials || []);
        if (serverList.length > 0 && materiUtbk.value) {
          serverList.forEach(m => {
            const subName = (m.subtest || m.subtes || '').toLowerCase();
            let targetSub = materiUtbk.value.find(s => {
              const sName = (s.subtest || s.name || '').toLowerCase();
              return sName === subName ||
                (subName.includes('penalaran umum') && s.code === 'PU') ||
                (subName.includes('kuantitatif') && s.code === 'PK') ||
                (subName.includes('bacaan') && s.code === 'PBM') ||
                (subName.includes('pemahaman umum') && s.code === 'PPU') ||
                (subName.includes('indonesia') && s.code === 'LBI') ||
                (subName.includes('inggris') && s.code === 'LBE') ||
                (subName.includes('matematika') && s.code === 'PM');
            });

            if (!targetSub && subName) {
              const rawSub = m.subtest || m.subtes || 'TKA';
              const aType = (m.assessment_type || 'TKA').toUpperCase();
              const trk = (m.track || (['sosiologi', 'ekonomi', 'geografi', 'sejarah'].includes(rawSub.toLowerCase()) ? 'soshum' : 'saintek')).toLowerCase();
              const comp = aType === 'TKA' ? `TKA ${trk.toUpperCase()}` : (m.test_component || 'UMUM');
              targetSub = {
                id: `sub_${rawSub.toLowerCase().replace(/[^a-z0-9]/g, '_')}`,
                code: rawSub.slice(0, 4).toUpperCase(),
                name: rawSub,
                subtes: rawSub,
                subtest: rawSub,
                test_component: comp,
                assessment_type: aType,
                track: trk,
                icon: aType === 'TKA' ? (trk === 'saintek' ? '🔬' : '📚') : '📘',
                babList: []
              };
              materiUtbk.value.push(targetSub);
            }

            if (targetSub) {
              if (!targetSub.babList) targetSub.babList = [];

              const existingBab = targetSub.babList.find(b => 
                b.id === `db_${m.id}` || 
                b.id === m.id || 
                b.db_id === m.id ||
                (b.judul && m.title && b.judul.trim().toLowerCase() === m.title.trim().toLowerCase())
              );

              const summaryText = (m.formula_praktis || m.content || '')
                .replace(/<[^>]*>/g, '')
                .slice(0, 200)
                .replace(/\n/g, ' ') + '...';

              if (existingBab) {
                // Update in-place so active screen updates reactively
                existingBab.id = existingBab.id || `db_${m.id}`;
                existingBab.db_id = m.id;
                existingBab.judul = m.title;
                existingBab.topic = m.topic || existingBab.topic || m.title;
                existingBab.subtopic = m.subtopic || existingBab.subtopic || m.title;
                existingBab.skill = m.skill || existingBab.skill || `Pemahaman ${m.title}`;
                existingBab.indicator = m.indicator || existingBab.indicator || `Mampu menganalisis ${m.title}`;
                existingBab.teoriSingkat = m.content || m.title;
                existingBab.content = m.content || m.title;
                existingBab.formula_praktis = m.formula_praktis || existingBab.formula_praktis || 'Fokus pada konsep kunci dan trik pembahasannya.';
                existingBab.file_url = m.file_url || existingBab.file_url || '';
                if (!existingBab.microLesson) existingBab.microLesson = {};
                existingBab.microLesson.title = `Trik & Konsep: ${m.title}`;
                existingBab.microLesson.summary = summaryText;
              } else {
                targetSub.babList.push({
                  id: `db_${m.id}`,
                  db_id: m.id,
                  topic: m.topic || m.title,
                  subtopic: m.subtopic || m.title,
                  skill: m.skill || `Pemahaman ${m.title}`,
                  indicator: m.indicator || `Mampu menganalisis ${m.title}`,
                  judul: m.title,
                  teoriSingkat: m.content || m.title,
                  content: m.content || m.title,
                  formula_praktis: m.formula_praktis || 'Fokus pada konsep kunci dan trik pembahasannya.',
                  file_url: m.file_url || '',
                  microLesson: {
                    title: `Trik & Konsep: ${m.title}`,
                    duration: '5 Menit',
                    type: 'Modul Belajar',
                    summary: summaryText
                  }
                });
              }
            }
          });

          // Sync selectedBab so currently displayed chapter updates reactively
          if (selectedSubtes.value && selectedSubtes.value.babList && selectedSubtes.value.babList.length) {
            if (selectedBab.value) {
              const activeId = selectedBab.value.id || selectedBab.value.db_id;
              const activeJudul = selectedBab.value.judul?.trim()?.toLowerCase();
              const matched = selectedSubtes.value.babList.find(b => 
                (activeId && (b.id === activeId || b.db_id === activeId)) ||
                (activeJudul && b.judul?.trim()?.toLowerCase() === activeJudul)
              );
              if (matched) {
                selectedBab.value = matched;
              }
            } else {
              selectedBab.value = selectedSubtes.value.babList[0];
            }
          }
        }
      } catch (e) {
        console.warn("Using offline/default MATERI_UTBK fallback", e);
      }
    };

    onMounted(async () => {
      // Capture affiliate referral code
      const params = new URLSearchParams(window.location.search);
      const refCode = params.get('ref');
      if (refCode) {
        localStorage.setItem('affiliate_ref', refCode);
      }
      // Capture school referral
      const refSchool = params.get('school');
      const refSchoolId = params.get('school_id');
      if (refSchool) {
        localStorage.setItem('student_ref_school', refSchool);
        authForm.value.school = refSchool;
      }
      if (refSchoolId) {
        localStorage.setItem('student_ref_school_id', refSchoolId);
        authForm.value.school_id = refSchoolId;
      }
      fetchPlans();
      checkAuth();
      fetchMaterialsFromDb();
      try {
        const schools = await api.getPublicSchools().catch(() => []);
        publicSchoolsList.value = Array.isArray(schools) ? schools : (schools?.schools || []);
      } catch (e) {}
    });

    const doAuth = async () => {
      authError.value = '';
      authLoading.value = true;
      try {
        let res;
        if (isLoginMode.value) {
          res = await api.login(authForm.value.email, authForm.value.password);
        } else {
          const payload = {
            ...authForm.value,
            school: authForm.value.school || localStorage.getItem('student_ref_school') || '',
            school_id: authForm.value.school_id || localStorage.getItem('student_ref_school_id') || '',
            referral_code: localStorage.getItem('affiliate_ref') || ''
          };
          res = await api.register(payload);
        }
        localStorage.setItem('auth_token', res.token);
        if (res.user) {
          const p = (res.user.plan || res.user.plan_name || '').toLowerCase();
          const isPaid = (p !== 'free' && p !== 'trial' && p !== '') && ['mandiri', 'starter', 'utama', 'pejuang', 'vip', 'elite', 'utbk', 'premium'].some(k => p.includes(k));
          res.user.is_premium = Boolean(res.user.is_premium || isPaid || res.user.role === 'affiliate' || res.user.role === 'school_bk');
        }
        currentUser.value = res.user;

        // Sync profileEditForm
        if (res.user) {
          profileEditForm.value.name = res.user.name || '';
          profileEditForm.value.email = res.user.email || '';
          profileEditForm.value.school = res.user.school || '';
          profileEditForm.value.school_id = res.user.school_id || '';
          profileEditForm.value.target_ptn = res.user.target_ptn || '';
          profileEditForm.value.phone = res.user.phone || '';
        }

        // 1. SMART REDIRECT: Guru BK / Perwakilan Sekolah Mitra
        if (res.user?.role === 'school_bk' || res.user?.school_id || res.user?.school?.id) {
          sessionStorage.setItem('school_token', res.token);
          const sData = res.user.school || {
            id: res.user.school_id || res.user.reference_id,
            name: res.user.school_name || res.user.name,
            bk_name: res.user.bk_name || res.user.name,
            bk_email: res.user.email,
            city: res.user.school_city || ''
          };
          sessionStorage.setItem('school_data', JSON.stringify(sData));
          showLoginModal.value = false;
          showToast('Selamat datang di Portal Guru BK ' + (sData.name || '') + '!');
          window.location.hash = '#/school';
          window.location.reload();
          return;
        }

        // 2. Administrator & Superadmin wajib masuk melalui portal admin di /#/admin
        if (['admin', 'superadmin'].includes(res.user?.role)) {
          authError.value = 'Akun Administrator & Superadmin wajib masuk melalui portal admin di /#/admin.';
          return;
        }

        // 3. Guru & Tutor Bimbel (non-admin)
        if (['teacher', 'guru', 'tutor'].includes(res.user?.role)) {
          sessionStorage.setItem('admin_token', res.token);
          sessionStorage.setItem('admin_role', res.user.role);
          sessionStorage.setItem('ep_user', JSON.stringify(res.user));
          localStorage.setItem('user', JSON.stringify(res.user));
          if (res.user.name) localStorage.setItem('user_name', res.user.name);
          if (res.user.email) localStorage.setItem('user_email', res.user.email);
          showLoginModal.value = false;
          showToast('Selamat datang di Portal Pengajar!');
          window.location.hash = '#/admin';
          return;
        }

        // 4. Mitra Afiliasi
        if (res.user?.role === 'affiliate') {
          sessionStorage.setItem('admin_token', res.token);
          sessionStorage.setItem('admin_role', 'affiliate');
          sessionStorage.setItem('admin_active_tab', 'affiliate_dashboard');
          sessionStorage.setItem('ep_user', JSON.stringify(res.user));
          localStorage.setItem('user', JSON.stringify(res.user));
          localStorage.setItem('user_role', 'affiliate');
          if (res.user.name) localStorage.setItem('user_name', res.user.name);
          if (res.user.email) localStorage.setItem('user_email', res.user.email);
          showLoginModal.value = false;
          window.location.hash = '#/admin';
          showToast('Selamat datang di Portal Afiliasi!');
          return;
        }

        // 5. Default: Akun Siswa
        isLoggedIn.value = true;
        showLoginModal.value = false;
        currentAbilityScore.value = res.user.total_score || 0;
        streakCount.value = res.user.streak || 0;
        coins.value = res.user.coins || 0;
        currentTab.value = 'dashboard';

        // Persist student session and profile data
        localStorage.setItem('user', JSON.stringify(res.user));
        sessionStorage.setItem('ep_user', JSON.stringify(res.user));
        if (res.user.email) localStorage.setItem('user_email', res.user.email);
        if (res.user.name) localStorage.setItem('user_name', res.user.name);

        showToast(isLoginMode.value ? 'Masuk Akun Sukses! Selamat datang kembali.' : 'Pendaftaran Berhasil! Mulai perjalananmu.');
      } catch (err) {
        authError.value = err.message || 'Terjadi kesalahan jaringan.';
      } finally {
        authLoading.value = false;
      }
    };

    const login = () => {
      showLoginModal.value = true;
      isLoginMode.value = true;
    };

    const logout = async () => {
      // Optimistically update UI to prevent clicking around while waiting
      currentUser.value = null;
      isLoggedIn.value = false;
      currentTab.value = 'home';
      sidebarExpanded.value = false;
      mobileSidebarOpen.value = false;
      simTargetPtn.value = targetPtnList[0];
      document.body.style.pointerEvents = 'none';
      showToast('Sedang keluar dari akun...');

      try {
        await api.logout();
      } catch (e) {
        console.error('Logout API error', e);
      }
      
      // Clear all auth and session related storage keys
      sessionStorage.clear();
      const keys = [
        'auth_token', 'ep_session_token', 'ep_csrf', 'ep_token', 'ep_refresh',
        'ep_admin_token', 'ep_admin_csrf', 'admin_token', 'admin_role', 'admin_active_tab',
        'school_token', 'school_data', 'user_role', 'user_email', 'user_name', 'ep_user', 'user'
      ];
      keys.forEach(k => {
        try {
          sessionStorage.removeItem(k);
          localStorage.removeItem(k);
        } catch (e) {}
      });
      
      document.body.style.pointerEvents = '';
      showToast('Anda telah keluar dari akun.');
    };

    const materiSubMenuOpen = ref(false);
    const premiumTabs = ['simulator', 'studyroom', 'learning', 'diagnostic'];

    const handleTabClick = (tabId) => {
      const adminTarget = adminTabs.find(t => t.id === tabId);
      if (adminTarget) {
        if (adminTarget.isPreview) {
          isStudentPreviewMode.value = true;
          currentTab.value = 'home';
          showToast('Mode Pratinjau Tampilan Siswa Aktif');
          return;
        }
        if (adminTarget.adminTab) {
          sessionStorage.setItem('admin_active_tab', adminTarget.adminTab);
        }
        window.location.hash = '#/admin';
        return;
      }

      if (tabId === 'home') {
        goToHomeTop();
        return;
      }
      if (tabId === 'adminpanel') {
        window.location.hash = '#/admin';
        return;
      }
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun untuk mengakses modul ini!');
        return;
      }
      if (premiumTabs.includes(tabId) && !isPremiumUser.value && !isStaff.value) {
        showToast('Fitur ini khusus pengguna Premium. Silakan pilih paket belajar Anda.');
        openPlanSelector();
        return;
      }
      currentTab.value = tabId;
      if (tabId === 'affiliate' || tabId === 'settings') {
        fetchAffiliateData();
      }
    };

    const selectSubtesFromSidebar = (subtes) => {
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun untuk mengakses fitur ini!');
        return;
      }
      if (!isPremiumUser.value && !isStaff.value) {
        showToast('Fitur Materi khusus pengguna Premium. Silakan pilih paket belajar Anda.');
        openPlanSelector();
        return;
      }
      currentTab.value = 'learning';
      materiSubMenuOpen.value = true;
      selectSubtes(subtes);
    };

    const rotatingWords = ['Jalur Belajar Terarah.', 'Simulasi IRT Adaptif.', 'Persiapan Terukur.', 'Sistem EduPath.'];
    const currentWordIdx = ref(0);

    const startLearning = () => {
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun terlebih dahulu.');
      } else {
        currentTab.value = 'dashboard';
        showToast('Selamat belajar! Silakan pantau kemajuan Anda di dashboard.');
      }
    };

    const showIrtInfo = ref(false);
    const hideBabSidebar = ref(false);
    const checkoutTermsAgreed = ref(false);

    const checkoutModalData = ref({
      isOpen: false,
      planName: '',
      amount: 0,
      tax: 0,
      total: 0
    });

    const pendingPurchasePlanId = ref(null);
    const pendingPurchaseAmount = ref(null);
    const pendingPurchaseName = ref(null);

    const purchasePlan = (planNameFallback, fallbackAmount) => {
      checkoutTermsAgreed.value = false;
      let amount = fallbackAmount;
      let planId = planNameFallback;

      for (const p of publicPlans.value) {
        if (p.plans) {
          const cycle = isAnnualBilling.value ? 'yearly' : 'monthly';
          const found = p.plans.find(x => x.name.toLowerCase().includes(planNameFallback.toLowerCase()) && x.billing_cycle === cycle);
          if (found) { 
            amount = parseFloat(found.price);
            planId = found.id;
            break;
          }
        }
      }

      if (!isLoggedIn.value) {
        pendingPurchasePlanId.value = planId;
        pendingPurchaseName.value = planNameFallback;
        pendingPurchaseAmount.value = amount;
        isLoginMode.value = false;
        showLoginModal.value = true;
        showToast('Silakan buat akun terlebih dahulu untuk membeli paket.');
        return;
      }

      const tax = Math.round(amount * 0.11);
      const total = amount + tax;
      checkoutModalData.value = {
        isOpen: true,
        planId,
        planName: planNameFallback,
        amount,
        tax,
        total
      };
    };

    const confirmCheckout = async () => {
      if (!checkoutTermsAgreed.value) {
        showToast('Harap setujui Syarat & Ketentuan terlebih dahulu.');
        return;
      }
      const data = checkoutModalData.value;
      
      const btn = document.getElementById('btn-midtrans');
      if (btn) btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Memproses...';
      if (btn) btn.disabled = true;

      try {
        const response = await api.checkout(data.planId);
        if (response && response.token) {
          if (window.snap) {
            window.snap.pay(response.token, {
              onSuccess: function(result) {
                showToast(`Pembayaran berhasil! Paket ${data.planName} diaktifkan.`);
                checkoutModalData.value.isOpen = false;
                checkoutTermsAgreed.value = false;
              },
              onPending: function(result) {
                showToast('Menunggu pembayaran Anda diselesaikan.');
                checkoutModalData.value.isOpen = false;
                checkoutTermsAgreed.value = false;
              },
              onError: function(result) {
                alert('Pembayaran gagal, silakan coba lagi.');
              },
              onClose: function() {
                showToast('Anda menutup pop-up sebelum menyelesaikan pembayaran.');
              }
            });
          } else {
            alert('Sistem Pembayaran (Midtrans) tidak dimuat dengan benar. Pastikan VITE_MIDTRANS_CLIENT_KEY dikonfigurasi di .env');
          }
        }
      } catch (err) {
        alert(err.message || 'Gagal menghubungi server pembayaran.');
      } finally {
        if (btn) btn.innerHTML = '<i class="ph-bold ph-credit-card"></i> Lanjutkan ke Midtrans';
        if (btn) btn.disabled = false;
      }
    };

    const closeCheckoutModal = () => {
      checkoutModalData.value.isOpen = false;
      checkoutTermsAgreed.value = false;
    };

    watch(() => isLoggedIn.value, (newVal) => {
      if (newVal && pendingPurchasePlanId.value) {
        // Trigger purchase automatically after a slight delay to allow auth transitions to complete
        setTimeout(() => {
          purchasePlan(pendingPurchaseName.value, pendingPurchaseAmount.value);
          // Clear the pending state
          pendingPurchasePlanId.value = null;
          pendingPurchaseName.value = null;
          pendingPurchaseAmount.value = null;
        }, 500);
      }
    });

    const scrollToSection = (id) => {
      const el = document.getElementById(id);
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    };

    const goToHomeTop = () => {
      currentTab.value = 'home';
      const mainEl = document.querySelector('main');
      if (mainEl) {
        mainEl.scrollTo({ top: 0, behavior: 'smooth' });
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
      const topEl = document.getElementById('landing-top');
      if (topEl) {
        topEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    };

    const handleTakeReadinessFromSpp = (contextData = {}) => {
      if (contextData.targetUniv) {
        const query = contextData.targetUniv.toLowerCase();
        const matched = universities.value.find(u => 
          u.name.toLowerCase().includes(query) || query.includes(u.name.toLowerCase().split(' ')[0])
        );
        if (matched) {
          selectedUniversity.value = matched;
          simTargetPtn.value = matched.name;
        }
      }

      if (isLoggedIn.value) {
        currentTab.value = 'diagnostic';
        showToast(`Memulai Asesmen Kesiapan Akademik EduPath untuk target ${contextData.targetUniv || 'PTN Impian'}!`);
      } else {
        scrollToSection('quiz-demo');
        const targetLabel = contextData.targetUniv ? ` (${contextData.targetUniv} - ${contextData.targetMajor || 'Jurusan Impian'})` : '';
        showToast(`Mulai Kuis Uji Coba Kesiapan Akademik HOTS${targetLabel}!`);
      }
    };

    const faqs = ref([
      { 
        q: "Bagaimana EduPath membantu kesiapan menghadapi SNBT secara terukur?", 
        a: "EduPath mendeteksi kelemahan belajar melalui asesmen awal, menyusun jalur belajar adaptif berbasis micro-lessons, dan mengukur progres latihan secara berkala menggunakan model estimasi IRT terkalibrasi.", 
        open: true 
      },
      { 
        q: "Bagaimana cara kerja model estimasi skor IRT di EduPath?", 
        a: "Sistem mengadopsi prinsip Item Response Theory (IRT) yang dikalibrasi untuk latihan. Soal dengan tingkat kesulitan lebih tinggi dan jarang dijawab benar oleh peserta lain memiliki bobot nilai pemahaman lebih besar, sehingga hasil simulasi memberikan peta kesiapan yang realistis.", 
        open: false 
      },
      { 
        q: "Apakah EduPath memberikan jaminan kelulusan PTN 100%?", 
        a: "EduPath adalah platform latihan dan akselerasi belajar adaptif. Kami tidak memberikan klaim kelulusan mutlak karena hasil seleksi nasional resmi bergantung pada performa resmi ujian siswa.", 
        open: false 
      },
      { 
        q: "Bagaimana orang tua memantau perkembangan belajar anak?", 
        a: "Laporan ringkas mengenai topik yang dikuasai, skor latihan berkala, dan rekomendasi fokus mingguan dikirimkan otomatis ke Telegram orang tua tanpa perlu menginstal aplikasi tambahan.", 
        open: false 
      },
      { 
        q: "Bagaimana EduPath menjaga privasi data siswa & sekolah?", 
        a: "Data identitas, asal sekolah, dan riwayat asesmen dilindungi dengan enkripsi standar industri dan TIDAK PERNAH diperjualbelikan kepada pihak ketiga. Untuk kerja sama sekolah, kami menyediakan Data Processing Agreement (DPA) khusus.", 
        open: false 
      },
      {
        q: "Apakah ada versi gratis atau harus langsung bayar?",
        a: "EduPath menyediakan versi uji coba gratis (Free Tier) agar Anda bisa merasakan langsung asesmen diagnostik dan beberapa micro-lessons. Untuk akses penuh ke seluruh perpustakaan materi, AI Companion, dan TryOut Nasional, Anda dapat berlangganan paket Premium.",
        open: false
      },
      {
        q: "Bagaimana cara kerja komisi Program Afiliasi EduPath?",
        a: "Anda akan mendapatkan komisi sebesar 20% pada tahun pertama untuk setiap transaksi langganan dari pengguna yang mendaftar menggunakan kode referral Anda, ditambah komisi berulang (recurring) 10% seumur hidup selama pengguna tersebut memperpanjang langganannya.",
        open: false
      },
      {
        q: "Apakah EduPath bisa diakses lewat HP/Tablet?",
        a: "Ya! EduPath dirancang 100% responsif. Anda bisa belajar, menonton micro-lessons, hingga melakukan TryOut dengan nyaman melalui smartphone, tablet, maupun laptop/PC tanpa perlu menginstal aplikasi tambahan.",
        open: false
      },
      {
        q: "Apakah jadwal TryOut Nasional selalu serentak?",
        a: "Ya, TryOut Nasional diselenggarakan secara serentak sesuai jadwal yang telah ditentukan untuk mensimulasikan tekanan dan kondisi UTBK aslinya. Namun, untuk latihan reguler dan asesmen mandiri, Anda bisa mengerjakannya kapan saja 24/7.",
        open: false
      }
    ]);

    const toggleFaq = (index) => {
      faqs.value[index].open = !faqs.value[index].open;
    };

    const dailyMissions = ref([
      { title: 'Tonton 1 Micro-Lesson', reward: 20, completed: false },
      { title: 'Jawab 5 Soal Latihan', reward: 30, completed: false },
      { title: '20 Menit Sesi Fokus Pomodoro', reward: 40, completed: false }
    ]);

    const checkMissionReward = (mission) => {
      if (mission.completed) {
        coins.value += mission.reward;
        showToast(`Misi Selesai! Kamu mendapat +${mission.reward} Coins.`);
      }
    };

    const learningRecommendations = computed(() => {
      const path = [];
      if (getSkillMastery('Persamaan Kuadrat') < 50) path.push({ title: 'Aljabar & Persamaan Kuadrat', desc: 'Penguasaan 40%. Remedial disarankan sebelum lanjut ke Geometri.' });
      if (getSkillMastery('Problem Solving') < 60) path.push({ title: 'Penalaran Kuantitatif', desc: 'Penalaran kuantitatif 45%. Fokus pada data interpretation dan logika matematika.' });
      path.push({ title: 'Latihan Pemeliharaan Geometri', desc: 'Tinjau bangun ruang untuk pemantapan skor target UTBK Anda.' });
      return path;
    });

    const showToast = (msg) => {
      toastMessage.value = msg;
      setTimeout(() => { toastMessage.value = ''; }, 3000);
    };

    const diagnosticActive = ref(false);
    const diagnosticFinished = ref(false);
    const diagnosticQuestions = ref([]);
    const diagnosticIdx = ref(0);
    const selectedDiagAnswer = ref(null);
    const diagnosticScore = ref(0);
    const diagnosticAnswers = ref([]);
    const diagnosticResultData = ref(null);

    const currentDiagQuestion = computed(() => diagnosticQuestions.value[diagnosticIdx.value] || {});

    const startDiagnostic = async () => {
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun terlebih dahulu.');
        return;
      }
      if (!isPremiumUser.value && !isStaff.value) {
        showToast('Fitur Diagnostic Assessment khusus pengguna Premium. Silakan pilih paket belajar Anda.');
        openPlanSelector();
        return;
      }
      try {
        const res = await api.startDiagnostic();
        if (res?.data && res.data.length > 0) {
          // Map backend format to frontend format
          diagnosticQuestions.value = res.data.map(q => ({
            id: q.id,
            category: q.domain || q.subtes || 'Umum',
            skill: q.sub_materi || 'Analisis Logika',
            difficulty: q.difficulty || 'SEDANG',
            question: q.question,
            options: [q.option_a, q.option_b, q.option_c, q.option_d, q.option_e].filter(Boolean)
          }));
        } else {
          console.warn('API diagnostic kosong, gunakan bank soal lokal');
          diagnosticQuestions.value = DIAGNOSTIC_QUESTIONS;
        }
      } catch (e) {
        console.warn('Error startDiagnostic dari API, gunakan fallback lokal', e);
        diagnosticQuestions.value = DIAGNOSTIC_QUESTIONS;
      }

      if (diagnosticQuestions.value && diagnosticQuestions.value.length > 0) {
        diagnosticActive.value = true;
        diagnosticFinished.value = false;
        diagnosticIdx.value = 0;
        selectedDiagAnswer.value = null;
        diagnosticAnswers.value = [];
        showToast('Diagnostic Assessment Dimulai!');
      } else {
        showToast('Gagal memuat soal diagnostic.', 'error');
      }
    };

    const submitDiagAnswer = () => {
      // Store the answer (convert idx to A, B, C, D, E)
      if (selectedDiagAnswer.value !== null) {
        const letter = String.fromCharCode(65 + selectedDiagAnswer.value);
        diagnosticAnswers.value.push({
          question_id: currentDiagQuestion.value.id,
          answer: letter
        });
      }
      
      if (diagnosticIdx.value < diagnosticQuestions.value.length - 1) {
        diagnosticIdx.value++;
        selectedDiagAnswer.value = null;
      } else {
        finishDiagnostic();
      }
    };

    const finishDiagnostic = async () => {
      diagnosticActive.value = false;
      
      try {
        const res = await api.submitDiagnostic(diagnosticAnswers.value);
        if (res.success) {
          diagnosticFinished.value = true;
          diagnosticResultData.value = res;
          showToast('Diagnostic Assessment Selesai! Jalur belajar telah dikalibrasi.');
        } else {
          showToast(res.error || 'Gagal menyimpan hasil diagnostic.', 'error');
        }
      } catch (e) {
        console.error('Error submitDiagnostic', e);
        showToast(e.message || 'Gagal menyimpan hasil diagnostic.', 'error');
      }
    };

    const resetDiagnostic = () => {
      diagnosticFinished.value = false;
      diagnosticActive.value = false;
      diagnosticResultData.value = null;
    };

    const diagnosticFocus = computed(() => diagnosticActive.value || diagnosticFinished.value);

    const materiUtbk = ref(MATERI_UTBK);
    const selectedSubtes = ref(MATERI_UTBK[0]);
    const selectedBab = ref(MATERI_UTBK[0].babList[0]);

    const restoreSavedMaterial = () => {
      try {
        const savedSub = sessionStorage.getItem('ep_selected_subtes');
        const savedBab = sessionStorage.getItem('ep_selected_bab');
        if (savedSub && materiUtbk.value) {
          const targetSub = materiUtbk.value.find(s => s.id === savedSub || s.code === savedSub || s.subtes === savedSub);
          if (targetSub) {
            selectedSubtes.value = targetSub;
            if (savedBab && targetSub.babList) {
              const targetBab = targetSub.babList.find(b => b.id === savedBab || b.judul === savedBab);
              if (targetBab) {
                selectedBab.value = targetBab;
              }
            }
          }
        }
      } catch (e) {}
    };

    restoreSavedMaterial();

    const selectSubtes = (subtes) => {
      selectedSubtes.value = subtes;
      selectedBab.value = subtes.babList[0];
      selectedLessonQuizAns.value = null;
      showLessonQuizFeedback.value = false;
      try {
        sessionStorage.setItem('ep_selected_subtes', subtes.id || subtes.code || subtes.subtes || subtes.subtest);
        if (subtes.babList && subtes.babList[0]) {
          sessionStorage.setItem('ep_selected_bab', subtes.babList[0].id || subtes.babList[0].judul);
        }
      } catch (e) {}
    };

    const onSubtestDropdownChange = (event) => {
      const val = event.target.value;
      if (!val || !materiUtbk.value) return;
      const target = materiUtbk.value.find(s => 
        s.id === val || s.code === val || s.subtest === val || s.subtes === val
      );
      if (target) {
        selectSubtes(target);
      }
    };

    const selectBab = (bab) => {
      selectedBab.value = bab;
      selectedLessonQuizAns.value = null;
      showLessonQuizFeedback.value = false;
      try {
        sessionStorage.setItem('ep_selected_bab', bab.id || bab.judul);
      } catch (e) {}
    };

    const microLessons = ref(MICRO_LESSONS);
    const selectedLesson = ref(MICRO_LESSONS[0]);
    const selectedLessonQuizAns = ref(null);
    const showLessonQuizFeedback = ref(false);

    const microQuizFocus = ref(false);

    const selectMicroLesson = (lesson) => {
      selectedLesson.value = lesson;
      selectedLessonQuizAns.value = null;
      showLessonQuizFeedback.value = false;
      microQuizFocus.value = false;
    };

    const selectLessonQuizOption = async (idx) => {
      selectedLessonQuizAns.value = idx;
      showLessonQuizFeedback.value = true;
      const isCorrect = idx === (selectedBab.value ? selectedBab.value.microLesson.quiz.answer : selectedLesson.value.quiz.answer);
      
      if (isCorrect) {
        coins.value += 10;
        if (isLoggedIn.value && selectedSubtes.value && selectedBab.value) {
          try {
            await api.saveProgress(selectedSubtes.value.id, selectedBab.value.id, 100, 2);
          } catch(e) {}
        }
      }
    };

    const activePracticeQuestion = ref(DIAGNOSTIC_QUESTIONS[0]);
    const practiceUserAnswer = ref(null);
    const practiceEvaluated = ref(false);
    const aiChatHistory = ref([
      { sender: 'ai', senderName: 'AI Tutor Companion', text: 'Halo! Saya AI Tutor pendamping belajarmu. Coba pecahkan soal di samping terlebih dahulu. Jika kesulitan, kamu bisa menanyakan Hint ke saya!' }
    ]);

    const showAICompanion = ref(false);

    const checkPracticeAnswer = () => {
      practiceEvaluated.value = true;
      if (practiceUserAnswer.value === activePracticeQuestion.value.answer) {
        showToast('Jawaban Anda Benar! +20 Coins.');
        coins.value += 20;
      }
    };

    const triggerAIEscalation = () => {
      showAICompanion.value = true;
      askAILevel(1);
    };

    const askAILevel = (level) => {
      let responseText = (level === 1) ? `[HINT]: ${activePracticeQuestion.value.hint}` : `[SOLUSI]: ${activePracticeQuestion.value.explanation}`;
      aiChatHistory.value.push({ sender: 'user', senderName: 'Siswa', text: `Bisa bantu level ${level}?` });
      setTimeout(() => {
        aiChatHistory.value.push({ sender: 'ai', senderName: 'AI Tutor Companion', text: responseText });
      }, 500);
    };

    const triggerSOSCall = () => {
      showAICompanion.value = true;
      showToast(' SOS Tutor Terkirim! Tutor manusia akan segera membantu.');
      aiChatHistory.value.push({ sender: 'system', senderName: 'SISTEM', text: 'SOS diaktifkan.' });
    };

    const onlineStudents = ref(1482);
    const timerMinutes = ref(25);
    const timerSeconds = ref(0);
    const timerActive = ref(false);
    const isBreak = ref(false);
    const currentTrack = ref('relax');
    let timerInterval = null;

    const formattedTime = computed(() => `${timerMinutes.value.toString().padStart(2, '0')}:${timerSeconds.value.toString().padStart(2, '0')}`);

    const toggleTimer = () => {
      if (timerActive.value) {
        clearInterval(timerInterval);
        timerActive.value = false;
      } else {
        timerActive.value = true;
        timerInterval = setInterval(() => {
          if (timerSeconds.value > 0) timerSeconds.value--;
          else if (timerMinutes.value > 0) { timerMinutes.value--; timerSeconds.value = 59; }
          else { clearInterval(timerInterval); timerActive.value = false; }
        }, 1000);
      }
    };

    const resetTimer = () => {
      clearInterval(timerInterval);
      timerActive.value = false;
      timerMinutes.value = isBreak.value ? 5 : 25;
      timerSeconds.value = 0;
    };

    const playTrack = (trackId) => {
      currentTrack.value = trackId;
      showToast(`Memutar track: ${trackId}`);
    };

    const simulateTelegramSent = () => showToast(' Laporan mingguan sukses dikirimkan ke Telegram Orang Tua.');

    const liveActivityList = [
      { icon: '🔥', title: 'EduPath Beta Aktif', desc: 'Platform sedang dalam tahap pengembangan & pengujian', time: 'Live' },
      { icon: '🚀', title: 'Fitur Baru Segera Hadir', desc: 'Daftar sekarang untuk akses early bird & notifikasi peluncuran', time: 'Coming Soon' }
    ];
    const currentActivityIdx = ref(0);
    const showLiveActivity = ref(true);

    let countdownTimer = null;
    let activityTimer = null;

    const isJoiningAffiliate = ref(false);
    const isLoadingAffiliate = ref(false);
    const isEditingProfile = ref(false);

    const startEditingProfile = () => {
      if (currentUser.value) {
        profileEditForm.value.name = currentUser.value.name || '';
        profileEditForm.value.email = currentUser.value.email || '';
        profileEditForm.value.school = currentUser.value.school || '';
        profileEditForm.value.school_id = currentUser.value.school_id || '';
        profileEditForm.value.target_ptn = currentUser.value.target_ptn || '';
        profileEditForm.value.phone = currentUser.value.phone || '';
      }
      isEditingProfile.value = true;
    };

    const cancelEditingProfile = () => {
      isEditingProfile.value = false;
      profileEditForm.value.password = '';
    };

    const affiliateData = ref({
      status: 'loading',
      commission_rate: 20,
      referral_code: '',
      stats: {
        total_referrals: 0,
        active_referrals: 0,
        total_paid: 0,
        total_pending: 0
      },
      referrals: [],
      history: [],
      bank_info: { bank_name: '', bank_account: '', bank_owner: '' }
    });
    const activeAffiliateTab = ref('rujukan');

    const joinAffiliateProgram = async () => {
      isJoiningAffiliate.value = true;
      try {
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
        const apiUrl = import.meta.env.VITE_API_URL || '/api';
        const res = await fetch(`${apiUrl}/affiliate.php?action=join`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ action: 'join' })
        });
        
        if (!res.ok) {
          throw new Error('Gagal mengaktifkan afiliasi');
        }
        
        showToast('Berhasil mengaktifkan Program Afiliasi!');
        
        // Refresh data
        await fetchAffiliateData();
      } catch(err) {
        alert(err.message);
      } finally {
        isJoiningAffiliate.value = false;
      }
    };

    const isSavingProfile = ref(false);
    const profileEditForm = ref({
      name: '',
      email: '',
      password: '',
      school: '',
      school_id: '',
      bank_name: '',
      bank_account: '',
      bank_owner: ''
    });

    const saveProfileAndBankSettings = async () => {
      isSavingProfile.value = true;
      try {
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
        const apiUrl = import.meta.env.VITE_API_URL || '/api';

        // 1. Update Profile (Name, Email, Password if provided, School)
        const profileRes = await fetch(`${apiUrl}/auth.php?action=update_profile`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({
            name: profileEditForm.value.name || undefined,
            email: profileEditForm.value.email || undefined,
            password: profileEditForm.value.password || undefined,
            school: profileEditForm.value.school || undefined,
            school_id: profileEditForm.value.school_id || undefined
          })
        });

        const profileData = await profileRes.json();
        if (!profileRes.ok) {
          throw new Error(profileData.error || 'Gagal memperbarui profil');
        }

        if (profileData.token) {
          localStorage.setItem('auth_token', profileData.token);
        }

        // 2. Update Bank Info if any bank field is filled
        if (profileEditForm.value.bank_name || profileEditForm.value.bank_account || profileEditForm.value.bank_owner) {
          try {
            await fetch(`${apiUrl}/affiliate.php?action=update_bank`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
              },
              body: JSON.stringify({
                action: 'update_bank',
                bank_name: profileEditForm.value.bank_name,
                bank_account: profileEditForm.value.bank_account,
                bank_owner: profileEditForm.value.bank_owner
              })
            });
          } catch (bErr) {
            console.warn('Bank info update warning:', bErr);
          }
        }

        // Update local currentUser state
        if (profileData.user) {
          currentUser.value = { ...currentUser.value, ...profileData.user };
        } else if (currentUser.value) {
          if (profileEditForm.value.name) currentUser.value.name = profileEditForm.value.name;
          if (profileEditForm.value.email) currentUser.value.email = profileEditForm.value.email;
          if (profileEditForm.value.school !== undefined) currentUser.value.school = profileEditForm.value.school;
          if (profileEditForm.value.school_id !== undefined) currentUser.value.school_id = profileEditForm.value.school_id;
        }

        // Persist to storage so reload never reverts profile
        if (currentUser.value) {
          localStorage.setItem('user', JSON.stringify(currentUser.value));
          sessionStorage.setItem('ep_user', JSON.stringify(currentUser.value));
          if (currentUser.value.name) localStorage.setItem('user_name', currentUser.value.name);
          if (currentUser.value.email) localStorage.setItem('user_email', currentUser.value.email);
        }

        // Reset password field after save & exit edit mode
        profileEditForm.value.password = '';
        isEditingProfile.value = false;

        showToast('Profil & pengaturan berhasil disimpan!');
        await fetchAffiliateData().catch(() => {});
      } catch (err) {
        alert(err.message || 'Terjadi kesalahan saat menyimpan pengaturan');
      } finally {
        isSavingProfile.value = false;
      }
    };

    const fetchAffiliateData = async () => {
      isLoadingAffiliate.value = true;
      try {
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('ep_session_token');
        if (!token) {
          affiliateData.value.status = 'not_joined';
          return;
        }
        const apiUrl = import.meta.env.VITE_API_URL || '/api';
        const res = await fetch(`${apiUrl}/affiliate.php?action=get_data`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        if (res.ok) {
          const data = await res.json();
          if (data.status === 'not_joined') {
            affiliateData.value.status = 'not_joined';
          } else {
            affiliateData.value = {
              status: data.status || 'joined',
              referral_code: data.referral_code || '',
              commission_rate: data.commission_rate || 20,
              stats: {
                total_referrals: Number(data.stats?.total_referrals || 0),
                active_referrals: Number(data.stats?.active_referrals || (data.referrals ? data.referrals.filter(r => r.plan).length : 0)),
                total_paid: Number(data.stats?.total_paid || 0),
                total_pending: Number(data.stats?.total_pending || 0)
              },
              bank_info: data.bank_info || {},
              referrals: (data.referrals || []).map(r => ({
                name: r.name || 'Siswa',
                school: r.school || '-',
                date: r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : (r.date || '-'),
                status: r.plan ? 'Aktif' : (r.status || 'Terdaftar')
              })),
              history: data.history || []
            };

            const b = data.bank_info || data.bank;
            if (b) {
              if (b.bank_name) profileEditForm.value.bank_name = b.bank_name;
              if (b.bank_account) profileEditForm.value.bank_account = b.bank_account;
              if (b.bank_owner) profileEditForm.value.bank_owner = b.bank_owner;
            }
          }
          if (currentUser.value) {
            if (!profileEditForm.value.name) profileEditForm.value.name = currentUser.value.name || '';
            if (!profileEditForm.value.email) profileEditForm.value.email = currentUser.value.email || '';
          }
        } else if (res.status === 403) {
          affiliateData.value.status = 'staff_mode';
        }
      } catch(err) {
        console.error("fetchAffiliateData error:", err);
      } finally {
        isLoadingAffiliate.value = false;
      }
    };

    onMounted(() => {
      updateCountdown();
      countdownTimer = setInterval(updateCountdown, 1000);
      activityTimer = setInterval(() => { currentActivityIdx.value = (currentActivityIdx.value + 1) % liveActivityList.length; }, 4500);

      setInterval(() => {
        currentWordIdx.value = (currentWordIdx.value + 1) % rotatingWords.length;
      }, 2500);

      const ambientGlow = ambientGlowRef.value;
      if (ambientGlow) {
        document.addEventListener('mousemove', (e) => {
          ambientGlow.style.left = `${e.clientX}px`;
          ambientGlow.style.top = `${e.clientY}px`;
        });
      }

      // Scroll Reveal Observer
      initScrollReveal();
      initNeuralCanvas();
      restoreSavedMaterial();
      
      if (isLoggedIn.value) {
        fetchAffiliateData();
      }
    });

    onUnmounted(() => {
      if (countdownTimer) clearInterval(countdownTimer);
      if (activityTimer) clearInterval(activityTimer);
      
    });

    return {
      showIrtInfo,
      hideBabSidebar,
      checkoutTermsAgreed,
      checkoutModalData,
      confirmCheckout,
      closeCheckoutModal,
      ambientGlowRef,
      isLoggedIn,
      currentUser,
      showLoginModal,
      isLoginMode,
      authForm,
      showPassword,
      authLoading,
      authError,
      doAuth,
      isForgotPasswordMode,
      isResetPasswordMode,
      resetToken,
      newPassword,
      handleForgotPassword,
      showTermsModal,
      showPrivacyModal,
      showDisclaimerModal,
      showParentConsentModal,
      showContactModal,
      showAffiliateRegisterModal,
      baseUrl,
      copyReferralLink,
      copyCompanyAddress,
      sidebarExpanded,
      login,
      logout,
      handleTabClick,
      selectSubtesFromSidebar,
      materiSubMenuOpen,
      streakCount,
      coins,
      currentAbilityScore,
      toastMessage,
      universities,
      selectedUniversity,
      skillMap,
      getSkillMastery,
      getSkillColor,
      gapScore,
      progressPercentage,
      recalcTargetGap,
      currentTab,
      canvasOpacity,
      handleScroll,
      tabs,
      adminTabs,
      filteredTabs,
      isStudentPreviewMode,
      dailyMissions,
      checkMissionReward,
      learningRecommendations,
      rotatingWords,
      currentWordIdx,
      startLearning,
      purchasePlan,
      scrollToSection,
      goToHomeTop,
      toolsDropdownOpen,
      handleTakeReadinessFromSpp,
      // Live Countdown & Hero
      countdownDays,
      countdownHours,
      countdownMinutes,
      countdownSeconds,
      liveActivityList,
      currentActivityIdx,
      showLiveActivity,

      // Target PTN & Chance Simulator
      targetPtnList,
      simTargetPtn,
      simCurrentScore,
      simDailyHours,
      simPredictedGain,
      simScoreIncrease,
      simChancePercentage,
      simChanceStatus,

      // Mini HOTS Quiz Widget
      miniQuizQuestions,
      miniQuizActiveIdx,
      miniQuizUserAnswer,
      miniQuizShowExplanation,
      selectMiniQuizOption,
      nextMiniQuiz,

      // Testimonials & Pricing
      testimonialFilter,
      testimonialsList,
      filteredTestimonials,
      isAnnualBilling,

      faqs,
      toggleFaq,
      diagnosticActive,
      diagnosticFinished,
      diagnosticIdx,
      selectedDiagAnswer,
      currentDiagQuestion,
      diagnosticQuestions,
      startDiagnostic,
      submitDiagAnswer,
      resetDiagnostic,
      materiUtbk,
      selectedSubtes,
      selectedBab,
      selectSubtes,
      onSubtestDropdownChange,
      selectBab,
      renderMaterialHtml,
      microLessons,
      selectedLesson,
      selectedLessonQuizAns,
      showLessonQuizFeedback,
      microQuizFocus,
      selectMicroLesson,
      selectLessonQuizOption,
      activePracticeQuestion,
      practiceUserAnswer,
      practiceEvaluated,
      showAICompanion,
      aiChatHistory,
      checkPracticeAnswer,
      triggerAIEscalation,
      askAILevel,
      triggerSOSCall,
      onlineStudents,
      formattedTime,
      isBreak,
      timerActive,
      currentTrack,
      toggleTimer,
      resetTimer,
      playTrack,
      simulateTelegramSent,
      ansProj1,
      ansProj2,
      ansProj3,
      ansProj4,
      ansProj5,
      activeSubtest,
      SIMULATOR_DATABASE,
      userAnswersMap,
      analisaWorking,
      answerAnalisaQuestion,
      cbtCurrentIndex,
      cbtRaguMap,
      cbtFontSize,
      cbtShowAnalysis,
      cbtTimerSeconds,
      cbtFormattedTimer,
      currentSubtestQuestions,
      currentCbtQuestion,
      toggleRaguRagu,
      jumpToQuestion,
      prevQuestion,
      nextQuestion,
      setSubtest,
      mobileSidebarOpen,
      toggleMobileSidebar,
      affiliateForm,
      isRegistering,
      registerAffiliate,
      isJoiningAffiliate,
      isLoadingAffiliate,
      affiliateData,
      joinAffiliateProgram,
      activeAffiliateTab,
      profileEditForm,
      isSavingProfile,
      saveProfileAndBankSettings,
      isAdmin,
      isTeacher,
      isStaff,
      isAffiliate,
      isSchoolBk,
      isPremiumUser,
      getStudentDisplayName,
      showPlanSelectorModal,
      openPlanSelector,
      isEditingProfile,
      startEditingProfile,
      cancelEditingProfile,
      displayProducts,
      publicPlans,
      publicSchoolsList,
      matchedPartnerSchool,
      onSchoolSelected
    };
  }
};
</script>

<style scoped>
@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>












