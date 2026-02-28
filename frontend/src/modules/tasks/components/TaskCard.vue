<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { TaskDTO } from '@/modules/tasks/types/task.types'
import { taskStatusSeverity, taskPrioritySeverity } from '@/shared/utils/status-severity'
import { formatDate, isOverdue } from '@/shared/utils/date-format'

defineProps<{
  task: TaskDTO
  active: boolean
}>()

defineEmits<{
  click: []
}>()

const { t } = useI18n()

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
</script>

<template>
  <div
    class="task-card"
    :class="{ active }"
    @click="$emit('click')"
  >
    <!-- Top row: type icon + process context + badges -->
    <div class="task-card-header">
      <div class="task-card-type-icon" :class="task.workflow_summary ? 'workflow' : 'regular'">
        <i :class="task.workflow_summary ? 'pi pi-sitemap' : 'pi pi-list'" />
      </div>

      <div class="task-card-header-content">
        <!-- Process context line (workflow tasks only) -->
        <div v-if="task.workflow_summary" class="task-card-process">
          <span>{{ task.workflow_summary.process_name }}</span>
          <span class="process-separator">&rarr;</span>
          <span>{{ task.workflow_summary.node_name }}</span>
        </div>

        <!-- Task title -->
        <div class="task-card-title">{{ task.title }}</div>

        <!-- Description (one line, truncated) -->
        <div v-if="task.description" class="task-card-description">
          {{ task.description }}
        </div>
      </div>
    </div>

    <!-- Bottom row: status + pool badge + priority + due date -->
    <div class="task-card-meta">
      <div class="task-card-meta-left">
        <Tag
          :value="t(statusLabelKeys[task.status] ?? task.status)"
          :severity="taskStatusSeverity(task.status)"
        />
        <Tag
          v-if="task.isPoolTask && !task.assigneeId"
          :value="t('tasks.poolTaskBadge')"
          severity="info"
          class="pool-badge"
        />
      </div>
      <div class="task-card-meta-right">
        <Tag
          :value="t(priorityLabelKeys[task.priority] ?? task.priority)"
          :severity="taskPrioritySeverity(task.priority)"
        />
        <span v-if="task.dueDate" class="task-card-date" :class="{ overdue: isOverdue(task.dueDate) }">
          <i class="pi pi-calendar" />
          {{ formatDate(task.dueDate) }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-card {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.15s;
  border-bottom: 1px solid var(--p-surface-border);
}

.task-card:hover {
  background: var(--p-surface-hover);
}

.task-card.active {
  background: var(--p-highlight-background);
}

.task-card-header {
  display: flex;
  gap: 0.6rem;
  align-items: flex-start;
}

.task-card-type-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  min-width: 1.75rem;
  border-radius: 50%;
  font-size: 0.75rem;
  margin-top: 0.1rem;
}

.task-card-type-icon.workflow {
  background: var(--p-purple-100);
  color: var(--p-purple-600);
}

:root.p-dark .task-card-type-icon.workflow {
  background: color-mix(in srgb, var(--p-purple-400) 20%, transparent);
  color: var(--p-purple-300);
}

.task-card-type-icon.regular {
  background: var(--p-blue-100);
  color: var(--p-blue-600);
}

:root.p-dark .task-card-type-icon.regular {
  background: color-mix(in srgb, var(--p-blue-400) 20%, transparent);
  color: var(--p-blue-300);
}

.task-card-header-content {
  flex: 1;
  min-width: 0;
}

.task-card-process {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.7rem;
  color: var(--p-purple-600);
  margin-bottom: 0.15rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

:root.p-dark .task-card-process {
  color: var(--p-purple-300);
}

.process-separator {
  opacity: 0.6;
  font-size: 0.65rem;
}

.task-card-title {
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 0.2rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-card-description {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-bottom: 0.2rem;
}

.task-card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.4rem;
  padding-left: 2.35rem;
}

.task-card-meta-left {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.task-card-meta-right {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.pool-badge {
  font-size: 0.65rem;
}

.task-card-date {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: var(--p-text-muted-color);
  font-size: 0.7rem;
}

.task-card-date.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}
</style>
