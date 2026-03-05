import httpClient from '@/shared/api/http-client'
import type {
  BoardDTO,
  CreateBoardPayload,
  UpdateBoardPayload,
  AddColumnPayload,
  UpdateColumnPayload,
  CreateProcessBoardPayload,
  ProcessBoardDataDTO,
} from '@/modules/tasks/types/board.types'

export const boardApi = {
  list(orgId: string) {
    return httpClient.get<BoardDTO[]>(`/organizations/${orgId}/boards`)
  },

  get(orgId: string, boardId: string) {
    return httpClient.get<BoardDTO>(`/organizations/${orgId}/boards/${boardId}`)
  },

  create(orgId: string, data: CreateBoardPayload) {
    return httpClient.post<{ id: string }>(`/organizations/${orgId}/boards`, data)
  },

  update(orgId: string, boardId: string, data: UpdateBoardPayload) {
    return httpClient.put(`/organizations/${orgId}/boards/${boardId}`, data)
  },

  delete(orgId: string, boardId: string) {
    return httpClient.delete(`/organizations/${orgId}/boards/${boardId}`)
  },

  addColumn(orgId: string, boardId: string, data: AddColumnPayload) {
    return httpClient.post<{ id: string }>(`/organizations/${orgId}/boards/${boardId}/columns`, data)
  },

  updateColumn(orgId: string, boardId: string, columnId: string, data: UpdateColumnPayload) {
    return httpClient.put(`/organizations/${orgId}/boards/${boardId}/columns/${columnId}`, data)
  },

  deleteColumn(orgId: string, boardId: string, columnId: string) {
    return httpClient.delete(`/organizations/${orgId}/boards/${boardId}/columns/${columnId}`)
  },

  async createProcessBoard(orgId: string, payload: CreateProcessBoardPayload): Promise<{ id: string }> {
    const { data } = await httpClient.post(`/organizations/${orgId}/boards/process`, payload)
    return data
  },

  async getProcessBoardData(orgId: string, boardId: string): Promise<ProcessBoardDataDTO> {
    const { data } = await httpClient.get(`/organizations/${orgId}/boards/${boardId}/process-data`)
    return data
  },
}
