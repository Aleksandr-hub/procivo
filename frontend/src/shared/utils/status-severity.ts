export function instanceStatusSeverity(status: string): string {
  switch (status) {
    case 'running': return 'info'
    case 'completed': return 'success'
    case 'cancelled': return 'warn'
    case 'error': return 'danger'
    default: return 'info'
  }
}

export function tokenStatusSeverity(status: string): string {
  switch (status) {
    case 'active': return 'info'
    case 'waiting': return 'warn'
    case 'completed': return 'success'
    case 'cancelled': return 'danger'
    default: return 'info'
  }
}

export function definitionStatusSeverity(status: string): string {
  switch (status) {
    case 'published': return 'success'
    case 'archived': return 'warn'
    default: return 'info'
  }
}

export function taskStatusSeverity(status: string): string {
  const map: Record<string, string> = {
    draft: 'secondary', open: 'info', in_progress: 'warn',
    review: 'info', done: 'success', blocked: 'danger', cancelled: 'secondary',
  }
  return map[status] ?? 'info'
}

export function taskPrioritySeverity(priority: string): string {
  const map: Record<string, string> = {
    low: 'secondary', medium: 'info', high: 'warn', critical: 'danger',
  }
  return map[priority] ?? 'info'
}
