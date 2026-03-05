export type ProcessInstanceStatus = 'running' | 'completed' | 'cancelled' | 'error'

export interface ProcessInstanceTokenDTO {
  id: string
  node_id: string
  status: string
  fire_at?: string | null
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

export interface ProcessInstanceGraphDTO {
  nodes: Array<{
    id: string
    type: string
    name: string
    description: string | null
    config: Record<string, unknown>
    position_x: number
    position_y: number
  }>
  transitions: Array<{
    id: string
    source_node_id: string
    target_node_id: string
    name: string | null
    action_key: string | null
    condition_expression: string | null
    form_fields: unknown[]
    sort_order: number
  }>
}

export interface StartProcessPayload {
  process_definition_id: string
  variables?: Record<string, unknown>
}
