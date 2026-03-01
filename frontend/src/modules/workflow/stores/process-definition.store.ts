import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import type {
  ProcessDefinitionDTO,
  ProcessDefinitionDetailDTO,
  ProcessDefinitionVersionDTO,
  CreateProcessDefinitionPayload,
  UpdateProcessDefinitionPayload,
} from '@/modules/workflow/types/process-definition.types'

export const useProcessDefinitionStore = defineStore('processDefinition', () => {
  const definitions = ref<ProcessDefinitionDTO[]>([])
  const currentDefinition = ref<ProcessDefinitionDetailDTO | null>(null)
  const versions = ref<ProcessDefinitionVersionDTO[]>([])
  const loading = ref(false)

  const currentVersion = computed<number | null>(() => {
    if (versions.value.length === 0) return null
    return versions.value[0]?.version_number ?? null
  })

  async function fetchDefinitions(orgId: string, status?: string) {
    loading.value = true
    try {
      definitions.value = await processDefinitionApi.list(orgId, status)
    } finally {
      loading.value = false
    }
  }

  async function fetchDefinition(orgId: string, id: string) {
    loading.value = true
    try {
      currentDefinition.value = await processDefinitionApi.get(orgId, id)
    } finally {
      loading.value = false
    }
  }

  async function fetchVersions(orgId: string, id: string) {
    versions.value = await processDefinitionApi.versions(orgId, id)
  }

  async function createDefinition(orgId: string, data: CreateProcessDefinitionPayload) {
    const result = await processDefinitionApi.create(orgId, data)
    await fetchDefinitions(orgId)
    return result.id
  }

  async function updateDefinition(orgId: string, id: string, data: UpdateProcessDefinitionPayload) {
    await processDefinitionApi.update(orgId, id, data)
    await fetchDefinitions(orgId)
  }

  async function deleteDefinition(orgId: string, id: string) {
    await processDefinitionApi.delete(orgId, id)
    await fetchDefinitions(orgId)
  }

  async function publishDefinition(orgId: string, id: string) {
    await processDefinitionApi.publish(orgId, id)
    await fetchDefinition(orgId, id)
    await fetchVersions(orgId, id)
  }

  async function revertToDraft(orgId: string, id: string) {
    await processDefinitionApi.revertToDraft(orgId, id)
    await fetchDefinitions(orgId)
  }

  return {
    definitions,
    currentDefinition,
    versions,
    currentVersion,
    loading,
    fetchDefinitions,
    fetchDefinition,
    fetchVersions,
    createDefinition,
    updateDefinition,
    deleteDefinition,
    publishDefinition,
    revertToDraft,
  }
})
