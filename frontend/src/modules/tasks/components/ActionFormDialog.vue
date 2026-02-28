<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DynamicFormField from '@/modules/tasks/components/DynamicFormField.vue'
import { buildZodSchema, flattenZodErrors } from '@/shared/utils/zod-schema-builder'
import type { StatusAction } from '@/modules/tasks/types/task.types'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  visible: boolean
  action: StatusAction | null
  sharedFields?: FormFieldDefinition[]
}>()

const emit = defineEmits<{
  hide: []
  submit: [data: { actionKey: string; formData: Record<string, unknown> }]
}>()

const { t } = useI18n()
const formData = ref<Record<string, unknown>>({})
const errors = ref<Record<string, string>>({})
const comment = ref('')
const hasSubmitted = ref(false)

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

watch(
  () => props.visible,
  (val) => {
    if (val && props.action) {
      errors.value = {}
      comment.value = ''
      hasSubmitted.value = false
      const data: Record<string, unknown> = {}
      for (const field of props.sharedFields ?? []) {
        data[field.name] = getDefaultValue(field)
      }
      for (const field of props.action.formFields) {
        data[field.name] = getDefaultValue(field)
      }
      formData.value = data
    }
  },
)

function allFields(): FormFieldDefinition[] {
  return [...(props.sharedFields ?? []), ...(props.action?.formFields ?? [])]
}

function validate(): boolean {
  const fields = allFields()
  const schema = buildZodSchema(fields)
  const result = schema.safeParse(formData.value)
  if (!result.success) {
    errors.value = flattenZodErrors(result.error)
    return false
  }
  errors.value = {}
  return true
}

function onFieldBlur() {
  if (hasSubmitted.value) {
    validate()
  }
}

function actionSeverity(actionKey: string): 'success' | 'danger' | 'secondary' {
  const key = actionKey.toLowerCase()
  if (key.includes('approve') || key.includes('accept') || key.includes('confirm')) {
    return 'success'
  }
  if (key.includes('reject') || key.includes('decline') || key.includes('cancel')) {
    return 'danger'
  }
  return 'secondary'
}

function handleSubmit() {
  if (!props.action) return

  hasSubmitted.value = true
  if (!validate()) return

  const serialized: Record<string, unknown> = {}
  for (const [key, value] of Object.entries(formData.value)) {
    serialized[key] = value instanceof Date ? value.toISOString().split('T')[0] : value
  }

  if (comment.value.trim()) {
    serialized._comment = comment.value.trim()
  }

  emit('submit', { actionKey: props.action.key, formData: serialized })
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="action?.label ?? ''"
    modal
    :style="{ width: '500px' }"
    @update:visible="!$event && emit('hide')"
  >
    <p class="action-subtitle">{{ t('taskDetail.fillFormForAction') }}</p>

    <div class="action-form">
      <!-- Shared fields -->
      <template v-if="sharedFields && sharedFields.length > 0">
        <DynamicFormField
          v-for="field in sharedFields"
          :key="field.name"
          :field="field"
          :model-value="formData[field.name]"
          :error="errors[field.name]"
          @update:model-value="formData[field.name] = $event"
          @blur="onFieldBlur"
        />
      </template>

      <!-- Action-specific fields -->
      <template v-if="action && action.formFields.length > 0">
        <DynamicFormField
          v-for="field in action.formFields"
          :key="field.name"
          :field="field"
          :model-value="formData[field.name]"
          :error="errors[field.name]"
          @update:model-value="formData[field.name] = $event"
          @blur="onFieldBlur"
        />
      </template>

      <!-- Comment -->
      <div class="comment-section">
        <label>{{ t('taskDetail.comment') }}</label>
        <Textarea
          v-model="comment"
          :placeholder="t('taskDetail.commentPlaceholder')"
          class="w-full"
          :rows="3"
          auto-resize
        />
      </div>
    </div>

    <template #footer>
      <Button :label="t('common.cancel')" text @click="emit('hide')" />
      <Button
        :label="action?.label ?? t('common.submit')"
        :severity="action ? actionSeverity(action.key) : undefined"
        @click="handleSubmit"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.action-subtitle {
  margin: 0 0 1rem;
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.action-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.comment-section {
  margin-top: 0.5rem;
}

.comment-section label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}
</style>
