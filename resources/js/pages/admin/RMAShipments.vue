<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
          {{ $t('pageTitles.RMAShipments') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.permintaanShipmentDibuatOtomatisUntukRmaPenggantianStokBerkurangSaatPengirimanDikonfirmasi') }}</p>
      </div>
      <button @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        {{ $t('ui.newShipment') }}
      </button>
    </div>

    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadShipments" type="text"
          :placeholder="$t('ui.searchByCodeTrackingOrRecipient')" class="input" />
        <select v-model="filters.status" @change="loadShipments" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="pending">{{ $t('labels.pending') }}</option>
          <option value="shipped">{{ $t('labels.shipped') }}</option>
          <option value="in_transit">{{ $t('labels.in_transit') }}</option>
          <option value="delivered">{{ $t('labels.delivered') }}</option>
          <option value="cancelled">{{ $t('labels.cancelled') }}</option>
        </select>
      </div>
    </div>

    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.shipmentRecords') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('common.loading') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
      <table class="min-w-[1100px] w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.shipmentCode') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.rmaCode') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.quantity') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.recipient') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.tracking') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
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
              <span :class="getStatusBadge(shipment.status)">{{ $label(shipment.status) }}</span>
              <button
                v-if="shipment.status === 'pending' && (shipment.rma?.status !== 'processed' || shipment.rma?.resolution !== 'replace')"
                @click="openRMA(shipment)"
                class="ml-2 inline-flex items-center rounded bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                {{ $t('ui.processRma') }}
              </button>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                v-if="shipment.status === 'pending' && shipment.rma?.status === 'processed' && shipment.rma?.resolution === 'replace'"
                @click="openShipModal(shipment)"
                class="text-blue-600 hover:text-blue-900">{{ $t('ui.ship') }}</button>
              <span v-else-if="shipment.status === 'pending'" class="text-xs text-amber-600">{{ $t('ui.awaitingRmaProcessing') }}</span>
              <button v-if="shipment.status === 'shipped' || shipment.status === 'in_transit'"
                @click="markDelivered(shipment)" class="text-green-600 hover:text-green-900">{{ $t('ui.markDelivered') }}</button>
              <button @click="viewDetails(shipment)" class="text-gray-600 hover:text-gray-900">{{ $t('ui.details') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
      </div>

      <div v-if="shipments.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ shipments.from }} {{ $t('ui.to') }} {{ shipments.to }} {{ $t('sales.of') }} {{ shipments.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadShipments(shipments.current_page - 1)" :disabled="!shipments.prev_page_url"
            class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadShipments(shipments.current_page + 1)" :disabled="!shipments.next_page_url"
            class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.newShipment2') }}</h3>

        <form @submit.prevent="saveShipment" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.selectRma') }}</label>
            <select v-model="form.rma_id" @change="onRMASelect" required class="input">
              <option value="">{{ $t('ui.selectRma2') }}</option>
              <option v-for="rma in processedRMAs" :key="rma.id" :value="rma.id">
                {{ rma.rma_code }} - {{ rma.product?.title }} {{ $t('ui.qty') }} {{ rma.quantity }})
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ $t('ui.hanyaRmaProcessedTanpaPermintaanShipmentYangSudahAda') }}</p>
          </div>

          <div v-if="form.rma_id" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('sales.product') }}</label>
              <input :value="selectedRMA?.product?.title" disabled class="input bg-gray-100" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantity') }}</label>
              <input v-model.number="form.quantity" type="number" min="1" :max="selectedRMA?.quantity" required
                class="input" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.recipientName') }}</label>
            <input v-model="form.recipient_name" required class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.recipientPhone') }}</label>
            <input v-model="form.recipient_phone" required class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.recipientAddress') }}</label>
            <textarea v-model="form.recipient_address" required rows="3" class="input"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.courierOptional') }}</label>
            <input v-model="form.courier" class="input" :placeholder="$t('ui.eGJneSicepatAnteraja')" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
              {{ saving ? $t('sales.saving') : $t('deliveries.createDelivery') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showShipModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ $t('ui.shipPackage') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $t('ui.shipment') }} {{ selectedShipment?.shipment_code }}</p>
        <p class="text-sm text-gray-600 mb-4">
          {{ $t('ui.rma') }} <span class="font-semibold text-blue-600">{{ selectedShipment?.rma?.rma_code }}</span>{{ $t('ui.stokReplacementAkanDikurangiSetelahKonfirmasi') }}
        </p>

        <form @submit.prevent="confirmShip" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.trackingNumber') }}</label>
            <input v-model="shipForm.tracking_number" required class="input" :placeholder="$t('ui.eGJne123456789')" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.courier') }}</label>
            <input v-model="shipForm.courier" required class="input" :placeholder="$t('ui.eGJneSicepat')" />
          </div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showShipModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
              {{ saving ? $t('ui.processing') : $t('ui.confirmShipment') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">{{ $t('ui.shipmentDetails') }}</h3>
          <button @click="showDetailModal = false" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <div v-if="selectedShipment" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">{{ $t('ui.shipmentCode') }}</p>
              <p class="font-semibold">{{ selectedShipment.shipment_code }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('projectInvestment.materials.status') }}</p>
              <span :class="getStatusBadge(selectedShipment.status)">{{ $label(selectedShipment.status) }}</span>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('ui.rmaCode') }}</p>
              <p class="font-semibold text-blue-600">{{ selectedShipment.rma?.rma_code }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('sales.product') }}</p>
              <p class="font-semibold">{{ selectedShipment.product?.title }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">{{ $t('sales.quantity') }}</p>
              <p class="font-semibold">{{ selectedShipment.quantity }}</p>
            </div>
            <div v-if="selectedShipment.tracking_number">
              <p class="text-sm text-gray-500">{{ $t('deliveries.trackingNumber') }}</p>
              <p class="font-mono font-semibold">{{ selectedShipment.tracking_number }}</p>
            </div>
            <div v-if="selectedShipment.courier">
              <p class="text-sm text-gray-500">{{ $t('deliveries.courier') }}</p>
              <p class="font-semibold">{{ selectedShipment.courier }}</p>
            </div>
          </div>

          <div class="border-t pt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">{{ $t('ui.recipientInformation') }}</p>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p class="font-semibold">{{ selectedShipment.recipient_name }}</p>
              <p class="text-sm text-gray-600">{{ selectedShipment.recipient_phone }}</p>
              <p class="text-sm text-gray-600 mt-2">{{ selectedShipment.recipient_address }}</p>
            </div>
          </div>

          <div v-if="selectedShipment.notes" class="border-t pt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">{{ $t('projectInvestment.progress.notes') }}</p>
            <p class="text-sm text-gray-600">{{ selectedShipment.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
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
      rma => rma.status === 'processed'
        && rma.resolution === 'replace'
        && !rma.shipment
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
    error.value = err.response?.data?.message || uiText("Gagal membuat pengiriman");
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
    alert(err.response?.data?.message || uiText("Gagal mengirim"));
  } finally {
    saving.value = false;
  }
};

const markDelivered = async (shipment) => {
  if (!confirm(uiText("Tandai pengiriman ini sebagai terkirim?"))) return;
  
  try {
    await api.post(`/rma-shipments/${shipment.id}/mark-delivered`);
    loadShipments();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menandai sebagai terkirim"));
  }
};

const viewDetails = (shipment) => {
  selectedShipment.value = shipment;
  showDetailModal.value = true;
};

const openRMA = (shipment) => {
  router.push({ name: 'RMAs', query: { search: shipment.rma?.rma_code || '' } });
};

onMounted(() => {
  loadShipments();
  loadProcessedRMAs();
});
</script>
