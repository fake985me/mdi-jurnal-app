<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Delivery</h2>
      <p class="mt-1 text-sm text-gray-500">Pantau pengiriman dan perbarui statusnya.</p>
    </div>

    <div class="card p-4">
      <input
        v-model="filters.search"
        @input="debouncedLoadDeliveries"
        type="search"
        placeholder="Cari invoice, pelanggan, atau nomor resi..."
        class="input max-w-xl"
      />
    </div>

    <div class="table-wrapper">
      <div class="card-header flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Pengiriman</h3>
        <span class="text-sm text-gray-500">{{ deliveries.total || 0 }} pengiriman</span>
      </div>

      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">Memuat data pengiriman...</div>
      <div v-else-if="!deliveries.data?.length" class="p-10 text-center text-sm text-gray-500">Tidak ada data pengiriman.</div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="table-header">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Invoice</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Pelanggan</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Pengiriman</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="delivery in deliveries.data" :key="delivery.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ delivery.sale?.invoice_number || '-' }}</td>
              <td class="px-6 py-4">
                <p class="text-sm font-medium text-gray-900">{{ delivery.sale?.customer_name || '-' }}</p>
                <p class="text-xs text-gray-500">{{ delivery.sale?.customer_phone || '-' }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-gray-700">{{ delivery.courier || 'Kurir belum diisi' }}</p>
                <button
                  v-if="delivery.tracking_number"
                  @click="copyTrackingNumber(delivery.tracking_number)"
                  class="mt-1 font-mono text-xs text-indigo-600 hover:text-indigo-800"
                  title="Salin nomor resi"
                >
                  {{ delivery.tracking_number }}
                </button>
              </td>
              <td class="px-6 py-4"><span :class="getDeliveryBadge(delivery.status)">{{ formatStatus(delivery.status) }}</span></td>
              <td class="px-6 py-4 text-right text-sm">
                <button @click="openStatusModal(delivery)" class="mr-4 font-medium text-indigo-600 hover:text-indigo-800">Status</button>
                <button @click="openSerialPage(delivery.id)" class="font-medium text-purple-600 hover:text-purple-800">Serial</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="deliveries.data?.length" class="flex items-center justify-between bg-gray-50 px-6 py-4">
        <p class="text-sm text-gray-600">Menampilkan {{ deliveries.from }}–{{ deliveries.to }} dari {{ deliveries.total }}</p>
        <div class="flex gap-2">
          <button @click="loadDeliveries(deliveries.current_page - 1)" :disabled="!deliveries.prev_page_url" class="btn-secondary disabled:opacity-50">Sebelumnya</button>
          <button @click="loadDeliveries(deliveries.current_page + 1)" :disabled="!deliveries.next_page_url" class="btn-secondary disabled:opacity-50">Berikutnya</button>
        </div>
      </div>
    </div>

    <div v-if="statusModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5">
          <h3 class="text-lg font-bold text-gray-900">Perbarui Status Pengiriman</h3>
          <p class="mt-1 text-sm text-gray-500">{{ selectedDelivery?.sale?.invoice_number }}</p>
        </div>
        <form @submit.prevent="saveStatus" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
            <select v-model="statusForm.status" required class="input">
              <option value="preparing">Preparing</option>
              <option value="shipped">Shipped</option>
              <option value="in_transit">In Transit</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Nomor Resi</label>
            <input v-model="statusForm.tracking_number" class="input" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Catatan</label>
            <textarea v-model="statusForm.notes" rows="3" class="input"></textarea>
          </div>
          <p v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="statusModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const deliveries = ref({ data: [] });
const loading = ref(true);
const saving = ref(false);
const statusModal = ref(false);
const selectedDelivery = ref(null);
const error = ref('');
const filters = ref({ search: '' });
const statusForm = ref({ status: '', tracking_number: '', notes: '' });
let searchTimer;

const formatStatus = (status) => (status || '').replace(/_/g, ' ').toUpperCase();
const getDeliveryBadge = (status) => ({
  preparing: 'badge-info',
  shipped: 'badge-warning',
  in_transit: 'badge-warning',
  delivered: 'badge-success',
  cancelled: 'badge-danger',
}[status] || 'badge');

const loadDeliveries = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/deliveries', { params: { page, ...filters.value } });
    deliveries.value = response.data;
  } catch (err) {
    console.error('Failed to load deliveries:', err);
  } finally {
    loading.value = false;
  }
};

const debouncedLoadDeliveries = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadDeliveries(), 300);
};

const copyTrackingNumber = async (trackingNumber) => {
  try {
    await navigator.clipboard.writeText(trackingNumber);
  } catch (err) {
    console.error('Failed to copy tracking number:', err);
  }
};

const openStatusModal = (delivery) => {
  selectedDelivery.value = delivery;
  statusForm.value = {
    status: delivery.status,
    tracking_number: delivery.tracking_number || '',
    notes: delivery.notes || '',
  };
  error.value = '';
  statusModal.value = true;
};

const saveStatus = async () => {
  saving.value = true;
  error.value = '';
  try {
    await api.put(`/deliveries/${selectedDelivery.value.id}`, statusForm.value);
    statusModal.value = false;
    await loadDeliveries(deliveries.value.current_page || 1);
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memperbarui status pengiriman.';
  } finally {
    saving.value = false;
  }
};

const openSerialPage = (deliveryId) => router.push({ name: 'DeliverySerialNumbers', params: { id: deliveryId } });

onMounted(loadDeliveries);
</script>
