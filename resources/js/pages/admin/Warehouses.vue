<template>
  <div class="space-y-6" :class="{ 'form-page': formPage }">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-linear-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
          {{ $t('pageTitles.Warehouses') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.manageYourWarehousesAndStorageLocations') }}</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="openAddProductModal()"
          class="px-6 py-3 bg-linear-to-r from-orange-500 to-amber-500 text-white rounded-lg hover:from-orange-600 hover:to-amber-600 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
          {{ $t('ui.addProductToWarehouse') }}
        </button>
        <button
          @click="openWarehouseForm()"
          class="px-6 py-3 bg-linear-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
          {{ $t('ui.newWarehouse') }}
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card p-4 bg-linear-to-br from-emerald-50 to-teal-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ $t('ui.totalWarehouses') }}</p>
            <p class="text-2xl font-bold text-emerald-600">{{ stats.total_warehouses || 0 }}</p>
          </div>
          <span class="text-3xl">🏭</span>
        </div>
      </div>
      <div class="card p-4 bg-linear-to-br from-blue-50 to-indigo-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ $t('ui.activeWarehouses') }}</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.active_warehouses || 0 }}</p>
          </div>
          <span class="text-3xl">✅</span>
        </div>
      </div>
      <div class="card p-4 bg-linear-to-br from-purple-50 to-pink-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ $t('ui.totalLocations') }}</p>
            <p class="text-2xl font-bold text-purple-600">{{ stats.total_locations || 0 }}</p>
          </div>
          <span class="text-3xl">📍</span>
        </div>
      </div>
      <div class="card p-4 bg-linear-to-br from-orange-50 to-amber-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ $t('ui.activeLocations') }}</p>
            <p class="text-2xl font-bold text-orange-600">{{ stats.active_locations || 0 }}</p>
          </div>
          <span class="text-3xl">📦</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="filters.search"
          @input="loadWarehouses"
          type="text"
          :placeholder="$t('ui.searchWarehouses')"
          class="input" />
        <select v-model="filters.is_active" @change="loadWarehouses" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="1">{{ $t('labels.active') }}</option>
          <option value="0">{{ $t('labels.inactive') }}</option>
        </select>
      </div>
    </div>

    <!-- Warehouse Tabs Navigation -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="border-b border-gray-200">
        <nav class="flex overflow-x-auto" :aria-label="$t('ui.warehouseTabs')">
          <button
            v-for="warehouse in allWarehouses"
            :key="warehouse.id"
            @click="selectWarehouseTab(warehouse)"
            :class="[
              'px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 flex items-center gap-2',
              activeWarehouseId === warehouse.id
                ? 'border-emerald-500 text-emerald-600 bg-emerald-50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50'
            ]">
            <span>{{ warehouse.name }}</span>
            <span v-if="warehouse.is_default" class="px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-700">{{ $t('ui.default') }}</span>
            <span v-if="warehouse.project_investment" class="px-1.5 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700">{{ $t('labels.project') }}</span>
            <span class="px-1.5 py-0.5 text-xs rounded bg-gray-100 text-gray-600">{{ warehouse.stocks_sum_quantity || 0 }}</span>
          </button>
        </nav>
      </div>

      <!-- Selected Warehouse Content -->
      <div v-if="selectedWarehouse" class="p-6">
        <!-- Warehouse Header -->
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">{{ selectedWarehouse.name }}</h3>
            <p class="text-sm text-gray-500">{{ selectedWarehouse.code }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span :class="selectedWarehouse.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              class="px-3 py-1 text-sm rounded-full">
              {{ selectedWarehouse.is_active ? $t('labels.active') : $t('labels.inactive') }}
            </span>
            <button @click="editWarehouse(selectedWarehouse)" class="text-blue-600 hover:text-blue-900 text-sm">{{ $t('common.edit') }}</button>
            <button v-if="!selectedWarehouse.is_default" @click="setDefault(selectedWarehouse)" class="text-yellow-600 hover:text-yellow-900 text-sm">{{ $t('ui.setAsDefault') }}</button>
            <button @click="deleteWarehouse(selectedWarehouse)" class="text-red-600 hover:text-red-900 text-sm">{{ $t('common.delete') }}</button>
          </div>
        </div>

        <!-- Warehouse Info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
          <div><span class="text-gray-500">{{ $t('ui.address') }}</span> {{ selectedWarehouse.address || '-' }}</div>
          <div><span class="text-gray-500">{{ $t('ui.phone') }}</span> {{ selectedWarehouse.phone || '-' }}</div>
          <div><span class="text-gray-500">{{ $t('ui.email2') }}</span> {{ selectedWarehouse.email || '-' }}</div>
          <div><span class="text-gray-500">{{ $t('ui.totalStock') }}</span> <strong class="text-emerald-600">{{ selectedWarehouse.stocks_sum_quantity || 0 }}</strong></div>
        </div>

        <!-- Project & Contract Section -->
        <div v-if="selectedWarehouse.project_investment" class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="font-semibold text-indigo-900">{{ $t('labels.invest') }}</h4>
              <p class="text-sm text-indigo-700">
                {{ selectedWarehouse.project_investment.project_name }} ({{ selectedWarehouse.project_investment.project_code }})
              </p>
              <p class="text-xs text-indigo-600">
                {{ $t('ui.type3') }} {{ $label(selectedWarehouse.project_investment.type) }} |
                Status: {{ $label(selectedWarehouse.project_investment.status) }}
              </p>
            </div>
            <span class="px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700">{{ $t('ui.projectWarehouse') }}</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3 text-sm">
            <div><span class="text-gray-500">{{ $t('ui.client2') }}</span> {{ selectedWarehouse.project_investment.client_name || '-' }}</div>
            <div><span class="text-gray-500">{{ $t('ui.start') }}</span> {{ formatDate(selectedWarehouse.project_investment.start_date) }}</div>
            <div><span class="text-gray-500">{{ $t('ui.expectedEnd') }}</span> {{ formatDate(selectedWarehouse.project_investment.expected_end_date) }}</div>
          </div>

          <div v-if="selectedContract" class="mt-4 p-3 bg-white rounded-lg border border-indigo-100">
            <div class="flex justify-between items-center mb-2">
              <h5 class="font-semibold text-gray-800">{{ $t('ui.contract') }}</h5>
              <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ $label(selectedContract.status) }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
              <div><span class="text-gray-500">{{ $t('ui.contractNo') }}</span> {{ selectedContract.contract_number || '-' }}</div>
              <div><span class="text-gray-500">{{ $t('ui.contractDate') }}</span> {{ formatDate(selectedContract.contract_date) }}</div>
              <div><span class="text-gray-500">{{ $t('ui.value2') }}</span> {{ formatCurrency(selectedContract.contract_value || 0) }}</div>
            </div>
          </div>
        </div>

        <!-- Contract Payments -->
        <div v-if="selectedContract" class="border-t pt-4 mt-4">
          <div class="flex justify-between items-center mb-2">
            <h4 class="font-semibold text-gray-800">{{ $t('ui.contractPayments') }}</h4>
            <button @click="togglePaymentForm" class="text-sm text-indigo-600 hover:text-indigo-800">
              {{ showPaymentForm ? $t('ui.hideForm') : $t('ui.addPayment') }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm mb-4">
            <div class="p-3 bg-gray-50 rounded">
              <div class="text-gray-500">{{ $t('ui.contractTotal') }}</div>
              <div class="font-semibold text-gray-800">{{ formatCurrency(contractTotal) }}</div>
            </div>
            <div class="p-3 bg-gray-50 rounded">
              <div class="text-gray-500">{{ $t('labels.paid') }}</div>
              <div class="font-semibold text-emerald-600">{{ formatCurrency(totalPaid) }}</div>
            </div>
            <div class="p-3 bg-gray-50 rounded">
              <div class="text-gray-500">{{ $t('ui.remaining') }}</div>
              <div class="font-semibold text-orange-600">{{ formatCurrency(remainingAmount) }}</div>
            </div>
          </div>

          <div v-if="showPaymentForm" class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-semibold text-gray-800">{{ editingPaymentId ? $t('ui.editPayment') : $t('ui.addPayment2') }}</h4>
              <button v-if="editingPaymentId" @click="resetPaymentForm" type="button" class="text-sm text-gray-500 hover:text-gray-700">
                {{ $t('ui.cancelEdit') }}
              </button>
            </div>
            <form @submit.prevent="submitPayment" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.type2') }}</label>
                <select v-model="paymentForm.payment_type" required class="input">
                  <option value="dp">DP</option>
                  <option value="termin">{{ $t('labels.installment') }}</option>
                  <option value="full">{{ $t('labels.full') }}</option>
                  <option value="sharing_profit">{{ $t('ui.sharingProfit') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.amount2') }}</label>
                <input v-model.number="paymentForm.amount" type="number" min="0.01" step="0.01" required class="input" />
                <p v-if="paymentForm.amount && contractTotal > 0 && paymentForm.amount > remainingAvailable"
                  class="text-xs text-red-600 mt-1">
                  {{ $t('ui.amountExceedsRemainingBalance') }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.paymentDate') }}</label>
                <input v-model="paymentForm.payment_date" type="date" class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.method') }}</label>
                <input v-model="paymentForm.method" class="input" :placeholder="$t('ui.transferCash')" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.referenceNo') }}</label>
                <input v-model="paymentForm.reference_number" class="input" :placeholder="$t('ui.optional')" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.materials.status') }}</label>
                <select v-model="paymentForm.status" class="input">
                  <option value="paid">{{ $t('labels.paid') }}</option>
                  <option value="pending">{{ $t('labels.pending') }}</option>
                  <option value="cancelled">{{ $t('labels.cancelled') }}</option>
                </select>
              </div>
              <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
                <textarea v-model="paymentForm.notes" rows="2" class="input" :placeholder="$t('ui.optionalNotes')"></textarea>
              </div>

              <div v-if="paymentError" class="md:col-span-3 bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ paymentError }}</div>

              <div class="md:col-span-3 flex justify-end gap-3">
                <button type="button" @click="togglePaymentForm" class="btn-secondary">{{ $t('projectInvestment.actions.cancel') }}</button>
                <button type="submit" :disabled="paymentSaving" class="btn-primary">
                  {{ paymentSaving ? $t('sales.saving') : $t('ui.savePayment') }}
                </button>
              </div>
            </form>
          </div>

          <div v-if="paymentsLoading" class="text-center py-4 text-gray-500">{{ $t('ui.loadingPayments') }}</div>
          <table v-else-if="contractPayments.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">{{ $t('sales.date') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.type') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.method') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.reference') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('ui.amount') }}</th>
              <th class="px-4 py-2 text-right">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-4 py-2 text-right">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="payment in contractPayments" :key="payment.id">
                <td class="px-4 py-2">{{ formatDate(payment.payment_date) }}</td>
                <td class="px-4 py-2 capitalize">{{ $label(payment.payment_type) }}</td>
                <td class="px-4 py-2">{{ payment.method || '-' }}</td>
                <td class="px-4 py-2">{{ payment.reference_number || '-' }}</td>
                <td class="px-4 py-2 text-right font-semibold">{{ formatCurrency(payment.amount || 0) }}</td>
              <td class="px-4 py-2 text-right">
                <span :class="payment.status === 'paid' ? 'text-emerald-600' : payment.status === 'pending' ? 'text-yellow-600' : 'text-gray-400'">
                  {{ $label(payment.status) }}
                </span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <button @click="editPayment(payment)" class="text-blue-600 hover:text-blue-900">{{ $t('common.edit') }}</button>
                <button @click="deletePayment(payment)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
          <p v-else class="text-center text-gray-500 py-4">{{ $t('ui.noPaymentsRecorded') }}</p>
        </div>

        <!-- Product Stocks Section -->
        <div class="border-t pt-4 mt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">{{ $t('ui.productStocks') }}{{ warehouseStocks.length }})</h4>
            <button v-if="selectedWarehouse.is_default" @click="openAddProductModal()" 
              class="text-sm text-orange-600 hover:text-orange-800">{{ $t('ui.addProduct2') }}</button>
          </div>

          <div v-if="loadingStocks" class="text-center py-4 text-gray-500">{{ $t('ui.loadingStocks') }}</div>
          
          <table v-else-if="warehouseStocks.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">{{ $t('dashboard.sku') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('sales.product') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.brand') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('projectInvestment.projectLocation') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.quantity') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('ui.lastUpdated') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="stock in warehouseStocks" :key="stock.id">
                <td class="px-4 py-2 font-mono text-sm">{{ stock.product?.sku || '-' }}</td>
                <td class="px-4 py-2 font-medium">{{ stock.product?.title || '-' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ stock.product?.brand || '-' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ stock.location?.name || $t('ui.noLocation') }}</td>
                <td class="px-4 py-2 text-right">
                  <span class="font-bold text-emerald-600">{{ stock.quantity }}</span>
                </td>
                <td class="px-4 py-2 text-right text-gray-500 text-xs">
                  {{ stock.last_updated ? new Date(stock.last_updated).toLocaleDateString() : '-' }}
                </td>
              <td class="px-4 py-2 text-right text-gray-500 text-xs">
                <button
                  :disabled="!stock.product"
                  @click="editProduct(stock.product)"
                  class="text-indigo-600 hover:text-indigo-900 disabled:text-gray-400 disabled:cursor-not-allowed">
                  {{ $t('common.edit') }}
                </button>
              </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">{{ $t('ui.noProductsInThisWarehouse') }}</p>
        </div>

        <!-- Locations Section -->
        <div class="border-t pt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">{{ $t('ui.locations') }}{{ selectedWarehouse.locations?.length || 0 }})</h4>
            <button @click="openLocationForm()" class="text-sm text-emerald-600 hover:text-emerald-800">{{ $t('ui.addLocation') }}</button>
          </div>

          <table v-if="selectedWarehouse.locations?.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">{{ $t('ui.code') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.name') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.zoneAisleRack') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('ui.capacity') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.stock') }}</th>
                <th class="px-4 py-2 text-center">{{ $t('projectInvestment.materials.status') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="loc in selectedWarehouse.locations" :key="loc.id">
                <td class="px-4 py-2 font-medium">{{ loc.code }}</td>
                <td class="px-4 py-2">{{ loc.name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ [loc.zone, loc.aisle, loc.rack, loc.shelf].filter(Boolean).join('-') || '-' }}</td>
                <td class="px-4 py-2 text-right">{{ loc.capacity || '-' }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ loc.stocks_sum_quantity || 0 }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="loc.is_active ? 'text-green-600' : 'text-gray-400'">{{ loc.is_active ? '●' : '○' }}</span>
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <button @click="editLocation(loc)" class="text-blue-600 hover:text-blue-900">{{ $t('common.edit') }}</button>
                  <button @click="deleteLocation(loc)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">{{ $t('ui.noLocationsDefined') }}</p>
        </div>

      </div>

      <div v-else class="p-8 text-center text-gray-500">
        <p v-if="loading">{{ $t('ui.loadingWarehouses') }}</p>
        <p v-else>{{ $t('ui.selectAWarehouseTabToViewDetails') }}</p>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="tab-form" :class="formPage ? 'mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'">
      <div class="bg-white rounded-xl p-6 w-full overflow-y-auto shadow-md" :class="formPage" ? >
        <h3 class="text-2xl font-bold mb-6">{{ editingId ? $t('ui.editWarehouse') : $t('ui.newWarehouse') }}</h3>
        
        <form @submit.prevent="saveWarehouse" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.code') }}</label>
              <input v-model="form.code" class="input" :placeholder="$t('ui.autoGeneratedIfEmpty')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.name2') }}</label>
              <input v-model="form.name" required class="input" :placeholder="$t('ui.warehouseName')" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.address') }}</label>
            <textarea v-model="form.address" rows="2" class="input" :placeholder="$t('ui.fullAddress')"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.phone') }}</label>
              <input v-model="form.phone" class="input" :placeholder="$t('ui.phoneNumber')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.email') }}</label>
              <input v-model="form.email" type="email" class="input" :placeholder="$t('ui.emailAddress')" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.description') }}</label>
            <textarea v-model="form.description" rows="2" class="input" :placeholder="$t('ui.optionalDescription2')"></textarea>
          </div>

          <div class="flex items-center space-x-6">
            <label class="flex items-center">
              <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-emerald-600 rounded" />
              <span class="ml-2 text-sm text-gray-700">{{ $t('labels.active') }}</span>
            </label>
            <label class="flex items-center">
              <input v-model="form.is_default" type="checkbox" class="h-4 w-4 text-yellow-600 rounded" />
              <span class="ml-2 text-sm text-gray-700">{{ $t('ui.setAsDefault') }}</span>
            </label>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="closeFormTab" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('ui.saveWarehouse') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Warehouse Modal -->
    <div v-if="showViewModal" class="tab-form" :class="formPage ? 'mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'">
      <div class="bg-white rounded-xl p-6 w-full overflow-y-auto shadow-md" :class="formPage">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">{{ selectedWarehouse?.name }}</h3>
            <p class="text-sm text-gray-500">{{ selectedWarehouse?.code }}</p>
          </div>
          <span :class="selectedWarehouse?.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
            class="px-3 py-1 text-sm rounded-full">
            {{ selectedWarehouse?.is_active ? $t('labels.active') : $t('labels.inactive') }}
          </span>
        </div>

        <!-- Warehouse Info -->
        <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
          <div><span class="text-gray-500">{{ $t('ui.address') }}</span> {{ selectedWarehouse?.address || '-' }}</div>
          <div><span class="text-gray-500">{{ $t('ui.phone') }}</span> {{ selectedWarehouse?.phone || '-' }}</div>
          <div><span class="text-gray-500">{{ $t('ui.email2') }}</span> {{ selectedWarehouse?.email || '-' }}</div>
          <div><span class="text-gray-500">{{ $t('ui.totalStock') }}</span> <strong>{{ selectedWarehouse?.stocks_sum_quantity || 0 }}</strong></div>
        </div>

        <!-- Locations Section -->
        <div class="border-t pt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">{{ $t('ui.locations') }}{{ selectedWarehouse?.locations?.length || 0 }})</h4>
            <button @click="openLocationForm()" class="text-sm text-emerald-600 hover:text-emerald-800">{{ $t('ui.addLocation') }}</button>
          </div>

          <table v-if="selectedWarehouse?.locations?.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">{{ $t('ui.code') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.name') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.zoneAisleRack') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('ui.capacity') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.stock') }}</th>
                <th class="px-4 py-2 text-center">{{ $t('projectInvestment.materials.status') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="loc in selectedWarehouse?.locations" :key="loc.id">
                <td class="px-4 py-2 font-medium">{{ loc.code }}</td>
                <td class="px-4 py-2">{{ loc.name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ [loc.zone, loc.aisle, loc.rack, loc.shelf].filter(Boolean).join('-') || '-' }}</td>
                <td class="px-4 py-2 text-right">{{ loc.capacity || '-' }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ loc.stocks_sum_quantity || 0 }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="loc.is_active ? 'text-green-600' : 'text-gray-400'">{{ loc.is_active ? '●' : '○' }}</span>
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <button @click="editLocation(loc)" class="text-blue-600 hover:text-blue-900">{{ $t('common.edit') }}</button>
                  <button @click="deleteLocation(loc)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">{{ $t('ui.noLocationsDefined') }}</p>
        </div>

        <!-- Product Stocks Section -->
        <div class="border-t pt-4 mt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">{{ $t('ui.productStocks') }}{{ warehouseStocks.length }})</h4>
            <button v-if="selectedWarehouse?.is_default" @click="openAddProductModal()" 
              class="text-sm text-orange-600 hover:text-orange-800">{{ $t('ui.addProduct2') }}</button>
          </div>

          <div v-if="loadingStocks" class="text-center py-4 text-gray-500">{{ $t('ui.loadingStocks') }}</div>
          
          <table v-else-if="warehouseStocks.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">{{ $t('dashboard.sku') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('sales.product') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('ui.brand') }}</th>
                <th class="px-4 py-2 text-left">{{ $t('projectInvestment.projectLocation') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.quantity') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('ui.lastUpdated') }}</th>
                <th class="px-4 py-2 text-right">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="stock in warehouseStocks" :key="stock.id">
                <td class="px-4 py-2 font-mono text-sm">{{ stock.product?.sku || '-' }}</td>
                <td class="px-4 py-2 font-medium">{{ stock.product?.title || '-' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ stock.product?.brand || '-' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ stock.location?.name || $t('ui.noLocation') }}</td>
                <td class="px-4 py-2 text-right">
                  <span class="font-bold text-emerald-600">{{ stock.quantity }}</span>
                </td>
                <td class="px-4 py-2 text-right text-gray-500 text-xs">
                  {{ stock.last_updated ? new Date(stock.last_updated).toLocaleDateString() : '-' }}
                </td>
              <td class="px-4 py-2 text-right text-gray-500 text-xs">
                <button
                  :disabled="!stock.product"
                  @click="editProduct(stock.product)"
                  class="text-indigo-600 hover:text-indigo-900 disabled:text-gray-400 disabled:cursor-not-allowed">
                  {{ $t('common.edit') }}
                </button>
              </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">{{ $t('ui.noProductsInThisWarehouse') }}</p>
        </div>

        <div class="flex justify-end mt-6">
          <button @click="closeFormTab" class="btn-secondary">{{ $t('ui.close') }}</button>
        </div>
      </div>
    </div>

    <!-- Location Modal -->
    <div v-if="showLocationModal" class="tab-form" :class="formPage ? 'max-w-4xl mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-md" :class="formPage ? 'max-w-4xl' : ''">
        <h3 class="text-xl font-bold mb-4">{{ editingLocationId ? $t('ui.editLocation') : $t('ui.newLocation') }}</h3>
        
        <form @submit.prevent="saveLocation" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.code') }}</label>
              <input v-model="locationForm.code" class="input" :placeholder="$t('ui.autoGenerated')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.name2') }}</label>
              <input v-model="locationForm.name" required class="input" :placeholder="$t('ui.locationName')" />
            </div>
          </div>

          <div class="grid grid-cols-4 gap-2">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('ui.zone') }}</label>
              <input v-model="locationForm.zone" class="input" placeholder="A" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('ui.aisle') }}</label>
              <input v-model="locationForm.aisle" class="input" placeholder="1" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('ui.rack') }}</label>
              <input v-model="locationForm.rack" class="input" placeholder="01" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('ui.shelf') }}</label>
              <input v-model="locationForm.shelf" class="input" placeholder="A" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.capacity') }}</label>
            <input v-model.number="locationForm.capacity" type="number" min="0" class="input" :placeholder="$t('ui.maxItemsOptional')" />
          </div>

          <label class="flex items-center">
            <input v-model="locationForm.is_active" type="checkbox" class="h-4 w-4 text-emerald-600 rounded" />
            <span class="ml-2 text-sm text-gray-700">{{ $t('labels.active') }}</span>
          </label>

          <div v-if="locationError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ locationError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="closeFormTab" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="savingLocation" class="btn-primary">{{ savingLocation ? $t('sales.saving') : $t('ui.saveLocation') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Product Stock Modal -->
    <div v-if="showAddProductModal" class="tab-form" :class="formPage ? 'mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'">
      <div class="bg-white rounded-xl p-6 w-full shadow-md" :class="formPage">
        <h3 class="text-xl font-bold mb-4">{{ $t('ui.addProductToDefaultWarehouse') }}</h3>
        
        <div v-if="!defaultWarehouse" class="bg-yellow-50 text-yellow-700 p-4 rounded-lg mb-4">
          <p class="font-medium">{{ $t('ui.noDefaultWarehouseSet') }}</p>
          <p class="text-sm">{{ $t('ui.pleaseSetAWarehouseAsDefaultFirstByClickingSetDefaultOnAWarehouse') }}</p>
        </div>
        
        <div v-else class="mb-4 p-3 bg-emerald-50 rounded-lg">
          <p class="text-sm text-gray-600">{{ $t('ui.addingTo') }}</p>
          <p class="font-medium text-emerald-700">{{ defaultWarehouse.name }} ({{ defaultWarehouse.code }})</p>
        </div>
        
        <form @submit.prevent="submitAddProduct" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.searchProduct') }}</label>

            <select v-model="addProductForm.product_id" @change="onProductSelect" required class="input text-sm">
                    <option value="">{{ $t('sales.selectProduct') }}</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{ product.title || product.name }} - {{ product.brand }} {{ $t('ui.stock') }} {{ product.stock || 0 }})
                    </option>
                  </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantity') }}</label>
            <input v-model.number="addProductForm.quantity" type="number" min="1" required class="input" :placeholder="$t('ui.enterQuantity')" :disabled="!defaultWarehouse" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="addProductForm.notes" rows="2" class="input" :placeholder="$t('ui.optionalNotes2')" :disabled="!defaultWarehouse"></textarea>
          </div>

          <div v-if="addProductError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ addProductError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="closeFormTab" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="addingProduct || !defaultWarehouse || !addProductForm.product_id" class="btn-primary">
              {{ editingProduct ? $t('ui.editProduct') : $t('ui.addProduct') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const route = useRoute();
const formPage = ref(false);
const formType = ref('');

const openFormTab = (query) => {
  window.open(router.resolve({ path: '/dashboard/warehouses', query }).href, '_blank');
};

const closeFormTab = () => {
  if (!formPage.value) {
    showModal.value = false;
    showViewModal.value = false;
    showLocationModal.value = false;
    showAddProductModal.value = false;
    return;
  }
  if (window.opener) {
    window.opener.postMessage({ type: 'admin-data-saved', resource: 'warehouses' }, window.location.origin);
    window.close();
    window.setTimeout(() => {
      if (!window.closed) window.location.replace('/dashboard/warehouses');
    }, 150);
    return;
  }
  router.replace('/dashboard/warehouses');
};

const onWarehouseSaved = (event) => {
  if (event.origin === window.location.origin && event.data?.type === 'admin-data-saved' &&
      ['warehouses', 'products'].includes(event.data.resource)) {
    loadWarehouses();
    loadStats();
    if (formType.value === 'view' && selectedWarehouse.value) {
      selectWarehouseTab(selectedWarehouse.value);
      loadWarehouseStocks(selectedWarehouse.value.id);
    }
  }
};

const warehouses = ref({ data: [] });
const stats = ref({});
const loading = ref(true);
const showModal = ref(false);
const showViewModal = ref(false);
const showLocationModal = ref(false);
const selectedWarehouse = ref(null);
const activeWarehouseId = ref(null);
const allWarehouses = ref([]);
const editingId = ref(null);
const editingLocationId = ref(null);
const editingProduct = ref(null);
const saving = ref(false);
const savingLocation = ref(false);
const error = ref('');
const locationError = ref('');
const contractPayments = ref([]);
const paymentsLoading = ref(false);
const showPaymentForm = ref(false);
const paymentSaving = ref(false);
const paymentError = ref('');
const editingPaymentId = ref(null);
const editingPaymentAmount = ref(0);

const filters = ref({ search: '', is_active: '' });

const form = ref({
  code: '',
  name: '',
  address: '',
  phone: '',
  email: '',
  description: '',
  is_active: true,
  is_default: false,
});

const locationForm = ref({
  code: '',
  name: '',
  zone: '',
  aisle: '',
  rack: '',
  shelf: '',
  capacity: null,
  is_active: true,
});

const paymentForm = ref({
  payment_type: 'dp',
  amount: null,
  payment_date: new Date().toISOString().split('T')[0],
  method: '',
  status: 'paid',
  reference_number: '',
  notes: ''
});

const selectedContract = computed(() => selectedWarehouse.value?.project_investment?.contract || null);
const contractTotal = computed(() => Number(selectedContract.value?.contract_value || 0));
const totalPaid = computed(() => contractPayments.value.reduce((sum, p) => {
  if (p.status === 'cancelled') return sum;
  return sum + Number(p.amount || 0);
}, 0));
const remainingAmount = computed(() => Math.max(0, contractTotal.value - totalPaid.value));
const remainingAvailable = computed(() => {
  if (!editingPaymentId.value) return remainingAmount.value;
  return Math.max(0, remainingAmount.value + Number(editingPaymentAmount.value || 0));
});

const loadWarehouses = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warehouses', { params: { page, per_page: 100, ...filters.value } });
    warehouses.value = response.data;
    allWarehouses.value = response.data.data || [];
    
    // Auto-select first warehouse or default warehouse
    if (allWarehouses.value.length > 0 && !activeWarehouseId.value) {
      const defaultWh = allWarehouses.value.find(w => w.is_default) || allWarehouses.value[0];
      selectWarehouseTab(defaultWh);
    }
  } catch (err) {
    console.error('Gagal memuat gudang:', err);
  } finally {
    loading.value = false;
  }
};

