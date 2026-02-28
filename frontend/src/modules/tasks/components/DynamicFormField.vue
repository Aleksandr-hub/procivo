<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'

defineProps<{
  field: FormFieldDefinition
  modelValue: unknown
  error?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: unknown]
  'blur': []
}>()

const { t } = useI18n()
const empStore = useEmployeeStore()

const employeeOptions = computed(() =>
  empStore.employees
    .filter((e) => e.status === 'active')
    .map((e) => ({
      label: e.userFullName || e.employeeNumber,
      value: e.id,
    })),
)

function onUpdate(value: unknown) {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="dynamic-field">
    <label>
      {{ field.label }}
      <Tag v-if="field.required" :value="t('form.required')" severity="secondary" class="required-badge" />
    </label>

    <InputText
      v-if="field.type === 'text'"
      :model-value="(modelValue as string) ?? ''"
      class="w-full"
      @update:model-value="onUpdate"
      @blur="emit('blur')"
    />

    <InputNumber
      v-else-if="field.type === 'number'"
      :model-value="(modelValue as number) ?? null"
      class="w-full"
      @update:model-value="onUpdate"
      @blur="emit('blur')"
    />

    <DatePicker
      v-else-if="field.type === 'date'"
      :model-value="(modelValue as Date) ?? null"
      class="w-full"
      date-format="yy-mm-dd"
      show-icon
      @update:model-value="onUpdate"
      @blur="emit('blur')"
    />

    <Select
      v-else-if="field.type === 'select'"
      :model-value="(modelValue as string) ?? null"
      :options="field.options ?? []"
      class="w-full"
      @update:model-value="onUpdate"
      @blur="emit('blur')"
    />

    <Checkbox
      v-else-if="field.type === 'checkbox'"
      :model-value="(modelValue as boolean) ?? false"
      binary
      @update:model-value="onUpdate"
    />

    <Select
      v-else-if="field.type === 'employee'"
      :model-value="(modelValue as string) ?? null"
      :options="employeeOptions"
      option-label="label"
      option-value="value"
      filter
      :loading="empStore.loading"
      class="w-full"
      @update:model-value="onUpdate"
      @blur="emit('blur')"
    />

    <Textarea
      v-else-if="field.type === 'textarea'"
      :model-value="(modelValue as string) ?? ''"
      class="w-full"
      :rows="3"
      auto-resize
      @update:model-value="onUpdate"
      @blur="emit('blur')"
    />

    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<style scoped>
.dynamic-field {
  margin-bottom: 1rem;
}

.dynamic-field label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.required-badge {
  font-size: 0.65rem;
  padding: 0.1rem 0.4rem;
}
</style>
