<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/modules/auth/stores/auth.store'

const { t } = useI18n()
const toast = useToast()
const auth = useAuthStore()

// Avatar section
const avatarLoading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const initials = computed(() => {
  if (!auth.user) return ''
  return (auth.user.firstName[0] + auth.user.lastName[0]).toUpperCase()
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

// Profile form section
const form = ref({ firstName: '', lastName: '', email: '' })
const profileLoading = ref(false)

onMounted(() => {
  if (auth.user) {
    form.value = {
      firstName: auth.user.firstName,
      lastName: auth.user.lastName,
      email: auth.user.email,
    }
  }
})

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
          <Button
            :label="t('profile.changeAvatar')"
            icon="pi pi-upload"
            :loading="avatarLoading"
            @click="triggerAvatarUpload"
          />
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
</style>
