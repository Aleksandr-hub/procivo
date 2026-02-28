import httpClient from '@/shared/api/http-client'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'
import type {
  CreateTaskPayload,
  ExecuteActionPayload,
  TaskDTO,
  TaskDetailDTO,
  UpdateTaskPayload,
} from '@/modules/tasks/types/task.types'

export const taskApi = {
  list(orgId: string, status?: string, assigneeId?: string, candidateEmployeeId?: string): Promise<TaskDTO[]> {
    const params: Record<string, string> = {}
    if (status) params.status = status
    if (assigneeId) params.assignee_id = assigneeId
    if (candidateEmployeeId) params.candidate_employee_id = candidateEmployeeId
    return httpClient.get(`/organizations/${orgId}/tasks`, { params }).then((r) => r.data)
  },

  get(orgId: string, taskId: string): Promise<TaskDetailDTO> {
    return httpClient.get(`/organizations/${orgId}/tasks/${taskId}`).then((r) => r.data)
  },

  create(orgId: string, data: CreateTaskPayload): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/tasks`, data).then((r) => r.data)
  },

  update(orgId: string, taskId: string, data: UpdateTaskPayload): Promise<MessageResponse> {
    return httpClient.put(`/organizations/${orgId}/tasks/${taskId}`, data).then((r) => r.data)
  },

  transition(orgId: string, taskId: string, transition: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/transition`, { transition })
      .then((r) => r.data)
  },

  assign(orgId: string, taskId: string, assigneeId: string | null): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/tasks/${taskId}/assign`, { assignee_id: assigneeId })
      .then((r) => r.data)
  },

  delete(orgId: string, taskId: string): Promise<void> {
    return httpClient.delete(`/organizations/${orgId}/tasks/${taskId}`).then(() => undefined)
  },

  completeTask(orgId: string, taskId: string, data: ExecuteActionPayload): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/complete`, data)
      .then((r) => r.data)
  },

  claim(orgId: string, taskId: string, employeeId: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/claim`, { employee_id: employeeId })
      .then((r) => r.data)
  },

  unclaim(orgId: string, taskId: string, employeeId: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/unclaim`, { employee_id: employeeId })
      .then((r) => r.data)
  },
}
