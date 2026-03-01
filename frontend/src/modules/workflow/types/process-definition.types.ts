export type NodeType =
  | 'start'
  | 'end'
  | 'task'
  | 'exclusive_gateway'
  | 'parallel_gateway'
  | 'inclusive_gateway'
  | 'timer'
  | 'sub_process'
  | 'webhook'
  | 'notification'

export type ProcessDefinitionStatus = 'draft' | 'published' | 'archived'

export type FormFieldType = 'text' | 'number' | 'date' | 'select' | 'checkbox' | 'textarea' | 'employee'

export interface FormFieldDefinition {
  name: string
  label: string
  type: FormFieldType
  required: boolean
  options?: string[]
}

export interface ProcessDefinitionDTO {
  id: string
  organization_id: string
  name: string
  description: string | null
  status: ProcessDefinitionStatus
  created_by: string
  created_at: string
  updated_at: string | null
}

export interface NodeDTO {
  id: string
  process_definition_id: string
  type: NodeType
  name: string
  description: string | null
  config: Record<string, unknown>
  position_x: number
  position_y: number
}

export interface TransitionDTO {
  id: string
  process_definition_id: string
  source_node_id: string
  target_node_id: string
  name: string | null
  action_key: string | null
  condition_expression: string | null
  form_fields: FormFieldDefinition[]
  sort_order: number
}

export interface ProcessDefinitionDetailDTO extends ProcessDefinitionDTO {
  nodes: NodeDTO[]
  transitions: TransitionDTO[]
}

export interface ProcessDefinitionVersionDTO {
  id: string
  version_number: number
  published_at: string
  published_by: string
  running_instance_count: number
}

export interface CreateProcessDefinitionPayload {
  name: string
  description?: string | null
}

export interface UpdateProcessDefinitionPayload {
  name: string
  description?: string | null
}

export interface AddNodePayload {
  type: NodeType
  name: string
  description?: string | null
  config?: Record<string, unknown>
  position_x?: number
  position_y?: number
}

export interface UpdateNodePayload {
  name: string
  description?: string | null
  config?: Record<string, unknown>
  position_x?: number
  position_y?: number
}

export interface AddTransitionPayload {
  source_node_id: string
  target_node_id: string
  name?: string | null
  action_key?: string | null
  condition_expression?: string | null
  form_fields?: FormFieldDefinition[]
  sort_order?: number
}

export interface UpdateTransitionPayload {
  source_node_id?: string
  target_node_id?: string
  name?: string | null
  action_key?: string | null
  condition_expression?: string | null
  form_fields?: FormFieldDefinition[]
  sort_order?: number
}
