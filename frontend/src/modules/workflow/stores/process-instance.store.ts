import { ref } from 'vue'
import { defineStore } from 'pinia'
import { processInstanceApi } from '@/modules/workflow/api/process-instance.api'
import type {
  ProcessInstanceDTO,
  ProcessEventDTO,
} from '@/modules/workflow/types/process-instance.types'

export const useProcessInstanceStore = defineStore('processInstance', () => {
  const instances = ref<ProcessInstanceDTO[]>([])
  const currentInstance = ref<ProcessInstanceDTO | null>(null)
  const history = ref<ProcessEventDTO[]>([])
  const loading = ref(false)

  async function fetchInstances(orgId: string, status?: string) {
    loading.value = true
    try {
      instances.value = await processInstanceApi.list(orgId, status)
    } finally {
      loading.value = false
    }
  }

  async function fetchInstance(orgId: string, id: string) {
    loading.value = true
    try {
      currentInstance.value = await processInstanceApi.get(orgId, id)
    } finally {
      loading.value = false
    }
  }

  async function fetchHistory(orgId: string, id: string) {
    history.value = await processInstanceApi.history(orgId, id)
  }

  async function startProcess(orgId: string, definitionId: string, variables: Record<string, unknown> = {}) {
    const result = await processInstanceApi.start(orgId, {
      process_definition_id: definitionId,
      variables,
    })
    await fetchInstances(orgId)
    return result.id
  }

  async function cancelProcess(orgId: string, id: string, reason?: string) {
    await processInstanceApi.cancel(orgId, id, reason)
    await fetchInstances(orgId)
  }

  return {
    instances,
    currentInstance,
    history,
    loading,
    fetchInstances,
    fetchInstance,
    fetchHistory,
    startProcess,
    cancelProcess,
  }
})
