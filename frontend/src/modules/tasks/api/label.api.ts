import httpClient from '@/shared/api/http-client'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'
import type {
  LabelDTO,
  CreateLabelPayload,
  UpdateLabelPayload,
} from '@/modules/tasks/types/label.types'

export const labelApi = {
  list(orgId: string): Promise<LabelDTO[]> {
    return httpClient.get(`/organizations/${orgId}/labels`).then((r) => r.data)
  },

  create(orgId: string, data: CreateLabelPayload): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/labels`, data).then((r) => r.data)
  },

  update(orgId: string, labelId: string, data: UpdateLabelPayload): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/labels/${labelId}`, data)
      .then((r) => r.data)
  },

  delete(orgId: string, labelId: string): Promise<MessageResponse> {
    return httpClient
      .delete(`/organizations/${orgId}/labels/${labelId}`)
      .then((r) => r.data)
  },

  getTaskLabels(orgId: string, taskId: string): Promise<LabelDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/tasks/${taskId}/labels`)
      .then((r) => r.data)
  },

  assignToTask(orgId: string, taskId: string, labelId: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/labels/${labelId}`)
      .then((r) => r.data)
  },

  removeFromTask(orgId: string, taskId: string, labelId: string): Promise<MessageResponse> {
    return httpClient
      .delete(`/organizations/${orgId}/tasks/${taskId}/labels/${labelId}`)
      .then((r) => r.data)
  },
}
