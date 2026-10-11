<template>
  
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Solutions Management</h2>
      <button
        @click="openAddModal"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        + Add Solution
      </button>
    </div>

    <!-- Solutions Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading solutions...</p>
      </div>

      <div v-else-if="loadError" class="p-8 text-center text-red-600">
        {{ loadError }}
        <button @click="loadSolutions" class="ml-2 underline">Retry</button>
      </div>

      <table v-else class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in solutions" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.order }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ item.description.length > 100 ? `${item.description.substring(0, 100)}...` : item.description }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button @click="editItem(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
              <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
          <tr v-if="solutions.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No solutions found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">{{ editingItem ? 'Edit Solution' : 'Add New Solution' }}</h3>
        
        <form @submit.prevent="saveItem" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input
              v-model="form.title"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
            <textarea
              v-model="form.description"
              required
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
            <input
              v-model="form.image_url"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload image</label>
            <input
              ref="imageInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              @change="selectImage"
              class="w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
            />
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP; maximum 5 MB. An uploaded image replaces the image URL above.</p>
            <img
              v-if="imagePreview"
              :src="imagePreview"
              alt="Solution image preview"
              class="mt-3 h-36 w-auto max-w-full rounded-lg border border-gray-200 object-contain"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
              <input
                v-model.number="form.order"
                type="number"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div class="flex items-center">
              <label class="flex items-center cursor-pointer">
                <input v-model="form.is_active" type="checkbox" class="mr-2" />
                <span class="text-sm font-medium text-gray-700">Active</span>
              </label>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import api from '../../services/api';

const solutions = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingItem = ref(null);
const saving = ref(false);
const error = ref('');
const loadError = ref('');
const selectedImage = ref(null);
const imagePreview = ref('');
const imageInput = ref(null);

const form = ref({
  title: '',
  description: '',
  image_url: '',
  order: 0,
  is_active: true,
});

const loadSolutions = async () => {
  loading.value = true;
  loadError.value = '';
  try {
    const response = await api.get('/solutions');
    solutions.value = response.data;
  } catch (err) {
    console.error('Failed to load solutions:', err);
    loadError.value = err.response?.data?.message || 'Failed to load solutions';
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  clearSelectedImage();
  form.value = { title: '', description: '', image_url: '', order: 0, is_active: true };
};

const clearSelectedImage = () => {
  if (imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value);
  }
  selectedImage.value = null;
  imagePreview.value = '';
  if (imageInput.value) imageInput.value.value = '';
};

const selectImage = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  clearSelectedImage();
  selectedImage.value = file;
  imagePreview.value = URL.createObjectURL(file);
};

const openAddModal = () => {
  editingItem.value = null;
  resetForm();
  error.value = '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
  error.value = '';
};

const editItem = (item) => {
  clearSelectedImage();
  editingItem.value = item;
  error.value = '';
  form.value = {
    title: item.title,
    description: item.description,
    image_url: item.image_url || '',
    order: item.order,
    is_active: item.is_active,
  };
  imagePreview.value = item.image_url || '';
  showModal.value = true;
};

const saveItem = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    const formData = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
      const fieldValue = key === 'is_active' ? (value ? '1' : '0') : value;
      formData.append(key, fieldValue === null || fieldValue === undefined ? '' : String(fieldValue));
    });
    if (selectedImage.value) {
      formData.append('image', selectedImage.value);
    }

    if (editingItem.value) {
      formData.append('_method', 'PUT');
      await api.post(`/solutions/${editingItem.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      await api.post('/solutions', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }
    showModal.value = false;
    resetForm();
    await loadSolutions();
  } catch (err) {
    const validationErrors = err.response?.data?.errors;
    error.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Failed to save solution';
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (id) => {
  if (!confirm('Are you sure you want to delete this solution?')) return;
  
  try {
    await api.delete(`/solutions/${id}`);
    await loadSolutions();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete solution');
  }
};

onMounted(() => {
  loadSolutions();
});

onBeforeUnmount(clearSelectedImage);
</script>
