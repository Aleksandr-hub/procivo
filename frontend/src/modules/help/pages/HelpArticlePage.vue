<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import HelpArticleRenderer from '../components/HelpArticleRenderer.vue'
import { getArticleBySlug } from '../data/articles'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const slug = computed(() => {
  const params = route.params.slug
  if (Array.isArray(params)) return params.join('/')
  return params ?? ''
})

const article = computed(() => getArticleBySlug(slug.value))

const breadcrumbItems = computed(() => {
  if (!article.value) return []

  return [
    {
      label: t(`help.categories.${article.value.category === 'user-guide' ? 'userGuide' : 'adminGuide'}`),
    },
    {
      label: t(`help.subcategories.${article.value.subcategory}`),
    },
    {
      label: article.value.title,
    },
  ]
})

const breadcrumbHome = computed(() => ({
  icon: 'pi pi-question-circle',
  command: () => router.push({ name: 'help-center' }),
}))

const relatedArticles = computed(() => {
  if (!article.value || !article.value.related?.length) return []
  return article.value.related
    .map((slug) => getArticleBySlug(slug))
    .filter((a): a is NonNullable<typeof a> => !!a)
})

function navigateToRelated(relatedSlug: string) {
  router.push({ name: 'help-article', params: { slug: relatedSlug.split('/') } })
}

function goBack() {
  router.push({ name: 'help-center' })
}
</script>

<template>
  <div class="help-article-page">
    <template v-if="article">
      <Breadcrumb :model="breadcrumbItems" :home="breadcrumbHome" class="article-breadcrumb" />

      <div class="article-header">
        <h1 class="article-title">{{ article.title }}</h1>
        <p class="article-description">{{ article.description }}</p>
        <div class="article-meta">
          <Tag :value="article.module" severity="info" />
          <Tag v-for="role in article.roles" :key="role" :value="role" severity="secondary" />
          <span class="meta-date">{{ article.lastUpdated }}</span>
        </div>
      </div>

      <Divider />

      <HelpArticleRenderer :html="article.html" />

      <!-- Related Articles -->
      <div v-if="relatedArticles.length > 0" class="related-section">
        <Divider />
        <h3 class="related-title">{{ t('help.relatedArticles') }}</h3>
        <div class="related-list">
          <div
            v-for="related in relatedArticles"
            :key="related.slug"
            class="related-item"
            @click="navigateToRelated(related.slug)"
          >
            <i class="pi pi-file" />
            <span>{{ related.title }}</span>
          </div>
        </div>
      </div>

      <div class="back-link">
        <Button
          :label="t('help.backToCenter')"
          icon="pi pi-arrow-left"
          text
          @click="goBack"
        />
      </div>
    </template>

    <!-- 404 State -->
    <template v-else>
      <div class="not-found">
        <i class="pi pi-exclamation-triangle not-found-icon" />
        <h2>{{ t('help.articleNotFound') }}</h2>
        <Button
          :label="t('help.backToCenter')"
          icon="pi pi-arrow-left"
          @click="goBack"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.help-article-page {
  padding: 1.5rem;
  max-width: 800px;
}

.article-breadcrumb {
  margin-bottom: 1.5rem;
  background: transparent;
  padding: 0;
}

.article-header {
  margin-bottom: 0.5rem;
}

.article-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  color: var(--p-text-color);
}

.article-description {
  font-size: 1rem;
  color: var(--p-text-muted-color);
  margin: 0 0 1rem 0;
}

.article-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.meta-date {
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  margin-left: auto;
}

.related-section {
  margin-top: 2rem;
}

.related-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: var(--p-text-color);
}

.related-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.related-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: var(--p-content-border-radius);
  cursor: pointer;
  color: var(--p-primary-color);
  transition: background-color 0.15s;
}

.related-item:hover {
  background: var(--p-content-hover-background);
}

.back-link {
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid var(--p-surface-border);
}

.not-found {
  text-align: center;
  padding: 4rem 2rem;
}

.not-found-icon {
  font-size: 3rem;
  color: var(--p-text-muted-color);
  margin-bottom: 1rem;
}

.not-found h2 {
  color: var(--p-text-muted-color);
  margin-bottom: 1.5rem;
}
</style>
