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

  async function executeAction(orgId: string, taskId: string, data: ExecuteActionPayload) {
    await taskApi.executeAction(orgId, taskId, data)
    await fetchTask(orgId, taskId)
  }

  function clearCurrentTask() {
    currentTask.value = null
  }

  return {
    tasks,
    currentTask,
    loading,
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    transitionTask,
    assignTask,
    deleteTask,
    executeAction,
    clearCurrentTask,
  }
})
