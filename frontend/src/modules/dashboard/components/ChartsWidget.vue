<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Chart from 'primevue/chart'
import type { DashboardStatsDTO } from '@/modules/dashboard/types/dashboard.types'

const props = defineProps<{
  stats: DashboardStatsDTO | null
}>()

const { t } = useI18n()

// Resolved CSS variable colors for dark mode compatibility
const colors = ref({
  blue400: '#60a5fa',
  orange400: '#fb923c',
  purple400: '#c084fc',
  green400: '#4ade80',
  red400: '#f87171',
  surface400: '#94a3b8',
  surface300: '#cbd5e1',
  textColor: '#1e293b',
  textMutedColor: '#64748b',
  primaryColor: '#6366f1',
})

onMounted(() => {
  const style = getComputedStyle(document.documentElement)
  const get = (v: string) => style.getPropertyValue(v).trim()

  colors.value = {
    blue400: get('--p-blue-400') || colors.value.blue400,
    orange400: get('--p-orange-400') || colors.value.orange400,
    purple400: get('--p-purple-400') || colors.value.purple400,
    green400: get('--p-green-400') || colors.value.green400,
    red400: get('--p-red-400') || colors.value.red400,
    surface400: get('--p-surface-400') || colors.value.surface400,
    surface300: get('--p-surface-300') || colors.value.surface300,
    textColor: get('--p-text-color') || colors.value.textColor,
    textMutedColor: get('--p-text-muted-color') || colors.value.textMutedColor,
    primaryColor: get('--p-primary-color') || colors.value.primaryColor,
  }
})

const statusColorMap: Record<string, string> = {
  open: 'blue400',
  in_progress: 'orange400',
  review: 'purple400',
  done: 'green400',
  blocked: 'red400',
  draft: 'surface400',
  cancelled: 'surface300',
}

const donutData = computed(() => {
  if (!props.stats) return null
  const statusKeys = Object.keys(props.stats.tasks_by_status)
  return {
    labels: statusKeys.map((key) => t(`taskStatus.${key}`, key)),
    datasets: [
      {
        data: Object.values(props.stats.tasks_by_status),
        backgroundColor: statusKeys.map(
          (key) => colors.value[statusColorMap[key] as keyof typeof colors.value] || colors.value.surface400,
        ),
      },
    ],
  }
})

const donutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        color: colors.value.textColor,
        font: { size: 11 },
        padding: 8,
      },
    },
  },
}))

const lineData = computed(() => {
  if (!props.stats) return null
  return {
    labels: props.stats.tasks_completed_by_day.map((d) => d.day),
    datasets: [
      {
        label: t('dashboard.completedTasks'),
        data: props.stats.tasks_completed_by_day.map((d) => d.cnt),
        borderColor: colors.value.primaryColor,
        backgroundColor: colors.value.primaryColor + '33',
        tension: 0.4,
        fill: false,
        pointRadius: 3,
      },
    ],
  }
})

const lineOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    x: {
      ticks: {
        color: colors.value.textMutedColor,
        font: { size: 10 },
        maxRotation: 0,
        autoSkip: true,
        maxTicksLimit: 7,
      },
      grid: { display: false },
    },
    y: {
      ticks: {
        color: colors.value.textMutedColor,
        font: { size: 10 },
        stepSize: 1,
      },
      beginAtZero: true,
    },
  },
}))

const barData = computed(() => {
  if (!props.stats) return null
  const ps = props.stats.processes_by_status
  return {
    labels: [t('workflow.instanceStatus_running'), t('workflow.instanceStatus_completed'), t('workflow.instanceStatus_cancelled')],
    datasets: [
      {
        label: t('dashboard.processes'),
        data: [ps['running'] ?? 0, ps['completed'] ?? 0, ps['cancelled'] ?? 0],
        backgroundColor: [colors.value.blue400, colors.value.green400, colors.value.surface400],
        borderRadius: 4,
      },
    ],
  }
})

const barOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    x: {
      ticks: {
        color: colors.value.textMutedColor,
        font: { size: 11 },
      },
      grid: { display: false },
    },
    y: {
      ticks: {
        color: colors.value.textMutedColor,
        font: { size: 10 },
        stepSize: 1,
      },
      beginAtZero: true,
    },
  },
}))
</script>

<template>
  <div class="charts-widget">
    <div v-if="!stats" class="empty-state">
      <i class="pi pi-chart-bar empty-icon" />
      <p>{{ t('dashboard.noData') }}</p>
    </div>

    <template v-else>
      <div class="chart-section">
        <div class="chart-title">{{ t('dashboard.tasksByStatus') }}</div>
        <div class="chart-container">
          <Chart v-if="donutData" type="doughnut" :data="donutData" :options="donutOptions" />
        </div>
      </div>

      <div class="chart-section">
        <div class="chart-title">{{ t('dashboard.completionTrend') }}</div>
        <div class="chart-container">
          <Chart v-if="lineData" type="line" :data="lineData" :options="lineOptions" />
        </div>
      </div>

      <div class="chart-section">
        <div class="chart-title">{{ t('dashboard.processCompletionRate') }}</div>
        <div class="chart-container">
          <Chart v-if="barData" type="bar" :data="barData" :options="barOptions" />
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.charts-widget {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  overflow-y: auto;
  max-height: 400px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 0.75rem;
  color: var(--p-text-muted-color);
}

.empty-icon {
  font-size: 2rem;
}

.chart-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.chart-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.chart-container {
  max-height: 220px;
  min-height: 160px;
  position: relative;
}
</style>
