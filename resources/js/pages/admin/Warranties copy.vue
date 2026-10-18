<template>
  <div class="space-y-0">
    <!-- Top Header -->
    <div class="flex justify-between items-center mb-4">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          {{ $t('pageTitles.Warranties') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.productWarrantiesFromSalesTransactions') }}</p>
      </div>
    </div>

      <!-- Sales Summary + Pie Chart Footer -->
      <div class="border-t border-slate-200 bg-gradient-to-br from-slate-50 to-white">

        <!-- Donut Chart + Status Cards -->
        <div class="px-6 py-5 flex flex-col md:flex-row items-center gap-8">

          <!-- Donut SVG Chart -->
          <div class="relative shrink-0" style="width:140px;height:140px">
            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
              <!-- Background ring -->
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3.8"/>
              <!-- Segments -->
              <template v-for="(seg, i) in donutSegments" :key="i">
                <circle
                  cx="18" cy="18" r="15.9"
                  fill="none"
                  :stroke="seg.color"
                  stroke-width="3.8"
                  :stroke-dasharray="`${seg.dash} ${100 - seg.dash}`"
                  :stroke-dashoffset="`${-seg.offset}`"
                  stroke-linecap="butt"
                  style="transition: stroke-dasharray 0.6s ease"
                />
              </template>
            </svg>
            <!-- Center label -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-2xl font-bold text-slate-800">{{ allSales.length }}</span>
              <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wide">{{ $t('sales.total') }}</span>
            </div>
          </div>

          <!-- Status Cards -->
          <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 w-full">
            <div v-for="stat in salesStats" :key="stat.label"
              class="flex items-center gap-3 p-3 rounded-xl border"
              :style="`border-color: ${stat.borderColor}; background: ${stat.bg}`">
              <div class="w-3 h-3 rounded-full shrink-0" :style="`background:${stat.color}`"></div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ $ui(stat.label) }}</div>
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
            <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">{{ $t('sales.grandTotal') }}</div>
            <div class="text-2xl font-bold text-indigo-700">Rp {{ formatInt(allTotal) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ allSales.length }} {{ $t('ui.transactions') }}</div>
          </div>
        </div>

        <!-- Compact Pagination -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 pb-3 border-t border-slate-100 pt-3">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <label>{{ $t('ui.rows') }}</label>
            <select v-model="pageSize" @change="currentPage=1"
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
            <button v-for="p in visiblePages" :key="p" @click="currentPage = p"
              :class="['px-3 py-1 rounded-lg border text-sm', p === currentPage
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50']">
              {{ p }}
            </button>
            <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
              class="px-3 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm">
              &rarr;
            </button>
            <span class="ml-2 text-xs text-slate-400">
              {{ filteredSales.length === 0 ? 0 : ((currentPage-1)*pageSize)+1 }}–{{ Math.min(currentPage*pageSize, filteredSales.length) }}
              / {{ filteredSales.length }}
            </span>
          </div>
        </div>
      </div>

      <!-- Header: Title + Stats + Search -->
      <div class="px-4 pt-4 pb-3 border-b border-slate-200">
        <div class="grid items-center gap-3 md:grid-cols-[auto,1fr,auto]">
          <h3 class="text-lg font-bold text-slate-800">{{ $t('ui.daftarWarranty') }}</h3>
          <div class="justify-self-end w-full max-w-sm">
            <div class="flex items-center gap-2">
              <button @click="exportExcel"
                class="px-3 py-2 text-xs font-semibold rounded-lg border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 shadow-sm whitespace-nowrap flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm-1 1.5L18.5 9H13zM8 13h2.5l1.5 2.5L13.5 13H16l-2.5 3.5L16 20h-2.5L12 17.5 10.5 20H8l2.5-3.5zm0 0"/></svg>
                {{ $t('sales.excel') }}
              </button>
              <input v-model="searchQuery" type="text"
                class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-indigo-100"
                :placeholder="$t('ui.cariInvoiceCustomer')" />
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Legend Row -->
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/60">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <span class="legend-label">{{ $t('projectInvestment.materials.status') }}</span>
            <button v-for="s in statusOptions" :key="s"
              @click="setStatusFilter(s)"
              :class="['legend-btn', statusFilter === s ? 'active' : '']">
              <span>{{ $label(s) }}</span>
              <span class="count-badge">{{ statusCount(s) }}</span>
            </button>
          </div>
          <div class="flex flex-wrap items-center gap-2 ml-auto pl-3 border-l border-slate-200">
            <span class="legend-label">{{ $t('sales.date') }}</span>
            <input v-model="dateFrom" type="date" @change="currentPage=1"
              class="rounded-lg border border-slate-200 px-2 py-1 text-xs outline-none focus:ring-2 focus:ring-indigo-100" />
            <span class="text-xs text-slate-400">-</span>
            <input v-model="dateTo" type="date" @change="currentPage=1"
              class="rounded-lg border border-slate-200 px-2 py-1 text-xs outline-none focus:ring-2 focus:ring-indigo-100" />
            <button @click="resetFilters"
              class="px-3 py-1 rounded-lg border border-slate-200 bg-white text-xs font-semibold hover:bg-slate-50">
              {{ $t('ui.reset') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-gray-200 border-t-indigo-600"></div>
        <p class="mt-3 text-sm text-gray-600">{{ $t('ui.loadingSalesData') }}</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm vsep">
          <thead class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-3 py-3 w-8"></th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('invoice_number')">
                {{ $t('sales.invoice') }} <span v-if="sortKey==='invoice_number'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('customer_name')">
                {{ $t('sales.customer') }} <span v-if="sortKey==='customer_name'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('sale_date')">
                {{ $t('sales.date') }} <span v-if="sortKey==='sale_date'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <!-- tanggal pembelian tanggal masa aktif garansi -->
              <th class="px-4 py-3 text-left">{{ $t('ui.productWarranty') }}</th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('status')">
                {{ $t('projectInvestment.materials.status') }} <span v-if="sortKey==='status'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left">{{ $t('sales.delivery') }}</th>
              <th class="px-4 py-3 text-right">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <template v-for="(sale, idx) in pagedSales" :key="sale.id">
              <!-- Main Row -->
              <tr :class="['transition-colors cursor-pointer', idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white', 'hover:bg-indigo-50/40']"
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
                    <div :class="['w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-sm shrink-0', avatarColor(sale.customer_name)]">
                      {{ (sale.customer_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="font-semibold truncate text-slate-900">{{ sale.customer_name || '-' }}</div>
                      <div class="text-[11px] text-slate-400 truncate">{{ sale.customer_phone || '' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                  {{ formatDate(purchaseDate(sale)) }}
                  <div class="text-[11px] text-slate-400">{{ sale.invoice?.invoice_date ? $t('ui.invoiceDate') : $t('ui.transactionDate') }}</div>
                </td>
                <td class="px-4 py-3">
                  <template v-if="sale.items && sale.items.length">
                    <div v-for="(item, i) in sale.items.slice(0,2)" :key="i" class="text-xs text-slate-700">
                      <div>{{ item.product ? item.product.title : $t('sales.product') }} <span class="text-slate-400">x{{ item.quantity }}</span></div>
                      <div class="mt-1 flex flex-wrap gap-1">
                        <span :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold', warrantyInfo(sale, item).active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600']">
                          {{ warrantyInfo(sale, item).label }}
                        </span>
                        <span v-if="warrantyInfo(sale, item).endDate" class="text-[10px] text-slate-500">
                          {{ warrantyInfo(sale, item).months }} {{ $t('ui.monthsUntil') }} {{ formatDate(warrantyInfo(sale, item).endDate) }}
                        </span>
                        <span v-if="hasClaim(sale, item, 'replace')" class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                          {{ $t('ui.previouslyReturnedReplaced') }}
                        </span>
                        <span v-if="hasClaim(sale, item, 'repair')" class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                          {{ $t('ui.previouslyClaimedForRepair') }}
                        </span>
                      </div>
                    </div>
                    <div v-if="sale.items.length > 2" class="text-[11px] text-slate-400">+{{ sale.items.length - 2 }} {{ $t('ui.more') }}</div>
                  </template>
                  <span v-else class="text-slate-400 text-xs">-</span>
                </td>
                <td class="px-4 py-3">
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', statusClr(sale.status)]">
                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5"></span>
                    {{ $label(sale.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="sale.delivery"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700 ring-1 ring-teal-200">
                    &#128666; {{ sale.delivery.tracking_number }}
                  </span>
                  <span v-else class="text-slate-300 text-xs">{{ $t('sales.noDelivery') }}</span>
                </td>
                <td class="px-4 py-3 text-right" @click.stop>
                  <div class="flex justify-end gap-1.5 flex-wrap">
                    <button v-if="!sale.delivery && ['pending','completed'].includes(sale.status)"
                      @click="$router.push({ name: 'SalesEdit', params: { id: sale.id } })"
                      class="px-2 py-1 text-xs rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 font-medium">
                      {{ $t('sales.addDelivery') }}
                    </button>
                    <button @click="editWarranty(sale)"
                      class="px-2 py-1 text-xs rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium">{{ $t('common.edit') }}</button>
                    <button @click="$router.push({ name: 'SalesView', params: { id: sale.id } })"
                      class="px-2 py-1 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium">{{ $t('projectInvestment.actions.view') }}</button>
                    <button @click="deleteWarranty(sale.id)"
                      class="px-2 py-1 text-xs rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-medium">{{ $t('common.delete') }}</button>
                  </div>
                </td>
              </tr>

              <!-- Expand Detail Row -->
              <tr v-if="expanded[sale.id]" class="bg-white">
                <td colspan="10" class="px-0 py-0">
                  <div class="p-4 bg-indigo-50/30">
                    <div class="grid grid-cols-1 gap-4">

                      <div class="w-full border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">{{ $t('ui.productList') }}</div>

                        <div class="space-y-1.5 text-sm">
                          <input v-model="productSearchQueries[sale.id]"
                                 @input="productListPages[sale.id] = 1"
                                 type="search"
                                 class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-4 focus:ring-indigo-100"
                                 :placeholder="$t('ui.cariProdukAtauSerialNumber')" />
                          <table class="w-full text-sm text-left text-slate-700">
                            <thead class="text-[10px] text-slate-500 uppercase bg-slate-100">
                              <tr>
                                <th scope="col" class="px-2 py-1">{{ $t('sales.product') }}</th>
                                <th scope="col" class="px-2 py-1">SN</th>
                                <th scope="col" class="px-2 py-1">{{ $t('ui.warrantyPeriod') }}</th>
                                <th scope="col" class="px-2 py-1">{{ $t('projectInvestment.materials.status') }}</th>
                                <th scope="col" class="px-2 py-1 w-0">{{ $t('sales.actions') }}</th>
                              </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in productPagination[sale.id].rows" :key="row.key" class="border-b border-slate-200">
                                  <td class="px-2 py-1">{{ row.product?.title || row.product?.name || $t('ui.productDeleted') }}</td>
                                  <td class="px-2 py-1">
                                    <code v-if="row.serial_number" class="text-[10px] font-mono text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded">{{ row.serial_number }}</code>
                                    <span v-else class="text-[10px] text-slate-400">{{ $t('ui.noSerialNumbersYet') }}</span>
                                    <p v-if="row.previousSerials.length" class="mt-1 text-[10px] text-slate-500">{{ $t('ui.returnedSerialNumber') }} {{ row.previousSerials.join(', ') }}</p>
                                  </td>
                                  <td class="px-2 py-1">
                                    <span v-if="row.info.startDate && row.info.endDate" class="text-[10px] text-slate-500">
                                      {{ formatDate(row.info.startDate) }} - {{ formatDate(row.info.endDate) }}
                                    </span>
                                    <span v-else class="text-[10px] text-slate-400">-</span>
                                  </td>
                                  <td class="px-2 py-1">
                                    <span :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold', row.info.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600']">{{ row.info.label }}</span>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                      <span v-if="row.claims.length" class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">{{ $t('ui.warrantyAlreadyClaimed') }}</span>
                                      <span v-if="row.exchanged" class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ $t('ui.returnExchangeItem') }}</span>
                                      <span v-if="row.activeClaim" class="rounded-full bg-yellow-50 px-2 py-0.5 text-[10px] font-semibold text-yellow-700">{{ $t('ui.claimInProgress') }}</span>
                                    </div>
                                    <router-link v-for="claim in row.claims" :key="claim.id" :to="{ name: 'RMAs', query: { search: claim.rma_code } }" class="mt-1 block text-[10px] text-indigo-600 hover:underline">{{ claim.rma_code }} · {{ claimStatusLabel(claim.status) }}</router-link>
                                  </td>
                                  <td class="px-2 py-1">
                                    <router-link v-if="row.warranty_id && row.info.active && !row.activeClaim" :to="{ name: 'WarrantyClaim', params: { id: row.warranty_id } }" class="text-blue-500 hover:text-blue-700 whitespace-nowrap">{{ $t('labels.warranty_claim') }}</router-link>
                                    <span v-else-if="row.activeClaim" class="text-xs text-amber-600">{{ $t('ui.awaitingClaimResults') }}</span>
                                    <span v-else class="text-xs text-slate-400">{{ $t('ui.claimUnavailable') }}</span>
                                    <button v-if="row.warranty_id" @click="deleteWarranty(row.warranty_id)" class="text-red-500 hover:text-red-700 ml-2">{{ $t('common.delete') }}</button>
                                  </td>
                                </tr>

                              <tr v-if="!productPagination[sale.id].total">
                                <td colspan="5" class="px-2 py-1 text-slate-400">{{ productSearchQueries[sale.id]?.trim() ? $t('ui.productOrSerialNumberNotFound') : $t('ui.noItems') }}</td>
                              </tr>
                            </tbody>
                          </table>
                          <div v-if="productPagination[sale.id].total > productsPerPage" class="flex flex-wrap items-center justify-between gap-2 pt-3 text-xs text-slate-500">
                            <span>{{ productPagination[sale.id].from }}–{{ productPagination[sale.id].to }} {{ $t('sales.of') }} {{ productPagination[sale.id].total }} {{ $t('ui.products') }}</span>
                            <div class="flex items-center gap-2">
                              <button type="button" @click="productListPages[sale.id] = productPagination[sale.id].page - 1" :disabled="productPagination[sale.id].page === 1" class="rounded-lg border border-slate-200 px-2 py-1 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">{{ $t('sales.previous') }}</button>
                              <span>{{ productPagination[sale.id].page }} / {{ productPagination[sale.id].pages }}</span>
                              <button type="button" @click="productListPages[sale.id] = productPagination[sale.id].page + 1" :disabled="productPagination[sale.id].page === productPagination[sale.id].pages" class="rounded-lg border border-slate-200 px-2 py-1 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">{{ $t('sales.next') }}</button>
                            </div>
                          </div>
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
                <div class="text-sm font-medium">{{ $t('ui.noSalesData') }}</div>
                <div class="text-xs mt-1">{{ $t('ui.tryAdjustingTheFiltersOrCreatingANewSale') }}</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      
    <!-- </div> -->

    <!-- DELIVERY TAB -->
    <!-- <div v-if="activeTab === 'deliveries'" class="mt-4">
      <Deliveries />
    </div> -->
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import * as XLSX from 'xlsx'
import api from '../../services/api'

const router = useRouter()


const allSales     = ref([])
const loading      = ref(true)
const searchQuery  = ref('')
const statusFilter = ref('all')
const dateFrom     = ref('')
const dateTo       = ref('')
const sortKey      = ref('sale_date')
const sortDir      = ref(-1)
const currentPage  = ref(1)
const pageSize     = ref(10)
const expanded     = ref({})
const productsPerPage = 5
const productListPages = ref({})
const productSearchQueries = ref({})

const statusOptions = ['all', 'pending', 'completed', 'cancelled']

const loadSales = async () => {
  loading.value = true
  try {
    const response = await api.get('/sales', { params: { per_page: 1000, include_warranty_status: true } })
    allSales.value = response.data.data || response.data || []
  } catch (err) {
    console.error('Gagal memuat penjualan:', err)
  } finally {
    loading.value = false
  }
}

const filteredSales = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return allSales.value.filter(s => {
    if (statusFilter.value !== 'all' && s.status !== statusFilter.value) return false
    if (dateFrom.value && s.sale_date < dateFrom.value) return false
    if (dateTo.value   && s.sale_date > dateTo.value)   return false
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
  let end   = Math.min(tp, start + w - 1)
  start = Math.max(1, end - w + 1)
  for (let p = start; p <= end; p++) pages.push(p)
  return pages
})

const allTotal      = computed(() => allSales.value.reduce((s, x) => s + (Number(x.grand_total || x.total_amount) || 0), 0))
const filteredTotal = computed(() => filteredSales.value.reduce((s, x) => s + (Number(x.grand_total || x.total_amount) || 0), 0))

// ── Pie / Donut chart data ───────────────────────────────────
const salesStats = computed(() => {
  const statuses = [
    { key: 'pending',   label: uiText("Menunggu"),   color: '#3b82f6', bg: '#eff6ff', borderColor: '#bfdbfe' },
    { key: 'completed', label: uiText("Selesai"), color: '#10b981', bg: '#f0fdf4', borderColor: '#a7f3d0' },
    { key: 'cancelled', label: uiText("Dibatalkan"), color: '#f43f5e', bg: '#fff1f2', borderColor: '#fecdd3' },
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

const filters = ref({ search: '', status: '' });
let debounceTimer;

const activeCount = computed(() => (warranties.value.data || []).filter((warranty) => warranty.status === 'active').length);
const groupedWarranties = computed(() => {
  const groups = new Map();

  for (const warranty of warranties.value.data || []) {
    const key = warranty.sale_id || `warranty-${warranty.id}`;
    if (!groups.has(key)) {
      groups.set(key, {
        key,
        invoice: warranty.sale?.invoice_number || uiText("Tanpa Faktur"),
        customer: warranty.sale?.customer_name || '',
        items: [],
      });
    }
    groups.get(key).items.push(warranty);
  }

  return [...groups.values()];
});

const setStatusFilter = (s) => { statusFilter.value = s; currentPage.value = 1 }
const statusCount = (s) => s === 'all' ? allSales.value.length : allSales.value.filter(x => x.status === s).length
const resetFilters = () => { searchQuery.value = ''; statusFilter.value = 'all'; dateFrom.value = ''; dateTo.value = ''; currentPage.value = 1 }
const setSort = (col) => { if (sortKey.value === col) sortDir.value *= -1; else { sortKey.value = col; sortDir.value = -1 }; currentPage.value = 1 }
const toggleExpand = (id) => { expanded.value = { ...expanded.value, [id]: !expanded.value[id] } }
const editSale = (sale) => { router.push({ name: 'SalesEdit', params: { id: sale.id } }) }

const deleteSale = async (id) => {
  if (!confirm(uiText("Yakin ingin menghapus penjualan ini?"))) return
  try {
    await api.delete('/sales/' + id)
    await loadSales()
  } catch (err) {
    alert((err.response && err.response.data && err.response.data.message) || uiText("Gagal menghapus penjualan"))
  }
}

const exportExcel = () => {
  const rows = sortedSales.value

  // Build AOA (array of arrays) with header
  const headers = [uiText("Faktur"), uiText("Pelanggan"), uiText("Telepon"), uiText("Tanggal"), uiText("Gudang"), uiText("Status"), uiText("Pajak"), 'Subtotal (Rp)', uiText("Total Keseluruhan (Rp)")]
  const data = [headers]
  rows.forEach(s => {
    const items = (s.items || []).map(i => (i.product ? i.product.title : (i.product_name || uiText("Produk"))) + ' x' + i.quantity).join('; ')
    data.push([
      s.invoice_number || '',
      s.customer_name  || '',
      s.customer_phone || '',
      s.sale_date      || '',
      s.warehouse ? s.warehouse.name : '',
      s.status         || '',
      s.tax_type ? (taxDisplayName(s.tax_type) + ' ' + s.tax_rate + '%') : '',
      Number(s.subtotal || s.total_amount || 0),
      Number(s.grand_total || s.total_amount || 0)
    ])
  })

  const ws = XLSX.utils.aoa_to_sheet(data)

  // Auto column width
  const colWidths = headers.map((h, ci) => {
    const max = data.reduce((m, row) => Math.max(m, String(row[ci] || '').length), h.length)
    return { wch: Math.min(max + 2, 40) }
  })
  ws['!cols'] = colWidths

  // Style header row (bold)
  headers.forEach((_, ci) => {
    const cellRef = XLSX.utils.encode_cell({ r: 0, c: ci })
    if (ws[cellRef]) ws[cellRef].s = { font: { bold: true }, fill: { fgColor: { rgb: 'E8EEFF' } } }
  })

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, uiText("Penjualan"))
  XLSX.writeFile(wb, 'sales_' + new Date().toISOString().slice(0, 10) + '.xlsx')
}

const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(uiLocale(), { year: 'numeric', month: 'short', day: 'numeric' })
}
const purchaseDate = (sale) => sale.invoice?.invoice_date || sale.sale_date
const splitSerials = (serials) => String(serials || '').split(',').map(sn => sn.trim()).filter(Boolean)
const claimStatusLabel = (status) => ({ pending: 'Menunggu pengecekan', approved: uiText("Disetujui"), received: uiText("Barang diterima"), processed: uiText("Menunggu pengiriman"), completed: uiText("Selesai"), rejected: uiText("Ditolak") }[status] || status)
const productList = (sale) => (sale.items || []).flatMap(item => {
  const records = (sale.warranties || []).filter(record => Number(record.product_id) === Number(item.product_id))
  const deliveryItems = (sale.delivery?.items || []).filter(record => Number(record.product_id) === Number(item.product_id))
  const units = records.length ? records : deliveryItems.length ? deliveryItems : [item]
  return units.map((record, index) => {
    const serial = String(record.serial_number || '').trim().toLowerCase()
    const claims = (sale.rmas || []).filter(rma => {
      if (Number(rma.product_id) !== Number(item.product_id)) return false
      const matchesWarranty = records.length && Number(rma.warranty_id) === Number(record.id)
      const matchesSerial = [...splitSerials(rma.serial_number), ...splitSerials(rma.returned_serial_number)]
        .some(sn => sn.toLowerCase() === serial)
      return serial ? matchesSerial || (rma.warranty_claim_flow && matchesWarranty) : matchesWarranty
    })
    const previousSerials = [...new Set(claims.flatMap(rma => {
      const current = splitSerials(rma.serial_number)
      const previous = splitSerials(rma.returned_serial_number)
      const position = current.findIndex(sn => sn.toLowerCase() === serial)
      return position >= 0 && previous[position] && previous[position].toLowerCase() !== serial ? [previous[position]] : []
    }))]
    return {
    key: `${item.id}-${index}`,
    warranty_id: records.length ? record.id : null,
    product: record.product || item.product,
    serial_number: record.serial_number,
    claims,
    previousSerials,
    activeClaim: claims.some(rma => !['completed', 'rejected'].includes(rma.status)),
    exchanged: claims.some(rma => rma.status !== 'rejected' && rma.resolution === 'replace'),
    info: warrantyInfo(records.length ? { ...sale, warranties: [record] } : sale, item),
    }
  })
})
const productPagination = computed(() => Object.fromEntries(allSales.value.map(sale => {
  const query = (productSearchQueries.value[sale.id] || '').trim().toLowerCase()
  const products = productList(sale).filter(row => !query ||
    [row.product?.title, row.product?.name, row.product?.sku, row.serial_number, ...row.previousSerials]
      .some(value => String(value || '').toLowerCase().includes(query)))
  const total = products.length
  const pages = Math.max(1, Math.ceil(total / productsPerPage))
  const page = Math.min(Math.max(1, productListPages.value[sale.id] || 1), pages)
  const start = (page - 1) * productsPerPage
  return [sale.id, {
    rows: products.slice(start, start + productsPerPage),
    total, pages, page,
    from: total ? start + 1 : 0,
    to: Math.min(start + productsPerPage, total),
  }]
})))
const warrantyInfo = (sale, item) => {
  if (!item.product_id) {
    return { active: false, label: 'Data produk tidak tersedia', months: 0, startDate: null, endDate: null }
  }

  const warranty = (sale.warranties || []).find(record => Number(record.product_id) === Number(item.product_id))
  const months = Math.trunc(Number(warranty?.warranty_period_months ?? item.product?.warranty_period_months ?? 12))
  const startDate = purchaseDate(sale)

  if (!startDate || months <= 0) {
    return { active: false, label: months <= 0 ? 'Tanpa garansi' : uiText("Tanggal pembelian tidak tersedia"), months, startDate: null, endDate: null }
  }

  const [year, month, day] = startDate.slice(0, 10).split('-').map(Number)
  const targetMonth = month - 1 + months
  const targetYear = year + Math.floor(targetMonth / 12)
  const normalizedMonth = targetMonth % 12
  const lastDay = new Date(targetYear, normalizedMonth + 1, 0).getDate()
  const endDate = `${targetYear}-${String(normalizedMonth + 1).padStart(2, '0')}-${String(Math.min(day, lastDay)).padStart(2, '0')}`
  const today = new Date()
  const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
  const active = sale.status !== 'cancelled' && endDate >= todayDate

  const label = sale.status === 'cancelled'
    ? 'Transaksi dibatalkan'
    : active ? 'Garansi aktif' : 'Garansi berakhir'
  return { active, label, months, startDate, endDate }
}
const hasClaim = (sale, item, resolution) => (sale.rmas || []).some(rma =>
  Number(rma.product_id) === Number(item.product_id) &&
  rma.status !== 'rejected' &&
  rma.resolution === resolution
)
const formatInt = (n) => Number(n || 0).toLocaleString(uiLocale())
const taxDisplayName = (code) => { const names = { ppn: 'PPN', pph23: 'PPh 23' }; return names[code] || (code ? code.toUpperCase() : '') }
const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : ''

const avatarColors = ['bg-rose-500','bg-violet-600','bg-emerald-500','bg-cyan-600','bg-sky-600','bg-amber-600','bg-indigo-600','bg-pink-600']
const avatarColor = (name) => { if (!name) return 'bg-slate-400'; return avatarColors[name.charCodeAt(0) % avatarColors.length] }

const statusClr = (s) => {
  const map = { pending: uiText("bg-blue-50 text-blue-700 ring-blue-200"), completed: uiText("bg-emerald-50 text-emerald-700 ring-emerald-200"), cancelled: 'bg-rose-50 text-rose-700 ring-rose-200', delivered: 'bg-teal-50 text-teal-700 ring-teal-200' }
  return map[s] || 'bg-slate-100 text-slate-700 ring-slate-200'
}



watch([searchQuery, statusFilter, dateFrom, dateTo], () => { currentPage.value = 1 })
onMounted(() => {
  loadSales()
  window.addEventListener('focus', loadSales)
})
onUnmounted(() => window.removeEventListener('focus', loadSales))

const loadWarranties = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warranties', {
      params: { page, per_page: 15, ...filters.value },
    });
    warranties.value = response.data;
  } catch (err) {
    console.error('Gagal memuat garansi:', err);
  } finally {
    loading.value = false;
  }
};

const warranties = ref({ data: [] });
const debouncedLoad = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadWarranties(), 300);
};

const editWarranty = (id) => router.push({ name: 'WarrantyEdit', params: { id } });

const deleteWarranty = async (id) => {
  if (!confirm(uiText("Hapus garansi ini?"))) return;
  try {
    await api.delete(`/warranties/${id}`);
    await loadSales();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus garansi."));
  }
};

onMounted(loadWarranties);
</script>

<style scoped>
.legend-btn { display:inline-flex; align-items:center; gap:.5rem; border-radius:9999px; padding:.25rem .75rem; font-size:.75rem; font-weight:600; border:1px solid #e5e7eb; background:#ffffff; color:#334155; transition:background .15s,color .15s,border-color .15s; cursor:pointer; }
.legend-btn:hover { background:#f8fafc; }
.legend-btn.active { background:#1e3a8a; color:#fff; border-color:#1e3a8a; }
.legend-btn.active:hover { background:#1e40af; }
.legend-label { display:inline-flex; align-items:center; border-radius:9999px; padding:.2rem .5rem; font-size:.65rem; font-weight:700; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.count-badge { display:inline-flex; align-items:center; justify-content:center; min-width:1.2rem; height:1.2rem; padding:0 .3rem; border-radius:9999px; font-size:.7rem; font-weight:700; background:#eef2ff; color:#3730a3; border:1px solid #e0e7ff; }
.stat-badge { display:inline-flex; align-items:center; gap:.25rem; border-radius:9999px; padding:.3rem .6rem; font-size:.72rem; font-weight:600; background:#f8fafc; color:#334155; border:1px solid #e5e7eb; }
.vsep th + th, .vsep td + td { border-left:1px solid #e5e7eb; }
</style>
