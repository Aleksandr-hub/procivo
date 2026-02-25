<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import type {
  PermissionResource,
  PermissionAction,
  PermissionScope,
  PermissionDTO,
} from '@/modules/organization/types/organization.types'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const roleStore = useRoleStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const roleId = computed(() => route.params.roleId as string)

const showPermDialog = ref(false)
const permResource = ref<PermissionResource>('employee')
const permAction = ref<PermissionAction>('view')
const permScope = ref<PermissionScope>('own')
const saving = ref(false)

const resourceOptions = computed(() => [
  { label: t('permissions.resources.employee'), value: 'employee' as PermissionResource, description: t('permissions.resources.employeeDesc') },
  { label: t('permissions.resources.department'), value: 'department' as PermissionResource, description: t('permissions.resources.departmentDesc') },
  { label: t('permissions.resources.position'), value: 'position' as PermissionResource, description: t('permissions.resources.positionDesc') },
  { label: t('permissions.resources.role'), value: 'role' as PermissionResource, description: t('permissions.resources.roleDesc') },
  { label: t('permissions.resources.invitation'), value: 'invitation' as PermissionResource, description: t('permissions.resources.invitationDesc') },
  { label: t('permissions.resources.organization'), value: 'organization' as PermissionResource, description: t('permissions.resources.organizationDesc') },
])

const actionOptions = computed(() => [
  { label: t('permissions.actions.view'), value: 'view' as PermissionAction, description: t('permissions.actions.viewDesc') },
  { label: t('permissions.actions.create'), value: 'create' as PermissionAction, description: t('permissions.actions.createDesc') },
  { label: t('permissions.actions.update'), value: 'update' as PermissionAction, description: t('permissions.actions.updateDesc') },
  { label: t('permissions.actions.delete'), value: 'delete' as PermissionAction, description: t('permissions.actions.deleteDesc') },
  { label: t('permissions.actions.manage'), value: 'manage' as PermissionAction, description: t('permissions.actions.manageDesc') },
])

const scopeOptions = computed(() => [
  { label: t('permissions.scopes.own'), value: 'own' as PermissionScope, description: t('permissions.scopes.ownDesc') },
  { label: t('permissions.scopes.subordinates'), value: 'subordinates' as PermissionScope, description: t('permissions.scopes.subordinatesDesc') },
  { label: t('permissions.scopes.subordinates_tree'), value: 'subordinates_tree' as PermissionScope, description: t('permissions.scopes.subordinates_treeDesc') },
  { label: t('permissions.scopes.department'), value: 'department' as PermissionScope, description: t('permissions.scopes.departmentDesc') },
  { label: t('permissions.scopes.department_tree'), value: 'department_tree' as PermissionScope, description: t('permissions.scopes.department_treeDesc') },
  { label: t('permissions.scopes.organization'), value: 'organization' as PermissionScope, description: t('permissions.scopes.organizationDesc') },
])

onMounted(async () => {
  await roleStore.fetchRole(orgId.value, roleId.value)
})

function openAddPermission() {
  permResource.value = 'employee'
  permAction.value = 'view'
  permScope.value = 'own'
  showPermDialog.value = true
}

async function addPermission() {
  saving.value = true
  try {
    await roleStore.grantPermission(orgId.value, roleId.value, {
      resource: permResource.value,
      action: permAction.value,
      scope: permScope.value,
    })
    showPermDialog.value = false
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('permissions.permissionAdded'),
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

function confirmRevokePermission(perm: PermissionDTO) {
  confirm.require({
    message: t('permissions.confirmRevoke', { resource: perm.resource, action: perm.action }),
    header: t('permissions.confirmRevokeTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await roleStore.revokePermission(orgId.value, roleId.value, perm.id)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('permissions.permissionRevoked'),
          life: 3000,
        })
      } catch {
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: t('permissions.failedToRevoke'),
          life: 5000,
        })
      }
    },
  })
}

function goBack() {
  router.push({ name: 'roles', params: { orgId: orgId.value } })
}

