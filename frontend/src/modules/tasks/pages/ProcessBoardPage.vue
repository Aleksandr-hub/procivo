<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useBoardStore } from '@/modules/tasks/stores/board.store'
import { useProcessBoardStore } from '@/modules/tasks/stores/process-board.store'
import ProcessBoardCard from '@/modules/tasks/components/ProcessBoardCard.vue'
import ProcessBoardMetrics from '@/modules/tasks/components/ProcessBoardMetrics.vue'
import ActionFormDialog from '@/modules/tasks/components/ActionFormDialog.vue'
import type { BoardColumnDTO, ProcessBoardInstanceDTO } from '@/modules/tasks/types/board.types'
import type { StatusAction } from '@/modules/tasks/types/task.types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const boardStore = useBoardStore()
const processBoardStore = useProcessBoardStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const boardId = computed(() => route.params.boardId as string)

const board = computed(() => boardStore.boards.find((b) => b.id === boardId.value) ?? null)
const sortedColumns = computed(() => {
  if (!board.value) return []
  return [...board.value.columns].sort((a, b) => a.position - b.position)
})

// Drag-and-drop state
const draggedInstanceId = ref<string | null>(null)

// ActionFormDialog state
const showActionDialog = ref(false)
const pendingActionTaskId = ref<string | null>(null)
const pendingAction = ref<StatusAction | null>(null)

onMounted(async () => {
  await Promise.all([
    boardStore.fetchBoards(orgId.value),
    processBoardStore.fetchBoardData(orgId.value, boardId.value),
  ])
})

function getInstancesForColumn(column: BoardColumnDTO): ProcessBoardInstanceDTO[] {
  return processBoardStore.getInstancesForColumn(column)
}

function navigateToInstance(instanceId: string) {
  router.push({
    name: 'process-instance-detail',
    params: { orgId: orgId.value, instanceId },
  })
}

function goBack() {
  router.push({ name: 'boards', params: { orgId: orgId.value } })
}

// Drag handlers
function onDragStart(event: DragEvent, instance: ProcessBoardInstanceDTO) {
  if (!instance.activeTaskId) {
    event.preventDefault()
    return
  }
  draggedInstanceId.value = instance.id
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', instance.id)
  }
}

async function onDrop(event: DragEvent, targetColumn: BoardColumnDTO) {
  event.preventDefault()
  const instanceId = draggedInstanceId.value
  draggedInstanceId.value = null

  if (!instanceId) return

  const instance = processBoardStore.data?.instances.find((i) => i.id === instanceId)
  if (!instance || !instance.activeTaskId) {
    toast.add({ severity: 'warn', detail: t('processBoard.noActiveTask'), life: 3000 })
    return
  }

  // Don't drop on the same column
  if (targetColumn.nodeId === instance.activeNodeId) return

  try {
    const { taskApi } = await import('@/modules/tasks/api/task.api')
    const taskDetail = await taskApi.get(orgId.value, instance.activeTaskId)
    const workflowContext = taskDetail.workflow_context

    if (!workflowContext?.form_schema?.actions?.length) {
      toast.add({ severity: 'warn', detail: t('processBoard.dragRejected'), life: 3000 })
      return
    }

    const defaultAction = workflowContext.form_schema.actions[0]
    const hasRequired = defaultAction.form_fields.some((f) => f.required)

    if (hasRequired) {
      // Show ActionFormDialog for the user to fill required fields
      pendingActionTaskId.value = instance.activeTaskId
      pendingAction.value = {
        key: defaultAction.key,
        label: defaultAction.label,
        formFields: defaultAction.form_fields,
        type: 'workflow',
      }
      showActionDialog.value = true
    } else {
      // Execute immediately with empty form data
      await taskApi.executeAction(orgId.value, instance.activeTaskId, {
        action_key: defaultAction.key,
        form_data: {},
      })
      toast.add({ severity: 'success', detail: t('processBoard.dragSuccess'), life: 2000 })
      await processBoardStore.fetchBoardData(orgId.value, boardId.value)
    }
  } catch {
    toast.add({ severity: 'error', detail: t('processBoard.dragRejected'), life: 5000 })
  }
}

async function onActionSubmit(payload: { actionKey: string; formData: Record<string, unknown> }) {
  if (!pendingActionTaskId.value) return
  try {
    const { taskApi } = await import('@/modules/tasks/api/task.api')
    await taskApi.executeAction(orgId.value, pendingActionTaskId.value, {
      action_key: payload.actionKey,
      form_data: payload.formData,
    })
    toast.add({ severity: 'success', detail: t('processBoard.dragSuccess'), life: 2000 })
    showActionDialog.value = false
    pendingActionTaskId.value = null
    pendingAction.value = null
    await processBoardStore.fetchBoardData(orgId.value, boardId.value)
  } catch {
    toast.add({ severity: 'error', detail: t('processBoard.dragRejected'), life: 5000 })
  }
}

function onActionDialogHide() {
  showActionDialog.value = false
  pendingActionTaskId.value = null
  pendingAction.value = null
}
</script>

<template>
  <div class="process-board-page">
    <div class="page-header">
      <div class="header-left">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <h3>{{ board?.name ?? t('processBoard.title') }}</h3>
      </div>
    </div>

    <ProcessBoardMetrics
      v-if="processBoardStore.data"
      :metrics="processBoardStore.data.metrics"
    />

    <ProgressBar v-if="processBoardStore.loading" mode="indeterminate" style="height: 4px" />

    <div class="kanban-board">
      <div
        v-for="column in sortedColumns"
        :key="column.id"
        class="kanban-column"
        @dragover.prevent
        @drop="onDrop($event, column)"
      >
        <div class="column-header">
          <span class="column-name">{{ column.name }}</span>
          <span class="column-count">{{ getInstancesForColumn(column).length }}</span>
        </div>
        <div class="column-body">
          <ProcessBoardCard
            v-for="instance in getInstancesForColumn(column)"
            :key="instance.id"
            :instance="instance"
            :is-active-column="column.nodeId === instance.activeNodeId"
            @click="navigateToInstance(instance.id)"
            @dragstart="onDragStart($event, instance)"
          />
          <div v-if="getInstancesForColumn(column).length === 0" class="column-empty">
            {{ t('processBoard.noInstances') }}
          </div>
        </div>
      </div>
    </div>

    <ActionFormDialog
      v-if="showActionDialog && pendingAction"
      :visible="showActionDialog"
      :action="pendingAction"
      @hide="onActionDialogHide"
      @submit="onActionSubmit"
    />
  </div>
</template>

<style scoped>
.process-board-page {
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

.kanban-board {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  flex: 1;
  padding-bottom: 1rem;
  margin-top: 1rem;
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
