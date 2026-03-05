<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import type { TaskDTO } from '@/modules/tasks/types/task.types'
import { taskStatusSeverity, taskPrioritySeverity } from '@/shared/utils/status-severity'
import { formatDate, isOverdue } from '@/shared/utils/date-format'

const props = defineProps<{
  task: TaskDTO
  active: boolean
}>()

defineEmits<{
  click: []
}>()

const { t } = useI18n()
const empStore = useEmployeeStore()

const statusLabelKeys: Record<string, string> = {
  draft: 'tasks.statusDraft',
  open: 'tasks.statusOpen',
  in_progress: 'tasks.statusInProgress',
  review: 'tasks.statusReview',
  done: 'tasks.statusDone',
  blocked: 'tasks.statusBlocked',
  cancelled: 'tasks.statusCancelled',
}

const priorityLabelKeys: Record<string, string> = {
  low: 'tasks.priorityLow',
  medium: 'tasks.priorityMedium',
  high: 'tasks.priorityHigh',
  critical: 'tasks.priorityCritical',
}

const isWorkflow = computed(() => !!props.task.workflow_summary)

const shortId = computed(() => `TASK-${String(props.task.sequenceNumber).padStart(3, '0')}`)

const assigneeName = computed(() => {
  if (!props.task.assigneeId) return null
  const emp = empStore.employees.find((e) => e.id === props.task.assigneeId)
  return emp?.userFullName ?? null
})

const deadlineText = computed(() => {
  if (!props.task.dueDate) return null
  return formatDate(props.task.dueDate)
})
</script>

<template>
  <div
    class="task-card"
    :class="{ active }"
    @click="$emit('click')"
  >
    <!-- Type icon (left) -->
    <div class="card-icon" :class="isWorkflow ? 'workflow' : 'regular'">
      <i :class="isWorkflow ? 'pi pi-sitemap' : 'pi pi-list'" />
    </div>

    <!-- Main content (center) -->
    <div class="card-content">
      <!-- Meta line: task ID + process context -->
      <div class="card-meta-line">
        <span class="task-short-id">{{ shortId }}</span>
        <template v-if="task.workflow_summary">
          <span class="meta-separator">•</span>
          <span class="process-context">{{ task.workflow_summary.process_name }}</span>
          <template v-if="task.workflow_summary.node_name">
            <span class="meta-separator">→</span>
            <span class="process-context">{{ task.workflow_summary.node_name }}</span>
          </template>
        </template>
      </div>

      <!-- Title -->
      <div class="card-title">{{ task.title }}</div>

      <!-- Description -->
      <div v-if="task.description" class="card-description">{{ task.description }}</div>

      <!-- Bottom meta: assignee, pool badge, labels, deadline -->
      <div class="card-bottom-line">
        <div class="card-bottom-left">
          <span v-if="assigneeName" class="assignee-chip">
            <Avatar
              :image="task.assigneeAvatarUrl ?? undefined"
              :label="task.assigneeAvatarUrl ? undefined : (assigneeName || '?').charAt(0).toUpperCase()"
              shape="circle"
              size="small"
              style="width: 1.25rem; height: 1.25rem; font-size: 0.55rem;"
            />
            {{ assigneeName }}
          </span>
          <Tag
            v-if="task.isPoolTask && !task.assigneeId"
            :value="t('tasks.poolTaskBadge')"
            severity="info"
          />
          <span
            v-for="label in task.labels"
            :key="label.name"
            class="label-chip"
            :style="{ '--label-color': label.color }"
          >
            {{ label.name }}
          </span>
        </div>
        <span v-if="deadlineText" class="deadline" :class="{ overdue: isOverdue(task.dueDate) }">
          {{ t('tasks.deadlineIn', { date: deadlineText }) }}
        </span>
      </div>
    </div>

    <!-- Badges (right) -->
    <div class="card-badges">
      <Tag
        :value="t(priorityLabelKeys[task.priority] ?? task.priority)"
        :severity="taskPrioritySeverity(task.priority)"
        outlined
      />
      <Tag
        :value="t(statusLabelKeys[task.status] ?? task.status)"
        :severity="taskStatusSeverity(task.status)"
        outlined
      />
    </div>
  </div>
</template>

<style scoped>
.task-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.25rem;
  cursor: pointer;
  border: 1px solid var(--p-surface-200);
  border-radius: 0.75rem;
  background: var(--p-surface-0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
}

:root.p-dark .task-card {
  border-color: var(--p-surface-600);
  background: var(--p-surface-800);
}

.task-card:hover {
  border-color: var(--p-primary-200);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-1px);
}

:root.p-dark .task-card:hover {
  border-color: var(--p-primary-800);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.task-card.active {
  border-color: var(--p-primary-color);
  box-shadow: 0 0 0 1px var(--p-primary-color), 0 2px 8px rgba(0, 0, 0, 0.06);
}

/* Icon (left) — rounded square per Figma */
.card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  min-width: 2.25rem;
  border-radius: 0.5rem;
  font-size: 0.85rem;
  margin-top: 0.125rem;
  flex-shrink: 0;
}

.card-icon.workflow {
  background: color-mix(in srgb, var(--p-purple-500) 10%, transparent);
  color: var(--p-purple-600);
}

:root.p-dark .card-icon.workflow {
  background: color-mix(in srgb, var(--p-purple-400) 20%, transparent);
  color: var(--p-purple-300);
}

.card-icon.regular {
  background: color-mix(in srgb, var(--p-blue-500) 10%, transparent);
  color: var(--p-blue-600);
}

:root.p-dark .card-icon.regular {
  background: color-mix(in srgb, var(--p-blue-400) 20%, transparent);
  color: var(--p-blue-300);
}

/* Content (center) */
.card-content {
  flex: 1;
  min-width: 0;
}

/* Meta line */
.card-meta-line {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.25rem;
}

.task-short-id {
  font-family: ui-monospace, SFMono-Regular, monospace;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.meta-separator {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  opacity: 0.5;
}

.process-context {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Title */
.card-title {
  font-size: 1rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Description: 2 lines max */
.card-description {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

/* Bottom line */
.card-bottom-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.card-bottom-left {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.assignee-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: var(--p-text-color);
  background: var(--p-surface-100);
  padding: 0.2rem 0.6rem;
  border-radius: 1rem;
  border: 1px solid var(--p-surface-border);
}

:root.p-dark .assignee-chip {
  background: var(--p-surface-700);
}


.label-chip {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  color: var(--p-text-color);
  background: color-mix(in srgb, var(--label-color, var(--p-surface-400)) 15%, transparent);
  padding: 0.15rem 0.5rem;
  border-radius: 1rem;
  border: 1px solid color-mix(in srgb, var(--label-color, var(--p-surface-400)) 30%, transparent);
  white-space: nowrap;
}

/* Deadline pushed to right */
.deadline {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  margin-left: auto;
  flex-shrink: 0;
}

.deadline.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}

/* Badges (right) */
.card-badges {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.35rem;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .card-badges {
    flex-direction: row;
  }
}
</style>
