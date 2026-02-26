import httpClient from '@/shared/api/http-client'
import type { NotificationDTO } from '@/modules/notifications/types/notification.types'

export const notificationApi = {
  list(limit = 50, offset = 0): Promise<NotificationDTO[]> {
    return httpClient
      .get('/notifications', { params: { limit, offset } })
      .then((r) => r.data)
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
}