const selectWarehouseTab = async (warehouse) => {
  activeWarehouseId.value = warehouse.id;
  try {
    const response = await api.get(`/warehouses/${warehouse.id}`);
    selectedWarehouse.value = response.data;
    loadWarehouseStocks(warehouse.id);
    showPaymentForm.value = false;
    resetPaymentForm();
    await loadContractPayments(response.data?.project_investment?.contract?.id);
  } catch (err) {
    console.error('Gagal memuat detail gudang:', err);
  }
};

const loadStats = async () => {
  try {
    const response = await api.get('/warehouses/statistics');
    stats.value = response.data;
  } catch (err) {
    console.error('Gagal memuat statistik:', err);
  }
};

const resetForm = () => {
  form.value = { code: '', name: '', address: '', phone: '', email: '', description: '', is_active: true, is_default: false };
  editingId.value = null;
  error.value = '';
};

const resetLocationForm = () => {
  locationForm.value = { code: '', name: '', zone: '', aisle: '', rack: '', shelf: '', capacity: null, is_active: true };
  editingLocationId.value = null;
  locationError.value = '';
};

const openWarehouseForm = (warehouse = null) => {
  if (!formPage.value || formType.value !== 'warehouse') {
    openFormTab({ form: 'warehouse', ...(warehouse ? { id: warehouse.id } : {}) });
    return;
  }
  if (warehouse) {
    form.value = { ...warehouse };
    editingId.value = warehouse.id;
    error.value = '';
  } else {
    resetForm();
  }
  showModal.value = true;
};

