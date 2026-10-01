import { createRouter, createWebHashHistory } from 'vue-router'
import StudentApp from '../App.vue'
import AdminPanel from '../views/AdminPanel.vue'
import TryOutCBT from '../views/TryOutCBT.vue'
import TermsView from '../views/TermsView.vue'

const routes = [
  { path: '/', component: StudentApp },
  { path: '/admin', component: AdminPanel },
  { path: '/tryout', component: TryOutCBT },
  { path: '/terms', component: TermsView },
]

export default createRouter({
  history: createWebHashHistory(),
  routes,
})
