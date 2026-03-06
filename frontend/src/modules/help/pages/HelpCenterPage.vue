<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import HelpSearchBar from '../components/HelpSearchBar.vue'
import HelpCategoryCard from '../components/HelpCategoryCard.vue'
import {
  categories,
  getArticlesByCategory,
} from '../data/articles'
import type { HelpArticle } from '../types/help.types'

const { t } = useI18n()
const router = useRouter()

const userGuideArticles = computed(() => getArticlesByCategory('user-guide'))
const adminGuideArticles = computed(() => getArticlesByCategory('admin-guide'))

// Group articles by subcategory
function groupBySubcategory(articles: HelpArticle[]): Map<string, HelpArticle[]> {
  const map = new Map<string, HelpArticle[]>()
  for (const article of articles) {
    const key = article.subcategory
    const group = map.get(key) ?? []
    group.push(article)
    map.set(key, group)
  }
  return map
}

const userGuideGroups = computed(() => groupBySubcategory(userGuideArticles.value))
const adminGuideGroups = computed(() => groupBySubcategory(adminGuideArticles.value))

function navigateToArticle(article: HelpArticle) {
  router.push({ name: 'help-article', params: { slug: article.slug.split('/') } })
}

function onCategorySelect(key: string) {
  selectedCategory.value = selectedCategory.value === key ? null : key
}

function subcategoryLabel(key: string): string {
  const i18nKey = `help.subcategories.${key}`
  const translated = t(i18nKey)
  // Fallback to key if no translation found
  return translated === i18nKey ? key : translated
}
</script>

<template>
  <div class="help-center">
    <div class="help-header">
      <h1 class="help-title">{{ t('help.title') }}</h1>
      <HelpSearchBar />
    </div>

    <!-- Category Cards -->
    <div class="categories-grid">
      <HelpCategoryCard
        v-for="cat in categories"
        :key="cat.key"
        :category="cat"
        @select="onCategorySelect"
      />
    </div>

    <!-- User Guide Section -->
    <section v-if="userGuideArticles.length > 0" class="category-section">
      <h2 class="section-title">
        <i class="pi pi-book" />
        {{ t('help.categories.userGuide') }}
      </h2>

      <div v-for="[subcategory, articles] in userGuideGroups" :key="subcategory" class="subcategory-group">
        <h3 class="subcategory-title">{{ subcategoryLabel(subcategory) }}</h3>
        <div class="article-list">
          <div
            v-for="article in articles"
            :key="article.slug"
            class="article-item"
            @click="navigateToArticle(article)"
          >
            <i class="pi pi-file article-icon" />
            <div class="article-info">
              <span class="article-title">{{ article.title }}</span>
              <span class="article-description">{{ article.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Admin Guide Section -->
    <section v-if="adminGuideArticles.length > 0" class="category-section">
      <h2 class="section-title">
        <i class="pi pi-cog" />
        {{ t('help.categories.adminGuide') }}
      </h2>

      <div v-for="[subcategory, articles] in adminGuideGroups" :key="subcategory" class="subcategory-group">
        <h3 class="subcategory-title">{{ subcategoryLabel(subcategory) }}</h3>
        <div class="article-list">
          <div
            v-for="article in articles"
            :key="article.slug"
            class="article-item"
            @click="navigateToArticle(article)"
          >
            <i class="pi pi-file article-icon" />
            <div class="article-info">
              <span class="article-title">{{ article.title }}</span>
              <span class="article-description">{{ article.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Empty state -->
    <div v-if="categories.length === 0" class="empty-state">
      <i class="pi pi-inbox empty-icon" />
      <p>{{ t('help.search.noResults') }}</p>
    </div>
  </div>
</template>

<style scoped>
.help-center {
  padding: 1.5rem;
  max-width: 960px;
}

.help-header {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.help-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
  color: var(--p-text-color);
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.category-section {
  margin-bottom: 2rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--p-text-color);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--p-surface-border);
}

.subcategory-group {
  margin-bottom: 1.5rem;
}

.subcategory-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.8rem;
}

.article-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.article-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: var(--p-content-border-radius);
  cursor: pointer;
  transition: background-color 0.15s;
}

.article-item:hover {
  background: var(--p-content-hover-background);
}

.article-icon {
  color: var(--p-text-muted-color);
  margin-top: 0.15rem;
  flex-shrink: 0;
}

.article-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.article-title {
  font-weight: 500;
  color: var(--p-text-color);
}

.article-description {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: var(--p-text-muted-color);
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}
</style>
