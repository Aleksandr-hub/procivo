<script setup lang="ts">
import { ref, computed, watch, watchEffect, markRaw, type Ref, toRef } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'
import type { Node, Edge, Connection, NodeMouseEvent, EdgeMouseEvent } from '@vue-flow/core'
import { processDefinitionApi } from '@/modules/workflow/api/process-definition.api'
import { getApiErrorMessage } from '@/shared/utils/api-error'
import type { ProcessDefinitionDetailDTO, NodeType } from '@/modules/workflow/types/process-definition.types'
import { useCanvasValidation } from '@/modules/workflow/composables/useCanvasValidation'
import StartNode from '@/modules/workflow/components/nodes/StartNode.vue'
import EndNode from '@/modules/workflow/components/nodes/EndNode.vue'
import TaskNode from '@/modules/workflow/components/nodes/TaskNode.vue'
import GatewayNode from '@/modules/workflow/components/nodes/GatewayNode.vue'
import TimerNode from '@/modules/workflow/components/nodes/TimerNode.vue'
import NodePropertyPanel from '@/modules/workflow/components/NodePropertyPanel.vue'
import TransitionPropertyPanel from '@/modules/workflow/components/TransitionPropertyPanel.vue'
import GettingStartedPanel from '@/modules/workflow/components/GettingStartedPanel.vue'

const props = defineProps<{
  definition: ProcessDefinitionDetailDTO
  orgId: string
}>()

const toast = useToast()
const { t } = useI18n()
const { onConnect, onNodeDragStop } = useVueFlow()

const selectedNodeId = ref<string | null>(null)
const selectedEdgeId = ref<string | null>(null)
const showHelp = ref(false)
const isDraft = computed(() => props.definition.status === 'draft')

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

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])

const definitionRef = computed(() => props.definition)
const { validationErrors, orphanNodeIds } = useCanvasValidation(nodes as Ref<Node[]>, edges as Ref<Edge[]>, definitionRef)

const humanErrors = computed(() =>
  validationErrors.value.map((err) => t(`workflow.${err.key}`, err.params ?? {})),
)

// Propagate orphan state to node data for visual highlighting
watchEffect(() => {
  const orphans = orphanNodeIds.value
  for (const node of nodes.value) {
    const isOrphan = orphans.has(node.id)
    if (node.data.isOrphan !== isOrphan) {
      node.data = { ...node.data, isOrphan }
    }
  }
})

function syncFromDefinition() {
  nodes.value = props.definition.nodes.map((n) => ({
    id: n.id,
    type: n.type,
    position: { x: n.position_x, y: n.position_y },
    data: { label: n.name, description: n.description, config: n.config, nodeType: n.type },
  })) as Node[]

  edges.value = props.definition.transitions.map((tr) => ({
    id: tr.id,
    source: tr.source_node_id,
    target: tr.target_node_id,
    label: tr.name || tr.condition_expression || '',
    animated: !!tr.condition_expression,
  })) as Edge[]
}

watch(() => props.definition, syncFromDefinition, { immediate: true })

