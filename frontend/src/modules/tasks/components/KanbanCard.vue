<script setup lang="ts">
import { computed } from 'vue'
import type { TaskDTO } from '@/modules/tasks/types/task.types'

const props = defineProps<{
  task: TaskDTO
}>()

defineEmits<{
  click: []
  dragstart: [event: DragEvent]
}>()

function getPrioritySeverity(priority: string) {
  switch (priority) {
    case 'low':
      return 'secondary'
    case 'medium':
      return 'info'
    case 'high':
      return 'warn'
    case 'critical':
      return 'danger'
    default:
      return undefined
  }
}

const isOverdue = computed(() => {
  if (!props.task.dueDate) return false
  return new Date(props.task.dueDate) < new Date()
})

const dueDateFormatted = computed(() => {
  if (!props.task.dueDate) return null
  return new Date(props.task.dueDate).toLocaleDateString()
})

const assigneeInitial = computed(() => {
  if (!props.task.assigneeName) return '?'
  return props.task.assigneeName.charAt(0).toUpperCase()
})
</script>

<template>
  <div
    class="kanban-card"
    draggable="true"
    @click="$emit('click')"
    @dragstart="$emit('dragstart', $event)"
  >
    <!-- Header row: assignee avatar + priority chip -->
    <div class="card-header">
      <Avatar
        v-if="task.assigneeId"
        :image="task.assigneeAvatarUrl ?? undefined"
        :label="!task.assigneeAvatarUrl ? assigneeInitial : undefined"
        size="small"
        shape="circle"
        class="assignee-avatar"
      />
      <div v-else class="avatar-placeholder" />
      <Tag
        :value="task.priority"
        :severity="getPrioritySeverity(task.priority)"
        rounded
        class="priority-tag"
        style="font-size: 0.7rem"
      />
    </div>

    <!-- Title -->
    <div class="card-title">{{ task.title }}</div>

    <!-- Label chips row -->
    <div v-if="task.labels.length > 0" class="card-labels">
      <Tag
        v-for="label in task.labels.slice(0, 3)"
        :key="label.name"
        :value="label.name"
        :style="{ backgroundColor: label.color + '22', color: label.color, border: '1px solid ' + label.color + '44' }"
        rounded
        class="label-chip"
      />
      <span v-if="task.labels.length > 3" class="label-more">+{{ task.labels.length - 3 }}</span>
    </div>

    <!-- Footer row: due date + comment count -->
    <div class="card-footer">
      <span v-if="dueDateFormatted" class="due-date" :class="{ overdue: isOverdue }">
        <i class="pi pi-calendar" />
        {{ dueDateFormatted }}
      </span>
      <span v-if="task.commentCount > 0" class="comment-count">
        <i class="pi pi-comment" />
        {{ task.commentCount }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.kanban-card {
  background: var(--p-surface-card);
  border: 1px solid var(--p-content-border-color);
  border-radius: var(--card-radius);
  padding: 0.75rem;
  cursor: grab;
  box-shadow: var(--card-shadow);
  transition: box-shadow var(--transition-base), transform var(--transition-base);
}

.kanban-card:hover {
  box-shadow: var(--card-shadow-hover);
  transform: translateY(-1px);
}

.kanban-card:active {
  cursor: grabbing;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.assignee-avatar {
  flex-shrink: 0;
}

.avatar-placeholder {
  width: 2rem;
  height: 2rem;
}

.priority-tag {
  flex-shrink: 0;
}

.card-title {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  line-height: 1.3;
  word-break: break-word;
}

.card-labels {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-bottom: 0.5rem;
}

.label-chip {
  font-size: 0.7rem;
  padding: 0.125rem 0.375rem;
}

.label-more {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  display: flex;
  align-items: center;
}

.card-footer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.due-date {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.due-date.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}

.due-date i,
.comment-count i {
  font-size: 0.7rem;
}

.comment-count {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-left: auto;
}
</style>
