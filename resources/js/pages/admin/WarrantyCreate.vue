<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
      <button @click="$router.push({ name: 'Warranties' })"
        class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <span class="text-xl">←</span>
      </button>
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
          {{ isEditing ? $t('pageTitles.WarrantyEdit') : $t('ui.addNewWarranty') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ isEditing ? $t('ui.updateWarrantyDetails') : $t('ui.registerProductWarranties') }}</p>
      </div>
    </div>

    <!-- Form Card -->
    <div class="card p-6">
      <form @submit.prevent="saveWarranty" class="space-y-6">

        <!-- Section: Sale Selection (Create mode) -->
        <div v-if="!isEditing">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('labels.sale') }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.sale') }}</label>
              <select v-model="form.sale_id" @change="loadSaleProducts" required class="input">
                <option value="">{{ $t('ui.selectSale') }}</option>
                <option v-for="sale in sales" :key="sale.id" :value="sale.id">{{ sale.invoice_number }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section: Products with Serial Numbers (Create mode - multi product) -->
        <div v-if="!isEditing && saleProducts.length > 0">
          <hr class="border-gray-100 mb-6" />
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
            {{ $t('ui.products2') }}{{ saleProducts.length }} {{ $t('ui.fromThisSaleSelectProductsToRegisterWarranty') }}
          </h3>
          <div class="space-y-3">
            <div v-for="item in saleProducts" :key="item.id"
              class="flex items-center gap-4 p-4 rounded-xl border transition-all duration-200"
              :class="isProductSelected(item.product_id) ? 'border-purple-300 bg-purple-50' : 'border-gray-200 bg-white hover:border-gray-300'">
              <!-- Checkbox -->
              <input type="checkbox" :value="item.product_id" v-model="selectedProductIds"
                class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
              <!-- Product Info -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ item.product?.title || item.product?.name }}</p>
                <p class="text-xs text-gray-500">{{ $t('ui.qty2') }} {{ item.quantity }}</p>
              </div>
              <!-- Serial Number Input (only if checked) -->
              <div v-if="isProductSelected(item.product_id)" class="w-64">
                <input
                  :value="getSerialNumber(item.product_id)"
                  @input="setSerialNumber(item.product_id, $event.target.value)"
                  type="text"
                  :placeholder="$t('ui.serialNumber')"
                  class="input text-sm" />
              </div>
            </div>
          </div>
          <p v-if="selectedProductIds.length === 0" class="text-sm text-amber-600 mt-2">
            {{ $t('ui.pilihMinimal1ProdukUntukMembuatWarranty') }}
          </p>
        </div>

        <!-- Section: Edit mode (single product) -->
        <div v-if="isEditing">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('ui.basicInformation') }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.warrantyCode') }}</label>
              <input v-model="form.warranty_code" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.serialNumber') }}</label>
              <input v-model="form.serial_number" type="text" :placeholder="$t('ui.enterSerialNumber')" class="input" />
            </div>
          </div>
        </div>

        <hr class="border-gray-100" />

        <!-- Section: Warranty Period -->
        <div>
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('ui.warrantyPeriod') }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.status') }}</label>
              <select v-model="form.status" required class="input">
                <option value="active">{{ $t('labels.active') }}</option>
                <option value="expired">{{ $t('labels.expired') }}</option>
                <option value="claimed">{{ $t('labels.claimed') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.startDate') }}</label>
              <input v-model="form.start_date" type="date" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.endDate2') }}</label>
              <input v-model="form.end_date" type="date" required class="input" />
            </div>
          </div>
        </div>

        <hr class="border-gray-100" />

        <!-- Section: Notes -->
        <div>
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('ui.additionalNotes2') }}</h3>
          <textarea v-model="form.notes" rows="4" :placeholder="$t('ui.addAnyAdditionalNotesAboutThisWarranty')" class="input"></textarea>
        </div>

        <!-- Error -->
        <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

        <!-- Actions -->
        <div class="flex justify-between items-center pt-4 border-t">
          <p v-if="!isEditing && selectedProductIds.length > 0" class="text-sm text-gray-500">
            {{ selectedProductIds.length }} {{ $t('ui.productsSelectedWillCreate2') }} {{ selectedProductIds.length }} {{ $t('ui.warranty') }}
          </p>
          <span v-else></span>
          <div class="flex space-x-3">
            <button type="button" @click="$router.push({ name: 'Warranties' })" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving || (!isEditing && selectedProductIds.length === 0)"
              class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium disabled:opacity-50">
              {{ saving ? $t('sales.saving') : (isEditing ? $t('ui.updateWarranty') : `Save ${selectedProductIds.length} Warranty`) }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const route = useRoute();

const sales = ref([]);
const saleProducts = ref([]);
const saving = ref(false);
const error = ref('');

// Multi-product selection for create mode
const selectedProductIds = ref([]);
const serialNumbers = ref({}); // { product_id: 'serial_number' }

const isEditing = computed(() => !!route.params.id);

const form = ref({
  warranty_code: '',
  serial_number: '',
  sale_id: '',
  product_id: '',
  start_date: new Date().toISOString().split('T')[0],
  end_date: '',
  status: 'active',
  notes: '',
});

const isProductSelected = (productId) => selectedProductIds.value.includes(productId);

const getSerialNumber = (productId) => serialNumbers.value[productId] || '';

const setSerialNumber = (productId, value) => {
  serialNumbers.value[productId] = value;
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
    selectedProductIds.value = [];
    serialNumbers.value = {};
    return;
  }

  try {
    const response = await api.get(`/sales/${form.value.sale_id}`);
    saleProducts.value = response.data.items || [];
    selectedProductIds.value = [];
    serialNumbers.value = {};
  } catch (err) {
    console.error('Error loading sale products:', err);
    saleProducts.value = [];
  }
};

const loadWarranty = async () => {
  if (!route.params.id) return;

  try {
    const response = await api.get(`/warranties/${route.params.id}`);
    const warranty = response.data;
    form.value = {
      warranty_code: warranty.warranty_code,
      serial_number: warranty.serial_number || '',
      sale_id: warranty.sale_id,
      product_id: warranty.product_id,
      start_date: warranty.start_date?.split('T')[0] || '',
      end_date: warranty.end_date?.split('T')[0] || '',
      status: warranty.status,
      notes: warranty.notes || '',
    };
  } catch (err) {
    console.error('Error loading warranty:', err);
    error.value = uiText("Gagal memuat data garansi");
  }
};

const saveWarranty = async () => {
  saving.value = true;
  error.value = '';

  try {
    if (isEditing.value) {
      // Edit mode: single warranty update
      await api.put(`/warranties/${route.params.id}`, form.value);
    } else {
      // Create mode: batch create
      const items = selectedProductIds.value.map(productId => ({
        product_id: productId,
        serial_number: serialNumbers.value[productId] || null,
      }));

      await api.post('/warranties/batch', {
        sale_id: form.value.sale_id,
        start_date: form.value.start_date,
        end_date: form.value.end_date,
        status: form.value.status,
        notes: form.value.notes || null,
        items,
      });
    }
    router.push({ name: 'Warranties' });
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal menyimpan garansi");
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadSales();
  if (isEditing.value) {
    await loadWarranty();
  }
});
</script>
