<template>
  <div class="min-h-screen bg-[#f0f2f5] font-['Arial','Helvetica',sans-serif] text-slate-800 flex flex-col">
    <!-- Header -->
    <header class="bg-[#024a86] text-white shadow-md z-10 shrink-0">
      <div class="px-3 sm:px-4 py-2.5 sm:py-3 flex items-center justify-between border-b border-white/10 gap-2">
        <div class="flex items-center gap-2.5 sm:gap-4 min-w-0">
          <button @click="$router.push('/')" title="Kembali ke Dashboard" class="px-2.5 sm:px-4 py-1.5 sm:py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg flex items-center gap-1.5 transition-all font-bold text-xs sm:text-sm shadow-sm shrink-0 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span class="hidden sm:inline">Kembali ke Dashboard</span>
            <span class="sm:hidden">Keluar</span>
          </button>
          <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white rounded-full flex items-center justify-center font-black text-[#024a86] text-sm sm:text-lg shrink-0">
            {{ cbtBadge }}
          </div>
          <div class="min-w-0">
            <h1 class="text-xs sm:text-base md:text-xl font-bold uppercase tracking-wide leading-tight truncate">{{ cbtTitle }}</h1>
            <p class="text-[10px] sm:text-xs text-blue-200 truncate">{{ cbtSubtitle }}</p>
          </div>
        </div>
        <div class="hidden md:flex items-center gap-4 text-right shrink-0">
          <div>
            <p class="text-sm font-semibold">Siswa Tryout 01</p>
            <p class="text-xs text-blue-200">Nomor Peserta: 2026-001-002-3</p>
          </div>
          <div class="w-10 h-10 rounded-full border-2 border-white/30 overflow-hidden shrink-0 bg-white/10 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
        </div>
      </div>
      <!-- Sub-header: Mata Ujian & Timer -->
      <div class="bg-[#013b6b] px-3 sm:px-4 py-2 flex items-center justify-between text-xs sm:text-sm shadow-inner gap-2">
        <div class="font-semibold uppercase tracking-wider flex items-center gap-1.5 sm:gap-2 min-w-0">
          <span class="hidden sm:inline">Mata Uji:</span>
          <span class="bg-[#facc15] text-black px-2 py-0.5 rounded font-bold text-[10px] sm:text-xs truncate max-w-[140px] sm:max-w-none">{{ currentQuestion.subtest || currentQuestion.sub_materi || 'Tes Akademik' }}</span>
        </div>
        <div class="flex items-center gap-2 sm:gap-4 shrink-0">
          <button @click="showQuestionList = true" class="bg-blue-600 hover:bg-blue-500 text-white px-2.5 sm:px-3 py-1 sm:py-1.5 rounded font-bold text-[10px] sm:text-xs uppercase flex items-center gap-1 sm:gap-2 transition-colors border border-blue-400 shadow cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z"></path></svg>
            <span>Daftar Soal</span>
          </button>
          <div class="flex items-center gap-1.5 sm:gap-2 font-bold text-sm sm:text-lg">
            <span class="text-[10px] sm:text-xs uppercase font-normal text-blue-200 hidden sm:inline">Sisa Waktu:</span>
            <span class="bg-red-600 px-2 sm:px-3 py-0.5 sm:py-1 rounded text-white tracking-widest font-mono text-xs sm:text-base">
              {{ formattedTime }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow flex w-full max-w-[1000px] mx-auto p-2 lg:p-4 min-h-0">
      
      <!-- Insufficient Bank Error / Entitlement Error State -->
      <div v-if="insufficientBankError || entitlementError" class="flex-grow flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-rose-200 p-6 text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto text-3xl font-black">!</div>
          <h2 class="text-lg font-black text-slate-800">{{ entitlementError ? 'Akses Khusus Pro Member' : 'Paket Asesmen Belum Siap' }}</h2>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">{{ entitlementError || insufficientBankError }}</p>
          <div class="flex flex-col gap-2 pt-2">
            <button v-if="entitlementError" @click="$router.push('/#plans')" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition-all shadow-md cursor-pointer">
              Pilih Paket Pro Member
            </button>
            <button @click="$router.push('/')" class="w-full py-2.5 px-4 bg-[#024a86] hover:bg-[#023b6b] text-white font-bold text-sm rounded-xl transition-all shadow-md cursor-pointer">
              Kembali ke Dashboard Siswa
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else-if="isLoading" class="flex-grow flex items-center justify-center">
        <div class="text-center">
          <i class="ph-bold ph-spinner animate-spin text-4xl text-blue-600 mb-4"></i>
          <h2 class="text-xl font-bold text-gray-700">Menyiapkan Soal Try Out CBT...</h2>
        </div>
      </div>

        <!-- Question Area -->
        <div v-else class="flex-grow bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col relative overflow-hidden">
          <div class="px-3.5 sm:px-6 py-2.5 sm:py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center gap-2 shrink-0">
          <div class="font-bold text-sm sm:text-lg md:text-xl text-slate-800">SOAL NO. <span class="text-lg sm:text-2xl ml-1 font-black text-[#024a86]">{{ activeIndex + 1 }}</span></div>
          <div class="text-xs sm:text-sm text-gray-600 font-bold flex items-center">
            <span class="hidden sm:inline mr-1">Ukuran Teks:</span>
            <button type="button" class="cursor-pointer border border-gray-300 rounded px-2 py-0.5 ml-1 hover:bg-gray-200 text-xs sm:text-sm font-semibold transition-colors" :class="fontSize === 'text-base' ? 'bg-[#024a86] text-white border-[#024a86]' : 'bg-white text-gray-700'" @click="fontSize = 'text-base'">A-</button>
            <button type="button" class="cursor-pointer border border-gray-300 rounded px-2 sm:px-2.5 py-0.5 ml-1 hover:bg-gray-200 text-xs sm:text-base font-bold transition-colors" :class="fontSize === 'text-lg' ? 'bg-[#024a86] text-white border-[#024a86]' : 'bg-white text-gray-700'" @click="fontSize = 'text-lg'">A</button>
            <button type="button" class="cursor-pointer border border-gray-300 rounded px-2.5 sm:px-3 py-0.5 ml-1 hover:bg-gray-200 text-sm sm:text-lg font-black transition-colors" :class="fontSize === 'text-xl' ? 'bg-[#024a86] text-white border-[#024a86]' : 'bg-white text-gray-700'" @click="fontSize = 'text-xl'">A+</button>
          </div>
        </div>

        <!-- Scrollable Question Area -->
        <div class="flex-grow overflow-y-auto p-3.5 sm:p-6 md:p-10 font-sans" :class="fontSize">
          <div class="mb-6 sm:mb-8 leading-relaxed text-justify text-slate-900 font-medium question-body" v-html="currentQuestion.text"></div>

          <!-- Question Type Indicator Badge -->
          <div class="mb-4 flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-300">
              <span v-if="currentQuestion.question_type === 'complex_mcq'">☑ Pilihan Ganda Kompleks (Bisa pilih lebih dari satu)</span>
              <span v-else-if="currentQuestion.question_type === 'true_false'">⚖ Benar / Salah (Tentukan setiap pernyataan)</span>
              <span v-else-if="currentQuestion.question_type === 'short_answer'">✍ Isian Singkat</span>
              <span v-else>◉ Pilihan Ganda (Pilih satu jawaban)</span>
            </span>
          </div>

          <!-- Options Rendered Dynamically by question_type -->
          <!-- 1. Multiple Choice (Single Choice) -->
          <div v-if="!currentQuestion.question_type || currentQuestion.question_type === 'multiple_choice'" class="space-y-3 sm:space-y-4">
            <label 
              v-for="(option, idx) in currentQuestion.options" 
              :key="idx"
              class="flex items-start gap-3 sm:gap-4 p-2.5 sm:p-3 rounded-lg border-2 cursor-pointer transition-colors"
              :class="answers[activeIndex] === option.id ? 'border-[#024a86] bg-blue-50' : 'border-transparent hover:bg-gray-50'"
            >
              <div class="relative flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full border-2 mt-0.5 shrink-0 text-xs sm:text-sm"
                   :class="answers[activeIndex] === option.id ? 'border-[#024a86] bg-[#024a86] text-white' : 'border-gray-400 text-gray-600'">
                <input 
                  type="radio" 
                  :id="'q_' + activeIndex + '_' + option.id"
                  :name="'question-' + activeIndex" 
                  :value="option.id"
                  v-model="answers[activeIndex]"
                  class="absolute opacity-0"
                >
                <span class="font-bold">{{ option.id }}</span>
              </div>
              <div class="pt-1 leading-relaxed flex-grow text-xs sm:text-sm" v-html="option.text"></div>
            </label>
          </div>

          <!-- 2. Complex MCQ (Multiple Selection Checkboxes) -->
          <div v-else-if="currentQuestion.question_type === 'complex_mcq'" class="space-y-3 sm:space-y-4">
            <div 
              v-for="(option, idx) in currentQuestion.options" 
              :key="idx"
              @click="toggleCbtComplexOption(option.id)"
              class="flex items-start gap-3 sm:gap-4 p-2.5 sm:p-3 rounded-lg border-2 cursor-pointer transition-colors"
              :class="isCbtComplexSelected(option.id) ? 'border-[#024a86] bg-blue-50' : 'border-gray-200 hover:bg-gray-50'"
            >
              <div class="relative flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-md border-2 mt-0.5 shrink-0 text-xs sm:text-sm font-bold"
                   :class="isCbtComplexSelected(option.id) ? 'border-[#024a86] bg-[#024a86] text-white' : 'border-gray-400 text-gray-600'">
                <span>{{ option.id }}</span>
              </div>
              <div class="pt-1 leading-relaxed flex-grow text-xs sm:text-sm" v-html="option.text"></div>
              <span v-if="isCbtComplexSelected(option.id)" class="text-blue-700 font-black text-sm shrink-0">✓ Terpilih</span>
            </div>
          </div>

          <!-- 3. True / False (Statement Matrix) -->
          <div v-else-if="currentQuestion.question_type === 'true_false'" class="space-y-3 sm:space-y-4">
            <div 
              v-for="(option, idx) in currentQuestion.options" 
              :key="idx"
              class="p-3.5 rounded-xl border border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
              <div class="flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">{{ option.id }}</span>
                <span class="text-xs sm:text-sm text-slate-900 leading-relaxed" v-html="option.text"></span>
              </div>
              <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                <button
                  type="button"
                  @click="setCbtTrueFalse(option.id, 'B')"
                  :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all', getCbtTrueFalse(option.id) === 'B' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-300 hover:bg-emerald-50']"
                >
                  ✓ Benar
                </button>
                <button
                  type="button"
                  @click="setCbtTrueFalse(option.id, 'S')"
                  :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all', getCbtTrueFalse(option.id) === 'S' ? 'bg-rose-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-300 hover:bg-rose-50']"
                >
                  ✗ Salah
                </button>
              </div>
            </div>
          </div>

          <!-- 4. Short Answer -->
          <div v-else-if="currentQuestion.question_type === 'short_answer'" class="space-y-3">
            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1">Ketikkan Jawaban Akhir Anda:</label>
            <input 
              type="text" 
              v-model="answers[activeIndex]" 
              placeholder="Ketikkan jawaban berupa angka atau kata kunci..."
              class="w-full p-3.5 text-sm sm:text-base border-2 border-[#024a86] rounded-xl bg-white text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-400"
            />
            <p class="text-[11px] text-gray-500 italic">*Pastikan penulisan sesuai instruksi soal tanpa spasi berlebih.</p>
          </div>
        </div>

        <!-- Navigation Footer -->
        <div class="bg-gray-100 border-t border-gray-300 p-2.5 sm:p-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3 shrink-0">
          <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
            <button 
              @click="prevQuestion"
              :disabled="activeIndex === 0"
              class="px-3 sm:px-5 py-2 sm:py-2.5 bg-white border border-gray-400 text-gray-700 font-bold rounded text-xs sm:text-sm shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed uppercase flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <span>← Sebelumnya</span>
            </button>

            <label :for="'doubt_' + activeIndex" class="flex items-center justify-center gap-1.5 cursor-pointer px-3 sm:px-5 py-2 sm:py-2.5 bg-[#facc15] text-yellow-900 border border-yellow-500 font-bold rounded text-xs sm:text-sm shadow-sm hover:bg-yellow-500 uppercase transition-colors">
              <input 
                type="checkbox" 
                :id="'doubt_' + activeIndex"
                :name="'doubt_' + activeIndex"
                v-model="doubt[activeIndex]"
                class="w-4 h-4 sm:w-5 sm:h-5 accent-yellow-600 cursor-pointer"
              >
              <span>Ragu-Ragu</span>
            </label>
          </div>

          <div class="w-full sm:w-auto">
            <button 
              v-if="activeIndex < questions.length - 1"
              @click="nextQuestion"
              class="w-full sm:w-auto px-4 sm:px-5 py-2 sm:py-2.5 bg-[#024a86] border border-[#013b6b] text-white font-bold rounded text-xs sm:text-sm shadow-sm hover:bg-[#013b6b] uppercase flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <span>Selanjutnya →</span>
            </button>
            <button 
              v-else
              @click="finishExam"
              class="w-full sm:w-auto px-5 sm:px-6 py-2 sm:py-2.5 bg-green-600 border border-green-700 text-white font-bold rounded text-xs sm:text-sm shadow-sm hover:bg-green-700 uppercase flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <span>Selesai Ujian</span>
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal/Drawer Daftar Soal -->
    <div v-if="showQuestionList" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm" @click.self="showQuestionList = false">
      <div class="w-full max-w-[340px] bg-white shadow-2xl flex flex-col h-full animate-slide-left">
        <div class="bg-[#024a86] border-b border-blue-800 px-4 py-4 flex items-center justify-between text-white">
          <h2 class="font-bold text-lg tracking-wider">DAFTAR SOAL</h2>
          <button @click="showQuestionList = false" class="modal-close-btn" title="Tutup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>
        
        <!-- Legends -->
        <div class="p-3 border-b border-gray-200 flex flex-wrap gap-2 text-xs justify-center bg-gray-50">
          <div class="flex items-center gap-1"><span class="w-3 h-3 bg-white border border-gray-400 inline-block"></span> Belum</div>
          <div class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 border border-green-600 inline-block"></span> Dijawab</div>
          <div class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-400 border border-yellow-500 inline-block"></span> Ragu</div>
        </div>

        <div class="p-5 overflow-y-auto flex-grow bg-[#f0f2f5]">
          <div class="grid grid-cols-5 gap-3">
            <button
              v-for="(q, idx) in questions"
              :key="idx"
              @click="goToQuestion(idx); showQuestionList = false"
              class="relative w-full aspect-square text-sm font-bold flex items-center justify-center border-2 rounded transition-all shadow-sm"
              :class="[
                activeIndex === idx ? 'ring-2 ring-blue-500 scale-110 z-10' : 'hover:scale-105',
                getQuestionBtnClass(idx)
              ]"
            >
              {{ idx + 1 }}
              <!-- Checkmark for answered -->
              <div v-if="answers[idx] && !doubt[idx]" class="absolute -bottom-1 -right-1 text-green-700 text-[10px] bg-white rounded-full leading-none shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Selesai -->
    <div v-if="showFinishModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full overflow-hidden font-['Arial']">
        <div class="bg-[#024a86] px-4 py-3 text-white font-bold text-lg">
          Konfirmasi Akhiri Ujian
        </div>
        <div class="p-6 text-center">
          <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
            !
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Apakah Anda yakin?</h3>
          <p class="text-gray-600 mb-6">Anda masih memiliki sisa waktu. Jika Anda mengakhiri ujian sekarang, semua jawaban akan disimpan dan Anda tidak bisa kembali untuk mengubah jawaban.</p>
          
          <div class="flex gap-3 justify-center">
            <button @click="showFinishModal = false" class="px-5 py-2.5 bg-gray-200 text-gray-800 font-bold rounded hover:bg-gray-300 transition-colors">
              Batal
            </button>
            <button @click="submitExam" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition-colors">
              Ya, Selesai Ujian
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import api from '../api';
import { HOTS_DIAGNOSTIC_BANK } from '../EduData';

