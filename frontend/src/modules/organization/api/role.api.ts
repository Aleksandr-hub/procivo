import httpClient from '@/shared/api/http-client'
import type {
  RoleDTO,
  MyPermissionsResponse,
} from '@/modules/organization/types/organization.types'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'

export const roleApi = {
  list(orgId: string): Promise<RoleDTO[]> {
    return httpClient.get(`/organizations/${orgId}/roles`).then((r) => r.data)
  },

  get(orgId: string, roleId: string): Promise<RoleDTO> {
    return httpClient.get(`/organizations/${orgId}/roles/${roleId}`).then((r) => r.data)
  },

  create(
    orgId: string,
    data: { name: string; description?: string | null; hierarchy: number },
  ): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/roles`, data).then((r) => r.data)
  },

  update(
    orgId: string,
    roleId: string,
    data: { name: string; description?: string | null; hierarchy: number },
  ): Promise<MessageResponse> {
    return httpClient.put(`/organizations/${orgId}/roles/${roleId}`, data).then((r) => r.data)
  },

  delete(orgId: string, roleId: string): Promise<void> {
    return httpClient.delete(`/organizations/${orgId}/roles/${roleId}`).then(() => {})
  },

  grantPermission(
    orgId: string,
    roleId: string,
    data: { resource: string; action: string; scope: string },
  ): Promise<IdResponse> {
    return httpClient
      .post(`/organizations/${orgId}/roles/${roleId}/permissions`, data)
      .then((r) => r.data)
  },

  revokePermission(orgId: string, roleId: string, permissionId: string): Promise<void> {
    return httpClient
      .delete(`/organizations/${orgId}/roles/${roleId}/permissions/${permissionId}`)
      .then(() => {})
  },

  getEmployeeRoles(orgId: string, employeeId: string): Promise<RoleDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/employees/${employeeId}/roles`)
      .then((r) => r.data)
  },

  assignRole(orgId: string, employeeId: string, roleId: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/employees/${employeeId}/roles`, { role_id: roleId })
      .then((r) => r.data)
  },

  revokeRole(orgId: string, employeeId: string, roleId: string): Promise<void> {
    return httpClient
      .delete(`/organizations/${orgId}/employees/${employeeId}/roles/${roleId}`)
      .then(() => {})
  },

  getMyPermissions(orgId: string): Promise<MyPermissionsResponse> {
    return httpClient.get(`/organizations/${orgId}/my-permissions`).then((r) => r.data)
  },
}
