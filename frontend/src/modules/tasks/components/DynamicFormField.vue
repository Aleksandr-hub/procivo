<script setup lang="ts">
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

defineProps<{
  field: FormFieldDefinition
  modelValue: unknown
  error?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: unknown]
}>()

function onUpdate(value: unknown) {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="dynamic-field">
    <label>
      {{ field.label }}
      <span v-if="field.required" class="required-mark">*</span>
    </label>

    <InputText
      v-if="field.type === 'text'"
      :model-value="(modelValue as string) ?? ''"
      class="w-full"
      @update:model-value="onUpdate"
    />

    <InputNumber
      v-else-if="field.type === 'number'"
      :model-value="(modelValue as number) ?? null"
      class="w-full"
      @update:model-value="onUpdate"
    />

    <DatePicker
      v-else-if="field.type === 'date'"
      :model-value="(modelValue as Date) ?? null"
      class="w-full"
      date-format="yy-mm-dd"
      show-icon
      @update:model-value="onUpdate"
    />

    <Select
      v-else-if="field.type === 'select'"
      :model-value="(modelValue as string) ?? null"
      :options="field.options ?? []"
      class="w-full"
      @update:model-value="onUpdate"
    />

    <Checkbox
      v-else-if="field.type === 'checkbox'"
      :model-value="(modelValue as boolean) ?? false"
      binary
      @update:model-value="onUpdate"
    />

    <Textarea
      v-else-if="field.type === 'textarea'"
      :model-value="(modelValue as string) ?? ''"
      class="w-full"
      :rows="3"
      auto-resize
      @update:model-value="onUpdate"
    />

    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<style scoped>
.dynamic-field {
  margin-bottom: 1rem;
}

.dynamic-field label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.required-mark {
  color: var(--p-red-500);
}
</style>