const openLocationForm = (location = null) => {
  if (!formPage.value || formType.value === 'view') {
    openFormTab({
      form: 'location',
      warehouseId: selectedWarehouse.value.id,
      ...(location ? { locationId: location.id } : {}),
    });
    return;
  }
  if (location) {
    locationForm.value = { ...location };
    editingLocationId.value = location.id;
    locationError.value = '';
  } else {
    resetLocationForm();
  }
  showLocationModal.value = true;
};

const saveWarehouse = async () => {
  saving.value = true;
  error.value = '';
  try {
    if (editingId.value) {
      await api.put(`/warehouses/${editingId.value}`, form.value);
    } else {
      await api.post('/warehouses', form.value);
    }
    if (formPage.value) {
      closeFormTab();
    } else {
      showModal.value = false;
      loadWarehouses();
      loadStats();
    }
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal menyimpan gudang");
  } finally {
    saving.value = false;
  }
};

const editWarehouse = (warehouse) => {
  openWarehouseForm(warehouse);
};

// Warehouse stocks
const warehouseStocks = ref([]);
const loadingStocks = ref(false);

const loadWarehouseStocks = async (warehouseId) => {
  loadingStocks.value = true;
  try {
    const response = await api.get('/stock', {
      params: { warehouse_id: warehouseId, per_page: 100 }
    });
    // Filter stocks for this warehouse AND only show items with quantity > 0
    const allStocks = response.data.data || response.data || [];
    warehouseStocks.value = allStocks.filter(s => s.warehouse_id === warehouseId && s.quantity > 0);
  } catch (err) {
    console.error('Gagal memuat stok gudang:', err);
    // Try alternate endpoint
    try {
      const res = await api.get('/current-stocks', { params: { warehouse_id: warehouseId } });
      const stocks = res.data.data || res.data || [];
      warehouseStocks.value = stocks.filter(s => s.quantity > 0);
    } catch (e) {
      warehouseStocks.value = [];
    }
  } finally {
    loadingStocks.value = false;
  }
};

