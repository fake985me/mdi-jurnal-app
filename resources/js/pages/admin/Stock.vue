<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">{{ $t('pageTitles.Stock') }}</h2>
      <button
        v-if="activeTab === 'regular'"
        @click="showTransactionModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        {{ $t('ui.recordTransaction') }}
      </button>
    </div>

    <!-- Summary Cards (only for Regular Stock tab) -->
    <div v-if="activeTab === 'regular'" class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-indigo-500">
        <div class="text-sm text-gray-500">{{ $t('ui.totalProducts') }}</div>
        <div class="text-2xl font-bold text-indigo-600">{{ stocks.total || 0 }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
        <div class="text-sm text-gray-500">{{ $t('dashboard.lowStock') }}</div>
        <div class="text-2xl font-bold text-red-600">{{ stockSummary.lowStock }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-gray-500">
        <div class="text-sm text-gray-500">{{ $t('dashboard.outOfStock') }}</div>
        <div class="text-2xl font-bold text-gray-600">{{ stockSummary.outOfStock }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
        <div class="text-sm text-gray-500">{{ $t('ui.healthyStock') }}</div>
        <div class="text-2xl font-bold text-green-600">{{ stockSummary.healthy }}</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-md">
      <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
          <button
            @click="activeTab = 'regular'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'regular'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ $t('ui.regularStock') }}
          </button>
          <button
            @click="activeTab = 'rma'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'rma'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ $t('ui.rmaInventory') }}
          </button>
          <button
            @click="activeTab = 'investment'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'investment'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ $t('ui.investmentStock') }}
          </button>
          <button
            @click="activeTab = 'msa'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'msa'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ $t('ui.msaProject') }}
          </button>
          <button
            @click="activeTab = 'defective'"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeTab === 'defective'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ $t('ui.defectiveItems') }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Regular Stock Tab -->
    <div v-show="activeTab === 'regular'" class="space-y-4">
      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <input
            v-model="filters.search"
            @input="debouncedLoadStocks"
            type="text"
            :placeholder="$t('ui.searchByProductNameSkuOrBrand')"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
          <select
            v-model="filters.warehouse_id"
            @change="loadStocks()"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">{{ $t('ui.allWarehouses') }}</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }} {{ wh.is_default ? $t('ui.default2') : '' }}
            </option>
          </select>
          <div class="flex items-center">
            <input
              id="low-stock"
              v-model="filters.low_stock"
              @change="loadStocks()"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="low-stock" class="ml-2 text-sm text-gray-700">
              {{ $t('ui.showOnlyLowStockItems') }}
            </label>
          </div>
          <div class="flex items-center">
            <input
              id="out-of-stock"
              v-model="filters.out_of_stock"
              @change="loadStocks()"
              type="checkbox"
              class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
            />
            <label for="out-of-stock" class="ml-2 text-sm text-gray-700">
              {{ $t('ui.showOnlyOutOfStock') }}
            </label>
          </div>
        </div>
      </div>

    <!-- Current Stock Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.currentStockLevels') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent"></div>
        <p class="text-gray-600 mt-2">{{ $t('ui.loadingStockData') }}</p>
      </div>

      <div v-else-if="!stocks.data?.length" class="p-8 text-center">
        <p class="text-gray-500 text-lg">{{ $t('ui.noStockDataFound') }}</p>
        <p class="text-gray-400 text-sm mt-1">{{ $t('ui.tryAdjustingYourFiltersOrAddProductsFirst') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.product') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('dashboard.sku') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('ui.categorySubCategory') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('nav.warehouses') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ filters.warehouse_id ? $t('ui.currentStock') : $t('ui.totalStock2') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('ui.minStock') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('ui.lastUpdated') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="stock in stocks.data" :key="stock.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ stock.product?.title || stock.product?.name }}</div>
                <div class="text-xs text-gray-500">{{ stock.product?.brand }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ stock.product?.sku || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-wrap gap-1">
                  <template v-if="stock.product?.category_pairs?.length > 0">
                    <span v-for="(pair, idx) in stock.product.category_pairs" :key="idx"
                      class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">
                      {{ pair.category_name }}
                      <span v-if="pair.sub_category_name" class="ml-1 text-indigo-600">
                        / {{ pair.sub_category_name }}
                      </span>
                    </span>
                  </template>
                  <span v-else class="text-gray-400 text-sm">-</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ filters.warehouse_id ? (stock.warehouse?.name || $t('ui.default')) : $t('ui.allWarehouses') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-lg font-bold" :class="getStockColor(stock)">
                  {{ stock.quantity }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ getMinStock(stock) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 py-1 text-xs rounded-full',
                  getStatusBadge(stock)
                ]">
                  {{ getStatusLabel(stock) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(stock.last_updated || stock.updated_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="stocks.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center">
        <p class="text-sm text-gray-700">
          {{ $t('sales.showing') }} {{ stocks.from }} {{ $t('ui.to') }} {{ stocks.to }} {{ $t('sales.of') }} {{ stocks.total }} {{ $t('ui.items2') }}
        </p>
        <div class="flex space-x-2">
          <button
            @click="loadStocks(stocks.current_page - 1)"
            :disabled="!stocks.prev_page_url"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50"
          >
            {{ $t('sales.previous') }}
          </button>
          <button
            @click="loadStocks(stocks.current_page + 1)"
            :disabled="!stocks.next_page_url"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50"
          >
            {{ $t('sales.next') }}
          </button>
        </div>
      </div>
    </div>
    </div>

    <!-- RMA Inventory Tab -->
    <div v-show="activeTab === 'rma'">
    <!-- RMA Inventory Section -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-red-50">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.rmaInventoryReturnedItems') }}</h3>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.usableReturnedItemsWithABookValueOfRp0KeptSeparateFromRegular') }}</p>
      </div>

      <div v-if="loadingRMA" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-500 border-t-transparent"></div>
        <p class="text-gray-600 mt-2">{{ $t('ui.loadingRmaInventory') }}</p>
      </div>

      <div v-else-if="!rmaStock.length" class="p-8 text-center">
        <p class="text-gray-500">{{ $t('ui.noRmaItemsInStock') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.product') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('ui.condition') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.quantity') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="rma in rmaStock" :key="rma.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ rma.product?.title }}</div>
                <div class="text-xs text-gray-500">{{ rma.product?.brand }}</div>
                <div class="text-xs font-mono text-gray-500">{{ rma.serial_number || $t('ui.noSerialNumber') }}</div>
                <div class="text-xs text-gray-500">{{ $t('ui.bookValueRp0') }} {{ rma.notes }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getConditionBadge(rma.condition)" class="px-2 py-1 text-xs rounded-full capitalize">
                  {{ $label(rma.condition) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-lg font-bold text-gray-900">{{ rma.total_quantity }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <router-link to="/dashboard/rmas" class="text-indigo-600 hover:text-indigo-900">
                  {{ $t('ui.viewRmas') }}
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </div>

    <!-- Investment Stock Tab -->
    <div v-show="activeTab === 'investment'">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.investmentStockProjectAllocations') }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ $t('ui.itemsAllocatedToActiveProjects') }}</p>
        </div>

        <div v-if="loadingInvestment" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-purple-500 border-t-transparent"></div>
          <p class="text-gray-600 mt-2">{{ $t('ui.loadingInvestmentStock') }}</p>
        </div>

        <div v-else-if="!investmentStock.length" class="p-8 text-center">
          <p class="text-gray-500">{{ $t('ui.noItemsAllocatedToProjects') }}</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.product') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('labels.project') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.quantity') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('projectInvestment.totalValue') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in investmentStock" :key="`${item.product_id}-${item.project_investment_id}`" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ item.product?.title }}</div>
                  <div class="text-xs text-gray-500">{{ item.product?.brand }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ item.project?.project_name }}</div>
                  <div class="text-xs text-gray-500">{{ item.project?.project_code }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-lg font-bold text-purple-600">{{ item.total_quantity }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-semibold text-green-600">{{ formatCurrency(item.total_value) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <router-link to="/dashboard/project-investments" class="text-indigo-600 hover:text-indigo-900">
                    {{ $t('ui.viewProjects') }}
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MSA Project Tab -->
    <div v-show="activeTab === 'msa'">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-red-50">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.msaProjectItemsInRepair') }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ $t('ui.itemsFromProjectsCurrentlyBeingRepairedOrReplaced') }}</p>
        </div>

        <div v-if="loadingMSA" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-500 border-t-transparent"></div>
          <p class="text-gray-600 mt-2">{{ $t('ui.loadingMsaItems') }}</p>
        </div>

        <div v-else-if="!msaStock?.length" class="p-8 text-center">
          <p class="text-gray-500">{{ $t('ui.noMsaItemsInRepair') }}</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.msaCode') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('labels.project') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.issue') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.quantity') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in msaStock" :key="item.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.msa_code }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ item.product?.title }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ item.project?.project_name || '-' }}</td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">{{ item.issue_type }}</span>
                </td>
                <td class="px-6 py-4 text-sm font-bold">{{ item.quantity }}</td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $label(item.status) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Defective Items Tab -->
    <div v-show="activeTab === 'defective'">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.defectiveReturnedItems') }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ $t('ui.combinedDefectiveItemsFromRmaMsaAndProjects') }}</p>
        </div>

        <div v-if="loadingDefective" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-red-500 border-t-transparent"></div>
          <p class="text-gray-600 mt-2">{{ $t('ui.loadingDefectiveItems') }}</p>
        </div>

        <div v-else-if="!defectiveStock?.length" class="p-8 text-center">
          <p class="text-gray-500">{{ $t('ui.noDefectiveItems') }}</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.source') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.code') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.quantity') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.condition') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(item, index) in defectiveStock" :key="index" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <span :class="item.source === 'RMA' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'" class="px-2 py-1 text-xs rounded-full">
                    {{ item.source }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.code }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ item.product?.title }}<p class="text-xs font-mono text-gray-500">{{ item.serial_number || $t('ui.noSerialNumber') }}</p></td>
                <td class="px-6 py-4 text-sm font-bold text-red-600">{{ item.quantity }}</td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">{{ $label(item.condition) }}</span>
                  <p class="mt-1 text-xs text-gray-500">{{ item.notes }}</p>
                </td>
                <td class="px-6 py-4 text-right"><button type="button" @click="openReturnInspection(item)" class="text-indigo-600 hover:underline">{{ $t('ui.repairResults') }}</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="returnInspection" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <form @submit.prevent="saveReturnInspection" class="w-full max-w-lg rounded-xl bg-white p-6 space-y-4">
        <h3 class="text-lg font-bold">{{ $t('ui.returnedItemFollowUp') }}</h3>
        <p>{{ returnInspection.product?.title }} · {{ returnInspection.serial_number || returnInspection.code }}</p>
        <div><label class="block mb-1 text-sm">{{ $t('ui.repairResults') }}</label><select v-model="returnResult.condition" class="input" required><option value="unrepairable">{{ $t('ui.cannotBeRepairedRemainsADamagedItem') }}</option><option value="limited">{{ $t('ui.usableWithLimitationsRmaStock') }}</option><option value="working">{{ $t('ui.normalRmaStock') }}</option></select></div>
        <div><label class="block mb-1 text-sm">{{ $t('ui.notesUsageLimitations') }}</label><textarea v-model="returnResult.notes" class="input" rows="3" required maxlength="5000"></textarea></div>
        <p class="text-xs text-gray-500">{{ $t('ui.rmaStockHasABookValueOfRp0AndIsKeptSeparateFrom') }}</p>
        <p v-if="returnError" class="text-red-700 text-sm">{{ returnError }}</p>
        <div class="flex justify-end gap-2"><button type="button" @click="returnInspection = null" class="btn-secondary">{{ $t('common.cancel') }}</button><button :disabled="returnSaving" class="btn-primary">{{ returnSaving ? $t('sales.saving') : $t('common.save') }}</button></div>
      </form>
    </div>
    <!-- Transaction Modal -->
    <div v-if="showTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ $t('ui.recordStockTransaction') }}</h3>
        
        <form @submit.prevent="saveTransaction" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.product2') }}</label>
            <select
              v-model="transactionForm.product_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">{{ $t('sales.selectProduct') }}</option>
              <option v-for="stock in allStocks" :key="stock.product?.id" :value="stock.product?.id">
                {{ stock.product?.title || stock.product?.name }} {{ $t('ui.current') }} {{ stock.quantity }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.transactionType') }}</label>
            <select
              v-model="transactionForm.transaction_type"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">{{ $t('ui.selectType') }}</option>
              <option value="in">{{ $t('ui.stockIn2') }}</option>
              <option value="out">{{ $t('ui.stockOut2') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantity') }}</label>
            <input
              v-model="transactionForm.quantity"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea
              v-model="transactionForm.notes"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ $ui(error) }}
          </div>

          <div v-if="success" class="bg-green-50 text-green-600 p-3 rounded-lg text-sm">
            {{ $ui(success) }}
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showTransactionModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ saving ? $t('sales.saving') : $t('ui.saveTransaction') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';

const stocks = ref({ data: [] });
const allStocks = ref([]);
const rmaStock = ref([]);
const investmentStock = ref([]);
const msaStock = ref([]);
const defectiveStock = ref([]);
const returnInspection = ref(null);
const returnResult = ref({ condition: 'unrepairable', notes: '' });
const returnSaving = ref(false);
const returnError = ref('');
const openReturnInspection = item => {
  returnInspection.value = item;
  returnResult.value = { condition: 'unrepairable', notes: item.notes || '' };
  returnError.value = '';
};
const saveReturnInspection = async () => {
  returnSaving.value = true;
  try {
    await api.post(`/returned-items/${returnInspection.value.id}/inspect`, returnResult.value);
    returnInspection.value = null;
    await Promise.all([loadDefectiveStock(), loadRMAStock()]);
  } catch (err) { returnError.value = err.response?.data?.message || uiText("Gagal menyimpan hasil perbaikan."); }
  finally { returnSaving.value = false; }
};
const warehouses = ref([]);
const activeTab = ref('regular');
const loading = ref(true);
const loadingRMA = ref(true);
const loadingInvestment = ref(true);
const loadingMSA = ref(true);
const loadingDefective = ref(true);
const showTransactionModal = ref(false);
const saving = ref(false);
const error = ref('');
const success = ref('');

const filters = ref({
  search: '',
  low_stock: false,
  out_of_stock: false,
  warehouse_id: '',
});

const transactionForm = ref({
  product_id: '',
  transaction_type: '',
  quantity: 1,
  notes: '',
});

// Debounce timer
let searchTimer = null;
const debouncedLoadStocks = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadStocks(), 300);
};

// Stock summary computed from allStocks
const stockSummary = computed(() => {
  const all = allStocks.value || [];
  const outOfStock = all.filter(s => s.quantity <= 0).length;
  const lowStock = all.filter(s => {
    const minStock = s.product?.minimum_stock || 10;
    return s.quantity > 0 && s.quantity <= minStock;
  }).length;
  const healthy = all.filter(s => {
    const minStock = s.product?.minimum_stock || 10;
    return s.quantity > minStock;
  }).length;
  return { outOfStock, lowStock, healthy };
});

// Helper to get minimum stock for a stock item
const getMinStock = (stock) => {
  return stock.product?.minimum_stock || 10;
};

// Helper to determine stock color
const getStockColor = (stock) => {
  const minStock = getMinStock(stock);
  if (stock.quantity <= 0) return 'text-gray-600';
  if (stock.quantity <= minStock) return 'text-red-600';
  return 'text-green-600';
};

// Helper to get status badge classes
const getStatusBadge = (stock) => {
  const minStock = getMinStock(stock);
  if (stock.quantity <= 0) return 'bg-gray-100 text-gray-800';
  if (stock.quantity <= minStock) return 'bg-red-100 text-red-800';
  if (stock.quantity <= minStock * 2) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
};

// Helper to get status label
const getStatusLabel = (stock) => {
  const minStock = getMinStock(stock);
  if (stock.quantity <= 0) return uiText("Habis");
  if (stock.quantity <= minStock) return uiText("Stok Rendah");
  if (stock.quantity <= minStock * 2) return uiText("Sedang");
  return uiText("Baik");
};

// Format date safely
const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    return date.toLocaleDateString();
  } catch {
    return '-';
  }
};

const loadStocks = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page, ...filters.value };
    const response = await api.get('/stock', { params });
    stocks.value = response.data;
  } catch (err) {
    console.error('Gagal memuat stok:', err);
  } finally {
    loading.value = false;
  }
};

const loadRMAStock = async () => {
  loadingRMA.value = true;
  try {
    const response = await api.get('/stock/rma-inventory');
    rmaStock.value = response.data;
  } catch (err) {
    console.error('Failed to load RMA stock:', err);
  } finally {
    loadingRMA.value = false;
  }
};

const loadInvestmentStock = async () => {
  loadingInvestment.value = true;
  try {
    const response = await api.get('/stock/investment-inventory');
    investmentStock.value = response.data;
  } catch (err) {
    console.error('Gagal memuat stok investasi:', err);
  } finally {
    loadingInvestment.value = false;
  }
};

const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString(uiLocale());
};

const loadMSAStock = async () => {
  loadingMSA.value = true;
  try {
    const response = await api.get('/stock/msa-inventory');
    msaStock.value = response.data;
  } catch (err) {
    console.error('Failed to load MSA stock:', err);
  } finally {
    loadingMSA.value = false;
  }
};

const loadDefectiveStock = async () => {
  loadingDefective.value = true;
  try {
    const response = await api.get('/stock/defective');
    defectiveStock.value = response.data;
  } catch (err) {
    console.error('Gagal memuat stok barang rusak:', err);
  } finally {
    loadingDefective.value = false;
  }
};

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses');
    warehouses.value = response.data.data || response.data;
  } catch (err) {
    console.error('Gagal memuat gudang:', err);
  }
};

