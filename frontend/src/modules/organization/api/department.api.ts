import httpClient from '@/shared/api/http-client'
import type { DepartmentDTO, DepartmentTreeDTO } from '@/modules/organization/types/organization.types'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'

export const departmentApi = {
  tree(orgId: string): Promise<DepartmentTreeDTO[]> {
    return httpClient.get(`/organizations/${orgId}/departments/tree`).then((r) => r.data)
  },

  get(orgId: string, deptId: string): Promise<DepartmentDTO> {
    return httpClient.get(`/organizations/${orgId}/departments/${deptId}`).then((r) => r.data)
  },

  create(
    orgId: string,
    data: {
      name: string
      code: string
      parent_id?: string | null
      description?: string | null
      sort_order?: number
    },
  ): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/departments`, data).then((r) => r.data)
  },

  update(
    orgId: string,
    deptId: string,
    data: { name: string; description?: string | null; sort_order?: number },
  ): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/departments/${deptId}`, data)
      .then((r) => r.data)
  },

  move(orgId: string, deptId: string, newParentId: string | null): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/departments/${deptId}/move`, {
        new_parent_id: newParentId,
      })
      .then((r) => r.data)
  },

  delete(orgId: string, deptId: string): Promise<void> {
    return httpClient.delete(`/organizations/${orgId}/departments/${deptId}`)
  },
}
