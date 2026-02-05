<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
          Stock Transfers
        </h2>
        <p class="text-sm text-gray-600 mt-1">Transfer products between warehouses</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        + New Transfer
      </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="card p-4 bg-gradient-to-br from-gray-50 to-gray-100">
        <p class="text-xs text-gray-500 uppercase">Total</p>
        <p class="text-2xl font-bold text-gray-700">{{ stats.total_transfers || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-yellow-50 to-amber-50">
        <p class="text-xs text-gray-500 uppercase">Pending</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-blue-50 to-indigo-50">
        <p class="text-xs text-gray-500 uppercase">In Transit</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.in_transit || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-green-50 to-emerald-50">
        <p class="text-xs text-gray-500 uppercase">Completed</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.completed || 0 }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-red-50 to-rose-50">
        <p class="text-xs text-gray-500 uppercase">Cancelled</p>
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
          placeholder="Search by code or product..."
          class="input" />
        <select v-model="filters.status" @change="loadTransfers" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="in_transit">In Transit</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.from_warehouse_id" @change="loadTransfers" class="input">
          <option value="">From Any Warehouse</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
        <select v-model="filters.to_warehouse_id" @change="loadTransfers" class="input">
          <option value="">To Any Warehouse</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
      </div>
    </div>

    <!-- Transfers Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Transfer History</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading transfers...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                {{ transfer.status.replace('_', ' ') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(transfer.created_at) }}
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="transfer.status === 'pending'" @click="startTransfer(transfer)" class="text-blue-600 hover:text-blue-900">Start</button>
              <button v-if="transfer.status === 'in_transit'" @click="completeTransfer(transfer)" class="text-green-600 hover:text-green-900">Complete</button>
              <button v-if="['pending', 'in_transit'].includes(transfer.status)" @click="cancelTransfer(transfer)" class="text-red-600 hover:text-red-900">Cancel</button>
              <button @click="viewTransfer(transfer)" class="text-indigo-600 hover:text-indigo-900">View</button>
            </td>
          </tr>
          <tr v-if="!transfers.data?.length">
            <td colspan="8" class="px-6 py-8 text-center text-gray-500">No transfers found</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="transfers.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ transfers.from }} to {{ transfers.to }} of {{ transfers.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadTransfers(transfers.current_page - 1)" :disabled="!transfers.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadTransfers(transfers.current_page + 1)" :disabled="!transfers.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Transfer Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-2xl font-bold mb-6">New Stock Transfer</h3>
        
        <form @submit.prevent="saveTransfer" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Warehouse *</label>
            <select v-model="form.from_warehouse_id" required @change="loadSourceStock" class="input">
              <option value="">Select source warehouse</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To Warehouse *</label>
            <select v-model="form.to_warehouse_id" required class="input">
              <option value="">Select destination warehouse</option>
              <option v-for="wh in availableDestinations" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
            <select v-model="form.product_id" required class="input">
              <option value="">Select product</option>
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.title }} (Stock: {{ p.quantity || 0 }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
            <input v-model.number="form.quantity" type="number" min="1" required class="input" placeholder="Quantity to transfer" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input" placeholder="Optional notes"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Creating...' : 'Create Transfer' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Transfer Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-2xl font-bold mb-6">Transfer Details</h3>
        
        <div class="space-y-4">
          <div class="flex justify-between">
            <span class="text-gray-500">Code:</span>
            <strong>{{ selectedTransfer?.transfer_code }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Product:</span>
            <strong>{{ selectedTransfer?.product?.title }}</strong>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">From:</span>
            <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">{{ selectedTransfer?.from_warehouse?.name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">To:</span>
            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">{{ selectedTransfer?.to_warehouse?.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Quantity:</span>
            <strong class="text-lg text-indigo-600">{{ selectedTransfer?.quantity }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Status:</span>
            <span :class="getStatusBadge(selectedTransfer?.status)" class="px-2 py-1 text-xs rounded-full">
              {{ selectedTransfer?.status?.replace('_', ' ') }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Created:</span>
            <span>{{ formatDate(selectedTransfer?.created_at) }}</span>
          </div>
          <div v-if="selectedTransfer?.transferred_at" class="flex justify-between">
            <span class="text-gray-500">Started:</span>
            <span>{{ formatDate(selectedTransfer?.transferred_at) }}</span>
          </div>
          <div v-if="selectedTransfer?.received_at" class="flex justify-between">
            <span class="text-gray-500">Completed:</span>
            <span>{{ formatDate(selectedTransfer?.received_at) }}</span>
          </div>
          <div v-if="selectedTransfer?.notes" class="pt-2 border-t">
            <p class="text-gray-500 text-sm">Notes:</p>
            <p class="text-gray-700">{{ selectedTransfer?.notes }}</p>
          </div>
        </div>

        <div class="flex justify-end mt-6">
          <button @click="showViewModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

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
    console.error('Failed to load transfers:', err);
  } finally {
    loading.value = false;
  }
};

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100, is_active: 1 } });
    warehouses.value = response.data.data || [];
  } catch (err) {
    console.error('Failed to load warehouses:', err);
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || [];
  } catch (err) {
    console.error('Failed to load products:', err);
  }
};

const loadStats = async () => {
  try {
    const response = await api.get('/stock-transfers/statistics');
    stats.value = response.data;
  } catch (err) {
    console.error('Failed to load statistics:', err);
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
        title: s.product?.title || `Product #${s.product_id}`,
        sku: s.product?.sku || '',
        quantity: s.quantity  // This is the warehouse stock
      }));
  } catch (err) {
    console.error('Failed to load source stock:', err);
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
    error.value = err.response?.data?.message || 'Failed to create transfer';
  } finally {
    saving.value = false;
  }
};

const startTransfer = async (transfer) => {
  if (!confirm('Start this transfer? Stock will be deducted from source warehouse.')) return;
  try {
    await api.post(`/stock-transfers/${transfer.id}/start`);
    loadTransfers();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to start transfer');
  }
};

const completeTransfer = async (transfer) => {
  if (!confirm('Complete this transfer? Stock will be added to destination warehouse.')) return;
  try {
    await api.post(`/stock-transfers/${transfer.id}/complete`);
    loadTransfers();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to complete transfer');
  }
};

const cancelTransfer = async (transfer) => {
  if (!confirm('Cancel this transfer?')) return;
  try {
    await api.post(`/stock-transfers/${transfer.id}/cancel`);
    loadTransfers();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to cancel transfer');
  }
};

const viewTransfer = async (transfer) => {
  try {
    const response = await api.get(`/stock-transfers/${transfer.id}`);
    selectedTransfer.value = response.data;
    showViewModal.value = true;
  } catch (err) {
    alert('Failed to load transfer details');
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
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
