<template>
  <section class="py-12 px-4 sm:px-6" id="Product">
    <!-- Title -->
    <div class="text-center mb-10">
      <h2 class="text-4xl font-bold text-gray-800">Our Service & Solutions</h2>
      <p class="text-gray-500 text-lg mt-2">Make it easier</p>
    </div>

    <!-- Tabs -->
    <div class="flex justify-center">
      <div class="w-full max-w-6xl">
        <ul class="flex flex-wrap justify-center md:justify-start gap-3 sm:gap-4 mb-6 sm:mb-8">
          <li
            v-for="feature in features"
            :key="feature.id"
            :class="[
              'px-5 py-2.5 rounded-lg text-sm font-medium cursor-pointer transition-all',
              isOpen === feature.id
                ? 'bg-gray-900 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            ]"
            @click.prevent="isOpen = feature.id"
          >
            {{ feature.name }}
          </li>
        </ul>

        <!-- Feature Card -->
        <Transition name="fade" mode="out-in">
          <div
            v-if="activeFeature"
            :key="activeFeature.id"
            class="w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col md:flex-row min-h-[400px] md:min-h-[300px] transition-all duration-300"
          >
            <!-- Gambar -->
            <div class="md:w-1/2">
              <img
                :src="activeFeature.details.imageUrl"
                :alt="activeFeature.details.title"
                class="w-full h-60 md:h-full object-cover md:max-h-[300px]"
              />
            </div>

            <!-- Konten -->
            <div class="p-6 sm:p-8 md:w-1/2 flex flex-col">
              <h3 class="text-xl font-semibold text-indigo-600 uppercase tracking-wide mb-2">
                {{ activeFeature.details.title }}
              </h3>
              <p
                class="text-gray-600 text-base leading-relaxed overflow-auto max-h-[180px] md:max-h-[200px]"
              >
                {{ activeFeature.details.description }}
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import useSolutions from '@/composable/useSolutions.js'

const { solutions: features } = useSolutions()

const isOpen = ref(1)
const activeFeature = computed(() => features.value?.find((feature) => feature.id === isOpen.value))
</script>

<style scoped>
/* Smooth fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
