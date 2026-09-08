# CONTEXT BUNDLE UNTUK ANALISIS AI (COMPREHENSIVE CODE REVIEW)

Dokumen ini berisi petunjuk analisis dan seluruh kode sumber (codebase) terbaru dari sistem **EduPath - Bimbel & Personalized Learning Platform** berbasis Vue 3 + Vite + Tailwind CSS. Seluruh UI sistem ini, terutama Landing Page utama, telah diselaraskan dengan standar estetika premium dari `SAMPLE.HTML`, mengikuti cetak biru (blueprint) struktur ideal 14 bagian yang diminta, menggunakan tata letak asimetris yang dinamis (asymmetrical fluid grids, Bento box, connected timeline paths, dan glow blob decoration) serta keterbacaan ukuran font yang ditingkatkan (body text minimal `text-sm` hingga `text-base` / 14-16px) untuk kenyamanan aksesibilitas orang tua.

Anda dapat mengunggah dokumen ini atau menyalin isinya ke Claude, ChatGPT, atau model AI lainnya sebagai opini kedua (2nd opinion) untuk mendapatkan review sistem secara komprehensif.

---

## 🎯 INSTRUKSI UNTUK AI (PROMPT ANALISIS)
> **Salin teks di bawah ini sebagai prompt ketika Anda memasukkan dokumen ini ke AI lain:**
>
> "Saya melampirkan seluruh kode sumber aplikasi e-learning/bimbel adaptif saya yang bernama **EduPath**. Aplikasi ini dibuat menggunakan Vue 3 (single-file component), Tailwind CSS dengan kustomisasi tema premium (glassmorphism/dark mode), sistem simulasi Item Response Theory (IRT) adaptif sederhana, serta proyeksi materi UTBK HOTS 2027.
>
> Tolong berikan analisis komprehensif (2nd opinion) mengenai:
> 1. **Arsitektur & Kualitas Kode**: Evaluasi struktur penulisan Vue 3 (`setup()`, reactivity, state management mock di `EduData.js`). Bagaimana skalabilitas kode ini jika ingin dihubungkan ke real backend API?
> 2. **Algoritma & Logika (IRT & Adaptive Testing)**: Analisis logika penskalaan IRT & diagnostic test di `App.vue` (`submitDiagAnswer`, `finishDiagnostic`). Apakah ada saran optimasi matematis/logika agar estimasi kemampuan siswa lebih akurat?
> 3. **UI/UX & Desain**: Tinjau layouting Tailwind CSS (penggunaan CSS Variables, flexbox/grid, responsive design, glassmorphic card). Apa saja aspek visual dan aksesibilitas yang bisa ditingkatkan?
> 4. **Fitur AI Tutor & SOS Escalation**: Berikan rekomendasi bagaimana cara mengimplementasikan chat AI Tutor & SOS Tutor manusia secara riil menggunakan WebSockets atau streaming API (misalnya menggunakan Gemini SDK / OpenAI API).
> 5. **Rekomendasi Refactoring**: Tuliskan saran langkah-demi-langkah (step-by-step) untuk memecah `App.vue` yang berukuran besar (>1100 baris) menjadi komponen-komponen Vue modular (misal: `DashboardTab.vue`, `DiagnosticTab.vue`, `AiTutorTab.vue`, dll.)."

---

## 📂 STRUKTUR FOLDER PROYEK
```text
/7 BIMBEL
├── package.json
├── index.html
├── start-local.bat
└── /src
    ├── main.js
    ├── index.css
    ├── EduData.js
    └── App.vue
```

---

## 📄 KODE SUMBER LENGKAP (SOURCE CODE)

### 1. `package.json`
```json
{
  "name": "7 BIMBEL",
  "version": "0.0.0",
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "dependencies": {
    "@lucide/vue": "^1.34.0",
    "lucide-vue-next": "^1.0.0",
    "vue": "^3.0.4"
  },
  "devDependencies": {
    "@vue/compiler-sfc": "^3.0.4",
    "vite": "^1.0.0-rc.13"
  }
}
```

---

### 2. `src/main.js`
```javascript
import { createApp } from 'vue'
import App from './App.vue'
import './index.css'

createApp(App).mount('#app')
```

---

### 3. `src/index.css`
```css
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

:root {
  --bg-primary: #030712;
  --bg-secondary: #111827;
  --primary: #0ea5e9;
  --secondary: #8b5cf6;
  --accent: #f43f5e;
}

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background-color: #030712;
  color: #f8fafc;
  overflow-x: hidden;
}

h1, h2, h3, h4, h5, h6 {
  font-family: 'Space Grotesk', sans-serif;
}

/* Ambient mouse tracker */
#ambient-glow {
  position: fixed;
  top: 0;
  left: 0;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, rgba(14, 165, 233, 0.05) 40%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
  transform: translate(-50%, -50%);
  z-index: 0;
  transition: opacity 0.5s ease;
}

/* Background Grid Pattern */
.bg-grid {
  background-size: 40px 40px;
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
}

/* Glassmorphism khusus Dark Mode */
.glass-dark {
  background: rgba(17, 24, 39, 0.6);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.glass-card {
  background: linear-gradient(145deg, rgba(31, 41, 55, 0.7) 0%, rgba(17, 24, 39, 0.9) 100%);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.glass-card:hover {
  transform: translateY(-4px);
  border-color: rgba(14, 165, 233, 0.3);
  box-shadow: 0 30px 60px -12px rgba(14, 165, 233, 0.15);
}

/* Text Gradients */
.text-gradient-cyan {
  background: linear-gradient(to right, #38bdf8, #0ea5e9);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.text-gradient-purple {
  background: linear-gradient(to right, #a78bfa, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Shimmer Button */
.btn-shimmer {
  background: linear-gradient(90deg, #0ea5e9, #8b5cf6, #0ea5e9);
  background-size: 200% auto;
  color: white;
  transition: 0.5s;
}
.btn-shimmer:hover {
  background-position: right center;
}

/* Marquee Animation */
@keyframes marquee {
  0% { transform: translateX(0%); }
  100% { transform: translateX(-50%); }
}

.animate-marquee {
  animation: marquee 25s linear infinite;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: #030712;
}
::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 99px;
}
::-webkit-scrollbar-thumb:hover {
  background: #0ea5e9;
}
```

---

### 4. `src/EduData.js`
```javascript
export const SKILL_MAP = {
  TPS: {
    'Penalaran Umum': ['Penalaran Induktif', 'Penalaran Deduktif', 'Kesesuaian Pernyataan'],
    'Pengetahuan Kuantitatif': ['Aljabar Dasar', 'Aritmetika Sosial', 'Statistika Peluang'],
    'PPU & PBM': ['Gagasan Utama', 'Kalimat Efektif', 'Ejaan & Konjungsi']
  },
  Literasi: {
    'Bahasa Indonesia': ['Mengevaluasi Argumen', 'Memahami Teks Akademik', 'Logika Paragraf'],
    'Bahasa Inggris': ['Main Idea & Purpose', 'Vocabulary In Context', 'Implicit Information'],
    'Penalaran Matematika': ['Aplikasi Geometri', 'Model SPLDV', 'Analisis Data']
  }
};

export const TARGET_UNIVERSITIES = [
  { name: 'Kedokteran UI', targetScore: 720, reqAbility: { 'Penalaran Umum': 85, 'Pengetahuan Kuantitatif': 90, 'Bahasa Indonesia': 85, 'Bahasa Inggris': 80 } },
  { name: 'Bisnis ITB', targetScore: 700, reqAbility: { 'Penalaran Umum': 80, 'Pengetahuan Kuantitatif': 85, 'Bahasa Indonesia': 80, 'Bahasa Inggris': 85 } },
  { name: 'Aktuaria UGM', targetScore: 710, reqAbility: { 'Penalaran Umum': 85, 'Pengetahuan Kuantitatif': 92, 'Bahasa Indonesia': 75, 'Bahasa Inggris': 80 } }
];

export const MICRO_LESSONS = [
  {
    id: 'l1',
    subject: 'TPS',
    topic: 'Pengetahuan Kuantitatif',
    title: 'Trik Faktorisasi Cepat Persamaan Kuadrat',
    duration: '3 Menit',
    type: 'Flashcard & Trik',
    videoPlaceholder: 'https://images.unsplash.com/photo-1453733190148-c44698c26588?w=600&auto=format&fit=crop&q=60',
    summary: 'Jika a = 1 pada ax² + bx + c = 0, cari p dan q dengan syarat p * q = c dan p + q = b. Maka faktornya adalah (x + p)(x + q).',
    quiz: {
      question: 'Jika faktor dari suatu persamaan kuadrat adalah (x - 3)(x + 2) = 0, persamaannya adalah...',
      options: ['x² - x - 6 = 0', 'x² + x - 6 = 0', 'x² - 5x + 6 = 0', 'x² + 5x - 6 = 0'],
      answer: 0,
      hint: 'Gunakan perkalian distributif.'
    }
  },
  {
    id: 'l2',
    subject: 'Literasi',
    topic: 'Bahasa Inggris',
    title: 'Reading Comprehension: Finding the Main Idea',
    duration: '5 Menit',
    type: 'Visual Explanation',
    videoPlaceholder: 'https://images.unsplash.com/photo-1509228468518-180dd4864904?w=600&auto=format&fit=crop&q=60',
    summary: 'Gagasan utama (main idea) teks bahasa Inggris akademik biasanya terletak di paragraf pertama (thesis statement) atau kalimat pertama setiap paragraf.',
    quiz: {
      question: 'Where can you usually locate the main thesis statement in an academic essay?',
      options: ['In the introduction paragraph', 'In the middle of the body paragraphs', 'At the end of the bibliography', 'Only in the title of the essay'],
      answer: 0,
      hint: 'Look at the introductory section of the text.'
    }
  },
  {
    id: 'l3',
    subject: 'TPS',
    topic: 'Penalaran Umum',
    title: 'Logika Silogisme: Penalaran Deduktif',
    duration: '4 Menit',
    type: 'Video & Visual',
    videoPlaceholder: 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=600&auto=format&fit=crop&q=60',
    summary: 'Jika Premis 1: p -> q, dan Premis 2: q -> r, maka kesimpulannya adalah p -> r (Modus Silogisme).',
    quiz: {
      question: 'Semua mahasiswa rajin belajar. Sebagian mahasiswa menyukai olahraga. Kesimpulan yang sah adalah...',
      options: ['Sebagian mahasiswa yang rajin belajar menyukai olahraga', 'Semua mahasiswa yang menyukai olahraga tidak rajin belajar', 'Semua olahragawan adalah mahasiswa', 'Tidak ada mahasiswa yang tidak menyukai olahraga'],
      answer: 0,
      hint: 'Irisan antara himpunan mahasiswa rajin belajar dengan mahasiswa penyuka olahraga.'
    }
  }
];

export const DIAGNOSTIC_QUESTIONS = [
  {
    id: 'q1',
    subject: 'TPS',
    category: 'Pengetahuan Kuantitatif',
    skill: 'Aljabar Dasar',
    difficulty: 'Intermediate',
    question: 'Jika salah satu akar dari x² - px + 12 = 0 adalah 3, berapakah nilai p?',
    options: ['4', '5', '7', '8'],
    answer: 2,
    hint: 'Substitusikan nilai x = 3 ke dalam persamaan x² - px + 12 = 0.',
    concept: 'Akar dari suatu persamaan kuadrat memenuhi persamaan tersebut jika disubstitusikan.',
    guidedSteps: [
      'Langkah 1: Masukkan x = 3 ke persamaan. Didapat: 3² - p(3) + 12 = 0',
      'Langkah 2: Sederhanakan: 9 - 3p + 12 = 0',
      'Langkah 3: Jumlahkan angka konstan: 21 - 3p = 0',
      'Langkah 4: Selesaikan untuk p: 3p = 21, maka p = 7.'
    ],
    explanation: 'Substitusi x = 3: 3² - p(3) + 12 = 0 => 9 - 3p + 12 = 0 => 21 = 3p => p = 7.'
  },
  {
    id: 'q2',
    subject: 'TPS',
    category: 'Penalaran Umum',
    skill: 'Penalaran Deduktif',
    difficulty: 'Intermediate',
    question: 'Semua unggas bertelur. Sebagian unggas tidak dapat terbang. Kesimpulan yang paling tepat adalah...',
    options: [
      'Semua hewan yang bertelur tidak dapat terbang',
      'Sebagian hewan yang bertelur tidak dapat terbang',
      'Semua unggas yang dapat terbang tidak bertelur',
      'Sebagian unggas yang bertelur dapat terbang saja'
    ],
    answer: 1,
    hint: 'Unggas adalah bagian dari hewan yang bertelur. Sebagian unggas ini tidak bisa terbang.',
    concept: 'Silogisme himpunan bagian (subset) logika.',
    guidedSteps: [
      'Langkah 1: Tahu bahwa semua unggas adalah bagian dari kelompok hewan bertelur.',
      'Langkah 2: Diketahui sebagian unggas tidak dapat terbang.',
      'Langkah 3: Maka otomatis sebagian dari kelompok bertelur (yaitu unggas tersebut) juga tidak dapat terbang.'
    ],
    explanation: 'Karena semua unggas bertelur, maka sebagian unggas yang tidak dapat terbang merupakan kelompok hewan bertelur yang tidak dapat terbang.'
  },
  {
    id: 'q3',
    subject: 'Literasi',
    category: 'Bahasa Indonesia',
    skill: 'Mengevaluasi Argumen',
    difficulty: 'HOTS',
    question: 'Bacalah kalimat berikut: "Penggunaan plastik sekali pakai harus segera dihentikan demi menjaga kelangsungan ekosistem laut." Argumen yang memperlemah pernyataan tersebut adalah...',
    options: [
      'Plastik merupakan limbah padat terbesar yang mencemari wilayah pesisir.',
      'Banyak biota laut yang mati akibat tidak sengaja memakan sampah plastik.',
      'Plastik sekali pakai memiliki kontribusi ekonomi yang tinggi bagi pelaku UMKM pengemas makanan.',
      'Kampanye pengurangan plastik terbukti meningkatkan kesadaran lingkungan siswa sekolah dasar.'
    ],
    answer: 2,
    hint: 'Cari argumen bertentangan atau dampak negatif dari penghentian penggunaan plastik.',
    concept: 'Mengidentifikasi premis argumen kontra (pelemah gagasan).',
    guidedSteps: [
      'Langkah 1: Analisis klaim utama: Penghentian plastik demi laut.',
      'Langkah 2: Cari opsi yang menunjukkan sisi kontra, hambatan ekonomi, atau kelemahan dari id pelarangan tersebut.',
      'Langkah 3: Pilihan 3 menyoroti dampak buruk dari sisi ekonomi UMKM, yang melemahkan usulan penghentian plastik secara mutlak.'
    ],
    explanation: 'Pernyataan tentang dampak ekonomi plastik bagi UMKM memberikan sudut pandang kontra yang mempersulit pelarangan, sehingga memperlemah argumen utama.'
  },
  {
    id: 'q4',
    subject: 'Literasi',
    category: 'Bahasa Inggris',
    skill: 'Main Idea & Purpose',
    difficulty: 'Intermediate',
    question: 'Read the text excerpt: "Artificial Intelligence (AI) has shown remarkable performance in medical diagnostics. However, issues regarding patient data privacy and bias in training datasets remain unsolved." What is the main concern of the author?',
    options: [
      'AI is fully ready to replace doctors.',
      'The potential ethical and technical challenges of using AI in diagnostics.',
      'The cost of implementing AI software in hospitals.',
      'A complete guide to secure medical data servers.'
    ],
    answer: 1,
    hint: 'Look at the word "However" which shifts focus to the challenges.',
    concept: 'Understanding transitions and contrast markers in paragraph themes.',
    guidedSteps: [
      'Langkah 1: Kalimat pertama memuji AI medis.',
      'Langkah 2: Kata hubung kontras "However" mengindikasikan munculnya masalah utama (privasi & bias).',
      'Langkah 3: Simpulkan bahwa penulis menyoroti tantangan (challenges) tersebut.'
    ],
    explanation: 'The transition word "However" indicates a shift towards the unresolved problems/challenges of data privacy and bias, which forms the main focus.'
  }
];

export const generateFull150Questions = () => {
  const categories = {
    PU: { subject: 'TPS', name: 'Penalaran Umum', total: 30, baseQuestion: 'Dalam sebuah keluarga, jika ayah pergi maka ibu memasak. Jika ibu memasak maka anak makan nasi.' },
    PK: { subject: 'TPS', name: 'Pengetahuan Kuantitatif', total: 20, baseQuestion: 'Berapakah nilai dari x jika diketahui 3x + y = 15 dan y adalah konstanta prima terkecil?' },
    LIndo: { subject: 'Literasi', name: 'Bahasa Indonesia', total: 30, baseQuestion: 'Pemerintah menetapkan aturan zonasi sekolah. Kalimat yang mengandung ejaan salah pada wacana tersebut adalah...' },
    LEng: { subject: 'Literasi', name: 'Bahasa Inggris', total: 20, baseQuestion: 'Based on paragraph 3, what can be inferred about the correlation between greenhouse gas emissions and agricultural productivity?' },
    PM: { subject: 'Literasi', name: 'Penalaran Matematika', total: 30, baseQuestion: 'Sebuah tangki air berbentuk silinder memiliki jari-jari r cm dan tinggi h cm. Berapakah volume air jika tangki terisi 3/4 bagian?' }
  };

  const list = {};
  Object.keys(categories).forEach(key => {
    const info = categories[key];
    list[key] = Array.from({ length: info.total }, (_, idx) => {
      const num = idx + 1;
      return {
        id: `${key}_${num}`,
        num: num,
        subject: info.subject,
        category: info.name,
        question: `[Soal Nomor ${num}] ${info.baseQuestion} (Pertanyaan simulasi variasi UTBK 2027 kode paket-${key}${100 + num})`,
        options: [
          `Pilihan A - Jawaban alternatif representasi ${num}`,
          `Pilihan B - Kunci jawaban optimal (Benar)`,
          `Pilihan C - Distraktor tingkat kesulitan tinggi`,
          `Pilihan D - Pembahasan prasyarat`
        ],
        answer: 1, // B is correct
        hint: `Fokus pada premis logika transisi nomor ${num}.`,
        concept: `Gunakan formula eliminasi silogisme/rumus dasar subtes ${key}.`,
        guidedSteps: [
          `Langkah 1: Identifikasi variabel utama pada nomor ${num}.`,
          `Langkah 2: Sederhanakan persamaan atau analisis struktur ejaan kalimat.`,
          `Langkah 3: Pilih opsi B sebagai penyelesaian paling logis.`
        ],
        explanation: `Melalui tahapan analisis teoretis subtes ${info.name}, didapat pilihan B bernilai benar secara mutlak.`
      };
    });
  });
  return list;
};

export const SIMULATOR_DATABASE = generateFull150Questions();
```

