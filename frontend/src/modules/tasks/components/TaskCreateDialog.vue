<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useProcessInstanceStore } from '@/modules/workflow/stores/process-instance.store'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import DynamicFormField from '@/modules/tasks/components/DynamicFormField.vue'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import type { TaskPriority } from '@/modules/tasks/types/task.types'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  visible: boolean
  orgId: string
}>()

const emit = defineEmits<{
  hide: []
  created: []
}>()

const toast = useToast()
const { t } = useI18n()
const taskStore = useTaskStore()
const empStore = useEmployeeStore()
const instanceStore = useProcessInstanceStore()
const defStore = useProcessDefinitionStore()

const mode = ref<'quick' | 'process'>('quick')
const selectedDefId = ref<string | null>(null)
const startFormFields = ref<FormFieldDefinition[]>([])
const loadingStartForm = ref(false)
const submitting = ref(false)

// Basic task fields
const title = ref('')
const description = ref('')
const priority = ref<TaskPriority>('medium')
const assigneeId = ref<string | null>(null)
const dueDate = ref<Date | null>(null)
const estimatedHours = ref<number | null>(null)

// Process form data
const processFormData = ref<Record<string, unknown>>({})
const errors = ref<Record<string, string>>({})

const modeOptions = computed(() => [
  { label: t('tasks.quickTask'), value: 'quick' },
  { label: t('tasks.fromProcess'), value: 'process' },
])

const priorityOptions = computed(() => [
  { label: t('tasks.priorityLow'), value: 'low' },
  { label: t('tasks.priorityMedium'), value: 'medium' },
  { label: t('tasks.priorityHigh'), value: 'high' },
  { label: t('tasks.priorityCritical'), value: 'critical' },
])

const employeeOptions = computed(() =>
  empStore.employees
    .filter((e) => e.status === 'active')
    .map((e) => ({
      label: e.userFullName || e.employeeNumber,
      value: e.id,
    })),
)

const canSubmit = computed(() => {
  if (!title.value.trim()) return false
  if (mode.value === 'process' && !selectedDefId.value) return false
  return true
})

function resetForm() {
  mode.value = 'quick'
  selectedDefId.value = null
  startFormFields.value = []
  processFormData.value = {}
  errors.value = {}
  title.value = ''
  description.value = ''
  priority.value = 'medium'
  assigneeId.value = null
  dueDate.value = null
  estimatedHours.value = null
  submitting.value = false
}

watch(
  () => props.visible,
  (val) => {
    if (val) {
      resetForm()
      if (empStore.employees.length === 0) empStore.fetchEmployees(props.orgId)
      if (defStore.definitions.length === 0) defStore.fetchDefinitions(props.orgId, 'published')
    }
  },
)

watch(selectedDefId, async (defId) => {
  startFormFields.value = []
  processFormData.value = {}
  if (!defId) return

  loadingStartForm.value = true
  try {
    const schema = await processDefinitionApi.getStartForm(props.orgId, defId)
    startFormFields.value = schema.fields
    const data: Record<string, unknown> = {}
    for (const field of schema.fields) {
      data[field.name] = field.type === 'checkbox' ? false : field.type === 'number' ? null : ''
    }
    processFormData.value = data
  } finally {
    loadingStartForm.value = false
  }
})

function validate(): boolean {
  const errs: Record<string, string> = {}
  for (const field of startFormFields.value) {
    if (field.required) {
      const value = processFormData.value[field.name]
      if (value === null || value === undefined || value === '') {
        errs[field.name] = t('taskDetail.fieldRequired')
      }
    }
  }
  errors.value = errs
  return Object.keys(errs).length === 0
}

function serializeDate(d: Date | null): string | null {
  if (!d) return null
  return d.toISOString().split('T')[0]!
}

