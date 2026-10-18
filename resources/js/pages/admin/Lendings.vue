<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">{{ $t('pageTitles.Lendings') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.trackBorrowedItemsAndReturns') }}</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        {{ $t('ui.newLending') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadLendings" type="text" :placeholder="$t('ui.search')" class="input" />
        <select v-model="filters.status" @change="loadLendings" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="borrowed">{{ $t('labels.borrowed') }}</option>
          <option value="returned">{{ $t('labels.returned') }}</option>
        </select>
      </div>
    </div>

    <!-- Lendings Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.lendingRecords') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('common.loading') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.code') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.borrower') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.quantity') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.expectedReturn') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
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
            <td class="px-6 py-4 text-sm text-gray-900">{{ lending.quantity }}<div class="text-xs text-gray-500">{{ $t('ui.remaining2') }} {{ lending.remaining_quantity }}</div></td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(lending.expected_return_date) }}</td>
            <td class="px-6 py-4">
              <span :class="lending.status === 'borrowed' ? 'badge-warning' : 'badge-success'">
                {{ $label(lending.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="lending.status === 'borrowed'" @click="processReturn(lending)" class="text-green-600 hover:text-green-900">{{ $t('ui.processReturn') }}</button>
              <button @click="deleteLending(lending.id)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="lendings.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ lendings.from }} {{ $t('ui.to') }} {{ lendings.to }} {{ $t('sales.of') }} {{ lendings.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadLendings(lendings.current_page - 1)" :disabled="!lendings.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadLendings(lendings.current_page + 1)" :disabled="!lendings.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.newLending2') }}</h3>
        
        <form @submit.prevent="saveLending" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.lendingCode') }}</label>
              <input v-model="form.lending_code" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.product') }}{{ products.length }} {{ $t('ui.available') }}</label>
              <select v-model="form.product_id" required class="input">
                <option value="">{{ $t('sales.selectProduct') }}</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.title || product.name }} {{ $t('ui.stock') }} {{ product.stock || 0 }})
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.borrowerName') }}</label>
              <input v-model="form.borrower_name" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.contact') }}</label>
              <input v-model="form.borrower_contact" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantity') }}</label>
              <input v-model.number="form.quantity" type="number" min="1" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.lendingDate') }}</label>
              <input v-model="form.lending_date" type="date" required class="input" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.expectedReturnDate') }}</label>
              <input v-model="form.expected_return_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('ui.createLending') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Return Modal -->
    <div v-if="returnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.processReturn') }}</h3>
        
        <form @submit.prevent="saveReturn" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.returnDate') }}</label>
            <input v-model="returnForm.return_date" type="date" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantityReturned') }}</label>
            <input v-model.number="returnForm.quantity_returned" type="number" min="1" :max="selectedLending?.remaining_quantity" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.condition2') }}</label>
            <select v-model="returnForm.condition" required class="input">
              <option value="good">{{ $t('labels.good') }}</option>
              <option value="damaged">{{ $t('labels.defective') }}</option>
              <option value="lost">{{ $t('labels.lost') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="returnForm.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="returnModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('ui.processing') : $t('ui.processReturn') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import api from '../../services/api';

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
    console.log('Memuat produk untuk peminjaman...');
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
    error.value = err.response?.data?.message || uiText("Gagal menyimpan");
  } finally {
    saving.value = false;
  }
};

const processReturn = (lending) => {
  selectedLending.value = lending;
  returnForm.value.quantity_returned = lending.remaining_quantity ?? lending.quantity;
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
    error.value = err.response?.data?.message || uiText("Gagal memproses pengembalian");
  } finally {
    saving.value = false;
  }
};

const deleteLending = async (id) => {
  if (!confirm(uiText("Hapus peminjaman ini?"))) return;
  try {
    await api.delete(`/lendings/${id}`);
    loadLendings();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus"));
  }
};

onMounted(() => {
  loadLendings();
  loadProducts();
});
</script>
