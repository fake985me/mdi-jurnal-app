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
            <h2 class="text-lg sm:text-xl text-center font-semibold mb-2">Overview</h2>
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
                <h3 class="text-lg sm:text-2xl font-bold mb-4 text-gray-900">Features</h3>
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
                Tidak ada fitur yang terdaftar untuk produk ini.
              </div>
            </div>
          </Transition>

          <!-- Divider -->
          <div class="hidden lg:block w-px bg-gray-800"></div>

          <!-- Specification -->
          <Transition name="fade-slide-right">
            <div class="flex-1">
              <h2 class="text-lg sm:text-2xl font-bold mb-4 text-gray-900">Specification</h2>
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
            {{ selectedProduct?.category || product.category }} {{ selectedProduct?.subCategory || product.subCategory }} Network Diagram
          </h2>
          <Transition name="fade-scale">
            <template v-if="effectiveProduct">
              <OpticLine 
                v-if="diagramType.includes('xgspon') || diagramType.includes('optical')" 
                :product="effectiveProduct"
                :diagram="effectiveProduct.diagram" 
              />
              <GponLine 
                v-else-if="diagramType.includes('gpon')" 
                :product="effectiveProduct"
                :diagram="effectiveProduct.diagram" 
              />
              <SwitchLine 
                v-else-if="diagramType.includes('switch')" 
                :product="effectiveProduct"
                :diagram="effectiveProduct.diagram" 
              />
              <div v-else class="text-center text-gray-500 mt-4">
                Diagram belum tersedia untuk produk ini.
              </div>
            </template>
            <div v-else class="text-center text-gray-500 mt-4">
              Diagram belum tersedia untuk produk ini.
            </div>
          </Transition>

          <!-- Tombol Fullscreen -->
          <button
            class="mt-4 bg-white text-sm px-3 py-1 rounded shadow hover:bg-gray-100 border border-gray-300"
            @click.stop="openFullscreenTab"
          >
            Lihat Penuh ⤢
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
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

// Tentukan tipe diagram
const diagramType = computed(() => {
  const p = effectiveProduct.value || {}
  
  // Prioritas dari networkDiagram / network_diagram / diagram
  const raw = (
    p.networkDiagram ||
    p.network_diagram ||
    p.diagram
  )?.toString().trim().toLowerCase()
  
  if (raw) return raw
  
  // Fallback dari kategori
  const cat = (p.category || '').toString().trim().toLowerCase()
  const sub = (p.subCategory || '').toString().trim().toLowerCase()
  
  if (cat.includes('xgsp') || sub.includes('xgsp')) return 'xgspon'
  if (cat.includes('gpon') || sub.includes('gpon')) return 'gpon'
  if (cat.includes('switch') || sub.includes('switch')) return 'switch'
  
  return ''
})

// FEATURES: ambil dari fitur1..fitur15
const features = computed(() => {
  return Array.from({ length: 15 }, (_, i) => props.product?.[`fitur${i + 1}`])
    .filter((f) => f && f !== 'null')
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
  addSpec('Flash Memory', p.flashmemory)
  addSpec('SDRAM Memory', p.sdrammemory)
  
  // Switching & Throughput (for Switch products)
  addSpec('Switching Capacity', p.switching)
  addSpec('Throughput', p.throughput)
  
  // Wireless specs (for AP products)
  addSpec('Wireless Standard', p.wirelessStandard)
  const wirelessStreams = collectArray(
    p.wirelessStream, p.wirelessStream1, p.wirelessStream2, 
    p.wirelessStream3, p.wirelessStream4, p.wirelessStream5
  )
  if (wirelessStreams.length > 0) {
    addSpec('Wireless Stream', wirelessStreams)
  }
  addSpec('Client Capacity', p.capacity)
  
  // Controller specs (for AC products)
  addSpec('Number of Manageable APs', p.manageableAps)
  addSpec('Maximum Configurable AP Number', p.APnumber)
  addSpec('Maximum Number Of Clients', p.numberOfClients)
  
  // Interface fields - try to collect all variants
  const interfaces = collectArray(
    p.Interface, p.Interface1, p.Interface2, p.Interface3, 
    p.Interface4, p.Interface5
  )
  if (interfaces.length > 0) {
    addSpec('Interface', interfaces)
  }
  
  // Control Unit (for specific switch models)
  const controlUnits = collectArray(p.cu, p.cu1, p.cu2, p.cu3, p.cu4)
  if (controlUnits.length > 0) {
    addSpec('Control Unit', controlUnits)
  }
  
  // Additional Interface
  const additionalInterfaces = collectArray(
    p.aditionalinterface1, p.aditionalinterface2, 
    p.aditionalinterface3, p.aditionalinterface4
  )
  if (additionalInterfaces.length > 0) {
    addSpec('Additional Interface', additionalInterfaces)
  }
  
  // Network specs
  addSpec('MAC Address', p.macaddress)
  addSpec('Routing Table', p.routingtable)
  
  // Antenna
  addSpec('Antena', p.antena)
  
  // Ratings
  addSpec('IP Rating', p.iprating)
  addSpec('Dustproof and Waterproof', p.dustproofwaterproof)
  addSpec('Noise', p.noise)
  
  // Temperature & Environment
  addSpec('Operating Temp', p.operatingtemperature)
  addSpec('Storage Temp', p.storagetemperature)
  addSpec('Humidity', p.operatinghumidity)
  
  // Power
  const powerSpecs = collectArray(p.power1, p.power2, p.power3)
  if (powerSpecs.length > 0) {
    addSpec('Power', powerSpecs)
  }
  addSpec('Power Consumption', p.powercomsumptions)
  
  // Dimensions
  addSpec('Dimensions', p.dimensions)
  
  return specs
})

function openFullscreenTab() {
  const p = effectiveProduct.value
  const diagram = p?.diagram || ''
  const slug = encodeURIComponent(p?.slug || '')
  const url = `/diagram-fullscreen?slug=${slug}&diagram=${diagram}`
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
