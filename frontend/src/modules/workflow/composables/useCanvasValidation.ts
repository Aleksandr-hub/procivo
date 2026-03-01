import { computed, type Ref } from 'vue'
import type { Node, Edge } from '@vue-flow/core'
import type { ProcessDefinitionDetailDTO } from '@/modules/workflow/types/process-definition.types'

export interface ValidationError {
  key: string
  params?: Record<string, string>
}

function findOrphanIds(ns: Node[], es: Edge[]): Set<string> {
  const ids = new Set<string>()
  for (const node of ns) {
    const hasIncoming = es.some((e) => e.target === node.id)
    const hasOutgoing = es.some((e) => e.source === node.id)

    if (node.type === 'start') {
      if (!hasOutgoing && ns.length > 1) ids.add(node.id)
      continue
    }

    if (node.type === 'end') {
      if (!hasIncoming) ids.add(node.id)
      continue
    }

    if (!hasIncoming || !hasOutgoing) {
      ids.add(node.id)
    }
  }
  return ids
}

export function useCanvasValidation(
  nodes: Ref<Node[]>,
  edges: Ref<Edge[]>,
  definition?: Ref<ProcessDefinitionDetailDTO | null>,
) {
  const orphanNodeIds = computed(() => findOrphanIds(nodes.value, edges.value))

  const validationErrors = computed<ValidationError[]>(() => {
    const errors: ValidationError[] = []
    const ns = nodes.value
    const es = edges.value

    if (ns.length === 0) return errors

    const starts = ns.filter((n) => n.type === 'start')
    if (starts.length === 0) {
      errors.push({ key: 'validationNoStart' })
    } else if (starts.length > 1) {
      errors.push({ key: 'validationMultipleStarts' })
    }

    const ends = ns.filter((n) => n.type === 'end')
    if (ends.length === 0) {
      errors.push({ key: 'validationNoEnd' })
    }

    const orphans = orphanNodeIds.value
    for (const node of ns) {
      if (orphans.has(node.id)) {
        errors.push({ key: 'validationOrphanNode', params: { name: node.data.label as string } })
      }
    }

    const gatewayTypes = ['exclusive_gateway', 'parallel_gateway', 'inclusive_gateway']
    for (const node of ns) {
      if (!gatewayTypes.includes(node.type as string)) continue
      const incoming = es.filter((e) => e.target === node.id)
      const outgoing = es.filter((e) => e.source === node.id)
      // A merge gateway (2+ incoming, 1 outgoing) is valid.
      // Only warn about min 2 outgoing when it's a split (fewer than 2 incoming).
      const isMerge = incoming.length >= 2
      if (!isMerge && outgoing.length > 0 && outgoing.length < 2) {
        errors.push({ key: 'validationGatewayMinPaths', params: { name: node.data.label as string } })
      }
    }

    // Check transition-level warnings using the definition from the API
    if (definition?.value) {
      const transitions = definition.value.transitions

      // Check for transitions with form_fields but no action_key
      for (const transition of transitions) {
        if (transition.form_fields && transition.form_fields.length > 0 && !transition.action_key) {
          const sourceNode = ns.find((n) => n.id === transition.source_node_id)
          const sourceName = sourceNode ? (sourceNode.data.label as string) : transition.source_node_id
          errors.push({
            key: 'validationTransitionNoActionKey',
            params: { name: sourceName },
          })
        }
      }

      // Check for duplicate action_keys on transitions from the same source task node
      const taskNodes = ns.filter((n) => n.type === 'task')
      for (const taskNode of taskNodes) {
        const outgoingTransitions = transitions.filter(
          (tr) => tr.source_node_id === taskNode.id && tr.action_key !== null && tr.action_key !== '',
        )
        const actionKeyCounts = new Map<string, number>()
        for (const tr of outgoingTransitions) {
          const key = tr.action_key as string
          actionKeyCounts.set(key, (actionKeyCounts.get(key) ?? 0) + 1)
        }
        for (const [actionKey, count] of actionKeyCounts) {
          if (count > 1) {
            errors.push({
              key: 'validationDuplicateActionKey',
              params: { name: taskNode.data.label as string, actionKey },
            })
          }
        }
      }
    }

    return errors
  })

  return { validationErrors, orphanNodeIds }
}
