import { createRouter, createWebHashHistory } from 'vue-router'
import StudentApp from '../App.vue'
import AdminPanel from '../views/AdminPanel.vue'

const routes = [
  { path: '/', component: StudentApp },
  { path: '/admin', component: AdminPanel },
]

export default createRouter({
  history: createWebHashHistory(),
  routes,
})
