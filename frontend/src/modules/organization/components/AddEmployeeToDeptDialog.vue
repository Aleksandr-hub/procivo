<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useInvitationStore } from '@/modules/organization/stores/invitation.store'
import { positionApi } from '@/modules/organization/api/position.api'
import { userApi } from '@/modules/auth/api/user.api'
import type { PositionDTO } from '@/modules/organization/types/organization.types'
import type { UserDTO } from '@/modules/auth/types/auth.types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  visible: boolean
  orgId: string
  departmentId: string
  departmentName: string
}>()

const emit = defineEmits<{
  hide: []
  added: []
}>()

const toast = useToast()
const empStore = useEmployeeStore()
const invStore = useInvitationStore()
const { t } = useI18n()

const activeTab = ref(0)
const submitted = ref(false)
const saving = ref(false)

// Shared fields
const positionId = ref('')
const employeeNumber = ref('')
const positions = ref<PositionDTO[]>([])
const positionsLoading = ref(false)

// Hire tab
const selectedUser = ref<UserDTO | null>(null)
const userSuggestions = ref<UserDTO[]>([])

// Invite tab
const email = ref('')

const positionOptions = computed(() =>
  positions.value.map((p) => ({ label: p.name, value: p.id })),
)

const noPositions = computed(
  () => !positionsLoading.value && positions.value.length === 0,
)

watch(
  () => props.visible,
  async (val) => {
    if (val) {
      activeTab.value = 0
      positionId.value = ''
      employeeNumber.value = ''
      selectedUser.value = null
      userSuggestions.value = []
      email.value = ''
      submitted.value = false
      positions.value = []

      if (props.departmentId) {
        positionsLoading.value = true
        try {
          positions.value = await positionApi.list(props.orgId, props.departmentId)
        } finally {
          positionsLoading.value = false
        }
      }
    }
  },
)

async function searchUsers(event: { query: string }) {
  try {
    userSuggestions.value = await userApi.search(event.query)
  } catch {
    userSuggestions.value = []
  }
}

function formatUserOption(item: unknown): string {
  const user = item as UserDTO
  return `${user.firstName} ${user.lastName} (${user.email})`
}

async function onHire() {
  submitted.value = true
  if (!selectedUser.value || !positionId.value || !employeeNumber.value) return

  saving.value = true
  try {
    await empStore.hireEmployee(props.orgId, {
      user_id: selectedUser.value.id,
      department_id: props.departmentId,
      position_id: positionId.value,
      employee_number: employeeNumber.value,
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('dialog.employeeForm.employeeHired'),
      life: 3000,
    })
    emit('added')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.addEmployeeToDept.failedToHire'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

async function onInvite() {
  submitted.value = true
  if (!email.value || !positionId.value || !employeeNumber.value) return

  saving.value = true
  try {
    await invStore.inviteUser(props.orgId, {
      email: email.value,
      department_id: props.departmentId,
      position_id: positionId.value,
      employee_number: employeeNumber.value,
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('dialog.addEmployeeToDept.invitationSentTo', { email: email.value }),
      life: 3000,
    })
    emit('added')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.addEmployeeToDept.failedToSend'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

function onSubmit() {
  if (activeTab.value === 0) {
    onHire()
  } else {
    onInvite()
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="t('dialog.addEmployeeToDept.headerPrefix', { name: departmentName })"
    :style="{ width: '480px' }"
    modal
    @update:visible="!$event && emit('hide')"
  >
    <TabView v-model:activeIndex="activeTab" @tab-change="submitted = false">
      <TabPanel :header="t('dialog.addEmployeeToDept.hireExistingUserTab')">
        <form @submit.prevent="onSubmit" class="dialog-form">
          <div class="field">
            <label>{{ t('dialog.addEmployeeToDept.user') }}</label>
            <AutoComplete
              v-model="selectedUser"
              :suggestions="userSuggestions"
              :optionLabel="formatUserOption"
              :placeholder="t('dialog.addEmployeeToDept.userSearchPlaceholder')"
              :invalid="submitted && !selectedUser"
              @complete="searchUsers"
              fluid
              forceSelection
            />
            <small class="field-help">{{ t('dialog.addEmployeeToDept.userSearchHelp') }}</small>
          </div>

          <div class="field">
            <label>{{ t('dialog.addEmployeeToDept.employeeNumber') }}</label>
            <InputText
              v-model="employeeNumber"
              :placeholder="t('dialog.addEmployeeToDept.employeeNumberPlaceholder')"
              :invalid="submitted && !employeeNumber"
              fluid
            />
          </div>

          <div class="field">
            <label>{{ t('dialog.addEmployeeToDept.position') }}</label>
            <Select
              v-model="positionId"
              :options="positionOptions"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('dialog.addEmployeeToDept.positionPlaceholder')"
              :invalid="submitted && !positionId"
              :loading="positionsLoading"
              :disabled="noPositions"
              fluid
            />
            <small v-if="noPositions" class="field-warn">{{ t('dialog.addEmployeeToDept.noPositionsWarning') }}</small>
          </div>

          <div class="dialog-footer">
            <Button :label="t('common.cancel')" severity="secondary" text @click="emit('hide')" />
            <Button type="submit" :label="t('dialog.addEmployeeToDept.hire')" icon="pi pi-user-plus" :loading="saving" />
          </div>
        </form>
      </TabPanel>

      <TabPanel :header="t('dialog.addEmployeeToDept.inviteNewUserTab')">
        <form @submit.prevent="onSubmit" class="dialog-form">
          <div class="field">
            <label>{{ t('dialog.addEmployeeToDept.email') }}</label>
            <InputText
              v-model="email"
              type="email"
              :placeholder="t('dialog.addEmployeeToDept.emailPlaceholder')"
              :invalid="submitted && !email"
              fluid
            />
            <small class="field-help">{{ t('dialog.addEmployeeToDept.emailHelp') }}</small>
          </div>

          <div class="field">
            <label>{{ t('dialog.addEmployeeToDept.employeeNumber') }}</label>
            <InputText
              v-model="employeeNumber"
              :placeholder="t('dialog.addEmployeeToDept.employeeNumberPlaceholder')"
              :invalid="submitted && !employeeNumber"
              fluid
            />
          </div>

          <div class="field">
            <label>{{ t('dialog.addEmployeeToDept.position') }}</label>
            <Select
              v-model="positionId"
              :options="positionOptions"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('dialog.addEmployeeToDept.positionPlaceholder')"
              :invalid="submitted && !positionId"
              :loading="positionsLoading"
              :disabled="noPositions"
              fluid
            />
            <small v-if="noPositions" class="field-warn">{{ t('dialog.addEmployeeToDept.noPositionsWarning') }}</small>
          </div>

          <div class="dialog-footer">
            <Button :label="t('common.cancel')" severity="secondary" text @click="emit('hide')" />
            <Button type="submit" :label="t('dialog.addEmployeeToDept.sendInvitation')" icon="pi pi-envelope" :loading="saving" />
          </div>
        </form>
      </TabPanel>
    </TabView>
  </Dialog>
</template>

<style scoped>
.dialog-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-top: 0.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field label {
  font-weight: 600;
  font-size: 0.875rem;
}

.field-help {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}

.field-warn {
  color: var(--p-orange-500);
  font-size: 0.75rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 0.5rem;
}
</style>