---

### 5. `src/App.vue`
```vue
<template>
  <div class="relative min-h-screen bg-[#030712] text-slate-200 antialiased font-body flex overflow-hidden">
    <!-- Ambient Glow & Grid -->
    <div id="ambient-glow" ref="ambientGlowRef" class="opacity-80"></div>
    <div class="fixed inset-0 bg-grid pointer-events-none z-0"></div>
    
    <!-- Background Decorative Blobs for Depth and Fluidity -->
    <div class="fixed top-[-10%] right-[-5%] w-[45vw] h-[45vw] rounded-full bg-secondary/8 blur-[130px] mix-blend-screen pointer-events-none z-0"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[55vw] h-[55vw] rounded-full bg-primary/6 blur-[130px] mix-blend-screen pointer-events-none z-0"></div>
    <div class="fixed top-[40%] left-[30%] w-[35vw] h-[35vw] rounded-full bg-accent/3 blur-[140px] mix-blend-screen pointer-events-none z-0"></div>

    <!-- Sidebar Navigation (Only visible when Logged In) -->
    <aside v-if="isLoggedIn" class="relative z-10 w-72 bg-slate-955/60 border-r border-slate-800/80 p-6 flex flex-col justify-between backdrop-blur-xl shrink-0">
      <div>
        <div class="flex items-center gap-3 mb-2 group cursor-pointer" @click="currentTab = 'home'">
          <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-center group-hover:border-primary transition-all">
            <i class="ph-fill ph-lightning text-xl text-primary group-hover:animate-pulse"></i>
          </div>
          <span class="font-heading font-bold text-2xl tracking-tight text-white">EduPath<span class="text-primary">.ai</span></span>
        </div>
        <p class="text-sm text-slate-500 font-light mb-8">Personalized Learning Platform</p>
        
        <nav class="flex flex-col gap-2">
          <button 
            v-for="tab in tabs" 
            :key="tab.id" 
            :class="['flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all text-left', 
              currentTab === tab.id 
                ? 'bg-slate-800/60 border border-slate-700/60 text-white shadow-lg shadow-black/20' 
                : 'text-slate-400 hover:text-white hover:bg-slate-900/40 border border-transparent'
            ]"
            @click="handleTabClick(tab.id)"
          >
            <span class="text-lg text-primary">{{ tab.icon }}</span>
            <span class="font-heading">{{ tab.name }}</span>
          </button>
        </nav>
      </div>

      <!-- User Profile & Log In / Log Out Section -->
      <div class="glass-dark border border-slate-805 p-4 rounded-2xl flex flex-col gap-3">
        <div class="flex items-center gap-3">
          <div class="text-2xl w-10 h-10 rounded-xl bg-slate-900/80 flex items-center justify-center border border-slate-800">👦</div>
          <div class="flex-grow">
            <h4 class="text-sm font-bold text-white">Siswa Mandiri</h4>
            <span class="text-xs text-gradient-purple font-extrabold uppercase tracking-wider">Premium + SOS</span>
          </div>
        </div>
        <button @click="logout" class="w-full py-2.5 bg-rose-950/20 hover:bg-rose-955/40 border border-rose-900/30 text-rose-455 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1.5">
          <i class="ph ph-sign-out text-sm"></i> Keluar Akun
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="relative z-10 flex-grow overflow-y-auto max-h-screen flex flex-col" :class="isLoggedIn ? 'p-8' : ''">
      
      <!-- Public Top Navbar (Only visible when Logged Out) -->
      <header v-if="!isLoggedIn" class="w-full max-w-6xl mx-auto flex justify-between items-center py-6 px-8 border-b border-slate-900/60 shrink-0 z-30">
        <div class="flex items-center gap-3 group cursor-pointer" @click="currentTab = 'home'">
          <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-center group-hover:border-primary transition-all">
            <i class="ph-fill ph-lightning text-xl text-primary group-hover:animate-pulse"></i>
          </div>
          <span class="font-heading font-bold text-2xl tracking-tight text-white">EduPath<span class="text-primary">.ai</span></span>
        </div>

        <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300">
          <button @click="scrollToSection('problems')" class="hover:text-primary transition-colors">Masalah</button>
          <button @click="scrollToSection('features')" class="hover:text-primary transition-colors">Fitur</button>
          <button @click="scrollToSection('testimonials')" class="hover:text-primary transition-colors">Testimoni</button>
          <button @click="scrollToSection('pricing')" class="hover:text-primary transition-colors">Harga</button>
          <button @click="scrollToSection('faq')" class="hover:text-primary transition-colors">FAQ</button>
        </nav>

        <div class="flex items-center gap-4">
          <button @click="showLoginModal = true" class="px-6 py-2.5 rounded-xl text-sm font-bold border border-slate-700 bg-slate-900/40 hover:bg-slate-800 transition-all text-white">
            Masuk Akun
          </button>
        </div>
      </header>

      <!-- App Header Utility (Only visible when Logged In) -->
      <header v-if="isLoggedIn" class="flex justify-between items-center pb-6 border-b border-slate-800/60 mb-8 shrink-0">
        <div class="flex items-center gap-3">
          <label class="text-sm text-slate-400 font-semibold uppercase tracking-wider">Target PTN:</label>
          <div class="relative">
            <select v-model="selectedUniversity" class="bg-slate-900/80 border border-slate-800 text-white text-sm font-medium py-2 px-4 pr-8 rounded-xl appearance-none outline-none focus:border-primary transition-colors cursor-pointer" @change="recalcTargetGap">
              <option v-for="u in universities" :key="u.name" :value="u">
                {{ u.name }} (Target: {{ u.targetScore }})
              </option>
            </select>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500">
              <i class="ph ph-caret-down"></i>
            </div>
          </div>
        </div>

        <div class="flex gap-4">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-805 bg-slate-900/50 backdrop-blur-md text-sm font-semibold text-amber-400">
            🔥 <span>{{ streakCount }} Hari Streak</span>
          </div>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-805 bg-slate-900/50 backdrop-blur-md text-sm font-semibold text-primary">
            💎 <span>{{ coins }} Coins</span>
          </div>
        </div>
      </header>

      <!-- Main Panel content wrap -->
      <div class="flex-grow">
        <!-- TAB 0: LANDING PAGE -->
        <section v-if="currentTab === 'home'" class="animate-fade-in space-y-36 py-12 px-6 max-w-6xl mx-auto overflow-visible">
          
          <!-- 1. Hero Section -->
          <div class="text-center space-y-6 max-w-4xl mx-auto pt-6 relative">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-800/80 bg-slate-900/40 backdrop-blur-md text-slate-300 font-medium text-sm mb-4">
              <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-secondary"></span>
              </span>
              Sistem AI Evaluasi Terbaru 2026 Telah Aktif
            </div>

            <h1 class="text-5xl md:text-7xl font-bold font-heading leading-tight text-white tracking-tight">
              Tembus PTN Impian dengan <br />
              <span class="text-gradient-cyan">{{ rotatingWords[currentWordIdx] }}</span>
            </h1>

            <p class="text-xl md:text-2xl text-slate-350 max-w-3xl mx-auto font-light leading-relaxed">
              Skor UTBK 700+ untuk Pejuang PTN tanpa Belajar SKS (Sistem Kebut Semalam) &amp; Menghafal Ribuan Rumus secara Buta.
            </p>

            <p class="text-sm md:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
              Platform bimbel adaptif pertama di Indonesia yang mendeteksi kelemahan belajarmu secara instan dan menyusun kurikulum personal berbasis AI.
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4 pt-4">
              <button class="btn-shimmer px-8 py-4.5 rounded-full font-bold text-base w-full sm:w-auto shadow-[0_0_15px_rgba(14,165,233,0.3)] hover:shadow-[0_0_25px_rgba(14,165,233,0.5)] transform hover:scale-105 transition-all flex items-center justify-center gap-2" @click="startLearning">
                Mulai Belajar Sekarang (Gratis)
                <i class="ph-bold ph-arrow-right text-lg"></i>
              </button>
              <button class="px-8 py-4.5 rounded-full font-semibold text-base w-full sm:w-auto border border-slate-700 bg-surface/50 hover:bg-slate-800 hover:border-slate-655 transition-all text-white flex items-center justify-center gap-2" @click="scrollToSection('preview')">
                <i class="ph ph-play-circle text-xl"></i> Lihat Demo Interaktif
              </button>
            </div>

            <div class="text-xs md:text-sm text-slate-500 pt-2 font-medium">
              🛡️ Dipercaya oleh 10,000+ Siswa SMA &amp; Pejuang Gap Year seluruh Indonesia.
            </div>
          </div>

          <!-- 2. Trust Bar -->
          <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-6 py-8 border-y border-slate-900/60 text-center max-w-5xl mx-auto">
            <div class="space-y-1">
              <span class="block text-3xl md:text-4xl font-extrabold text-white font-heading">10,000+</span>
              <span class="text-xs md:text-sm text-slate-400 uppercase tracking-widest font-bold">Pejuang PTN</span>
            </div>
            <div class="text-slate-805 text-xl hidden md:block">•</div>
            <div class="space-y-1">
              <span class="block text-3xl md:text-4xl font-extrabold text-primary font-heading">87.4%</span>
              <span class="text-xs md:text-sm text-slate-400 uppercase tracking-widest font-bold">Tingkat Kelulusan</span>
            </div>
            <div class="text-slate-805 text-xl hidden md:block">•</div>
            <div class="space-y-1">
              <span class="block text-3xl md:text-4xl font-extrabold text-secondary font-heading">150k+</span>
              <span class="text-xs md:text-sm text-slate-400 uppercase tracking-widest font-bold">Soal Dikerjakan</span>
            </div>
            <div class="text-slate-805 text-xl hidden md:block">•</div>
            <div class="space-y-1">
              <span class="block text-3xl md:text-4xl font-extrabold text-emerald-450 font-heading">9.8/10</span>
              <span class="text-xs md:text-sm text-slate-400 uppercase tracking-widest font-bold">Rating Kepuasan</span>
            </div>
          </div>

          <!-- 3. Problem / Pain Point -->
          <div id="problems" class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-center scroll-mt-24">
            <div class="lg:col-span-2 space-y-5">
              <span class="text-rose-500 text-sm font-bold uppercase tracking-widest block">MASALAH KLASIK</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white leading-tight">
                Mengapa 90% Siswa Gagal Ujian?
              </h2>
              <p class="text-base md:text-lg text-slate-350 leading-relaxed font-light">
                Mengerjakan ribuan soal tanpa mengetahui di mana letak kelemahan terbesarmu hanyalah membuang waktu secara sia-sia.
              </p>
              <div class="w-20 h-1 bg-gradient-to-r from-rose-500 to-transparent rounded-full"></div>
            </div>

            <div class="lg:col-span-3 space-y-8 relative">
              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center font-heading font-bold text-sm text-rose-400 shrink-0 group-hover:bg-rose-500/25 transition-all">01</div>
                <div>
                  <h3 class="text-lg md:text-xl font-bold text-white mb-1.5">Blind Spot Belajar</h3>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-light">Belajar acak tanpa menyadari materi esensial apa yang menahan kenaikan skor Anda secara konstan.</p>
                </div>
              </div>
              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center font-heading font-bold text-sm text-rose-400 shrink-0 group-hover:bg-rose-500/25 transition-all">02</div>
                <div>
                  <h3 class="text-lg md:text-xl font-bold text-white mb-1.5">Hafalan Buta Rumus</h3>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-light">Menghafal rumus-rumus mati matematika tanpa pernah melatih logika dasar untuk menyelesaikan soal variasi baru (HOTS).</p>
                </div>
              </div>
              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center font-heading font-bold text-sm text-rose-455 shrink-0 group-hover:bg-rose-500/25 transition-all">03</div>
                <div>
                  <h3 class="text-lg md:text-xl font-bold text-white mb-1.5">Skor Tryout Mandek</h3>
                  <p class="text-sm md:text-base text-slate-300 leading-relaxed font-light">Mengulang latihan yang terlalu mudah atau terlampau rumit tanpa adanya kalibrasi model IRT terkomputerisasi.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Solution & Value Proposition -->
          <div class="relative py-12">
            <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-secondary/10 blur-3xl opacity-30 rounded-3xl"></div>
            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
              <div class="space-y-6">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary/15 border border-primary/20 text-xs font-bold text-primary rounded-md uppercase tracking-wider">
                  ⚡ SOLUSI ADAPTIF
                </div>
                <h3 class="text-3xl md:text-5xl font-bold font-heading text-white leading-tight">Persiapan Terukur Dengan Analitik Presisi</h3>
                <p class="text-sm md:text-base text-slate-355 leading-relaxed font-light">
                  EduPath mendeteksi kelemahan spesifik Anda dalam waktu 10 menit, membuat jalur belajar khusus, dan memandu Anda menyelesaikannya materi per materi.
                </p>
                
                <ul class="space-y-3 pt-2 text-sm md:text-base text-slate-300 font-light">
                  <li class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                    Sistem evaluasi berbasis model IRT SNBT Riil
                  </li>
                  <li class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                    Rekomendasi prioritas materi otomatis
                  </li>
                </ul>
              </div>

              <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-secondary to-primary rounded-2xl blur opacity-30"></div>
                <div class="relative bg-slate-900 border border-slate-800 p-8 rounded-2xl space-y-6">
                  <div class="flex justify-between items-center text-xs md:text-sm">
                    <span class="text-slate-450 font-bold tracking-wider">ALUR DIAGNOSIS AI</span>
                    <span class="text-primary font-bold">AKTIF</span>
                  </div>
                  <div class="space-y-3.5">
                    <div class="flex justify-between text-xs md:text-sm">
                      <span class="text-slate-300 font-medium">Kemampuan Aljabar Dasar</span>
                      <span class="text-emerald-450 font-bold">85% Sukses</span>
                    </div>
                    <div class="w-full bg-slate-955 rounded-full h-3">
                      <div class="bg-emerald-400 h-3 rounded-full w-[85%]"></div>
                    </div>
                  </div>
                  <div class="space-y-3.5">
                    <div class="flex justify-between text-xs md:text-sm">
                      <span class="text-slate-300 font-medium">Bangun Ruang Geometri</span>
                      <span class="text-rose-455 font-bold">35% Kritis</span>
                    </div>
                    <div class="w-full bg-slate-955 rounded-full h-3">
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

          <!-- 5. Core Benefits -->
          <div id="benefits" class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-center scroll-mt-24">
            <div class="lg:col-span-3 space-y-8">
              <div class="space-y-2">
                <span class="text-secondary text-sm font-bold uppercase tracking-widest block">MANFAAT PLATFORM</span>
                <h3 class="text-3xl md:text-4xl font-bold font-heading text-white">Efisiensi Belajar Level Tertinggi</h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 pt-4">
                <div class="space-y-3">
                  <span class="text-2xl">⏱️</span>
                  <h4 class="text-base md:text-lg font-bold text-white">Hemat Waktu Belajar</h4>
                  <p class="text-xs md:text-sm text-slate-350 leading-relaxed font-light">Eliminasi materi yang sudah dikuasai. Hanya pelajari materi kritis yang menahan kenaikan skor Anda.</p>
                </div>
                <div class="space-y-3">
                  <span class="text-2xl">🎯</span>
                  <h4 class="text-base md:text-lg font-bold text-white">Simulasi Penilaian IRT</h4>
                  <p class="text-xs md:text-sm text-slate-350 leading-relaxed font-light">Setiap tryout memberikan konversi persentil kelulusan yang riil sesuai bobot skor SNBT terbaru.</p>
                </div>
                <div class="space-y-3">
                  <span class="text-2xl">🤖</span>
                  <h4 class="text-base md:text-lg font-bold text-white">AI Tutor Siaga 24 Jam</h4>
                  <p class="text-xs md:text-sm text-slate-350 leading-relaxed font-light">Klinik tugas aktif setiap saat yang menuntun logika berpikir Anda langkah demi langkah.</p>
                </div>
                <div class="space-y-3">
                  <span class="text-2xl">📈</span>
                  <h4 class="text-base md:text-lg font-bold text-white">Grafik Kenaikan Riil</h4>
                  <p class="text-xs md:text-sm text-slate-350 leading-relaxed font-light">Pantau secara riil proyeksi peluang Anda menembus universitas target pilihan secara visual.</p>
                </div>
              </div>
            </div>

            <div class="lg:col-span-2 bg-gradient-to-b from-slate-900 to-slate-955 p-8 rounded-3xl border border-slate-800 relative">
              <div class="absolute top-2 right-2 w-32 h-32 bg-secondary/10 blur-[40px] rounded-full"></div>
              <h4 class="text-xs md:text-sm font-bold text-white uppercase tracking-wider mb-6">EduRank Leaderboard</h4>
              
              <div class="space-y-5">
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 rounded bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-xs">1</span>
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
                  <span class="text-xs md:text-sm text-primary font-bold">648 Poin</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 6. Bento Grid Feature Showcase -->
          <div id="features" class="space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-primary text-sm font-bold uppercase tracking-widest block">ETALASE FITUR</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Alat Tempur Terlengkap</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 auto-rows-[270px] max-w-5xl mx-auto">
              
              <!-- Feature 1: Adaptive Learning -->
              <div class="glass-card rounded-3xl p-8 col-span-1 md:col-span-2 lg:col-span-2 row-span-2 flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/10 blur-[80px] rounded-full group-hover:bg-primary/20 transition-all duration-700"></div>
                
                <div class="space-y-4 z-10">
                  <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary border border-primary/20">
                    <i class="ph-bold ph-cpu text-2xl"></i>
                  </div>
                  <h3 class="text-2xl font-bold text-white font-heading">AI Adaptive Assessment™</h3>
                  <p class="text-sm text-slate-350 leading-relaxed font-light">
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
                    <h3 class="text-lg md:text-xl font-bold font-heading text-white">Micro-Lessons Library</h3>
                    <span class="text-xs bg-secondary/25 text-secondary px-2.5 py-1 rounded font-bold uppercase tracking-wider">📚 5 Menit</span>
                  </div>
                  <p class="text-xs md:text-sm text-slate-350 leading-relaxed font-light">
                    Koleksi modul video ringkas terfokus durasi 3-7 menit yang langsung mengupas trik penyelesaian rumus cepat dan eliminasi pilihan jawaban.
                  </p>
                </div>
                <div class="flex -space-x-3 mt-4">
                  <span class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-955 flex items-center justify-center text-xs text-slate-300 font-bold">PU</span>
                  <span class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-955 flex items-center justify-center text-xs text-slate-300 font-bold">PK</span>
                  <span class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-955 flex items-center justify-center text-xs text-slate-300 font-bold">PM</span>
                </div>
              </div>

              <!-- Feature 3: AI Tutor -->
              <div class="glass-card rounded-3xl p-6 col-span-1 flex flex-col justify-between group">
                <div class="w-12 h-12 rounded-xl bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-lg">
                  🤖
                </div>
                <div>
                  <h3 class="text-base md:text-lg font-bold text-white font-heading mb-1">AI Companion</h3>
                  <p class="text-xs text-slate-355 font-light leading-relaxed">Asisten interaktif pembongkar kerumitan langkah pengerjaan.</p>
                </div>
              </div>

              <!-- Feature 4: Virtual Study Room -->
              <div class="glass-card rounded-3xl p-6 col-span-1 flex flex-col justify-between group">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-lg">
                  ⏳
                </div>
                <div>
                  <h3 class="text-base md:text-lg font-bold text-white font-heading mb-1">Pomodoro Lofi</h3>
                  <p class="text-xs text-slate-355 font-light leading-relaxed">Ruang belajar fokus bersama iringan musik lofi ambient.</p>
                </div>
              </div>

              <!-- Feature 5: Parent Portal -->
              <div class="glass-card rounded-3xl p-8 col-span-1 md:col-span-3 lg:col-span-4 bg-gradient-to-r from-slate-955 to-slate-900 border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-2">
                  <h3 class="text-xl md:text-2xl font-bold font-heading text-white flex flex-wrap items-center gap-3">
                    Laporan Otomatis WhatsApp Orang Tua
                    <span class="bg-emerald-500/15 text-emerald-450 px-2.5 py-1 rounded text-[10px] font-bold border border-emerald-500/20 uppercase tracking-widest">WhatsApp</span>
                  </h3>
                  <p class="text-xs md:text-sm text-slate-350 max-w-2xl font-light leading-relaxed">
                    Hilangkan kekhawatiran orang tua secara transparan. Sistem otomatis meringkas total waktu belajar, perolehan skor target PTN, dan mengirimkannya dalam format pesan ramah langsung ke WhatsApp orang tua.
                  </p>
                </div>
                <button class="shrink-0 bg-white text-slate-950 hover:bg-slate-200 px-6 py-3.5 rounded-xl font-bold text-xs md:text-sm transition-all flex items-center gap-2" @click="currentTab = 'parent'">
                  Uji Coba Portal <i class="ph-bold ph-whatsapp-logo text-base"></i>
                </button>
              </div>

            </div>
          </div>

          <!-- 7. How It Works -->
          <div class="space-y-16">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-secondary text-sm font-bold uppercase tracking-widest block">LALUAN BELAJAR</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Bagaimana Ini Bekerja?</h2>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-4 gap-12 max-w-5xl mx-auto items-stretch">
              <div class="absolute top-6 left-[12%] right-[12%] h-[1px] bg-gradient-to-r from-primary/30 via-secondary/30 to-rose-500/30 hidden md:block z-0"></div>

              <div class="text-center space-y-4 relative z-10">
                <span class="w-12 h-12 rounded-full bg-slate-955 border border-slate-805 text-primary flex items-center justify-center font-heading font-black text-base mx-auto shadow-lg shadow-black/60">01</span>
                <h4 class="text-base font-bold text-white">Diagnosis Cepat</h4>
                <p class="text-xs md:text-sm text-slate-400 font-light leading-relaxed max-w-xs mx-auto">Evaluasi awal kompetensi utama untuk memetakan kelemahan materi prasyarat.</p>
              </div>
              <div class="text-center space-y-4 relative z-10">
                <span class="w-12 h-12 rounded-full bg-slate-955 border border-slate-805 text-primary flex items-center justify-center font-heading font-black text-base mx-auto shadow-lg shadow-black/60">02</span>
                <h4 class="text-base font-bold text-white">Jalur Rekomendasi</h4>
                <p class="text-xs md:text-sm text-slate-400 font-light leading-relaxed max-w-xs mx-auto">AI menyusun modul prioritas mingguan untuk memperkecil gap nilai target kelulusan.</p>
              </div>
              <div class="text-center space-y-4 relative z-10">
                <span class="w-12 h-12 rounded-full bg-slate-955 border border-slate-805 text-primary flex items-center justify-center font-heading font-black text-base mx-auto shadow-lg shadow-black/60">03</span>
                <h4 class="text-base font-bold text-white">Latihan Terpandu</h4>
                <p class="text-xs md:text-sm text-slate-400 font-light leading-relaxed max-w-xs mx-auto">Tuntaskan micro-lessons dan kuis adaptif dengan panduan interaktif AI Companion.</p>
              </div>
              <div class="text-center space-y-4 relative z-10">
                <span class="w-12 h-12 rounded-full bg-slate-955 border border-slate-805 text-emerald-450 flex items-center justify-center font-heading font-black text-base mx-auto shadow-lg shadow-black/60">04</span>
                <h4 class="text-base font-bold text-white">Tembus PTN Target</h4>
                <p class="text-xs md:text-sm text-slate-400 font-light leading-relaxed max-w-xs mx-auto">Pantau pencapaian skor rata-rata mingguan melampaui target universitas.</p>
              </div>
            </div>
          </div>

          <!-- 8. Use Cases & Segmentasi -->
          <div class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-center">
            <div class="lg:col-span-2 space-y-4">
              <span class="text-primary text-sm font-bold uppercase tracking-widest block">SEGMENTASI KELAS</span>
              <h2 class="text-2xl md:text-3xl font-bold font-heading text-white leading-tight">Dirancang Sesuai Kebutuhan Spesifik Anda</h2>
              <p class="text-xs md:text-sm text-slate-400 leading-relaxed font-light">
                Metode pembobotan materi, durasi belajar harian, dan penyajian kuis adaptif dikalibrasi berdasarkan profil target kelulusan Anda.
              </p>
            </div>
            
            <div class="lg:col-span-3 space-y-6">
              <div class="flex gap-4 items-center border-b border-slate-900 pb-4">
                <span class="text-3xl shrink-0">🩺</span>
                <div>
                  <h4 class="text-base md:text-lg font-bold text-white">Kedokteran &amp; Sains</h4>
                  <p class="text-xs md:text-sm text-slate-350 font-light">Pendalaman Penalaran Matematika, Pengetahuan Kuantitatif kompleks, dan simulator ujian mandiri.</p>
                </div>
              </div>
              <div class="flex gap-4 items-center border-b border-slate-900 pb-4">
                <span class="text-3xl shrink-0">💼</span>
                <div>
                  <h4 class="text-base md:text-lg font-bold text-white">Soshum / Humaniora</h4>
                  <p class="text-xs md:text-sm text-slate-355 font-light">Fokus ke Literasi Bahasa Indonesia, Literasi Bahasa Inggris, penalaran logika analitik, dan koherensi paragraf.</p>
                </div>
              </div>
              <div class="flex gap-4 items-center">
                <span class="text-3xl shrink-0">🎓</span>
                <div>
                  <h4 class="text-base md:text-lg font-bold text-white">Gap Year / Alumni</h4>
                  <p class="text-xs md:text-sm text-slate-355 font-light">Mendeteksi gap pemahaman sisa tahun lalu dengan cepat untuk efisiensi persiapan ulang.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 9. Social Proof / Testimonials -->
          <div id="testimonials" class="space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-rose-500 text-sm font-bold uppercase tracking-widest block">BUKTI NYATA</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Kisah Sukses Pejuang</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-4xl mx-auto items-start">
              <div class="space-y-4 relative">
                <span class="absolute -top-8 -left-6 text-7xl font-serif text-slate-800/20 select-none pointer-events-none">“</span>
                <p class="text-sm md:text-base italic text-slate-200 font-light leading-relaxed relative z-10">
                  "Dulu skor TO mandiri saya macet di 520. Setelah AI EduPath menyuruh saya fokus penuh ke materi Aljabar Kuadrat dan Bangun Ruang (yang ternyata akar kelemahan saya), grafik skor TO naik tajam hingga 715. Lolos Kedokteran UI!"
                </p>
                <div class="flex items-center gap-3 pt-2">
                  <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center font-bold text-xs text-primary border border-slate-800">A</div>
                  <div>
                    <span class="block text-xs md:text-sm font-bold text-white">Ahmad</span>
                    <span class="text-[10px] md:text-xs text-slate-500 font-medium">FK UI Angkatan 2025</span>
                  </div>
                </div>
              </div>

              <div class="space-y-4 relative">
                <span class="absolute -top-8 -left-6 text-7xl font-serif text-slate-800/20 select-none pointer-events-none">“</span>
                <p class="text-sm md:text-base italic text-slate-200 font-light leading-relaxed relative z-10">
                  "Paling suka fitur Pomodoro Study Room-nya. Bikin betah belajar berjam-jam bersama siswa pejuang lainnya di seluruh Indonesia tanpa merasa jenuh. Fitur AI Tutor-nya juga sangat membantu. Lolos Aktuaria UGM!"
                </p>
                <div class="flex items-center gap-3 pt-2">
                  <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center font-bold text-xs text-secondary border border-slate-800">D</div>
                  <div>
                    <span class="block text-xs md:text-sm font-bold text-white">Dewi</span>
                    <span class="text-[10px] md:text-xs text-slate-500 font-medium">Aktuaria UGM Angkatan 2025</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 10. Demonstration / Product Preview -->
          <div id="preview" class="space-y-12 scroll-mt-24 relative overflow-visible">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-primary text-sm font-bold uppercase tracking-widest block">TAMPILAN INTERFACE</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Eksplorasi Dashboard</h2>
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
                      <span class="text-[9px] text-primary font-bold uppercase tracking-wider block">PROGRES BELAJAR</span>
                      <h4 class="text-base font-bold text-white">Peluang Kelulusan Kedokteran UI</h4>
                    </div>
                    <span class="text-xs font-bold text-emerald-450 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md">82.4% Sukses</span>
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

                  <div class="flex items-center gap-3 bg-slate-900/60 border border-slate-805 p-3 rounded-xl">
                    <span class="text-lg">🤖</span>
                    <p class="text-xs text-slate-350 font-light leading-relaxed"><strong>AI Tutor:</strong> "Siswa Mandiri telah meningkatkan kemampuan Aljabar Dasar sebesar 15% minggu ini. Pertahankan streak Anda!"</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 11. Pricing -->
          <div id="pricing" class="space-y-12 scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-secondary text-sm font-bold uppercase tracking-widest block">HARGA PAKET</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Investasi Sukses Anda</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto items-stretch">
              <!-- Plan 1 -->
              <div class="glass-card rounded-3xl p-8 flex flex-col justify-between opacity-80 hover:opacity-100 transition-all duration-300">
                <div>
                  <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block mb-1">MANDIRI</span>
                  <div class="flex items-end gap-1 mb-4">
                    <span class="text-3xl font-bold text-white font-heading">Rp 149k</span>
                    <span class="text-slate-500 text-xs mb-1">/bulan</span>
                  </div>
                  <p class="text-xs md:text-sm text-slate-400 leading-relaxed font-light mb-6 border-b border-slate-900 pb-4">
                    Akses materi terstruktur untuk Anda yang ingin belajar mandiri secara teratur.
                  </p>
                  <ul class="space-y-3.5 text-xs md:text-sm text-slate-305 font-light">
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> Video Materi Terstruktur</li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> 5x Tryout Nasional / bln</li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> Latihan Soal Dasar</li>
                  </ul>
                </div>
                <button class="w-full mt-8 py-3.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs md:text-sm font-bold border border-slate-805 transition-colors" @click="startLearning">Pilih Paket</button>
              </div>

              <!-- Plan 2 -->
              <div class="glass-card rounded-3xl p-8 flex flex-col justify-between border-primary/50 shadow-[0_0_25px_rgba(14,165,233,0.2)] relative scale-105 z-10 overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:animate-shimmer z-0 pointer-events-none"></div>
                
                <span class="absolute top-0 right-0 bg-primary text-slate-955 text-[10px] font-black px-3.5 py-1.5 rounded-bl-lg uppercase tracking-wider">Pilihan Terbaik</span>
                
                <div class="relative z-10">
                  <span class="text-primary text-xs font-semibold uppercase tracking-wider block mb-1">UTAMA (PRO)</span>
                  <div class="flex items-end gap-1 mb-4">
                    <span class="text-4xl font-bold text-white font-heading">Rp 299k</span>
                    <span class="text-slate-500 text-xs mb-1.5">/bulan</span>
                  </div>
                  <p class="text-xs md:text-sm text-slate-400 leading-relaxed font-light mb-6 border-b border-slate-800/85 pb-4">
                    Tingkatkan peluang kelulusan maksimal dengan kelas interaktif AI adaptif.
                  </p>
                  <ul class="space-y-3.5 text-xs md:text-sm text-slate-100 font-light">
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> <strong>Semua Fitur Mandiri</strong></li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> AI Adaptive Path™</li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> 4x Live Class / Minggu</li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-primary text-sm"></i> Akses Klinik PR &amp; Tugas</li>
                  </ul>
                </div>
                <button class="w-full mt-8 py-3.5 btn-shimmer text-white rounded-xl text-xs md:text-sm font-bold transition-transform hover:scale-[1.02] relative z-10" @click="startLearning">Mulai Belajar</button>
              </div>

              <!-- Plan 3 -->
              <div class="glass-card rounded-3xl p-8 flex flex-col justify-between opacity-80 hover:opacity-100 transition-all duration-300">
                <div>
                  <span class="text-secondary text-xs font-semibold uppercase tracking-wider block mb-1">ELITE VIP</span>
                  <div class="flex items-end gap-1 mb-4">
                    <span class="text-3xl font-bold text-white font-heading">Rp 799k</span>
                    <span class="text-slate-550 text-xs mb-1">/bulan</span>
                  </div>
                  <p class="text-xs md:text-sm text-slate-400 leading-relaxed font-light mb-6 border-b border-slate-900 pb-4">
                    Pendampingan privat intensif tatap muka 1-on-1 dengan mentor utama kami.
                  </p>
                  <ul class="space-y-3.5 text-xs md:text-sm text-slate-300 font-light">
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-secondary text-sm"></i> <strong>Semua Fitur Pro</strong></li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-secondary text-sm"></i> 1-on-1 Mentoring Bulanan</li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-secondary text-sm"></i> Konsultasi Jurusan Prioritas</li>
                    <li class="flex items-center gap-2.5"><i class="ph ph-check text-secondary text-sm"></i> Garansi Kelulusan Uang Kembali*</li>
                  </ul>
                </div>
                <button class="w-full mt-8 py-3.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs md:text-sm font-bold border border-slate-800 transition-colors" @click="startLearning">Hubungi Konselor</button>
              </div>
            </div>
          </div>

          <!-- 12. FAQ -->
          <div id="faq" class="space-y-12 max-w-3xl mx-auto scroll-mt-24">
            <div class="text-center max-w-xl mx-auto space-y-2">
              <span class="text-primary text-sm font-bold uppercase tracking-widest block">PERTANYAAN UMUM</span>
              <h2 class="text-3xl md:text-5xl font-bold font-heading text-white">Masih Ragu?</h2>
            </div>

            <div class="space-y-2">
              <div v-for="(faq, idx) in faqs" :key="idx" class="border-b border-slate-900 py-5 transition-all">
                <button class="w-full text-left flex justify-between items-center font-bold text-sm md:text-base text-white focus:outline-none py-2" @click="toggleFaq(idx)">
                  <span>{{ faq.q }}</span>
                  <span class="text-slate-550 text-lg transition-transform" :class="{ 'rotate-45': faq.open }">+</span>
                </button>
                <div v-if="faq.open" class="mt-2 text-xs md:text-sm text-slate-350 leading-relaxed animate-fade-in">
                  {{ faq.a }}
                </div>
              </div>
            </div>
          </div>

          <!-- 13. Final CTA -->
          <div class="text-center py-20 bg-gradient-to-r from-primary/5 via-secondary/5 to-rose-500/5 rounded-3xl p-8 space-y-6 max-w-4xl mx-auto relative overflow-hidden">
            <div class="absolute w-48 h-48 bg-primary/10 blur-[80px] rounded-full top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
            
            <h2 class="text-3xl md:text-5xl font-bold font-heading text-white tracking-tight relative z-10">Siap Mengubah Cara Belajar Anda?</h2>
            <p class="text-sm md:text-base text-slate-400 max-w-md mx-auto leading-relaxed font-light relative z-10">Daftar sekarang dan ambil assessment diagnostic pertama Anda secara gratis.</p>
            
            <div class="flex flex-wrap justify-center gap-4 pt-4 relative z-10">
              <button class="btn-shimmer px-8 py-4.5 rounded-xl font-bold text-xs md:text-sm transform hover:scale-[1.02] active:scale-[0.98] transition-all" @click="startLearning">
                Mulai Uji Coba Gratis
              </button>
              <button class="px-8 py-4.5 rounded-xl font-bold text-xs md:text-sm bg-slate-900 hover:bg-slate-800 border border-slate-800 text-white transition-all" @click="currentTab = 'diagnostic'">
                Mulai Uji Coba Tryout
              </button>
            </div>
          </div>

          <!-- 14. Footer -->
          <footer class="border-t border-slate-900/60 pt-8 pb-4 text-xs text-slate-500 font-light">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
              <div class="flex items-center gap-2">
                <span class="font-bold text-white font-heading text-sm">EduPath<span class="text-primary text-sm">.ai</span></span>
                <span class="text-xs">© 2026 EduPath. All rights reserved.</span>
              </div>
              <div class="flex space-x-6 text-xs">
                <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-white transition-colors">WhatsApp Support</a>
              </div>
            </div>
          </footer>

        </section>

        <!-- TAB 1: DASHBOARD -->
        <section v-if="currentTab === 'dashboard'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Ability Meter Card -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-bold font-heading text-white mb-4">Estimasi Kemampuan &amp; Gap Nilai</h3>
                <div class="flex items-center justify-around py-6 bg-slate-955/40 rounded-2xl border border-slate-900">
                  <div class="text-center">
                    <span class="block text-4xl font-black font-heading text-primary">{{ currentAbilityScore }}</span>
                    <span class="text-xs text-slate-400">Skor Saat Ini</span>
                  </div>
                  <div class="text-slate-600 text-2xl font-bold">➡️</div>
                  <div class="text-center">
                    <span class="block text-4xl font-black font-heading text-secondary">{{ selectedUniversity.targetScore }}</span>
                    <span class="text-xs text-slate-400">Target ({{ selectedUniversity.name }})</span>
                  </div>
                </div>
              </div>

              <!-- Progress bar -->
              <div class="mt-6">
                <div class="flex justify-between text-xs font-semibold mb-2">
                  <span>Selisih Gap: <strong class="text-amber-400 font-bold">{{ gapScore }} Poin</strong></span>
                  <span>{{ progressPercentage }}% Tercapai</span>
                </div>
                <div class="w-full bg-slate-900 rounded-full h-3 overflow-hidden border border-slate-800">
                  <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full transition-all duration-500" :style="{ width: progressPercentage + '%' }"></div>
                </div>
              </div>
            </div>

            <!-- Daily Mission Card -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-bold font-heading text-white mb-3">Misi Hari Ini 🎯</h3>
                <p class="text-xs text-slate-500 mb-4">Selesaikan misi untuk menimbun koin XP tambahan.</p>
                <ul class="space-y-3">
                  <li v-for="(m, index) in dailyMissions" :key="index" :class="['flex items-center gap-3 p-3 rounded-xl border transition-all', m.completed ? 'bg-emerald-950/20 border-emerald-900/40 text-emerald-405' : 'bg-slate-955/30 border-slate-900 text-slate-300']">
                    <input type="checkbox" v-model="m.completed" class="rounded border-slate-805 bg-slate-900 text-primary focus:ring-primary w-4 h-4 cursor-pointer" @change="checkMissionReward(m)">
                    <span :class="['text-xs font-medium flex-grow', { 'line-through opacity-60': m.completed }]">{{ m.title }}</span>
                    <span class="text-xs font-bold shrink-0">+{{ m.reward }} XP</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Priority Learning Skills map -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2">
              <h3 class="text-xl font-bold font-heading text-white mb-2">Peta Penguasaan Kompetensi (Skill Map)</h3>
              <p class="text-xs text-slate-505 mb-6">Deteksi otomatis kekuatan dan kelemahan siswa secara visual.</p>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="(skills, category) in skillMap" :key="category" class="bg-slate-955/40 border border-slate-900 p-5 rounded-2xl">
                  <h4 class="text-sm font-bold text-white mb-4 border-b border-slate-800 pb-2 flex items-center gap-2">
                    <span class="w-1.5 h-3 bg-secondary rounded-full"></span>
                    {{ category }}
                  </h4>
                  <div class="space-y-4">
                    <div v-for="skill in skills" :key="skill" class="space-y-1.5">
                      <div class="flex justify-between text-xs">
                        <span class="text-slate-300 font-medium">{{ skill }}</span>
                        <span class="font-semibold" :style="{ color: getSkillColor(getSkillMastery(skill)) }">{{ getSkillMastery(skill) }}%</span>
                      </div>
                      <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300" :style="{ width: getSkillMastery(skill) + '%', backgroundColor: getSkillColor(getSkillMastery(skill)) }"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recommendation path -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-bold font-heading text-white mb-1">Rekomendasi Jalur AI 🧭</h3>
                <p class="text-xs text-slate-550 mb-6">Tindakan prioritas berikutnya untuk menutup gap skor target Anda.</p>
                
                <div class="space-y-6">
                  <div v-for="(rec, i) in learningRecommendations" :key="i" class="flex gap-4 relative">
                    <div v-if="i < learningRecommendations.length - 1" class="absolute left-4 top-8 bottom-0 w-0.5 bg-slate-805"></div>
                    <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center font-heading font-bold text-xs text-primary shrink-0 z-10">
                      {{ i + 1 }}
                    </div>
                    <div>
                      <h5 class="text-sm font-bold text-white leading-tight mb-1">{{ rec.title }}</h5>
                      <p class="text-xs text-slate-400 font-light leading-relaxed">{{ rec.desc }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 2: DIAGNOSTIC & TRY OUT -->
        <section v-if="currentTab === 'diagnostic'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="glass-card rounded-3xl p-6 lg:col-span-2">
              <h3 class="text-xl font-bold font-heading text-white mb-2">Diagnostic &amp; Adaptive Simulation Test</h3>
              <p class="text-xs text-slate-500 mb-6">
                Sistem simulasi ujian dengan Item Response Theory (IRT). Kesulitan soal dinamis sesuai respons kemampuan Anda.
              </p>

              <div v-if="!diagnosticActive && !diagnosticFinished" class="text-center py-12 bg-slate-955/20 rounded-2xl border border-slate-900">
                <div class="text-5xl mb-4">🚀</div>
                <h4 class="text-lg font-bold text-white mb-2">Mulai Diagnostic Assessment</h4>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-6 leading-relaxed">
                  Ukur kemampuan Aljabar, Geometri, dan Penalaran Logis awal Anda secara presisi dalam 4 soal terpilih.
                </p>
                <button class="btn-shimmer px-8 py-3 rounded-xl font-bold text-sm transform hover:scale-[1.02] active:scale-[0.98] transition-transform" @click="startDiagnostic">
                  Mulai Ujian
                </button>
              </div>

              <!-- Quiz Play state -->
              <div v-else-if="diagnosticActive && !diagnosticFinished" class="space-y-6">
                <div class="flex justify-between items-center border-b border-slate-850 pb-3">
                  <span class="px-3 py-1 bg-slate-900 border border-slate-800 rounded-lg text-xs font-semibold text-slate-400">
                    {{ currentDiagQuestion.category }} &raquo; {{ currentDiagQuestion.skill }}
                  </span>
                  <span :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider', 
                    currentDiagQuestion.difficulty === 'HOTS' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                  ]">
                    {{ currentDiagQuestion.difficulty }}
                  </span>
                </div>

                <div class="space-y-2">
                  <strong class="text-xs text-primary uppercase font-bold tracking-widest">Pertanyaan {{ diagnosticIdx + 1 }} dari {{ diagnosticQuestions.length }}:</strong>
                  <p class="text-base text-white font-medium leading-relaxed">{{ currentDiagQuestion.question }}</p>
                </div>

                <div class="grid grid-cols-1 gap-3">
                  <button 
                    v-for="(option, idx) in currentDiagQuestion.options" 
                    :key="idx"
                    :class="['w-full text-left px-5 py-4 rounded-xl border text-sm font-medium transition-all outline-none', 
                      selectedDiagAnswer === idx 
                        ? 'bg-primary/10 border-primary text-white' 
                        : 'bg-slate-955/40 border-slate-900 text-slate-300 hover:border-slate-800 hover:bg-slate-900/20'
                    ]"
                    @click="selectedDiagAnswer = idx"
                  >
                    <span class="inline-block w-6 h-6 rounded-lg bg-slate-900 border border-slate-805 text-center leading-6 text-xs text-slate-405 font-bold mr-3">{{ String.fromCharCode(65 + idx) }}</span>
                    {{ option }}
                  </button>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-slate-805">
                  <button class="px-5 py-2.5 bg-slate-900 hover:bg-slate-850 border border-slate-805 text-slate-305 rounded-xl text-xs font-bold transition-colors" @click="skipDiagQuestion">
                    Lewati
                  </button>
                  <button class="px-6 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-xs font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :disabled="selectedDiagAnswer === null" @click="submitDiagAnswer">
                    Jawab &amp; Lanjut
                  </button>
                </div>
              </div>

              <!-- Quiz Result State -->
              <div v-else-if="diagnosticFinished" class="space-y-6">
                <div class="text-center py-6 bg-slate-955/40 border border-slate-900 rounded-2xl">
                  <div class="text-5xl mb-2">🎉</div>
                  <h4 class="text-lg font-bold text-white">Diagnostic Selesai!</h4>
                </div>

                <div class="grid grid-cols-3 gap-4">
                  <div class="bg-slate-955/30 border border-slate-900 p-4 rounded-xl text-center">
                    <span class="block text-2xl font-bold text-primary">{{ Math.round(diagnosticStats.masteredPct) }}%</span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Skill Dikuasai</span>
                  </div>
                  <div class="bg-slate-955/30 border border-slate-955 p-4 rounded-xl text-center">
                    <span class="block text-2xl font-bold text-amber-500">{{ Math.round(diagnosticStats.unmasteredPct) }}%</span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Belum Dikuasai</span>
                  </div>
                  <div class="bg-slate-955/30 border border-slate-955 p-4 rounded-xl text-center">
                    <span class="block text-2xl font-bold text-rose-500">{{ Math.round(diagnosticStats.criticalPct) }}%</span>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Materi Kritis</span>
                  </div>
                </div>

                <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl text-xs text-slate-305 leading-relaxed">
                  <strong class="text-white">Rekomendasi AI:</strong> Estimasi skor Anda diperbarui menjadi <strong class="text-primary font-bold">{{ currentAbilityScore }}</strong>. Kurikulum disesuaikan otomatis untuk mendalami <em>Penalaran Kuantitatif</em> dan <em>Aljabar</em>.
                </div>

                <div class="text-center">
                  <button class="px-6 py-3 bg-slate-900 border border-slate-800 hover:bg-slate-850 rounded-xl text-xs font-bold transition-colors" @click="resetDiagnostic">
                    Ulangi Diagnostic Test
                  </button>
                </div>
              </div>
            </div>

            <!-- IRT Details Card -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-bold font-heading text-white mb-4">Kenapa Memilih IRT Model?</h3>
                <div class="space-y-4">
                  <div class="p-4 bg-slate-955/40 rounded-xl border border-slate-900">
                    <strong class="text-xs text-white block mb-1">1. Estimasi Skor Presisi</strong>
                    <p class="text-xs text-slate-400 font-light leading-relaxed">Ujian biasa menyamakan bobot semua soal. IRT memberi bobot lebih tinggi pada soal tersulit yang berhasil Anda jawab.</p>
                  </div>
                  <div class="p-4 bg-slate-955/40 rounded-xl border border-slate-900">
                    <strong class="text-xs text-white block mb-1">2. Pendeteksian Misconception</strong>
                    <p class="text-xs text-slate-400 font-light leading-relaxed">Mendeteksi pola kesalahan jawaban untuk melacak akar materi prasyarat yang terlewat.</p>
                  </div>
                  <div class="p-4 bg-slate-955/40 rounded-xl border border-slate-900">
                    <strong class="text-xs text-white block mb-1">3. Umpan Balik Real-Time</strong>
                    <p class="text-xs text-slate-400 font-light leading-relaxed">Dashboard rekomendasi Anda langsung berubah setiap kali ada peningkatan diagnostic.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 3: MICRO-LEARNING CONTENT -->
        <section v-if="currentTab === 'learning'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Lessons selection list -->
            <div class="glass-card rounded-3xl p-6">
              <h3 class="text-xl font-bold font-heading text-white mb-2">Micro-Lessons 📚</h3>
              <p class="text-xs text-slate-500 mb-6">Materi instan 3-7 menit yang berfokus penuh pada penyelesaian materi spesifik.</p>
              
              <div class="space-y-3">
                <button 
                  v-for="lesson in microLessons" 
                  :key="lesson.id"
                  :class="['w-full text-left p-4 rounded-xl border transition-all outline-none', 
                    selectedLesson.id === lesson.id 
                      ? 'bg-primary/10 border-primary text-white shadow-lg' 
                      : 'bg-slate-955/40 border-slate-900 text-slate-300 hover:border-slate-800'
                  ]"
                  @click="selectedLesson = lesson"
                >
                  <div class="flex justify-between text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-2">
                    <span>{{ lesson.duration }}</span>
                    <span class="text-secondary">{{ lesson.topic }}</span>
                  </div>
                  <h4 class="text-sm font-bold text-white leading-snug">{{ lesson.title }}</h4>
                </button>
              </div>
            </div>

            <!-- Video & Lesson Viewer -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2">
              <div class="space-y-6">
                <div class="h-64 rounded-2xl bg-cover bg-center border border-slate-800 flex items-center justify-center relative overflow-hidden group cursor-pointer" :style="{ backgroundImage: 'linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)), url(' + selectedLesson.videoPlaceholder + ')' }">
                  <div class="w-16 h-16 rounded-full bg-slate-950/80 border border-slate-700 flex items-center justify-center text-2xl text-white group-hover:scale-110 transition-transform">▶️</div>
                  <div class="absolute bottom-4 left-4 right-4">
                    <span class="text-[10px] font-bold bg-primary text-slate-955 px-2 py-0.5 rounded uppercase">{{ selectedLesson.type }}</span>
                    <h3 class="text-lg font-bold text-white mt-1.5 leading-snug">{{ selectedLesson.title }}</h3>
                  </div>
                </div>

                <div class="space-y-4">
                  <h4 class="text-sm font-bold text-white">Ringkasan Materi &amp; Tips Cepat</h4>
                  <p class="text-xs text-slate-400 font-light leading-relaxed">{{ selectedLesson.summary }}</p>

                  <!-- Quick quiz inside micro-lesson -->
                  <div class="bg-slate-955/40 border border-slate-900 p-5 rounded-2xl mt-6">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-500/10 border border-amber-500/20 text-[10px] font-bold text-amber-400 rounded-md mb-3">
                      <i class="ph-bold ph-lightning"></i> QUICK QUIZ
                    </div>
                    <h5 class="text-sm font-bold text-white mb-4">{{ selectedLesson.quiz.question }}</h5>
                    
                    <div class="grid grid-cols-1 gap-2.5">
                      <button 
                        v-for="(opt, idx) in selectedLesson.quiz.options"
                        :key="idx"
                        :class="['w-full text-left px-4 py-3.5 rounded-xl border text-xs font-semibold transition-all outline-none', 
                          selectedLessonQuizAns === idx
                            ? (showLessonQuizFeedback && idx === selectedLesson.quiz.answer ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' : 'bg-rose-500/10 border-rose-500 text-rose-400')
                            : (showLessonQuizFeedback && idx === selectedLesson.quiz.answer ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' : 'bg-slate-955/80 border-slate-900 text-slate-350 hover:border-slate-805')
                        ]"
                        @click="selectLessonQuizOption(idx)"
                      >
                        {{ opt }}
                      </button>
                    </div>

                    <div v-if="showLessonQuizFeedback" class="mt-4 p-3.5 bg-slate-900 border border-slate-800 rounded-xl text-xs">
                      <p v-if="selectedLessonQuizAns === selectedLesson.quiz.answer" class="text-emerald-400 font-bold">
                        🎉 Jawaban Benar! Anda mendapatkan +10 XP.
                      </p>
                      <p v-else class="text-rose-400 font-bold">
                        ❌ Salah. Tips: <span class="font-normal text-slate-400">{{ selectedLesson.quiz.hint }}</span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 4: PRACTICE & AI TUTOR -->
        <section v-if="currentTab === 'practice'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Interactive Problem Screen -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
              <div>
                <div class="flex justify-between items-center border-b border-slate-855 pb-3 mb-6">
                  <h3 class="text-xl font-bold font-heading text-white">Practice Engine &amp; AI Tutor</h3>
                  <span class="px-2 py-0.5 bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary rounded-md uppercase">Latihan Aktif</span>
                </div>

                <div class="space-y-4">
                  <span class="text-xs text-secondary font-bold uppercase tracking-widest block">SOAL LATIHAN</span>
                  <p class="text-base font-semibold text-white leading-relaxed">
                    {{ activePracticeQuestion.question }}
                  </p>

                  <div class="grid grid-cols-1 gap-2.5 pt-4">
                    <button 
                      v-for="(opt, idx) in activePracticeQuestion.options"
                      :key="idx"
                      :class="['w-full text-left px-4 py-3.5 rounded-xl border text-xs font-semibold transition-all outline-none', 
                        practiceUserAnswer === idx
                          ? (practiceEvaluated && idx === activePracticeQuestion.answer ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' : 'bg-rose-500/10 border-rose-500 text-rose-400')
                          : (practiceEvaluated && idx === activePracticeQuestion.answer ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' : 'bg-slate-950/80 border-slate-905 text-slate-300 hover:border-slate-800')
                      ]"
                      @click="practiceUserAnswer = idx"
                    >
                      <span class="inline-block w-5 h-5 rounded bg-slate-900 border border-slate-800 text-center leading-5 text-[10px] font-bold text-slate-400 mr-2">{{ String.fromCharCode(65 + idx) }}</span>
                      {{ opt }}
                    </button>
                  </div>
                </div>
              </div>

              <div class="mt-8 border-t border-slate-855 pt-6 space-y-4">
                <div class="flex flex-wrap gap-3">
                  <button class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 border border-slate-805 text-slate-305 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" @click="triggerAIEscalation">
                    🤖 Tanya AI Tutor
                  </button>
                  <button class="px-4 py-2.5 bg-rose-955/30 hover:bg-rose-955/55 border border-rose-900/40 text-rose-400 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" @click="triggerSOSCall">
                    🚨 SOS Live Tutor
                  </button>
                  <button class="ml-auto px-6 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed" :disabled="practiceUserAnswer === null" @click="checkPracticeAnswer">
                    Kirim Jawaban
                  </button>
                </div>

                <!-- Detect -> Explain -> Remediate Notification -->
                <div v-if="practiceEvaluated && practiceUserAnswer !== activePracticeQuestion.answer" class="flex gap-3.5 p-4 bg-rose-955/20 border border-rose-900/40 rounded-2xl animate-fade-in">
                  <div class="text-rose-400 text-lg">⚠️</div>
                  <div>
                    <h5 class="text-xs font-bold text-white mb-0.5">Sistem Remedial Terdeteksi</h5>
                    <p class="text-[11px] text-slate-400 font-light leading-relaxed">Jawaban Anda belum tepat. Sistem merekomendasikan untuk mereview sub-kompetensi prasyarat di tab <strong class="text-primary cursor-pointer hover:underline" @click="currentTab = 'learning'">Micro Lessons</strong> terlebih dahulu.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Interactive AI Tutor Companion Panel -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between min-h-[420px]">
              <div>
                <h3 class="text-xl font-bold font-heading text-white mb-1">AI Tutor Companion 🤖</h3>
                <p class="text-xs text-slate-505 mb-4">Gunakan tombol petunjuk di bawah untuk bantuan analisis terpandu.</p>

                <div class="chat-area h-64 border border-slate-905 bg-slate-955/50 rounded-xl p-3 overflow-y-auto space-y-3">
                  <div v-for="(chat, i) in aiChatHistory" :key="i" :class="['p-3 rounded-xl text-xs leading-relaxed max-w-[85%]', 
                    chat.sender === 'ai' ? 'bg-slate-900 text-slate-303 border border-slate-805 self-start' : (chat.sender === 'system' ? 'bg-rose-955/20 border border-rose-900 text-rose-400 max-w-[95%] mx-auto' : 'bg-primary/20 text-white border border-primary/20 self-end ml-auto')
                  ]">
                    <strong class="block font-bold text-[10px] uppercase tracking-wider text-slate-400 mb-1">{{ chat.senderName }}</strong>
                    <p>{{ chat.text }}</p>
                    <ul v-if="chat.steps" class="mt-2 space-y-1 list-disc pl-4 text-slate-400">
                      <li v-for="step in chat.steps" :key="step">{{ step }}</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="mt-4">
                <div class="grid grid-cols-2 gap-2">
                  <button class="py-2 px-3 bg-slate-900 border border-slate-850 hover:border-slate-700 text-slate-300 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(1)">Level 1: Hint</button>
                  <button class="py-2 px-3 bg-slate-900 border border-slate-850 hover:border-slate-700 text-slate-300 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(2)">Level 2: Konsep</button>
                  <button class="py-2 px-3 bg-slate-900 border border-slate-850 hover:border-slate-700 text-slate-300 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(3)">Level 3: Langkah</button>
                  <button class="py-2 px-3 bg-slate-900 border border-slate-850 hover:border-slate-700 text-slate-300 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider" @click="askAILevel(4)">Level 4: Solusi</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- TAB 5: VIRTUAL STUDY ROOM -->
        <section v-if="currentTab === 'studyroom'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Room Status Card -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
              <div>
                <div class="flex justify-between items-start border-b border-slate-855 pb-3 mb-6">
                  <div>
                    <h3 class="text-xl font-bold font-heading text-white">Virtual Study Room 24/7</h3>
                    <p class="text-xs text-slate-505 mt-1">Belajar mandiri bersama pejuang PTN lainnya dengan teknik fokus Pomodoro.</p>
                  </div>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-xs font-semibold text-emerald-400 rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-ping"></span>
                    🟢 {{ onlineStudents }} online
                  </span>
                </div>

                <!-- Pomodoro Widget -->
                <div class="py-12 bg-slate-955/40 rounded-2xl border border-slate-900 text-center space-y-4">
                  <div class="timer-display font-heading font-black text-6xl text-white tracking-widest">{{ formattedTime }}</div>
                  <div class="text-xs text-gradient-cyan uppercase tracking-widest font-extrabold">
                    {{ isBreak ? 'Waktunya Istirahat ☕' : 'Sesi Fokus Belajar 🧠' }}
                  </div>
                  <div class="flex justify-center gap-3 pt-4">
                    <button class="px-6 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-xs font-bold transition-colors" @click="toggleTimer">
                      {{ timerActive ? 'Pause' : 'Start Sesi' }}
                    </button>
                    <button class="px-5 py-2.5 bg-slate-900 border border-slate-850 text-slate-300 rounded-xl text-xs font-bold transition-colors" @click="resetTimer">
                      Reset
                    </button>
                  </div>
                </div>
              </div>

              <div class="mt-8 border-t border-slate-805 pt-6">
                <label class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-3">🎵 Pilihan Musik Lofi Ambience:</label>
                <div class="flex gap-2">
                  <button 
                    v-for="track in lofiTracks" 
                    :key="track.id" 
                    :class="['px-4 py-2 border rounded-xl text-xs font-semibold transition-all', 
                      currentTrack === track.id 
                        ? 'bg-secondary/15 border-secondary text-white shadow-md' 
                        : 'bg-slate-955/50 border-slate-900 text-slate-400 hover:border-slate-850'
                    ]"
                    @click="playTrack(track.id)"
                  >
                    {{ track.name }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Accountability leaderboard -->
            <div class="glass-card rounded-3xl p-6">
              <h3 class="text-xl font-bold font-heading text-white mb-2">Peringkat Streak Belajar 🔥</h3>
              <p class="text-xs text-slate-500 mb-6">Konsistensi harian mengalahkan kecepatan menyelesaikan soal.</p>

              <ul class="space-y-3">
                <li class="flex items-center gap-3 p-3.5 bg-slate-800/40 border border-slate-700/80 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-amber-500/10 border border-amber-500/25 flex items-center justify-center font-heading font-black text-xs text-amber-500">1</span>
                  <span class="text-xs font-bold text-white flex-grow">Anda (Siswa Mandiri)</span>
                  <span class="text-xs text-amber-400 font-semibold">🔥 {{ streakCount }} Hari</span>
                </li>
                <li class="flex items-center gap-3 p-3.5 bg-slate-955/30 border border-slate-900 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-slate-900 border border-slate-850 flex items-center justify-center font-heading font-bold text-xs text-slate-404">2</span>
                  <span class="text-xs font-medium text-slate-300 flex-grow">Budi Santoso</span>
                  <span class="text-xs text-slate-450">🔥 6 Hari</span>
                </li>
                <li class="flex items-center gap-3 p-3.5 bg-slate-955/30 border border-slate-900 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-slate-900 border border-slate-805 flex items-center justify-center font-heading font-bold text-xs text-slate-404">3</span>
                  <span class="text-xs font-medium text-slate-300 flex-grow">Citra Lestari</span>
                  <span class="text-xs text-slate-455">🔥 5 Hari</span>
                </li>
                <li class="flex items-center gap-3 p-3.5 bg-slate-955/30 border border-slate-900 rounded-xl">
                  <span class="w-6 h-6 rounded-full bg-slate-900 border border-slate-805 flex items-center justify-center font-heading font-bold text-xs text-slate-404">4</span>
                  <span class="text-xs font-medium text-slate-300 flex-grow">Dewi Setyowati</span>
                  <span class="text-xs text-slate-455">🔥 5 Hari</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- TAB 6: PARENT PORTAL -->
        <section v-if="currentTab === 'parent'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Parent insight metrics card -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2">
              <h3 class="text-xl md:text-2xl font-bold font-heading text-white mb-2">Parent Portal 👨‍👩‍👧</h3>
              <p class="text-xs md:text-sm text-slate-400 mb-6">Laporan analisis keterbacaan belajar ananda untuk tinjauan berkala orang tua.</p>

              <div class="space-y-6">
                <div class="grid grid-cols-3 gap-4">
                  <div class="bg-slate-950/40 border border-slate-900 p-4 rounded-xl text-center">
                    <span class="block text-lg md:text-xl font-bold text-primary">4 Jam 35m</span>
                    <span class="text-[10px] md:text-xs text-slate-500 uppercase font-semibold">Durasi Belajar</span>
                  </div>
                  <div class="bg-slate-950/40 border border-slate-900 p-4 rounded-xl text-center">
                    <span class="block text-lg md:text-xl font-bold text-emerald-450">+8%</span>
                    <span class="text-[10px] md:text-xs text-slate-500 uppercase font-semibold">Progres Akademik</span>
                  </div>
                  <div class="bg-slate-950/40 border border-slate-900 p-4 rounded-xl text-center">
                    <span class="block text-lg md:text-xl font-bold text-amber-505">5/7 Hari</span>
                    <span class="text-[10px] md:text-xs text-slate-505 uppercase font-semibold">Konsistensi Login</span>
                  </div>
                </div>

                <div class="p-5 bg-primary/10 border border-primary/20 rounded-xl">
                  <strong class="text-sm text-white block mb-1">Analisis Perilaku Belajar:</strong>
                  <p class="text-xs md:text-sm text-slate-300 font-light leading-relaxed">
                    Ananda menunjukkan penyelesaian sangat baik pada pemecahan soal Aljabar dasar, namun mengalami pelambatan kecepatan pengerjaan pada materi Geometri Bangun Ruang. AI menyarankan pendampingan berupa latihan visualisasi geometri minggu ini.
                  </p>
                </div>
              </div>
            </div>

            <!-- WhatsApp Report Simulator -->
            <div class="glass-card rounded-3xl p-6 flex flex-col justify-between">
              <div>
                <h3 class="text-lg md:text-xl font-bold font-heading text-white mb-2">Notifikasi Laporan WA</h3>
                <p class="text-xs md:text-sm text-slate-405 mb-4">Laporan ringkas mingguan dikirimkan otomatis ke WhatsApp orang tua.</p>

                <div class="border border-slate-800 rounded-2xl overflow-hidden bg-[#075e54]">
                  <div class="bg-[#075e54] px-4 py-2 flex items-center gap-2 border-b border-black/10">
                    <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full"></span>
                    <span class="text-xs font-bold text-white">EduPath Parenting Bot</span>
                  </div>
                  <div class="bg-[#ece5dd] p-3 h-44 overflow-y-auto">
                    <div class="bg-white text-slate-955 p-3 rounded-xl text-[10px] leading-relaxed shadow-sm max-w-[85%] border border-black/5">
                      <p class="font-bold text-slate-900 border-b border-slate-105 pb-1 mb-1">Laporan Belajar Mingguan EduPath</p>
                      <p>Ananda belajar selama 4 jam 35 menit.</p>
                      <p>📈 Matematika: +8%</p>
                      <p>📈 Literasi: +4%</p>
                      <p>⚠️ Penalaran: Butuh latihan lanjutan</p>
                      <p>Target PTN UI: 720</p>
                      <p class="font-bold text-primary mt-1">Estimasi Kemampuan: {{ currentAbilityScore }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <button class="w-full mt-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-slate-950 rounded-xl text-xs md:text-sm font-black transition-colors" @click="simulateWASent">
                Kirim Laporan WA
              </button>
            </div>
          </div>
        </section>

        <!-- TAB 7: 2027 QUESTION PROJECTION -->
        <section v-if="currentTab === 'projection2027'" class="animate-fade-in space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Predictive Card -->
            <div class="glass-card rounded-3xl p-6 lg:col-span-2">
              <h3 class="text-xl font-bold font-heading text-white mb-2">Analisis Potensi Soal UTBK 2027 🔮</h3>
              <p class="text-xs text-slate-500 mb-6">
                AI menganalisis tren pergeseran soal dari UTBK 2023 - 2025 untuk mendeteksi materi baru yang berpotensi keluar di UTBK 2027.
              </p>

              <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl mb-6 space-y-2">
                <strong class="text-xs text-white block">💡 Ringkasan Analisis Tren Makro:</strong>
                <ul class="text-xs text-slate-300 font-light leading-relaxed pl-4 list-disc space-y-1">
                  <li><strong>UTBK 2023-2024:</strong> Fokus utama pada logika silogisme murni dan aritmetika dasar.</li>
                  <li><strong>UTBK 2025:</strong> Mulai mengintegrasikan isian singkat numerik dan statistika deskrittif terapan.</li>
                  <li><strong>Prediksi UTBK 2027:</strong> Soal model gabungan (Hybrid HOTS) yang menuntut visualisasi spasial 3D serta analisis perbandingan kuantitatif (tipe P &gt; Q).</li>
                </ul>
              </div>

              <!-- Subtest Selector -->
              <div class="space-y-3 mb-6">
                <label class="text-[10px] font-bold tracking-wider text-slate-400 uppercase block">Pilih Subtes Simulasi (Total 150 Soal):</label>
                <div class="flex gap-2 overflow-x-auto pb-2 border-b border-slate-900">
                  <button 
                    v-for="sub in [
                      { id: 'PU', name: 'Penalaran Umum', icon: '🧠' },
                      { id: 'PK', name: 'Pengetahuan Kuantitatif', icon: '📐' },
                      { id: 'LIndo', name: 'Literasi B. Indonesia', icon: '🇮🇩' },
                      { id: 'LEng', name: 'Literasi B. Inggris', icon: '🇬🇧' },
                      { id: 'PM', name: 'Penalaran Matematika', icon: '📊' }
                    ]" 
                    :key="sub.id"
                    :class="['flex items-center gap-1.5 px-3 py-2 border rounded-xl text-xs font-semibold transition-all shrink-0 outline-none', 
                      activeSubtest === sub.id 
                        ? 'bg-primary/15 border-primary text-white shadow-md' 
                        : 'bg-slate-950/40 border-slate-900 text-slate-405 hover:border-slate-800'
                    ]"
                    @click="activeSubtest = sub.id"
                  >
                    <span>{{ sub.icon }}</span>
                    <span>{{ sub.name }}</span>
                  </button>
                </div>
              </div>

              <!-- Dynamic Simulator Question Pane -->
              <div class="space-y-4 max-h-[420px] overflow-y-auto pr-2">
                <div 
                  v-for="q in SIMULATOR_DATABASE[activeSubtest]" 
                  :key="q.id" 
                  class="bg-slate-955/40 border border-slate-900 p-5 rounded-2xl"
                >
                  <div class="flex justify-between items-center mb-3">
                    <span class="px-2 py-0.5 bg-slate-900 border border-slate-850 rounded-md text-[10px] text-slate-500 font-semibold">Soal {{ q.num }} dari {{ SIMULATOR_DATABASE[activeSubtest].length }}</span>
                    <span class="px-2 py-0.5 bg-rose-500/10 border border-rose-500/20 text-[9px] font-bold text-rose-400 rounded-md uppercase">HOTS</span>
                  </div>
                  <p class="text-sm font-semibold text-white leading-relaxed mb-4">
                    {{ q.question }}
                  </p>
                  <div class="grid grid-cols-1 gap-2">
                    <button 
                      v-for="(opt, idx) in q.options" 
                      :key="idx"
                      :class="['w-full text-left px-4 py-3.5 border rounded-xl text-xs font-medium transition-all outline-none', 
                        userAnswersMap[q.id] === idx 
                          ? (idx === q.answer ? 'bg-emerald-500/15 border-emerald-500 text-emerald-405' : 'bg-rose-500/15 border-rose-500 text-rose-405')
                          : 'bg-slate-900/60 border-slate-900/60 text-slate-300 hover:border-slate-800'
                      ]" 
                      @click="userAnswersMap[q.id] = idx"
                    >
                      <span class="inline-block w-5 h-5 rounded bg-slate-950 border border-slate-800 text-center leading-5 text-[9px] font-bold text-slate-400 mr-2">{{ String.fromCharCode(65 + idx) }}</span>
                      {{ opt }}
                    </button>
                  </div>
                  <div v-if="userAnswersMap[q.id] !== undefined" class="p-4 bg-slate-900 border border-slate-850 rounded-xl mt-4 space-y-2">
                    <div class="flex items-center gap-1.5 text-xs">
                      <span v-if="userAnswersMap[q.id] === q.answer" class="text-emerald-400 font-bold">✅ Benar! (+10 XP)</span>
                      <span v-else class="text-rose-400 font-bold">❌ Kurang tepat.</span>
                    </div>
                    <p class="text-xs text-slate-300"><strong>Pembahasan:</strong> {{ q.explanation }}</p>
                    <p class="text-[11px] text-slate-555"><strong>Konsep:</strong> {{ q.concept }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Probability Weight Matrix -->
            <div class="glass-card rounded-3xl p-6">
              <h3 class="text-xl font-bold font-heading text-white mb-2">Komposisi Soal UTBK</h3>
              <p class="text-xs text-slate-550 mb-6">Struktur resmi subtes SNBT (Total 150 Soal / 195 Menit):</p>
              
              <div class="space-y-4">
                <div>
                  <div class="flex justify-between text-xs font-semibold mb-1">
                    <span>Tes Potensi Skolastik (TPS)</span>
                    <span>70 Soal</span>
                  </div>
                  <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: 47%;"></div>
                  </div>
                </div>
                <div>
                  <div class="flex justify-between text-xs font-semibold mb-1">
                    <span>Literasi B. Indonesia</span>
                    <span>30 Soal</span>
                  </div>
                  <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-secondary h-full rounded-full" style="width: 20%;"></div>
                  </div>
                </div>
                <div>
                  <div class="flex justify-between text-xs font-semibold mb-1">
                    <span>Literasi B. Inggris</span>
                    <span>20 Soal</span>
                  </div>
                  <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-accent h-full rounded-full" style="width: 13%;"></div>
                  </div>
                </div>
                <div>
                  <div class="flex justify-between text-xs font-semibold mb-1">
                    <span>Penalaran Matematika</span>
                    <span>30 Soal</span>
                  </div>
                  <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-rose-500 h-full rounded-full" style="width: 20%;"></div>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-slate-955/40 border border-slate-900 rounded-2xl mt-6 text-xs text-slate-400 leading-relaxed font-light">
                ⚠️ <strong>Aturan Waktu:</strong> Sistem Ujian menggunakan Block-Timing. Siswa tidak dapat mereview kembali subtes yang telah terlewat setelah waktu habis.
              </div>
            </div>
          </div>
        </section>
      </div>

    </main>

    <!-- Interactive Login Modal -->
    <div v-if="showLoginModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-md animate-fade-in">
      <div class="glass-card max-w-sm w-full p-8 rounded-3xl space-y-6 relative border-primary/30">
        <!-- Close button -->
        <button @click="showLoginModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">✕</button>
        
        <div class="text-center space-y-2">
          <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary text-xl mx-auto">
            ⚡
          </div>
          <h3 class="text-xl font-bold font-heading text-white">Masuk ke EduPath.ai</h3>
          <p class="text-xs text-slate-400 font-light">Gunakan akun demo untuk mengaktifkan seluruh dashboard adaptif.</p>
        </div>

        <div class="space-y-4">
          <div class="space-y-1">
            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Email Siswa (Demo)</label>
            <input type="email" value="siswa@edupath.ai" disabled class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-400 outline-none cursor-not-allowed">
          </div>
          <div class="space-y-1">
            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Password (Demo)</label>
            <input type="password" value="••••••••" disabled class="w-full bg-slate-900 border border-slate-805 rounded-xl px-4 py-2.5 text-xs text-slate-400 outline-none cursor-not-allowed">
          </div>
        </div>

        <button @click="login" class="w-full py-3 btn-shimmer text-white rounded-xl text-xs font-bold transition-transform hover:scale-[1.02]">
          Masuk Sekarang &raquo;
        </button>

        <p class="text-[10px] text-slate-500 text-center font-light">Email &amp; password otomatis terisi untuk keperluan simulasi.</p>
      </div>
    </div>

    <!-- Global Toast Notification -->
    <div v-if="toastMessage" class="fixed bottom-6 right-6 z-50 bg-slate-900 border border-slate-800/80 px-6 py-3.5 rounded-2xl shadow-2xl flex items-center gap-2 animate-fade-in">
      <span class="w-2 h-2 bg-primary rounded-full animate-ping"></span>
      <span class="text-xs font-semibold text-white">{{ toastMessage }}</span>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { 
  SKILL_MAP, 
  TARGET_UNIVERSITIES, 
  MICRO_LESSONS, 
  DIAGNOSTIC_QUESTIONS,
  SIMULATOR_DATABASE
} from './EduData.js';

export default {
  name: 'App',
  setup() {
    const ambientGlowRef = ref(null);

    // Basic User & Auth States
    const isLoggedIn = ref(true); // Default to logged in for direct sandbox experience, can toggle via UI
    const showLoginModal = ref(false);

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

    // Skill mastery mapping details (Simulating diagnostic impact)
    const skillMastery = ref({
      'Operasi Aljabar': 85,
      'Persamaan Linear': 78,
      'Faktorisasi': 70,
      'Persamaan Kuadrat': 40,
      'Bangun Datar': 65,
      'Bangun Ruang': 35,
      'Transformasi': 50,
      'Logika': 60,
      'Data': 55,
      'Problem Solving': 45
    });

    const skillMap = ref(SKILL_MAP);

    const getSkillMastery = (skill) => {
      return skillMastery.value[skill] || 0;
    };

    const getSkillColor = (val) => {
      if (val >= 80) return '#10b981'; // Green
      if (val >= 50) return '#f59e0b'; // Orange
      return '#ef4444'; // Red
    };

    // Calculate Admission Goal Gap
    const gapScore = computed(() => {
      const gap = selectedUniversity.value.targetScore - currentAbilityScore.value;
      return gap > 0 ? gap : 0;
    });

    const progressPercentage = computed(() => {
      const pct = Math.round((currentAbilityScore.value / selectedUniversity.value.targetScore) * 100);
      return pct > 100 ? 100 : pct;
    });

    const recalcTargetGap = () => {
      showToast(`Target diubah ke ${selectedUniversity.value.name}`);
    };

    // Navigation & Tabs
    const currentTab = ref('home');
    const tabs = [
      { id: 'home', name: 'Beranda', icon: '🏠' },
      { id: 'dashboard', name: 'Dashboard', icon: '📊' },
      { id: 'diagnostic', name: 'Diagnostic & Test', icon: '📝' },
      { id: 'learning', name: 'Micro Lessons', icon: '📚' },
      { id: 'practice', name: 'Practice Engine', icon: '🎯' },
      { id: 'studyroom', name: 'Study Room', icon: '⏳' },
      { id: 'projection2027', name: 'Analisis 2027', icon: '🔮' },
      { id: 'parent', name: 'Parent Portal', icon: '👨‍👩‍👧' }
    ];

    // Auth functions
    const login = () => {
      isLoggedIn.value = true;
      showLoginModal.value = false;
      showToast('Masuk Akun Sukses! Selamat datang kembali.');
    };

    const logout = () => {
      isLoggedIn.value = false;
      currentTab.value = 'home';
      showToast('Anda telah keluar dari akun.');
    };

    const handleTabClick = (tabId) => {
      if (tabId === 'home') {
        currentTab.value = 'home';
        return;
      }
      
      // If user is not logged in, prompt login modal
      if (!isLoggedIn.value) {
        showLoginModal.value = true;
        showToast('Silakan Masuk Akun untuk mengakses fitur ini!');
      } else {
        currentTab.value = tabId;
      }
    };

    // Rotating Words logic
    const rotatingWords = ['Cara Cerdas.', 'Analitik AI.', 'Tutor Ahli.', 'Sistem EduPath.'];
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

    const scrollToSection = (id) => {
      const el = document.getElementById(id);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
      }
    };

    // FAQs (Objection Killers)
    const faqs = ref([
      { q: "Apakah EduPath.ai bisa digunakan di HP / Tablet?", a: "Ya! EduPath.ai sepenuhnya responsif dan dapat diakses dengan lancar dari perangkat mobile, tablet, maupun komputer tanpa perlu install aplikasi tambahan.", open: false },
      { q: "Apakah AI Tutor Companion aktif 24 jam?", a: "Tentu saja. AI Tutor Companion mendampingi latihan pengerjaan Anda 24 jam nonstop dengan tuntunan logis bertahap tanpa membocorkan jawaban langsung.", open: false },
      { q: "Bagaimana cara kerja penskalaan skor IRT (Item Response Theory)?", a: "Sistem kami melacak tingkat kesulitan dinamis berdasarkan persentase jawaban benar pengguna lain. Soal yang sulit dijawab benar mendapatkan bobot nilai kelulusan lebih tinggi.", open: false },
      { q: "Apakah orang tua wajib login untuk melihat laporan belajar?", a: "Tidak perlu. Progres belajar mingguan akan terkirim secara berkala dalam format ringkas otomatis langsung ke nomor WhatsApp orang tua terdaftar.", open: false }
    ]);

    const toggleFaq = (index) => {
      faqs.value[index].open = !faqs.value[index].open;
    };

    // Daily Mission list
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

    // Dynamic Learning Paths based on scores
    const learningRecommendations = computed(() => {
      const path = [];
      if (getSkillMastery('Persamaan Kuadrat') < 50) {
        path.push({ title: 'Aljabar & Persamaan Kuadrat', desc: 'Penguasaan 40%. Remedial disarankan sebelum lanjut ke Geometri.' });
      }
      if (getSkillMastery('Problem Solving') < 60) {
        path.push({ title: 'Penalaran Kuantitatif', desc: 'Penalaran kuantitatif 45%. Fokus pada data interpretation dan logika matematika.' });
      }
      path.push({ title: 'Latihan Pemeliharaan Geometri', desc: 'Tinjau bangun ruang untuk pemantapan skor target UTBK Anda.' });
      return path;
    });

    // Toast Trigger utility
    const showToast = (msg) => {
      toastMessage.value = msg;
      setTimeout(() => {
        toastMessage.value = '';
      }, 3000);
    };

    // Diagnostic Quiz States
    const diagnosticActive = ref(false);
    const diagnosticFinished = ref(false);
    const diagnosticQuestions = ref(DIAGNOSTIC_QUESTIONS);
    const diagnosticIdx = ref(0);
    const selectedDiagAnswer = ref(null);
    const diagnosticScore = ref(0);

    const currentDiagQuestion = computed(() => {
      return diagnosticQuestions.value[diagnosticIdx.value];
    });

    const diagnosticStats = ref({
      masteredPct: 64,
      unmasteredPct: 21,
      criticalPct: 15
    });

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
        // Improve corresponding skill
        const skill = currentDiagQuestion.value.skill;
        if (skillMastery.value[skill]) {
          skillMastery.value[skill] = Math.min(skillMastery.value[skill] + 15, 100);
        }
      } else {
        // Decrease if incorrect to trigger remedial path
        const skill = currentDiagQuestion.value.skill;
        if (skillMastery.value[skill]) {
          skillMastery.value[skill] = Math.max(skillMastery.value[skill] - 10, 10);
        }
      }

      if (diagnosticIdx.value < diagnosticQuestions.value.length - 1) {
        diagnosticIdx.value++;
        selectedDiagAnswer.value = null;
      } else {
        finishDiagnostic();
      }
    };

    const skipDiagQuestion = () => {
      if (diagnosticIdx.value < diagnosticQuestions.value.length - 1) {
        diagnosticIdx.value++;
        selectedDiagAnswer.value = null;
      } else {
        finishDiagnostic();
      }
    };

    const finishDiagnostic = () => {
      diagnosticActive.value = false;
      diagnosticFinished.value = true;
      // Adjust estimated ability based on diagnostic success rate
      const correctRatio = diagnosticScore.value / diagnosticQuestions.value.length;
      currentAbilityScore.value = Math.round(600 + correctRatio * 150);

      // Adjust mock metrics
      diagnosticStats.value.masteredPct = 60 + correctRatio * 30;
      diagnosticStats.value.unmasteredPct = 25 - correctRatio * 15;
      diagnosticStats.value.criticalPct = 15 - correctRatio * 15;

      showToast('Diagnostic Selesai! Jalur Belajar Baru Terbentuk.');
    };

    const resetDiagnostic = () => {
      diagnosticFinished.value = false;
      diagnosticActive.value = false;
    };

    // Micro Lesson state
    const microLessons = ref(MICRO_LESSONS);
    const selectedLesson = ref(MICRO_LESSONS[0]);
    const selectedLessonQuizAns = ref(null);
    const showLessonQuizFeedback = ref(false);

    const selectLessonQuizOption = (idx) => {
      selectedLessonQuizAns.value = idx;
      showLessonQuizFeedback.value = true;
      if (idx === selectedLesson.value.quiz.answer) {
        coins.value += 10;
      }
    };

    // Practice Engine & AI Tutor state
    const activePracticeQuestion = ref(DIAGNOSTIC_QUESTIONS[0]); // Uses first diagnostic as practice
    const practiceUserAnswer = ref(null);
    const practiceEvaluated = ref(false);
    const aiChatHistory = ref([
      { sender: 'ai', senderName: 'AI Tutor Companion', text: 'Halo! Saya AI Tutor pendamping belajarmu. Coba pecahkan soal di samping terlebih dahulu. Jika kesulitan, kamu bisa menanyakan Hint ke saya!' }
    ]);

    const checkPracticeAnswer = () => {
      practiceEvaluated.value = true;
      if (practiceUserAnswer.value === activePracticeQuestion.value.answer) {
        showToast('Jawaban Anda Benar! +20 Coins.');
        coins.value += 20;
      } else {
        showToast('Jawaban kurang tepat. Coba minta solusi terpandu ke AI!');
      }
    };

    const triggerAIEscalation = () => {
      askAILevel(1);
    };

    const askAILevel = (level) => {
      let responseText = '';
      let steps = null;

      if (level === 1) {
        responseText = `[HINT]: ${activePracticeQuestion.value.hint}`;
      } else if (level === 2) {
        responseText = `[KONSEP INTI]: ${activePracticeQuestion.value.concept}`;
      } else if (level === 3) {
        responseText = `[LANGKAH MANDIRI]: Ikuti petunjuk penyelesaian berikut secara perlahan:`;
        steps = activePracticeQuestion.value.guidedSteps;
      } else {
        responseText = `[SOLUSI LENGKAP]: ${activePracticeQuestion.value.explanation}`;
      }

      aiChatHistory.value.push({
        sender: 'user',
        senderName: 'Siswa',
        text: `Bisa bantu saya dengan penjelasan level ${level}?`
      });

      setTimeout(() => {
        aiChatHistory.value.push({
          sender: 'ai',
          senderName: 'AI Tutor Companion',
          text: responseText,
          steps: steps
        });
      }, 500);
    };

    const triggerSOSCall = () => {
      showToast(' SOS Tutor Terkirim! Tutor manusia akan masuk ke sesi chat Anda dalam waktu < 2 menit.');
      aiChatHistory.value.push({
        sender: 'system',
        senderName: 'SISTEM ESCALATION',
        text: 'SOS Tutor diaktifkan. Kak Dina (Tutor Matematika ITB) telah menerima detail track performa Anda dan sedang membaca riwayat pengerjaan Anda.'
      });
    };

    // Virtual Study Room: Pomodoro Timer & Audio simulator
    const onlineStudents = ref(327);
    const timerMinutes = ref(25);
    const timerSeconds = ref(0);
    const timerActive = ref(false);
    const isBreak = ref(false);
    const currentTrack = ref('relax');
    let timerInterval = null;

    const lofiTracks = [
      { id: 'relax', name: 'Ambient Chill' },
      { id: 'focus', name: 'Deep Work Beats' },
      { id: 'rain', name: 'Soft Rain Coffee' }
    ];

    const formattedTime = computed(() => {
      const min = timerMinutes.value.toString().padStart(2, '0');
      const sec = timerSeconds.value.toString().padStart(2, '0');
      return `${min}:${sec}`;
    });

    const toggleTimer = () => {
      if (timerActive.value) {
        clearInterval(timerInterval);
        timerActive.value = false;
      } else {
        timerActive.value = true;
        timerInterval = setInterval(() => {
          if (timerSeconds.value > 0) {
            timerSeconds.value--;
          } else if (timerMinutes.value > 0) {
            timerMinutes.value--;
            timerSeconds.value = 59;
          } else {
            // Timer expired
            clearInterval(timerInterval);
            timerActive.value = false;
            if (!isBreak.value) {
              isBreak.value = true;
              timerMinutes.value = 5;
              showToast('Sesi belajar selesai! Waktunya istirahat 5 menit ☕');
              coins.value += 15;
            } else {
              isBreak.value = false;
              timerMinutes.value = 25;
              showToast('Sesi istirahat selesai! Mari kembali fokus 🧠');
            }
          }
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
      // Simulated browser audio notification
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();
      
      oscillator.type = 'sine';
      oscillator.frequency.setValueAtTime(120, audioCtx.currentTime); // Low soothing drone
      gainNode.gain.setValueAtTime(0.02, audioCtx.currentTime);
      
      oscillator.connect(gainNode);
      gainNode.connect(audioCtx.destination);
      oscillator.start();
      setTimeout(() => {
        oscillator.stop();
      }, 600);
    };

    // Parent WhatsApp simulation
    const simulateWASent = () => {
      showToast(' Laporan mingguan sukses dikirimkan ke WhatsApp Orang Tua.');
    };

    // Simulating updates periodically
    onMounted(() => {
      // Set to true by default for demo ease of use, can click "Keluar" to see logged-out view
      isLoggedIn.value = true;

      setInterval(() => {
        // Randomly update online students
        onlineStudents.value += Math.floor(window.crypto ? Math.random() * 7 : Math.random() * 7) - 3;
        if (onlineStudents.value < 300) onlineStudents.value = 300;
      }, 5000);

      // Rotating words animation
      setInterval(() => {
        currentWordIdx.value = (currentWordIdx.value + 1) % rotatingWords.length;
      }, 2500);

      // Ambient mouse tracking
      const ambientGlow = ambientGlowRef.value;
      if (ambientGlow) {
        document.addEventListener('mousemove', (e) => {
          ambientGlow.style.left = `${e.clientX}px`;
          ambientGlow.style.top = `${e.clientY}px`;
        });
      }
    });

    return {
      ambientGlowRef,
      isLoggedIn,
      showLoginModal,
      login,
      logout,
      handleTabClick,

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
      tabs,
      dailyMissions,
      checkMissionReward,
      learningRecommendations,
      
      // Rotating words
      rotatingWords,
      currentWordIdx,
      startLearning,
      scrollToSection,

      // FAQs
      faqs,
      toggleFaq,

      // Diagnostic Section
      diagnosticActive,
      diagnosticFinished,
      diagnosticIdx,
      selectedDiagAnswer,
      currentDiagQuestion,
      diagnosticQuestions,
      diagnosticStats,
      startDiagnostic,
      submitDiagAnswer,
      skipDiagQuestion,
      resetDiagnostic,

      // Micro Lesson Section
      microLessons,
      selectedLesson,
      selectedLessonQuizAns,
      showLessonQuizFeedback,
      selectLessonQuizOption,

      // Practice Section
      activePracticeQuestion,
      practiceUserAnswer,
      practiceEvaluated,
      aiChatHistory,
      checkPracticeAnswer,
      triggerAIEscalation,
      askAILevel,
      triggerSOSCall,

      // Study Room
      onlineStudents,
      formattedTime,
      isBreak,
      timerActive,
      currentTrack,
      lofiTracks,
      toggleTimer,
      resetTimer,
      playTrack,

      // Parent
      simulateWASent,

      // 2027 Projections
      ansProj1,
      ansProj2,
      ansProj3,
      ansProj4,
      ansProj5,
      activeSubtest,
      SIMULATOR_DATABASE,
      userAnswersMap
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
```
