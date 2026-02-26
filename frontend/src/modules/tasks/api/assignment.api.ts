import httpClient from '@/shared/api/http-client'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'
import type { TaskAssignmentDTO, AddAssignmentPayload } from '@/modules/tasks/types/assignment.types'

export const assignmentApi = {
  list(orgId: string, taskId: string): Promise<TaskAssignmentDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/tasks/${taskId}/assignments`)
      .then((r) => r.data)
  },

  add(orgId: string, taskId: string, data: AddAssignmentPayload): Promise<IdResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/assignments`, data)
      .then((r) => r.data)
  },

  remove(orgId: string, taskId: string, assignmentId: string): Promise<MessageResponse> {
    return httpClient
      .delete(`/organizations/${orgId}/tasks/${taskId}/assignments/${assignmentId}`)
      .then((r) => r.data)
  },
}
