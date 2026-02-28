<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  processName: string
  currentStageName: string
  completedStepCount: number
  processInstanceUrl: string
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
      </div>
      <a
        :href="processInstanceUrl"
        target="_blank"
        rel="noopener"
        class="view-process-link"
      >
        <i class="pi pi-external-link" />
        {{ t('process.viewProcess') }}
      </a>
    </div>
  </div>
</template>

<style scoped>
.process-context-card {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, var(--p-primary-50) 0%, var(--p-purple-50) 100%);
  border: 1px solid var(--p-primary-100);
  border-radius: var(--p-border-radius);
  margin-bottom: 1rem;
}

.context-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.context-section--right {
  margin-left: auto;
  flex-shrink: 0;
  gap: 1rem;
}

.context-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
  color: var(--p-primary-color);
  font-size: 0.9rem;
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
  color: var(--p-primary-color);
}

.step-info {
  min-width: 80px;
}

.step-text {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--p-text-color);
}

.step-progress {
  height: 4px;
  margin-top: 0.25rem;
}

.view-process-link {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: var(--p-primary-color);
  text-decoration: none;
  white-space: nowrap;
  font-weight: 500;
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
    justify-content: space-between;
  }
}
</style>
