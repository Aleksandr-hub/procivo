import { ref } from 'vue'
import { defineStore } from 'pinia'
import { boardApi } from '@/modules/tasks/api/board.api'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import type { BoardColumnDTO, ProcessBoardDataDTO, ProcessBoardInstanceDTO } from '@/modules/tasks/types/board.types'

export const useProcessBoardStore = defineStore('process-board', () => {
  const data = ref<ProcessBoardDataDTO | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchBoardData(orgId: string, boardId: string) {
    loading.value = true
    error.value = null
    try {
      data.value = await boardApi.getProcessBoardData(orgId, boardId)
    } catch (e) {
      error.value = getApiErrorMessage(e, 'Failed to load process board data')
    } finally {
      loading.value = false
    }
  }

  function getInstancesForColumn(column: BoardColumnDTO): ProcessBoardInstanceDTO[] {
    if (!data.value) return []
    if (!column.nodeId) {
      // Completed column: show completed instances
      return data.value.instances.filter((i) => i.status === 'completed')
    }
    return data.value.instances.filter((i) => i.activeNodeId === column.nodeId)
  }

  return { data, loading, error, fetchBoardData, getInstancesForColumn }
})
