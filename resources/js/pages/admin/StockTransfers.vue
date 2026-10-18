<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
          {{ $t('labels.stock_transfer') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.transferProductsBetweenWarehouses') }}</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        {{ $t('ui.newTransfer') }}
      </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="card p-4 bg-gradient-to-br from-gray-50 to-gray-100">
        <p class="text-xs text-gray-500 uppercase">{{ $t('sales.total') }}</p>
        <p class="text-2xl font-bold text-gray-700">{{ stats.total_transfers || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-yellow-50 to-amber-50">
        <p class="text-xs text-gray-500 uppercase">{{ $t('labels.pending') }}</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-blue-50 to-indigo-50">
        <p class="text-xs text-gray-500 uppercase">{{ $t('labels.in_transit') }}</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.in_transit || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-green-50 to-emerald-50">
        <p class="text-xs text-gray-500 uppercase">{{ $t('labels.completed') }}</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.completed || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-red-50 to-rose-50">
        <p class="text-xs text-gray-500 uppercase">{{ $t('labels.cancelled') }}</p>
        <p class="text-2xl font-bold text-red-600">{{ stats.cancelled || 0 }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          @input="loadTransfers"
          type="text"
          :placeholder="$t('ui.searchByCodeOrProduct')"
          class="input" />
        <select v-model="filters.status" @change="loadTransfers" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="pending">{{ $t('labels.pending') }}</option>
          <option value="in_transit">{{ $t('labels.in_transit') }}</option>
          <option value="completed">{{ $t('labels.completed') }}</option>
          <option value="cancelled">{{ $t('labels.cancelled') }}</option>
        </select>
        <select v-model="filters.from_warehouse_id" @change="loadTransfers" class="input">
          <option value="">{{ $t('ui.fromAnyWarehouse') }}</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
        <select v-model="filters.to_warehouse_id" @change="loadTransfers" class="input">
          <option value="">{{ $t('ui.toAnyWarehouse') }}</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
      </div>
    </div>

    <!-- Transfers Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.transferHistory') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('ui.loadingTransfers') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.code') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.from') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.to3') }}</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('sales.quantity') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.date') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="transfer in transfers.data" :key="transfer.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ transfer.transfer_code }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ transfer.product?.title }}</div>
              <div class="text-xs text-gray-500">{{ transfer.product?.sku }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">{{ transfer.from_warehouse?.name }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">{{ transfer.to_warehouse?.name }}</span>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="text-lg font-bold text-indigo-600">{{ transfer.quantity }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusBadge(transfer.status)" class="px-2 py-1 text-xs rounded-full">
                {{ $label(transfer.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(transfer.created_at) }}
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="transfer.status === 'pending'" @click="startTransfer(transfer)" class="text-blue-600 hover:text-blue-900">{{ $t('projectInvestment.actions.start') }}</button>
              <button v-if="transfer.status === 'in_transit'" @click="completeTransfer(transfer)" class="text-green-600 hover:text-green-900">{{ $t('projectInvestment.actions.complete') }}</button>
              <button v-if="['pending', 'in_transit'].includes(transfer.status)" @click="cancelTransfer(transfer)" class="text-red-600 hover:text-red-900">{{ $t('projectInvestment.actions.cancel') }}</button>
              <button @click="viewTransfer(transfer)" class="text-indigo-600 hover:text-indigo-900">{{ $t('projectInvestment.actions.view') }}</button>
            </td>
          </tr>
          <tr v-if="!transfers.data?.length">
            <td colspan="8" class="px-6 py-8 text-center text-gray-500">{{ $t('ui.noTransfersFound') }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="transfers.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ transfers.from }} {{ $t('ui.to') }} {{ transfers.to }} {{ $t('sales.of') }} {{ transfers.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadTransfers(transfers.current_page - 1)" :disabled="!transfers.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadTransfers(transfers.current_page + 1)" :disabled="!transfers.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <!-- Create Transfer Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.newStockTransfer') }}</h3>
        
        <form @submit.prevent="saveTransfer" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.fromWarehouse') }}</label>
            <select v-model="form.from_warehouse_id" required @change="loadSourceStock" class="input">
              <option value="">{{ $t('ui.selectSourceWarehouse') }}</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.toWarehouse') }}</label>
            <select v-model="form.to_warehouse_id" required class="input">
              <option value="">{{ $t('ui.selectDestinationWarehouse') }}</option>
              <option v-for="wh in availableDestinations" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.product2') }}</label>
            <select v-model="form.product_id" required class="input">
              <option value="">{{ $t('ui.selectProduct2') }}</option>
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.title }} {{ $t('ui.stock') }} {{ p.quantity || 0 }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantity') }}</label>
            <input v-model.number="form.quantity" type="number" min="1" required class="input" :placeholder="$t('ui.quantityToTransfer')" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="input" :placeholder="$t('ui.optionalNotes')"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('ui.creating') : $t('ui.createTransfer') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Transfer Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.transferDetails') }}</h3>
        
        <div class="space-y-4">
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.code2') }}</span>
            <strong>{{ selectedTransfer?.transfer_code }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.product3') }}</span>
            <strong>{{ selectedTransfer?.product?.title }}</strong>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">{{ $t('ui.from2') }}</span>
            <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">{{ selectedTransfer?.from_warehouse?.name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">{{ $t('ui.to4') }}</span>
            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">{{ selectedTransfer?.to_warehouse?.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.qty2') }}</span>
            <strong class="text-lg text-indigo-600">{{ selectedTransfer?.quantity }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.status2') }}</span>
            <span :class="getStatusBadge(selectedTransfer?.status)" class="px-2 py-1 text-xs rounded-full">
              {{ $label(selectedTransfer?.status) }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.created') }}</span>
            <span>{{ formatDate(selectedTransfer?.created_at) }}</span>
          </div>
          <div v-if="selectedTransfer?.transferred_at" class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.started') }}</span>
            <span>{{ formatDate(selectedTransfer?.transferred_at) }}</span>
          </div>
          <div v-if="selectedTransfer?.received_at" class="flex justify-between">
            <span class="text-gray-500">{{ $t('ui.completed') }}</span>
            <span>{{ formatDate(selectedTransfer?.received_at) }}</span>
          </div>
          <div v-if="selectedTransfer?.notes" class="pt-2 border-t">
            <p class="text-gray-500 text-sm">{{ $t('ui.note') }}</p>
            <p class="text-gray-700">{{ selectedTransfer?.notes }}</p>
          </div>
        </div>

        <div class="flex justify-end mt-6">
          <button @click="showViewModal = false" class="btn-secondary">{{ $t('ui.close') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';

const transfers = ref({ data: [] });
const warehouses = ref([]);
const products = ref([]);
const stats = ref({});
const loading = ref(true);
const showModal = ref(false);
const showViewModal = ref(false);
const selectedTransfer = ref(null);
const saving = ref(false);
const error = ref('');

const filters = ref({ search: '', status: '', from_warehouse_id: '', to_warehouse_id: '' });

const form = ref({
  from_warehouse_id: '',
  to_warehouse_id: '',
  product_id: '',
  quantity: 1,
  notes: '',
});

const availableDestinations = computed(() => {
  return warehouses.value.filter(wh => wh.id !== parseInt(form.value.from_warehouse_id));
});

const loadTransfers = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/stock-transfers', { params: { page, ...filters.value } });
    transfers.value = response.data;
  } catch (err) {
    console.error('Gagal memuat transfer:', err);
  } finally {
    loading.value = false;
  }
};

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100, is_active: 1 } });
    warehouses.value = response.data.data || [];
  } catch (err) {
    console.error('Gagal memuat gudang:', err);
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || [];
  } catch (err) {
    console.error('Gagal memuat produk:', err);
  }
};

const loadStats = async () => {
  try {
    const response = await api.get('/stock-transfers/statistics');
    stats.value = response.data;
  } catch (err) {
    console.error('Gagal memuat statistik:', err);
  }
};

const loadSourceStock = async () => {
  if (!form.value.from_warehouse_id) {
    products.value = [];
    return;
  }
  
  try {
    // Load stocks from source warehouse
    const response = await api.get('/stock', {
      params: { warehouse_id: form.value.from_warehouse_id, per_page: 500 }
    });
    
    // Map current_stocks to product format with quantity from warehouse
    const stocks = response.data.data || response.data || [];
    products.value = stocks
      .filter(s => s.quantity > 0)  // Only show products with stock > 0
      .map(s => ({
        id: s.product_id,
        title: s.product?.title || `Produk #${s.product_id}`,
        sku: s.product?.sku || '',
        quantity: s.quantity  // This is the warehouse stock
      }));
  } catch (err) {
    console.error('Gagal memuat stok asal:', err);
    products.value = [];
  }
};

const resetForm = () => {
  form.value = { from_warehouse_id: '', to_warehouse_id: '', product_id: '', quantity: 1, notes: '' };
  error.value = '';
};

const saveTransfer = async () => {
  saving.value = true;
  error.value = '';
  try {
    await api.post('/stock-transfers', form.value);
    showModal.value = false;
    loadTransfers();
    loadStats();
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal membuat transfer");
  } finally {
    saving.value = false;
  }
};

const startTransfer = async (transfer) => {
  if (!confirm(uiText("Mulai transfer ini? Stok akan dikurangi dari gudang asal."))) return;
  try {
    await api.post(`/stock-transfers/${transfer.id}/start`);
    loadTransfers();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal memulai transfer"));
  }
};

const completeTransfer = async (transfer) => {
  if (!confirm(uiText("Selesaikan transfer ini? Stok akan ditambahkan ke gudang tujuan."))) return;
  try {
    await api.post(`/stock-transfers/${transfer.id}/complete`);
    loadTransfers();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menyelesaikan transfer"));
  }
};

const cancelTransfer = async (transfer) => {
  if (!confirm(uiText("Batalkan transfer ini?"))) return;
  try {
    await api.post(`/stock-transfers/${transfer.id}/cancel`);
    loadTransfers();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal membatalkan transfer"));
  }
};

const viewTransfer = async (transfer) => {
  try {
    const response = await api.get(`/stock-transfers/${transfer.id}`);
    selectedTransfer.value = response.data;
    showViewModal.value = true;
  } catch (err) {
    alert(uiText("Gagal memuat detail transfer"));
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString(uiLocale(), { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusBadge = (status) => {
  const badges = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_transit: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return badges[status] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
  loadTransfers();
  loadWarehouses();
  loadStats();
});
</script>