async function handleSubmit() {
  if (!canSubmit.value || !validate()) return

  submitting.value = true
  try {
    if (mode.value === 'quick') {
      const currentEmployee = empStore.employees.find(() => true)
      await taskStore.createTask(props.orgId, {
        title: title.value,
        description: description.value || null,
        priority: priority.value,
        due_date: serializeDate(dueDate.value),
        estimated_hours: estimatedHours.value,
        creator_id: currentEmployee?.id ?? '',
        assignee_id: assigneeId.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('tasks.taskCreated'), life: 3000 })
    } else {
      // Serialize process form data
      const serializedFormData: Record<string, unknown> = {}
      for (const [key, value] of Object.entries(processFormData.value)) {
        serializedFormData[key] = value instanceof Date ? value.toISOString().split('T')[0] : value
      }

      const variables: Record<string, unknown> = {
        _task_title: title.value,
        _task_description: description.value || null,
        _task_priority: priority.value,
        _task_due_date: serializeDate(dueDate.value),
        _task_creator_id: empStore.employees.find(() => true)?.id ?? '',
        ...serializedFormData,
      }

      await instanceStore.startProcess(props.orgId, selectedDefId.value!, variables)
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.instanceStarted'), life: 3000 })

      // Small delay for event-driven task creation
      await new Promise((resolve) => setTimeout(resolve, 500))
      await taskStore.fetchTasks(props.orgId)
    }

    emit('created')
    emit('hide')
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="t('tasks.createTask')"
    modal
    :style="{ width: '600px' }"
    @update:visible="!$event && emit('hide')"
  >
    <div class="create-form">
      <!-- Mode toggle -->
      <div class="form-group">
        <SelectButton
          v-model="mode"
          :options="modeOptions"
          optionLabel="label"
          optionValue="value"
          :allowEmpty="false"
          size="small"
          class="mode-toggle"
        />
      </div>

      <!-- Process selector -->
      <div v-if="mode === 'process'" class="form-group">
        <label>{{ t('tasks.selectProcess') }}</label>
        <Select
          v-model="selectedDefId"
          :options="defStore.definitions"
          optionValue="id"
          optionLabel="name"
          :placeholder="t('tasks.selectProcess')"
          class="w-full"
          showClear
        />
        <ProgressSpinner v-if="loadingStartForm" style="width: 1.5rem; height: 1.5rem; margin-top: 0.5rem" />
      </div>

      <Divider />

      <!-- Basic fields -->
      <div class="form-group">
        <label>{{ t('tasks.titleColumn') }} *</label>
        <InputText v-model="title" :placeholder="t('tasks.titleColumn')" class="w-full" />
      </div>

      <div class="form-group">
        <label>{{ t('tasks.description') }}</label>
        <Textarea v-model="description" :placeholder="t('tasks.description')" class="w-full" rows="3" autoResize />
      </div>

      <div class="form-row">
        <div class="form-group flex-1">
          <label>{{ t('tasks.priorityLabel') }}</label>
          <Select
            v-model="priority"
            :options="priorityOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>
        <div v-if="mode === 'quick'" class="form-group flex-1">
          <label>{{ t('tasks.assigneeLabel') }}</label>
          <Select
            v-model="assigneeId"
            :options="employeeOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('tasks.assigneePlaceholder')"
            class="w-full"
            showClear
            filter
          />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group flex-1">
          <label>{{ t('taskDetail.dueDate') }}</label>
          <DatePicker v-model="dueDate" dateFormat="yy-mm-dd" showIcon class="w-full" />
        </div>
        <div class="form-group flex-1">
          <label>{{ t('taskDetail.estimatedHours') }}</label>
          <InputNumber v-model="estimatedHours" :min="0" :maxFractionDigits="1" class="w-full" />
        </div>
      </div>

      <!-- Process start form fields -->
      <template v-if="mode === 'process' && startFormFields.length > 0">
        <Divider />
        <h4 class="section-title">{{ t('tasks.processFields') }}</h4>
        <DynamicFormField
          v-for="field in startFormFields"
          :key="field.name"
          :field="field"
          :model-value="processFormData[field.name]"
          :error="errors[field.name]"
          @update:model-value="processFormData[field.name] = $event"
        />
      </template>
    </div>

    <template #footer>
      <Button :label="t('common.cancel')" text @click="emit('hide')" />
      <Button
        :label="t('tasks.createTask')"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="handleSubmit"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.create-form {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group {
  margin-bottom: 0.75rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 500;
  font-size: 0.875rem;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
  min-width: 0;
}

.mode-toggle {
  width: 100%;
}

.mode-toggle :deep(.p-togglebutton) {
  flex: 1;
}

.section-title {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}
</style>
