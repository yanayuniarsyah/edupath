import vue from '@vitejs/plugin-vue' 
 
export default { 
  plugins: [vue()], 
  base: './',
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: { 
    assetsDir: 'assets' 
  } 
} 
