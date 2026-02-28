<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useTaskStore } from '@/modules/tasks/stores/task.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import StatusDropdownButton from '@/modules/tasks/components/StatusDropdownButton.vue'
import ActionFormDialog from '@/modules/tasks/components/ActionFormDialog.vue'
import ProcessHistoryTimeline from '@/modules/tasks/components/ProcessHistoryTimeline.vue'
import TaskComments from '@/modules/tasks/components/TaskComments.vue'
import TaskAssignments from '@/modules/tasks/components/TaskAssignments.vue'
import TaskAttachments from '@/modules/tasks/components/TaskAttachments.vue'
import TaskLabels from '@/modules/tasks/components/TaskLabels.vue'
import PoolTaskBanner from '@/modules/tasks/components/PoolTaskBanner.vue'
import TaskDetailSidebar from '@/modules/tasks/components/TaskDetailSidebar.vue'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import { taskStatusSeverity, taskPrioritySeverity } from '@/shared/utils/status-severity'
import { formatDate, isOverdue } from '@/shared/utils/date-format'
import type { StatusAction } from '@/modules/tasks/types/task.types'

const props = defineProps<{
  orgId: string
  taskId: string
  mode: 'panel' | 'full'
}>()

const emit = defineEmits<{
  close: []
  expand: []
}>()

const toast = useToast()
const confirm = useConfirm()
const { t } = useI18n()
const taskStore = useTaskStore()
const empStore = useEmployeeStore()
const roleStore = useRoleStore()
const deptStore = useDepartmentStore()
const authStore = useAuthStore()

const task = computed(() => taskStore.currentTask)

const currentEmployeeId = computed(() => {
  if (!authStore.user) return null
  const emp = empStore.employees.find((e) => e.userId === authStore.user!.id && e.status === 'active')
  return emp?.id ?? null
})

const isCurrentAssignee = computed(() => {
  return !!(task.value?.assigneeId && currentEmployeeId.value && task.value.assigneeId === currentEmployeeId.value)
})

const poolDescription = computed(() => {
  if (!task.value?.isPoolTask) return null
  if (task.value.candidateRoleId) {
    const role = roleStore.roles.find((r) => r.id === task.value!.candidateRoleId)
    return role ? `${t('workflow.strategyByRole')}: ${role.name}` : null
  }
  if (task.value.candidateDepartmentId) {
    const dept = findDeptName(task.value.candidateDepartmentId)
    return dept ? `${t('workflow.strategyByDepartment')}: ${dept}` : null
  }
  return null
})

function findDeptName(deptId: string): string | null {
  function search(nodes: { id: string; name: string; children: unknown[] }[]): string | null {
    for (const node of nodes) {
      if (node.id === deptId) return node.name
      const found = search(node.children as typeof nodes)
      if (found) return found
    }
    return null
  }
  return search(deptStore.tree)
}

const claimLoading = ref(false)

const poolCandidates = computed(() => {
  if (!task.value?.isPoolTask || task.value.assigneeId) return []
  return empStore.employees.filter((e) => {
    if (e.status !== 'active') return false
    if (task.value!.candidateDepartmentId) return e.departmentId === task.value!.candidateDepartmentId
    return true
  })
})

const candidateAvatars = computed(() => {
  return poolCandidates.value.map((e) => ({
    id: e.id,
    initials: `${e.firstName?.charAt(0) ?? ''}${e.lastName?.charAt(0) ?? ''}`.toUpperCase(),
    fullName: e.userFullName ?? `${e.firstName ?? ''} ${e.lastName ?? ''}`.trim(),
  }))
})

const showActionDialog = ref(false)
const selectedAction = ref<StatusAction | null>(null)

const statusLabelKeys: Record<string, string> = {
  draft: 'tasks.statusDraft',
  open: 'tasks.statusOpen',
  in_progress: 'tasks.statusInProgress',
  review: 'tasks.statusReview',
  done: 'tasks.statusDone',
  blocked: 'tasks.statusBlocked',
  cancelled: 'tasks.statusCancelled',
}