export default {
  name: 'TryOutCBT',
  data() {
    return {
      showQuestionList: false,
      activeIndex: 0,
      fontSize: 'text-lg',
      answers: [],
      doubt: [],
      timeLeft: 15 * 60,
      totalDurationSec: 15 * 60,
      attemptId: null,
      saveTimer: null,
      submitting: false,
      timerInterval: null,
      showFinishModal: false,
      questions: [],
      isLoading: true,
      assessmentType: 'SNBT',
      track: 'umum',
      cbtTitle: 'Ujian CBT SNPMB',
      cbtSubtitle: 'Seleksi Nasional Penerimaan Mahasiswa Baru',
      cbtBadge: 'SN',
      insufficientBankError: null,
      entitlementError: null
    }
  },
  computed: {
    currentQuestion() {
      return this.questions[this.activeIndex] || {};
    },
    formattedTime() {
      const hours = Math.floor(this.timeLeft / 3600);
      const minutes = Math.floor((this.timeLeft % 3600) / 60);
      const seconds = this.timeLeft % 60;
      
      if (hours > 0) {
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
      }
      return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
  },
  watch: {
    answers: { deep: true, handler() { this.persistAnswers(); } },
    doubt: { deep: true, handler() { this.persistAnswers(); } }
  },
  async mounted() {
    try {
      const token = sessionStorage.getItem('ep_session_token') || sessionStorage.getItem('token') || localStorage.getItem('token') || localStorage.getItem('auth_token');
      if (!token) {
        throw new Error('Guest mode: student token not present');
      }

      // Resolve assessment program and track from query parameters
      const urlParams = new URLSearchParams(window.location.search || window.location.hash.split('?')[1] || '');
      this.assessmentType = (urlParams.get('assessment_type') || this.$route?.query?.assessment_type || 'SNBT').toUpperCase();
      this.track = (urlParams.get('track') || this.$route?.query?.track || 'umum').toLowerCase();

      if (this.assessmentType === 'TKA') {
        if (this.track === 'sd') {
          this.cbtTitle = 'Ujian CBT TKA SD';
          this.cbtSubtitle = 'Simulasi Ujian Kemampuan Akademik Sekolah Dasar';
          this.cbtBadge = 'SD';
          this.totalDurationSec = 60 * 60;
        } else if (this.track === 'smp') {
          this.cbtTitle = 'Ujian CBT TKA SMP';
          this.cbtSubtitle = 'Simulasi Ujian Kemampuan Akademik Sekolah Menengah Pertama';
          this.cbtBadge = 'SMP';
          this.totalDurationSec = 75 * 60;
        } else if (this.track === 'saintek') {
          this.cbtTitle = 'Ujian CBT TKA SMA — SAINTEK';
          this.cbtSubtitle = 'Simulasi Ujian Kemampuan Akademik Bidang Sains & Teknologi';
          this.cbtBadge = 'TK';
          this.totalDurationSec = 90 * 60;
        } else if (this.track === 'soshum') {
          this.cbtTitle = 'Ujian CBT TKA SMA — SOSHUM';
          this.cbtSubtitle = 'Simulasi Ujian Kemampuan Akademik Bidang Sosial & Humaniora';
          this.cbtBadge = 'TK';
          this.totalDurationSec = 90 * 60;
        } else {
          this.cbtTitle = 'Ujian CBT TKA SMA';
          this.cbtSubtitle = 'Simulasi Ujian Kemampuan Akademik SMA';
          this.cbtBadge = 'TK';
          this.totalDurationSec = 90 * 60;
        }
        this.timeLeft = this.totalDurationSec;
      }

      const savedAttemptId = localStorage.getItem(`edupath_tryout_attempt_id_${this.assessmentType}_${this.track}`) || localStorage.getItem('edupath_tryout_attempt_id');
      const attempt = await api.quizStart({ 
        attempt_id: savedAttemptId, 
        quiz_type: 'tryout', 
        assessment_type: this.assessmentType,
        track: this.track,
        limit: this.assessmentType === 'TKA' ? 80 : 15, 
        duration_sec: this.totalDurationSec 
      });

      this.attemptId = attempt.attempt_id;
      localStorage.setItem(`edupath_tryout_attempt_id_${this.assessmentType}_${this.track}`, this.attemptId);
      localStorage.setItem('edupath_tryout_attempt_id', this.attemptId);
      const dbQuestions = attempt.questions || [];
      if (dbQuestions.length > 0) {
        this.questions = dbQuestions.map(q => ({
          id: q.id,
          question_type: q.question_type || 'multiple_choice',
          subtest: q.sub_materi || q.subtes || 'Tes Akademik',
          text: q.question,
          options: [
            { id: 'A', text: q.option_a },
            { id: 'B', text: q.option_b },
            { id: 'C', text: q.option_c },
            { id: 'D', text: q.option_d },
            { id: 'E', text: q.option_e }
          ].filter(o => o.text !== null && o.text !== undefined && o.text !== '')
        }));
      } else {
        throw new Error('No questions returned from API');
      }
    } catch (e) {
      if (e.message?.includes('Paket Berlangganan') || e.message?.includes('Pro Member') || e.code === 'ENTITLEMENT_REQUIRED') {
        this.entitlementError = e.message;
        this.questions = [];
        return;
      }

      // Guard against silent substitution (Constraint 5): Never fallback to SNBT when TKA fails
      if (this.assessmentType === 'TKA') {
        this.insufficientBankError = e.message || 'Bank soal TKA untuk peminatan ini belum mencukupi kuota simulasi. Silakan kembali ke dashboard atau hubungi koordinator akademik.';
        this.questions = [];
        return;
      }

      console.warn('Tryout API fallback to HOTS_DIAGNOSTIC_BANK for SNBT only', e);
      this.attemptId = 'local-to-' + Date.now();
      this.questions = HOTS_DIAGNOSTIC_BANK.map((q, idx) => ({
        id: q.id || (idx + 1),
        subtest: q.category || q.subject || 'Penalaran Umum',
        text: q.question,
        options: (q.options || []).map((opt, oIdx) => ({
          id: String.fromCharCode(65 + oIdx),
          text: opt
        }))
      }));
    } finally {
      this.isLoading = false;
      if (!this.insufficientBankError && !this.entitlementError && this.questions.length > 0) {
        this.answers = new Array(this.questions.length).fill(null);
        this.doubt = new Array(this.questions.length).fill(false);
        const saved = localStorage.getItem(`edupath_tryout_${this.attemptId}`);
        if (saved) {
          const state = JSON.parse(saved);
          this.answers = state.answers || this.answers;
          this.doubt = state.doubt || this.doubt;
          if (state.expiresAt) this.timeLeft = Math.max(0, Math.floor((new Date(state.expiresAt).getTime() - Date.now()) / 1000));
        }
        this.startTimer();
      }
    }
  },
  beforeUnmount() {
    clearInterval(this.timerInterval);
    clearTimeout(this.saveTimer);
  },
  methods: {
    goToQuestion(index) {
      this.activeIndex = index;
    },
    nextQuestion() {
      if (this.activeIndex < this.questions.length - 1) {
        this.activeIndex++;
      }
    },
    prevQuestion() {
      if (this.activeIndex > 0) {
        this.activeIndex--;
      }
    },
    toggleCbtComplexOption(optId) {
      const current = (this.answers[this.activeIndex] || '')
        .split(',')
        .map(s => s.trim().toUpperCase())
        .filter(Boolean);
      const target = optId.toUpperCase();
      const idx = current.indexOf(target);
      if (idx >= 0) {
        current.splice(idx, 1);
      } else {
        current.push(target);
      }
      current.sort();
      this.answers[this.activeIndex] = current.join(',');
    },
    isCbtComplexSelected(optId) {
      const current = (this.answers[this.activeIndex] || '')
        .split(',')
        .map(s => s.trim().toUpperCase())
        .filter(Boolean);
      return current.includes(optId.toUpperCase());
    },
    setCbtTrueFalse(optId, val) {
      const key = optId.toLowerCase();
      const map = {};
      (this.answers[this.activeIndex] || '').split(',').forEach(item => {
        const [k, v] = item.split(':');
        if (k && v) map[k.trim().toLowerCase()] = v.trim().toUpperCase();
      });
      map[key] = val.toUpperCase();
      const parts = Object.keys(map).sort().map(k => `${k}:${map[k]}`);
      this.answers[this.activeIndex] = parts.join(',');
    },
    getCbtTrueFalse(optId) {
      const key = optId.toLowerCase();
      const map = {};
      (this.answers[this.activeIndex] || '').split(',').forEach(item => {
        const [k, v] = item.split(':');
        if (k && v) map[k.trim().toLowerCase()] = v.trim().toUpperCase();
      });
      return map[key] || null;
    },
    getQuestionBtnClass(idx) {
      // Logic for question button colors based on SNPMB UI
      // Kuning = Ragu, Hijau = Terjawab, Putih = Belum
      if (this.doubt[idx]) {
        return 'bg-yellow-400 border-yellow-500 text-yellow-900';
      } else if (this.answers[idx]) {
        return 'bg-green-500 border-green-600 text-white';
      }
      return 'bg-white border-gray-400 text-gray-700';
    },
    startTimer() {
      this.timerInterval = setInterval(() => {
        if (this.timeLeft > 0) {
          this.timeLeft--;
        } else {
          clearInterval(this.timerInterval);
          this.autoSubmit();
        }
      }, 1000);
    },
    persistAnswers() {
      if (!this.attemptId) return;
      const state = {
        answers: this.answers,
        doubt: this.doubt,
        expiresAt: new Date(Date.now() + this.timeLeft * 1000).toISOString()
      };
      localStorage.setItem(`edupath_tryout_${this.attemptId}`, JSON.stringify(state));
      clearTimeout(this.saveTimer);
      if (!String(this.attemptId).startsWith('local-to-')) {
        this.saveTimer = setTimeout(() => {
          api.quizSave(this.attemptId, this.answers.map((answer, index) => ({
            question_id: this.questions[index]?.id,
            answer
          })).filter(item => item.answer)).catch(() => {});
        }, 500);
      }
    },
    finishExam() {
      this.showFinishModal = true;
    },
    async submitExam() {
      if (this.submitting) return;
      this.submitting = true;
      this.showFinishModal = false;
      clearInterval(this.timerInterval);
      try {
        if (String(this.attemptId).startsWith('local-to-')) {
          localStorage.removeItem(`edupath_tryout_${this.attemptId}`);
          localStorage.removeItem('edupath_tryout_attempt_id');
          alert("Ujian selesai (Mode Latihan/Demo). Jawaban Anda telah tersimpan.");
          this.$router.push('/');
          return;
        }
        await api.submitQuiz({
          attempt_id: this.attemptId,
          quiz_type: 'tryout',
          assessment_type: this.assessmentType,
          track: this.track,
          total_questions: this.questions.length,
          answers: this.answers.map((answer, index) => ({ question_id: this.questions[index]?.id, answer })).filter(item => item.answer),
          duration_sec: Math.max(0, this.totalDurationSec - this.timeLeft)
        });
        localStorage.removeItem(`edupath_tryout_${this.attemptId}`);
        localStorage.removeItem(`edupath_tryout_attempt_id_${this.assessmentType}_${this.track}`);
        localStorage.removeItem('edupath_tryout_attempt_id');
        alert("Ujian selesai. Jawaban Anda telah disimpan.");
        this.$router.push('/');
      } catch (error) {
        this.submitting = false;
        alert(error.message || 'Jawaban belum dapat disimpan.');
      }
    },
    autoSubmit() {
      this.submitExam();
    }
  }
}
</script>

<style scoped>
@keyframes slideLeft {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
.animate-slide-left {
  animation: slideLeft 0.3s ease-out forwards;
}

/* Additional tweaks to ensure formal CBT look */
input[type="radio"]:focus {
  outline: none;
}
.text-justify {
  text-align: justify;
}
</style>
