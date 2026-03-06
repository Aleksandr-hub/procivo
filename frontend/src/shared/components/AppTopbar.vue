<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useTheme } from '@/shared/composables/useTheme'
import { useLocale } from '@/shared/composables/useLocale'
import NotificationBell from '@/modules/notifications/components/NotificationBell.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { isDark, toggle: toggleTheme } = useTheme()
const { currentLocale, setLocale } = useLocale()

const routeToI18nKey: Record<string, string> = {
  dashboard: 'nav.dashboard',
  organizations: 'nav.organizations',
  departments: 'nav.departments',
  employees: 'nav.employees',
  tasks: 'nav.tasks',
  boards: 'nav.boards',
  labels: 'nav.labels',
  'org-chart': 'nav.orgChart',
  roles: 'nav.roles',
  'process-definitions': 'nav.processes',
  'process-instances': 'nav.instances',
  permissions: 'nav.permissions',
  notifications: 'notifications.sidebar',
  profile: 'auth.profile.title',
  'help-center': 'help.title',
  'help-article': 'help.title',
}

const pageTitle = computed(() => {
  const name = route.name as string | undefined
  if (!name) return ''
  const key = routeToI18nKey[name]
  return key ? t(key) : ''
})

const initials = computed(() =>
  auth.user ? (auth.user.firstName[0] + auth.user.lastName[0]).toUpperCase() : '',
)

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
      <h1 v-if="pageTitle" class="page-title">{{ pageTitle }}</h1>
    </div>
    <div class="topbar-right">
      <div v-if="auth.user" class="user-info" @click="router.push('/profile')" style="cursor: pointer;">
        <Avatar
          :image="auth.user.avatarUrl ?? undefined"
          :label="auth.user.avatarUrl ? undefined : initials"
          shape="circle"
          size="small"
        />
        <span class="user-name">{{ auth.user.firstName }} {{ auth.user.lastName }}</span>
      </div>
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
  height: var(--topbar-height);
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
  gap: 0.25rem;
}

.page-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--p-text-color);
  margin: 0;
}

.user-info {
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
