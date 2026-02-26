<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import type { Edge } from '@vue-flow/core'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import FormFieldsBuilder from '@/modules/workflow/components/FormFieldsBuilder.vue'
import type { ProcessDefinitionDetailDTO, FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  edge: Edge
  definition: ProcessDefinitionDetailDTO
  orgId: string
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [data: { name: string; condition_expression: string | null }]
  delete: []
  close: []
}>()

const toast = useToast()
const { t } = useI18n()

const name = ref('')
const actionKey = ref('')
const conditionExpression = ref('')
const formFields = ref<FormFieldDefinition[]>([])

watch(
  () => props.edge,
  (edge) => {
    const tr = props.definition.transitions.find((transition) => transition.id === edge.id)
    name.value = tr?.name || ''
    actionKey.value = tr?.action_key || ''
    conditionExpression.value = tr?.condition_expression || ''
    formFields.value = tr?.form_fields ? [...tr.form_fields] : []
  },
  { immediate: true },
)

const sourceNode = computed(() =>
  props.definition.nodes.find((n) => n.id === props.edge.source),
)

const sourceFormFields = computed<FormFieldDefinition[]>(() => {
  const config = sourceNode.value?.config
  if (!config || !Array.isArray(config.formFields)) return []
  return config.formFields as FormFieldDefinition[]
})

function insertCondition(fieldName: string, optionValue: string) {
  conditionExpression.value = `${fieldName} == '${optionValue}'`
}

async function save() {
  try {
    await processDefinitionApi.updateTransition(props.orgId, props.definition.id, props.edge.id, {
      source_node_id: props.edge.source,
      target_node_id: props.edge.target,
      name: name.value || null,
      action_key: actionKey.value || null,
      condition_expression: conditionExpression.value || null,
      form_fields: formFields.value.length > 0 ? formFields.value : undefined,
    })
    emit('update', {
      name: name.value,
      condition_expression: conditionExpression.value || null,
    })
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.transitionUpdated'), life: 2000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

async function remove() {
  try {
    await processDefinitionApi.removeTransition(props.orgId, props.definition.id, props.edge.id)
    emit('delete')
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.transitionDeleted'), life: 2000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}
</script>

<template>
  <div class="transition-property-panel">
    <div class="panel-header">
      <h4>{{ t('workflow.transitionProperties') }}</h4>
      <Button icon="pi pi-times" text size="small" @click="emit('close')" />
    </div>

    <div class="panel-body">
      <div class="form-group">
        <label>{{ t('workflow.transitionName') }}</label>
        <InputText
          v-model="name"
          :disabled="readonly"
          :placeholder="t('workflow.transitionNamePlaceholder')"
          class="w-full"
        />
      </div>

      <div class="form-group">
        <label>{{ t('workflow.actionKey') }}</label>
        <InputText
          v-model="actionKey"
          :disabled="readonly"
          :placeholder="t('workflow.actionKeyPlaceholder')"
          class="w-full"
        />
        <small class="help-text">{{ t('workflow.actionKeyHelp') }}</small>
      </div>

      <div class="form-group">
        <label>{{ t('workflow.transitionCondition') }}</label>
        <InputText
          v-model="conditionExpression"
          :disabled="readonly"
          :placeholder="t('workflow.transitionConditionPlaceholder')"
          class="w-full"
        />
        <small class="help-text">{{ t('workflow.transitionConditionHelp') }}</small>
      </div>

      <div v-if="sourceFormFields.length > 0" class="fields-hint">
        <small class="hint-label">
          <i class="pi pi-info-circle" />
          {{ t('workflow.transitionAvailableFields') }}
        </small>
        <div v-for="field in sourceFormFields" :key="field.name" class="field-item">
          <code class="field-name">{{ field.name }}</code>
          <span class="field-label-text">{{ field.label }}</span>
          <div v-if="field.options && field.options.length > 0" class="field-options">
            <span
              v-for="opt in field.options"
              :key="opt"
              class="option-chip"
              :class="{ clickable: !readonly }"
              @click="!readonly && insertCondition(field.name, opt)"
            >
              {{ opt }}
            </span>
          </div>
        </div>
        <small class="help-text">{{ t('workflow.transitionConditionHelpDetailed') }}</small>
      </div>

      <div v-else-if="sourceNode" class="fields-hint">
        <small class="hint-label hint-warn">
          <i class="pi pi-exclamation-triangle" />
          {{ t('workflow.transitionNoFormFields') }}
        </small>
      </div>

      <Divider />

      <FormFieldsBuilder
        :fields="formFields"
        :readonly="readonly"
        @update="formFields = $event"
      />

      <div v-if="!readonly" class="panel-actions">
        <Button :label="t('common.save')" size="small" @click="save" />
        <Button :label="t('common.delete')" size="small" severity="danger" text @click="remove" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.transition-property-panel {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 340px;
  background-color: var(--p-surface-0, #ffffff);
  border-left: 1px solid var(--p-surface-border);
  box-shadow: -4px 0 12px rgb(0 0 0 / 0.08);
  z-index: 20;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--p-surface-border);
}

.panel-header h4 {
  margin: 0;
}

.panel-body {
  padding: 1rem;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.help-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.fields-hint {
  background: var(--p-surface-50);
  border: 1px solid var(--p-surface-200);
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 1rem;
}

.hint-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--p-primary-color);
  margin-bottom: 0.5rem;
}

.hint-warn {
  color: var(--p-orange-600);
}

.field-item {
  margin-bottom: 0.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--p-surface-200);
}

.field-item:last-of-type {
  margin-bottom: 0.375rem;
  padding-bottom: 0;
  border-bottom: none;
}

.field-name {
  font-size: 0.8125rem;
  background: var(--p-surface-200);
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
}

.field-label-text {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  margin-left: 0.375rem;
}

.field-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-top: 0.375rem;
}

.option-chip {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 10px;
  background: var(--p-primary-50);
  color: var(--p-primary-700);
  border: 1px solid var(--p-primary-200);
}

.option-chip.clickable {
  cursor: pointer;
}

.option-chip.clickable:hover {
  background: var(--p-primary-100);
}

.panel-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}
</style>
