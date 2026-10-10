<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Warranty Management</h2>
        <p class="mt-1 text-sm text-gray-500">Daftar garansi produk dari transaksi penjualan.</p>
      </div>
      <p class="text-sm text-gray-500">{{ warranties.total || 0 }} total warranty</p>
    </div>

    <div class="card p-4">
      <div class="flex flex-col gap-3 md:flex-row">
        <input
          v-model="filters.search"
          @input="debouncedLoad"
          type="search"
          placeholder="Cari serial number, produk, atau invoice..."
          class="input flex-1"
        />
        <select v-model="filters.status" @change="loadWarranties" class="input md:w-48">
          <option value="">Semua Status</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="claimed">Claimed</option>
        </select>
      </div>
    </div>

    <div class="table-wrapper">
      <div class="card-header flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Warranty</h3>
        <span class="text-sm text-gray-500">{{ activeCount }} aktif pada halaman ini</span>
      </div>

      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">Memuat warranty...</div>
      <div v-else-if="!warranties.data?.length" class="p-10 text-center">
        <p class="font-medium text-gray-700">Tidak ada warranty ditemukan.</p>
        <p class="mt-1 text-sm text-gray-500">Warranty dibuat otomatis saat serial number diisi di Delivery.</p>
      </div>

      <div v-else class="overflow-x-auto">


        <table class="min-w-full text-sm vsep">
          <thead class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-3 py-3 w-8"></th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('invoice_number')">
                Invoice <span v-if="sortKey==='invoice_number'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('customer_name')">
                Customer <span v-if="sortKey==='customer_name'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('sale_date')">
                Tanggal <span v-if="sortKey==='sale_date'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left">Produk</th>
              <th class="px-4 py-3 text-left cursor-pointer select-none" @click="setSort('status')">
                Status <span v-if="sortKey==='status'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-left">Pajak</th>
              <th class="px-4 py-3 text-left">Delivery</th>
              <th class="px-4 py-3 text-right cursor-pointer select-none" @click="setSort('grand_total')">
                Total <span v-if="sortKey==='grand_total'" class="text-[10px] text-indigo-500">{{ sortDir > 0 ? '?' : '?' }}</span>
              </th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <template v-for="group in groupedWarranties" :key="group.key">
              <!-- Main Row -->
              <tr :class="['transition-colors cursor-pointer', idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white', 'hover:bg-indigo-50/40']"
                @click="toggleExpand(group.key)">
                <td class="px-3 py-3 text-center">
                  <span class="inline-block text-slate-400 transition-transform duration-200"
                    :style="expanded[group.key] ? 'transform:rotate(90deg)' : ''">&#9658;</span>
                </td>
                <td class="px-4 py-3 align-top">
                  <div class="font-semibold text-slate-900 whitespace-nowrap">{{ group.invoice }}</div>
                  <div class="text-[11px] text-slate-400">ID: {{ group.key }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div :class="['w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-sm shrink-0', avatarColor(sale.customer_name)]">
                      {{ (sale.customer_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="font-semibold truncate text-slate-900">{{ sale.customer_name || '-' }}</div>
                      <div class="text-[11px] text-slate-400 truncate">{{ sale.customer_phone || '' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ formatDate(sale.sale_date) }}</td>
                <td class="px-4 py-3">
                  <template v-if="sale.items && sale.items.length">
                    <div v-for="(item, i) in sale.items.slice(0,2)" :key="i" class="text-xs text-slate-700">
                      {{ item.product ? item.product.title : 'Produk' }} <span class="text-slate-400">x{{ item.quantity }}</span>
                    </div>
                    <div v-if="sale.items.length > 2" class="text-[11px] text-slate-400">+{{ sale.items.length - 2 }} lainnya</div>
                  </template>
                  <span v-else class="text-slate-400 text-xs">-</span>
                </td>
                <td class="px-4 py-3">
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', statusClr(sale.status)]">
                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5"></span>
                    {{ capitalize(sale.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="sale.tax_type"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                    {{ taxDisplayName(sale.tax_type) }} {{ sale.tax_rate }}%
                  </span>
                  <span v-else class="text-slate-300 text-xs">-</span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="sale.delivery"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700 ring-1 ring-teal-200">
                    &#128666; {{ sale.delivery.tracking_number }}
                  </span>
                  <span v-else class="text-slate-300 text-xs">No delivery</span>
                </td>
                <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                  Rp {{ formatInt(sale.grand_total || sale.total_amount) }}
                </td>
                <td class="px-4 py-3 text-right" @click.stop>
                  <div class="flex justify-end gap-1.5 flex-wrap">
                    <button v-if="!sale.delivery && ['pending','completed'].includes(sale.status)"
                      @click="$router.push({ name: 'SalesEdit', params: { id: sale.id } })"
                      class="px-2 py-1 text-xs rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 font-medium">
                      + Delivery
                    </button>
                    <button @click="editSale(sale)"
                      class="px-2 py-1 text-xs rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium">Edit</button>
                    <button @click="$router.push({ name: 'SalesEdit', params: { id: sale.id } })"
                      class="px-2 py-1 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium">View</button>
                    <button @click="deleteSale(sale.id)"
                      class="px-2 py-1 text-xs rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-medium">Hapus</button>
                  </div>
                </td>
              </tr>

              <!-- Expand Detail Row -->
              <tr v-if="expanded[sale.id]" class="bg-white">
                <td colspan="10" class="px-0 py-0">
                  <div class="p-4 bg-indigo-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">Produk Dipesan</div>
                        <ul class="space-y-1 text-sm">
                          <li v-for="item in (sale.items || [])" :key="item.id"
                            class="flex items-center justify-between text-slate-700">
                            <span class="truncate mr-2">{{ item.product ? item.product.title : (item.product_name || 'Produk') }}</span>
                            <span class="text-slate-400 shrink-0">x {{ item.quantity }}</span>
                          </li>
                          <li v-if="!sale.items || !sale.items.length" class="text-slate-400">Tidak ada item</li>
                        </ul>
                      </div>
                      <div class="border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">Info Transaksi</div>
                        <div class="space-y-1.5 text-sm">
                          <div class="flex justify-between">
                            <span class="text-slate-500">Invoice</span>
                            <span class="font-semibold">{{ sale.invoice_number }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-slate-500">Tanggal</span>
                            <span>{{ formatDate(sale.sale_date) }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-slate-500">Subtotal</span>
                            <span>Rp {{ formatInt(sale.subtotal || sale.total_amount) }}</span>
                          </div>
                          <div v-if="sale.tax_type" class="flex justify-between">
                            <span class="text-slate-500">Pajak</span>
                            <span>{{ taxDisplayName(sale.tax_type) }} {{ sale.tax_rate }}%</span>
                          </div>
                          <div class="flex justify-between border-t pt-1.5 font-bold">
                            <span class="text-slate-700">Grand Total</span>
                            <span class="text-indigo-700">Rp {{ formatInt(sale.grand_total || sale.total_amount) }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="border border-slate-200 rounded-xl bg-white p-3">
                        <div class="text-xs uppercase text-slate-500 font-bold mb-2">Pengiriman &amp; Gudang</div>
                        <div class="space-y-1.5 text-sm">
                          <div class="flex justify-between">
                            <span class="text-slate-500">Gudang</span>
                            <span>{{ sale.warehouse ? sale.warehouse.name : '-' }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-slate-500">Status</span>
                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset', statusClr(sale.status)]">
                              {{ capitalize(sale.status) }}
                            </span>
                          </div>
                          <template v-if="sale.delivery">
                            <div class="flex justify-between">
                              <span class="text-slate-500">Tracking</span>
                              <span class="font-mono text-xs">{{ sale.delivery.tracking_number }}</span>
                            </div>
                            <div class="flex justify-between">
                              <span class="text-slate-500">Kurir</span>
                              <span>{{ sale.delivery.courier_name || '-' }}</span>
                            </div>
                          </template>
                          <div v-else class="text-slate-400 text-xs">Belum ada pengiriman</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="!loading && pagedSales.length === 0">
              <td colspan="10" class="py-16 text-center text-slate-400">
                <div class="text-4xl mb-3">&#128203;</div>
                <div class="text-sm font-medium">Tidak ada data penjualan</div>
                <div class="text-xs mt-1">Coba ubah filter atau buat penjualan baru</div>
              </td>
            </tr>
          </tbody>
        </table>





        <table class="min-w-full">
          <thead class="table-header">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Invoice</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Produk</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Serial Number</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Masa Garansi</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Aksi</th>
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
                    <span class="text-xs text-indigo-700">{{ group.items.length }} warranty</span>
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
                  <p class="mt-1 text-xs text-gray-400">sampai {{ formatDate(warranty.end_date) }}</p>
                </td>
                <td class="px-6 py-4"><span :class="getStatusBadge(warranty.status)">{{ formatStatus(warranty.status) }}</span></td>
                <td class="px-6 py-4 text-right text-sm">
                  <button @click="editWarranty(warranty.id)" class="mr-4 font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                  <button @click="deleteWarranty(warranty.id)" class="font-medium text-red-600 hover:text-red-800">Hapus</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div v-if="warranties.data?.length" class="flex items-center justify-between bg-gray-50 px-6 py-4">
        <p class="text-sm text-gray-600">Menampilkan {{ warranties.from }}–{{ warranties.to }} dari {{ warranties.total }}</p>
        <div class="flex gap-2">
          <button @click="loadWarranties(warranties.current_page - 1)" :disabled="!warranties.prev_page_url" class="btn-secondary disabled:opacity-50">Sebelumnya</button>
          <button @click="loadWarranties(warranties.current_page + 1)" :disabled="!warranties.next_page_url" class="btn-secondary disabled:opacity-50">Berikutnya</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const warranties = ref({ data: [] });
const loading = ref(true);

const allSales     = ref([])

const activeTab    = ref('sales')
const searchQuery  = ref('')
const statusFilter = ref('all')
const dateFrom     = ref('')
const dateTo       = ref('')
const sortKey      = ref('sale_date')
const sortDir      = ref(-1)
const currentPage  = ref(1)
const pageSize     = ref(10)
const expanded     = ref({})

const statusOptions = ['all', 'pending', 'completed', 'cancelled']
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
        invoice: warranty.sale?.invoice_number || 'Tanpa Invoice',
        customer: warranty.sale?.customer_name || '',
        items: [],
      });
    }
    groups.get(key).items.push(warranty);
  }

  return [...groups.values()];
});
const formatDate = (date) => date ? new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
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
    console.error('Failed to load warranties:', err);
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
  if (!confirm('Hapus warranty ini?')) return;
  try {
    await api.delete(`/warranties/${id}`);
    await loadWarranties(warranties.value.data.length === 1 && warranties.value.current_page > 1
      ? warranties.value.current_page - 1
      : warranties.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus warranty.');
  }
};

onMounted(loadWarranties);



const loadSales = async () => {
  loading.value = true
  try {
    const response = await api.get('/sales', { params: { per_page: 1000 } })
    allSales.value = response.data.data || response.data || []
  } catch (err) {
    console.error('Failed to load sales:', err)
  } finally {
    loading.value = false
  }
}

const filteredSales = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return allSales.value.filter(s => {
    if (statusFilter.value !== 'all' && s.status !== statusFilter.value) return false
    if (dateFrom.value && s.sale_date < dateFrom.value) return false
    if (dateTo.value   && s.sale_date > dateTo.value)   return false
    if (q) {
      const hay = ((s.invoice_number || '') + ' ' + (s.customer_name || '') + ' ' + (s.customer_phone || '')).toLowerCase()
      if (!hay.includes(q)) return false
    }
    return true
  })
})

const sortedSales = computed(() => {
  const key = sortKey.value
  return [...filteredSales.value].sort((a, b) => {
    let va = a[key] != null ? a[key] : ''
    let vb = b[key] != null ? b[key] : ''
    if (key === 'grand_total' || key === 'total_amount') {
      return (Number(va) - Number(vb)) * sortDir.value
    }
    return String(va).localeCompare(String(vb)) * sortDir.value
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSales.value.length / pageSize.value)))

const pagedSales = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return sortedSales.value.slice(start, start + pageSize.value)
})

const visiblePages = computed(() => {
  const tp = totalPages.value, cp = currentPage.value
  const pages = [], w = 7
  let start = Math.max(1, cp - Math.floor(w / 2))
  let end   = Math.min(tp, start + w - 1)
  start = Math.max(1, end - w + 1)
  for (let p = start; p <= end; p++) pages.push(p)
  return pages
})

const allTotal      = computed(() => allSales.value.reduce((s, x) => s + (Number(x.grand_total || x.total_amount) || 0), 0))
const filteredTotal = computed(() => filteredSales.value.reduce((s, x) => s + (Number(x.grand_total || x.total_amount) || 0), 0))
</script>
