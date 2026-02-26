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
