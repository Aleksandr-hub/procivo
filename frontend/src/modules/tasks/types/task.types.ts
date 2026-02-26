export interface TaskDTO {
  id: string
  organizationId: string
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  dueDate: string | null
  estimatedHours: number | null
  assigneeId: string | null
  creatorId: string
  createdAt: string
  updatedAt: string | null
  availableTransitions: string[]
}

export type TaskStatus =
  | 'draft'
  | 'open'
  | 'in_progress'
  | 'review'
  | 'done'
  | 'blocked'
  | 'cancelled'

export type TaskPriority = 'low' | 'medium' | 'high' | 'critical'

export interface CreateTaskPayload {
  title: string
  description?: string | null
  priority: TaskPriority
  due_date?: string | null
  estimated_hours?: number | null
  creator_id: string
  assignee_id?: string | null
}

export interface UpdateTaskPayload {
  title: string
  description?: string | null
  priority: TaskPriority
  due_date?: string | null
  estimated_hours?: number | null
}
