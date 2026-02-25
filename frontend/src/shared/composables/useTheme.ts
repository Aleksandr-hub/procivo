import { ref, watch } from 'vue'

const STORAGE_KEY = 'procivo-theme'

const isDark = ref(false)

let initialized = false

function initTheme(): void {
  if (initialized) return
  initialized = true

  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored) {
    isDark.value = stored === 'dark'
  } else {
    isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  applyTheme()

  watch(isDark, () => {
    applyTheme()
    localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light')
  })
}

function applyTheme(): void {
  document.documentElement.classList.toggle('app-dark', isDark.value)
}

function toggle(): void {
  isDark.value = !isDark.value
}

export function useTheme() {
  initTheme()

  return {
    isDark,
    toggle,
  }
}
