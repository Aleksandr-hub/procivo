<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useInvitationStore } from '@/modules/organization/stores/invitation.store'
import { positionApi } from '@/modules/organization/api/position.api'
import type { PositionDTO } from '@/modules/organization/types/organization.types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  visible: boolean
  orgId: string
  departments: { label: string; value: string }[]
}>()

const emit = defineEmits<{
  hide: []
  invited: []
}>()

const toast = useToast()
const invStore = useInvitationStore()
const { t } = useI18n()

const email = ref('')
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
      email.value = ''
      departmentId.value = ''
      positionId.value = ''
      employeeNumber.value = ''
      submitted.value = false
      positions.value = []
    }
  },
)

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
  if (!email.value || !departmentId.value || !positionId.value || !employeeNumber.value) return

  saving.value = true
  try {
    await invStore.inviteUser(props.orgId, {
      email: email.value,
      department_id: departmentId.value,
      position_id: positionId.value,
      employee_number: employeeNumber.value,
    })
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('dialog.invite.invitationSentTo', { email: email.value }),
      life: 3000,
    })
    emit('invited')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.invite.failedToSend'),
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
    :header="t('dialog.invite.header')"
    :style="{ width: '480px' }"
    modal
    @update:visible="!$event && emit('hide')"
  >
    <form @submit.prevent="onSubmit" class="dialog-form">
      <div class="field">
        <label for="invEmail">{{ t('dialog.invite.email') }}</label>
        <InputText
          id="invEmail"
          v-model="email"
          type="email"
          :placeholder="t('dialog.invite.emailPlaceholder')"
          :invalid="submitted && !email"
          fluid
        />
        <small class="field-help">{{ t('dialog.invite.emailHelp') }}</small>
      </div>

      <div class="field">
        <label for="invNumber">{{ t('dialog.invite.employeeNumber') }}</label>
        <InputText
          id="invNumber"
          v-model="employeeNumber"
          :placeholder="t('dialog.invite.employeeNumberPlaceholder')"
          :invalid="submitted && !employeeNumber"
          fluid
        />
        <small class="field-help">{{ t('dialog.invite.employeeNumberHelp') }}</small>
      </div>

      <div class="field">
        <label for="invDept">{{ t('dialog.invite.department') }}</label>
        <Select
          id="invDept"
          v-model="departmentId"
          :options="departments"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('dialog.invite.departmentPlaceholder')"
          :invalid="submitted && !departmentId"
          fluid
          @change="onDepartmentChange"
        />
      </div>

      <div class="field">
        <label for="invPos">{{ t('dialog.invite.position') }}</label>
        <Select
          id="invPos"
          v-model="positionId"
          :options="positionOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('dialog.invite.positionPlaceholder')"
          :invalid="submitted && !positionId"
          :loading="positionsLoading"
          :disabled="!departmentId || noPositions"
          fluid
        />
        <small v-if="noPositions" class="field-warn">{{ t('dialog.invite.noPositionsWarning') }}</small>
      </div>

      <div class="dialog-footer">
        <Button :label="t('common.cancel')" severity="secondary" text @click="emit('hide')" />
        <Button type="submit" :label="t('dialog.invite.sendInvitation')" icon="pi pi-envelope" :loading="saving" />
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
