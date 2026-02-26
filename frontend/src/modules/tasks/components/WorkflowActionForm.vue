<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import DynamicFormField from '@/modules/tasks/components/DynamicFormField.vue'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import type { TaskWorkflowContextDTO, WorkflowActionDTO } from '@/modules/tasks/types/task.types'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  workflowContext: TaskWorkflowContextDTO
  orgId: string
  taskId: string
}>()

const emit = defineEmits<{
  executed: []
}>()

const toast = useToast()
const { t } = useI18n()
const taskStore = useTaskStore()

const selectedAction = ref<WorkflowActionDTO | null>(null)
const formData = ref<Record<string, unknown>>({})
const submitting = ref(false)
const errors = ref<Record<string, string>>({})

function getDefaultValue(field: FormFieldDefinition): unknown {
  switch (field.type) {
    case 'checkbox':
      return false
    case 'number':
      return null
    case 'date':
      return null
    default:
      return ''
  }
}

function selectAction(action: WorkflowActionDTO) {
  selectedAction.value = action
  errors.value = {}

  const data: Record<string, unknown> = {}
  for (const field of props.workflowContext.form_schema.shared_fields) {
    data[field.name] = formData.value[field.name] ?? getDefaultValue(field)
  }
  for (const field of action.form_fields) {
    data[field.name] = formData.value[field.name] ?? getDefaultValue(field)
  }
  formData.value = data
}

function validate(): boolean {
  const errs: Record<string, string> = {}
  const allFields = [
    ...props.workflowContext.form_schema.shared_fields,
    ...(selectedAction.value?.form_fields ?? []),
  ]

  for (const field of allFields) {
    if (field.required) {
      const value = formData.value[field.name]
      if (value === null || value === undefined || value === '') {
        errs[field.name] = t('taskDetail.fieldRequired')
      }
    }
  }

  errors.value = errs
  return Object.keys(errs).length === 0
}

function serializeFormData(): Record<string, unknown> {
  const serialized: Record<string, unknown> = {}
  for (const [key, value] of Object.entries(formData.value)) {
    if (value instanceof Date) {
      serialized[key] = value.toISOString().split('T')[0]
    } else {
      serialized[key] = value
    }
  }
  return serialized
}

async function handleSubmit() {
  if (!selectedAction.value || !validate()) return

  submitting.value = true
  try {
    await taskStore.executeAction(props.orgId, props.taskId, {
      action_key: selectedAction.value.key,
      form_data: serializeFormData(),
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('taskDetail.actionExecuted'),
      life: 3000,
    })
    emit('executed')
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('taskDetail.actionFailed')),
      life: 5000,
    })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="workflow-action-form">
    <div class="workflow-info">
      <i class="pi pi-sitemap" />
      <span class="process-name">{{ workflowContext.process_name }}</span>
      <Tag :value="workflowContext.node_name" severity="info" />
    </div>

    <Message v-if="workflowContext.is_completed" severity="success" :closable="false">
      {{ t('taskDetail.stageCompleted') }}
    </Message>

    <template v-else>
      <!-- Shared fields -->
      <div v-if="workflowContext.form_schema.shared_fields.length > 0" class="shared-fields">
        <h5>{{ t('taskDetail.sharedFields') }}</h5>
        <DynamicFormField
          v-for="field in workflowContext.form_schema.shared_fields"
          :key="field.name"
          :field="field"
          :model-value="formData[field.name]"
          :error="errors[field.name]"
          @update:model-value="formData[field.name] = $event"
        />
      </div>

      <!-- Action buttons -->
      <div class="actions-section">
        <h5>{{ t('taskDetail.selectAction') }}</h5>
        <div class="action-buttons">
          <Button
            v-for="action in workflowContext.form_schema.actions"
            :key="action.key"
            :label="action.label"
            :outlined="selectedAction?.key !== action.key"
            :severity="selectedAction?.key === action.key ? undefined : 'secondary'"
            @click="selectAction(action)"
          />
        </div>
      </div>

      <!-- Per-action fields -->
      <div v-if="selectedAction && selectedAction.form_fields.length > 0" class="action-fields">
        <DynamicFormField
          v-for="field in selectedAction.form_fields"
          :key="field.name"
          :field="field"
          :model-value="formData[field.name]"
          :error="errors[field.name]"
          @update:model-value="formData[field.name] = $event"
        />
      </div>

      <!-- Submit -->
      <div v-if="selectedAction" class="submit-section">
        <Button
          :label="t('taskDetail.submitAction', { action: selectedAction.label })"
          :loading="submitting"
          @click="handleSubmit"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.workflow-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.workflow-info .pi {
  color: var(--p-primary-color);
}

.process-name {
  font-weight: 600;
}

.shared-fields {
  margin-bottom: 1rem;
}

.shared-fields h5 {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.actions-section {
  margin-bottom: 1rem;
}

.actions-section h5 {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.action-fields {
  margin-top: 1rem;
  padding: 1rem;
  border: 1px solid var(--p-surface-border);
  border-radius: 6px;
  background: var(--p-surface-ground);
}

.submit-section {
  margin-top: 1rem;
}
</style>
