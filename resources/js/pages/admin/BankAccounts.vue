<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
          Bank Accounts
        </h2>
        <p class="text-sm text-gray-600 mt-1">Manage bank accounts and track balances</p>
      </div>
      <button @click="openCreateModal" class="btn-primary flex items-center space-x-2">
        <span>+ Add Bank Account</span>
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="totals">
      <div class="card p-5 bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-emerald-600 font-medium">Total Balance</p>
            <p class="text-2xl font-bold text-emerald-800 mt-1">{{ formatCurrency(totals.total_balance) }}</p>
          </div>
          <div class="w-12 h-12 bg-emerald-200 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
      <div class="card p-5 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-blue-600 font-medium">Total Incoming</p>
            <p class="text-2xl font-bold text-blue-800 mt-1">{{ formatCurrency(totals.total_incoming) }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
            </svg>
          </div>
        </div>
      </div>
      <div class="card p-5 bg-gradient-to-br from-red-50 to-red-100 border border-red-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-red-600 font-medium">Total Outgoing</p>
            <p class="text-2xl font-bold text-red-800 mt-1">{{ formatCurrency(totals.total_outgoing) }}</p>
          </div>
          <div class="w-12 h-12 bg-red-200 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Bank Accounts Grid -->
    <div v-if="loading" class="card p-8 text-center">
      <p class="text-gray-600">Loading bank accounts...</p>
    </div>

    <div v-else-if="!accounts.length" class="card p-12 text-center">
      <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
        </svg>
      </div>
      <p class="text-gray-500 text-lg font-medium">No bank accounts added yet</p>
      <p class="text-gray-400 text-sm mt-1">Add your first bank account to start tracking finances</p>
      <button @click="openCreateModal" class="btn-primary mt-4">+ Add Bank Account</button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="account in accounts" :key="account.id"
        class="card p-5 hover:shadow-lg transition-all duration-200 border-l-4"
        :class="account.is_default ? 'border-l-blue-500' : 'border-l-gray-200'"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <div class="flex items-center space-x-2">
              <h3 class="font-bold text-gray-900">{{ account.bank_name }}</h3>
              <span v-if="account.is_default" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Default</span>
              <span v-if="!account.is_active" class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium">Inactive</span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">{{ account.account_name }}</p>
          </div>
          <div class="flex space-x-1">
            <button @click="editAccount(account)" class="text-blue-500 hover:text-blue-700 p-1" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button @click="deleteAccount(account)" class="text-red-500 hover:text-red-700 p-1" title="Delete">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-3 mb-3">
          <p class="text-xs text-gray-500 font-medium">Account Number</p>
          <p class="text-sm font-mono font-bold text-gray-800">{{ account.account_number }}</p>
          <p v-if="account.branch" class="text-xs text-gray-400 mt-1">{{ account.branch }}</p>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-gray-500">Current Balance</p>
            <p class="text-lg font-bold" :class="(account.current_balance || 0) >= 0 ? 'text-emerald-700' : 'text-red-700'">
              {{ formatCurrency(account.current_balance) }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500">Transactions</p>
            <p class="text-sm font-semibold text-gray-700">{{ account.total_transactions || 0 }}</p>
          </div>
        </div>

        <div class="flex items-center mt-3 pt-3 border-t border-gray-100 space-x-2">
          <button v-if="!account.is_default" @click="setDefault(account)"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
            Set as Default
          </button>
          <button @click="viewTransactions(account)"
            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium ml-auto">
            View Transactions →
          </button>
        </div>
      </div>
    </div>

    <!-- Transactions Modal -->
    <div v-if="showTransModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ transAccount?.bank_name }} Transactions</h3>
            <p class="text-sm text-gray-500">{{ transAccount?.account_number }}</p>
          </div>
          <button @click="showTransModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <div v-if="transLoading" class="p-8 text-center">
          <p class="text-gray-600">Loading transactions...</p>
        </div>

        <table v-else-if="transactions.data?.length" class="min-w-full">
          <thead class="table-header">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(t.payment_date) }}</td>
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ t.reference_number || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700 capitalize">{{ t.payment_type?.replace('_', ' ') }}</td>
              <td class="px-4 py-3 text-sm font-bold text-right" :class="t.payable_type?.includes('Purchase') ? 'text-red-600' : 'text-emerald-600'">
                {{ t.payable_type?.includes('Purchase') ? '-' : '+' }}{{ formatCurrency(t.amount) }}
              </td>
              <td class="px-4 py-3">
                <span :class="getStatusBadge(t.status)" class="text-xs font-medium px-2 py-1 rounded-full">{{ t.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="p-8 text-center text-gray-500">No transactions for this bank account</div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-gray-900">{{ editingId ? 'Edit' : 'Add' }} Bank Account</h3>

        <form @submit.prevent="saveAccount" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name *</label>
              <input v-model="form.bank_name" type="text" required class="input" placeholder="BCA, Mandiri, BNI..." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Account Name *</label>
              <input v-model="form.account_name" type="text" required class="input" placeholder="PT Company Name" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Account Number *</label>
              <input v-model="form.account_number" type="text" required class="input" placeholder="1234567890" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
              <input v-model="form.branch" type="text" class="input" placeholder="Jakarta Pusat" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
              <select v-model="form.currency" class="input">
                <option value="IDR">IDR - Indonesian Rupiah</option>
                <option value="USD">USD - US Dollar</option>
                <option value="SGD">SGD - Singapore Dollar</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Initial Balance</label>
              <input v-model.number="form.initial_balance" type="number" min="0" step="0.01" class="input" />
            </div>
          </div>

          <div class="flex items-center space-x-6">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-blue-600 rounded" />
              <span class="text-sm text-gray-700">Active</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input v-model="form.is_default" type="checkbox" class="w-4 h-4 text-blue-600 rounded" />
              <span class="text-sm text-gray-700">Set as Default</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="2" class="input" placeholder="Additional notes..."></textarea>
          </div>

          <div v-if="formError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ formError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Saving...' : (editingId ? 'Save Changes' : 'Create Account') }}
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

const accounts = ref([]);
const totals = ref(null);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const showTransModal = ref(false);
const editingId = ref(null);
const formError = ref('');
const transLoading = ref(false);
const transAccount = ref(null);
const transactions = ref({ data: [] });

const form = ref({
  bank_name: '',
  account_name: '',
  account_number: '',
  branch: '',
  currency: 'IDR',
  initial_balance: 0,
  is_active: true,
  is_default: false,
  notes: '',
});

const formatCurrency = (value) => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
const formatDate = (date) => date ? new Date(date).toLocaleDateString('id-ID') : '—';

const getStatusBadge = (status) => ({
  unpaid: 'bg-red-100 text-red-700',
  paid: 'bg-green-100 text-green-700',
  cancelled: 'bg-gray-100 text-gray-500',
}[status] || 'bg-gray-100 text-gray-700');

const loadAccounts = async () => {
  loading.value = true;
  try {
    const [accountsRes, summaryRes] = await Promise.all([
      api.get('/bank-accounts'),
      api.get('/bank-accounts/summary'),
    ]);
    accounts.value = accountsRes.data;
    totals.value = summaryRes.data.totals;
  } catch (err) {
    console.error('Failed to load bank accounts:', err);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  editingId.value = null;
  form.value = {
    bank_name: '', account_name: '', account_number: '',
    branch: '', currency: 'IDR', initial_balance: 0,
    is_active: true, is_default: false, notes: '',
  };
  formError.value = '';
  showModal.value = true;
};

const editAccount = (account) => {
  editingId.value = account.id;
  form.value = { ...account };
  formError.value = '';
  showModal.value = true;
};

const saveAccount = async () => {
  saving.value = true;
  formError.value = '';
  try {
    if (editingId.value) {
      await api.put(`/bank-accounts/${editingId.value}`, form.value);
    } else {
      await api.post('/bank-accounts', form.value);
    }
    showModal.value = false;
    loadAccounts();
  } catch (err) {
    formError.value = err.response?.data?.message || 'Failed to save bank account';
  } finally {
    saving.value = false;
  }
};

const deleteAccount = async (account) => {
  if (!confirm(`Delete bank account "${account.bank_name} - ${account.account_number}"?`)) return;
  try {
    await api.delete(`/bank-accounts/${account.id}`);
    loadAccounts();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete bank account');
  }
};

const setDefault = async (account) => {
  try {
    await api.post(`/bank-accounts/${account.id}/set-default`);
    loadAccounts();
  } catch (err) {
    alert('Failed to set default');
  }
};

const viewTransactions = async (account) => {
  transAccount.value = account;
  transLoading.value = true;
  showTransModal.value = true;
  try {
    const res = await api.get(`/bank-accounts/${account.id}/transactions`, { params: { per_page: 50 } });
    transactions.value = res.data;
  } catch (err) {
    console.error('Failed to load transactions:', err);
  } finally {
    transLoading.value = false;
  }
};

onMounted(loadAccounts);
</script>
