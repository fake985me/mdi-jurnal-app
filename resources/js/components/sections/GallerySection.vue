<template>
  <section class="gallery-section py-16 px-4" :style="sectionStyle">
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

      <!-- Gallery Grid -->
      <div 
        :class="[
          'grid gap-6',
          gridClass
        ]"
      >
        <div 
          v-for="(image, index) in content.images" 
          :key="index"
          class="gallery-item group relative overflow-hidden rounded-lg shadow-md hover:shadow-xl transition-all cursor-pointer"
          @click="openLightbox(index)"
        >
          <img 
            :src="image.url || image" 
            :alt="image.caption || `Gallery image ${index + 1}`"
            class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300"
          />
          
          <!-- Caption Overlay -->
          <div 
            v-if="image.caption" 
            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <p class="text-sm font-medium">{{ image.caption }}</p>
          </div>
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
      images: []
    })
  },
  settings: {
    type: Object,
    default: () => ({
      columns: 3, // 2, 3, 4
      spacing: 'normal', // compact, normal, spacious
      backgroundColor: '#FFFFFF',
    })
  }
})

const gridClass = computed(() => {
  const columns = {
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
  }
  return columns[props.settings.columns] || 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
})

const sectionStyle = computed(() => {
  return {
    backgroundColor: props.settings.backgroundColor || '#FFFFFF',
  }
})

const openLightbox = (index) => {
  // Future: implement lightbox functionality
  console.log('Open image', index)
}
</script>
