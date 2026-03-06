import { type Readonly, type Ref, ref, watch } from 'vue'

const STORAGE_KEY = 'procivo-sidebar-expanded'

const expanded = ref(localStorage.getItem(STORAGE_KEY) !== 'false')

watch(expanded, (value) => {
  localStorage.setItem(STORAGE_KEY, String(value))
})

function toggle(): void {
  expanded.value = !expanded.value
}

export function useCollapsibleSidebar(): {
  expanded: Readonly<Ref<boolean>>
  toggle: () => void
} {
  return {
    expanded,
    toggle,
  }
}
