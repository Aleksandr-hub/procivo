import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { isTwoFactorChallenge } from '@/modules/auth/types/auth.types'
import type { LoginResponse } from '@/modules/auth/types/auth.types'

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(localStorage.getItem('access_token'))
  const refreshToken = ref<string | null>(localStorage.getItem('refresh_token'))
  const user = ref<{ id: string; email: string; firstName: string; lastName: string; avatarUrl?: string; roles?: string[]; totpEnabled?: boolean } | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  // Two-factor authentication state (in-memory only, NOT persisted)
  const partialToken = ref<string | null>(null)
  const twoFactorRequired = computed(() => !!partialToken.value)

  // Impersonation state
  const impersonationTrigger = ref(0)
  const impersonatedUser = ref<{ id: string; firstName: string; lastName: string } | null>(null)

  const isAuthenticated = computed(() => !!accessToken.value)
  const isImpersonating = computed(() => {
    impersonationTrigger.value // dependency for reactivity
    return !!sessionStorage.getItem('admin_token_backup')
  })

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

    // Restore impersonation state on page refresh
    if (sessionStorage.getItem('admin_token_backup')) {
      const stored = sessionStorage.getItem('impersonated_user')
      impersonatedUser.value = stored ? JSON.parse(stored) : null
      impersonationTrigger.value++
    }

    initialized.value = true
  }

  async function login(email: string, password: string): Promise<boolean> {
    loading.value = true
    try {
      const { default: httpClient } = await import('@/shared/api/http-client')
      const response = await httpClient.post('/auth/login', { email, password })
      const data = response.data as LoginResponse

      if (isTwoFactorChallenge(data)) {
        partialToken.value = data.partial_token
        return true // indicates 2FA required
      }

      setTokens(data.access_token, data.refresh_token)
      await fetchUser()
      return false // normal login, no 2FA
    } finally {
      loading.value = false
    }
  }

  async function verifyTwoFactor(code: string, rememberDevice: boolean) {
    if (!partialToken.value) {
      throw new Error('No partial token available')
    }

    loading.value = true
    try {
      const { userApi } = await import('@/modules/auth/api/user.api')
      const data = await userApi.verifyTwoFactor(partialToken.value, code, rememberDevice)
      partialToken.value = null
      setTokens(data.access_token, data.refresh_token)
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
      // Clear impersonation state if active
      if (sessionStorage.getItem('admin_token_backup')) {
        sessionStorage.removeItem('admin_token_backup')
        sessionStorage.removeItem('admin_refresh_token_backup')
        sessionStorage.removeItem('impersonated_user')
        impersonatedUser.value = null
        impersonationTrigger.value++
      }
      clearTokens()
    }
  }

  async function fetchUser() {
    const { default: httpClient } = await import('@/shared/api/http-client')
    const response = await httpClient.get('/auth/me')
    user.value = response.data
  }

  async function startImpersonation(userId: string, reason: string) {
    const { startImpersonation: apiStartImpersonation } = await import(
      '@/modules/auth/api/admin.api'
    )

    // Backup current admin tokens
    sessionStorage.setItem('admin_token_backup', accessToken.value!)
    sessionStorage.setItem('admin_refresh_token_backup', refreshToken.value!)

    const response = await apiStartImpersonation(userId, reason)

    // Set impersonation token (no refresh token for impersonation)
    accessToken.value = response.access_token
    localStorage.setItem('access_token', response.access_token)
    refreshToken.value = null
    localStorage.removeItem('refresh_token')

    // Store impersonated user info
    impersonatedUser.value = {
      id: response.impersonated_user.id,
      firstName: response.impersonated_user.firstName,
      lastName: response.impersonated_user.lastName,
    }
    sessionStorage.setItem('impersonated_user', JSON.stringify(impersonatedUser.value))

    impersonationTrigger.value++

    // Load impersonated user's profile
    await fetchUser()
  }

  async function exitImpersonation() {
    // Restore admin tokens from backup
    const adminAccessToken = sessionStorage.getItem('admin_token_backup')
    const adminRefreshToken = sessionStorage.getItem('admin_refresh_token_backup')

    if (adminAccessToken) {
      accessToken.value = adminAccessToken
      localStorage.setItem('access_token', adminAccessToken)
    }
    if (adminRefreshToken) {
      refreshToken.value = adminRefreshToken
      localStorage.setItem('refresh_token', adminRefreshToken)
    }

    // Clear impersonation state
    sessionStorage.removeItem('admin_token_backup')
    sessionStorage.removeItem('admin_refresh_token_backup')
    sessionStorage.removeItem('impersonated_user')
    impersonatedUser.value = null
    impersonationTrigger.value++

    // Notify backend (fire-and-forget, audit is best-effort)
    try {
      const { endImpersonation: apiEndImpersonation } = await import(
        '@/modules/auth/api/admin.api'
      )
      await apiEndImpersonation()
    } catch {
      // Audit logging is best-effort
    }

    // Load admin's profile back
    await fetchUser()
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
    isImpersonating,
    impersonatedUser,
    partialToken,
    twoFactorRequired,
    setTokens,
    clearTokens,
    initialize,
    login,
    register,
    logout,
    fetchUser,
    verifyTwoFactor,
    startImpersonation,
    exitImpersonation,
    updateProfile,
    uploadAvatar,
    changePassword,
  }
})
