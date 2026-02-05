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
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">📦</span>
          <span class="text-xs text-gray-400 group-hover:text-blue-500 transition-colors">{{ $t('dashboard.totalProducts') }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.totalProducts || 0 }}</p>
      </div>

      <div class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">💰</span>
          <span class="text-xs text-gray-400 group-hover:text-green-500 transition-colors">{{ $t('dashboard.totalSales') }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.totalSales || 0 }}</p>
      </div>

      <div class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">🛒</span>
          <span class="text-xs text-gray-400 group-hover:text-purple-500 transition-colors">{{ $t('dashboard.totalPurchases') }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.totalPurchases || 0 }}</p>
      </div>

      <div class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">📋</span>
          <span class="text-xs text-gray-400 group-hover:text-orange-500 transition-colors">Pending</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.pendingSales || 0 }}</p>
      </div>

      <div class="group bg-white rounded-2xl p-5 border border-gray-100 hover:border-gray-200 hover:shadow-sm transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <span class="text-2xl">⚠️</span>
          <span class="text-xs text-gray-400 group-hover:text-red-500 transition-colors">{{ $t('dashboard.lowStock') }}</span>
        </div>
        <p class="text-2xl font-semibold text-gray-800">{{ stats.lowStockItems || 0 }}</p>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Chart - Takes 2 columns -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-lg font-medium text-gray-800">Top Products</h2>
            <p class="text-sm text-gray-400">Units sold this period</p>
          </div>
          <select v-model="selectedCategory" @change="loadDashboardData"
            class="text-sm px-3 py-2 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-gray-400 transition-colors">
            <option :value="null">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        <VueApexCharts v-if="stockChartOptions && stockChartSeries.length" type="bar" height="280"
          :options="stockChartOptions" :series="stockChartSeries" />
        <div v-else class="h-64 flex items-center justify-center text-gray-400">
          <p>{{ $t('common.noData') }}</p>
        </div>
      </div>

      <!-- Stock Status Cards -->
      <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <h2 class="text-lg font-medium text-gray-800 mb-6">{{ $t('dashboard.stockStatus') }}</h2>
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
      </div>
    </div>

    <!-- Recent Stock Report -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-lg font-medium text-gray-800">{{ $t('dashboard.stockReport') }}</h2>
          <p class="text-sm text-gray-400">{{ $t('dashboard.topProducts') }}</p>
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
                  <span v-for="(cat, i) in item.categories" :key="i" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">{{ cat }}</span>
                </div>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="py-4 text-sm text-gray-600">
                <div v-if="item.subcategories && item.subcategories.length" class="flex flex-wrap gap-1">
                  <span v-for="(sub, i) in item.subcategories" :key="i" class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs">{{ sub }}</span>
                </div>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="py-4 text-right">
                <span class="text-sm font-semibold text-gray-800">{{ item.value }}</span>
              </td>
              <td class="py-4 text-right text-sm text-gray-400">{{ item.date }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!stockReports.length" class="py-8 text-center text-gray-400">
          {{ $t('dashboard.noStockData') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';
import VueApexCharts from 'vue3-apexcharts';

const stats = ref({});
const stockChartOptions = ref(null);
const stockChartSeries = ref([]);
const stockReports = ref([]);
const categories = ref([]);
const selectedCategory = ref(null);

const stockStatus = ref({
  needed: 0,
  out: 0,
  restock: 0,
  ready: 0,
});

const currentDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

onMounted(async () => {
  try {
    await loadCategories();
    await loadDashboardData();
  } catch (error) {
    console.error('Dashboard: Failed to load', error);
  }
});

const loadCategories = async () => {
  try {
    const response = await api.get('/categories');
    categories.value = response.data;
  } catch (error) {
    console.error('Failed to load categories:', error);
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
          columnWidth: '50%',
          borderRadius: 8,
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
        axisTicks: { show: false },
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
          formatter: (val) => val + ' units',
        },
      },
      legend: { show: false },
    };

    stockChartSeries.value = stockResponse.data.series || [];
    await loadStockStatus();
    await loadStockReports();
  } catch (error) {
    console.error('Failed to load dashboard data:', error);
  }
};

const loadStockStatus = async () => {
  try {
    const response = await api.get('/stock');
    const stocks = response.data.data || [];
    
    stockStatus.value = {
      ready: stocks.filter(s => s.quantity > 10).length,
      out: stocks.filter(s => s.quantity === 0).length,
      restock: stocks.filter(s => s.quantity > 0 && s.quantity <= 5).length,
      needed: stocks.filter(s => s.quantity > 5 && s.quantity <= 10).length,
    };
  } catch (error) {
    console.error('Failed to load stock status:', error);
  }
};

const loadStockReports = async () => {
  try {
    const response = await api.get('/stock');
    const stocks = response.data.data || [];
    
    stockReports.value = stocks
      .sort((a, b) => b.quantity - a.quantity)
      .slice(0, 5)
      .map(stock => {
        // Get all category pairs
        const categoryPairs = stock.product?.category_pairs || [];
        return {
          code: stock.product?.sku || 'N/A',
          value: stock.quantity,
          unit: stock.product?.title || stock.product?.name || 'Unknown',
          categories: categoryPairs.map(p => p.category_name).filter(Boolean),
          subcategories: categoryPairs.map(p => p.sub_category_name).filter(Boolean),
          date: new Date(stock.last_updated).toLocaleDateString('id-ID')
        };
      });
  } catch (error) {
    console.error('Failed to load stock reports:', error);
  }
};
</script>
