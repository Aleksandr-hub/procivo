export interface NotificationDTO {
  id: string
  recipientId: string
  type: NotificationType
  title: string
  body: string
  relatedEntityId: string | null
  relatedEntityType: 'task' | 'process_instance' | 'organization' | null
  channel: string
  isRead: boolean
  readAt: string | null
  createdAt: string
}

export type NotificationType =
  | 'task_assigned'
  | 'task_completed'
  | 'task_status_changed'
  | 'comment_added'
  | 'process_started'
  | 'process_completed'
  | 'process_cancelled'
  | 'invitation_received'

export interface NotificationPreferences {
  [eventType: string]: {
    in_app: boolean
    email: boolean
  }
}
