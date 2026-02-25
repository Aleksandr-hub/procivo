<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  orgId: string
}>()

const route = useRoute()
const router = useRouter()
const orgStore = useOrganizationStore()
const { t } = useI18n()

onMounted(async () => {
  orgStore.selectOrganization(props.orgId)
  if (orgStore.organizations.length === 0) {
    await orgStore.fetchOrganizations()
  }
  if (!route.matched.some((r) => r.name === 'departments' || r.name === 'employees' || r.name === 'org-chart' || r.name === 'roles' || r.name === 'role-detail')) {
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
          { label: t('organizationDetail.departments'), icon: 'pi pi-sitemap', route: `/organizations/${orgId}/departments` },
          { label: t('organizationDetail.employees'), icon: 'pi pi-users', route: `/organizations/${orgId}/employees` },
          { label: t('organizationDetail.orgChart'), icon: 'pi pi-share-alt', route: `/organizations/${orgId}/org-chart` },
          { label: t('organizationDetail.roles'), icon: 'pi pi-shield', route: `/organizations/${orgId}/roles` },
        ]"
        @tab-change="(e: { index: number }) => {
          const items = [
            `/organizations/${orgId}/departments`,
            `/organizations/${orgId}/employees`,
            `/organizations/${orgId}/org-chart`,
            `/organizations/${orgId}/roles`,
          ]
          router.push(items[e.index])
        }"
      />
    </div>
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
</style>
