<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  processName: string
  currentStageName: string
  completedStepCount: number
  processInstanceUrl: string
  nextStepName?: string
}>()

const { t } = useI18n()
</script>

<template>
  <div class="process-context-card">
    <div class="context-section">
      <div class="context-icon">
        <i class="pi pi-sitemap" />
      </div>
      <div class="context-info">
        <span class="context-label">{{ t('process.processLabel') }}</span>
        <span class="context-value">{{ processName }}</span>
      </div>
    </div>

    <div class="context-section">
      <div class="context-info">
        <span class="context-label">{{ t('process.currentStage') }}</span>
        <span class="context-value current-stage">{{ currentStageName }}</span>
      </div>
    </div>

    <div class="context-section context-section--right">
      <div class="context-info step-info">
        <span class="step-text">{{ t('process.step', { n: completedStepCount }) }}</span>
        <ProgressBar
          :value="completedStepCount * 10"
          :show-value="false"
          class="step-progress"
        />
        <span v-if="nextStepName" class="next-step-text">
          {{ t('process.nextStep') }}: {{ nextStepName }}
        </span>
      </div>
      <router-link
        :to="processInstanceUrl"
        class="view-process-link"
      >
        <i class="pi pi-external-link" />
        {{ t('process.viewProcess') }}
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.process-context-card {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.25rem;
  background: linear-gradient(
    135deg,
    color-mix(in srgb, var(--p-purple-500) 5%, transparent) 0%,
    color-mix(in srgb, var(--p-blue-500) 5%, transparent) 100%
  );
  border: 1px solid color-mix(in srgb, var(--p-purple-500) 20%, transparent);
  border-radius: 0.75rem;
  margin-bottom: 1rem;
}

:root.p-dark .process-context-card {
  background: linear-gradient(
    135deg,
    color-mix(in srgb, var(--p-purple-400) 8%, transparent) 0%,
    color-mix(in srgb, var(--p-blue-400) 8%, transparent) 100%
  );
  border-color: color-mix(in srgb, var(--p-purple-400) 25%, transparent);
}

.context-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.context-section--right {
  margin-left: auto;
  flex-shrink: 0;
  min-width: 200px;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.context-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  background: color-mix(in srgb, var(--p-purple-500) 12%, transparent);
  color: var(--p-purple-600);
  font-size: 1rem;
  flex-shrink: 0;
}

:root.p-dark .context-icon {
  background: color-mix(in srgb, var(--p-purple-400) 20%, transparent);
  color: var(--p-purple-300);
}

.context-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.context-label {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.context-value {
  font-size: 0.9rem;
  font-weight: 600;
}

.current-stage {
  color: var(--p-purple-600);
}

:root.p-dark .current-stage {
  color: var(--p-purple-300);
}

.step-info {
  min-width: 120px;
  align-items: flex-end;
}

.step-text {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--p-text-color);
}

.step-progress {
  height: 4px;
  margin-top: 0.25rem;
  width: 100%;
}

.next-step-text {
  font-size: 0.7rem;
  color: var(--p-text-muted-color);
  margin-top: 0.15rem;
}

.view-process-link {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: var(--p-purple-600);
  text-decoration: none;
  white-space: nowrap;
  font-weight: 500;
}

:root.p-dark .view-process-link {
  color: var(--p-purple-300);
}

.view-process-link:hover {
  text-decoration: underline;
}

@media (max-width: 640px) {
  .process-context-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .context-section--right {
    margin-left: 0;
    width: 100%;
    align-items: flex-start;
  }
}
</style>
