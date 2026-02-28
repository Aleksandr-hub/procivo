<script setup lang="ts">
import { computed, markRaw } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import type { Node, Edge } from '@vue-flow/core'
import type {
  ProcessInstanceGraphDTO,
  ProcessInstanceTokenDTO,
} from '@/modules/workflow/types/process-instance.types'
import StartNode from '@/modules/workflow/components/nodes/StartNode.vue'
import EndNode from '@/modules/workflow/components/nodes/EndNode.vue'
import TaskNode from '@/modules/workflow/components/nodes/TaskNode.vue'
import GatewayNode from '@/modules/workflow/components/nodes/GatewayNode.vue'
import TimerNode from '@/modules/workflow/components/nodes/TimerNode.vue'

const props = defineProps<{
  graph: ProcessInstanceGraphDTO
  tokens: ProcessInstanceTokenDTO[]
}>()

const nodeTypes = markRaw({
  start: StartNode,
  end: EndNode,
  task: TaskNode,
  exclusive_gateway: GatewayNode,
  parallel_gateway: GatewayNode,
  inclusive_gateway: GatewayNode,
  timer: TimerNode,
  notification: TaskNode,
  webhook: TaskNode,
  sub_process: TaskNode,
// eslint-disable-next-line @typescript-eslint/no-explicit-any
} as any)

const statusPriority: Record<string, number> = {
  active: 3,
  waiting: 2,
  completed: 1,
}

const tokenStatusMap = computed(() => {
  const map: Record<string, string> = {}
  for (const token of props.tokens) {
    const current = map[token.node_id]
    if (!current || (statusPriority[token.status] ?? 0) > (statusPriority[current] ?? 0)) {
      map[token.node_id] = token.status
    }
  }
  return map
})

const nodes = computed<Node[]>(() =>
  props.graph.nodes.map((n) => ({
    id: n.id,
    type: n.type,
    position: { x: n.position_x, y: n.position_y },
    data: {
      label: n.name,
      description: n.description,
      nodeType: n.type,
      tokenStatus: tokenStatusMap.value[n.id] || null,
    },
  })),
)

const edges = computed<Edge[]>(() =>
  props.graph.transitions.map((tr) => ({
    id: tr.id,
    source: tr.source_node_id,
    target: tr.target_node_id,
    label: tr.name || '',
    animated: tokenStatusMap.value[tr.source_node_id] === 'active',
    style: tokenStatusMap.value[tr.source_node_id] === 'completed'
      ? { stroke: '#9ca3af', opacity: 0.5 }
      : undefined,
  })),
)
</script>

<template>
  <div class="process-monitor-graph">
    <VueFlow
      :nodes="nodes"
      :edges="edges"
      :node-types="nodeTypes"
      :nodes-draggable="false"
      :nodes-connectable="false"
      :edges-updatable="false"
      :zoom-on-scroll="true"
      :pan-on-scroll="true"
      fit-view-on-init
    >
      <Background />
      <Controls :show-interactive="false" />
      <MiniMap />
    </VueFlow>
  </div>
</template>

<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
@import '@vue-flow/controls/dist/style.css';
@import '@vue-flow/minimap/dist/style.css';
</style>

<style scoped>
.process-monitor-graph {
  width: 100%;
  height: 100%;
}
</style>
