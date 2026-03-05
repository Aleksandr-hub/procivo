<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  config: Record<string, unknown>
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [config: Record<string, unknown>]
}>()

const { t } = useI18n()

const timerType = ref<string>((props.config.timer_type as string) ?? 'duration')
const duration = ref<number | null>(null)
const unit = ref('hours')
const dateValue = ref<Date | null>(null)

const timerTypeOptions = [
  { label: t('workflow.timerModeDuration'), value: 'duration' },
  { label: t('workflow.timerModeDate'), value: 'date' },
]

const unitOptions = [
  { label: t('workflow.timerUnitMinutes'), value: 'minutes' },
  { label: t('workflow.timerUnitHours'), value: 'hours' },
  { label: t('workflow.timerUnitDays'), value: 'days' },
]

watch(() => props.config, (cfg) => {
  timerType.value = (cfg.timer_type as string) || 'duration'
  duration.value = (cfg.timer_duration as number) ?? null
  unit.value = (cfg.timer_unit as string) || 'hours'
  const dateExpr = cfg.date_expression as string | undefined
  dateValue.value = dateExpr ? new Date(dateExpr) : null
}, { immediate: true })

function emitConfig() {
  if (timerType.value === 'date') {
    emit('update', {
      ...props.config,
      timer_type: 'date',
      date_expression: dateValue.value?.toISOString() ?? '',
    })
  }
  else {
    emit('update', {
      ...props.config,
      timer_type: 'duration',
      timer_duration: duration.value,
      timer_unit: unit.value,
      duration: buildDurationString(),
    })
  }
}

function buildDurationString(): string | undefined {
  if (!duration.value) return undefined
  switch (unit.value) {
    case 'minutes': return `PT${duration.value}M`
    case 'hours': return `PT${duration.value}H`
    case 'days': return `P${duration.value}D`
    default: return undefined
  }
}
</script>

<template>
  <div class="config-section">
    <h5>{{ t('workflow.timerConfig') }}</h5>

    <div class="config-field">
      <label>{{ t('workflow.timerMode') }}</label>
      <SelectButton
        v-model="timerType"
        :options="timerTypeOptions"
        option-label="label"
        option-value="value"
        :disabled="readonly"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>

    <template v-if="timerType === 'duration'">
      <div class="config-field">
        <label>{{ t('workflow.timerDuration') }}</label>
        <InputNumber
          v-model="duration"
          :disabled="readonly"
          :min="1"
          class="w-full"
          @update:model-value="emitConfig"
        />
      </div>

      <div class="config-field">
        <label>{{ t('workflow.timerUnit') }}</label>
        <Select
          v-model="unit"
          :options="unitOptions"
          option-label="label"
          option-value="value"
          :disabled="readonly"
          class="w-full"
          @update:model-value="emitConfig"
        />
      </div>
    </template>

    <div v-if="timerType === 'date'" class="config-field">
      <label>{{ t('workflow.timerDate') }}</label>
      <DatePicker
        v-model="dateValue"
        :disabled="readonly"
        showTime
        hourFormat="24"
        class="w-full"
        @update:model-value="emitConfig"
      />
    </div>
  </div>
</template>

<style scoped>
.config-section h5 {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}

.config-field {
  margin-bottom: 0.75rem;
}

.config-field label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 500;
}
</style>
