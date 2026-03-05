<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Chart from 'primevue/chart'
import type { ProcessBoardMetricsDTO } from '@/modules/tasks/types/board.types'

const props = defineProps<{
  metrics: ProcessBoardMetricsDTO
}>()

const { t } = useI18n()

const primaryColor = ref('#6366f1')

onMounted(() => {
  const color = getComputedStyle(document.documentElement).getPropertyValue('--p-primary-color').trim()
  if (color) primaryColor.value = color
})

const sparklineData = computed(() => ({
  labels: props.metrics.completedByDay.map((d) => d.date),
  datasets: [
    {
      data: props.metrics.completedByDay.map((d) => d.count),
      fill: false,
      tension: 0.4,
      borderColor: primaryColor.value,
      pointRadius: 0,
      borderWidth: 2,
    },
  ],
}))

const sparklineOptions = {
  plugins: {
    legend: { display: false },
    tooltip: { enabled: false },
  },
  scales: {
    x: { display: false },
    y: { display: false },
  },
  responsive: true,
  maintainAspectRatio: false,
}
</script>

<template>
  <div class="process-board-metrics">
    <div class="metric-item">
      <span class="metric-value">{{ metrics.totalActive }}</span>
      <span class="metric-label">{{ t('processBoard.totalActive') }}</span>
    </div>

    <div class="metric-divider" />

    <div class="metric-item sparkline-item">
      <div class="sparkline-container">
        <Chart type="line" :data="sparklineData" :options="sparklineOptions" />
      </div>
      <span class="metric-label">{{ t('processBoard.throughput') }}</span>
    </div>
  </div>
</template>

<style scoped>
.process-board-metrics {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0.75rem 1rem;
  background: var(--p-surface-card);
  border: 1px solid var(--p-content-border-color);
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.metric-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--p-primary-color);
  line-height: 1;
}

.metric-label {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  white-space: nowrap;
}

.metric-divider {
  width: 1px;
  height: 2rem;
  background: var(--p-content-border-color);
}

.sparkline-item {
  flex-direction: column;
  align-items: flex-start;
  gap: 0.25rem;
}

.sparkline-container {
  width: 120px;
  height: 40px;
  position: relative;
}
</style>
