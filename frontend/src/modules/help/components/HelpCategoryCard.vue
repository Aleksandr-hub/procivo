<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { HelpCategory } from '../types/help.types'

defineProps<{
  category: HelpCategory
}>()

defineEmits<{
  select: [categoryKey: string]
}>()

const { t } = useI18n()
</script>

<template>
  <Card class="help-category-card" @click="$emit('select', category.key)">
    <template #content>
      <div class="category-content">
        <i :class="category.icon" class="category-icon" />
        <div class="category-info">
          <h3 class="category-label">{{ t(category.label) }}</h3>
          <span class="category-count">
            {{ category.articleCount }}
            {{ category.articleCount === 1 ? t('help.article') : t('help.articles') }}
          </span>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.help-category-card {
  cursor: pointer;
  transition: box-shadow 0.15s, transform 0.15s;
}

.help-category-card:hover {
  box-shadow: var(--p-overlay-select-shadow);
  transform: translateY(-2px);
}

.category-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.category-icon {
  font-size: 2rem;
  color: var(--p-primary-color);
}

.category-info {
  display: flex;
  flex-direction: column;
}

.category-label {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
  color: var(--p-text-color);
}

.category-count {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}
</style>
