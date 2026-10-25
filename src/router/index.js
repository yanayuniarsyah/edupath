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
    } else if (currentPath === 'school' || currentPath === 'school/login') {
      window.location.replace('/#/school' + window.location.search);
    } else if (currentPath === 'spp') {
      window.location.replace('/#/spp' + window.location.search);
    }
  }
} catch (e) {}

const routes = [
  {
    path: '/',
    component: () => import('../App.vue'),
  },
  {
    path: '/spp',
    component: () => import('../App.vue'),
  },
  {
    path: '/school',
    component: () => import(/* webpackChunkName: "school" */ '../views/SchoolDashboard.vue'),
  },
  {
    path: '/school/login',
    redirect: '/school',
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
  {
    path: '/verify-cert',
    component: () => import(/* webpackChunkName: "verify-cert" */ '../views/VerifyCertificate.vue'),
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

export default createRouter({
  history: createWebHashHistory(),
  routes,
})
