<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h2 class="text-2xl font-bold text-gray-900">Daftar Transaksi</h2><p class="mt-1 text-sm text-gray-500">Transaksi pembayaran penjualan, pembelian, kontrak proyek, dan MSA untuk pemeriksaan akuntansi.</p></div>
      <button type="button" @click="loadTransactions(transactions.current_page || 1)" :disabled="loading" class="btn-secondary">Perbarui</button>
    </div>
    <div class="card p-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div><label class="block text-sm font-medium mb-1">Pencarian</label><input v-model="filters.search" type="search" @input="searchTransactions" placeholder="Referensi pembayaran, metode, atau catatan..." class="input" /></div>
      <div><label class="block text-sm font-medium mb-1">Jenis Transaksi</label><select v-model="filters.payable_type" @change="applyFilters" class="input"><option value="">Semua jenis</option><option v-for="(label, type) in typeLabels" :key="type" :value="type">{{ label }}</option></select></div>
      <div><label class="block text-sm font-medium mb-1">Status Pembayaran</label><select v-model="filters.status" @change="applyFilters" class="input"><option value="">Semua status</option><option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option></select></div>
      <div><label class="block text-sm font-medium mb-1">Dari Tanggal Pembayaran</label><input v-model="filters.start_date" type="date" @change="applyFilters" class="input" /></div>
      <div><label class="block text-sm font-medium mb-1">Sampai Tanggal Pembayaran</label><input v-model="filters.end_date" type="date" @change="applyFilters" class="input" /></div>
      <div class="flex items-end"><button type="button" @click="resetFilters" class="btn-secondary">Reset Filter</button></div>
    </div>
    <div class="table-wrapper">
      <div class="card-header flex items-center justify-between gap-3"><h3 class="text-lg font-semibold">Transaksi Pembayaran</h3><span class="text-sm text-gray-500">{{ transactions.total || 0 }} transaksi</span></div>
      <p v-if="error" class="p-6 text-sm text-red-700">{{ error }}</p>
      <p v-else-if="loading" class="p-8 text-center text-gray-500">Memuat transaksi...</p>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="table-header"><tr>
            <th class="px-4 py-3 text-left">Tanggal</th><th class="px-4 py-3 text-left">Jenis</th><th class="px-4 py-3 text-left">Dokumen / Referensi</th><th class="px-4 py-3 text-left">Pelanggan / Pemasok / Proyek</th><th class="px-4 py-3 text-right">Nominal</th><th class="px-4 py-3 text-right">Pajak</th><th class="px-4 py-3 text-left">Metode / Bank</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Aksi</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="transaction in transactions.data" :key="transaction.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(transaction.payment_date) }}</td>
              <td class="px-4 py-3">{{ typeLabels[transaction.payable_type_label] || transaction.payable_type_label }}</td>
              <td class="px-4 py-3"><p class="font-medium">{{ transaction.payable_reference || '-' }}</p><p class="text-xs text-gray-500">{{ transaction.reference_number || '-' }}</p></td>
              <td class="px-4 py-3">{{ transaction.payable_party || '-' }}</td>
              <td class="px-4 py-3 text-right whitespace-nowrap">{{ currency(transaction.amount) }}</td>
              <td class="px-4 py-3 text-right whitespace-nowrap">{{ currency(transaction.tax_amount) }}<p v-if="transaction.tax_type" class="text-xs text-gray-500">{{ transaction.tax_type.toUpperCase() }} {{ transaction.tax_rate }}%</p></td>
              <td class="px-4 py-3"><p>{{ transaction.method || '-' }}</p><p class="text-xs text-gray-500">{{ transaction.bank_account?.bank_name || '-' }}</p></td>
              <td class="px-4 py-3"><span :class="statusBadge(transaction.status)">{{ statusLabels[transaction.status] || transaction.status }}</span></td>
              <td class="px-4 py-3 text-right"><button type="button" @click="selected = transaction" class="font-medium text-indigo-600 hover:underline">Detail</button></td>
            </tr>
            <tr v-if="!transactions.data?.length"><td colspan="9" class="p-8 text-center text-gray-500">Tidak ada transaksi sesuai filter.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 border-t">
        <span class="text-sm text-gray-500">{{ transactions.from || 0 }}–{{ transactions.to || 0 }} dari {{ transactions.total || 0 }} transaksi</span>
        <div class="flex items-center gap-2"><select v-model="filters.per_page" @change="applyFilters" aria-label="Jumlah transaksi per halaman" class="input !w-auto"><option :value="15">15 / halaman</option><option :value="25">25 / halaman</option><option :value="50">50 / halaman</option></select><button type="button" @click="loadTransactions(transactions.current_page - 1)" :disabled="loading || !transactions.prev_page_url" class="btn-secondary disabled:opacity-40">Sebelumnya</button><button type="button" @click="loadTransactions(transactions.current_page + 1)" :disabled="loading || !transactions.next_page_url" class="btn-secondary disabled:opacity-40">Berikutnya</button></div>
      </div>
    </div>
    <div v-if="selected" @click.self="selected = null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div role="dialog" aria-modal="true" aria-labelledby="transaction-title" class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <h3 id="transaction-title" class="text-lg font-bold mb-4">Detail Transaksi</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
          <dt class="text-gray-500">Dokumen</dt><dd>{{ selected.payable_reference || '-' }}</dd>
          <dt class="text-gray-500">Referensi Pembayaran</dt><dd>{{ selected.reference_number || '-' }}</dd>
          <dt class="text-gray-500">Tanggal Pembayaran</dt><dd>{{ formatDate(selected.payment_date) }}</dd>
          <dt class="text-gray-500">Jenis Pembayaran</dt><dd>{{ paymentLabels[selected.payment_type] || selected.payment_type || '-' }}</dd>
          <dt class="text-gray-500">Nominal</dt><dd>{{ currency(selected.amount) }}</dd>
          <dt class="text-gray-500">Pajak</dt><dd>{{ currency(selected.tax_amount) }}</dd>
          <dt class="text-gray-500">Bank / Rekening</dt><dd>{{ selected.bank_account?.bank_name || '-' }} {{ selected.bank_account?.account_number || '' }}</dd>
          <dt class="text-gray-500">Status</dt><dd>{{ statusLabels[selected.status] || selected.status }}</dd>
          <dt class="text-gray-500">Dicatat Oleh</dt><dd>{{ selected.user?.name || '-' }}</dd>
          <dt class="text-gray-500">Catatan</dt><dd class="whitespace-pre-wrap break-words">{{ selected.notes || '-' }}</dd>
        </dl>
        <div class="flex justify-end mt-6"><button type="button" @click="selected = null" class="btn-secondary">Tutup</button></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from '../../services/api';
