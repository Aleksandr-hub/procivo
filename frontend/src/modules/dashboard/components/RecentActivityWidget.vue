<script setup lang="ts">
import AuditLogTimeline from '@/modules/audit/components/AuditLogTimeline.vue'

defineProps<{
  orgId: string
}>()
</script>

<template>
  <div class="recent-activity-widget">
    <AuditLogTimeline :org-id="orgId" :limit="20" />
  </div>
</template>

<style scoped>
.recent-activity-widget {
  overflow-y: auto;
  max-height: 320px;
}
</style>
