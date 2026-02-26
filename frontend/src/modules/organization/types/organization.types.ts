export interface OrganizationDTO {
  id: string
  name: string
  slug: string
  description: string | null
  status: string
  ownerUserId: string
  createdAt: string
  updatedAt: string | null
}

export interface DepartmentDTO {
  id: string
  organizationId: string
  name: string
  code: string
  description: string | null
  parentId: string | null
  path: string
  level: number
  sortOrder: number
  status: string
  createdAt: string
}

export interface DepartmentTreeDTO {
  id: string
  parentId: string | null
  name: string
  code: string
  description: string | null
  sortOrder: number
  level: number
  status: string
  children: DepartmentTreeDTO[]
}

export interface PositionDTO {
  id: string
  organizationId: string
  departmentId: string
  name: string
  description: string | null
  sortOrder: number
  isHead: boolean
  createdAt: string
}

export interface EmployeeDTO {
  id: string
  organizationId: string
  userId: string
  positionId: string
  departmentId: string
  employeeNumber: string
  managerId: string | null
  hiredAt: string
  status: string
  createdAt: string
  departmentName: string | null
  positionName: string | null
  userFullName: string | null
  userEmail: string | null
}

export interface OrgChartNodeDTO {
  type: 'department' | 'person'
  id: string
  label: string
  children: OrgChartNodeDTO[]
  // department fields
  departmentCode?: string
  // person fields
  employeeNumber?: string
  email?: string
  positionName?: string
  departmentName?: string
  isHead?: boolean
  managerId?: string | null
}

export interface RoleDTO {
  id: string
  organizationId: string
  name: string
  description: string | null
  isSystem: boolean
  hierarchy: number
  createdAt: string
  permissions: PermissionDTO[]
}

export interface PermissionDTO {
  id: string
  roleId: string
  resource: PermissionResource
  action: PermissionAction
  scope: PermissionScope
}

export type PermissionResource =
  | 'employee'
  | 'department'
  | 'position'
  | 'role'
  | 'invitation'
  | 'organization'
  | 'task'

export type PermissionAction = 'view' | 'create' | 'update' | 'delete' | 'manage'

export type PermissionScope =
  | 'own'
  | 'subordinates'
  | 'subordinates_tree'
  | 'department'
  | 'department_tree'
  | 'organization'

export interface MyPermissionsResponse {
  isOwner: boolean
  roles: RoleDTO[]
  permissions: PermissionDTO[]
}

export interface InvitationDTO {
  id: string
  organizationId: string
  email: string
  departmentId: string
  positionId: string
  employeeNumber: string
  status: string
  invitedByUserId: string
  expiresAt: string
  acceptedAt: string | null
  createdAt: string
}
