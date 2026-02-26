export type ProcessInstanceStatus = 'running' | 'completed' | 'cancelled' | 'error'

export interface ProcessInstanceTokenDTO {
  id: string
  node_id: string
  status: string
}

export interface ProcessInstanceDTO {
  id: string
  definition_id: string
  definition_name: string
  version_id: string
  organization_id: string
  status: ProcessInstanceStatus
  started_by: string
  variables: Record<string, unknown>
  tokens: ProcessInstanceTokenDTO[]
  started_at: string
  completed_at: string | null
  cancelled_at: string | null
}

export interface ProcessEventDTO {
  id: string
  event_type: string
  payload: Record<string, unknown>
  version: number
  occurred_at: string
}

export interface StartProcessPayload {
  process_definition_id: string
  variables?: Record<string, unknown>
}
