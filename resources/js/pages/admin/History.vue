<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Histori Transaksi Stok</h1>
      <p class="text-gray-600">Riwayat keluar-masuk barang untuk penjualan, pembelian, RMA, peminjaman, dan transfer gudang.</p>
    </div>

    <div v-if="stats" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <p class="text-sm text-gray-600 mb-1">Total Penjualan Bulan Ini</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(stats.sales.total) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ stats.sales.count }} transaksi</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <p class="text-sm text-gray-600 mb-1">Total Pembelian Bulan Ini</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.purchases.total) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ stats.purchases.count }} transaksi</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <p class="text-sm text-gray-600 mb-1">Selisih Penjualan - Pembelian</p>
        <p class="text-2xl font-bold" :class="stats.net >= 0 ? 'text-emerald-600' : 'text-red-600'">
          {{ formatCurrency(stats.net) }}
        </p>
        <p class="text-xs text-gray-500 mt-1">Bulan ini</p>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 border-b border-gray-200 bg-gray-50">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
          <input v-model="filters.search" @input="applyFilters" type="search"
            placeholder="Customer, barang, invoice, resi..."
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Arah Stok</label>
          <select v-model="filters.direction" @change="applyFilters"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Semua (Masuk & Keluar)</option>
            <option value="in">Barang Masuk</option>
            <option value="out">Barang Keluar</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal</label>
          <input v-model="filters.date_from" @change="applyFilters" type="date"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal</label>
          <input v-model="filters.date_to" @change="applyFilters" type="date"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        </div>
        <div class="md:col-span-4 flex justify-end">
          <button @click="resetFilters"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-redo mr-2"></i>Reset Filter
          </button>
        </div>
      </div>

      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-indigo-600"></div>
        <p class="mt-4 text-gray-600">Memuat data...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-[1100px] w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer / Pihak</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Barang</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis Transaksi</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Invoice / Dokumen</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Delivery / Resi</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ formatDate(row.date) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ row.party_name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ row.product_name }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium"
                :class="row.direction === 'in' ? 'text-green-700' : 'text-red-700'">
                {{ row.direction === 'in' ? '+' : '-' }}{{ row.quantity }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                  :class="row.direction === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ row.transaction_label }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ row.document_number || '-' }}</td>
              <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ row.delivery_number || '-' }}</td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat transaksi untuk filter ini.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!loading && meta.total > 0" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-600">
          Menampilkan {{ (meta.current_page - 1) * meta.per_page + 1 }}–{{ Math.min(meta.current_page * meta.per_page, meta.total) }} dari {{ meta.total }} pergerakan
        </p>
        <div class="flex items-center space-x-2">
          <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page <= 1"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            Sebelumnya
          </button>
          <span class="px-2 text-sm">Halaman {{ meta.current_page }} dari {{ meta.last_page }}</span>
          <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            Berikutnya
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const loading = ref(false)
const rows = ref([])
const stats = ref(null)
const meta = ref({ current_page: 1, per_page: 20, total: 0, last_page: 1 })
const filters = ref({ search: '', direction: '', date_from: '', date_to: '' })

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/history', {
      params: {
        page: meta.value.current_page,
        per_page: meta.value.per_page,
        search: filters.value.search,
        direction: filters.value.direction,
        date_from: filters.value.date_from,
        date_to: filters.value.date_to,
      },
    })
    rows.value = response.data.data
    meta.value = response.data.meta
  } catch (error) {
    console.error('Error fetching transaction history:', error)
    rows.value = []
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await api.get('/history/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Error fetching history statistics:', error)
  }
}

const applyFilters = () => {
  meta.value.current_page = 1
  fetchData()
}

const changePage = (page) => {
  if (page >= 1 && page <= meta.value.last_page) {
    meta.value.current_page = page
    fetchData()
  }
}

const resetFilters = () => {
  filters.value = { search: '', direction: '', date_from: '', date_to: '' }
  applyFilters()
}

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
}).format(value || 0)

const formatDate = (date) => date
  ? new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
  : '-'

onMounted(() => {
  fetchData()
  fetchStats()
})
</script>
