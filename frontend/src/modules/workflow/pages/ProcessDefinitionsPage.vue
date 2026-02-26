<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import type {
  ProcessDefinitionDTO,
  CreateProcessDefinitionPayload,
} from '@/modules/workflow/types/process-definition.types'
import { definitionStatusSeverity } from '@/shared/utils/status-severity'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import ProcessTemplateGallery from '@/modules/workflow/components/ProcessTemplateGallery.vue'
import type { ProcessTemplate } from '@/modules/workflow/data/process-templates'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const store = useProcessDefinitionStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const showDialog = ref(false)
const showTemplateDialog = ref(false)
const importingTemplate = ref(false)
const editingDef = ref<ProcessDefinitionDTO | null>(null)
const formName = ref('')
const formDescription = ref<string | null>(null)

onMounted(async () => {
  await store.fetchDefinitions(orgId.value)
})

function openCreate() {
  editingDef.value = null
  formName.value = ''
  formDescription.value = null
  showDialog.value = true
}

function openEdit(def: ProcessDefinitionDTO) {
  editingDef.value = def
  formName.value = def.name
  formDescription.value = def.description
  showDialog.value = true
}

async function handleSave() {
  if (!formName.value.trim()) return
  const data: CreateProcessDefinitionPayload = {
    name: formName.value,
    description: formDescription.value,
  }
  try {
    if (editingDef.value) {
      await store.updateDefinition(orgId.value, editingDef.value.id, data)
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.definitionUpdated'), life: 3000 })
    } else {
      await store.createDefinition(orgId.value, data)
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.definitionCreated'), life: 3000 })
    }
    showDialog.value = false
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

function openDesigner(def: ProcessDefinitionDTO) {
  router.push({ name: 'workflow-designer', params: { orgId: orgId.value, definitionId: def.id } })
}

async function publishDef(def: ProcessDefinitionDTO) {
  try {
    await store.publishDefinition(orgId.value, def.id)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.definitionPublished'), life: 3000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.publishFailed')), life: 5000 })
  }
}

async function importTemplate(template: ProcessTemplate) {
  importingTemplate.value = true
  try {
    const defResult = await processDefinitionApi.create(orgId.value, {
      name: t(template.nameKey),
      description: t(template.descriptionKey),
    })
    const defId = defResult.id

    const nodeIdMap = new Map<number, string>()
    for (let i = 0; i < template.nodes.length; i++) {
      const node = template.nodes[i]
      const result = await processDefinitionApi.addNode(orgId.value, defId, {
        type: node.type,
        name: node.nameKey,
        config: node.config ?? {},
        position_x: node.position_x,
        position_y: node.position_y,
      })
      nodeIdMap.set(i, result.id)
    }

    for (const tr of template.transitions) {
      const sourceId = nodeIdMap.get(tr.sourceIndex)
      const targetId = nodeIdMap.get(tr.targetIndex)
      if (!sourceId || !targetId) continue
      await processDefinitionApi.addTransition(orgId.value, defId, {
        source_node_id: sourceId,
        target_node_id: targetId,
        name: tr.nameKey ?? null,
        condition_expression: tr.condition_expression ?? null,
      })
    }

    showTemplateDialog.value = false
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.templateImported'), life: 3000 })
    router.push({ name: 'workflow-designer', params: { orgId: orgId.value, definitionId: defId } })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  } finally {
    importingTemplate.value = false
  }
}

function confirmDelete(def: ProcessDefinitionDTO) {
  confirm.require({
    message: t('workflow.confirmDelete', { name: def.name }),
    header: t('common.confirm'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.deleteDefinition(orgId.value, def.id)
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.definitionDeleted'), life: 3000 })
      } catch {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('workflow.failedToDelete'), life: 5000 })
      }
    },
  })
}

</script>

<template>
  <div class="process-definitions-page">
    <div class="page-header">
      <h3>{{ t('workflow.processDefinitions') }}</h3>
      <div class="header-actions">
        <Button :label="t('workflow.createFromTemplate')" icon="pi pi-th-large" severity="secondary" outlined @click="showTemplateDialog = true" />
        <Button :label="t('workflow.createDefinition')" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <DataTable :value="store.definitions" :loading="store.loading" stripedRows paginator :rows="20">
      <template #empty>
        <div class="empty-table">{{ t('workflow.noDefinitionsFound') }}</div>
      </template>
      <Column field="name" :header="t('workflow.name')" sortable />
      <Column field="status" :header="t('workflow.status')" sortable>
        <template #body="{ data }">
          <Tag :value="t('workflow.status_' + data.status)" :severity="definitionStatusSeverity(data.status)" />
        </template>
      </Column>
      <Column field="created_at" :header="t('workflow.createdAt')" sortable />
      <Column :header="t('common.actions')">
        <template #body="{ data }">
          <Button icon="pi pi-sitemap" text size="small" v-tooltip="t('workflow.openDesigner')" @click="openDesigner(data)" />
          <Button v-if="data.status === 'draft'" icon="pi pi-check" text size="small" severity="success" v-tooltip="t('workflow.publish')" @click="publishDef(data)" />
          <Button icon="pi pi-pencil" text size="small" @click="openEdit(data)" />
          <Button icon="pi pi-trash" text size="small" severity="danger" @click="confirmDelete(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showDialog" :header="editingDef ? t('workflow.editDefinition') : t('workflow.createDefinition')" modal :style="{ width: '450px' }">
      <div class="form-group">
        <label>{{ t('workflow.name') }}</label>
        <InputText v-model="formName" class="w-full" />
      </div>
      <div class="form-group">
        <label>{{ t('workflow.description') }}</label>
        <Textarea v-model="formDescription" class="w-full" rows="3" />
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showDialog = false" />
        <Button :label="t('common.save')" @click="handleSave" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showTemplateDialog" :header="t('workflow.templateGalleryTitle')" modal :style="{ width: '700px' }">
      <ProcessTemplateGallery @select="importTemplate" @close="showTemplateDialog = false" />
      <div v-if="importingTemplate" class="importing-overlay">
        <i class="pi pi-spin pi-spinner" style="font-size: 1.5rem" />
        <span>{{ t('workflow.templateImporting') }}</span>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.process-definitions-page {
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

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.importing-overlay {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: var(--p-text-secondary-color);
}
</style>
