<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import Chart from 'primevue/chart'

const props = defineProps<{
  label: string
  value: number | string
  subtitle?: string
  icon: string
  trend?: number[]
  color?: string
}>()

const resolvedColor = ref('#3B82F6')

onMounted(() => {
  const c = props.color ?? 'var(--color-primary)'
  if (c.startsWith('var(--')) {
    const varName = c.slice(4, -1)
    resolvedColor.value =
      getComputedStyle(document.documentElement).getPropertyValue(varName).trim() || '#3B82F6'
  } else {
    resolvedColor.value = c
  }
})

const sparklineData = computed(() => {
  if (!props.trend || props.trend.length === 0) return null
  return {
    labels: props.trend.map((_, i) => String(i)),
    datasets: [
      {
        data: props.trend,
        borderColor: resolvedColor.value,
        backgroundColor: resolvedColor.value + '1a',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointRadius: 0,
        pointHoverRadius: 0,
      },
    ],
  }
})

const sparklineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: false },
  },
  scales: {
    x: { display: false },
    y: { display: false },
  },
}
</script>

<template>
  <div class="kpi-card">
    <div class="kpi-header">
      <div class="kpi-icon-wrap" :style="{ backgroundColor: resolvedColor + '1a' }">
        <i :class="icon" :style="{ color: resolvedColor }" />
      </div>
      <span class="kpi-label">{{ label }}</span>
    </div>
    <div class="kpi-value">{{ value }}</div>
    <div v-if="subtitle" class="kpi-subtitle">{{ subtitle }}</div>
    <div v-if="sparklineData" class="kpi-sparkline">
      <Chart type="line" :data="sparklineData" :options="sparklineOptions" />
    </div>
  </div>
</template>

<style scoped>
.kpi-card {
  background: var(--p-surface-card);
  border-radius: var(--card-radius);
  box-shadow: var(--card-shadow);
  transition: box-shadow var(--transition-base);
  padding: 1.25rem;
}

.kpi-card:hover {
  box-shadow: var(--card-shadow-hover);
}

.kpi-header {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  margin-bottom: 0.75rem;
}

.kpi-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  flex-shrink: 0;
}

.kpi-icon-wrap i {
  font-size: 1.125rem;
}

.kpi-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.kpi-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--p-text-color);
  line-height: 1.2;
}

.kpi-subtitle {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  margin-top: 0.25rem;
}

.kpi-sparkline {
  height: 40px;
  margin-top: 0.75rem;
}
</style>
