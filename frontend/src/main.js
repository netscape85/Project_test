import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import { useAuth } from './composables/useAuth'

// Views
import LoginView from './views/LoginView.vue'
import RegisterView from './views/RegisterView.vue'
import ProjectsView from './views/ProjectsView.vue'
import ProjectDetailView from './views/ProjectDetailView.vue'
import UsersView from './views/UsersView.vue'
import ArtifactsView from './views/ArtifactsView.vue'
import ModulesView from './views/ModulesView.vue'
import ProfileView from './views/ProfileView.vue'

// Router configuration
const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guest: true }
  },
  {
    path: '/',
    name: 'projects',
    component: ProjectsView,
    meta: { requiresAuth: true }
  },
  {
    path: '/projects',
    name: 'projects-list',
    component: ProjectsView,
    meta: { requiresAuth: true }
  },
  {
    path: '/projects/:id',
    name: 'project-detail',
    component: ProjectDetailView,
    meta: { requiresAuth: true }
  },
  {
    path: '/artifacts',
    name: 'artifacts',
    component: ArtifactsView,
    meta: { requiresAuth: true }
  },
  {
    path: '/modules',
    name: 'modules',
    component: ModulesView,
    meta: { requiresAuth: true }
  },
  {
    path: '/users',
    name: 'users',
    component: UsersView,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/projects'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const { initAuth, isAuthenticated, isAdmin, isPM, isEngineer } = useAuth()
  
  // Initialize auth state
  await initAuth()
  
  if (to.meta.requiresAuth && !isAuthenticated.value) {
    next('/login')
  } else if (to.meta.guest && isAuthenticated.value) {
    next('/projects')
  } else if (to.name === 'users' && !isAdmin.value) {
    // Only admin can access users page
    next('/projects')
  } else {
    next()
  }
})

const app = createApp(App)
app.use(vuetify)
app.use(router)
app.mount('#app')

