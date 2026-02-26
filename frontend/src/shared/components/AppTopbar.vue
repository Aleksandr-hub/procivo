<script setup lang="ts">
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useTheme } from '@/shared/composables/useTheme'
import { useLocale } from '@/shared/composables/useLocale'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import NotificationBell from '@/modules/notifications/components/NotificationBell.vue'

const emit = defineEmits<{
  toggleSidebar: []
}>()

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const { isDark, toggle: toggleTheme } = useTheme()
const { currentLocale, setLocale } = useLocale()

function toggleLocale() {
  setLocale(currentLocale.value === 'en' ? 'uk' : 'en')
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="topbar">
    <div class="topbar-left">
      <Button
        icon="pi pi-bars"
        text
        rounded
        @click="emit('toggleSidebar')"
      />
    </div>
    <div class="topbar-right">
      <span v-if="auth.user" class="user-name">
        {{ auth.user.firstName }} {{ auth.user.lastName }}
      </span>
      <NotificationBell />
      <Button
        :label="currentLocale.toUpperCase()"
        text
        rounded
        @click="toggleLocale"
        v-tooltip.bottom="currentLocale === 'en' ? 'Українська' : 'English'"
        class="locale-btn"
      />
      <Button
        :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
        text
        rounded
        @click="toggleTheme"
        v-tooltip.bottom="isDark ? t('topbar.lightMode') : t('topbar.darkMode')"
      />
      <Button
        icon="pi pi-sign-out"
        text
        rounded
        severity="secondary"
        @click="handleLogout"
        v-tooltip.bottom="t('topbar.logout')"
      />
    </div>
  </div>
</template>

<style scoped>
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  padding: 0 1.5rem;
  background: var(--p-surface-card);
  border-bottom: 1px solid var(--p-surface-border);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--p-text-color);
  margin-right: 0.25rem;
}

.locale-btn {
  font-weight: 600;
  font-size: 0.8rem;
  min-width: 2.5rem;
}
</style>
