<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/stores/auth.store'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const { t } = useI18n()

const email = ref('')
const password = ref('')
const submitted = ref(false)

async function onSubmit() {
  submitted.value = true

  if (!email.value || !password.value) return

  try {
    const requires2FA = await auth.login(email.value, password.value)

    if (requires2FA) {
      router.push({ name: 'two-factor-verify', query: { redirect: route.query.redirect } })
      return
    }

    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    const message = axiosError.response?.data?.error || t('auth.login.loginFailed')
    toast.add({ severity: 'error', summary: t('common.error'), detail: message, life: 5000 })
  }
}
</script>

<template>
  <div class="auth-layout">
    <div class="auth-card">
      <h1>{{ t('auth.login.title') }}</h1>
      <p class="subtitle">{{ t('auth.login.subtitle') }}</p>

      <form @submit.prevent="onSubmit" class="auth-form">
        <div class="field">
          <label for="email">{{ t('auth.login.email') }}</label>
          <InputText
            id="email"
            v-model="email"
            type="email"
            :placeholder="t('auth.login.emailPlaceholder')"
            :invalid="submitted && !email"
            fluid
          />
          <small v-if="submitted && !email" class="p-error">{{ t('auth.login.emailRequired') }}</small>
        </div>

        <div class="field">
          <label for="password">{{ t('auth.login.password') }}</label>
          <Password
            id="password"
            v-model="password"
            :placeholder="t('auth.login.passwordPlaceholder')"
            :feedback="false"
            toggleMask
            :invalid="submitted && !password"
            fluid
          />
          <small v-if="submitted && !password" class="p-error">{{ t('auth.login.passwordRequired') }}</small>
        </div>

        <Button
          type="submit"
          :label="t('auth.login.signIn')"
          :loading="auth.loading"
          fluid
        />
      </form>

      <p class="auth-link">
        {{ t('auth.login.noAccount') }}
        <router-link to="/register">{{ t('auth.login.signUp') }}</router-link>
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
