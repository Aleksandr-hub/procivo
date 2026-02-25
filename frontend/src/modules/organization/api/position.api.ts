import httpClient from '@/shared/api/http-client'
import type { PositionDTO } from '@/modules/organization/types/organization.types'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'

export const positionApi = {
  list(orgId: string, departmentId?: string): Promise<PositionDTO[]> {
    const params = departmentId ? { department_id: departmentId } : {}
    return httpClient.get(`/organizations/${orgId}/positions`, { params }).then((r) => r.data)
  },

  create(
    orgId: string,
    data: {
      department_id: string
      name: string
      description?: string | null
      sort_order?: number
      is_head?: boolean
    },
  ): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/positions`, data).then((r) => r.data)
  },

  update(
    orgId: string,
    posId: string,
    data: {
      name: string
      description?: string | null
      sort_order?: number
      is_head?: boolean
    },
  ): Promise<MessageResponse> {
    return httpClient.put(`/organizations/${orgId}/positions/${posId}`, data).then((r) => r.data)
  },

  delete(orgId: string, posId: string): Promise<void> {
    return httpClient.delete(`/organizations/${orgId}/positions/${posId}`)
  },
}