function getScopeSeverity(scope: string) {
  switch (scope) {
    case 'organization':
      return 'danger'
    case 'department_tree':
    case 'department':
      return 'warn'
    case 'subordinates_tree':
    case 'subordinates':
      return 'info'
    default:
      return 'secondary'
  }
}
</script>

<template>
  <div class="role-detail-page">
    <div class="page-header">
      <div class="header-left">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <h3>{{ roleStore.currentRole?.name ?? 'Role' }}</h3>
        <Tag
          v-if="roleStore.currentRole?.isSystem"
          :value="t('roles.system')"
          severity="secondary"
          class="ml-2"
        />
      </div>
      <Button
        :label="t('permissions.addPermission')"
        icon="pi pi-plus"
        @click="openAddPermission"
        :disabled="!roleStore.currentRole"
      />
    </div>

    <div v-if="roleStore.currentRole" class="role-info">
      <p v-if="roleStore.currentRole.description" class="text-muted">
        {{ roleStore.currentRole.description }}
      </p>
      <p class="meta">{{ t('roles.priority') }}: {{ roleStore.currentRole.hierarchy }}</p>
    </div>

    <DataTable
      :value="roleStore.currentRole?.permissions ?? []"
      :loading="roleStore.loading"
      stripedRows
    >
      <template #empty>
        <div class="empty-table">{{ t('permissions.noPermissionsAssigned') }}</div>
      </template>
      <Column field="resource" :header="t('permissions.resource')" sortable style="width: 180px">
        <template #body="{ data }">
          <span class="resource-name">{{ data.resource }}</span>
        </template>
      </Column>
      <Column field="action" :header="t('permissions.action')" sortable style="width: 140px">
        <template #body="{ data }">
          <Tag :value="data.action" :severity="data.action === 'manage' ? 'danger' : 'info'" />
        </template>
      </Column>
      <Column field="scope" :header="t('permissions.scope')" sortable style="min-width: 180px">
        <template #body="{ data }">
          <Tag :value="data.scope" :severity="getScopeSeverity(data.scope)" />
        </template>
      </Column>
      <Column style="width: 80px">
        <template #body="{ data }">
          <Button
            icon="pi pi-trash"
            text
            rounded
            size="small"
            severity="danger"
            @click="confirmRevokePermission(data)"
            v-tooltip="t('permissions.revoke')"
          />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showPermDialog" :header="t('permissions.dialogHeader')" modal style="width: 500px">
      <div class="form-field">
        <label>{{ t('permissions.resource') }}</label>
        <Select
          v-model="permResource"
          :options="resourceOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        >
          <template #option="{ option }">
            <div class="select-option-with-desc">
              <span class="option-label">{{ option.label }}</span>
              <span class="option-desc">{{ option.description }}</span>
            </div>
          </template>
        </Select>
      </div>
      <div class="form-field">
        <label>{{ t('permissions.action') }}</label>
        <Select
          v-model="permAction"
          :options="actionOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        >
          <template #option="{ option }">
            <div class="select-option-with-desc">
              <span class="option-label">{{ option.label }}</span>
              <span class="option-desc">{{ option.description }}</span>
            </div>
          </template>
        </Select>
      </div>
      <div class="form-field">
        <label>{{ t('permissions.scope') }}</label>
        <small class="scope-hint">{{ t('permissions.scopeHint') }}</small>
        <Select
          v-model="permScope"
          :options="scopeOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        >
          <template #option="{ option }">
            <div class="select-option-with-desc">
              <span class="option-label">{{ option.label }}</span>
              <span class="option-desc">{{ option.description }}</span>
            </div>
          </template>
        </Select>
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showPermDialog = false" />
        <Button :label="t('common.add')" :loading="saving" @click="addPermission" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.role-detail-page {
  max-width: 900px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-left h3 {
  margin: 0;
}

.role-info {
  margin-bottom: 1.5rem;
}

.meta {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
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

.text-muted {
  color: var(--p-text-muted-color);
}

.ml-2 {
  margin-left: 0.5rem;
}

.select-option-with-desc {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.option-label {
  font-weight: 500;
}

.option-desc {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
}

.scope-hint {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--p-text-muted-color);
}
</style>
