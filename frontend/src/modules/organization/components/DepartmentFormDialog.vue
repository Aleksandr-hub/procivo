<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import type { DepartmentTreeDTO } from '@/modules/organization/types/organization.types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  visible: boolean
  orgId: string
  department: DepartmentTreeDTO | null
  parentId: string | null
}>()

const emit = defineEmits<{
  hide: []
  saved: []
}>()

const toast = useToast()
const deptStore = useDepartmentStore()
const { t } = useI18n()

const name = ref('')
const code = ref('')
const description = ref('')
const sortOrder = ref(0)
const submitted = ref(false)
const saving = ref(false)

const isEdit = () => !!props.department

watch(
  () => props.visible,
  (val) => {
    if (val) {
      submitted.value = false
      if (props.department) {
        name.value = props.department.name
        code.value = props.department.code
        description.value = props.department.description || ''
        sortOrder.value = props.department.sortOrder
      } else {
        name.value = ''
        code.value = ''
        description.value = ''
        sortOrder.value = 0
      }
    }
  },
)

async function onSubmit() {
  submitted.value = true
  if (!name.value || (!isEdit() && !code.value)) return

  saving.value = true
  try {
    if (isEdit() && props.department) {
      await deptStore.updateDepartment(props.orgId, props.department.id, {
        name: name.value,
        description: description.value || null,
        sort_order: sortOrder.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('dialog.departmentForm.departmentUpdated'), life: 3000 })
    } else {
      await deptStore.createDepartment(props.orgId, {
        name: name.value,
        code: code.value,
        parent_id: props.parentId,
        description: description.value || null,
        sort_order: sortOrder.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('dialog.departmentForm.departmentCreated'), life: 3000 })
    }
    emit('saved')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.departmentForm.operationFailed'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="isEdit() ? t('dialog.departmentForm.headerEdit') : t('dialog.departmentForm.headerCreate')"
    :style="{ width: '450px' }"
    modal
    @update:visible="!$event && emit('hide')"
  >
    <form @submit.prevent="onSubmit" class="dialog-form">
      <div class="field">
        <label for="deptName">{{ t('dialog.departmentForm.name') }}</label>
        <InputText
          id="deptName"
          v-model="name"
          :placeholder="t('dialog.departmentForm.namePlaceholder')"
          :invalid="submitted && !name"
          fluid
        />
      </div>

      <div v-if="!isEdit()" class="field">
        <label for="deptCode">{{ t('dialog.departmentForm.code') }}</label>
        <InputText
          id="deptCode"
          v-model="code"
          :placeholder="t('dialog.departmentForm.codePlaceholder')"
          :invalid="submitted && !code"
          fluid
        />
        <small class="field-help">{{ t('dialog.departmentForm.codeHelp') }}</small>
      </div>

      <div class="field">
        <label for="deptDesc">{{ t('dialog.departmentForm.description') }}</label>
        <Textarea
          id="deptDesc"
          v-model="description"
          :placeholder="t('dialog.departmentForm.descriptionPlaceholder')"
          rows="3"
          fluid
        />
      </div>

      <div class="field">
        <label for="deptSort">{{ t('dialog.departmentForm.sortOrder') }}</label>
        <InputNumber id="deptSort" v-model="sortOrder" :min="0" fluid />
      </div>

      <div class="dialog-footer">
        <Button :label="t('common.cancel')" severity="secondary" text @click="emit('hide')" />
        <Button type="submit" :label="isEdit() ? t('common.update') : t('common.create')" :loading="saving" />
      </div>
    </form>
  </Dialog>
</template>

<style scoped>
.dialog-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-top: 0.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field label {
  font-weight: 600;
  font-size: 0.875rem;
}

.field-help {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 0.5rem;
}
</style>
