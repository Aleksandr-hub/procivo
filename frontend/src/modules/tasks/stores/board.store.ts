import { ref } from 'vue'
import { defineStore } from 'pinia'
import { boardApi } from '@/modules/tasks/api/board.api'
import type {
  BoardDTO,
  CreateBoardPayload,
  UpdateBoardPayload,
  AddColumnPayload,
  UpdateColumnPayload,
} from '@/modules/tasks/types/board.types'

export const useBoardStore = defineStore('boards', () => {
  const boards = ref<BoardDTO[]>([])
  const loading = ref(false)

  async function fetchBoards(orgId: string) {
    loading.value = true
    try {
      const { data } = await boardApi.list(orgId)
      boards.value = data
    } finally {
      loading.value = false
    }
  }

  async function createBoard(orgId: string, payload: CreateBoardPayload) {
    const { data } = await boardApi.create(orgId, payload)
    await fetchBoards(orgId)
    return data.id
  }

  async function updateBoard(orgId: string, boardId: string, payload: UpdateBoardPayload) {
    await boardApi.update(orgId, boardId, payload)
    await fetchBoards(orgId)
  }

  async function deleteBoard(orgId: string, boardId: string) {
    await boardApi.delete(orgId, boardId)
    boards.value = boards.value.filter((b) => b.id !== boardId)
  }

  async function addColumn(orgId: string, boardId: string, payload: AddColumnPayload) {
    await boardApi.addColumn(orgId, boardId, payload)
    await fetchBoards(orgId)
  }

  async function updateColumn(
    orgId: string,
    boardId: string,
    columnId: string,
    payload: UpdateColumnPayload,
  ) {
    await boardApi.updateColumn(orgId, boardId, columnId, payload)
    await fetchBoards(orgId)
  }

  async function deleteColumn(orgId: string, boardId: string, columnId: string) {
    await boardApi.deleteColumn(orgId, boardId, columnId)
    await fetchBoards(orgId)
  }

  return {
    boards,
    loading,
    fetchBoards,
    createBoard,
    updateBoard,
    deleteBoard,
    addColumn,
    updateColumn,
    deleteColumn,
  }
})
