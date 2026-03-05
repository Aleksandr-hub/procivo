<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import { useAuthStore } from '@/modules/auth/stores/auth.store'

const { t } = useI18n()
const auth = useAuthStore()

const impersonatedUserName = computed(() => {
  if (!auth.impersonatedUser) return ''
  return `${auth.impersonatedUser.firstName} ${auth.impersonatedUser.lastName}`
})
</script>

<template>
  <div v-if="auth.isImpersonating" class="impersonation-banner">
    <i class="pi pi-exclamation-triangle" />
    <span>{{ t('impersonation.viewing', { name: impersonatedUserName }) }}</span>
    <Button
      :label="t('impersonation.exit')"
      size="small"
      severity="contrast"
      @click="auth.exitImpersonation()"
    />
  </div>
</template>

<style scoped>
.impersonation-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  height: 40px;
  background-color: var(--p-orange-500, #f59e0b);
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
}

.impersonation-banner .pi {
  font-size: 1rem;
}
</style>
