import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import uk from './locales/uk.json'

export type SupportedLocale = 'en' | 'uk'

export const LOCALE_STORAGE_KEY = 'procivo-locale'

const savedLocale = localStorage.getItem(LOCALE_STORAGE_KEY) as SupportedLocale | null

const i18n = createI18n({
  legacy: false,
  locale: savedLocale && ['en', 'uk'].includes(savedLocale) ? savedLocale : 'en',
  fallbackLocale: 'en',
  messages: { en, uk },
})

export default i18n
