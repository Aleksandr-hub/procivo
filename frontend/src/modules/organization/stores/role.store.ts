import { ref } from 'vue'
import { defineStore } from 'pinia'
import { roleApi } from '@/modules/organization/api/role.api'
import type { RoleDTO } from '@/modules/organization/types/organization.types'

export const useRoleStore = defineStore('role', () => {
  const roles = ref<RoleDTO[]>([])
  const currentRole = ref<RoleDTO | null>(null)
  const loading = ref(false)

  async function fetchRoles(orgId: string) {
    loading.value = true
    try {
      roles.value = await roleApi.list(orgId)
    } finally {
      loading.value = false
    }
  }

  async function fetchRole(orgId: string, roleId: string) {
    loading.value = true
    try {
      currentRole.value = await roleApi.get(orgId, roleId)
    } finally {
      loading.value = false
    }
  }

  async function createRole(
    orgId: string,
    data: { name: string; description?: string | null; hierarchy: number },
  ) {
    const result = await roleApi.create(orgId, data)
    await fetchRoles(orgId)
    return result.id
  }

  async function updateRole(
    orgId: string,
    roleId: string,
    data: { name: string; description?: string | null; hierarchy: number },
  ) {
    await roleApi.update(orgId, roleId, data)
    await fetchRoles(orgId)
  }

  async function deleteRole(orgId: string, roleId: string) {
    await roleApi.delete(orgId, roleId)
    await fetchRoles(orgId)
  }

  async function grantPermission(
    orgId: string,
    roleId: string,
    data: { resource: string; action: string; scope: string },
  ) {
    const result = await roleApi.grantPermission(orgId, roleId, data)
    await fetchRole(orgId, roleId)
    return result.id
  }

  async function revokePermission(orgId: string, roleId: string, permissionId: string) {
    await roleApi.revokePermission(orgId, roleId, permissionId)
    await fetchRole(orgId, roleId)
  }

  return {
    roles,
    currentRole,
    loading,
    fetchRoles,
    fetchRole,
    createRole,
    updateRole,
    deleteRole,
    grantPermission,
    revokePermission,
  }
})