const transactions = ref({ data: [] });
const selected = ref(null);
const loading = ref(true);
const error = ref('');
const defaults = () => ({ search: '', payable_type: '', status: '', start_date: '', end_date: '', per_page: 15 });
const filters = ref(defaults());
const typeLabels = { sale: 'Penjualan', purchase: 'Pembelian', project_contract: 'Kontrak Proyek', msa_contract: 'Kontrak MSA' };
const statusLabels = { paid: 'Lunas', unpaid: 'Belum Dibayar', cancelled: 'Dibatalkan' };
const paymentLabels = { dp: 'Uang Muka', full: 'Pembayaran Penuh', termin: 'Termin', sharing_profit: 'Bagi Hasil' };
const currency = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
const formatDate = value => value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
const statusBadge = status => ({ paid: 'badge-success', unpaid: 'badge-warning', cancelled: 'badge-danger' }[status] || 'badge');
let searchTimer;
let latestRequest = 0;
const loadTransactions = async (page = 1) => {
  const requestId = ++latestRequest;
  error.value = '';
  if (filters.value.start_date && filters.value.end_date && filters.value.start_date > filters.value.end_date) {
    error.value = 'Tanggal akhir harus sama atau setelah tanggal awal.';
    loading.value = false;
    return;
  }
  loading.value = true;
  try {
    const response = await api.get('/payments', { params: { ...filters.value, page } });
    if (requestId === latestRequest) transactions.value = response.data;
  } catch (err) {
    if (requestId === latestRequest) error.value = err.response?.data?.message || 'Gagal memuat transaksi.';
  } finally {
    if (requestId === latestRequest) loading.value = false;
  }
};
const applyFilters = () => { clearTimeout(searchTimer); loadTransactions(); };
const searchTransactions = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadTransactions(), 300); };
const resetFilters = () => { filters.value = defaults(); applyFilters(); };
onMounted(() => loadTransactions());
onUnmounted(() => { clearTimeout(searchTimer); latestRequest++; });
</script>
