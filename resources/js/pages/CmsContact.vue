<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Contact Information</h2>
      <button
        @click="showModal = true; editingItem = null"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
      >
        + Add Contact Info
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading contact info...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in contacts" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.order }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                {{ item.type }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ item.label }}</td>
            <td class="px-6 py-4 text-sm">{{ item.value }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
              <button @click="editItem(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
              <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ editingItem ? 'Edit Contact Info' : 'Add New Contact Info' }}</h3>
        
        <form @submit.prevent="saveItem" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select v-model="form.type" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
              <option value="description">Description (Company Info)</option>
              <option value="address">Address</option>
              <option value="phone">Phone</option>
              <option value="email">Email</option>
              <option value="sales">Sales & Marketing</option>
              <option value="social">Social Media</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
            <input v-model="form.label" placeholder="e.g., Office, Mail, Our Address" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
            <small class="text-gray-500">Optional - leave empty for items that don't need a label</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Value *</label>
            <textarea
              v-model="form.value"
              required
              rows="3"
              :placeholder="getPlaceholder(form.type)"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
            ></textarea>
            <small class="text-gray-500">{{ getHint(form.type) }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
            <input v-model="form.icon" placeholder="e.g., envelope, phone, map-marker" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
            <input v-model.number="form.order" type="number" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="mr-2" />
              <span class="text-sm font-medium text-gray-700">Active</span>
            </label>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3">
            <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Cancel</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const contacts = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingItem = ref(null);
const saving = ref(false);
const error = ref('');

const form = ref({
  type: 'description',
  label: '',
  value: '',
  icon: '',
  order: 0,
  is_active: true,
});

const getPlaceholder = (type) => {
  const placeholders = {
    description: 'Enter company description...',
    address: 'Gedung Tifa Arum Realty\n3th Floor Suite 301',
    phone: '021 2930-6714',
    email: 'info@mdi-solutions.com',
    sales: 'Hadi : +62 887-0978-7005',
    social: '@company_social'
  }
  return placeholders[type] || 'Enter value...'
}

const getHint = (type) => {
  const hints = {
    description: 'Company description shown at the top of contact page',
    address: 'Use line breaks for multi-line addresses',
    phone: 'Phone number with or without label',
    email: 'Email address for contact',
    sales: 'Format: Name : Phone Number',
    social: 'Social media handle or URL'
  }
  return hints[type] || ''
}

const loadContacts = async () => {
  loading.value = true;
  try {
    const response = await api.get('/contact-info');
    contacts.value = response.data;
  } catch (err) {
    console.error('Failed to load contact info:', err);
  } finally {
    loading.value = false;
  }
};

const editItem = (item) => {
  editingItem.value = item;
  form.value = { ...item };
  showModal.value = true;
};

const saveItem = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    if (editingItem.value) {
      await api.put(`/contact-info/${editingItem.value.id}`, form.value);
    } else {
      await api.post('/contact-info', form.value);
    }
    showModal.value = false;
    loadContacts();
    form.value = { type: 'description', label: '', value: '', icon: '', order: 0, is_active: true };
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save contact info';
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (id) => {
  if (!confirm('Are you sure?')) return;
  try {
    await api.delete(`/contact-info/${id}`);
    loadContacts();
  } catch (err) {
    alert('Failed to delete contact info');
  }
};

onMounted(() => loadContacts());
</script>
