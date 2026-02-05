<template>
  <section 
    :class="[
      'hero-section relative overflow-hidden',
      heightClass,
      textAlignClass
    ]"
    :style="sectionStyle"
  >
    <!-- Background Image -->
    <div 
      v-if="content.backgroundImage" 
      class="absolute inset-0 bg-cover bg-center"
      :style="{ backgroundImage: `url(${content.backgroundImage})` }"
    >
      <!-- Overlay -->
      <div 
        v-if="settings.overlay"
        class="absolute inset-0 bg-black"
        :style="{ opacity: settings.overlayOpacity || 0.5 }"
      ></div>
    </div>

    <!-- Content Container -->
    <div class="relative z-10 container mx-auto px-4 h-full flex items-center justify-center">
      <div class="max-w-4xl">
        <h1 
          v-if="content.title" 
          class="text-4xl md:text-6xl font-bold text-white mb-4"
          v-html="content.title"
        ></h1>
        
        <p 
          v-if="content.subtitle" 
          class="text-xl md:text-2xl text-white/90 mb-8"
          v-html="content.subtitle"
        ></p>
        
        <div v-if="content.ctaText && content.ctaLink" class="flex gap-4" :class="buttonAlignClass">
          <router-link 
            :to="content.ctaLink"
            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition-colors"
          >
            {{ content.ctaText }}
          </router-link>
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
      ctaText: '',
      ctaLink: '',
      backgroundImage: '',
    })
  },
  settings: {
    type: Object,
    default: () => ({
      height: 'full', // sm, md, lg, full
      textAlign: 'center', // left, center, right
      overlay: true,
      overlayOpacity: 0.5,
    })
  }
})

const heightClass = computed(() => {
  const heights = {
    sm: 'min-h-[400px]',
    md: 'min-h-[600px]',
    lg: 'min-h-[800px]',
    full: 'min-h-screen',
  }
  return heights[props.settings.height] || 'min-h-screen'
})

const textAlignClass = computed(() => {
  const aligns = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
  }
  return aligns[props.settings.textAlign] || 'text-center'
})

const buttonAlignClass = computed(() => {
  const aligns = {
    left: 'justify-start',
    center: 'justify-center',
    right: 'justify-end',
  }
  return aligns[props.settings.textAlign] || 'justify-center'
})

const sectionStyle = computed(() => {
  return {
    backgroundColor: props.settings.backgroundColor || 'transparent',
  }
})
</script>

<style scoped>
.hero-section {
  position: relative;
}
</style>
