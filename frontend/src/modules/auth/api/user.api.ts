import httpClient from '@/shared/api/http-client'
import type { UserDTO } from '@/modules/auth/types/auth.types'

export const userApi = {
  search(search: string, limit = 20): Promise<UserDTO[]> {
    return httpClient.get('/users', { params: { search, limit } }).then((r) => r.data)
  },
}
