import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

export interface WorkflowActionDTO {
  key: string
  label: string
  form_fields: FormFieldDefinition[]
}

export interface WorkflowFormSchemaDTO {
  shared_fields: FormFieldDefinition[]
  actions: WorkflowActionDTO[]
}

export interface TaskWorkflowContextDTO {
  process_instance_id: string
  process_name: string
  node_name: string
  node_id: string
  is_completed: boolean
  form_schema: WorkflowFormSchemaDTO
}

export interface TaskWorkflowSummaryDTO {
  process_instance_id: string
  process_name: string
  node_name: string
  is_completed: boolean
}

export type AssignmentStrategy = 'unassigned' | 'specific_user' | 'by_role' | 'by_department' | 'from_variable'

export interface TaskLabelSummary {
  name: string
  color: string
}

export interface TaskDTO {
  id: string
  sequenceNumber: number
  organizationId: string
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  dueDate: string | null
  estimatedHours: number | null
  assigneeId: string | null
  assignmentStrategy: AssignmentStrategy
  candidateRoleId: string | null
  candidateDepartmentId: string | null
  isPoolTask: boolean
  creatorId: string
  createdAt: string
  updatedAt: string | null
  availableTransitions: string[]
  labels: TaskLabelSummary[]
  workflow_summary: TaskWorkflowSummaryDTO | null
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
  assignment_strategy?: AssignmentStrategy
  assignee_employee_id?: string | null
  assignee_role_id?: string | null
  assignee_department_id?: string | null
}

export interface UpdateTaskPayload {
  title: string
  description?: string | null
  priority: TaskPriority
  due_date?: string | null
  estimated_hours?: number | null
}

export interface TaskDetailDTO extends TaskDTO {
  workflow_context: TaskWorkflowContextDTO | null
}

export interface ExecuteActionPayload {
  action_key: string
  form_data: Record<string, unknown>
}

export interface StatusAction {
  key: string
  label: string
  formFields: FormFieldDefinition[]
  type: 'workflow' | 'transition'
}
