<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { formatRelativeDate } from '@/shared/utils/date-format'
import type { ProcessInstanceDTO, ProcessInstanceStatus } from '@/modules/workflow/types/process-instance.types'

const props = defineProps<{
  processes: ProcessInstanceDTO[]
  orgId: string
}>()

const router = useRouter()
const { t } = useI18n()

const DISPLAY_LIMIT = 10

const displayedProcesses = computed(() => props.processes.slice(0, DISPLAY_LIMIT))
const remainingCount = computed(() => Math.max(0, props.processes.length - DISPLAY_LIMIT))

function statusSeverity(status: ProcessInstanceStatus): string {
  switch (status) {
    case 'running':
      return 'info'
    case 'completed':
      return 'success'
    case 'cancelled':
      return 'secondary'
    case 'error':
      return 'danger'
    default:
      return 'secondary'
  }
}

function navigateToProcess(instanceId: string) {
  router.push({ name: 'process-instance-detail', params: { orgId: props.orgId, instanceId } })
}

function navigateToAllProcesses() {
  router.push({ name: 'process-instances', params: { orgId: props.orgId } })
}
</script>

<template>
  <div class="active-processes-widget">
    <div v-if="processes.length === 0" class="empty-state">
      <i class="pi pi-play empty-icon" />
      <p>{{ t('dashboard.noProcesses') }}</p>
    </div>

    <template v-else>
      <div
        v-for="process in displayedProcesses"
        :key="process.id"
        class="process-row"
        @click="navigateToProcess(process.id)"
      >
        <div class="process-info">
          <span class="process-name">{{ process.definition_name }}</span>
          <small class="process-time">{{ formatRelativeDate(process.started_at) }}</small>
        </div>
        <div class="process-meta">
          <Tag :value="process.status" :severity="statusSeverity(process.status)" class="status-tag" />
        </div>
      </div>

      <div v-if="remainingCount > 0" class="more-link" @click="navigateToAllProcesses">
        {{ t('dashboard.andMore', { count: remainingCount }) }}
      </div>
    </template>
  </div>
</template>

<style scoped>
.active-processes-widget {
  overflow-y: auto;
  max-height: 320px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 0.75rem;
  color: var(--p-text-muted-color);
}

.empty-icon {
  font-size: 2rem;
  color: var(--p-text-muted-color);
}

.process-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.15s;
  border: 1px solid transparent;
}

.process-row:hover {
  background: var(--p-surface-hover);
  border-color: var(--p-surface-border);
}

.process-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  flex: 1;
  min-width: 0;
}

.process-name {
  font-size: 0.875rem;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.process-time {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.process-meta {
  flex-shrink: 0;
}

.status-tag {
  font-size: 0.7rem !important;
}

.more-link {
  margin-top: 0.5rem;
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
  color: var(--p-primary-color);
  cursor: pointer;
  text-align: center;
  border-radius: 6px;
  transition: background-color 0.15s;
}

.more-link:hover {
  background: var(--p-surface-hover);
}
</style>