const viewWarehouse = async (warehouse) => {
  if (!formPage.value) {
    openFormTab({ form: 'view', id: warehouse.id });
    return;
  }
  try {
    const response = await api.get(`/warehouses/${warehouse.id}`);
    selectedWarehouse.value = response.data;
    showViewModal.value = true;
    // Load stocks for this warehouse
    loadWarehouseStocks(warehouse.id);
    await loadContractPayments(response.data?.project_investment?.contract?.id);
  } catch (err) {
    alert(uiText("Gagal memuat detail gudang"));
  }
};

const deleteWarehouse = async (warehouse) => {
  if (!confirm(uiText('ui.deleteWarehouseNamed', { p0: warehouse.name }))) return;
  try {
    await api.delete(`/warehouses/${warehouse.id}`);
    loadWarehouses();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus gudang"));
  }
};

const setDefault = async (warehouse) => {
  try {
    await api.post(`/warehouses/${warehouse.id}/set-default`);
    loadWarehouses();
  } catch (err) {
    alert(uiText("Gagal menetapkan gudang utama"));
  }
};

const saveLocation = async () => {
  savingLocation.value = true;
  locationError.value = '';
  try {
    if (editingLocationId.value) {
      await api.put(`/warehouse-locations/${editingLocationId.value}`, locationForm.value);
    } else {
      await api.post(`/warehouses/${selectedWarehouse.value.id}/locations`, locationForm.value);
    }
    if (formPage.value) {
      closeFormTab();
    } else {
      showLocationModal.value = false;
      viewWarehouse(selectedWarehouse.value); // Refresh
      loadStats();
    }
  } catch (err) {
    locationError.value = err.response?.data?.message || uiText("Gagal menyimpan lokasi");
  } finally {
    savingLocation.value = false;
  }
};

