<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import type { RoleDTO } from '@/modules/organization/types/organization.types'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const roleStore = useRoleStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)

const showCreateDialog = ref(false)
const editingRole = ref<RoleDTO | null>(null)
const formName = ref('')
const formDescription = ref('')
const formHierarchy = ref(100)
const saving = ref(false)

onMounted(async () => {
  await roleStore.fetchRoles(orgId.value)
})

function openCreate() {
  editingRole.value = null
  formName.value = ''
  formDescription.value = ''
  formHierarchy.value = 100
  showCreateDialog.value = true
}

function openEdit(role: RoleDTO) {
  editingRole.value = role
  formName.value = role.name
  formDescription.value = role.description ?? ''
  formHierarchy.value = role.hierarchy
  showCreateDialog.value = true
}

async function saveRole() {
  saving.value = true
  try {
    if (editingRole.value) {
      await roleStore.updateRole(orgId.value, editingRole.value.id, {
        name: formName.value,
        description: formDescription.value || null,
        hierarchy: formHierarchy.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('roles.roleUpdated'), life: 3000 })
    } else {
      await roleStore.createRole(orgId.value, {
        name: formName.value,
        description: formDescription.value || null,
        hierarchy: formHierarchy.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('roles.roleCreated'), life: 3000 })
    }
    showCreateDialog.value = false
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('roles.failedToSaveRole'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

function confirmDelete(role: RoleDTO) {
  confirm.require({
    message: t('roles.confirmDeleteRole', { name: role.name }),
    header: t('roles.confirmDelete'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await roleStore.deleteRole(orgId.value, role.id)
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('roles.roleDeleted'), life: 3000 })
      } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { error?: string } } }
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: axiosError.response?.data?.error || t('roles.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}

function openRoleDetail(role: RoleDTO) {
  router.push({ name: 'role-detail', params: { orgId: orgId.value, roleId: role.id } })
}
</script>

<template>
  <div class="roles-page">
    <div class="page-header">
      <h3>{{ t('roles.title') }}</h3>
      <Button :label="t('roles.createRole')" icon="pi pi-plus" @click="openCreate" />
    </div>

    <DataTable
      :value="roleStore.roles"
      :loading="roleStore.loading"
      stripedRows
      paginator
      :rows="20"
      @row-click="(e: { data: RoleDTO }) => openRoleDetail(e.data)"
      class="cursor-pointer"
    >
      <template #empty>
        <div class="empty-table">{{ t('roles.noRolesFound') }}</div>
      </template>
      <Column field="name" :header="t('roles.name')" sortable style="min-width: 200px">
        <template #body="{ data }">
          <span class="role-name">{{ data.name }}</span>
          <Tag v-if="data.isSystem" :value="t('roles.system')" severity="secondary" class="ml-2" />
        </template>
      </Column>
      <Column field="description" :header="t('roles.description')" style="min-width: 200px">
        <template #body="{ data }">
          <span class="text-muted">{{ data.description || '—' }}</span>
        </template>
      </Column>
      <Column field="hierarchy" :header="t('roles.priority')" sortable style="width: 100px" />
      <Column :header="t('roles.permissionsCount')" style="width: 120px">
        <template #body="{ data }">
          <Badge :value="data.permissions.length" severity="info" />
        </template>
      </Column>
      <Column style="width: 120px">
        <template #body="{ data }">
          <div class="action-buttons">
            <Button
              icon="pi pi-pencil"
              text
              rounded
              size="small"
              @click.stop="openEdit(data)"
              v-tooltip="t('common.edit')"
              :disabled="data.isSystem"
            />
            <Button
              icon="pi pi-trash"
              text
              rounded
              size="small"
              severity="danger"
              @click.stop="confirmDelete(data)"
              v-tooltip="t('common.delete')"
              :disabled="data.isSystem"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog
      v-model:visible="showCreateDialog"
      :header="editingRole ? t('roles.dialogHeaderEdit') : t('roles.dialogHeaderCreate')"
      modal
      style="width: 450px"
    >
      <div class="form-field">
        <label for="role-name">{{ t('roles.dialogName') }}</label>
        <InputText id="role-name" v-model="formName" class="w-full" />
      </div>
      <div class="form-field">
        <label for="role-desc">{{ t('roles.dialogDescription') }}</label>
        <Textarea id="role-desc" v-model="formDescription" rows="3" class="w-full" />
      </div>
      <div class="form-field">
        <label for="role-hierarchy">{{ t('roles.dialogPriority') }}</label>
        <InputNumber id="role-hierarchy" v-model="formHierarchy" class="w-full" :min="0" :max="1000" />
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showCreateDialog = false" />
        <Button
          :label="editingRole ? t('common.update') : t('common.create')"
          :loading="saving"
          :disabled="!formName.trim()"
          @click="saveRole"
        />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.roles-page {
  max-width: 1000px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.page-header h3 {
  margin: 0;
}

.role-name {
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
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

.cursor-pointer :deep(tr) {
  cursor: pointer;
}
</style>
