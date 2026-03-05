<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useInvitationStore } from '@/modules/organization/stores/invitation.store'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import type {
  EmployeeDTO,
  InvitationDTO,
  DepartmentTreeDTO,
} from '@/modules/organization/types/organization.types'
import InviteDialog from '@/modules/organization/components/InviteDialog.vue'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const empStore = useEmployeeStore()
const invStore = useInvitationStore()
const deptStore = useDepartmentStore()

const orgId = computed(() => route.params.orgId as string)
const { t } = useI18n()
const filterDeptId = ref<string | undefined>(undefined)
const activeTab = ref(0)

const showInviteDialog = ref(false)

onMounted(async () => {
  await Promise.all([
    empStore.fetchEmployees(orgId.value),
    invStore.fetchInvitations(orgId.value),
    deptStore.tree.length === 0 ? deptStore.fetchTree(orgId.value) : Promise.resolve(),
  ])
})

function flattenDepts(items: DepartmentTreeDTO[]): { label: string; value: string }[] {
  const result: { label: string; value: string }[] = []
  function walk(nodes: DepartmentTreeDTO[], prefix: string) {
    for (const node of nodes) {
      result.push({ label: prefix + node.name, value: node.id })
      if (node.children.length > 0) {
        walk(node.children, prefix + '  ')
      }
    }
  }
  walk(items, '')
  return result
}

const departmentOptions = computed(() => flattenDepts(deptStore.tree))

async function onFilterChange() {
  await empStore.fetchEmployees(orgId.value, filterDeptId.value)
}

function confirmDismiss(emp: EmployeeDTO) {
  confirm.require({
    message: t('employees.confirmDismiss', { number: emp.employeeNumber }),
    header: t('employees.confirmDismissTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await empStore.dismissEmployee(orgId.value, emp.id)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('employees.employeeDismissed'),
          life: 3000,
        })
      } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { error?: string } } }
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: axiosError.response?.data?.error || t('employees.failedToDismiss'),
          life: 5000,
        })
      }
    },
  })
}

function confirmCancelInvitation(inv: InvitationDTO) {
  confirm.require({
    message: t('employees.confirmCancelInvitation', { email: inv.email }),
    header: t('employees.confirmCancelTitle'),
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await invStore.cancelInvitation(orgId.value, inv.id)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('employees.invitationCancelled'),
          life: 3000,
        })
      } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { error?: string } } }
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: axiosError.response?.data?.error || t('employees.failedToCancelInvitation'),
          life: 5000,
        })
      }
    },
  })
}

async function handleInvited() {
  showInviteDialog.value = false
}

function getStatusSeverity(status: string) {
  switch (status) {
    case 'active':
      return 'success'
    case 'dismissed':
      return 'danger'
    default:
      return 'info'
  }
}

function getInvitationSeverity(status: string) {
  switch (status) {
    case 'pending':
      return 'warn'
    case 'accepted':
      return 'success'
    case 'expired':
      return 'danger'
    case 'cancelled':
      return 'secondary'
    default:
      return 'info'
  }
}

const pendingCount = computed(
  () => invStore.invitations.filter((i) => i.status === 'pending').length,
)
</script>

