<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { employeeApi } from '@/modules/organization/api/employee.api'
import AddEmployeeToDeptDialog from '@/modules/organization/components/AddEmployeeToDeptDialog.vue'
import { useI18n } from 'vue-i18n'
import type { OrgChartNodeDTO } from '@/modules/organization/types/organization.types'

interface ChartTreeNode {
  key: string
  type: string
  data: OrgChartNodeDTO
  children: ChartTreeNode[]
}

const route = useRoute()
const toast = useToast()

const orgId = computed(() => route.params.orgId as string)
const { t } = useI18n()
const loading = ref(false)
const chartNodes = ref<OrgChartNodeDTO[]>([])
const treeValue = ref<ChartTreeNode | null>(null)
const selectedNode = ref<OrgChartNodeDTO | null>(null)

// Add Employee state
const showAddDialog = ref(false)
const addDeptId = ref('')
const addDeptName = ref('')

// Set Manager state
const showManagerDialog = ref(false)
const managerTarget = ref<OrgChartNodeDTO | null>(null)
const newManagerId = ref<string | null>(null)
const savingManager = ref(false)

function toTreeNode(node: OrgChartNodeDTO): ChartTreeNode {
  return {
    key: `${node.type}-${node.id}`,
    type: node.type,
    data: node,
    children: (node.children ?? []).map(toTreeNode),
  }
}

function buildChart(nodes: OrgChartNodeDTO[]): ChartTreeNode | null {
  if (nodes.length === 0) return null

  if (nodes.length === 1) {
    return toTreeNode(nodes[0])
  }

  return {
    key: 'root',
    type: 'org',
    data: {} as OrgChartNodeDTO,
    children: nodes.map(toTreeNode),
  }
}

function flattenPersonNodes(nodes: OrgChartNodeDTO[]): OrgChartNodeDTO[] {
  const result: OrgChartNodeDTO[] = []
  for (const node of nodes) {
    if (node.type === 'person') {
      result.push(node)
    }
    if (node.children?.length) {
      result.push(...flattenPersonNodes(node.children))
    }
  }
  return result
}

const allPersons = computed(() => flattenPersonNodes(chartNodes.value))

const managerOptions = computed(() => {
  if (!managerTarget.value) return []
  return allPersons.value
    .filter((p) => p.id !== managerTarget.value!.id)
    .map((p) => ({
      label: `${p.label} (#${p.employeeNumber})`,
      value: p.id,
      position: p.positionName ?? '',
    }))
})

function findPersonLabel(id: string | null | undefined): string | null {
  if (!id) return null
  const person = allPersons.value.find((p) => p.id === id)
  return person ? person.label : null
}

async function fetchOrgChart() {
  loading.value = true
  try {
    chartNodes.value = await employeeApi.getOrgChart(orgId.value)
    treeValue.value = buildChart(chartNodes.value)
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('orgChart.failedToLoad'),
      life: 5000,
    })
  } finally {
    loading.value = false
  }
}

function selectNode(node: ChartTreeNode) {
  if (node.type !== 'person') return
  selectedNode.value = node.data
}

function countPersonChildren(node: OrgChartNodeDTO): number {
  return (node.children ?? []).filter((c) => c.type === 'person').length
}

function openAddEmployee(node: OrgChartNodeDTO) {
  addDeptId.value = node.id
  addDeptName.value = node.label
  showAddDialog.value = true
}

async function handleEmployeeAdded() {
  showAddDialog.value = false
  await fetchOrgChart()
}

function openSetManager(node: OrgChartNodeDTO) {
  selectedNode.value = null
  managerTarget.value = node
  newManagerId.value = node.managerId ?? null
  showManagerDialog.value = true
}

async function saveManager() {
  if (!managerTarget.value) return

  savingManager.value = true
  try {
    await employeeApi.setManager(orgId.value, managerTarget.value.id, newManagerId.value)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('orgChart.managerUpdated'),
      life: 3000,
    })
    showManagerDialog.value = false
    managerTarget.value = null
    await fetchOrgChart()
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('orgChart.failedToSetManager'),
      life: 5000,
    })
  } finally {
    savingManager.value = false
  }
}

async function removeManager() {
  if (!managerTarget.value) return

  savingManager.value = true
  try {
    await employeeApi.setManager(orgId.value, managerTarget.value.id, null)
    toast.add({
      severity: 'success',
      summary: t('common.success'),
      detail: t('orgChart.managerRemoved'),
      life: 3000,
    })
    showManagerDialog.value = false
    managerTarget.value = null
    await fetchOrgChart()
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('orgChart.failedToRemoveManager'),
      life: 5000,
    })
  } finally {
    savingManager.value = false
  }
}

onMounted(fetchOrgChart)
</script>

