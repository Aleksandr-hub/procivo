<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import { isOverdue } from '@/shared/utils/date-format'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'
import TaskCard from '@/modules/tasks/components/TaskCard.vue'
import TaskCreateDialog from '@/modules/tasks/components/TaskCreateDialog.vue'
import TaskFormDialog from '@/modules/tasks/components/TaskFormDialog.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { t } = useI18n()
const taskStore = useTaskStore()
const empStore = useEmployeeStore()
const authStore = useAuthStore()
const defStore = useProcessDefinitionStore()

const orgId = computed(() => route.params.orgId as string)

const showCreateDialog = ref(false)
const showFormDialog = ref(false)
const editingTask = ref<TaskDTO | null>(null)

const searchQuery = ref('')
const showFilters = ref(false)
const quickFilter = ref<string>('all')
const filterStatus = ref<string | undefined>(undefined)
const filterPriority = ref<string | undefined>(undefined)

const quickFilterOptions = computed(() => [
  { label: t('tasks.allTasks'), value: 'all' },
  { label: t('tasks.myTasks'), value: 'my' },
  { label: t('tasks.availableForMe'), value: 'available' },
  { label: t('tasks.overdue'), value: 'overdue' },
])

const statusOptions = computed(() => [
  { label: t('tasks.statusDraft'), value: 'draft' },
  { label: t('tasks.statusOpen'), value: 'open' },
  { label: t('tasks.statusInProgress'), value: 'in_progress' },
  { label: t('tasks.statusReview'), value: 'review' },
  { label: t('tasks.statusDone'), value: 'done' },
  { label: t('tasks.statusBlocked'), value: 'blocked' },
  { label: t('tasks.statusCancelled'), value: 'cancelled' },
])

const priorityOptions = computed(() => [
  { label: t('tasks.priorityLow'), value: 'low' },
  { label: t('tasks.priorityMedium'), value: 'medium' },
  { label: t('tasks.priorityHigh'), value: 'high' },
  { label: t('tasks.priorityCritical'), value: 'critical' },
])

const currentEmployeeId = computed(() => {
  if (!authStore.user) return undefined
  return empStore.employees.find((e) => e.userId === authStore.user!.id && e.status === 'active')?.id
})

const filteredTasks = computed(() => {
  let result = taskStore.tasks

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter((task) => task.title.toLowerCase().includes(q))
  }

  if (quickFilter.value === 'my' && currentEmployeeId.value) {
    result = result.filter((task) => task.assigneeId === currentEmployeeId.value)
  } else if (quickFilter.value === 'available') {
    result = result.filter((task) => task.isPoolTask && !task.assigneeId)
  } else if (quickFilter.value === 'overdue') {
    result = result.filter((task) => isOverdue(task.dueDate))
  }

  if (filterStatus.value) {
    result = result.filter((task) => task.status === filterStatus.value)
  }

  if (filterPriority.value) {
    result = result.filter((task) => task.priority === filterPriority.value)
  }

  return result
})

function onTaskClick(taskId: string) {
  router.push({ name: 'task-detail', params: { orgId: orgId.value, taskId } })
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

onMounted(async () => {
  await Promise.all([
    taskStore.fetchTasks(orgId.value),
    empStore.employees.length === 0 ? empStore.fetchEmployees(orgId.value) : Promise.resolve(),
    defStore.definitions.length === 0 ? defStore.fetchDefinitions(orgId.value, 'published') : Promise.resolve(),
  ])
})

watch(
  () => orgId.value,
  (newOrgId) => {
    if (newOrgId) taskStore.fetchTasks(newOrgId)
  },
)
</script>

<template>
  <div class="tasks-page">
    <!-- Header -->
    <div class="tasks-header">
      <div class="tasks-header-left">
        <h2>{{ t('tasks.title') }}</h2>
        <span class="tasks-count">{{ filteredTasks.length }} {{ t('tasks.tasksCount') }}</span>
      </div>
      <div class="tasks-header-right">
        <Button
          :label="t('tasks.createTask')"
          icon="pi pi-plus"
          @click="showCreateDialog = true"
        />
      </div>
    </div>

    <!-- Search + Filters row -->
    <div class="tasks-toolbar">
      <div class="toolbar-left">
        <IconField class="search-field">
          <InputIcon class="pi pi-search" />
          <InputText
            v-model="searchQuery"
            :placeholder="t('tasks.searchPlaceholder')"
            class="w-full"
          />
        </IconField>

        <SelectButton
          v-model="quickFilter"
          :options="quickFilterOptions"
          optionLabel="label"
          optionValue="value"
          :allowEmpty="false"
          class="quick-filter-buttons"
        />
      </div>

      <Button
        :label="t('tasks.filters')"
        icon="pi pi-filter"
        :outlined="!showFilters"
        :severity="showFilters ? undefined : 'secondary'"
        @click="showFilters = !showFilters"
      />
    </div>

    <!-- Extended filters (collapsible) -->
    <div v-if="showFilters" class="tasks-filters">
      <Select
        v-model="filterStatus"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('tasks.filterByStatus')"
        showClear
        class="filter-select"
      />
      <Select
        v-model="filterPriority"
        :options="priorityOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('tasks.filterByPriority')"
        showClear
        class="filter-select"
      />
    </div>

    <!-- Task list -->
    <div class="tasks-list">
      <div v-if="taskStore.loading && taskStore.tasks.length === 0" class="tasks-loading">
        <ProgressSpinner style="width: 3rem; height: 3rem" />
      </div>
      <div v-else-if="filteredTasks.length === 0" class="tasks-empty">
        <i class="pi pi-inbox" />
        <p>{{ t('tasks.noTasksFound') }}</p>
      </div>
      <template v-else>
        <TaskCard
          v-for="task in filteredTasks"
          :key="task.id"
          :task="task"
          :active="false"
          @click="onTaskClick(task.id)"
        />
      </template>
    </div>
  </div>

  <TaskCreateDialog
    :visible="showCreateDialog"
    :org-id="orgId"
    @hide="showCreateDialog = false"
    @created="onCreated"
  />

  <TaskFormDialog
    v-if="editingTask"
    :visible="showFormDialog"
    :task="editingTask"
    @hide="showFormDialog = false; editingTask = null"
    @save="handleSave"
  />
</template>

<style scoped>
.tasks-page {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem;
}

.tasks-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.tasks-header-left {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
}

.tasks-header-left h2 {
  margin: 0;
  font-size: 1.5rem;
}

.tasks-count {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.tasks-header-right {
  display: flex;
  gap: 0.5rem;
}

.tasks-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.search-field {
  max-width: 400px;
  flex: 1;
}

.quick-filter-buttons :deep(.p-togglebutton) {
  font-size: 0.85rem;
  padding: 0.4rem 0.75rem;
}

.tasks-filters {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: var(--p-surface-50);
  border-radius: var(--p-border-radius);
  border: 1px solid var(--p-surface-border);
}

:root.p-dark .tasks-filters {
  background: var(--p-surface-800);
}

.filter-select {
  min-width: 180px;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.tasks-loading,
.tasks-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  gap: 1rem;
  color: var(--p-text-muted-color);
}

.tasks-empty i {
  font-size: 3rem;
}

@media (max-width: 768px) {
  .tasks-page {
    padding: 1rem;
  }

  .tasks-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-left {
    flex-direction: column;
  }

  .search-field {
    max-width: none;
  }

  .quick-filter-buttons {
    width: 100%;
  }

  .quick-filter-buttons :deep(.p-selectbutton) {
    width: 100%;
  }

  .quick-filter-buttons :deep(.p-togglebutton) {
    flex: 1;
  }
}
</style>
