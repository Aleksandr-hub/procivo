import httpClient from '@/shared/api/http-client'
import type { NotificationDTO, NotificationPreferences } from '@/modules/notifications/types/notification.types'

export const notificationApi = {
  list(limit = 50, offset = 0, type?: string): Promise<NotificationDTO[]> {
    const params: Record<string, unknown> = { limit, offset }
    if (type) params.type = type
    return httpClient.get('/notifications', { params }).then((r) => r.data)
  },

  unreadCount(): Promise<{ count: number }> {
    return httpClient.get('/notifications/unread-count').then((r) => r.data)
  },

  markAsRead(notificationId: string): Promise<void> {
    return httpClient.post(`/notifications/${notificationId}/read`).then(() => undefined)
  },

  markAllAsRead(): Promise<void> {
    return httpClient.post('/notifications/read-all').then(() => undefined)
  },

  getPreferences(): Promise<NotificationPreferences> {
    return httpClient.get('/notifications/preferences').then((r) => r.data)
  },

  savePreferences(preferences: NotificationPreferences): Promise<void> {
    return httpClient.put('/notifications/preferences', preferences).then(() => undefined)
  },
}
