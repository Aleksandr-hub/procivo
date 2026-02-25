<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import { positionApi } from '@/modules/organization/api/position.api'
import type { DepartmentTreeDTO, PositionDTO } from '@/modules/organization/types/organization.types'
import DepartmentFormDialog from '@/modules/organization/components/DepartmentFormDialog.vue'
import PositionFormDialog from '@/modules/organization/components/PositionFormDialog.vue'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const deptStore = useDepartmentStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)

const selectedNodeKey = ref<string | null>(null)
const selectedDept = ref<DepartmentTreeDTO | null>(null)
const positions = ref<PositionDTO[]>([])
const positionsLoading = ref(false)

const showDeptDialog = ref(false)
const editingDept = ref<DepartmentTreeDTO | null>(null)
const parentIdForNew = ref<string | null>(null)

const showPosDialog = ref(false)
const editingPos = ref<PositionDTO | null>(null)

onMounted(() => {
  deptStore.fetchTree(orgId.value)
})

function mapTreeToNodes(items: DepartmentTreeDTO[]): object[] {
  return items.map((item) => ({
    key: item.id,
    label: item.name,
    data: item,
    children: item.children.length > 0 ? mapTreeToNodes(item.children) : undefined,
    icon: 'pi pi-folder',
  }))
}

const treeNodes = computed(() => mapTreeToNodes(deptStore.tree))

async function onNodeSelect(node: { key: string; data: DepartmentTreeDTO }) {
  selectedNodeKey.value = node.key
  selectedDept.value = node.data
  await loadPositions(node.key)
}

async function loadPositions(deptId: string) {
  positionsLoading.value = true
  try {
    positions.value = await positionApi.list(orgId.value, deptId)
  } finally {
    positionsLoading.value = false
  }
}

function openCreateDept(parentId: string | null = null) {
  editingDept.value = null
  parentIdForNew.value = parentId
  showDeptDialog.value = true
}

function openEditDept(dept: DepartmentTreeDTO) {
  editingDept.value = dept
  parentIdForNew.value = null
  showDeptDialog.value = true
}

