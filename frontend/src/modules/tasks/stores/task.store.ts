import { ref } from 'vue'
import { defineStore } from 'pinia'
import { taskApi } from '@/modules/tasks/api/task.api'
import type {
  CreateTaskPayload,
  ExecuteActionPayload,
  TaskDTO,
  TaskDetailDTO,
  UpdateTaskPayload,
} from '@/modules/tasks/types/task.types'

export const useTaskStore = defineStore('task', () => {
  const tasks = ref<TaskDTO[]>([])
  const currentTask = ref<TaskDetailDTO | null>(null)
  const loading = ref(false)
  const selectedTaskId = ref<string | null>(null)

  function selectTask(taskId: string | null) {
    selectedTaskId.value = taskId
  }

  async function fetchTasks(orgId: string, status?: string, assigneeId?: string) {
    loading.value = true
    try {
      tasks.value = await taskApi.list(orgId, status, assigneeId)
    } finally {
      loading.value = false
    }
  }

  async function createTask(orgId: string, data: CreateTaskPayload) {
    const result = await taskApi.create(orgId, data)
    await fetchTasks(orgId)
    return result.id
  }

  async function updateTask(orgId: string, taskId: string, data: UpdateTaskPayload) {
    await taskApi.update(orgId, taskId, data)
    await fetchTasks(orgId)
  }

  async function transitionTask(orgId: string, taskId: string, transition: string) {
    await taskApi.transition(orgId, taskId, transition)
    await fetchTasks(orgId)
  }

  async function assignTask(orgId: string, taskId: string, assigneeId: string | null) {
    await taskApi.assign(orgId, taskId, assigneeId)
    await fetchTasks(orgId)
  }

  async function deleteTask(orgId: string, taskId: string) {
    await taskApi.delete(orgId, taskId)
    await fetchTasks(orgId)
  }

  async function fetchTask(orgId: string, taskId: string) {
    loading.value = true
    try {
      currentTask.value = await taskApi.get(orgId, taskId)
    } finally {
      loading.value = false
    }
  }

  async function completeTask(orgId: string, taskId: string, data: ExecuteActionPayload) {
    await taskApi.completeTask(orgId, taskId, data)
    await Promise.all([fetchTask(orgId, taskId), fetchTasks(orgId)])
  }

  async function claimTask(orgId: string, taskId: string, employeeId: string) {
    await taskApi.claim(orgId, taskId, employeeId)
    await Promise.all([fetchTask(orgId, taskId), fetchTasks(orgId)])
  }

  async function unclaimTask(orgId: string, taskId: string, employeeId: string) {
    await taskApi.unclaim(orgId, taskId, employeeId)
    await Promise.all([fetchTask(orgId, taskId), fetchTasks(orgId)])
  }

  function clearCurrentTask() {
    currentTask.value = null
  }

  return {
    tasks,
    currentTask,
    loading,
    selectedTaskId,
    selectTask,
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    transitionTask,
    assignTask,
    deleteTask,
    completeTask,
    claimTask,
    unclaimTask,
    clearCurrentTask,
  }
})
