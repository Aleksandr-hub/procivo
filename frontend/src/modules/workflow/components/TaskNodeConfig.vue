<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'
import FormFieldsBuilder from '@/modules/workflow/components/FormFieldsBuilder.vue'

const props = defineProps<{
  config: Record<string, unknown>
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [config: Record<string, unknown>]
}>()

const { t } = useI18n()

const taskTitle = ref('')
const taskDescription = ref('')
const assigneeType = ref('none')
const assigneeValue = ref('')
const priority = ref('medium')

const assigneeTypeOptions = [
  { label: t('workflow.assigneeTypeNone'), value: 'none' },
  { label: t('workflow.assigneeTypeSpecific'), value: 'specific' },
  { label: t('workflow.assigneeTypeRole'), value: 'role' },
]

const priorityOptions = [
  { label: t('tasks.priorityLow'), value: 'low' },
  { label: t('tasks.priorityMedium'), value: 'medium' },
  { label: t('tasks.priorityHigh'), value: 'high' },
  { label: t('tasks.priorityCritical'), value: 'critical' },
]

watch(() => props.config, (cfg) => {
  taskTitle.value = (cfg.task_title_template as string) || ''
  taskDescription.value = (cfg.task_description_template as string) || ''
  assigneeType.value = (cfg.assignee_type as string) || 'none'
  assigneeValue.value = (cfg.assignee_value as string) || ''
  priority.value = (cfg.priority as string) || 'medium'
}, { immediate: true })

function buildConfig(extraFields?: Record<string, unknown>): Record<string, unknown> {
  return {
    ...props.config,
    task_title_template: taskTitle.value || undefined,
    task_description_template: taskDescription.value || undefined,
    assignee_type: assigneeType.value,
    assignee_value: assigneeType.value !== 'none' ? assigneeValue.value : undefined,
    priority: priority.value,
    ...extraFields,
  }
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
      <label>{{ t('workflow.assigneeType') }}</label>
      <Select
        v-model="assigneeType"
        :options="assigneeTypeOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div v-if="assigneeType !== 'none'" class="config-field">
      <label>{{ t('workflow.assigneeValue') }}</label>
      <InputText
        v-model="assigneeValue"
        :disabled="readonly"
        :placeholder="t('workflow.assigneeValuePlaceholder')"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.taskPriority') }}</label>
      <Select
        v-model="priority"
        :options="priorityOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
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
</style>
