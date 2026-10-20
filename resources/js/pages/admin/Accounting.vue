<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-linear-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
          {{ $t('pageTitles.Accounting') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.manageAllPaymentsAndFinancialTransactions') }}</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="card p-4 bg-linear-to-br from-red-50 to-red-100 border border-red-200">
        <p class="text-sm text-red-600 font-medium">{{ $t('ui.totalUnpaid') }}</p>
        <p class="text-2xl font-bold text-red-800">{{ formatCurrency(summary.total_unpaid) }}</p>
        <p class="text-xs text-red-500 mt-1">{{ summary.count_unpaid }} {{ $t('ui.transactions') }}</p>
      </div>
      <div class="card p-4 bg-linear-to-br from-green-50 to-green-100 border border-green-200">
        <p class="text-sm text-green-600 font-medium">{{ $t('ui.totalPaid') }}</p>
        <p class="text-2xl font-bold text-green-800">{{ formatCurrency(summary.total_paid) }}</p>
        <p class="text-xs text-green-500 mt-1">{{ summary.count_paid }} {{ $t('ui.transactions') }}</p>
      </div>
      <div class="card p-4 bg-linear-to-br from-blue-50 to-blue-100 border border-blue-200">
        <p class="text-sm text-blue-600 font-medium">{{ $t('ui.thisMonthPaid') }}</p>
        <p class="text-2xl font-bold text-blue-800">{{ formatCurrency(summary.this_month_paid) }}</p>
      </div>
      <div class="card p-4 bg-linear-to-br from-amber-50 to-amber-100 border border-amber-200">
        <p class="text-sm text-amber-600 font-medium">{{ $t('ui.totalTaxPaid') }}</p>
        <p class="text-2xl font-bold text-amber-800">{{ formatCurrency(summary.total_paid_tax) }}</p>
        <p class="text-xs text-amber-500 mt-1">{{ $t('ui.thisMonth') }} {{ formatCurrency(summary.this_month_tax) }}</p>
      </div>
      <div class="card p-4 bg-linear-to-br from-gray-50 to-gray-100 border border-gray-200">
        <p class="text-sm text-gray-600 font-medium">{{ $t('ui.totalTransactions') }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ summary.count_all }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input
          v-model="filters.search"
          @input="debouncedLoad"
          type="text"
          :placeholder="$t('ui.searchReferenceMethod')"
          class="input"
        />
        <select v-model="filters.payable_type" @change="loadPayments" class="input">
          <option value="">{{ $t('ui.allTypes') }}</option>
          <option value="purchase">{{ $t('labels.purchase') }}</option>
          <option value="expense">{{ $t('expenses.title') }}</option>
          <option value="sale">{{ $t('labels.sale') }}</option>
          <option value="project_contract">{{ $t('ui.projectContract') }}</option>
          <option value="msa_contract">{{ $t('ui.msaContract') }}</option>
        </select>
        <select v-model="filters.status" @change="loadPayments" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="unpaid">{{ $t('labels.unpaid') }}</option>
          <option value="paid">{{ $t('labels.paid') }}</option>
          <option value="cancelled">{{ $t('labels.cancelled') }}</option>
        </select>
        <input v-model="filters.start_date" @change="loadPayments" type="date" class="input" />
        <input v-model="filters.end_date" @change="loadPayments" type="date" class="input" />
      </div>
    </div>

    <!-- Payments Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.allPayments') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('ui.loadingPayments') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.date') }}</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.type') }}</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.reference') }}</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.party') }}</th>
            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.payType') }}</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('ui.amount') }}</th>
            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.tax') }}</th>
            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.method') }}</th>
            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase sticky right-0 bg-linear-to-r from-gray-50 to-gray-100">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ formatDate(payment.payment_date) }}</td>
            <td class="px-3 py-3">
              <span :class="getTypeBadge(payment.payable_type_label)" class="text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap">
                {{ formatTypeName(payment.payable_type_label) }}
              </span>
            </td>
            <td class="px-3 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ payment.payable_reference || payment.reference_number || '—' }}</td>
            <td class="px-3 py-3 text-sm text-gray-700 max-w-30 truncate">{{ payment.payable_party || '—' }}</td>
            <td class="px-2 py-3 text-sm text-gray-700 capitalize whitespace-nowrap">{{ $label(payment.payment_type) }}</td>
            <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right whitespace-nowrap">{{ formatCurrency(payment.amount) }}</td>
            <td class="px-2 py-3">
              <div v-if="payment.tax_type" class="text-xs">
                <span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium uppercase">{{ payment.tax_type }}</span>
                <span class="text-gray-600 ml-1">{{ payment.tax_rate }}%</span>
                <div class="text-amber-700 font-semibold mt-0.5 whitespace-nowrap">{{ formatCurrency(payment.tax_amount) }}</div>
              </div>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>
            <td class="px-2 py-3 text-sm text-gray-700">{{ payment.method || '—' }}</td>
            <td class="px-2 py-3 text-sm text-gray-700">
              <span v-if="payment.bank_account" class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-medium whitespace-nowrap">
                {{ payment.bank_account.bank_name }}
              </span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-2 py-3">
              <span :class="getStatusBadge(payment.status)" class="text-xs font-medium px-2 py-1 rounded-full">
                {{ $label(payment.status) }}
              </span>
            </td>
            <td class="px-3 py-3 text-right text-sm whitespace-nowrap sticky right-0 bg-white space-x-1">
              <button
                v-if="payment.payable_type_label !== 'expense'" @click="editPayment(payment)"
                class="text-blue-600 hover:text-blue-900 font-medium"
              >{{ $t('sales.actions') }}</button>
              <router-link v-else :to="{ name: 'Expenses', query: { expense: payment.payable_id } }" class="text-blue-600">{{ $t('expenses.manage') }}</router-link>
            </td>
          </tr>
          <tr v-if="!payments.data?.length && !loading">
            <td colspan="11" class="px-4 py-8 text-center text-gray-500">{{ $t('ui.noPaymentsFound') }}</td>
          </tr>
        </tbody>
      </table>
      </div>

      <!-- Pagination -->
      <div v-if="payments.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ payments.from }} {{ $t('ui.to') }} {{ payments.to }} {{ $t('sales.of') }} {{ payments.total }}</p>
        <div class="flex space-x-2">
          <button
            @click="loadPayments(payments.current_page - 1)"
            :disabled="!payments.prev_page_url"
            class="btn-secondary disabled:opacity-50"
          >{{ $t('sales.previous') }}</button>
          <button
            @click="loadPayments(payments.current_page + 1)"
            :disabled="!payments.next_page_url"
            class="btn-secondary disabled:opacity-50"
          >{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <!-- Edit Payment Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-gray-900">{{ $t('ui.editPayment') }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ editForm.payable_reference }}</p>

        <form @submit.prevent="savePayment" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.payType') }}</label>
              <select v-model="editForm.payment_type" class="input">
                <option value="dp">DP</option>
                <option value="termin">{{ $t('labels.installment') }}</option>
                <option value="full">{{ $t('labels.full') }}</option>
                <option value="sharing_profit">{{ $t('ui.sharingProfit') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.amount') }}</label>
              <input v-model.number="editForm.amount" type="number" min="0.01" step="0.01" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.paymentDate') }}</label>
              <input v-model="editForm.payment_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.method') }}</label>
              <input v-model="editForm.method" class="input" :placeholder="$t('ui.transferCash')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.materials.status') }}</label>
              <select v-model="editForm.status" class="input">
                <option value="unpaid">{{ $t('labels.unpaid') }}</option>
                <option value="paid">{{ $t('labels.paid') }}</option>
                <option value="cancelled">{{ $t('labels.cancelled') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.referenceNo') }}</label>
              <input v-model="editForm.reference_number" class="input" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('pageTitles.BankAccounts') }}</label>
              <select v-model="editForm.bank_account_id" class="input">
                <option :value="null">{{ $t('ui.noBankAccount') }}</option>
                <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                  {{ bank.bank_name }} - {{ bank.account_number }} ({{ bank.account_name }})
                </option>
              </select>
            </div>
            <div class="col-span-2 p-3 bg-amber-50 rounded-lg">
              <label class="block text-sm font-medium text-amber-700 mb-2">{{ $t('ui.taxInformation') }}</label>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('ui.taxType') }}</label>
                  <select v-model="editForm.tax_type" class="input text-sm">
                    <option :value="null">{{ $t('sales.noTax') }}</option>
                    <option v-for="tax in taxRates" :key="tax.id" :value="tax.code">
                      {{ tax.name }} ({{ tax.rate }}%)
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('nav.taxRates') }}</label>
                  <input :value="computedTaxRate + '%'" disabled class="input text-sm bg-gray-50" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('sales.taxAmount') }}</label>
                  <input :value="formatCurrency(computedTaxAmount)" disabled class="input text-sm bg-gray-50 font-semibold text-amber-700" />
                </div>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="editForm.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="editError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ editError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showEditModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? $t('sales.saving') : $t('ui.saveChanges') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { Dialog, DialogPanel, DialogTitle, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';

const payments = ref({ data: [] });
const summary = ref({
  total_unpaid: 0, total_paid: 0, total_cancelled: 0,
  count_unpaid: 0, count_paid: 0, count_all: 0, this_month_paid: 0,
  total_tax_amount: 0, total_paid_tax: 0, this_month_tax: 0
});
const bankAccounts = ref([]);
const taxRates = ref([]);
const loading = ref(true);
const saving = ref(false);
const showEditModal = ref(false);
const editError = ref('');
const editingId = ref(null);

const filters = ref({
  search: '',
  payable_type: '',
  status: '',
  start_date: '',
  end_date: '',
});

const editForm = ref({
  payment_type: 'full',
  amount: 0,
  tax_type: null,
  payment_date: '',
  method: '',
  status: 'unpaid',
  reference_number: '',
  bank_account_id: null,
  notes: '',
  payable_reference: '',
});

let debounceTimer = null;
const debouncedLoad = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadPayments(), 300);
};

