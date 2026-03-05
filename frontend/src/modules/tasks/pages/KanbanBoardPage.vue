<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useBoardStore } from '@/modules/tasks/stores/board.store'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import KanbanCard from '@/modules/tasks/components/KanbanCard.vue'
import QuickFilterBar from '@/modules/tasks/components/QuickFilterBar.vue'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'
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

// Filter state
const filterText = ref('')
const filterAssigneeId = ref('')
const filterLabels = ref<string[]>([])
const filterDateRange = ref<Date[] | null>(null)
const swimlaneMode = ref<'none' | 'assignee' | 'priority'>('none')

const draggedTaskId = ref<string | null>(null)
const eventSource = ref<EventSource | null>(null)

// Compute assignee and label options from loaded tasks
const assigneeOptions = computed(() => {
  const map = new Map<string, string>()
  for (const task of taskStore.tasks) {
    if (task.assigneeId && task.assigneeName) {
      map.set(task.assigneeId, task.assigneeName)
    }
  }
  return [...map.entries()].map(([value, label]) => ({ value, label }))
})

const labelOptions = computed(() => {
  const set = new Set<string>()
  for (const task of taskStore.tasks) {
    for (const label of task.labels) set.add(label.name)
  }
  return [...set]
})

// Filtered tasks
const filteredTasks = computed(() => {
  return taskStore.tasks.filter((task) => {
    if (filterText.value && !task.title.toLowerCase().includes(filterText.value.toLowerCase())) return false
    if (filterAssigneeId.value && task.assigneeId !== filterAssigneeId.value) return false
    if (filterLabels.value.length > 0) {
      const taskLabelNames = task.labels.map((l) => l.name)
      if (!filterLabels.value.some((fl) => taskLabelNames.includes(fl))) return false
    }
    if (filterDateRange.value && filterDateRange.value[0]) {
      if (!task.dueDate) return false
      const due = new Date(task.dueDate)
      if (due < filterDateRange.value[0]) return false
      if (filterDateRange.value[1] && due > filterDateRange.value[1]) return false
    }
    return true
  })
})

// Swimlane interface and computation
interface Swimlane {
  key: string
  label: string
  tasks: TaskDTO[]
}

const swimlanes = computed((): Swimlane[] => {
  if (swimlaneMode.value === 'none') {
    return [{ key: 'all', label: '', tasks: filteredTasks.value }]
  }
  if (swimlaneMode.value === 'assignee') {
    const groups = new Map<string, Swimlane>()
    for (const task of filteredTasks.value) {
      const key = task.assigneeId ?? 'unassigned'
      const label = task.assigneeName ?? t('kanban.unassigned')
      if (!groups.has(key)) groups.set(key, { key, label, tasks: [] })
      groups.get(key)!.tasks.push(task)
    }
    return [...groups.entries()]
      .sort(([a], [b]) => (a === 'unassigned' ? 1 : b === 'unassigned' ? -1 : 0))
      .map(([, v]) => v)
  }
  // priority mode
  const PRIORITY_ORDER: TaskPriority[] = ['critical', 'high', 'medium', 'low']
  return PRIORITY_ORDER.map((p) => ({
    key: p,
    label: t(`tasks.priority_${p}`),
    tasks: filteredTasks.value.filter((task) => task.priority === p),
  }))
})

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

function getTasksForColumnInLane(column: BoardColumnDTO, lane: Swimlane): TaskDTO[] {
  if (!column.statusMapping) return []
  return lane.tasks.filter((task) => task.status === column.statusMapping)
}

function getTasksForColumn(column: BoardColumnDTO): TaskDTO[] {
  if (!column.statusMapping) return []
  return filteredTasks.value.filter((task) => task.status === column.statusMapping)
}

function getColumnStyle(column: BoardColumnDTO) {
  if (column.color) {
    return { borderTopColor: column.color }
  }
  return {}
}

function getColumnWipState(column: BoardColumnDTO): 'ok' | 'warning' | 'exceeded' {
  if (!column.wipLimit) return 'ok'
  const count = getTasksForColumn(column).length
  if (count >= column.wipLimit) return 'exceeded'
  if (count / column.wipLimit >= 0.8) return 'warning'
  return 'ok'
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

    <template v-if="board">
      <QuickFilterBar
        v-model:filter-text="filterText"
        v-model:filter-assignee-id="filterAssigneeId"
        v-model:filter-labels="filterLabels"
        v-model:filter-date-range="filterDateRange"
        v-model:swimlane-mode="swimlaneMode"
        :assignee-options="assigneeOptions"
        :label-options="labelOptions"
      />

      <div v-for="lane in swimlanes" :key="lane.key" class="swimlane-row">
        <div v-if="swimlaneMode !== 'none'" class="swimlane-header">{{ lane.label }}</div>
        <div class="kanban-board">
          <div
            v-for="column in sortedColumns"
            :key="column.id"
            class="kanban-column"
            :class="{
              'wip-warning': getColumnWipState(column) === 'warning',
              'wip-exceeded': getColumnWipState(column) === 'exceeded',
            }"
            :style="getColumnStyle(column)"
            @dragover="onDragOver"
            @drop="onDrop($event, column)"
          >
            <div class="column-header">
              <span class="column-name">{{ column.name }}</span>
              <span class="column-count">
                {{ getTasksForColumnInLane(column, lane).length }}
                <span v-if="column.wipLimit" class="wip-limit">/ {{ column.wipLimit }}</span>
              </span>
            </div>

            <div class="column-body">
              <KanbanCard
                v-for="task in getTasksForColumnInLane(column, lane)"
                :key="task.id"
                :task="task"
                @click="router.push({ name: 'task-detail', params: { orgId: orgId, taskId: task.id } })"
                @dragstart="onDragStart($event, task)"
              />

              <div v-if="getTasksForColumnInLane(column, lane).length === 0" class="column-empty">
                {{ t('kanban.noTasks') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
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

.swimlane-row {
  margin-bottom: 1.5rem;
}

.swimlane-header {
  font-weight: 600;
  font-size: 0.9rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--p-content-border-color);
  margin-bottom: 0.75rem;
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

.kanban-column.wip-warning {
  border-top-color: var(--p-orange-500);
}

.kanban-column.wip-exceeded {
  border-top-color: var(--p-red-500);
}

.kanban-column.wip-warning .column-count {
  color: var(--p-orange-600);
}

.kanban-column.wip-exceeded .column-count {
  color: var(--p-red-600);
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
</style>
