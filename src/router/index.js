import { createRouter, createWebHashHistory } from 'vue-router'

// Handle direct pathname URL like https://edupath.co.id/admin when using hash history
try {
  const currentPath = window.location.pathname.replace(/^\/+/, '').replace(/\/+$/, '');
  if (currentPath && !window.location.hash) {
    if (currentPath === 'admin' || currentPath === 'admin/login') {
      window.location.replace('/#/admin');
    } else if (currentPath === 'tryout') {
      window.location.replace('/#/tryout');
    } else if (currentPath === 'terms') {
      window.location.replace('/#/terms');
    }
  }
} catch (e) {}

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
    path: '/admin/login',
    redirect: '/admin',
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