const formatCurrency = (value) => 'Rp ' + Number(value || 0).toLocaleString(uiLocale());
const formatDate = (date) => date ? new Date(date).toLocaleDateString(uiLocale()) : '—';

const formatTypeName = (type) => {
  const names = {
    expense: uiText('expenses.title'),
    purchase: uiText("Pembelian"),
    sale: uiText("Penjualan"),
    project_contract: uiText("Proyek"),
    msa_contract: uiText("MSA"),
  };
  return names[type] || type;
};

const getTypeBadge = (type) => {
  const badges = {
    purchase: 'bg-blue-100 text-blue-700',
    sale: 'bg-purple-100 text-purple-700',
    project_contract: 'bg-orange-100 text-orange-700',
    msa_contract: 'bg-cyan-100 text-cyan-700',
  };
  return badges[type] || 'bg-gray-100 text-gray-700';
};

const getStatusBadge = (status) => {
  const badges = {
    unpaid: 'bg-red-100 text-red-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-gray-100 text-gray-500',
  };
  return badges[status] || 'bg-gray-100 text-gray-700';
};

const loadPayments = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/payments', { params: { page, per_page: 20, ...filters.value } });
    payments.value = response.data;
  } catch (err) {
    console.error('Gagal memuat pembayaran:', err);
  } finally {
    loading.value = false;
  }
};

