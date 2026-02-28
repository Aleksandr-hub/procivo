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
  <div class="stepper-card">
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
            :class="step.status"
          />

          <!-- Step circle -->
          <div
            class="step-circle"
            :class="step.status"
            v-tooltip.top="step.status === 'completed' ? buildTooltip(step) : undefined"
          >
            <i v-if="step.status === 'completed'" class="pi pi-check" />
            <span v-else class="pulse-dot">
              <span class="ping" />
            </span>
          </div>

          <!-- Step label -->
          <span class="step-label" :class="step.status">
            {{ step.nodeName }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stepper-card {
  border: 1px solid var(--p-surface-200);
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
  background: var(--p-surface-0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

:root.p-dark .stepper-card {
  border-color: var(--p-surface-600);
  background: var(--p-surface-800);
}

.stepper-container {
  overflow-x: auto;
  padding: 0.25rem 0;
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
  top: 12px;
  right: 50%;
  width: 100%;
  height: 1px;
  z-index: 0;
}

.connector.completed {
  background: var(--p-green-500);
}

.connector.current {
  background: transparent;
  border-top: 1px dashed var(--p-surface-300);
}

.step-circle {
  position: relative;
  z-index: 1;
  width: 24px;
  height: 24px;
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
  font-size: 0.65rem;
}

.step-circle.current {
  background: transparent;
  border: 2px solid var(--p-blue-500);
}

.pulse-dot {
  position: relative;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--p-blue-500);
}

.ping {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: var(--p-blue-500);
  animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

.step-label {
  margin-top: 0.4rem;
  font-size: 0.72rem;
  color: var(--p-text-muted-color);
  text-align: center;
  max-width: 120px;
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
  width: 3rem;
  right: calc(50% + 12px);
}
</style>
