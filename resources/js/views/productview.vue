<template>
  <div>

    <div class="max-w-7xl mx-auto py-8 px-4 lg:py-4 lg:px-6">
      <!-- Header -->
      <div class="flex items-baseline justify-between border-b pb-2 pt-8 flex-wrap">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Products</h1>
        <div class="flex lg:space-x-2 sm:space-x-0 gap-1 mt-4 sm:mt-0">
          <button v-for="(tab, index) in tabs" :key="index" @click="switchTab(tab.title)" :class="[
            'px-4 py-2 rounded text-sm font-medium',
            activeCategory === tab.title
              ? 'bg-blue-900 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-400',
          ]">
            {{ tab.title }}
          </button>
        </div>
      </div>

      <div class="md:h-[800px] flex flex-col md:flex-row">
        <!-- Sidebar Filter -->
        <div class="mr-0 md:mr-8 mb-6 md:mb-0 pt-4">
          <aside>
            <ul class="space-y-2 text-sm font-medium text-gray-900">
              <li :class="[
                'cursor-pointer rounded px-3 py-2',
                activeSubCategory === null
                  ? 'bg-indigo-100 text-indigo-600'
                  : 'hover:bg-gray-100',
              ]" @click="switchSubCategory(null)">
                All
              </li>
              <li v-for="(sub, index) in currentSubCategories" :key="index" @click="switchSubCategory(sub)" :class="[
                'cursor-pointer rounded px-3 py-2',
                activeSubCategory === sub ? 'bg-indigo-100 text-indigo-600' : 'hover:bg-gray-100',
              ]">
                {{ sub }}
              </li>
            </ul>
          </aside>
        </div>

        <!-- Grid Produk -->
        <div class="flex-1 flex flex-col sm:flex-row flex-wrap -mb-4 -mx-2 pt-4">
          <div class="lg:col-span-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div v-for="product in paginatedProducts" :key="product.id"
                class="bg-white shadow-md rounded-xl hover:scale-105 duration-300 hover:shadow-xl">
                <RouterLink :to="{
                  name: 'product-detail',
                  params: { slug: product.slug },
                  query: { category: product.category, sub: product.subCategory }
                }">
                  <img :src="product.image" :alt="product.title" class="w-full h-48 object-contain rounded-t-lg p-4" />
                </RouterLink>
                <div class="px-4 py-3">
                  <h3 class="text-lg font-semibold text-gray-900 truncate">
                    <RouterLink :to="{
                      name: 'product-detail',
                      params: { slug: product.slug },
                      query: { category: product.category, sub: product.subCategory }
                    }" class="hover:underline">
                      {{ product.title }}
                    </RouterLink>
                  </h3>
                  <p class="text-sm text-gray-500" v-if="product.spec1">{{ product.spec1 }}</p>
                  <p class="text-sm text-gray-500" v-if="product.spec2">{{ product.spec2 }}</p>
                </div>
              </div>
            </div>


            <!-- Pagination -->
            <div v-if="totalPages > 1" class="mt-6 flex justify-center space-x-2">
              <button v-for="page in totalPages" :key="page" @click="goToPage(page)" :class="[
                'px-4 py-2 border rounded',
                currentPage === page
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100',
              ]">
                {{ page }}
              </button>
            </div>
          </div>


        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import useProducts from '@/composable/useProducts.js'

const { products, fetchProducts } = useProducts()
const route = useRoute()
const router = useRouter()

const tabs = [{ title: 'XGSPON' }, { title: 'GPON' }, { title: 'SWITCH' }, { title: 'WIRELESS' }]

// State aktif
const activeCategory = ref(route.query.category || tabs[0].title)
const activeSubCategory = ref(route.query.sub || null)
const currentPage = ref(1)
const itemsPerPage = ref(6)

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
  () => route.query,
  (query) => {
    if (query.category) activeCategory.value = query.category
    if ('sub' in query) activeSubCategory.value = query.sub || null
  },
  { immediate: true },
)

// Subkategori yang tersedia dari produk aktif
const currentSubCategories = computed(() => {
  const list = products.value || []
  const filtered = list.filter((p) => p.category === activeCategory.value)
  const subSet = new Set(filtered.map((p) => p.subCategory))
  return Array.from(subSet)
})

// Produk hasil filter
const filteredProducts = computed(() => {
  const list = products.value || []
  return list.filter(
    (p) =>
      p.category === activeCategory.value &&
      (!activeSubCategory.value || p.subCategory === activeSubCategory.value),
  )
})

// Pagination
const totalPages = computed(() =>
  Math.ceil(filteredProducts.value.length / itemsPerPage.value),
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredProducts.value.slice(start, start + itemsPerPage.value)
})

// Event handler
function switchTab(tab) {
  activeCategory.value = tab
  activeSubCategory.value = null
  currentPage.value = 1
  updateQuery()
}

function switchSubCategory(sub) {
  activeSubCategory.value = sub
  currentPage.value = 1
  updateQuery()
}

function goToPage(page) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page

    // Scroll ke atas (dengan animasi smooth)
    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    })
  }
}

function updateQuery() {
  router.replace({
    query: {
      category: activeCategory.value,
      ...(activeSubCategory.value ? { sub: activeSubCategory.value } : {}),
    },
  })
}
</script>

