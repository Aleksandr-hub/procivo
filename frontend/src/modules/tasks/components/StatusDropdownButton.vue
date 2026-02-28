<script setup lang="ts">
import { ref } from 'vue'
import type { StatusAction } from '@/modules/tasks/types/task.types'

defineProps<{
  statusLabel: string
  statusSeverity: string
  actions: StatusAction[]
  disabled?: boolean
}>()

const emit = defineEmits<{
  action: [action: StatusAction]
}>()

const popoverRef = ref()

function togglePopover(event: Event) {
  popoverRef.value?.toggle(event)
}

function selectAction(action: StatusAction) {
  popoverRef.value?.hide()
  emit('action', action)
}
</script>

<template>
  <div class="status-dropdown">
    <Button
      v-if="actions.length > 0 && !disabled"
      :label="statusLabel"
      :severity="statusSeverity"
      size="small"
      icon="pi pi-chevron-down"
      iconPos="right"
      @click="togglePopover"
    />
    <Tag v-else :value="statusLabel" :severity="statusSeverity" />

    <Popover ref="popoverRef">
      <div class="status-action-list">
        <div
          v-for="action in actions"
          :key="action.key"
          class="status-action-item"
          @click="selectAction(action)"
        >
          <span>{{ action.label }}</span>
          <i v-if="action.formFields.length > 0" class="pi pi-file-edit action-form-icon" />
        </div>
      </div>
    </Popover>
  </div>
</template>

<style scoped>
.status-dropdown {
  display: inline-flex;
}

.status-action-list {
  min-width: 200px;
}

.status-action-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  border-radius: var(--p-border-radius);
  transition: background-color 0.15s;
  font-size: 0.875rem;
}

.status-action-item:hover {
  background: var(--p-content-hover-background);
}

.action-form-icon {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  margin-left: 0.5rem;
}
</style>