const priorityLabelKeys: Record<string, string> = {
  low: 'tasks.priorityLow',
  medium: 'tasks.priorityMedium',
  high: 'tasks.priorityHigh',
  critical: 'tasks.priorityCritical',
}

const assigneeName = computed(() => {
  if (!task.value?.assigneeId) return null
  const emp = empStore.employees.find((e) => e.id === task.value!.assigneeId)
  return emp ? `${emp.firstName} ${emp.lastName}` : task.value.assigneeId
})

// Status dropdown: unified for workflow + regular tasks
const currentStatusLabel = computed(() => {
  if (!task.value) return ''
  if (task.value.workflow_context && !task.value.workflow_context.is_completed) {
    return task.value.workflow_context.node_name
  }
  const key = statusLabelKeys[task.value.status]
  return key ? t(key) : task.value.status
})

const currentStatusSeverity = computed(() => {
  if (!task.value) return 'info'
  if (task.value.workflow_context && !task.value.workflow_context.is_completed) {
    return 'info'
  }
  return taskStatusSeverity(task.value.status)
})

const availableActions = computed<StatusAction[]>(() => {
  if (!task.value) return []

  // Workflow task: use form_schema.actions
  if (task.value.workflow_context && !task.value.workflow_context.is_completed) {
    return task.value.workflow_context.form_schema.actions.map((action) => ({
      key: action.key,
      label: action.label,
      formFields: action.form_fields,
      type: 'workflow' as const,
    }))
  }

  // Regular task: use availableTransitions
  if (!task.value.workflow_context) {
    return task.value.availableTransitions.map((tr) => ({
      key: tr,
      label: t(`tasks.transition_${tr}`, tr),
      formFields: [],
      type: 'transition' as const,
    }))
  }

  return []
})

const sharedFields = computed(() => {
  return task.value?.workflow_context?.form_schema.shared_fields ?? []
})

const historyFieldLabels = computed<Record<string, string>>(() => {
  const ctx = task.value?.workflow_context
  if (!ctx) return {}
  const map: Record<string, string> = {}
  for (const f of ctx.form_schema.shared_fields) {
    map[f.name] = f.label
  }
  for (const action of ctx.form_schema.actions) {
    for (const f of action.form_fields) {
      if (!map[f.name]) map[f.name] = f.label
    }
  }
  return map
})

function onActionSelected(action: StatusAction) {
  if (action.type === 'workflow' && (action.formFields.length > 0 || sharedFields.value.length > 0)) {
    selectedAction.value = action
    showActionDialog.value = true
  } else if (action.type === 'workflow') {
    confirm.require({
      message: t('taskDetail.confirmAction', { action: action.label }),
      header: t('common.confirm'),
      acceptLabel: t('common.confirm'),
      rejectLabel: t('common.cancel'),
      accept: () => executeWorkflowAction(action.key, {}),
    })
  } else {
    handleTransition(action.key)
  }
}

async function executeWorkflowAction(actionKey: string, formData: Record<string, unknown>) {
  try {
    await taskStore.completeTask(props.orgId, props.taskId, {
      action_key: actionKey,
      form_data: formData,
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('taskDetail.actionExecuted'),
      life: 3000,
    })
    showActionDialog.value = false
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('taskDetail.actionFailed')),
      life: 5000,
    })
  }
}

async function handleTransition(transition: string) {
  try {
    await taskStore.transitionTask(props.orgId, props.taskId, transition)
    await taskStore.fetchTask(props.orgId, props.taskId)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tasks.statusUpdated'), life: 2000 })
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  }
}

function onActionFormSubmit(data: { actionKey: string; formData: Record<string, unknown> }) {
  executeWorkflowAction(data.actionKey, data.formData)
}

