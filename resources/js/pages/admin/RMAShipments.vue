<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
          RMA Shipments
        </h2>
        <p class="text-sm text-gray-600 mt-1">Manage replacement product shipments for RMA</p>
      </div>
      <button @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        + New Shipment
      </button>
    </div>

    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadShipments" type="text"
          placeholder="Search by code, tracking, or recipient..." class="input" />
        <select v-model="filters.status" @change="loadShipments" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="shipped">Shipped</option>
          <option value="in_transit">In Transit</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Shipment Records</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shipment Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RMA Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tracking</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="shipment in shipments.data" :key="shipment.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ shipment.shipment_code }}</td>
            <td class="px-6 py-4 text-sm text-blue-600">{{ shipment.rma?.rma_code || '-' }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ shipment.product?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ shipment.quantity }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ shipment.recipient_name }}</div>
              <div class="text-xs text-gray-500">{{ shipment.recipient_phone }}</div>
            </td>
            <td class="px-6 py-4">
              <div v-if="shipment.tracking_number" class="text-sm">
                <div class="font-mono text-gray-900">{{ shipment.tracking_number }}</div>
                <div class="text-xs text-gray-500">{{ shipment.courier }}</div>
              </div>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(shipment.status)">{{ shipment.status }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="shipment.status === 'pending'" @click="openShipModal(shipment)"
                class="text-blue-600 hover:text-blue-900">Ship</button>
              <button v-if="shipment.status === 'shipped' || shipment.status === 'in_transit'"
                @click="markDelivered(shipment)" class="text-green-600 hover:text-green-900">Mark Delivered</button>
              <button @click="viewDetails(shipment)" class="text-gray-600 hover:text-gray-900">Details</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="shipments.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ shipments.from }} to {{ shipments.to }} of {{ shipments.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadShipments(shipments.current_page - 1)" :disabled="!shipments.prev_page_url"
            class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadShipments(shipments.current_page + 1)" :disabled="!shipments.next_page_url"
            class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">New Shipment</h3>

        <form @submit.prevent="saveShipment" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Select RMA *</label>
            <select v-model="form.rma_id" @change="onRMASelect" required class="input">
              <option value="">-- Select RMA --</option>
              <option v-for="rma in processedRMAs" :key="rma.id" :value="rma.id">
                {{ rma.rma_code }} - {{ rma.product?.title }} (Qty: {{ rma.quantity }})
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Only RMAs with resolution "replace" are shown</p>
          </div>

          <div v-if="form.rma_id" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
              <input :value="selectedRMA?.product?.title" disabled class="input bg-gray-100" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
              <input v-model.number="form.quantity" type="number" min="1" :max="selectedRMA?.quantity" required
                class="input" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Name *</label>
            <input v-model="form.recipient_name" required class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Phone *</label>
            <input v-model="form.recipient_phone" required class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Address *</label>
            <textarea v-model="form.recipient_address" required rows="3" class="input"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Courier (Optional)</label>
            <input v-model="form.courier" class="input" placeholder="e.g., JNE, Sicepat, Anteraja" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
              {{ saving ? 'Saving...' : 'Create Shipment' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showShipModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Ship Package</h3>
        <p class="text-sm text-gray-600 mb-4">Shipment: {{ selectedShipment?.shipment_code }}</p>

        <form @submit.prevent="confirmShip" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number *</label>
            <input v-model="shipForm.tracking_number" required class="input" placeholder="e.g., JNE123456789" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Courier *</label>
            <input v-model="shipForm.courier" required class="input" placeholder="e.g., JNE, Sicepat" />
          </div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showShipModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
              {{ saving ? 'Processing...' : 'Confirm Ship' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">Shipment Details</h3>
          <button @click="showDetailModal = false" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <div v-if="selectedShipment" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Shipment Code</p>
              <p class="font-semibold">{{ selectedShipment.shipment_code }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Status</p>
              <span :class="getStatusBadge(selectedShipment.status)">{{ selectedShipment.status }}</span>
            </div>
            <div>
              <p class="text-sm text-gray-500">RMA Code</p>
              <p class="font-semibold text-blue-600">{{ selectedShipment.rma?.rma_code }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Product</p>
              <p class="font-semibold">{{ selectedShipment.product?.title }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Quantity</p>
              <p class="font-semibold">{{ selectedShipment.quantity }}</p>
            </div>
            <div v-if="selectedShipment.tracking_number">
              <p class="text-sm text-gray-500">Tracking Number</p>
              <p class="font-mono font-semibold">{{ selectedShipment.tracking_number }}</p>
            </div>
            <div v-if="selectedShipment.courier">
              <p class="text-sm text-gray-500">Courier</p>
              <p class="font-semibold">{{ selectedShipment.courier }}</p>
            </div>
          </div>

          <div class="border-t pt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Recipient Information</p>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p class="font-semibold">{{ selectedShipment.recipient_name }}</p>
              <p class="text-sm text-gray-600">{{ selectedShipment.recipient_phone }}</p>
              <p class="text-sm text-gray-600 mt-2">{{ selectedShipment.recipient_address }}</p>
            </div>
          </div>

          <div v-if="selectedShipment.notes" class="border-t pt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Notes</p>
            <p class="text-sm text-gray-600">{{ selectedShipment.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const shipments = ref({ data: [] });
const processedRMAs = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showShipModal = ref(false);
const showDetailModal = ref(false);
const selectedShipment = ref(null);
const selectedRMA = ref(null);
const saving = ref(false);
const error = ref('');

const filters = ref({
  search: '',
  status: ''
});

const form = ref({
  rma_id: '',
  product_id: '',
  quantity: 1,
  recipient_name: '',
  recipient_address: '',
  recipient_phone: '',
  courier: '',
  notes: ''
});

const shipForm = ref({
  tracking_number: '',
  courier: ''
});

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    shipped: 'badge-info',
    in_transit: 'badge-primary',
    delivered: 'badge-success',
    cancelled: 'badge-danger'
  };
  return badges[status] || 'badge';
};

const loadShipments = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/rma-shipments', {
      params: { page, ...filters.value }
    });
    shipments.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadProcessedRMAs = async () => {
  try {
    const response = await api.get('/rmas', {
      params: { per_page: 100 }
    });
    processedRMAs.value = (response.data.data || []).filter(
      rma => rma.replacement_requested === true || rma.replacement_requested === 1
    );
  } catch (err) {
    console.error('Failed to load RMAs:', err);
  }
};

const onRMASelect = () => {
  if (form.value.rma_id) {
    selectedRMA.value = processedRMAs.value.find(r => r.id === form.value.rma_id);
    if (selectedRMA.value) {
      form.value.product_id = selectedRMA.value.product_id;
      form.value.quantity = selectedRMA.value.quantity;
      form.value.recipient_name = selectedRMA.value.customer_name || '';
      form.value.recipient_phone = selectedRMA.value.customer_contact || '';
    }
  } else {
    selectedRMA.value = null;
  }
};

const resetForm = () => {
  form.value = {
    rma_id: '',
    product_id: '',
    quantity: 1,
    recipient_name: '',
    recipient_address: '',
    recipient_phone: '',
    courier: '',
    notes: ''
  };
  selectedRMA.value = null;
  error.value = '';
};

const saveShipment = async () => {
  saving.value = true;
  error.value = '';

  try {
    await api.post('/rma-shipments', form.value);
    showModal.value = false;
    loadShipments();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create shipment';
  } finally {
    saving.value = false;
  }
};

const openShipModal = (shipment) => {
  selectedShipment.value = shipment;
  shipForm.value = {
    tracking_number: '',
    courier: shipment.courier || ''
  };
  showShipModal.value = true;
};

const confirmShip = async () => {
  saving.value = true;
  try {
    await api.post(`/rma-shipments/${selectedShipment.value.id}/ship`, shipForm.value);
    showShipModal.value = false;
    loadShipments();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to ship');
  } finally {
    saving.value = false;
  }
};

const markDelivered = async (shipment) => {
  if (!confirm('Mark this shipment as delivered?')) return;
  
  try {
    await api.post(`/rma-shipments/${shipment.id}/mark-delivered`);
    loadShipments();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to mark as delivered');
  }
};

const viewDetails = (shipment) => {
  selectedShipment.value = shipment;
  showDetailModal.value = true;
};

onMounted(() => {
  loadShipments();
  loadProcessedRMAs();
});
</script>
