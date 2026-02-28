<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { useResponsive } from '@/shared/composables/useResponsive'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'
import TaskListPanel from '@/modules/tasks/components/TaskListPanel.vue'
import TaskFormDialog from '@/modules/tasks/components/TaskFormDialog.vue'
import TaskCreateDialog from '@/modules/tasks/components/TaskCreateDialog.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { t } = useI18n()
const taskStore = useTaskStore()
const { isMobile } = useResponsive()

const orgId = computed(() => route.params.orgId as string)
const selectedTaskId = computed(() => route.params.taskId as string | undefined)
const hasSelectedTask = computed(() => !!selectedTaskId.value)

// Unified create dialog
const showCreateDialog = ref(false)

// Edit dialog (for existing tasks)
const showFormDialog = ref(false)
const editingTask = ref<TaskDTO | null>(null)

function onTaskSelect(taskId: string) {
  if (isMobile.value) {
    router.push({ name: 'task-detail-full', params: { orgId: orgId.value, taskId } })
  } else {
    router.push({ name: 'task-detail', params: { orgId: orgId.value, taskId } })
  }
}

function openCreate() {
  showCreateDialog.value = true
}

function onCreated() {
  showCreateDialog.value = false
  taskStore.fetchTasks(orgId.value)
}

async function handleSave(data: {
  title: string
  description: string | null
  priority: TaskPriority
  due_date: string | null
  estimated_hours: number | null
}) {
  if (!editingTask.value) return

  try {
    await taskStore.updateTask(orgId.value, editingTask.value.id, data)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tasks.taskUpdated'), life: 3000 })
    showFormDialog.value = false
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  }
}
</script>

<template>
  <div class="tasks-master-detail" :class="{ 'detail-open': hasSelectedTask }">
    <aside class="task-list-panel" :class="{ 'hidden-mobile': hasSelectedTask && isMobile }">
      <TaskListPanel
        :org-id="orgId"
        :selected-task-id="selectedTaskId"
        @select="onTaskSelect"
        @create="openCreate"
      />
    </aside>

    <section v-if="hasSelectedTask || !isMobile" class="task-detail-panel">
      <RouterView />
    </section>
  </div>

  <!-- Unified create dialog -->
  <TaskCreateDialog
    :visible="showCreateDialog"
    :org-id="orgId"
    @hide="showCreateDialog = false"
    @created="onCreated"
  />

  <!-- Edit dialog (for existing tasks) -->
  <TaskFormDialog
    v-if="editingTask"
    :visible="showFormDialog"
    :task="editingTask"
    @hide="showFormDialog = false; editingTask = null"
    @save="handleSave"
  />
</template>

<style scoped>
.tasks-master-detail {
  display: grid;
  grid-template-columns: 380px 1fr;
  height: calc(100vh - 56px - 3rem);
  margin: -1.5rem;
}

.task-list-panel {
  border-right: 1px solid var(--p-surface-border);
  overflow-y: auto;
  background: var(--p-surface-card);
}

.task-detail-panel {
  overflow-y: auto;
  padding: 1.5rem;
  background: var(--p-surface-ground);
}

@media (max-width: 768px) {
  .tasks-master-detail {
    display: block;
    height: auto;
    margin: 0;
  }

  .task-list-panel.hidden-mobile {
    display: none;
  }

  .task-detail-panel {
    padding: 1rem;
  }
}
</style>
