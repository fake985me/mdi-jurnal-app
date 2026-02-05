<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">Lending Management</h2>
        <p class="text-sm text-gray-600 mt-1">Track borrowed items and returns</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + New Lending
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadLendings" type="text" placeholder="Search..." class="input" />
        <select v-model="filters.status" @change="loadLendings" class="input">
          <option value="">All Status</option>
          <option value="borrowed">Borrowed</option>
          <option value="returned">Returned</option>
        </select>
      </div>
    </div>

    <!-- Lendings Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Lending Records</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Borrower</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expected Return</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="lending in lendings.data" :key="lending.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ lending.lending_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ lending.product?.title }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ lending.borrower_name }}</div>
              <div class="text-xs text-gray-500">{{ lending.borrower_contact }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ lending.quantity }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(lending.expected_return_date) }}</td>
            <td class="px-6 py-4">
              <span :class="lending.status === 'borrowed' ? 'badge-warning' : 'badge-success'">
                {{ lending.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="lending.status === 'borrowed'" @click="processReturn(lending)" class="text-green-600 hover:text-green-900">Process Return</button>
              <button @click="deleteLending(lending.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="lendings.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ lendings.from }} to {{ lendings.to }} of {{ lendings.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadLendings(lendings.current_page - 1)" :disabled="!lendings.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadLendings(lendings.current_page + 1)" :disabled="!lendings.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
        <h3 class="text-2xl font-bold mb-6">New Lending</h3>
        
        <form @submit.prevent="saveLending" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Lending Code *</label>
              <input v-model="form.lending_code" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Product * ({{ products.length }} available)</label>
              <select v-model="form.product_id" required class="input">
                <option value="">Select Product</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.title || product.name }} (Stock: {{ product.stock || 0 }})
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Borrower Name *</label>
              <input v-model="form.borrower_name" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
              <input v-model="form.borrower_contact" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
              <input v-model.number="form.quantity" type="number" min="1" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Lending Date *</label>
              <input v-model="form.lending_date" type="date" required class="input" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Expected Return Date *</label>
              <input v-model="form.expected_return_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Create Lending' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Return Modal -->
    <div v-if="returnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-2xl font-bold mb-6">Process Return</h3>
        
        <form @submit.prevent="saveReturn" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Return Date *</label>
            <input v-model="returnForm.return_date" type="date" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Returned *</label>
            <input v-model.number="returnForm.quantity_returned" type="number" min="1" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Condition *</label>
            <select v-model="returnForm.condition" required class="input">
              <option value="good">Good</option>
              <option value="damaged">Damaged</option>
              <option value="lost">Lost</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="returnForm.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="returnModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Processing...' : 'Process Return' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const lendings = ref({ data: [] });
const products = ref([]);
const loading = ref(true);
const showModal = ref(false);
const returnModal = ref(false);
const saving = ref(false);
const error = ref('');
const selectedLending = ref(null);

const filters = ref({ search: '', status: '' });

const form = ref({
  lending_code: '',
  product_id: '',
  borrower_name: '',
  borrower_contact: '',
  quantity: 1,
  lending_date: new Date().toISOString().split('T')[0],
  expected_return_date: '',
  notes: '',
});

const returnForm = ref({
  return_date: new Date().toISOString().split('T')[0],
  quantity_returned: 1,
  condition: 'good',
  notes: '',
});

const formatDate = (date) => new Date(date).toLocaleDateString();

const loadLendings = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/lendings', { params: { page, ...filters.value } });
    lendings.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    console.log('Loading products for lending...');
    const response = await api.get('/products', { 
      params: { 
        per_page: 1000
      } 
    });
    console.log('Products response:', response.data);
    
    // Handle both paginated and non-paginated responses
    if (response.data.data) {
      products.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      products.value = response.data;
    } else {
      products.value = [];
    }
    
    console.log('Products loaded for lending:', products.value.length);
    console.log('First product with stock:', products.value[0]);
  } catch (err) {
    console.error('Error loading products:', err);
    products.value = [];
  }
};

const resetForm = () => {
  form.value = {
    lending_code: 'LND-' + Date.now(),
    product_id: '',
    borrower_name: '',
    borrower_contact: '',
    quantity: 1,
    lending_date: new Date().toISOString().split('T')[0],
    expected_return_date: '',
    notes: '',
  };
  error.value = '';
};

const saveLending = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/lendings', form.value);
    showModal.value = false;
    loadLendings();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save';
  } finally {
    saving.value = false;
  }
};

const processReturn = (lending) => {
  selectedLending.value = lending;
  returnForm.value.quantity_returned = lending.quantity;
  returnModal.value = true;
};

const saveReturn = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post(`/lendings/${selectedLending.value.id}/return`, returnForm.value);
    returnModal.value = false;
    loadLendings();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to process return';
  } finally {
    saving.value = false;
  }
};

const deleteLending = async (id) => {
  if (!confirm('Delete this lending?')) return;
  try {
    await api.delete(`/lendings/${id}`);
    loadLendings();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete');
  }
};

onMounted(() => {
  loadLendings();
  loadProducts();
});
</script>
