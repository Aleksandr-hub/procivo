<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { invitationApi } from '@/modules/organization/api/invitation.api'
import type { InvitationDTO } from '@/modules/organization/types/organization.types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { t } = useI18n()

const invitation = ref<InvitationDTO | null>(null)
const loadError = ref('')
const pageLoading = ref(true)

const firstName = ref('')
const lastName = ref('')
const password = ref('')
const submitted = ref(false)
const saving = ref(false)
const accepted = ref(false)

const token = computed(() => route.query.token as string)

const isExpired = computed(() => {
  if (!invitation.value) return false
  return new Date(invitation.value.expiresAt) < new Date()
})

const isUsable = computed(() => {
  return invitation.value?.status === 'pending' && !isExpired.value
})

onMounted(async () => {
  if (!token.value) {
    loadError.value = t('auth.invitation.invalidLink')
    pageLoading.value = false
    return
  }

  try {
    invitation.value = await invitationApi.getByToken(token.value)
  } catch {
    loadError.value = t('auth.invitation.notFoundOrUsed')
  } finally {
    pageLoading.value = false
  }
})

async function onSubmit() {
  submitted.value = true
  if (!firstName.value || !lastName.value || !password.value) return

  saving.value = true
  try {
    await invitationApi.accept(token.value, {
      first_name: firstName.value,
      last_name: lastName.value,
      password: password.value,
    })
    accepted.value = true
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || 'Failed to accept invitation',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="auth-layout">
    <div class="auth-card">
      <h1>{{ t('auth.invitation.title') }}</h1>

      <div v-if="pageLoading" class="loading-state">
        <ProgressSpinner style="width: 40px; height: 40px" />
        <p>{{ t('auth.invitation.loadingInvitation') }}</p>
      </div>

      <div v-else-if="loadError" class="error-state">
        <i class="pi pi-exclamation-circle" style="font-size: 2rem; color: var(--p-red-500)" />
        <p>{{ loadError }}</p>
        <router-link to="/login">{{ t('auth.invitation.goToLogin') }}</router-link>
      </div>

      <div v-else-if="!isUsable" class="error-state">
        <i class="pi pi-clock" style="font-size: 2rem; color: var(--p-orange-500)" />
        <p v-if="isExpired">{{ t('auth.invitation.invitationExpired') }}</p>
        <p v-else>{{ t('auth.invitation.invitationUnavailable') }} ({{ invitation?.status }})</p>
        <router-link to="/login">{{ t('auth.invitation.goToLogin') }}</router-link>
      </div>

      <div v-else-if="accepted" class="success-state">
        <i class="pi pi-check-circle" style="font-size: 2rem; color: var(--p-green-500)" />
        <p>{{ t('auth.invitation.successfulJoined') }}</p>
        <p class="subtitle">{{ t('auth.invitation.youCanNowSignIn') }}</p>
        <Button :label="t('auth.invitation.goToLogin')" @click="router.push('/login')" fluid />
      </div>

      <template v-else>
        <p class="subtitle">
          {{ t('auth.invitation.youveBeenInvited') }}
        </p>
        <div class="invite-info">
          <span><strong>{{ t('auth.invitation.inviteInfoEmail') }}</strong> {{ invitation?.email }}</span>
        </div>

        <form @submit.prevent="onSubmit" class="auth-form">
          <div class="field">
            <label for="firstName">{{ t('auth.invitation.firstName') }}</label>
            <InputText
              id="firstName"
              v-model="firstName"
              :placeholder="t('auth.invitation.firstNamePlaceholder')"
              :invalid="submitted && !firstName"
              fluid
            />
          </div>

          <div class="field">
            <label for="lastName">{{ t('auth.invitation.lastName') }}</label>
            <InputText
              id="lastName"
              v-model="lastName"
              :placeholder="t('auth.invitation.lastNamePlaceholder')"
              :invalid="submitted && !lastName"
              fluid
            />
          </div>

          <div class="field">
            <label for="password">{{ t('auth.invitation.password') }}</label>
            <Password
              id="password"
              v-model="password"
              :placeholder="t('auth.invitation.passwordPlaceholder')"
              toggleMask
              :invalid="submitted && !password"
              fluid
            />
            <small class="field-help">{{ t('auth.invitation.passwordHelp') }}</small>
          </div>

          <Button type="submit" :label="t('auth.invitation.acceptAndJoin')" icon="pi pi-check" :loading="saving" fluid />
        </form>
      </template>
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
  max-width: 440px;
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
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
}

.invite-info {
  background: var(--p-surface-50);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

:root.app-dark .invite-info {
  background: var(--p-surface-800);
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

.field-help {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}

.loading-state,
.error-state,
.success-state {
  text-align: center;
  padding: 2rem 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.error-state p,
.success-state p {
  margin: 0;
  color: var(--p-text-color);
}

.error-state a {
  color: var(--p-primary-color);
  font-size: 0.875rem;
}

.loading-state p {
  color: var(--p-text-muted-color);
}
</style>
