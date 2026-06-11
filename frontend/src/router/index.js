import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/Login.vue'
import Register from '../pages/Register.vue'
import Dashboard from '../pages/Dashboard.vue'
import Sites from '../pages/Sites.vue'
import WebsiteDetail from '../pages/WebsiteDetail.vue'
import Settings from '../pages/Settings.vue'

const routes = [
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  {
    path: '/dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/sites',
    component: Sites,
    meta: { requiresAuth: true }
  },
  {
    path: '/sites/:id',
    component: WebsiteDetail,
    meta: { requiresAuth: true }
  },
  {
    path: '/sites/:id/:tab',
    component: WebsiteDetail,
    meta: { requiresAuth: true }
  },
  {
    path: '/settings',
    component: Settings,
    meta: { requiresAuth: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) return '/login'
  if ((to.path === '/login' || to.path === '/register') && token) return '/dashboard'
})

export default router
