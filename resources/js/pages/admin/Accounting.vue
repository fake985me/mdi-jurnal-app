<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
          Accounting
        </h2>
        <p class="text-sm text-gray-600 mt-1">Manage all payments and financial transactions</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card p-4 bg-gradient-to-br from-red-50 to-red-100 border border-red-200">
        <p class="text-sm text-red-600 font-medium">Total Unpaid</p>
        <p class="text-2xl font-bold text-red-800">{{ formatCurrency(summary.total_unpaid) }}</p>
        <p class="text-xs text-red-500 mt-1">{{ summary.count_unpaid }} transactions</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-green-50 to-green-100 border border-green-200">
        <p class="text-sm text-green-600 font-medium">Total Paid</p>
        <p class="text-2xl font-bold text-green-800">{{ formatCurrency(summary.total_paid) }}</p>
        <p class="text-xs text-green-500 mt-1">{{ summary.count_paid }} transactions</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200">
        <p class="text-sm text-blue-600 font-medium">This Month Paid</p>
        <p class="text-2xl font-bold text-blue-800">{{ formatCurrency(summary.this_month_paid) }}</p>
      </div>
      <div class="card p-4 bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200">
        <p class="text-sm text-gray-600 font-medium">Total Transactions</p>
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
          placeholder="Search reference, method..."
          class="input"
        />
        <select v-model="filters.payable_type" @change="loadPayments" class="input">
          <option value="">All Types</option>
          <option value="purchase">Purchase</option>
          <option value="sale">Sale</option>
          <option value="project_contract">Project Contract</option>
          <option value="msa_contract">MSA Contract</option>
        </select>
        <select v-model="filters.status" @change="loadPayments" class="input">
          <option value="">All Status</option>
          <option value="unpaid">Unpaid</option>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <input v-model="filters.start_date" @change="loadPayments" type="date" class="input" />
        <input v-model="filters.end_date" @change="loadPayments" type="date" class="input" />
      </div>
    </div>

    <!-- Payments Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">All Payments</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading payments...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Party</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Type</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(payment.payment_date) }}</td>
            <td class="px-4 py-3">
              <span :class="getTypeBadge(payment.payable_type_label)" class="text-xs font-medium px-2 py-1 rounded-full">
                {{ formatTypeName(payment.payable_type_label) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ payment.payable_reference || payment.reference_number || '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ payment.payable_party || '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-700 capitalize">{{ payment.payment_type?.replace('_', ' ') }}</td>
            <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ formatCurrency(payment.amount) }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ payment.method || '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">
              <span v-if="payment.bank_account" class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-medium">
                {{ payment.bank_account.bank_name }}
              </span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-4 py-3">
              <span :class="getStatusBadge(payment.status)" class="text-xs font-medium px-2 py-1 rounded-full">
                {{ payment.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-1">
              <button
                v-if="payment.status === 'unpaid'"
                @click="markPaid(payment)"
                class="text-green-600 hover:text-green-900 font-medium"
              >Pay</button>
              <button
                @click="editPayment(payment)"
                class="text-blue-600 hover:text-blue-900 font-medium"
              >Edit</button>
              <button
                v-if="payment.status !== 'cancelled'"
                @click="cancelPayment(payment)"
                class="text-orange-600 hover:text-orange-900 font-medium"
              >Cancel</button>
              <button
                @click="deletePayment(payment)"
                class="text-red-600 hover:text-red-900 font-medium"
              >Delete</button>
            </td>
          </tr>
          <tr v-if="!payments.data?.length && !loading">
            <td colspan="10" class="px-4 py-8 text-center text-gray-500">No payments found</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="payments.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
        <p class="text-sm text-gray-700">Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }}</p>
        <div class="flex space-x-2">
          <button
            @click="loadPayments(payments.current_page - 1)"
            :disabled="!payments.prev_page_url"
            class="btn-secondary disabled:opacity-50"
          >Previous</button>
          <button
            @click="loadPayments(payments.current_page + 1)"
            :disabled="!payments.next_page_url"
            class="btn-secondary disabled:opacity-50"
          >Next</button>
        </div>
      </div>
    </div>

    <!-- Edit Payment Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-gray-900">Edit Payment</h3>
        <p class="text-sm text-gray-500 mb-4">{{ editForm.payable_reference }}</p>

        <form @submit.prevent="savePayment" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
              <select v-model="editForm.payment_type" class="input">
                <option value="dp">DP</option>
                <option value="termin">Termin</option>
                <option value="full">Full Payment</option>
                <option value="sharing_profit">Sharing Profit</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
              <input v-model.number="editForm.amount" type="number" min="0.01" step="0.01" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
              <input v-model="editForm.payment_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
              <input v-model="editForm.method" class="input" placeholder="Transfer/Cash/..." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="editForm.status" class="input">
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reference No</label>
              <input v-model="editForm.reference_number" class="input" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account</label>
              <select v-model="editForm.bank_account_id" class="input">
                <option :value="null">— No Bank Account —</option>
                <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                  {{ bank.bank_name }} - {{ bank.account_number }} ({{ bank.account_name }})
                </option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="editForm.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="editError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ editError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showEditModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const payments = ref({ data: [] });
const summary = ref({
  total_unpaid: 0, total_paid: 0, total_cancelled: 0,
  count_unpaid: 0, count_paid: 0, count_all: 0, this_month_paid: 0
});
const bankAccounts = ref([]);
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

const formatCurrency = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
const formatDate = (date) => date ? new Date(date).toLocaleDateString('id-ID') : '—';

const formatTypeName = (type) => {
  const names = {
    purchase: 'Purchase',
    sale: 'Sale',
    project_contract: 'Project',
    msa_contract: 'MSA',
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
    console.error('Failed to load payments:', err);
  } finally {
    loading.value = false;
  }
};

const loadSummary = async () => {
  try {
    const response = await api.get('/payments/summary');
    summary.value = response.data;
  } catch (err) {
    console.error('Failed to load summary:', err);
  }
};

const loadBankAccounts = async () => {
  try {
    const response = await api.get('/bank-accounts', { params: { is_active: true } });
    bankAccounts.value = response.data;
  } catch (err) {
    console.error('Failed to load bank accounts:', err);
  }
};

const markPaid = async (payment) => {
  if (!confirm(`Mark payment of ${formatCurrency(payment.amount)} as Paid?`)) return;
  try {
    await api.put(`/payments/${payment.id}`, { status: 'paid' });
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to update payment');
  }
};

const cancelPayment = async (payment) => {
  if (!confirm(`Cancel this payment?`)) return;
  try {
    await api.put(`/payments/${payment.id}`, { status: 'cancelled' });
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to cancel payment');
  }
};

const editPayment = (payment) => {
  editingId.value = payment.id;
  editForm.value = {
    payment_type: payment.payment_type || 'full',
    amount: Number(payment.amount || 0),
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
    editError.value = err.response?.data?.message || 'Failed to save payment';
  } finally {
    saving.value = false;
  }
};

const deletePayment = async (payment) => {
  if (!confirm('Delete this payment permanently?')) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    loadPayments(payments.value.current_page);
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete payment');
  }
};

onMounted(() => {
  loadPayments();
  loadSummary();
  loadBankAccounts();
});
</script>
