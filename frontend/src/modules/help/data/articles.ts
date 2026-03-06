import type { HelpArticle, HelpCategory, ArticleMeta } from '../types/help.types'

interface MarkdownModule {
  attributes: Record<string, unknown>
  body: string
  html: string
}

// Import all markdown files from knowledge base at build time
// @docs alias resolves to ../docs (repo root docs directory)
const modules = import.meta.glob<MarkdownModule>('@docs/knowledge-base/**/*.md', {
  eager: true,
  import: 'default',
})

function toArticle(path: string, mod: MarkdownModule): HelpArticle {
  // Strip everything up to and including 'knowledge-base/' for slug extraction
  const slug = path.replace(/^.*knowledge-base\//, '').replace(/\.md$/, '')
  const attrs = mod.attributes as unknown as ArticleMeta

  return {
    title: attrs.title ?? '',
    description: attrs.description ?? '',
    module: attrs.module ?? 'Shared',
    feature: attrs.feature ?? '',
    roles: attrs.roles ?? [],
    category: attrs.category ?? 'user-guide',
    subcategory: attrs.subcategory ?? '',
    order: typeof attrs.order === 'number' ? attrs.order : 0,
    keywords: attrs.keywords ?? [],
    related: attrs.related ?? [],
    lastUpdated: attrs.lastUpdated ?? '',
    slug,
    body: mod.body,
    html: mod.html,
  }
}

export const articles: HelpArticle[] = Object.entries(modules)
  .map(([path, mod]) => toArticle(path, mod))
  .sort((a, b) => a.order - b.order)

export function getArticleBySlug(slug: string): HelpArticle | undefined {
  return articles.find((a) => a.slug === slug)
}

export function getArticlesByCategory(category: string): HelpArticle[] {
  return articles.filter((a) => a.category === category)
}

export function getArticlesBySubcategory(category: string, subcategory: string): HelpArticle[] {
  return articles.filter((a) => a.category === category && a.subcategory === subcategory)
}

const categoryConfig: Record<string, { label: string; icon: string }> = {
  'user-guide': { label: 'help.categories.userGuide', icon: 'pi pi-book' },
  'admin-guide': { label: 'help.categories.adminGuide', icon: 'pi pi-cog' },
}

export const categories: HelpCategory[] = (() => {
  const grouped = new Map<string, number>()
  for (const article of articles) {
    const count = grouped.get(article.category) ?? 0
    grouped.set(article.category, count + 1)
  }

  return Array.from(grouped.entries()).map(([key, articleCount]) => {
    const config = categoryConfig[key] ?? { label: key, icon: 'pi pi-folder' }
    return {
      key,
      label: config.label,
      icon: config.icon,
      articleCount,
    }
  })
})()
