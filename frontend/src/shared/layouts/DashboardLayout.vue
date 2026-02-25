<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from '@/shared/components/AppSidebar.vue'
import AppTopbar from '@/shared/components/AppTopbar.vue'

const sidebarVisible = ref(true)

function toggleSidebar() {
  sidebarVisible.value = !sidebarVisible.value
}
</script>

<template>
  <div class="layout-wrapper">
    <AppSidebar :visible="sidebarVisible" />
    <div class="layout-content" :class="{ 'sidebar-collapsed': !sidebarVisible }">
      <AppTopbar @toggle-sidebar="toggleSidebar" />
      <main class="layout-main">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.layout-wrapper {
  min-height: 100vh;
}

.layout-content {
  margin-left: 250px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}

.layout-content.sidebar-collapsed {
  margin-left: 0;
}

.layout-main {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}
</style>
