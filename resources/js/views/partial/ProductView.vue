<template>
  <section class="py-8 px-4 relative">
    <h2 class="text-3xl font-bold text-center">Product Solutions</h2>
    <p class="text-gray-800 mb-3">choose what you need</p>

    <!-- Tabs -->
    <div class="flex flex-wrap gap-3 mb-4 justify-center">
      <div
        v-for="(tab, i) in tabs"
        :key="i"
        @click="
          () => {
            activeTab = i
            activeSubTab = 0
            desktopIndex = 0
            mobileIndex = 0
          }
        "
        :class="[
          'cursor-pointer py-2 px-2 border-b-2',
          i === activeTab
            ? 'border-blue-600 text-blue-600 font-bold'
            : 'border-transparent border-b-slate-900 text-gray-800 hover:text-blue-800',
        ]"
      >
        {{ tab.title }}
      </div>
    </div>

    <!-- Sub Tabs -->
    <div
      v-if="tabs[activeTab].subCategories"
      class="flex flex-wrap gap-2 -mt-2 mb-8 justify-center"
    >
      <div
        v-for="(sub, j) in tabs[activeTab].subCategories"
        :key="j"
        @click="
          () => {
            activeSubTab = j
            desktopIndex = 0
            mobileIndex = 0
          }
        "
        :class="[
          'cursor-pointer text-sm py-1 px-3 border-2',
          j === activeSubTab
            ? 'bg-black border-blue-500 rounded-md text-white font-medium'
            : 'bg-white border-slate-900 rounded-md text-gray-900 hover:text-blue-800',
        ]"
      >
        {{ sub }}
      </div>
    </div>

    <!-- Desktop Grid (4 per page) -->
    <div class="hidden md:flex justify-center items-center relative">
      <button
        v-if="pagedDesktop.length > 1"
        @click="prevDesktop"
        class="block absolute left-4 top-1/2 -translate-y-1/2 px-2 py-1 z-10"
      >
        ❮
      </button>

      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mx-auto max-w-7xl px-6 lg:px-16"
      >
        <div
          v-for="(product, i) in pagedDesktop[desktopIndex]"
          :key="i"
          class="relative h-[320px] bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1"
        >
          <RouterLink
            :to="`/product/${product.id}`"
            class="block flex-1 flex-col justify-between"
          >
            <!-- Default logo (kiri) -->
            <img
              :src="defaultLogo"
              alt="default logo"
              class="flex w-full h-8 top-0 left-0 ml-2"
            />

            <!-- Brand logo dinamis (kanan) -->
            <img
              v-if="getBrandLogo(product.brand)"
              :src="getBrandLogo(product.brand)"
              :alt="product.brand"
              class="absolute w-full h-8 top-0 right-0 mr-3"
            />

            <div
              class="aspect-w-16 aspect-h-16 bg-gradient-to-br from-primary-50 to-primary-100"
            >
              <div
                class="w-50 h-48 flex object-contain justify-center items-center mx-auto p-4"
              >
                <div
                  class="w-[100%] h-[100%] bg-primary-200 rounded-full flex items-center justify-center"
                >
                  <img
                    :src="product.image"
                    alt=""
                    class="w-64 h-32 object-contain mb-2 mx-auto"
                  />
                </div>
              </div>
            </div>
            <div class="p-6">
              <div class="flex items-start justify-between mb-1">
                <h3
                  class="text-lg font-mono font-semibold mb-1 line-clamp-2"
                >
                  {{ product.title }}
                </h3>
              </div>
              <ul
                class="text-xs shadow-slate-300 font-semibold italic text-black -mt-2"
              >
                <li class="text-sm overline">
                  {{ product.subtitle }}
                </li>
                <ul
                  class="list-disc pl-5 text-justify text-xs text-gray-950 mt-1"
                  v-if="getSpecs(product).length"
                >
                  <li
                    v-for="(spec, index) in getSpecs(product)"
                    :key="index"
                  >
                    {{ spec }}
                  </li>
                </ul>
              </ul>
            </div>
          </RouterLink>
        </div>
      </div>
      <button
        v-if="pagedDesktop.length > 1"
        @click="nextDesktop"
        class="absolute right-4 top-1/2 -translate-y-1/2 px-2 py-1 z-10"
      >
        ❯
      </button>
    </div>

    <!-- Mobile (1 per page + navigation) -->
    <div class="md:hidden text-center relative mt-4">
      <div
        v-if="pagedMobile.length"
        class="mx-auto w-[280px] min-h-[130px] flex flex-col items-center relative"
      >
        <div
          class="relative w-[300px] h-[450px] bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-1"
        >
          <RouterLink :to="`/product/${pagedMobile[mobileIndex].id}`">
            <!-- Default logo (kiri) -->
            <img
              :src="defaultLogo"
              alt="default logo"
              class="flex w-full h-10 top-0 left-0 ml-2"
            />

            <!-- Brand logo dinamis (kanan) -->
            <img
              v-if="getBrandLogo(pagedMobile[mobileIndex].brand)"
              :src="getBrandLogo(pagedMobile[mobileIndex].brand)"
              :alt="pagedMobile[mobileIndex].brand"
              class="absolute w-full h-10 top-0 right-0 mr-3"
            />

            <div
              class="aspect-w-92 aspect-h-24 bg-gradient-to-br from-primary-50 to-primary-100"
            >
              <div
                class="w-full h-48 flex object-contain justify-center items-center mx-auto p-4"
              >
                <div
                  class="w-[62] h-[80%] bg-primary-200 rounded-full flex items-center justify-center"
                >
                  <img
                    :src="pagedMobile[mobileIndex].image"
                    alt=""
                    class="w-42 h-auto object-contain mb-2 mx-auto"
                  />
                </div>
              </div>
            </div>

            <div class="p-6">
              <div class="flex items-center justify-center mb-1">
                <h3
                  class="text-lg font-mono font-semibold mt-1 line-clamp-2"
                >
                  {{ pagedMobile[mobileIndex].title }}
                </h3>
              </div>
              <ul
                class="text-xs shadow-slate-300 font-semibold italic text-black -mt-2"
              >
                <li class="text-sm overline">
                  {{ pagedMobile[mobileIndex].subtitle }}
                </li>
                <ul
                  class="list-disc pl-5 text-justify text-sm whitespace-normal text-gray-950"
                  v-if="getSpecs(pagedMobile[mobileIndex]).length"
                >
                  <li
                    v-for="(spec, index) in getSpecs(pagedMobile[mobileIndex])"
                    :key="index"
                  >
                    {{ spec }}
                  </li>
                </ul>
              </ul>
            </div>
          </RouterLink>
        </div>

        <!-- Mobile Navigation -->
        <div class="flex justify-center gap-6 mt-4">
          <button
            v-if="pagedMobile.length > 1"
            @click="prevMobile"
            class="text-2xl px-3 py-1"
          >
            ❮
          </button>
          <button
            v-if="pagedMobile.length > 1"
            @click="nextMobile"
            class="text-2xl px-3 py-1"
          >
            ❯
          </button>
        </div>
      </div>
    </div>

    <!-- Kosong -->
    <div
      v-if="filteredProducts.length === 0"
      class="text-center text-gray-500 py-8"
    >
      No products found in this category.
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import useProducts from '@/composable/useProducts.js'

