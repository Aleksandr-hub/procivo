import httpClient from '@/shared/api/http-client'
import type { AuditLogListParams, AuditLogListResponse } from '@/modules/audit/types/audit-log.types'

export const auditLogApi = {
  list(orgId: string, params: AuditLogListParams = {}): Promise<AuditLogListResponse> {
    return httpClient.get(`/organizations/${orgId}/audit-log`, { params }).then((r) => r.data)
  },
}
