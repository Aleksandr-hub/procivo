<script setup lang="ts">
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'

const visible = defineModel<boolean>('visible', { required: true })

const props = defineProps<{
  codes: string[]
}>()

const { t } = useI18n()
const toast = useToast()

async function copyAll() {
  try {
    const text = props.codes.join('\n')
    await navigator.clipboard.writeText(text)
    toast.add({
      severity: 'success',
      summary: t('auth.twoFactor.backupCodes.copied'),
      life: 2000,
    })
  } catch {
    // Clipboard API not available
  }
}

function download() {
  const text = `Procivo Backup Codes\n${'='.repeat(30)}\n\n${props.codes.join('\n')}\n\nEach code can only be used once.`
  const blob = new Blob([text], { type: 'text/plain' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'procivo-backup-codes.txt'
  a.click()
  URL.revokeObjectURL(url)
}

function onSaved() {
  visible.value = false
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    :header="t('auth.twoFactor.backupCodes.title')"
    modal
    :closable="false"
    :style="{ width: '450px' }"
  >
    <Message severity="warn" :closable="false" class="backup-warning">
      {{ t('auth.twoFactor.backupCodes.warning') }}
    </Message>

    <div class="codes-grid">
      <div v-for="code in codes" :key="code" class="code-item">
        {{ code }}
      </div>
    </div>

    <div class="backup-actions">
      <Button
        :label="t('auth.twoFactor.backupCodes.copyAll')"
        icon="pi pi-copy"
        text
        @click="copyAll"
      />
      <Button
        :label="t('auth.twoFactor.backupCodes.download')"
        icon="pi pi-download"
        text
        @click="download"
      />
    </div>

    <Divider />

    <div class="saved-action">
      <Button
        :label="t('auth.twoFactor.backupCodes.saved')"
        @click="onSaved"
        fluid
      />
    </div>
  </Dialog>
</template>

<style scoped>
.backup-warning {
  margin-bottom: 1rem;
}

.codes-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  padding: 1rem;
  background: var(--p-surface-ground);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.code-item {
  font-family: monospace;
  font-size: 0.95rem;
  padding: 0.5rem 0.75rem;
  text-align: center;
  color: var(--p-text-color);
  background: var(--p-surface-card);
  border-radius: 4px;
  letter-spacing: 0.05em;
}

.backup-actions {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.saved-action {
  display: flex;
  justify-content: center;
}
</style>
