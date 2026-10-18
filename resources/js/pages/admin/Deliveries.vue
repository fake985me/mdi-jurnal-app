<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">{{ $t('sales.delivery') }}</h2>
      <p class="mt-1 text-sm text-gray-500">{{ $t('ui.outgoingItemRequestsAcrossAllTransactionsAndShipmentTracking') }}</p>
    </div>

    <div class="table-wrapper">
      <div class="card-header flex items-center justify-between">
        <h3 class="text-lg font-semibold">{{ $t('ui.outgoingItemRequests') }}</h3>
        <button @click="refresh" class="btn-secondary">{{ $t('ui.update') }}</button>
      </div>
      <p v-if="requestError" class="p-4 text-red-700">{{ requestError }}</p>
      <p v-else-if="requestsLoading" class="p-6 text-gray-500">{{ $t('ui.loadingRequests') }}</p>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="table-header"><tr>
            <th class="px-6 py-3 text-left">{{ $t('ui.transactionDocument') }}</th>
            <th class="px-6 py-3 text-left">{{ $t('ui.recipientDestination') }}</th>
            <th class="px-6 py-3 text-left">{{ $t('ui.items') }}</th>
            <th class="px-6 py-3 text-left">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right">{{ $t('sales.actions') }}</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="request in requests.data" :key="request.id">
              <td class="px-6 py-4"><p class="font-semibold">{{ request.number }}</p><p class="text-xs text-gray-500">{{ $label(request.type) }}</p></td>
              <td class="px-6 py-4">{{ request.party || '-' }}</td>
              <td class="px-6 py-4"><p v-for="(item, index) in request.items" :key="index">{{ item.product || $t('ui.productDeleted') }} × {{ item.quantity }}</p></td>
              <td class="px-6 py-4">{{ formatStatus(request.status) }}</td>
              <td class="px-6 py-4 text-right">
                <button v-if="['preparing', 'shipped', 'in_transit'].includes(request.status)" @click="openRequestModal(request)" class="mr-4 font-semibold text-green-600">{{ request.status === 'preparing' ? $t('ui.processShipment') : $t('ui.confirmReceipt') }}</button>
                <router-link :to="request.route" class="text-indigo-600">{{ $t('ui.openTransactions') }}</router-link>
              </td>
            </tr>
            <tr v-if="!requests.data?.length"><td colspan="5" class="p-6 text-center text-gray-500">{{ $t('ui.noOutgoingItemRequests') }}</td></tr>
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-between px-6 py-4">
        <span class="text-sm text-gray-500">{{ requests.total || 0 }} {{ $t('ui.requests') }}</span>
        <div class="flex gap-2">
          <button @click="loadRequests(requests.current_page - 1)" :disabled="!requests.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadRequests(requests.current_page + 1)" :disabled="!requests.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <div class="card p-4">
      <input
        v-model="filters.search"
        @input="debouncedLoadDeliveries"
        type="search"
        :placeholder="$t('ui.searchTransactionsRecipientsItemsOrTrackingNumbers')"
        class="input max-w-xl"
      />
    </div>

    <div class="table-wrapper">
      <div class="card-header flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.shipmentRecords') }}</h3>
        <span class="text-sm text-gray-500">{{ deliveries.total || 0 }} {{ $t('ui.shipments') }}</span>
      </div>
      <p v-if="attachmentDownloadError" class="px-6 py-3 text-sm text-red-700">{{ attachmentDownloadError }}</p>

      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">{{ $t('ui.loadingDeliveryData') }}</div>
      <div v-else-if="!deliveries.data?.length" class="p-10 text-center text-sm text-gray-500">{{ $t('ui.noDeliveryData') }}</div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="table-header">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('ui.transactionDocument') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('ui.recipientDestination') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('sales.delivery') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('ui.attachment') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="delivery in deliveries.data" :key="delivery.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <p class="text-sm font-semibold text-gray-900">{{ delivery.number || '-' }}</p>
                <p class="text-xs text-gray-500">{{ $label(delivery.type) }}</p>
                <p v-for="(item, index) in delivery.items" :key="index" class="text-xs text-gray-500">{{ item.product || $t('ui.productDeleted') }} &times; {{ item.quantity }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm font-medium text-gray-900">{{ delivery.party || '-' }}</p>
                <p class="text-xs text-gray-500">{{ delivery.destination || delivery.contact || '-' }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-gray-700">{{ delivery.courier || $t('ui.courierNotSpecified') }}</p>
                <button
                  v-if="delivery.tracking_number"
                  @click="copyTrackingNumber(delivery.tracking_number)"
                  class="mt-1 font-mono text-xs text-indigo-600 hover:text-indigo-800"
                  :title="$t('ui.copyTrackingNumber')"
                >
                  {{ delivery.tracking_number }}
                </button>
              </td>
              <td class="px-6 py-4"><span :class="getDeliveryBadge(delivery.status)">{{ formatStatus(delivery.status) }}</span></td>
              <td class="px-6 py-4 text-sm">
                <ul v-if="delivery.attachments?.length" class="space-y-1">
                  <li v-for="file in delivery.attachments" :key="file.id">
                    <button type="button" @click="downloadAttachment(file, delivery.id)" :title="`Unduh ${file.original_name}`" class="max-w-48 truncate text-left text-indigo-600 hover:underline">{{ file.original_name }}</button>
                  </li>
                </ul>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 text-right text-sm">
                <button @click="openStatusModal(delivery)" class="mr-4 font-medium text-indigo-600 hover:text-indigo-800">{{ $t('projectInvestment.materials.status') }}</button>
                <button @click="openSerialPage(delivery)" :disabled="!delivery.delivery_id && !delivery.id.startsWith('rma-')" class="font-medium text-purple-600 hover:text-purple-800 disabled:opacity-50 disabled:cursor-not-allowed">{{ $t('ui.serialNumber') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="deliveries.data?.length" class="flex items-center justify-between bg-gray-50 px-6 py-4">
        <p class="text-sm text-gray-600">{{ $t('sales.showing') }} {{ deliveries.from }}–{{ deliveries.to }} {{ $t('sales.of') }} {{ deliveries.total }}</p>
        <div class="flex gap-2">
          <button @click="loadDeliveries(deliveries.current_page - 1)" :disabled="!deliveries.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadDeliveries(deliveries.current_page + 1)" :disabled="!deliveries.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <div v-if="statusModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5">
          <h3 class="text-lg font-bold text-gray-900">{{ $t('ui.updateDeliveryStatus') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ selectedDelivery?.number || selectedDelivery?.sale?.invoice_number }}</p>
        </div>
        <form @submit.prevent="saveStatus" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('projectInvestment.materials.status') }}</label>
            <select v-model="statusForm.status" required class="input">
              <option v-if="!requestMode" value="preparing">{{ $t('labels.preparing') }}</option>
              <option v-if="!requestMode || selectedDelivery?.status === 'preparing'" value="shipped">{{ $t('labels.shipped') }}</option>
              <option v-if="!requestMode" value="in_transit">{{ $t('labels.in_transit') }}</option>
              <option v-if="!requestMode || selectedDelivery?.status !== 'preparing'" value="delivered">{{ $t('labels.delivered') }}</option>
              <option v-if="!requestMode" value="cancelled">{{ $t('labels.cancelled') }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('deliveries.trackingNumber') }}</label>
            <input v-model="statusForm.tracking_number" :required="requestMode && statusForm.status === 'shipped'" class="input" />
          </div>
          <template v-if="requestMode">
            <div><label class="mb-1 block text-sm font-medium">{{ $t('ui.courierDeliveryPerson') }}</label><input v-model="statusForm.courier" :required="statusForm.status === 'shipped'" class="input" /></div>
            <div><label class="mb-1 block text-sm font-medium">{{ $t('ui.addressDeliveryDestination') }}</label><textarea v-model="statusForm.destination" :required="statusForm.status === 'shipped'" class="input" rows="2"></textarea></div>
          </template>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="statusForm.notes" rows="3" class="input"></textarea>
          </div>
          <p v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $ui(error) }}</p>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $t('ui.attachment') }}</label>
            <input type="file" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.avif,.pdf,.doc,.docx,.xls,.xlsx,.csv" @change="selectAttachments" :disabled="saving" class="block w-full text-sm text-gray-600" />
            <p class="mt-1 text-xs text-gray-500">{{ $t('ui.imagesPdfWordOrExcelUpTo5Files10MbPerFile') }}</p>
            <ul v-if="selectedDelivery?.attachments?.length" class="mt-2 space-y-1 text-sm">
              <li v-for="file in selectedDelivery.attachments" :key="file.id">
                <button type="button" @click="downloadAttachment(file)" class="text-indigo-600 hover:underline break-all">{{ file.original_name }}</button>
              </li>
            </ul>
            <ul v-if="attachmentFiles.length" class="mt-2 space-y-1 text-sm text-gray-700">
              <li v-for="(file, index) in attachmentFiles" :key="index" class="flex items-center justify-between gap-2">
                <span class="break-all">{{ file.name }}</span>
                <button type="button" @click="attachmentFiles.splice(index, 1)" :disabled="saving" class="text-red-600">{{ $t('common.delete') }}</button>
              </li>
            </ul>
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="statusModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('common.save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const deliveries = ref({ data: [] });
const loading = ref(true);
const saving = ref(false);
const statusModal = ref(false);
const requestMode = ref(false);
const selectedDelivery = ref(null);
const error = ref('');
const filters = ref({ search: '' });
const statusForm = ref({ status: '', tracking_number: '', notes: '' });
const attachmentFiles = ref([]);
const attachmentDownloadError = ref('');
let searchTimer;
let refreshTimer;
const requests = ref({ data: [] });
const requestsLoading = ref(false);
const requestError = ref('');
const loadRequests = async (page = 1) => {
  requestsLoading.value = !requests.value.data?.length;
  requestError.value = '';
  try {
    requests.value = (await api.get('/delivery-requests', { params: { page, ...filters.value } })).data;
  } catch (err) {
    requestError.value = err.response?.data?.message || uiText("Gagal memuat permintaan keluar barang.");
  } finally {
    requestsLoading.value = false;
  }
};
const refresh = () => Promise.all([loadRequests(requests.value.current_page || 1), loadDeliveries(deliveries.value.current_page || 1)]);

