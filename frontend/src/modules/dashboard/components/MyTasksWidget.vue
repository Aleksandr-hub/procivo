<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { isOverdue, formatDate } from '@/shared/utils/date-format'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'

const props = defineProps<{
  tasks: TaskDTO[]
  orgId: string
}>()

const router = useRouter()
const { t } = useI18n()

const overdueTasks = computed(() =>
  props.tasks.filter((task) => task.dueDate !== null && isOverdue(task.dueDate)),
)

const todayTasks = computed(() =>
  props.tasks.filter(
    (task) =>
      task.dueDate !== null &&
      !isOverdue(task.dueDate) &&
      new Date(task.dueDate).toDateString() === new Date().toDateString(),
  ),
)

const upcomingTasks = computed(() =>
  props.tasks.filter(
    (task) =>
      task.dueDate !== null &&
      !isOverdue(task.dueDate) &&
      new Date(task.dueDate).toDateString() !== new Date().toDateString(),
  ),
)

const noDueDateTasks = computed(() => props.tasks.filter((task) => task.dueDate === null))

function prioritySeverity(priority: TaskPriority): string {
  switch (priority) {
    case 'critical':
      return 'danger'
    case 'high':
      return 'warn'
    case 'medium':
      return 'info'
    case 'low':
    default:
      return 'secondary'
  }
}

function navigateToTask(taskId: string) {
  router.push({ name: 'task-detail', params: { orgId: props.orgId, taskId } })
}
</script>

<template>
  <div class="my-tasks-widget">
    <div v-if="tasks.length === 0" class="empty-state">
      <i class="pi pi-check-circle empty-icon" />
      <p>{{ t('dashboard.noTasks') }}</p>
    </div>

    <template v-else>
      <!-- Overdue -->
      <div v-if="overdueTasks.length > 0" class="task-bucket">
        <div class="bucket-header bucket-header--overdue">
          {{ t('dashboard.overdue') }} ({{ overdueTasks.length }})
        </div>
        <div
          v-for="task in overdueTasks"
          :key="task.id"
          class="task-row"
          @click="navigateToTask(task.id)"
        >
          <span class="task-title">{{ task.title }}</span>
          <div class="task-meta">
            <Tag :value="task.priority" :severity="prioritySeverity(task.priority)" class="priority-tag" />
            <small class="due-date due-date--overdue">{{ formatDate(task.dueDate) }}</small>
          </div>
        </div>
      </div>

      <!-- Due today -->
      <div v-if="todayTasks.length > 0" class="task-bucket">
        <div class="bucket-header bucket-header--today">
          {{ t('dashboard.dueToday') }} ({{ todayTasks.length }})
        </div>
        <div
          v-for="task in todayTasks"
          :key="task.id"
          class="task-row"
          @click="navigateToTask(task.id)"
        >
          <span class="task-title">{{ task.title }}</span>
          <div class="task-meta">
            <Tag :value="task.priority" :severity="prioritySeverity(task.priority)" class="priority-tag" />
            <small class="due-date due-date--today">{{ formatDate(task.dueDate) }}</small>
          </div>
        </div>
      </div>

      <!-- Upcoming -->
      <div v-if="upcomingTasks.length > 0" class="task-bucket">
        <div class="bucket-header bucket-header--upcoming">
          {{ t('dashboard.upcoming') }} ({{ upcomingTasks.length }})
        </div>
        <div
          v-for="task in upcomingTasks"
          :key="task.id"
          class="task-row"
          @click="navigateToTask(task.id)"
        >
          <span class="task-title">{{ task.title }}</span>
          <div class="task-meta">
            <Tag :value="task.priority" :severity="prioritySeverity(task.priority)" class="priority-tag" />
            <small class="due-date">{{ formatDate(task.dueDate) }}</small>
          </div>
        </div>
      </div>

      <!-- No due date -->
      <div v-if="noDueDateTasks.length > 0" class="task-bucket">
        <div class="bucket-header bucket-header--no-due">
          {{ t('dashboard.noDueDate') }} ({{ noDueDateTasks.length }})
        </div>
        <div
          v-for="task in noDueDateTasks"
          :key="task.id"
          class="task-row"
          @click="navigateToTask(task.id)"
        >
          <span class="task-title">{{ task.title }}</span>
          <div class="task-meta">
            <Tag :value="task.priority" :severity="prioritySeverity(task.priority)" class="priority-tag" />
            <small v-if="task.workflow_summary" class="workflow-badge">
              {{ task.workflow_summary.node_name }}
            </small>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.my-tasks-widget {
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
  color: var(--p-green-400);
}

.task-bucket {
  margin-bottom: 0.75rem;
}

.bucket-header {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.25rem 0;
  margin-bottom: 0.25rem;
}

.bucket-header--overdue {
  color: var(--p-red-500);
}

.bucket-header--today {
  color: var(--p-orange-500);
}

.bucket-header--upcoming {
  color: var(--p-blue-500);
}

.bucket-header--no-due {
  color: var(--p-text-muted-color);
}

.task-row {
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

.task-row:hover {
  background: var(--p-surface-hover);
  border-color: var(--p-surface-border);
}

.task-title {
  font-size: 0.875rem;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.priority-tag {
  font-size: 0.7rem !important;
}

.due-date {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
}

.due-date--overdue {
  color: var(--p-red-500);
  font-weight: 600;
}

.due-date--today {
  color: var(--p-orange-500);
  font-weight: 600;
}

.workflow-badge {
  font-size: 0.7rem;
  color: var(--p-primary-color);
  background: var(--p-primary-100);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}
</style>
