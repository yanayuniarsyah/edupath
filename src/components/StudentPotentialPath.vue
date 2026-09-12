<template>
  <div id="spp" class="relative py-16 px-4 md:px-8 max-w-6xl mx-auto scroll-mt-24">
    <!-- Ambient glow behind section -->
    <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-[#c0ff00]/10 blur-[130px] rounded-full pointer-events-none -z-10"></div>

    <!-- Mode Switcher: Siswa (Asesmen) vs Sekolah (Insight BK) -->
    <div class="flex justify-center mb-8">
      <div class="inline-flex p-1.5 rounded-full bg-white/5 border border-white/10 backdrop-blur-lg">
        <button
          @click="activeMode = 'student'"
          :class="[
            'px-5 py-2 rounded-full text-xs md:text-sm font-bold transition-all flex items-center gap-2',
            activeMode === 'student'
              ? 'bg-[#c0ff00] text-black shadow-[0_0_20px_rgba(192,255,0,0.35)]'
              : 'text-white/60 hover:text-white'
          ]"
        >
          <i class="ph-bold ph-student text-base"></i>
          <span>Untuk Siswa (Asesmen Potensi)</span>
        </button>
        <button
          @click="activeMode = 'school'"
          :class="[
            'px-5 py-2 rounded-full text-xs md:text-sm font-bold transition-all flex items-center gap-2',
            activeMode === 'school'
              ? 'bg-white text-black shadow-[0_0_20px_rgba(255,255,255,0.35)]'
              : 'text-white/60 hover:text-white'
          ]"
        >
          <i class="ph-bold ph-buildings text-base"></i>
          <span>Untuk Sekolah & Guru BK</span>
          <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-black border border-indigo-500/30">Insight & QR</span>
        </button>
      </div>
    </div>

    <!-- ==================== MODE 1: UNTUK SISWA ==================== -->
    <div v-if="activeMode === 'student'" class="space-y-10">
      
      <!-- 1. Header & Positioning -->
      <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-[#c0ff00]/40 bg-[#c0ff00]/10 text-[#c0ff00] text-xs font-black uppercase tracking-widest">
          <i class="ph-bold ph-compass"></i>
          <span>Student Potential Path (SPP)</span>
        </div>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black font-heading text-white tracking-tight leading-tight">
          Kenali Potensimu. <br class="hidden sm:inline" />
          <span style="color: #c0ff00;">Pahami Cara Belajarmu.</span> Siapkan Masa Depanmu.
        </h2>
        <p class="text-sm md:text-base text-white/65 max-w-2xl mx-auto font-medium leading-relaxed">
          Bukan sekadar tes bakat biasa. SPP memetakan 4 dimensi berpikir dan kesiapan belajarmu secara mandiri, gratis, dan terarah untuk menembus PTN impian.
        </p>

        <!-- Feature Badges -->
        <div class="flex flex-wrap items-center justify-center gap-2.5 pt-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-white/80 text-xs font-semibold">
            <i class="ph-bold ph-globe text-[#c0ff00]"></i> 100% Online & Mandiri
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-white/80 text-xs font-semibold">
            <i class="ph-bold ph-seal-check text-[#c0ff00]"></i> 100% Gratis
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-white/80 text-xs font-semibold">
            <i class="ph-bold ph-timer text-[#c0ff00]"></i> Estimasi ±15 Menit
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-white/80 text-xs font-semibold">
            <i class="ph-bold ph-graduation-cap text-[#c0ff00]"></i> Khusus SMA/MA/SMK & Gap Year
          </span>
        </div>
      </div>

      <!-- 2. ASSESSMENT ENGINE CONTAINER -->
      <div class="relative rounded-3xl border border-white/15 bg-white/[0.03] backdrop-blur-2xl p-6 md:p-10 shadow-2xl overflow-hidden">
        
        <!-- STEP A: INTRO / PRE-ASSESSMENT -->
        <div v-if="sppStage === 'intro'" class="space-y-8 max-w-3xl mx-auto text-center py-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-left">
            <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="w-10 h-10 rounded-xl bg-[#c0ff00]/15 text-[#c0ff00] flex items-center justify-center text-xl mb-3 font-black">
                <i class="ph-bold ph-brain"></i>
              </div>
              <h4 class="font-black text-sm text-white mb-1">1. Potential</h4>
              <p class="text-xs text-white/50 leading-relaxed">Memetakan pola berpikir, logika, & kekuatan naluriahmu.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center text-xl mb-3 font-black">
                <i class="ph-bold ph-heart"></i>
              </div>
              <h4 class="font-black text-sm text-white mb-1">2. Interest</h4>
              <p class="text-xs text-white/50 leading-relaxed">Menemukan bidang studi & karir yang paling memicu antusiasmemu.</p>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-400 flex items-center justify-center text-xl mb-3 font-black">
                <i class="ph-bold ph-books"></i>
              </div>
              <h4 class="font-black text-sm text-white mb-1">3. Learning</h4>
              <p class="text-xs text-white/50 leading-relaxed">Kecenderungan gaya belajar (konsep, visual, studi kasus).</p>
            </div>

            <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 hover:border-[#c0ff00]/40 transition-all">
              <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center text-xl mb-3 font-black">
                <i class="ph-bold ph-chart-line-up"></i>
              </div>
              <h4 class="font-black text-sm text-white mb-1">4. Growth</h4>
              <p class="text-xs text-white/50 leading-relaxed">Konsistensi, resiliensi (grit), dan determinasi belajarmu.</p>
            </div>
          </div>

          <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <button
              @click="sppStage = 'lead_form'"
              class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#c0ff00] text-black font-black text-sm hover:scale-105 active:scale-95 transition-all shadow-[0_0_25px_rgba(192,255,0,0.45)] flex items-center justify-center gap-2"
            >
              <span>Mulai Asesmen SPP Sekarang (Gratis)</span>
              <i class="ph-bold ph-arrow-right text-lg"></i>
            </button>
            <button
              @click="loadSampleResult"
              class="w-full sm:w-auto px-5 py-3 rounded-full bg-white/5 border border-white/20 text-white/80 font-bold text-xs hover:bg-white/10 transition-all"
            >
              Lihat Contoh Profil Hasil
            </button>
          </div>
          <p class="text-[11px] text-white/40 italic">
            *SPP merupakan alat eksplorasi diri untuk membantu siswa mengenali kecenderungan minat dan pola belajar. SPP bukan tes IQ, diagnosis psikologis, tes psikologi klinis, penentu jurusan, atau prediksi kelulusan perguruan tinggi.
          </p>
        </div>

        <!-- STEP A.1: LEAD CAPTURE FORM (DATA SISWA & ASAL SEKOLAH) -->
        <div v-else-if="sppStage === 'lead_form'" class="max-w-lg mx-auto space-y-6 animate-fade-in py-2">
          <div class="text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#c0ff00]/10 border border-[#c0ff00]/30 text-[#c0ff00] text-xs font-black uppercase">
              <i class="ph-bold ph-identification-card"></i> Identitas Peserta
            </span>
            <h3 class="text-2xl font-black text-white">Sebelum Memulai Asesmen</h3>
            <p class="text-xs text-white/60">
              Data ini digunakan untuk mempersonalisasi Digital Potential Card dan menghubungkan hasilmu dengan analitik sekolahmu.
            </p>
          </div>

          <form @submit.prevent="proceedToQuestions" class="space-y-4 text-left">
            <div>
              <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                Nama Lengkap / Panggilan <span class="text-[#c0ff00]">*</span>
              </label>
              <input
                v-model="studentData.name"
                type="text"
                required
                placeholder="Contoh: Muhammad Farhan"
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/15 text-white placeholder-white/30 text-sm focus:border-[#c0ff00] focus:outline-none transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                Asal Sekolah / Madrasah <span class="text-[#c0ff00]">*</span>
              </label>
              <input
                v-model="studentData.school"
                type="text"
                required
                placeholder="Contoh: SMAN 1 Jakarta / MAN 2 Bandung"
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/15 text-white placeholder-white/30 text-sm focus:border-[#c0ff00] focus:outline-none transition-all"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                  Target Kampus PTN <span class="text-[#c0ff00]">*</span>
                </label>
                <input
                  v-model="studentData.targetUniv"
                  type="text"
                  required
                  placeholder="Contoh: ITB / UI / UGM"
                  class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/15 text-white placeholder-white/30 text-sm focus:border-[#c0ff00] focus:outline-none transition-all"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                  Jurusan Impian <span class="text-[#c0ff00]">*</span>
                </label>
                <input
                  v-model="studentData.targetMajor"
                  type="text"
                  required
                  placeholder="Contoh: Teknik Informatika"
                  class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/15 text-white placeholder-white/30 text-sm focus:border-[#c0ff00] focus:outline-none transition-all"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                Nomor WhatsApp <span class="text-white/40 font-normal">(Opsional — untuk kirim backup hasil &amp; laporan orang tua)</span>
              </label>
              <input
                v-model="studentData.whatsapp"
                type="tel"
                placeholder="0812xxxxxxx"
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/15 text-white placeholder-white/30 text-sm focus:border-[#c0ff00] focus:outline-none transition-all"
              />
            </div>

            <!-- Privacy & Consent Notice & Checkbox (UU PDP Compliance) -->
            <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/10 space-y-2.5 text-[11px] text-white/70 leading-relaxed">
              <div class="flex items-start gap-2">
                <i class="ph-bold ph-shield-check text-[#c0ff00] text-sm shrink-0 mt-0.5"></i>
                <span>
                  <strong>Jaminan Privasi &amp; UU PDP:</strong> Data peserta diproses secara aman untuk kebutuhan pemetaan potensi edukatif dan tidak pernah diperjualbelikan kepada pihak ketiga.
                </span>
              </div>
              <label class="flex items-start gap-2.5 text-xs text-white/80 cursor-pointer pt-1 border-t border-white/10">
                <input type="checkbox" required class="rounded border-white/20 bg-white/10 text-[#c0ff00] focus:ring-[#c0ff00] w-4 h-4 mt-0.5 cursor-pointer shrink-0">
                <span>Saya telah memperoleh izin orang tua/wali untuk mengisi data dan menyetujui pemrosesan data edukatif EduPath.</span>
              </label>
            </div>

            <div class="pt-2 flex items-center justify-between gap-3">
              <button
                type="button"
                @click="sppStage = 'intro'"
                class="px-5 py-2.5 rounded-xl text-xs font-bold text-white/60 hover:text-white"
              >
                Kembali
              </button>
              <button
                type="submit"
                class="px-7 py-3 rounded-full bg-[#c0ff00] text-black font-black text-xs md:text-sm hover:scale-105 active:scale-95 transition-all shadow-[0_0_20px_rgba(192,255,0,0.3)] flex items-center gap-2"
              >
                <span>Masuk ke Soal Asesmen</span>
                <i class="ph-bold ph-arrow-right text-base"></i>
              </button>
            </div>
          </form>
        </div>

        <!-- STEP B: INTERACTIVE QUESTION FLOW -->
        <div v-else-if="sppStage === 'questions'" class="max-w-2xl mx-auto space-y-8 animate-fade-in">
          
          <!-- Progress Bar & Dimension Badge -->
          <div class="space-y-3">
            <div class="flex items-center justify-between text-xs font-bold text-white/60">
              <span class="flex items-center gap-1.5 text-[#c0ff00]">
                <i class="ph-bold ph-sparkle"></i>
                Dimensi: {{ currentQuestion.dimension }}
              </span>
              <span>Pertanyaan {{ currentQuestionIdx + 1 }} dari {{ questions.length }}</span>
            </div>
            <div class="w-full h-2 rounded-full bg-white/10 overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-[#c0ff00] to-emerald-400 transition-all duration-300 rounded-full"
                :style="{ width: `${((currentQuestionIdx + 1) / questions.length) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- Scenario Card -->
          <div class="p-6 md:p-8 rounded-2xl bg-white/[0.05] border border-white/10 space-y-4">
            <span class="inline-block text-[11px] font-mono uppercase tracking-widest text-white/40">Skenario Interaktif</span>
            <h3 class="text-xl md:text-2xl font-black text-white leading-snug">
              {{ currentQuestion.question }}
            </h3>
            <p v-if="currentQuestion.context" class="text-xs md:text-sm text-white/60 leading-relaxed">
              {{ currentQuestion.context }}
            </p>
          </div>

          <!-- 1-5 Likert Scale Options -->
          <div class="space-y-3">
            <button
              v-for="value in [1, 2, 3, 4, 5]"
              :key="value"
              @click="selectOption(value)"
              :class="[
                'w-full text-left p-4 md:p-5 rounded-2xl border transition-all flex items-center justify-between group',
                selectedAnswers[currentQuestionIdx] === value
                  ? 'bg-[#c0ff00]/15 border-[#c0ff00] shadow-[0_0_20px_rgba(192,255,0,0.15)] text-white'
                  : 'bg-white/[0.02] border-white/10 hover:border-white/30 hover:bg-white/[0.05] text-white/80'
              ]"
            >
              <div class="flex items-center gap-4">
                <div
                  :class="[
                    'w-8 h-8 shrink-0 rounded-full border flex items-center justify-center font-black text-sm transition-colors',
                    selectedAnswers[currentQuestionIdx] === value
                      ? 'bg-[#c0ff00] text-black border-[#c0ff00]'
                      : 'border-white/30 text-white/60 group-hover:border-white group-hover:text-white'
                  ]"
                >
                  {{ value }}
                </div>
                <div class="font-bold text-sm md:text-base text-white group-hover:text-[#c0ff00] transition-colors">
                  {{ 
                    value === 1 ? (currentQuestion.dimension === 'Interest' ? 'Sangat Tidak Suka' : 'Sangat Tidak Setuju') :
                    value === 2 ? (currentQuestion.dimension === 'Interest' ? 'Tidak Suka' : 'Tidak Setuju') :
                    value === 3 ? 'Netral / Ragu-ragu' :
                    value === 4 ? (currentQuestion.dimension === 'Interest' ? 'Suka' : 'Setuju') :
                    (currentQuestion.dimension === 'Interest' ? 'Sangat Suka' : 'Sangat Setuju')
                  }}
                </div>
              </div>
            </button>
          </div>

          <!-- Navigation buttons -->
          <div class="flex items-center justify-between pt-4">
            <button
              v-if="currentQuestionIdx > 0"
              @click="currentQuestionIdx--"
              class="px-5 py-2 rounded-xl text-xs font-bold text-white/60 hover:text-white flex items-center gap-1.5 transition-colors"
            >
              <i class="ph-bold ph-arrow-left"></i> Sebelumnya
            </button>
            <div v-else></div>

            <button
              :disabled="!selectedAnswers[currentQuestionIdx] || isSubmitting"
              @click="nextQuestion"
              class="px-6 py-2.5 rounded-full bg-[#c0ff00] disabled:bg-white/10 text-black disabled:text-white/30 font-black text-xs md:text-sm hover:scale-105 active:scale-95 disabled:hover:scale-100 transition-all flex items-center gap-1.5 shadow-[0_0_15px_rgba(192,255,0,0.3)] disabled:shadow-none"
            >
              <span>{{ isSubmitting ? 'Memproses...' : (currentQuestionIdx === questions.length - 1 ? 'Lihat Potential Profile' : 'Lanjut') }}</span>
              <i class="ph-bold ph-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- STEP C: YOUR POTENTIAL PROFILE (THE RESULT) -->
        <div v-else-if="sppStage === 'result'" class="space-y-12 animate-fade-in">
          
          <!-- Top Banner Result with Student Personalization -->
          <div class="text-center space-y-3">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-black uppercase tracking-widest">
              <i class="ph-bold ph-check-circle"></i> Asesmen Selesai • Profil Terverifikasi
            </div>
            
            <div v-if="studentData.name" class="text-xs text-[#c0ff00] font-mono tracking-wider font-bold uppercase">
              Hai {{ studentData.name }} ({{ studentData.school }}) — Target: {{ studentData.targetUniv }} ({{ studentData.targetMajor }})
            </div>

            <h2 class="text-xs font-bold tracking-[0.3em] uppercase text-white/40 font-mono">YOUR POTENTIAL PROFILE</h2>
            <h3 class="text-3xl sm:text-4xl md:text-5xl font-black text-white font-heading">
              Hasil <span class="text-[#c0ff00] underline decoration-[#c0ff00]/40 underline-offset-8">Eksplorasimu</span>
            </h3>
            <p class="text-xs md:text-sm text-white/60 max-w-xl mx-auto">
              Profil eksplorasi berdasarkan sintesis 4 dimensi pemikiran, minat, strategi belajar, dan daya tahan belajarmu.
            </p>
          </div>

          <!-- Grid: Strengths, Growth, Gambaran Learning -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Kekuatan Relatif -->
            <div class="p-6 rounded-2xl bg-white/[0.04] border border-white/10 space-y-4 hover:border-emerald-400/40 transition-all">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 font-mono flex items-center gap-1.5">
                  <i class="ph-bold ph-lightning text-base"></i> Kekuatan Relatif
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 font-bold">Pilar Utama</span>
              </div>
              <ul class="space-y-3 text-sm">
                <li v-for="(strength, sIdx) in profile.relativeStrength" :key="sIdx" class="flex items-start gap-2.5 text-white/90 font-semibold">
                  <i class="ph-bold ph-check text-emerald-400 mt-1 shrink-0"></i>
                  <span>{{ strength }}</span>
                </li>
              </ul>
            </div>

            <!-- Area Pengembangan + WLC Bridge -->
            <div class="p-6 rounded-2xl bg-white/[0.04] border border-white/10 space-y-4 hover:border-amber-400/40 transition-all">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400 font-mono flex items-center gap-1.5">
                  <i class="ph-bold ph-trend-up text-base"></i> Area Pengembangan
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 font-bold">Ruang Upgrade</span>
              </div>
              <ul class="space-y-3 text-sm">
                <li v-for="(growth, gIdx) in profile.developmentArea" :key="gIdx" class="flex items-start gap-2.5 text-white/90 font-semibold">
                  <i class="ph-bold ph-arrow-elbow-right-up text-amber-400 mt-1 shrink-0"></i>
                  <span>{{ growth }}</span>
                </li>
              </ul>

              <!-- WLC Concrete Bridge Hook -->
              <div class="pt-2 border-t border-white/10">
                <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-500/30 space-y-1.5">
                  <div class="flex items-center gap-1.5 text-indigo-300 font-bold text-xs">
                    <i class="ph-bold ph-calendar-check"></i>
                    <span>Solusi Habit: WLC Sync</span>
                  </div>
                  <p class="text-[11px] text-white/60 leading-relaxed">
                    Kunci sukses menembus target {{ studentData.targetUniv || 'PTN Impian' }} bukan belajar sistem kebut semalam, melainkan konsistensi mingguan via <strong>Weekly Learning Check-in (WLC)</strong>.
                  </p>
                </div>
              </div>
            </div>

            <!-- Gambaran Learning -->
            <div class="p-6 rounded-2xl bg-gradient-to-br from-[#c0ff00]/10 to-transparent border border-[#c0ff00]/30 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-[#c0ff00] font-mono flex items-center gap-1.5">
                  <i class="ph-bold ph-book-open text-base"></i> Gambaran Learning
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-md bg-[#c0ff00]/20 text-[#c0ff00] font-bold">Karakter</span>
              </div>
              <blockquote class="text-xs md:text-sm text-white/90 font-medium italic leading-relaxed border-l-2 border-[#c0ff00] pl-3 py-1">
                "{{ profile.learning }}"
              </blockquote>
            </div>
          </div>

          <!-- Area Eksplorasi (Interest / RIASEC Top 3) -->
          <div class="p-6 md:p-8 rounded-2xl bg-white/[0.03] border border-white/10 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div>
                <h4 class="font-black text-lg md:text-xl text-white">Area Eksplorasi (Interest / RIASEC Top 3)</h4>
                <p class="text-xs text-white/50">Area ini bukan rekomendasi jurusan otomatis. Gunakan sebagai titik awal untuk mencari tahu lebih jauh tentang bidang studi yang menarik buatmu.</p>
              </div>
              <span class="text-xs font-mono text-[#c0ff00] font-bold">Tingkat Keselarasan Potensi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div
                v-for="(field, fIdx) in profile.fields"
                :key="fIdx"
                class="p-4 rounded-xl bg-white/[0.04] border border-white/10 hover:border-white/20 transition-all flex flex-col justify-between gap-3"
              >
                <div>
                  <div class="font-black text-sm text-white">{{ field.name }}</div>
                  <div class="text-[11px] text-white/50 mt-1">{{ field.desc }}</div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-white/5">
                  <div class="flex text-amber-400 text-xs">
                    <span v-for="star in 5" :key="star" :class="star <= field.stars ? 'opacity-100' : 'opacity-20'">★</span>
                  </div>
                  <span class="text-[10px] font-mono text-white/60">{{ field.matchRate }}% Match</span>
                </div>
              </div>
            </div>
          </div>

          <!-- DIGITAL POTENTIAL CARD (SHAREABLE TO IG STORY / WA) -->
          <div class="p-8 rounded-3xl bg-gradient-to-b from-[#0e1626] to-[#080d16] border border-white/20 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#c0ff00]/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
              <!-- Card Visual Preview -->
              <div class="w-full md:w-[320px] shrink-0 bg-gradient-to-br from-slate-900 to-black p-6 rounded-2xl border border-white/25 shadow-2xl space-y-4 relative">
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                  <span class="font-black text-xs tracking-tighter text-white">EduPath<span class="text-[#c0ff00]">.SPP</span></span>
                  <span class="text-[9px] font-mono text-white/40">VERIFIED ID: #{{ studentData.school ? studentData.school.slice(0, 3).toUpperCase() : 'SPP' }}-2027</span>
                </div>
                
                <div class="space-y-1">
                  <div class="text-[10px] uppercase font-mono tracking-widest text-[#c0ff00]">Digital Potential Card</div>
                  <div class="text-xl font-black text-white font-heading">Profil Potensi</div>
                  <div class="text-xs text-white/80 font-bold">{{ studentData.name || 'Siswa Mandiri' }}</div>
                  <div class="text-[10px] text-white/50">{{ studentData.school || 'SMA Negeri Indonesia' }} • Target: {{ studentData.targetUniv || 'PTN Impian' }}</div>
                </div>

                <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-[10px] text-white/70 italic">
                  "{{ profile.learning.slice(0, 85) }}..."
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-white/10 text-[9px] text-white/40 font-mono">
                  <span>Verified by EduPath Engine</span>
                  <span class="text-[#c0ff00]">★ Verified 2027</span>
                </div>
              </div>

              <!-- Share Callout -->
              <div class="space-y-4 flex-grow text-center md:text-left">
                <span class="px-3 py-1 rounded-full bg-[#c0ff00]/15 text-[#c0ff00] text-xs font-black uppercase tracking-wider">
                  🔥 Viral / Shareable Feature
                </span>
                <h3 class="text-2xl md:text-3xl font-black text-white font-heading">
                  Bagikan Digital Potential Card Kamu!
                </h3>
                <p class="text-xs md:text-sm text-white/60 leading-relaxed max-w-lg">
                  Tunjukkan profil potensimu ke teman-teman, orang tua, atau guru BK di <strong>{{ studentData.school || 'sekolahmu' }}</strong>. Ajak mereka eksplorasi bareng dan siapkan strategi masuk kampus favorit!
                </p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 pt-2">
                  <button
                    @click="shareCard"
                    class="px-5 py-2.5 rounded-full bg-white text-black font-black text-xs hover:bg-[#c0ff00] active:scale-95 transition-all flex items-center gap-2 shadow-lg"
                  >
                    <i class="ph-bold ph-share-network text-base"></i>
                    <span>{{ copySuccess ? 'Link Berhasil Disalin!' : 'Bagikan ke WhatsApp / IG' }}</span>
                  </button>
                  <button
                    @click="retakeAssessment"
                    class="px-4 py-2.5 rounded-full bg-white/5 border border-white/15 text-white/70 font-bold text-xs hover:text-white transition-all"
                  >
                    Ulangi Asesmen
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. THE BRIDGE TO EDUPATH (CONTEXTUAL HANDOFF) -->
          <div class="relative p-8 md:p-12 rounded-3xl bg-gradient-to-r from-indigo-950/60 via-black to-slate-900/80 border-2 border-[#c0ff00]/40 text-center space-y-6 shadow-[0_0_50px_rgba(192,255,0,0.15)]">
            
            <!-- Philosophy Hook -->
            <div class="inline-block">
              <span class="text-xs md:text-sm font-mono tracking-widest text-[#c0ff00] uppercase font-black">
                The Real Transition
              </span>
              <p class="text-lg sm:text-xl md:text-2xl font-bold text-white mt-1 italic">
                "Potensi adalah titik awal. Kemampuan dibangun melalui proses."
              </p>
            </div>

            <!-- Hook Question Contextualized -->
            <div class="space-y-3 max-w-2xl mx-auto">
              <h3 class="text-2xl sm:text-3xl md:text-4xl font-black text-white font-heading leading-tight">
                Seberapa Siap Kamu untuk {{ studentData.targetMajor || 'Jurusan Impian' }} di {{ studentData.targetUniv || 'PTN Favoritmu' }}?
              </h3>
              <p class="text-xs md:text-sm text-white/70 leading-relaxed">
                Mengetahui potensi adalah titik awal strategis. Saatnya mengukur kesiapan kemampuan akademikmu menggunakan simulasi model IRT adaptif yang terkalibrasi untuk kebutuhan latihan SNBT.
              </p>
            </div>

            <!-- Ecosystem Visual Flow: Potential -> Habit -> Academic -> University -->
            <div class="py-4">
              <div class="flex flex-wrap items-center justify-center gap-2 md:gap-3 text-xs md:text-sm font-bold">
                <div class="px-3.5 py-1.5 rounded-xl bg-white/10 border border-white/15 text-white flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-[#c0ff00]"></span>
                  <span>1. Potential Discovery (SPP)</span>
                </div>
                <i class="ph-bold ph-arrow-right text-[#c0ff00]"></i>
                <div class="px-3.5 py-1.5 rounded-xl bg-indigo-500/20 border border-indigo-500/40 text-indigo-200 flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                  <span>2. Habit & Growth (WLC)</span>
                </div>
                <i class="ph-bold ph-arrow-right text-[#c0ff00]"></i>
                <div class="px-3.5 py-1.5 rounded-xl bg-[#c0ff00]/20 border border-[#c0ff00]/50 text-[#c0ff00] flex items-center gap-1.5 font-black">
                  <span class="w-2 h-2 rounded-full bg-[#c0ff00] animate-ping"></span>
                  <span>3. Academic Readiness (EduPath)</span>
                </div>
                <i class="ph-bold ph-arrow-right text-[#c0ff00]"></i>
                <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 flex items-center gap-1.5">
                  <i class="ph-bold ph-trophy"></i>
                  <span>4. {{ studentData.targetUniv || 'PTN Impian' }}</span>
                </div>
              </div>
            </div>

            <!-- Big CTA to Academic Readiness Check with Context -->
            <div class="pt-2">
              <button
                @click="handleEduPathCTA"
                class="px-8 md:px-10 py-4 rounded-full bg-[#c0ff00] text-black font-black text-sm md:text-base hover:scale-105 active:scale-95 transition-all duration-300 shadow-[0_0_35px_rgba(192,255,0,0.6)] inline-flex items-center gap-2 group"
              >
                <span>Mulai Persiapan Masuk Perguruan Tinggi</span>
                <i class="ph-bold ph-arrow-right text-xl group-hover:translate-x-1 transition-transform"></i>
              </button>
            </div>
            <p class="text-[11px] text-white/40">
              Langsung terintegrasi ke kalkulator prediksi peluang & tes diagnostik adaptif EduPath.
            </p>
          </div>

        </div>

      </div>

    </div>

    <!-- ==================== MODE 2: UNTUK SEKOLAH & GURU BK ==================== -->
    <div v-else class="space-y-12 animate-fade-in">
      
      <!-- School Proposition Header -->
      <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-indigo-500/40 bg-indigo-500/10 text-indigo-300 text-xs font-black uppercase tracking-widest">
          <i class="ph-bold ph-buildings"></i>
          <span>B2B School Acquisition & BK Partnership</span>
        </div>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black font-heading text-white tracking-tight leading-tight">
          School Potential Insight: <br />
          <span class="text-indigo-400">Petakan Potensi 1 Angkatan</span> Tanpa Repot
        </h2>
        <p class="text-sm md:text-base text-white/65 max-w-2xl mx-auto font-medium leading-relaxed">
          Bantu Guru BK memetakan minat jurusan, pola belajar, dan area pengembangan ratusan siswa dalam satu dashboard analitik agregat yang objektif dan instan.
        </p>
      </div>

      <!-- INTERACTIVE SCHOOL QR CODE & POSTER GENERATOR (NEW!) -->
      <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-br from-indigo-950/50 via-slate-900 to-black p-6 md:p-10 shadow-2xl space-y-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 pb-4 border-b border-white/10">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold uppercase tracking-wider mb-2">
              <i class="ph-bold ph-qr-code"></i> Instant School Portal Generator
            </div>
            <h3 class="text-xl md:text-2xl font-black text-white">Generate QR Code & Link Khusus Sekolah Anda</h3>
            <p class="text-xs text-white/60">Ketik nama sekolah Anda di bawah untuk mendapatkan tautan & QR code khusus siswa angkatan.</p>
          </div>
          <span class="text-xs font-mono text-emerald-400 font-bold bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-full">
            ● Sistem Aktif Siap Pakai
          </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <!-- Form Input Sekolah -->
          <div class="lg:col-span-7 space-y-4 text-left">
            <div>
              <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                Nama Resmi Sekolah / Lembaga
              </label>
              <input
                v-model="partnerSchoolName"
                type="text"
                placeholder="Contoh: SMAN 1 Yogyakarta"
                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/20 text-white placeholder-white/30 text-sm font-semibold focus:border-indigo-400 focus:outline-none transition-all"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                  Koordinator / Guru BK
                </label>
                <input
                  v-model="partnerBkName"
                  type="text"
                  placeholder="Contoh: Ibu Rina, M.Pd"
                  class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/20 text-white placeholder-white/30 text-sm focus:border-indigo-400 focus:outline-none transition-all"
                />
              </div>
              <div>
                <label class="block text-xs font-bold text-white/80 uppercase tracking-wider mb-1.5">
                  Estimasi Jumlah Siswa
                </label>
                <select
                  v-model="partnerStudentCount"
                  class="w-full px-4 py-3 rounded-xl bg-[#090d16] border border-white/20 text-white text-sm focus:border-indigo-400 focus:outline-none cursor-pointer"
                >
                  <option value="100-250 Siswa (3-7 Kelas)">100 - 250 Siswa (3-7 Kelas)</option>
                  <option value="250-500 Siswa (1 Angkatan Penuh)">250 - 500 Siswa (1 Angkatan Penuh)</option>
                  <option value="> 500 Siswa (Multi Angkatan)">> 500 Siswa (Multi Angkatan)</option>
                </select>
              </div>
            </div>

            <!-- Result Link & Copy -->
            <div class="p-3.5 rounded-xl bg-black/40 border border-white/10 space-y-2">
              <div class="text-[11px] font-mono text-white/50">Tautan Langsung Siswa:</div>
              <div class="flex items-center gap-2">
                <input
                  :value="generatedSchoolLink"
                  readonly
                  class="bg-transparent text-xs text-[#c0ff00] font-mono flex-grow outline-none truncate"
                />
                <button
                  @click="copySchoolLink"
                  class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all shrink-0 flex items-center gap-1"
                >
                  <i class="ph-bold ph-copy"></i>
                  <span>{{ schoolLinkCopied ? 'Tersalin!' : 'Salin' }}</span>
                </button>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
              <a
                :href="partnerWhatsAppUrl"
                target="_blank"
                class="px-6 py-3 rounded-full bg-emerald-500 hover:bg-emerald-400 text-black font-black text-xs md:text-sm hover:scale-105 active:scale-95 transition-all inline-flex items-center gap-2 shadow-lg shadow-emerald-500/20"
              >
                <i class="ph-bold ph-whatsapp-logo text-base"></i>
                <span>Klaim Banner Cetak & Fasilitasi BK via WA</span>
              </a>
            </div>
          </div>

          <!-- Live QR Code Card Preview -->
          <div class="lg:col-span-5 flex justify-center">
            <div class="w-full max-w-[280px] p-6 rounded-2xl bg-white text-black text-center shadow-2xl space-y-3 border-4 border-indigo-500/40 relative">
              <div class="text-[10px] font-mono uppercase tracking-widest text-slate-500 font-bold">Portal Asesmen Resmi</div>
              <div class="text-sm font-black tracking-tight truncate">{{ partnerSchoolName || 'SMAN Contoh' }}</div>
              
              <!-- Dynamic QR Image -->
              <div class="p-3 bg-slate-100 rounded-xl flex items-center justify-center">
                <img
                  :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(generatedSchoolLink)}`"
                  :alt="`QR Code ${partnerSchoolName}`"
                  class="w-40 h-40 object-contain rounded-lg shadow-sm"
                  loading="lazy"
                />
              </div>

              <div class="text-[11px] text-slate-600 font-medium">
                Scan untuk ikuti Asesmen Potensi Gratis (SPP)
              </div>
              <div class="text-[9px] font-mono text-slate-400 border-t border-slate-200 pt-1.5">
                Powered by EduPath.ai
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mockup School Insight Dashboard -->
      <div class="rounded-3xl border border-white/20 bg-slate-900/60 backdrop-blur-2xl p-6 md:p-10 shadow-2xl space-y-8">
        
        <!-- School Banner Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-white/10 gap-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-black text-2xl border border-indigo-500/30">
              <i class="ph-bold ph-chart-polar"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg md:text-xl font-black text-white">{{ partnerSchoolName || 'Nama Sekolah Anda' }}</h3>
                <span class="px-2 py-0.5 rounded bg-white/10 text-white/50 text-[10px] font-bold">Preview Laporan</span>
              </div>
              <p class="text-xs text-white/50">Periode Asesmen: Semester Ganjil • Koordinator: {{ partnerBkName || 'Tim BK Sekolah' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-right">
              <div class="text-[10px] uppercase font-mono text-white/40">Total Partisipasi</div>
              <div class="text-lg font-black text-[#c0ff00]">Diisi Setelah Daftar</div>
            </div>
          </div>
        </div>

        <!-- Metric Aggregates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          
          <!-- Top Potential Areas Breakdown -->
          <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/10 space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-black text-sm text-white flex items-center gap-2">
                <i class="ph-bold ph-compass text-indigo-400"></i> Top Potential Areas Angkatan
              </h4>
              <span class="text-[10px] text-white/40 font-mono">Contoh Visualisasi Data</span>
            </div>

            <div class="space-y-3 pt-2">
              <div v-for="(stat, idx) in schoolStats.potentialAreas" :key="idx" class="space-y-1">
                <div class="flex justify-between text-xs font-bold text-white/80">
                  <span>{{ stat.name }}</span>
                  <span class="text-indigo-300">{{ stat.pct }}% ({{ stat.count }} siswa)</span>
                </div>
                <div class="w-full h-2 rounded-full bg-white/10 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="idx === 0 ? 'bg-indigo-400' : idx === 1 ? 'bg-sky-400' : idx === 2 ? 'bg-emerald-400' : 'bg-amber-400'"
                    :style="{ width: `${stat.pct * 2.5}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Growth Areas -->
          <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/10 space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-black text-sm text-white flex items-center gap-2">
                <i class="ph-bold ph-trend-up text-amber-400"></i> Top Growth Areas (Tantangan Bersama)
              </h4>
              <span class="text-[10px] text-white/40 font-mono">Fokus Intervensi BK</span>
            </div>

            <div class="space-y-3 pt-2">
              <div v-for="(growth, idx) in schoolStats.growthAreas" :key="idx" class="p-3 rounded-xl bg-white/5 border border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-300 flex items-center justify-center font-bold text-xs">
                    0{{ idx + 1 }}
                  </span>
                  <div>
                    <div class="text-xs font-bold text-white">{{ growth.title }}</div>
                    <div class="text-[11px] text-white/50">{{ growth.desc }}</div>
                  </div>
                </div>
                <span class="text-xs font-mono font-bold text-amber-400">{{ growth.pct }}%</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Privacy & Consent Notice -->
        <div class="p-4 rounded-xl bg-indigo-950/40 border border-indigo-500/30 flex items-center gap-3 text-xs text-indigo-200">
          <i class="ph-bold ph-shield-check text-xl shrink-0 text-indigo-400"></i>
          <div>
            <strong>Privasi & Consent Dijamin (UU PDP):</strong> Data individual nilai dan tes tetap merupakan hak milik siswa. Sekolah menerima wawasan agregat analitik untuk memandu bimbingan karir tanpa melanggar privasi peserta didik.
          </div>
        </div>

      </div>

    </div>

  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue';
import api from '../api';

export default {
  name: 'StudentPotentialPath',
  emits: ['take-readiness'],
  setup(props, { emit }) {
    const activeMode = ref('student'); // 'student' | 'school'
    const sppStage = ref('intro'); // 'intro' | 'lead_form' | 'questions' | 'result'
    const currentQuestionIdx = ref(0);
    const copySuccess = ref(false);
    const schoolLinkCopied = ref(false);
    const selectedAnswers = ref([]);
    const attemptId = ref(null);
    const isSubmitting = ref(false);

    // Lead Capture Data
    const studentData = reactive({
      name: '',
      school: '',
      targetUniv: '',
      targetMajor: '',
      whatsapp: ''
    });

    // School Partner Interactive Inputs
    const partnerSchoolName = ref('');
    const partnerBkName = ref('');
    const partnerStudentCount = ref('250-500 Siswa (1 Angkatan Penuh)');

    const generatedSchoolLink = computed(() => {
      const slug = encodeURIComponent(partnerSchoolName.value || 'sekolah');
      return `https://edupath.biz.id/spp?school=${slug}`;
    });

    const partnerWhatsAppUrl = computed(() => {
      const text = `Halo Tim Kemitraan EduPath, saya ${partnerBkName.value} dari ${partnerSchoolName.value} (${partnerStudentCount.value}) ingin mengklaim banner QR Code resmi & fasilitasi asesmen SPP untuk sekolah kami.`;
      return `https://wa.me/6281234567890?text=${encodeURIComponent(text)}`;
    });

    // 30 Canonical Questions
    const questions = [
      { id: 'EFC_MAST_01', dimension: 'Potential', question: 'Saya yakin mampu memahami konsep materi UTBK yang menurut saya rumit.' },
      { id: 'EFC_MAST_02', dimension: 'Potential', question: 'Saya merasa yakin bisa memecahkan soal ujian mandiri yang paling sulit sekalipun.' },
      { id: 'EFC_CHAL_01', dimension: 'Potential', question: 'Saya merasa sanggup menghadapi format soal ujian yang baru dan belum pernah saya pelajari.' },
      { id: 'EFC_CHAL_02', dimension: 'Potential', question: 'Saya percaya diri mengerjakan paket soal latihan yang melebihi standar sekolah saya.' },
      { id: 'INT_REAL_01', dimension: 'Interest', question: 'Mempelajari cara kerja komponen mekanik pada kendaraan atau mesin.' },
      { id: 'INT_REAL_02', dimension: 'Interest', question: 'Melakukan pekerjaan yang melibatkan perakitan perangkat elektronik secara langsung.' },
      { id: 'INT_INVS_01', dimension: 'Interest', question: 'Melakukan percobaan laboratorium untuk menemukan jawaban atas fenomena ilmiah.' },
      { id: 'INT_INVS_02', dimension: 'Interest', question: 'Membaca jurnal penelitian atau menganalisis kumpulan data statistik.' },
      { id: 'INT_ARTS_01', dimension: 'Interest', question: 'Menulis karya fiksi, merancang skenario, atau menyusun naskah drama.' },
      { id: 'INT_ARTS_02', dimension: 'Interest', question: 'Mendesain ilustrasi grafis, logo, atau membuat komposisi karya seni rupa.' },
      { id: 'INT_SOCL_01', dimension: 'Interest', question: 'Memberikan bimbingan atau mengajari teman yang sedang kesulitan dalam belajar.' },
      { id: 'INT_SOCL_02', dimension: 'Interest', question: 'Menjadi pendengar yang baik dan membantu menyelesaikan konflik dalam kelompok studi.' },
      { id: 'INT_ENTR_01', dimension: 'Interest', question: 'Mengambil peran sebagai ketua atau pemimpin dalam proyek tugas sekolah.' },
      { id: 'INT_ENTR_02', dimension: 'Interest', question: 'Mempresentasikan proposal dan meyakinkan orang lain untuk menyetujui ide saya.' },
      { id: 'INT_CONV_01', dimension: 'Interest', question: 'Mengelompokkan data penelitian ke dalam tabel atau spreadsheet yang rapi.' },
      { id: 'INT_CONV_02', dimension: 'Interest', question: 'Mengelola pembukuan, jadwal, atau arsip administrasi secara sistematis.' },
      { id: 'SRL_PLAN_01', dimension: 'Learning', question: 'Saya membuat target mingguan untuk menyelesaikan bab-bab pelajaran tertentu.' },
      { id: 'SRL_PLAN_02', dimension: 'Learning', question: 'Sebelum mulai latihan soal, saya memastikan semua catatan penting sudah saya siapkan.' },
      { id: 'SRL_PLAN_03', dimension: 'Learning', question: 'Saya membagi materi belajar yang panjang menjadi bagian-bagian kecil agar lebih mudah dipelajari harian.' },
      { id: 'SRL_CTRL_01', dimension: 'Learning', question: 'Saya menjauhkan diri dari gangguan fisik seperti notifikasi HP saat sesi belajar berlangsung.' },
      { id: 'SRL_CTRL_02', dimension: 'Learning', question: 'Saya secara sadar memaksa diri saya untuk kembali fokus saat pikiran mulai melayang ketika membaca.' },
      { id: 'SRL_CTRL_03', dimension: 'Learning', question: 'Saya membuka media sosial atau game di tengah-tengah waktu mengerjakan latihan soal.' },
      { id: 'SRL_REFL_01', dimension: 'Learning', question: 'Saya mencatat bagian mana saja yang saya kerjakan salah setelah melihat skor tryout.' },
      { id: 'SRL_REFL_02', dimension: 'Learning', question: 'Saya mempertimbangkan apakah pola belajar saya saat ini masih efektif untuk mencapai target.' },
      { id: 'SRL_REFL_03', dimension: 'Learning', question: 'Saya mengabaikan pembahasan soal yang saya jawab salah karena merasa sudah tidak penting.' },
      { id: 'GRT_PERS_01', dimension: 'Growth', question: 'Saya terus mencoba mengerjakan soal yang sama meskipun memakan waktu jauh lebih lama dari biasanya.' },
      { id: 'GRT_PERS_02', dimension: 'Growth', question: 'Saya tidak berhenti belajar meskipun topik yang saya hadapi sangat membosankan.' },
      { id: 'GRT_PERS_03', dimension: 'Growth', question: 'Saya mengulangi latihan pada materi yang sama walau sudah berulang kali mendapat nilai buruk.' },
      { id: 'GRT_PERS_04', dimension: 'Growth', question: 'Saya berhenti berusaha menyelesaikan soal rumit setelah beberapa percobaan pertama gagal.' },
      { id: 'GRT_PERS_05', dimension: 'Growth', question: 'Saya melewati bab materi yang sulit dipahami dan enggan mencobanya lagi di kemudian hari.' }
    ];

    // Profile from Backend
    const profile = reactive({
      archetype: '',
      strengths: [],
      growthAreas: [],
      learningProfile: '',
      fields: []
    });

    const schoolStats = reactive({
      potentialAreas: [],
      growthAreas: []
    });

    const proceedToQuestions = async () => {
      api.trackEvent('spp_start');
      try {
        isSubmitting.value = true;
        const res = await api.sppCreateAttempt();
        attemptId.value = res.attempt_id;
        sppStage.value = 'questions';
        currentQuestionIdx.value = 0;
        selectedAnswers.value = [];
      } catch (err) {
        alert("Gagal memulai attempt: " + err.message);
      } finally {
        isSubmitting.value = false;
      }
    };

    const loadSampleResult = () => {
      studentData.name = 'Raka Pratama';
      studentData.school = 'SMAN 8 Jakarta';
      studentData.targetUniv = 'ITB Bandung';
      studentData.targetMajor = 'Teknik Informatika';
      profile.archetype = 'Analytical Explorer';
      sppStage.value = 'result';
          api.trackEvent('spp_result_view');
          api.trackEvent('spp_exploration_view');
    };

    const selectOption = (value) => {
      selectedAnswers.value[currentQuestionIdx.value] = value;
    };

    const nextQuestion = async () => {
      if (currentQuestionIdx.value < questions.length - 1) {
        currentQuestionIdx.value++;
      } else {
        // Submit all 30 responses
        try {
          isSubmitting.value = true;
          const responses = {};
          questions.forEach((q, idx) => {
            responses[q.id] = selectedAnswers.value[idx];
          });
          
          api.trackEvent('spp_submit');
          await api.sppSubmitAttempt(attemptId.value, responses, 300); // placeholder duration
          const result = await api.sppGetResult(attemptId.value);
          
          // Map backend result to frontend profile object
          if (result && result.score_data) {
             profile.archetype = result.score_data.archetype || 'Explorer';
             profile.relativeStrength = result.score_data.relative_strength || [];
             profile.developmentArea = result.score_data.development_area || [];
             profile.learning = (result.score_data.narrative_keys && result.score_data.narrative_keys.learning) || 'Siswa menunjukkan pola pembelajaran mandiri.';
             profile.fields = (result.score_data.riasec_top3 || []).map(r => ({
               name: r.type,
               desc: `Cocok untuk tipe ${r.type}`,
               stars: r.score >= 80 ? 5 : 4,
               matchRate: Math.round(r.score)
             }));
          }
          sppStage.value = 'result';
          api.trackEvent('spp_result_view');
          api.trackEvent('spp_exploration_view');
        } catch (err) {
          alert("Gagal mengirim jawaban: " + err.message);
        } finally {
          isSubmitting.value = false;
        }
      }
    };

    const retakeAssessment = () => {
      sppStage.value = 'intro';
      currentQuestionIdx.value = 0;
      selectedAnswers.value = [];
      attemptId.value = null;
    };

    const shareCard = async () => {
      const nameTag = studentData.name ? `${studentData.name} (${studentData.school})` : 'Gue';
      const shareText = `${nameTag} baru aja cek potensi diri di Student Potential Path (SPP)! Telah menyelesaikan SPP!. Target kampus: ${studentData.targetUniv || 'PTN Impian'}. Coba cek potensimu juga di EduPath secara gratis!`;
      if (navigator.clipboard) {
        try {
          await navigator.clipboard.writeText(shareText + ' ' + window.location.href);
          copySuccess.value = true;
          setTimeout(() => {
            copySuccess.value = false;
          }, 3000);
        } catch (e) {
          // fallback
        }
      }
    };

    const copySchoolLink = async () => {
      if (navigator.clipboard) {
        try {
          await navigator.clipboard.writeText(generatedSchoolLink.value);
          schoolLinkCopied.value = true;
          setTimeout(() => {
            schoolLinkCopied.value = false;
          }, 3000);
        } catch (e) {
          // fallback
        }
      }
    };

    const handleEduPathCTA = () => {
      emit('take-readiness', {
        studentName: studentData.name,
        school: studentData.school,
        targetUniv: studentData.targetUniv,
        targetMajor: studentData.targetMajor,
        archetype: profile.archetype
      });
    };

    return {
      activeMode,
      sppStage,
      currentQuestionIdx,
      currentQuestion: computed(() => questions[currentQuestionIdx.value]),
      questions,
      selectedAnswers,
      studentData,
      partnerSchoolName,
      partnerBkName,
      partnerStudentCount,
      generatedSchoolLink,
      partnerWhatsAppUrl,
      profile,
      schoolStats,
      copySuccess,
      schoolLinkCopied,
      isSubmitting,
      proceedToQuestions,
      loadSampleResult,
      selectOption,
      nextQuestion,
      retakeAssessment,
      shareCard,
      copySchoolLink,
      handleEduPathCTA
    };
  }
};
</script>

<style scoped>
.font-heading {
  font-family: 'Outfit', sans-serif;
}
</style>
