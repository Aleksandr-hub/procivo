import httpClient from '@/shared/api/http-client'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'
import type {
  ProcessDefinitionDTO,
  ProcessDefinitionDetailDTO,
  CreateProcessDefinitionPayload,
  UpdateProcessDefinitionPayload,
  AddNodePayload,
  UpdateNodePayload,
  AddTransitionPayload,
  UpdateTransitionPayload,
  FormFieldDefinition,
} from '@/modules/workflow/types/process-definition.types'

const base = (orgId: string) => `/organizations/${orgId}/process-definitions`

export const processDefinitionApi = {
  list(orgId: string, status?: string): Promise<ProcessDefinitionDTO[]> {
    const params: Record<string, string> = {}
    if (status) params.status = status
    return httpClient.get(base(orgId), { params }).then((r) => r.data)
  },

  get(orgId: string, id: string): Promise<ProcessDefinitionDetailDTO> {
    return httpClient.get(`${base(orgId)}/${id}`).then((r) => r.data)
  },

  create(orgId: string, data: CreateProcessDefinitionPayload): Promise<IdResponse> {
    return httpClient.post(base(orgId), data).then((r) => r.data)
  },

  update(orgId: string, id: string, data: UpdateProcessDefinitionPayload): Promise<MessageResponse> {
    return httpClient.put(`${base(orgId)}/${id}`, data).then((r) => r.data)
  },

  delete(orgId: string, id: string): Promise<void> {
    return httpClient.delete(`${base(orgId)}/${id}`).then(() => undefined)
  },

  publish(orgId: string, id: string): Promise<MessageResponse> {
    return httpClient.post(`${base(orgId)}/${id}/publish`).then((r) => r.data)
  },

  revertToDraft(orgId: string, id: string): Promise<MessageResponse> {
    return httpClient.post(`${base(orgId)}/${id}/revert-to-draft`).then((r) => r.data)
  },

  getStartForm(orgId: string, defId: string): Promise<{ fields: FormFieldDefinition[] }> {
    return httpClient.get(`${base(orgId)}/${defId}/start-form`).then((r) => r.data)
  },

  // Nodes
  addNode(orgId: string, defId: string, data: AddNodePayload): Promise<IdResponse> {
    return httpClient.post(`${base(orgId)}/${defId}/nodes`, data).then((r) => r.data)
  },

  updateNode(orgId: string, defId: string, nodeId: string, data: UpdateNodePayload): Promise<MessageResponse> {
    return httpClient.put(`${base(orgId)}/${defId}/nodes/${nodeId}`, data).then((r) => r.data)
  },

  removeNode(orgId: string, defId: string, nodeId: string): Promise<void> {
    return httpClient.delete(`${base(orgId)}/${defId}/nodes/${nodeId}`).then(() => undefined)
  },

  // Transitions
  addTransition(orgId: string, defId: string, data: AddTransitionPayload): Promise<IdResponse> {
    return httpClient.post(`${base(orgId)}/${defId}/transitions`, data).then((r) => r.data)
  },

  updateTransition(
    orgId: string,
    defId: string,
    transitionId: string,
    data: UpdateTransitionPayload,
  ): Promise<MessageResponse> {
    return httpClient.put(`${base(orgId)}/${defId}/transitions/${transitionId}`, data).then((r) => r.data)
  },

  removeTransition(orgId: string, defId: string, transitionId: string): Promise<void> {
    return httpClient.delete(`${base(orgId)}/${defId}/transitions/${transitionId}`).then(() => undefined)
  },
}
