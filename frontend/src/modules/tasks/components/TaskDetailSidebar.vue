<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { taskStatusSeverity, taskPrioritySeverity } from '@/shared/utils/status-severity'
import { formatDate, isOverdue } from '@/shared/utils/date-format'
import type { TaskDetailDTO } from '@/modules/tasks/types/task.types'

const props = defineProps<{
  task: TaskDetailDTO
  assigneeName: string | null
  isPoolTaskClaimed?: boolean
  isCurrentAssignee?: boolean
  unclaimLoading?: boolean
}>()

const emit = defineEmits<{
  unclaim: []
}>()

const { t } = useI18n()
const empStore = useEmployeeStore()
const auth = useAuthStore()

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

const creatorName = computed(() => {
  if (props.task.creatorId === 'system') return 'System'
  const emp = empStore.employees.find((e) => e.userId === props.task.creatorId || e.id === props.task.creatorId)
  return emp?.userFullName ?? emp?.userEmail ?? props.task.creatorId
})

const creatorInitials = computed(() => {
  const emp = empStore.employees.find((e) => e.userId === props.task.creatorId || e.id === props.task.creatorId)
  if (emp) {
    const parts = (emp.userFullName ?? '').split(' ')
    return `${parts[0]?.charAt(0) ?? ''}${parts[1]?.charAt(0) ?? ''}`.toUpperCase()
  }
  return '?'
})

const isCurrentUserAssignee = computed(
  () => !!(auth.user && props.task.assigneeId && props.task.assigneeId === auth.user.id),
)

const isCurrentUserCreator = computed(
  () => !!(auth.user && props.task.creatorId === auth.user.id),
)
</script>

<template>
  <div class="sidebar-cards">
    <!-- Assignment Card -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('sidebar.assignment') }}</div>
      <div class="card-content">
        <template v-if="assigneeName">
          <div class="assignee-row">
            <Avatar
              :image="isCurrentUserAssignee ? auth.user?.avatarUrl ?? undefined : undefined"
              :label="isCurrentUserAssignee && auth.user?.avatarUrl ? undefined : assigneeName.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)"
              shape="circle"
              size="small"
            />
            <span>{{ assigneeName }}</span>
          </div>
          <Button
            v-if="isPoolTaskClaimed && isCurrentAssignee"
            :label="t('tasks.returnToQueue')"
            icon="pi pi-undo"
            text
            size="small"
            :loading="unclaimLoading"
            class="unclaim-btn"
            @click="emit('unclaim')"
          />
          <div v-if="isPoolTaskClaimed" class="pool-tag-inline">
            <i class="pi pi-users" />
            <span>{{ t('tasks.poolTask') }}</span>
          </div>
        </template>
        <template v-else-if="task.isPoolTask">
          <div class="pool-info-compact">
            <i class="pi pi-users" />
            <span>{{ t('tasks.poolTask') }}</span>
          </div>
        </template>
        <template v-else>
          <span class="muted-text">{{ t('tasks.unassigned') }}</span>
        </template>
      </div>
    </div>

    <!-- Status & Priority Card -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('sidebar.statusAndPriority') }}</div>
      <div class="card-content tags-row">
        <Tag
          :value="t(statusLabelKeys[task.status] ?? task.status)"
          :severity="taskStatusSeverity(task.status)"
          size="small"
          outlined
        />
        <Tag
          :value="t(priorityLabelKeys[task.priority] ?? task.priority)"
          :severity="taskPrioritySeverity(task.priority)"
          size="small"
          outlined
        />
      </div>
    </div>

    <!-- Dates Card -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('sidebar.dates') }}</div>
      <div class="card-content dates-list">
        <div class="date-row">
          <span class="date-label">{{ t('taskDetail.created') }}</span>
          <span class="date-value">{{ formatDate(task.createdAt) }}</span>
        </div>
        <div v-if="task.dueDate" class="date-row">
          <span class="date-label">{{ t('taskDetail.dueDate') }}</span>
          <span class="date-value" :class="{ overdue: isOverdue(task.dueDate) }">
            <i v-if="isOverdue(task.dueDate)" class="pi pi-exclamation-triangle overdue-icon" />
            {{ formatDate(task.dueDate) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Time Tracking Card (placeholder) -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('sidebar.timeTracking') }}</div>
      <div class="card-content time-tracking">
        <div class="time-row">
          <span class="time-label">{{ t('sidebar.estimate') }}</span>
          <span class="time-value">{{ task.estimatedHours ? `${task.estimatedHours}h` : '\u2014' }}</span>
        </div>
        <div class="time-row">
          <span class="time-label">{{ t('sidebar.spent') }}</span>
          <span class="time-value muted-text">&mdash;</span>
        </div>
        <Button
          :label="t('sidebar.startTimer')"
          icon="pi pi-stopwatch"
          text
          size="small"
          disabled
          class="timer-btn"
        />
      </div>
    </div>

    <!-- Watchers Card (placeholder) -->
    <div class="sidebar-card">
      <div class="card-label">
        {{ t('sidebar.watchers') }}
        <i class="pi pi-question-circle help-icon" v-tooltip="t('sidebar.watchersHelp')" />
      </div>
      <div class="card-content watchers">
        <Avatar :label="creatorInitials" shape="circle" size="small" />
        <Button
          :label="t('sidebar.subscribe')"
          icon="pi pi-eye"
          text
          size="small"
          disabled
        />
      </div>
    </div>

    <!-- Creator Card -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('sidebar.creator') }}</div>
      <div class="card-content">
        <div class="creator-row">
          <Avatar
            :image="isCurrentUserCreator ? auth.user?.avatarUrl ?? undefined : undefined"
            :label="isCurrentUserCreator && auth.user?.avatarUrl ? undefined : creatorInitials"
            shape="circle"
            size="small"
          />
          <span>{{ creatorName }}</span>
        </div>
      </div>
    </div>

    <!-- Labels Card -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('labels.assignedLabels') }}</div>
      <div class="card-content">
        <span class="muted-text">&mdash;</span>
      </div>
    </div>

    <!-- SLA Card (placeholder) -->
    <div class="sidebar-card">
      <div class="card-label">{{ t('sidebar.sla') }}</div>
      <div class="card-content">
        <template v-if="task.dueDate">
          <span class="date-value" :class="{ overdue: isOverdue(task.dueDate) }">
            {{ formatDate(task.dueDate) }}
          </span>
        </template>
        <template v-else>
          <span class="muted-text">&mdash;</span>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sidebar-cards {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.sidebar-card {
  padding: 0.875rem 1rem;
  border: 1px solid var(--p-surface-200);
  border-radius: 0.75rem;
  background: var(--p-surface-0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

:root.p-dark .sidebar-card {
  border-color: var(--p-surface-600);
  background: var(--p-surface-800);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.card-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--p-text-muted-color);
  margin-bottom: 0.4rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.card-content {
  font-size: 0.875rem;
}

.muted-text {
  color: var(--p-text-muted-color);
}

.tags-row {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.assignee-row,
.creator-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pool-info-compact {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}

.unclaim-btn {
  margin-top: 0.3rem;
  align-self: flex-start;
}

.pool-tag-inline {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  margin-top: 0.25rem;
}

.dates-list,
.time-tracking {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.date-row,
.time-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.date-label,
.time-label {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
}

.date-value,
.time-value {
  font-size: 0.85rem;
}

.date-value.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}

.overdue-icon {
  font-size: 0.75rem;
  margin-right: 0.2rem;
}

.timer-btn {
  margin-top: 0.3rem;
  align-self: flex-start;
}

.watchers {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.help-icon {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  cursor: help;
}
</style>
