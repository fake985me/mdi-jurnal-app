<template>
  <div class="space-y-0">
    <!-- Top Header -->
    <div class="flex justify-between items-center mb-4">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          Sales Management
        </h2>
        <p class="text-sm text-gray-600 mt-1">Kelola dan pantau semua transaksi penjualan</p>
      </div>
      <button @click="$router.push({ name: 'SalesCreate' })"
        class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-md hover:shadow-lg font-semibold text-sm">
        + Buat Penjualan
      </button>
    </div>

    <!-- SALES TAB -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-4">


      <!-- Sales Summary + Pie Chart Footer -->
      <div class="border-t border-slate-200 bg-gradient-to-br from-slate-50 to-white">

        <!-- Donut Chart + Status Cards -->
        <div class="px-6 py-5 flex flex-col md:flex-row items-center gap-8">

          <!-- Donut SVG Chart -->
          <div class="relative shrink-0" style="width:140px;height:140px">
            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
              <!-- Background ring -->
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3.8" />
              <!-- Segments -->
              <template v-for="(seg, i) in donutSegments" :key="i">
                <circle cx="18" cy="18" r="15.9" fill="none" :stroke="seg.color" stroke-width="3.8"
                  :stroke-dasharray="`${seg.dash} ${100 - seg.dash}`" :stroke-dashoffset="`${-seg.offset}`"
                  stroke-linecap="butt" style="transition: stroke-dasharray 0.6s ease" />
              </template>
            </svg>
            <!-- Center label -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-2xl font-bold text-slate-800">{{ allSales.length }}</span>
              <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wide">Total</span>
            </div>
          </div>

          <!-- Status Cards -->
          <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 w-full">
            <div v-for="stat in salesStats" :key="stat.label" class="flex items-center gap-3 p-3 rounded-xl border"
              :style="`border-color: ${stat.borderColor}; background: ${stat.bg}`">
              <div class="w-3 h-3 rounded-full shrink-0" :style="`background:${stat.color}`"></div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ stat.label }}</div>
                <div class="text-xl font-bold" :style="`color:${stat.color}`">{{ stat.count }}</div>
                <div class="text-[11px] text-slate-400">Rp {{ formatInt(stat.total) }}</div>
              </div>
              <div class="text-right">
                <div class="text-xs font-bold" :style="`color:${stat.color}`">
                  {{ allSales.length ? Math.round(stat.count / allSales.length * 100) : 0 }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Total summary -->
          <div class="shrink-0 text-right hidden lg:block">
            <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Grand Total</div>
            <div class="text-2xl font-bold text-indigo-700">Rp {{ formatInt(allTotal) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ allSales.length }} transaksi</div>
          </div>
        </div>

        <!-- Compact Pagination -->
        <div
          class="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 pb-3 border-t border-slate-100 pt-3">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <label>Baris:</label>
            <select v-model="pageSize" @change="currentPage = 1"
              class="border border-slate-200 rounded-lg px-2 py-1 text-sm">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
            </select>
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center">
            <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
              class="px-3 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm">
              &larr;
            </button>
            <button v-for="p in visiblePages" :key="p" @click="currentPage = p" :class="['px-3 py-1 rounded-lg border text-sm', p === currentPage
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50']">
              {{ p }}
            </button>
            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
              class="px-3 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm">
              &rarr;
            </button>
            <span class="ml-2 text-xs text-slate-400">
              {{ filteredSales.length === 0 ? 0 : ((currentPage - 1) * pageSize) + 1 }}–{{ Math.min(currentPage *
                pageSize,
              filteredSales.length) }}
              / {{ filteredSales.length }}
            </span>
          </div>
        </div>
      </div>

      <!-- Header: Title + Stats + Search -->
      <div class="px-4 pt-4 pb-3 border-b border-slate-200">
        <div class="grid items-center gap-3 md:grid-cols-[auto,1fr,auto]">
          <h3 class="text-lg font-bold text-slate-800">Daftar Penjualan</h3>
          <div class="justify-self-center">
            <!-- <div class="flex flex-wrap items-center justify-center gap-2">
              <span class="stat-badge">Total : <strong class="ml-1">{{ allSales.length }}</strong></span>
              <span class="stat-badge">Filtered : <strong class="ml-1">{{ filteredSales.length }}</strong></span>
              <span class="stat-badge">Total : <strong class="ml-1">Rp {{ formatInt(allTotal) }}</strong></span>
              <span class="stat-badge">Filtered : <strong class="ml-1">Rp {{ formatInt(filteredTotal) }}</strong></span>
            </div> -->
          </div>
          <div class="justify-self-end w-full max-w-sm">
            <div class="flex items-center gap-2">
              <input v-model="searchQuery" type="text"
                class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-indigo-100"
                placeholder="Cari invoice, customer..." />
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Legend Row -->
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/60">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <span class="legend-label">Status</span>
            <button v-for="s in statusOptions" :key="s" @click="setStatusFilter(s)"
              :class="['legend-btn', statusFilter === s ? 'active' : '']">
              <span>{{ s === 'all' ? 'All' : capitalize(s) }}</span>
              <span class="count-badge">{{ statusCount(s) }}</span>
            </button>
          </div>
          <div class="flex flex-wrap items-center gap-2 ml-auto pl-3 border-l border-slate-200">
            <span class="legend-label">Tanggal</span>
            <input v-model="dateFrom" type="date" @change="currentPage = 1"
              class="rounded-lg border border-slate-200 px-2 py-1 text-xs outline-none focus:ring-2 focus:ring-indigo-100" />
            <span class="text-xs text-slate-400">-</span>
            <input v-model="dateTo" type="date" @change="currentPage = 1"
              class="rounded-lg border border-slate-200 px-2 py-1 text-xs outline-none focus:ring-2 focus:ring-indigo-100" />
            <button @click="resetFilters"
              class="px-3 py-1 rounded-lg border border-slate-200 bg-white text-xs font-semibold hover:bg-slate-50">
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-gray-200 border-t-indigo-600">
        </div>
        <p class="mt-3 text-sm text-gray-600">Memuat data penjualan...</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm vsep">
          <thead class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-3 py-3 w-8"></th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('invoice_number')">
                Invoice <span v-if="sortKey === 'invoice_number'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ?
                  '▲'
                  : '▼'
                  }}</span>
              </th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('customer_name')">
                Customer <span v-if="sortKey === 'customer_name'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ?
                  '▲'
                  : '▼'
                  }}</span>
              </th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('sale_date')">
                Tanggal <span v-if="sortKey === 'sale_date'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '▲'
                  : '▼'
                }}</span>
              </th>
              <th class="px-4 py-3 text-left">Produk</th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('status')">
                Status <span v-if="sortKey === 'status'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '▲' : '▼'
                }}</span>
              </th>
              <th class="px-4 py-3 text-left">Pajak</th>
              <th class="px-4 py-3 text-left">Delivery</th>
              <th class="px-4 py-3 text-right cursor-pointer select-none" @click="setSort('grand_total')">
                Total <span v-if="sortKey === 'grand_total'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '▲' :
                  '▼'
                }}</span>
              </th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <template v-for="(sale, idx) in pagedSales" :key="sale.id">
              <!-- Main Row -->
              <tr
                :class="['transition-colors cursor-pointer', idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white', 'hover:bg-indigo-50/40']"
                @click="toggleExpand(sale.id)">
                <td class="px-3 py-3 text-center">
                  <span class="inline-block text-slate-400 transition-transform duration-200"
                    :style="expanded[sale.id] ? 'transform:rotate(90deg)' : ''">&#9658;</span>
                </td>
                <td class="px-4 py-3 align-top">
                  <div class="font-semibold text-slate-900 whitespace-nowrap">{{ sale.invoice_number }}</div>
                  <div class="text-[11px] text-slate-400">ID: {{ sale.id }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div
                      :class="['w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-sm shrink-0', avatarColor(sale.customer_name)]">
                      {{ (sale.customer_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="font-semibold truncate text-slate-900">{{ sale.customer_name || '-' }}</div>
                      <div class="text-[11px] text-slate-400 truncate">{{ sale.customer_phone || '' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ formatDate(sale.sale_date) }}</td>
                <td class="px-4 py-3">
                  <template v-if="sale.items && sale.items.length">
                    <div v-for="(item, i) in sale.items.slice(0, 2)" :key="i" class="text-xs text-slate-700">
                      {{ item.product ? item.product.title : 'Produk' }} <span class="text-slate-400">x{{ item.quantity
                      }}</span>
                    </div>
                    <div v-if="sale.items.length > 2" class="text-[11px] text-slate-400">+{{ sale.items.length - 2 }}
                      lainnya</div>
                  </template>
                  <span v-else class="text-slate-400 text-xs">-</span>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', statusClr(sale.status)]">
                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5"></span>
                    {{ capitalize(sale.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="sale.tax_type"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                    {{ taxDisplayName(sale.tax_type) }} {{ sale.tax_rate }}%
                  </span>
                  <span v-else class="text-slate-300 text-xs">-</span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="sale.delivery"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700 ring-1 ring-teal-200">
                    &#128666; {{ sale.delivery.tracking_number }}
                  </span>
                  <span v-else class="text-slate-300 text-xs">No delivery</span>
                </td>
                <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                  Rp {{ formatInt(sale.grand_total || sale.total_amount) }}
                </td>
                <td class="px-4 py-3 text-right" @click.stop>
                  <div class="flex justify-end gap-1.5 flex-wrap">
                    <button v-if="!sale.delivery && ['pending', 'completed'].includes(sale.status)"
                      @click="$router.push({ name: 'SalesEdit', params: { id: sale.id } })"
                      class="px-2 py-1 text-xs rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 font-medium">
                      + Delivery
                    </button>
                    <button @click="editSale(sale)"
                      class="px-2 py-1 text-xs rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium">Edit</button>
                    <button @click="$router.push({ name: 'SalesView', params: { id: sale.id } })"
                      class="px-2 py-1 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium">View</button>
                    <button @click="deleteSale(sale.id)"
                      class="px-2 py-1 text-xs rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-medium">Hapus</button>
                  </div>
                </td>
              </tr>

              <!-- Expand Detail Row -->
              <tr v-if="expanded[sale.id]" class="bg-white">
                <td colspan="10" class="px-0 py-0">
                  <div class="p-4 bg-indigo-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">Produk Dipesan</div>
                        <ul class="space-y-1 text-sm">
                          <li v-for="item in (sale.items || [])" :key="item.id"
                            class="flex items-center justify-between text-slate-700">
                            <span class="truncate mr-2">{{ item.product ? item.product.title : (item.product_name ||
                              'Produk')
                            }}</span>
                            <span class="text-slate-400 shrink-0">x {{ item.quantity }}</span>
                          </li>
                          <li v-if="!sale.items || !sale.items.length" class="text-slate-400">Tidak ada item</li>
                        </ul>
                      </div>
                      <div class="border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">Info Transaksi</div>
                        <div class="space-y-1.5 text-sm">
                          <div class="flex justify-between">
                            <span class="text-slate-500">Invoice</span>
                            <span class="font-semibold">{{ sale.invoice_number }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-slate-500">Tanggal</span>
                            <span>{{ formatDate(sale.sale_date) }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-slate-500">Subtotal</span>
                            <span>Rp {{ formatInt(sale.subtotal || sale.total_amount) }}</span>
                          </div>
                          <div v-if="sale.tax_type" class="flex justify-between">
                            <span class="text-slate-500">Pajak</span>
                            <span>{{ taxDisplayName(sale.tax_type) }} {{ sale.tax_rate }}%</span>
                          </div>
                          <div class="flex justify-between border-t pt-1.5 font-bold">
                            <span class="text-slate-700">Grand Total</span>
                            <span class="text-indigo-700">Rp {{ formatInt(sale.grand_total || sale.total_amount)
                            }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">Pengiriman &amp; Gudang</div>
                        <div class="space-y-1.5 text-sm">
                          <div class="flex justify-between">
                            <span class="text-slate-500">Gudang</span>
                            <span>{{ sale.warehouse ? sale.warehouse.name : '-' }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-slate-500">Status</span>
                            <span
                              :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset', statusClr(sale.status)]">
                              {{ capitalize(sale.status) }}
                            </span>
                          </div>
                          <template v-if="sale.delivery">
                            <div class="flex justify-between">
                              <span class="text-slate-500">Tracking</span>
                              <span class="font-mono text-xs">{{ sale.delivery.tracking_number }}</span>
                            </div>
                            <div class="flex justify-between">
                              <span class="text-slate-500">Kurir</span>
                              <span>{{ sale.delivery.courier_name || '-' }}</span>
                            </div>
                          </template>
                          <div v-else class="text-slate-400 text-xs">Belum ada pengiriman</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="!loading && pagedSales.length === 0">
              <td colspan="10" class="py-16 text-center text-slate-400">
                <div class="text-4xl mb-3">&#128203;</div>
                <div class="text-sm font-medium">Tidak ada data penjualan</div>
                <div class="text-xs mt-1">Coba ubah filter atau buat penjualan baru</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>


    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import * as XLSX from 'xlsx'
import api from '../../services/api'

const router = useRouter()

const allSales = ref([])
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('all')
const dateFrom = ref('')
const dateTo = ref('')
const sortKey = ref('sale_date')
const sortDir = ref(-1)
const currentPage = ref(1)
const pageSize = ref(10)
const expanded = ref({})

const statusOptions = ['all', 'pending', 'completed', 'cancelled']

const loadSales = async () => {
  loading.value = true
  try {
    const response = await api.get('/sales', { params: { per_page: 1000 } })
    allSales.value = response.data.data || response.data || []
  } catch (err) {
    console.error('Failed to load sales:', err)
  } finally {
    loading.value = false
  }
}

const filteredSales = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return allSales.value.filter(s => {
    if (statusFilter.value !== 'all' && s.status !== statusFilter.value) return false
    if (dateFrom.value && s.sale_date < dateFrom.value) return false
    if (dateTo.value && s.sale_date > dateTo.value) return false
    if (q) {
      const hay = ((s.invoice_number || '') + ' ' + (s.customer_name || '') + ' ' + (s.customer_phone || '')).toLowerCase()
      if (!hay.includes(q)) return false
    }
    return true
  })
})

const sortedSales = computed(() => {
  const key = sortKey.value
  return [...filteredSales.value].sort((a, b) => {
    let va = a[key] != null ? a[key] : ''
    let vb = b[key] != null ? b[key] : ''
    if (key === 'grand_total' || key === 'total_amount') {
      return (Number(va) - Number(vb)) * sortDir.value
    }
    return String(va).localeCompare(String(vb)) * sortDir.value
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSales.value.length / pageSize.value)))

const pagedSales = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return sortedSales.value.slice(start, start + pageSize.value)
})

const visiblePages = computed(() => {
  const tp = totalPages.value, cp = currentPage.value
  const pages = [], w = 7
  let start = Math.max(1, cp - Math.floor(w / 2))
  let end = Math.min(tp, start + w - 1)
  start = Math.max(1, end - w + 1)
  for (let p = start; p <= end; p++) pages.push(p)
  return pages
})

const allTotal = computed(() => allSales.value.reduce((s, x) => s + (Number(x.grand_total || x.total_amount) || 0), 0))
const filteredTotal = computed(() => filteredSales.value.reduce((s, x) => s + (Number(x.grand_total || x.total_amount) || 0), 0))

// ── Pie / Donut chart data ───────────────────────────────────
const salesStats = computed(() => {
  const statuses = [
    { key: 'pending', label: 'Pending', color: '#3b82f6', bg: '#eff6ff', borderColor: '#bfdbfe' },
    { key: 'completed', label: 'Completed', color: '#10b981', bg: '#f0fdf4', borderColor: '#a7f3d0' },
    { key: 'cancelled', label: 'Cancelled', color: '#f43f5e', bg: '#fff1f2', borderColor: '#fecdd3' },
  ]
  return statuses.map(s => {
    const items = allSales.value.filter(x => x.status === s.key)
    return {
      ...s,
      count: items.length,
      total: items.reduce((acc, x) => acc + (Number(x.grand_total || x.total_amount) || 0), 0)
    }
  })
})

const donutSegments = computed(() => {
  const total = allSales.value.length
  if (!total) return []
  let offset = 0
  return salesStats.value.map(s => {
    const pct = (s.count / total) * 100
    const seg = { color: s.color, dash: pct, offset }
    offset += pct
    return seg
  })
})

const setStatusFilter = (s) => { statusFilter.value = s; currentPage.value = 1 }
const statusCount = (s) => s === 'all' ? allSales.value.length : allSales.value.filter(x => x.status === s).length
const resetFilters = () => { searchQuery.value = ''; statusFilter.value = 'all'; dateFrom.value = ''; dateTo.value = ''; currentPage.value = 1 }
const setSort = (col) => { if (sortKey.value === col) sortDir.value *= -1; else { sortKey.value = col; sortDir.value = -1 }; currentPage.value = 1 }
const toggleExpand = (id) => { expanded.value = { ...expanded.value, [id]: !expanded.value[id] } }
const editSale = (sale) => { router.push({ name: 'SalesEdit', params: { id: sale.id } }) }

const deleteSale = async (id) => {
  if (!confirm('Yakin ingin menghapus penjualan ini?')) return
  try {
    await api.delete('/sales/' + id)
    await loadSales()
  } catch (err) {
    alert((err.response && err.response.data && err.response.data.message) || 'Gagal menghapus penjualan')
  }
}


const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}
const formatInt = (n) => Number(n || 0).toLocaleString('id-ID')
const taxDisplayName = (code) => { const names = { ppn: 'PPN', pph23: 'PPh 23' }; return names[code] || (code ? code.toUpperCase() : '') }
const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : ''

const avatarColors = ['bg-rose-500', 'bg-violet-600', 'bg-emerald-500', 'bg-cyan-600', 'bg-sky-600', 'bg-amber-600', 'bg-indigo-600', 'bg-pink-600']
const avatarColor = (name) => { if (!name) return 'bg-slate-400'; return avatarColors[name.charCodeAt(0) % avatarColors.length] }

const statusClr = (s) => {
  const map = { pending: 'bg-blue-50 text-blue-700 ring-blue-200', completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200', cancelled: 'bg-rose-50 text-rose-700 ring-rose-200', delivered: 'bg-teal-50 text-teal-700 ring-teal-200' }
  return map[s] || 'bg-slate-100 text-slate-700 ring-slate-200'
}

watch([searchQuery, statusFilter, dateFrom, dateTo], () => { currentPage.value = 1 })
onMounted(loadSales)
</script>

<style scoped>
.legend-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  border-radius: 9999px;
  padding: .25rem .75rem;
  font-size: .75rem;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #334155;
  transition: background .15s, color .15s, border-color .15s;
  cursor: pointer;
}

.legend-btn:hover {
  background: #f8fafc;
}

.legend-btn.active {
  background: #1e3a8a;
  color: #fff;
  border-color: #1e3a8a;
}

.legend-btn.active:hover {
  background: #1e40af;
}

.legend-label {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: .2rem .5rem;
  font-size: .65rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.2rem;
  height: 1.2rem;
  padding: 0 .3rem;
  border-radius: 9999px;
  font-size: .7rem;
  font-weight: 700;
  background: #eef2ff;
  color: #3730a3;
  border: 1px solid #e0e7ff;
}

.stat-badge {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  border-radius: 9999px;
  padding: .3rem .6rem;
  font-size: .72rem;
  font-weight: 600;
  background: #f8fafc;
  color: #334155;
  border: 1px solid #e5e7eb;
}

.vsep th+th,
.vsep td+td {
  border-left: 1px solid #e5e7eb;
}
</style>