function confirmDeleteDept(dept: DepartmentTreeDTO) {
  confirm.require({
    message: t('departments.confirmDeleteDept', { name: dept.name }),
    header: t('departments.confirmDelete'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await deptStore.deleteDepartment(orgId.value, dept.id)
        if (selectedDept.value?.id === dept.id) {
          selectedDept.value = null
          positions.value = []
        }
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('departments.departmentDeleted'), life: 3000 })
      } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { error?: string } } }
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: axiosError.response?.data?.error || t('departments.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}

async function handleDeptSaved() {
  showDeptDialog.value = false
  if (selectedDept.value) {
    await loadPositions(selectedDept.value.id)
  }
}

function openCreatePosition() {
  editingPos.value = null
  showPosDialog.value = true
}

function openEditPosition(pos: PositionDTO) {
  editingPos.value = pos
  showPosDialog.value = true
}

function confirmDeletePosition(pos: PositionDTO) {
  confirm.require({
    message: t('departments.confirmDeletePosition', { name: pos.name }),
    header: t('departments.confirmDelete'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await positionApi.delete(orgId.value, pos.id)
        if (selectedDept.value) {
          await loadPositions(selectedDept.value.id)
        }
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('departments.positionDeleted'), life: 3000 })
      } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { error?: string } } }
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: axiosError.response?.data?.error || t('departments.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}

async function handlePosSaved() {
  showPosDialog.value = false
  if (selectedDept.value) {
    await loadPositions(selectedDept.value.id)
  }
}
</script>

<template>
  <div class="departments-page">
    <div class="dept-tree-panel">
      <div class="panel-header">
        <h3>{{ t('departments.title') }}</h3>
        <Button icon="pi pi-plus" text rounded size="small" @click="openCreateDept(null)" v-tooltip="t('departments.addRootDepartment')" />
      </div>
      <div v-if="deptStore.loading" class="loading-tree">
        <Skeleton v-for="i in 4" :key="i" height="2rem" class="mb-2" />
      </div>
      <Tree
        v-else-if="treeNodes.length > 0"
        :value="treeNodes"
        selectionMode="single"
        v-model:selectionKeys="selectedNodeKey"
        @node-select="onNodeSelect"
        class="dept-tree"
      />
      <div v-else class="empty-tree">
        <p>{{ t('departments.noDepartments') }}</p>
        <Button :label="t('departments.createFirstDepartment')" icon="pi pi-plus" size="small" @click="openCreateDept(null)" />
      </div>
    </div>

    <div class="dept-detail-panel">
      <template v-if="selectedDept">
        <div class="panel-header">
          <div>
            <h3>{{ selectedDept.name }}</h3>
            <small class="text-muted">{{ selectedDept.code }}</small>
          </div>
          <div class="header-actions">
            <Button icon="pi pi-plus" text rounded size="small" @click="openCreateDept(selectedDept!.id)" v-tooltip="t('departments.addChildDepartment')" />
            <Button icon="pi pi-pencil" text rounded size="small" @click="openEditDept(selectedDept!)" v-tooltip="t('common.edit')" />
            <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="confirmDeleteDept(selectedDept!)" v-tooltip="t('common.delete')" />
          </div>
        </div>

        <p v-if="selectedDept.description" class="dept-description">{{ selectedDept.description }}</p>

        <div class="positions-section">
          <div class="panel-header">
            <h4>{{ t('departments.positions') }}</h4>
            <Button icon="pi pi-plus" text rounded size="small" @click="openCreatePosition" v-tooltip="t('departments.addPosition')" />
          </div>

          <DataTable
            :value="positions"
            :loading="positionsLoading"
            size="small"
            stripedRows
          >
            <template #empty>{{ t('departments.noPositions') }}</template>
            <Column field="name" :header="t('departments.name')" />
            <Column field="description" :header="t('departments.description')" />
            <Column field="isHead" :header="t('departments.head')" style="width: 80px">
              <template #body="{ data }">
                <Tag v-if="data.isHead" :value="t('departments.head')" severity="info" />
              </template>
            </Column>
            <Column style="width: 100px">
              <template #body="{ data }">
                <div class="action-buttons">
                  <Button icon="pi pi-pencil" text rounded size="small" @click="openEditPosition(data)" />
                  <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="confirmDeletePosition(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>

      <div v-else class="empty-detail">
        <i class="pi pi-sitemap" style="font-size: 2rem; color: var(--p-text-muted-color)" />
        <p>{{ t('departments.selectToSeeDetails') }}</p>
      </div>
    </div>

    <DepartmentFormDialog
      :visible="showDeptDialog"
      :org-id="orgId"
      :department="editingDept"
      :parent-id="parentIdForNew"
      @hide="showDeptDialog = false"
      @saved="handleDeptSaved"
    />

    <PositionFormDialog
      v-if="selectedDept"
      :visible="showPosDialog"
      :org-id="orgId"
      :department-id="selectedDept.id"
      :position="editingPos"
      @hide="showPosDialog = false"
      @saved="handlePosSaved"
    />
  </div>
</template>

<style scoped>
.departments-page {
  display: flex;
  gap: 1.5rem;
  height: calc(100vh - 160px);
}

.dept-tree-panel {
  width: 320px;
  min-width: 280px;
  background: var(--p-surface-card);
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-border-radius);
  padding: 1rem;
  overflow-y: auto;
}

.dept-detail-panel {
  flex: 1;
  background: var(--p-surface-card);
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-border-radius);
  padding: 1.25rem;
  overflow-y: auto;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.panel-header h3,
.panel-header h4 {
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.25rem;
}

.dept-tree {
  border: none;
  padding: 0;
}

.dept-description {
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
  margin-bottom: 1.5rem;
}

.text-muted {
  color: var(--p-text-muted-color);
}

.positions-section {
  margin-top: 1.5rem;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.loading-tree {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.empty-tree,
.empty-detail {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2rem;
  text-align: center;
  color: var(--p-text-muted-color);
}

.mb-2 {
  margin-bottom: 0.5rem;
}
</style>
