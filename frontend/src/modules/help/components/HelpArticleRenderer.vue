<script setup lang="ts">
defineProps<{
  html: string
}>()
</script>

<template>
  <div class="help-article-content" v-html="html" />
</template>

<style scoped>
.help-article-content {
  line-height: 1.7;
  color: var(--p-text-color);
}

.help-article-content :deep(h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin-top: 2rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--p-surface-border);
  color: var(--p-text-color);
}

.help-article-content :deep(h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
  color: var(--p-text-color);
}

.help-article-content :deep(p) {
  margin-bottom: 1rem;
}

.help-article-content :deep(ul),
.help-article-content :deep(ol) {
  margin-bottom: 1rem;
  padding-left: 1.5rem;
}

.help-article-content :deep(li) {
  margin-bottom: 0.35rem;
}

.help-article-content :deep(code) {
  background: var(--p-surface-ground);
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  font-size: 0.875em;
  font-family: monospace;
}

.help-article-content :deep(pre) {
  background: var(--p-surface-ground);
  padding: 1rem;
  border-radius: var(--p-content-border-radius);
  overflow-x: auto;
  margin-bottom: 1rem;
}

.help-article-content :deep(pre code) {
  background: none;
  padding: 0;
}

.help-article-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
}

.help-article-content :deep(th),
.help-article-content :deep(td) {
  border: 1px solid var(--p-surface-border);
  padding: 0.5rem 0.75rem;
  text-align: left;
}

.help-article-content :deep(th) {
  background: var(--p-surface-ground);
  font-weight: 600;
}

.help-article-content :deep(a) {
  color: var(--p-primary-color);
  text-decoration: none;
}

.help-article-content :deep(a:hover) {
  text-decoration: underline;
}

.help-article-content :deep(blockquote) {
  border-left: 4px solid var(--p-primary-color);
  margin: 1rem 0;
  padding: 0.5rem 1rem;
  background: var(--p-surface-ground);
  border-radius: 0 var(--p-content-border-radius) var(--p-content-border-radius) 0;
}
</style>