const editLocation = (location) => {
  openLocationForm(location);
};

const deleteLocation = async (location) => {
  if (!confirm(uiText('ui.deleteLocationNamed', { p0: location.name }))) return;
  try {
    await api.delete(`/warehouse-locations/${location.id}`);
    viewWarehouse(selectedWarehouse.value); // Refresh
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus lokasi"));
  }
};

const editProduct = (product) => {
    if (!product?.id) {
        console.warn('editProduct called without product');
        return;
    }

    window.open(`/dashboard/products?edit=${encodeURIComponent(product.id)}`, '_blank');
};

const saveProduct = async () => {
    saving.value = true;
    try {
        // Filter out empty category pairs
        const validCategories = form.value.categories.filter(c => c.category_id);
        const payload = {
            ...form.value,
            categories: validCategories
        };

        if (editingProduct.value) {
            await api.put(`/products/${editingProduct.value.id}`, payload);
        } else {
            await api.post('/products', payload);
        }
        showModal.value = false;
        editingProduct.value = null;
        resetForm();
        loadWarehouseStocks();
    } catch (error) {
        console.error('Gagal menyimpan produk:', error);
        alert('Failed to save product: ' + (error.response?.data?.message || uiText("Kesalahan tidak diketahui")));
    } finally {
        saving.value = false;
    }
};

