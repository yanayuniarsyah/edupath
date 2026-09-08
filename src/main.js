import { createApp } from 'vue'
import RootApp from './RootApp.vue'
import router from './router/index.js'
import './index.css'

createApp(RootApp).use(router).mount('#app')
