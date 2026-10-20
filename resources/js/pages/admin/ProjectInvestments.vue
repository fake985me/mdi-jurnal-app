<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">{{ t('projectInvestment.title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('projectInvestment.subtitle') }}</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        {{ t('projectInvestment.newProject') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadProjects" type="text" :placeholder="$t('ui.searchByProjectNameCodeOrClient')" class="input" />
        <select v-model="filters.status" @change="loadProjects" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="pending">{{ $t('labels.pending_approval') }}</option>
          <option value="approved">{{ $t('labels.approved') }}</option>
          <option value="active">{{ $t('labels.active') }}</option>
          <option value="completed">{{ $t('labels.completed') }}</option>
          <option value="cancelled">{{ $t('labels.cancelled') }}</option>
        </select>
      </div>
    </div>

    <!-- Projects Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('labels.invest') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('common.loading') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.projectCode') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.type') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.projectName') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.client') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.totalValue') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="project in projects.data" :key="project.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ project.project_code }}</td>
            <td class="px-6 py-4">
              <span :class="project.type === 'invest' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'" class="px-2 py-1 text-xs rounded-full">
                {{ project.type === 'invest' ? $t('ui.investment') : $t('labels.design_build') }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ project.project_name }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ project.client_name }}</div>
              <div class="text-xs text-gray-500">{{ project.client_contact }}</div>
            </td>
            <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ formatCurrency(project.total_value) }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(project.status)">{{ $label(project.status) }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="project.status === 'pending'" @click="approveProject(project)" class="text-green-600 hover:text-green-900">{{ $t('projectInvestment.actions.approve') }}</button>
              <button v-if="project.status === 'approved'" @click="startProject(project)" class="text-blue-600 hover:text-blue-900">{{ $t('projectInvestment.actions.start') }}</button>
              <button v-if="['approved', 'active'].includes(project.status)" @click="completeProject(project)" class="text-green-600 hover:text-green-900">{{ $t('projectInvestment.actions.complete') }}</button>
              <button v-if="project.status !== 'completed'" @click="cancelProject(project)" class="text-red-600 hover:text-red-900">{{ $t('projectInvestment.actions.cancel') }}</button>
              <router-link :to="{ name: 'ProjectInvestmentDetail', params: { id: project.id } }" class="text-indigo-600 hover:text-indigo-900">{{ $t('ui.manageItems') }}</router-link>
              <button @click="exportProject(project)" class="text-purple-600 hover:text-purple-900">{{ $t('projectInvestment.actions.export') }}</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="projects.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ projects.from }} {{ $t('ui.to') }} {{ projects.to }} {{ $t('sales.of') }} {{ projects.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadProjects(projects.current_page - 1)" :disabled="!projects.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadProjects(projects.current_page + 1)" :disabled="!projects.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.newProjectInvestment') }}</h3>
        
        <form @submit.prevent="saveProject" class="space-y-4">
          <!-- Project Type Selector -->
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('ui.projectType') }}</label>
            <div class="flex gap-4">
              <label class="flex items-center p-3 border rounded-lg cursor-pointer" :class="form.type === 'invest' ? 'bg-green-50 border-green-500' : 'hover:bg-gray-100'">
                <input type="radio" v-model="form.type" value="invest" class="mr-2" />
                <div>
                  <span class="font-medium">{{ $t('labels.invest') }}</span>
                  <p class="text-xs text-gray-500">{{ $t('ui.autoCreateDedicatedWarehouseMsa') }}</p>
                </div>
              </label>
              <label class="flex items-center p-3 border rounded-lg cursor-pointer" :class="form.type === 'design_build' ? 'bg-blue-50 border-blue-500' : 'hover:bg-gray-100'">
                <input type="radio" v-model="form.type" value="design_build" class="mr-2" />
                <div>
                  <span class="font-medium">{{ $t('labels.design_build') }}</span>
                  <p class="text-xs text-gray-500">{{ $t('ui.requiresMsaContract') }}</p>
                </div>
              </label>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.projectName') }}</label>
              <input v-model="form.project_name" required class="input" :placeholder="$t('ui.enterProjectName')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.clientName') }}</label>
              <input v-model="form.client_name" required class="input" :placeholder="$t('ui.enterClientName')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.clientContact') }}</label>
              <input v-model="form.client_contact" class="input" :placeholder="$t('ui.phoneOrEmail')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.startDate') }}</label>
              <input v-model="form.start_date" type="date" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.expectedEndDate') }}</label>
              <input v-model="form.expected_end_date" type="date" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.description') }}</label>
            <textarea v-model="form.description" rows="2" class="input"></textarea>
          </div>

          <!-- Items Section -->
          <div class="border-t pt-4 mt-4">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-semibold text-gray-800">{{ $t('ui.projectItemsStockAllocation') }}</h4>
              <button type="button" @click="addItem" class="text-sm text-purple-600 hover:text-purple-800">{{ $t('sales.addItem') }}</button>
            </div>
            
            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-5 gap-2 mb-2">
              <select v-model="item.product_id" @change="setItemPrice(index)" class="input col-span-2">
                <option value="">{{ $t('sales.selectProduct') }}</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.title }}
                </option>
              </select>
              <input v-model.number="item.quantity" type="number" min="1" class="input" :placeholder="$t('sales.quantity')" />
              <input v-model.number="item.unit_price" type="number" step="0.01" class="input" :placeholder="$t('ui.unitPrice')" />
              <button type="button" @click="form.items.splice(index, 1)" class="text-red-500 hover:text-red-700">✕</button>
            </div>
            
            <div v-if="form.items.length" class="text-right text-sm font-semibold text-gray-700 mt-2">
              Total: {{ formatCurrency(calculateTotal()) }}
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('ui.createProject') }}</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';

