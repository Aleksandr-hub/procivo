<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{
  data: { label: string; isOrphan?: boolean }
}>()
</script>

<template>
  <div class="timer-node" :class="{ orphan: data.isOrphan }">
    <Handle type="target" :position="Position.Left" />
    <div class="timer-circle">
      <i class="pi pi-clock" />
    </div>
    <Handle type="source" :position="Position.Right" />
  </div>
</template>

<style scoped>
.timer-node {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
}

.timer-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #8b5cf6;
  border: 2px solid #7c3aed;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
}

.timer-node.orphan .timer-circle {
  border: 2px dashed #ef4444;
  animation: pulse-border 1.5s ease-in-out infinite;
}

@keyframes pulse-border {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
}
</style>
