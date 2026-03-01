<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useNotificationStore } from '@/modules/notifications/stores/notification.store'
import type { NotificationPreferences } from '@/modules/notifications/types/notification.types'

const { t } = useI18n()
const toast = useToast()
const auth = useAuthStore()
const notificationStore = useNotificationStore()

// Avatar section
const avatarLoading = ref(false)
const deleteAvatarLoading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const initials = computed(() => {
  if (!auth.user) return ''
  const f = auth.user.firstName?.[0] ?? ''
  const l = auth.user.lastName?.[0] ?? ''
  return (f + l).toUpperCase() || '?'
})

function triggerAvatarUpload() {
  fileInput.value?.click()
}

async function onFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  avatarLoading.value = true
  try {
    await auth.uploadAvatar(file)
    toast.add({ severity: 'success', summary: t('profile.avatarUploaded'), life: 3000 })
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : t('common.error')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
  } finally {
    avatarLoading.value = false
    // Reset file input so the same file can be re-uploaded
    if (input) input.value = ''
  }
}

async function onDeleteAvatar() {
  deleteAvatarLoading.value = true
  try {
    await auth.deleteAvatar()
    toast.add({ severity: 'success', summary: t('profile.avatarDeleted'), life: 3000 })
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : t('common.error')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
  } finally {
    deleteAvatarLoading.value = false
  }
}

// Profile form section
const form = ref({ firstName: '', lastName: '', email: '' })
const profileLoading = ref(false)

// Notification preferences section
const preferenceEventTypes = [
  'task_assigned',
  'task_completed',
  'comment_added',
  'process_started',
  'process_completed',
  'process_cancelled',
  'invitation_received',
] as const

const localPreferences = ref<NotificationPreferences>({})
const preferencesSaving = ref(false)

onMounted(async () => {
  if (auth.user) {
    form.value = {
      firstName: auth.user.firstName,
      lastName: auth.user.lastName,
      email: auth.user.email,
    }
  }
  await notificationStore.fetchPreferences()
  localPreferences.value = JSON.parse(JSON.stringify(notificationStore.preferences))
})

async function saveNotificationPreferences() {
  preferencesSaving.value = true
  try {
    await notificationStore.savePreferences(localPreferences.value)
    toast.add({ severity: 'success', summary: t('notifications.preferences.saved'), life: 3000 })
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : t('common.error')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
  } finally {
    preferencesSaving.value = false
  }
}

function getPreference(eventType: string, channel: 'in_app' | 'email'): boolean {
  return localPreferences.value[eventType]?.[channel] ?? (channel === 'in_app')
}

function setPreference(eventType: string, channel: 'in_app' | 'email', value: boolean) {
  if (!localPreferences.value[eventType]) {
    localPreferences.value[eventType] = { in_app: true, email: false }
  }
  localPreferences.value[eventType][channel] = value
}

async function saveProfile() {
  profileLoading.value = true
  try {
    await auth.updateProfile(form.value)
    toast.add({ severity: 'success', summary: t('profile.saved'), life: 3000 })
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : t('common.error')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
  } finally {
    profileLoading.value = false
  }
}

// Password change section
const currentPassword = ref('')
const newPassword = ref('')
const passwordLoading = ref(false)

