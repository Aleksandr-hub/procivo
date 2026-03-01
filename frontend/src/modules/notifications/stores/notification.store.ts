import { ref } from 'vue'
import { defineStore } from 'pinia'
import { notificationApi } from '@/modules/notifications/api/notification.api'
import type { NotificationDTO, NotificationPreferences } from '@/modules/notifications/types/notification.types'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<NotificationDTO[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const preferences = ref<NotificationPreferences>({})
  const preferencesLoading = ref(false)
  const typeFilter = ref<string | undefined>(undefined)

  let eventSource: EventSource | null = null

  async function fetchNotifications(type?: string) {
    loading.value = true
    try {
      notifications.value = await notificationApi.list(50, 0, type)
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
      item.readAt = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  async function markAllAsRead() {
    await notificationApi.markAllAsRead()
    notifications.value.forEach((n) => {
      n.isRead = true
      n.readAt = new Date().toISOString()
    })
    unreadCount.value = 0
  }

  // Mercure SSE — replaces polling
  function initMercure(userId: string) {
    const mercureUrl = import.meta.env.VITE_MERCURE_URL
    if (!mercureUrl || eventSource) return

    const topic = encodeURIComponent(`/users/${userId}/notifications`)
    eventSource = new EventSource(`${mercureUrl}?topic=${topic}`)

    eventSource.onmessage = (event) => {
      try {
        const msg = JSON.parse(event.data)
        if (msg.event === 'notification.created') {
          notifications.value.unshift(msg.data)
          unreadCount.value += 1
        }
      } catch {
        // ignore parse errors
      }
    }

    eventSource.onerror = () => {
      // SSE will auto-reconnect
    }
  }

  function closeMercure() {
    eventSource?.close()
    eventSource = null
  }

  // Preferences
  async function fetchPreferences() {
    preferencesLoading.value = true
    try {
      preferences.value = await notificationApi.getPreferences()
    } finally {
      preferencesLoading.value = false
    }
  }

  async function savePreferences(prefs: NotificationPreferences) {
    await notificationApi.savePreferences(prefs)
    preferences.value = prefs
  }

  return {
    notifications,
    unreadCount,
    loading,
    preferences,
    preferencesLoading,
    typeFilter,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    initMercure,
    closeMercure,
    fetchPreferences,
    savePreferences,
  }
})
