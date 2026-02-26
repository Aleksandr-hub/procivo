export interface TaskAttachmentDTO {
  id: string
  taskId: string
  originalName: string
  storagePath: string
  mimeType: string
  fileSize: number
  uploadedBy: string
  uploadedAt: string
  downloadUrl: string
}
