<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { processTemplates, type ProcessTemplate } from '@/modules/workflow/data/process-templates'

defineEmits<{
  select: [template: ProcessTemplate]
  close: []
}>()

const { t } = useI18n()

const selectedCategory = ref<string>('all')

const categories = [
  { label: t('workflow.templateCategoryAll'), value: 'all' },
  { label: t('workflow.templateCategoryGeneral'), value: 'general' },
  { label: t('workflow.templateCategoryHr'), value: 'hr' },
  { label: t('workflow.templateCategoryFinance'), value: 'finance' },
  { label: t('workflow.templateCategoryIt'), value: 'it' },
]

const filteredTemplates = computed(() => {
  if (selectedCategory.value === 'all') return processTemplates
  return processTemplates.filter((tpl) => tpl.category === selectedCategory.value)
})
</script>

<template>
  <div class="template-gallery">
    <div class="gallery-filter">
      <SelectButton v-model="selectedCategory" :options="categories" option-label="label" option-value="value" />
    </div>

    <div class="template-grid">
      <div
        v-for="tpl in filteredTemplates"
        :key="tpl.id"
        class="template-card"
        @click="$emit('select', tpl)"
      >
        <div class="template-icon">
          <i :class="tpl.icon" />
        </div>
        <div class="template-info">
          <strong>{{ t(tpl.nameKey) }}</strong>
          <p>{{ t(tpl.descriptionKey) }}</p>
          <div class="template-meta">
            <Tag :value="t('workflow.templateCategoryLabel_' + tpl.category)" size="small" />
            <span class="node-count">{{ tpl.nodes.length }} {{ t('workflow.templateNodes') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="filteredTemplates.length === 0" class="empty-gallery">
      {{ t('workflow.noTemplatesInCategory') }}
    </div>
  </div>
</template>

<style scoped>
.template-gallery {
  min-height: 300px;
}

.gallery-filter {
  margin-bottom: 1rem;
}

.template-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.template-card {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  border: 1px solid var(--p-surface-200);
  border-radius: 8px;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.template-card:hover {
  border-color: var(--p-primary-color);
  box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
}

.template-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: var(--p-primary-50);
  color: var(--p-primary-color);
  font-size: 1.25rem;
}

.template-info {
  flex: 1;
  min-width: 0;
}

.template-info strong {
  display: block;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.template-info p {
  margin: 0 0 0.5rem;
  font-size: 0.8125rem;
  color: var(--p-text-secondary-color);
  line-height: 1.4;
}

.template-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.empty-gallery {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}
</style>
