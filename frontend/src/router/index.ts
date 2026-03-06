import { createRouter, createWebHistory } from 'vue-router'
import type { PermissionResource, PermissionAction } from '@/modules/organization/types/organization.types'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/modules/auth/pages/LoginPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/modules/auth/pages/RegisterPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/auth/2fa/verify',
      name: 'two-factor-verify',
      component: () => import('@/modules/auth/pages/TwoFactorVerifyPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/invitation/accept',
      name: 'accept-invitation',
      component: () => import('@/modules/organization/pages/AcceptInvitationPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/',
      component: () => import('@/shared/layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/modules/auth/pages/ProfilePage.vue'),
        },
        {
          path: 'notifications',
          name: 'notifications',
          component: () => import('@/modules/notifications/pages/NotificationsPage.vue'),
        },
        {
          path: '',
          name: 'organizations',
          component: () => import('@/modules/organization/pages/OrganizationsPage.vue'),
        },
        {
          path: 'organizations/:orgId',
          component: () => import('@/modules/organization/pages/OrganizationDetailPage.vue'),
          props: true,
          children: [
            {
              path: 'departments',
              name: 'departments',
              component: () => import('@/modules/organization/pages/DepartmentsPage.vue'),
            },
            {
              path: 'employees',
              name: 'employees',
              component: () => import('@/modules/organization/pages/EmployeesPage.vue'),
            },
            {
              path: 'org-chart',
              name: 'org-chart',
              component: () => import('@/modules/organization/pages/OrgChartPage.vue'),
            },
            {
              path: 'roles',
              name: 'roles',
              component: () => import('@/modules/organization/pages/RolesPage.vue'),
            },
            {
              path: 'roles/:roleId',
              name: 'role-detail',
              component: () => import('@/modules/organization/pages/RoleDetailPage.vue'),
            },
            {
              path: 'permissions',
              name: 'permissions',
              component: () => import('@/modules/organization/pages/PermissionsPage.vue'),
              meta: { permission: { resource: 'role', action: 'view' } },
            },
          ],
        },
        {
          path: 'organizations/:orgId/dashboard',
          name: 'dashboard',
          component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/tasks',
          name: 'tasks',
          component: () => import('@/modules/tasks/pages/TasksPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/tasks/:taskId',
          name: 'task-detail',
          component: () => import('@/modules/tasks/pages/TaskDetailFullPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/boards',
          name: 'boards',
          component: () => import('@/modules/tasks/pages/BoardsPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/boards/:boardId/kanban',
          name: 'kanban',
          component: () => import('@/modules/tasks/pages/KanbanBoardPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/boards/:boardId/process',
          name: 'process-board',
          component: () => import('@/modules/tasks/pages/ProcessBoardPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/labels',
          name: 'labels',
          component: () => import('@/modules/tasks/pages/LabelsPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/process-definitions',
          name: 'process-definitions',
          component: () => import('@/modules/workflow/pages/ProcessDefinitionsPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/process-definitions/:definitionId/designer',
          name: 'workflow-designer',
          component: () => import('@/modules/workflow/pages/ProcessDesignerPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/process-instances',
          name: 'process-instances',
          component: () => import('@/modules/workflow/pages/ProcessInstancesPage.vue'),
          props: true,
        },
        {
          path: 'organizations/:orgId/process-instances/:instanceId',
          name: 'process-instance-detail',
          component: () => import('@/modules/workflow/pages/ProcessInstanceDetailPage.vue'),
          props: true,
        },
      ],
    },
  ],
})

// Navigation guard for permission-based route protection
router.beforeEach(async (to) => {
  const permMeta = to.meta.permission as
    | { resource: PermissionResource; action: PermissionAction }
    | undefined

  if (permMeta) {
    const { usePermissionStore } = await import(
      '@/modules/organization/stores/permission.store'
    )
    const permissionStore = usePermissionStore()

    // If permissions not loaded yet, allow navigation (will be checked after fetch)
    if (!permissionStore.loaded) return true

    if (!permissionStore.can(permMeta.resource, permMeta.action)) {
      // Redirect to dashboard or org detail page
      const orgId = to.params.orgId as string | undefined
      if (orgId) {
        return { name: 'dashboard', params: { orgId } }
      }
      return { name: 'organizations' }
    }
  }

  return true
})

export default router
