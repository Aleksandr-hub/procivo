<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  nodeType: string
}>()

const { t } = useI18n()

const helpText = computed(() => {
  switch (props.nodeType) {
    case 'exclusive_gateway': return t('workflow.gatewayExclusiveHelp')
    case 'parallel_gateway': return t('workflow.gatewayParallelHelp')
    case 'inclusive_gateway': return t('workflow.gatewayInclusiveHelp')
    default: return ''
  }
})

const exampleText = computed(() => {
  switch (props.nodeType) {
    case 'exclusive_gateway': return t('workflow.gatewayExclusiveExample')
    case 'parallel_gateway': return t('workflow.gatewayParallelExample')
    case 'inclusive_gateway': return t('workflow.gatewayInclusiveExample')
    default: return ''
  }
})

const diagram = computed(() => {
  switch (props.nodeType) {
    case 'exclusive_gateway':
      return '  [Review Task]\n       |\n     <XOR>\n    /     \\\nApproved  Rejected\n   |         |\n[Proceed] [Rework]'
    case 'parallel_gateway':
      return '     [Hire]\n        |\n      <AND>\n    /  |  \\\n [A] [B] [C]\n    \\  |  /\n      <AND>\n        |\n    [Continue]'
    case 'inclusive_gateway':
      return '  [Order]\n     |\n   <OR>\n  /    \\\n[A]   [B]\n  \\    /\n   <OR>\n     |\n  [Done]'
    default: return ''
  }
})
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.gatewayConfig') }}</h5>
    <Message severity="info" :closable="false" class="gateway-help">
      {{ helpText }}
    </Message>

    <div v-if="diagram" class="gateway-diagram">
      <pre>{{ diagram }}</pre>
    </div>

    <div v-if="exampleText" class="gateway-example">
      <i class="pi pi-lightbulb" />
      <span>{{ exampleText }}</span>
    </div>
  </div>
</template>

<style scoped>
.config-section h5 {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.gateway-help {
  font-size: 0.8125rem;
}

.gateway-diagram {
  margin: 0.75rem 0;
  background: var(--p-surface-50);
  border: 1px solid var(--p-surface-200);
  border-radius: 6px;
  padding: 0.75rem;
  overflow-x: auto;
}

.gateway-diagram pre {
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.4;
  color: var(--p-text-secondary-color);
  font-family: 'Courier New', monospace;
  text-align: center;
}

.gateway-example {
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
  font-size: 0.8125rem;
  color: var(--p-text-secondary-color);
  line-height: 1.5;
  background: var(--p-yellow-50);
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
}

.gateway-example i {
  color: var(--p-yellow-700);
  margin-top: 0.125rem;
  flex-shrink: 0;
}
</style>
