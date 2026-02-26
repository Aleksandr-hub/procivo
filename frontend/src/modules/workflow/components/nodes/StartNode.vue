<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{
  data: { label: string; isOrphan?: boolean }
}>()
</script>

<template>
  <div class="start-node" :class="{ orphan: data.isOrphan }">
    <div class="start-circle" />
    <Handle type="source" :position="Position.Right" />
  </div>
</template>

<style scoped>
.start-node {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
}

.start-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #22c55e;
  border: 2px solid #16a34a;
}

.start-node.orphan .start-circle {
  border: 2px dashed #ef4444;
  animation: pulse-border 1.5s ease-in-out infinite;
}

@keyframes pulse-border {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
}
</style>
