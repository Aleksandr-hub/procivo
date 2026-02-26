<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import WorkflowActionForm from '@/modules/tasks/components/WorkflowActionForm.vue'
import TaskComments from '@/modules/tasks/components/TaskComments.vue'
import TaskAssignments from '@/modules/tasks/components/TaskAssignments.vue'
import TaskAttachments from '@/modules/tasks/components/TaskAttachments.vue'
import TaskLabels from '@/modules/tasks/components/TaskLabels.vue'
import { getApiErrorMessage } from '@/shared/utils/api-error'

const props = defineProps<{
  orgId: string
  taskId: string
}>()

const router = useRouter()
const toast = useToast()
const { t } = useI18n()
const taskStore = useTaskStore()

const task = computed(() => taskStore.currentTask)

function getStatusSeverity(status: string) {
  switch (status) {
    case 'draft': return 'secondary'
    case 'open': return 'info'
    case 'in_progress': return 'warn'
    case 'review': return 'info'
    case 'done': return 'success'
    case 'blocked': return 'danger'
    case 'cancelled': return 'secondary'
    default: return undefined
  }
}

function getPrioritySeverity(priority: string) {
  switch (priority) {
    case 'low': return 'secondary'
    case 'medium': return 'info'
    case 'high': return 'warn'
    case 'critical': return 'danger'
    default: return undefined
  }
}

function goBack() {
  router.push({ name: 'tasks', params: { orgId: props.orgId } })
}

async function handleTransition(transition: string) {
  try {
    await taskStore.transitionTask(props.orgId, props.taskId, transition)
    await taskStore.fetchTask(props.orgId, props.taskId)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('tasks.statusUpdated'),
      life: 2000,
    })
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  }
}

function onActionExecuted() {
  taskStore.fetchTask(props.orgId, props.taskId)
}

onMounted(() => {
  taskStore.fetchTask(props.orgId, props.taskId)
})

onUnmounted(() => {
  taskStore.clearCurrentTask()
})
</script>

<template>
  <div class="task-detail-page">
    <ProgressSpinner v-if="taskStore.loading && !task" class="loading-spinner" />

    <template v-if="task">
      <!-- Header -->
      <div class="page-header">
        <div class="header-left">
          <Button icon="pi pi-arrow-left" text @click="goBack" />
          <h2>{{ task.title }}</h2>
          <Tag :value="t(`tasks.status${task.status.charAt(0).toUpperCase()}${task.status.slice(1)}`)" :severity="getStatusSeverity(task.status)" />
          <Tag :value="t(`tasks.priority${task.priority.charAt(0).toUpperCase()}${task.priority.slice(1)}`)" :severity="getPrioritySeverity(task.priority)" />
        </div>
      </div>

      <!-- Meta info -->
      <div class="task-meta">
        <div v-if="task.dueDate" class="meta-item">
          <i class="pi pi-calendar" />
          <span>{{ t('taskDetail.dueDate') }}: {{ new Date(task.dueDate).toLocaleDateString() }}</span>
        </div>
        <div v-if="task.estimatedHours" class="meta-item">
          <i class="pi pi-clock" />
          <span>{{ t('taskDetail.estimatedHours') }}: {{ task.estimatedHours }}h</span>
        </div>
        <div class="meta-item">
          <i class="pi pi-calendar-plus" />
          <span>{{ t('taskDetail.created') }}: {{ new Date(task.createdAt).toLocaleDateString() }}</span>
        </div>
      </div>

      <!-- Workflow Action Form -->
      <Card v-if="task.workflow_context" class="section-card">
        <template #title>
          <i class="pi pi-sitemap" /> {{ t('taskDetail.workflowInfo') }}
        </template>
        <template #content>
          <WorkflowActionForm
            :workflow-context="task.workflow_context"
            :org-id="orgId"
            :task-id="taskId"
            @executed="onActionExecuted"
          />
        </template>
      </Card>

      <!-- Regular task transitions -->
      <div v-else-if="task.availableTransitions.length > 0" class="transitions-section">
        <Button
          v-for="tr in task.availableTransitions"
          :key="tr"
          :label="t(`tasks.transition_${tr}`, tr)"
          outlined
          size="small"
          @click="handleTransition(tr)"
        />
      </div>

      <!-- Description -->
      <Card v-if="task.description" class="section-card">
        <template #title>{{ t('tasks.descriptionLabel') }}</template>
        <template #content>
          <p class="description-text">{{ task.description }}</p>
        </template>
      </Card>

      <!-- Tabs -->
      <TabView class="detail-tabs">
        <TabPanel :header="t('assignments.title')">
          <TaskAssignments :org-id="orgId" :task-id="taskId" />
        </TabPanel>
        <TabPanel :header="t('comments.title')">
          <TaskComments :org-id="orgId" :task-id="taskId" />
        </TabPanel>
        <TabPanel :header="t('attachments.title')">
          <TaskAttachments :org-id="orgId" :task-id="taskId" />
        </TabPanel>
        <TabPanel :header="t('labels.assignedLabels')">
          <TaskLabels :org-id="orgId" :task-id="taskId" />
        </TabPanel>
      </TabView>
    </template>
  </div>
</template>

<style scoped>
.task-detail-page {
  padding: 1.5rem;
}

.loading-spinner {
  display: flex;
  justify-content: center;
  margin-top: 3rem;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-left h2 {
  margin: 0;
  font-size: 1.25rem;
}

.task-meta {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.section-card {
  margin-bottom: 1.5rem;
}

.transitions-section {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.description-text {
  margin: 0;
  white-space: pre-wrap;
}

.detail-tabs {
  margin-top: 1rem;
}
</style>
