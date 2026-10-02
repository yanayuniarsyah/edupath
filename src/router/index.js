import { createRouter, createWebHashHistory } from 'vue-router'

// LAZY IMPORTS: setiap route di-download hanya saat dibutuhkan
// - Siswa biasa tidak perlu download AdminPanel.vue (89KB + xlsx library)
// - TryOutCBT hanya didownload saat masuk halaman tryout
// Ini memotong initial bundle secara signifikan

const routes = [
  {
    path: '/',
    component: () => import('../App.vue'),
  },
  {
    path: '/admin',
    // AdminPanel membawa xlsx — pisahkan ke chunk sendiri
    component: () => import(/* webpackChunkName: "admin" */ '../views/AdminPanel.vue'),
  },
  {
    path: '/tryout',
    component: () => import(/* webpackChunkName: "tryout" */ '../views/TryOutCBT.vue'),
  },
  {
    path: '/terms',
    component: () => import(/* webpackChunkName: "terms" */ '../views/TermsView.vue'),
  },
]

export default createRouter({
  history: createWebHashHistory(),
  routes,
})
