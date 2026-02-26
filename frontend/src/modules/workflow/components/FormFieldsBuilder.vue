<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { FormFieldDefinition, FormFieldType } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  fields: FormFieldDefinition[]
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [fields: FormFieldDefinition[]]
}>()

const { t } = useI18n()

const fieldTypeOptions: { label: string; value: FormFieldType }[] = [
  { label: t('workflow.fieldTypeText'), value: 'text' },
  { label: t('workflow.fieldTypeNumber'), value: 'number' },
  { label: t('workflow.fieldTypeDate'), value: 'date' },
  { label: t('workflow.fieldTypeSelect'), value: 'select' },
  { label: t('workflow.fieldTypeCheckbox'), value: 'checkbox' },
  { label: t('workflow.fieldTypeTextarea'), value: 'textarea' },
]

function addField() {
  const updated = [...props.fields, {
    name: '',
    label: '',
    type: 'text' as FormFieldType,
    required: false,
  }]
  emit('update', updated)
}

function updateField(index: number, field: Partial<FormFieldDefinition>) {
  const updated = props.fields.map((f, i) => (i === index ? { ...f, ...field } : f))
  emit('update', updated)
}

function removeField(index: number) {
  const updated = props.fields.filter((_, i) => i !== index)
  emit('update', updated)
}

function getOptionsString(field: FormFieldDefinition): string {
  return field.options?.join(', ') || ''
}

function setOptionsFromString(index: number, value: string) {
  const options = value.split(',').map((s) => s.trim()).filter(Boolean)
  updateField(index, { options })
}
</script>

<template>
  <div class="form-fields-builder">
    <div class="builder-header">
      <h5>{{ t('workflow.formFields') }}</h5>
      <Button v-if="!readonly" :label="t('workflow.addField')" icon="pi pi-plus" text size="small" @click="addField" />
    </div>

    <small v-if="fields.length > 0" class="help-text">{{ t('workflow.formFieldsHelp') }}</small>

    <div v-else class="empty-fields">
      <span>{{ t('workflow.formFieldsHelp') }}</span>
    </div>

    <div v-for="(field, index) in fields" :key="index" class="field-card">
      <div class="field-row">
        <div class="field-input">
          <label>{{ t('workflow.fieldName') }}</label>
          <InputText
            :model-value="field.name"
            :disabled="readonly"
            :placeholder="t('workflow.fieldNamePlaceholder')"
            class="w-full"
            @update:model-value="updateField(index, { name: ($event as string) })"
          />
        </div>
        <div class="field-input">
          <label>{{ t('workflow.fieldLabel') }}</label>
          <InputText
            :model-value="field.label"
            :disabled="readonly"
            :placeholder="t('workflow.fieldLabelPlaceholder')"
            class="w-full"
            @update:model-value="updateField(index, { label: ($event as string) })"
          />
        </div>
      </div>

      <div class="field-row">
        <div class="field-input">
          <label>{{ t('workflow.fieldType') }}</label>
          <Select
            :model-value="field.type"
            :options="fieldTypeOptions"
            option-label="label"
            option-value="value"
            :disabled="readonly"
            class="w-full"
            @update:model-value="updateField(index, { type: ($event as FormFieldType) })"
          />
        </div>
        <div class="field-checkbox">
          <Checkbox
            :model-value="field.required"
            :disabled="readonly"
            binary
            @update:model-value="updateField(index, { required: ($event as boolean) })"
          />
          <label>{{ t('workflow.fieldRequired') }}</label>
        </div>
      </div>

      <div v-if="field.type === 'select'" class="field-row">
        <div class="field-input full-width">
          <label>{{ t('workflow.fieldOptions') }}</label>
          <InputText
            :model-value="getOptionsString(field)"
            :disabled="readonly"
            :placeholder="t('workflow.fieldOptionsPlaceholder')"
            class="w-full"
            @update:model-value="setOptionsFromString(index, ($event as string))"
          />
        </div>
      </div>

      <Button
        v-if="!readonly"
        :label="t('workflow.removeField')"
        icon="pi pi-trash"
        text
        size="small"
        severity="danger"
        class="remove-btn"
        @click="removeField(index)"
      />
    </div>
  </div>
</template>

<style scoped>
.form-fields-builder {
  margin-top: 0.25rem;
}

.builder-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.builder-header h5 {
  margin: 0;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.help-text {
  display: block;
  margin-bottom: 0.75rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.empty-fields {
  text-align: center;
  padding: 1rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
}

.field-card {
  border: 1px solid var(--p-surface-border);
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  background: var(--p-surface-ground);
}

.field-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.field-input {
  flex: 1;
}

.field-input.full-width {
  flex: 1 1 100%;
}

.field-input label {
  display: block;
  margin-bottom: 0.125rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.field-checkbox {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding-top: 1rem;
}

.field-checkbox label {
  font-size: 0.8125rem;
}

.remove-btn {
  margin-top: 0.25rem;
}
</style>