const loadSummary = async () => {
  try {
    const response = await api.get('/payments/summary');
    summary.value = response.data;
  } catch (err) {
    console.error('Gagal memuat ringkasan:', err);
  }
};

const loadBankAccounts = async () => {
  try {
    const response = await api.get('/bank-accounts', { params: { is_active: true } });
    bankAccounts.value = response.data;
  } catch (err) {
    console.error('Gagal memuat rekening bank:', err);
  }
};

const loadTaxRates = async () => {
  try {
    const response = await api.get('/tax-rates/active');
    taxRates.value = response.data;
  } catch (err) {
    console.error('Gagal memuat tarif pajak:', err);
  }
};

const computedTaxRate = computed(() => {
  if (!editForm.value.tax_type) return 0;
  const tax = taxRates.value.find(t => t.code === editForm.value.tax_type);
  return tax ? parseFloat(tax.rate) : 0;
});

const computedTaxAmount = computed(() => {
  if (!editForm.value.tax_type || !editForm.value.amount) return 0;
  return Math.round(editForm.value.amount * (computedTaxRate.value / 100) * 100) / 100;
});

const markPaid = async (payment) => {
  if (!confirm(uiText('ui.markPaymentPaid', { p0: formatCurrency(payment.amount) }))) return;
  try {
    await api.put(`/payments/${payment.id}`, { status: 'paid' });
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal memperbarui pembayaran"));
  }
};

const cancelPayment = async (payment) => {
  if (!confirm(uiText('ui.cancelPaymentConfirm', {  }))) return;
  try {
    await api.put(`/payments/${payment.id}`, { status: 'cancelled' });
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal membatalkan pembayaran"));
  }
};

const editPayment = (payment) => {
  editingId.value = payment.id;
  editForm.value = {
    payment_type: payment.payment_type || 'full',
    amount: Number(payment.amount || 0),
    tax_type: payment.tax_type || null,
    payment_date: payment.payment_date ? payment.payment_date.split('T')[0] : '',
    method: payment.method || '',
    status: payment.status || 'unpaid',
    reference_number: payment.reference_number || '',
    bank_account_id: payment.bank_account_id || null,
    notes: payment.notes || '',
    payable_reference: `${formatTypeName(payment.payable_type_label)}: ${payment.payable_reference}`,
  };
  editError.value = '';
  showEditModal.value = true;
};

const savePayment = async () => {
  saving.value = true;
  editError.value = '';
  try {
    const { payable_reference, ...payload } = editForm.value;
    await api.put(`/payments/${editingId.value}`, payload);
    showEditModal.value = false;
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    editError.value = err.response?.data?.message || uiText("Gagal menyimpan pembayaran");
  } finally {
    saving.value = false;
  }
};

const deletePayment = async (payment) => {
  if (!confirm(uiText("Hapus pembayaran ini secara permanen?"))) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus pembayaran"));
  }
};

onMounted(() => {
  loadPayments();
  loadSummary();
  loadBankAccounts();
  loadTaxRates();
});
</script>
