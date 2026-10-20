<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-xs text-slate-500 mb-1">{{ $t('purchasePage.workspace') }}</p>
        <h2 class="text-2xl font-semibold text-slate-900">{{ $t('pageTitles.Purchases') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $t('purchasePage.subtitle') }}</p>
      </div>
      <Menu as="div" class="relative">
        <MenuButton class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
          <PlusIcon class="h-5 w-5" />{{ $t('purchasePage.create') }}<ChevronDownIcon class="h-4 w-4" />
        </MenuButton>
        <MenuItems class="absolute right-0 z-20 mt-2 w-64 rounded-lg border border-slate-200 bg-white p-1 shadow-lg focus:outline-none">
          <MenuItem v-for="type in purchaseTypes" :key="type.value" v-slot="{ active }">
            <button @click="createPurchase(type.value)" :class="['w-full rounded-md px-3 py-3 text-left text-sm', active ? 'bg-blue-50 text-blue-700' : 'text-slate-700']">
              {{ $t(type.label) }}
            </button>
          </MenuItem>
        </MenuItems>
      </Menu>
    </header>
    <div class="grid gap-4 sm:grid-cols-3">
      <div v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $t(card.label) }}</p>
        <p class="mt-3 text-xl font-semibold text-slate-900">{{ loading ? '…' : card.value }}</p>
        <p class="mt-2 text-xs text-slate-400">{{ $t('purchasePage.filteredSummary') }}</p>
      </div>
    </div>
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="flex overflow-x-auto border-b border-slate-200 w-full" :aria-label="$t('purchasePage.type')">
        <button v-for="tab in tabs" :key="tab.value" 
          @click="setType(tab.value)" :aria-pressed="filters.transaction_type === tab.value" 
          :class="['whitespace-nowrap border-b-2 px-5 py-4 text-sm font-medium', filters.transaction_type === tab.value ? 'border-blue-600 text-blue-600 bg-blue-50/40' 
          : 'border-transparent text-slate-500 hover:text-blue-600']">
          {{ $t(tab.label) }}
        </button>
      </div>
      <div class="p-5 space-y-4 w-full">
        <div class="flex flex-wrap justify-between gap-3 items-center">
          <h3 class="font-semibold text-slate-800">
            {{ $t('purchasePage.list') }}
          </h3>
          <input v-model="filters.search" :placeholder="$t('ui.searchByPoOrSupplier')" :aria-label="$t('ui.searchByPoOrSupplier')" class="field w-full sm:w-80" />
        </div>
        <div class="flex flex-wrap items-end gap-3">
          <label class="text-xs text-slate-500">
            {{ $t('projectInvestment.materials.status') }}
            <select v-model="filters.status" class="field block mt-1">
              <option value="">{{ $t('sales.allStatus') }}</option>
              <option v-for="state in statusOptions" :key="state" :value="state">{{ state === 'recorded' ? $t('purchasePage.recorded') : $t(`labels.${state}`) }}</option>
            </select>
          </label>
          <label class="text-xs text-slate-500">
            {{ $t('purchasePage.fromDate') }}
            <input v-model="filters.start_date" type="date" class="field block mt-1" />
          </label>
          <label class="text-xs text-slate-500">
            {{ $t('purchasePage.toDate') }}
            <input v-model="filters.end_date" type="date" :min="filters.start_date" class="field block mt-1" />
          </label>
          <button @click="resetFilters" class="field text-blue-600 hover:bg-slate-50">{{ $t('ui.reset') }}</button>
          <label class="ml-auto text-xs text-slate-500">
            {{ $t('ui.rows') }}
            <select v-model.number="filters.per_page" class="field ml-2">
              <option v-for="size in [10,15,25,50]" :key="size" :value="size">
                {{ size }}
              </option>
            </select>
          </label>
        </div>
      </div>
      <div v-if="loadError" role="alert" class="m-5 rounded-lg bg-red-50 p-4 text-sm text-red-700 flex justify-between gap-3">
        {{ loadError }}
        <button @click="loadPurchases()" class="font-semibold underline">
          {{ $t('purchasePage.retry') }}
        </button>
      </div>
      <div v-else-if="loading" class="p-14 text-center text-sm text-slate-500" role="status">
        {{ $t('ui.loadingPurchases') }}
      </div>
      <div v-else-if="!purchases.data.length" class="p-14 text-center">
        <DocumentTextIcon class="mx-auto h-10 w-10 text-slate-300" />
        <p class="mt-3 font-medium text-slate-700">{{ $t('purchasePage.empty') }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $t('purchasePage.emptyHint') }}</p>
      </div>
        <div v-else class="overflow-x-auto w-full">
          <table class="w-full whitespace-nowrap text-sm">
            <thead class="border-y border-slate-200 bg-slate-50 text-xs text-slate-500">
              <tr>
                <th class="cell text-left">{{ $t('sales.date') }}</th>
                <th class="cell text-left">{{ $t('purchasePage.number') }}</th>
                <th class="cell text-left">{{ $t('purchasePage.type') }}</th>
                <th class="cell text-left">{{ $t('ui.supplier') }}</th>
                <th class="cell text-left">{{ $t('projectInvestment.materials.status') }}</th>
                <th class="cell text-left">{{ $t('nav.warehouses') }}</th>
                <th class="cell text-right">{{ $t('sales.total') }}</th>
                <th class="cell text-right">{{ $t('sales.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="purchase in purchases.data" :key="purchase.id" class="hover:bg-blue-50/30">
                <td class="cell text-slate-500">
                  {{ displayDate(purchase.order_date) }}
                </td>
                <td class="cell">
                  <button @click="selectedPurchase = purchase" class="font-medium text-blue-600 hover:underline">
                    {{ purchase.po_number }}
                  </button>
                </td>
                <td class="cell text-slate-600">
                  {{ typeLabel(purchase.transaction_type) }}
                </td>
                <td class="cell">
                  <p class="font-medium text-slate-800">{{ purchase.supplier?.company || purchase.supplier?.name || purchase.supplier_name }}</p>
                  <p class="mt-1 text-xs text-slate-400">{{ purchase.supplier?.phone || purchase.supplier_phone }}</p>
                </td>
                <td class="cell">
                  <span :class="statusClass(purchase)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">{{ statusLabel(purchase) }}</span>
                </td>
                <td class="cell text-slate-500">
                  {{ purchase.warehouse?.name || '—' }}
                </td>
                <td class="cell text-right font-medium">
                  {{ money(purchase.total_amount) }}
                </td>
                <td class="cell text-right">
                  <button @click="selectedPurchase = purchase" class="text-blue-600 mr-4">{{ $t('projectInvestment.actions.view') }}</button>
                  <button @click="deletePurchase(purchase)" :disabled="deleting === purchase.id" class="text-red-600 disabled:opacity-40">{{ $t('common.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="purchases.total && !loadError && !loading" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 text-sm text-slate-500">
          <p>{{ $t('sales.showing') }} {{ purchases.from }}–{{ purchases.to }} {{ $t('sales.of') }} {{ purchases.total }}</p>
          <div class="flex items-center gap-3">
            <button @click="loadPurchases(purchases.current_page - 1)" :disabled="!purchases.prev_page_url" class="field disabled:opacity-40">
              {{ $t('sales.previous') }}
            </button>
            <span>{{ purchases.current_page }} / {{ purchases.last_page }}</span>
            <button @click="loadPurchases(purchases.current_page + 1)" :disabled="!purchases.next_page_url" class="field disabled:opacity-40">
              {{ $t('sales.next') }}
            </button>
          </div>
        </div>
    </section>
    <Dialog :open="!!selectedPurchase" @close="selectedPurchase = null" class="relative z-50">
      <div class="fixed inset-0 bg-slate-900/40" aria-hidden="true" />
        <div class="fixed inset-0 overflow-y-auto p-4 flex items-center justify-center">
          <DialogPanel v-if="selectedPurchase" class="w-full max-w-3xl rounded-xl bg-white p-6 shadow-xl max-h-[90vh] overflow-auto">
            <div class="flex justify-between gap-4">
              <div>
                <p class="text-sm text-slate-500">
                  {{ typeLabel(selectedPurchase.transaction_type) }}
                </p>
                <DialogTitle class="text-xl font-semibold mt-1">
                  {{ selectedPurchase.po_number }}
                </DialogTitle>
              </div>
              <button @click="selectedPurchase = null" :aria-label="$t('common.cancel')">
                 <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
            <div class="grid sm:grid-cols-2 gap-4 my-6 text-sm">
              <div>
                <p class="text-slate-500">{{ $t('ui.supplier') }}</p>
                <p class="mt-1 font-medium">{{ selectedPurchase.supplier?.company || selectedPurchase.supplier?.name || selectedPurchase.supplier_name }}</p>
              </div>
              <div>
                <p class="text-slate-500">{{ $t('sales.date') }}</p>
                <p class="mt-1">{{ displayDate(selectedPurchase.order_date) }}</p>
              </div>
            </div>
            <div class="overflow-auto">
              <table class="w-full text-sm">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="cell text-left">{{ $t('sales.product') }}</th>
                    <th class="cell text-right">{{ $t('sales.quantity') }}</th>
                    <th class="cell text-right">{{ $t('ui.price') }}</th>
                    <th class="cell text-right">{{ $t('sales.subtotal') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in selectedPurchase.items" :key="item.id" class="border-b border-slate-100">
                    <td class="cell">{{ item.product?.title || item.product?.name || '—' }}</td>
                    <td class="cell text-right">{{ item.quantity }}</td>
                    <td class="cell text-right">{{ money(item.unit_price) }}</td>
                    <td class="cell text-right">{{ money(item.subtotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
             <p class="mt-5 text-right font-semibold">{{ $t('sales.total') }}: {{ money(selectedPurchase.total_amount) }}</p>
             <p v-if="selectedPurchase.notes" class="mt-5 whitespace-pre-wrap text-sm text-slate-600">{{ selectedPurchase.notes }}</p>
          </DialogPanel>
        </div>
    </Dialog>
  </div>
</template>
<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Dialog, DialogPanel, DialogTitle, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { PlusIcon, ChevronDownIcon, DocumentTextIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import api from '../../services/api';
import { displayDate, uiLocale } from '../../utils/uiText';
import { purchaseTypes } from '../../utils/purchaseTypes';
const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const tabs = [{ value: '', label: 'purchasePage.all' }, ...purchaseTypes];
const filters = ref({ transaction_type: purchaseTypes.some(type => type.value === route.query.type) ? route.query.type : '', search: '', status: '', start_date: '', end_date: '', per_page: 15 });
const purchases = ref({ data: [], summary: { count: 0, total: 0 } });
const loading = ref(true);
const loadError = ref('');
const selectedPurchase = ref(null);
const deleting = ref(null);
let timer;
let requestId = 0;
const money = value => new Intl.NumberFormat(uiLocale(), { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);
const typeLabel = value => t(purchaseTypes.find(type => type.value === (value || 'order'))?.label || 'purchasePage.order');
const status = purchase => purchase.status === 'cancelled' ? 'cancelled' : ['request', 'quotation'].includes(purchase.transaction_type) ? 'recorded' : purchase.payment_status || purchase.status;
const statusLabel = purchase => status(purchase) === 'recorded' ? t('purchasePage.recorded') : t(`labels.${status(purchase)}`);
const statusClass = purchase => ({ paid: 'bg-emerald-50 text-emerald-700', unpaid: 'bg-amber-50 text-amber-700', partial: 'bg-blue-50 text-blue-700', recorded: 'bg-blue-50 text-blue-700', cancelled: 'bg-slate-100 text-slate-500' }[status(purchase)]);
const statusOptions = computed(() => ['request', 'quotation'].includes(filters.value.transaction_type) ? ['recorded', 'cancelled'] : ['unpaid', 'partial', 'paid', 'recorded', 'cancelled']);
const summaryCards = computed(() => [
  { label: 'purchasePage.totalTransactions', value: purchases.value.summary?.count || 0 },
  { label: 'purchasePage.totalValue', value: money(purchases.value.summary?.total) },
  { label: 'purchasePage.activeType', value: filters.value.transaction_type ? typeLabel(filters.value.transaction_type) : t('purchasePage.all') },
]);
const createPurchase = type => router.push({ name: 'PurchaseCreate', query: { type } });
const setType = type => { filters.value.transaction_type = type; filters.value.status = ''; router.replace({ query: { ...route.query, type: type || undefined } }); };
watch(() => route.query.type, type => { filters.value.transaction_type = purchaseTypes.some(option => option.value === type) ? type : ''; });
const resetFilters = () => { Object.assign(filters.value, { search: '', status: '', start_date: '', end_date: '' }); };
const loadPurchases = async (page = 1) => {
  const id = ++requestId;
  loading.value = true;
  loadError.value = '';
  try { const response = await api.get('/purchases', { params: { ...filters.value, page } }); if (id === requestId) purchases.value = response.data; }
  catch (error) { if (id === requestId) loadError.value = t('purchasePage.loadError'); }
  finally { if (id === requestId) loading.value = false; }
};
watch(filters, () => { clearTimeout(timer); ++requestId; loading.value = true; timer = setTimeout(() => loadPurchases(), 300); }, { deep: true });
const deletePurchase = async purchase => {
  if (!confirm(t('purchasePage.confirmDelete', { number: purchase.po_number }))) return;
  deleting.value = purchase.id;
  try { await api.delete(`/purchases/${purchase.id}`); await loadPurchases(purchases.value.data.length === 1 ? Math.max(1, purchases.value.current_page - 1) : purchases.value.current_page); }
  catch (error) { alert(error.response?.data?.message || t('purchasePage.deleteError')); }
  finally { deleting.value = null; }
};
onMounted(() => loadPurchases());
onBeforeUnmount(() => { clearTimeout(timer); ++requestId; });
</script>
<style scoped>
.field { border: 1px solid #e2e8f0; border-radius: 0.5rem; background-color: white; padding: 0.55rem 0.75rem; font-size: 0.875rem; color: #475569; }
.field:focus { outline: 2px solid #93c5fd; outline-offset: 1px; }
.cell { padding: 1rem 1.25rem; }
</style>