async function changePassword() {
  if (!currentPassword.value || !newPassword.value) return

  passwordLoading.value = true
  try {
    await auth.changePassword(currentPassword.value, newPassword.value)
    toast.add({ severity: 'success', summary: t('profile.passwordChanged'), life: 3000 })
    currentPassword.value = ''
    newPassword.value = ''
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : t('common.error')
    toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 4000 })
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="profile-page">
    <h1 class="page-title">{{ t('profile.title') }}</h1>

    <!-- Avatar Section -->
    <Card class="profile-card">
      <template #title>{{ t('profile.avatar') }}</template>
      <template #content>
        <div class="avatar-section">
          <Avatar
            :image="auth.user?.avatarUrl ?? undefined"
            :label="auth.user?.avatarUrl ? undefined : initials"
            shape="circle"
            class="profile-avatar"
          />
          <div class="avatar-actions">
            <Button
              :label="t('profile.changeAvatar')"
              icon="pi pi-upload"
              :loading="avatarLoading"
              @click="triggerAvatarUpload"
            />
            <Button
              v-if="auth.user?.avatarUrl"
              :label="t('profile.deleteAvatar')"
              icon="pi pi-trash"
              severity="danger"
              text
              :loading="deleteAvatarLoading"
              @click="onDeleteAvatar"
            />
          </div>
          <input
            ref="fileInput"
            type="file"
            accept="image/*"
            style="display: none"
            @change="onFileSelected"
          />
        </div>
      </template>
    </Card>

    <!-- Personal Info Section -->
    <Card class="profile-card">
      <template #title>{{ t('profile.personalInfo') }}</template>
      <template #content>
        <div class="form-grid">
          <div class="field">
            <label>{{ t('profile.firstName') }}</label>
            <InputText v-model="form.firstName" class="w-full" />
          </div>
          <div class="field">
            <label>{{ t('profile.lastName') }}</label>
            <InputText v-model="form.lastName" class="w-full" />
          </div>
          <div class="field field-full">
            <label>{{ t('profile.email') }}</label>
            <InputText v-model="form.email" type="email" class="w-full" />
          </div>
        </div>
        <div class="form-actions">
          <Button
            :label="t('profile.save')"
            :loading="profileLoading"
            @click="saveProfile"
          />
        </div>
      </template>
    </Card>

    <!-- Password Change Section -->
    <Card class="profile-card">
      <template #title>{{ t('profile.changePassword') }}</template>
      <template #content>
        <div class="form-grid">
          <div class="field field-full">
            <label>{{ t('profile.currentPassword') }}</label>
            <Password
              v-model="currentPassword"
              :feedback="false"
              toggleMask
              class="w-full"
              inputClass="w-full"
            />
          </div>
          <div class="field field-full">
            <label>{{ t('profile.newPassword') }}</label>
            <Password
              v-model="newPassword"
              toggleMask
              class="w-full"
              inputClass="w-full"
            />
          </div>
        </div>
        <div class="form-actions">
          <Button
            :label="t('profile.changePassword')"
            :loading="passwordLoading"
            :disabled="!currentPassword || !newPassword"
            @click="changePassword"
          />
        </div>
      </template>
    </Card>

    <!-- Notification Preferences Section -->
    <Card class="profile-card">
      <template #title>{{ t('notifications.preferences.title') }}</template>
      <template #content>
        <ProgressBar v-if="notificationStore.preferencesLoading" mode="indeterminate" style="height: 3px; margin-bottom: 1rem" />
        <table v-else class="preferences-table">
          <thead>
            <tr>
              <th class="pref-event-col">Event Type</th>
              <th class="pref-channel-col">{{ t('notifications.preferences.inApp') }}</th>
              <th class="pref-channel-col">{{ t('notifications.preferences.email') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="eventType in preferenceEventTypes" :key="eventType">
              <td class="pref-event-label">{{ t(`notifications.preferences.types.${eventType}`) }}</td>
              <td class="pref-channel-cell">
                <ToggleSwitch
                  :modelValue="getPreference(eventType, 'in_app')"
                  @update:modelValue="setPreference(eventType, 'in_app', $event)"
                />
              </td>
              <td class="pref-channel-cell">
                <ToggleSwitch
                  :modelValue="getPreference(eventType, 'email')"
                  @update:modelValue="setPreference(eventType, 'email', $event)"
                />
              </td>
            </tr>
          </tbody>
        </table>
        <div class="form-actions">
          <Button
            :label="t('notifications.preferences.save')"
            :loading="preferencesSaving"
            :disabled="notificationStore.preferencesLoading"
            @click="saveNotificationPreferences"
          />
        </div>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.profile-page {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: var(--p-text-color);
}

.profile-card {
  width: 100%;
}

.avatar-section {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.avatar-actions {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.profile-avatar {
  width: 80px;
  height: 80px;
  font-size: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.field label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--p-text-color);
}

.field-full {
  grid-column: 1 / -1;
}

.w-full {
  width: 100%;
}

.form-actions {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
}

.preferences-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 0.5rem;
}

.preferences-table th {
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid var(--p-surface-border);
}

.preferences-table tr:not(:last-child) td {
  border-bottom: 1px solid var(--p-surface-border);
}

.pref-event-col {
  width: 60%;
}

.pref-channel-col {
  width: 20%;
  text-align: center;
}

.pref-event-label {
  font-size: 0.875rem;
  color: var(--p-text-color);
  padding: 0.75rem;
}

.pref-channel-cell {
  text-align: center;
  padding: 0.75rem;
}
</style>
