import vue from '@vitejs/plugin-vue' 
 
export default { 
  plugins: [vue()], 
  base: './',
  build: { 
    assetsDir: 'assets' 
  } 
} 
