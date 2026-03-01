<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useProcessDefinitionStore } from '@/modules/workflow/stores/process-definition.store'
import type { ProcessDefinitionVersionDTO } from '@/modules/workflow/types/process-definition.types'
import { getApiErrorMessage } from '@/shared/utils/api-error'

const props = defineProps<{
  visible: boolean
  orgId: string
  definitionId: string
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  migrated: []
}>()

const { t } = useI18n()
const toast = useToast()
const confirm = useConfirm()
const store = useProcessDefinitionStore()

const drawerVisible = computed({
  get: () => props.visible,
  set: (val: boolean) => emit('update:visible', val),
})

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString()
}

function totalRunningOnOtherVersions(targetVersion: ProcessDefinitionVersionDTO): number {
  return store.versions.filter((v) => v.id !== targetVersion.id).reduce((sum, v) => sum + v.running_instance_count, 0)
}

function confirmMigrate(version: ProcessDefinitionVersionDTO) {
  confirm.require({
    message: t('workflow.migrateConfirmMessage', {
      version: version.version_number,
      count: totalRunningOnOtherVersions(version),
    }),
    header: t('workflow.migrateConfirmTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-warning',
    acceptLabel: t('workflow.migrate'),
    rejectLabel: t('common.cancel'),
    accept: () => executeMigrate(version),
  })
}

async function executeMigrate(version: ProcessDefinitionVersionDTO) {
  try {
    await store.migrateInstances(props.orgId, props.definitionId, version.id)
    toast.add({
      severity: 'success',
      summary: t('workflow.migrateSuccess'),
      detail: t('workflow.migrateSuccessDetail', { version: version.version_number }),
      life: 3000,
    })
    emit('migrated')
  } catch (error: unknown) {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: getApiErrorMessage(error, t('workflow.migrateFailed')),
      life: 5000,
    })
  }
}
</script>

<template>
  <Drawer v-model:visible="drawerVisible" :header="t('workflow.versionHistory')" position="right" class="version-history-drawer">
    <DataTable :value="store.versions" :rows="20" data-key="id">
      <Column field="version_number" :header="t('workflow.version')">
        <template #body="{ data }">
          <Tag :value="'v' + data.version_number" severity="info" />
        </template>
      </Column>
      <Column field="published_at" :header="t('workflow.publishedAt')">
        <template #body="{ data }">
          {{ formatDate(data.published_at) }}
        </template>
      </Column>
      <Column field="running_instance_count" :header="t('workflow.runningInstances')">
        <template #body="{ data }">
          <Tag
            :value="String(data.running_instance_count)"
            :severity="data.running_instance_count > 0 ? 'warn' : 'secondary'"
          />
        </template>
      </Column>
      <Column :header="t('common.actions')">
        <template #body="{ data }">
          <Button
            :label="t('workflow.migrateToVersion')"
            icon="pi pi-arrow-right-arrow-left"
            severity="warn"
            size="small"
            text
            :disabled="data.version_number === store.currentVersion"
            @click="confirmMigrate(data)"
          />
        </template>
      </Column>
    </DataTable>
    <ConfirmDialog />
  </Drawer>
</template>

<style scoped>
.version-history-drawer {
  width: 500px;
}
</style>
