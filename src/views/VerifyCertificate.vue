<template>
  <div class="min-h-screen bg-[#070b14] text-white flex flex-col justify-between selection:bg-[#c0ff00] selection:text-black">
    <!-- Ambient Background Glow -->
    <div class="fixed top-[-20%] left-[-10%] w-[500px] h-[500px] rounded-full bg-emerald-500/10 blur-[130px] pointer-events-none"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[600px] h-[600px] rounded-full bg-[#c0ff00]/10 blur-[140px] pointer-events-none"></div>

    <!-- Header Navigation -->
    <header class="border-b border-white/10 bg-[#0b1329]/80 backdrop-blur-md px-6 py-4 flex items-center justify-between sticky top-0 z-30">
      <div class="flex items-center gap-3 cursor-pointer" @click="goToHome">
        <div class="w-9 h-9 rounded-xl bg-[#c0ff00] flex items-center justify-center font-black text-black text-lg shadow-md shadow-[#c0ff00]/20">
          E
        </div>
        <div>
          <span class="font-black text-lg tracking-tight text-white">EduPath<span class="text-[#c0ff00]">.ai</span></span>
          <span class="block text-[10px] text-white/40 font-mono tracking-widest uppercase">Verification Portal</span>
        </div>
      </div>
      <button 
        @click="goToHome" 
        class="text-xs font-bold text-white/70 hover:text-white px-3.5 py-1.5 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all flex items-center gap-1.5"
      >
        <i class="ph-bold ph-house"></i>
        <span>Beranda</span>
      </button>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-6 md:p-10 relative z-10">
      <div class="w-full max-w-2xl">

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-20 space-y-4">
          <div class="w-12 h-12 border-3 border-white/20 border-t-[#c0ff00] rounded-full animate-spin mx-auto"></div>
          <p class="text-sm font-mono text-white/60">Memverifikasi keaslian dokumen sertifikat EduPath...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="!certData || !certData.valid" class="bg-[#0e1726]/90 border border-rose-500/30 rounded-3xl p-8 sm:p-10 text-center space-y-6 shadow-2xl backdrop-blur-xl">
          <div class="w-20 h-20 rounded-full bg-rose-500/15 border border-rose-500/30 text-rose-400 flex items-center justify-center mx-auto text-4xl shadow-[0_0_30px_rgba(244,63,94,0.3)]">
            <i class="ph-bold ph-shield-warning"></i>
          </div>
          <div class="space-y-2">
            <h2 class="text-2xl font-black text-white font-heading">Dokumen Tidak Ditemukan</h2>
            <p class="text-sm text-white/60 max-w-md mx-auto leading-relaxed">
              {{ errorMessage || 'Nomor sertifikat atau kode verifikasi tidak cocok dengan basis data resmi EduPath Indonesia.' }}
            </p>
          </div>

          <!-- Manual Verification Input -->
          <div class="pt-4 max-w-md mx-auto space-y-3">
            <div class="flex gap-2">
              <input 
                v-model="inputNumber" 
                @keyup.enter="recheck"
                type="text" 
                placeholder="Masukkan Nomor Sertifikat (misal: CERT-SPP-...)" 
                class="flex-grow px-4 py-3 rounded-xl bg-white/5 border border-white/15 text-xs text-white placeholder-white/40 focus:outline-none focus:border-[#c0ff00]"
              />
              <button 
                @click="recheck"
                class="px-5 py-3 rounded-xl bg-[#c0ff00] text-black font-black text-xs hover:bg-[#a6de00] transition-all shrink-0"
              >
                Cek
              </button>
            </div>
            <p class="text-[11px] text-white/40">Pastikan nomor sertifikat tertulis lengkap sesuai dokumen fisik/digital.</p>
          </div>
        </div>

        <!-- Valid State (Official Certificate Verified) -->
        <div v-else class="space-y-6">
          <!-- Verification Badge -->
          <div class="flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-black tracking-widest uppercase mx-auto w-fit shadow-[0_0_20px_rgba(16,185,129,0.2)]">
            <i class="ph-fill ph-seal-check text-base text-emerald-400"></i>
            <span>Dokumen Otentik & Terverifikasi</span>
          </div>

          <!-- Certificate Card -->
          <div class="bg-gradient-to-b from-[#0e1726]/95 via-[#0b1329]/90 to-[#070b14]/95 border-2 border-emerald-500/30 rounded-3xl p-6 sm:p-8 md:p-10 shadow-[0_0_50px_rgba(16,185,129,0.15)] relative overflow-hidden backdrop-blur-xl">
            <!-- Decorative Seal Background -->
            <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-emerald-500/5 blur-2xl pointer-events-none"></div>

            <!-- Top Card Header -->
            <div class="border-b border-white/10 pb-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
              <div>
                <span class="text-[10px] font-mono tracking-[0.25em] text-[#c0ff00] uppercase font-bold block mb-1">
                  OFFICIAL ASSESSMENT CERTIFICATE
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-white font-heading">
                  Student Potential Profile (SPP)
                </h1>
                <p class="text-xs text-white/50 mt-0.5">EduPath Indonesia • Standar Psikometri Adaptif</p>
              </div>
              <div class="text-center sm:text-right font-mono">
                <div class="text-[10px] text-white/40 uppercase">No. Registrasi</div>
                <div class="text-xs sm:text-sm font-black text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-lg border border-emerald-500/20 mt-0.5">
                  {{ certData.certificate_number }}
                </div>
              </div>
            </div>

            <!-- Identity Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-6 border-b border-white/10 text-xs">
              <div class="space-y-1">
                <span class="text-white/40 text-[10px] uppercase font-bold tracking-wider">Nama Peserta Didik</span>
                <div class="text-sm font-bold text-white flex items-center gap-2">
                  <i class="ph-bold ph-user text-emerald-400"></i>
                  <span>{{ certData.student_name }}</span>
                </div>
              </div>
              <div class="space-y-1">
                <span class="text-white/40 text-[10px] uppercase font-bold tracking-wider">Institusi / Asal Sekolah</span>
                <div class="text-sm font-bold text-white flex items-center gap-2">
                  <i class="ph-bold ph-graduation-cap text-sky-400"></i>
                  <span>{{ certData.school_name || 'Umum' }}</span>
                </div>
              </div>
              <div class="space-y-1">
                <span class="text-white/40 text-[10px] uppercase font-bold tracking-wider">Kode Tipologi RIASEC</span>
                <div class="text-sm font-mono font-black text-amber-400 flex items-center gap-2">
                  <i class="ph-bold ph-compass text-amber-400"></i>
                  <span>{{ certData.riasec_code }}</span>
                </div>
              </div>
              <div class="space-y-1">
                <span class="text-white/40 text-[10px] uppercase font-bold tracking-wider">Gaya Belajar Dominan</span>
                <div class="text-sm font-bold text-[#c0ff00] flex items-center gap-2">
                  <i class="ph-bold ph-brain text-[#c0ff00]"></i>
                  <span>{{ certData.learning_style }}</span>
                </div>
              </div>
            </div>

            <!-- Recommendations Section -->
            <div v-if="certData.top_recommendations" class="py-6 border-b border-white/10 space-y-3">
              <span class="text-white/40 text-[10px] uppercase font-bold tracking-wider block">
                Rekomendasi Arah Studi & Karir
              </span>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-center">
                  <div class="text-[9px] text-[#c0ff00] font-bold uppercase">Pilihan Utama</div>
                  <div class="text-xs font-black text-white mt-1">{{ certData.top_recommendations.bidang_utama }}</div>
                </div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-center">
                  <div class="text-[9px] text-sky-400 font-bold uppercase">Pendukung</div>
                  <div class="text-xs font-black text-white mt-1">{{ certData.top_recommendations.bidang_pendukung }}</div>
                </div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-center">
                  <div class="text-[9px] text-amber-400 font-bold uppercase">Alternatif</div>
                  <div class="text-xs font-black text-white mt-1">{{ certData.top_recommendations.pilihan_alternatif }}</div>
                </div>
              </div>
            </div>

            <!-- Verification Metadata -->
            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] font-mono text-white/40">
              <div class="space-y-1 text-center sm:text-left">
                <div>Tanggal Terbit: <strong class="text-white/70">{{ certData.issue_date }}</strong></div>
                <div class="truncate max-w-sm">Hash: <span class="text-emerald-400/80">{{ certData.verification_hash }}</span></div>
              </div>
              <button 
                @click="printCertificate"
                class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all flex items-center gap-2 shrink-0 border border-white/10"
              >
                <i class="ph-bold ph-printer"></i>
                <span>Cetak Bukti Verifikasi</span>
              </button>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 py-4 px-6 text-center text-xs text-white/40 font-mono">
      © {{ new Date().getFullYear() }} EduPath Indonesia • Sistem Verifikasi Sertifikat Resmi Anti-Pemalsuan
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const loading = ref(true);
const certData = ref(null);
const errorMessage = ref('');
const inputNumber = ref('');

