<template>
  <section class="features-section py-16 px-4" :style="sectionStyle">
    <div class="container mx-auto max-w-7xl">
      <!-- Section Header -->
      <div v-if="content.title || content.subtitle" class="text-center mb-12">
        <h2 v-if="content.title" class="text-3xl md:text-4xl font-bold mb-4">
          {{ content.title }}
        </h2>
        <p v-if="content.subtitle" class="text-xl text-gray-600">
          {{ content.subtitle }}
        </p>
      </div>

      <!-- Features Grid -->
      <div 
        :class="[
          'grid gap-8',
          gridClass
        ]"
      >
        <div 
          v-for="(feature, index) in content.features" 
          :key="index"
          class="feature-card bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow"
        >
          <!-- Icon -->
          <div v-if="feature.icon" class="mb-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">{{ getIcon(feature.icon) }}</span>
            </div>
          </div>

          <!-- Title -->
          <h3 v-if="feature.title" class="text-xl font-semibold mb-3">
            {{ feature.title }}
          </h3>

          <!-- Description -->
          <p v-if="feature.description" class="text-gray-600">
            {{ feature.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  content: {
    type: Object,
    default: () => ({
      title: '',
      subtitle: '',
      features: []
    })
  },
  settings: {
    type: Object,
    default: () => ({
      columns: 3, // 1, 2, 3, 4
      layout: 'grid', // grid, list
      backgroundColor: '#F9FAFB',
    })
  }
})

const gridClass = computed(() => {
  const columns = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
  }
  return columns[props.settings.columns] || 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
})

const sectionStyle = computed(() => {
  return {
    backgroundColor: props.settings.backgroundColor || '#F9FAFB',
  }
})

const getIcon = (iconName) => {
  const icons = {
    check: '✓',
    star: '⭐',
    heart: '❤️',
    support: '🎧',
    delivery: '🚚',
    quality: '⚡',
    secure: '🔒',
    fast: '⚡',
  }
  return icons[iconName] || '📌'
}
</script>
