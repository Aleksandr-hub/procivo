<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { permissionApi } from '@/modules/organization/api/permission.api'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import type {
  ProcessDefinitionAccessDTO,
  DepartmentTreeDTO,
} from '@/modules/organization/types/organization.types'

const props = defineProps<{
  orgId: string
  definitionId: string
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
}>()

const toast = useToast()
const { t } = useI18n()
const roleStore = useRoleStore()
const deptStore = useDepartmentStore()

const accessRules = ref<ProcessDefinitionAccessDTO[]>([])
const loading = ref(false)
const saving = ref(false)

// Editable local state
const viewRules = ref<{ departmentId: string | null; roleId: string | null }[]>([])
const startRules = ref<{ departmentId: string | null; roleId: string | null }[]>([])

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
const roleOptions = computed(() =>
  roleStore.roles.map((r) => ({ label: r.name, value: r.id })),
)

const dialogVisible = computed({
  get: () => props.visible,
  set: (val: boolean) => emit('update:visible', val),
})

async function fetchAccess() {
  if (!props.definitionId) return
  loading.value = true
  try {
    accessRules.value = await permissionApi.getProcessDefinitionAccess(
      props.orgId,
      props.definitionId,
    )
    viewRules.value = accessRules.value
      .filter((r) => r.accessType === 'view')
      .map((r) => ({ departmentId: r.departmentId, roleId: r.roleId }))
    startRules.value = accessRules.value
      .filter((r) => r.accessType === 'start')
      .map((r) => ({ departmentId: r.departmentId, roleId: r.roleId }))
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

watch(
  () => props.visible,
  (v) => {
    if (v) {
      fetchAccess()
      if (roleStore.roles.length === 0) roleStore.fetchRoles(props.orgId)
      if (deptStore.tree.length === 0) deptStore.fetchTree(props.orgId)
    }
  },
)

function addViewRule() {
  viewRules.value.push({ departmentId: null, roleId: null })
}

function removeViewRule(index: number) {
  viewRules.value.splice(index, 1)
}

function addStartRule() {
  startRules.value.push({ departmentId: null, roleId: null })
}

function removeStartRule(index: number) {
  startRules.value.splice(index, 1)
}

async function save() {
  saving.value = true
  try {
    const rules = [
      ...viewRules.value.map((r) => ({
        departmentId: r.departmentId,
        roleId: r.roleId,
        accessType: 'view' as const,
      })),
      ...startRules.value.map((r) => ({
        departmentId: r.departmentId,
        roleId: r.roleId,
        accessType: 'start' as const,
      })),
    ]

    await permissionApi.setProcessDefinitionAccess(props.orgId, props.definitionId, rules)

    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('permissions.saved'),
      life: 3000,
    })
    dialogVisible.value = false
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('permissions.failedToSave'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    :header="t('permissions.processAccess.title')"
    modal
    style="width: 650px"
  >
    <div v-if="loading" class="loading-state">
      <ProgressSpinner style="width: 40px; height: 40px" />
    </div>
    <template v-else>
      <!-- View Access -->
      <div class="access-section">
        <div class="section-header">
          <h4>{{ t('permissions.processAccess.viewAccess') }}</h4>
          <Button icon="pi pi-plus" text size="small" @click="addViewRule" />
        </div>
        <div v-if="viewRules.length === 0" class="open-indicator">
          <Tag :value="t('permissions.processAccess.openToAll')" severity="success" />
        </div>
        <div v-for="(rule, idx) in viewRules" :key="`view-${idx}`" class="rule-row">
          <Select
            v-model="rule.departmentId"
            :options="departmentOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.processAccess.anyDepartment')"
            showClear
            class="rule-select"
          />
          <Select
            v-model="rule.roleId"
            :options="roleOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.processAccess.anyRole')"
            showClear
            class="rule-select"
          />
          <Button
            icon="pi pi-trash"
            text
            rounded
            size="small"
            severity="danger"
            @click="removeViewRule(idx)"
          />
        </div>
      </div>

      <Divider />

      <!-- Start Access -->
      <div class="access-section">
        <div class="section-header">
          <h4>{{ t('permissions.processAccess.startAccess') }}</h4>
          <Button icon="pi pi-plus" text size="small" @click="addStartRule" />
        </div>
        <div v-if="startRules.length === 0" class="open-indicator">
          <Tag :value="t('permissions.processAccess.openToAll')" severity="success" />
        </div>
        <div v-for="(rule, idx) in startRules" :key="`start-${idx}`" class="rule-row">
          <Select
            v-model="rule.departmentId"
            :options="departmentOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.processAccess.anyDepartment')"
            showClear
            class="rule-select"
          />
          <Select
            v-model="rule.roleId"
            :options="roleOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('permissions.processAccess.anyRole')"
            showClear
            class="rule-select"
          />
          <Button
            icon="pi pi-trash"
            text
            rounded
            size="small"
            severity="danger"
            @click="removeStartRule(idx)"
          />
        </div>
      </div>
    </template>

    <template #footer>
      <Button :label="t('common.cancel')" text @click="dialogVisible = false" />
      <Button :label="t('common.save')" :loading="saving" @click="save" />
    </template>
  </Dialog>
</template>

<style scoped>
.loading-state {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.access-section {
  margin-bottom: 0.5rem;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.section-header h4 {
  margin: 0;
}

.open-indicator {
  padding: 0.5rem 0;
}

.rule-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.rule-select {
  flex: 1;
}
</style>
