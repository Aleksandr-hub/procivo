import httpClient from '@/shared/api/http-client'
import type { DashboardStatsDTO } from '@/modules/dashboard/types/dashboard.types'

export const dashboardApi = {
  stats(orgId: string): Promise<DashboardStatsDTO> {
    return httpClient.get(`/organizations/${orgId}/dashboard/stats`).then((r) => r.data)
  },
}