const formatStatus = (status) => (status || '').replace(/_/g, ' ').toUpperCase();
const getDeliveryBadge = (status) => ({
  preparing: 'badge-info',
  shipped: 'badge-warning',
  in_transit: 'badge-warning',
  delivered: 'badge-success',
  cancelled: 'badge-danger',
}[status] || 'badge');

const loadDeliveries = async (page = 1) => {
  loading.value = !deliveries.value.data?.length;
  try {
    const response = await api.get('/delivery-requests', { params: { page, ...filters.value } });
    deliveries.value = response.data;
  } catch (err) {
    console.error('Gagal memuat pengiriman:', err);
  } finally {
    loading.value = false;
  }
};

const debouncedLoadDeliveries = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { loadDeliveries(); loadRequests(); }, 300);
};

const copyTrackingNumber = async (trackingNumber) => {
  try {
    await navigator.clipboard.writeText(trackingNumber);
  } catch (err) {
    console.error('Failed to copy tracking number:', err);
  }
};

const openStatusModal = (delivery) => {
  attachmentFiles.value = [];
  requestMode.value = false;
  selectedDelivery.value = delivery;
  statusForm.value = {
    status: delivery.status,
    tracking_number: delivery.tracking_number || '',
    notes: delivery.notes || '',
  };
  error.value = '';
  statusModal.value = true;
};

