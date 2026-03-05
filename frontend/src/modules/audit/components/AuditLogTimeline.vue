<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { auditLogApi } from '@/modules/audit/api/audit-log.api'
import { formatDateTime } from '@/shared/utils/date-format'
import type { AuditLogDTO } from '@/modules/audit/types/audit-log.types'

const props = withDefaults(
  defineProps<{
    orgId: string
    entityType?: string
    entityId?: string
    limit?: number
  }>(),
  {
    limit: 20,
  },
)

const router = useRouter()
const { t } = useI18n()

const entries = ref<AuditLogDTO[]>([])
const loading = ref(false)

interface EventConfig {
  icon: string
  color: string
  labelKey: string
}

const eventConfigMap: Record<string, EventConfig> = {
  'task_manager.task.created': { icon: 'pi pi-plus', color: '#8B5CF6', labelKey: 'audit.taskCreated' },
  'task_manager.task.status_changed': { icon: 'pi pi-refresh', color: '#3B82F6', labelKey: 'audit.taskStatusChanged' },
  'task_manager.task.assigned': { icon: 'pi pi-user', color: '#3B82F6', labelKey: 'audit.taskAssigned' },
  'task_manager.task.claimed': { icon: 'pi pi-check-circle', color: '#10B981', labelKey: 'audit.taskClaimed' },
  'task_manager.task.unclaimed': { icon: 'pi pi-undo', color: '#F59E0B', labelKey: 'audit.taskUnclaimed' },
  'task_manager.task.deleted': { icon: 'pi pi-trash', color: '#EF4444', labelKey: 'audit.taskDeleted' },
  'task_manager.comment.added': { icon: 'pi pi-comment', color: '#6366F1', labelKey: 'audit.commentAdded' },
  'workflow.process.started': { icon: 'pi pi-play', color: '#8B5CF6', labelKey: 'audit.processStarted' },
  'workflow.process.completed': { icon: 'pi pi-flag', color: '#10B981', labelKey: 'audit.processCompleted' },
  'workflow.process.cancelled': { icon: 'pi pi-times-circle', color: '#EF4444', labelKey: 'audit.processCancelled' },
  'identity.user.registered': { icon: 'pi pi-user-plus', color: '#8B5CF6', labelKey: 'audit.userRegistered' },
  'identity.user.password_changed': { icon: 'pi pi-lock', color: '#F59E0B', labelKey: 'audit.passwordChanged' },
}

const fallbackConfig: EventConfig = { icon: 'pi pi-circle', color: '#9CA3AF', labelKey: '' }

function getConfig(eventType: string): EventConfig {
  return eventConfigMap[eventType] ?? fallbackConfig
}

function getLabel(entry: AuditLogDTO): string {
  const config = getConfig(entry.event_type)
  if (config.labelKey) {
    return t(config.labelKey)
  }
  return entry.event_type
}

function isNavigable(entry: AuditLogDTO): boolean {
  return entry.entity_type === 'task' || entry.entity_type === 'process_instance'
}

function navigateToEntity(entry: AuditLogDTO): void {
  switch (entry.entity_type) {
    case 'task':
      router.push(`/organizations/${props.orgId}/tasks/${entry.entity_id}`)
      break
    case 'process_instance':
      router.push(`/organizations/${props.orgId}/process-instances/${entry.entity_id}`)
      break
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const params: Record<string, unknown> = { limit: props.limit }
    if (props.entityType) params.entity_type = props.entityType
    if (props.entityId) params.entity_id = props.entityId

    const response = await auditLogApi.list(props.orgId, params)
    entries.value = response.items
  } catch {
    // Non-critical — audit timeline is informational
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="audit-log-timeline">
    <div v-if="loading" class="timeline-loading">
      <ProgressSpinner style="width: 2rem; height: 2rem" />
    </div>

    <div v-else-if="entries.length === 0" class="timeline-empty">
      <i class="pi pi-history" />
      <p>{{ t('audit.noActivity') }}</p>
    </div>

    <Timeline v-else :value="entries" align="left">
      <template #marker="{ item }">
        <span
          class="audit-marker"
          :style="{ borderColor: getConfig(item.event_type).color, color: getConfig(item.event_type).color }"
        >
          <i :class="getConfig(item.event_type).icon" />
        </span>
      </template>
      <template #content="{ item }">
        <div class="audit-event">
          <a
            v-if="isNavigable(item)"
            href="#"
            class="event-label event-label--link"
            role="link"
            @click.prevent="navigateToEntity(item)"
          >{{ getLabel(item) }}</a>
          <div v-else class="event-label">{{ getLabel(item) }}</div>
          <div v-if="item.changes && Object.keys(item.changes).length > 0" class="event-changes">
            <div
              v-for="(value, key) in item.changes"
              :key="key"
              class="change-row"
            >
              <span class="change-key">{{ key }}:</span>
              <span class="change-value">{{ String(value) }}</span>
            </div>
          </div>
          <small class="event-time">{{ formatDateTime(item.occurred_at) }}</small>
        </div>
      </template>
    </Timeline>
  </div>
</template>

<style scoped>
.timeline-loading,
.timeline-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 0.75rem;
  color: var(--p-text-muted-color);
}

.timeline-empty i {
  font-size: 2rem;
}

.audit-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  background: var(--p-surface-card);
  border: 2px solid currentColor;
  flex-shrink: 0;
}

.audit-marker .pi {
  font-size: 0.65rem;
}

.audit-log-timeline :deep(.p-timeline-event-connector) {
  background: color-mix(in srgb, var(--p-surface-border) 60%, transparent);
}

.audit-event {
  padding-bottom: 0.5rem;
}

.event-label {
  font-weight: 600;
  font-size: 0.875rem;
  line-height: 1.4;
}

.event-label--link {
  cursor: pointer;
  color: var(--p-primary-color);
  text-decoration: none;
}

.event-label--link:hover {
  text-decoration: underline;
}

.event-changes {
  margin-top: 0.4rem;
  padding: 0.4rem 0.6rem;
  background: var(--p-surface-ground);
  border-radius: var(--p-border-radius);
  font-size: 0.8rem;
}

.change-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.15rem;
}

.change-key {
  color: var(--p-text-muted-color);
  min-width: 60px;
  flex-shrink: 0;
}

.change-value {
  word-break: break-word;
}

.event-time {
  display: block;
  margin-top: 0.25rem;
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}
</style>
