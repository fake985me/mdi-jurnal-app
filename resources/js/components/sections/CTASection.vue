<template>
  <section 
    class="cta-section py-16 px-4"
    :style="sectionStyle"
  >
    <div class="container mx-auto max-w-4xl text-center">
      <!-- Title -->
      <h2 
        v-if="content.title" 
        class="text-3xl md:text-4xl font-bold mb-4"
        :style="{ color: settings.textColor || '#FFFFFF' }"
      >
        {{ content.title }}
      </h2>

      <!-- Description -->
      <p 
        v-if="content.description" 
        class="text-lg md:text-xl mb-8 opacity-90"
        :style="{ color: settings.textColor || '#FFFFFF' }"
      >
        {{ content.description }}
      </p>

      <!-- CTA Button -->
      <div v-if="content.buttonText && content.buttonLink" class="flex justify-center gap-4">
        <router-link 
          :to="content.buttonLink"
          class="px-8 py-3 bg-white text-indigo-600 hover:bg-gray-100 rounded-lg font-semibold transition-colors shadow-lg"
        >
          {{ content.buttonText }}
        </router-link>
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
      description: '',
      buttonText: '',
      buttonLink: '',
    })
  },
  settings: {
    type: Object,
    default: () => ({
      backgroundColor: '#4F46E5',
      textColor: '#FFFFFF',
    })
  }
})

const sectionStyle = computed(() => {
  return {
    backgroundColor: props.settings.backgroundColor || '#4F46E5',
    color: props.settings.textColor || '#FFFFFF',
  }
})
</script>
