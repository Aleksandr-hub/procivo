import httpClient from '@/shared/api/http-client'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'
import type {
  ProcessInstanceDTO,
  ProcessEventDTO,
  StartProcessPayload,
} from '@/modules/workflow/types/process-instance.types'

const base = (orgId: string) => `/organizations/${orgId}/process-instances`

export const processInstanceApi = {
  list(orgId: string, status?: string): Promise<ProcessInstanceDTO[]> {
    const params: Record<string, string> = {}
    if (status) params.status = status
    return httpClient.get(base(orgId), { params }).then((r) => r.data)
  },

  get(orgId: string, id: string): Promise<ProcessInstanceDTO> {
    return httpClient.get(`${base(orgId)}/${id}`).then((r) => r.data)
  },

  start(orgId: string, data: StartProcessPayload): Promise<IdResponse> {
    return httpClient.post(base(orgId), data).then((r) => r.data)
  },

  cancel(orgId: string, id: string, reason?: string): Promise<MessageResponse> {
    return httpClient.post(`${base(orgId)}/${id}/cancel`, { reason }).then((r) => r.data)
  },

  history(orgId: string, id: string): Promise<ProcessEventDTO[]> {
    return httpClient.get(`${base(orgId)}/${id}/history`).then((r) => r.data)
  },
}