async function handleClaim() {
  if (!currentEmployeeId.value) return
  claimLoading.value = true
  try {
    await taskStore.claimTask(props.orgId, props.taskId, currentEmployeeId.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tasks.taskClaimed'), life: 3000 })
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  } finally {
    claimLoading.value = false
  }
}

async function handleAssignCandidate(employeeId: string) {
  claimLoading.value = true
  try {
    await taskStore.claimTask(props.orgId, props.taskId, employeeId)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tasks.taskAssigned'), life: 3000 })
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  } finally {
    claimLoading.value = false
  }
}

async function handleUnclaim() {
  if (!currentEmployeeId.value) return
  claimLoading.value = true
  try {
    await taskStore.unclaimTask(props.orgId, props.taskId, currentEmployeeId.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('tasks.taskUnclaimed'), life: 3000 })
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('tasks.operationFailed')),
      life: 5000,
    })
  } finally {
    claimLoading.value = false
  }
}

onMounted(() => {
  taskStore.fetchTask(props.orgId, props.taskId)
  if (empStore.employees.length === 0) empStore.fetchEmployees(props.orgId)
  if (roleStore.roles.length === 0) roleStore.fetchRoles(props.orgId)
  if (deptStore.tree.length === 0) deptStore.fetchTree(props.orgId)
})

watch(
  () => props.taskId,
  (newId, oldId) => {
    if (newId && newId !== oldId) {
      taskStore.fetchTask(props.orgId, newId)
    }
  },
)

onUnmounted(() => {
  taskStore.clearCurrentTask()
})
</script>

<template>
  <div class="task-detail" :class="{ 'two-column': mode === 'full' }">
    <ProgressSpinner v-if="taskStore.loading && !task" class="loading-spinner" />

    <template v-if="task">
      <!-- Main content area -->
      <main class="main-content">
        <!-- Header -->
        <div class="detail-header">
          <div class="header-top">
            <Button
              v-if="mode === 'full'"
              icon="pi pi-arrow-left"
              text
              rounded
              size="small"
              v-tooltip="t('tasks.backToList')"
              @click="emit('close')"
            />
            <h3 class="detail-title">{{ task.title }}</h3>
            <div class="header-actions">
              <Button
                v-if="mode === 'panel'"
                icon="pi pi-window-maximize"
                text
                rounded
                size="small"
                v-tooltip="t('tasks.expandToFullPage')"
                @click="emit('expand')"
              />
              <Button
                v-if="mode === 'panel'"
                icon="pi pi-times"
                text
                rounded
                size="small"
                v-tooltip="t('tasks.closeDetail')"
                @click="emit('close')"
              />
            </div>
          </div>

          <!-- Process breadcrumb -->
          <div v-if="task.workflow_context" class="process-breadcrumb">
            <i class="pi pi-sitemap" />
            <span>{{ task.workflow_context.process_name }}</span>
            <i class="pi pi-chevron-right" style="font-size: 0.65rem" />
            <Tag :value="task.workflow_context.node_name" severity="info" size="small" />
          </div>

          <div class="header-tags">
            <StatusDropdownButton
              :status-label="currentStatusLabel"
              :status-severity="currentStatusSeverity"
              :actions="availableActions"
              :disabled="task.workflow_context?.is_completed"
              @action="onActionSelected"
            />
            <Tag :value="t(priorityLabelKeys[task.priority] ?? task.priority)" :severity="taskPrioritySeverity(task.priority)" />
            <span v-if="task.dueDate" class="due-date-badge" :class="{ overdue: isOverdue(task.dueDate) }">
              <i class="pi pi-calendar" />
              {{ formatDate(task.dueDate) }}
            </span>
          </div>

          <!-- Workflow completed message -->
          <Message v-if="task.workflow_context?.is_completed" severity="success" :closable="false" class="completed-message">
            {{ t('taskDetail.stageCompleted') }}
          </Message>
        </div>

        <Divider />

        <!-- Pool Task Banner (conditional) -->
        <PoolTaskBanner
          v-if="task.isPoolTask"
          :pool-description="poolDescription"
          :candidate-count="poolCandidates.length"
          :candidates="candidateAvatars"
          :is-current-assignee="isCurrentAssignee"
          :assignee-id="task.assigneeId"
          :claim-loading="claimLoading"
          @claim="handleClaim"
          @unclaim="handleUnclaim"
          @assign="handleAssignCandidate"
        />

        <!-- Panel mode: compact properties display (no sidebar) -->
        <div v-if="mode === 'panel'" class="section properties-compact">
          <div class="prop-inline">
            <span class="prop-label">{{ t('tasks.assignee') }}</span>
            <span class="prop-value">{{ assigneeName ?? t('tasks.unassigned') }}</span>
          </div>
          <div v-if="task.dueDate" class="prop-inline">
            <span class="prop-label">{{ t('taskDetail.dueDate') }}</span>
            <span class="prop-value" :class="{ overdue: isOverdue(task.dueDate) }">{{ formatDate(task.dueDate) }}</span>
          </div>
          <div class="prop-inline">
            <span class="prop-label">{{ t('taskDetail.created') }}</span>
            <span class="prop-value">{{ formatDate(task.createdAt) }}</span>
          </div>
        </div>

        <!-- Description -->
        <div v-if="task.description" class="section">
          <h4>{{ t('tasks.description') }}</h4>
          <p class="description-text">{{ task.description }}</p>
        </div>

        <Divider />

        <!-- Tabs -->
        <TabView class="detail-tabs">
          <TabPanel :header="t('comments.title')">
            <TaskComments :org-id="orgId" :task-id="taskId" />
          </TabPanel>
          <TabPanel :header="t('attachments.title')">
            <TaskAttachments :org-id="orgId" :task-id="taskId" />
          </TabPanel>
          <TabPanel :header="t('assignments.title')">
            <TaskAssignments :org-id="orgId" :task-id="taskId" />
          </TabPanel>
          <TabPanel :header="t('labels.assignedLabels')">
            <TaskLabels :org-id="orgId" :task-id="taskId" />
          </TabPanel>
          <TabPanel v-if="task.workflow_context" :header="t('history.title')">
            <ProcessHistoryTimeline
              :org-id="orgId"
              :process-instance-id="task.workflow_context.process_instance_id"
              :field-labels="historyFieldLabels"
            />
          </TabPanel>
        </TabView>
      </main>

      <!-- Sidebar (full mode only) -->
      <aside v-if="mode === 'full'" class="sidebar">
        <TaskDetailSidebar :task="task" :assignee-name="assigneeName" />
      </aside>
    </template>

    <!-- Action form dialog -->
    <ActionFormDialog
      :visible="showActionDialog"
      :action="selectedAction"
      :shared-fields="sharedFields"
      @hide="showActionDialog = false"
      @submit="onActionFormSubmit"
    />
  </div>
