<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ProcessBoardInstanceDTO } from '@/modules/tasks/types/board.types'

const props = defineProps<{
  instance: ProcessBoardInstanceDTO
  isActiveColumn: boolean
}>()

defineEmits<{
  click: []
  dragstart: [event: DragEvent]
}>()

const { t } = useI18n()

const startedDateFormatted = computed(() => {
  return new Date(props.instance.startedAt).toLocaleDateString()
})

const stageSeverity = computed(() => {
  if (props.isActiveColumn) return 'success'
  return 'info'
})

const hasMultipleActiveTokens = computed(() => {
  return props.instance.activeNodeId === null && props.instance.status === 'running'
})

const isDraggable = computed(() => {
  return !!props.instance.activeTaskId
})
</script>

<template>
  <div
    class="process-board-card"
    :class="{ 'is-active': isActiveColumn, 'not-draggable': !isDraggable }"
    :draggable="isDraggable"
    @click="$emit('click')"
    @dragstart="isDraggable && $emit('dragstart', $event)"
  >
    <!-- Instance name -->
    <div class="card-title">{{ instance.name }}</div>

    <!-- Stage badge -->
    <div class="card-stage">
      <Tag
        v-if="hasMultipleActiveTokens"
        :value="t('processBoard.multipleStages')"
        severity="warn"
        rounded
        class="stage-tag"
      />
      <Tag
        v-else-if="instance.activeNodeName"
        :value="instance.activeNodeName"
        :severity="stageSeverity"
        rounded
        class="stage-tag"
      />
    </div>

    <!-- Footer row -->
    <div class="card-footer">
      <span class="started-date">
        <i class="pi pi-clock" />
        {{ startedDateFormatted }}
      </span>
      <span v-if="instance.activeTaskAssigneeName" class="assignee">
        <i class="pi pi-user" />
        {{ instance.activeTaskAssigneeName }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.process-board-card {
  background: var(--p-surface-card);
  border: 1px solid var(--p-content-border-color);
  border-radius: 6px;
  padding: 0.75rem;
  cursor: grab;
  transition: box-shadow 0.15s;
}

.process-board-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.process-board-card:active {
  cursor: grabbing;
}

.process-board-card.is-active {
  border-color: var(--p-green-400);
  border-left-width: 3px;
}

.process-board-card.not-draggable {
  cursor: pointer;
  opacity: 0.85;
}

.card-title {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  line-height: 1.3;
  word-break: break-word;
}

.card-stage {
  margin-bottom: 0.5rem;
}

.stage-tag {
  font-size: 0.75rem;
}

.card-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.started-date,
.assignee {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.started-date i,
.assignee i {
  font-size: 0.7rem;
}
</style>
