export interface NotificationDTO {
  id: string
  recipientId: string
  type: NotificationType
  title: string
  body: string
  relatedEntityId: string | null
  isRead: boolean
  createdAt: string
}

export type NotificationType = 'task_assigned' | 'task_status_changed' | 'comment_added' | 'task_created'
