import { ref } from 'vue'
import { defineStore } from 'pinia'
import { employeeApi } from '@/modules/organization/api/employee.api'
import type { EmployeeDTO } from '@/modules/organization/types/organization.types'

export const useEmployeeStore = defineStore('employee', () => {
  const employees = ref<EmployeeDTO[]>([])
  const loading = ref(false)

  async function fetchEmployees(orgId: string, departmentId?: string) {
    loading.value = true
    try {
      employees.value = await employeeApi.list(orgId, departmentId)
    } finally {
      loading.value = false
    }
  }

  async function hireEmployee(
    orgId: string,
    data: {
      user_id: string
      position_id: string
      department_id: string
      employee_number: string
      hired_at?: string
    },
  ) {
    const result = await employeeApi.hire(orgId, data)
    await fetchEmployees(orgId)
    return result.id
  }

  async function updateEmployee(
    orgId: string,
    empId: string,
    data: { position_id?: string; department_id?: string },
  ) {
    await employeeApi.update(orgId, empId, data)
    await fetchEmployees(orgId)
  }

  async function dismissEmployee(orgId: string, empId: string) {
    await employeeApi.dismiss(orgId, empId)
    await fetchEmployees(orgId)
  }

  return {
    employees,
    loading,
    fetchEmployees,
    hireEmployee,
    updateEmployee,
    dismissEmployee,
  }
})
