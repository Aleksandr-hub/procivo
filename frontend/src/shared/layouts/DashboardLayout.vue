<script setup lang="ts">
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from '@/shared/components/AppSidebar.vue'
import AppTopbar from '@/shared/components/AppTopbar.vue'
import ImpersonationBanner from '@/shared/components/ImpersonationBanner.vue'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { useNotificationStore } from '@/modules/notifications/stores/notification.store'
import { usePermissionStore } from '@/modules/organization/stores/permission.store'
import { useCollapsibleSidebar } from '@/shared/composables/useCollapsibleSidebar'

const route = useRoute()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const permissionStore = usePermissionStore()
const { expanded } = useCollapsibleSidebar()

// Initialize Mercure SSE when user is available, close on logout
watch(
  () => authStore.user,
  (user) => {
    if (user) {
      notificationStore.initMercure(user.id)
    } else {
      notificationStore.closeMercure()
      permissionStore.reset()
    }
  },
  { immediate: true },
)

// Fetch permissions when org context changes
watch(
  () => route.params.orgId as string | undefined,
  (orgId, oldOrgId) => {
    if (orgId && orgId !== oldOrgId && authStore.user) {
      permissionStore.fetchMyPermissions(orgId)
    } else if (!orgId) {
      permissionStore.reset()
    }
  },
  { immediate: true },
)
</script>

<template>
  <ImpersonationBanner />
  <div class="layout-wrapper" :class="{ 'has-impersonation-banner': authStore.isImpersonating }">
    <AppSidebar />
    <div class="layout-content" :class="{ 'sidebar-collapsed': !expanded }">
      <AppTopbar />
      <main class="layout-main">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.layout-wrapper {
  min-height: 100vh;
}

.layout-wrapper.has-impersonation-banner {
  margin-top: 40px;
}

.layout-content {
  margin-left: var(--sidebar-width-expanded);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left var(--transition-base);
}

.layout-content.sidebar-collapsed {
  margin-left: var(--sidebar-width-collapsed);
}

.layout-main {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}
</style>
