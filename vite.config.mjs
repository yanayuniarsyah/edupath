import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [vue()],
  base: '/',

  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },

  build: {
    assetsDir: 'assets',

    // Minifikasi agresif dengan terser
    minify: 'terser',
    terserOptions: {
      compress: {
        // Hapus semua console.log, console.warn di production
        drop_console: true,
        drop_debugger: true,
        // Hapus code yang tidak pernah dieksekusi
        dead_code: true,
        // Agresif inline fungsi kecil
        inline: 2,
      },
      format: {
        // Hapus komentar di output
        comments: false,
      }
    },

    // Peringatan jika chunk > 500KB (default 500KB, diperkecil jadi 400KB)
    chunkSizeWarningLimit: 400,

    // Asset kecil (<= 4KB) di-inline sebagai base64 untuk kurangi HTTP requests
    assetsInlineLimit: 4096,

    rollupOptions: {
      output: {
        // Manual chunk splitting: pisahkan vendor dari app code
        // Browser bisa cache vendor chunk lebih lama (immutable)
        manualChunks(id) {
          // Vue core — jarang berubah, cache panjang
          if (id.includes('node_modules/vue') ||
              id.includes('node_modules/@vue') ||
              id.includes('node_modules/vue-router')) {
            return 'vendor-vue';
          }
          // Lucide icons — library besar, pisahkan
          if (id.includes('node_modules/lucide') ||
              id.includes('node_modules/@lucide')) {
            return 'vendor-icons';
          }
          // xlsx (library parsing soal) — besar dan jarang berubah
          if (id.includes('node_modules/xlsx')) {
            return 'vendor-xlsx';
          }
        },
        // Naming konsisten dengan hash untuk cache busting
        chunkFileNames: 'assets/js/[name]-[hash].js',
        entryFileNames: 'assets/js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          // Pisahkan CSS, gambar, font ke folder berbeda
          if (/\.(css)$/.test(assetInfo.name)) {
            return 'assets/css/[name]-[hash][extname]';
          }
          if (/\.(png|jpg|jpeg|gif|svg|webp|avif)$/.test(assetInfo.name)) {
            return 'assets/img/[name]-[hash][extname]';
          }
          if (/\.(woff|woff2|eot|ttf|otf)$/.test(assetInfo.name)) {
            return 'assets/fonts/[name]-[hash][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        }
      }
    },

    // Source maps HANYA untuk dev, tidak di production (keamanan + ukuran)
    sourcemap: false,

    // Target browser modern (tidak perlu polyfill ES5 yang berat)
    target: 'es2020',
  },
})
