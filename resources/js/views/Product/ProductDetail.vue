<template>
  <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="w-full mx-auto py-8 lg:py-16">
      <div class="max-w-6xl mx-auto">
        <!-- Judul Produk -->
        <h3 class="text-2xl text-center font-bold mb-4 text-gray-900 pt-20 sm:pt-10">
          {{ selectedProduct?.title || product.title }}
        </h3>

        <!-- Gambar Produk -->
        <Transition name="fade-scale">
          <div v-if="selectedProduct?.image || product.image" class="w-full flex justify-center">
            <img 
              :src="selectedProduct?.image || product.image" 
              :alt="selectedProduct?.title || product.title"
              class="w-full max-w-xs sm:max-w-sm md:max-w-md object-contain" 
            />
          </div>
        </Transition>

        <!-- Overview -->
        <Transition name="fade-slide-up">
          <div class="w-full mt-8 px-2 sm:px-4">
            <h2 class="text-lg sm:text-xl text-center font-semibold mb-2">{{ $t('projectInvestment.tabs.overview') }}</h2>
            <p class="text-gray-800 text-sm sm:text-base text-justify leading-relaxed">
              {{ selectedProduct?.descriptions || product.descriptions }}
            </p>
          </div>
        </Transition>

        <!-- Features & Specification -->
        <div class="flex flex-col lg:flex-row gap-8 pt-8">
          <!-- Features -->
          <Transition name="fade-slide-left">
            <div class="flex-1">
              <div v-if="features.length">
                <h3 class="text-lg sm:text-2xl font-bold mb-4 text-gray-900">{{ $t('labels.features') }}</h3>
                <div class="overflow-x-auto">
                  <table class="table-auto w-full text-sm text-gray-700 border-collapse">
                    <tbody>
                      <tr v-for="(fitur, index) in features" :key="index">
                        <td class="border-b border-gray-800 px-3 py-2">{{ fitur }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Kalau benar-benar tidak ada fitur -->
              <div v-else class="text-gray-500 text-sm">
                {{ $t('ui.noFeaturesAreListedForThisProduct') }}
              </div>
            </div>
          </Transition>

          <!-- Divider -->
          <div class="hidden lg:block w-px bg-gray-800"></div>

          <!-- Specification -->
          <Transition name="fade-slide-right">
            <div class="flex-1">
              <h2 class="text-lg sm:text-2xl font-bold mb-4 text-gray-900">{{ $t('ui.specification') }}</h2>
              <div class="overflow-x-auto">
                <table class="table-auto w-full text-sm text-gray-700 border-collapse">
                  <tbody>
                    <tr v-for="(spec, idx) in specificationList" :key="idx">
                      <td class="font-semibold border-b border-r border-gray-800 px-2 py-2">
                        {{ spec.label }}
                      </td>
                      <td class="border-b border-gray-800 px-2 py-2">
                        <div v-if="Array.isArray(spec.value)">
                          <ul class="list-disc pl-4 space-y-1">
                            <li v-for="(val, i) in spec.value" :key="i">{{ val }}</li>
                          </ul>
                        </div>
                        <span v-else>{{ spec.value }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Diagram Section -->
        <div class="w-full mt-10 px-2 sm:px-4">
          <h2 class="text-lg sm:text-xl font-semibold text-center mb-2">
            {{ selectedProduct?.title || product.title }}<br />
            {{ diagramCategoryLabel }} {{ diagramSubCategoryLabel }} {{ $t('ui.networkDiagram') }}
          </h2>
          <!-- Multi-Diagram Tabs -->
          <div v-if="diagramList.length > 1" class="flex justify-center gap-2 mb-4 flex-wrap">
            <button
              v-for="(diag, idx) in diagramList"
              :key="idx"
              @click="activeDiagramIndex = idx"
              :class="[
                'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                activeDiagramIndex === idx
                  ? 'bg-blue-600 text-white shadow-md'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              Diagram {{ idx + 1 }} ({{ diag }})
            </button>
          </div>

          <Transition name="fade-scale">
            <template v-if="effectiveProduct">
              <OpticLine 
                v-if="diagramType.includes('xgspon') || diagramType.includes('optical')" 
                :product="effectiveProductForDiagram"
                :diagram="activeDiagram" 
              />
              <GponLine 
                v-else-if="diagramType.includes('gpon')" 
                :product="effectiveProductForDiagram"
                :diagram="activeDiagram" 
              />
              <SwitchLine 
                v-else-if="diagramType.includes('switch')" 
                :product="effectiveProductForDiagram"
                :diagram="activeDiagram" 
              />
              <div v-else class="text-center text-gray-500 mt-4">
                {{ $t('ui.noDiagramIsAvailableForThisProductYet') }}
              </div>
            </template>
            <div v-else class="text-center text-gray-500 mt-4">
              {{ $t('ui.noDiagramIsAvailableForThisProductYet') }}
            </div>
          </Transition>

          <!-- Tombol Fullscreen -->
          <button
            class="mt-4 bg-white text-sm px-3 py-1 rounded shadow hover:bg-gray-100 border border-gray-300"
            @click.stop="openFullscreenTab"
          >
            {{ $t('ui.viewFullscreen') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import useProducts from '@/composable/useProducts.js'
import OpticLine from './components/xgsponLine.vue'
import SwitchLine from './components/SwitchLine.vue'
import GponLine from './components/GponLine.vue'

const route = useRoute()

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  category: {
    type: String,
    default: '',
  },
  sub: {
    type: String,
    default: '',
  },
})

const { products } = useProducts()

// Ambil produk sesuai slug jika ada di route
const selectedProduct = computed(() => {
  const slug = route.params.slug
  if (!slug) return props.product || null
  const list = products?.value || []
  return list.find((p) => p.slug === slug) || props.product || null
})

const effectiveProduct = computed(() => selectedProduct.value || props.product || null)

// Kategori & subkategori kontekstual dari tab/route query yang dipilih
const targetCategory = computed(() => {
  return (props.category || route.query.category || '').toString().trim()
})

const targetSubCategory = computed(() => {
  return (props.sub || route.query.sub || '').toString().trim()
})

// Label kategori untuk judul diagram
const diagramCategoryLabel = computed(() => {
  if (targetCategory.value) return targetCategory.value
  const p = effectiveProduct.value || {}
  return p.category || ''
})

// Label subkategori untuk judul diagram & diagram marker
const diagramSubCategoryLabel = computed(() => {
  if (targetSubCategory.value) return targetSubCategory.value
  const p = effectiveProduct.value || {}
  const targetCat = targetCategory.value.toLowerCase()
  if (targetCat && p.category_pairs && p.category_pairs.length > 0) {
    const pair = p.category_pairs.find(cp => cp.category_name?.toLowerCase() === targetCat)
    if (pair?.sub_category_name) return pair.sub_category_name
  }
  return p.subCategory || ''
})

// Multi-Diagram Handling: representasi setiap diagram produk dipetakan ke kategorinya
const allDiagramEntries = computed(() => {
  const p = effectiveProduct.value || {}
  const rawDiag = p.diagram || ''
  const rawNet = p.networkDiagram || p.network_diagram || ''

  const diags = (p.diagrams && p.diagrams.length > 0)
    ? p.diagrams
    : rawDiag.split('|').map(s => s.trim()).filter(Boolean)
  const nets = (p.networkDiagrams && p.networkDiagrams.length > 0)
    ? p.networkDiagrams
    : rawNet.split('|').map(s => s.trim()).filter(Boolean)

  const rawCats = (p.rawCategory || p.category || '').split('|').map(s => s.trim()).filter(Boolean)
  const rawSubs = (p.rawSubCategory || p.subCategory || '').split('|').map(s => s.trim()).filter(Boolean)

  const count = Math.max(diags.length, nets.length, rawCats.length, 1)
  const entries = []

  for (let i = 0; i < count; i++) {
    const d = diags[i] || diags[0] || rawDiag || ''
    const n = nets[i] || nets[0] || rawNet || ''
    const cat = rawCats[i] || rawCats[0] || ''
    const sub = rawSubs[i] || rawSubs[0] || ''

    let type = ''
    const lowerD = d.toLowerCase()
    const lowerN = n.toLowerCase()
    const lowerC = cat.toLowerCase()

    if (lowerD.includes('xgsp') || lowerN.includes('xgsp') || lowerC.includes('xgsp') || lowerD.includes('optic') || lowerN.includes('optic')) {
      type = 'xgspon'
    } else if (lowerD.includes('gpon') || lowerN.includes('gpon') || lowerC.includes('gpon')) {
      type = 'gpon'
    } else if (lowerD.includes('switch') || lowerN.includes('switch') || lowerC.includes('switch') || lowerD.includes('access') || lowerN.includes('access') || lowerD.includes('core') || lowerN.includes('core')) {
      type = 'switch'
    } else {
      type = lowerC || ''
    }

    entries.push({
      diagram: d,
      networkDiagram: n,
      category: cat,
      subCategory: sub,
      type: type,
    })
  }

  return entries
})

// Filter diagram entries hanya yang sesuai kategori aktif yang dipilih
const diagramEntries = computed(() => {
  const all = allDiagramEntries.value
  const targetCat = targetCategory.value.toLowerCase()
  if (!targetCat) return all

  const matched = all.filter(entry => {
    const entryCat = entry.category?.toLowerCase() || ''
    const entryType = entry.type?.toLowerCase() || ''
    const entryDiag = entry.diagram?.toLowerCase() || ''
    const entryNet = entry.networkDiagram?.toLowerCase() || ''

    if (entryCat && (entryCat === targetCat || entryCat.includes(targetCat) || targetCat.includes(entryCat))) {
      return true
    }
    if (entryType && (entryType === targetCat || entryType.includes(targetCat) || targetCat.includes(entryType))) {
      return true
    }
    if (targetCat.includes('gpon') && !targetCat.includes('xgsp') && (entryDiag.includes('gpon') || entryNet.includes('gpon'))) {
      return true
    }
    if ((targetCat.includes('xgsp') || targetCat.includes('optic')) && (entryDiag.includes('xgsp') || entryNet.includes('xgsp') || entryDiag.includes('optic') || entryNet.includes('optic'))) {
      return true
    }
    if (targetCat.includes('switch') && (entryDiag.includes('switch') || entryNet.includes('switch') || entryDiag.includes('access') || entryNet.includes('access') || entryDiag.includes('core') || entryNet.includes('core'))) {
      return true
    }
    return false
  })

  return matched.length > 0 ? matched : all
})

const activeDiagramIndex = ref(0)

// Reset index saat kategori / diagram berubah
watch(diagramEntries, () => {
  activeDiagramIndex.value = 0
})

const diagramList = computed(() => {
  return diagramEntries.value.map(e => e.diagram).filter(Boolean)
})

const currentEntry = computed(() => {
  return diagramEntries.value[activeDiagramIndex.value] || diagramEntries.value[0] || null
})

const activeDiagram = computed(() => {
  return currentEntry.value?.diagram || ''
})

const activeNetworkDiagram = computed(() => {
  return currentEntry.value?.networkDiagram || ''
})

const diagramType = computed(() => {
  if (currentEntry.value?.type) return currentEntry.value.type

  const activeNet = (activeNetworkDiagram.value || '').toLowerCase()
  const activeDiag = (activeDiagram.value || '').toLowerCase()
  const raw = activeNet || activeDiag

  if (raw.includes('xgsp') || raw.includes('optical')) return 'xgspon'
  if (raw.includes('gpon')) return 'gpon'
  if (raw.includes('switch') || raw.includes('access') || raw.includes('core')) return 'switch'

  const cat = targetCategory.value.toLowerCase() || (effectiveProduct.value?.category || '').toLowerCase()
  if (cat.includes('xgsp')) return 'xgspon'
  if (cat.includes('gpon')) return 'gpon'
  if (cat.includes('switch')) return 'switch'

  return ''
})

// Objek produk khusus diagram dengan category & subCategory kontekstual agar marker akurat
const effectiveProductForDiagram = computed(() => {
  const p = effectiveProduct.value
  if (!p) return null
  return {
    ...p,
    category: diagramCategoryLabel.value || p.category,
    subCategory: diagramSubCategoryLabel.value || p.subCategory,
  }
})

// FEATURES: ambil dari fitur1..fitur20
const features = computed(() => {
  const p = effectiveProduct.value || props.product || {}
  return Array.from({ length: 20 }, (_, i) => p[`fitur${i + 1}`])
    .filter((f) => f && f !== 'null' && f !== '-')
})

// DYNAMIC SPECIFICATION LIST
// Secara otomatis mendeteksi field mana yang tersedia dan menampilkannya
const specificationList = computed(() => {
  const p = selectedProduct.value || props.product || {}
  const specs = []
  
  // Helper function to add spec only if value exists
  const addSpec = (label, value) => {
    if (value && value !== 'null' && value !== null && value !== undefined) {
      specs.push({ label, value })
    }
  }
  
  // Helper function to collect array values
  const collectArray = (...values) => {
    return values.filter(v => v && v !== 'null' && v !== null && v !== undefined)
  }
  
  // Flash Memory & SDRAM (for OLT products)
  addSpec(uiText("Memori Flash"), p.flashmemory)
  addSpec(uiText("Memori SDRAM"), p.sdrammemory)
  
  // Switching & Throughput (for Switch products)
  addSpec(uiText("Kapasitas Switching"), p.switching)
  addSpec('Throughput', p.throughput)
  
  // Wireless specs (for AP products)
  addSpec(uiText("Standar Nirkabel"), p.wirelessStandard)
  const wirelessStreams = collectArray(
    p.wirelessStream, p.wirelessStream1, p.wirelessStream2, 
    p.wirelessStream3, p.wirelessStream4, p.wirelessStream5
  )
  if (wirelessStreams.length > 0) {
    addSpec(uiText("Aliran Nirkabel"), wirelessStreams)
  }
  addSpec(uiText("Kapasitas Klien"), p.capacity)
  
  // Controller specs (for AC products)
  addSpec(uiText("Jumlah AP yang Dapat Dikelola"), p.manageableAps)
  addSpec(uiText("Jumlah Maksimal AP yang Dapat Dikonfigurasi"), p.APnumber)
  addSpec(uiText("Jumlah Maksimal Klien"), p.numberOfClients)
  
  // Interface fields - try to collect all variants
  const interfaces = collectArray(
    p.Interface, p.Interface1, p.Interface2, p.Interface3, 
    p.Interface4, p.Interface5
  )
  if (interfaces.length > 0) {
    addSpec(uiText("Antarmuka"), interfaces)
  }
  
  // Control Unit (for specific switch models)
  const controlUnits = collectArray(p.cu, p.cu1, p.cu2, p.cu3, p.cu4)
  if (controlUnits.length > 0) {
    addSpec(uiText("Unit Kontrol"), controlUnits)
  }
  
  // Additional Interface
  const additionalInterfaces = collectArray(
    p.aditionalinterface1, p.aditionalinterface2, 
    p.aditionalinterface3, p.aditionalinterface4
  )
  if (additionalInterfaces.length > 0) {
    addSpec(uiText("Antarmuka Tambahan"), additionalInterfaces)
  }
  
  // Network specs
  addSpec(uiText("Alamat MAC"), p.macaddress)
  addSpec(uiText("Tabel Perutean"), p.routingtable)
  
  // Antenna
  addSpec(uiText("Antena"), p.antena)
  
  // Ratings
  addSpec(uiText("Tingkat Perlindungan IP"), p.iprating)
  addSpec(uiText("Ketahanan Debu dan Air"), p.dustproofwaterproof)
  addSpec(uiText("Kebisingan"), p.noise)
  
  // Temperature & Environment
  addSpec(uiText("Suhu Operasional"), p.operatingtemperature)
  addSpec(uiText("Suhu Penyimpanan"), p.storagetemperature)
  addSpec(uiText("Kelembapan"), p.operatinghumidity)
  
  // Power
  const powerSpecs = collectArray(p.power1, p.power2, p.power3)
  if (powerSpecs.length > 0) {
    addSpec(uiText("Daya"), powerSpecs)
  }
  addSpec(uiText("Konsumsi Daya"), p.powercomsumptions)
  
  // General specs (spec1 to spec20) if available
  for (let i = 1; i <= 20; i++) {
    const val = p[`spec${i}`]
    if (val && val !== 'null' && val !== '-' && typeof val === 'string' && val.trim()) {
      addSpec(`Spesifikasi ${i}`, val.trim())
    }
  }

  return specs
})

function openFullscreenTab() {
  const p = effectiveProduct.value
  const diagram = activeDiagram.value || p?.diagram || ''
  const slug = encodeURIComponent(p?.slug || '')
  const cat = encodeURIComponent(diagramCategoryLabel.value || '')
  const sub = encodeURIComponent(diagramSubCategoryLabel.value || '')
  const url = `/diagram-fullscreen?slug=${slug}&diagram=${encodeURIComponent(diagram)}&category=${cat}&sub=${sub}`
  window.open(url, '_blank')
}
</script>

<style scoped>
/* Animasi Fade + Scale */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.4s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Slide Up */
.fade-slide-up-enter-active {
  transition: all 0.5s ease;
}

.fade-slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

/* Slide Left */
.fade-slide-left-enter-active {
  transition: all 0.5s ease;
}

.fade-slide-left-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

/* Slide Right */
.fade-slide-right-enter-active {
  transition: all 0.5s ease;
}

.fade-slide-right-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
</style>
