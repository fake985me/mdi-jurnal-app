<template>
  <div class="w-full flex items-center justify-center bg-white relative">
    <!-- Tombol FAB -->
    <button
      @click="closewindow"
      class="fixed bottom-6 right-6 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg px-5 py-3 text-sm sm:text-base transition"
    >
      ← Kembali
    </button>

    <!-- Multi-Diagram Switcher -->
    <div v-if="diagramList.length > 1" class="fixed top-6 z-50 flex gap-2 bg-white/90 backdrop-blur p-2 rounded-xl shadow-lg border border-gray-200">
      <button
        v-for="(diag, idx) in diagramList"
        :key="idx"
        @click="currentDiagram = diag"
        :class="[
          'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
          currentDiagram === diag
            ? 'bg-blue-600 text-white shadow-md'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]"
      >
        Diagram {{ idx + 1 }} ({{ diag }})
      </button>
    </div>

    <!-- Tampilkan diagram sesuai tipe -->
    <OpticLine
      v-if="diagramType.includes('xgspon') || diagramType.includes('optical')"
      :product="effectiveProduct"
      :diagram="currentDiagram"
    />
    <GponLine
      v-else-if="diagramType.includes('gpon')"
      :product="effectiveProduct"
      :diagram="currentDiagram"
    />
    <SwitchLine
      v-else-if="diagramType.includes('switch')"
      :product="effectiveProduct"
      :diagram="currentDiagram"
    />
    <DiagramUnavailable v-else />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import useProducts from '@/composable/useProducts.js'

import OpticLine from './xgsponLine.vue'
import SwitchLine from './SwitchLine.vue'
import DiagramUnavailable from './DiagramUnavailable.vue'
import GponLine from './GponLine.vue'

const route = useRoute()
const router = useRouter()

const slug = computed(() => route.query.slug || '')
const queryDiagram = computed(() => route.query.diagram || '')
const queryCategory = computed(() => route.query.category || '')
const querySub = computed(() => route.query.sub || '')
const { products } = useProducts()

const selectedProduct = computed(() => {
  const list = products?.value || []
  return list.find((p) => p.slug === slug.value) || null
})

const effectiveProduct = computed(() => {
  const p = selectedProduct.value
  if (!p) return null
  return {
    ...p,
    category: queryCategory.value || p.category,
    subCategory: querySub.value || p.subCategory,
  }
})

const diagramList = computed(() => {
  const p = selectedProduct.value || {}
  const rawDiag = p.diagram || ''
  const all = (p.diagrams && p.diagrams.length > 0)
    ? p.diagrams
    : rawDiag.split('|').map(s => s.trim()).filter(Boolean)

  const cat = queryCategory.value.toLowerCase()
  if (cat) {
    const filtered = all.filter(d => {
      const dl = d.toLowerCase()
      if (cat.includes('gpon') && !cat.includes('xgsp')) return dl.includes('gpon')
      if (cat.includes('xgsp') || cat.includes('optic')) return dl.includes('xgsp') || dl.includes('optic')
      if (cat.includes('switch')) return dl.includes('switch') || dl.includes('access') || dl.includes('core')
      return dl.includes(cat)
    })
    if (filtered.length > 0) return filtered
  }

  return all
})

const currentDiagram = ref(route.query.diagram || '')

watch([diagramList, queryDiagram], () => {
  if (queryDiagram.value) {
    currentDiagram.value = queryDiagram.value
  } else if (diagramList.value.length > 0 && !currentDiagram.value) {
    currentDiagram.value = diagramList.value[0]
  } else if (!currentDiagram.value && selectedProduct.value?.diagram) {
    currentDiagram.value = selectedProduct.value.diagram
  }
}, { immediate: true })

const diagramType = computed(() => {
  const current = (currentDiagram.value || '').toLowerCase()
  if (current.includes('xgsp') || current.includes('optic')) return 'xgspon'
  if (current.includes('gpon')) return 'gpon'
  if (current.includes('switch') || current.includes('access') || current.includes('core')) return 'switch'

  const cat = (queryCategory.value || selectedProduct.value?.category || '').toLowerCase()
  if (cat.includes('xgsp')) return 'xgspon'
  if (cat.includes('gpon')) return 'gpon'
  if (cat.includes('switch')) return 'switch'

  return ''
})

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
