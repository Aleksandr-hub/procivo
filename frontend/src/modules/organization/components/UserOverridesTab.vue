<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { permissionApi } from '@/modules/organization/api/permission.api'
import type {
  UserPermissionOverrideDTO,
  PermissionResource,
  PermissionAction,
  PermissionScope,
} from '@/modules/organization/types/organization.types'

const props = defineProps<{
  orgId: string
  employeeId: string
}>()

const toast = useToast()
const confirm = useConfirm()
const { t } = useI18n()

const overrides = ref<UserPermissionOverrideDTO[]>([])
const loading = ref(false)
const showAddForm = ref(false)
const saving = ref(false)

const newResource = ref<PermissionResource>('employee')
const newAction = ref<PermissionAction>('view')
const newEffect = ref<'allow' | 'deny'>('allow')
const newScope = ref<PermissionScope>('own')

const resourceOptions = computed(() => [
  { label: t('permissions.resources.employee'), value: 'employee' as PermissionResource },
  { label: t('permissions.resources.department'), value: 'department' as PermissionResource },
  { label: t('permissions.resources.position'), value: 'position' as PermissionResource },
  { label: t('permissions.resources.role'), value: 'role' as PermissionResource },
  { label: t('permissions.resources.invitation'), value: 'invitation' as PermissionResource },
  { label: t('permissions.resources.organization'), value: 'organization' as PermissionResource },
  { label: t('permissions.resources.task'), value: 'task' as PermissionResource },
  { label: t('permissions.resources.workflow'), value: 'workflow' as PermissionResource },
  { label: t('permissions.resources.audit'), value: 'audit' as PermissionResource },
])

const actionOptions = computed(() => [
  { label: t('permissions.actions.view'), value: 'view' as PermissionAction },
  { label: t('permissions.actions.create'), value: 'create' as PermissionAction },
  { label: t('permissions.actions.update'), value: 'update' as PermissionAction },
  { label: t('permissions.actions.delete'), value: 'delete' as PermissionAction },
  { label: t('permissions.actions.manage'), value: 'manage' as PermissionAction },
])

const effectOptions = [
  { label: 'Allow', value: 'allow' as const },
  { label: 'Deny', value: 'deny' as const },
]

const scopeOptions = computed(() => [
  { label: t('permissions.scopes.own'), value: 'own' as PermissionScope },
  { label: t('permissions.scopes.subordinates'), value: 'subordinates' as PermissionScope },
  { label: t('permissions.scopes.subordinates_tree'), value: 'subordinates_tree' as PermissionScope },
  { label: t('permissions.scopes.department'), value: 'department' as PermissionScope },
  { label: t('permissions.scopes.department_tree'), value: 'department_tree' as PermissionScope },
  { label: t('permissions.scopes.organization'), value: 'organization' as PermissionScope },
])

async function fetchOverrides() {
  loading.value = true
  try {
    overrides.value = await permissionApi.getUserOverrides(props.orgId, props.employeeId)
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('permissions.failedToLoad'),
      life: 5000,
    })
  } finally {
    loading.value = false
  }
}

watch(() => props.employeeId, fetchOverrides, { immediate: true })

function openAddForm() {
  newResource.value = 'employee'
  newAction.value = 'view'
  newEffect.value = 'allow'
  newScope.value = 'own'
  showAddForm.value = true
}

async function addOverride() {
  saving.value = true
  try {
    await permissionApi.setUserOverride(props.orgId, props.employeeId, {
      resource: newResource.value,
      action: newAction.value,
      effect: newEffect.value,
      scope: newScope.value,
    })
    showAddForm.value = false
    await fetchOverrides()
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('permissions.overrideAdded'),
      life: 3000,
    })
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('permissions.failedToAdd'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

function confirmRemove(override: UserPermissionOverrideDTO) {
  confirm.require({
    message: t('permissions.confirmRemoveOverride', {
      resource: override.resource,
      action: override.action,
    }),
    header: t('permissions.confirmRemoveTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await permissionApi.removeUserOverride(props.orgId, props.employeeId, override.id)
        await fetchOverrides()
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('permissions.overrideRemoved'),
          life: 3000,
        })
      } catch {
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: t('permissions.failedToRemove'),
          life: 5000,
        })
      }
    },
  })
}
</script>

<template>
  <div class="user-overrides-tab">
    <div class="section-header">
      <h4>{{ t('permissions.userOverrides') }}</h4>
      <Button
        :label="t('permissions.addOverride')"
        icon="pi pi-plus"
        size="small"
        @click="openAddForm"
      />
    </div>

    <DataTable
      :value="overrides"
      :loading="loading"
      stripedRows
    >
      <template #empty>
        <div class="empty-table">{{ t('permissions.noOverrides') }}</div>
      </template>
      <Column field="resource" :header="t('permissions.resource')" sortable style="min-width: 120px">
        <template #body="{ data }">
          <span class="resource-name">{{ data.resource }}</span>
        </template>
      </Column>
      <Column field="action" :header="t('permissions.action')" sortable style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.action" severity="info" />
        </template>
      </Column>
      <Column field="effect" :header="t('permissions.effect')" style="width: 100px">
        <template #body="{ data }">
          <Tag
            :value="t(`permissions.effects.${data.effect}`)"
            :severity="data.effect === 'allow' ? 'success' : 'danger'"
          />
        </template>
      </Column>
      <Column field="scope" :header="t('permissions.scope')" style="min-width: 140px">
        <template #body="{ data }">
          {{ t(`permissions.scopes.${data.scope}`, data.scope) }}
        </template>
      </Column>
      <Column style="width: 60px">
        <template #body="{ data }">
          <Button
            icon="pi pi-trash"
            text
            rounded
            size="small"
            severity="danger"
            @click="confirmRemove(data)"
          />
        </template>
      </Column>
    </DataTable>

    <Dialog
      v-model:visible="showAddForm"
      :header="t('permissions.addOverride')"
      modal
      style="width: 450px"
    >
      <div class="form-field">
        <label>{{ t('permissions.resource') }}</label>
        <Select
          v-model="newResource"
          :options="resourceOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>
      <div class="form-field">
        <label>{{ t('permissions.action') }}</label>
        <Select
          v-model="newAction"
          :options="actionOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>
      <div class="form-field">
        <label>{{ t('permissions.effect') }}</label>
        <Select
          v-model="newEffect"
          :options="effectOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>
      <div class="form-field">
        <label>{{ t('permissions.scope') }}</label>
        <Select
          v-model="newScope"
          :options="scopeOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showAddForm = false" />
        <Button :label="t('common.add')" :loading="saving" @click="addOverride" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.user-overrides-tab {
  min-height: 100px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.section-header h4 {
  margin: 0;
}

.resource-name {
  font-weight: 500;
  text-transform: capitalize;
}

.form-field {
  margin-bottom: 1rem;
}

.form-field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}
</style>
