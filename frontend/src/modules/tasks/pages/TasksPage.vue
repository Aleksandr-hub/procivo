<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'
import TaskFormDialog from '@/modules/tasks/components/TaskFormDialog.vue'
import TaskComments from '@/modules/tasks/components/TaskComments.vue'
import TaskLabels from '@/modules/tasks/components/TaskLabels.vue'
import TaskAssignments from '@/modules/tasks/components/TaskAssignments.vue'
import TaskAttachments from '@/modules/tasks/components/TaskAttachments.vue'

const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const taskStore = useTaskStore()
const empStore = useEmployeeStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)

const filterStatus = ref<string | undefined>(undefined)
const showFormDialog = ref(false)
const editingTask = ref<TaskDTO | null>(null)

const showCommentsDrawer = ref(false)
const commentsTask = ref<TaskDTO | null>(null)

const statusOptions = [
  { label: t('tasks.statusDraft'), value: 'draft' },
  { label: t('tasks.statusOpen'), value: 'open' },
  { label: t('tasks.statusInProgress'), value: 'in_progress' },
  { label: t('tasks.statusReview'), value: 'review' },
  { label: t('tasks.statusDone'), value: 'done' },
  { label: t('tasks.statusBlocked'), value: 'blocked' },
  { label: t('tasks.statusCancelled'), value: 'cancelled' },
]

onMounted(async () => {
  await Promise.all([
    taskStore.fetchTasks(orgId.value),
    empStore.employees.length === 0 ? empStore.fetchEmployees(orgId.value) : Promise.resolve(),
  ])
})

async function onFilterChange() {
  await taskStore.fetchTasks(orgId.value, filterStatus.value)
}

function openCreate() {
  editingTask.value = null
  showFormDialog.value = true
}

function openEdit(task: TaskDTO) {
  editingTask.value = task
  showFormDialog.value = true
}

function openComments(task: TaskDTO) {
  commentsTask.value = task
  showCommentsDrawer.value = true
}

async function handleSave(data: {
  title: string
  description: string | null
  priority: TaskPriority
  due_date: string | null
  estimated_hours: number | null
}) {
  try {
    if (editingTask.value) {
      await taskStore.updateTask(orgId.value, editingTask.value.id, data)
      toast.add({
        severity: 'success',
        summary: t('common.success'),
        detail: t('tasks.taskUpdated'),
        life: 3000,
      })
    } else {
      const currentEmployee = empStore.employees.find(() => true)
      await taskStore.createTask(orgId.value, {
        ...data,
        creator_id: currentEmployee?.id ?? '',
      })
      toast.add({
        severity: 'success',
        summary: t('common.success'),
        detail: t('tasks.taskCreated'),
        life: 3000,
      })
    }
    showFormDialog.value = false
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('tasks.operationFailed'),
      life: 5000,
    })
  }
}

async function handleTransition(task: TaskDTO, transition: string) {
  try {
    await taskStore.transitionTask(orgId.value, task.id, transition)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('tasks.statusUpdated'),
      life: 3000,
    })
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('tasks.operationFailed'),
      life: 5000,
    })
  }
}

function confirmDelete(task: TaskDTO) {
  confirm.require({
    message: t('tasks.confirmDelete', { title: task.title }),
    header: t('tasks.confirmDeleteTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await taskStore.deleteTask(orgId.value, task.id)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('tasks.taskDeleted'),
          life: 3000,
        })
      } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { error?: string } } }
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: axiosError.response?.data?.error || t('tasks.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}

function getStatusSeverity(status: string) {
  switch (status) {
    case 'draft':
      return 'secondary'
    case 'open':
      return 'info'
    case 'in_progress':
      return 'warn'
    case 'review':
      return 'info'
    case 'done':
      return 'success'
    case 'blocked':
      return 'danger'
    case 'cancelled':
      return 'secondary'
    default:
      return undefined
  }
}

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

function getTransitionLabel(transition: string): string {
  const key = `tasks.transition_${transition}`
  return t(key)
}
</script>

<template>
  <div class="tasks-page">
    <div class="page-header">
      <h3>{{ t('tasks.title') }}</h3>
      <Button :label="t('tasks.createTask')" icon="pi pi-plus" @click="openCreate" />
    </div>

    <div class="tab-toolbar">
      <Select
        v-model="filterStatus"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('tasks.allStatuses')"
        showClear
        @change="onFilterChange"
        style="width: 200px"
      />
    </div>

    <DataTable
      :value="taskStore.tasks"
      :loading="taskStore.loading"
      stripedRows
      paginator
      :rows="20"
    >
      <template #empty>
        <div class="empty-table">{{ t('tasks.noTasksFound') }}</div>
      </template>
      <Column field="title" :header="t('tasks.titleColumn')" sortable style="min-width: 200px" />
      <Column field="status" :header="t('tasks.statusColumn')" sortable style="width: 140px">
        <template #body="{ data }">
          <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
        </template>
      </Column>
      <Column field="priority" :header="t('tasks.priorityColumn')" sortable style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.priority" :severity="getPrioritySeverity(data.priority)" />
        </template>
      </Column>
      <Column field="dueDate" :header="t('tasks.dueDateColumn')" sortable style="width: 140px">
        <template #body="{ data }">
          {{ data.dueDate ? new Date(data.dueDate).toLocaleDateString() : '—' }}
        </template>
      </Column>
      <Column field="createdAt" :header="t('tasks.createdAtColumn')" sortable style="width: 140px">
        <template #body="{ data }">
          {{ new Date(data.createdAt).toLocaleDateString() }}
        </template>
      </Column>
      <Column :header="t('tasks.actionsColumn')" style="width: 280px">
        <template #body="{ data }">
          <div class="action-buttons">
            <Button
              v-for="tr in data.availableTransitions"
              :key="tr"
              :label="getTransitionLabel(tr)"
              text
              size="small"
              @click="handleTransition(data, tr)"
            />
            <Button
              icon="pi pi-comments"
              text
              rounded
              size="small"
              @click="openComments(data)"
              v-tooltip="t('comments.title')"
            />
            <Button
              icon="pi pi-pencil"
              text
              rounded
              size="small"
              @click="openEdit(data)"
              v-tooltip="t('common.edit')"
            />
            <Button
              icon="pi pi-trash"
              text
              rounded
              size="small"
              severity="danger"
              @click="confirmDelete(data)"
              v-tooltip="t('common.delete')"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <TaskFormDialog
      :visible="showFormDialog"
      :task="editingTask"
      @hide="showFormDialog = false"
      @save="handleSave"
    />

    <!-- Comments Drawer -->
    <Drawer
      v-model:visible="showCommentsDrawer"
      :header="commentsTask ? `${t('comments.title')}: ${commentsTask.title}` : t('comments.title')"
      position="right"
      style="width: 500px"
    >
      <template v-if="commentsTask">
        <TaskAssignments :orgId="orgId" :taskId="commentsTask.id" />
        <Divider />
        <TaskLabels :orgId="orgId" :taskId="commentsTask.id" />
        <Divider />
        <TaskAttachments :orgId="orgId" :taskId="commentsTask.id" />
        <Divider />
        <TaskComments :orgId="orgId" :taskId="commentsTask.id" />
      </template>
    </Drawer>
  </div>
</template>

<style scoped>
.tasks-page {
  max-width: 1200px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.page-header h3 {
  margin: 0;
}

.tab-toolbar {
  margin-bottom: 1rem;
}

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-wrap: wrap;
}
</style>
