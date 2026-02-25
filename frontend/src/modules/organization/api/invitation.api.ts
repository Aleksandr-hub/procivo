import httpClient from '@/shared/api/http-client'
import type { InvitationDTO } from '@/modules/organization/types/organization.types'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'

export const invitationApi = {
  list(orgId: string): Promise<InvitationDTO[]> {
    return httpClient.get(`/organizations/${orgId}/invitations`).then((r) => r.data)
  },

  create(
    orgId: string,
    data: {
      email: string
      department_id: string
      position_id: string
      employee_number: string
    },
  ): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/invitations`, data).then((r) => r.data)
  },

  cancel(orgId: string, invitationId: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/invitations/${invitationId}/cancel`)
      .then((r) => r.data)
  },

  getByToken(token: string): Promise<InvitationDTO> {
    return httpClient.get(`/invitations/${token}`).then((r) => r.data)
  },

  accept(
    token: string,
    data: {
      first_name: string
      last_name: string
      password: string
    },
  ): Promise<MessageResponse> {
    return httpClient.post(`/invitations/${token}/accept`, data).then((r) => r.data)
  },
}
