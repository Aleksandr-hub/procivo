<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useDashboardStore } from '@/modules/dashboard/stores/dashboard.store'
import MyTasksWidget from '@/modules/dashboard/components/MyTasksWidget.vue'
import ActiveProcessesWidget from '@/modules/dashboard/components/ActiveProcessesWidget.vue'
import ChartsWidget from '@/modules/dashboard/components/ChartsWidget.vue'
import RecentActivityWidget from '@/modules/dashboard/components/RecentActivityWidget.vue'

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

onMounted(() => {
  dashboardStore.fetchAll(orgId, currentEmployeeId.value)
})
</script>

<template>
  <div class="dashboard-page">
    <h1 class="page-title">{{ t('dashboard.title') }}</h1>

    <div class="dashboard-grid">
      <!-- My Tasks -->
      <Card class="dashboard-card">
        <template #title>{{ t('dashboard.myTasks') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="200px" />
          <MyTasksWidget v-else :tasks="dashboardStore.myTasks" :org-id="orgId" />
        </template>
      </Card>

      <!-- Active Processes -->
      <Card class="dashboard-card">
        <template #title>{{ t('dashboard.activeProcesses') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="200px" />
          <ActiveProcessesWidget v-else :processes="dashboardStore.activeProcesses" :org-id="orgId" />
        </template>
      </Card>

      <!-- Charts -->
      <Card class="dashboard-card">
        <template #title>{{ t('dashboard.charts') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="200px" />
          <ChartsWidget v-else :stats="dashboardStore.stats" />
        </template>
      </Card>

      <!-- Recent Activity -->
      <Card class="dashboard-card">
        <template #title>{{ t('dashboard.recentActivity') }}</template>
        <template #content>
          <Skeleton v-if="dashboardStore.loading" height="200px" />
          <RecentActivityWidget v-else :org-id="orgId" />
        </template>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  padding: 1.5rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 1.5rem;
  color: var(--p-text-color);
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

.dashboard-card :deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}

.dashboard-card :deep(.p-card-content) {
  padding-top: 0;
}
</style>
