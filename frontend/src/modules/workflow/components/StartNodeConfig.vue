<script setup lang="ts">
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

function onFieldsUpdate(fields: FormFieldDefinition[]) {
  emit('update', { ...props.config, formFields: fields })
}
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.startFormConfig') }}</h5>
    <p class="config-hint">{{ t('workflow.startFormFieldsHelp') }}</p>

    <FormFieldsBuilder
      :fields="(config.formFields as FormFieldDefinition[]) || []"
      :readonly="readonly"
      @update="onFieldsUpdate"
    />
  </div>
</template>

<style scoped>
.config-section h5 {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.config-hint {
  margin: 0 0 0.75rem;
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  line-height: 1.4;
}
</style>
