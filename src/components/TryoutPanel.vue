<template>
  <div class="animate-fade-in space-y-6">
    <!-- Header -->
    <div class="border-b border-slate-200 pb-4">
      <h2 class="text-2xl font-bold text-slate-900 mb-2">📋 Tryout</h2>
      <p class="text-sm text-slate-600">Ikuti tryout untuk mengukur progress persiapan SNBT Anda</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="text-center space-y-3">
        <div class="w-12 h-12 border-4 border-slate-200 border-t-[#c0ff00] rounded-full animate-spin mx-auto"></div>
        <p class="text-slate-600 font-medium">Memuat data tryout...</p>
      </div>
    </div>

    <!-- List of Tryouts -->
    <div v-else-if="tryouts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="tryout in tryouts"
        :key="tryout.id"
        class="border border-slate-200 rounded-2xl p-6 hover:border-[#c0ff00] hover:shadow-md transition-all group cursor-pointer"
        @click="selectTryout(tryout)"
      >
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-900 group-hover:text-[#c0ff00] transition-colors">
              {{ tryout.title }}
            </h3>
            <p class="text-xs text-slate-500 mt-1">{{ tryout.subtes }}</p>
          </div>
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
            {{ tryout.total_questions }} Soal
          </span>
        </div>

        <p class="text-sm text-slate-600 mb-4 line-clamp-2">{{ tryout.description }}</p>

        <div class="flex items-center justify-between text-xs text-slate-500 mb-4">
          <span class="flex items-center gap-1">
            <i class="ph-bold ph-clock"></i>
            {{ tryout.time_limit_minutes }} menit
          </span>
          <span class="flex items-center gap-1">
            <i class="ph-bold ph-target"></i>
            Passing: {{ tryout.passing_score }}%
          </span>
        </div>

        <div v-if="tryout.last_attempt" class="p-3 rounded-xl bg-slate-50 mb-4">
          <p class="text-xs font-semibold text-slate-700 mb-1">Attempt Terakhir:</p>
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-slate-900">{{ tryout.last_attempt.score }}%</span>
            <span class="text-xs text-slate-500">{{ formatDate(tryout.last_attempt.submitted_at) }}</span>
          </div>
        </div>

        <button
          class="w-full py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2"
          :class="tryout.last_attempt 
            ? 'bg-slate-100 text-slate-900 hover:bg-slate-200' 
            : 'bg-[#c0ff00] text-black hover:bg-[#b0ef00]'"
          @click.stop="selectTryout(tryout)"
        >
          {{ tryout.last_attempt ? 'Ulangi Tryout' : 'Mulai Sekarang' }}
          <i class="ph-bold ph-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="text-5xl mb-3">📭</div>
      <p class="text-slate-600 font-medium mb-2">Belum ada tryout tersedia</p>
      <p class="text-sm text-slate-500">Cek kembali nanti untuk tryout terbaru</p>
    </div>

    <!-- Modal Tryout -->
    <div v-if="selectedTryout" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-white border-b border-slate-200 p-6 flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">{{ selectedTryout.title }}</h3>
          <button @click="selectedTryout = null" class="text-slate-400 hover:text-slate-600 text-xl">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <!-- Modal Content -->
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-slate-50 text-center">
              <p class="text-xs text-slate-500 font-semibold mb-1">Total Soal</p>
              <p class="text-2xl font-bold text-slate-900">{{ selectedTryout.total_questions }}</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 text-center">
              <p class="text-xs text-slate-500 font-semibold mb-1">Durasi</p>
              <p class="text-2xl font-bold text-slate-900">{{ selectedTryout.time_limit_minutes }}m</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 text-center">
              <p class="text-xs text-slate-500 font-semibold mb-1">Passing</p>
              <p class="text-2xl font-bold text-slate-900">{{ selectedTryout.passing_score }}%</p>
            </div>
          </div>

          <div>
            <h4 class="font-semibold text-slate-900 mb-2">Deskripsi</h4>
            <p class="text-slate-600 text-sm leading-relaxed">{{ selectedTryout.description }}</p>
          </div>

          <div class="p-4 rounded-xl border-2 border-[#c0ff00]/30 bg-[#c0ff00]/5">
            <p class="text-sm text-slate-700 font-medium mb-2">⚠️ Persiapan:</p>
            <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
              <li>Pastikan koneksi internet stabil</li>
              <li>Catat waktu mulai dan perhatikan durasi timer</li>
              <li>Jawaban tidak bisa diubah setelah submit</li>
              <li>Hasil akan langsung ditampilkan dengan analisis detail</li>
            </ul>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="sticky bottom-0 bg-white border-t border-slate-200 p-6 flex gap-3">
          <button
            @click="selectedTryout = null"
            class="flex-1 py-3 rounded-xl font-bold border border-slate-200 text-slate-900 hover:bg-slate-50 transition-all"
          >
            Batal
          </button>
          <button
            @click="startTryout"
            class="flex-1 py-3 rounded-xl font-bold text-black transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95"
            style="background: #c0ff00;"
          >
            Mulai Tryout
            <i class="ph-bold ph-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TryoutPanel',
  data() {
    return {
      tryouts: [],
      loading: true,
      selectedTryout: null
    };
  },
  mounted() {
    this.fetchTryouts();
  },
  methods: {
    async fetchTryouts() {
      try {
        this.loading = true;
        const response = await fetch('/api/assessments/tryout', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('ep_access_token')}`
          }
        });
        const result = await response.json();
        if (result.success) {
          this.tryouts = result.data.tryouts || [];
        }
      } catch (error) {
        console.error('Error fetching tryouts:', error);
      } finally {
        this.loading = false;
      }
    },
    selectTryout(tryout) {
      this.selectedTryout = tryout;
    },
    async startTryout() {
      try {
        const response = await fetch('/api/assessments/attempt/start', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('ep_access_token')}`
          },
          body: JSON.stringify({ tryout_id: this.selectedTryout.id })
        });
        const result = await response.json();
        if (result.success) {
          // Emit event to parent to navigate to tryout screen
          this.$emit('start-tryout', {
            attempt_id: result.data.attempt_id,
            tryout_id: result.data.tryout_id,
            time_limit: result.data.time_limit
          });
          this.selectedTryout = null;
        }
      } catch (error) {
        console.error('Error starting tryout:', error);
      }
    },
    formatDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString('id-ID', { 
        day: 'short', 
        month: 'short', 
        year: 'numeric' 
      });
    }
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
