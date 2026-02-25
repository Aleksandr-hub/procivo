import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePrimeVue } from 'primevue/config'
import type { SupportedLocale } from '@/i18n'
import { LOCALE_STORAGE_KEY } from '@/i18n'
import { primeVueLocales } from '@/i18n/primevue'

const SUPPORTED_LOCALES: SupportedLocale[] = ['en', 'uk']

const currentLocale = ref<SupportedLocale>('en')

let initialized = false

export function useLocale() {
  const { locale } = useI18n()
  const primevue = usePrimeVue()

  if (!initialized) {
    initialized = true

    const stored = localStorage.getItem(LOCALE_STORAGE_KEY) as SupportedLocale | null
    if (stored && SUPPORTED_LOCALES.includes(stored)) {
      currentLocale.value = stored
    }

    locale.value = currentLocale.value
    primevue.config.locale = primeVueLocales[currentLocale.value]
    document.documentElement.lang = currentLocale.value

    watch(currentLocale, (val) => {
      locale.value = val
      localStorage.setItem(LOCALE_STORAGE_KEY, val)
      primevue.config.locale = primeVueLocales[val]
      document.documentElement.lang = val
    })
  }

  function setLocale(loc: SupportedLocale) {
    currentLocale.value = loc
  }

  return {
    currentLocale,
    setLocale,
    supportedLocales: SUPPORTED_LOCALES,
  }
}
