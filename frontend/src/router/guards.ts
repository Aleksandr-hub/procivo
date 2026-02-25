import type { Router } from 'vue-router'
import { useAuthStore } from '@/modules/auth/stores/auth.store'

export function setupAuthGuard(router: Router): void {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (!auth.initialized) {
      await auth.initialize()
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (to.meta.guest && auth.isAuthenticated) {
      return { path: '/' }
    }
  })
}
