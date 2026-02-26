<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{
  data: { label: string; nodeType?: string; isOrphan?: boolean }
}>()

function gatewaySymbol(type?: string) {
  switch (type) {
    case 'exclusive_gateway': return 'X'
    case 'parallel_gateway': return '+'
    case 'inclusive_gateway': return 'O'
    default: return '?'
  }
}
</script>

<template>
  <div class="gateway-node" :class="{ orphan: data.isOrphan }">
    <Handle type="target" :position="Position.Left" />
    <div class="diamond">
      <span class="symbol">{{ gatewaySymbol(data.nodeType) }}</span>
    </div>
    <Handle type="source" :position="Position.Right" />
  </div>
</template>

<style scoped>
.gateway-node {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
}

.diamond {
  width: 40px;
  height: 40px;
  background: #f59e0b;
  border: 2px solid #d97706;
  transform: rotate(45deg);
  display: flex;
  align-items: center;
  justify-content: center;
}

.symbol {
  transform: rotate(-45deg);
  font-weight: bold;
  font-size: 16px;
  color: white;
}

.gateway-node.orphan .diamond {
  border: 2px dashed #ef4444;
  animation: pulse-border 1.5s ease-in-out infinite;
}

@keyframes pulse-border {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
}
</style>
