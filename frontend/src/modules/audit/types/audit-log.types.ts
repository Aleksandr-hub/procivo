export interface AuditLogDTO {
  id: string
  event_type: string
  actor_id: string | null
  entity_type: string
  entity_id: string
  organization_id: string | null
  changes: Record<string, unknown> | null
  occurred_at: string
}

export interface AuditLogListResponse {
  items: AuditLogDTO[]
  total: number
  page: number
  limit: number
}

export interface AuditLogListParams {
  entity_type?: string
  entity_id?: string
  actor_id?: string
  date_from?: string
  date_to?: string
  page?: number
  limit?: number
}
