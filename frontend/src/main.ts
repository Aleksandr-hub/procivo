import './assets/main.css'
import 'primeicons/primeicons.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import { definePreset } from '@primevue/themes'
import Aura from '@primevue/themes/aura'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'

import App from './App.vue'
import router from './router'
import { setupAuthGuard } from './router/guards'
import i18n from './i18n'

const ProcivoPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '{slate.50}',
      100: '{slate.100}',
      200: '{slate.200}',
      300: '{slate.300}',
      400: '{slate.400}',
      500: '{slate.500}',
      600: '{slate.600}',
      700: '{slate.700}',
      800: '{slate.800}',
      900: '{slate.900}',
      950: '{slate.950}',
    },
    colorScheme: {
      light: {
        primary: {
          color: '{slate.700}',
          inverseColor: '#ffffff',
          hoverColor: '{slate.800}',
          activeColor: '{slate.900}',
        },
        highlight: {
          background: '{slate.100}',
          focusBackground: '{slate.200}',
          color: '{slate.800}',
          focusColor: '{slate.900}',
        },
        surface: {
          0: '#ffffff',
          50: '{slate.50}',
          100: '{slate.100}',
          200: '{slate.200}',
          300: '{slate.300}',
          400: '{slate.400}',
          500: '{slate.500}',
          600: '{slate.600}',
          700: '{slate.700}',
          800: '{slate.800}',
          900: '{slate.900}',
          950: '{slate.950}',
        },
      },
      dark: {
        primary: {
          color: '{slate.300}',
          inverseColor: '{slate.950}',
          hoverColor: '{slate.200}',
          activeColor: '{slate.100}',
        },
        highlight: {
          background: 'color-mix(in srgb, {slate.400}, transparent 84%)',
          focusBackground: 'color-mix(in srgb, {slate.400}, transparent 76%)',
          color: 'rgba(255,255,255,.87)',
          focusColor: 'rgba(255,255,255,.87)',
        },
        surface: {
          0: '#ffffff',
          50: '{zinc.50}',
          100: '{zinc.100}',
          200: '{zinc.200}',
          300: '{zinc.300}',
          400: '{zinc.400}',
          500: '{zinc.500}',
          600: '{zinc.600}',
          700: '{zinc.700}',
          800: '{zinc.800}',
          900: '{zinc.900}',
          950: '{zinc.950}',
        },
      },
    },
  },
})

const app = createApp(App)

app.use(createPinia())
app.use(i18n)
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: ProcivoPreset,
    options: {
      prefix: 'p',
      darkModeSelector: '.app-dark',
    },
  },
})
app.use(ConfirmationService)
app.use(ToastService)

setupAuthGuard(router)

app.mount('#app')
