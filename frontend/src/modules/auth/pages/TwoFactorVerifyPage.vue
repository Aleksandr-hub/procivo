<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import InputOtp from 'primevue/inputotp'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const { t } = useI18n()

const code = ref('')
const rememberDevice = ref(false)
const useBackupCode = ref(false)
const backupCode = ref('')
const submitting = ref(false)

onMounted(() => {
  if (!auth.partialToken) {
    router.replace({ name: 'login' })
  }
})

async function onSubmit() {
  const codeValue = useBackupCode.value ? backupCode.value.trim() : code.value
  if (!codeValue) return

  submitting.value = true
  try {
    await auth.verifyTwoFactor(codeValue, rememberDevice.value)
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (error: unknown) {
    const axiosError = error as { response?: { status?: number; data?: { error?: string } } }

    if (axiosError.response?.status === 429) {
      toast.add({
        severity: 'error',
        summary: t('common.error'),
        detail: t('auth.twoFactor.rateLimited'),
        life: 5000,
      })
      setTimeout(() => {
        auth.partialToken = null
        router.replace({ name: 'login' })
      }, 3000)
      return
    }

    const message = axiosError.response?.data?.error || t('auth.twoFactor.invalidCode')
    toast.add({ severity: 'error', summary: t('common.error'), detail: message, life: 5000 })
  } finally {
    submitting.value = false
  }
}

function onOtpComplete(value: string) {
  code.value = value
  onSubmit()
}

function toggleBackupCode() {
  useBackupCode.value = !useBackupCode.value
  code.value = ''
  backupCode.value = ''
}
</script>

<template>
  <div class="auth-layout">
    <div class="auth-card">
      <h1>{{ t('auth.twoFactor.title') }}</h1>
      <p class="subtitle">{{ t('auth.twoFactor.description') }}</p>

      <form @submit.prevent="onSubmit" class="auth-form">
        <!-- OTP Input -->
        <div v-if="!useBackupCode" class="otp-section">
          <InputOtp
            v-model="code"
            :length="6"
            integerOnly
            @complete="onOtpComplete"
            class="otp-input"
          />
        </div>

        <!-- Backup Code Input -->
        <div v-else class="field">
          <label for="backupCode">{{ t('auth.twoFactor.backupCodePlaceholder') }}</label>
          <InputText
            id="backupCode"
            v-model="backupCode"
            :placeholder="t('auth.twoFactor.backupCodePlaceholder')"
            fluid
          />
        </div>

        <!-- Remember Device -->
        <div class="remember-device">
          <Checkbox
            v-model="rememberDevice"
            :binary="true"
            inputId="rememberDevice"
          />
          <label for="rememberDevice" class="remember-label">
            {{ t('auth.twoFactor.rememberDevice') }}
          </label>
        </div>

        <Button
          type="submit"
          :label="t('auth.twoFactor.verify')"
          :loading="submitting"
          :disabled="useBackupCode ? !backupCode.trim() : code.length < 6"
          fluid
        />

        <div class="backup-toggle">
          <a href="#" @click.prevent="toggleBackupCode">
            {{ useBackupCode ? t('auth.twoFactor.useAuthenticator') : t('auth.twoFactor.useBackupCode') }}
          </a>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: var(--p-surface-ground);
}

.auth-card {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem;
  background: var(--p-surface-card);
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

:root.app-dark .auth-card {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.32);
}

.auth-card h1 {
  text-align: center;
  margin-bottom: 0.25rem;
  color: var(--p-text-color);
  font-size: 1.75rem;
  letter-spacing: -0.025em;
}

.subtitle {
  text-align: center;
  color: var(--p-text-muted-color);
  margin-bottom: 2rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.otp-section {
  display: flex;
  justify-content: center;
}

.otp-input {
  gap: 0.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field label {
  font-weight: 600;
  font-size: 0.875rem;
}

.remember-device {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.remember-label {
  font-size: 0.875rem;
  color: var(--p-text-color);
  cursor: pointer;
}

.backup-toggle {
  text-align: center;
  font-size: 0.875rem;
}

.backup-toggle a {
  color: var(--p-primary-color);
  text-decoration: none;
}

.backup-toggle a:hover {
  text-decoration: underline;
}
</style>
