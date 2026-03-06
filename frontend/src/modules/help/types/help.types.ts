export type HelpModule =
  | 'Identity'
  | 'Organization'
  | 'TaskManager'
  | 'Workflow'
  | 'Notification'
  | 'Dashboard'
  | 'Shared'
  | 'Infrastructure'

export type HelpRole = 'employee' | 'manager' | 'admin'

export type HelpCategoryKey = 'user-guide' | 'admin-guide'

export interface ArticleMeta {
  title: string
  description: string
  module: HelpModule
  feature: string
  roles: HelpRole[]
  category: HelpCategoryKey
  subcategory: string
  order: number
  keywords: string[]
  related: string[]
  lastUpdated: string
}

export interface HelpArticle extends ArticleMeta {
  slug: string
  body: string
  html: string
}

export interface HelpCategory {
  key: string
  label: string
  icon: string
  articleCount: number
}
