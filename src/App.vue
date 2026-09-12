
<template>
  <div :class="['relative min-h-screen antialiased font-body flex overflow-hidden transition-colors duration-300', 
    isLoggedIn ? 'bg-[#f8fafc] text-slate-900' : 'bg-[#050505] text-white',
    mobileSidebarOpen ? 'mobile-sidebar-open' : ''
  ]">
    <!-- Ambient Glow (Public only) -->
    <div v-if="!isLoggedIn" id="ambient-glow" ref="ambientGlowRef" class="opacity-80"></div>
    
    <!-- Background Blobs & Neural Canvas (Public only) -->
    <div v-if="!isLoggedIn" class="fixed top-[-20%] left-[-10%] w-[500px] h-[500px] rounded-full bg-white/5 blur-[120px] pointer-events-none z-0 animate-pulse"></div>
    <div v-if="!isLoggedIn" class="fixed bottom-[-20%] left-1/4 w-[600px] h-[600px] rounded-full bg-primary/8 blur-[130px] pointer-events-none z-0" style="animation: float 10s ease-in-out infinite alternate"></div>
    <canvas id="bg-canvas" :style="{ opacity: canvasOpacity }" class="fixed inset-0 w-full h-full z-0 pointer-events-none transition-opacity duration-700"></canvas>


    <!-- Sidebar Navigation — auto-hide (icon rail), expand on hover -->
    <aside
      v-if="isLoggedIn"
      class="fixed left-0 top-0 h-screen z-40 flex flex-col backdrop-blur-xl border-r border-white/5 transition-all duration-300 ease-out overflow-hidden text-white shadow-2xl"
      style="background: linear-gradient(160deg, #0a0f1e 0%, #0d1224 60%, #0a0f1e 100%);"
      :class="sidebarExpanded ? 'w-72 shadow-[20px_0_60px_rgba(0,0,0,0.35)]' : 'w-[72px]'"
      @mouseenter="sidebarExpanded = true"
      @mouseleave="sidebarExpanded = false"
    >
      <!-- Top: Logo + Nav (scrollable) -->
      <div class="flex flex-col flex-grow min-h-0 overflow-y-auto" :class="sidebarExpanded ? 'p-6 pb-2' : 'p-3 pb-2'">
        <div class="flex items-center gap-3 mb-2 group cursor-pointer shrink-0" :class="sidebarExpanded ? '' : 'justify-center'" @click="goToHomeTop">
          <div class="w-10 h-10 shrink-0 rounded-full bg-white flex items-center justify-center font-black text-black text-xl group-hover:bg-[#c0ff00] group-hover:rotate-12 transition-all duration-300 shadow-md">E</div>
          <div v-show="sidebarExpanded" class="min-w-0">
            <span class="font-black text-2xl tracking-tighter text-white whitespace-nowrap">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          </div>
        </div>
        <p v-show="sidebarExpanded" class="text-xs text-white/40 font-semibold mb-6 uppercase tracking-wider whitespace-nowrap shrink-0">Adaptive Learning Platform</p>
        <p v-show="!sidebarExpanded" class="mb-4 shrink-0"></p>
        
        <nav class="flex flex-col gap-1.5">
          <template v-for="tab in tabs" :key="tab.id">
            <button 
              :title="tab.name"
              :class="['flex items-center rounded-xl text-xs font-bold transition-all text-left group', 
                sidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center px-0 py-2.5',
                currentTab === tab.id 
                  ? 'bg-[#c0ff00]/15 border border-[#c0ff00]/40 text-[#c0ff00] shadow-[0_0_20px_rgba(192,255,0,0.15)]' 
                  : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
              ]"
              @click="handleTabClick(tab.id)"
            >
              <i :class="['ph-bold shrink-0', tab.iconName, sidebarExpanded ? 'text-lg' : 'text-xl', currentTab === tab.id ? 'text-[#c0ff00]' : 'text-white/60 group-hover:text-white']"></i>
              <span v-show="sidebarExpanded" class="font-bold tracking-tight whitespace-nowrap">{{ tab.name }}</span>
            </button>

            <!-- Sub Menu Materi di Side Menu (Auto Hide) -->
            <div v-if="tab.id === 'learning' && sidebarExpanded && materiSubMenuOpen" class="ml-4 pl-3 border-l border-white/10 flex flex-col gap-1 my-1 animate-fade-in">
              <button
                v-for="subtes in materiUtbk"
                :key="subtes.id"
                :class="['text-left text-[11px] font-semibold px-3 py-1.5 rounded-lg transition-all flex items-center gap-2', 
                  currentTab === 'learning' && selectedSubtes.id === subtes.id
                    ? 'bg-[#c0ff00]/15 text-[#c0ff00] font-bold border border-[#c0ff00]/30'
                    : 'text-white/50 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
                @click.stop="selectSubtesFromSidebar(subtes)"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-[#c0ff00]/70 shrink-0"></span>
                <span class="truncate">{{ subtes.subtes }}</span>
              </button>
            </div>
          </template>
        </nav>
      </div>

      <!-- Bottom: User + Logout -->
      <div class="shrink-0 border-t border-white/10" :class="sidebarExpanded ? 'p-4 mx-2 mb-2' : 'p-2 mx-1 mb-2'">
        <div class="flex items-center gap-3 mb-3" :class="sidebarExpanded ? '' : 'justify-center'">
          <div class="w-9 h-9 shrink-0 rounded-full bg-white/10 flex items-center justify-center font-bold text-white text-xs border border-white/20">SM</div>
          <div v-show="sidebarExpanded" class="flex-grow min-w-0">
            <h4 class="text-xs font-black text-white whitespace-nowrap">Siswa Mandiri</h4>
            <span class="text-[10px] text-[#c0ff00] font-black uppercase tracking-wider whitespace-nowrap">Pro Member</span>
          </div>
        </div>
        <!-- Tombol Logout -->
        <button
          @click="logout"
          :title="'Keluar Akun'"
          :class="['bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-400 rounded-xl font-bold transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95', sidebarExpanded ? 'w-full py-2.5 text-xs' : 'w-9 h-9 text-sm']"
        >
          <i class="ph-bold ph-sign-out shrink-0 text-base"></i>
          <span v-show="sidebarExpanded" class="whitespace-nowrap">Keluar Akun</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main @scroll="handleScroll" class="relative z-10 flex-grow overflow-y-auto overflow-x-hidden max-h-screen flex flex-col w-full" :class="isLoggedIn ? 'p-6 pl-6 ml-[72px] bg-[#f8fafc]' : ''">
      
      <!-- Public Top Navbar (Only visible when Logged Out) -->
      <header v-if="!isLoggedIn" class="w-full flex justify-center pt-3 px-3 sm:pt-4 sm:px-4 shrink-0 z-30 fixed top-0 left-0 right-0">
        <div class="glass-pill rounded-full px-3 sm:px-6 py-2 sm:py-2.5 flex items-center justify-between shadow-[0_8px_32px_rgba(0,0,0,0.5)] w-full sm:w-[90%] max-w-5xl border border-white/15 gap-2">
          <!-- Hamburger hanya di mobile -->
          <button @click="toggleMobileSidebar" class="lg:hidden flex-shrink-0 w-8 h-8 rounded-lg bg-white/10 border border-white/15 text-white flex items-center justify-center hover:bg-white/20 transition-all" aria-label="Menu">
            <i class="ph-bold ph-list text-base"></i>
          </button>

          <div id="nav-logo" class="flex items-center gap-2 group cursor-pointer select-none flex-shrink-0" @click="goToHomeTop">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white flex items-center justify-center font-black text-black text-sm sm:text-base group-hover:bg-[#c0ff00] group-hover:rotate-12 transition-all duration-300 shadow-sm">E</div>
            <span class="font-black text-base sm:text-xl tracking-tighter text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
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
            <button @click="showLoginModal = true" class="h-8 px-3 sm:px-5 rounded-full border border-white/20 bg-white/5 hover:bg-white/10 text-white font-bold text-[11px] sm:text-xs transition-all flex items-center justify-center hover:scale-105 active:scale-95 whitespace-nowrap flex-shrink-0">
              Masuk
            </button>
          </div>
        </div>
      </header>

      <!-- App Header Utility (Only visible when Logged In — Crisp Light Theme) -->
      <header v-if="isLoggedIn" class="flex justify-between items-center pb-4 border-b border-slate-200/90 mb-6 shrink-0 bg-white/90 backdrop-blur px-6 py-3.5 -mx-6 -mt-6 rounded-b-2xl shadow-sm">
        <div class="flex items-center gap-3">
          <label class="text-xs text-slate-500 font-bold uppercase tracking-wider">Target PTN:</label>
          <div class="relative">
            <select v-model="selectedUniversity" class="bg-slate-50 border border-slate-200 text-slate-900 text-xs font-bold py-1.5 px-3 pr-7 rounded-xl appearance-none outline-none focus:border-indigo-500 shadow-sm transition-colors cursor-pointer" @change="recalcTargetGap">
              <option v-for="u in universities" :key="u.name" :value="u">
                {{ u.name }} (Target: {{ u.targetScore }})
              </option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs">
              <i class="ph-bold ph-caret-down"></i>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 shadow-sm">
            <span class="text-indigo-600 font-black">STREAK</span> <span>{{ streakCount }} Hari</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 shadow-sm">
            <span class="text-amber-500 font-black">COINS</span> <span>{{ coins }}</span>
          </div>
        </div>
      </header>


      <!-- Main Panel content wrap -->
      <div class="flex-grow relative">
        <!-- TAB 0: LANDING PAGE -->
        <section v-if="currentTab === 'home'" id="landing-top" class="animate-fade-in space-y-16 sm:space-y-24 pt-20 md:pt-24 pb-16 relative w-full">
          
          <!-- Hero Section -->
          <div class="hero-bg -mt-20 md:-mt-24 min-h-[85vh] sm:min-h-[90vh] md:min-h-screen flex flex-col justify-end pt-40 sm:pt-32 pb-6 md:pb-10 relative z-0 overflow-hidden">
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
              <div class="flex justify-center items-center gap-3 pt-6 sm:pt-10 md:pt-14 relative z-20">
                <button class="h-8 px-3 sm:px-5 rounded-full border border-[#c0ff00]/40 bg-[#c0ff00]/10 hover:bg-[#c0ff00]/20 text-[#c0ff00] font-bold text-[11px] sm:text-xs transition-all flex items-center justify-center gap-1.5 shadow-[0_0_15px_rgba(192,255,0,0.15)] hover:scale-105 hover:shadow-[0_0_20px_rgba(192,255,0,0.3)] active:scale-95 whitespace-nowrap" @click="startLearning">
                  Mulai Belajar
                  <i class="ph-bold ph-arrow-right"></i>
                </button>
                <button class="h-8 px-3 sm:px-5 rounded-full border border-white/20 bg-white/5 hover:bg-white/10 text-white font-bold text-[11px] sm:text-xs transition-all flex items-center justify-center gap-1.5 hover:scale-105 active:scale-95 whitespace-nowrap" @click="scrollToSection('calculator')">
                  Hitung Peluang
                </button>
              </div>

              <div class="text-[11px] text-white/40 font-medium pb-2">
                Untuk Siswa SMA &amp; Pejuang Gap Year yang serius meraih PTN impian.
              </div>
            </div>
          </div>

          <!-- Trust Bar — Premium Ticker (Mobile-safe) -->
          <div class="w-full relative -mt-12 sm:-mt-16 md:-mt-24 mb-12 sm:mb-16 z-10 overflow-hidden" style="transform: rotate(-2deg) scale(1.05);">
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



          <div id="problems" class="space-y-20 scroll-mt-24">
            <!-- Section Heading -->
            <div class="text-center max-w-3xl mx-auto space-y-4 reveal">
              <span class="text-white/40 text-sm font-bold uppercase tracking-widest block">TANTANGAN NYATA</span>
              <h2 class="text-5xl md:text-7xl font-black font-heading text-white leading-tight">
                Belajar Keras<br />Tapi <span style="color: #c0ff00;">Tanpa Arah?</span>
              </h2>
              <p class="text-lg text-white/50 font-medium leading-relaxed">
                Banyak pejuang PTN belajar penuh semangat, tapi tanpa peta yang jelas. Inilah 3 jebakan paling umum.
              </p>
            </div>

            <!-- 3 Staggered Cards — seperti SAMPLE.HTML -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative items-end">
              <!-- Card 1 — rotate -3deg -->
              <div class="reveal reveal-delay-1">
                <div class="bouncy-card group rounded-3xl p-8 pt-16 relative origin-bottom -rotate-3 hover:rotate-0 hover:-translate-y-4 hover:scale-[1.03] hover:border-[#c0ff00]/50 hover:shadow-[0_20px_50px_-10px_rgba(192,255,0,0.2)] hover:z-20 transition-spring h-full">
                  <div class="absolute -top-8 left-8 w-20 h-20 rounded-3xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-3xl shadow-[0_0_30px_rgba(192,255,0,0.4)]">01</div>
                  <div class="space-y-3">
                    <h3 class="text-2xl md:text-3xl font-black text-white">Blind Spot Belajar</h3>
                    <p class="text-white/50 font-medium leading-relaxed">Belajar acak tanpa tahu materi mana yang paling nahan kenaikan skormu. Gak terarah, gak efisien.</p>
                  </div>
                </div>
              </div>

              <!-- Card 2 — center, taller -->
              <div class="reveal reveal-delay-2 md:-translate-y-8">
                <div class="bouncy-card group rounded-3xl p-8 pt-16 relative origin-bottom hover:-translate-y-4 hover:scale-[1.03] hover:border-[#c0ff00]/50 hover:shadow-[0_20px_50px_-10px_rgba(192,255,0,0.2)] hover:z-20 transition-spring h-full">
                  <div class="absolute -top-8 left-8 w-20 h-20 rounded-3xl bg-white flex items-center justify-center font-black text-black text-3xl">02</div>
                  <div class="space-y-3">
                    <h3 class="text-2xl md:text-3xl font-black text-white">Hafalan Buta Rumus</h3>
                    <p class="text-white/50 font-medium leading-relaxed">Hafal rumus mati tanpa ngerti logikanya. Pas ketemu soal HOTS variasi baru — blank total.</p>
                  </div>
                </div>
              </div>

              <!-- Card 3 — rotate +3deg -->
              <div class="reveal reveal-delay-3">
                <div class="bouncy-card group rounded-3xl p-8 pt-16 relative origin-bottom rotate-3 hover:rotate-0 hover:-translate-y-4 hover:scale-[1.03] hover:border-[#c0ff00]/50 hover:shadow-[0_20px_50px_-10px_rgba(192,255,0,0.2)] hover:z-20 transition-spring h-full">
                  <div class="absolute -top-8 left-8 w-20 h-20 rounded-3xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-3xl shadow-[0_0_30px_rgba(192,255,0,0.4)]">03</div>
                  <div class="space-y-3">
                    <h3 class="text-2xl md:text-3xl font-black text-white">Skor Tryout Mandek</h3>
                    <p class="text-white/50 font-medium leading-relaxed">Latihan terlalu mudah atau susah tanpa kalibrasi IRT. Skor stuck di angka yang sama berbulan-bulan.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>



          <!-- STUDENT POTENTIAL PATH (SPP) SECTION -->
          <StudentPotentialPath @take-readiness="handleTakeReadinessFromSpp" />

          <!-- 3. Dynamic Section: KALKULATOR PREDIKSI SKOR & PELUANG LOLOS PTN AI -->
          <div id="calculator" class="space-y-12 scroll-mt-24 relative">
            <div class="text-center max-w-3xl mx-auto space-y-3 reveal">
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#c0ff00]/30 bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black uppercase tracking-widest">
                🎯 SIMULASI INTERAKTIF AI
              </span>
              <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black font-heading text-white leading-tight whitespace-nowrap">Hitung Peluang <span style="color: #c0ff00;">Lolos PTN-mu</span></h2>
              <p class="text-base md:text-lg text-white/50 font-medium max-w-2xl mx-auto">
                Geser slider sesuai kondisimu saat ini. Sistem AI EduPath akan mengkalkulasi proyeksi lonjakan nilai dan peluang kelulusanmu secara real-time!
              </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch max-w-5xl mx-auto">
              
              <!-- Left: Inputs & Sliders (7 cols) -->
              <div class="lg:col-span-7 glass-card rounded-3xl p-8 space-y-8 relative overflow-hidden border-white/10">
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <label class="text-xs font-black uppercase tracking-wider text-white/70 flex items-center gap-2">
                      <span>🏛️</span> Target Universitas &amp; Jurusan:
                    </label>
                    <span class="text-xs font-bold text-[#c0ff00] bg-[#c0ff00]/10 px-2.5 py-0.5 rounded-full border border-[#c0ff00]/30">
                      Target: {{ simTargetPtn.targetScore }} Poin
                    </span>
                  </div>
                  
                  <!-- PTN Selection Grid -->
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button
                      v-for="ptn in targetPtnList"
                      :key="ptn.id"
                      :class="['p-2.5 rounded-xl text-xs font-bold text-left transition-all border flex flex-col justify-between h-20',
                        simTargetPtn.id === ptn.id
                          ? 'bg-[#c0ff00] text-black border-[#c0ff00] shadow-[0_0_20px_rgba(192,255,0,0.3)] scale-[1.02]'
                          : 'bg-white/5 text-white/70 border-white/10 hover:border-white/30 hover:bg-white/10'
                      ]"
                      @click="simTargetPtn = ptn"
                    >
                      <span class="truncate font-black">{{ ptn.name }}</span>
                      <span :class="simTargetPtn.id === ptn.id ? 'text-black/80 font-mono text-[10px]' : 'text-[#c0ff00] font-mono text-[10px]'">
                        {{ ptn.targetScore }} Poin
                      </span>
                    </button>
                  </div>
                </div>

                <!-- Slider 1: Skor Tryout Terakhir -->
                <div class="space-y-3">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-wider text-white/70 flex items-center gap-2">
                      <span>📊</span> Skor Tryout Terakhir Kamu:
                    </span>
                    <span class="text-xl font-black font-heading text-white font-mono bg-black/60 px-3 py-1 rounded-xl border border-white/10">
                      {{ simCurrentScore }} <span class="text-xs text-white/40 font-normal">Poin</span>
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
                  <div class="flex justify-between text-[11px] font-bold text-white/30">
                    <span>400 (Pemula)</span>
                    <span>550 (Menengah)</span>
                    <span>750 (Master)</span>
                  </div>
                </div>

                <!-- Slider 2: Komitmen Belajar Harian -->
                <div class="space-y-3">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-wider text-white/70 flex items-center gap-2">
                      <span>⏱️</span> Komitmen Belajar per Hari:
                    </span>
                    <span class="text-xl font-black font-heading text-[#c0ff00] font-mono bg-black/60 px-3 py-1 rounded-xl border border-[#c0ff00]/30">
                      {{ simDailyHours }} <span class="text-xs text-[#c0ff00]/70 font-normal">Jam / Hari</span>
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
                  <div class="flex justify-between text-[11px] font-bold text-white/30">
                    <span>1 Jam (Santai)</span>
                    <span>3 Jam (Disiplin)</span>
                    <span>6 Jam (Mode Ambis)</span>
                  </div>
                </div>
              </div>

              <!-- Right: Realtime Dynamic AI Result Card (5 cols) -->
              <div class="lg:col-span-5 rounded-3xl p-8 flex flex-col justify-between relative overflow-hidden border border-white/15 bg-gradient-to-b from-[#141414] to-[#0a0a0a] shadow-2xl">
                <div class="absolute -right-12 -top-12 w-44 h-44 rounded-full bg-[#c0ff00]/10 blur-3xl pointer-events-none"></div>

                <div class="space-y-6 relative z-10">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-black tracking-wider uppercase text-white/50">PROYEKSI ANALITIK AI</span>
                    <span class="px-3 py-1 rounded-full text-xs font-black border" :class="simChanceStatus.badge">
                      {{ simChanceStatus.text }}
                    </span>
                  </div>

                  <!-- Probability Meter -->
                  <div class="space-y-2">
                    <div class="flex justify-between items-end">
                      <span class="text-xs font-bold text-white/60">Peluang Kelulusan:</span>
                      <span class="text-4xl font-black font-heading tracking-tighter" :style="{ color: simChanceStatus.color }">
                        {{ simChancePercentage }}%
                      </span>
                    </div>
                    <div class="w-full bg-black/80 rounded-full h-4 p-0.5 border border-white/10 overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-500 ease-out shadow-[0_0_15px_rgba(192,255,0,0.5)]"
                        :style="{ width: simChancePercentage + '%', backgroundColor: simChanceStatus.color }"
                      ></div>
                    </div>
                  </div>

                  <!-- Score Calculation Matrix -->
                  <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="p-3.5 rounded-2xl bg-white/[0.03] border border-white/10 text-center">
                      <div class="text-[10px] font-bold text-white/40 uppercase">Prediksi Skor AI</div>
                      <div class="text-2xl font-black text-white font-heading mt-1">{{ simPredictedGain }}</div>
                      <div class="text-[10px] font-bold text-[#c0ff00] mt-0.5">+{{ simScoreIncrease }} Poin Lonjakan</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/[0.03] border border-white/10 text-center">
                      <div class="text-[10px] font-bold text-white/40 uppercase">Target {{ simTargetPtn.name }}</div>
                      <div class="text-2xl font-black text-white/80 font-heading mt-1">{{ simTargetPtn.targetScore }}</div>
                      <div class="text-[10px] font-bold mt-0.5" :class="simPredictedGain >= simTargetPtn.targetScore ? 'text-[#c0ff00]' : 'text-rose-400'">
                        {{ simPredictedGain >= simTargetPtn.targetScore ? '✓ Lolos Passing Grade' : `Sisa ${simTargetPtn.targetScore - simPredictedGain} Poin Lagi` }}
                      </div>
                    </div>
                  </div>

                  <!-- AI Subtest Focus Tip -->
                  <div class="p-4 rounded-2xl bg-black/50 border border-white/10 space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#c0ff00]">
                      <span>⚡</span> Rekomendasi Subtes Prioritas:
                    </div>
                    <p class="text-xs text-white/70 leading-relaxed font-medium">
                      Fokus pada: <strong class="text-white">{{ simTargetPtn.focus }}</strong> untuk akselerasi skor maksimal di {{ simTargetPtn.name }}.
                    </p>
                  </div>
                </div>

                <div class="pt-6 relative z-10">
                  <button
                    class="w-full py-4 rounded-2xl font-black text-sm text-black transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(192,255,0,0.3)]"
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
          <div id="quiz-demo" class="space-y-12 scroll-mt-24 relative">
            <div class="text-center max-w-3xl mx-auto space-y-3 reveal">
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-sky-500/30 bg-sky-500/10 text-sky-400 text-xs font-black uppercase tracking-widest">
                🧪 UJI DIAGNOSTIK INSTAN
              </span>
              <h2 class="text-4xl md:text-6xl font-black font-heading text-white leading-tight">
                Coba 1 Soal HOTS &amp; <span style="color: #c0ff00;">Lihat Analisa AI</span>
              </h2>
              <p class="text-base md:text-lg text-white/50 font-medium max-w-2xl mx-auto">
                Buktikan sendiri bagaimana AI EduPath membongkar jebakan dan rumus cepat 30 detik tanpa registrasi.
              </p>
            </div>

            <div class="max-w-4xl mx-auto glass-card rounded-3xl p-8 md:p-10 border-white/15 relative overflow-hidden">
              <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-6 mb-6">
                <!-- Tabs Question Switcher -->
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="(q, idx) in miniQuizQuestions"
                    :key="idx"
                    :class="['px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2',
                      miniQuizActiveIdx === idx
                        ? 'bg-[#c0ff00] text-black shadow-[0_0_20px_rgba(192,255,0,0.3)]'
                        : 'bg-white/5 text-white/60 hover:text-white hover:bg-white/10'
                    ]"
                    @click="miniQuizActiveIdx = idx; miniQuizUserAnswer = null; miniQuizShowExplanation = false;"
                  >
                    <span>{{ q.icon }}</span>
                    <span>Soal {{ idx + 1 }}: {{ q.category }}</span>
                  </button>
                </div>

                <div class="text-xs font-bold text-[#c0ff00] flex items-center gap-1.5">
                  <span>✦</span> Bobot Skor IRT: <strong>+{{ miniQuizQuestions[miniQuizActiveIdx].irtScore }} Poin</strong>
                </div>
              </div>

              <!-- Active Question -->
              <div class="space-y-6">
                <div class="space-y-2">
                  <span class="text-xs font-black uppercase tracking-wider text-white/40 block">PERTANYAAN #{{ miniQuizActiveIdx + 1 }}</span>
                  <h3 class="text-lg md:text-2xl font-bold text-white leading-relaxed">
                    {{ miniQuizQuestions[miniQuizActiveIdx].question }}
                  </h3>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                  <button
                    v-for="(opt, oIdx) in miniQuizQuestions[miniQuizActiveIdx].options"
                    :key="oIdx"
                    :disabled="miniQuizShowExplanation"
                    :class="['p-4 rounded-2xl text-left text-sm font-semibold transition-all border flex items-center justify-between group',
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
                    <span class="flex-grow">{{ opt.text }}</span>
                    <span v-if="miniQuizUserAnswer === oIdx" class="text-lg shrink-0 ml-2">
                      {{ opt.isCorrect ? '✅' : '❌' }}
                    </span>
                  </button>
                </div>

                <!-- AI Breakdown Card (Shows up after answering) -->
                <div v-if="miniQuizShowExplanation" class="mt-6 p-6 rounded-2xl bg-black/60 border border-[#c0ff00]/30 space-y-4 animate-fade-in">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-full bg-[#c0ff00] text-black flex items-center justify-center font-black text-sm">AI</div>
                      <span class="text-sm font-black text-white">Analisis Logika &amp; Trik AI EduPath</span>
                    </div>
                    <span class="text-xs font-bold" :class="miniQuizQuestions[miniQuizActiveIdx].options[miniQuizUserAnswer]?.isCorrect ? 'text-[#c0ff00]' : 'text-amber-400'">
                      {{ miniQuizQuestions[miniQuizActiveIdx].options[miniQuizUserAnswer]?.isCorrect ? '🎉 Jawaban Tepat! +Bobot IRT Tercapai' : '💡 Belum Tepat! Simak Trik Cepat Berikut:' }}
                    </span>
                  </div>

                  <div class="space-y-3 text-xs md:text-sm text-white/80 leading-relaxed">
                    <p><strong>📖 Pembahasan Konseptual:</strong> {{ miniQuizQuestions[miniQuizActiveIdx].explanation }}</p>
                    <div class="p-3.5 rounded-xl bg-[#c0ff00]/10 border border-[#c0ff00]/20 text-[#c0ff00] font-medium flex items-start gap-2.5">
                      <span class="text-base shrink-0">⚡</span>
                      <span><strong>Trik Kilat EduPath:</strong> {{ miniQuizQuestions[miniQuizActiveIdx].trick }}</span>
                    </div>
                  </div>

                  <div class="flex justify-end pt-2">
                    <button
                      class="px-5 py-2.5 rounded-xl bg-white text-black font-black text-xs hover:bg-[#c0ff00] transition-all flex items-center gap-2"
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
          <div id="comparison" class="space-y-12 scroll-mt-24 relative">
            <div class="text-center max-w-4xl mx-auto space-y-3 reveal">
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-purple-500/30 bg-purple-500/10 text-purple-400 text-xs font-black uppercase tracking-widest">
                ⚖️ PERBANDINGAN STRATEGIS
              </span>
              <h2 class="text-3xl md:text-5xl font-black font-heading text-white leading-tight">
                Bimbel Biasa vs <span class="text-sky-400">Aplikasi Video Massal</span> vs <span style="color: #c0ff00;">EduPath.ai</span>
              </h2>
              <p class="text-base md:text-lg text-white/60 font-medium max-w-3xl mx-auto">
                Bandingkan secara objektif antara bimbel tatap muka konvensional, aplikasi video rekaman satu arah, dan teknologi Adaptive Learning Path EduPath.
              </p>
            </div>

            <!-- 3-Way Head-to-Head Cards -->
            <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
              
              <!-- Card 1: Bimbel Konvensional -->
              <div class="glass-card rounded-3xl p-7 md:p-8 space-y-6 border-rose-500/25 bg-rose-950/[0.08] hover:border-rose-500/40 transition-all flex flex-col justify-between">
                <div class="space-y-5">
                  <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                      <span class="text-[11px] font-black text-rose-400 uppercase tracking-wider block">METODE LAMA 🏫</span>
                      <h3 class="text-xl md:text-2xl font-black text-white">Bimbel Konvensional</h3>
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
              <div class="glass-card rounded-3xl p-7 md:p-8 space-y-6 border-sky-500/30 bg-sky-950/[0.12] hover:border-sky-500/50 transition-all flex flex-col justify-between">
                <div class="space-y-5">
                  <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                      <span class="text-[11px] font-black text-sky-400 uppercase tracking-wider block">VIDEO ON-DEMAND 📱</span>
                      <h3 class="text-xl md:text-2xl font-black text-white">Aplikasi Video Massal</h3>
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
              <div class="rounded-3xl p-7 md:p-8 space-y-6 border-2 border-[#c0ff00] bg-[#10160a] shadow-[0_0_50px_rgba(192,255,0,0.18)] relative flex flex-col justify-between lg:scale-105 lg:-translate-y-2 z-10">
                <span class="absolute top-0 right-0 bg-[#c0ff00] text-black text-[10px] font-black px-4 py-1.5 rounded-bl-2xl uppercase tracking-wider">
                  Pilihan Cerdas Pejuang PTN ⚡
                </span>

                <div class="space-y-5">
                  <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                      <span class="text-[11px] font-black text-[#c0ff00] uppercase tracking-wider block">TEKNOLOGI AI 2026 🚀</span>
                      <h3 class="text-xl md:text-2xl font-black text-white">EduPath.ai Adaptive</h3>
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
                    class="w-full py-3.5 md:py-4 rounded-2xl font-black text-xs md:text-sm text-black transition-all hover:scale-105 active:scale-95 shadow-[0_0_30px_rgba(192,255,0,0.4)]"
                    style="background: #c0ff00;"
                    @click="startLearning"
                  >
                    Mulai Jalur Belajar Adaptif ⚡
                  </button>
                  <p class="text-center text-[11px] text-white/50 font-medium">
                    ✨ Garansi 7 hari kepuasan belajar tanpa risiko
                  </p>
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
          <div class="relative py-12">
            <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-secondary/10 blur-3xl opacity-30 rounded-3xl"></div>
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
          <div id="benefits" class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-center scroll-mt-24">
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
          <div id="features" class="space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2 reveal">
              <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">ETALASE FITUR</span>
              <h2 class="text-4xl md:text-6xl font-black font-heading text-white">Alat Tempur <span class="text-[#c0ff00]">Terlengkap</span></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 auto-rows-[270px] max-w-5xl mx-auto">
              
              <!-- Feature 1: Adaptive Learning -->
              <div class="glass-card rounded-3xl p-8 col-span-1 md:col-span-2 lg:col-span-2 row-span-2 flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/10 blur-[80px] rounded-full group-hover:bg-primary/20 transition-all duration-700"></div>
                
                <div class="space-y-4 z-10">
                  <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-[#c0ff00] border border-primary/20">
                    <i class="ph-bold ph-cpu text-2xl"></i>
                  </div>
                  <h3 class="text-2xl md:text-3xl font-black text-white font-heading">AI Adaptive Assessment™</h3>
                  <p class="text-base text-slate-300 leading-relaxed font-medium">
                    Sistem mendeteksi secara instan di materi mana pemahaman konsep Anda melambat, lalu secara reaktif merubah susunan subtes berikutnya agar pas dengan porsi pemahaman Anda.
                  </p>
                </div>

                <div class="bg-slate-955 border border-slate-900 rounded-xl p-4 mt-6 z-10 relative overflow-hidden h-28 flex items-end gap-2">
                  <div class="w-1/4 bg-slate-800 rounded-t h-[40%] group-hover:h-[60%] transition-all duration-500"></div>
                  <div class="w-1/4 bg-slate-800 rounded-t h-[60%] group-hover:h-[80%] transition-all duration-500 delay-75"></div>
                  <div class="w-1/4 bg-primary/60 rounded-t h-[75%] group-hover:h-[95%] transition-all duration-500 delay-100"></div>
                  <div class="w-1/4 bg-secondary/60 rounded-t h-[90%] group-hover:h-[110%] transition-all duration-500 delay-150"></div>
                </div>
              </div>

              <!-- Feature 2: Micro Lessons -->
              <div class="glass-card rounded-3xl p-6 col-span-1 lg:col-span-2 flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-secondary/15 blur-2xl rounded-full"></div>
                <div>
                  <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl md:text-2xl font-black font-heading text-white">Micro-Lessons Library</h3>
                    <span class="text-xs bg-secondary/25 text-[#c0ff00] px-2.5 py-1 rounded font-bold uppercase tracking-wider">📚 5 Menit</span>
                  </div>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-medium">
                    Koleksi modul video ringkas terfokus durasi 3-7 menit yang langsung mengupas trik penyelesaian rumus cepat dan eliminasi pilihan jawaban.
                  </p>
                </div>
                <div class="flex -space-x-3 mt-4">
                  <span class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-950 flex items-center justify-center text-xs text-slate-300 font-bold">PU</span>
                  <span class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-950 flex items-center justify-center text-xs text-slate-300 font-bold">PK</span>
                  <span class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-950 flex items-center justify-center text-xs text-slate-300 font-bold">PM</span>
                </div>
              </div>

              <!-- Feature 3: AI Tutor -->
              <div class="glass-card rounded-3xl p-6 col-span-1 flex flex-col justify-between group">
                <div class="w-12 h-12 rounded-xl bg-accent/10 border border-accent/20 flex items-center justify-center text-[#c0ff00] text-lg">
                  🤖
                </div>
                <div>
                  <h3 class="text-lg md:text-xl font-black text-white font-heading mb-1">AI Companion 24/7</h3>
                  <p class="text-sm text-slate-300 font-medium leading-relaxed">Asisten interaktif bongkar kerumitan soal jam berapa pun.</p>
                </div>
              </div>

              <!-- Feature 4: Virtual Study Room -->
              <div class="glass-card rounded-3xl p-6 col-span-1 flex flex-col justify-between group">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-[#c0ff00] text-lg">
                  ⏳
                </div>
                <div>
                  <h3 class="text-lg md:text-xl font-black text-white font-heading mb-1">Pomodoro Lofi Room</h3>
                  <p class="text-sm text-slate-300 font-medium leading-relaxed">Ruang belajar fokus bersama iringan musik lofi ambient.</p>
                </div>
              </div>

              <!-- Feature 5: Parent Portal -->
              <div class="glass-card rounded-3xl p-8 col-span-1 md:col-span-3 lg:col-span-4 bg-gradient-to-r from-slate-950 to-slate-900 border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-2">
                  <h3 class="text-2xl md:text-3xl font-black font-heading text-white flex flex-wrap items-center gap-3">
                    Auto Report WhatsApp Orang Tua
                    <span class="bg-emerald-500/15 text-[#c0ff00] px-2.5 py-1 rounded text-[10px] font-bold border border-emerald-500/20 uppercase tracking-widest">WhatsApp</span>
                  </h3>
                  <p class="text-sm md:text-base text-slate-300 max-w-2xl font-medium leading-relaxed">
                    Hilangkan kekhawatiran orang tua secara transparan. Sistem otomatis meringkas total waktu belajar, perolehan skor target PTN, dan mengirimkannya langsung ke WhatsApp orang tua.
                  </p>
                </div>
                <button class="shrink-0 bg-white text-slate-950 hover:bg-slate-200 px-6 py-3.5 rounded-xl font-bold text-xs md:text-sm transition-all flex items-center gap-2" @click="currentTab = 'parent'">
                  Uji Coba Portal <i class="ph-bold ph-whatsapp-logo text-base"></i>
                </button>
              </div>

            </div>
          </div>

          <!-- 9. Dynamic Section: FILTERABLE TESTIMONIALS -->
          <div id="testimonials" class="space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2 reveal">
              <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">FEEDBACK PENGGUNA AWAL</span>
              <h2 class="text-4xl md:text-6xl font-black font-heading text-white">Apa Kata Pengguna Beta EduPath</h2>
              <p class="text-sm text-white/50">Kesan pertama dari teman-teman yang mencoba EduPath lebih awal:</p>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap justify-center gap-2 max-w-2xl mx-auto">
              <button
                v-for="flt in [
                  { id: 'all', name: 'Semua Pengguna (3)' },
                  { id: 'health', name: '🩺 Saintek' },
                  { id: 'engineering', name: '💻 Teknik & MIPA' },
                  { id: 'soshum', name: '💼 Soshum' }
                ]"
                :key="flt.id"
                :class="['px-4 py-2 rounded-full text-xs font-black transition-all border',
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto items-stretch">
              <div
                v-for="(t, idx) in filteredTestimonials"
                :key="t.name"
                class="glass-card rounded-[2rem] p-8 relative overflow-hidden group flex flex-col justify-between animate-fade-in"
              >
                <div class="absolute -right-6 -top-6 text-9xl text-white/5 font-black font-serif italic">“</div>
                
                <div class="space-y-4 relative z-10">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-2xl border border-white/10">
                        {{ t.avatar }}
                      </div>
                      <div>
                        <h4 class="font-black text-white text-base">{{ t.name }}</h4>
                        <p class="font-bold text-[#c0ff00] text-xs">{{ t.school }}</p>
                      </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black bg-white/10 text-white border border-white/20">
                      {{ t.badge }}
                    </span>
                  </div>

                  <!-- Target indicator -->
                  <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-black/60 border border-white/10 text-xs font-mono">
                    <span class="text-white/40">Target: <strong>{{ t.target }}</strong></span>
                    <span class="text-[#c0ff00]">·</span>
                    <span class="text-[#c0ff00] font-bold">{{ t.badge }}</span>
                  </div>

                  <p class="text-slate-200 font-medium text-sm md:text-base leading-relaxed">
                    "{{ t.text }}"
                  </p>
                </div>

                <div class="pt-4 mt-4 border-t border-white/5 flex justify-between items-center text-[11px] text-white/40 font-mono">
                  <span>Pengguna Beta EduPath</span>
                  <span class="text-white/30">Kesan Awal</span>
                </div>
              </div>
            </div>
            <p class="text-center text-[11px] text-white/40 italic pt-3 max-w-2xl mx-auto">
              *Catatan: Ini adalah kesan awal dari pengguna yang mencoba EduPath pada tahap beta. EduPath saat ini masih dalam pengembangan aktif dan belum memiliki data kelulusan resmi.
            </p>
          </div>

          <!-- 10. Demonstration / Product Preview -->
          <div id="preview" class="space-y-12 scroll-mt-24 relative overflow-visible">
            <div class="text-center max-w-xl mx-auto space-y-2 reveal">
              <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">TAMPILAN INTERFACE</span>
              <h2 class="text-4xl md:text-6xl font-black font-heading text-white">Eksplorasi Dashboard</h2>
            </div>

            <div class="relative max-w-4xl mx-auto">
              <div class="absolute -inset-1 bg-gradient-to-r from-primary to-secondary rounded-2xl blur opacity-25 animate-pulse"></div>
              
              <div class="absolute -right-6 top-1/4 bg-slate-950/90 border border-slate-805 p-3 rounded-xl shadow-2xl hidden md:flex items-center gap-3 z-20 animate-[bounce_3s_infinite]">
                <span class="text-lg">💎</span>
                <div>
                  <div class="text-[10px] text-slate-550 font-bold uppercase">Skor Tryout</div>
                  <div class="text-xs md:text-sm font-bold text-white">780 Poin</div>
                </div>
              </div>
              <div class="absolute -left-6 bottom-1/4 bg-slate-955/90 border border-slate-800 p-3 rounded-xl shadow-2xl hidden md:flex items-center gap-3 z-20 animate-[bounce_4s_infinite]">
                <span class="text-lg">🎯</span>
                <div>
                  <div class="text-[10px] text-slate-550 font-bold uppercase">Rekomendasi AI</div>
                  <div class="text-xs md:text-sm font-bold text-white">Fokus: Geometri</div>
                </div>
              </div>

              <div class="relative bg-slate-955 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden aspect-video">
                <div class="h-8 bg-slate-900 border-b border-slate-850 flex items-center px-4 gap-2">
                  <div class="w-2.5 h-2.5 rounded-full bg-red-500/80"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/80"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-green-500/80"></div>
                  <div class="ml-4 text-[9px] text-slate-500 font-mono uppercase tracking-widest">EDUPATH.AI/DASHBOARD</div>
                </div>
                
                <div class="p-6 h-full flex flex-col justify-between relative bg-cover bg-center" style="background-image: linear-gradient(rgba(3,7,18,0.9), rgba(3,7,18,0.95)), url('https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=600&auto=format&fit=crop&q=60');">
                  <div class="flex justify-between items-start">
                    <div>
                      <span class="text-[9px] text-[#c0ff00] font-bold uppercase tracking-wider block">PROGRES BELAJAR</span>
                      <h4 class="text-base font-bold text-white">Peluang Kelulusan Kedokteran UI</h4>
                    </div>
                    <span class="text-xs font-bold text-[#c0ff00] bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md">82.4% Sukses</span>
                  </div>

                  <div class="space-y-3">
                    <div class="w-full bg-slate-900 rounded-full h-3 border border-slate-800 overflow-hidden">
                      <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full" style="width: 82%;"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 font-medium">
                      <span>Estimasi Skor: 648 / 720 Target</span>
                      <span>Sisa 72 Poin Lagi</span>
                    </div>
                  </div>

                  <div class="flex items-center gap-3 bg-slate-900/60 border border-slate-800 p-3 rounded-xl">
                    <span class="text-lg">🤖</span>
                    <p class="text-xs text-slate-350 font-light leading-relaxed"><strong>AI Tutor:</strong> "Siswa Mandiri telah meningkatkan kemampuan Aljabar Dasar sebesar 15% minggu ini. Pertahankan streak Anda!"</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 11. Dynamic Section: PRICING WITH BILLING TOGGLE -->
          <div id="pricing" class="space-y-12 scroll-mt-24">
            <div class="text-center max-w-2xl mx-auto space-y-4 reveal">
              <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-black uppercase tracking-widest">
                💎 INVESTASI LEHER KE ATAS • BEBAS RISIKO
              </span>
              <h2 class="text-4xl md:text-6xl font-black font-heading text-white leading-tight">
                Investasi Terbaik <br />
                <span style="color: #c0ff00;">Menembus PTN Impian</span>
              </h2>
              <p class="text-sm md:text-base text-white/60 leading-relaxed font-medium">
                Bimbel konvensional memungut Rp 15–30 juta untuk metode satu buku yang sama bagi semua murid.<br />
                Di EduPath, Anda berinvestasi pada kecerdasan AI adaptif yang melatih langsung titik lemah spesifik Anda.
              </p>
              
              <!-- Interactive Billing Cycle Toggle with 40% DISC Badge -->
              <div class="pt-2">
                <div class="inline-flex items-center p-1.5 rounded-full bg-white/5 border border-white/15 backdrop-blur-md shadow-xl">
                  <button
                    :class="['px-5 py-2.5 rounded-full text-xs font-black transition-all', !isAnnualBilling ? 'bg-white text-black shadow-lg' : 'text-white/60 hover:text-white']"
                    @click="isAnnualBilling = false"
                  >
                    Bayar Bulanan
                  </button>
                  <button
                    :class="['px-6 py-2.5 rounded-full text-xs font-black transition-all flex items-center gap-2', isAnnualBilling ? 'bg-[#c0ff00] text-black shadow-[0_0_25px_rgba(192,255,0,0.5)]' : 'text-white/60 hover:text-white']"
                    @click="isAnnualBilling = true"
                  >
                    <span>Bayar Tahunan</span>
                    <span class="bg-black text-[#c0ff00] text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">HEMAT 40% OFF 🔥</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
              
              <!-- Plan 1: Mandiri -->
              <div class="glass-card rounded-3xl p-8 flex flex-col justify-between border border-white/10 hover:border-white/20 bg-white/[0.02] hover:bg-white/[0.04] transition-all duration-300">
                <div class="space-y-5">
                  <div class="flex justify-between items-center">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block">STARTER PASS</span>
                    <span class="text-[10px] px-2.5 py-1 rounded-full bg-white/10 text-white/80 font-bold">Mandiri &amp; Disiplin</span>
                  </div>
                  
                  <div>
                    <h3 class="text-2xl font-black text-white font-heading">Paket Mandiri</h3>
                    <p class="text-xs text-white/50 mt-1">Akses kurasi materi &amp; bank soal IRT untuk pejuang yang disiplin belajar sendiri.</p>
                  </div>

                  <!-- Price Framing -->
                  <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1">
                    <div v-if="isAnnualBilling" class="flex items-center gap-2">
                      <span class="text-xs text-white/40 line-through">Rp 180.000</span>
                      <span class="text-[10px] font-black text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">Hemat 40%</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                      <span class="text-4xl sm:text-5xl font-black text-white font-heading">{{ isAnnualBilling ? '108rb' : '180rb' }}</span>
                      <span class="text-white/50 font-bold text-xs">/bulan</span>
                    </div>
                    <div class="text-[11px] text-[#c0ff00] font-semibold">
                      {{ isAnnualBilling ? 'Hanya ~Rp 3.600/hari • Ditagih Rp 1.296.000/thn' : 'Fleksibel, batalkan kapan saja' }}
                    </div>
                  </div>

                  <!-- Value Stack -->
                  <ul class="space-y-3 text-xs md:text-sm text-white/80 font-medium pt-2">
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                      <span><strong>500+ Micro-Lessons Adaptif</strong> (TPS &amp; Literasi)</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                      <span><strong>50.000+ Bank Soal HOTS</strong> dengan Standar Skor IRT</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                      <span><strong>5x Tryout Nasional / Bulan</strong> + Pembahasan Lengkap</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                      <span>Radar Deteksi Blind-Spot Belajar Instan</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-check text-[#c0ff00] mt-0.5 shrink-0"></i>
                      <span>Habit Tracker: Weekly Learning Check-in (WLC)</span>
                    </li>
                  </ul>
                </div>

                <div class="pt-8">
                  <button 
                    class="w-full py-3.5 rounded-xl bg-white/10 hover:bg-white hover:text-black text-white font-black text-xs md:text-sm border border-white/15 transition-all shadow-sm active:scale-95" 
                    @click="purchasePlan('Mandiri', 180000)"
                  >
                    Pilih Paket Mandiri
                  </button>
                </div>
              </div>

              <!-- Plan 2: Utama (Featured & High-Converting) -->
              <div class="rounded-3xl p-8 flex flex-col justify-between relative scale-105 z-10 overflow-hidden shadow-2xl transition-all duration-300" style="background: linear-gradient(180deg, #0d1626 0%, #080d16 100%); border: 2px solid #c0ff00; box-shadow: 0 0 50px rgba(192, 255, 0, 0.25);">
                <!-- Most Popular Ribbon -->
                <div class="absolute top-0 right-0 bg-[#c0ff00] text-black text-[10px] font-black px-4 py-1.5 rounded-bl-2xl uppercase tracking-wider shadow-md">
                  👑 PILIHAN UTAMA PEJUANG PTN
                </div>

                <div class="space-y-5">
                  <div class="flex justify-between items-center">
                    <span class="text-[#c0ff00] text-xs font-black uppercase tracking-wider block">THE ACCELERATOR</span>
                  </div>

                  <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-white font-heading">Paket Utama</h3>
                    <p class="text-xs text-white/60 mt-1">Paket all-in-one paling direkomendasikan untuk akselerasi belajar adaptif dan kesiapan SNBT terukur.</p>
                  </div>

                  <!-- Price Framing -->
                  <div class="p-4 rounded-2xl bg-[#c0ff00]/10 border border-[#c0ff00]/30 space-y-1">
                    <div v-if="isAnnualBilling" class="flex items-center gap-2">
                      <span class="text-xs text-white/40 line-through">Rp 450.000</span>
                      <span class="text-[10px] font-black text-black bg-[#c0ff00] px-2 py-0.5 rounded-full uppercase">Hemat 40%</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                      <span class="text-5xl sm:text-6xl font-black font-heading text-[#c0ff00]">{{ isAnnualBilling ? '270rb' : '450rb' }}</span>
                      <span class="text-white/60 font-bold text-xs">/bulan</span>
                    </div>
                    <div class="text-[11px] text-white font-bold">
                      {{ isAnnualBilling ? 'Hanya ~Rp 9.000/hari (Kurang dari harga kopi!) • Ditagih Rp 3.240.000/thn' : 'Investasi bulanan fleksibel tanpa komitmen' }}
                    </div>
                  </div>

                  <!-- Value Stack -->
                  <ul class="space-y-3.5 text-xs md:text-sm text-white font-medium pt-2">
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] text-base shrink-0 font-black">⚡</span>
                      <span><strong>Semua Fitur di Paket Mandiri</strong></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] text-base shrink-0 font-black">⚡</span>
                      <span><strong>AI Tutor Companion 24/7</strong> (Bimbingan logika tanpa batas)</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] text-base shrink-0 font-black">⚡</span>
                      <span><strong>Unlimited Simulasi IRT Adaptif</strong> (Bebas TO tanpa kuota)</span>
                    </li>

                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] text-base shrink-0 font-black">⚡</span>
                      <span><strong>Estimasi Kesiapan &amp; Rasionalisasi Prodi Edukatif</strong></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <span class="text-[#c0ff00] text-base shrink-0 font-black">⚡</span>
                      <span>Laporan Progres Belajar Otomatis via WhatsApp Orang Tua</span>
                    </li>
                  </ul>
                </div>

                <div class="pt-8">
                  <button 
                    class="w-full py-4 rounded-xl text-sm font-black transition-all hover:scale-105 hover:shadow-[0_0_35px_rgba(192,255,0,0.6)] active:scale-95 bg-[#c0ff00] text-black flex items-center justify-center gap-2" 
                    @click="purchasePlan('Pro', 149000)"
                  >
                    <span>Mulai Paket Utama Sekarang</span>
                    <i class="ph-bold ph-arrow-right text-base"></i>
                  </button>
                </div>
              </div>

              <!-- Plan 3: VIP Mentoring -->
              <div class="glass-card rounded-3xl p-8 flex flex-col justify-between border border-purple-500/30 bg-purple-950/[0.08] hover:border-purple-500/50 transition-all duration-300">
                <div class="space-y-5">
                  <div class="flex justify-between items-center">
                    <span class="text-purple-300 text-xs font-bold uppercase tracking-wider block">PRESIDENTIAL PASS</span>
                    <span class="text-[10px] px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30">Kuota: 50 Siswa</span>
                  </div>

                  <div>
                    <h3 class="text-2xl font-black text-white font-heading">Paket VIP</h3>
                    <p class="text-xs text-white/50 mt-1">Pendampingan privat 1-on-1 intensif dengan Master Tutor top PTN untuk pemantapan strategi belajar maksimal.</p>
                  </div>

                  <!-- Price Framing -->
                  <div class="p-4 rounded-2xl bg-purple-900/20 border border-purple-500/30 space-y-1">
                    <div v-if="isAnnualBilling" class="flex items-center gap-2">
                      <span class="text-xs text-white/40 line-through">Rp 1.100.000</span>
                      <span class="text-[10px] font-black text-purple-300 bg-purple-500/20 px-2 py-0.5 rounded-full">Hemat 40%</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                      <span class="text-4xl sm:text-5xl font-black text-white font-heading">{{ isAnnualBilling ? '660rb' : '1,1jt' }}</span>
                      <span class="text-white/50 font-bold text-xs">/bulan</span>
                    </div>
                    <div class="text-[11px] text-purple-300 font-semibold">
                      {{ isAnnualBilling ? 'Hanya ~Rp 22.000/hari • Ditagih Rp 7.920.000/thn' : 'Pendampingan privat eksklusif bulanan' }}
                    </div>
                  </div>

                  <!-- Value Stack -->
                  <ul class="space-y-3 text-xs md:text-sm text-white/80 font-medium pt-2">
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-star text-purple-400 mt-0.5 shrink-0"></i>
                      <span><strong>Semua Fitur Lengkap di Paket Utama</strong></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-star text-purple-400 mt-0.5 shrink-0"></i>
                      <span><strong>1-on-1 Private Mentoring Mingguan</strong> via Zoom (60 Menit)</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-star text-purple-400 mt-0.5 shrink-0"></i>
                      <span><strong>Grup WhatsApp VIP Langsung bareng Mentor Senior</strong></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-star text-purple-400 mt-0.5 shrink-0"></i>
                      <span>Audit Portofolio Belajar &amp; Siasat Prodi Pilihan</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                      <i class="ph-bold ph-shield-check text-[#c0ff00] mt-0.5 shrink-0 text-base"></i>
                      <span><strong>Garansi 7 Hari Kepuasan Belajar &amp; Akses Penuh Mentor</strong></span>
                    </li>
                  </ul>
                </div>

                <div class="pt-8">
                  <button 
                    class="w-full py-3.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black text-xs md:text-sm transition-all shadow-lg shadow-purple-600/30 active:scale-95" 
                    @click="purchasePlan('Pro Annual', 990000)"
                  >
                    Daftar Kuota VIP Mentoring
                  </button>
                </div>
              </div>

            </div>

            <!-- Risk Reversal & Trust Strip -->
            <div class="max-w-4xl mx-auto pt-6 border-t border-white/10 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
              <button @click="showDisclaimerModal = true" class="flex items-center justify-center gap-2 text-xs font-bold text-white/70 hover:text-white transition-colors cursor-pointer">
                <i class="ph-bold ph-shield-check text-[#c0ff00] text-lg"></i>
                <span>Garansi 7 Hari Kepuasan Belajar</span>
              </button>
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
          <div id="faq" class="space-y-12 max-w-3xl mx-auto scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-[#c0ff00] text-sm font-bold uppercase tracking-widest block">PERTANYAAN UMUM</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Masih Ragu?</h2>
            </div>

            <div class="space-y-2">
              <div v-for="(faq, idx) in faqs" :key="idx" class="border-b border-slate-900 py-5 transition-all">
                <button class="w-full text-left flex justify-between items-center font-bold text-sm md:text-base text-white focus:outline-none py-2" @click="toggleFaq(idx)">
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
          <div id="affiliate-section" class="scroll-mt-24 space-y-12 pb-24">
            <!-- Hero -->
            <section class="glass-panel rounded-3xl p-6 sm:p-12 relative overflow-hidden border border-amber-500/20 text-center space-y-6">
              <div class="absolute -right-20 -top-20 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-yellow-500/15 rounded-full blur-3xl pointer-events-none"></div>
              <div class="max-w-3xl mx-auto space-y-4 relative z-10">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-mono font-bold">
                  <i class="ph-bold ph-lightning"></i> 1 KODE REFERRAL UNTUK 8 PRODUK UNGGULAN
                </div>
                <h2 class="text-3xl sm:text-5xl font-black font-heading text-white tracking-tight leading-tight">
                  Raih Komisi <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-400">20% Tahun Pertama</span><br class="hidden sm:inline">+ <span class="text-emerald-400">10% Recurring</span> Seumur Hidup
                </h2>
                <p class="text-white/70 text-sm sm:text-base leading-relaxed">
                  Dapatkan penghasilan pasif berkelanjutan dari setiap langganan software bisnis di ekosistem Elyana. Tracking otomatis, transparan, pencairan terjadwal, dan dipotong PPh resmi sesuai regulasi pajak.
                </p>
                <div class="flex flex-wrap justify-center gap-3 pt-2">
                  <button @click="showAffiliateRegisterModal = true" class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-300 hover:to-yellow-400 text-black font-black text-sm transition-all shadow-[0_0_35px_rgba(245,158,11,0.5)] flex items-center gap-2">
                    <i class="ph-bold ph-rocket"></i> Daftar Jadi Mitra (Gratis)
                  </button>
                </div>
              </div>
            </section>

            <!-- Products Grid -->
            <section class="space-y-4">
              <div class="text-center space-y-1">
                <span class="text-xs font-bold text-amber-400 uppercase">// 8 PRODUK SIAP DIJUAL</span>
                <h2 class="text-2xl font-black font-heading text-white">Solusi Digital Bernilai Tinggi yang Dibutuhkan Pasar</h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                <div class="p-5 rounded-2xl space-y-3 bg-white/5 border border-white/10">
                  <div class="flex justify-between items-start">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                      <i class="ph-bold ph-storefront"></i>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">20% Thn-1 | 10% Rec</span>
                  </div>
                  <div>
                    <h3 class="font-bold text-white text-sm">ROS Resto Platform</h3>
                    <p class="text-[11px] text-white/50 mt-1">Platform POS, Kitchen Display System (KDS), dan manajemen inventori restoran modern.</p>
                  </div>
                </div>
                <div class="p-5 rounded-2xl space-y-3 bg-white/5 border border-white/10">
                  <div class="flex justify-between items-start">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                      <i class="ph-bold ph-graduation-cap"></i>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">20% Thn-1 | 10% Rec</span>
                  </div>
                  <div>
                    <h3 class="font-bold text-white text-sm">EduPath Learning Platform</h3>
                    <p class="text-[11px] text-white/50 mt-1">Sistem pembelajaran adaptif dengan konten SNBT terukur.</p>
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
                    <h3 class="font-bold text-white text-sm">Elyana Exam Prep</h3>
                    <p class="text-[11px] text-white/50 mt-1">Koleksi soal dan simulasi ujian SNBT yang terkurasi.</p>
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
          <div class="text-center py-20 rounded-3xl p-8 space-y-6 max-w-4xl mx-auto relative overflow-hidden" style="background: #121212; border: 1px solid rgba(255,255,255,0.08);">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(192,255,0,0.15) 0%, transparent 70%);"></div>
            </div>
            
            <h2 class="text-4xl md:text-6xl font-black font-heading text-white tracking-tighter relative z-10 leading-tight">
              Siap Tembus<br /><span style="color: #c0ff00;">PTN Impianmu?</span>
            </h2>
            <p class="text-lg text-white/50 max-w-md mx-auto font-medium relative z-10">Daftar sekarang — assessment diagnostic pertamamu gratis, selamanya.</p>
            

            <div class="flex flex-wrap justify-center gap-4 pt-4 relative z-10">
              <button class="px-10 py-5 rounded-full font-black text-base transition-all hover:scale-105 hover:shadow-[0_0_40px_rgba(192,255,0,0.5)]" style="background: #c0ff00; color: #000;" @click="startLearning">
                Mulai Uji Coba Gratis ⚡
              </button>
              <button class="px-10 py-5 rounded-full font-bold text-base border border-white/20 bg-white/5 hover:bg-white/10 text-white transition-all" @click="currentTab = 'diagnostic'">
                Coba Tryout Dulu
              </button>
            </div>
          </div>

          <!-- 14. Footer -->
          <footer class="border-t border-white/10 pt-12 pb-8 text-sm text-white/50 font-medium">
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
                      <i class="ph-bold ph-whatsapp-logo text-emerald-400 text-sm group-hover:scale-110 transition-transform"></i>
                      <span>WhatsApp CS &amp; Konsultasi</span>
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

              <!-- Col 4: Informasi & Legalitas -->
              <div class="space-y-3">
                <h4 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-1.5">
                  <i class="ph-bold ph-shield-check text-[#c0ff00]"></i>
                  <span>Legalitas &amp; Kebijakan</span>
                </h4>
                <ul class="text-xs text-white/70 space-y-2">
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
                <button @click="showContactModal = true" class="text-[#c0ff00] hover:underline font-bold">Detail Kontak Resmi</button>
              </div>
            </div>
          </footer>

        </section>

        <!-- TAB: AFFILIATE -->
        <section v-if="currentTab === 'affiliate'" class="animate-fade-in space-y-6">
          <div class="max-w-4xl mx-auto space-y-8">
            <div class="text-center space-y-3">
              <span class="px-3.5 py-1 rounded-full bg-amber-400/10 border border-amber-400/30 text-amber-300 text-xs font-black uppercase tracking-wider inline-flex items-center gap-2">
                <i class="ph-bold ph-hand-coins text-amber-400 text-sm"></i>
                <span>PROGRAM MITRA AFILIASI RESMI</span>
              </span>
              <h2 class="text-3xl md:text-5xl font-black font-heading text-white">Dashboard Afiliasi EduPath</h2>
              <p class="text-sm text-white/70 max-w-xl mx-auto font-medium">
                Bagikan link referral Anda dan dapatkan komisi 20% dari setiap pendaftaran pertama serta 10% recurring setiap perpanjangan.
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="light-mode-card rounded-2xl p-6 text-center bg-slate-900/80 border border-amber-500/30 text-white">
                <span class="text-xs text-white/50 font-bold uppercase">Total Klik Referral</span>
                <span class="block text-4xl font-black font-mono text-amber-300 mt-2">148</span>
                <span class="text-[10px] text-emerald-400 font-bold mt-1 inline-block">+12 hari ini</span>
              </div>
              <div class="light-mode-card rounded-2xl p-6 text-center bg-slate-900/80 border border-amber-500/30 text-white">
                <span class="text-xs text-white/50 font-bold uppercase">Referral Aktif</span>
                <span class="block text-4xl font-black font-mono text-emerald-400 mt-2">24</span>
                <span class="text-[10px] text-white/50 font-bold mt-1 inline-block">18 Akuisisi + 6 Recurring</span>
              </div>
              <div class="light-mode-card rounded-2xl p-6 text-center bg-slate-900/80 border border-amber-500/30 text-white">
                <span class="text-xs text-white/50 font-bold uppercase">Total Komisi (IDR)</span>
                <span class="block text-4xl font-black font-mono text-[#c0ff00] mt-2">Rp 1.480.000</span>
                <span class="text-[10px] text-amber-300 font-bold mt-1 inline-block">Siap Dicairkan</span>
              </div>
            </div>

            <div class="light-mode-card rounded-2xl p-6 md:p-8 space-y-6 bg-slate-900/90 border border-amber-500/30 text-white">
              <h3 class="text-lg font-black font-heading">Link Referral Khusus Anda</h3>
              <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" readonly value="https://edupath.id/ref/STUDENT2026" class="w-full bg-black/50 border border-amber-500/30 rounded-xl px-4 py-3 text-xs text-amber-300 font-mono outline-none" />
                <button @click="copyCompanyAddress" class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-400 text-black font-black text-xs hover:scale-105 active:scale-95 transition-all shrink-0">
                  Salin Link
                </button>
              </div>
              <p class="text-xs text-white/50">Bagikan link ini melalui WhatsApp, Instagram Story, atau Telegram komunitas belajar Anda.</p>
            </div>
          </div>
        </section>

        <!-- TAB 1: DASHBOARD -->
        <section v-if="currentTab === 'dashboard'" class="animate-fade-in space-y-6">

          <!-- ✨ Greeting Banner -->
          <div class="relative overflow-hidden rounded-3xl p-6 md:p-8" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);">
            <!-- Decorative glow -->
            <div class="absolute top-0 right-0 w-72 h-72 rounded-full opacity-30 blur-3xl pointer-events-none" style="background: radial-gradient(circle, #c0ff00 0%, transparent 70%); transform: translate(30%, -30%);"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full opacity-20 blur-2xl pointer-events-none" style="background: radial-gradient(circle, #818cf8 0%, transparent 70%); transform: translate(-30%, 30%);"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
              <!-- Left: Greeting -->
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <span class="text-2xl">👋</span>
                  <p class="text-white/60 font-semibold text-sm">Selamat belajar hari ini</p>
                </div>
                <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">Halo, <span style="color: #c0ff00;">Siswa Mandiri!</span></h2>
                <div class="flex items-center gap-2 pt-1">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-white text-xs font-bold">
                    <i class="ph-bold ph-target text-[#c0ff00]"></i>
                    Target: {{ selectedUniversity.name }}
                  </span>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-xs font-bold text-amber-400">
                    <i class="ph-bold ph-fire"></i>
                    {{ streakCount }} Hari Streak
                  </span>
                </div>
              </div>

              <!-- Right: Quick Stats -->
              <div class="flex gap-3 flex-wrap">
                <div class="flex flex-col items-center justify-center px-5 py-3 rounded-2xl bg-white/5 border border-white/10 min-w-[80px]">
                  <span class="text-2xl font-black font-mono" style="color: #c0ff00;">{{ currentAbilityScore }}</span>
                  <span class="text-[10px] text-white/50 font-bold uppercase tracking-wider mt-0.5">Skor Saat Ini</span>
                </div>
                <div class="flex flex-col items-center justify-center px-5 py-3 rounded-2xl bg-white/5 border border-white/10 min-w-[80px]">
                  <span class="text-2xl font-black font-mono text-emerald-400">{{ selectedUniversity.targetScore }}</span>
                  <span class="text-[10px] text-white/50 font-bold uppercase tracking-wider mt-0.5">Skor Target</span>
                </div>
                <div class="flex flex-col items-center justify-center px-5 py-3 rounded-2xl bg-white/5 border border-white/10 min-w-[80px]">
                  <span class="text-2xl font-black font-mono text-amber-400">{{ coins }}</span>
                  <span class="text-[10px] text-white/50 font-bold uppercase tracking-wider mt-0.5">Koin XP</span>
                </div>
              </div>
            </div>

            <!-- Shortcut Buttons -->
            <div class="relative z-10 flex flex-wrap gap-2 mt-6 pt-5 border-t border-white/10">
              <button @click="currentTab = 'diagnostic'" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#c0ff00]/15 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-bold hover:bg-[#c0ff00]/25 transition-all hover:scale-105 active:scale-95">
                <i class="ph-bold ph-exam"></i>
                Mulai Tryout
              </button>
              <button @click="currentTab = 'learning'" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 border border-white/15 text-white text-xs font-bold hover:bg-white/15 transition-all hover:scale-105 active:scale-95">
                <i class="ph-bold ph-book-open"></i>
                Belajar Materi
              </button>
              <button @click="currentTab = 'practice'" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 border border-white/15 text-white text-xs font-bold hover:bg-white/15 transition-all hover:scale-105 active:scale-95">
                <i class="ph-bold ph-pencil-simple"></i>
                Latihan Soal
              </button>
              <button @click="currentTab = 'projection2027'" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 border border-white/15 text-white text-xs font-bold hover:bg-white/15 transition-all hover:scale-105 active:scale-95">
                <i class="ph-bold ph-trend-up"></i>
                Lihat Proyeksi
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Ability Meter Card -->
            <div class="light-mode-card rounded-2xl p-6 lg:col-span-2 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-black font-heading text-slate-900 mb-4">Estimasi Kemampuan &amp; Gap Nilai</h3>
                <div class="flex items-center justify-around py-6 bg-slate-50 rounded-2xl border border-slate-200">
                  <div class="text-center">
                    <span class="block text-4xl font-black font-heading text-indigo-600 font-mono">{{ currentAbilityScore }}</span>
                    <span class="text-xs text-slate-500 font-bold">Skor Saat Ini</span>
                  </div>
                  <div class="text-slate-300 text-2xl font-bold">➔</div>
                  <div class="text-center">
                    <span class="block text-4xl font-black font-heading text-emerald-600 font-mono">{{ selectedUniversity.targetScore }}</span>
                    <span class="text-xs text-slate-500 font-bold">Target ({{ selectedUniversity.name }})</span>
                  </div>
                </div>
              </div>

              <!-- Progress bar -->
              <div class="mt-6">
                <div class="flex justify-between text-xs font-bold text-slate-700 mb-2">
                  <span>Selisih Gap: <strong class="text-indigo-600 font-bold font-mono">{{ gapScore }} Poin</strong></span>
                  <span class="text-slate-500 font-mono">{{ progressPercentage }}% Tercapai</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
                  <div class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: progressPercentage + '%' }"></div>
                </div>
              </div>
            </div>

            <!-- Daily Mission Card -->
            <div class="light-mode-card rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-black font-heading text-slate-900 mb-2">Misi Hari Ini</h3>
                <p class="text-xs text-slate-500 mb-4 font-medium">Selesaikan misi harian untuk menimbun koin XP tambahan.</p>
                <ul class="space-y-2.5">
                  <li v-for="(m, index) in dailyMissions" :key="index" :class="['flex items-center gap-3 p-3 rounded-xl border transition-all', m.completed ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-700']">
                    <input type="checkbox" v-model="m.completed" class="rounded border-slate-300 bg-white text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" @change="checkMissionReward(m)">
                    <span :class="['text-xs font-semibold flex-grow', { 'line-through opacity-60': m.completed }]">{{ m.title }}</span>
                    <span class="text-xs font-black font-mono shrink-0 text-indigo-600">+{{ m.reward }} XP</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Priority Learning Skills map -->
            <div class="light-mode-card rounded-2xl p-6 lg:col-span-2">
              <h3 class="text-xl font-black font-heading text-slate-900 mb-2">Peta Penguasaan Kompetensi (Skill Map)</h3>
              <p class="text-xs text-slate-500 mb-6 font-medium">Deteksi otomatis kekuatan dan kelemahan siswa secara visual.</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="(skills, category) in skillMap" :key="category" class="bg-slate-50 border border-slate-200 p-5 rounded-2xl">
                  <h4 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-200 pb-2 flex items-center gap-2">
                    <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                    {{ category }}
                  </h4>
                  <div class="space-y-4">
                    <div v-for="skill in skills" :key="skill" class="space-y-1.5">
                      <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-700 font-medium">{{ skill }}</span>
                        <span class="font-mono" :style="{ color: getSkillColor(getSkillMastery(skill)) }">{{ getSkillMastery(skill) }}%</span>
                      </div>
                      <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300" :style="{ width: getSkillMastery(skill) + '%', backgroundColor: getSkillColor(getSkillMastery(skill)) }"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recommendation path -->
            <div class="light-mode-card rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-black font-heading text-slate-900 mb-1">Rekomendasi Jalur AI</h3>
                <p class="text-xs text-slate-500 mb-6 font-medium">Tindakan prioritas untuk menutup gap skor target Anda.</p>
                
                <div class="space-y-5">
                  <div v-for="(rec, i) in learningRecommendations" :key="i" class="flex gap-3.5 relative">
                    <div v-if="i < learningRecommendations.length - 1" class="absolute left-3.5 top-8 bottom-0 w-0.5 bg-slate-200"></div>
                    <div class="w-7 h-7 rounded-full bg-indigo-50 border border-indigo-200 flex items-center justify-center font-bold font-mono text-xs text-indigo-600 shrink-0 z-10">
                      {{ i + 1 }}
                    </div>
                    <div>
                      <h5 class="text-xs font-bold text-slate-800 leading-tight mb-1">{{ rec.title }}</h5>
                      <p class="text-[11px] text-slate-500 font-normal leading-relaxed">{{ rec.desc }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 2: DIAGNOSTIC & TRY OUT -->
        <section v-if="currentTab === 'diagnostic'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 gap-6" :class="diagnosticFocus ? '' : 'lg:grid-cols-3'">
            <div class="light-mode-card rounded-2xl p-6 lg:p-8" :class="diagnosticFocus ? '' : 'lg:col-span-2'">
              <h3 class="text-xl font-black font-heading text-slate-900 mb-2">Diagnostic &amp; Adaptive Simulation Test</h3>
              <p v-if="!diagnosticFocus" class="text-xs text-slate-500 mb-6 font-medium">
                Sistem simulasi ujian dengan Item Response Theory (IRT). Kesulitan soal dinamis sesuai respons kemampuan Anda.
              </p>

              <div v-if="!diagnosticActive && !diagnosticFinished" class="text-center py-12 bg-slate-50 rounded-2xl border border-slate-200">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-2xl mx-auto mb-4 font-black font-mono">
                  IRT
                </div>
                <h4 class="text-lg font-black font-heading text-slate-900 mb-2">Mulai Diagnostic Assessment</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6 leading-relaxed font-medium">
                  Ukur kemampuan Aljabar, Geometri, dan Penalaran Logis awal Anda secara presisi dalam 4 soal terpilih.
                </p>
                <button class="px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition-all" @click="startDiagnostic">
                  Mulai Ujian
                </button>
              </div>

              <!-- Quiz Play state -->
              <div v-else-if="diagnosticActive && !diagnosticFinished" class="space-y-6">
                <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                  <span class="px-3 py-1 bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold text-slate-700">
                    {{ currentDiagQuestion.category }} &raquo; {{ currentDiagQuestion.skill }}
                  </span>
                  <span :class="['px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider', 
                    currentDiagQuestion.difficulty === 'HOTS' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200'
                  ]">
                    {{ currentDiagQuestion.difficulty }}
                  </span>
                </div>

                <div class="space-y-3">
                  <strong class="text-xs text-indigo-600 uppercase font-black tracking-widest">Pertanyaan {{ diagnosticIdx + 1 }} dari {{ diagnosticQuestions.length }}:</strong>
                  <p class="text-lg md:text-xl text-slate-900 font-bold leading-relaxed">{{ currentDiagQuestion.question }}</p>
                </div>

                <div class="grid grid-cols-1 gap-2.5">
                  <button 
                    v-for="(option, idx) in currentDiagQuestion.options" 
                    :key="idx"
                    :class="['w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all outline-none', 
                      selectedDiagAnswer === idx 
                        ? 'bg-indigo-50 border-indigo-500 text-indigo-900 shadow-sm' 
                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-100'
                    ]" 
                    @click="selectedDiagAnswer = idx"
                  >
                    <span class="inline-block w-6 h-6 rounded-lg bg-white border border-slate-300 text-center leading-6 text-xs text-slate-700 font-black mr-2.5">{{ String.fromCharCode(65 + idx) }}</span>
                    {{ option }}
                  </button>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-slate-200">
                  <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors" @click="skipDiagQuestion">
                    Lewati
                  </button>
                  <button class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm" :disabled="selectedDiagAnswer === null" @click="submitDiagAnswer">
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

                <div class="grid grid-cols-3 gap-3">
                  <div class="bg-slate-50 border border-slate-200 p-3.5 rounded-xl text-center">
                    <span class="block text-xl font-black font-mono text-emerald-600">{{ Math.round(diagnosticStats.masteredPct) }}%</span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Skill Dikuasai</span>
                  </div>
                  <div class="bg-slate-50 border border-slate-200 p-3.5 rounded-xl text-center">
                    <span class="block text-xl font-black font-mono text-amber-600">{{ Math.round(diagnosticStats.unmasteredPct) }}%</span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Belum Dikuasai</span>
                  </div>
                  <div class="bg-slate-50 border border-slate-200 p-3.5 rounded-xl text-center">
                    <span class="block text-xl font-black font-mono text-rose-600">{{ Math.round(diagnosticStats.criticalPct) }}%</span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Materi Kritis</span>
                  </div>
                </div>

                <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-950 leading-relaxed">
                  <strong class="text-indigo-900">Rekomendasi AI:</strong> Estimasi skor Anda diperbarui menjadi <strong class="text-indigo-600 font-bold font-mono">{{ currentAbilityScore }}</strong>. Kurikulum disesuaikan otomatis untuk mendalami <em>Penalaran Kuantitatif</em> dan <em>Aljabar</em>.
                </div>

                <div class="text-center">
                  <button class="px-5 py-2.5 bg-slate-100 border border-slate-200 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-800 transition-colors" @click="resetDiagnostic">
                    Ulangi Diagnostic Test
                  </button>
                </div>
              </div>
            </div>

            <!-- IRT Details Card -->
            <div v-if="!diagnosticFocus" class="light-mode-card rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-lg font-black font-heading text-slate-900 mb-4">Kenapa Memilih IRT Model?</h3>
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
        <section v-if="currentTab === 'learning'" class="animate-fade-in space-y-6">
          <!-- Active Subtes Header Banner -->
          <div class="light-mode-card rounded-2xl p-5 flex items-center justify-between border-l-4 border-l-indigo-600">
            <div class="flex items-center gap-3">
              <span class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center font-bold text-indigo-600 text-lg font-mono">UTBK</span>
              <div>
                <span class="text-[10px] text-indigo-600 font-black uppercase tracking-widest block">SUBTES TERPILIH</span>
                <h3 class="text-lg font-black font-heading text-slate-900">{{ selectedSubtes.subtes }}</h3>
              </div>
            </div>
            <p class="text-xs text-slate-500 font-medium hidden md:block max-w-md text-right">{{ selectedSubtes.deskripsi }}</p>
          </div>

          <!-- Description & Bab List -->
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            <!-- Sidebar Bab list -->
            <div class="lg:col-span-1 light-mode-card rounded-2xl p-4 space-y-3">
              <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider px-2">Daftar Bab &amp; Topik:</h4>
              <div class="space-y-2">
                <button 
                  v-for="bab in selectedSubtes.babList" 
                  :key="bab.id"
                  :class="['w-full text-left px-3.5 py-3 rounded-xl border text-xs font-bold transition-all outline-none flex flex-col gap-1', 
                    selectedBab && selectedBab.id === bab.id 
                      ? 'bg-indigo-50 border-indigo-400 text-indigo-900 shadow-sm' 
                      : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-100'
                  ]"
                  @click="selectBab(bab)"
                >
                  <span class="text-slate-900">{{ bab.judul }}</span>
                  <span class="text-[10px] text-indigo-600 font-mono font-semibold">{{ bab.microLesson.duration }}</span>
                </button>
              </div>
            </div>

            <!-- Content Area: Teori Singkat -> Micro Lesson -> Quick Quiz -->
            <div class="lg:col-span-3 space-y-6">
              <div v-if="selectedBab" class="light-mode-card rounded-2xl p-6 lg:p-8 space-y-6">
                <!-- 1. HEADER & TEORI SINGKAT MATERI UTBK -->
                <div class="space-y-4 border-b border-slate-200 pb-6">
                  <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 border border-indigo-200 rounded-md text-xs font-black text-indigo-600 uppercase tracking-wider">
                    TEORI KONSEPTUAL UTBK
                  </div>
                  <h3 class="text-2xl font-black font-heading text-slate-900">{{ selectedBab.judul }}</h3>
                  
                  <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                    <h5 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ringkasan Konsep Dasar:</h5>
                    <p class="text-sm text-slate-700 leading-relaxed font-normal">
                      {{ selectedBab.teoriSingkat }}
                    </p>
                  </div>
                </div>

                <!-- 2. MICRO LESSON (Video / Flashcard & Trik) -->
                <div class="space-y-4 border-b border-slate-200 pb-6">
                  <div class="flex items-center justify-between">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-md text-xs font-black text-emerald-700 uppercase tracking-wider">
                      MICRO LESSON &amp; TRIK CEPAT ({{ selectedBab.microLesson.duration }})
                    </div>
                    <span class="text-xs text-slate-400 font-mono font-bold">{{ selectedBab.microLesson.type }}</span>
                  </div>

                  <h4 class="text-lg font-black text-slate-900">{{ selectedBab.microLesson.title }}</h4>

                  <!-- Micro lesson summary card -->
                  <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl">
                    <span class="text-xs font-bold text-emerald-800 block mb-1">Trik Cepat / Formula Praktis:</span>
                    <p class="text-sm text-slate-800 font-medium leading-relaxed">
                      {{ selectedBab.microLesson.summary }}
                    </p>
                  </div>
                </div>

                <!-- 3. QUICK QUIZ -->
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-2xl space-y-4">
                  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 text-xs font-bold text-amber-800 rounded-md">
                    MINI QUIZ UJI PEMAHAMAN
                  </div>
                  <h5 class="text-base md:text-lg font-bold text-slate-900 leading-relaxed">{{ selectedBab.microLesson.quiz.question }}</h5>
                  
                  <div class="grid grid-cols-1 gap-2.5">
                    <button 
                      v-for="(opt, idx) in selectedBab.microLesson.quiz.options"
                      :key="idx"
                      :class="['w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all outline-none', 
                        selectedLessonQuizAns === idx
                          ? (showLessonQuizFeedback && idx === selectedBab.microLesson.quiz.answer ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'bg-rose-50 border-rose-500 text-rose-900')
                          : (showLessonQuizFeedback && idx === selectedBab.microLesson.quiz.answer ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'bg-white border-slate-200 text-slate-800 hover:border-slate-300')
                      ]"
                      @click="selectLessonQuizOption(idx)"
                    >
                      <span class="inline-block w-6 h-6 rounded-lg bg-slate-100 border border-slate-200 text-center leading-6 text-xs font-black text-slate-600 mr-2.5">{{ String.fromCharCode(65 + idx) }}</span>
                      {{ opt }}
                    </button>
                  </div>

                  <div v-if="showLessonQuizFeedback" class="mt-4 p-3.5 bg-white border border-slate-200 rounded-xl text-sm">
                    <p v-if="selectedLessonQuizAns === selectedBab.microLesson.quiz.answer" class="text-emerald-700 font-bold">
                      ✓ Jawaban Benar! Anda mendapatkan +10 Coins.
                    </p>
                    <p v-else class="text-rose-600 font-bold">
                      ✕ Kurang tepat. Petunjuk: <span class="font-normal text-slate-600">{{ selectedBab.microLesson.quiz.hint }}</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 4: PRACTICE & AI TUTOR -->
        <section v-if="currentTab === 'practice'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 gap-6" :class="showAICompanion ? 'lg:grid-cols-3' : ''">
            <!-- Main Interactive Problem Screen -->
            <div class="light-mode-card rounded-2xl p-6 lg:p-8 flex flex-col justify-between" :class="showAICompanion ? 'lg:col-span-2' : ''">
              <div>
                <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-6">
                  <h3 class="text-xl font-black font-heading text-slate-900">Practice Engine &amp; AI Tutor</h3>
                  <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 text-xs font-bold text-indigo-600 rounded-md uppercase">Latihan Aktif</span>
                </div>

                <div class="space-y-4">
                  <span class="text-xs text-indigo-600 font-black uppercase tracking-widest block">SOAL LATIHAN</span>
                  <p class="text-lg md:text-xl font-bold text-slate-900 leading-relaxed">
                    {{ activePracticeQuestion.question }}
                  </p>

                  <div class="grid grid-cols-1 gap-2.5 pt-3">
                    <button 
                      v-for="(opt, idx) in activePracticeQuestion.options" 
                      :key="idx"
                      :class="['w-full text-left px-4 py-3.5 rounded-xl border text-sm font-semibold transition-all outline-none', 
                        practiceUserAnswer === idx
                          ? (practiceEvaluated && idx === activePracticeQuestion.answer ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'bg-rose-50 border-rose-500 text-rose-900')
                          : (practiceEvaluated && idx === activePracticeQuestion.answer ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-800 hover:border-slate-300 hover:bg-slate-100')
                      ]"
                      @click="practiceUserAnswer = idx"
                    >
                      <span class="inline-block w-6 h-6 rounded-lg bg-white border border-slate-300 text-center leading-6 text-xs font-black text-slate-700 mr-2.5">{{ String.fromCharCode(65 + idx) }}</span>
                      {{ opt }}
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
                  <button class="ml-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm" :disabled="practiceUserAnswer === null" @click="checkPracticeAnswer">
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
                    chat.sender === 'ai' ? 'bg-white text-slate-800 border border-slate-200 self-start shadow-sm' : (chat.sender === 'system' ? 'bg-rose-50 border border-rose-200 text-rose-700 max-w-[95%] mx-auto' : 'bg-indigo-600 text-white self-end ml-auto shadow-sm')
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

        <!-- TAB 5: VIRTUAL STUDY ROOM -->
        <section v-if="currentTab === 'studyroom'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Room Status Card -->
            <div class="light-mode-card rounded-2xl p-6 lg:col-span-2 flex flex-col justify-between">
              <div>
                <div class="flex justify-between items-start border-b border-slate-200 pb-3 mb-6">
                  <div>
                    <h3 class="text-xl font-black font-heading text-slate-900">Virtual Study Room 24/7</h3>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Belajar mandiri bersama pejuang PTN lainnya dengan teknik fokus Pomodoro.</p>
                  </div>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700 rounded-full">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    {{ onlineStudents }} online
                  </span>
                </div>

                <!-- Pomodoro Widget -->
                <div class="py-12 bg-slate-50 rounded-2xl border border-slate-200 text-center space-y-4">
                  <div class="timer-display font-heading font-black text-6xl text-slate-900 tracking-widest font-mono">{{ formattedTime }}</div>
                  <div class="text-xs text-indigo-600 uppercase tracking-widest font-black">
                    {{ isBreak ? 'Waktunya Istirahat' : 'Sesi Fokus Belajar' }}
                  </div>
                  <div class="flex justify-center gap-3 pt-4">
                    <button class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm" @click="toggleTimer">
                      {{ timerActive ? 'Pause' : 'Start Sesi' }}
                    </button>
                    <button class="px-5 py-2.5 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors" @click="resetTimer">
                      Reset
                    </button>
                  </div>
                </div>
              </div>

              <div class="mt-8 border-t border-slate-200 pt-6">
                <label class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-3">Pilihan Musik Lofi Ambience:</label>
                <div class="flex gap-2">
                  <button 
                    v-for="track in lofiTracks" 
                    :key="track.id" 
                    :class="['px-4 py-2 border rounded-xl text-xs font-bold transition-all', 
                      currentTrack === track.id 
                        ? 'bg-indigo-50 border-indigo-400 text-indigo-900 shadow-sm' 
                        : 'bg-slate-50 border-slate-200 text-slate-600 hover:border-slate-300'
                    ]"
                    @click="playTrack(track.id)"
                  >
                    {{ track.name }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Accountability leaderboard -->
            <div class="light-mode-card rounded-2xl p-6">
              <h3 class="text-lg font-black font-heading text-slate-900 mb-1">Peringkat Streak Belajar</h3>
              <p class="text-xs text-slate-500 mb-6 font-medium">Konsistensi harian mengalahkan kecepatan menyelesaikan soal.</p>

              <ul class="space-y-2.5">
                <li class="flex items-center gap-3 p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-mono font-black text-xs">1</span>
                  <span class="text-xs font-bold text-slate-900 flex-grow">Anda (Siswa Mandiri)</span>
                  <span class="text-xs text-indigo-600 font-black font-mono">{{ streakCount }} Hari</span>
                </li>
                <li class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-mono font-bold text-xs">2</span>
                  <span class="text-xs font-medium text-slate-700 flex-grow">Budi Santoso</span>
                  <span class="text-xs text-slate-500 font-mono">6 Hari</span>
                </li>
                <li class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-mono font-bold text-xs">3</span>
                  <span class="text-xs font-medium text-slate-700 flex-grow">Citra Lestari</span>
                  <span class="text-xs text-slate-500 font-mono">5 Hari</span>
                </li>
                <li class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-mono font-bold text-xs">4</span>
                  <span class="text-xs font-medium text-slate-700 flex-grow">Dewi Setyowati</span>
                  <span class="text-xs text-slate-500 font-mono">5 Hari</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- TAB 6: PARENT PORTAL -->
        <section v-if="currentTab === 'parent'" class="animate-fade-in space-y-6">
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

            <!-- WhatsApp Report Simulator -->
            <div class="light-mode-card rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-lg font-black font-heading text-slate-900 mb-2">Notifikasi Laporan WA</h3>
                <p class="text-xs text-slate-500 mb-4 font-medium">Laporan ringkas mingguan dikirimkan otomatis ke WhatsApp orang tua.</p>

                <div class="border border-emerald-600 rounded-2xl overflow-hidden bg-[#075e54]">
                  <div class="bg-[#075e54] px-4 py-2 flex items-center gap-2 border-b border-black/10">
                    <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full"></span>
                    <span class="text-xs font-bold text-white">EduPath Parenting Bot</span>
                  </div>
                  <div class="bg-[#ece5dd] p-3 h-44 overflow-y-auto">
                    <div class="bg-white text-slate-900 p-3 rounded-xl text-[10px] leading-relaxed shadow-sm max-w-[85%] border border-black/5">
                      <p class="font-bold text-slate-900 border-b border-slate-200 pb-1 mb-1">Laporan Belajar Mingguan EduPath</p>
                      <p>Ananda belajar selama 4 jam 35 menit.</p>
                      <p>Matematika: +8%</p>
                      <p>Literasi: +4%</p>
                      <p>Penalaran: Butuh latihan lanjutan</p>
                      <p>Target PTN UI: 720</p>
                      <p class="font-bold text-emerald-700 mt-1">Estimasi Kemampuan: {{ currentAbilityScore }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <button class="w-full mt-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm" @click="simulateWASent">
                Kirim Laporan WA
              </button>
            </div>
          </div>
        </section>

        <!-- TAB 7: 2027 AI INTELLIGENCE & PERFORMANCE COCKPIT -->
        <section v-if="currentTab === 'projection2027'" class="animate-fade-in space-y-6">
          
          <!-- Top KPI Strip -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            <!-- KPI 1: Projected IRT Score -->
            <div class="light-mode-card rounded-2xl p-4 md:p-5 relative overflow-hidden group">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] md:text-xs font-black uppercase tracking-widest text-slate-500">Proyeksi Skor IRT</span>
                <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-black font-mono">TOP 4.2%</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-slate-900 font-mono">685</span>
                <span class="text-xs font-bold text-slate-500">/ {{ selectedUniversity.targetScore }} Target</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: 88%;"></div>
              </div>
              <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500 mt-1.5">
                <span>Gap: <strong>35 Poin</strong></span>
                <span class="text-emerald-600 font-bold">+18 minggu ini</span>
              </div>
            </div>

            <!-- KPI 2: HOTS Accuracy -->
            <div class="light-mode-card rounded-2xl p-4 md:p-5 relative overflow-hidden group">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] md:text-xs font-black uppercase tracking-widest text-slate-500">Akurasi Soal HOTS</span>
                <span class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200 text-[10px] font-black font-mono">HIGH-TIER</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-slate-900 font-mono">78.4%</span>
                <span class="text-xs font-bold text-emerald-600 font-mono">+12.3% ↗</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-sky-500 h-full rounded-full transition-all duration-500" style="width: 78.4%;"></div>
              </div>
              <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500 mt-1.5">
                <span>Total 182 Soal</span>
                <span class="text-sky-700 font-bold">142 Benar</span>
              </div>
            </div>

            <!-- KPI 3: Velocity Pace -->
            <div class="light-mode-card rounded-2xl p-4 md:p-5 relative overflow-hidden group">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] md:text-xs font-black uppercase tracking-widest text-slate-500">Velocity Pace</span>
                <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black font-mono">OPTIMAL</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-slate-900 font-mono">42s</span>
                <span class="text-xs font-bold text-slate-500">/ Soal (Batas 55s)</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: 76%;"></div>
              </div>
              <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500 mt-1.5">
                <span>Efisiensi: <strong>+24% Lebih Cepat</strong></span>
              </div>
            </div>

            <!-- KPI 4: Admission Probability -->
            <div class="light-mode-card rounded-2xl p-4 md:p-5 border-emerald-300 relative overflow-hidden group shadow-sm">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] md:text-xs font-black uppercase tracking-widest text-emerald-700">Peluang Lolos PTN</span>
                <span class="px-2 py-0.5 rounded-md bg-emerald-600 text-white text-[10px] font-black">TERJANGKAU</span>
              </div>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-black font-heading text-emerald-600 font-mono">88%</span>
                <span class="text-xs font-bold text-slate-600">{{ selectedUniversity.name }}</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: 88%;"></div>
              </div>
              <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500 mt-1.5">
                <span>Status: <strong>Pertahankan Ritme</strong></span>
              </div>
            </div>
          </div>

          <!-- Main Interactive Cockpit Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Panel (7 cols): AI Hybrid HOTS Question Engine & Simulator -->
            <div class="lg:col-span-7 space-y-4">
              
              <!-- Subtest Pill Navigation Strip -->
              <div class="light-mode-card rounded-2xl p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Simulator Prediksi Soal UTBK 2027</h3>
                    <p class="text-xs text-slate-500 font-medium">Model soal prediksi kombinasi penalaran logika &amp; spasial 3D.</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-[11px] font-mono font-bold text-indigo-600">
                      {{ SIMULATOR_DATABASE[activeSubtest] ? SIMULATOR_DATABASE[activeSubtest].length : 0 }} Soal Aktif
                    </span>
                  </div>
                </div>

                <!-- Subtest Pills -->
                <div class="flex gap-2 overflow-x-auto pb-1 border-t border-slate-200 pt-3">
                  <button 
                    v-for="sub in [
                      { id: 'PU', name: 'Penalaran Umum (PU)' },
                      { id: 'PK', name: 'Pengetahuan Kuantitatif (PK)' },
                      { id: 'LIndo', name: 'Literasi B. Indonesia' },
                      { id: 'LEng', name: 'Literasi B. Inggris' },
                      { id: 'PM', name: 'Penalaran Matematika (PM)' }
                    ]" 
                    :key="sub.id"
                    :class="['px-3 py-2 rounded-xl text-xs font-bold transition-all shrink-0 border text-left', 
                      activeSubtest === sub.id 
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm font-black' 
                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:text-slate-900 hover:bg-slate-100'
                    ]"
                    @click="activeSubtest = sub.id"
                  >
                    {{ sub.name }}
                  </button>
                </div>
              </div>

              <!-- Question Cards Stack -->
              <div class="space-y-4">
                <div 
                  v-for="q in SIMULATOR_DATABASE[activeSubtest]" 
                  :key="q.id" 
                  class="light-mode-card rounded-2xl p-6 space-y-4"
                >
                  <!-- Question Badge Header -->
                  <div class="flex items-center justify-between gap-2 border-b border-slate-200 pb-3">
                    <div class="flex items-center gap-2">
                      <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 font-mono text-xs font-bold">Soal #{{ q.num }}</span>
                      <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-black uppercase tracking-wider">
                        HYBRID HOTS 2027
                      </span>
                    </div>
                    <span class="text-xs font-mono text-slate-500 font-bold">Bobot IRT: <strong class="text-indigo-600">+42 Poin</strong></span>
                  </div>

                  <!-- Question Text -->
                  <p class="text-base md:text-lg font-bold text-slate-900 leading-relaxed">
                    {{ q.question }}
                  </p>

                  <!-- Option Buttons -->
                  <div class="grid grid-cols-1 gap-2.5 pt-1">
                    <button 
                      v-for="(opt, idx) in q.options" 
                      :key="idx"
                      :class="['w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all outline-none flex items-center justify-between', 
                        userAnswersMap[q.id] === idx 
                          ? (idx === q.answer 
                              ? 'bg-emerald-50 border-emerald-500 text-emerald-900 shadow-sm' 
                              : 'bg-rose-50 border-rose-500 text-rose-900 shadow-sm')
                          : 'bg-slate-50 border-slate-200 text-slate-800 hover:border-slate-300 hover:bg-slate-100'
                      ]" 
                      @click="answerAnalisaQuestion(q.id, idx)"
                    >
                      <div class="flex items-center gap-3">
                        <span :class="['w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black font-mono',
                          userAnswersMap[q.id] === idx ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700'
                        ]">
                          {{ String.fromCharCode(65 + idx) }}
                        </span>
                        <span>{{ opt }}</span>
                      </div>
                      <span v-if="userAnswersMap[q.id] === idx" class="text-xs font-mono font-black">
                        {{ idx === q.answer ? '✓ BENAR' : '✕ SALAH' }}
                      </span>
                    </button>
                  </div>

                  <!-- AI Deep Breakdown Card (Instant on Answer) -->
                  <div v-if="userAnswersMap[q.id] !== undefined" class="p-4 rounded-xl bg-slate-900 text-white border border-slate-800 space-y-3 animate-fade-in shadow-lg">
                    <div class="flex items-center justify-between border-b border-white/10 pb-2">
                      <span class="text-xs font-black uppercase tracking-wider text-[#c0ff00] flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#c0ff00]"></span>
                        Bedah Analisis AI
                      </span>
                      <span :class="['text-xs font-bold font-mono', userAnswersMap[q.id] === q.answer ? 'text-emerald-400' : 'text-rose-400']">
                        {{ userAnswersMap[q.id] === q.answer ? '+10 XP Logika' : 'Terdeteksi Blindspot' }}
                      </span>
                    </div>

                    <div class="space-y-2 text-xs leading-relaxed">
                      <div class="text-white/80">
                        <strong class="text-white block font-bold mb-0.5">Analisis Jalur Solusi:</strong>
                        <p class="text-white/70">{{ q.explanation }}</p>
                      </div>

                      <div class="p-2.5 rounded-lg bg-[#c0ff00]/10 border border-[#c0ff00]/25 text-[#c0ff00]">
                        <strong class="block font-bold mb-0.5">Trik Cepat 30 Detik AI:</strong>
                        <p class="text-white/90">{{ q.concept }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Panel (5 cols): Blind Spot Heatmap & Action Plan -->
            <div class="lg:col-span-5 space-y-4">
              
              <!-- 1. Blind Spot Radar Card -->
              <div class="light-mode-card rounded-2xl p-5 space-y-4">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-black text-slate-900 tracking-tight uppercase">Radar Blind-Spot &amp; Kelemahan</h4>
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-600 border border-rose-200">1 Kritis</span>
                </div>

                <div class="space-y-3">
                  <!-- Skill 1: Geometri (Critical) -->
                  <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                      <span class="font-bold text-slate-900">Geometri Bangun Ruang (3D)</span>
                      <span class="text-rose-600 font-mono font-black">35% · Kritis</span>
                    </div>
                    <div class="w-full bg-rose-200/60 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-rose-500 h-full rounded-full" style="width: 35%;"></div>
                    </div>
                    <div class="flex justify-between items-center pt-1 text-[11px]">
                      <span class="text-slate-500">Potensi skor hilang: <strong class="text-rose-600">-35 Poin</strong></span>
                      <button @click="currentTab = 'learning'" class="text-[10px] font-black text-indigo-600 hover:underline">
                        Drill 5 Menit &raquo;
                      </button>
                    </div>
                  </div>

                  <!-- Skill 2: Silogisme (Moderate) -->
                  <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                      <span class="font-bold text-slate-900">Silogisme &amp; Logika Majemuk</span>
                      <span class="text-amber-700 font-mono font-black">60% · Pemantapan</span>
                    </div>
                    <div class="w-full bg-amber-200/60 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-amber-500 h-full rounded-full" style="width: 60%;"></div>
                    </div>
                    <div class="flex justify-between items-center pt-1 text-[11px]">
                      <span class="text-slate-500">Akurasi saat ini: <strong>6/10 Soal</strong></span>
                      <button @click="currentTab = 'practice'" class="text-[10px] font-black text-amber-800 hover:underline">
                        Latihan Variasi &raquo;
                      </button>
                    </div>
                  </div>

                  <!-- Skill 3: Aljabar (Strong) -->
                  <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                      <span class="font-bold text-slate-900">Aljabar &amp; Persamaan Kuadrat</span>
                      <span class="text-emerald-700 font-mono font-black">85% · Kuasai</span>
                    </div>
                    <div class="w-full bg-emerald-200/60 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-emerald-500 h-full rounded-full" style="width: 85%;"></div>
                    </div>
                    <div class="flex justify-between items-center pt-1 text-[11px]">
                      <span class="text-slate-500">Tingkat akurasi konsisten tinggi</span>
                      <span class="text-emerald-600 font-mono font-bold">AMAN</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 2. AI 7-Day Sprint Action Plan -->
              <div class="light-mode-card rounded-2xl p-5 space-y-3">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">AI 7-Day Sprint Plan</h4>
                  <span class="text-[10px] font-bold text-indigo-600 font-mono font-black">+45 Poin Goal</span>
                </div>
                <p class="text-xs text-slate-500 font-medium">Jalur prioritas harian untuk menutup blindspot sebelum tryout minggu ini.</p>

                <div class="space-y-2 pt-1">
                  <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                      <span class="w-5 h-5 rounded-full bg-indigo-600 text-white font-black text-[10px] flex items-center justify-center font-mono">1</span>
                      <span class="text-slate-800 font-medium">Visualisasi Spasial Geometri</span>
                    </div>
                    <span class="text-[10px] font-mono text-indigo-600 font-bold">Hari 1-2</span>
                  </div>

                  <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                      <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 font-black text-[10px] flex items-center justify-center font-mono">2</span>
                      <span class="text-slate-800 font-medium">Eliminasi Jebakan Kuantitatif</span>
                    </div>
                    <span class="text-[10px] font-mono text-slate-500 font-bold">Hari 3-4</span>
                  </div>

                  <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                      <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 font-black text-[10px] flex items-center justify-center font-mono">3</span>
                      <span class="text-slate-800 font-medium">Simulasi Tryout IRT Lengkap</span>
                    </div>
                    <span class="text-[10px] font-mono text-slate-500 font-bold">Hari 5-7</span>
                  </div>
                </div>

                <button @click="currentTab = 'learning'" class="w-full mt-2 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-all shadow-sm">
                  Mulai Sprint Belajar Hari Ini
                </button>
              </div>

              <!-- 3. Subtest Official Structure Reference -->
              <div class="light-mode-card rounded-2xl p-5 space-y-3">
                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Struktur Subtes Resmi UTBK SNBT</h4>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between items-center text-slate-700 pb-1.5 border-b border-slate-100">
                    <span>Tes Potensi Skolastik (TPS)</span>
                    <span class="font-mono text-slate-900 font-bold">70 Soal · 90m</span>
                  </div>
                  <div class="flex justify-between items-center text-slate-700 pb-1.5 border-b border-slate-100">
                    <span>Literasi B. Indonesia</span>
                    <span class="font-mono text-slate-900 font-bold">30 Soal · 45m</span>
                  </div>
                  <div class="flex justify-between items-center text-slate-700 pb-1.5 border-b border-slate-100">
                    <span>Literasi B. Inggris</span>
                    <span class="font-mono text-slate-900 font-bold">20 Soal · 30m</span>
                  </div>
                  <div class="flex justify-between items-center text-slate-700">
                    <span>Penalaran Matematika</span>
                    <span class="font-mono text-slate-900 font-bold">30 Soal · 30m</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </section>
      </div>

    </main>

    <!-- Checkout Modal -->
    <div v-if="checkoutModalData.isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeCheckoutModal"></div>
      <div class="relative bg-slate-900 border border-white/10 rounded-2xl w-full max-w-md p-6 shadow-2xl animate-fade-in text-white">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-black font-heading">Invoice Pembelian</h2>
          <button @click="closeCheckoutModal" class="text-white/50 hover:text-white transition-colors">
            <i class="ph-bold ph-x text-xl"></i>
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
          
          <div class="pt-6">
            <button 
              class="w-full py-3.5 rounded-xl text-sm font-black transition-all hover:scale-105 active:scale-95 bg-[#c0ff00] text-black flex items-center justify-center gap-2"
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
        <!-- Close button -->
        <button @click="showLoginModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">✕</button>
        
        <div v-if="isForgotPasswordMode">
          <div class="text-center space-y-2 mb-6">
            <h3 class="text-xl font-bold font-heading text-white">Lupa Password?</h3>
            <p class="text-xs text-slate-400 font-light">Masukkan email Anda untuk menerima token pemulihan.</p>
          </div>
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
          <div class="text-center space-y-2 mb-6">
            <h3 class="text-xl font-bold font-heading text-white">Reset Password</h3>
            <p class="text-xs text-slate-400 font-light">Masukkan token yang kami kirimkan ke email Anda dan password baru.</p>
          </div>
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
          <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-[#c0ff00] text-xl mx-auto">
              ⚡
            </div>
            <h3 class="text-xl font-bold font-heading text-white">{{ isLoginMode ? 'Masuk ke EduPath.ai' : 'Daftar EduPath.ai' }}</h3>
            <p class="text-xs text-slate-400 font-light">{{ isLoginMode ? 'Selamat datang kembali pejuang PTN!' : 'Mulai perjalanan belajarmu hari ini.' }}</p>
          </div>

          <form @submit.prevent="doAuth" class="space-y-4 mt-6">
            <div v-if="authError" class="px-3 py-2 bg-rose-500/10 border border-rose-500/30 rounded-lg text-rose-400 text-[11px] font-bold text-center">
              {{ authError }}
            </div>

            <div v-if="!isLoginMode" class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Nama Lengkap</label>
              <input v-model="authForm.name" type="text" required placeholder="Cth: Budi Santoso" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>

            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Email</label>
              <input v-model="authForm.email" type="email" required placeholder="demo@edupath.id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>

            <div class="space-y-1">
              <div class="flex justify-between items-center">
                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Password</label>
                <button v-if="isLoginMode" type="button" @click="isForgotPasswordMode = true" class="text-[10px] text-[#c0ff00] hover:underline focus:outline-none">Lupa Password?</button>
              </div>
              <input v-model="authForm.password" type="password" required placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-[#c0ff00]">
            </div>

            <button type="submit" :disabled="authLoading" class="w-full py-3 btn-shimmer text-white rounded-xl text-xs font-bold transition-transform hover:scale-[1.02] mt-2 flex justify-center items-center">
              <span v-if="authLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
              {{ isLoginMode ? 'Masuk Sekarang &raquo;' : 'Daftar Sekarang &raquo;' }}
            </button>
          </form>

          <p class="text-[10px] text-slate-500 text-center font-light mt-4">
            {{ isLoginMode ? 'Belum punya akun?' : 'Sudah punya akun?' }}
            <button @click="isLoginMode = !isLoginMode" class="text-[#c0ff00] font-bold hover:underline ml-1">
              {{ isLoginMode ? 'Daftar di sini' : 'Masuk di sini' }}
            </button>
          </p>
        </div>
      </div>
    </div>

    <!-- Floating Real-Time Activity Notification (Discreet Social Proof) -->
    <div v-if="!isLoggedIn && showLiveActivity" class="fixed bottom-6 left-6 z-40 max-w-xs md:max-w-sm glass-card p-3.5 rounded-2xl border-[#c0ff00]/30 shadow-[0_10px_40px_rgba(0,0,0,0.6)] animate-fade-in flex items-center gap-3">
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
      <button @click="showLiveActivity = false" class="text-white/30 hover:text-white text-xs p-1" title="Tutup">✕</button>
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
          <button @click="showPrivacyModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm transition-colors">✕</button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/70 leading-relaxed">
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">1.</span> Data yang Kami Kumpulkan
            </h4>
            <p>Kami hanya mengumpulkan data yang diperlukan untuk personalisasi pembelajaran dan laporan berkala, meliputi: nama/inisial siswa, tingkat sekolah, target jurusan/PTN impian, riwayat pengerjaan latihan asesmen, serta nomor kontak WhatsApp wali murid yang diberikan secara sukarela.</p>
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
            <p>Bagi siswa di bawah umur, pendaftaran dan pengiriman nomor WhatsApp wali dianggap telah melalui persetujuan orang tua/wali resmi. Untuk integrasi B2B sekolah/bimbel, seluruh data agregat dilindungi oleh perjanjian pemrosesan data (DPA) yang terisolasi.</p>
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
          <button @click="showDisclaimerModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm transition-colors">✕</button>
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

          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-1.5">
            <h4 class="text-white font-black text-sm flex items-center gap-2">
              <span class="text-[#c0ff00]">✦</span> Kebijakan Garansi 7 Hari Kepuasan Belajar
            </h4>
            <p>Kami menjamin kepuasan pengalaman belajar. Jika dalam 7 hari pertama Anda merasa platform EduPath tidak memberikan nilai tambah bagi proses belajar Anda, Anda dapat mengajukan refund 100% tanpa kesulitan.</p>
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
          <button @click="showParentConsentModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm transition-colors">✕</button>
        </div>

        <div class="space-y-4 text-xs md:text-sm text-white/70 leading-relaxed">
          <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10 space-y-2">
            <h4 class="text-white font-black text-sm flex items-center gap-2 text-indigo-300">
              <i class="ph-bold ph-whatsapp-logo text-emerald-400 text-base"></i>
              Laporan Mingguan via WhatsApp
            </h4>
            <p>Orang tua tidak perlu repot menginstal aplikasi tambahan. Ringkasan topik yang sudah dipelajari anak, skor tryout berkala, dan rekomendasi fokus minggu depan akan dikirimkan otomatis ke nomor WhatsApp orang tua.</p>
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
            <p>Penggunaan kontak WhatsApp orang tua semata-mata ditujukan untuk pelaporan progres edukatif di bawah naungan <strong>PT Kreasi Hasanah Indonesia</strong>. Orang tua berhak menghentikan pengiriman laporan kapan pun.</p>
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
          <button @click="showContactModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm transition-colors">✕</button>
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
                  <i class="ph-bold ph-whatsapp-logo text-base"></i> WhatsApp CS
                </span>
                <i class="ph-bold ph-arrow-up-right text-emerald-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform text-xs"></i>
              </div>
              <p class="font-black text-white text-sm">+62 812-3456-7890</p>
              <p class="text-[11px] text-white/50">Respon cepat via pesan WhatsApp</p>
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
          <button @click="showAffiliateRegisterModal = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm transition-colors">✕</button>
        </div>
        <div class="p-6">
          <form @submit.prevent="showAffiliateRegisterModal = false" class="space-y-4">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Nama Lengkap</label>
              <input type="text" placeholder="Masukkan nama" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Email Aktif</label>
              <input type="email" placeholder="nama@email.com" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-white/70">Nomor WhatsApp</label>
              <input type="tel" placeholder="08..." class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-400 transition-colors" required>
            </div>
            <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-300 hover:to-yellow-400 text-black font-black text-sm transition-all shadow-[0_0_20px_rgba(245,158,11,0.3)] flex items-center justify-center gap-2 mt-2">
              <i class="ph-bold ph-paper-plane-right"></i> Kirim Pendaftaran
            </button>
          </form>
        </div>
      </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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
import StudentPotentialPath from './components/StudentPotentialPath.vue';

export default {
  name: 'App',
  components: {
    StudentPotentialPath
  },
  setup() {
    const ambientGlowRef = ref(null);
    const canvasOpacity = ref(0);
    const handleScroll = (e) => { canvasOpacity.value = e.target.scrollTop > 300 ? 0.4 : 0; };
    const mobileSidebarOpen = ref(false);
    const toggleMobileSidebar = () => { mobileSidebarOpen.value = !mobileSidebarOpen.value; };

    // Basic User & Auth States
    const isLoggedIn = ref(false); 
    const showLoginModal = ref(false);
    const showPrivacyModal = ref(false);
    const showDisclaimerModal = ref(false);
    const showParentConsentModal = ref(false);
    const showContactModal = ref(false);
    const showAffiliateRegisterModal = ref(false);
    const sidebarExpanded = ref(false);
    const toolsDropdownOpen = ref(false);

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

    // PTN Chance Simulator
    const targetPtnList = [
      { id: 'fk_ui', name: 'Kedokteran UI', targetScore: 720, cluster: 'health', focus: 'Penalaran Matematika & Geometri' },
      { id: 'if_itb', name: 'Teknik Informatika ITB', targetScore: 715, cluster: 'engineering', focus: 'Pengetahuan Kuantitatif & Logika Induktif' },
      { id: 'akt_ugm', name: 'Aktuaria UGM', targetScore: 710, cluster: 'engineering', focus: 'Aljabar & Statistika Peluang' },
      { id: 'bis_itb', name: 'Bisnis & Manajemen ITB', targetScore: 700, cluster: 'soshum', focus: 'Literasi Bahasa Inggris & Analitik Data' },
      { id: 'huk_ui', name: 'Ilmu Hukum UI', targetScore: 695, cluster: 'soshum', focus: 'Literasi Bahasa Indonesia & Logika Deduktif' },
      { id: 'stei_itb', name: 'STEI ITB', targetScore: 725, cluster: 'engineering', focus: 'Penalaran Matematika Lanjut' },
      { id: 'far_unair', name: 'Farmasi UNAIR', targetScore: 685, cluster: 'health', focus: 'Pengetahuan Kuantitatif & Sains' },
      { id: 'psik_unpad', name: 'Psikologi Unpad', targetScore: 675, cluster: 'soshum', focus: 'Pemahaman Bacaan & PBM' }
    ];

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
        school: 'Siswa Kelas 12 — Pengguna Beta',
        avatar: '📚',
        cluster: 'health',
        target: 'Saintek / Kedokteran',
        badge: 'Pengguna Beta',
        text: 'Baru coba EduPath beberapa minggu dan udah kerasa bedanya. Fitur diagnostik blind spot-nya bikin aku tahu persis topik mana yang harus aku fokus duluan. Belajar jadi lebih terarah!'
      },
      {
        name: 'Dimas P.',
        school: 'Gap Year — Pengguna Beta',
        avatar: '🎯',
        cluster: 'engineering',
        target: 'Teknik Informatika',
        badge: 'Pengguna Beta',
        text: 'Latihan soalnya berbeda dari platform lain. Gak cuma asal kerjain soal, tapi ada analisis otomatis setelah TO. Aku jadi paham kenapa jawaban aku salah, bukan cuma lihat kunci.'
      },
      {
        name: 'Nadia R.',
        school: 'Siswa Kelas 12 — Pengguna Beta',
        avatar: '✨',
        cluster: 'soshum',
        target: 'Soshum / Ekonomi',
        badge: 'Pengguna Beta',
        text: 'Fitur laporan mingguan ke WhatsApp orang tua keren banget. Orang tua jadi bisa pantau perkembangan belajarku tanpa harus nanya-nanya terus. Akhirnya belajar tenang!'
      }
    ];

    const filteredTestimonials = computed(() => {
      if (testimonialFilter.value === 'all') return testimonialsList;
      return testimonialsList.filter(t => t.cluster === testimonialFilter.value);
    });

    // Pricing Billing Toggle
    const isAnnualBilling = ref(true);

    // Skill mastery
    const skillMastery = ref({
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
      'Penalaran Kuantitatif': 45,
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

    const currentTab = ref('home');
    const tabs = ref([
      { id: 'home', label: 'Home (Landing)', icon: 'ph-house' },
      { id: 'dashboard', label: 'Dashboard Belajar', icon: 'ph-gauge' },
      { id: 'diagnostic', label: 'Asesmen Kesiapan', icon: 'ph-brain' },
      { id: 'learning', label: 'Materi & Drill', icon: 'ph-books' },
      { id: 'simulator', label: 'Ujian 2027 Simulasi', icon: 'ph-calculator' },
      { id: 'studyroom', label: 'Pomodoro Room', icon: 'ph-headphones' },
      { id: 'affiliate', label: 'Afiliasi', icon: 'ph-hand-coins' }
    ]);
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
      const tabInfo = tabs.value.find(t => t.id === newTab);
      if (tabInfo) {
        document.title = `${tabInfo.label} | EduPath`;
      } else {
        document.title = 'EduPath - Platform Belajar Adaptif & Persiapan SNBT Terukur';
      }
      initScrollReveal();
    });
    const authForm = ref({ name: '', email: 'demo@edupath.id', password: 'demo123' });
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

    const checkAuth = async () => {
      const token = localStorage.getItem('auth_token');
      if (token) {
        try {
          const res = await api.getProfile();
          currentUser.value = res.user;
          isLoggedIn.value = true;
          // Update global states based on user data
          currentAbilityScore.value = res.user.total_score || 0;
          streakCount.value = res.user.streak || 0;
          coins.value = res.user.coins || 0;
          if (res.user.target_ptn) simTargetPtn.value = res.user.target_ptn;
        } catch (e) {
          console.error("Session expired", e);
          logout();
        }
      }
    };

    onMounted(() => {
      checkAuth();
    });

    const doAuth = async () => {
      authError.value = '';
      authLoading.value = true;
      try {
        let res;
        if (isLoginMode.value) {
          res = await api.login(authForm.value.email, authForm.value.password);
        } else {
          res = await api.register(authForm.value);
        }
        localStorage.setItem('auth_token', res.token);
        currentUser.value = res.user;
        isLoggedIn.value = true;
        showLoginModal.value = false;
        currentTab.value = 'dashboard';
        
        currentAbilityScore.value = res.user.total_score || 0;
        streakCount.value = res.user.streak || 0;
        coins.value = res.user.coins || 0;

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

    const logout = () => {
      localStorage.removeItem('auth_token');
      currentUser.value = null;
      isLoggedIn.value = false;
      currentTab.value = 'home';
      sidebarExpanded.value = false;
      showToast('Anda telah keluar dari akun.');
    };

    const materiSubMenuOpen = ref(false);

    const handleTabClick = (tabId) => {
      if (tabId === 'home') {
        goToHomeTop();
        return;
      }
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun untuk mengakses modul ini!');
        return;
      }
      currentTab.value = tabId;
    };

    const selectSubtesFromSidebar = (subtes) => {
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun untuk mengakses fitur ini!');
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

    const checkoutModalData = ref({
      isOpen: false,
      planName: '',
      amount: 0,
      tax: 0,
      total: 0
    });

    const purchasePlan = (planName, amount) => {
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun untuk membeli paket.');
        return;
      }
      const tax = Math.round(amount * 0.11);
      const total = amount + tax;
      checkoutModalData.value = {
        isOpen: true,
        planName,
        amount,
        tax,
        total
      };
    };

    const confirmCheckout = async () => {
      const data = checkoutModalData.value;
      try {
        const response = await api.checkout(data.planName, data.total);
        if (response.success && response.token) {
          if (window.snap) {
            window.snap.pay(response.token, {
              onSuccess: function(result) {
                showToast(`Pembayaran berhasil! Paket ${data.planName} diaktifkan.`);
                checkoutModalData.value.isOpen = false;
              },
              onPending: function(result) {
                showToast('Menunggu pembayaran Anda diselesaikan.');
                checkoutModalData.value.isOpen = false;
              },
              onError: function(result) {
                showToast('Pembayaran gagal, silakan coba lagi.');
              },
              onClose: function() {
                showToast('Anda menutup pop-up sebelum menyelesaikan pembayaran.');
              }
            });
          } else {
            showToast('Sistem Pembayaran (Midtrans) tidak dimuat dengan benar. Silakan coba lagi nanti.');
          }
        }
      } catch (err) {
        showToast(err.message || 'Gagal menghubungi server pembayaran.');
      }
    };

    const closeCheckoutModal = () => {
      checkoutModalData.value.isOpen = false;
    };

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
        a: "EduPath adalah platform latihan dan akselerasi belajar adaptif. Kami tidak memberikan klaim kelulusan mutlak karena hasil seleksi nasional resmi bergantung pada performa resmi ujian siswa. Namun kami memberikan Garansi 7 Hari Kepuasan Belajar untuk memastikan Anda mendapatkan platform pembelajaran berkualitas tinggi tanpa risiko.", 
        open: false 
      },
      { 
        q: "Bagaimana orang tua memantau perkembangan belajar anak?", 
        a: "Laporan ringkas mengenai topik yang dikuasai, skor latihan berkala, dan rekomendasi fokus mingguan dikirimkan otomatis ke WhatsApp orang tua tanpa perlu menginstal aplikasi tambahan.", 
        open: false 
      },
      { 
        q: "Bagaimana EduPath menjaga privasi data siswa & sekolah?", 
        a: "Data identitas, asal sekolah, dan riwayat asesmen dilindungi dengan enkripsi standar industri dan TIDAK PERNAH diperjualbelikan kepada pihak ketiga. Untuk kerja sama sekolah, kami menyediakan Data Processing Agreement (DPA) khusus.", 
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
    const diagnosticQuestions = ref(DIAGNOSTIC_QUESTIONS);
    const diagnosticIdx = ref(0);
    const selectedDiagAnswer = ref(null);
    const diagnosticScore = ref(0);

    const currentDiagQuestion = computed(() => diagnosticQuestions.value[diagnosticIdx.value]);

    const startDiagnostic = () => {
      diagnosticActive.value = true;
      diagnosticFinished.value = false;
      diagnosticIdx.value = 0;
      selectedDiagAnswer.value = null;
      diagnosticScore.value = 0;
      showToast('Diagnostic Assessment Dimulai!');
    };

    const submitDiagAnswer = () => {
      if (selectedDiagAnswer.value === currentDiagQuestion.value.answer) {
        diagnosticScore.value += 1;
        const skill = currentDiagQuestion.value.skill;
        if (skillMastery.value[skill]) skillMastery.value[skill] = Math.min(skillMastery.value[skill] + 15, 100);
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
      diagnosticFinished.value = true;
      currentAbilityScore.value = Math.min(780, 500 + diagnosticScore.value * 50);
      
      if (isLoggedIn.value) {
        try {
          await api.saveQuizResult('diagnostic', null, diagnosticScore.value * 10, diagnosticScore.value, diagnosticQuestions.value.length, 60, []);
        } catch (e) {
          console.error('Gagal menyimpan hasil:', e);
        }
      }

      showToast('Diagnostic Assessment Selesai! Jalur belajar telah dikalibrasi.');
    };

    const resetDiagnostic = () => {
      diagnosticFinished.value = false;
      diagnosticActive.value = false;
    };

    const materiUtbk = ref(MATERI_UTBK);
    const selectedSubtes = ref(MATERI_UTBK[0]);
    const selectedBab = ref(MATERI_UTBK[0].babList[0]);

    const selectSubtes = (subtes) => {
      selectedSubtes.value = subtes;
      selectedBab.value = subtes.babList[0];
      selectedLessonQuizAns.value = null;
      showLessonQuizFeedback.value = false;
    };

    const selectBab = (bab) => {
      selectedBab.value = bab;
      selectedLessonQuizAns.value = null;
      showLessonQuizFeedback.value = false;
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

    const simulateWASent = () => showToast(' Laporan mingguan sukses dikirimkan ke WhatsApp Orang Tua.');

    const liveActivityList = [
      { icon: '🔥', title: 'EduPath Beta Aktif', desc: 'Platform sedang dalam tahap pengembangan & pengujian', time: 'Live' },
      { icon: '🚀', title: 'Fitur Baru Segera Hadir', desc: 'Daftar sekarang untuk akses early bird & notifikasi peluncuran', time: 'Coming Soon' }
    ];
    const currentActivityIdx = ref(0);
    const showLiveActivity = ref(true);

    let countdownTimer = null;
    let activityTimer = null;

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
      
      
    });

    onUnmounted(() => {
      if (countdownTimer) clearInterval(countdownTimer);
      if (activityTimer) clearInterval(activityTimer);
      
    });

    return {
      checkoutModalData,
      confirmCheckout,
      closeCheckoutModal,
      ambientGlowRef,
      isLoggedIn,
      currentUser,
      showLoginModal,
      isLoginMode,
      authForm,
      authLoading,
      authError,
      doAuth,
      isForgotPasswordMode,
      isResetPasswordMode,
      resetToken,
      newPassword,
      handleForgotPassword,
      handleResetPassword,
      showPrivacyModal,
      showDisclaimerModal,
      showParentConsentModal,
      showContactModal,
      showAffiliateRegisterModal,
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
      selectBab,
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
      simulateWASent,
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
      mobileSidebarOpen,
      toggleMobileSidebar,
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



