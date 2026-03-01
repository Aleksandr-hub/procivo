import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(localStorage.getItem('access_token'))
  const refreshToken = ref<string | null>(localStorage.getItem('refresh_token'))
  const user = ref<{ id: string; email: string; firstName: string; lastName: string; avatarUrl?: string } | null>(null)
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

  async function updateProfile(data: { firstName: string; lastName: string; email: string }) {
    const { default: httpClient } = await import('@/shared/api/http-client')
    await httpClient.put('/auth/me', {
      first_name: data.firstName,
      last_name: data.lastName,
      email: data.email,
    })
    await fetchUser()
  }

  async function uploadAvatar(file: File) {
    const { default: httpClient } = await import('@/shared/api/http-client')
    const formData = new FormData()
    formData.append('avatar', file)
    await httpClient.post('/auth/me/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    await fetchUser()
  }

  async function changePassword(currentPassword: string, newPassword: string) {
    const { default: httpClient } = await import('@/shared/api/http-client')
    await httpClient.put('/auth/password', {
      current_password: currentPassword,
      new_password: newPassword,
    })
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
    updateProfile,
    uploadAvatar,
    changePassword,
  }
})
