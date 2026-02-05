<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Histori Transaksi</h1>
      <p class="text-gray-600">Lihat semua transaksi penjualan dan pembelian</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" v-if="stats">
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Total Penjualan</p>
            <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(stats.sales.total) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ stats.sales.count }} transaksi</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-shopping-cart text-blue-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Total Pembelian</p>
            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.purchases.total) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ stats.purchases.count}} transaksi</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-box text-green-600"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Selisih (Net)</p>
            <p class="text-2xl font-bold" :class="stats.net >= 0 ? 'text-emerald-600' : 'text-red-600'">
              {{ formatCurrency(stats.net) }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Bulan ini</p>
          </div>
          <div class="w-12 h-12 rounded-lg flex items-center justify-center" 
               :class="stats.net >= 0 ? 'bg-emerald-100' : 'bg-red-100'">
            <i class="fas fa-chart-line" :class="stats.net >= 0 ? 'text-emerald-600' : 'text-red-600'"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs & Content -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <!-- Tab Headers -->
      <div class="border-b border-gray-200">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
            ]"
          >
            <i :class="tab.icon" class="mr-2"></i>
            {{ tab.label }}
            <span v-if="tab.count !== undefined" 
                  class="ml-2 py-0.5 px-2 rounded-full text-xs"
                  :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600'">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Filters -->
      <div class="p-6 border-b border-gray-200 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select v-model="filters.status" @change="fetchData" 
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">Semua Status</option>
              <option v-if="activeTab === 'sales'" value="pending">Pending</option>
              <option v-if="activeTab === 'sales'" value="completed">Completed</option>
              <option v-if="activeTab === 'sales'" value="cancelled">Cancelled</option>
              <option v-if="activeTab === 'purchases'" value="pending">Pending</option>
              <option v-if="activeTab === 'purchases'" value="received">Received</option>
              <option v-if="activeTab === 'purchases'" value="cancelled">Cancelled</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal</label>
            <input type="date" v-model="filters.date_from" @change="fetchData"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal</label>
            <input type="date" v-model="filters.date_to" @change="fetchData"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          </div>

          <div class="flex items-end">
            <button @click="resetFilters"
                    class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
              <i class="fas fa-redo mr-2"></i>Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-indigo-600"></div>
        <p class="mt-4 text-gray-600">Memuat data...</p>
      </div>

      <!-- Sales Tab Content -->
      <div v-else-if="activeTab === 'sales'" class="p-6">
        <div v-if="salesData.length === 0" class="text-center py-12">
          <i class="fas fa-shopping-cart text-gray-300 text-6xl mb-4"></i>
          <p class="text-gray-600">Belum ada data penjualan</p>
        </div>

        <div v-else class="space-y-4">
          <div v-for="sale in salesData" :key="`sale-${sale.id}`"
               class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
               @click="viewSaleDetail(sale)">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600"></i>
                  </div>
                  <div>
                    <h3 class="font-semibold text-gray-900">{{ sale.reference_number }}</h3>
                    <p class="text-sm text-gray-600">{{ sale.party_name }}</p>
                  </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                  <div>
                    <p class="text-gray-500">Tanggal</p>
                    <p class="font-medium">{{ formatDate(sale.sale_date) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-500">Total</p>
                    <p class="font-medium text-blue-600">{{ formatCurrency(sale.total_amount) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-500">Items</p>
                    <div class="text-sm">
                      <template v-if="sale.items && sale.items.length > 0">
                        <p v-for="(item, idx) in sale.items.slice(0, 3)" :key="idx" class="font-medium">
                          {{ item.product?.title || item.product_name || 'Produk' }} x {{ item.quantity }}
                        </p>
                        <p v-if="sale.items.length > 3" class="text-gray-400">+{{ sale.items.length - 3 }} lainnya</p>
                      </template>
                      <p v-else class="font-medium">{{ sale.items_count || 0 }} produk</p>
                    </div>
                  </div>
                  <div>
                    <p class="text-gray-500">Status</p>
                    <span :class="getStatusClass(sale.status)">
                      {{ sale.status }}
                    </span>
                  </div>
                </div>
              </div>
              
              <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Purchases Tab Content -->
      <div v-else-if="activeTab === 'purchases'" class="p-6">
        <div v-if="purchasesData.length === 0" class="text-center py-12">
          <i class="fas fa-box text-gray-300 text-6xl mb-4"></i>
          <p class="text-gray-600">Belum ada data pembelian</p>
        </div>

        <div v-else class="space-y-4">
          <div v-for="purchase in purchasesData" :key="`purchase-${purchase.id}`"
               class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
               @click="viewPurchaseDetail(purchase)">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-green-600"></i>
                  </div>
                  <div>
                    <h3 class="font-semibold text-gray-900">{{ purchase.po_number }}</h3>
                    <p class="text-sm text-gray-600">{{ purchase.supplier_name }}</p>
                  </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                  <div>
                    <p class="text-gray-500">Tanggal</p>
                    <p class="font-medium">{{ formatDate(purchase.order_date) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-500">Total</p>
                    <p class="font-medium text-green-600">{{ formatCurrency(purchase.total_amount) }}</p>
                  </div>
                  <div>
                    <p class="text-gray-500">Items</p>
                    <div class="text-sm">
                      <template v-if="purchase.items && purchase.items.length > 0">
                        <p v-for="(item, idx) in purchase.items.slice(0, 3)" :key="idx" class="font-medium">
                          {{ item.product?.title || item.product_name || 'Produk' }} x {{ item.quantity }}
                        </p>
                        <p v-if="purchase.items.length > 3" class="text-gray-400">+{{ purchase.items.length - 3 }} lainnya</p>
                      </template>
                      <p v-else class="font-medium">{{ purchase.items_count || 0 }} produk</p>
                    </div>
                  </div>
                  <div>
                    <p class="text-gray-500">Status</p>
                    <span :class="getStatusClass(purchase.status)">
                      {{ purchase.status }}
                    </span>
                  </div>
                </div>
              </div>
              
              <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="!loading && meta.total > 0" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Menampilkan {{ salesData.length || purchasesData.length }} dari {{ meta.total }} data
        </div>
        
        <div class="flex space-x-2">
          <button @click="changePage(meta.current_page - 1)" 
                  :disabled="meta.current_page === 1"
                  class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-chevron-left"></i>
          </button>
          
          <span class="px-4 py-2  text-sm">
            Halaman {{ meta.current_page }} dari {{ meta.last_page }}
          </span>
          
          <button @click="changePage(meta.current_page + 1)" 
                  :disabled="meta.current_page === meta.last_page"
                  class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

// State
const activeTab = ref('sales')
const loading = ref(false)
const salesData = ref([])
const purchasesData = ref([])
const stats = ref(null)
const meta = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1
})

const filters = ref({
  status: '', // Empty default - show all
  date_from: '',
  date_to: ''
})



// Tabs configuration
const tabs = computed(() => [
  { 
    key: 'sales', 
    label: 'Penjualan', 
    icon: 'fas fa-shopping-cart',
    count: salesData.value.length 
  },
  { 
    key: 'purchases', 
    label: 'Pembelian', 
    icon: 'fas fa-box',
    count: purchasesData.value.length 
  }
])

// Fetch data
const fetchData = async () => {
  loading.value = true
  try {
    if (activeTab.value === 'sales') {
      // Fetch sales data
      const params = {
        page: meta.value.current_page,
        per_page: meta.value.per_page,
        status: filters.value.status,
        start_date: filters.value.date_from,
        end_date: filters.value.date_to
      }
      const response = await api.get('/sales', { params })
      salesData.value = response.data.data
      meta.value = {
        current_page: response.data.current_page,
        per_page: response.data.per_page,
        total: response.data.total,
        last_page: response.data.last_page
      }
    } else {
      // Fetch purchases data
      const params = {
        page: meta.value.current_page,
        per_page: meta.value.per_page,
        status: filters.value.status,
        start_date: filters.value.date_from,
        end_date: filters.value.date_to
      }
      const response = await api.get('/purchases', { params })
      purchasesData.value = response.data.data
      meta.value = {
        current_page: response.data.current_page,
        per_page: response.data.per_page,
        total: response.data.total,
        last_page: response.data.last_page
      }
    }
  } catch (error) {
    console.error('Error fetching history:', error)
  } finally {
    loading.value = false
  }
}

// Fetch stats
const fetchStats = async () => {
  try {
    const response = await api.get('/history/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}



// Change page
const changePage = (page) => {
  if (page >= 1 && page <= meta.value.last_page) {
    meta.value.current_page = page
    fetchData()
  }
}

// Reset filters
const resetFilters = () => {
  filters.value = {
    status: '', // Show all
    date_from: '',
    date_to: ''
  }
  meta.value.current_page = 1
  fetchData()
}

// View detail
const viewSaleDetail = (sale) => {
  router.push('/dashboard/sales')
}

const viewPurchaseDetail = (purchase) => {
  router.push('/dashboard/purchases')
}

// Formatters
const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
    completed: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800',
    received: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800',
    cancelled: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800'
  }
  return classes[status] || 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
}

// Watch tab changes
watch(activeTab, () => {
  // Reset filters when switching tabs
  filters.value.status = ''
  meta.value.current_page = 1
  fetchData()
})

// Lifecycle
onMounted(() => {
  fetchData()
  fetchStats()
})
</script>
