<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">MSA Project</h2>
        <p class="text-sm text-gray-600 mt-1">Maintenance Service Agreement - Track project items with issues</p>
      </div>
      <button v-if="activeTab === 'records'" @click="showModal = true; resetForm()" class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all shadow-md font-medium">
        + New MSA
      </button>
      <button v-else @click="showContractModal = true; resetContractForm()" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md font-medium">
        + New MSA Contract
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button @click="activeTab = 'records'"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm',
            activeTab === 'records'
              ? 'border-orange-500 text-orange-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
          MSA Records
        </button>
        <button @click="activeTab = 'contracts'; loadContracts()"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm',
            activeTab === 'contracts'
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
          MSA Contracts
        </button>
      </nav>
    </div>

    <!-- Filters -->
    <div class="card p-4" v-if="activeTab === 'records'">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadMSAs" type="text" placeholder="Search by MSA code or product..." class="input" />
        <select v-model="filters.status" @change="loadMSAs" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="in_repair">In Repair</option>
          <option value="returned">Returned</option>
          <option value="replaced">Replaced</option>
          <option value="closed">Closed</option>
        </select>
      </div>
    </div>

    <div class="card p-4" v-else>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="contractFilters.search" @input="loadContracts" type="text" placeholder="Search by MSA code or project..." class="input" />
        <select v-model="contractFilters.status" @change="loadContracts" class="input">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <!-- MSA Table -->
    <div class="table-wrapper" v-if="activeTab === 'records'">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">MSA Records</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MSA Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="msa in msas.data" :key="msa.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ msa.msa_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ msa.product?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ msa.project?.project_name || '-' }}</td>
            <td class="px-6 py-4">
              <span :class="getIssueBadge(msa.issue_type)">{{ msa.issue_type }}</span>
            </td>
            <td class="px-6 py-4 text-sm font-bold">{{ msa.quantity }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(msa.status)">{{ msa.status.replace('_', ' ') }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="msa.status === 'pending'" @click="startRepair(msa)" class="text-blue-600 hover:text-blue-900">Send to Repair</button>
              <button v-if="msa.status === 'in_repair'" @click="openReturnModal(msa, 'return')" class="text-green-600 hover:text-green-900">Mark Returned</button>
              <button v-if="msa.status === 'in_repair'" @click="openReturnModal(msa, 'replace')" class="text-orange-600 hover:text-orange-900">Replace</button>
              <button v-if="['returned', 'replaced'].includes(msa.status)" @click="closeMSA(msa)" class="text-gray-600 hover:text-gray-900">Close</button>
              <button @click="deleteMSA(msa)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="msas.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ msas.from }} to {{ msas.to }} of {{ msas.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadMSAs(msas.current_page - 1)" :disabled="!msas.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadMSAs(msas.current_page + 1)" :disabled="!msas.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- MSA Contracts Table -->
    <div class="table-wrapper" v-else>
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">MSA Contracts</h3>
      </div>

      <div v-if="loadingContracts" class="p-8 text-center">
        <p class="text-gray-600">Loading contracts...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MSA Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit Rate</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="contract in msaContracts.data" :key="contract.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ contract.msa_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">
              {{ contract.project?.project_name || '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ contract.start_date || '-' }} - {{ contract.end_date || '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">
              {{ contract.sharing_profit_rate != null ? contract.sharing_profit_rate + '%' : '-' }}
            </td>
            <td class="px-6 py-4">
              <span class="badge">{{ contract.status }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="openContractPaymentModal(contract)" class="text-emerald-600 hover:text-emerald-900">Payments</button>
              <button @click="editContract(contract)" class="text-blue-600 hover:text-blue-900">Edit</button>
              <button @click="deleteContract(contract)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="msaContracts.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ msaContracts.from }} to {{ msaContracts.to }} of {{ msaContracts.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadContracts(msaContracts.current_page - 1)" :disabled="!msaContracts.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadContracts(msaContracts.current_page + 1)" :disabled="!msaContracts.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-2xl font-bold mb-6">New MSA Record</h3>
        
        <form @submit.prevent="saveMSA" class="space-y-4">
          <!-- Project Selection (Primary) -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
            <select v-model="form.project_investment_id" required class="input">
              <option value="">Select Project</option>
              <option v-for="project in investProjects" :key="project.id" :value="project.id">{{ project.project_name }} ({{ project.project_code }})</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Only "Project Invest" type projects are shown</p>
          </div>
          
          <!-- Product (Optional) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product (Optional)</label>
            <select v-model="form.product_id" class="input">
              <option value="">No specific product</option>
              <option v-for="product in products" :key="product.id" :value="product.id">{{ product.title }}</option>
            </select>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
              <input v-model.number="form.quantity" type="number" min="1" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reported Date *</label>
              <input v-model="form.reported_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Issue Type *</label>
            <select v-model="form.issue_type" required class="input">
              <option value="damaged">Damaged</option>
              <option value="defective">Defective</option>
              <option value="malfunction">Malfunction</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Issue Description</label>
            <textarea v-model="form.issue_description" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Create MSA' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MSA Contract Modal -->
    <div v-if="showContractModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-2xl font-bold mb-6">{{ editingContractId ? 'Edit MSA Contract' : 'New MSA Contract' }}</h3>

        <form @submit.prevent="saveContract" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project (Optional)</label>
            <select v-model="contractForm.project_investment_id" class="input">
              <option value="">No project</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.project_name }} ({{ project.project_code }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">MSA Code</label>
              <input v-model="contractForm.msa_code" class="input" placeholder="Auto-generate if empty" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="contractForm.status" class="input">
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
              <input v-model="contractForm.start_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
              <input v-model="contractForm.end_date" type="date" class="input" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sharing Profit Rate (%)</label>
            <input v-model.number="contractForm.sharing_profit_rate" type="number" step="0.01" min="0" max="100" class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="contractForm.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="contractError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ contractError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showContractModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="savingContract" class="btn-primary">
              {{ savingContract ? 'Saving...' : 'Save Contract' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MSA Contract Payments Modal -->
    <div v-if="showContractPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">MSA Contract Payments</h3>
            <p class="text-sm text-gray-600">MSA Code: {{ paymentContract?.msa_code }}</p>
          </div>
          <button @click="closeContractPaymentModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        <div class="mb-4 p-3 bg-gray-50 rounded text-sm text-gray-600">
          No fixed total amount for MSA contracts. Payments are recorded as needed.
        </div>

        <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
          <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-gray-800">{{ editingContractPaymentId ? 'Edit Payment' : 'Add Payment' }}</h4>
            <button v-if="editingContractPaymentId" @click="resetContractPaymentForm" type="button" class="text-sm text-gray-500 hover:text-gray-700">
              Cancel Edit
            </button>
          </div>
          <form @submit.prevent="submitContractPayment" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
              <select v-model="contractPaymentForm.payment_type" required class="input">
                <option value="dp">DP</option>
                <option value="termin">Termin</option>
                <option value="full">Full Payment</option>
                <option value="sharing_profit">Sharing Profit</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
              <input v-model.number="contractPaymentForm.amount" type="number" min="0.01" step="0.01" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
              <input v-model="contractPaymentForm.payment_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
              <input v-model="contractPaymentForm.method" class="input" placeholder="Transfer/Cash/..." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reference No</label>
              <input v-model="contractPaymentForm.reference_number" class="input" placeholder="Optional" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="contractPaymentForm.status" class="input">
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="contractPaymentForm.notes" rows="2" class="input" placeholder="Optional notes"></textarea>
            </div>
            <div v-if="contractPaymentError" class="md:col-span-3 bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ contractPaymentError }}</div>
            <div class="md:col-span-3 flex justify-end gap-3">
              <button type="button" @click="closeContractPaymentModal" class="btn-secondary">Close</button>
              <button type="submit" :disabled="contractPaymentSaving" class="btn-primary">
                {{ contractPaymentSaving ? 'Saving...' : 'Save Payment' }}
              </button>
            </div>
          </form>
        </div>

        <div v-if="contractPaymentsLoading" class="text-center py-4 text-gray-500">Loading payments...</div>
        <table v-else-if="contractPayments.length" class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left">Date</th>
              <th class="px-4 py-2 text-left">Type</th>
              <th class="px-4 py-2 text-left">Method</th>
              <th class="px-4 py-2 text-left">Ref</th>
              <th class="px-4 py-2 text-right">Amount</th>
              <th class="px-4 py-2 text-right">Status</th>
              <th class="px-4 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="payment in contractPayments" :key="payment.id">
              <td class="px-4 py-2">{{ payment.payment_date || '-' }}</td>
              <td class="px-4 py-2 capitalize">{{ payment.payment_type?.replace('_', ' ') }}</td>
              <td class="px-4 py-2">{{ payment.method || '-' }}</td>
              <td class="px-4 py-2">{{ payment.reference_number || '-' }}</td>
              <td class="px-4 py-2 text-right font-semibold">Rp {{ formatCurrency(payment.amount || 0) }}</td>
              <td class="px-4 py-2 text-right">
                <span :class="payment.status === 'paid' ? 'text-emerald-600' : payment.status === 'pending' ? 'text-yellow-600' : 'text-gray-400'">
                  {{ payment.status }}
                </span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <button @click="editContractPayment(payment)" class="text-blue-600 hover:text-blue-900">Edit</button>
                <button @click="deleteContractPayment(payment)" class="text-red-600 hover:text-red-900">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="text-center text-gray-500 py-4">No payments recorded</p>
      </div>
    </div>

    <!-- Return/Replace Modal -->
    <div v-if="showReturnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ returnType === 'return' ? 'Mark as Returned' : 'Replace Item' }}</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Item Condition *</label>
            <select v-model="returnCondition" class="input">
              <option value="">Select Condition</option>
              <option value="repaired">Repaired / Fixed</option>
              <option value="partial">Partially Fixed</option>
              <option value="unrepairable">Unrepairable</option>
              <option value="discarded">Discarded</option>
            </select>
          </div>
          
          <p v-if="returnType === 'replace'" class="text-sm text-orange-600 bg-orange-50 p-3 rounded-lg">
            ⚠️ This will deduct {{ selectedMSA?.quantity }} unit(s) from stock for replacement.
          </p>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button @click="showReturnModal = false" class="btn-secondary">Cancel</button>
            <button @click="confirmReturn" :disabled="!returnCondition" class="btn-primary">
              {{ returnType === 'return' ? 'Confirm Return' : 'Confirm Replace' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const msas = ref({ data: [] });
const msaContracts = ref({ data: [] });
const products = ref([]);
const projects = ref([]);
const loading = ref(true);
const loadingContracts = ref(false);
const activeTab = ref('records');

// Computed: filter only invest-type projects
const investProjects = computed(() => {
  return projects.value.filter(p => p.type === 'invest' || !p.type);
});
const showModal = ref(false);
const showContractModal = ref(false);
const showContractPaymentModal = ref(false);
const showReturnModal = ref(false);
const selectedMSA = ref(null);
const returnType = ref('');
const returnCondition = ref('');
const saving = ref(false);
const error = ref('');
const savingContract = ref(false);
const contractError = ref('');
const editingContractId = ref(null);
const paymentContract = ref(null);
const contractPayments = ref([]);
const contractPaymentsLoading = ref(false);
const contractPaymentSaving = ref(false);
const contractPaymentError = ref('');
const editingContractPaymentId = ref(null);

const filters = ref({ search: '', status: '' });
const contractFilters = ref({ search: '', status: '' });

const form = ref({
  product_id: '',
  project_investment_id: '',
  quantity: 1,
  issue_type: 'defective',
  issue_description: '',
  reported_date: new Date().toISOString().split('T')[0],
});

const contractForm = ref({
  project_investment_id: '',
  msa_code: '',
  start_date: '',
  end_date: '',
  sharing_profit_rate: null,
  status: 'active',
  notes: ''
});

const contractPaymentForm = ref({
  payment_type: 'dp',
  amount: null,
  payment_date: new Date().toISOString().split('T')[0],
  method: '',
  status: 'paid',
  reference_number: '',
  notes: ''
});

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    in_repair: 'badge-info',
    returned: 'badge-success',
    replaced: 'badge-primary',
    closed: 'badge-secondary',
  };
  return badges[status] || 'badge';
};

const getIssueBadge = (issue) => {
  const badges = {
    damaged: 'badge-danger',
    defective: 'badge-warning',
    malfunction: 'badge-info',
    other: 'badge-secondary',
  };
  return badges[issue] || 'badge';
};

const loadMSAs = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/msa-projects', { params: { page, ...filters.value } });
    msas.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || [];
  } catch (err) {
    console.error(err);
  }
};

const loadProjects = async () => {
  try {
    const response = await api.get('/project-investments', { params: { per_page: 1000 } });
    projects.value = response.data.data || [];
  } catch (err) {
    console.error(err);
  }
};

const loadContracts = async (page = 1) => {
  loadingContracts.value = true;
  try {
    const response = await api.get('/msa-contracts', { params: { page, ...contractFilters.value } });
    msaContracts.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loadingContracts.value = false;
  }
};

const resetForm = () => {
  form.value = {
    product_id: '',
    project_investment_id: '',
    quantity: 1,
    issue_type: 'defective',
    issue_description: '',
    reported_date: new Date().toISOString().split('T')[0],
  };
  error.value = '';
};

const resetContractForm = () => {
  contractForm.value = {
    project_investment_id: '',
    msa_code: '',
    start_date: '',
    end_date: '',
    sharing_profit_rate: null,
    status: 'active',
    notes: ''
  };
  editingContractId.value = null;
  contractError.value = '';
};

const saveMSA = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/msa-projects', form.value);
    showModal.value = false;
    loadMSAs();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save MSA';
  } finally {
    saving.value = false;
  }
};

const saveContract = async () => {
  savingContract.value = true;
  contractError.value = '';

  try {
    if (editingContractId.value) {
      await api.put(`/msa-contracts/${editingContractId.value}`, contractForm.value);
    } else {
      await api.post('/msa-contracts', contractForm.value);
    }
    showContractModal.value = false;
    resetContractForm();
    loadContracts();
  } catch (err) {
    contractError.value = err.response?.data?.message || 'Failed to save contract';
  } finally {
    savingContract.value = false;
  }
};

const editContract = (contract) => {
  editingContractId.value = contract.id;
  contractForm.value = {
    project_investment_id: contract.project_investment_id || '',
    msa_code: contract.msa_code || '',
    start_date: contract.start_date || '',
    end_date: contract.end_date || '',
    sharing_profit_rate: contract.sharing_profit_rate ?? null,
    status: contract.status || 'active',
    notes: contract.notes || ''
  };
  showContractModal.value = true;
};

const deleteContract = async (contract) => {
  if (!confirm('Delete this MSA contract?')) return;
  try {
    await api.delete(`/msa-contracts/${contract.id}`);
    loadContracts();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete contract');
  }
};

const resetContractPaymentForm = () => {
  contractPaymentForm.value = {
    payment_type: 'dp',
    amount: null,
    payment_date: new Date().toISOString().split('T')[0],
    method: '',
    status: 'paid',
    reference_number: '',
    notes: ''
  };
  contractPaymentError.value = '';
  editingContractPaymentId.value = null;
};

const loadContractPayments = async (contractId) => {
  if (!contractId) {
    contractPayments.value = [];
    return;
  }
  contractPaymentsLoading.value = true;
  try {
    const response = await api.get('/payments', {
      params: { payable_type: 'msa_contract', payable_id: contractId, per_page: 100 }
    });
    contractPayments.value = response.data.data || response.data || [];
  } catch (err) {
    console.error(err);
    contractPayments.value = [];
  } finally {
    contractPaymentsLoading.value = false;
  }
};

const openContractPaymentModal = async (contract) => {
  paymentContract.value = contract;
  showContractPaymentModal.value = true;
  resetContractPaymentForm();
  await loadContractPayments(contract.id);
};

const closeContractPaymentModal = () => {
  showContractPaymentModal.value = false;
  paymentContract.value = null;
  contractPayments.value = [];
  resetContractPaymentForm();
};

const submitContractPayment = async () => {
  if (!paymentContract.value) return;
  contractPaymentError.value = '';

  const amount = Number(contractPaymentForm.value.amount || 0);
  if (amount <= 0) {
    contractPaymentError.value = 'Amount must be greater than 0.';
    return;
  }

  contractPaymentSaving.value = true;
  try {
    const payload = {
      payment_type: contractPaymentForm.value.payment_type,
      amount: amount,
      payment_date: contractPaymentForm.value.payment_date,
      method: contractPaymentForm.value.method,
      status: contractPaymentForm.value.status,
      reference_number: contractPaymentForm.value.reference_number,
      notes: contractPaymentForm.value.notes
    };

    if (editingContractPaymentId.value) {
      await api.put(`/payments/${editingContractPaymentId.value}`, payload);
    } else {
      await api.post('/payments', {
        payable_type: 'msa_contract',
        payable_id: paymentContract.value.id,
        ...payload
      });
    }
    await loadContractPayments(paymentContract.value.id);
    resetContractPaymentForm();
  } catch (err) {
    contractPaymentError.value = err.response?.data?.message || 'Failed to save payment';
  } finally {
    contractPaymentSaving.value = false;
  }
};

const editContractPayment = (payment) => {
  editingContractPaymentId.value = payment.id;
  contractPaymentForm.value = {
    payment_type: payment.payment_type || 'dp',
    amount: Number(payment.amount || 0),
    payment_date: payment.payment_date || new Date().toISOString().split('T')[0],
    method: payment.method || '',
    status: payment.status || 'paid',
    reference_number: payment.reference_number || '',
    notes: payment.notes || ''
  };
  contractPaymentError.value = '';
};

const deleteContractPayment = async (payment) => {
  if (!confirm('Delete this payment?')) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    await loadContractPayments(paymentContract.value?.id);
    if (editingContractPaymentId.value === payment.id) {
      resetContractPaymentForm();
    }
  } catch (err) {
    contractPaymentError.value = err.response?.data?.message || 'Failed to delete payment';
  }
};

const formatCurrency = (value) => {
  const num = Number(value || 0);
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
};

const startRepair = async (msa) => {
  try {
    await api.post(`/msa-projects/${msa.id}/start-repair`);
    loadMSAs();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to start repair');
  }
};

const openReturnModal = (msa, type) => {
  selectedMSA.value = msa;
  returnType.value = type;
  returnCondition.value = '';
  showReturnModal.value = true;
};

const confirmReturn = async () => {
  try {
    const endpoint = returnType.value === 'return' ? 'mark-returned' : 'replace';
    await api.post(`/msa-projects/${selectedMSA.value.id}/${endpoint}`, {
      condition: returnCondition.value
    });
    showReturnModal.value = false;
    loadMSAs();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to process');
  }
};

const closeMSA = async (msa) => {
  try {
    await api.post(`/msa-projects/${msa.id}/close`);
    loadMSAs();
  } catch (err) {
    alert('Failed to close MSA');
  }
};

const deleteMSA = async (msa) => {
  if (!confirm('Delete this MSA record?')) return;
  try {
    await api.delete(`/msa-projects/${msa.id}`);
    loadMSAs();
  } catch (err) {
    alert('Failed to delete MSA');
  }
};

onMounted(() => {
  loadMSAs();
  loadProducts();
  loadProjects();
});
</script>
