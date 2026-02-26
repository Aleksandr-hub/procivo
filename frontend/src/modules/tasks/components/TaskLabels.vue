<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import { useLabelStore } from '@/modules/tasks/stores/label.store'

const props = defineProps<{
  orgId: string
  taskId: string
}>()

const toast = useToast()
const labelStore = useLabelStore()
const { t } = useI18n()

const assignedIds = computed(() => new Set(labelStore.taskLabels.map((l) => l.id)))
const availableLabels = computed(() =>
  labelStore.labels.filter((l) => !assignedIds.value.has(l.id)),
)

onMounted(async () => {
  await Promise.all([
    labelStore.labels.length === 0 ? labelStore.fetchLabels(props.orgId) : Promise.resolve(),
    labelStore.fetchTaskLabels(props.orgId, props.taskId),
  ])
})

watch(
  () => props.taskId,
  (newId) => {
    if (newId) {
      labelStore.fetchTaskLabels(props.orgId, newId)
    }
  },
)

async function assign(labelId: string) {
  try {
    await labelStore.assignToTask(props.orgId, props.taskId, labelId)
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('labels.failedToAssign'),
      life: 5000,
    })
  }
}

async function remove(labelId: string) {
  try {
    await labelStore.removeFromTask(props.orgId, props.taskId, labelId)
  } catch {
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: t('labels.failedToRemove'),
      life: 5000,
    })
  }
}
</script>

<template>
  <div class="task-labels">
    <!-- Assigned labels -->
    <div class="labels-section">
      <h4>{{ t('labels.assignedLabels') }}</h4>
      <div v-if="labelStore.taskLabels.length === 0" class="no-labels">
        {{ t('labels.noLabelsAssigned') }}
      </div>
      <div v-else class="labels-list">
        <Tag
          v-for="label in labelStore.taskLabels"
          :key="label.id"
          :value="label.name"
          :style="{ backgroundColor: label.color, color: '#fff', cursor: 'pointer' }"
          removable
          @remove="remove(label.id)"
        />
      </div>
    </div>

    <!-- Available labels to add -->
    <div v-if="availableLabels.length > 0" class="labels-section">
      <h4>{{ t('labels.availableLabels') }}</h4>
      <div class="labels-list">
        <Tag
          v-for="label in availableLabels"
          :key="label.id"
          :value="label.name"
          :style="{
            backgroundColor: label.color + '33',
            color: label.color,
            cursor: 'pointer',
            border: `1px solid ${label.color}`,
          }"
          @click="assign(label.id)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-labels {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.labels-section h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
  color: var(--p-text-muted-color);
}

.labels-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.no-labels {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}
</style>
