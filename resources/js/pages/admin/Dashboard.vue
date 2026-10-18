<template>
  <div class="space-y-8 animate-fade-in">
    <!-- Greeting Header -->
    <div class="flex justify-between items-end">
      <div>
        <p class="text-gray-500 text-sm">{{ currentDate }}</p>
        <h1 class="text-3xl font-light text-gray-800">{{ $t('dashboard.welcomeBack') }}</h1>
      </div>
    </div>

    <!-- Stats Grid - Minimal Cards -->
    <!-- <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div
        class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">📦</span>
          <span class="text-xs text-gray-400 group-hover:text-blue-500 transition-colors">{{
            $t('dashboard.totalProducts') }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.totalProducts || 0 }}</p>
      </div>

      <div
        class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">💰</span>
          <span class="text-xs text-gray-400 group-hover:text-green-500 transition-colors">{{ $t('dashboard.totalSales')
            }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.totalSales || 0 }}</p>
      </div>

      <div
        class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">🛒</span>
          <span class="text-xs text-gray-400 group-hover:text-purple-500 transition-colors">{{
            $t('dashboard.totalPurchases') }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.totalPurchases || 0 }}</p>
      </div>

      <div
        class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">📋</span>
          <span class="text-xs text-gray-400 group-hover:text-orange-500 transition-colors">Pending</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.pendingSales || 0 }}</p>
      </div>

      <div
        class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">⚠️</span>
          <span class="text-xs text-gray-400 group-hover:text-red-500 transition-colors">{{ $t('dashboard.lowStock')
            }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.lowStockItems || 0 }}</p>
      </div>
    </div> -->

    <!-- Main Content Grid -->
    <div class="grid gap-4">

      <!-- Sales Pie Chart Section -->
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <!-- Header -->
      <div class="px-6 pt-5 pb-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-medium text-gray-800">{{ $t('ui.salesStatistics') }}</h2>
          <p class="text-sm text-gray-400">{{ $t('ui.summaryByTransactionStatus') }}</p>
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-400 mb-0.5">{{ $t('sales.grandTotal') }}</div>
          <div class="text-xl font-bold text-indigo-600">Rp {{ formatRp(salesGrandTotal) }}</div>
        </div>
      </div>

      <!-- Chart + Cards -->
      <div class="px-6 py-6 flex flex-col md:flex-row items-center gap-8">

        <!-- Donut SVG -->
        <div class="relative shrink-0" style="width:160px;height:160px">
          <svg viewBox="0 0 36 36" class="w-full h-full" style="transform:rotate(-90deg)">
            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="4"/>
            <template v-if="salesData.length">
              <circle v-for="(seg, i) in donutSegments" :key="i"
                cx="18" cy="18" r="15.9" fill="none"
                :stroke="seg.color" stroke-width="4"
                :stroke-dasharray="seg.dash + ' ' + (100 - seg.dash)"
                :stroke-dashoffset="-seg.offset"
                stroke-linecap="butt"
                style="transition: stroke-dasharray 0.7s ease"
              />
            </template>
            <!-- Empty ring when no data -->
            <circle v-if="!salesData.length" cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="4" stroke-dasharray="100 0"/>
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-2xl font-bold text-gray-800">{{ salesData.length }}</span>
            <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">{{ $t('ui.transactions2') }}</span>
          </div>
        </div>

        <!-- Status Cards -->
        <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 w-full">
          <div v-for="stat in salesStats" :key="stat.key"
            class="flex items-start gap-3 p-4 rounded-2xl border transition-all duration-200 hover:shadow-sm"
            :style="'border-color:' + stat.borderColor + '; background:' + stat.bg">
            <!-- Color dot + icon -->
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :style="'background:' + stat.iconBg">
              <span class="text-lg">{{ stat.icon }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-bold uppercase tracking-wide" :style="'color:' + stat.color">{{ $ui(stat.label) }}</div>
              <div class="text-2xl font-bold text-gray-800 mt-0.5">{{ stat.count }}</div>
              <div class="text-xs text-gray-400 mt-0.5">Rp {{ formatRp(stat.total) }}</div>
            </div>
            <div class="text-right shrink-0">
              <div class="text-sm font-bold rounded-full px-2 py-0.5" :style="'color:' + stat.color + '; background:' + stat.pctBg">
                {{ salesData.length ? Math.round(stat.count / salesData.length * 100) : 0 }}%
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly mini bar chart (SVG) -->
        <div class="shrink-0 hidden xl:block" style="width:180px">
          <div class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-3">{{ $t('ui.last6Months') }}</div>
          <div class="flex items-end gap-1 h-20">
            <div v-for="(m, i) in monthlySales" :key="i" class="flex-1 flex flex-col items-center gap-1">
              <div class="w-full rounded-t-md bg-indigo-100 hover:bg-indigo-400 transition-colors"
                :style="'height:' + (m.maxH) + 'px'" :title="m.label + ': ' + m.count + ' ' + $t('dashboard.transactionCountUnit')"></div>
              <span class="text-[9px] text-gray-400">{{ m.shortLabel }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Loading state -->
      <div v-if="salesLoading" class="px-6 pb-6 text-center text-sm text-gray-400">
        <span class="inline-block w-4 h-4 border-2 border-indigo-300 border-t-indigo-600 rounded-full animate-spin mr-2"></span>
        {{ $t('ui.loadingSalesData') }}
      </div>
      <div v-else-if="!salesData.length" class="px-6 pb-6 text-center text-sm text-gray-400">
        {{ $t('ui.noSalesDataYet') }}
      </div>
    </div>
      <!-- Recent Stock Report -->
      <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-lg font-medium text-gray-800">{{ $t('dashboard.stockReport') }}</h2>
            <p class="text-sm text-gray-400">{{ defaultWarehouseName ? defaultWarehouseName :
              $t('dashboard.topProducts') }}</p>
          </div>
        </div>
        <div class="overflow-hidden">
          <table class="w-full">
            <thead>
              <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                <th class="pb-4 font-medium">{{ $t('dashboard.sku') }}</th>
                <th class="pb-4 font-medium">{{ $t('dashboard.productName') }}</th>
                <th class="pb-4 font-medium">{{ $t('dashboard.category') }}</th>
                <th class="pb-4 font-medium">{{ $t('dashboard.subcategory') }}</th>
                <th class="pb-4 font-medium text-right">{{ $t('dashboard.quantity') }}</th>
                <th class="pb-4 font-medium text-right">{{ $t('dashboard.updated') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(item, index) in stockReports" :key="index" class="group">
                <td class="py-4">
                  <span class="text-sm font-mono text-gray-600 bg-gray-50 px-2 py-1 rounded">{{ item.code }}</span>
                </td>
                <td class="py-4 text-sm text-gray-700">{{ item.unit }}</td>
                <td class="py-4 text-sm text-gray-600">
                  <div v-if="item.categories && item.categories.length" class="flex flex-wrap gap-1">
                    <span v-for="(cat, i) in item.categories" :key="i"
                      class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">{{ cat }}</span>
                  </div>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="py-4 text-sm text-gray-600">
                  <div v-if="item.subcategories && item.subcategories.length" class="flex flex-wrap gap-1">
                    <span v-for="(sub, i) in item.subcategories" :key="i"
                      class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs">{{ sub }}</span>
                  </div>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="py-4 text-right">
                  <span class="text-sm font-semibold text-gray-800">{{ item.value }}</span>
                </td>
                <td class="py-4 text-right text-sm text-gray-400">{{ $date(item.date) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="!stockReports.length" class="py-8 text-center text-gray-400">
            {{ $t('dashboard.noStockData') }}
          </div>
          <!-- Pagination -->
          <div v-if="stockPagination.last_page > 1" class="flex items-center justify-between pt-4 border-t border-gray-100 mt-4">
            <p class="text-sm text-gray-500">
              {{ $t('nav.pages') }} {{ stockPagination.current_page }} {{ $t('sales.of') }} {{ stockPagination.last_page }}
              <span class="text-gray-400">({{ stockPagination.total }} {{ $t('ui.products3') }}</span>
            </p>
            <div class="flex gap-2">
              <button
                @click="changeStockPage(stockPagination.current_page - 1)"
                :disabled="stockPagination.current_page <= 1"
                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                {{ $t('ui.prev') }}
              </button>
              <button
                @click="changeStockPage(stockPagination.current_page + 1)"
                :disabled="stockPagination.current_page >= stockPagination.last_page"
                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                {{ $t('ui.next') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart - Takes 2 columns -->
      <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-lg font-medium text-gray-800">{{ $t('ui.topProducts') }}</h2>
            <p class="text-sm text-gray-400">{{ $t('ui.unitsSoldThisPeriod') }}</p>
          </div>
          <select v-model="selectedCategory" @change="loadDashboardData"
            class="text-sm px-3 py-2 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-gray-400 transition-colors">
            <option :value="null">{{ $t('ui.allCategories') }}</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        <VueApexCharts v-if="stockChartOptions && stockChartSeries.length" type="bar" height="580"
          :options="stockChartOptions" :series="localizedStockChartSeries" />
        <div v-else class="h-64 flex items-center justify-center text-gray-400">
          <p>{{ $t('common.noData') }}</p>
        </div>
      </div>



      <!-- Stock Status Cards -->
      <!-- <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <div class="mb-6">
          <h2 class="text-lg font-medium text-gray-800">{{ $t('dashboard.stockStatus') }}</h2>
          <p v-if="defaultWarehouseName" class="text-sm text-gray-400">{{ defaultWarehouseName }}</p>
        </div>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-green-600 text-lg">✓</span>
              </div>
              <span class="text-gray-700">{{ $t('dashboard.inStock') }}</span>
            </div>
            <span class="text-xl font-semibold text-gray-800">{{ stockStatus.ready }}</span>
          </div>

          <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <span class="text-yellow-600 text-lg">↻</span>
              </div>
              <span class="text-gray-700">{{ $t('dashboard.restock') }}</span>
            </div>
            <span class="text-xl font-semibold text-gray-800">{{ stockStatus.restock }}</span>
          </div>

          <div class="flex items-center justify-between p-4 bg-orange-50 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                <span class="text-orange-600 text-lg">!</span>
              </div>
              <span class="text-gray-700">Low</span>
            </div>
            <span class="text-xl font-semibold text-gray-800">{{ stockStatus.needed }}</span>
          </div>

          <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <span class="text-red-600 text-lg">×</span>
              </div>
              <span class="text-gray-700">{{ $t('dashboard.outOfStock') }}</span>
            </div>
            <span class="text-xl font-semibold text-gray-800">{{ stockStatus.out }}</span>
          </div>
        </div>
      </div> -->
    </div>

  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';
import VueApexCharts from 'vue3-apexcharts';

// ── Sales data for pie chart ─────────────────────────────────
const salesData    = ref([])
const salesLoading = ref(true)

const stats = ref({});
const stockChartOptions = ref(null);
const stockChartSeries = ref([]);
const localizedStockChartSeries = computed(() => stockChartSeries.value.map(series => ({
  ...series,
  name: uiText(series.name),
})));
const stockReports = ref([]);
const categories = ref([]);
const selectedCategory = ref(null);
const defaultWarehouseName = ref('');
const stockCurrentPage = ref(1);
const stockPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const stockStatus = ref({
  needed: 0,
  out: 0,
  restock: 0,
  ready: 0,
});

const currentDate = computed(() => {
  return new Date().toLocaleDateString(uiLocale(), {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

// ── Sales computed ───────────────────────────────────────────
const salesStats = computed(() => [
  { key: 'pending',   label: uiText("Menunggu"),   icon: '🕐', color: '#3b82f6', bg: '#eff6ff', borderColor: '#bfdbfe', iconBg: '#dbeafe', pctBg: '#eff6ff',
    count: salesData.value.filter(s => s.status === 'pending').length,
    total: salesData.value.filter(s => s.status === 'pending').reduce((a, x) => a + Number(x.grand_total || x.total_amount || 0), 0) },
  { key: 'completed', label: uiText("Selesai"), icon: '✅', color: '#10b981', bg: '#f0fdf4', borderColor: '#a7f3d0', iconBg: '#d1fae5', pctBg: '#f0fdf4',
    count: salesData.value.filter(s => s.status === 'completed').length,
    total: salesData.value.filter(s => s.status === 'completed').reduce((a, x) => a + Number(x.grand_total || x.total_amount || 0), 0) },
  { key: 'cancelled', label: uiText("Dibatalkan"), icon: '❌', color: '#f43f5e', bg: '#fff1f2', borderColor: '#fecdd3', iconBg: '#fee2e2', pctBg: '#fff1f2',
    count: salesData.value.filter(s => s.status === 'cancelled').length,
    total: salesData.value.filter(s => s.status === 'cancelled').reduce((a, x) => a + Number(x.grand_total || x.total_amount || 0), 0) },
])

const salesGrandTotal = computed(() =>
  salesData.value.reduce((a, x) => a + Number(x.grand_total || x.total_amount || 0), 0)
)

const donutSegments = computed(() => {
  const total = salesData.value.length
  if (!total) return []
  let offset = 0
  return salesStats.value.map(s => {
    const pct = (s.count / total) * 100
    const seg = { color: s.color, dash: pct, offset }
    offset += pct
    return seg
  })
})

const monthlySales = computed(() => {
  const now = new Date()
  const months = []
  for (let i = 5; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')
    months.push({
      key,
      label: d.toLocaleDateString(uiLocale(), { month: 'long', year: 'numeric' }),
      shortLabel: d.toLocaleDateString(uiLocale(), { month: 'short' }),
      count: salesData.value.filter(s => s.sale_date && s.sale_date.startsWith(key)).length
    })
  }
  const maxCount = Math.max(1, ...months.map(m => m.count))
  return months.map(m => ({ ...m, maxH: Math.max(4, Math.round((m.count / maxCount) * 64)) }))
})

const formatRp = (n) => Number(n || 0).toLocaleString(uiLocale())

const loadSalesData = async () => {
  salesLoading.value = true
  try {
    const res = await api.get('/sales', { params: { per_page: 1000 } })
    salesData.value = res.data.data || res.data || []
  } catch (e) {
    console.error('Failed to load sales for dashboard:', e)
  } finally {
    salesLoading.value = false
  }
}

onMounted(async () => {
  try {
    await loadCategories();
    await loadDashboardData();
    await loadSalesData();
  } catch (error) {
    console.error('Dashboard: Failed to load', error);
  }
});

const loadCategories = async () => {
  try {
    const response = await api.get('/categories');
    categories.value = response.data;
  } catch (error) {
    console.error('Gagal memuat kategori:', error);
  }
};

const loadDashboardData = async () => {
  try {
    const statsResponse = await api.get('/dashboard/stats');
    stats.value = statsResponse.data;

    const params = selectedCategory.value ? { category_id: selectedCategory.value } : {};
    const stockResponse = await api.get('/dashboard/stock-chart', { params });

    stockChartOptions.value = {
      chart: {
        type: 'bar',
        toolbar: { show: false },
        height: 280,
        fontFamily: 'inherit',
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '10%',
          borderRadius: 4,
          borderRadiusApplication: 'end',
        },
      },
      colors: ['#6366f1'],
      dataLabels: { enabled: false },
      stroke: { show: false },
      grid: {
        borderColor: '#f1f5f9',
        strokeDashArray: 4,
      },
      xaxis: {
        categories: stockResponse.data.categories || [],
        labels: {
          style: {
            fontSize: '11px',
            colors: '#94a3b8',
          },
        },
        axisBorder: { show: false },
        axisTicks: { show: true },
      },
      yaxis: {
        labels: {
          style: {
            colors: '#94a3b8',
          },
        },
      },
      fill: {
        opacity: 1,
        type: 'gradient',
        gradient: {
          shade: 'light',
          type: 'vertical',
          shadeIntensity: 0.3,
          opacityFrom: 1,
          opacityTo: 0.8,
        },
      },
      tooltip: {
        y: {
          formatter: (val) => val + ' ' + uiText('unit'),
        },
      },
      legend: { show: false },
    };

    stockChartSeries.value = stockResponse.data.series || [];
    await loadStockStatus();
    await loadStockReports();
  } catch (error) {
    console.error('Gagal memuat data dasbor:', error);
  }
};

const loadStockStatus = async () => {
  try {
    const response = await api.get('/dashboard/stock-report');
    const stocks = response.data.data || [];

    if (response.data.warehouse) {
      defaultWarehouseName.value = response.data.warehouse.name;
    }

    stockStatus.value = {
      ready: stocks.filter(s => s.quantity > 5).length,
      out: stocks.filter(s => s.quantity === 0).length,
      restock: stocks.filter(s => s.quantity > 0 && s.quantity <= 1).length,
      needed: stocks.filter(s => s.quantity > 1 && s.quantity <= 5).length,
    };
  } catch (error) {
    console.error('Gagal memuat status stok:', error);
  }
};

const loadStockReports = async (page = 1) => {
  try {
    const response = await api.get('/dashboard/stock-report', {
      params: { page, per_page: 10 }
    });
    const stocks = response.data.data || [];

    if (response.data.warehouse) {
      defaultWarehouseName.value = response.data.warehouse.name;
    }

    if (response.data.pagination) {
      stockPagination.value = response.data.pagination;
    }

    stockReports.value = stocks.map(stock => {
      const categoryPairs = stock.product?.category_pairs || [];
      return {
        code: stock.product?.sku || 'N/A',
        value: stock.quantity,
        unit: stock.product?.title || stock.product?.name || uiText("Tidak Diketahui"),
        categories: categoryPairs.map(p => p.category_name).filter(Boolean),
        subcategories: categoryPairs.map(p => p.sub_category_name).filter(Boolean),
        date: stock.last_updated,
      };
    });
  } catch (error) {
    console.error('Gagal memuat laporan stok:', error);
  }
};

const changeStockPage = (page) => {
  stockCurrentPage.value = page;
  loadStockReports(page);
};
</script>