const deleteProduct = async (id) => {
    if (!confirm(uiText("Yakin ingin menghapus produk ini?"))) return;
    try {
        await api.delete(`/products/${id}`);
        loadWarehouseStocks();
    } catch (error) {
        console.error('Gagal menghapus produk:', error);
        alert(uiText("Gagal menghapus produk"));
    }
};

// Add Product Stock states
const showAddProductModal = ref(false);
const defaultWarehouse = ref(null);
const productSearch = ref('');
const productSearchResults = ref([]);
const selectedProduct = ref(null);
const products = ref({ data: [] });
const addingProduct = ref(false);
const addProductError = ref('');
const addProductForm = ref({
  product_id: '',
  quantity: 1,
  notes: ''
});

const filteredProducts = computed(() => {
  if (!productSearch.value) return products.value;
  const search = productSearch.value.toLowerCase();
  return products.value.filter(p =>
    (p.title?.toLowerCase().includes(search)) ||
    (p.name?.toLowerCase().includes(search)) ||
    (p.brand?.toLowerCase().includes(search)) ||
    (p.sku?.toLowerCase().includes(search))
  );
});

const productForm = ref({
  title: '',
  brand: '',
  price: 0,
  stock: 0,
  sku: '',
});

const loadDefaultWarehouse = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100 } });
    const warehouses = response.data.data || [];
    defaultWarehouse.value = warehouses.find(w => w.is_default) || null;
  } catch (err) {
    console.error('Gagal memuat gudang utama:', err);
  }
};

