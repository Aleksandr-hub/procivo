<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{
  data: { label: string; isOrphan?: boolean }
}>()
</script>

<template>
  <div class="end-node" :class="{ orphan: data.isOrphan }">
    <div class="end-circle" />
    <Handle type="target" :position="Position.Left" />
  </div>
</template>

<style scoped>
.end-node {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
}

.end-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #ef4444;
  border: 3px solid #dc2626;
}

.end-node.orphan .end-circle {
  border: 3px dashed #f97316;
  animation: pulse-border 1.5s ease-in-out infinite;
}

@keyframes pulse-border {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
}
</style>
