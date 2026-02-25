<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/stores/auth.store'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const { t } = useI18n()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const submitted = ref(false)

async function onSubmit() {
  submitted.value = true

  if (!firstName.value || !lastName.value || !email.value || !password.value) return

  try {
    await auth.register({
      email: email.value,
      password: password.value,
      firstName: firstName.value,
      lastName: lastName.value,
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('auth.register.registrationSuccessful'),
      life: 8000,
    })
    router.push('/login')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string; message?: string } } }
    const message = axiosError.response?.data?.error || axiosError.response?.data?.message || t('auth.register.registrationFailed')
    toast.add({ severity: 'error', summary: t('common.error'), detail: message, life: 5000 })
  }
}
</script>

<template>
  <div class="auth-layout">
    <div class="auth-card">
      <h1>{{ t('auth.register.title') }}</h1>
      <p class="subtitle">{{ t('auth.register.subtitle') }}</p>

      <form @submit.prevent="onSubmit" class="auth-form">
        <div class="name-row">
          <div class="field">
            <label for="firstName">{{ t('auth.register.firstName') }}</label>
            <InputText
              id="firstName"
              v-model="firstName"
              :placeholder="t('auth.register.firstNamePlaceholder')"
              :invalid="submitted && !firstName"
              fluid
            />
          </div>
          <div class="field">
            <label for="lastName">{{ t('auth.register.lastName') }}</label>
            <InputText
              id="lastName"
              v-model="lastName"
              :placeholder="t('auth.register.lastNamePlaceholder')"
              :invalid="submitted && !lastName"
              fluid
            />
          </div>
        </div>

        <div class="field">
          <label for="email">{{ t('auth.register.email') }}</label>
          <InputText
            id="email"
            v-model="email"
            type="email"
            :placeholder="t('auth.register.emailPlaceholder')"
            :invalid="submitted && !email"
            fluid
          />
        </div>

        <div class="field">
          <label for="password">{{ t('auth.register.password') }}</label>
          <Password
            id="password"
            v-model="password"
            :placeholder="t('auth.register.passwordPlaceholder')"
            toggleMask
            :invalid="submitted && !password"
            fluid
          />
        </div>

        <Button
          type="submit"
          :label="t('auth.register.signUp')"
          :loading="auth.loading"
          fluid
        />
      </form>

      <p class="auth-link">
        {{ t('auth.register.alreadyHaveAccount') }}
        <router-link to="/login">{{ t('auth.register.signIn') }}</router-link>
      </p>
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
  max-width: 450px;
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

.name-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
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

.auth-link {
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}
</style>
