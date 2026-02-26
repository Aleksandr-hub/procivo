export interface BoardColumnDTO {
  id: string
  boardId: string
  name: string
  position: number
  statusMapping: string | null
  wipLimit: number | null
  color: string | null
  createdAt: string
}

export interface BoardDTO {
  id: string
  organizationId: string
  name: string
  description: string | null
  createdAt: string
  updatedAt: string | null
  columns: BoardColumnDTO[]
}

export interface CreateBoardPayload {
  name: string
  description?: string
}

export interface UpdateBoardPayload {
  name: string
  description?: string
}

export interface AddColumnPayload {
  name: string
  status_mapping?: string
  wip_limit?: number
  color?: string
}

export interface UpdateColumnPayload {
  name: string
  position: number
  status_mapping?: string
  wip_limit?: number
  color?: string
}
