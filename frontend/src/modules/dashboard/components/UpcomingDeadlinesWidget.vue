<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { formatDate, isOverdue } from '@/shared/utils/date-format'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'

const props = defineProps<{
  tasks: TaskDTO[]
  orgId: string
}>()

const router = useRouter()
const { t } = useI18n()

const DISPLAY_LIMIT = 8

const deadlineTasks = computed(() =>
  props.tasks
    .filter((task) => task.dueDate !== null)
    .sort((a, b) => new Date(a.dueDate!).getTime() - new Date(b.dueDate!).getTime())
    .slice(0, DISPLAY_LIMIT),
)

const priorityColorMap: Record<TaskPriority, string> = {
  critical: '#EF4444',
  high: '#F59E0B',
  medium: '#3B82F6',
  low: '#10B981',
}

function navigateToTask(taskId: string) {
  router.push({ name: 'task-detail', params: { orgId: props.orgId, taskId } })
}
</script>

<template>
  <div class="deadlines-widget">
    <div v-if="deadlineTasks.length === 0" class="empty-state">
      <i class="pi pi-calendar-clock empty-icon" />
      <p>{{ t('dashboard.noDeadlines') }}</p>
    </div>

    <div v-else class="deadline-list">
      <div
        v-for="task in deadlineTasks"
        :key="task.id"
        class="deadline-item"
        @click="navigateToTask(task.id)"
      >
        <span
          class="priority-dot"
          :style="{ backgroundColor: priorityColorMap[task.priority] }"
        />
        <span class="deadline-title">{{ task.title }}</span>
        <span
          class="deadline-date"
          :class="{ 'deadline-date--overdue': isOverdue(task.dueDate!) }"
        >
          {{ formatDate(task.dueDate) }}
          <span v-if="isOverdue(task.dueDate!)" class="overdue-badge">
            {{ t('dashboard.overdue') }}
          </span>
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.deadlines-widget {
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
}

.deadline-list {
  display: flex;
  flex-direction: column;
}

.deadline-item {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.625rem 0;
  border-bottom: 1px solid var(--p-surface-border);
  cursor: pointer;
  transition: background-color 0.15s;
  border-radius: 4px;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.deadline-item:last-child {
  border-bottom: none;
}

.deadline-item:hover {
  background: var(--p-surface-hover);
}

.priority-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.deadline-title {
  flex: 1;
  font-size: 0.875rem;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.deadline-date {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
  flex-shrink: 0;
}

.deadline-date--overdue {
  color: var(--color-error);
  font-weight: 500;
}

.overdue-badge {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-left: 0.25rem;
}
</style>
