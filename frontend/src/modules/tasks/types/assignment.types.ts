export interface TaskAssignmentDTO {
  id: string
  taskId: string
  employeeId: string
  role: AssignmentRole
  assignedBy: string
  assignedAt: string
  employeeName: string | null
}

export type AssignmentRole = 'assignee' | 'reviewer' | 'watcher'

export interface AddAssignmentPayload {
  employee_id: string
  role: AssignmentRole
}