const { t } = useI18n();

const projects = ref({ data: [] });
const products = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const error = ref('');

const filters = ref({ search: '', status: '' });

const form = ref({
  type: 'invest',
  project_name: '',
  client_name: '',
  client_contact: '',
  description: '',
  start_date: new Date().toISOString().split('T')[0],
  expected_end_date: '',
  items: [],
});

const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString(uiLocale());
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString(uiLocale());
};

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    approved: 'badge-info',
    active: 'badge-primary',
    completed: 'badge-success',
    cancelled: 'badge-danger',
  };
  return badges[status] || 'badge';
};

const loadProjects = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/project-investments', { params: { page, ...filters.value } });
    projects.value = response.data;
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

const resetForm = () => {
  form.value = {
    type: 'invest',
    project_name: '',
    client_name: '',
    client_contact: '',
    description: '',
    start_date: new Date().toISOString().split('T')[0],
    expected_end_date: '',
    items: [],
  };
  error.value = '';
};

const addItem = () => {
  form.value.items.push({ product_id: '', quantity: 1, unit_price: 0 });
};

const setItemPrice = (index) => {
  const item = form.value.items[index];
  const product = products.value.find(p => p.id === parseInt(item.product_id));
  if (product) {
    item.unit_price = product.price ?? product.sell_price ?? 0;
  }
};

const calculateTotal = () => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

const saveProject = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/project-investments', form.value);
    showModal.value = false;
    loadProjects();
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal menyimpan proyek");
  } finally {
    saving.value = false;
  }
};

const approveProject = async (project) => {
  if (!confirm(uiText("Setujui proyek ini? Stok akan dikurangi untuk semua barang."))) return;
  try {
    await api.post(`/project-investments/${project.id}/approve`);
    loadProjects();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menyetujui proyek"));
  }
};

const startProject = async (project) => {
  try {
    await api.post(`/project-investments/${project.id}/start`);
    loadProjects();
  } catch (err) {
    alert(uiText("Gagal memulai proyek"));
  }
};

const completeProject = async (project) => {
  if (!confirm(uiText("Tandai proyek ini sebagai selesai?"))) return;
  try {
    await api.post(`/project-investments/${project.id}/complete`);
    loadProjects();
  } catch (err) {
    alert(uiText("Gagal menyelesaikan proyek"));
  }
};

const cancelProject = async (project) => {
  if (!confirm(uiText("Batalkan proyek ini? Stok akan dikembalikan."))) return;
  try {
    await api.post(`/project-investments/${project.id}/cancel`);
    loadProjects();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal membatalkan proyek"));
  }
};

const exportProject = (project) => {
  window.open(`/api/project-investments/${project.id}/export`, '_blank');
};


onMounted(() => {
  loadProjects();
  loadProducts();
});
</script>