<template>
  <div class="org-chart-page">
    <div class="page-header">
      <h3>{{ t('orgChart.title') }}</h3>
      <Button icon="pi pi-refresh" text rounded @click="fetchOrgChart" :loading="loading" />
    </div>

    <div v-if="loading" class="loading-state">
      <ProgressSpinner style="width: 48px; height: 48px" />
    </div>

    <div v-else-if="!treeValue" class="empty-state">
      <i class="pi pi-share-alt" style="font-size: 3rem; color: var(--p-text-muted-color)"></i>
      <p>{{ t('orgChart.noDepartmentsFound') }}</p>
      <p class="hint">{{ t('orgChart.createDepartmentsHint') }}</p>
    </div>

    <div v-else class="chart-container">
      <OrganizationChart :value="treeValue" @node-select="selectNode" selectionMode="single">
        <template #org>
          <div class="org-root-node">
            <i class="pi pi-building"></i>
            <span>{{ t('orgChart.organization') }}</span>
          </div>
        </template>

        <template #department="{ node }">
          <div class="department-node">
            <div class="dept-icon">
              <i class="pi pi-sitemap"></i>
            </div>
            <div class="dept-info">
              <div class="dept-name">{{ node.data.label }}</div>
              <div class="dept-code">{{ node.data.departmentCode }}</div>
            </div>
            <Button
              icon="pi pi-user-plus"
              text
              rounded
              size="small"
              class="dept-add-btn"
              v-tooltip.top="t('orgChart.addEmployee')"
              @click.stop="openAddEmployee(node.data)"
            />
          </div>
        </template>

        <template #person="{ node }">
          <div class="person-node" :class="{ 'is-head': node.data.isHead }">
            <div class="person-avatar">
              <i class="pi pi-user"></i>
            </div>
            <div class="person-info">
              <div class="person-name">{{ node.data.label }}</div>
              <div class="person-position">{{ node.data.positionName }}</div>
            </div>
          </div>
        </template>
      </OrganizationChart>
    </div>

    <!-- Employee Detail Dialog -->
    <Dialog
      v-model:visible="selectedNode"
      :header="selectedNode?.label ?? ''"
      :style="{ width: '420px' }"
      modal
    >
      <div v-if="selectedNode" class="node-details">
        <div class="detail-row">
          <span class="detail-label">{{ t('orgChart.employeeNumber') }}</span>
          <span class="detail-value">{{ selectedNode.employeeNumber }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ t('orgChart.email') }}</span>
          <span class="detail-value">{{ selectedNode.email }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ t('orgChart.position') }}</span>
          <span class="detail-value">
            {{ selectedNode.positionName }}
            <Tag v-if="selectedNode.isHead" value="Head" severity="info" class="head-tag" />
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ t('orgChart.department') }}</span>
          <span class="detail-value">{{ selectedNode.departmentName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ t('orgChart.manager') }}</span>
          <span class="detail-value">
            {{ findPersonLabel(selectedNode.managerId) || t('common.none') }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ t('orgChart.directReports') }}</span>
          <span class="detail-value">{{ countPersonChildren(selectedNode) }}</span>
        </div>

        <div class="detail-actions">
          <Button
            :label="t('orgChart.setManager')"
            icon="pi pi-arrow-up"
            size="small"
            outlined
            @click="openSetManager(selectedNode!)"
          />
        </div>
      </div>
    </Dialog>

    <!-- Add Employee Dialog -->
    <AddEmployeeToDeptDialog
      :visible="showAddDialog"
      :org-id="orgId"
      :department-id="addDeptId"
      :department-name="addDeptName"
      @hide="showAddDialog = false"
      @added="handleEmployeeAdded"
    />

    <!-- Set Manager Dialog -->
    <Dialog
      v-model:visible="showManagerDialog"
      :header="t('orgChart.setManagerFor', { name: managerTarget?.label ?? '' })"
      :style="{ width: '420px' }"
      modal
    >
      <div v-if="managerTarget" class="manager-form">
        <div class="field">
          <label>{{ t('orgChart.manager') }}</label>
          <Select
            v-model="newManagerId"
            :options="managerOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('orgChart.selectManager')"
            showClear
            filter
            fluid
          >
            <template #option="{ option }">
              <div>
                <div>{{ option.label }}</div>
                <div style="font-size: 0.75rem; color: var(--p-text-muted-color)">
                  {{ option.position }}
                </div>
              </div>
            </template>
          </Select>
        </div>

        <div class="dialog-footer">
          <Button
            v-if="managerTarget.managerId"
            :label="t('orgChart.removeManager')"
            severity="danger"
            text
            size="small"
            :loading="savingManager"
            @click="removeManager"
          />
          <span style="flex: 1"></span>
          <Button :label="t('common.cancel')" severity="secondary" text @click="showManagerDialog = false" />
          <Button :label="t('common.save')" icon="pi pi-check" :loading="savingManager" @click="saveManager" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.org-chart-page {
  max-width: 100%;
  overflow-x: auto;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.page-header h3 {
  margin: 0;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 4rem 0;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 0;
  color: var(--p-text-muted-color);
}

.empty-state p {
  margin: 0.5rem 0 0;
}

.empty-state .hint {
  font-size: 0.875rem;
}

.chart-container {
  overflow-x: auto;
  padding: 1rem 0;
}

.org-root-node {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-weight: 600;
}

.department-node {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  min-width: 160px;
}

.dept-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: var(--p-surface-100);
  color: var(--p-text-muted-color);
  flex-shrink: 0;
}

.dept-add-btn {
  margin-left: auto;
  flex-shrink: 0;
}

.dept-info {
  text-align: left;
}

.dept-name {
  font-weight: 600;
  font-size: 0.875rem;
}

.dept-code {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  font-family: monospace;
}

.person-node {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  min-width: 180px;
  cursor: pointer;
}

.person-node.is-head {
  border-left: 3px solid var(--p-primary-color);
}

.person-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--p-primary-color);
  color: var(--p-primary-contrast-color);
  flex-shrink: 0;
  font-size: 0.8rem;
}

.person-info {
  text-align: left;
  min-width: 0;
}

.person-name {
  font-weight: 600;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.person-position {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.node-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.detail-value {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.head-tag {
  font-size: 0.7rem;
}

.detail-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.5rem;
  border-top: 1px solid var(--p-surface-200);
}

.manager-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-top: 0.5rem;
}

.manager-form .field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.manager-form .field label {
  font-weight: 600;
  font-size: 0.875rem;
}

.dialog-footer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-top: 0.5rem;
}
</style>