const openAddProductModal = () => {
  if (!formPage.value) {
    openFormTab({ form: 'add-product' });
    return;
  }
  showAddProductModal.value = true;
  resetAddProductForm();
  loadProducts();
};

const resetAddProductForm = () => {
  productSearch.value = '';
  productSearchResults.value = [];
  selectedProduct.value = null;
  addProductError.value = '';
  addProductForm.value = { product_id: '', quantity: 1, notes: '' };
};

let searchTimeout = null;
const searchProducts = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    if (productSearch.value.length < 2) {
      productSearchResults.value = [];
      return;
    }
    try {
      const response = await api.get('/warehouses/products', {
        params: { search: productSearch.value }
      });
      productSearchResults.value = response.data;
    } catch (err) {
      console.error('Gagal mencari produk:', err);
    }
  }, 300);
};

const selectProduct = (product) => {
  selectedProduct.value = product;
  productSearch.value = product.title;
  productSearchResults.value = [];
  addProductForm.value.product_id = product.id;
};

const onProductSelect = () => {
  const selectedId = addProductForm.value.product_id;
  selectedProduct.value = products.value.find(p => p.id == selectedId) || null;
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Gagal memuat produk:', err);
    products.value = [];
  }
};

const submitAddProduct = async () => {
  if (!addProductForm.value.product_id || !defaultWarehouse.value) return;
  
  addingProduct.value = true;
  addProductError.value = '';
  try {
    await api.post('/warehouses/add-product-stock', {
      product_id: addProductForm.value.product_id,
      quantity: addProductForm.value.quantity,
      notes: addProductForm.value.notes
    });
    if (formPage.value) {
      closeFormTab();
    } else {
      showAddProductModal.value = false;
      loadWarehouses();
      loadStats();
      alert(uiText("Stok produk berhasil ditambahkan!"));
    }
  } catch (err) {
    addProductError.value = err.response?.data?.message || uiText("Gagal menambahkan stok produk");
  } finally {
    addingProduct.value = false;
  }
};

