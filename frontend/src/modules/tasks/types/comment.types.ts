export interface CommentDTO {
  id: string
  taskId: string
  authorId: string
  parentId: string | null
  body: string
  createdAt: string
  updatedAt: string | null
  authorName: string | null
  authorAvatarUrl: string | null
}

export interface CreateCommentPayload {
  body: string
  parent_id?: string | null
}

export interface UpdateCommentPayload {
  body: string
}
