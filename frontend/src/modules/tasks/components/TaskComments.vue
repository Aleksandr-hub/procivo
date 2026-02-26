<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useCommentStore } from '@/modules/tasks/stores/comment.store'
import type { CommentDTO } from '@/modules/tasks/types/comment.types'

const props = defineProps<{
  orgId: string
  taskId: string
}>()

const toast = useToast()
const confirm = useConfirm()
const store = useCommentStore()
const { t } = useI18n()

const newCommentBody = ref('')
const replyToId = ref<string | null>(null)
const replyBody = ref('')
const editingId = ref<string | null>(null)
const editBody = ref('')

// Build threaded structure: top-level + replies grouped by parentId
const topLevelComments = computed(() =>
  store.comments.filter((c) => !c.parentId),
)

function getReplies(parentId: string): CommentDTO[] {
  return store.comments.filter((c) => c.parentId === parentId)
}

onMounted(() => {
  store.fetchComments(props.orgId, props.taskId)
})

watch(
  () => props.taskId,
  (newId) => {
    if (newId) {
      store.fetchComments(props.orgId, newId)
      resetState()
    }
  },
)

function resetState() {
  newCommentBody.value = ''
  replyToId.value = null
  replyBody.value = ''
  editingId.value = null
  editBody.value = ''
}

async function submitComment() {
  const body = newCommentBody.value.trim()
  if (!body) return
  try {
    await store.addComment(props.orgId, props.taskId, body)
    newCommentBody.value = ''
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('comments.commentAdded'),
      life: 3000,
    })
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('comments.failedToAdd'),
      life: 5000,
    })
  }
}

async function submitReply() {
  const body = replyBody.value.trim()
  if (!body || !replyToId.value) return
  try {
    await store.addComment(props.orgId, props.taskId, body, replyToId.value)
    replyToId.value = null
    replyBody.value = ''
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('comments.commentAdded'),
      life: 3000,
    })
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('comments.failedToAdd'),
      life: 5000,
    })
  }
}

function startReply(commentId: string) {
  replyToId.value = commentId
  replyBody.value = ''
  editingId.value = null
}

function cancelReply() {
  replyToId.value = null
  replyBody.value = ''
}

function startEdit(comment: CommentDTO) {
  editingId.value = comment.id
  editBody.value = comment.body
  replyToId.value = null
}

function cancelEdit() {
  editingId.value = null
  editBody.value = ''
}

async function submitEdit() {
  const body = editBody.value.trim()
  if (!body || !editingId.value) return
  try {
    await store.updateComment(props.orgId, props.taskId, editingId.value, body)
    editingId.value = null
    editBody.value = ''
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('comments.commentUpdated'),
      life: 3000,
    })
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('comments.failedToUpdate'),
      life: 5000,
    })
  }
}