const loadContractPayments = async (contractId) => {
  if (!contractId) {
    contractPayments.value = [];
    return;
  }

  paymentsLoading.value = true;
  try {
    const response = await api.get('/payments', {
      params: { payable_type: 'project_contract', payable_id: contractId, per_page: 100 }
    });
    contractPayments.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Gagal memuat pembayaran kontrak:', err);
    contractPayments.value = [];
  } finally {
    paymentsLoading.value = false;
  }
};

const resetPaymentForm = () => {
  paymentForm.value = {
    payment_type: 'dp',
    amount: null,
    payment_date: new Date().toISOString().split('T')[0],
    method: '',
    status: 'paid',
    reference_number: '',
    notes: ''
  };
  paymentError.value = '';
  editingPaymentId.value = null;
  editingPaymentAmount.value = 0;
};

const togglePaymentForm = () => {
  if (showPaymentForm.value) {
    showPaymentForm.value = false;
    resetPaymentForm();
    return;
  }
  showPaymentForm.value = true;
  resetPaymentForm();
};

const submitPayment = async () => {
  if (!selectedContract.value) return;
  paymentError.value = '';

  const amount = Number(paymentForm.value.amount || 0);
  if (amount <= 0) {
    paymentError.value = uiText("Nominal harus lebih besar dari 0.");
    return;
  }
  if (contractTotal.value > 0 && amount > remainingAvailable.value + 0.0001) {
    paymentError.value = uiText("Pembayaran melebihi saldo tersisa.");
    return;
  }

  paymentSaving.value = true;
  try {
    const payload = {
      payment_type: paymentForm.value.payment_type,
      amount: amount,
      payment_date: paymentForm.value.payment_date,
      method: paymentForm.value.method,
      status: paymentForm.value.status,
      reference_number: paymentForm.value.reference_number,
      notes: paymentForm.value.notes
    };

    if (editingPaymentId.value) {
      await api.put(`/payments/${editingPaymentId.value}`, payload);
    } else {
      await api.post('/payments', {
        payable_type: 'project_contract',
        payable_id: selectedContract.value.id,
        ...payload
      });
    }
    showPaymentForm.value = false;
    resetPaymentForm();
    await loadContractPayments(selectedContract.value.id);
  } catch (err) {
    paymentError.value = err.response?.data?.message || uiText("Gagal menyimpan pembayaran");
  } finally {
    paymentSaving.value = false;
  }
};

const editPayment = (payment) => {
  editingPaymentId.value = payment.id;
  editingPaymentAmount.value = Number(payment.amount || 0);
  paymentForm.value = {
    payment_type: payment.payment_type || 'dp',
    amount: Number(payment.amount || 0),
    payment_date: payment.payment_date || new Date().toISOString().split('T')[0],
    method: payment.method || '',
    status: payment.status || 'paid',
    reference_number: payment.reference_number || '',
    notes: payment.notes || ''
  };
  paymentError.value = '';
  showPaymentForm.value = true;
};

const deletePayment = async (payment) => {
  if (!confirm(uiText("Hapus pembayaran ini?"))) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    await loadContractPayments(selectedContract.value?.id);
    if (editingPaymentId.value === payment.id) {
      resetPaymentForm();
    }
  } catch (err) {
    paymentError.value = err.response?.data?.message || uiText("Gagal menghapus pembayaran");
  }
};

const formatCurrency = (value) => {
  const num = Number(value || 0);
  return new Intl.NumberFormat(uiLocale(), { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
};

const formatDate = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleDateString();
};

onMounted(async () => {
  window.addEventListener('message', onWarehouseSaved);
  formType.value = typeof route.query.form === 'string' ? route.query.form : '';
  formPage.value = Boolean(formType.value);

  await loadWarehouses();
  loadStats();
  await loadDefaultWarehouse();

  if (formType.value === 'warehouse') {
    const warehouse = route.query.id
      ? allWarehouses.value.find((item) => String(item.id) === String(route.query.id))
      : null;
    if (route.query.id && !warehouse) {
      closeFormTab();
      return;
    }
    openWarehouseForm(warehouse);
  } else if (formType.value === 'view') {
    const warehouse = allWarehouses.value.find((item) => String(item.id) === String(route.query.id));
    if (warehouse) await viewWarehouse(warehouse);
    else closeFormTab();
  } else if (formType.value === 'location') {
    const warehouse = allWarehouses.value.find((item) => String(item.id) === String(route.query.warehouseId));
    if (!warehouse) {
      closeFormTab();
      return;
    }
    await selectWarehouseTab(warehouse);
    const location = selectedWarehouse.value?.locations?.find(
      (item) => String(item.id) === String(route.query.locationId),
    );
    openLocationForm(location || null);
  } else if (formType.value === 'add-product') {
    openAddProductModal();
  }
});

onBeforeUnmount(() => window.removeEventListener('message', onWarehouseSaved));
</script>

<style scoped>
.form-page > :not(.tab-form) {
  display: none !important;
}
</style>
