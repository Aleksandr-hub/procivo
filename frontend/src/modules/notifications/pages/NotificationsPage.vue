<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/modules/notifications/stores/notification.store'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'

const { t } = useI18n()
const router = useRouter()
const store = useNotificationStore()
const orgStore = useOrganizationStore()

const typeFilter = ref<string | undefined>(undefined)

const typeOptions = [
  { label: t('notifications.center.allTypes'), value: undefined },
  { label: t('notifications.types.task_assigned'), value: 'task_assigned' },
  { label: t('notifications.types.task_completed'), value: 'task_completed' },
  { label: t('notifications.types.task_status_changed'), value: 'task_status_changed' },
  { label: t('notifications.types.comment_added'), value: 'comment_added' },
  { label: t('notifications.types.process_started'), value: 'process_started' },
  { label: t('notifications.types.process_completed'), value: 'process_completed' },
  { label: t('notifications.types.process_cancelled'), value: 'process_cancelled' },
  { label: t('notifications.types.invitation_received'), value: 'invitation_received' },
]

onMounted(() => {
  store.fetchNotifications(typeFilter.value)
})

watch(typeFilter, (type) => {
  store.fetchNotifications(type)
})

async function onMarkAllAsRead() {
  await store.markAllAsRead()
}

async function onClickNotification(id: string, relatedEntityType: string | null, relatedEntityId: string | null) {
  await store.markAsRead(id)

  if (!relatedEntityType || !relatedEntityId) return

  const orgId = orgStore.currentOrgId
  if (!orgId) return

  switch (relatedEntityType) {
    case 'task':
      router.push(`/organizations/${orgId}/tasks/${relatedEntityId}`)
      break
    case 'process_instance':
      router.push(`/organizations/${orgId}/process-instances/${relatedEntityId}`)
      break
    case 'organization':
      router.push(`/organizations/${relatedEntityId}`)
      break
  }
}

function formatTime(dateStr: string): string {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return t('notifications.justNow')
  if (diffMin < 60) return t('notifications.minutesAgo', { n: diffMin })
  const diffHours = Math.floor(diffMin / 60)
  if (diffHours < 24) return t('notifications.hoursAgo', { n: diffHours })
  return date.toLocaleDateString()
}

function getTypeIcon(type: string): string {
  switch (type) {
    case 'task_assigned':
      return 'pi pi-user-plus'
    case 'task_completed':
      return 'pi pi-check-circle'
    case 'task_status_changed':
      return 'pi pi-sync'
    case 'comment_added':
      return 'pi pi-comment'
    case 'process_started':
      return 'pi pi-play'
    case 'process_completed':
      return 'pi pi-check'
    case 'process_cancelled':
      return 'pi pi-times-circle'
    case 'invitation_received':
      return 'pi pi-envelope'
    default:
      return 'pi pi-bell'
  }
}
</script>

<template>
  <div class="notifications-page">
    <div class="page-header">
      <h1 class="page-title">{{ t('notifications.center.title') }}</h1>
      <div class="page-actions">
        <Select
          v-model="typeFilter"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('notifications.center.filterByType')"
          style="width: 220px"
        />
        <Button
          v-if="store.unreadCount > 0"
          :label="t('notifications.markAllRead')"
          text
          @click="onMarkAllAsRead"
        />
      </div>
    </div>

    <ProgressBar v-if="store.loading" mode="indeterminate" style="height: 3px; margin-bottom: 1rem" />

    <div v-if="!store.loading && store.notifications.length === 0" class="empty-state">
      <i class="pi pi-bell empty-icon" />
      <p>{{ t('notifications.center.noNotifications') }}</p>
    </div>

    <div v-if="store.notifications.length > 0" class="notification-list">
      <div
        v-for="n in store.notifications"
        :key="n.id"
        class="notification-item"
        :class="{ unread: !n.isRead, clickable: !!n.relatedEntityType }"
        @click="onClickNotification(n.id, n.relatedEntityType, n.relatedEntityId)"
      >
        <div class="notification-icon-wrapper">
          <i :class="getTypeIcon(n.type)" class="notification-icon" />
          <span v-if="!n.isRead" class="unread-dot" />
        </div>
        <div class="notification-content">
          <div class="notification-header">
            <span class="notification-title">{{ n.title }}</span>
            <span class="notification-time">{{ formatTime(n.createdAt) }}</span>
          </div>
          <div class="notification-body">{{ n.body }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notifications-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: var(--p-text-color);
}

.page-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  color: var(--p-text-muted-color);
}

.empty-icon {
  font-size: 3rem;
  opacity: 0.4;
}

.notification-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.notification-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-radius: 8px;
  background: var(--p-surface-card);
  border: 1px solid var(--p-surface-border);
  transition: background-color 0.15s;
}

.notification-item.clickable {
  cursor: pointer;
}

.notification-item.clickable:hover {
  background-color: var(--p-content-hover-background);
}

.notification-item.unread {
  border-left: 3px solid var(--p-primary-color);
}

.notification-icon-wrapper {
  position: relative;
  display: flex;
  align-items: flex-start;
  padding-top: 0.125rem;
}

.notification-icon {
  font-size: 1.25rem;
  color: var(--p-primary-color);
}

.unread-dot {
  position: absolute;
  top: -2px;
  right: -4px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: var(--p-primary-color);
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.notification-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--p-text-color);
}

.notification-time {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
  flex-shrink: 0;
}

.notification-body {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}
</style>
