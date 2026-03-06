<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { permissionApi } from '@/modules/organization/api/permission.api'
import type { PermissionResource, PermissionAction } from '@/modules/organization/types/organization.types'

const props = defineProps<{
  orgId: string
  departmentId: string
}>()

const toast = useToast()
const { t } = useI18n()

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

const matrix = ref<Record<string, Record<string, string>>>({})

function initEmptyMatrix(): Record<string, Record<string, string>> {
  const m: Record<string, Record<string, string>> = {}
  for (const res of resources) {
    m[res] = {}
    for (const act of actions) {
      m[res][act] = ''
    }
  }
  return m
}

async function fetchPermissions() {
  loading.value = true
  try {
    const perms = await permissionApi.getDepartmentPermissions(props.orgId, props.departmentId)
    const m = initEmptyMatrix()
    for (const p of perms) {
      if (m[p.resource]) {
        m[p.resource][p.action] = p.scope
      }
    }
    matrix.value = m
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

watch(() => props.departmentId, fetchPermissions, { immediate: true })

async function save() {
  saving.value = true
  try {
    const permissions: { resource: string; action: string; scope: string }[] = []
    for (const res of resources) {
      for (const act of actions) {
        const scope = matrix.value[res]?.[act]
        if (scope) {
          permissions.push({ resource: res, action: act, scope })
        }
      }
    }

    await permissionApi.setDepartmentPermissions(props.orgId, props.departmentId, permissions)

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
  <div class="dept-permissions-tab">
    <Message severity="info" :closable="false" class="dept-info-message">
      {{ t('permissions.departmentDefaultsInfo') }}
    </Message>

    <div v-if="loading" class="loading-state">
      <ProgressSpinner style="width: 40px; height: 40px" />
    </div>
    <template v-else>
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
.dept-permissions-tab {
  min-height: 200px;
}

.dept-info-message {
  margin-bottom: 1rem;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 3rem;
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
