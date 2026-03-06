<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { userApi } from '@/modules/auth/api/user.api'
import InputOtp from 'primevue/inputotp'

const visible = defineModel<boolean>('visible', { required: true })
const emit = defineEmits<{
  confirmed: [backupCodes: string[]]
}>()

const { t } = useI18n()
const toast = useToast()

const step = ref<1 | 2>(1)
const qrCodeSvg = ref('')
const secret = ref('')
const backupCodes = ref<string[]>([])
const confirmCode = ref('')
const loading = ref(false)
const setupLoading = ref(false)

async function startSetup() {
  setupLoading.value = true
  try {
    const data = await userApi.setupTwoFactor()
    qrCodeSvg.value = data.qr_code_svg
    secret.value = data.secret
    backupCodes.value = data.backup_codes
    step.value = 1
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : t('common.error')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
    visible.value = false
  } finally {
    setupLoading.value = false
  }
}

async function confirmSetup() {
  if (!confirmCode.value || confirmCode.value.length < 6) return

  loading.value = true
  try {
    await userApi.confirmTwoFactor(confirmCode.value)
    emit('confirmed', backupCodes.value)
    visible.value = false
    resetState()
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { error?: string } } }
    const msg = axiosError.response?.data?.error || t('auth.twoFactor.invalidCode')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
  } finally {
    loading.value = false
  }
}

function onOtpComplete(value: string) {
  confirmCode.value = value
  confirmSetup()
}

async function copySecret() {
  try {
    await navigator.clipboard.writeText(secret.value)
    toast.add({
      severity: 'success',
      summary: t('auth.twoFactor.setup.secretCopied'),
      life: 2000,
    })
  } catch {
    // Clipboard API not available
  }
}

function resetState() {
  step.value = 1
  qrCodeSvg.value = ''
  secret.value = ''
  backupCodes.value = []
  confirmCode.value = ''
}

function onShow() {
  resetState()
  startSetup()
}

function onHide() {
  resetState()
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    :header="t('auth.twoFactor.setup.title')"
    modal
    :closable="!loading"
    :style="{ width: '500px' }"
    @show="onShow"
    @hide="onHide"
  >
    <!-- Loading state -->
    <div v-if="setupLoading" class="setup-loading">
      <ProgressSpinner style="width: 50px; height: 50px" />
    </div>

    <!-- Step 1: QR Code -->
    <div v-else-if="step === 1" class="setup-step">
      <p class="step-description">{{ t('auth.twoFactor.setup.scanQr') }}</p>

      <div class="qr-container" v-html="qrCodeSvg" />

      <Divider />

      <p class="manual-entry-label">{{ t('auth.twoFactor.setup.manualEntry') }}</p>
      <div class="secret-row">
        <InputText :modelValue="secret" readonly class="secret-input" />
        <Button
          :label="t('auth.twoFactor.setup.copySecret')"
          icon="pi pi-copy"
          text
          size="small"
          @click="copySecret"
        />
      </div>

      <div class="step-actions">
        <Button
          :label="t('auth.twoFactor.setup.next')"
          @click="step = 2"
        />
      </div>
    </div>

    <!-- Step 2: Confirm -->
    <div v-else class="setup-step">
      <p class="step-description">{{ t('auth.twoFactor.setup.confirmDescription') }}</p>

      <div class="otp-section">
        <InputOtp
          v-model="confirmCode"
          :length="6"
          integerOnly
          @complete="onOtpComplete"
          class="otp-input"
        />
      </div>

      <div class="step-actions">
        <Button
          :label="t('common.cancel')"
          text
          @click="step = 1"
        />
        <Button
          :label="t('auth.twoFactor.setup.confirmCode')"
          :loading="loading"
          :disabled="confirmCode.length < 6"
          @click="confirmSetup"
        />
      </div>
    </div>
  </Dialog>
</template>

<style scoped>
.setup-loading {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.setup-step {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.step-description {
  text-align: center;
  color: var(--p-text-muted-color);
  margin: 0;
}

.qr-container {
  display: flex;
  justify-content: center;
  padding: 1rem;
}

.qr-container :deep(svg) {
  width: 200px;
  height: 200px;
}

.manual-entry-label {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  margin: 0;
}

.secret-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.secret-input {
  flex: 1;
  font-family: monospace;
  font-size: 0.8rem;
}

.otp-section {
  display: flex;
  justify-content: center;
  padding: 1rem 0;
}

.otp-input {
  gap: 0.5rem;
}

.step-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
</style>
