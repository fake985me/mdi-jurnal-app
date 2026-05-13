<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Warranty Management</h2>
        <p class="text-sm text-gray-600 mt-1">Kelola garansi produk berdasarkan penjualan</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center"><span class="text-lg">🛡️</span></div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
            <p class="text-xs text-gray-500">Total</p>
          </div>
        </div>
      </div>
      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center"><span class="text-lg">✅</span></div>
          <div>
            <p class="text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
            <p class="text-xs text-gray-500">Active</p>
          </div>
        </div>
      </div>
      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center"><span class="text-lg">⏰</span></div>
          <div>
            <p class="text-2xl font-bold text-red-600">{{ stats.expired }}</p>
            <p class="text-xs text-gray-500">Expired</p>
          </div>
        </div>
      </div>
      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center"><span class="text-lg">📋</span></div>
          <div>
            <p class="text-2xl font-bold text-amber-600">{{ stats.claimed }}</p>
            <p class="text-xs text-gray-500">Claimed</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="flex flex-wrap gap-3 items-center">
        <input v-model="filters.search" @input="debouncedLoad" type="text" placeholder="Cari serial number, produk, atau invoice..." class="input flex-1 min-w-[250px]" />
        <select v-model="filters.status" @change="loadWarranties" class="input w-auto">
          <option value="">Semua Status</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="claimed">Claimed</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-12 text-center">
      <div class="inline-flex items-center gap-2 text-gray-500">
        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Loading...
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!warranties.data?.length" class="card p-12 text-center">
      <div class="text-5xl mb-4">🛡️</div>
      <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum Ada Warranty</h3>
      <p class="text-sm text-gray-500">Warranty akan otomatis dibuat saat serial number diinput di halaman Delivery.</p>
    </div>

    <!-- Grouped Warranties by Sale -->
    <div v-else class="space-y-4">
      <div v-for="(group, saleId) in groupedWarranties" :key="saleId" class="card overflow-hidden">

        <!-- Sale Group Header -->
        <div @click="toggleGroup(saleId)"
          class="flex items-center justify-between px-5 py-3.5 cursor-pointer transition-colors duration-150"
          :class="expanded[saleId] ? 'bg-purple-50 border-b border-purple-100' : 'bg-gray-50 hover:bg-purple-50 border-b border-gray-100'">
          <div class="flex items-center gap-3">
            <!-- Arrow -->
            <svg class="w-4 h-4 text-purple-500 transition-transform duration-200" :class="{ 'rotate-90': expanded[saleId] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <!-- Invoice info -->
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-900">{{ group.invoice }}</span>
                <span v-if="group.customer" class="text-xs text-gray-500">· {{ group.customer }}</span>
              </div>
              <p class="text-[11px] text-gray-400 mt-0.5">
                {{ formatDate(group.saleDate) }} · {{ group.items.length }} warranty
              </p>
            </div>
          </div>
          <!-- Status pills -->
          <div class="flex items-center gap-1.5">
            <span v-if="group.activeCount" class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-100 text-emerald-700">{{ group.activeCount }} active</span>
            <span v-if="group.expiredCount" class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-700">{{ group.expiredCount }} expired</span>
            <span v-if="group.claimedCount" class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-700">{{ group.claimedCount }} claimed</span>
          </div>
        </div>

        <!-- Warranty Items (collapsible) -->
        <div v-show="expanded[saleId]" class="divide-y divide-gray-100">
          <div v-for="w in group.items" :key="w.id"
            class="flex items-center px-5 py-3 hover:bg-gray-50 transition-colors duration-100 group">
            <!-- Status dot -->
            <div class="w-2 h-2 rounded-full mr-3 flex-shrink-0" :class="{
              'bg-emerald-500': w.status === 'active',
              'bg-red-400': w.status === 'expired',
              'bg-amber-400': w.status === 'claimed',
            }"></div>

            <!-- Product + Serial -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-800 truncate">{{ w.product?.title || '-' }}</span>
                <span v-if="w.status === 'active' && getDaysLeft(w.end_date) <= 30"
                  class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium flex-shrink-0">
                  {{ getDaysLeft(w.end_date) }}d left
                </span>
              </div>
              <div class="flex items-center gap-3 mt-0.5 text-[11px] text-gray-400">
                <span v-if="w.serial_number" class="font-mono text-gray-600">SN: {{ w.serial_number }}</span>
                <span>{{ formatDate(w.start_date) }} — {{ formatDate(w.end_date) }}</span>
                <span :class="getStatusClass(w.status)" class="uppercase font-semibold">{{ w.status }}</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
              <button @click.stop="openEdit(w)" class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button @click.stop="deleteWarranty(w.id)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="warranties.data?.length" class="card px-6 py-4 flex justify-between items-center">
        <p class="text-sm text-gray-700">{{ warranties.from }} - {{ warranties.to }} dari {{ warranties.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadWarranties(warranties.current_page - 1)" :disabled="!warranties.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadWarranties(warranties.current_page + 1)" :disabled="!warranties.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="editModal = false">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit Warranty</h3>
        <p class="text-xs text-gray-500 mb-4">{{ editProductName }}</p>
        <form @submit.prevent="saveEdit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
            <input v-model="editForm.serial_number" type="text" class="input font-mono" placeholder="Serial number" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
              <input v-model="editForm.start_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
              <input v-model="editForm.end_date" type="date" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="editForm.status" class="input">
              <option value="active">Active</option>
              <option value="expired">Expired</option>
              <option value="claimed">Claimed</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="editForm.notes" rows="2" class="input" placeholder="Catatan..."></textarea>
          </div>
          <div v-if="editError" class="bg-red-50 text-red-600 p-2 rounded text-sm">{{ editError }}</div>
          <div class="flex justify-end gap-3 pt-2 border-t">
            <button type="button" @click="editModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="editSaving" class="btn-primary">{{ editSaving ? 'Saving...' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="successMsg" class="fixed bottom-4 right-4 bg-green-600 text-white px-5 py-3 rounded-lg shadow-xl z-50 flex items-center gap-2 text-sm">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      {{ successMsg }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';

const warranties = ref({ data: [] });
const loading = ref(true);
const expanded = ref({});
const editModal = ref(false);
const editSaving = ref(false);
const editError = ref('');
const editingId = ref(null);
const editProductName = ref('');
const successMsg = ref('');
const filters = ref({ search: '', status: '' });

const editForm = ref({ serial_number: '', start_date: '', end_date: '', status: 'active', notes: '' });

// Stats from current page data
const stats = computed(() => {
  const data = warranties.value.data || [];
  return {
    total: warranties.value.total || data.length,
    active: data.filter(w => w.status === 'active').length,
    expired: data.filter(w => w.status === 'expired').length,
    claimed: data.filter(w => w.status === 'claimed').length,
  };
});

// Group warranties by sale_id
const groupedWarranties = computed(() => {
  const data = warranties.value.data || [];
  if (!data.length) return {};

  const groups = {};
  for (const w of data) {
    const key = w.sale_id || 'no-sale';
    if (!groups[key]) {
      groups[key] = {
        invoice: w.sale?.invoice_number || 'Tanpa Invoice',
        customer: w.sale?.customer_name || w.sale?.customer?.name || '',
        saleDate: w.sale?.sale_date || w.sale?.created_at || null,
        items: [],
        activeCount: 0,
        expiredCount: 0,
        claimedCount: 0,
      };
    }
    groups[key].items.push(w);
    if (w.status === 'active') groups[key].activeCount++;
    else if (w.status === 'expired') groups[key].expiredCount++;
    else if (w.status === 'claimed') groups[key].claimedCount++;
  }
  return groups;
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getDaysLeft = (endDate) => {
  if (!endDate) return 0;
  return Math.max(0, Math.ceil((new Date(endDate) - new Date()) / (1000 * 60 * 60 * 24)));
};

const getStatusClass = (status) => ({
  active: 'text-emerald-600',
  expired: 'text-red-500',
  claimed: 'text-amber-600',
}[status] || 'text-gray-500');

const toggleGroup = (saleId) => {
  expanded.value[saleId] = !expanded.value[saleId];
};

let debounceTimer;
const debouncedLoad = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadWarranties(), 300);
};

const showSuccess = (msg) => {
  successMsg.value = msg;
  setTimeout(() => { successMsg.value = ''; }, 3000);
};

const loadWarranties = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warranties', { params: { page, per_page: 100, ...filters.value } });
    warranties.value = response.data;
    // Auto-expand all groups
    for (const w of (warranties.value.data || [])) {
      const key = w.sale_id || 'no-sale';
      if (expanded.value[key] === undefined) expanded.value[key] = true;
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const openEdit = (w) => {
  editingId.value = w.id;
  editProductName.value = w.product?.title || '-';
  editForm.value = {
    serial_number: w.serial_number || '',
    start_date: w.start_date?.split('T')[0] || '',
    end_date: w.end_date?.split('T')[0] || '',
    status: w.status,
    notes: w.notes || '',
  };
  editError.value = '';
  editModal.value = true;
};

const saveEdit = async () => {
  editSaving.value = true;
  editError.value = '';
  try {
    await api.put(`/warranties/${editingId.value}`, editForm.value);
    editModal.value = false;
    loadWarranties(warranties.value.current_page);
    showSuccess('Warranty berhasil diupdate');
  } catch (err) {
    editError.value = err.response?.data?.message || 'Gagal menyimpan';
  } finally {
    editSaving.value = false;
  }
};

const deleteWarranty = async (id) => {
  if (!confirm('Hapus warranty ini?')) return;
  try {
    await api.delete(`/warranties/${id}`);
    loadWarranties(warranties.value.current_page);
    showSuccess('Warranty berhasil dihapus');
  } catch (err) {
    alert('Gagal menghapus warranty');
  }
};

onMounted(() => loadWarranties());
</script>

<style scoped>
.rotate-90 { transform: rotate(90deg); }
</style>
