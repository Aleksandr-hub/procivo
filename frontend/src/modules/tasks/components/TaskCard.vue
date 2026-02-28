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
    <!-- Top line: icon + process context + badges -->
    <div class="card-top-line">
      <div class="card-top-left">
        <div class="type-icon" :class="isWorkflow ? 'workflow' : 'regular'">
          <i :class="isWorkflow ? 'pi pi-sitemap' : 'pi pi-list'" />
        </div>
        <span v-if="task.workflow_summary" class="process-context">
          {{ task.workflow_summary.process_name }}
          <span class="process-arrow">&rarr;</span>
          {{ task.workflow_summary.node_name }}
        </span>
      </div>
      <div class="card-top-right">
        <Tag
          :value="t(priorityLabelKeys[task.priority] ?? task.priority)"
          :severity="taskPrioritySeverity(task.priority)"
        />
        <Tag
          :value="t(statusLabelKeys[task.status] ?? task.status)"
          :severity="taskStatusSeverity(task.status)"
        />
      </div>
    </div>

    <!-- Title -->
    <div class="card-title">{{ task.title }}</div>

    <!-- Description -->
    <div v-if="task.description" class="card-description">{{ task.description }}</div>

    <!-- Bottom line: assignee + labels + pool badge + deadline -->
    <div class="card-bottom-line">
      <div class="card-bottom-left">
        <span v-if="assigneeName" class="assignee-chip">
          <i class="pi pi-user" />
          {{ assigneeName }}
        </span>
        <Tag
          v-if="task.isPoolTask && !task.assigneeId"
          :value="t('tasks.poolTaskBadge')"
          severity="info"
        />
      </div>
      <div class="card-bottom-right">
        <span v-if="deadlineText" class="deadline" :class="{ overdue: isOverdue(task.dueDate) }">
          {{ t('tasks.deadlineIn', { date: deadlineText }) }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-card {
  padding: 1rem 1.25rem;
  cursor: pointer;
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-border-radius);
  background: var(--p-surface-card);
  transition: box-shadow 0.15s, border-color 0.15s;
}

.task-card:hover {
  border-color: var(--p-primary-200);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

:root.p-dark .task-card:hover {
  border-color: var(--p-primary-800);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.task-card.active {
  border-color: var(--p-primary-color);
}

/* Top line */
.card-top-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.card-top-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.type-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  min-width: 2rem;
  border-radius: 50%;
  font-size: 0.8rem;
}

.type-icon.workflow {
  background: var(--p-purple-100);
  color: var(--p-purple-600);
}

:root.p-dark .type-icon.workflow {
  background: color-mix(in srgb, var(--p-purple-400) 20%, transparent);
  color: var(--p-purple-300);
}

.type-icon.regular {
  background: var(--p-blue-100);
  color: var(--p-blue-600);
}

:root.p-dark .type-icon.regular {
  background: color-mix(in srgb, var(--p-blue-400) 20%, transparent);
  color: var(--p-blue-300);
}

.process-context {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.process-arrow {
  opacity: 0.5;
  margin: 0 0.15rem;
}

.card-top-right {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
}

/* Title */
.card-title {
  font-size: 1.05rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  line-height: 1.3;
}

/* Description */
.card-description {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

/* Bottom line */
.card-bottom-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.4rem;
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

.assignee-chip i {
  font-size: 0.7rem;
}

.card-bottom-right {
  flex-shrink: 0;
}

.deadline {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
}

.deadline.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}
</style>
