<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">Sales Team Management</h2>
        <p class="text-sm text-gray-600 mt-1">Manage sales people and commission rates</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-lg hover:from-indigo-700 hover:to-indigo-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + Add Sales Person
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-indigo-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Total Team</p>
            <p class="text-3xl font-bold text-gray-900">{{ salesPeople.length }}</p>
          </div>
          <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">👥</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Active</p>
            <p class="text-3xl font-bold text-green-600">{{ activeCount }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">✅</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Inactive</p>
            <p class="text-3xl font-bold text-gray-600">{{ inactiveCount }}</p>
          </div>
          <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">⏸️</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Avg Commission</p>
            <p class="text-3xl font-bold text-purple-600">{{ avgCommission }}%</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <span class="text-2xl">💰</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales People Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Sales Team Members</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading sales people...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="person in salesPeople" :key="person.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                  {{ person.name.charAt(0).toUpperCase() }}
                </div>
                <span class="ml-3 text-sm font-medium text-gray-900">{{ person.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ person.phone || '-' }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ person.email || '-' }}</td>
            <td class="px-6 py-4">
              <span class="text-sm font-bold text-indigo-600">{{ person.commission_rate }}%</span>
            </td>
            <td class="px-6 py-4">
              <span :class="person.active ? 'badge-success' : 'badge-danger'">
                {{ person.active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="editPerson(person)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
              <button @click="deletePerson(person.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
        <h3 class="text-2xl font-bold mb-6">{{ editingPerson ? 'Edit Sales Person' : 'Add New Sales Person' }}</h3>
        
        <form @submit.prevent="savePerson" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
              <input v-model="form.name" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input v-model="form.phone" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="form.email" type="email" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%) *</label>
              <input v-model.number="form.commission_rate" type="number" step="0.01" min="0" max="100" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
              <select v-model="form.active" required class="input">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';

const salesPeople = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingPerson = ref(null);
const saving = ref(false);
const error = ref('');

const form = ref({
  name: '',
  phone: '',
  email: '',
  commission_rate: 0,
  active: true,
});

const activeCount = computed(() => salesPeople.value.filter(p => p.active).length);
const inactiveCount = computed(() => salesPeople.value.filter(p => !p.active).length);
const avgCommission = computed(() => {
  if (salesPeople.value.length === 0) return 0;
  const total = salesPeople.value.reduce((sum, p) => sum + parseFloat(p.commission_rate || 0), 0);
  return (total / salesPeople.value.length).toFixed(2);
});

const loadSalesPeople = async () => {
  loading.value = true;
  try {
    const response = await api.get('/sales-people');
    salesPeople.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  form.value = {
    name: '',
    phone: '',
    email: '',
    commission_rate: 5,
    active: true,
  };
  editingPerson.value = null;
  error.value = '';
};

const editPerson = (person) => {
  editingPerson.value = person;
  form.value = {
    name: person.name,
    phone: person.phone,
    email: person.email,
    commission_rate: person.commission_rate,
    active: person.active,
  };
  showModal.value = true;
};

const savePerson = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    if (editingPerson.value) {
      await api.put(`/sales-people/${editingPerson.value.id}`, form.value);
    } else {
      await api.post('/sales-people', form.value);
    }
    showModal.value = false;
    loadSalesPeople();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save';
  } finally {
    saving.value = false;
  }
};

const deletePerson = async (id) => {
  if (!confirm('Delete this sales person?')) return;
  try {
    await api.delete(`/sales-people/${id}`);
    loadSalesPeople();
  } catch (err) {
    alert('Failed to delete');
  }
};

onMounted(() => {
  loadSalesPeople();
});
</script>
