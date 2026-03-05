import { ref } from 'vue'
import { defineStore } from 'pinia'
import { taskApi } from '@/modules/tasks/api/task.api'
import { processInstanceApi } from '@/modules/workflow/api/process-instance.api'
import { auditLogApi } from '@/modules/audit/api/audit-log.api'
import { dashboardApi } from '@/modules/dashboard/api/dashboard.api'
import type { TaskDTO } from '@/modules/tasks/types/task.types'
import type { ProcessInstanceDTO } from '@/modules/workflow/types/process-instance.types'
import type { AuditLogDTO } from '@/modules/audit/types/audit-log.types'
import type { DashboardStatsDTO } from '@/modules/dashboard/types/dashboard.types'

export const useDashboardStore = defineStore('dashboard', () => {
  const myTasks = ref<TaskDTO[]>([])
  const activeProcesses = ref<ProcessInstanceDTO[]>([])
  const stats = ref<DashboardStatsDTO | null>(null)
  const recentActivity = ref<AuditLogDTO[]>([])
  const loading = ref(false)

  async function fetchMyTasks(orgId: string, employeeId: string | null): Promise<void> {
    try {
      if (employeeId === null) {
        myTasks.value = []
        return
      }
      const tasks = await taskApi.list(orgId, undefined, employeeId)
      myTasks.value = tasks.filter((t) => t.status !== 'done' && t.status !== 'cancelled')
    } catch {
      // error in individual fetch does not block others
    }
  }

  async function fetchActiveProcesses(orgId: string): Promise<void> {
    try {
      const result = await processInstanceApi.list(orgId, { status: 'running' })
      activeProcesses.value = result.items
    } catch {
      // error in individual fetch does not block others
    }
  }

  async function fetchStats(orgId: string): Promise<void> {
    try {
      stats.value = await dashboardApi.stats(orgId)
    } catch {
      // error in individual fetch does not block others
    }
  }

  async function fetchRecentActivity(orgId: string): Promise<void> {
    try {
      const result = await auditLogApi.list(orgId, { limit: 20 })
      recentActivity.value = result.items
    } catch {
      // error in individual fetch does not block others
    }
  }

  async function fetchAll(orgId: string, employeeId: string | null): Promise<void> {
    loading.value = true
    try {
      await Promise.all([
        fetchMyTasks(orgId, employeeId),
        fetchActiveProcesses(orgId),
        fetchStats(orgId),
        fetchRecentActivity(orgId),
      ])
    } finally {
      loading.value = false
    }
  }

  return {
    myTasks,
    activeProcesses,
    stats,
    recentActivity,
    loading,
    fetchAll,
  }
})
