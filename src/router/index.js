import { createRouter, createWebHashHistory } from 'vue-router'
import StudentApp from '../App.vue'
import AdminPanel from '../views/AdminPanel.vue'
import TryOutCBT from '../views/TryOutCBT.vue'

const routes = [
  { path: '/', component: StudentApp },
  { path: '/admin', component: AdminPanel },
  { path: '/tryout', component: TryOutCBT },
]

export default createRouter({
  history: createWebHashHistory(),
  routes,
})
