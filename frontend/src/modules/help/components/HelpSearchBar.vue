<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useHelpSearch } from '../composables/useHelpSearch'
import type { SearchResult } from '../composables/useHelpSearch'

const { t } = useI18n()
const router = useRouter()
const { search } = useHelpSearch()

const query = ref('')
const results = ref<SearchResult[]>([])
const showResults = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(query, (val) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    results.value = search(val)
    showResults.value = results.value.length > 0 || val.trim().length > 0
  }, 200)
})

function navigateToArticle(result: SearchResult) {
  query.value = ''
  results.value = []
  showResults.value = false
  router.push({ name: 'help-article', params: { slug: result.slug.split('/') } })
}

function onBlur() {
  // Delay to allow click on result
  setTimeout(() => {
    showResults.value = false
  }, 200)
}

function onFocus() {
  if (results.value.length > 0 || query.value.trim().length > 0) {
    showResults.value = true
  }
}
</script>

<template>
  <div class="help-search-bar">
    <IconField>
      <InputIcon class="pi pi-search" />
      <InputText
        v-model="query"
        :placeholder="t('help.search.placeholder')"
        class="help-search-input"
        @blur="onBlur"
        @focus="onFocus"
      />
    </IconField>

    <div v-if="showResults" class="search-results-dropdown">
      <div v-if="results.length === 0" class="no-results">
        {{ t('help.search.noResults') }}
      </div>
      <div
        v-for="result in results"
        :key="result.slug"
        class="search-result-item"
        @mousedown.prevent="navigateToArticle(result)"
      >
        <div class="result-title">{{ result.title }}</div>
        <div class="result-meta">
          <Tag :value="t(`help.categories.${result.category === 'user-guide' ? 'userGuide' : 'adminGuide'}`)" severity="info" class="result-tag" />
          <span class="result-description">{{ result.description }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.help-search-bar {
  position: relative;
  width: 100%;
  max-width: 480px;
}

.help-search-input {
  width: 100%;
}

.search-results-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 10;
  background: var(--p-surface-overlay);
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-content-border-radius);
  box-shadow: var(--p-overlay-select-shadow);
  margin-top: 4px;
  max-height: 320px;
  overflow-y: auto;
}

.no-results {
  padding: 1rem;
  text-align: center;
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
}

.search-result-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid var(--p-surface-border);
  transition: background-color 0.15s;
}

.search-result-item:last-child {
  border-bottom: none;
}

.search-result-item:hover {
  background: var(--p-content-hover-background);
}

.result-title {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--p-text-color);
  margin-bottom: 0.25rem;
}

.result-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.result-tag {
  font-size: 0.7rem;
}

.result-description {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
