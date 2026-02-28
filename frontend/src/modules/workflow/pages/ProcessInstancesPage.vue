<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useProcessInstanceStore } from '@/modules/workflow/stores/process-instance.store'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import type { ProcessInstanceDTO } from '@/modules/workflow/types/process-instance.types'
import { instanceStatusSeverity } from '@/shared/utils/status-severity'
import { getApiErrorMessage } from '@/shared/utils/api-error'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const instanceStore = useProcessInstanceStore()
const defStore = useProcessDefinitionStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const showStartDialog = ref(false)
const selectedDefId = ref<string | null>(null)
const filterStatus = ref<string | undefined>(undefined)

const statusOptions = [
  { label: t('workflow.instanceStatus_running'), value: 'running' },
  { label: t('workflow.instanceStatus_completed'), value: 'completed' },
  { label: t('workflow.instanceStatus_cancelled'), value: 'cancelled' },
  { label: t('workflow.instanceStatus_error'), value: 'error' },
]

function getActiveTokenCount(instance: ProcessInstanceDTO): number {
  return instance.tokens.filter(
    (tok) => tok.status === 'active' || tok.status === 'waiting',
  ).length
}

async function onFilterChange() {
  await instanceStore.fetchInstances(orgId.value, filterStatus.value)
}

onMounted(async () => {
  await Promise.all([
    instanceStore.fetchInstances(orgId.value),
    defStore.fetchDefinitions(orgId.value, 'published'),
  ])
})

async function startProcess() {
  if (!selectedDefId.value) return
  try {
    await instanceStore.startProcess(orgId.value, selectedDefId.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.instanceStarted'), life: 3000 })
    showStartDialog.value = false
    selectedDefId.value = null
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

function viewDetail(instance: ProcessInstanceDTO) {
  router.push({ name: 'process-instance-detail', params: { orgId: orgId.value, instanceId: instance.id } })
}

async function cancelInstance(instance: ProcessInstanceDTO) {
  try {
    await instanceStore.cancelProcess(orgId.value, instance.id)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.instanceCancelled'), life: 3000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}
</script>

<template>
  <div class="process-instances-page">
    <div class="page-header">
      <h3>{{ t('workflow.processInstances') }}</h3>
      <Button :label="t('workflow.startProcess')" icon="pi pi-play" @click="showStartDialog = true" />
    </div>

    <div class="tab-toolbar">
      <Select
        v-model="filterStatus"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('workflow.allStatuses')"
        showClear
        @change="onFilterChange"
        style="width: 200px"
      />
    </div>

    <DataTable :value="instanceStore.instances" :loading="instanceStore.loading" stripedRows paginator :rows="20">
      <template #empty>
        <div class="empty-table">{{ t('workflow.noInstancesFound') }}</div>
      </template>
      <Column field="definition_name" :header="t('workflow.definitionName')" sortable />
      <Column field="status" :header="t('workflow.status')" sortable>
        <template #body="{ data }">
          <Tag :value="t('workflow.instanceStatus_' + data.status)" :severity="instanceStatusSeverity(data.status)" />
        </template>
      </Column>
      <Column :header="t('workflow.activeTokens')" style="width: 140px">
        <template #body="{ data }">
          <Tag
            v-if="data.status === 'running'"
            :value="String(getActiveTokenCount(data))"
            :severity="getActiveTokenCount(data) > 0 ? 'warn' : 'secondary'"
          />
          <span v-else>—</span>
        </template>
      </Column>
      <Column field="started_at" :header="t('workflow.startedAt')" sortable />
      <Column :header="t('common.actions')">
        <template #body="{ data }">
          <Button icon="pi pi-eye" text size="small" @click="viewDetail(data)" />
          <Button v-if="data.status === 'running'" icon="pi pi-times" text size="small" severity="danger" @click="cancelInstance(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showStartDialog" :header="t('workflow.startProcess')" modal :style="{ width: '400px' }">
      <div class="form-group">
        <label>{{ t('workflow.selectDefinition') }}</label>
        <Select v-model="selectedDefId" :options="defStore.definitions" option-value="id" option-label="name" :placeholder="t('workflow.selectDefinition')" class="w-full" />
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showStartDialog = false" />
        <Button :label="t('workflow.start')" @click="startProcess" :disabled="!selectedDefId" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.process-instances-page {
  max-width: 1200px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.page-header h3 {
  margin: 0;
}

.tab-toolbar {
  margin-bottom: 1rem;
}

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}
</style>
