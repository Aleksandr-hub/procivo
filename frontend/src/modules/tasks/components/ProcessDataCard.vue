<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

export interface ProcessVariable {
  key: string
  label: string
  value: unknown
  sourceStageName: string
}

const props = withDefaults(
  defineProps<{
    variables: ProcessVariable[]
    initiallyExpanded?: boolean
  }>(),
  { initiallyExpanded: false },
)

const { t } = useI18n()

const MAX_VISIBLE = 4
const expanded = ref(props.initiallyExpanded)

const visibleVariables = computed(() => {
  if (expanded.value || props.variables.length <= MAX_VISIBLE) {
    return props.variables
  }
  return props.variables.slice(0, MAX_VISIBLE)
})

const hasMore = computed(() => props.variables.length > MAX_VISIBLE)

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return '\u2014'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}
</script>

<template>
  <div class="process-data-card">
    <div class="data-header">
      <i class="pi pi-list-check" />
      <span class="data-title">{{ t('process.processData') }}</span>
    </div>

    <div class="data-grid">
      <div
        v-for="variable in visibleVariables"
        :key="variable.key"
        class="data-item"
      >
        <span class="data-label">{{ variable.label }}</span>
        <span class="data-value">{{ formatValue(variable.value) }}</span>
        <span class="data-source">{{ t('process.fromStage', { stage: variable.sourceStageName }) }}</span>
      </div>
    </div>

    <Button
      v-if="hasMore"
      :label="expanded ? t('process.collapse') : t('process.showAll', { count: variables.length })"
      :icon="expanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
      text
      size="small"
      class="toggle-btn"
      @click="expanded = !expanded"
    />
  </div>
</template>

<style scoped>
.process-data-card {
  border: 1px solid var(--p-surface-200);
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
  background: var(--p-surface-0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

:root.p-dark .process-data-card {
  border-color: var(--p-surface-600);
  background: var(--p-surface-800);
}

.data-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  color: var(--p-text-muted-color);
}

.data-header .pi {
  font-size: 0.85rem;
}

.data-title {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.data-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem 1.5rem;
}

.data-item {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.data-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--p-text-color);
}

.data-value {
  font-size: 0.85rem;
  color: var(--p-text-color);
  word-break: break-word;
}

.data-source {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.toggle-btn {
  margin-top: 0.75rem;
}

@media (max-width: 640px) {
  .data-grid {
    grid-template-columns: 1fr;
  }
}
</style>