const openRequestModal = (request) => {
  attachmentFiles.value = [];
  requestMode.value = true;
  selectedDelivery.value = request;
  statusForm.value = {
    status: request.status === 'preparing' ? 'shipped' : 'delivered',
    courier: request.courier || '', tracking_number: request.tracking_number || '',
    destination: request.destination || request.party || '', notes: request.notes || '',
  };
  error.value = '';
  statusModal.value = true;
};

const saveStatus = async () => {
  saving.value = true;
  error.value = '';
  try {
    const selected = selectedDelivery.value;
    const endpoint = requestMode.value
      ? `/delivery-requests/${selected.id}`
      : `/delivery-requests/${selected.id}/status`;
    if (attachmentFiles.value.length) {
      const payload = new FormData();
      payload.append('_method', 'PUT');
      Object.entries(statusForm.value).forEach(([key, value]) => payload.append(key, value ?? ''));
      attachmentFiles.value.forEach(file => payload.append('attachments[]', file));
      await api.post(endpoint, payload, { headers: { 'Content-Type': 'multipart/form-data' } });
    } else {
      await api.put(endpoint, statusForm.value);
    }
    statusModal.value = false;
    await refresh();
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal memperbarui status pengiriman.");
  } finally {
    saving.value = false;
  }
};

const openSerialPage = (delivery) => router.push({
  name: 'DeliverySerialNumbers',
  params: { id: delivery.delivery_id || delivery.id },
});

const selectAttachments = (event) => {
  const files = Array.from(event.target.files || []);
  event.target.value = '';
  if ((selectedDelivery.value?.attachments?.length || 0) + attachmentFiles.value.length + files.length > 5) {
    error.value = uiText("Maksimal 5 lampiran untuk satu pengiriman.");
    return;
  }
  if (files.some(file => file.size > 10 * 1024 * 1024)) {
    error.value = uiText("Ukuran maksimal setiap lampiran adalah 10 MB.");
    return;
  }
  error.value = '';
  attachmentFiles.value.push(...files);
};

const downloadAttachment = async (file, requestKey = selectedDelivery.value?.id) => {
  attachmentDownloadError.value = '';
  try {
    const response = await api.get(`/delivery-requests/${requestKey}/attachments/${file.id}`, { responseType: 'blob' });
    const url = URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.download = file.original_name;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  } catch (err) {
    if (statusModal.value) error.value = uiText("Gagal mengunduh lampiran.");
    else attachmentDownloadError.value = uiText("Gagal mengunduh lampiran. Silakan coba lagi.");
  }
};

onMounted(() => {
  refresh();
  refreshTimer = setInterval(() => { if (!document.hidden && !statusModal.value) refresh(); }, 30000);
});
onUnmounted(() => { clearTimeout(searchTimer); clearInterval(refreshTimer); });
</script>
