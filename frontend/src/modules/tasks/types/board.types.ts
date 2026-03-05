export interface BoardColumnDTO {
  id: string
  boardId: string
  name: string
  position: number
  statusMapping: string | null
  wipLimit: number | null
  color: string | null
  nodeId: string | null
  createdAt: string
}

export interface BoardDTO {
  id: string
  organizationId: string
  name: string
  description: string | null
  boardType: string
  processDefinitionId: string | null
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

export interface ProcessBoardInstanceDTO {
  id: string
  name: string
  status: string
  startedAt: string
  activeNodeId: string | null
  activeNodeName: string | null
  activeTaskId: string | null
  activeTaskAssigneeName: string | null
}

export interface ProcessBoardMetricsDTO {
  totalActive: number
  completedByDay: { date: string; count: number }[]
}

export interface ProcessBoardDataDTO {
  instances: ProcessBoardInstanceDTO[]
  metrics: ProcessBoardMetricsDTO
}

export interface CreateProcessBoardPayload {
  name: string
  process_definition_id: string
}
