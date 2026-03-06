<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'
import { usePermissionStore } from '@/modules/organization/stores/permission.store'
import { useCollapsibleSidebar } from '@/shared/composables/useCollapsibleSidebar'

const route = useRoute()
const { t } = useI18n()
const orgStore = useOrganizationStore()
const permissionStore = usePermissionStore()
const { expanded, toggle } = useCollapsibleSidebar()

const orgId = computed(() => (route.params.orgId as string | undefined) ?? orgStore.currentOrgId ?? undefined)

const toggleTooltip = computed(() =>
  expanded.value ? t('sidebar.collapse') : t('sidebar.expand'),
)

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
  <aside class="sidebar" :class="{ collapsed: !expanded }">
    <div class="sidebar-logo">
      <span v-if="expanded" class="logo-text">Procivo</span>
      <span v-else class="logo-compact">P</span>
      <button
        class="collapse-toggle"
        :aria-label="toggleTooltip"
        v-tooltip.right="!expanded ? toggleTooltip : undefined"
        @click="toggle"
      >
        <i :class="expanded ? 'pi pi-chevron-left' : 'pi pi-chevron-right'" />
      </button>
    </div>

    <nav class="sidebar-nav">
      <router-link
        v-for="item in menuItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ active: isActive(item.to) }"
        v-tooltip.right="!expanded ? item.label : undefined"
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

  width: var(--sidebar-width-expanded);
  min-width: var(--sidebar-width-collapsed);
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
  background: var(--sidebar-bg);
  overflow-y: auto;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  transition: width var(--transition-base);
}

.sidebar.collapsed {
  width: var(--sidebar-width-collapsed);
}

.sidebar-logo {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--sidebar-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 56px;
}

.sidebar.collapsed .sidebar-logo {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--sidebar-text-active);
  letter-spacing: -0.025em;
}

.logo-compact {
  background: var(--sidebar-accent);
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
}

.collapse-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid var(--sidebar-border);
  border-radius: 6px;
  background: transparent;
  color: var(--sidebar-text);
  cursor: pointer;
  transition: color 0.15s, background-color 0.15s, border-color 0.15s;
  flex-shrink: 0;
  font-size: 0.75rem;
}

.collapse-toggle:hover {
  color: var(--sidebar-text-active);
  background: var(--sidebar-hover-bg);
  border-color: var(--sidebar-text);
}

.sidebar.collapsed .collapse-toggle {
  display: none;
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
  flex-shrink: 0;
}

.nav-label {
  white-space: nowrap;
  transition: opacity 0.2s ease 0.1s;
}

.sidebar.collapsed .nav-label {
  opacity: 0;
  width: 0;
  overflow: hidden;
  transition-delay: 0s;
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding-left: 0;
  padding-right: 0;
  margin: 0 0.375rem;
  border-left: none;
}

.sidebar.collapsed .nav-icon {
  margin: 0;
}
</style>
