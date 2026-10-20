<template>
  <div class="space-y-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <button @click="router.push({ name: 'Deliveries' })" class="mb-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ $t('ui.kembaliKeDelivery') }}</button>
        <h2 class="text-2xl font-bold text-gray-900">{{ $t('ui.serialNumber') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $t('ui.kelolaSerialNumberUntukSatuPengiriman') }}</p>
      </div>
      <div v-if="delivery" class="flex flex-wrap items-center gap-3">
        <span :class="getDeliveryBadge(delivery.status)">{{ formatStatus(delivery.status) }}</span>
        <button v-if="isTransfer || isRma || delivery.sale_id" @click="printNote" :disabled="printingNote || uploading" class="btn-primary disabled:opacity-50">
          {{ $t(printingNote ? 'deliveryNote.loading' : 'deliveryNote.print') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="card p-10 text-center text-sm text-gray-500">{{ $t('ui.loadingDeliveryData') }}</div>
    <template v-else-if="delivery">
      <div class="grid gap-4 md:grid-cols-3">
        <div class="card p-4">
          <p class="text-xs font-medium uppercase text-gray-500">{{ $t(isTransfer ? 'ui.transactionDocument' : 'sales.invoice') }}</p>
          <p class="mt-1 font-semibold text-gray-900">{{ delivery.sale?.invoice_number || '-' }}</p>
        </div>
        <div class="card p-4">
          <p class="text-xs font-medium uppercase text-gray-500">{{ $t(isTransfer ? 'ui.recipientDestination' : 'sales.customer') }}</p>
          <p class="mt-1 font-semibold text-gray-900">{{ delivery.sale?.customer_name || '-' }}</p>
        </div>
        <div class="card p-4"><p class="text-xs font-medium uppercase text-gray-500">{{ $t('deliveries.trackingNumber') }}</p><p class="mt-1 font-mono text-sm font-semibold text-gray-900">{{ delivery.tracking_number || '-' }}</p></div>
      </div>

      <div class="card p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="font-semibold text-gray-900">{{ $t('ui.daftarSerialNumber') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ isTransfer ? $t('transferSerials.instructions') : isRma ? $t('ui.importReplacementSerialNumbersToUpdateTheOriginalShipmentAndWarranty') : $t('ui.importSerialNumbersFromExcelToUpdateRecordsAndCreateWarrantiesAutomatically') }}</p>
          </div>
          <div v-if="!isTransfer" class="flex gap-2">
            <button @click="downloadTemplate" class="btn-secondary">{{ $t('ui.downloadTemplate') }}</button>
            <label class="btn-primary cursor-pointer">
              {{ uploading ? $t('ui.uploading') : $t('ui.importExcel') }}
              <input type="file" accept=".xlsx,.xls,.csv" @change="uploadSerialExcel" :disabled="uploading" class="hidden" />
            </label>
          </div>
        </div>

        <form v-if="isTransfer" @submit.prevent="saveTransferSerials" class="mb-5 space-y-3">
          <label for="transfer-serials" class="block text-sm font-medium">{{ $t('ui.serialNumber') }}</label>
          <textarea id="transfer-serials" v-model="serialText" rows="10" :disabled="!canEditTransfer || uploading" :placeholder="$t('ui.oneSerialNumberPerLine')" class="input font-mono"></textarea>
          <p class="text-sm text-gray-500">{{ $t('transferSerials.count', { count: transferSerials.length, quantity: delivery.quantity }) }}</p>
          <p v-if="!canEditTransfer" class="text-sm text-amber-700">{{ $t('transferSerials.locked') }}</p>
          <button v-if="canEditTransfer" :disabled="uploading || transferSerials.length !== delivery.quantity" class="btn-primary disabled:opacity-50">{{ $t(uploading ? 'sales.saving' : 'common.save') }}</button>
        </form>
        <p v-if="uploadMessage" class="mb-4 rounded-lg p-3 text-sm" :class="uploadError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">{{ uploadMessage }}</p>
        <div v-if="uploadErrors.length" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
          <p class="font-medium">{{ $t('ui.catatanImport') }}</p>
          <ul class="mt-1 list-inside list-disc text-xs"><li v-for="(item, index) in uploadErrors" :key="index">{{ item }}</li></ul>
        </div>

        <div v-if="delivery.items?.length" class="divide-y divide-gray-100 rounded-lg border border-gray-200">
          <div v-for="item in delivery.items" :key="item.id" class="flex items-center justify-between gap-4 p-4">
            <div>
              <p class="text-sm font-medium text-gray-900">{{ item.product?.title || item.product?.name || $t('sales.product') }}</p>
              <p class="mt-1 text-xs text-gray-500">{{ $t('ui.qty2') }} {{ item.quantity }}</p>
            </div>
            <code v-if="item.serial_number" class="rounded bg-green-50 px-3 py-1.5 font-mono text-xs text-green-800">{{ item.serial_number }}</code>
            <span v-else class="text-sm text-gray-400">{{ $t('ui.belumAdaSerial') }}</span>
          </div>
        </div>
        <p v-else class="py-6 text-center text-sm text-gray-500">{{ $t('ui.noItemsForThisShipmentYet') }}</p>
      </div>
    </template>
    <div v-else class="card p-10 text-center text-sm text-red-600">{{ $t('ui.deliveryDataNotFound') }}</div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import { printDeliveryNote } from '../../utils/printDeliveryNote';

const printingNote = ref(false);
const printNote = async () => {
  printingNote.value = true;
  try { await printDeliveryNote(isRma || isTransfer ? String(route.params.id) : `sale-${delivery.value.sale_id}`); }
  catch (error) { alert(error.message); }
  finally { printingNote.value = false; }
};

const route = useRoute();
const router = useRouter();
const isRma = String(route.params.id).startsWith('rma-');
const isTransfer = String(route.params.id).startsWith('transfer-');
const apiPath = isTransfer ? `/stock-transfers/${String(route.params.id).slice(9)}` : isRma ? `/rma-shipments/${String(route.params.id).slice(4)}` : `/deliveries/${route.params.id}`;
const normalizeDelivery = (data) => isTransfer ? {
  ...data,
  status: ({ pending: 'preparing', completed: 'delivered' })[data.status] || data.status,
  sale: { invoice_number: data.transfer_code, customer_name: data.to_warehouse?.name },
  items: data.serial_numbers?.length ? data.serial_numbers.map((sn,index)=>({id:index,product:data.product,quantity:1,serial_number:sn})) : [{id:0,product:data.product,quantity:data.quantity}],
} : isRma ? {
  ...data,
  sale: data.rma?.sale,
  items: (data.serial_numbers?.length ? data.serial_numbers : [null]).map((sn, index) => ({
    id: index, product: data.product, quantity: sn ? 1 : data.quantity, serial_number: sn,
  })),
} : data;
const delivery = ref(null);
const serialText = ref('');
const transferSerials = computed(() => serialText.value.split(/[\n,]+/).map(sn => sn.trim()).filter(Boolean));
const canEditTransfer = computed(() => delivery.value?.status === 'preparing' || (delivery.value?.status === 'in_transit' && !delivery.value?.serial_numbers?.length));
const saveTransferSerials = async () => {
  uploading.value = true; uploadMessage.value = ''; uploadError.value = false;
  try {
    const response = await api.post(`${apiPath}/serials`, { serial_numbers: transferSerials.value });
    delivery.value = normalizeDelivery(response.data);
    serialText.value = (response.data.serial_numbers || []).join('\n');
    uploadMessage.value = uiText('transferSerials.saved');
  } catch (err) {
    uploadError.value = true;
    uploadMessage.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || uiText('transferSerials.failed');
  } finally { uploading.value = false; }
};
const loading = ref(true);
const uploading = ref(false);
const uploadMessage = ref('');
const uploadError = ref(false);
const uploadErrors = ref([]);

const formatStatus = (status) => (status || '').replace(/_/g, ' ').toUpperCase();
const getDeliveryBadge = (status) => ({
  preparing: 'badge-info', shipped: 'badge-warning', in_transit: 'badge-warning', delivered: 'badge-success', cancelled: 'badge-danger',
}[status] || 'badge');

const loadDelivery = async () => {
  loading.value = true;
  try {
    const response = await api.get(apiPath);
    delivery.value = normalizeDelivery(response.data);
    if (isTransfer) serialText.value = (response.data.serial_numbers || []).join('\n');
  } catch (err) {
    console.error('Gagal memuat pengiriman:', err);
  } finally {
    loading.value = false;
  }
};

const downloadTemplate = async () => {
  try {
    const response = await api.get(`${apiPath}/serial-template`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = `serial_template_${delivery.value?.tracking_number || route.params.id}.xlsx`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    uploadError.value = true;
    uploadMessage.value = uiText("Gagal mengunduh template nomor seri.");
  }
};

const uploadSerialExcel = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  uploading.value = true;
  uploadMessage.value = '';
  uploadError.value = false;
  uploadErrors.value = [];
  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await api.post(`${apiPath}/import-serials`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    delivery.value = normalizeDelivery(isRma ? response.data.shipment : response.data.delivery);
    uploadMessage.value = response.data.message;
    uploadErrors.value = response.data.errors || [];
  } catch (err) {
    uploadError.value = true;
    uploadMessage.value = err.response?.data?.message || uiText("Gagal mengimpor nomor seri.");
  } finally {
    uploading.value = false;
    event.target.value = '';
  }
};

onMounted(loadDelivery);
</script>
