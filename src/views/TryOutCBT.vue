<template>
  <div class="min-h-screen bg-[#f0f2f5] font-['Arial','Helvetica',sans-serif] text-slate-800 flex flex-col">
    <!-- Header -->
    <header class="bg-[#024a86] text-white shadow-md z-10">
      <div class="px-4 py-3 flex items-center justify-between border-b border-white/10">
        <div class="flex items-center gap-4">
          <button @click="$router.push('/')" title="Kembali ke Dashboard" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg flex items-center gap-2 transition-all font-bold text-xs sm:text-sm shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span class="hidden sm:inline">Kembali ke Dashboard</span>
            <span class="sm:hidden">Kembali</span>
          </button>
          <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center font-bold text-[#024a86] text-lg shrink-0">
            SN
          </div>
          <div>
            <h1 class="text-lg md:text-xl font-bold uppercase tracking-wide leading-tight">Ujian CBT SNPMB</h1>
            <p class="text-xs text-blue-200">Seleksi Nasional Penerimaan Mahasiswa Baru</p>
          </div>
        </div>
        <div class="hidden md:flex items-center gap-4 text-right">
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
      <div class="bg-[#013b6b] px-4 py-2 flex items-center justify-between text-sm shadow-inner">
        <div class="font-semibold uppercase tracking-wider flex items-center gap-2">
          Subtes: <span class="bg-[#facc15] text-black px-2 py-0.5 rounded font-bold">{{ currentQuestion.sub_materit }}</span>
        </div>
        <div class="flex items-center gap-4">
          <button @click="showQuestionList = true" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded font-bold text-xs uppercase flex items-center gap-2 transition-colors border border-blue-400 shadow">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z"></path></svg>
            Daftar Soal
          </button>
          <div class="flex items-center gap-2 font-bold text-lg">
            <span class="text-xs uppercase font-normal text-blue-200 hidden sm:inline">Sisa Waktu:</span>
            <span class="bg-red-600 px-3 py-1 rounded text-white tracking-widest font-mono">
              {{ formattedTime }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow flex w-full max-w-[1000px] mx-auto p-2 lg:p-4 h-[calc(100vh-120px)]">
      
      <!-- Loading State -->
        <div v-if="isLoading" class="flex-grow flex items-center justify-center">
          <div class="text-center">
            <i class="ph-bold ph-spinner animate-spin text-4xl text-blue-600 mb-4"></i>
            <h2 class="text-xl font-bold text-gray-700">Menyiapkan Soal Try Out CBT...</h2>
          </div>
        </div>

        <!-- Question Area -->
        <div v-else class="flex-grow bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col relative overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
          <div class="font-bold text-lg">SOAL NO. <span class="text-2xl ml-1">{{ activeIndex + 1 }}</span></div>
          <div class="text-sm text-gray-500 font-medium">Ukuran Font: <span class="cursor-pointer border px-1 ml-1 hover:bg-gray-200" @click="fontSize = 'text-sm'">A-</span><span class="cursor-pointer border px-1 ml-1 font-bold hover:bg-gray-200" @click="fontSize = 'text-base'">A</span><span class="cursor-pointer border px-1 ml-1 text-lg hover:bg-gray-200" @click="fontSize = 'text-lg'">A+</span></div>
        </div>

        <!-- Scrollable Question Area -->
        <div class="flex-grow overflow-y-auto p-5 md:p-8" :class="fontSize">
          <div class="mb-8 leading-relaxed text-justify" v-html="currentQuestion.text"></div>

          <!-- Options -->
          <div class="space-y-4">
            <label 
              v-for="(option, idx) in currentQuestion.options" 
              :key="idx"
              class="flex items-start gap-4 p-3 rounded-lg border-2 cursor-pointer transition-colors"
              :class="answers[activeIndex] === option.id ? 'border-[#024a86] bg-blue-50' : 'border-transparent hover:bg-gray-50'"
            >
              <div class="relative flex items-center justify-center w-8 h-8 rounded-full border-2 mt-0.5 shrink-0"
                   :class="answers[activeIndex] === option.id ? 'border-[#024a86] bg-[#024a86] text-white' : 'border-gray-400 text-gray-600'">
                <input 
                  type="radio" 
                  :name="'question-' + activeIndex" 
                  :value="option.id"
                  v-model="answers[activeIndex]"
                  class="absolute opacity-0"
                >
                <span class="font-bold">{{ option.id }}</span>
              </div>
              <div class="pt-1.5 leading-relaxed flex-grow" v-html="option.text"></div>
            </label>
          </div>
        </div>

        <!-- Navigation Footer -->
        <div class="bg-gray-100 border-t border-gray-300 p-4 flex flex-wrap items-center justify-between gap-3">
          <button 
            @click="prevQuestion"
            :disabled="activeIndex === 0"
            class="px-5 py-2.5 bg-white border border-gray-400 text-gray-700 font-bold rounded shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed uppercase flex items-center gap-2 transition-colors"
          >
            ← Soal Sebelumnya
          </button>

          <label class="flex items-center gap-2 cursor-pointer px-5 py-2.5 bg-[#facc15] text-yellow-900 border border-yellow-500 font-bold rounded shadow-sm hover:bg-yellow-500 uppercase transition-colors">
            <input 
              type="checkbox" 
              v-model="doubt[activeIndex]"
              class="w-5 h-5 accent-yellow-600 cursor-pointer"
            >
            <span>Ragu-Ragu</span>
          </label>

          <button 
            v-if="activeIndex < questions.length - 1"
            @click="nextQuestion"
            class="px-5 py-2.5 bg-[#024a86] border border-[#013b6b] text-white font-bold rounded shadow-sm hover:bg-[#013b6b] uppercase flex items-center gap-2 transition-colors"
          >
            Soal Selanjutnya →
          </button>
          <button 
            v-else
            @click="finishExam"
            class="px-6 py-2.5 bg-green-600 border border-green-700 text-white font-bold rounded shadow-sm hover:bg-green-700 uppercase flex items-center gap-2 transition-colors"
          >
            Selesai Ujian
          </button>
        </div>
      </div>
    </main>

    <!-- Modal/Drawer Daftar Soal -->
    <div v-if="showQuestionList" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm" @click.self="showQuestionList = false">
      <div class="w-full max-w-[340px] bg-white shadow-2xl flex flex-col h-full animate-slide-left">
        <div class="bg-[#024a86] border-b border-blue-800 px-4 py-4 flex items-center justify-between text-white">
          <h2 class="font-bold text-lg tracking-wider">DAFTAR SOAL</h2>
          <button @click="showQuestionList = false" class="hover:bg-white/20 p-1 rounded transition-colors text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
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

export default {
  name: 'TryOutCBT',
  data() {
    return {
      showQuestionList: false,
      activeIndex: 0,
      fontSize: 'text-base',
      answers: [],
      doubt: [],
      timeLeft: 15 * 60, // 15 minutes in seconds
      timerInterval: null,
      showFinishModal: false,
      questions: [],
      isLoading: true
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
  async mounted() {
    try {
      // Fetch dynamic questions from the API
      const dbQuestions = await api.getQuizQuestions(15); // Ambil 15 soal
      this.questions = dbQuestions.map(q => ({
        id: q.id,
        subtest: q.sub_materi,
        text: q.question,
        options: [
          { id: 'A', text: q.option_a },
          { id: 'B', text: q.option_b },
          { id: 'C', text: q.option_c },
          { id: 'D', text: q.option_d },
          { id: 'E', text: q.option_e }
        ]
      }));
    } catch (e) {
      console.error('Failed to load questions', e);
      // Fallback jika API gagal (biar ga nge-blank)
      this.questions = [
        {
          id: 1, subtest: 'Error', text: '<p>Gagal memuat soal dari database. Pastikan backend berjalan dan database telah di-seed.</p>',
          options: [{id: 'A', text: 'Kembali'}, {id: 'B', text: 'Coba Lagi'}]
        }
      ];
    } finally {
      this.isLoading = false;
      this.answers = new Array(this.questions.length).fill(null);
      this.doubt = new Array(this.questions.length).fill(false);
      this.startTimer();
    }
  },
  beforeUnmount() {
    clearInterval(this.timerInterval);
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
    finishExam() {
      this.showFinishModal = true;
    },
    submitExam() {
      this.showFinishModal = false;
      clearInterval(this.timerInterval);
      alert("Ujian Selesai! Terima kasih.\n(Demo UI Try Out SNPMB)");
      this.$router.push('/');
    },
    autoSubmit() {
      alert("Waktu habis! Jawaban Anda telah otomatis tersimpan.");
      this.$router.push('/');
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
