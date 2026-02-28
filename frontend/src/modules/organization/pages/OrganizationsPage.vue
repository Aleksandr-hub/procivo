<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'
import OrgCreateDialog from '@/modules/organization/components/OrgCreateDialog.vue'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const toast = useToast()
const orgStore = useOrganizationStore()

const showCreateDialog = ref(false)
const { t } = useI18n()

onMounted(async () => {
  await orgStore.fetchOrganizations()
  if (orgStore.organizations.length === 1) {
    openOrg(orgStore.organizations[0].id)
  }
})

function openOrg(orgId: string) {
  orgStore.selectOrganization(orgId)
  router.push(`/organizations/${orgId}/departments`)
}

async function handleCreated(orgId: string) {
  showCreateDialog.value = false
  toast.add({ severity: 'success', summary: t('common.success'), detail: t('organizations.organizationCreated'), life: 3000 })
  openOrg(orgId)
}

function getStatusSeverity(status: string) {
  return status === 'active' ? 'success' : 'warn'
}
</script>

<template>
  <div class="organizations-page">
    <div class="page-header">
      <h2>{{ t('organizations.title') }}</h2>
      <Button
        :label="t('organizations.createOrganization')"
        icon="pi pi-plus"
        @click="showCreateDialog = true"
      />
    </div>

    <div v-if="orgStore.loading" class="loading-grid">
      <Skeleton v-for="i in 3" :key="i" height="140px" border-radius="8px" />
    </div>

    <div v-else-if="orgStore.organizations.length === 0" class="empty-state">
      <i class="pi pi-building" style="font-size: 3rem; color: var(--p-text-muted-color)" />
      <p>{{ t('organizations.noOrganizationsYet') }}</p>
      <Button
        :label="t('organizations.createYourFirstOrganization')"
        icon="pi pi-plus"
        @click="showCreateDialog = true"
      />
    </div>

    <div v-else class="org-grid">
      <div
        v-for="org in orgStore.organizations"
        :key="org.id"
        class="org-card"
        @click="openOrg(org.id)"
      >
        <div class="org-card-header">
          <span class="org-name">{{ org.name }}</span>
          <Tag :value="org.status" :severity="getStatusSeverity(org.status)" />
        </div>
        <p v-if="org.description" class="org-description">{{ org.description }}</p>
        <p v-else class="org-description muted">{{ t('common.noDescription') }}</p>
        <div class="org-card-footer">
          <span class="org-slug">{{ org.slug }}</span>
        </div>
      </div>
    </div>

    <OrgCreateDialog
      :visible="showCreateDialog"
      @hide="showCreateDialog = false"
      @created="handleCreated"
    />
  </div>
</template>

<style scoped>
.organizations-page {
  max-width: 1200px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.page-header h2 {
  margin: 0;
}

.loading-grid,
.org-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}

.org-card {
  background: var(--p-surface-card);
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-border-radius);
  padding: 1.25rem;
  cursor: pointer;
  transition: box-shadow 0.2s, border-color 0.2s;
}

.org-card:hover {
  border-color: var(--p-primary-color);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.org-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.org-name {
  font-size: 1.1rem;
  font-weight: 600;
}

.org-description {
  font-size: 0.875rem;
  color: var(--p-text-color);
  margin-bottom: 1rem;
  line-height: 1.4;
}

.org-description.muted {
  color: var(--p-text-muted-color);
  font-style: italic;
}

.org-card-footer {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  text-align: center;
  color: var(--p-text-muted-color);
}
</style>
