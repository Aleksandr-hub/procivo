<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/modules/notifications/stores/notification.store'

const { t } = useI18n()
const router = useRouter()
const store = useNotificationStore()
const menuRef = ref()

onMounted(async () => {
  await store.fetchUnreadCount()
  // Real-time updates handled by Mercure SSE (initialized in DashboardLayout)
})

function toggle(event: Event) {
  if (!menuRef.value?.visible) {
    store.fetchNotifications()
  }
  menuRef.value?.toggle(event)
}

async function onMarkAsRead(id: string) {
  await store.markAsRead(id)
}

async function onMarkAllAsRead() {
  await store.markAllAsRead()
}

function viewAll() {
  menuRef.value?.hide()
  router.push({ name: 'notifications' })
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
  <div class="notification-bell">
    <Button
      icon="pi pi-bell"
      text
      rounded
      :badge="store.unreadCount > 0 ? String(store.unreadCount) : undefined"
      badgeSeverity="danger"
      @click="toggle"
      v-tooltip.bottom="t('notifications.title')"
    />

    <Popover ref="menuRef" style="width: 360px">
      <div class="notification-panel">
        <div class="notification-panel-header">
          <span class="panel-title">{{ t('notifications.title') }}</span>
          <Button
            v-if="store.unreadCount > 0"
            :label="t('notifications.markAllRead')"
            text
            size="small"
            @click="onMarkAllAsRead"
          />
        </div>

        <ProgressBar v-if="store.loading" mode="indeterminate" style="height: 3px" />

        <div v-if="!store.loading && store.notifications.length === 0" class="no-notifications">
          {{ t('notifications.noNotifications') }}
        </div>

        <div v-if="store.notifications.length > 0" class="notification-list">
          <div
            v-for="n in store.notifications"
            :key="n.id"
            class="notification-item"
            :class="{ unread: !n.isRead }"
            @click="onMarkAsRead(n.id)"
          >
            <i :class="getTypeIcon(n.type)" class="notification-icon" />
            <div class="notification-content">
              <div class="notification-title">{{ n.title }}</div>
              <div class="notification-body">{{ n.body }}</div>
              <div class="notification-time">{{ formatTime(n.createdAt) }}</div>
            </div>
          </div>
        </div>

        <div class="notification-panel-footer">
          <Button
            :label="t('notifications.center.viewAll')"
            text
            size="small"
            icon="pi pi-arrow-right"
            icon-pos="right"
            @click="viewAll"
          />
        </div>
      </div>
    </Popover>
  </div>
</template>

<style scoped>
.notification-bell {
  position: relative;
}

.notification-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--p-content-border-color);
  margin-bottom: 0.5rem;
}

.panel-title {
  font-weight: 600;
  font-size: 0.9rem;
}

.no-notifications {
  text-align: center;
  padding: 1.5rem;
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
}

.notification-list {
  max-height: 400px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.notification-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.15s;
}

.notification-item:hover {
  background-color: var(--p-content-hover-background);
}

.notification-item.unread {
  background-color: var(--p-highlight-background);
}

.notification-icon {
  margin-top: 0.25rem;
  font-size: 1rem;
  color: var(--p-primary-color);
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  font-size: 0.8125rem;
  font-weight: 600;
}

.notification-body {
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-time {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  margin-top: 0.125rem;
}

.notification-panel-footer {
  display: flex;
  justify-content: center;
  padding-top: 0.5rem;
  border-top: 1px solid var(--p-content-border-color);
  margin-top: 0.5rem;
}
</style>
