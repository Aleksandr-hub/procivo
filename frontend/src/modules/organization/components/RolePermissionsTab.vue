<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { roleApi } from '@/modules/organization/api/role.api'
import type {
  RoleDTO,
  PermissionResource,
  PermissionAction,
} from '@/modules/organization/types/organization.types'

const props = defineProps<{
  orgId: string
  roleId: string
}>()

const toast = useToast()
const { t } = useI18n()

const role = ref<RoleDTO | null>(null)
const loading = ref(false)
const saving = ref(false)

const resources: PermissionResource[] = [
  'employee',
  'department',
  'position',
  'role',
  'invitation',
  'organization',
  'task',
  'workflow',
  'audit',
]

const actions: PermissionAction[] = ['view', 'create', 'update', 'delete', 'manage']

const scopeOptions = computed(() => [
  { label: '---', value: '' },
  { label: t('permissions.scopes.own'), value: 'own' },
  { label: t('permissions.scopes.subordinates'), value: 'subordinates' },
  { label: t('permissions.scopes.subordinates_tree'), value: 'subordinates_tree' },
  { label: t('permissions.scopes.department'), value: 'department' },
  { label: t('permissions.scopes.department_tree'), value: 'department_tree' },
  { label: t('permissions.scopes.organization'), value: 'organization' },
])

// Matrix state: resource -> action -> scope (empty string = no permission)
const matrix = ref<Record<string, Record<string, string>>>({})

function buildMatrix(r: RoleDTO) {
  const m: Record<string, Record<string, string>> = {}
  for (const res of resources) {
    m[res] = {}
    for (const act of actions) {
      m[res][act] = ''
    }
  }
  for (const perm of r.permissions) {
    if (m[perm.resource]) {
      m[perm.resource][perm.action] = perm.scope
    }
  }
  return m
}

async function fetchRole() {
  loading.value = true
  try {
    role.value = await roleApi.get(props.orgId, props.roleId)
    matrix.value = buildMatrix(role.value)
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('permissions.failedToLoad'),
      life: 5000,
    })
  } finally {
    loading.value = false
  }
}

watch(() => props.roleId, fetchRole, { immediate: true })

async function save() {
  if (!role.value) return
  saving.value = true
  try {
    // Compute diff: what to grant, what to revoke
    const existingPerms = role.value.permissions
    const desiredPerms: { resource: string; action: string; scope: string }[] = []

    for (const res of resources) {
      for (const act of actions) {
        const scope = matrix.value[res]?.[act]
        if (scope) {
          desiredPerms.push({ resource: res, action: act, scope })
        }
      }
    }

    // Revoke permissions that no longer exist or changed scope
    for (const perm of existingPerms) {
      const desired = desiredPerms.find(
        (d) => d.resource === perm.resource && d.action === perm.action,
      )
      if (!desired || desired.scope !== perm.scope) {
        await roleApi.revokePermission(props.orgId, props.roleId, perm.id)
      }
    }

    // Grant new or changed permissions
    for (const desired of desiredPerms) {
      const existing = existingPerms.find(
        (e) => e.resource === desired.resource && e.action === desired.action,
      )
      if (!existing || existing.scope !== desired.scope) {
        await roleApi.grantPermission(props.orgId, props.roleId, desired)
      }
    }

    // Refetch to sync state
    await fetchRole()

    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('permissions.saved'),
      life: 3000,
    })
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('permissions.failedToSave'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

function getResourceLabel(resource: string): string {
  return t(`permissions.resources.${resource}`, resource)
}

function getActionLabel(action: string): string {
  return t(`permissions.actions.${action}`, action)
}
</script>

<template>
  <div class="role-permissions-tab">
    <div v-if="loading" class="loading-state">
      <ProgressSpinner style="width: 40px; height: 40px" />
    </div>
    <template v-else-if="role">
      <div class="tab-info">
        <span class="role-name">{{ role.name }}</span>
        <Tag v-if="role.isSystem" :value="t('roles.system')" severity="secondary" />
      </div>

      <div class="matrix-table-wrapper">
        <table class="matrix-table">
          <thead>
            <tr>
              <th>{{ t('permissions.resource') }}</th>
              <th v-for="act in actions" :key="act">{{ getActionLabel(act) }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="res in resources" :key="res">
              <td class="resource-cell">{{ getResourceLabel(res) }}</td>
              <td v-for="act in actions" :key="act">
                <Select
                  v-model="matrix[res][act]"
                  :options="scopeOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="matrix-select"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="actions-bar">
        <Button
          :label="t('common.save')"
          icon="pi pi-check"
          :loading="saving"
          @click="save"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.role-permissions-tab {
  min-height: 200px;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 3rem;
}

.tab-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.role-name {
  font-weight: 600;
  font-size: 1.1rem;
}

.matrix-table-wrapper {
  overflow-x: auto;
}

.matrix-table {
  width: 100%;
  border-collapse: collapse;
}

.matrix-table th,
.matrix-table td {
  padding: 0.5rem;
  border: 1px solid var(--p-surface-200);
  text-align: left;
  white-space: nowrap;
}

.matrix-table th {
  background: var(--p-surface-50);
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: capitalize;
}

.resource-cell {
  font-weight: 500;
  text-transform: capitalize;
  min-width: 120px;
}

.matrix-select {
  width: 150px;
}

.actions-bar {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
}
</style>
