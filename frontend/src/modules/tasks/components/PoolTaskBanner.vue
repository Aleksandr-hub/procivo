<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

defineProps<{
  poolDescription: string | null
  candidateCount: number
  candidates: Array<{ id: string; initials: string; fullName: string }>
  isCurrentAssignee: boolean
  assigneeId: string | null
  claimLoading: boolean
}>()

const emit = defineEmits<{
  claim: []
  unclaim: []
  assign: [employeeId: string]
}>()

const { t } = useI18n()

const selectedCandidateId = ref<string | null>(null)
const showAssignDropdown = ref(false)

const visibleAvatars = 5

function handleAssign() {
  if (selectedCandidateId.value) {
    emit('assign', selectedCandidateId.value)
    selectedCandidateId.value = null
    showAssignDropdown.value = false
  }
}
</script>

<template>
  <div class="pool-banner">
    <div class="pool-banner-left">
      <div class="pool-icon-container">
        <i class="pi pi-users" />
      </div>
      <div class="pool-info-section">
        <div class="pool-text">
          <span class="pool-label">{{ t('tasks.poolTaskBadge') }}</span>
          <span v-if="poolDescription" class="pool-description">{{ poolDescription }}</span>
        </div>
        <Tag :value="`${candidateCount} ${t('tasks.candidates')}`" severity="contrast" size="small" />
      </div>

      <div v-if="candidates.length > 0" class="avatar-group">
        <div
          v-for="(candidate, index) in candidates.slice(0, visibleAvatars)"
          :key="candidate.id"
          class="candidate-avatar"
          :class="{ 'overlapping': index > 0 }"
          v-tooltip="candidate.fullName"
        >
          {{ candidate.initials }}
        </div>
        <span v-if="candidates.length > visibleAvatars" class="overflow-counter">
          +{{ candidates.length - visibleAvatars }}
        </span>
      </div>
    </div>

    <div class="pool-banner-right">
      <template v-if="!assigneeId">
        <div v-if="showAssignDropdown" class="assign-inline">
          <Select
            v-model="selectedCandidateId"
            :options="candidates"
            :option-label="(c: typeof candidates[0]) => c.fullName"
            option-value="id"
            :placeholder="t('tasks.selectEmployee')"
            size="small"
            class="assign-select"
          />
          <Button
            icon="pi pi-check"
            size="small"
            :disabled="!selectedCandidateId"
            :loading="claimLoading"
            @click="handleAssign"
          />
          <Button
            icon="pi pi-times"
            text
            size="small"
            @click="showAssignDropdown = false"
          />
        </div>
        <template v-else>
          <Button
            :label="t('tasks.assignTo')"
            text
            size="small"
            icon="pi pi-user-edit"
            @click="showAssignDropdown = true"
          />
          <Button
            :label="t('tasks.takeToWork')"
            size="small"
            icon="pi pi-user-plus"
            :loading="claimLoading"
            @click="emit('claim')"
          />
        </template>
      </template>
      <template v-else-if="isCurrentAssignee">
        <Button
          :label="t('tasks.returnToQueue')"
          severity="secondary"
          size="small"
          icon="pi pi-undo"
          :loading="claimLoading"
          @click="emit('unclaim')"
        />
      </template>
    </div>
  </div>
</template>

<style scoped>
.pool-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-radius: var(--p-border-radius);
  background: linear-gradient(
    135deg,
    color-mix(in srgb, var(--p-blue-500) 10%, transparent) 0%,
    color-mix(in srgb, var(--p-purple-500) 10%, transparent) 100%
  );
  border: 1px solid color-mix(in srgb, var(--p-blue-500) 30%, transparent);
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

:root.p-dark .pool-banner {
  background: linear-gradient(
    135deg,
    color-mix(in srgb, var(--p-blue-400) 12%, transparent) 0%,
    color-mix(in srgb, var(--p-purple-400) 12%, transparent) 100%
  );
  border-color: color-mix(in srgb, var(--p-blue-400) 25%, transparent);
}

.pool-banner-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.pool-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  background: color-mix(in srgb, var(--p-blue-500) 15%, transparent);
  color: var(--p-blue-600);
  font-size: 1.25rem;
  flex-shrink: 0;
}

:root.p-dark .pool-icon-container {
  background: color-mix(in srgb, var(--p-blue-400) 20%, transparent);
  color: var(--p-blue-300);
}

.pool-info-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.pool-text {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.pool-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--p-blue-700);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

:root.p-dark .pool-label {
  color: var(--p-blue-300);
}

.pool-description {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.avatar-group {
  display: flex;
  align-items: center;
}

.candidate-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  font-size: 0.65rem;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, var(--p-blue-400), var(--p-purple-400));
  border: 2px solid var(--p-surface-card);
  cursor: default;
}

.candidate-avatar.overlapping {
  margin-left: -0.5rem;
}

.overflow-counter {
  margin-left: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  background: var(--p-surface-200);
  border-radius: 50%;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

:root.p-dark .overflow-counter {
  background: var(--p-surface-700);
}

.pool-banner-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.assign-inline {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.assign-select {
  min-width: 180px;
}
</style>