onConnect(async (connection: Connection) => {
  if (!isDraft.value || !connection.source || !connection.target) return
  try {
    const result = await processDefinitionApi.addTransition(props.orgId, props.definition.id, {
      source_node_id: connection.source,
      target_node_id: connection.target,
    })
    edges.value.push({
      id: result.id,
      source: connection.source,
      target: connection.target,
    })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
})

onNodeDragStop(async ({ node }) => {
  if (!isDraft.value) return
  try {
    await processDefinitionApi.updateNode(props.orgId, props.definition.id, node.id, {
      name: node.data.label as string,
      description: (node.data.description as string | null) ?? null,
      config: (node.data.config as Record<string, unknown>) ?? {},
      position_x: node.position.x,
      position_y: node.position.y,
    })
  } catch {
    // Silent fail for position update
  }
})

const nodePalette: { type: NodeType; labelKey: string; icon: string; tooltipKey: string }[] = [
  { type: 'start', labelKey: 'workflow.paletteStart', icon: 'pi pi-play', tooltipKey: 'workflow.paletteStartTooltip' },
  { type: 'end', labelKey: 'workflow.paletteEnd', icon: 'pi pi-stop', tooltipKey: 'workflow.paletteEndTooltip' },
  { type: 'task', labelKey: 'workflow.paletteTask', icon: 'pi pi-file', tooltipKey: 'workflow.paletteTaskTooltip' },
  { type: 'exclusive_gateway', labelKey: 'workflow.paletteXor', icon: 'pi pi-directions', tooltipKey: 'workflow.paletteXorTooltip' },
  { type: 'parallel_gateway', labelKey: 'workflow.paletteAnd', icon: 'pi pi-clone', tooltipKey: 'workflow.paletteAndTooltip' },
  { type: 'timer', labelKey: 'workflow.paletteTimer', icon: 'pi pi-clock', tooltipKey: 'workflow.paletteTimerTooltip' },
  { type: 'notification', labelKey: 'workflow.paletteNotify', icon: 'pi pi-bell', tooltipKey: 'workflow.paletteNotifyTooltip' },
]

async function addNodeFromPalette(type: NodeType) {
  if (!isDraft.value) return
  const paletteItem = nodePalette.find((p) => p.type === type)
  const name = paletteItem ? t(paletteItem.labelKey) : type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
  const posX = 250 + Math.random() * 200
  const posY = 150 + Math.random() * 200
  try {
    const result = await processDefinitionApi.addNode(props.orgId, props.definition.id, {
      type,
      name,
      position_x: posX,
      position_y: posY,
    })
    nodes.value.push({
      id: result.id,
      type,
      position: { x: posX, y: posY },
      data: { label: name, description: null, config: {}, nodeType: type },
    })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

function onNodeClick({ node }: NodeMouseEvent) {
  selectedNodeId.value = node.id
  selectedEdgeId.value = null
}

function onEdgeClick({ edge }: EdgeMouseEvent) {
  selectedEdgeId.value = edge.id
  selectedNodeId.value = null
}

function onPaneClick() {
  selectedNodeId.value = null
  selectedEdgeId.value = null
}

async function deleteNode(nodeId: string) {
  if (!isDraft.value) return
  try {
    await processDefinitionApi.removeNode(props.orgId, props.definition.id, nodeId)
    // @ts-expect-error vue-flow deep type instantiation with reactive Node[]
    nodes.value = nodes.value.filter((n: Node) => n.id !== nodeId)
    edges.value = edges.value.filter((e: Edge) => e.source !== nodeId && e.target !== nodeId)
    selectedNodeId.value = null
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

async function updateNodeData(nodeId: string, data: { name: string; description: string | null; config: Record<string, unknown> }) {
  if (!isDraft.value) return
  const node = nodes.value.find((n) => n.id === nodeId)
  if (!node) return
  try {
    await processDefinitionApi.updateNode(props.orgId, props.definition.id, nodeId, {
      name: data.name,
      description: data.description,
      config: data.config,
      position_x: node.position.x,
      position_y: node.position.y,
    })
    node.data = { ...node.data, label: data.name, description: data.description, config: data.config }
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.nodeUpdated'), life: 2000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}

function onTransitionUpdate(edgeId: string, data: { name: string; condition_expression: string | null }) {
  const edge = edges.value.find((e) => e.id === edgeId)
  if (edge) {
    edge.label = data.name || data.condition_expression || ''
    edge.animated = !!data.condition_expression
  }
}

function onTransitionDelete(edgeId: string) {
  edges.value = edges.value.filter((e) => e.id !== edgeId)
  selectedEdgeId.value = null
}

const selectedNode = computed(() => nodes.value.find((n) => n.id === selectedNodeId.value) || null)
const selectedEdge = computed(() => edges.value.find((e) => e.id === selectedEdgeId.value) || null)
</script>

<template>
  <div class="workflow-designer">
    <div class="node-palette">
      <template v-if="isDraft">
        <Button
          v-for="item in nodePalette"
          :key="item.type"
          :icon="item.icon"
          :label="t(item.labelKey)"
          v-tooltip.bottom="t(item.tooltipKey)"
          text
          size="small"
          @click="addNodeFromPalette(item.type)"
        />
      </template>
      <template v-else>
        <span class="readonly-hint">
          <i class="pi pi-info-circle" />
          {{ t('workflow.clickNodeHint') }}
        </span>
      </template>
      <div class="palette-spacer" />
      <Button
        icon="pi pi-question-circle"
        v-tooltip.bottom="t('workflow.gettingStarted')"
        text
        size="small"
        @click="showHelp = !showHelp"
      />
    </div>

    <GettingStartedPanel v-if="showHelp || (isDraft && nodes.length === 0)" @close="showHelp = false" />

    <Message v-if="isDraft && humanErrors.length > 0 && nodes.length > 0" severity="warn" :closable="false" class="validation-banner">
      <div>
        <strong>{{ t('workflow.validationWarnings') }}:</strong>
        <ul class="validation-list">
          <li v-for="(err, i) in humanErrors" :key="i">{{ err }}</li>
        </ul>
      </div>
    </Message>

    <div class="canvas-container">
      <VueFlow
        v-model:nodes="nodes"
        v-model:edges="edges"
        :node-types="nodeTypes"
        :nodes-draggable="isDraft"
        :nodes-connectable="isDraft"
        :edges-updatable="isDraft"
        fit-view-on-init
        @node-click="onNodeClick"
        @edge-click="onEdgeClick"
        @pane-click="onPaneClick"
      >
        <Background />
        <Controls />
        <MiniMap />
      </VueFlow>
    </div>

    <NodePropertyPanel
      v-if="selectedNode"
      :node="selectedNode"
      :org-id="orgId"
      :readonly="!isDraft"
      @update="updateNodeData(selectedNode!.id, $event)"
      @delete="deleteNode(selectedNode!.id)"
      @close="selectedNodeId = null"
    />

    <TransitionPropertyPanel
      v-if="selectedEdge && !selectedNode"
      :edge="selectedEdge"
      :definition="definition"
      :org-id="orgId"
      :readonly="!isDraft"
      @update="onTransitionUpdate(selectedEdge!.id, $event)"
      @delete="onTransitionDelete(selectedEdge!.id)"
      @close="selectedEdgeId = null"
    />
  </div>
</template>

<style scoped>
.workflow-designer {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
}

.node-palette {
  display: flex;
  gap: 0.25rem;
  padding: 0.5rem;
  border-bottom: 1px solid var(--p-surface-border);
  background: var(--p-surface-ground);
}

.palette-spacer {
  flex: 1;
}

.readonly-hint {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
  padding: 0 0.25rem;
}

.validation-banner {
  margin: 0;
  border-radius: 0;
}

.validation-list {
  margin: 0.25rem 0 0;
  padding-left: 1.25rem;
  font-size: 0.8125rem;
}

.validation-list li {
  margin-bottom: 0.125rem;
}

.canvas-container {
  flex: 1;
  min-height: 400px;
}
</style>

<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
@import '@vue-flow/controls/dist/style.css';
@import '@vue-flow/minimap/dist/style.css';
</style>
