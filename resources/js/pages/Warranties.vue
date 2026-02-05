<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Warranty Management</h2>
        <p class="text-sm text-gray-600 mt-1">Track product warranties and claims</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + Add Warranty
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="filters.search"
          @input="loadWarranties"
          type="text"
          placeholder="Search by code or invoice..."
          class="input"
        />
        <select v-model="filters.status" @change="loadWarranties" class="input">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="claimed">Claimed</option>
        </select>
      </div>
    </div>

    <!-- Warranties Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Warranties</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading warranties...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="warranty in warranties.data" :key="warranty.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ warranty.warranty_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ warranty.product?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(warranty.start_date) }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(warranty.end_date) }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(warranty.status)">{{ warranty.status }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="editWarranty(warranty)" class="text-purple-600 hover:text-purple-900">Edit</button>
              <button @click="deleteWarranty(warranty.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="warranties.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ warranties.from }} to {{ warranties.to }} of {{ warranties.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadWarranties(warranties.current_page - 1)" :disabled="!warranties.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadWarranties(warranties.current_page + 1)" :disabled="!warranties.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
        <h3 class="text-2xl font-bold mb-6">{{ editingWarranty ? 'Edit Warranty' : 'Add New Warranty' }}</h3>
        
        <form @submit.prevent="saveWarranty" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Warranty Code *</label>
              <input v-model="form.warranty_code" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sale *</label>
              <select v-model="form.sale_id" @change="loadSaleProducts" required class="input">
                <option value="">Select Sale</option>
                <option v-for="sale in sales" :key="sale.id" :value="sale.id">{{ sale.invoice_number }}</option>
              </select>
            </div>
            
            <!-- Products from selected sale -->
            <div v-if="saleProducts.length > 0" class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Product * ({{ saleProducts.length }} from this sale)</label>
              <select v-model="form.product_id" required class="input">
                <option value="">Select Product</option>
                <option v-for="item in saleProducts" :key="item.id" :value="item.product_id">
                  {{ item.product?.title || item.product?.name }} (Qty: {{ item.quantity }})
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
              <select v-model="form.status" required class="input">
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="claimed">Claimed</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
              <input v-model="form.start_date" type="date" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
              <input v-model="form.end_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const warranties = ref({ data: [] });
const products = ref([]);
const sales = ref([]);
const saleProducts = ref([]); // Products from selected sale
const loading = ref(true);
const showModal = ref(false);
const editingWarranty = ref(null);
const saving = ref(false);
const error = ref('');

const filters = ref({ search: '', status: '' });

const form = ref({
  warranty_code: '',
  sale_id: '',
  product_id: '',
  start_date: '',
  end_date: '',
  status: 'active',
  notes: '',
});

const formatDate = (date) => new Date(date).toLocaleDateString();

const getStatusBadge = (status) => {
  const badges = {
    active: 'badge-success',
    expired: 'badge-danger',
    claimed: 'badge-warning',
  };
  return badges[status] || 'badge';
};

const loadWarranties = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warranties', { params: { page, ...filters.value } });
    warranties.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    console.log('Loading products...');
    const response = await api.get('/products', { params: { per_page: 1000 } });
    console.log('Products response:', response.data);
    
    // Handle both paginated and non-paginated responses
    if (response.data.data) {
      products.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      products.value = response.data;
    } else {
      products.value = [];
    }
    
    console.log('Products loaded:', products.value.length);
    console.log('First product:', products.value[0]);
  } catch (err) {
    console.error('Error loading products:', err);
    console.error('Error details:', err.response?.data);
  }
};

const loadSales = async () => {
  try {
    const response = await api.get('/sales', { params: { per_page: 1000 } });
    sales.value = response.data.data;
  } catch (err) {
    console.error(err);
  }
};

const loadSaleProducts = async () => {
  if (!form.value.sale_id) {
    saleProducts.value = [];
    form.value.product_id = '';
    return;
  }
  
  try {
    const response = await api.get(`/sales/${form.value.sale_id}`);
    saleProducts.value = response.data.items || [];
    console.log('Sale products loaded:', saleProducts.value);
    // Reset product selection when sale changes
    form.value.product_id = '';
  } catch (err) {
    console.error('Error loading sale products:', err);
    saleProducts.value = [];
  }
};

const resetForm = () => {
  form.value = {
    warranty_code: 'WRN-' + Date.now(),
    sale_id: '',
    product_id: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    status: 'active',
    notes: '',
  };
  saleProducts.value = [];
  editingWarranty.value = null;
  error.value = '';
};

const editWarranty = (warranty) => {
  editingWarranty.value = warranty;
  form.value = {
    warranty_code: warranty.warranty_code,
    sale_id: warranty.sale_id,
    product_id: warranty.product_id,
    start_date: warranty.start_date,
    end_date: warranty.end_date,
    status: warranty.status,
    notes: warranty.notes,
  };
  showModal.value = true;
};

const saveWarranty = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    if (editingWarranty.value) {
      await api.put(`/warranties/${editingWarranty.value.id}`, form.value);
    } else {
      await api.post('/warranties', form.value);
    }
    showModal.value = false;
    loadWarranties();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save warranty';
  } finally {
    saving.value = false;
  }
};

const deleteWarranty = async (id) => {
  if (!confirm('Delete this warranty?')) return;
  try {
    await api.delete(`/warranties/${id}`);
    loadWarranties();
  } catch (err) {
    alert('Failed to delete');
  }
};

onMounted(() => {
  loadWarranties();
  loadSales();
});
</script>
