<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import TaskCard from '@/modules/tasks/components/TaskCard.vue'
import { isOverdue } from '@/shared/utils/date-format'

const props = defineProps<{
  orgId: string
  selectedTaskId?: string
}>()

const emit = defineEmits<{
  select: [taskId: string]
  create: []
}>()

const { t } = useI18n()
const taskStore = useTaskStore()
const empStore = useEmployeeStore()
const authStore = useAuthStore()
const defStore = useProcessDefinitionStore()

const searchQuery = ref('')
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
    result = result.filter((t) => t.title.toLowerCase().includes(q))
  }

  if (quickFilter.value === 'my' && currentEmployeeId.value) {
    result = result.filter((t) => t.assigneeId === currentEmployeeId.value)
  } else if (quickFilter.value === 'available') {
    result = result.filter((t) => t.isPoolTask && !t.assigneeId)
  } else if (quickFilter.value === 'overdue') {
    result = result.filter((t) => isOverdue(t.dueDate))
  }

  if (filterStatus.value) {
    result = result.filter((t) => t.status === filterStatus.value)
  }

  if (filterPriority.value) {
    result = result.filter((t) => t.priority === filterPriority.value)
  }

  return result
})

onMounted(async () => {
  await Promise.all([
    taskStore.fetchTasks(props.orgId),
    empStore.employees.length === 0 ? empStore.fetchEmployees(props.orgId) : Promise.resolve(),
    defStore.definitions.length === 0 ? defStore.fetchDefinitions(props.orgId, 'published') : Promise.resolve(),
  ])
})

watch(
  () => props.orgId,
  (newOrgId) => {
    if (newOrgId) taskStore.fetchTasks(newOrgId)
  },
)
</script>

<template>
  <div class="task-list-panel-inner">
    <div class="panel-header">
      <h3>{{ t('tasks.title') }}</h3>
      <div class="panel-header-actions">
        <Button
          icon="pi pi-plus"
          text
          rounded
          size="small"
          v-tooltip.bottom="t('tasks.createTask')"
          @click="emit('create')"
        />
      </div>
    </div>

    <div class="panel-search">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText
          v-model="searchQuery"
          :placeholder="t('tasks.searchPlaceholder')"
          class="w-full"
          size="small"
        />
      </IconField>
    </div>

    <div class="panel-quick-filter">
      <SelectButton
        v-model="quickFilter"
        :options="quickFilterOptions"
        optionLabel="label"
        optionValue="value"
        :allowEmpty="false"
        size="small"
      />
    </div>

    <div class="panel-filters">
      <Select
        v-model="filterStatus"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('tasks.filterByStatus')"
        showClear
        size="small"
        class="filter-select"
      />
      <Select
        v-model="filterPriority"
        :options="priorityOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('tasks.filterByPriority')"
        showClear
        size="small"
        class="filter-select"
      />
    </div>

    <div class="panel-task-list">
      <div v-if="taskStore.loading && taskStore.tasks.length === 0" class="panel-loading">
        <ProgressSpinner style="width: 2rem; height: 2rem" />
      </div>
      <div v-else-if="filteredTasks.length === 0" class="panel-empty">
        <i class="pi pi-inbox" style="font-size: 2rem; color: var(--p-text-muted-color)" />
        <p>{{ t('tasks.noTasksFound') }}</p>
      </div>
      <template v-else>
        <TaskCard
          v-for="task in filteredTasks"
          :key="task.id"
          :task="task"
          :active="task.id === selectedTaskId"
          @click="emit('select', task.id)"
        />
      </template>
    </div>
  </div>
</template>

<style scoped>
.task-list-panel-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1rem 0.5rem;
}

.panel-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.panel-header-actions {
  display: flex;
  gap: 0.25rem;
}

.panel-search {
  padding: 0 1rem 0.5rem;
}

.panel-quick-filter {
  padding: 0 1rem 0.5rem;
}

.panel-quick-filter :deep(.p-selectbutton) {
  width: 100%;
}

.panel-quick-filter :deep(.p-selectbutton .p-togglebutton) {
  flex: 1;
  font-size: 0.8rem;
  padding: 0.35rem 0.5rem;
}

.panel-filters {
  display: flex;
  gap: 0.5rem;
  padding: 0 1rem 0.5rem;
}

.filter-select {
  flex: 1;
  min-width: 0;
}

.panel-task-list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0 0.75rem 0.75rem;
}

.panel-loading,
.panel-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  gap: 0.75rem;
  color: var(--p-text-muted-color);
}
</style>
