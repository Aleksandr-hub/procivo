<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useProcessInstanceStore } from '@/modules/workflow/stores/process-instance.store'
import ProcessMonitorGraph from '@/modules/workflow/components/ProcessMonitorGraph.vue'
import AuditLogTimeline from '@/modules/audit/components/AuditLogTimeline.vue'
import { instanceStatusSeverity, tokenStatusSeverity } from '@/shared/utils/status-severity'
import { getApiErrorMessage } from '@/shared/utils/api-error'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const store = useProcessInstanceStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const instanceId = computed(() => route.params.instanceId as string)

onMounted(async () => {
  await Promise.all([
    store.fetchInstance(orgId.value, instanceId.value),
    store.fetchHistory(orgId.value, instanceId.value),
    store.fetchGraph(orgId.value, instanceId.value),
  ])
})

function goBack() {
  router.push({ name: 'process-instances', params: { orgId: orgId.value } })
}

async function cancelInstance() {
  try {
    await store.cancelProcess(orgId.value, instanceId.value)
    await store.fetchInstance(orgId.value, instanceId.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.instanceCancelled'), life: 3000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

function confirmCancel() {
  confirm.require({
    message: t('workflow.confirmCancelInstance'),
    header: t('common.confirm'),
    acceptLabel: t('workflow.cancelProcess'),
    rejectLabel: t('common.cancel'),
    acceptClass: 'p-button-danger',
    accept: () => cancelInstance(),
  })
}

function formatEventType(type: string) {
  return type.replace('workflow.', '').replace(/\./g, ' ').replace(/_/g, ' ')
}
</script>

<template>
  <div class="process-instance-detail">
    <div class="page-header">
      <div class="header-left">
        <Button icon="pi pi-arrow-left" text @click="goBack" />
        <h3>{{ store.currentInstance?.definition_name || t('workflow.instanceDetail') }}</h3>
        <Tag v-if="store.currentInstance" :value="t('workflow.instanceStatus_' + store.currentInstance.status)" :severity="instanceStatusSeverity(store.currentInstance.status)" />
      </div>
      <Button v-if="store.currentInstance?.status === 'running'" :label="t('workflow.cancel')" icon="pi pi-times" severity="danger" @click="confirmCancel" />
    </div>

    <div v-if="store.currentInstance" class="detail-content">
      <div v-if="store.graph" class="graph-section">
        <h4>{{ t('workflow.processGraph') }}</h4>
        <div class="graph-container">
          <ProcessMonitorGraph :graph="store.graph" :tokens="store.currentInstance.tokens" />
        </div>
      </div>

      <Divider />

      <div class="detail-section">
        <h4>{{ t('workflow.tokens') }}</h4>
        <DataTable :value="store.currentInstance.tokens" stripedRows size="small">
          <template #empty>
            <div class="empty-table">{{ t('workflow.noTokens') }}</div>
          </template>
          <Column field="id" :header="t('workflow.tokenId')" />
          <Column field="node_id" :header="t('workflow.tokenNodeId')" />
          <Column field="status" :header="t('workflow.status')">
            <template #body="{ data }">
              <Tag :value="t('workflow.tokenStatus_' + data.status)" :severity="tokenStatusSeverity(data.status)" />
            </template>
          </Column>
        </DataTable>
      </div>

      <Divider />

      <div class="detail-section">
        <h4>{{ t('workflow.eventHistory') }}</h4>
        <DataTable :value="store.history" stripedRows size="small" paginator :rows="10">
          <template #empty>
            <div class="empty-table">{{ t('workflow.noEvents') }}</div>
          </template>
          <Column field="version" header="#" style="width: 50px" />
          <Column field="event_type" :header="t('workflow.eventType')">
            <template #body="{ data }">
              <span class="event-type">{{ formatEventType(data.event_type) }}</span>
            </template>
          </Column>
          <Column field="occurred_at" :header="t('workflow.occurredAt')" />
        </DataTable>
      </div>

      <Divider />

      <Fieldset :legend="t('audit.activityTimeline')" :toggleable="true" :collapsed="true">
        <AuditLogTimeline
          :org-id="orgId"
          entity-type="process_instance"
          :entity-id="instanceId"
          :limit="20"
        />
      </Fieldset>
    </div>

    <div v-else-if="store.loading" class="loading">
      <i class="pi pi-spin pi-spinner" style="font-size: 2rem" />
    </div>
  </div>
</template>

<style scoped>
.process-instance-detail {
  max-width: 1200px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-left h3 {
  margin: 0;
}

.detail-section h4 {
  margin: 0 0 1rem;
}

.empty-table {
  text-align: center;
  padding: 1rem;
  color: var(--p-text-muted-color);
}

.event-type {
  text-transform: capitalize;
}

.graph-section h4 {
  margin: 0 0 1rem;
}

.graph-container {
  height: 400px;
  border: 1px solid var(--p-content-border-color);
  border-radius: 8px;
  overflow: hidden;
}

.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
}
</style>
