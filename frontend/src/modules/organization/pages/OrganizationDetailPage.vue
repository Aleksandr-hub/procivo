<script setup lang="ts">
import { onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'
import { useI18n } from 'vue-i18n'
import AuditLogTimeline from '@/modules/audit/components/AuditLogTimeline.vue'

const props = defineProps<{
  orgId: string
}>()

const route = useRoute()
const router = useRouter()
const orgStore = useOrganizationStore()
const { t } = useI18n()

const tabRoutes = computed(() => [
  `/organizations/${props.orgId}/departments`,
  `/organizations/${props.orgId}/employees`,
  `/organizations/${props.orgId}/org-chart`,
  `/organizations/${props.orgId}/roles`,
])

const activeIndex = computed(() => {
  const idx = tabRoutes.value.findIndex((r) => route.path.startsWith(r))
  return idx >= 0 ? idx : 0
})

onMounted(async () => {
  orgStore.selectOrganization(props.orgId)
  if (orgStore.organizations.length === 0) {
    await orgStore.fetchOrganizations()
  }
  if (!route.matched.some((r) => r.name === 'departments' || r.name === 'employees' || r.name === 'org-chart' || r.name === 'roles' || r.name === 'role-detail' || r.name === 'permissions')) {
    router.replace(`/organizations/${props.orgId}/departments`)
  }
})

watch(
  () => props.orgId,
  (id) => orgStore.selectOrganization(id),
)
</script>

<template>
  <div class="org-detail">
    <div v-if="orgStore.currentOrg" class="org-detail-header">
      <h2>{{ orgStore.currentOrg.name }}</h2>
      <TabMenu
        :model="[
          { label: t('organizationDetail.departments'), icon: 'pi pi-sitemap' },
          { label: t('organizationDetail.employees'), icon: 'pi pi-users' },
          { label: t('organizationDetail.orgChart'), icon: 'pi pi-share-alt' },
          { label: t('organizationDetail.roles'), icon: 'pi pi-shield' },
        ]"
        :activeIndex="activeIndex"
        @tab-change="(e: { index: number }) => router.push(tabRoutes[e.index])"
      />
    </div>
    <Fieldset :legend="t('audit.activityTimeline')" :toggleable="true" :collapsed="true" class="org-audit-fieldset">
      <AuditLogTimeline :org-id="orgId" :limit="20" />
    </Fieldset>

    <RouterView />
  </div>
</template>

<style scoped>
.org-detail-header {
  margin-bottom: 1.5rem;
}

.org-detail-header h2 {
  margin: 0 0 1rem 0;
}

.org-audit-fieldset {
  margin-bottom: 1.5rem;
}
</style>
