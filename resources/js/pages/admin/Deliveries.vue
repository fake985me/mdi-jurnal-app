<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">{{ $t('deliveries.title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('deliveries.subtitle') }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="filters.search" @input="loadDeliveries" type="text" placeholder="Search by invoice or customer..." class="input" />
      </div>
    </div>

    <!-- Deliveries Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('deliveries.allDeliveries') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('deliveries.loadingDeliveries') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.invoice') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.customer') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.saleDate') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.total') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.courier') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.trackingNumber') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.deliveryStatus') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('deliveries.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="delivery in deliveries.data" :key="delivery.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ delivery.sale?.invoice_number }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ delivery.sale?.customer_name }}</div>
              <div class="text-sm text-gray-500">{{ delivery.sale?.customer_phone }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(delivery.sale?.sale_date) }}</td>
            <td class="px-6 py-4 text-sm font-bold text-gray-900">Rp {{ formatPrice(delivery.sale?.total_amount) }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">
              <span v-if="delivery.courier" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ delivery.courier }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">
              <div v-if="delivery.tracking_number" class="flex items-center gap-2">
                <code class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">{{ delivery.tracking_number }}</code>
                <button @click="copyTrackingNumber(delivery.tracking_number)" class="text-gray-400 hover:text-gray-600" title="Copy">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4">
              <span :class="getDeliveryBadge(delivery.status)">{{ formatStatus(delivery.status) }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="updateDeliveryStatus(delivery)" class="text-blue-600 hover:text-blue-900">{{ $t('deliveries.updateStatus') }}</button>
              <button @click="openSerialModal(delivery)" class="text-purple-600 hover:text-purple-900">Serial</button>
              <button @click="viewSale(delivery.sale)" class="text-indigo-600 hover:text-indigo-900">{{ $t('deliveries.view') }}</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="deliveries.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('deliveries.showing') }} {{ deliveries.from }} - {{ deliveries.to }} {{ $t('deliveries.of') }} {{ deliveries.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadDeliveries(deliveries.current_page - 1)" :disabled="!deliveries.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('deliveries.previous') }}</button>
          <button @click="loadDeliveries(deliveries.current_page + 1)" :disabled="!deliveries.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('deliveries.next') }}</button>
        </div>
      </div>
    </div>

    <!-- Create Delivery Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">{{ $t('deliveries.createDelivery') }} - {{ selectedSale?.invoice_number }}</h3>
        
        <form @submit.prevent="saveDelivery" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.courier') }} *</label>
              <input v-model="form.courier" required class="input" placeholder="JNE, TIKI, etc" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.trackingNumber') }}</label>
              <input v-model="form.tracking_number" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="input"></textarea>
          </div>

          <!-- Serial Numbers Section -->
          <div v-if="saleItems.length > 0" class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Serial Numbers (Opsional)</h4>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-800">
              ℹ️ Serial number yang diisi akan otomatis membuat warranty aktif untuk produk tersebut.
            </div>

            <div class="space-y-3">
              <div v-for="item in saleItems" :key="item.product_id" class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-800">{{ item.product_title }}</span>
                  <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded-full">Qty: {{ item.quantity }}</span>
                </div>
                <div class="space-y-2">
                  <div v-for="(sn, idx) in item.serial_numbers" :key="idx" class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 w-12">SN {{ idx + 1 }}:</span>
                    <input v-model="item.serial_numbers[idx]" type="text" placeholder="Enter serial number" class="input text-sm flex-1 font-mono" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('common.loading') : $t('deliveries.createDelivery') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Update Status Modal -->
    <div v-if="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">{{ $t('deliveries.updateDeliveryStatus') }}</h3>
        
        <form @submit.prevent="saveStatus" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.status') }} *</label>
              <select v-model="statusForm.status" required class="input">
                <option value="preparing">{{ $t('deliveries.preparing') }}</option>
                <option value="shipped">{{ $t('deliveries.shipped') }}</option>
                <option value="in_transit">{{ $t('deliveries.inTransit') }}</option>
                <option value="delivered">{{ $t('deliveries.delivered') }}</option>
                <option value="cancelled">{{ $t('deliveries.cancelled') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.trackingNumber') }}</label>
              <input v-model="statusForm.tracking_number" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.notes') }}</label>
            <textarea v-model="statusForm.notes" rows="2" class="input"></textarea>
          </div>

          <!-- Serial Numbers in Update Modal -->
          <div v-if="statusSaleItems.length > 0" class="border-t pt-4">
            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Serial Numbers</h4>
            <div class="space-y-3">
              <div v-for="item in statusSaleItems" :key="item.product_id" class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-800">{{ item.product_title }}</span>
                  <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded-full">Qty: {{ item.quantity }}</span>
                </div>
                <div class="space-y-2">
                  <div v-for="(sn, idx) in item.serial_numbers" :key="idx" class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 w-12">SN {{ idx + 1 }}:</span>
                    <input v-model="item.serial_numbers[idx]" type="text" placeholder="Enter serial number" class="input text-sm flex-1 font-mono" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="statusForm.status === 'delivered'" class="bg-green-50 p-3 rounded-lg text-sm text-green-800">
            {{ $t('deliveries.deliveredNote') }}
          </div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="statusModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('common.loading') : $t('deliveries.updateStatus') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Serial Number / Excel Modal -->
    <div v-if="serialModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-xl font-bold mb-4">Serial Numbers — {{ serialDelivery?.tracking_number }}</h3>

        <!-- Existing serial numbers -->
        <div v-if="serialDelivery?.items?.length" class="mb-4">
          <h4 class="text-sm font-semibold text-gray-500 mb-2">Current Serial Numbers</h4>
          <div class="space-y-2">
            <div v-for="item in serialDelivery.items" :key="item.id" class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
              <span class="font-medium">{{ item.product?.title }}</span>
              <code v-if="item.serial_number" class="bg-green-100 text-green-800 px-2 py-0.5 rounded font-mono text-xs">{{ item.serial_number }}</code>
              <span v-else class="text-gray-400 text-xs">No SN</span>
            </div>
          </div>
        </div>

        <!-- Excel Upload/Download -->
        <div class="border-t pt-4 space-y-3">
          <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Import / Export Excel</h4>
          <div class="flex gap-3">
            <button @click="downloadSerialTemplate" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition text-sm font-medium">
              📋 Download Template
            </button>
            <label class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg hover:bg-purple-100 transition text-sm font-medium cursor-pointer">
              📥 Upload Excel
              <input type="file" accept=".xlsx,.xls,.csv" @change="uploadSerialExcel" class="hidden" />
            </label>
          </div>
          <div v-if="uploadMessage" class="p-3 rounded-lg text-sm" :class="uploadError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">
            {{ uploadMessage }}
          </div>
          <div v-if="uploadErrors.length" class="bg-amber-50 border border-amber-200 rounded-lg p-3">
            <p class="text-sm font-medium text-amber-800 mb-1">Import Warnings:</p>
            <ul class="text-xs text-amber-700 list-disc list-inside">
              <li v-for="(err, i) in uploadErrors" :key="i">{{ err }}</li>
            </ul>
          </div>
        </div>

        <div class="flex justify-end pt-4 border-t mt-4">
          <button @click="serialModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- Success notification -->
    <div v-if="successMessage" class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-50 flex items-center gap-2 animate-bounce-in">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      {{ successMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';

const { t } = useI18n();

const deliveries = ref({ data: [] });
const loading = ref(true);
const showModal = ref(false);
const statusModal = ref(false);
const serialModal = ref(false);
const saving = ref(false);
const error = ref('');
const selectedSale = ref(null);
const selectedDelivery = ref(null);
const serialDelivery = ref(null);
const successMessage = ref('');
const uploadMessage = ref('');
const uploadError = ref(false);
const uploadErrors = ref([]);

const filters = ref({ search: '' });

const form = ref({
  sale_id: '',
  courier: '',
  tracking_number: '',
  notes: '',
});

const saleItems = ref([]);

const statusForm = ref({
  status: '',
  tracking_number: '',
  notes: '',
});

const statusSaleItems = ref([]);

const formatDate = (date) => new Date(date).toLocaleDateString();
const formatPrice = (price) => new Intl.NumberFormat('id-ID').format(price || 0);
const formatStatus = (status) => status.replace(/_/g, ' ').toUpperCase();

const downloadSerialTemplate = async () => {
  if (!serialDelivery.value) return;
  try {
    const response = await api.get(`/deliveries/${serialDelivery.value.id}/serial-template`, {
      responseType: 'blob'
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `serial_template_${serialDelivery.value.tracking_number || serialDelivery.value.id}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Failed to download template:', err);
    alert('Failed to download template');
  }
};

const copyTrackingNumber = async (trackingNumber) => {
  try {
    await navigator.clipboard.writeText(trackingNumber);
    alert(t('deliveries.copied'));
  } catch (err) {
    console.error('Failed to copy:', err);
  }
};

const getDeliveryBadge = (status) => {
  const badges = {
    preparing: 'badge-info',
    shipped: 'badge-warning',
    in_transit: 'badge-warning',
    delivered: 'badge-success',
    cancelled: 'badge-danger',
  };
  return badges[status] || 'badge';
};

const showSuccess = (msg) => {
  successMessage.value = msg;
  setTimeout(() => { successMessage.value = ''; }, 4000);
};

const loadDeliveries = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/deliveries', { params: { page, ...filters.value } });
    deliveries.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const createDelivery = (sale) => {
  selectedSale.value = sale;
  form.value = { sale_id: sale.id, courier: '', tracking_number: '', notes: '' };
  // Build serial number inputs from sale items
  saleItems.value = (sale.items || []).map(item => ({
    product_id: item.product_id,
    product_title: item.product?.title || item.product?.name || 'Product',
    quantity: item.quantity,
    serial_numbers: Array(item.quantity).fill(''),
  }));
  showModal.value = true;
};

const saveDelivery = async () => {
  saving.value = true;
  error.value = '';
  try {
    // Build items array with serial numbers
    const items = [];
    for (const item of saleItems.value) {
      for (let i = 0; i < item.quantity; i++) {
        items.push({
          product_id: item.product_id,
          quantity: 1,
          serial_number: item.serial_numbers[i] || null,
        });
      }
    }

    const payload = { ...form.value, items };
    const response = await api.post('/deliveries', payload);
    showModal.value = false;
    loadDeliveries();

    const wc = response.data.warranties_created || 0;
    if (wc > 0) {
      showSuccess(`Delivery created! ${wc} warranty otomatis dibuat.`);
    } else {
      showSuccess('Delivery created successfully.');
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create delivery';
  } finally {
    saving.value = false;
  }
};

const updateDeliveryStatus = async (delivery) => {
  selectedDelivery.value = delivery;
  statusForm.value = {
    status: delivery.status,
    tracking_number: delivery.tracking_number || '',
    notes: '',
  };

  // Load delivery items for serial number input
  try {
    const response = await api.get(`/deliveries/${delivery.id}`);
    const full = response.data;
    const saleItemsMap = {};
    (full.sale?.items || []).forEach(si => { saleItemsMap[si.product_id] = si; });

    statusSaleItems.value = (full.sale?.items || []).map(si => {
      const existingItems = (full.items || []).filter(di => di.product_id === si.product_id);
      const sns = [];
      for (let i = 0; i < si.quantity; i++) {
        sns.push(existingItems[i]?.serial_number || '');
      }
      return {
        product_id: si.product_id,
        product_title: si.product?.title || si.product?.name || 'Product',
        quantity: si.quantity,
        serial_numbers: sns,
      };
    });
  } catch (err) {
    statusSaleItems.value = [];
  }

  statusModal.value = true;
};

const saveStatus = async () => {
  saving.value = true;
  try {
    // Build items with serial numbers
    const items = [];
    for (const item of statusSaleItems.value) {
      for (let i = 0; i < item.quantity; i++) {
        items.push({
          product_id: item.product_id,
          quantity: 1,
          serial_number: item.serial_numbers[i] || null,
        });
      }
    }

    const payload = { ...statusForm.value, items };
    const response = await api.put(`/deliveries/${selectedDelivery.value.id}`, payload);
    statusModal.value = false;
    loadDeliveries();

    const wc = response.data.warranties_created || 0;
    if (wc > 0) {
      showSuccess(`Status updated! ${wc} warranty otomatis dibuat.`);
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to update status');
  } finally {
    saving.value = false;
  }
};

const openSerialModal = async (delivery) => {
  try {
    const response = await api.get(`/deliveries/${delivery.id}`);
    serialDelivery.value = response.data;
  } catch (err) {
    serialDelivery.value = delivery;
  }
  uploadMessage.value = '';
  uploadError.value = false;
  uploadErrors.value = [];
  serialModal.value = true;
};

const uploadSerialExcel = async (event) => {
  const file = event.target.files[0];
  if (!file || !serialDelivery.value) return;

  const formData = new FormData();
  formData.append('file', file);

  uploadMessage.value = 'Uploading...';
  uploadError.value = false;
  uploadErrors.value = [];

  try {
    const response = await api.post(`/deliveries/${serialDelivery.value.id}/import-serials`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    uploadMessage.value = response.data.message;
    uploadErrors.value = response.data.errors || [];
    serialDelivery.value = response.data.delivery;
    loadDeliveries();

    if (response.data.warranties_created > 0) {
      showSuccess(`${response.data.warranties_created} warranty otomatis dibuat dari Excel!`);
    }
  } catch (err) {
    uploadMessage.value = err.response?.data?.message || 'Upload failed';
    uploadError.value = true;
  }

  event.target.value = '';
};

const viewSale = (sale) => {
  const items = sale.items?.map(i => `- ${i.product?.title}: ${i.quantity} x Rp ${formatPrice(i.unit_price)}`).join('\n');
  alert(`Sale: ${sale.invoice_number}\nCustomer: ${sale.customer_name}\nAddress: ${sale.customer_address || '-'}\n\nItems:\n${items}\n\nTotal: Rp ${formatPrice(sale.total_amount)}`);
};

onMounted(() => {
  loadDeliveries();
});
</script>
