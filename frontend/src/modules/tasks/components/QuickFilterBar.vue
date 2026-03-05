<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import InputText from 'primevue/inputtext'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import SelectButton from 'primevue/selectbutton'

const props = defineProps<{
  assigneeOptions: { label: string; value: string }[]
  labelOptions: string[]
}>()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const filterText = defineModel<string>('filterText', { default: '' })
const filterAssigneeId = defineModel<string>('filterAssigneeId', { default: '' })
const filterLabels = defineModel<string[]>('filterLabels', { default: () => [] })
const filterDateRange = defineModel<Date[] | null>('filterDateRange', { default: null })
const swimlaneMode = defineModel<'none' | 'assignee' | 'priority'>('swimlaneMode', { default: 'none' })

const swimlaneOptions = [
  { label: t('kanban.swimlaneNone'), value: 'none' },
  { label: t('kanban.swimlaneAssignee'), value: 'assignee' },
  { label: t('kanban.swimlanePriority'), value: 'priority' },
]

const assigneeOptionsWithAll = [
  { label: t('common.none'), value: '' },
  ...props.assigneeOptions,
]

// Initialize from URL on mount
onMounted(() => {
  filterText.value = (route.query.q as string) ?? ''
  filterAssigneeId.value = (route.query.assignee as string) ?? ''
  filterLabels.value = (route.query.labels as string)?.split(',').filter(Boolean) ?? []
  swimlaneMode.value = (['none', 'assignee', 'priority'].includes(route.query.swimlane as string)
    ? (route.query.swimlane as 'none' | 'assignee' | 'priority')
    : 'none')
  if (route.query.from) {
    filterDateRange.value = [
      new Date(route.query.from as string),
      route.query.to ? new Date(route.query.to as string) : new Date(route.query.from as string),
    ]
  }
})

watch([filterText, filterAssigneeId, filterLabels, filterDateRange, swimlaneMode], () => {
  router.replace({
    query: {
      ...route.query,
      q: filterText.value || undefined,
      assignee: filterAssigneeId.value || undefined,
      labels: filterLabels.value.length ? filterLabels.value.join(',') : undefined,
      from: filterDateRange.value?.[0]?.toISOString().split('T')[0] || undefined,
      to: filterDateRange.value?.[1]?.toISOString().split('T')[0] || undefined,
      swimlane: swimlaneMode.value !== 'none' ? swimlaneMode.value : undefined,
    },
  })
}, { deep: true })
</script>

<template>
  <div class="quick-filter-bar">
    <InputGroup class="filter-search">
      <InputGroupAddon>
        <i class="pi pi-search" />
      </InputGroupAddon>
      <InputText
        v-model="filterText"
        size="small"
        :placeholder="t('kanban.filterSearch')"
      />
    </InputGroup>

    <Select
      v-model="filterAssigneeId"
      :options="assigneeOptionsWithAll"
      option-label="label"
      option-value="value"
      size="small"
      :placeholder="t('kanban.filterAssignee')"
      class="filter-select"
    />

    <MultiSelect
      v-model="filterLabels"
      :options="labelOptions"
      size="small"
      :placeholder="t('kanban.filterLabels')"
      class="filter-select"
    />

    <DatePicker
      v-model="filterDateRange"
      selection-mode="range"
      size="small"
      :placeholder="t('kanban.filterDueDate')"
      show-button-bar
      class="filter-datepicker"
    />

    <SelectButton
      v-model="swimlaneMode"
      :options="swimlaneOptions"
      option-label="label"
      option-value="value"
      size="small"
      class="filter-swimlane"
    />
  </div>
</template>

<style scoped>
.quick-filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: var(--p-surface-card);
  border-radius: 8px;
  border: 1px solid var(--p-content-border-color);
}

.filter-search {
  min-width: 180px;
  max-width: 240px;
}

.filter-select {
  min-width: 140px;
}

.filter-datepicker {
  min-width: 180px;
}

.filter-swimlane {
  margin-left: auto;
}
</style>
