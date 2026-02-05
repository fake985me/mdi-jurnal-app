<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Stock Management</h2>
      <button
        v-if="activeTab === 'regular'"
        @click="showTransactionModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        + Record Transaction
      </button>
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
            📦 Regular Stock
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
            ↩️ RMA Inventory
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
            📊 Investment Stock
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
            🔧 MSA Project
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
            ⚠️ Defective Items
          </button>
        </nav>
      </div>
    </div>

    <!-- Regular Stock Tab -->
    <div v-show="activeTab === 'regular'" class="space-y-4">
      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <input
            v-model="filters.search"
            @input="loadStocks"
            type="text"
            placeholder="Search by product name or SKU..."
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
          <div class="flex items-center">
            <input
              id="low-stock"
              v-model="filters.low_stock"
              @change="loadStocks"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="low-stock" class="ml-2 text-sm text-gray-700">
              Show only low stock items
            </label>
          </div>
        </div>
      </div>

    <!-- Current Stock Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Current Stock Levels</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading stock data...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category / Sub Category</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Stock</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="stock in stocks.data" :key="stock.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ stock.product?.title || stock.product?.name }}</div>
              <div class="text-xs text-gray-500">{{ stock.product?.brand }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ stock.product?.sku }}
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
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-lg font-bold" :class="stock.quantity <= 10 ? 'text-red-600' : 'text-green-600'">
                {{ stock.quantity }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              10
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                stock.quantity <= 10
                  ? 'bg-red-100 text-red-800'
                  : stock.quantity <= 20
                    ? 'bg-yellow-100 text-yellow-800'
                    : 'bg-green-100 text-green-800'
              ]">
                {{ stock.quantity <= 10 ? 'Low Stock' : stock.quantity <= 20 ? 'Medium' : 'Good' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(stock.last_updated).toLocaleDateString() }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="stocks.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center">
        <p class="text-sm text-gray-700">
          Showing {{ stocks.from }} to {{ stocks.to }} of {{ stocks.total }} items
        </p>
        <div class="flex space-x-2">
          <button
            @click="loadStocks(stocks.current_page - 1)"
            :disabled="!stocks.prev_page_url"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="loadStocks(stocks.current_page + 1)"
            :disabled="!stocks.next_page_url"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50"
          >
            Next
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
        <h3 class="text-lg font-semibold text-gray-900">↩️ RMA Inventory (Returned Items)</h3>
        <p class="text-sm text-gray-600 mt-1">Items returned from RMAs grouped by condition</p>
      </div>

      <div v-if="loadingRMA" class="p-8 text-center">
        <p class="text-gray-600">Loading RMA inventory...</p>
      </div>

      <div v-else-if="!rmaStock.length" class="p-8 text-center">
        <p class="text-gray-500">No RMA items in stock</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="rma in rmaStock" :key="`${rma.product_id}-${rma.condition}`" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ rma.product?.title }}</div>
              <div class="text-xs text-gray-500">{{ rma.product?.brand }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getConditionBadge(rma.condition)" class="px-2 py-1 text-xs rounded-full capitalize">
                {{ rma.condition?.replace('_', ' ') || 'N/A' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-lg font-bold text-gray-900">{{ rma.total_quantity }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <router-link to="/dashboard/rmas" class="text-indigo-600 hover:text-indigo-900">
                View RMAs →
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    </div>

    <!-- Investment Stock Tab -->
    <div v-show="activeTab === 'investment'">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
          <h3 class="text-lg font-semibold text-gray-900">📊 Investment Stock (Project Allocations)</h3>
          <p class="text-sm text-gray-600 mt-1">Items allocated to active projects</p>
        </div>

        <div v-if="loadingInvestment" class="p-8 text-center">
          <p class="text-gray-600">Loading investment stock...</p>
        </div>

        <div v-else-if="!investmentStock.length" class="p-8 text-center">
          <p class="text-gray-500">No items allocated to projects</p>
        </div>

        <table v-else class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                  View Projects →
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MSA Project Tab -->
    <div v-show="activeTab === 'msa'">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-red-50">
          <h3 class="text-lg font-semibold text-gray-900">🔧 MSA Project (Items in Repair)</h3>
          <p class="text-sm text-gray-600 mt-1">Items from projects currently being repaired or replaced</p>
        </div>

        <div v-if="loadingMSA" class="p-8 text-center">
          <p class="text-gray-600">Loading MSA items...</p>
        </div>

        <div v-else-if="!msaStock?.length" class="p-8 text-center">
          <p class="text-gray-500">No MSA items in repair</p>
        </div>

        <table v-else class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MSA Code</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
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
                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ item.status.replace('_', ' ') }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Defective Items Tab -->
    <div v-show="activeTab === 'defective'">
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">⚠️ Defective/Returned Items</h3>
          <p class="text-sm text-gray-600 mt-1">Combined defective items from RMA, MSA, and Projects</p>
        </div>

        <div v-if="loadingDefective" class="p-8 text-center">
          <p class="text-gray-600">Loading defective items...</p>
        </div>

        <div v-else-if="!defectiveStock?.length" class="p-8 text-center">
          <p class="text-gray-500">No defective items</p>
        </div>

        <table v-else class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
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
              <td class="px-6 py-4 text-sm text-gray-900">{{ item.product?.title }}</td>
              <td class="px-6 py-4 text-sm font-bold text-red-600">{{ item.quantity }}</td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">{{ item.condition }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Transaction Modal -->
    <div v-if="showTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Record Stock Transaction</h3>
        
        <form @submit.prevent="saveTransaction" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
            <select
              v-model="transactionForm.product_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Select Product</option>
              <option v-for="stock in allStocks" :key="stock.product.id" :value="stock.product.id">
                {{ stock.product.title || stock.product.name }} (Current: {{ stock.quantity }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type *</label>
            <select
              v-model="transactionForm.transaction_type"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Select Type</option>
              <option value="in">Stock In</option>
              <option value="out">Stock Out</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
            <input
              v-model="transactionForm.quantity"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea
              v-model="transactionForm.notes"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <div v-if="success" class="bg-green-50 text-green-600 p-3 rounded-lg text-sm">
            {{ success }}
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showTransactionModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save Transaction' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const stocks = ref({ data: [] });
const allStocks = ref([]);
const rmaStock = ref([]);
const investmentStock = ref([]);
const msaStock = ref([]);
const defectiveStock = ref([]);
const activeTab = ref('regular');
const loading = ref(true);
const loadingRMA = ref(true);
const loadingInvestment = ref(true);
const loadingMSA = ref(true);
const loadingDefective = ref(true);
const showTransactionModal = ref(false);
const saving = ref(false);
const error = ref('');
const success = '';

const filters = ref({
  search: '',
  low_stock: false,
});

const transactionForm = ref({
  product_id: '',
  transaction_type: '',
  quantity: 1,
  notes: '',
});

const loadStocks = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page, ...filters.value };
    const response = await api.get('/stock', { params });
    stocks.value = response.data;
  } catch (err) {
    console.error('Failed to load stocks:', err);
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
    console.error('Failed to load investment stock:', err);
  } finally {
    loadingInvestment.value = false;
  }
};

const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
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
    console.error('Failed to load defective stock:', err);
  } finally {
    loadingDefective.value = false;
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
    console.error('Failed to load all stocks:', err);
  }
};

const saveTransaction = async () => {
  saving.value = true;
  error.value = '';
  success.value = '';
  
  try {
    await api.post('/stock/transaction', transactionForm.value);
    success.value = 'Transaction recorded successfully!';
    transactionForm.value = { product_id: '', transaction_type: '', quantity: 1, notes: '' };
    loadStocks();
    loadAllStocks();
    setTimeout(() => {
      showTransactionModal.value = false;
      success.value = '';
    }, 1500);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to record transaction';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadStocks();
  loadAllStocks();
  loadRMAStock();
  loadInvestmentStock();
  loadMSAStock();
  loadDefectiveStock();
});
</script>
