<template>
  <div class="sidebar-section">
    <!-- Section Title -->
    <div 
      v-if="title && !collapsed" 
      class="section-title"
    >
      {{ title }}
    </div>
    
    <!-- Section Items -->
    <div class="section-items">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: ''
  },
  collapsed: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.sidebar-section {
  margin-bottom: 0.5rem;
}

.section-title {
  padding: 0.75rem 1.25rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #1a2954;
}

.section-items {
  display: flex;
  flex-direction: column;
}
</style>