// Logo paths from public folder
const defaultLogo = '/assets/static/logo_mdi.png'
const ldasan = '/assets/static/logo_dasan.png'
const lzaram = '/assets/static/logo_zaram.png'

// Fetch products from database
const { products, loading } = useProducts()

const brandLogos = {
  dasan: ldasan,
  zaram: lzaram,
}

const getBrandLogo = (brand) => {
  return brandLogos[brand?.toLowerCase()] || null
}

const activeTab = ref(0)
const activeSubTab = ref(0)
const desktopIndex = ref(0)
const mobileIndex = ref(0)

const tabs = [
  { title: 'XGSPON', subCategories: ['All', 'OLT', 'ONT', 'ONU', 'XGSPON STICK'] },
  { title: 'GPON', subCategories: ['All', 'OLT', 'ONT', 'ONU PoE', 'ONU', 'GPON STICK'] },
  {
    title: 'SWITCH',
    subCategories: ['All', 'BACKBONE', 'L3 SWITCH', 'L2 SWITCH', 'PoE SWITCH'],
  },
  { title: 'WIRELESS', subCategories: ['All', 'AP', 'CONTROLLER'] },
]

const filteredProducts = computed(() => {
  const category = tabs[activeTab.value].title
  const subCategory = tabs[activeTab.value].subCategories[activeSubTab.value] || 'All'
  let filtered = products.value.filter((p) => p.category === category)
  if (subCategory !== 'All') {
    filtered = filtered.filter((p) => p.subCategory === subCategory)
  }
  return filtered
})

const pagedDesktop = computed(() => {
  const chunkSize = 4
  const pages = []
  const source = filteredProducts.value
  for (let i = 0; i < source.length; i += chunkSize) {
    pages.push(source.slice(i, i + chunkSize))
  }
  return pages
})

// Daftar spec yang valid
const getSpecs = (product) => {
  return Array.from({ length: 7 }, (_, i) => product[`spec${i + 1}`]).filter(
    (s) => s && s !== 'null',
  )
}

const pagedMobile = computed(() => {
  return filteredProducts.value
})

const prevDesktop = () => {
  desktopIndex.value =
    (desktopIndex.value - 1 + pagedDesktop.value.length) %
    pagedDesktop.value.length
}
const nextDesktop = () => {
  desktopIndex.value = (desktopIndex.value + 1) % pagedDesktop.value.length
}

const prevMobile = () => {
  mobileIndex.value =
    (mobileIndex.value - 1 + pagedMobile.value.length) %
    pagedMobile.value.length
}
const nextMobile = () => {
  mobileIndex.value = (mobileIndex.value + 1) % pagedMobile.value.length
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
