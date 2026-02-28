export function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '\u2014'
  return new Date(dateStr).toLocaleDateString()
}

export function formatDateTime(dateStr: string | null | undefined): string {
  if (!dateStr) return '\u2014'
  return new Date(dateStr).toLocaleString()
}

export function isOverdue(dueDate: string | null | undefined): boolean {
  if (!dueDate) return false
  return new Date(dueDate) < new Date()
}

export function formatRelativeDate(dateStr: string): string {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours}h ago`
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}
