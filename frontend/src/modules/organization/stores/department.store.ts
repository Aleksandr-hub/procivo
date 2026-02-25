import { ref } from 'vue'
import { defineStore } from 'pinia'
import { departmentApi } from '@/modules/organization/api/department.api'
import type { DepartmentDTO, DepartmentTreeDTO } from '@/modules/organization/types/organization.types'

export const useDepartmentStore = defineStore('department', () => {
  const tree = ref<DepartmentTreeDTO[]>([])
  const selectedDepartment = ref<DepartmentDTO | null>(null)
  const loading = ref(false)

  async function fetchTree(orgId: string) {
    loading.value = true
    try {
      tree.value = await departmentApi.tree(orgId)
    } finally {
      loading.value = false
    }
  }

  async function fetchDepartment(orgId: string, deptId: string) {
    selectedDepartment.value = await departmentApi.get(orgId, deptId)
  }

  async function createDepartment(
    orgId: string,
    data: {
      name: string
      code: string
      parent_id?: string | null
      description?: string | null
      sort_order?: number
    },
  ) {
    const result = await departmentApi.create(orgId, data)
    await fetchTree(orgId)
    return result.id
  }

  async function updateDepartment(
    orgId: string,
    deptId: string,
    data: { name: string; description?: string | null; sort_order?: number },
  ) {
    await departmentApi.update(orgId, deptId, data)
    await fetchTree(orgId)
  }

  async function moveDepartment(orgId: string, deptId: string, newParentId: string | null) {
    await departmentApi.move(orgId, deptId, newParentId)
    await fetchTree(orgId)
  }

  async function deleteDepartment(orgId: string, deptId: string) {
    await departmentApi.delete(orgId, deptId)
    if (selectedDepartment.value?.id === deptId) {
      selectedDepartment.value = null
    }
    await fetchTree(orgId)
  }

  return {
    tree,
    selectedDepartment,
    loading,
    fetchTree,
    fetchDepartment,
    createDepartment,
    updateDepartment,
    moveDepartment,
    deleteDepartment,
  }
})
