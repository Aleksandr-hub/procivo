import httpClient from '@/shared/api/http-client'
import type { MessageResponse } from '@/shared/types/api.types'
import type { TaskAttachmentDTO } from '@/modules/tasks/types/attachment.types'

export const attachmentApi = {
  list(orgId: string, taskId: string): Promise<TaskAttachmentDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/tasks/${taskId}/attachments`)
      .then((r) => r.data)
  },

  upload(orgId: string, taskId: string, file: File): Promise<{ id: string }> {
    const formData = new FormData()
    formData.append('file', file)
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/attachments`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((r) => r.data)
  },

  delete(orgId: string, taskId: string, attachmentId: string): Promise<MessageResponse> {
    return httpClient
      .delete(`/organizations/${orgId}/tasks/${taskId}/attachments/${attachmentId}`)
      .then((r) => r.data)
  },
}
