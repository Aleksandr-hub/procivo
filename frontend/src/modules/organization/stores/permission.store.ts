import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { roleApi } from '@/modules/organization/api/role.api'
import type {
  MyPermissionsResponse,
  PermissionResource,
  PermissionAction,
} from '@/modules/organization/types/organization.types'

export const usePermissionStore = defineStore('permission', () => {
  const data = ref<MyPermissionsResponse | null>(null)
  const loading = ref(false)

  const isOwner = computed(() => data.value?.isOwner ?? false)

  async function fetchMyPermissions(orgId: string) {
    loading.value = true
    try {
      data.value = await roleApi.getMyPermissions(orgId)
    } finally {
      loading.value = false
    }
  }

  function can(resource: PermissionResource, action: PermissionAction): boolean {
    if (!data.value) return false
    if (data.value.isOwner) return true

    return data.value.permissions.some(
      (p) => p.resource === resource && (p.action === action || p.action === 'manage'),
    )
  }

  function getScope(resource: PermissionResource, action: PermissionAction): string | null {
    if (!data.value) return null
    if (data.value.isOwner) return 'organization'

    const scopePriority = [
      'organization',
      'department_tree',
      'department',
      'subordinates_tree',
      'subordinates',
      'own',
    ]

    const matching = data.value.permissions
      .filter((p) => p.resource === resource && (p.action === action || p.action === 'manage'))
      .map((p) => p.scope)

    for (const scope of scopePriority) {
      if (matching.includes(scope as PermissionResource)) return scope
    }
    return null
  }

  function reset() {
    data.value = null
  }

  return { data, loading, isOwner, fetchMyPermissions, can, getScope, reset }
})
