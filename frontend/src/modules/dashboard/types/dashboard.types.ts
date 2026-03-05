export interface DashboardStatsDTO {
  tasks_by_status: Record<string, number>
  tasks_completed_by_day: Array<{ day: string; cnt: number }>
  processes_by_status: Record<string, number>
}
