<template>
  <div class="space-y-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <button @click="router.push({ name: 'Deliveries' })" class="mb-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">← Kembali ke Delivery</button>
        <h2 class="text-2xl font-bold text-gray-900">Serial Number</h2>
        <p class="mt-1 text-sm text-gray-500">Kelola serial number untuk satu pengiriman.</p>
      </div>
      <span v-if="delivery" :class="getDeliveryBadge(delivery.status)">{{ formatStatus(delivery.status) }}</span>
    </div>

    <div v-if="loading" class="card p-10 text-center text-sm text-gray-500">Memuat data pengiriman...</div>
    <template v-else-if="delivery">
      <div class="grid gap-4 md:grid-cols-3">
        <div class="card p-4"><p class="text-xs font-medium uppercase text-gray-500">Invoice</p><p class="mt-1 font-semibold text-gray-900">{{ delivery.sale?.invoice_number || '-' }}</p></div>
        <div class="card p-4"><p class="text-xs font-medium uppercase text-gray-500">Pelanggan</p><p class="mt-1 font-semibold text-gray-900">{{ delivery.sale?.customer_name || '-' }}</p></div>
        <div class="card p-4"><p class="text-xs font-medium uppercase text-gray-500">Nomor Resi</p><p class="mt-1 font-mono text-sm font-semibold text-gray-900">{{ delivery.tracking_number || '-' }}</p></div>
      </div>

      <div class="card p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="font-semibold text-gray-900">Daftar Serial Number</h3>
            <p class="mt-1 text-sm text-gray-500">Import serial dengan Excel untuk memperbarui data dan membuat warranty otomatis.</p>
          </div>
          <div class="flex gap-2">
            <button @click="downloadTemplate" class="btn-secondary">Download Template</button>
            <label class="btn-primary cursor-pointer">
              {{ uploading ? 'Mengunggah...' : 'Import Excel' }}
              <input type="file" accept=".xlsx,.xls,.csv" @change="uploadSerialExcel" :disabled="uploading" class="hidden" />
            </label>
          </div>
        </div>

        <p v-if="uploadMessage" class="mb-4 rounded-lg p-3 text-sm" :class="uploadError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">{{ uploadMessage }}</p>
        <div v-if="uploadErrors.length" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
          <p class="font-medium">Catatan import:</p>
          <ul class="mt-1 list-inside list-disc text-xs"><li v-for="(item, index) in uploadErrors" :key="index">{{ item }}</li></ul>
        </div>

        <div v-if="delivery.items?.length" class="divide-y divide-gray-100 rounded-lg border border-gray-200">
          <div v-for="item in delivery.items" :key="item.id" class="flex items-center justify-between gap-4 p-4">
            <div>
              <p class="text-sm font-medium text-gray-900">{{ item.product?.title || item.product?.name || 'Produk' }}</p>
              <p class="mt-1 text-xs text-gray-500">Qty: {{ item.quantity }}</p>
            </div>
            <code v-if="item.serial_number" class="rounded bg-green-50 px-3 py-1.5 font-mono text-xs text-green-800">{{ item.serial_number }}</code>
            <span v-else class="text-sm text-gray-400">Belum ada serial</span>
          </div>
        </div>
        <p v-else class="py-6 text-center text-sm text-gray-500">Belum ada item untuk pengiriman ini.</p>
      </div>
    </template>
    <div v-else class="card p-10 text-center text-sm text-red-600">Data pengiriman tidak ditemukan.</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';

const route = useRoute();
const router = useRouter();
const delivery = ref(null);
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
    const response = await api.get(`/deliveries/${route.params.id}`);
    delivery.value = response.data;
  } catch (err) {
    console.error('Failed to load delivery:', err);
  } finally {
    loading.value = false;
  }
};

const downloadTemplate = async () => {
  try {
    const response = await api.get(`/deliveries/${route.params.id}/serial-template`, { responseType: 'blob' });
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
    uploadMessage.value = 'Gagal mengunduh template serial number.';
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
    const response = await api.post(`/deliveries/${route.params.id}/import-serials`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    delivery.value = response.data.delivery;
    uploadMessage.value = response.data.message;
    uploadErrors.value = response.data.errors || [];
  } catch (err) {
    uploadError.value = true;
    uploadMessage.value = err.response?.data?.message || 'Gagal mengimpor serial number.';
  } finally {
    uploading.value = false;
    event.target.value = '';
  }
};

onMounted(loadDelivery);
</script>
