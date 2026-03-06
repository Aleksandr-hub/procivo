<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useDashboardStore } from '@/modules/dashboard/stores/dashboard.store'
import KpiCard from '@/modules/dashboard/components/KpiCard.vue'
import MyTasksWidget from '@/modules/dashboard/components/MyTasksWidget.vue'
import ChartsWidget from '@/modules/dashboard/components/ChartsWidget.vue'
import RecentActivityWidget from '@/modules/dashboard/components/RecentActivityWidget.vue'
import UpcomingDeadlinesWidget from '@/modules/dashboard/components/UpcomingDeadlinesWidget.vue'

const route = useRoute()
const { t } = useI18n()
const authStore = useAuthStore()
const empStore = useEmployeeStore()
const dashboardStore = useDashboardStore()

const orgId = route.params.orgId as string

const currentEmployeeId = computed(
  () =>
    empStore.employees.find((e) => e.userId === authStore.user?.id && e.status === 'active')?.id ?? null,
)

const activeProcessCount = computed(() => dashboardStore.activeProcesses.length)
const myTasksCount = computed(() => dashboardStore.myTasks.length)

const dueTodayCount = computed(() => {
  const today = new Date().toDateString()
  return dashboardStore.myTasks.filter(
    (task) => task.dueDate !== null && new Date(task.dueDate).toDateString() === today,
  ).length
})

const completionRate = computed(() => {
  if (!dashboardStore.stats) return 0
  const byStatus = dashboardStore.stats.tasks_by_status
  const total = Object.values(byStatus).reduce((sum, v) => sum + v, 0)
  if (total === 0) return 0
  return Math.round(((byStatus['done'] ?? 0) / total) * 100)
})

const sparklineTrend = computed(() => {
  if (!dashboardStore.stats) return undefined
  return dashboardStore.stats.tasks_completed_by_day.map((d) => d.cnt)
})

const processesByStatusTotal = computed(() => {
  if (!dashboardStore.stats) return 0
  return Object.values(dashboardStore.stats.processes_by_status).reduce((sum, v) => sum + v, 0)
})

onMounted(() => {
  dashboardStore.fetchAll(orgId, currentEmployeeId.value)
})
</script>

<template>
  <div class="dashboard-page">
    <div class="bento-grid">
      <!-- Row 1: KPI Cards -->
      <div class="span-3">
        <Skeleton v-if="dashboardStore.loading" height="140px" border-radius="var(--card-radius)" />
        <KpiCard
          v-else
          :label="t('dashboard.activeProcessesCount')"
          :value="activeProcessCount"
          icon="pi pi-play"
          color="var(--color-primary)"
        />
      </div>

      <div class="span-3">
        <Skeleton v-if="dashboardStore.loading" height="140px" border-radius="var(--card-radius)" />
        <KpiCard
          v-else
          :label="t('dashboard.myTasksCount')"
          :value="myTasksCount"
          icon="pi pi-check-square"
          color="var(--color-accent)"
          :subtitle="t('dashboard.dueToday_count', { count: dueTodayCount })"
        />
      </div>

      <div class="span-3">
        <Skeleton v-if="dashboardStore.loading" height="140px" border-radius="var(--card-radius)" />
        <KpiCard
          v-else
          :label="t('dashboard.completionRate')"
          :value="`${completionRate}%`"
          icon="pi pi-chart-line"
          color="var(--color-success)"
          :trend="sparklineTrend"
        />
      </div>

      <div class="span-3">
        <Skeleton v-if="dashboardStore.loading" height="140px" border-radius="var(--card-radius)" />
        <KpiCard
          v-else
          :label="t('dashboard.processesByStatus')"
          :value="processesByStatusTotal"
          icon="pi pi-sitemap"
          color="var(--color-warning)"
        />
      </div>

      <!-- Row 2: Charts (span-8) + Team Activity (span-4) -->
      <Card class="span-8 row-span-2 bento-card">
        <template #title>{{ t('dashboard.charts') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="400px" />
          <ChartsWidget v-else :stats="dashboardStore.stats" />
        </template>
      </Card>

      <Card class="span-4 row-span-2 bento-card">
        <template #title>{{ t('dashboard.teamActivity') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="400px" />
          <RecentActivityWidget v-else :org-id="orgId" />
        </template>
      </Card>

      <!-- Row 3: Upcoming Deadlines (span-6) + My Tasks (span-6) -->
      <Card class="span-6 bento-card">
        <template #title>{{ t('dashboard.upcomingDeadlines') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="200px" />
          <UpcomingDeadlinesWidget v-else :tasks="dashboardStore.myTasks" :org-id="orgId" />
        </template>
      </Card>

      <Card class="span-6 bento-card">
        <template #title>{{ t('dashboard.myTasks') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="200px" />
          <MyTasksWidget v-else :tasks="dashboardStore.myTasks" :org-id="orgId" />
        </template>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  padding: 1.5rem;
}

.bento-grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 1.5rem;
}

.span-3 {
  grid-column: span 3;
}

.span-4 {
  grid-column: span 4;
}

.span-6 {
  grid-column: span 6;
}

.span-8 {
  grid-column: span 8;
}

.row-span-2 {
  grid-row: span 2;
}

.bento-card {
  border-radius: var(--card-radius);
  box-shadow: var(--card-shadow);
  transition: box-shadow var(--transition-base);
}

.bento-card:hover {
  box-shadow: var(--card-shadow-hover);
}

.bento-card :deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}

.bento-card :deep(.p-card-content) {
  padding-top: 0;
}

@media (max-width: 1200px) {
  .span-3 {
    grid-column: span 6;
  }

  .span-8,
  .span-4 {
    grid-column: span 12;
  }

  .row-span-2 {
    grid-row: span 1;
  }
}

@media (max-width: 768px) {
  .bento-grid {
    grid-template-columns: 1fr;
  }

  .span-3,
  .span-4,
  .span-6,
  .span-8 {
    grid-column: span 1;
  }

  .row-span-2 {
    grid-row: span 1;
  }
}
</style>
