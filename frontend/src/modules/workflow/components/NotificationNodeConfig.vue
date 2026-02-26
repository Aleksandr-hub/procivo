<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  config: Record<string, unknown>
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [config: Record<string, unknown>]
}>()

const { t } = useI18n()

const recipientType = ref('initiator')
const recipientValue = ref('')
const messageTemplate = ref('')

const recipientTypeOptions = [
  { label: t('workflow.notificationRecipientInitiator'), value: 'initiator' },
  { label: t('workflow.notificationRecipientSpecific'), value: 'specific' },
]

watch(() => props.config, (cfg) => {
  recipientType.value = (cfg.recipient_type as string) || 'initiator'
  recipientValue.value = (cfg.recipient_value as string) || ''
  messageTemplate.value = (cfg.template as string) || ''
}, { immediate: true })

function emitConfig() {
  emit('update', {
    ...props.config,
    recipient_type: recipientType.value,
    recipient_value: recipientType.value === 'specific' ? recipientValue.value : undefined,
    template: messageTemplate.value || undefined,
  })
}
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.notificationConfig') }}</h5>

    <div class="config-field">
      <label>{{ t('workflow.notificationRecipientType') }}</label>
      <Select
        v-model="recipientType"
        :options="recipientTypeOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div v-if="recipientType === 'specific'" class="config-field">
      <label>{{ t('workflow.notificationRecipientValue') }}</label>
      <InputText
        v-model="recipientValue"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.notificationMessageTemplate') }}</label>
      <Textarea
        v-model="messageTemplate"
        :disabled="readonly"
        :placeholder="t('workflow.notificationMessagePlaceholder')"
        class="w-full"
        rows="3"
        @update:model-value="emitConfig"
      />
    </div>
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
