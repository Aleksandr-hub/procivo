<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{
  data: { label: string; nodeType?: string; isOrphan?: boolean }
}>()

function nodeIcon(type?: string) {
  switch (type) {
    case 'notification': return 'pi pi-bell'
    case 'webhook': return 'pi pi-globe'
    case 'sub_process': return 'pi pi-box'
    default: return 'pi pi-file'
  }
}
</script>

<template>
  <div class="task-node" :class="{ orphan: data.isOrphan }">
    <Handle type="target" :position="Position.Left" />
    <div class="task-content">
      <i :class="nodeIcon(data.nodeType)" />
      <span>{{ data.label }}</span>
    </div>
    <Handle type="source" :position="Position.Right" />
  </div>
</template>

<style scoped>
.task-node {
  min-width: 120px;
  padding: 8px 16px;
  border-radius: 8px;
  background: #3b82f6;
  color: white;
  border: 2px solid #2563eb;
  font-size: 13px;
}

.task-content {
  display: flex;
  align-items: center;
  gap: 6px;
}

.task-node.orphan {
  border: 2px dashed #ef4444;
  animation: pulse-border 1.5s ease-in-out infinite;
}

@keyframes pulse-border {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
}
</style>
