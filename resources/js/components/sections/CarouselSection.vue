<template>
  <div id="carousel-hero" class="bg-white text-white py-10 relative" :style="{ height: heightStyle }">
    <Transition name="fade" mode="out-in">
      <div :key="currentIndex" class="absolute inset-0">
        <!-- Background Image or Gradient -->
        <div 
          v-if="currentSlide.backgroundImage" 
          class="absolute inset-0 bg-cover bg-center z-0"
          :style="{ backgroundImage: `url(${currentSlide.backgroundImage})` }"
        ></div>
        <div 
          v-else-if="currentSlide.gradient" 
          class="absolute inset-0 z-0"
          :style="{ background: currentSlide.gradient }"
        ></div>
        <div v-else class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 z-0"></div>

        <!-- Overlay -->
        <div 
          v-if="settings.overlay" 
          class="absolute inset-0 bg-black z-0"
          :style="{ opacity: settings.overlayOpacity || 0.3 }"
        ></div>

        <!-- Content -->
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-center px-4">
          <div class="max-w-4xl mx-auto">
            <!-- Center Image -->
            <div v-if="currentSlide.centerImage" class="mb-6">
              <img :src="currentSlide.centerImage" class="max-w-md mx-auto animate-fadeInDown" />
            </div>

            <!-- Title -->
            <h1 
              v-if="currentSlide.title" 
              class="text-4xl md:text-6xl font-bold text-white mb-4 animate-fadeInUp"
              v-html="currentSlide.title"
            ></h1>
            
            <!-- Subtitle -->
            <p 
              v-if="currentSlide.subtitle" 
              class="text-xl md:text-2xl text-white/90 mb-8 animate-fadeInUp"
              v-html="currentSlide.subtitle"
            ></p>
            
            <!-- CTA Button -->
            <div v-if="currentSlide.ctaText && currentSlide.ctaLink" class="flex justify-center gap-4 animate-fadeInUp">
              <router-link 
                :to="currentSlide.ctaLink"
                class="px-8 py-3 bg-white text-indigo-600 hover:bg-gray-100 rounded-lg font-semibold transition-colors shadow-lg"
              >
                {{ currentSlide.ctaText }}
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Navigation Arrows -->
    <template v-if="slides.length > 1">
      <button 
        @click="prevSlide"
        class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-gray-800/50 hover:bg-gray-700/80 text-white p-3 rounded-full z-20 transition-colors"
      >
        ❮
      </button>
      <button 
        @click="nextSlide"
        class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-gray-800/50 hover:bg-gray-700/80 text-white p-3 rounded-full z-20 transition-colors"
      >
        ❯
      </button>

      <!-- Dots -->
      <div class="absolute bottom-6 flex gap-2 justify-center w-full z-20">
        <button 
          v-for="(slide, index) in slides" 
          :key="index"
          class="w-3 h-3 rounded-full transition-all duration-300"
          :class="index === currentIndex ? 'bg-white scale-125' : 'bg-white/50 hover:bg-white/75'"
          @click="goToSlide(index)"
        ></button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  content: {
    type: Object,
    default: () => ({
      slides: [],
    })
  },
  settings: {
    type: Object,
    default: () => ({
      height: 'full',
      autoplay: true,
      interval: 7500,
      overlay: true,
      overlayOpacity: 0.3,
    })
  }
})

const currentIndex = ref(0)
let intervalId = null

// Default slides if none provided
const defaultSlides = [
  {
    title: 'Welcome to Our Platform',
    subtitle: 'Build amazing things with our solutions',
    ctaText: 'Get Started',
    ctaLink: '/product',
    gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
  }
]

const slides = computed(() => {
  return props.content.slides?.length > 0 ? props.content.slides : defaultSlides
})

const currentSlide = computed(() => {
  return slides.value[currentIndex.value] || defaultSlides[0]
})

const heightStyle = computed(() => {
  const heights = {
    sm: '400px',
    md: '600px',
    lg: '800px',
    full: '80vh',
  }
  return heights[props.settings.height] || '80vh'
})

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % slides.value.length
}

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + slides.value.length) % slides.value.length
}

const goToSlide = (index) => {
  currentIndex.value = index
}

const startAutoSlide = () => {
  if (!props.settings.autoplay) return
  if (intervalId) clearInterval(intervalId)
  intervalId = setInterval(nextSlide, props.settings.interval || 7500)
}

onMounted(() => {
  startAutoSlide()
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>

<style scoped>
#carousel-hero {
  position: relative;
  overflow: hidden;
  min-height: 400px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeInUp {
  animation: fadeInUp 0.8s ease-out;
}

.animate-fadeInDown {
  animation: fadeInDown 0.8s ease-out;
}
</style>
