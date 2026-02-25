<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { positionApi } from '@/modules/organization/api/position.api'
import { userApi } from '@/modules/auth/api/user.api'
import type { PositionDTO } from '@/modules/organization/types/organization.types'
import type { UserDTO } from '@/modules/auth/types/auth.types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  visible: boolean
  orgId: string
  departments: { label: string; value: string }[]
}>()

const emit = defineEmits<{
  hide: []
  hired: []
}>()

const toast = useToast()
const empStore = useEmployeeStore()
const { t } = useI18n()

const selectedUser = ref<UserDTO | null>(null)
const userSuggestions = ref<UserDTO[]>([])
const departmentId = ref('')
const positionId = ref('')
const employeeNumber = ref('')
const submitted = ref(false)
const saving = ref(false)

const positions = ref<PositionDTO[]>([])
const positionsLoading = ref(false)

const positionOptions = computed(() =>
  positions.value.map((p) => ({ label: p.name, value: p.id })),
)

const noPositions = computed(
  () => departmentId.value && !positionsLoading.value && positions.value.length === 0,
)

watch(
  () => props.visible,
  (val) => {
    if (val) {
      selectedUser.value = null
      userSuggestions.value = []
      departmentId.value = ''
      positionId.value = ''
      employeeNumber.value = ''
      submitted.value = false
      positions.value = []
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

function formatUserOption(user: UserDTO): string {
  return `${user.firstName} ${user.lastName} (${user.email})`
}

async function onDepartmentChange() {
  positionId.value = ''
  if (!departmentId.value) {
    positions.value = []
    return
  }
  positionsLoading.value = true
  try {
    positions.value = await positionApi.list(props.orgId, departmentId.value)
  } finally {
    positionsLoading.value = false
  }
}

async function onSubmit() {
  submitted.value = true
  if (!selectedUser.value || !departmentId.value || !positionId.value || !employeeNumber.value)
    return

  saving.value = true
  try {
    await empStore.hireEmployee(props.orgId, {
      user_id: selectedUser.value.id,
      department_id: departmentId.value,
      position_id: positionId.value,
      employee_number: employeeNumber.value,
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('dialog.employeeForm.employeeHired'),
      life: 3000,
    })
    emit('hired')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.employeeForm.failedToHire'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="t('dialog.employeeForm.header')"
    :style="{ width: '480px' }"
    modal
    @update:visible="!$event && emit('hide')"
  >
    <form @submit.prevent="onSubmit" class="dialog-form">
      <div class="field">
        <label for="empUser">{{ t('dialog.employeeForm.user') }}</label>
        <AutoComplete
          id="empUser"
          v-model="selectedUser"
          :suggestions="userSuggestions"
          :optionLabel="formatUserOption"
          :placeholder="t('dialog.employeeForm.userSearchPlaceholder')"
          :invalid="submitted && !selectedUser"
          @complete="searchUsers"
          fluid
          forceSelection
        />
        <small class="field-help">{{ t('dialog.employeeForm.userSearchHelp') }}</small>
      </div>

      <div class="field">
        <label for="empNumber">{{ t('dialog.employeeForm.employeeNumber') }}</label>
        <InputText
          id="empNumber"
          v-model="employeeNumber"
          :placeholder="t('dialog.employeeForm.employeeNumberPlaceholder')"
          :invalid="submitted && !employeeNumber"
          fluid
        />
        <small class="field-help">{{ t('dialog.employeeForm.employeeNumberHelp') }}</small>
      </div>

      <div class="field">
        <label for="empDept">{{ t('dialog.employeeForm.department') }}</label>
        <Select
          id="empDept"
          v-model="departmentId"
          :options="departments"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('dialog.employeeForm.departmentPlaceholder')"
          :invalid="submitted && !departmentId"
          fluid
          @change="onDepartmentChange"
        />
      </div>

      <div class="field">
        <label for="empPos">{{ t('dialog.employeeForm.position') }}</label>
        <Select
          id="empPos"
          v-model="positionId"
          :options="positionOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('dialog.employeeForm.positionPlaceholder')"
          :invalid="submitted && !positionId"
          :loading="positionsLoading"
          :disabled="!departmentId || noPositions"
          fluid
        />
        <small v-if="noPositions" class="field-warn">{{ t('dialog.employeeForm.noPositionsWarning') }}</small>
      </div>

      <div class="dialog-footer">
        <Button :label="t('common.cancel')" severity="secondary" text @click="emit('hide')" />
        <Button type="submit" :label="t('dialog.employeeForm.hire')" :loading="saving" />
      </div>
    </form>
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
