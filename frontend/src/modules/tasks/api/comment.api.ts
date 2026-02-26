import httpClient from '@/shared/api/http-client'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'
import type {
  CommentDTO,
  CreateCommentPayload,
  UpdateCommentPayload,
} from '@/modules/tasks/types/comment.types'

export const commentApi = {
  list(orgId: string, taskId: string): Promise<CommentDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/tasks/${taskId}/comments`)
      .then((r) => r.data)
  },

  create(orgId: string, taskId: string, data: CreateCommentPayload): Promise<IdResponse> {
    return httpClient
      .post(`/organizations/${orgId}/tasks/${taskId}/comments`, data)
      .then((r) => r.data)
  },

  update(
    orgId: string,
    taskId: string,
    commentId: string,
    data: UpdateCommentPayload,
  ): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/tasks/${taskId}/comments/${commentId}`, data)
      .then((r) => r.data)
  },

  delete(orgId: string, taskId: string, commentId: string): Promise<MessageResponse> {
    return httpClient
      .delete(`/organizations/${orgId}/tasks/${taskId}/comments/${commentId}`)
      .then((r) => r.data)
  },
}
