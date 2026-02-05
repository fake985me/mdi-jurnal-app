<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">MSA Project</h2>
        <p class="text-sm text-gray-600 mt-1">Maintenance Service Agreement - Track project items with issues</p>
      </div>
      <button @click="showModal = true; resetForm()" class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all shadow-md font-medium">
        + New MSA
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
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

    <!-- MSA Table -->
    <div class="table-wrapper">
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
const products = ref([]);
const projects = ref([]);
const loading = ref(true);

// Computed: filter only invest-type projects
const investProjects = computed(() => {
  return projects.value.filter(p => p.type === 'invest' || !p.type);
});
const showModal = ref(false);
const showReturnModal = ref(false);
const selectedMSA = ref(null);
const returnType = ref('');
const returnCondition = ref('');
const saving = ref(false);
const error = ref('');

const filters = ref({ search: '', status: '' });

const form = ref({
  product_id: '',
  project_investment_id: '',
  quantity: 1,
  issue_type: 'defective',
  issue_description: '',
  reported_date: new Date().toISOString().split('T')[0],
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
