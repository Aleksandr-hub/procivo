import { ref } from 'vue'
import { defineStore } from 'pinia'
import { invitationApi } from '@/modules/organization/api/invitation.api'
import type { InvitationDTO } from '@/modules/organization/types/organization.types'

export const useInvitationStore = defineStore('invitation', () => {
  const invitations = ref<InvitationDTO[]>([])
  const loading = ref(false)

  async function fetchInvitations(orgId: string) {
    loading.value = true
    try {
      invitations.value = await invitationApi.list(orgId)
    } finally {
      loading.value = false
    }
  }

  async function inviteUser(
    orgId: string,
    data: {
      email: string
      department_id: string
      position_id: string
      employee_number: string
    },
  ) {
    const result = await invitationApi.create(orgId, data)
    await fetchInvitations(orgId)
    return result.id
  }

  async function cancelInvitation(orgId: string, invitationId: string) {
    await invitationApi.cancel(orgId, invitationId)
    await fetchInvitations(orgId)
  }

  return {
    invitations,
    loading,
    fetchInvitations,
    inviteUser,
    cancelInvitation,
  }
})