<template>
  <div class="employees-page">
    <div class="page-header">
      <h3>{{ t('employees.title') }}</h3>
      <Button :label="t('employees.inviteEmployee')" icon="pi pi-envelope" @click="showInviteDialog = true" />
    </div>

    <TabView v-model:activeIndex="activeTab">
      <TabPanel :header="t('employees.title')">
        <div class="tab-toolbar">
          <Select
            v-model="filterDeptId"
            :options="departmentOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('employees.allDepartments')"
            showClear
            @change="onFilterChange"
            style="width: 220px"
          />
        </div>

        <DataTable
          :value="empStore.employees"
          :loading="empStore.loading"
          stripedRows
          paginator
          :rows="20"
        >
          <template #empty>
            <div class="empty-table">{{ t('employees.noEmployeesFound') }}</div>
          </template>
          <Column field="employeeNumber" :header="t('employees.employeeNumber')" sortable style="width: 130px" />
          <Column field="userFullName" :header="t('employees.fullName')" sortable style="min-width: 180px">
            <template #body="{ data }">
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <Avatar
                  :image="data.userAvatarUrl ?? undefined"
                  :label="data.userAvatarUrl ? undefined : (data.userFullName || '?').charAt(0).toUpperCase()"
                  shape="circle"
                  size="small"
                />
                <div>
                  <div>{{ data.userFullName || data.userId }}</div>
                  <div v-if="data.userEmail" class="text-muted text-sm">{{ data.userEmail }}</div>
                </div>
              </div>
            </template>
          </Column>
          <Column field="departmentName" :header="t('employees.department')" sortable style="min-width: 160px">
            <template #body="{ data }">
              {{ data.departmentName || data.departmentId }}
            </template>
          </Column>
          <Column field="positionName" :header="t('employees.position')" sortable style="min-width: 150px">
            <template #body="{ data }">
              {{ data.positionName || '—' }}
            </template>
          </Column>
          <Column field="hiredAt" :header="t('employees.hired')" sortable style="width: 160px">
            <template #body="{ data }">
              {{ new Date(data.hiredAt).toLocaleDateString() }}
            </template>
          </Column>
          <Column field="status" :header="t('employees.status')" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>
          <Column style="width: 80px">
            <template #body="{ data }">
              <Button
                v-if="data.status === 'active'"
                icon="pi pi-user-minus"
                text
                rounded
                size="small"
                severity="danger"
                @click="confirmDismiss(data)"
                v-tooltip="t('employees.dismiss')"
              />
            </template>
          </Column>
        </DataTable>
      </TabPanel>

      <TabPanel>
        <template #header>
          <span>{{ t('employees.invitations') }}</span>
          <Badge v-if="pendingCount > 0" :value="pendingCount" severity="warn" class="tab-badge" />
        </template>

        <DataTable
          :value="invStore.invitations"
          :loading="invStore.loading"
          stripedRows
          paginator
          :rows="20"
        >
          <template #empty>
            <div class="empty-table">{{ t('employees.noInvitationsYet') }}</div>
          </template>
          <Column field="email" :header="t('employees.email')" sortable style="min-width: 200px" />
          <Column field="employeeNumber" :header="t('employees.employeeNumber')" style="width: 140px" />
          <Column field="status" :header="t('employees.status')" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getInvitationSeverity(data.status)" />
            </template>
          </Column>
          <Column field="expiresAt" :header="t('employees.expires')" sortable style="width: 160px">
            <template #body="{ data }">
              {{ new Date(data.expiresAt).toLocaleDateString() }}
            </template>
          </Column>
          <Column field="createdAt" :header="t('employees.sent')" sortable style="width: 160px">
            <template #body="{ data }">
              {{ new Date(data.createdAt).toLocaleDateString() }}
            </template>
          </Column>
          <Column style="width: 80px">
            <template #body="{ data }">
              <Button
                v-if="data.status === 'pending'"
                icon="pi pi-times"
                text
                rounded
                size="small"
                severity="danger"
                @click="confirmCancelInvitation(data)"
                v-tooltip="t('employees.cancelInvitation')"
              />
            </template>
          </Column>
        </DataTable>
      </TabPanel>
    </TabView>

    <InviteDialog
      :visible="showInviteDialog"
      :org-id="orgId"
      :departments="departmentOptions"
      @hide="showInviteDialog = false"
      @invited="handleInvited"
    />
  </div>
</template>

<style scoped>
.employees-page {
  max-width: 1200px;
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

.tab-toolbar {
  margin-bottom: 1rem;
}

.tab-badge {
  margin-left: 0.5rem;
}

.text-muted {
  color: var(--p-text-muted-color);
}

.text-sm {
  font-size: 0.8rem;
}

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}
</style>
