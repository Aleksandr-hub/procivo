import { ref } from 'vue'
import { defineStore } from 'pinia'
import { commentApi } from '@/modules/tasks/api/comment.api'
import type { CommentDTO } from '@/modules/tasks/types/comment.types'

export const useCommentStore = defineStore('comment', () => {
  const comments = ref<CommentDTO[]>([])
  const loading = ref(false)

  async function fetchComments(orgId: string, taskId: string) {
    loading.value = true
    try {
      comments.value = await commentApi.list(orgId, taskId)
    } finally {
      loading.value = false
    }
  }

  async function addComment(orgId: string, taskId: string, body: string, parentId?: string) {
    await commentApi.create(orgId, taskId, {
      body,
      parent_id: parentId ?? null,
    })
    await fetchComments(orgId, taskId)
  }

  async function updateComment(
    orgId: string,
    taskId: string,
    commentId: string,
    body: string,
  ) {
    await commentApi.update(orgId, taskId, commentId, { body })
    await fetchComments(orgId, taskId)
  }

  async function deleteComment(orgId: string, taskId: string, commentId: string) {
    await commentApi.delete(orgId, taskId, commentId)
    await fetchComments(orgId, taskId)
  }

  function clear() {
    comments.value = []
  }

  return {
    comments,
    loading,
    fetchComments,
    addComment,
    updateComment,
    deleteComment,
    clear,
  }
})
