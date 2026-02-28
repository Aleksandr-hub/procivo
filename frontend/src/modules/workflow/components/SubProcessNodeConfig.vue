<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import type { ProcessDefinitionDTO } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  config: Record<string, unknown>
  orgId: string
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [config: Record<string, unknown>]
}>()

const { t } = useI18n()

const selectedDefinitionId = ref('')
const definitions = ref<ProcessDefinitionDTO[]>([])
const variableMappings = ref<{ parent_var: string; child_var: string }[]>([])

onMounted(async () => {
  try {
    definitions.value = await processDefinitionApi.list(props.orgId, 'published')
  } catch {
    definitions.value = []
  }
})

watch(() => props.config, (cfg) => {
  selectedDefinitionId.value = (cfg.sub_process_definition_id as string) || ''
  const mappings = cfg.variable_mappings as { parent_var: string; child_var: string }[] | undefined
  variableMappings.value = mappings?.length ? mappings.map(m => ({ ...m })) : []
}, { immediate: true })

function addMapping() {
  variableMappings.value.push({ parent_var: '', child_var: '' })
  emitConfig()
}

function removeMapping(index: number) {
  variableMappings.value.splice(index, 1)
  emitConfig()
}

function emitConfig() {
  const filteredMappings = variableMappings.value.filter(m => m.parent_var && m.child_var)
  emit('update', {
    ...props.config,
    sub_process_definition_id: selectedDefinitionId.value || undefined,
    variable_mappings: filteredMappings.length > 0 ? filteredMappings : undefined,
  })
}
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.subProcessConfig') }}</h5>

    <div class="config-field">
      <label>{{ t('workflow.subProcessDefinition') }}</label>
      <Select
        v-model="selectedDefinitionId"
        :options="definitions"
        option-label="name"
        option-value="id"
        :placeholder="t('workflow.subProcessSelectDefinition')"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <div class="config-field">
      <label>{{ t('workflow.subProcessVariableMappings') }}</label>
      <div v-for="(mapping, index) in variableMappings" :key="index" class="mapping-row">
        <InputText
          v-model="mapping.parent_var"
          :placeholder="t('workflow.subProcessParentVar')"
          :disabled="readonly"
          class="mapping-input"
          @update:model-value="emitConfig"
        />
        <span class="mapping-arrow">→</span>
        <InputText
          v-model="mapping.child_var"
          :placeholder="t('workflow.subProcessChildVar')"
          :disabled="readonly"
          class="mapping-input"
          @update:model-value="emitConfig"
        />
        <Button
          v-if="!readonly"
          icon="pi pi-trash"
          text
          severity="danger"
          size="small"
          @click="removeMapping(index)"
        />
      </div>
      <Button
        v-if="!readonly"
        :label="t('workflow.subProcessAddMapping')"
        icon="pi pi-plus"
        text
        size="small"
        @click="addMapping"
      />
    </div>

    <small class="config-hint">{{ t('workflow.subProcessHint') }}</small>
  </div>
</template>

<style scoped>
.config-section h5 {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.config-field {
  margin-bottom: 0.75rem;
}

.config-field label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 500;
}

.mapping-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.mapping-input {
  flex: 1;
}

.mapping-arrow {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.config-hint {
  display: block;
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
  margin-top: 0.25rem;
}
</style>