const fetchVerification = async (identifier) => {
  if (!identifier) {
    loading.value = false;
    certData.value = null;
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  try {
    const res = await api.sppVerifyCertificate(identifier);
    if (res && res.valid) {
      certData.value = res;
    } else {
      certData.value = null;
      errorMessage.value = res?.message || 'Sertifikat tidak terdaftar.';
    }
  } catch (err) {
    certData.value = null;
    errorMessage.value = err?.message || 'Gagal memverifikasi dokumen sertifikat.';
  } finally {
    loading.value = false;
  }
};

const recheck = () => {
  if (inputNumber.value.trim()) {
    fetchVerification(inputNumber.value.trim());
  }
};

const printCertificate = () => {
  window.print();
};

const goToHome = () => {
  window.location.hash = '#/';
};

onMounted(() => {
  // Ambil query param no, number, hash, atau id dari hash route atau window location
  let id = '';
  const hash = window.location.hash || '';
  if (hash.includes('?')) {
    const params = new URLSearchParams(hash.split('?')[1]);
    id = params.get('no') || params.get('number') || params.get('hash') || params.get('id') || '';
  }
  if (!id && window.location.search) {
    const params = new URLSearchParams(window.location.search);
    id = params.get('no') || params.get('number') || params.get('hash') || params.get('id') || '';
  }

  inputNumber.value = id;
  fetchVerification(id);
});
</script>
