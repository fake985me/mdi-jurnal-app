<script setup>
import Sidebar from './Sidebar.vue'

defineEmits(['close'])
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black/40 lg:hidden" @click.self="$emit('close')">
    <aside class="w-64 bg-white h-full shadow-lg">
      <Sidebar :collapsed="false" :is-super-admin="true" />
    </aside>
  </div>
</template>
