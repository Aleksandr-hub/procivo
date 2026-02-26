<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useAssignmentStore } from '@/modules/tasks/stores/assignment.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import type { AssignmentRole } from '@/modules/tasks/types/assignment.types'

const props = defineProps<{
  orgId: string
  taskId: string
}>()

const toast = useToast()
const assignmentStore = useAssignmentStore()
const empStore = useEmployeeStore()
const { t } = useI18n()

const selectedEmployeeId = ref<string | null>(null)
const selectedRole = ref<AssignmentRole>('assignee')

const roleOptions = computed(() => [
  { label: t('assignments.roleAssignee'), value: 'assignee' as AssignmentRole },
  { label: t('assignments.roleReviewer'), value: 'reviewer' as AssignmentRole },
  { label: t('assignments.roleWatcher'), value: 'watcher' as AssignmentRole },
])

const assignedEmployeeIds = computed(() =>
  new Set(assignmentStore.assignments.map((a) => `${a.employeeId}-${a.role}`)),
)

const availableEmployees = computed(() =>
  empStore.employees.filter(
    (e) => !assignedEmployeeIds.value.has(`${e.id}-${selectedRole.value}`),
  ),
)

onMounted(async () => {
  await Promise.all([
    assignmentStore.fetchAssignments(props.orgId, props.taskId),
    empStore.employees.length === 0 ? empStore.fetchEmployees(props.orgId) : Promise.resolve(),
  ])
})

watch(
  () => props.taskId,
  (newId) => {
    if (newId) {
      assignmentStore.fetchAssignments(props.orgId, newId)
    }
  },
)

async function addAssignment() {
  if (!selectedEmployeeId.value) return
  try {
    await assignmentStore.addAssignment(
      props.orgId,
      props.taskId,
      selectedEmployeeId.value,
      selectedRole.value,
    )
    selectedEmployeeId.value = null
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('assignments.failedToAssign'),
      life: 5000,
    })
  }
}

async function removeAssignment(assignmentId: string) {
  try {
    await assignmentStore.removeAssignment(props.orgId, props.taskId, assignmentId)
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('assignments.failedToRemove'),
      life: 5000,
    })
  }
}

function getRoleSeverity(role: string) {
  switch (role) {
    case 'assignee':
      return 'info'
    case 'reviewer':
      return 'warn'
    case 'watcher':
      return 'secondary'
    default:
      return undefined
  }
}

function getRoleLabel(role: string): string {
  return t(`assignments.role${role.charAt(0).toUpperCase() + role.slice(1)}`)
}
</script>

<template>
  <div class="task-assignments">
    <h4>{{ t('assignments.title') }}</h4>

    <!-- Current assignments -->
    <div v-if="assignmentStore.assignments.length === 0" class="no-assignments">
      {{ t('assignments.noAssignments') }}
    </div>
    <div v-else class="assignments-list">
      <div
        v-for="assignment in assignmentStore.assignments"
        :key="assignment.id"
        class="assignment-item"
      >
        <div class="assignment-info">
          <i class="pi pi-user" />
          <span class="assignment-name">
            {{ assignment.employeeName || assignment.employeeId }}
          </span>
          <Tag :value="getRoleLabel(assignment.role)" :severity="getRoleSeverity(assignment.role)" />
        </div>
        <Button
          icon="pi pi-times"
          text
          rounded
          size="small"
          severity="danger"
          @click="removeAssignment(assignment.id)"
          v-tooltip="t('common.remove')"
        />
      </div>
    </div>

    <!-- Add assignment form -->
    <div class="add-assignment-form">
      <Select
        v-model="selectedRole"
        :options="roleOptions"
        optionLabel="label"
        optionValue="value"
        style="width: 130px"
      />
      <Select
        v-model="selectedEmployeeId"
        :options="availableEmployees"
        :optionLabel="(e: { userFullName?: string; employeeNumber: string }) => e.userFullName || e.employeeNumber"
        optionValue="id"
        :placeholder="t('assignments.selectEmployee')"
        filter
        class="flex-1"
      />
      <Button
        icon="pi pi-plus"
        :disabled="!selectedEmployeeId"
        @click="addAssignment"
        size="small"
      />
    </div>
  </div>
</template>

<style scoped>
.task-assignments h4 {
  margin: 0 0 0.75rem 0;
  font-size: 0.9rem;
  color: var(--p-text-muted-color);
}

.no-assignments {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
  margin-bottom: 0.75rem;
}

.assignments-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.assignment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--p-content-border-color);
  border-radius: var(--p-border-radius);
}

.assignment-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.assignment-name {
  font-weight: 500;
}

.add-assignment-form {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.flex-1 {
  flex: 1;
}
</style>
