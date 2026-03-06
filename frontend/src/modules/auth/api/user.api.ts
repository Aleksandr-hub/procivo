import axios from 'axios'
import httpClient from '@/shared/api/http-client'
import type {
  UserDTO,
  TwoFactorSetupResponse,
  AuthTokensResponse,
} from '@/modules/auth/types/auth.types'

export const userApi = {
  search(search: string, limit = 20): Promise<UserDTO[]> {
    return httpClient.get('/users', { params: { search, limit } }).then((r) => r.data)
  },

  setupTwoFactor(): Promise<TwoFactorSetupResponse> {
    return httpClient.post('/auth/2fa/setup').then((r) => r.data)
  },

  confirmTwoFactor(code: string): Promise<void> {
    return httpClient.post('/auth/2fa/confirm', { code }).then(() => undefined)
  },

  verifyTwoFactor(
    partialToken: string,
    code: string,
    rememberDevice = false,
  ): Promise<AuthTokensResponse> {
    // Use a separate axios instance — partial token is not a full JWT
    // and must be passed manually in Authorization header
    return axios
      .post(
        `${import.meta.env.VITE_API_BASE_URL}/auth/2fa/verify`,
        { code, remember_device: rememberDevice },
        {
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${partialToken}`,
          },
        },
      )
      .then((r) => r.data)
  },

  disableTwoFactor(code: string): Promise<void> {
    return httpClient.post('/auth/2fa/disable', { code }).then(() => undefined)
  },
}
