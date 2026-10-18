<template>
  <section class="py-8 px-4 relative">
    <h2 class="text-3xl font-bold text-center">{{ $t('ui.productSolutions') }}</h2>
    <p class="text-gray-800 mb-3">{{ $t('ui.chooseWhatYouNeed') }}</p>

    <!-- Tabs -->
    <div class="flex flex-wrap gap-3 mb-4 justify-center">
      <div
        v-for="(tab, i) in tabs" :key="i"  @click="switchTab(tab.title)"
        :class="[
          'cursor-pointer py-2 px-2 border-b-2',
          activeTab === tab.title
            ? 'border-blue-600 text-blue-600 font-bold'
            : 'border-transparent border-b-slate-900 text-gray-800 hover:text-blue-800',
        ]">
        {{ $ui(tab.title) }}
      </div>
    </div>

    <!-- Sub Tabs -->
    <div class="flex flex-wrap gap-2 -mt-2 mb-8 justify-center">
      <div
        @click="switchSubCategory(null)"
        :class="[
          'cursor-pointer text-sm py-1 px-3 border-2 rounded-md transition-colors',
          activeSubTab === null
            ? 'bg-black border-blue-500 text-white font-medium'
            : 'bg-white border-slate-900 text-gray-900 hover:text-blue-800',
        ]"
      >
        {{ $t('labels.all') }}
      </div>
      <div
        v-for="(sub, j) in currentSubCategories"
        :key="j"
        @click="switchSubCategory(sub)"
        :class="[
          'cursor-pointer text-sm py-1 px-3 border-2 rounded-md transition-colors',
          activeSubTab === sub
            ? 'bg-black border-blue-500 text-white font-medium'
            : 'bg-white border-slate-900 text-gray-900 hover:text-blue-800',
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
            :to="{
              name: 'product-detail',
              params: { slug: product.slug || product.id },
              query: {
                ...(activeTab ? { category: activeTab } : {}),
                ...(activeSubTab ? { sub: activeSubTab } : {})
              }
            }"
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
          <RouterLink :to="{
            name: 'product-detail',
            params: { slug: pagedMobile[mobileIndex].slug || pagedMobile[mobileIndex].id },
            query: {
              ...(activeTab ? { category: activeTab } : {}),
              ...(activeSubTab ? { sub: activeSubTab } : {})
            }
          }">
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
      {{ $t('ui.noProductsFoundInThisCategory') }}
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import useProducts from '@/composable/useProducts.js'

// Logo paths from public folder
const defaultLogo = '/assets/static/logo_mdi.png'
const ldasan = '/assets/static/logo_dasan.png'
const lzaram = '/assets/static/logo_zaram.png'

const route = useRoute()
const router = useRouter()

// Fetch products and categories from database
const { products, categories, fetchProducts, loading } = useProducts()

const brandLogos = {
  dasan: ldasan,
  zaram: lzaram,
}

const getBrandLogo = (brand) => {
  return brandLogos[brand?.toLowerCase()] || null
}

// Dynamic tabs based on categories from database
const tabs = computed(() => {
  if (categories.value && categories.value.length > 0) {
    return categories.value.map(c => ({
      title: c.name,
      subCategories: c.sub_categories ? c.sub_categories.map(s => s.name) : []
    }))
  }
  // Fallback from products
  const catSet = new Set()
  products.value.forEach(p => {
    if (p.categories && p.categories.length > 0) {
      p.categories.forEach(c => catSet.add(c))
    } else if (p.category) {
      catSet.add(p.category)
    }
  })
  return Array.from(catSet).map(name => ({ title: name, subCategories: [] }))
})

// State aktif
const activeTab = ref(route?.query?.category || '')
const activeSubTab = ref(route?.query?.sub || null)
const desktopIndex = ref(0)
const mobileIndex = ref(0)

// Set default category when tabs become available
watch(tabs, (newTabs) => {
  if (newTabs.length && !activeTab.value) {
    activeTab.value = newTabs[0].title
  }
}, { immediate: true })

// Auto-refresh when page becomes visible (e.g., user switches back to tab)
let visibilityHandler = null

onMounted(() => {
  visibilityHandler = () => {
    if (!document.hidden) {
      // Force refresh products when user comes back to the page
      fetchProducts(true)
    }
  }
  document.addEventListener('visibilitychange', visibilityHandler)
})

onUnmounted(() => {
  if (visibilityHandler) {
    document.removeEventListener('visibilitychange', visibilityHandler)
  }
})

// Sinkronisasi dengan query string saat berubah
watch(
  () => route?.query,
  (query) => {
    if (!query) return
    if (query.category) activeTab.value = query.category
    if (query.sub) activeSubTab.value = query.sub || null
  },
  { immediate: true }
)

// Subkategori yang tersedia dari kategori aktif — hanya yang berpasangan dengan kategori aktif
const currentSubCategories = computed(() => {
  const activeCat = activeTab.value?.toLowerCase()
  if (!activeCat) return []

  const subSet = new Set()
  const list = products.value || []

  list.forEach(p => {
    if (p.category_pairs && p.category_pairs.length > 0) {
      // Gunakan pairs: hanya ambil sub_category yang berpasangan dengan kategori aktif
      p.category_pairs.forEach(pair => {
        if (pair.category_name?.toLowerCase() === activeCat && pair.sub_category_name) {
          subSet.add(pair.sub_category_name)
        }
      })
    } else {
      // Fallback jika tidak ada category_pairs: cek kategori lalu ambil subkategori
      const pCats = p.categories?.length > 0 ? p.categories : (p.category ? [p.category] : [])
      const matchesCat = pCats.some(c => c.toLowerCase() === activeCat)
      if (matchesCat) {
        if (p.subCategories?.length > 0) {
          p.subCategories.forEach(s => subSet.add(s))
        } else if (p.subCategory) {
          subSet.add(p.subCategory)
        }
      }
    }
  })

  return Array.from(subSet).filter(s => s && s.toLowerCase() !== 'all')
})

function switchTab(tabTitle) {
  activeTab.value = tabTitle
  activeSubTab.value = null
  desktopIndex.value = 0
  mobileIndex.value = 0
}

function switchSubCategory(sub) {
  activeSubTab.value = sub
  desktopIndex.value = 0
  mobileIndex.value = 0
}

// Produk hasil filter dengan dukungan multi-kategori & multi-subkategori
const filteredProducts = computed(() => {
  const list = products.value || []
  if (!activeTab.value) return list

  return list.filter((p) => {
    const pCats = p.categories && p.categories.length > 0 ? p.categories : (p.category ? [p.category] : [])
    const matchesCat = pCats.some(c => c.toLowerCase() === activeTab.value.toLowerCase())

    if (!matchesCat) return false
    if (!activeSubTab.value) return true

    const pSubs = p.subCategories && p.subCategories.length > 0 ? p.subCategories : (p.subCategory ? [p.subCategory] : [])
    return pSubs.some(s => s.toLowerCase() === activeSubTab.value.toLowerCase())
  })
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

// Daftar spec yang valid (hingga spec20)
const getSpecs = (product) => {
  return Array.from({ length: 20 }, (_, i) => product[`spec${i + 1}`]).filter(
    (s) => s && s !== 'null' && s !== '',
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
