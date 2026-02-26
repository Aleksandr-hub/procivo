import { ref } from 'vue'
import { defineStore } from 'pinia'
import { assignmentApi } from '@/modules/tasks/api/assignment.api'
import type { TaskAssignmentDTO, AssignmentRole } from '@/modules/tasks/types/assignment.types'

export const useAssignmentStore = defineStore('assignment', () => {
  const assignments = ref<TaskAssignmentDTO[]>([])
  const loading = ref(false)

  async function fetchAssignments(orgId: string, taskId: string) {
    loading.value = true
    try {
      assignments.value = await assignmentApi.list(orgId, taskId)
    } finally {
      loading.value = false
    }
  }

  async function addAssignment(orgId: string, taskId: string, employeeId: string, role: AssignmentRole) {
    await assignmentApi.add(orgId, taskId, { employee_id: employeeId, role })
    await fetchAssignments(orgId, taskId)
  }

  async function removeAssignment(orgId: string, taskId: string, assignmentId: string) {
    await assignmentApi.remove(orgId, taskId, assignmentId)
    await fetchAssignments(orgId, taskId)
  }

  function clear() {
    assignments.value = []
  }

  return {
    assignments,
    loading,
    fetchAssignments,
    addAssignment,
    removeAssignment,
    clear,
  }
})
