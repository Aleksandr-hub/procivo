<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'
import { usePermissionStore } from '@/modules/organization/stores/permission.store'

defineProps<{
  visible: boolean
}>()

const route = useRoute()
const { t } = useI18n()
const orgStore = useOrganizationStore()
const permissionStore = usePermissionStore()

const orgId = computed(() => (route.params.orgId as string | undefined) ?? orgStore.currentOrgId ?? undefined)

const menuItems = computed(() => {
  const items = [
    {
      label: t('nav.organizations'),
      icon: 'pi pi-building',
      to: '/',
    },
    {
      label: t('notifications.sidebar'),
      icon: 'pi pi-bell',
      to: '/notifications',
    },
  ]

  if (orgId.value) {
    items.push(
      {
        label: t('nav.dashboard'),
        icon: 'pi pi-home',
        to: `/organizations/${orgId.value}/dashboard`,
      },
      {
        label: t('nav.departments'),
        icon: 'pi pi-sitemap',
        to: `/organizations/${orgId.value}/departments`,
      },
      {
        label: t('nav.employees'),
        icon: 'pi pi-users',
        to: `/organizations/${orgId.value}/employees`,
      },
      {
        label: t('nav.tasks'),
        icon: 'pi pi-check-square',
        to: `/organizations/${orgId.value}/tasks`,
      },
      {
        label: t('nav.boards'),
        icon: 'pi pi-objects-column',
        to: `/organizations/${orgId.value}/boards`,
      },
      {
        label: t('nav.labels'),
        icon: 'pi pi-tag',
        to: `/organizations/${orgId.value}/labels`,
      },
      {
        label: t('nav.orgChart'),
        icon: 'pi pi-share-alt',
        to: `/organizations/${orgId.value}/org-chart`,
      },
      {
        label: t('nav.roles'),
        icon: 'pi pi-shield',
        to: `/organizations/${orgId.value}/roles`,
      },
      {
        label: t('nav.processes'),
        icon: 'pi pi-sitemap',
        to: `/organizations/${orgId.value}/process-definitions`,
      },
      {
        label: t('nav.instances'),
        icon: 'pi pi-play',
        to: `/organizations/${orgId.value}/process-instances`,
      },
    )

    if (permissionStore.can('role', 'view') || permissionStore.isOwner) {
      items.push({
        label: t('nav.permissions'),
        icon: 'pi pi-lock',
        to: `/organizations/${orgId.value}/permissions`,
      })
    }
  }

  return items
})

function isActive(to: string): boolean {
  if (to === '/') {
    return route.path === '/'
  }
  return route.path.startsWith(to)
}
</script>

<template>
  <aside v-show="visible" class="sidebar">
    <div class="sidebar-logo">
      <span class="logo-text">Procivo</span>
    </div>

    <nav class="sidebar-nav">
      <router-link
        v-for="item in menuItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: isActive(item.to) }"
      >
        <i :class="item.icon" class="nav-icon" />
        <span class="nav-label">{{ item.label }}</span>
      </router-link>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  --sidebar-bg: #0f172a;
  --sidebar-text: rgba(255, 255, 255, 0.7);
  --sidebar-text-active: #ffffff;
  --sidebar-hover-bg: rgba(255, 255, 255, 0.05);
  --sidebar-active-bg: rgba(255, 255, 255, 0.08);
  --sidebar-border: rgba(255, 255, 255, 0.08);
  --sidebar-accent: #60a5fa;

  width: 250px;
  min-width: 250px;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
  background: var(--sidebar-bg);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.sidebar-logo {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--sidebar-border);
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--sidebar-text-active);
  letter-spacing: -0.025em;
}

.sidebar-nav {
  padding: 0.75rem 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 1.5rem;
  margin: 0 0.5rem;
  border-radius: 6px;
  color: var(--sidebar-text);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition:
    background-color 0.15s,
    color 0.15s;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: var(--sidebar-hover-bg);
  color: var(--sidebar-text-active);
  text-decoration: none;
}

.nav-item.active {
  background: var(--sidebar-active-bg);
  color: var(--sidebar-text-active);
  border-left-color: var(--sidebar-accent);
}

.nav-icon {
  font-size: 1rem;
  width: 1.25rem;
  text-align: center;
}

.nav-label {
  white-space: nowrap;
}
</style>
