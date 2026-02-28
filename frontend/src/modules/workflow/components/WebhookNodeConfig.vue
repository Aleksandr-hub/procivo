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

const url = ref('')
const method = ref('POST')
const bodyTemplate = ref('')
const headers = ref<{ key: string; value: string }[]>([])

const methodOptions = [
  { label: 'GET', value: 'GET' },
  { label: 'POST', value: 'POST' },
  { label: 'PUT', value: 'PUT' },
  { label: 'PATCH', value: 'PATCH' },
  { label: 'DELETE', value: 'DELETE' },
]

watch(() => props.config, (cfg) => {
  url.value = (cfg.url as string) || ''
  method.value = (cfg.method as string) || 'POST'
  bodyTemplate.value = (cfg.body_template as string) || ''
  headers.value = Array.isArray(cfg.headers) ? [...(cfg.headers as { key: string; value: string }[])] : []
}, { immediate: true })

function emitConfig() {
  emit('update', {
    ...props.config,
    url: url.value || undefined,
    method: method.value,
    body_template: bodyTemplate.value || undefined,
    headers: headers.value.filter((h) => h.key.trim() !== ''),
  })
}

function addHeader() {
  headers.value.push({ key: '', value: '' })
}

function removeHeader(index: number) {
  headers.value.splice(index, 1)
  emitConfig()
}
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.webhookConfig') }}</h5>

    <div class="config-field">
      <label>{{ t('workflow.webhookUrl') }} *</label>
      <InputText
        v-model="url"
        :disabled="readonly"
        placeholder="https://example.com/webhook"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.webhookMethod') }}</label>
      <Select
        v-model="method"
        :options="methodOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.webhookHeaders') }}</label>
      <div v-for="(header, i) in headers" :key="i" class="header-row">
        <InputText
          v-model="header.key"
          :disabled="readonly"
          :placeholder="t('workflow.webhookHeaderKey')"
          class="header-key"
          @update:model-value="emitConfig"
        />
        <InputText
          v-model="header.value"
          :disabled="readonly"
          :placeholder="t('workflow.webhookHeaderValue')"
          class="header-value"
          @update:model-value="emitConfig"
        />
        <Button
          v-if="!readonly"
          icon="pi pi-trash"
          text
          size="small"
          severity="danger"
          @click="removeHeader(i)"
        />
      </div>
      <Button
        v-if="!readonly"
        :label="t('workflow.webhookAddHeader')"
        icon="pi pi-plus"
        text
        size="small"
        @click="addHeader"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.webhookBody') }}</label>
      <Textarea
        v-model="bodyTemplate"
        :disabled="readonly"
        :placeholder="t('workflow.webhookBodyPlaceholder')"
        class="w-full"
        rows="4"
        @update:model-value="emitConfig"
      />
      <small class="hint">{{ t('workflow.webhookBodyHint') }}</small>
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

.header-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.header-key {
  flex: 1;
}

.header-value {
  flex: 2;
}

.hint {
  display: block;
  margin-top: 0.25rem;
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}
</style>
