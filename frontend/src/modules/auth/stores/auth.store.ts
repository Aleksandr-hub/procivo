import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(localStorage.getItem('access_token'))
  const refreshToken = ref<string | null>(localStorage.getItem('refresh_token'))
  const user = ref<{ id: string; email: string; firstName: string; lastName: string } | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!accessToken.value)

  function setTokens(access: string, refresh: string) {
    accessToken.value = access
    refreshToken.value = refresh
    localStorage.setItem('access_token', access)
    localStorage.setItem('refresh_token', refresh)
  }

  function clearTokens() {
    accessToken.value = null
    refreshToken.value = null
    user.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('refresh_token')
  }

  async function initialize() {
    if (initialized.value) return

    if (accessToken.value) {
      try {
        await fetchUser()
      } catch {
        clearTokens()
      }
    }

    initialized.value = true
  }

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const { default: httpClient } = await import('@/shared/api/http-client')
      const response = await httpClient.post('/auth/login', { email, password })
      setTokens(response.data.access_token, response.data.refresh_token)
      await fetchUser()
    } finally {
      loading.value = false
    }
  }

  async function register(data: {
    email: string
    password: string
    firstName: string
    lastName: string
  }) {
    loading.value = true
    try {
      const { default: httpClient } = await import('@/shared/api/http-client')
      await httpClient.post('/auth/register', data)
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      if (refreshToken.value) {
        const { default: httpClient } = await import('@/shared/api/http-client')
        await httpClient.post('/auth/logout', { refresh_token: refreshToken.value })
      }
    } finally {
      clearTokens()
    }
  }

  async function fetchUser() {
    const { default: httpClient } = await import('@/shared/api/http-client')
    const response = await httpClient.get('/auth/me')
    user.value = response.data
  }

  return {
    accessToken,
    refreshToken,
    user,
    loading,
    initialized,
    isAuthenticated,
    setTokens,
    clearTokens,
    initialize,
    login,
    register,
    logout,
    fetchUser,
  }
})
