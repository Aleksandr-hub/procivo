<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Node } from '@vue-flow/core'
import TaskNodeConfig from '@/modules/workflow/components/TaskNodeConfig.vue'
import TimerNodeConfig from '@/modules/workflow/components/TimerNodeConfig.vue'
import NotificationNodeConfig from '@/modules/workflow/components/NotificationNodeConfig.vue'
import GatewayNodeConfig from '@/modules/workflow/components/GatewayNodeConfig.vue'

const props = defineProps<{
  node: Node
  readonly: boolean
}>()

const emit = defineEmits<{
  update: [data: { name: string; description: string | null; config: Record<string, unknown> }]
  delete: []
  close: []
}>()

const { t } = useI18n()

const name = ref('')
const description = ref<string | null>(null)
const config = ref<Record<string, unknown>>({})

const nodeType = computed(() => (props.node.data.nodeType as string) || (props.node.type as string))
const isGateway = computed(() => ['exclusive_gateway', 'parallel_gateway', 'inclusive_gateway'].includes(nodeType.value))
const hasConfig = computed(() => ['task', 'timer', 'notification'].includes(nodeType.value) || isGateway.value)

const nodeTypeKeyMap: Record<string, string> = {
  start: 'nodeTypeStart',
  end: 'nodeTypeEnd',
  task: 'nodeTypeTask',
  exclusive_gateway: 'nodeTypeExclusiveGateway',
  parallel_gateway: 'nodeTypeParallelGateway',
  inclusive_gateway: 'nodeTypeInclusiveGateway',
  timer: 'nodeTypeTimer',
  notification: 'nodeTypeNotification',
  webhook: 'nodeTypeWebhook',
  sub_process: 'nodeTypeSubProcess',
}

const nodeDescKeyMap: Record<string, string> = {
  start: 'nodeDescStart',
  end: 'nodeDescEnd',
  task: 'nodeDescTask',
  exclusive_gateway: 'nodeDescExclusiveGateway',
  parallel_gateway: 'nodeDescParallelGateway',
  inclusive_gateway: 'nodeDescInclusiveGateway',
  timer: 'nodeDescTimer',
  notification: 'nodeDescNotification',
  webhook: 'nodeDescWebhook',
  sub_process: 'nodeDescSubProcess',
}

const nodeTypeLabel = computed(() => {
  const key = nodeTypeKeyMap[nodeType.value]
  return key ? t(`workflow.${key}`) : nodeType.value
})

const nodeDescription = computed(() => {
  const key = nodeDescKeyMap[nodeType.value]
  return key ? t(`workflow.${key}`) : ''
})

watch(
  () => props.node,
  (node) => {
    name.value = (node.data.label as string) || ''
    description.value = (node.data.description as string | null) ?? null
    config.value = { ...((node.data.config as Record<string, unknown>) ?? {}) }
  },
  { immediate: true },
)

function onConfigUpdate(newConfig: Record<string, unknown>) {
  config.value = newConfig
}

function save() {
  emit('update', {
    name: name.value,
    description: description.value,
    config: config.value,
  })
}
</script>

<template>
  <div class="node-property-panel">
    <div class="panel-header">
      <h4>{{ t('workflow.nodeProperties') }}</h4>
      <Button icon="pi pi-times" text size="small" @click="emit('close')" />
    </div>

    <div class="panel-body">
      <Message v-if="readonly" severity="info" :closable="false" class="readonly-notice">
        {{ t('workflow.readonlyNotice') }}
      </Message>

      <div class="node-type-badge">
        <Tag :value="nodeTypeLabel" severity="secondary" />
      </div>
      <p class="node-type-desc">{{ nodeDescription }}</p>

      <Divider />

      <div class="form-group">
        <label>{{ t('workflow.name') }}</label>
        <InputText v-model="name" :disabled="readonly" class="w-full" />
      </div>

      <div class="form-group">
        <label>{{ t('workflow.description') }}</label>
        <Textarea v-model="description" :disabled="readonly" class="w-full" rows="2" />
      </div>

      <Divider v-if="hasConfig" />

      <TaskNodeConfig
        v-if="nodeType === 'task'"
        :config="config"
        :readonly="readonly"
        @update="onConfigUpdate"
      />
      <TimerNodeConfig
        v-if="nodeType === 'timer'"
        :config="config"
        :readonly="readonly"
        @update="onConfigUpdate"
      />
      <NotificationNodeConfig
        v-if="nodeType === 'notification'"
        :config="config"
        :readonly="readonly"
        @update="onConfigUpdate"
      />
      <GatewayNodeConfig
        v-if="isGateway"
        :node-type="nodeType"
      />

      <div v-if="!readonly" class="panel-actions">
        <Button :label="t('common.save')" size="small" @click="save" />
        <Button :label="t('common.delete')" size="small" severity="danger" text @click="emit('delete')" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.node-property-panel {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 340px;
  background-color: var(--p-surface-0, #ffffff);
  border-left: 1px solid var(--p-surface-border);
  box-shadow: -4px 0 12px rgb(0 0 0 / 0.08);
  z-index: 20;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--p-surface-border);
}

.panel-header h4 {
  margin: 0;
}

.panel-body {
  padding: 1rem;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.readonly-notice {
  margin-bottom: 0.75rem;
  font-size: 0.8125rem;
}

.node-type-badge {
  margin-bottom: 0.5rem;
}

.node-type-desc {
  margin: 0 0 0.25rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
  line-height: 1.5;
}

.panel-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}
</style>
