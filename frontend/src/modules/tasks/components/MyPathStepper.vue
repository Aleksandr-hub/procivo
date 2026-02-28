<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { formatDateTime } from '@/shared/utils/date-format'

export interface StepperStep {
  nodeId: string
  nodeName: string
  status: 'completed' | 'current'
  completedAt?: string
  actionLabel?: string
}

defineProps<{
  steps: StepperStep[]
}>()

const { t } = useI18n()

function buildTooltip(step: StepperStep): string {
  const parts = [step.nodeName]
  if (step.completedAt) {
    parts.push(t('stepper.completedAt', { time: formatDateTime(step.completedAt) }))
  }
  if (step.actionLabel) {
    parts.push(t('stepper.action', { action: step.actionLabel }))
  }
  return parts.join('\n')
}
</script>

<template>
  <div class="stepper-container">
    <div class="stepper-track">
      <div
        v-for="(step, index) in steps"
        :key="step.nodeId"
        class="stepper-step"
      >
        <!-- Connector line (not before first step) -->
        <div
          v-if="index > 0"
          class="connector"
          :class="{ completed: step.status === 'completed' }"
        />

        <!-- Step circle -->
        <div
          class="step-circle"
          :class="step.status"
          v-tooltip.top="step.status === 'completed' ? buildTooltip(step) : undefined"
        >
          <i v-if="step.status === 'completed'" class="pi pi-check" />
          <span v-else class="pulse-dot" />
        </div>

        <!-- Step label -->
        <span class="step-label" :class="step.status">
          {{ step.nodeName }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stepper-container {
  overflow-x: auto;
  padding: 0.5rem 0 1rem;
  margin-bottom: 1rem;
}

.stepper-track {
  display: flex;
  align-items: flex-start;
  min-width: min-content;
}

.stepper-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex-shrink: 0;
}

.connector {
  position: absolute;
  top: 14px;
  right: 50%;
  width: 100%;
  height: 2px;
  background: var(--p-surface-300);
  z-index: 0;
}

.connector.completed {
  background: var(--p-green-500);
}

.step-circle {
  position: relative;
  z-index: 1;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.step-circle.completed {
  background: var(--p-green-500);
  color: white;
  cursor: pointer;
}

.step-circle.completed .pi {
  font-size: 0.75rem;
}

.step-circle.current {
  background: transparent;
  border: 2px solid var(--p-blue-500);
}

.pulse-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--p-blue-500);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.85); }
}

.step-label {
  margin-top: 0.4rem;
  font-size: 0.72rem;
  color: var(--p-text-muted-color);
  text-align: center;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 0 0.5rem;
}

.step-label.current {
  color: var(--p-blue-500);
  font-weight: 600;
}

.step-label.completed {
  color: var(--p-text-color);
}

/* Adjust spacing between steps */
.stepper-step + .stepper-step {
  margin-left: 1rem;
}

/* Connector sizing for adjacent steps */
.stepper-step + .stepper-step .connector {
  width: calc(100% + 1rem);
  right: calc(50% + 14px);
}
</style>
