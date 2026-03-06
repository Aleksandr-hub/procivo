<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import RolePermissionsTab from '@/modules/organization/components/RolePermissionsTab.vue'
import DepartmentPermissionsTab from '@/modules/organization/components/DepartmentPermissionsTab.vue'
import UserOverridesTab from '@/modules/organization/components/UserOverridesTab.vue'
import EffectivePermissionsView from '@/modules/organization/components/EffectivePermissionsView.vue'
import type { DepartmentTreeDTO } from '@/modules/organization/types/organization.types'

const route = useRoute()
const { t } = useI18n()
const roleStore = useRoleStore()
const deptStore = useDepartmentStore()
const empStore = useEmployeeStore()

const orgId = computed(() => route.params.orgId as string)
const activeTab = ref(0)

const selectedRoleId = ref<string | undefined>(undefined)
const selectedDeptId = ref<string | undefined>(undefined)
const selectedEmployeeId = ref<string | undefined>(undefined)

const roleOptions = computed(() =>
  roleStore.roles.map((r) => ({ label: r.name, value: r.id })),
)

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

const employeeOptions = computed(() =>
  empStore.employees
    .filter((e) => e.status === 'active')
    .map((e) => ({
      label: e.userFullName || e.userEmail || e.employeeNumber,
      value: e.id,
    })),
)

onMounted(async () => {
  await Promise.all([
    roleStore.roles.length === 0 ? roleStore.fetchRoles(orgId.value) : Promise.resolve(),
    deptStore.tree.length === 0 ? deptStore.fetchTree(orgId.value) : Promise.resolve(),
    empStore.employees.length === 0 ? empStore.fetchEmployees(orgId.value) : Promise.resolve(),
  ])
})
</script>

<template>
  <div class="permissions-page">
    <div class="page-header">
      <h3>{{ t('permissions.title') }}</h3>
    </div>

    <TabView v-model:activeIndex="activeTab">
      <TabPanel :header="t('permissions.rolesTab')">
        <div class="tab-toolbar">
          <Select
            v-model="selectedRoleId"
            :options="roleOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.selectRole')"
            class="selector"
          />
        </div>
        <RolePermissionsTab
          v-if="selectedRoleId"
          :org-id="orgId"
          :role-id="selectedRoleId"
        />
        <div v-else class="empty-selection">
          {{ t('permissions.selectRoleHint') }}
        </div>
      </TabPanel>

      <TabPanel :header="t('permissions.departmentsTab')">
        <div class="tab-toolbar">
          <Select
            v-model="selectedDeptId"
            :options="departmentOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.selectDepartment')"
            class="selector"
          />
        </div>
        <DepartmentPermissionsTab
          v-if="selectedDeptId"
          :org-id="orgId"
          :department-id="selectedDeptId"
        />
        <div v-else class="empty-selection">
          {{ t('permissions.selectDepartmentHint') }}
        </div>
      </TabPanel>

      <TabPanel :header="t('permissions.usersTab')">
        <div class="tab-toolbar">
          <Select
            v-model="selectedEmployeeId"
            :options="employeeOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.selectUser')"
            filter
            class="selector"
          />
        </div>
        <template v-if="selectedEmployeeId">
          <UserOverridesTab
            :org-id="orgId"
            :employee-id="selectedEmployeeId"
          />
          <Divider />
          <EffectivePermissionsView
            :org-id="orgId"
            :employee-id="selectedEmployeeId"
          />
        </template>
        <div v-else class="empty-selection">
          {{ t('permissions.selectUserHint') }}
        </div>
      </TabPanel>
    </TabView>
  </div>
</template>

<style scoped>
.permissions-page {
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

.selector {
  width: 300px;
}

.empty-selection {
  text-align: center;
  padding: 3rem;
  color: var(--p-text-muted-color);
  font-size: 0.95rem;
}
</style>
