import { ref } from 'vue'
import { defineStore } from 'pinia'
import { notificationApi } from '@/modules/notifications/api/notification.api'
import type { NotificationDTO } from '@/modules/notifications/types/notification.types'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<NotificationDTO[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)

  async function fetchNotifications() {
    loading.value = true
    try {
      notifications.value = await notificationApi.list()
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    const result = await notificationApi.unreadCount()
    unreadCount.value = result.count
  }

  async function markAsRead(notificationId: string) {
    await notificationApi.markAsRead(notificationId)
    const item = notifications.value.find((n) => n.id === notificationId)
    if (item && !item.isRead) {
      item.isRead = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  async function markAllAsRead() {
    await notificationApi.markAllAsRead()
    notifications.value.forEach((n) => (n.isRead = true))
    unreadCount.value = 0
  }

  return {
    notifications,
    unreadCount,
    loading,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
  }
})
