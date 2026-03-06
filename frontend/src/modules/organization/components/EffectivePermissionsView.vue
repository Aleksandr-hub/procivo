<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { permissionApi } from '@/modules/organization/api/permission.api'
import type { EffectivePermissionDTO } from '@/modules/organization/types/organization.types'

const props = defineProps<{
  orgId: string
  employeeId: string
}>()

const toast = useToast()
const { t } = useI18n()

const permissions = ref<EffectivePermissionDTO[]>([])
const loading = ref(false)

async function fetchEffective() {
  loading.value = true
  try {
    permissions.value = await permissionApi.getEffectivePermissions(props.orgId, props.employeeId)
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

watch(() => props.employeeId, fetchEffective, { immediate: true })

function getSourceSeverity(source: string): string {
  switch (source) {
    case 'role':
      return 'info'
    case 'department':
      return 'warn'
    case 'user_override':
      return 'success'
    default:
      return 'secondary'
  }
}

function getSourceLabel(source: string): string {
  return t(`permissions.sources.${source}`, source)
}
</script>

<template>
  <div class="effective-permissions-view">
    <h4>{{ t('permissions.effectiveTitle') }}</h4>

    <DataTable
      :value="permissions"
      :loading="loading"
      stripedRows
    >
      <template #empty>
        <div class="empty-table">{{ t('permissions.noEffectivePermissions') }}</div>
      </template>
      <Column field="resource" :header="t('permissions.resource')" sortable style="min-width: 120px">
        <template #body="{ data }">
          <span class="resource-name">{{ data.resource }}</span>
        </template>
      </Column>
      <Column field="action" :header="t('permissions.action')" sortable style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.action" severity="info" />
        </template>
      </Column>
      <Column field="scope" :header="t('permissions.scope')" style="min-width: 140px">
        <template #body="{ data }">
          {{ t(`permissions.scopes.${data.scope}`, data.scope) }}
        </template>
      </Column>
      <Column field="source" :header="t('permissions.source')" style="width: 160px">
        <template #body="{ data }">
          <Tag :value="getSourceLabel(data.source)" :severity="getSourceSeverity(data.source)" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<style scoped>
.effective-permissions-view h4 {
  margin: 0 0 1rem 0;
}

.resource-name {
  font-weight: 500;
  text-transform: capitalize;
}

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}
</style>