function confirmDelete(comment: CommentDTO) {
  confirm.require({
    message: t('comments.confirmDelete'),
    header: t('comments.confirmDeleteTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.deleteComment(props.orgId, props.taskId, comment.id)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('comments.commentDeleted'),
          life: 3000,
        })
      } catch {
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: t('comments.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString()
}
</script>

<template>
  <div class="task-comments">
    <div v-if="store.loading" class="comments-loading">
      <ProgressSpinner style="width: 30px; height: 30px" />
    </div>

    <div v-else>
      <!-- New comment form -->
      <div class="new-comment-form">
        <Textarea
          v-model="newCommentBody"
          :placeholder="t('comments.writeComment')"
          rows="2"
          autoResize
          class="comment-input"
        />
        <Button
          :label="t('comments.send')"
          icon="pi pi-send"
          size="small"
          :disabled="!newCommentBody.trim()"
          @click="submitComment"
        />
      </div>

      <!-- Comments list -->
      <div v-if="topLevelComments.length === 0" class="no-comments">
        {{ t('comments.noComments') }}
      </div>

      <div v-for="comment in topLevelComments" :key="comment.id" class="comment-thread">
        <!-- Top-level comment -->
        <div class="comment-item">
          <div class="comment-header">
            <span class="comment-author">
              <i class="pi pi-user" />
              {{ comment.authorName || comment.authorId }}
            </span>
            <span class="comment-date">{{ formatDate(comment.createdAt) }}</span>
            <span v-if="comment.updatedAt" class="comment-edited">
              ({{ t('comments.edited') }})
            </span>
          </div>

          <!-- Edit mode -->
          <div v-if="editingId === comment.id" class="comment-edit-form">
            <Textarea v-model="editBody" rows="2" autoResize class="comment-input" />
            <div class="comment-edit-actions">
              <Button :label="t('common.save')" size="small" @click="submitEdit" />
              <Button
                :label="t('common.cancel')"
                size="small"
                text
                @click="cancelEdit"
              />
            </div>
          </div>

          <!-- View mode -->
          <div v-else>
            <p class="comment-body">{{ comment.body }}</p>
            <div class="comment-actions">
              <Button
                :label="t('comments.reply')"
                text
                size="small"
                icon="pi pi-reply"
                @click="startReply(comment.id)"
              />
              <Button
                icon="pi pi-pencil"
                text
                size="small"
                rounded
                @click="startEdit(comment)"
                v-tooltip="t('common.edit')"
              />
              <Button
                icon="pi pi-trash"
                text
                size="small"
                rounded
                severity="danger"
                @click="confirmDelete(comment)"
                v-tooltip="t('common.delete')"
              />
            </div>
          </div>

          <!-- Reply form -->
          <div v-if="replyToId === comment.id" class="reply-form">
            <Textarea
              v-model="replyBody"
              :placeholder="t('comments.writeReply')"
              rows="2"
              autoResize
              class="comment-input"
            />
            <div class="comment-edit-actions">
              <Button
                :label="t('comments.send')"
                size="small"
                icon="pi pi-send"
                :disabled="!replyBody.trim()"
                @click="submitReply"
              />
              <Button
                :label="t('common.cancel')"
                size="small"
                text
                @click="cancelReply"
              />
            </div>
          </div>
        </div>

        <!-- Replies -->
        <div
          v-for="reply in getReplies(comment.id)"
          :key="reply.id"
          class="comment-item reply"
        >
          <div class="comment-header">
            <span class="comment-author">
              <i class="pi pi-user" />
              {{ reply.authorName || reply.authorId }}
            </span>
            <span class="comment-date">{{ formatDate(reply.createdAt) }}</span>
            <span v-if="reply.updatedAt" class="comment-edited">
              ({{ t('comments.edited') }})
            </span>
          </div>

          <!-- Edit mode for reply -->
          <div v-if="editingId === reply.id" class="comment-edit-form">
            <Textarea v-model="editBody" rows="2" autoResize class="comment-input" />
            <div class="comment-edit-actions">
              <Button :label="t('common.save')" size="small" @click="submitEdit" />
              <Button
                :label="t('common.cancel')"
                size="small"
                text
                @click="cancelEdit"
              />
            </div>
          </div>

          <!-- View mode for reply -->
          <div v-else>
            <p class="comment-body">{{ reply.body }}</p>
            <div class="comment-actions">
              <Button
                icon="pi pi-pencil"
                text
                size="small"
                rounded
                @click="startEdit(reply)"
                v-tooltip="t('common.edit')"
              />
              <Button
                icon="pi pi-trash"
                text
                size="small"
                rounded
                severity="danger"
                @click="confirmDelete(reply)"
                v-tooltip="t('common.delete')"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-comments {
  padding: 0.5rem 0;
}

.comments-loading {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.new-comment-form {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  margin-bottom: 1.5rem;
}

.comment-input {
  flex: 1;
  width: 100%;
}

.no-comments {
  text-align: center;
  padding: 1.5rem;
  color: var(--p-text-muted-color);
}

.comment-thread {
  margin-bottom: 0.75rem;
}

.comment-item {
  padding: 0.75rem;
  border: 1px solid var(--p-content-border-color);
  border-radius: var(--p-border-radius);
  background: var(--p-content-background);
}

.comment-item.reply {
  margin-left: 2rem;
  margin-top: 0.5rem;
  border-left: 3px solid var(--p-primary-color);
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
}

.comment-author {
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.comment-date {
  color: var(--p-text-muted-color);
}

.comment-edited {
  color: var(--p-text-muted-color);
  font-style: italic;
  font-size: 0.8rem;
}

.comment-body {
  margin: 0 0 0.5rem 0;
  white-space: pre-wrap;
  line-height: 1.5;
}

.comment-actions {
  display: flex;
  gap: 0.25rem;
}

.comment-edit-form {
  margin-top: 0.5rem;
}

.comment-edit-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.reply-form {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--p-content-border-color);
}
</style>
