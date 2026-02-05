<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent">RMA Management</h2>
        <p class="text-sm text-gray-600 mt-1">Manage product returns and warranty claims</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        + New RMA
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input v-model="filters.search" @input="loadRMAs" type="text" placeholder="Search by code or customer..." class="input" />
        <select v-model="filters.status" @change="loadRMAs" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="received">Received</option>
          <option value="processed">Processed</option>
          <option value="completed">Completed</option>
        </select>
        <select v-model="filters.reason" @change="loadRMAs" class="input">
          <option value="">All Reasons</option>
          <option value="warranty_claim">Warranty Claim</option>
          <option value="damaged_shipment">Damaged Shipment</option>
          <option value="defective">Defective</option>
          <option value="dead_on_arrival">DOA</option>
        </select>
      </div>
    </div>

    <!-- RMAs Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">RMA Records</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RMA Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="rma in rmas.data" :key="rma.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ rma.rma_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ rma.product?.title }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ rma.customer_name }}</div>
              <div class="text-xs text-gray-500">{{ rma.customer_contact }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ rma.quantity }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <span class="capitalize">{{ formatReason(rma.reason) }}</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span v-if="rma.condition" :class="getConditionBadge(rma.condition)" class="capitalize">{{ rma.condition.replace('_', ' ') }}</span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(rma.status)">{{ rma.status }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="rma.status === 'pending'" @click="approveRMA(rma)" class="text-green-600 hover:text-green-900">Approve</button>
              <button v-if="rma.status === 'approved'" @click="markReceived(rma)" class="text-blue-600 hover:text-blue-900">Received</button>
              <button @click="deleteRMA(rma.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="rmas.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ rmas.from }} to {{ rmas.to }} of {{ rmas.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadRMAs(rmas.current_page - 1)" :disabled="!rmas.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadRMAs(rmas.current_page + 1)" :disabled="!rmas.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">New RMA</h3>
        
        <form @submit.prevent="saveRMA" class="space-y-4">
          <!-- Step 1: Select Sale -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Sale with Warranty/MSA *</label>
            <select v-model="form.sale_id" @change="onSaleSelect" required class="input">
              <option value="">-- Select a Sale --</option>
              <option v-for="sale in sales" :key="sale.id" :value="sale.id">
                {{ sale.invoice_number }} - {{ sale.customer_name }} ({{ formatDate(sale.sale_date) }})
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Only sales with active warranty or MSA are shown</p>
          </div>

          <!-- Eligibility Status -->
          <div v-if="eligibility.checked" :class="eligibility.valid ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'" class="p-3 rounded-lg text-sm">
            <span v-if="eligibility.valid">✓ {{ eligibility.reason }}</span>
            <span v-else>✗ {{ eligibility.reason }}</span>
          </div>

          <!-- Step 2: Select Product from Sale Items (when sale selected) -->
          <div v-if="form.sale_id && saleItems.length" class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Product *</label>
            <div class="space-y-2">
              <label v-for="item in saleItems" :key="item.id" 
                class="flex items-center p-3 border rounded-lg cursor-pointer"
                :class="form.product_id === item.product_id ? 'bg-white border-blue-500' : 'hover:bg-gray-100'">
                <input type="radio" v-model="form.product_id" :value="item.product_id" @change="checkEligibility" class="mr-3" />
                <div>
                  <span class="font-medium">{{ item.product?.title }}</span>
                  <span class="text-sm text-gray-500 ml-2">(Qty: {{ item.quantity }})</span>
                </div>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
              <input v-model="form.customer_name" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Customer Contact</label>
              <input v-model="form.customer_contact" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
              <input v-model.number="form.quantity" type="number" min="1" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
              <select v-model="form.reason" required class="input">
                <option value="">Select Reason</option>
                <option value="warranty_claim">Warranty Claim</option>
                <option value="damaged_shipment">Damaged Shipment</option>
                <option value="defective">Defective</option>
                <option value="dead_on_arrival">Dead on Arrival (DOA)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date *</label>
              <input v-model="form.issue_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving || !eligibility.valid" class="btn-primary">{{ saving ? 'Saving...' : 'Create RMA' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Condition Modal -->
    <div v-if="showConditionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Select Item Condition</h3>
        <p class="text-sm text-gray-600 mb-4">RMA: {{ selectedRMA?.rma_code }}</p>
        
        <div class="space-y-3">
          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="conditionForm.condition === 'working' ? 'border-green-500 bg-green-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="working" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">✅ Working / Functional</p>
              <p class="text-sm text-gray-500">Item is in good working condition</p>
            </div>
          </label>
          
          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="conditionForm.condition === 'damaged' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="damaged" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">⚠️ Damaged</p>
              <p class="text-sm text-gray-500">Has physical damage but may be repairable</p>
            </div>
          </label>
          
          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="conditionForm.condition === 'broken' ? 'border-red-500 bg-red-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="broken" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">❌ Broken / Not Working</p>
              <p class="text-sm text-gray-500">Not functioning, needs repair or replacement</p>
            </div>
          </label>
          
          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="conditionForm.condition === 'parts_only' ? 'border-gray-500 bg-gray-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="parts_only" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">🔧 Parts Only</p>
              <p class="text-sm text-gray-500">Can only be used for spare parts</p>
            </div>
          </label>

          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="conditionForm.condition === 'other' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="other" class="mr-3" />
            <div class="flex-1">
              <p class="font-medium text-gray-900">✏️ Other / Custom</p>
              <p class="text-sm text-gray-500">Describe condition manually</p>
            </div>
          </label>

          <!-- Custom condition input -->
          <div v-if="conditionForm.condition === 'other'" class="ml-8 mt-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Describe Condition *</label>
            <textarea 
              v-model="conditionForm.customCondition" 
              rows="3" 
              class="input w-full" 
              placeholder="e.g., Screen cracked, battery swollen, etc."
              required></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
          <button type="button" @click="showConditionModal = false" class="btn-secondary">Cancel</button>
          <button @click="confirmReceived" :disabled="!canConfirmReceived || saving" class="btn-primary">
            {{ saving ? 'Processing...' : 'Confirm Received' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const rmas = ref({ data: [] });
const sales = ref([]);
const saleItems = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showConditionModal = ref(false);
const selectedRMA = ref(null);
const saving = ref(false);
const error = ref('');
const eligibility = ref({ checked: false, valid: false, reason: '' });

const filters = ref({ search: '', status: '', reason: '' });

const conditionForm = ref({
  condition: '',
  customCondition: ''
});

const form = ref({
  sale_id: '',
  product_id: '',
  customer_name: '',
  customer_contact: '',
  quantity: 1,
  reason: '',
  issue_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID');
};

const formatReason = (reason) => {
  return reason.replace(/_/g, ' ');
};

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    approved: 'badge-info',
    rejected: 'badge-danger',
    received: 'badge-primary',
    processed: 'badge-success',
    completed: 'badge-success',
  };
  return badges[status] || 'badge';
};

const getConditionBadge = (condition) => {
  const badges = {
    working: 'badge-success',
    damaged: 'badge-warning',
    broken: 'badge-danger',
    parts_only: 'badge',
    other: 'badge-info',
  };
  return badges[condition] || 'badge';
};

// Validation for confirm button
const canConfirmReceived = computed(() => {
  if (!conditionForm.value.condition) return false;
  if (conditionForm.value.condition === 'other') {
    return conditionForm.value.customCondition && conditionForm.value.customCondition.trim().length > 0;
  }
  return true;
});

const loadRMAs = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/rmas', { params: { page, ...filters.value } });
    rmas.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || [];
  } catch (err) {
    console.error(err);
  }
};

const loadSalesWithWarranty = async () => {
  try {
    const response = await api.get('/rmas/sales-with-warranty');
    sales.value = response.data || [];
  } catch (err) {
    console.error('Failed to load sales:', err);
  }
};

const onSaleSelect = () => {
  form.value.product_id = '';
  eligibility.value = { checked: false, valid: false, reason: '' };
  
  if (form.value.sale_id) {
    const sale = sales.value.find(s => s.id === parseInt(form.value.sale_id));
    if (sale) {
      saleItems.value = sale.items || [];
      form.value.customer_name = sale.customer_name || '';
      form.value.customer_contact = sale.customer_phone || sale.customer_email || '';
    }
  } else {
    saleItems.value = [];
  }
};

const checkEligibility = async () => {
  if (!form.value.sale_id || !form.value.product_id) {
    eligibility.value = { checked: false, valid: false, reason: '' };
    return;
  }
  
  try {
    const response = await api.post('/rmas/check-eligibility', {
      sale_id: form.value.sale_id,
      product_id: form.value.product_id,
    });
    eligibility.value = { checked: true, ...response.data };
  } catch (err) {
    eligibility.value = { checked: true, valid: false, reason: 'Failed to check eligibility' };
  }
};

const resetForm = () => {
  form.value = {
    sale_id: '',
    product_id: '',
    customer_name: '',
    customer_contact: '',
    quantity: 1,
    reason: '',
    issue_date: new Date().toISOString().split('T')[0],
    notes: '',
  };
  saleItems.value = [];
  eligibility.value = { checked: false, valid: false, reason: '' };
  error.value = '';
};

const saveRMA = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/rmas', form.value);
    showModal.value = false;
    loadRMAs();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save RMA';
  } finally {
    saving.value = false;
  }
};

const approveRMA = async (rma) => {
  if (!confirm('Approve this RMA?')) return;
  try {
    await api.put(`/rmas/${rma.id}`, { status: 'approved' });
    loadRMAs();
  } catch (err) {
    alert('Failed to approve RMA');
  }
};

const markReceived = (rma) => {
  selectedRMA.value = rma;
  conditionForm.value.condition = ''; // Reset
  conditionForm.value.customCondition = ''; // Reset custom input
  showConditionModal.value = true;
};

const confirmReceived = async () => {
  saving.value = true;
  try {
    // Determine final condition value
    const finalCondition = conditionForm.value.condition === 'other'
      ? conditionForm.value.customCondition
      : conditionForm.value.condition;
      
    await api.post(`/rmas/${selectedRMA.value.id}/mark-received`, {
      condition: finalCondition
    });
    showConditionModal.value = false;
    loadRMAs();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to mark as received');
  } finally {
    saving.value = false;
  }
};

const deleteRMA = async (id) => {
  if (!confirm('Delete this RMA?')) return;
  try {
    await api.delete(`/rmas/${id}`);
    loadRMAs();
  } catch (err) {
    alert('Failed to delete');
  }
};

onMounted(() => {
  loadRMAs();
  loadSalesWithWarranty();
});
</script>