</template>

<style scoped>
.task-detail {
  max-width: 900px;
}

.task-detail.two-column {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1.5rem;
  align-items: start;
}

.main-content {
  min-width: 0;
}

.sidebar {
  position: sticky;
  top: 1.5rem;
  background: var(--p-surface-card);
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-border-radius);
  padding: 1rem;
}

.loading-spinner {
  display: flex;
  justify-content: center;
  margin-top: 3rem;
  grid-column: 1 / -1;
}

.detail-header {
  margin-bottom: 0.5rem;
}

.header-top {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.detail-title {
  margin: 0;
  font-size: 1.25rem;
  flex: 1;
}

.header-actions {
  display: flex;
  gap: 0.25rem;
  margin-left: auto;
}

.process-breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  margin-bottom: 0.5rem;
}

.header-tags {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.due-date-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
}

.due-date-badge.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}

.completed-message {
  margin-top: 0.75rem;
}

.section {
  margin-bottom: 1rem;
}

.section h4 {
  margin: 0 0 0.5rem;
  font-size: 0.9rem;
  color: var(--p-text-muted-color);
}

/* Panel mode compact properties */
.properties-compact {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.5rem;
  padding: 0.5rem 0;
}

.prop-inline {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.prop-label {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.prop-value {
  font-size: 0.85rem;
}

.prop-value.overdue {
  color: var(--p-red-500);
  font-weight: 500;
}

.description-text {
  margin: 0;
  white-space: pre-wrap;
  font-size: 0.9rem;
  line-height: 1.5;
}

.detail-tabs {
  margin-top: 0.5rem;
}

@media (max-width: 1024px) {
  .task-detail.two-column {
    grid-template-columns: 1fr;
  }

  .sidebar {
    display: none;
  }
}
</style>
