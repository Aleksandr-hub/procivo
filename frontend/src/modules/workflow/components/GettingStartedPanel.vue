<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineEmits<{
  close: []
}>()

const { t } = useI18n()

const steps = [
  'workflow.gettingStartedStep1',
  'workflow.gettingStartedStep2',
  'workflow.gettingStartedStep3',
  'workflow.gettingStartedStep4',
  'workflow.gettingStartedStep5',
  'workflow.gettingStartedStep6',
]
</script>

<template>
  <div class="getting-started">
    <div class="getting-started-header">
      <i class="pi pi-info-circle" />
      <strong>{{ t('workflow.gettingStarted') }}</strong>
      <Button icon="pi pi-times" text size="small" class="close-btn" @click="$emit('close')" />
    </div>
    <ol class="steps-list">
      <li v-for="(step, i) in steps" :key="i">{{ t(step) }}</li>
    </ol>

    <div class="gateway-section">
      <strong class="gateway-title">
        <i class="pi pi-directions" />
        {{ t('workflow.gettingStartedGatewaysTitle') }}
      </strong>
      <div class="gateway-tip tip-simple">
        <i class="pi pi-check-circle" />
        <span>{{ t('workflow.gettingStartedSimpleFlow') }}</span>
      </div>
      <div class="gateway-tip tip-xor">
        <i class="pi pi-directions" />
        <span>{{ t('workflow.gettingStartedXorWhen') }}</span>
      </div>
      <div class="gateway-tip tip-and">
        <i class="pi pi-clone" />
        <span>{{ t('workflow.gettingStartedAndWhen') }}</span>
      </div>
      <div class="gateway-tip tip-note">
        <i class="pi pi-lightbulb" />
        <span>{{ t('workflow.gettingStartedNoGateway') }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.getting-started {
  background: var(--p-surface-50);
  border-bottom: 1px solid var(--p-surface-border);
  padding: 0.75rem 1rem;
}

.getting-started-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  color: var(--p-primary-color);
}

.close-btn {
  margin-left: auto;
}

.steps-list {
  margin: 0 0 0.75rem;
  padding-left: 1.5rem;
  font-size: 0.8125rem;
  color: var(--p-text-secondary-color);
  line-height: 1.6;
}

.gateway-section {
  border-top: 1px solid var(--p-surface-200);
  padding-top: 0.75rem;
}

.gateway-title {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: var(--p-primary-color);
  margin-bottom: 0.5rem;
}

.gateway-tip {
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
  font-size: 0.8125rem;
  color: var(--p-text-secondary-color);
  line-height: 1.5;
  padding: 0.375rem 0.5rem;
  border-radius: 6px;
  margin-bottom: 0.375rem;
}

.gateway-tip i {
  margin-top: 0.125rem;
  flex-shrink: 0;
}

.tip-simple {
  background: var(--p-green-50);
  color: var(--p-green-700);
}

.tip-xor {
  background: var(--p-blue-50);
  color: var(--p-blue-700);
}

.tip-and {
  background: var(--p-orange-50);
  color: var(--p-orange-700);
}

.tip-note {
  background: var(--p-yellow-50);
  color: var(--p-yellow-700);
}
</style>
