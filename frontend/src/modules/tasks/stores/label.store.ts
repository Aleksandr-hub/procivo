import { ref } from 'vue'
import { defineStore } from 'pinia'
import { labelApi } from '@/modules/tasks/api/label.api'
import type { LabelDTO } from '@/modules/tasks/types/label.types'

export const useLabelStore = defineStore('label', () => {
  const labels = ref<LabelDTO[]>([])
  const taskLabels = ref<LabelDTO[]>([])
  const loading = ref(false)

  async function fetchLabels(orgId: string) {
    loading.value = true
    try {
      labels.value = await labelApi.list(orgId)
    } finally {
      loading.value = false
    }
  }

  async function createLabel(orgId: string, name: string, color: string) {
    const result = await labelApi.create(orgId, { name, color })
    await fetchLabels(orgId)
    return result.id
  }

  async function updateLabel(orgId: string, labelId: string, name: string, color: string) {
    await labelApi.update(orgId, labelId, { name, color })
    await fetchLabels(orgId)
  }

  async function deleteLabel(orgId: string, labelId: string) {
    await labelApi.delete(orgId, labelId)
    await fetchLabels(orgId)
  }

  async function fetchTaskLabels(orgId: string, taskId: string) {
    taskLabels.value = await labelApi.getTaskLabels(orgId, taskId)
  }

  async function assignToTask(orgId: string, taskId: string, labelId: string) {
    await labelApi.assignToTask(orgId, taskId, labelId)
    await fetchTaskLabels(orgId, taskId)
  }

  async function removeFromTask(orgId: string, taskId: string, labelId: string) {
    await labelApi.removeFromTask(orgId, taskId, labelId)
    await fetchTaskLabels(orgId, taskId)
  }

  return {
    labels,
    taskLabels,
    loading,
    fetchLabels,
    createLabel,
    updateLabel,
    deleteLabel,
    fetchTaskLabels,
    assignToTask,
    removeFromTask,
  }
})
