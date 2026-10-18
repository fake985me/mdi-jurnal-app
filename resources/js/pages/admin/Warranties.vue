<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $t('pageTitles.Warranties') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $t('ui.productWarrantiesFromSalesTransactions') }}</p>
      </div>
      <p class="text-sm text-gray-500">{{ warranties.total || 0 }} {{ $t('ui.totalWarranty') }}</p>
    </div>

    <div class="card p-4">
      <div class="flex flex-col gap-3 md:flex-row">
        <input
          v-model="filters.search"
          @input="debouncedLoad"
          type="search"
          :placeholder="$t('ui.cariSerialNumberProdukAtauInvoice')"
          class="input flex-1"
        />
        <select v-model="filters.status" @change="loadWarranties" class="input md:w-48">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="active">{{ $t('labels.active') }}</option>
          <option value="expired">{{ $t('labels.expired') }}</option>
          <option value="claimed">{{ $t('labels.claimed') }}</option>
        </select>
      </div>
    </div>

    <div class="table-wrapper">
      <div class="card-header flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.daftarWarranty') }}</h3>
        <span class="text-sm text-gray-500">{{ activeCount }} {{ $t('ui.activeOnThisPage') }}</span>
      </div>

      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">{{ $t('ui.memuatWarranty') }}</div>
      <div v-else-if="!warranties.data?.length" class="p-10 text-center">
        <p class="font-medium text-gray-700">{{ $t('ui.tidakAdaWarrantyDitemukan') }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ $t('ui.warrantyDibuatOtomatisSaatSerialNumberDiisiDiDelivery') }}</p>
      </div>

      <div v-else class="overflow-x-auto">

        <table class="min-w-full">
          <thead class="table-header">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('sales.invoice') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('sales.product') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('ui.serialNumber') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('ui.warrantyPeriod') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <template v-for="group in groupedWarranties" :key="group.key">
              <tr class="bg-indigo-50">
                <td colspan="6" class="px-6 py-3">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <span class="text-sm font-semibold text-indigo-900">{{ group.invoice }}</span>
                      <span v-if="group.customer" class="ml-2 text-xs text-indigo-700">{{ group.customer }}</span>
                    </div>
                    <span class="text-xs text-indigo-700">{{ group.items.length }} {{ $t('ui.warranty') }}</span>
                  </div>
                </td>
              </tr>
              <tr v-for="warranty in group.items" :key="warranty.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(warranty.sale?.sale_date) }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ warranty.product?.title || warranty.product?.name || '-' }}</td>
                <td class="px-6 py-4">
                  <code v-if="warranty.serial_number" class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700">{{ warranty.serial_number }}</code>
                  <span v-else class="text-sm text-gray-400">-</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  <p>{{ formatDate(warranty.start_date) }}</p>
                  <p class="mt-1 text-xs text-gray-400">{{ $t('ui.to') }} {{ formatDate(warranty.end_date) }}</p>
                </td>
                <td class="px-6 py-4"><span :class="getStatusBadge(warranty.status)">{{ formatStatus(warranty.status) }}</span></td>
                <td class="px-6 py-4 text-right text-sm">
                  <button @click="editWarranty(warranty.id)" class="mr-4 font-medium text-indigo-600 hover:text-indigo-800">{{ $t('common.edit') }}</button>
                  <button @click="deleteWarranty(warranty.id)" class="font-medium text-red-600 hover:text-red-800">{{ $t('common.delete') }}</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div v-if="warranties.data?.length" class="flex items-center justify-between bg-gray-50 px-6 py-4">
        <p class="text-sm text-gray-600">{{ $t('sales.showing') }} {{ warranties.from }}–{{ warranties.to }} {{ $t('sales.of') }} {{ warranties.total }}</p>
        <div class="flex gap-2">
          <button @click="loadWarranties(warranties.current_page - 1)" :disabled="!warranties.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadWarranties(warranties.current_page + 1)" :disabled="!warranties.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const warranties = ref({ data: [] });
const loading = ref(true);

const allSales     = ref([])

const filters = ref({ search: '', status: '' });
let debounceTimer;

const activeCount = computed(() => (warranties.value.data || []).filter((warranty) => warranty.status === 'active').length);
const groupedWarranties = computed(() => {
  const groups = new Map();

  for (const warranty of warranties.value.data || []) {
    const key = warranty.sale_id || `warranty-${warranty.id}`;
    if (!groups.has(key)) {
      groups.set(key, {
        key,
        invoice: warranty.sale?.invoice_number || uiText("Tanpa Faktur"),
        customer: warranty.sale?.customer_name || '',
        items: [],
      });
    }
    groups.get(key).items.push(warranty);
  }

  return [...groups.values()];
});
const formatDate = (date) => date ? new Date(date).toLocaleDateString(uiLocale(), { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
const formatStatus = (status) => (status || '').toUpperCase();
const getStatusBadge = (status) => ({
  active: 'badge-success',
  expired: 'badge-danger',
  claimed: 'badge-warning',
}[status] || 'badge');

const loadWarranties = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warranties', {
      params: { page, per_page: 15, ...filters.value },
    });
    warranties.value = response.data;
  } catch (err) {
    console.error('Gagal memuat garansi:', err);
  } finally {
    loading.value = false;
  }
};

const debouncedLoad = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadWarranties(), 300);
};

const editWarranty = (id) => router.push({ name: 'WarrantyEdit', params: { id } });

const deleteWarranty = async (id) => {
  if (!confirm(uiText("Hapus garansi ini?"))) return;
  try {
    await api.delete(`/warranties/${id}`);
    await loadWarranties(warranties.value.data.length === 1 && warranties.value.current_page > 1
      ? warranties.value.current_page - 1
      : warranties.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus garansi."));
  }
};

onMounted(loadWarranties);

</script>
