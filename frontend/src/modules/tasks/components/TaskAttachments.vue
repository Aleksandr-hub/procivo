<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useAttachmentStore } from '@/modules/tasks/stores/attachment.store'

const props = defineProps<{
  orgId: string
  taskId: string
}>()

const toast = useToast()
const confirm = useConfirm()
const store = useAttachmentStore()
const { t } = useI18n()

const fileInput = ref<HTMLInputElement | null>(null)
const MAX_SIZE = 20 * 1024 * 1024

onMounted(() => {
  store.fetchAttachments(props.orgId, props.taskId)
})

const hasAttachments = computed(() => store.attachments.length > 0)

function triggerUpload() {
  fileInput.value?.click()
}

async function onFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (file.size > MAX_SIZE) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('attachments.fileTooLarge'),
      life: 5000,
    })
    input.value = ''
    return
  }

  try {
    await store.uploadAttachment(props.orgId, props.taskId, file)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('attachments.uploaded'),
      life: 3000,
    })
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('attachments.failedToUpload'),
      life: 5000,
    })
  }
  input.value = ''
}

function confirmDelete(attachmentId: string, name: string) {
  confirm.require({
    message: t('attachments.confirmDelete', { name }),
    header: t('attachments.confirmDeleteTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.deleteAttachment(props.orgId, props.taskId, attachmentId)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('attachments.deleted'),
          life: 3000,
        })
      } catch {
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: t('attachments.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function getFileIcon(mimeType: string): string {
  if (mimeType.startsWith('image/')) return 'pi pi-image'
  if (mimeType.startsWith('video/')) return 'pi pi-video'
  if (mimeType === 'application/pdf') return 'pi pi-file-pdf'
  return 'pi pi-file'
}
</script>

<template>
  <div class="task-attachments">
    <div class="attachments-header">
      <h4>{{ t('attachments.title') }}</h4>
      <Button
        :label="t('attachments.upload')"
        icon="pi pi-upload"
        size="small"
        outlined
        :loading="store.uploading"
        @click="triggerUpload"
      />
      <input
        ref="fileInput"
        type="file"
        style="display: none"
        @change="onFileSelected"
      />
    </div>

    <ProgressBar v-if="store.loading" mode="indeterminate" style="height: 4px" />

    <div v-if="!store.loading && !hasAttachments" class="no-attachments">
      {{ t('attachments.noAttachments') }}
    </div>

    <div v-if="hasAttachments" class="attachment-list">
      <div
        v-for="att in store.attachments"
        :key="att.id"
        class="attachment-item"
      >
        <i :class="getFileIcon(att.mimeType)" class="file-icon" />
        <div class="attachment-info">
          <a :href="att.downloadUrl" target="_blank" class="file-name">
            {{ att.originalName }}
          </a>
          <span class="file-meta">{{ formatSize(att.fileSize) }}</span>
        </div>
        <Button
          icon="pi pi-trash"
          text
          rounded
          size="small"
          severity="danger"
          @click="confirmDelete(att.id, att.originalName)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-attachments {
  padding: 0.5rem 0;
}

.attachments-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.attachments-header h4 {
  margin: 0;
}

.no-attachments {
  text-align: center;
  padding: 1rem;
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
}

.attachment-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.attachment-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 6px;
  border: 1px solid var(--p-content-border-color);
}

.file-icon {
  font-size: 1.25rem;
  color: var(--p-text-muted-color);
}

.attachment-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.file-name {
  font-size: 0.875rem;
  color: var(--p-primary-color);
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-name:hover {
  text-decoration: underline;
}

.file-meta {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}
</style>
