<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import WorkflowDesigner from '@/modules/workflow/components/WorkflowDesigner.vue'
import { getApiErrorMessage } from '@/shared/utils/api-error'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const store = useProcessDefinitionStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)
const definitionId = computed(() => route.params.definitionId as string)

onMounted(async () => {
  await store.fetchDefinition(orgId.value, definitionId.value)
})

function goBack() {
  router.push({ name: 'process-definitions', params: { orgId: orgId.value } })
}

async function handlePublish() {
  try {
    await store.publishDefinition(orgId.value, definitionId.value)
    await store.fetchDefinition(orgId.value, definitionId.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.definitionPublished'), life: 3000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.publishFailed')), life: 5000 })
  }
}

async function handleRevertToDraft() {
  try {
    await store.revertToDraft(orgId.value, definitionId.value)
    await store.fetchDefinition(orgId.value, definitionId.value)
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.definitionRevertedToDraft'), life: 3000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

async function onDefinitionChanged() {
  await store.fetchDefinition(orgId.value, definitionId.value)
}
</script>

<template>
  <div class="process-designer-page">
    <div class="page-header">
      <div class="header-left">
        <Button icon="pi pi-arrow-left" text @click="goBack" />
        <h3>{{ store.currentDefinition?.name || t('workflow.designer') }}</h3>
        <Tag v-if="store.currentDefinition" :value="t('workflow.status_' + store.currentDefinition.status)" :severity="store.currentDefinition.status === 'published' ? 'success' : 'info'" />
      </div>
      <div class="header-right">
        <Button v-if="store.currentDefinition?.status === 'published'" :label="t('workflow.revertToDraft')" icon="pi pi-pencil" severity="warn" outlined @click="handleRevertToDraft" />
        <Button v-if="store.currentDefinition?.status === 'draft'" :label="t('workflow.publish')" icon="pi pi-check" severity="success" @click="handlePublish" />
      </div>
    </div>

    <WorkflowDesigner v-if="store.currentDefinition" :definition="store.currentDefinition" :org-id="orgId" @definition-changed="onDefinitionChanged" />
    <div v-else-if="store.loading" class="loading">
      <i class="pi pi-spin pi-spinner" style="font-size: 2rem" />
    </div>
  </div>
</template>

<style scoped>
.process-designer-page {
  height: calc(100vh - 120px);
  display: flex;
  flex-direction: column;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-left h3 {
  margin: 0;
}

.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
}
</style>
