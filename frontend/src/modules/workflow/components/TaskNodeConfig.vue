<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'
import type { AssignmentStrategy } from '@/modules/tasks/types/task.types'
import FormFieldsBuilder from '@/modules/workflow/components/FormFieldsBuilder.vue'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import type { DepartmentTreeDTO } from '@/modules/organization/types/organization.types'

const props = defineProps<{
  config: Record<string, unknown>
  orgId: string
  readonly: boolean
  nodeId: string
}>()

const emit = defineEmits<{
  update: [config: Record<string, unknown>]
}>()

const { t } = useI18n()

const empStore = useEmployeeStore()
const roleStore = useRoleStore()
const deptStore = useDepartmentStore()

const taskTitle = ref('')
const taskDescription = ref('')
const assignmentStrategy = ref<AssignmentStrategy>('unassigned')
const assigneeEmployeeId = ref<string | null>(null)
const assigneeRoleId = ref<string | null>(null)
const assigneeDepartmentId = ref<string | null>(null)

const strategyOptions = computed(() => [
  { label: t('workflow.strategyUnassigned'), value: 'unassigned' },
  { label: t('workflow.strategySpecificUser'), value: 'specific_user' },
  { label: t('workflow.strategyByRole'), value: 'by_role' },
  { label: t('workflow.strategyByDepartment'), value: 'by_department' },
  { label: t('workflow.strategyFromVariable'), value: 'from_variable' },
])

const employeeOptions = computed(() =>
  empStore.employees
    .filter((e) => e.status === 'active')
    .map((e) => ({
      label: e.userFullName || e.employeeNumber,
      value: e.id,
    })),
)

const roleOptions = computed(() =>
  roleStore.roles.map((r) => ({
    label: r.name,
    value: r.id,
  })),
)

function flattenDepts(nodes: DepartmentTreeDTO[]): { label: string; value: string }[] {
  const result: { label: string; value: string }[] = []
  for (const node of nodes) {
    const indent = '\u00A0\u00A0'.repeat(node.level)
    result.push({ label: `${indent}${node.name}`, value: node.id })
    if (node.children.length > 0) {
      result.push(...flattenDepts(node.children))
    }
  }
  return result
}

const departmentOptions = computed(() => flattenDepts(deptStore.tree))

const strategyHint = computed(() => {
  switch (assignmentStrategy.value) {
    case 'by_role':
      return t('workflow.strategyByRoleHint')
    case 'by_department':
      return t('workflow.strategyByDeptHint')
    case 'from_variable':
      return t('workflow.strategyFromVariableHint')
    default:
      return null
  }
})

watch(
  () => props.config,
  (cfg) => {
    taskTitle.value = (cfg.task_title_template as string) || ''
    taskDescription.value = (cfg.task_description_template as string) || ''
    // Support new strategy keys with fallback to old assignee_type
    const strategy = (cfg.assignment_strategy as string) || (cfg.assignee_type as string) || 'unassigned'
    // Map old values to new
    if (strategy === 'none') {
      assignmentStrategy.value = 'unassigned'
    } else if (strategy === 'specific') {
      assignmentStrategy.value = 'specific_user'
      assigneeEmployeeId.value = (cfg.assignee_employee_id as string) || (cfg.assignee_value as string) || null
    } else if (strategy === 'role') {
      assignmentStrategy.value = 'by_role'
      assigneeRoleId.value = (cfg.assignee_role_id as string) || (cfg.assignee_value as string) || null
    } else {
      assignmentStrategy.value = strategy as AssignmentStrategy
      assigneeEmployeeId.value = (cfg.assignee_employee_id as string) || null
      assigneeRoleId.value = (cfg.assignee_role_id as string) || null
      assigneeDepartmentId.value = (cfg.assignee_department_id as string) || null
    }
  },
  { immediate: true },
)

onMounted(() => {
  if (empStore.employees.length === 0) empStore.fetchEmployees(props.orgId)
  if (roleStore.roles.length === 0) roleStore.fetchRoles(props.orgId)
  if (deptStore.tree.length === 0) deptStore.fetchTree(props.orgId)
})

function buildConfig(extraFields?: Record<string, unknown>): Record<string, unknown> {
  const cfg: Record<string, unknown> = {
    ...props.config,
    task_title_template: taskTitle.value || undefined,
    task_description_template: taskDescription.value || undefined,
    assignment_strategy: assignmentStrategy.value,
    ...extraFields,
  }

  // Clean old keys
  delete cfg.assignee_type
  delete cfg.assignee_value

  // Set strategy-specific keys
  cfg.assignee_employee_id =
    assignmentStrategy.value === 'specific_user' ? assigneeEmployeeId.value : undefined
  cfg.assignee_role_id =
    assignmentStrategy.value === 'by_role' ? assigneeRoleId.value : undefined
  cfg.assignee_department_id =
    assignmentStrategy.value === 'by_department' ? assigneeDepartmentId.value : undefined
  // Clean old key
  delete cfg.assignee_variable_name

  return cfg
}

function emitConfig() {
  emit('update', buildConfig())
}

function onFieldsUpdate(fields: FormFieldDefinition[]) {
  emit('update', buildConfig({ formFields: fields }))
}
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.taskConfig') }}</h5>

    <div class="config-field">
      <label>{{ t('workflow.taskTitleTemplate') }}</label>
      <InputText
        v-model="taskTitle"
        :disabled="readonly"
        :placeholder="t('workflow.taskTitleTemplatePlaceholder')"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.taskDescriptionTemplate') }}</label>
      <Textarea
        v-model="taskDescription"
        :disabled="readonly"
        :placeholder="t('workflow.taskDescriptionTemplatePlaceholder')"
        class="w-full"
        rows="2"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.assignmentStrategy') }}</label>
      <Select
        v-model="assignmentStrategy"
        :options="strategyOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
      <small v-if="strategyHint" class="config-hint">{{ strategyHint }}</small>
    </div>

    <div v-if="assignmentStrategy === 'specific_user'" class="config-field">
      <label>{{ t('workflow.selectEmployee') }}</label>
      <Select
        v-model="assigneeEmployeeId"
        :options="employeeOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        :placeholder="t('workflow.selectEmployee')"
        :loading="empStore.loading"
        filter
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div v-if="assignmentStrategy === 'by_role'" class="config-field">
      <label>{{ t('workflow.selectRole') }}</label>
      <Select
        v-model="assigneeRoleId"
        :options="roleOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        :placeholder="t('workflow.selectRole')"
        :loading="roleStore.loading"
        filter
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div v-if="assignmentStrategy === 'by_department'" class="config-field">
      <label>{{ t('workflow.selectDepartment') }}</label>
      <Select
        v-model="assigneeDepartmentId"
        :options="departmentOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        :placeholder="t('workflow.selectDepartment')"
        :loading="deptStore.loading"
        filter
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div v-if="assignmentStrategy === 'from_variable'" class="config-field">
      <label>{{ t('workflow.variableName') }}</label>
      <InputText
        :model-value="`_assignee_for_${nodeId}`"
        disabled
        class="w-full"
      />
      <small class="config-hint">{{ t('workflow.strategyFromVariableHint') }}</small>
    </div>

    <Divider />

    <FormFieldsBuilder
      :fields="(config.formFields as FormFieldDefinition[]) || []"
      :readonly="readonly"
      @update="onFieldsUpdate"
    />
  </div>
</template>

<style scoped>
.config-section h5 {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.config-field {
  margin-bottom: 0.75rem;
}

.config-field label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 500;
}

.config-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  line-height: 1.4;
}
</style>
