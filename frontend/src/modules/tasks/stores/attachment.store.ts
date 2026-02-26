import { ref } from 'vue'
import { defineStore } from 'pinia'
import { attachmentApi } from '@/modules/tasks/api/attachment.api'
import type { TaskAttachmentDTO } from '@/modules/tasks/types/attachment.types'

export const useAttachmentStore = defineStore('attachment', () => {
  const attachments = ref<TaskAttachmentDTO[]>([])
  const loading = ref(false)
  const uploading = ref(false)

  async function fetchAttachments(orgId: string, taskId: string) {
    loading.value = true
    try {
      attachments.value = await attachmentApi.list(orgId, taskId)
    } finally {
      loading.value = false
    }
  }

  async function uploadAttachment(orgId: string, taskId: string, file: File) {
    uploading.value = true
    try {
      await attachmentApi.upload(orgId, taskId, file)
      await fetchAttachments(orgId, taskId)
    } finally {
      uploading.value = false
    }
  }

  async function deleteAttachment(orgId: string, taskId: string, attachmentId: string) {
    await attachmentApi.delete(orgId, taskId, attachmentId)
    await fetchAttachments(orgId, taskId)
  }

  function clear() {
    attachments.value = []
  }

  return {
    attachments,
    loading,
    uploading,
    fetchAttachments,
    uploadAttachment,
    deleteAttachment,
    clear,
  }
})
