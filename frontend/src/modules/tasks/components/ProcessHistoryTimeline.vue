<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { processInstanceApi } from '@/modules/workflow/api/process-instance.api'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import { formatDateTime } from '@/shared/utils/date-format'
import type { ProcessEventDTO } from '@/modules/workflow/types/process-instance.types'

const props = defineProps<{
  orgId: string
  processInstanceId: string
  fieldLabels?: Record<string, string>
}>()

const { t } = useI18n()
const events = ref<ProcessEventDTO[]>([])
const loading = ref(false)
const allFieldLabels = ref<Record<string, string>>({})

interface TimelineItem {
  icon: string
  color: string
  title: string
  details: Record<string, unknown> | null
  time: string
}

const eventConfig: Record<string, { icon: string; color: string; labelKey: string }> = {
  'workflow.process.started': { icon: 'pi pi-play', color: 'purple', labelKey: 'history.processStarted' },
  'workflow.task_node.activated': { icon: 'pi pi-user', color: 'purple', labelKey: 'history.taskActivated' },
  'workflow.process.variables.merged': {
    icon: 'pi pi-file-edit',
    color: 'purple',
    labelKey: 'history.formSubmitted',
  },
  'workflow.token.completed': { icon: 'pi pi-check-circle', color: 'success', labelKey: 'history.stageCompleted' },
  'workflow.process.completed': { icon: 'pi pi-flag', color: 'success', labelKey: 'history.processCompleted' },
  'workflow.process.cancelled': { icon: 'pi pi-times-circle', color: 'danger', labelKey: 'history.processCancelled' },
}

const visibleEventTypes = new Set(Object.keys(eventConfig))

function applyLabels(data: Record<string, unknown>): Record<string, unknown> {
  const labels = { ...allFieldLabels.value, ...props.fieldLabels }
  if (Object.keys(labels).length === 0) return data
  const result: Record<string, unknown> = {}
  for (const [key, value] of Object.entries(data)) {
    const label = labels[key] ?? key
    result[label] = value
  }
  return result
}

const timelineItems = computed<TimelineItem[]>(() => {
  return events.value
    .filter((e) => visibleEventTypes.has(e.event_type))
    .map((e) => {
      const config = eventConfig[e.event_type]!
      let title = t(config.labelKey)
      let details: Record<string, unknown> | null = null

      if (e.event_type === 'workflow.task_node.activated') {
        const nodeName = (e.payload.nodeName as string) ?? (e.payload.node_name as string) ?? ''
        title = t('history.taskActivated', { name: nodeName })
      } else if (e.event_type === 'workflow.process.variables.merged') {
        const mergedData = (e.payload.mergedData ?? e.payload.merged_data) as Record<string, unknown> | undefined
        if (mergedData && Object.keys(mergedData).length > 0) {
          details = applyLabels(mergedData)
        }
      } else if (e.event_type === 'workflow.process.started') {
        const vars = e.payload.variables as Record<string, unknown> | undefined
        if (vars) {
          const userVars = Object.fromEntries(
            Object.entries(vars).filter(([k]) => !k.startsWith('_')),
          )
          if (Object.keys(userVars).length > 0) {
            details = applyLabels(userVars)
          }
        }
      }

      return {
        icon: config.icon,
        color: config.color,
        title,
        details,
        time: formatDateTime(e.occurred_at),
      }
    })
    .reverse()
})

onMounted(async () => {
  loading.value = true
  try {
    const [historyEvents, instanceData] = await Promise.all([
      processInstanceApi.history(props.orgId, props.processInstanceId),
      processInstanceApi.get(props.orgId, props.processInstanceId),
    ])
    events.value = historyEvents

    // Fetch start form fields to get labels for start variables
    try {
      const startForm = await processDefinitionApi.getStartForm(props.orgId, instanceData.definition_id)
      for (const field of startForm.fields) {
        allFieldLabels.value[field.name] = field.label
      }
    } catch {
      // Start form may not be available — ignore
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="process-history">
    <div v-if="loading" class="history-loading">
      <ProgressSpinner style="width: 2rem; height: 2rem" />
    </div>

    <div v-else-if="timelineItems.length === 0" class="history-empty">
      <i class="pi pi-clock" />
      <p>{{ t('history.noEvents') }}</p>
    </div>

    <Timeline v-else :value="timelineItems" align="left">
      <template #marker="{ item }">
        <span class="timeline-marker" :class="item.color">
          <i :class="item.icon" />
        </span>
      </template>
      <template #content="{ item }">
        <div class="timeline-event">
          <div class="event-title">{{ item.title }}</div>
          <div v-if="item.details" class="event-details">
            <div v-for="(value, key) in item.details" :key="key" class="detail-row">
              <span class="detail-key">{{ key }}:</span>
              <span class="detail-value">{{ value }}</span>
            </div>
          </div>
          <small class="event-time">{{ item.time }}</small>
        </div>
      </template>
    </Timeline>
  </div>
</template>

<style scoped>
.history-loading,
.history-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 0.75rem;
  color: var(--p-text-muted-color);
}

.history-empty i {
  font-size: 2rem;
}

.timeline-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  background: var(--p-surface-card);
  border: 2px solid var(--p-purple-500);
  position: relative;
}

/* Inner dot */
.timeline-marker::after {
  content: '';
  position: absolute;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: var(--p-purple-500);
  display: none;
}

.timeline-marker.purple {
  border-color: var(--p-purple-500);
  color: var(--p-purple-500);
}

.timeline-marker.success {
  border-color: var(--p-green-500);
  color: var(--p-green-500);
}

.timeline-marker.success::after {
  background: var(--p-green-500);
}

.timeline-marker.danger {
  border-color: var(--p-red-500);
  color: var(--p-red-500);
}

.timeline-marker.danger::after {
  background: var(--p-red-500);
}

.timeline-marker .pi {
  font-size: 0.65rem;
}

/* Purple connector line */
.process-history :deep(.p-timeline-event-connector) {
  background: color-mix(in srgb, var(--p-purple-500) 30%, transparent);
}

.event-title {
  font-weight: 600;
  font-size: 0.875rem;
}

.event-details {
  margin-top: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: var(--p-surface-ground);
  border-radius: var(--p-border-radius);
  font-size: 0.8rem;
}

.detail-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.15rem;
}

.detail-key {
  color: var(--p-text-muted-color);
  min-width: 80px;
  flex-shrink: 0;
}

.detail-value {
  word-break: break-word;
}

.event-time {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}
</style>
