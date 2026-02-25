import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { organizationApi } from '@/modules/organization/api/organization.api'
import type { OrganizationDTO } from '@/modules/organization/types/organization.types'

export const useOrganizationStore = defineStore('organization', () => {
  const organizations = ref<OrganizationDTO[]>([])
  const currentOrgId = ref<string | null>(null)
  const loading = ref(false)

  const currentOrg = computed(() =>
    organizations.value.find((o) => o.id === currentOrgId.value) ?? null,
  )

  async function fetchOrganizations() {
    loading.value = true
    try {
      organizations.value = await organizationApi.list()
    } finally {
      loading.value = false
    }
  }

  function selectOrganization(id: string) {
    currentOrgId.value = id
  }

  async function createOrganization(data: {
    name: string
    slug: string
    description?: string | null
  }) {
    const result = await organizationApi.create(data)
    await fetchOrganizations()
    return result.id
  }

  async function updateOrganization(
    id: string,
    data: { name: string; description?: string | null },
  ) {
    await organizationApi.update(id, data)
    await fetchOrganizations()
  }

  async function suspendOrganization(id: string) {
    await organizationApi.suspend(id)
    await fetchOrganizations()
  }

  return {
    organizations,
    currentOrgId,
    currentOrg,
    loading,
    fetchOrganizations,
    selectOrganization,
    createOrganization,
    updateOrganization,
    suspendOrganization,
  }
})
