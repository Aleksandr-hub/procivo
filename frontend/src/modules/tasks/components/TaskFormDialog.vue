<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { TaskDTO, TaskPriority } from '@/modules/tasks/types/task.types'

const props = defineProps<{
  visible: boolean
  task?: TaskDTO | null
}>()

const emit = defineEmits<{
  hide: []
  save: [data: {
    title: string
    description: string | null
    priority: TaskPriority
    due_date: string | null
    estimated_hours: number | null
  }]
}>()

const { t } = useI18n()

const title = ref('')
const description = ref<string | null>(null)
const priority = ref<TaskPriority>('medium')
const dueDate = ref<Date | null>(null)
const estimatedHours = ref<number | null>(null)

const priorityOptions = [
  { label: t('tasks.priorityLow'), value: 'low' as TaskPriority },
  { label: t('tasks.priorityMedium'), value: 'medium' as TaskPriority },
  { label: t('tasks.priorityHigh'), value: 'high' as TaskPriority },
  { label: t('tasks.priorityCritical'), value: 'critical' as TaskPriority },
]

watch(
  () => props.visible,
  (val) => {
    if (val && props.task) {
      title.value = props.task.title
      description.value = props.task.description
      priority.value = props.task.priority
      dueDate.value = props.task.dueDate ? new Date(props.task.dueDate) : null
      estimatedHours.value = props.task.estimatedHours
    } else if (val) {
      title.value = ''
      description.value = null
      priority.value = 'medium'
      dueDate.value = null
      estimatedHours.value = null
    }
  },
)

function onSave() {
  emit('save', {
    title: title.value,
    description: description.value || null,
    priority: priority.value,
    due_date: dueDate.value ? dueDate.value.toISOString() : null,
    estimated_hours: estimatedHours.value,
  })
}

const isEdit = ref(false)
watch(
  () => props.task,
  (val) => {
    isEdit.value = !!val
  },
)
</script>

<template>
  <Dialog
    :visible="visible"
    :header="task ? t('tasks.dialogHeaderEdit') : t('tasks.dialogHeaderCreate')"
    modal
    :style="{ width: '500px' }"
    @update:visible="!$event && emit('hide')"
  >
    <div class="form-grid">
      <div class="field">
        <label>{{ t('tasks.titleLabel') }}</label>
        <InputText v-model="title" :placeholder="t('tasks.titlePlaceholder')" class="w-full" />
      </div>

      <div class="field">
        <label>{{ t('tasks.descriptionLabel') }}</label>
        <Textarea
          v-model="description"
          :placeholder="t('tasks.descriptionPlaceholder')"
          rows="3"
          class="w-full"
        />
      </div>

      <div class="field">
        <label>{{ t('tasks.priorityLabel') }}</label>
        <Select
          v-model="priority"
          :options="priorityOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>

      <div class="field">
        <label>{{ t('tasks.dueDateLabel') }}</label>
        <DatePicker
          v-model="dueDate"
          :placeholder="t('tasks.dueDatePlaceholder')"
          dateFormat="yy-mm-dd"
          showIcon
          class="w-full"
        />
      </div>

      <div class="field">
        <label>{{ t('tasks.estimatedHoursLabel') }}</label>
        <InputNumber
          v-model="estimatedHours"
          :placeholder="t('tasks.estimatedHoursPlaceholder')"
          :min="0"
          :maxFractionDigits="1"
          class="w-full"
        />
      </div>
    </div>

    <template #footer>
      <Button :label="t('common.cancel')" text @click="emit('hide')" />
      <Button
        :label="task ? t('common.update') : t('common.create')"
        :disabled="!title.trim()"
        @click="onSave"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.form-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.field label {
  font-weight: 600;
  font-size: 0.875rem;
}

.w-full {
  width: 100%;
}
</style>
