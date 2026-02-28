<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import TaskDetailContent from '@/modules/tasks/components/TaskDetailContent.vue'

defineProps<{
  taskId: string
}>()

const route = useRoute()
const router = useRouter()

const orgId = computed(() => route.params.orgId as string)

function onClose() {
  router.push({ name: 'tasks', params: { orgId: orgId.value } })
}
</script>

<template>
  <div class="full-page-wrapper">
    <TaskDetailContent
      :org-id="orgId"
      :task-id="taskId"
      mode="full"
      @close="onClose"
    />
  </div>
</template>

<style scoped>
.full-page-wrapper {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}
</style>
