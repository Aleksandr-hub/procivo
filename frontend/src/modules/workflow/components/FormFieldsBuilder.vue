<script setup lang="ts">
import { ref } from 'vue'
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

// Track which names were manually edited (not auto-generated)
const manualNames = ref<Set<number>>(new Set())

const fieldTypeOptions: { label: string; value: FormFieldType }[] = [
  { label: t('workflow.fieldTypeText'), value: 'text' },
  { label: t('workflow.fieldTypeNumber'), value: 'number' },
  { label: t('workflow.fieldTypeDate'), value: 'date' },
  { label: t('workflow.fieldTypeSelect'), value: 'select' },
  { label: t('workflow.fieldTypeCheckbox'), value: 'checkbox' },
  { label: t('workflow.fieldTypeTextarea'), value: 'textarea' },
  { label: t('workflow.fieldTypeEmployee'), value: 'employee' },
]

const cyrMap: Record<string, string> = {
  а: 'a', б: 'b', в: 'v', г: 'h', ґ: 'g', д: 'd', е: 'e', є: 'ye',
  ж: 'zh', з: 'z', и: 'y', і: 'i', ї: 'yi', й: 'y', к: 'k', л: 'l',
  м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't', у: 'u',
  ф: 'f', х: 'kh', ц: 'ts', ч: 'ch', ш: 'sh', щ: 'shch', ь: '',
  ю: 'yu', я: 'ya', ё: 'yo', ъ: '', э: 'e', ы: 'y',
}

function transliterate(text: string): string {
  return text
    .toLowerCase()
    .split('')
    .map((ch) => cyrMap[ch] ?? ch)
    .join('')
}

function toFieldName(text: string): string {
  const transliterated = transliterate(text)
  return transliterated
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/_{2,}/g, '_')
    .replace(/^_|_$/g, '')
}

function sanitizeFieldName(value: string): string {
  return value.toLowerCase().replace(/[^a-z0-9_]/g, '').replace(/_{2,}/g, '_').replace(/^_|_$/g, '')
}

function addField() {
  const updated = [...props.fields, {
    name: '',
    label: '',
    type: 'text' as FormFieldType,
    required: false,
  }]
  emit('update', updated)
}

function handleFieldNameInput(index: number, event: Event) {
  const input = event.target as HTMLInputElement
  const sanitized = sanitizeFieldName(input.value)
  if (input.value !== sanitized) {
    const pos = input.selectionStart ?? sanitized.length
    input.value = sanitized
    const newPos = Math.min(pos, sanitized.length)
    input.setSelectionRange(newPos, newPos)
  }
  manualNames.value.add(index)
  const updated = props.fields.map((f, i) => (i === index ? { ...f, name: sanitized } : f))
  emit('update', updated)
}

function handleLabelInput(index: number, label: string) {
  const field = props.fields[index]
  if (!field) return
  const autoName = manualNames.value.has(index) ? field.name : toFieldName(label)
  const updated = props.fields.map((f, i) => (i === index ? { ...f, label, name: autoName } : f))
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
          <label>{{ t('workflow.fieldLabel') }}</label>
          <InputText
            :model-value="field.label"
            :disabled="readonly"
            :placeholder="t('workflow.fieldLabelPlaceholder')"
            class="w-full"
            @update:model-value="handleLabelInput(index, ($event as string))"
          />
        </div>
        <div class="field-input">
          <label class="label-with-hint">
            {{ t('workflow.fieldName') }}
            <i v-tooltip.top="t('workflow.fieldNameHint')" class="pi pi-question-circle hint-icon" />
          </label>
          <InputText
            :model-value="field.name"
            :disabled="readonly"
            :placeholder="t('workflow.fieldNamePlaceholder')"
            :class="['w-full', { 'field-name-auto': !manualNames.has(index) }]"
            :invalid="field.label.length > 0 && field.name.length === 0"
            @input="handleFieldNameInput(index, $event)"
          />
          <small v-if="field.label.length > 0 && field.name.length === 0" class="field-error">
            {{ t('workflow.fieldNameRequired') }}
          </small>
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

.label-with-hint {
  display: flex !important;
  align-items: center;
  gap: 0.25rem;
}

.hint-icon {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  cursor: help;
}

.field-name-auto :deep(input) {
  color: var(--p-text-muted-color);
}

.field-error {
  display: block;
  margin-top: 0.125rem;
  font-size: 0.7rem;
  color: var(--p-red-500);
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
