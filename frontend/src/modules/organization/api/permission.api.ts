import httpClient from '@/shared/api/http-client'
import type {
  DepartmentPermissionDTO,
  UserPermissionOverrideDTO,
  EffectivePermissionDTO,
  ProcessDefinitionAccessDTO,
} from '@/modules/organization/types/organization.types'
import type { MessageResponse } from '@/shared/types/api.types'

export const permissionApi = {
  getDepartmentPermissions(
    orgId: string,
    deptId: string,
  ): Promise<DepartmentPermissionDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/permissions/departments/${deptId}`)
      .then((r) => r.data)
  },

  setDepartmentPermissions(
    orgId: string,
    deptId: string,
    permissions: { resource: string; action: string; scope: string }[],
  ): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/permissions/departments/${deptId}`, { permissions })
      .then((r) => r.data)
  },

  getUserOverrides(
    orgId: string,
    employeeId: string,
  ): Promise<UserPermissionOverrideDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/permissions/users/${employeeId}`)
      .then((r) => r.data)
  },

  setUserOverride(
    orgId: string,
    employeeId: string,
    data: { resource: string; action: string; effect: 'allow' | 'deny'; scope: string },
  ): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/permissions/users/${employeeId}`, data)
      .then((r) => r.data)
  },

  removeUserOverride(
    orgId: string,
    employeeId: string,
    overrideId: string,
  ): Promise<void> {
    return httpClient
      .delete(`/organizations/${orgId}/permissions/users/${employeeId}/${overrideId}`)
      .then(() => {})
  },

  getEffectivePermissions(
    orgId: string,
    employeeId: string,
  ): Promise<EffectivePermissionDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/permissions/users/${employeeId}/effective`)
      .then((r) => r.data)
  },

  getProcessDefinitionAccess(
    orgId: string,
    definitionId: string,
  ): Promise<ProcessDefinitionAccessDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/process-definitions/${definitionId}/access`)
      .then((r) => r.data)
  },

  setProcessDefinitionAccess(
    orgId: string,
    definitionId: string,
    rules: { departmentId: string | null; roleId: string | null; accessType: 'view' | 'start' }[],
  ): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/process-definitions/${definitionId}/access`, { rules })
      .then((r) => r.data)
  },
}