const getConditionBadge = (condition) => {
  const badges = {
    working: 'bg-green-100 text-green-800',
    damaged: 'bg-yellow-100 text-yellow-800',
    broken: 'bg-red-100 text-red-800',
    parts_only: 'bg-gray-100 text-gray-800',
  };
  return badges[condition] || 'bg-blue-100 text-blue-800';
};

const loadAllStocks = async () => {
  try {
    const response = await api.get('/stock', { params: { per_page: 1000 } });
    allStocks.value = response.data.data;
  } catch (err) {
    console.error('Gagal memuat semua stok:', err);
  }
};

const saveTransaction = async () => {
  saving.value = true;
  error.value = '';
  success.value = '';
  
  try {
    await api.post('/stock/transaction', transactionForm.value);
    success.value = uiText("Transaksi berhasil dicatat!");
    transactionForm.value = { product_id: '', transaction_type: '', quantity: 1, notes: '' };
    loadStocks();
    loadAllStocks();
    setTimeout(() => {
      showTransactionModal.value = false;
      success.value = '';
    }, 1500);
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal mencatat transaksi");
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadStocks();
  loadAllStocks();
  loadWarehouses();
  loadRMAStock();
  loadInvestmentStock();
  loadMSAStock();
  loadDefectiveStock();
});
</script>
