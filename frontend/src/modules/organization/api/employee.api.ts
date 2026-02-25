import httpClient from '@/shared/api/http-client'
import type { EmployeeDTO, OrgChartNodeDTO } from '@/modules/organization/types/organization.types'
import type { IdResponse, MessageResponse } from '@/shared/types/api.types'

export const employeeApi = {
  list(orgId: string, departmentId?: string): Promise<EmployeeDTO[]> {
    const params = departmentId ? { department_id: departmentId } : {}
    return httpClient.get(`/organizations/${orgId}/employees`, { params }).then((r) => r.data)
  },

  get(orgId: string, empId: string): Promise<EmployeeDTO> {
    return httpClient.get(`/organizations/${orgId}/employees/${empId}`).then((r) => r.data)
  },

  hire(
    orgId: string,
    data: {
      user_id: string
      position_id: string
      department_id: string
      employee_number: string
      hired_at?: string
    },
  ): Promise<IdResponse> {
    return httpClient.post(`/organizations/${orgId}/employees`, data).then((r) => r.data)
  },

  update(
    orgId: string,
    empId: string,
    data: { position_id?: string; department_id?: string },
  ): Promise<MessageResponse> {
    return httpClient.put(`/organizations/${orgId}/employees/${empId}`, data).then((r) => r.data)
  },

  dismiss(orgId: string, empId: string): Promise<MessageResponse> {
    return httpClient
      .post(`/organizations/${orgId}/employees/${empId}/dismiss`)
      .then((r) => r.data)
  },

  setManager(
    orgId: string,
    empId: string,
    managerId: string | null,
  ): Promise<MessageResponse> {
    return httpClient
      .put(`/organizations/${orgId}/employees/${empId}/manager`, { manager_id: managerId })
      .then((r) => r.data)
  },

  getSubordinates(
    orgId: string,
    empId: string,
    recursive = false,
  ): Promise<EmployeeDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/employees/${empId}/subordinates`, {
        params: { recursive: recursive ? 'true' : 'false' },
      })
      .then((r) => r.data)
  },

  getOrgChart(orgId: string): Promise<OrgChartNodeDTO[]> {
    return httpClient
      .get(`/organizations/${orgId}/employees/org-chart`)
      .then((r) => r.data)
  },
}
