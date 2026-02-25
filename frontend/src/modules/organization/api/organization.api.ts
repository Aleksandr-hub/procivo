import httpClient from '@/shared/api/http-client'
import type { OrganizationDTO } from '@/modules/organization/types/organization.types'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'

export const organizationApi = {
  list(): Promise<OrganizationDTO[]> {
    return httpClient.get('/organizations').then((r) => r.data)
  },

  get(id: string): Promise<OrganizationDTO> {
    return httpClient.get(`/organizations/${id}`).then((r) => r.data)
  },

  create(data: { name: string; slug: string; description?: string | null }): Promise<IdResponse> {
    return httpClient.post('/organizations', data).then((r) => r.data)
  },

  update(
    id: string,
    data: { name: string; description?: string | null },
  ): Promise<MessageResponse> {
    return httpClient.put(`/organizations/${id}`, data).then((r) => r.data)
  },

  suspend(id: string): Promise<MessageResponse> {
    return httpClient.post(`/organizations/${id}/suspend`).then((r) => r.data)
  },
}
