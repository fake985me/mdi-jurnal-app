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
        <p v-if="loading" class="text-center text-gray-500">Loading solutions...</p>
        <p v-else-if="error" class="text-center text-red-600">{{ error }}</p>
        <ul class="flex flex-wrap justify-center md:justify-start gap-3 sm:gap-4 mb-6 sm:mb-8">
          <li
            v-for="feature in features"
            :key="feature.id"
            v-show="!loading && !error"
            :class="[
              'px-5 py-2.5 rounded-lg text-sm font-medium cursor-pointer transition-all',
              isOpen === feature.id
                ? 'bg-gray-900 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            ]"
            @click.prevent="isOpen = feature.id"
          >
            {{ feature.title }}
          </li>
        </ul>

        <!-- Feature Card -->
        <Transition name="fade" mode="out-in">
          <div
            v-if="activeFeature && !loading && !error"
            :key="activeFeature.id"
            class="w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col md:flex-row min-h-[400px] md:min-h-[300px] transition-all duration-300"
          >
            <!-- Gambar -->
            <div v-if="activeFeature.image_url" class="md:w-1/2">
              <img
                v-if="activeFeature.image_url"
                :src="activeFeature.image_url"
                :alt="activeFeature.title"
                class="w-full h-60 md:h-full object-cover md:max-h-[300px]"
              />
            </div>

            <!-- Konten -->
            <div
              :class="[
                'p-6 sm:p-8 flex flex-col',
                activeFeature.image_url ? 'md:w-1/2' : 'w-full',
              ]"
            >
              <h3 class="text-xl font-semibold text-indigo-600 uppercase tracking-wide mb-2">
                {{ activeFeature.title }}
              </h3>
              <p
                class="text-gray-600 text-base leading-relaxed overflow-auto max-h-[180px] md:max-h-[200px]"
              >
                {{ activeFeature.description }}
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import useSolutions from '@/composable/useSolutions.js'

const { solutions: features, loading, error } = useSolutions()

const isOpen = ref(null)
const activeFeature = computed(
  () => features.value.find((feature) => feature.id === isOpen.value) || features.value[0],
)

watch(features, (solutions) => {
  if (!solutions.some((solution) => solution.id === isOpen.value)) {
    isOpen.value = solutions[0]?.id ?? null
  }
}, { immediate: true })
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
