<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { positionApi } from '@/modules/organization/api/position.api'
import type { PositionDTO } from '@/modules/organization/types/organization.types'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  visible: boolean
  orgId: string
  departmentId: string
  position: PositionDTO | null
}>()

const emit = defineEmits<{
  hide: []
  saved: []
}>()

const toast = useToast()
const { t } = useI18n()

const name = ref('')
const description = ref('')
const sortOrder = ref(0)
const isHead = ref(false)
const submitted = ref(false)
const saving = ref(false)

const isEdit = () => !!props.position

watch(
  () => props.visible,
  (val) => {
    if (val) {
      submitted.value = false
      if (props.position) {
        name.value = props.position.name
        description.value = props.position.description || ''
        sortOrder.value = props.position.sortOrder
        isHead.value = props.position.isHead
      } else {
        name.value = ''
        description.value = ''
        sortOrder.value = 0
        isHead.value = false
      }
    }
  },
)

async function onSubmit() {
  submitted.value = true
  if (!name.value) return

  saving.value = true
  try {
    if (isEdit() && props.position) {
      await positionApi.update(props.orgId, props.position.id, {
        name: name.value,
        description: description.value || null,
        sort_order: sortOrder.value,
        is_head: isHead.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('dialog.positionForm.positionUpdated'), life: 3000 })
    } else {
      await positionApi.create(props.orgId, {
        department_id: props.departmentId,
        name: name.value,
        description: description.value || null,
        sort_order: sortOrder.value,
        is_head: isHead.value,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('dialog.positionForm.positionCreated'), life: 3000 })
    }
    emit('saved')
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.positionForm.operationFailed'),
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
    :header="isEdit() ? t('dialog.positionForm.headerEdit') : t('dialog.positionForm.headerCreate')"
    :style="{ width: '450px' }"
    modal
    @update:visible="!$event && emit('hide')"
  >
    <form @submit.prevent="onSubmit" class="dialog-form">
      <div class="field">
        <label for="posName">{{ t('dialog.positionForm.name') }}</label>
        <InputText
          id="posName"
          v-model="name"
          :placeholder="t('dialog.positionForm.namePlaceholder')"
          :invalid="submitted && !name"
          fluid
        />
      </div>

      <div class="field">
        <label for="posDesc">{{ t('dialog.positionForm.description') }}</label>
        <Textarea
          id="posDesc"
          v-model="description"
          :placeholder="t('dialog.positionForm.descriptionPlaceholder')"
          rows="2"
          fluid
        />
      </div>

      <div class="field-row">
        <div class="field">
          <label for="posSort">{{ t('dialog.positionForm.sortOrder') }}</label>
          <InputNumber id="posSort" v-model="sortOrder" :min="0" fluid />
        </div>
        <div class="field checkbox-field">
          <Checkbox id="posHead" v-model="isHead" :binary="true" />
          <label for="posHead">{{ t('dialog.positionForm.headPosition') }}</label>
        </div>
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

.field-row {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.field-row .field {
  flex: 1;
}

.checkbox-field {
  flex-direction: row !important;
  align-items: center;
  gap: 0.5rem;
  padding-bottom: 0.25rem;
}

.checkbox-field label {
  font-weight: normal;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 0.5rem;
}
</style>
