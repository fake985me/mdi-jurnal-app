<template>
  <div class="w-full flex items-center justify-center bg-white relative">
    <!-- Tombol FAB -->
    <button
      @click="closewindow"
      class="fixed bottom-6 right-6 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg px-5 py-3 text-sm sm:text-base transition"
    >
      ← Kembali
    </button>

    <!-- Tampilkan diagram sesuai tipe -->
    <OpticLine
      v-if="diagramType.startsWith('xgspon_')"
      :product="selectedProduct"
      :diagram="diagramType"
    />
    <GponLine
      v-else-if="diagramType.startsWith('gpon_')"
      :product="selectedProduct"
      :diagram="diagramType"
    />
    <SwitchLine
      v-else-if="diagramType.startsWith('switch_')"
      :product="selectedProduct"
      :diagram="diagramType"
    />
    <DiagramUnavailable v-else />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import useProducts from '@/composable/useProducts.js'

import OpticLine from './xgsponLine.vue'
import SwitchLine from './SwitchLine.vue'
import DiagramUnavailable from './DiagramUnavailable.vue'
import GponLine from './GponLine.vue'

const route = useRoute()
const router = useRouter()

const slug = computed(() => route.query.slug || '')
const { products } = useProducts()

const selectedProduct = computed(() => {
  const list = products?.value || []
  return list.find((p) => p.slug === slug.value) || null
})

const diagramType = computed(() =>
  (selectedProduct.value?.diagram || '').toString().trim().toLowerCase()
)

const goBack = () => {
  if (slug.value) {
    router.push(`/product/${slug.value}`)
  } else {
    router.back()
  }
}

function closewindow() {
  window.close()
}
</script>

<style scoped>
html,
body,
#app {
  height: 100%;
  margin: 0;
  padding: 0;
}
</style>
