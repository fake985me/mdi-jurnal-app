<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
          Supplier Management
        </h2>
        <p class="text-sm text-gray-600 mt-1">Manage supplier contacts and purchase relationships</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-lg hover:from-amber-700 hover:to-orange-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + Add Supplier
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card p-4 bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200">
        <p class="text-sm text-amber-600 font-medium">Total Suppliers</p>
        <p class="text-2xl font-bold text-amber-800">{{ summary.total_suppliers }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-green-50 to-green-100 border border-green-200">
        <p class="text-sm text-green-600 font-medium">Active</p>
        <p class="text-2xl font-bold text-green-800">{{ summary.active_suppliers }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200">
        <p class="text-sm text-blue-600 font-medium">Companies</p>
        <p class="text-2xl font-bold text-blue-800">{{ summary.companies }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200">
        <p class="text-sm text-purple-600 font-medium">Individuals</p>
        <p class="text-2xl font-bold text-purple-800">{{ summary.individuals }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          @input="debouncedLoad"
          type="text"
          placeholder="Search name, company, phone, code..."
          class="input col-span-2"
        />
        <select v-model="filters.type" @change="loadSuppliers" class="input">
          <option value="">All Types</option>
          <option value="company">Company</option>
          <option value="individual">Individual</option>
        </select>
        <select v-model="filters.is_active" @change="loadSuppliers" class="input">
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Suppliers Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Suppliers</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="inline-flex items-center space-x-2 text-gray-500">
          <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span>Loading suppliers...</span>
        </div>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name / Company</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NPWP</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Purchases</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="supplier in suppliers.data" :key="supplier.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm font-mono text-amber-700">{{ supplier.supplier_code }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ supplier.company || supplier.name }}</div>
              <div v-if="supplier.company" class="text-xs text-gray-500">{{ supplier.name }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">{{ supplier.phone || '—' }}</div>
              <div class="text-xs text-gray-500">{{ supplier.email || '' }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ supplier.npwp || '—' }}</td>
            <td class="px-6 py-4">
              <div v-if="supplier.bank_name" class="text-sm text-gray-700">
                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-medium">{{ supplier.bank_name }}</span>
                <div class="text-xs text-gray-500 mt-1">{{ supplier.bank_account_number }}</div>
              </div>
              <span v-else class="text-gray-400 text-sm">—</span>
            </td>
            <td class="px-6 py-4">
              <span
                :class="supplier.type === 'company' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                class="text-xs font-medium px-2 py-1 rounded-full capitalize"
              >
                {{ supplier.type }}
              </span>
            </td>
            <td class="px-6 py-4 text-center text-sm font-semibold text-gray-700">{{ supplier.purchases_count || 0 }}</td>
            <td class="px-6 py-4">
              <span
                :class="supplier.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                class="text-xs font-medium px-2 py-1 rounded-full"
              >
                {{ supplier.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="viewSupplier(supplier)" class="text-amber-600 hover:text-amber-900 font-medium">View</button>
              <button @click="editSupplier(supplier)" class="text-blue-600 hover:text-blue-900 font-medium">Edit</button>
              <button @click="deleteSupplier(supplier)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
            </td>
          </tr>
          <tr v-if="!suppliers.data?.length && !loading">
            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
              <div class="flex flex-col items-center space-y-2">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="font-medium">No suppliers found</p>
                <p class="text-sm">Create your first supplier to get started</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="suppliers.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
        <p class="text-sm text-gray-700">Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }} suppliers</p>
        <div class="flex space-x-2">
          <button
            @click="loadSuppliers(suppliers.current_page - 1)"
            :disabled="!suppliers.prev_page_url"
            class="btn-secondary disabled:opacity-50"
          >Previous</button>
          <button
            @click="loadSuppliers(suppliers.current_page + 1)"
            :disabled="!suppliers.next_page_url"
            class="btn-secondary disabled:opacity-50"
          >Next</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="text-2xl font-bold mb-6 bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
          {{ isEditing ? 'Edit Supplier' : 'Add New Supplier' }}
        </h3>

        <form @submit.prevent="saveSupplier" class="space-y-6">
          <!-- Basic Information -->
          <div class="p-4 bg-amber-50 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-3">Basic Information</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name *</label>
                <input v-model="form.name" required class="input" placeholder="Contact person name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                <input v-model="form.company" class="input" placeholder="Company name" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select v-model="form.type" class="input">
                  <option value="company">Company</option>
                  <option value="individual">Individual</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                <input v-model="form.npwp" class="input" placeholder="Tax number (NPWP)" />
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-3">Contact Information</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input v-model="form.phone" class="input" placeholder="Primary phone" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alt. Phone</label>
                <input v-model="form.phone_alt" class="input" placeholder="Alternative phone" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="form.email" type="email" class="input" placeholder="email@company.com" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <input v-model="form.city" class="input" placeholder="City" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                <input v-model="form.province" class="input" placeholder="Province" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                <input v-model="form.postal_code" class="input" placeholder="Postal code" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="form.address" rows="2" class="input" placeholder="Full address"></textarea>
              </div>
            </div>
          </div>

          <!-- Bank Information -->
          <div class="p-4 bg-indigo-50 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-3">Bank Information</h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                <input v-model="form.bank_name" class="input" placeholder="BCA, Mandiri, etc." />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                <input v-model="form.bank_account_number" class="input" placeholder="Account number" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                <input v-model="form.bank_account_name" class="input" placeholder="Account holder name" />
              </div>
            </div>
          </div>

          <!-- Notes & Status -->
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="form.notes" rows="2" class="input" placeholder="Additional notes..."></textarea>
            </div>
            <div v-if="isEditing">
              <label class="flex items-center space-x-2 pt-2">
                <input v-model="form.is_active" type="checkbox" class="w-5 h-5 text-amber-600 rounded border-gray-300" />
                <span class="text-sm font-medium text-gray-700">Active</span>
              </label>
            </div>
          </div>

          <div v-if="formError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ formError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="px-6 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-lg hover:from-amber-700 hover:to-orange-700 transition-all duration-200 shadow-md font-medium disabled:opacity-50">
              {{ saving ? 'Saving...' : (isEditing ? 'Update Supplier' : 'Add Supplier') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ selectedSupplier?.company || selectedSupplier?.name }}</h3>
            <p class="text-sm text-gray-500 font-mono">{{ selectedSupplier?.supplier_code }}</p>
          </div>
          <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 font-medium">Contact Name</p>
            <p class="text-sm font-semibold text-gray-900">{{ selectedSupplier?.name || '—' }}</p>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 font-medium">Phone</p>
            <p class="text-sm font-semibold text-gray-900">{{ selectedSupplier?.phone || '—' }}</p>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 font-medium">Email</p>
            <p class="text-sm font-semibold text-gray-900">{{ selectedSupplier?.email || '—' }}</p>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 font-medium">NPWP</p>
            <p class="text-sm font-semibold text-gray-900">{{ selectedSupplier?.npwp || '—' }}</p>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg col-span-2">
            <p class="text-xs text-gray-500 font-medium">Address</p>
            <p class="text-sm font-semibold text-gray-900">
              {{ [selectedSupplier?.address, selectedSupplier?.city, selectedSupplier?.province, selectedSupplier?.postal_code].filter(Boolean).join(', ') || '—' }}
            </p>
          </div>
          <div v-if="selectedSupplier?.bank_name" class="p-3 bg-indigo-50 rounded-lg col-span-2">
            <p class="text-xs text-indigo-600 font-medium">Bank Information</p>
            <p class="text-sm font-semibold text-gray-900">{{ selectedSupplier?.bank_name }} — {{ selectedSupplier?.bank_account_number }}</p>
            <p class="text-xs text-gray-600">a/n {{ selectedSupplier?.bank_account_name }}</p>
          </div>
        </div>

        <!-- Recent Purchases -->
        <div v-if="selectedSupplier?.recent_purchases?.length">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Recent Purchases</h4>
          <div class="space-y-2">
            <div v-for="purchase in selectedSupplier.recent_purchases" :key="purchase.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div>
                <span class="text-sm font-medium text-gray-900">{{ purchase.po_number }}</span>
                <span class="text-xs text-gray-500 ml-2">{{ formatDate(purchase.order_date) }}</span>
              </div>
              <div class="text-right">
                <span class="text-sm font-bold text-gray-900">{{ formatCurrency(purchase.total_amount) }}</span>
                <span :class="getStatusBadge(purchase.status)" class="text-xs font-medium px-2 py-0.5 rounded-full ml-2">
                  {{ purchase.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center text-gray-400 text-sm py-4">No purchase history</div>

        <div v-if="selectedSupplier?.notes" class="mt-4 p-3 bg-yellow-50 rounded-lg">
          <p class="text-xs text-yellow-600 font-medium">Notes</p>
          <p class="text-sm text-gray-700">{{ selectedSupplier.notes }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const suppliers = ref({ data: [] });
const summary = ref({ total_suppliers: 0, active_suppliers: 0, companies: 0, individuals: 0 });
const loading = ref(true);
const showModal = ref(false);
const showDetailModal = ref(false);
const saving = ref(false);
const formError = ref('');
const isEditing = ref(false);
const editingId = ref(null);
const selectedSupplier = ref(null);

const filters = ref({
  search: '',
  type: '',
  is_active: '',
});

const defaultForm = () => ({
  name: '',
  company: '',
  email: '',
  phone: '',
  phone_alt: '',
  address: '',
  city: '',
  province: '',
  postal_code: '',
  npwp: '',
  type: 'company',
  bank_name: '',
  bank_account_number: '',
  bank_account_name: '',
  is_active: true,
  notes: '',
});

const form = ref(defaultForm());

let debounceTimer = null;
const debouncedLoad = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadSuppliers(), 300);
};

const formatCurrency = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
const formatDate = (date) => date ? new Date(date).toLocaleDateString('id-ID') : '—';

const getStatusBadge = (status) => {
  const badges = {
    unpaid: 'bg-red-100 text-red-700',
    paid: 'bg-green-100 text-green-700',
    partial: 'bg-yellow-100 text-yellow-700',
    cancelled: 'bg-gray-100 text-gray-500',
  };
  return badges[status] || 'bg-gray-100 text-gray-700';
};

const loadSuppliers = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page, ...filters.value };
    const response = await api.get('/suppliers', { params });
    suppliers.value = response.data;
  } catch (err) {
    console.error('Failed to load suppliers:', err);
  } finally {
    loading.value = false;
  }
};

const loadSummary = async () => {
  try {
    const response = await api.get('/suppliers/summary');
    summary.value = response.data;
  } catch (err) {
    console.error('Failed to load summary:', err);
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value = defaultForm();
  formError.value = '';
  showModal.value = true;
};

const editSupplier = (supplier) => {
  isEditing.value = true;
  editingId.value = supplier.id;
  form.value = {
    name: supplier.name || '',
    company: supplier.company || '',
    email: supplier.email || '',
    phone: supplier.phone || '',
    phone_alt: supplier.phone_alt || '',
    address: supplier.address || '',
    city: supplier.city || '',
    province: supplier.province || '',
    postal_code: supplier.postal_code || '',
    npwp: supplier.npwp || '',
    type: supplier.type || 'company',
    bank_name: supplier.bank_name || '',
    bank_account_number: supplier.bank_account_number || '',
    bank_account_name: supplier.bank_account_name || '',
    is_active: supplier.is_active ?? true,
    notes: supplier.notes || '',
  };
  formError.value = '';
  showModal.value = true;
};

const viewSupplier = async (supplier) => {
  try {
    const response = await api.get(`/suppliers/${supplier.id}`);
    selectedSupplier.value = response.data;
    showDetailModal.value = true;
  } catch (err) {
    console.error('Failed to load supplier details:', err);
  }
};

const saveSupplier = async () => {
  saving.value = true;
  formError.value = '';
  try {
    if (isEditing.value) {
      await api.put(`/suppliers/${editingId.value}`, form.value);
    } else {
      await api.post('/suppliers', form.value);
    }
    showModal.value = false;
    loadSuppliers(suppliers.value.current_page || 1);
    loadSummary();
  } catch (err) {
    formError.value = err.response?.data?.message || 'Failed to save supplier';
  } finally {
    saving.value = false;
  }
};

const deleteSupplier = async (supplier) => {
  if (!confirm(`Delete supplier "${supplier.company || supplier.name}"? This cannot be undone.`)) return;
  try {
    await api.delete(`/suppliers/${supplier.id}`);
    loadSuppliers(suppliers.value.current_page || 1);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete supplier');
  }
};

onMounted(() => {
  loadSuppliers();
  loadSummary();
});
</script>
