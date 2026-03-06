import { ref } from 'vue'
import MiniSearch from 'minisearch'
import { articles } from '../data/articles'
import type { HelpArticle } from '../types/help.types'

export interface SearchResult {
  title: string
  description: string
  slug: string
  category: string
  subcategory: string
  module: string
  roles: string[]
  score: number
}

const miniSearch = new MiniSearch<HelpArticle>({
  fields: ['title', 'description', 'body', 'keywordsText'],
  storeFields: ['title', 'description', 'slug', 'category', 'subcategory', 'module', 'roles'],
  searchOptions: {
    boost: { title: 3, keywordsText: 2, description: 1.5 },
    fuzzy: 0.2,
    prefix: true,
  },
})

// Index all articles (use array index as id)
const indexed = articles.map((article, index) => ({
  ...article,
  id: index,
  keywordsText: article.keywords.join(' '),
}))

miniSearch.addAll(indexed)

export function useHelpSearch() {
  const query = ref('')
  const results = ref<SearchResult[]>([])

  function search(q: string): SearchResult[] {
    query.value = q
    if (!q.trim()) {
      results.value = []
      return []
    }

    const raw = miniSearch.search(q)
    results.value = raw.map((r) => ({
      title: r.title as string,
      description: r.description as string,
      slug: r.slug as string,
      category: r.category as string,
      subcategory: r.subcategory as string,
      module: r.module as string,
      roles: r.roles as string[],
      score: r.score,
    }))

    return results.value
  }

  return {
    query,
    results,
    search,
    articles,
  }
}
