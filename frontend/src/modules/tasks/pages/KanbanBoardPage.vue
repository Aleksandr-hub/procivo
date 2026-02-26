<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useBoardStore } from '@/modules/tasks/stores/board.store'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import type { TaskDTO } from '@/modules/tasks/types/task.types'
import type { BoardColumnDTO } from '@/modules/tasks/types/board.types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const boardStore = useBoardStore()
const taskStore = useTaskStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const boardId = computed(() => route.params.boardId as string)

const board = computed(() => boardStore.boards.find((b) => b.id === boardId.value) ?? null)
const sortedColumns = computed(() => {
  if (!board.value) return []
  return [...board.value.columns].sort((a, b) => a.position - b.position)
})

const draggedTaskId = ref<string | null>(null)
const eventSource = ref<EventSource | null>(null)

onMounted(async () => {
  await Promise.all([
    boardStore.fetchBoards(orgId.value),
    taskStore.fetchTasks(orgId.value),
  ])
  connectMercure()
})

onUnmounted(() => {
  disconnectMercure()
})

function getTasksForColumn(column: BoardColumnDTO): TaskDTO[] {
  if (!column.statusMapping) return []
  return taskStore.tasks.filter((task) => task.status === column.statusMapping)
}

function getColumnStyle(column: BoardColumnDTO) {
  if (column.color) {
    return { borderTopColor: column.color }
  }
  return {}
}

function isWipExceeded(column: BoardColumnDTO): boolean {
  if (!column.wipLimit) return false
  return getTasksForColumn(column).length >= column.wipLimit
}

// Drag and drop
function onDragStart(event: DragEvent, task: TaskDTO) {
  draggedTaskId.value = task.id
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', task.id)
  }
}

function onDragOver(event: DragEvent) {
  event.preventDefault()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }
}

async function onDrop(event: DragEvent, column: BoardColumnDTO) {
  event.preventDefault()
  const taskId = draggedTaskId.value
  draggedTaskId.value = null

  if (!taskId || !column.statusMapping) return

  const task = taskStore.tasks.find((t) => t.id === taskId)
  if (!task || task.status === column.statusMapping) return

  // Find the transition that leads to the target status
  const transition = findTransition(task, column.statusMapping)
  if (!transition) {
    toast.add({
      severity: 'warn',
      summary: t('common.error'),
      detail: t('kanban.transitionNotAllowed'),
      life: 3000,
    })
    return
  }

  try {
    await taskStore.transitionTask(orgId.value, taskId, transition)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('kanban.taskMoved'),
      life: 2000,
    })
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('kanban.moveFailed'),
      life: 5000,
    })
  }
}

function findTransition(task: TaskDTO, targetStatus: string): string | null {
  // Map target status to known transition names
  const statusToTransition: Record<string, string[]> = {
    open: ['open', 'reopen'],
    in_progress: ['to_progress'],
    review: ['to_review'],
    done: ['complete'],
    blocked: ['block'],
    cancelled: ['cancel'],
  }

  const possibleTransitions = statusToTransition[targetStatus] ?? []
  return task.availableTransitions.find((t) => possibleTransitions.includes(t)) ?? null
}

function getPrioritySeverity(priority: string) {
  switch (priority) {
    case 'low': return 'secondary'
    case 'medium': return 'info'
    case 'high': return 'warn'
    case 'critical': return 'danger'
    default: return undefined
  }
}

// Mercure real-time
function connectMercure() {
  const mercureUrl = import.meta.env.VITE_MERCURE_URL
  if (!mercureUrl) return

  const topic = encodeURIComponent(`/organizations/${orgId.value}/tasks`)
  const url = `${mercureUrl}?topic=${topic}`

  eventSource.value = new EventSource(url)

  eventSource.value.onmessage = () => {
    // Refetch tasks when any update is received
    taskStore.fetchTasks(orgId.value)
  }
}

function disconnectMercure() {
  if (eventSource.value) {
    eventSource.value.close()
    eventSource.value = null
  }
}

function goBack() {
  router.push({ name: 'boards', params: { orgId: orgId.value } })
}
</script>

<template>
  <div class="kanban-page">
    <div class="page-header">
      <div class="header-left">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <h3>{{ board?.name ?? t('kanban.title') }}</h3>
      </div>
    </div>

    <ProgressBar v-if="taskStore.loading" mode="indeterminate" style="height: 4px" />

    <div v-if="!board && !boardStore.loading" class="no-board">
      {{ t('kanban.boardNotFound') }}
    </div>

    <div v-if="board" class="kanban-board">
      <div
        v-for="column in sortedColumns"
        :key="column.id"
        class="kanban-column"
        :class="{ 'wip-exceeded': isWipExceeded(column) }"
        :style="getColumnStyle(column)"
        @dragover="onDragOver"
        @drop="onDrop($event, column)"
      >
        <div class="column-header">
          <span class="column-name">{{ column.name }}</span>
          <span class="column-count">
            {{ getTasksForColumn(column).length }}
            <span v-if="column.wipLimit" class="wip-limit">/ {{ column.wipLimit }}</span>
          </span>
        </div>

        <div class="column-body">
          <div
            v-for="task in getTasksForColumn(column)"
            :key="task.id"
            class="kanban-card"
            draggable="true"
            @dragstart="onDragStart($event, task)"
          >
            <div class="card-title">{{ task.title }}</div>
            <div class="card-meta">
              <Tag :value="task.priority" :severity="getPrioritySeverity(task.priority)" rounded />
              <span v-if="task.dueDate" class="due-date">
                <i class="pi pi-calendar" />
                {{ new Date(task.dueDate).toLocaleDateString() }}
              </span>
            </div>
          </div>

          <div v-if="getTasksForColumn(column).length === 0" class="column-empty">
            {{ t('kanban.noTasks') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.kanban-page {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-left h3 {
  margin: 0;
}

.no-board {
  text-align: center;
  padding: 3rem;
  color: var(--p-text-muted-color);
}

.kanban-board {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  flex: 1;
  padding-bottom: 1rem;
}

.kanban-column {
  min-width: 280px;
  max-width: 320px;
  background: var(--p-surface-ground);
  border-radius: 8px;
  border-top: 3px solid var(--p-primary-color);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.kanban-column.wip-exceeded {
  border-top-color: var(--p-red-500);
}

.column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  font-weight: 600;
  font-size: 0.875rem;
}

.column-count {
  font-weight: 400;
  color: var(--p-text-muted-color);
  font-size: 0.8rem;
}

.wip-limit {
  color: var(--p-text-muted-color);
}

.column-body {
  flex: 1;
  padding: 0 0.5rem 0.5rem;
  overflow-y: auto;
  min-height: 100px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.column-empty {
  text-align: center;
  padding: 2rem 0.5rem;
  color: var(--p-text-muted-color);
  font-size: 0.8rem;
}

.kanban-card {
  background: var(--p-surface-card);
  border: 1px solid var(--p-content-border-color);
  border-radius: 6px;
  padding: 0.75rem;
  cursor: grab;
  transition: box-shadow 0.15s;
}

.kanban-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.kanban-card:active {
  cursor: grabbing;
}

.card-title {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
}

.due-date {
  color: var(--p-text-muted-color);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.due-date i {
  font-size: 0.7rem;
}
</style>
