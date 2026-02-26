<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useI18n } from 'vue-i18n'
import { useLabelStore } from '@/modules/tasks/stores/label.store'
import type { LabelDTO } from '@/modules/tasks/types/label.types'

const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const labelStore = useLabelStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)

const showDialog = ref(false)
const editingLabel = ref<LabelDTO | null>(null)
const formName = ref('')
const formColor = ref('#6366f1')

onMounted(() => {
  labelStore.fetchLabels(orgId.value)
})

function openCreate() {
  editingLabel.value = null
  formName.value = ''
  formColor.value = '#6366f1'
  showDialog.value = true
}

function openEdit(label: LabelDTO) {
  editingLabel.value = label
  formName.value = label.name
  formColor.value = label.color
  showDialog.value = true
}

async function handleSave() {
  const name = formName.value.trim()
  if (!name) return

  try {
    if (editingLabel.value) {
      await labelStore.updateLabel(orgId.value, editingLabel.value.id, name, formColor.value)
      toast.add({
        severity: 'success',
        summary: t('common.success'),
        detail: t('labels.labelUpdated'),
        life: 3000,
      })
    } else {
      await labelStore.createLabel(orgId.value, name, formColor.value)
      toast.add({
        severity: 'success',
        summary: t('common.success'),
        detail: t('labels.labelCreated'),
        life: 3000,
      })
    }
    showDialog.value = false
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('labels.failedToSave'),
      life: 5000,
    })
  }
}

function confirmDelete(label: LabelDTO) {
  confirm.require({
    message: t('labels.confirmDelete', { name: label.name }),
    header: t('labels.confirmDeleteTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await labelStore.deleteLabel(orgId.value, label.id)
        toast.add({
          severity: 'success',
          summary: t('common.success'),
          detail: t('labels.labelDeleted'),
          life: 3000,
        })
      } catch {
        toast.add({
          severity: 'error',
          summary: t('common.error'),
          detail: t('labels.failedToDelete'),
          life: 5000,
        })
      }
    },
  })
}
</script>

<template>
  <div class="labels-page">
    <div class="page-header">
      <h3>{{ t('labels.title') }}</h3>
      <Button :label="t('labels.createLabel')" icon="pi pi-plus" @click="openCreate" />
    </div>

    <DataTable
      :value="labelStore.labels"
      :loading="labelStore.loading"
      stripedRows
      paginator
      :rows="20"
    >
      <template #empty>
        <div class="empty-table">{{ t('labels.noLabelsFound') }}</div>
      </template>
      <Column :header="t('labels.color')" style="width: 60px">
        <template #body="{ data }">
          <div
            class="color-swatch"
            :style="{ backgroundColor: data.color }"
          />
        </template>
      </Column>
      <Column field="name" :header="t('labels.name')" sortable style="min-width: 200px">
        <template #body="{ data }">
          <Tag
            :value="data.name"
            :style="{ backgroundColor: data.color, color: '#fff' }"
          />
        </template>
      </Column>
      <Column field="createdAt" :header="t('labels.createdAt')" sortable style="width: 160px">
        <template #body="{ data }">
          {{ new Date(data.createdAt).toLocaleDateString() }}
        </template>
      </Column>
      <Column :header="t('tasks.actionsColumn')" style="width: 120px">
        <template #body="{ data }">
          <div class="action-buttons">
            <Button
              icon="pi pi-pencil"
              text
              rounded
              size="small"
              @click="openEdit(data)"
              v-tooltip="t('common.edit')"
            />
            <Button
              icon="pi pi-trash"
              text
              rounded
              size="small"
              severity="danger"
              @click="confirmDelete(data)"
              v-tooltip="t('common.delete')"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Create / Edit Dialog -->
    <Dialog
      :visible="showDialog"
      :header="editingLabel ? t('labels.dialogHeaderEdit') : t('labels.dialogHeaderCreate')"
      modal
      :closable="true"
      :style="{ width: '400px' }"
      @update:visible="showDialog = $event"
    >
      <div class="dialog-form">
        <div class="field">
          <label>{{ t('labels.nameLabel') }}</label>
          <InputText v-model="formName" :placeholder="t('labels.namePlaceholder')" class="w-full" />
        </div>
        <div class="field">
          <label>{{ t('labels.colorLabel') }}</label>
          <div class="color-picker-row">
            <ColorPicker v-model="formColor" />
            <InputText v-model="formColor" style="width: 100px" />
            <Tag
              v-if="formName.trim()"
              :value="formName"
              :style="{ backgroundColor: formColor, color: '#fff' }"
            />
          </div>
        </div>
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showDialog = false" />
        <Button
          :label="editingLabel ? t('common.save') : t('common.create')"
          :disabled="!formName.trim()"
          @click="handleSave"
        />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.labels-page {
  max-width: 800px;
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

.action-buttons {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.color-swatch {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  border: 1px solid var(--p-content-border-color);
}

.dialog-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.color-picker-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
</style>
