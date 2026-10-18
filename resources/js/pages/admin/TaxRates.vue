<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ $t('ui.taxManagement') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('ui.manageTaxRatesVatWithholdingTaxEtcUsedInSalesTransactions') }}</p>
      </div>
      <button @click="openModal()" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ $t('ui.addTax') }}
      </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-16 text-gray-400 gap-3">
        <svg class="animate-spin w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        {{ $t('ui.loadingTaxData') }}
      </div>

      <!-- Empty -->
      <div v-else-if="!taxRates.length" class="flex flex-col items-center justify-center py-20 text-gray-400">
        <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
        </svg>
        <p class="font-medium">{{ $t('ui.noTaxDataYet') }}</p>
        <p class="text-xs mt-1">{{ $t('ui.clickAddTaxToAddANewRate') }}</p>
      </div>

      <!-- Table -->
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('ui.taxName') }}</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('ui.code') }}</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('ui.rate') }}</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('projectInvestment.description') }}</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="tax in taxRates" :key="tax.id" class="hover:bg-gray-50/60 transition-colors">
            <!-- Name -->
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold shrink-0"
                  :class="tax.code === 'ppn' ? 'bg-indigo-100 text-indigo-600' : tax.code === 'pph23' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'">
                  %
                </div>
                <div>
                  <div class="font-semibold text-gray-900">{{ tax.name }}</div>
                  <div class="text-xs text-gray-400">ID: {{ tax.id }}</div>
                </div>
              </div>
            </td>
            <!-- Code -->
            <td class="px-5 py-4">
              <code class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-mono">{{ tax.code }}</code>
            </td>
            <!-- Rate -->
            <td class="px-5 py-4 text-center">
              <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-bold"
                :class="tax.code === 'ppn' ? 'bg-indigo-50 text-indigo-700' : tax.code === 'pph23' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'">
                {{ parseFloat(tax.rate).toFixed(1) }}%
              </span>
            </td>
            <!-- Description -->
            <td class="px-5 py-4 text-gray-500 text-sm max-w-xs truncate">{{ tax.description || '—' }}</td>
            <!-- Status -->
            <td class="px-5 py-4 text-center">
              <button @click="toggleTax(tax)"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all"
                :class="tax.is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                <span class="w-1.5 h-1.5 rounded-full" :class="tax.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                {{ tax.is_active ? $t('labels.active') : $t('ui.inactive') }}
              </button>
            </td>
            <!-- Actions -->
            <td class="px-5 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="openModal(tax)" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" :title="$t('common.edit')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button @click="deleteTax(tax)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" :title="$t('common.delete')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3 text-sm text-blue-800">
      <svg class="w-5 h-5 shrink-0 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <div>
        <p class="font-semibold">{{ $t('ui.howTaxesWork') }}</p>
        <p class="text-xs text-blue-600 mt-1">{{ $t('ui.activeTaxRatesAppearAsOptionsWhenCreatingSalesInvoicesTaxCodesSuchAs') }} <code class="bg-blue-100 px-1 rounded">ppn</code> {{ $t('ui.or') }} <code class="bg-blue-100 px-1 rounded">pph23</code>{{ $t('ui.isUsedForAutomaticCalculations') }}</p>
      </div>
    </div>

    <!-- Modal Add/Edit -->
    <Transition name="modal-fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="closeModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
              <h3 class="text-lg font-bold text-gray-900">{{ editingTax ? $t('ui.editTax') : $t('ui.addNewTax') }}</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ editingTax ? $t('ui.updateTaxRateData') : $t('ui.createANewTaxRate') }}</p>
            </div>
            <button @click="closeModal" class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Modal Body -->
          <form @submit.prevent="saveTax" class="px-6 py-5 space-y-4">
            <!-- Error -->
            <div v-if="formError" class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
              <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              {{ $ui(formError) }}
            </div>

            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.taxName') }} <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" required :placeholder="$t('ui.eGVat11')"
                class="block w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
            </div>

            <!-- Code -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('ui.uniqueCode') }} <span class="text-red-500">*</span>
                <span class="text-xs text-gray-400 font-normal ml-1">{{ $t('ui.lowercaseTanpaSpasi') }}</span>
              </label>
              <input v-model="form.code" type="text" required :placeholder="$t('ui.eGPpnPph23')"
                pattern="[a-z0-9_]+"
                :title="$t('ui.lowercaseLettersNumbersAndUnderscoresOnly')"
                :disabled="!!editingTax"
                class="block w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition font-mono"
                :class="editingTax ? 'bg-gray-50 text-gray-400' : ''" />
              <p v-if="editingTax" class="text-xs text-gray-400 mt-1">{{ $t('ui.theCodeCannotBeChangedAfterSaving') }}</p>
            </div>

            <!-- Rate -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.rate') }} <span class="text-red-500">*</span></label>
              <div class="relative">
                <input v-model.number="form.rate" type="number" required min="0" max="100" step="0.01"
                  :placeholder="$t('ui.eG11')"
                  class="block w-full border border-gray-300 rounded-xl px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">%</span>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.description') }}</label>
              <textarea v-model="form.description" rows="2" :placeholder="$t('ui.briefDescriptionOfThisTax')"
                class="block w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition resize-none"></textarea>
            </div>

            <!-- Active toggle -->
            <div class="flex items-center gap-3">
              <button type="button" @click="form.is_active = !form.is_active"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                :class="form.is_active ? 'bg-indigo-600' : 'bg-gray-200'">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                  :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
              </button>
              <label class="text-sm font-medium text-gray-700">{{ form.is_active ? $t('ui.activeShownInDropdown') : $t('ui.inactiveHidden') }}</label>
            </div>
          </form>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
              {{ $t('common.cancel') }}
            </button>
            <button @click="saveTax" :disabled="saving" class="px-5 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition disabled:opacity-50 flex items-center gap-2">
              <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              {{ saving ? $t('sales.saving') : (editingTax ? $t('ui.saveChanges') : $t('ui.addTax')) }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import api from '../../services/api';

const taxRates = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editingTax = ref(null);
const formError = ref('');

const defaultForm = () => ({
  name: '',
  code: '',
  rate: '',
  description: '',
  is_active: true,
});

const form = ref(defaultForm());

const loadTaxRates = async () => {
  loading.value = true;
  try {
    const res = await api.get('/tax-rates');
    taxRates.value = res.data;
  } catch (e) {
    console.error('Gagal memuat tarif pajak:', e);
  } finally {
    loading.value = false;
  }
};

const openModal = (tax = null) => {
  editingTax.value = tax;
  formError.value = '';
  if (tax) {
    form.value = {
      name: tax.name,
      code: tax.code,
      rate: parseFloat(tax.rate),
      description: tax.description || '',
      is_active: tax.is_active,
    };
  } else {
    form.value = defaultForm();
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingTax.value = null;
  formError.value = '';
};

const saveTax = async () => {
  if (!form.value.name || !form.value.code || form.value.rate === '') {
    formError.value = uiText("Nama, kode, dan tarif wajib diisi.");
    return;
  }
  saving.value = true;
  formError.value = '';
  try {
    if (editingTax.value) {
      await api.put(`/tax-rates/${editingTax.value.id}`, form.value);
    } else {
      await api.post('/tax-rates', form.value);
    }
    closeModal();
    await loadTaxRates();
  } catch (e) {
    const errs = e.response?.data?.errors;
    if (errs) {
      formError.value = Object.values(errs).flat().join(', ');
    } else {
      formError.value = e.response?.data?.message || uiText("Gagal menyimpan data pajak.");
    }
  } finally {
    saving.value = false;
  }
};

const toggleTax = async (tax) => {
  try {
    const res = await api.post(`/tax-rates/${tax.id}/toggle`);
    const idx = taxRates.value.findIndex(t => t.id === tax.id);
    if (idx !== -1) taxRates.value[idx] = res.data;
  } catch (e) {
    alert(uiText("Gagal mengubah status pajak."));
  }
};

const deleteTax = async (tax) => {
  if (!confirm(uiText('ui.deleteTaxNamed', { p0: tax.name }))) return;
  try {
    await api.delete(`/tax-rates/${tax.id}`);
    taxRates.value = taxRates.value.filter(t => t.id !== tax.id);
  } catch (e) {
    alert(e.response?.data?.message || uiText("Gagal menghapus pajak."));
  }
};

onMounted(loadTaxRates);
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
