<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">{{ $t('pageTitles.CmsProjects') }}</h2>
      <button
        @click="openAddModal"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
      >
        {{ $t('ui.addProject') }}
      </button>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
      <label for="project-category-filter" class="text-sm font-medium text-gray-700">{{ $t('ui.filterByCategory') }}</label>
      <select
        id="project-category-filter"
        v-model="categoryFilter"
        class="w-full sm:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
      >
        <option value="">{{ $t('ui.allCategories2') }}</option>
        <option v-for="category in categoryOptions" :key="category" :value="category">{{ category }}</option>
      </select>
      <p class="text-sm text-gray-500">{{ $t('ui.theCategoryDeterminesWhichSectionDisplaysAProjectOnThePublicProjectsPage') }}</p>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('ui.loadingProjects') }}</p>
      </div>

      <div v-else-if="loadError" class="p-8 text-center text-red-600">
        {{ $ui(loadError) }}
        <button @click="loadProjects" class="ml-2 underline">{{ $t('ui.retry') }}</button>
      </div>

      <table v-else class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.order') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.heading') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('dashboard.category') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in filteredProjects" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.order }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $ui(item.title) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.category }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600">
              <a v-if="item.url" :href="item.url" target="_blank">{{ $t('projectInvestment.actions.view') }}</a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ item.is_active ? $t('labels.active') : $t('labels.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
              <button @click="editItem(item)" class="text-indigo-600 hover:text-indigo-900">{{ $t('common.edit') }}</button>
              <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
            </td>
          </tr>
          <tr v-if="filteredProjects.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('ui.noProjectsFoundInThisCategory') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">{{ editingItem ? $t('ui.editProject') : $t('ui.addNewProject') }}</h3>
        
        <form @submit.prevent="saveItem" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.title') }}</label>
            <input v-model="form.title" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.descriptionAddress') }}</label>
            <textarea v-model="form.description" required rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('dashboard.category') }}</label>
              <select v-model="form.category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option v-for="category in categoryOptions" :key="category" :value="category">{{ category }}</option>
              </select>
              <p class="mt-1 text-xs text-gray-500">{{ $t('ui.ispCustomerManagedServicesAndFttxProjectEachHaveASectionOnThePublic') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.order') }}</label>
              <input v-model.number="form.order" type="number" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.imageUrl') }}</label>
            <input v-model="form.image_url" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.uploadImage') }}</label>
            <input
              ref="imageInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="hidden"
              @change="selectImage"
            />
            <button
              type="button"
              class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100"
              @click="imageInput?.click()"
            >
              {{ selectedImage ? $t('ui.chooseAnotherImage') : $t('ui.chooseImage') }}
            </button>
            <span v-if="selectedImage" class="ml-3 text-sm text-gray-600">{{ selectedImage.name }}</span>
            <p class="mt-1 text-xs text-gray-500">{{ $t('ui.jpgPngOrWebpMaximum5MbAnUploadedImageReplacesTheImageUrl') }}</p>
            <img
              v-if="imagePreview"
              :src="imagePreview"
              :alt="form.title || $t('ui.projectImagePreview')"
              class="mt-3 h-36 w-auto max-w-full rounded-lg border border-gray-200 object-contain"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.projectUrl') }}</label>
            <input v-model="form.url" type="url" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="mr-2" />
              <span class="text-sm font-medium text-gray-700">{{ $t('labels.active') }}</span>
            </label>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {{ saving ? $t('sales.saving') : $t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import api from '../../services/api';

const projects = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingItem = ref(null);
const saving = ref(false);
const error = ref('');
const loadError = ref('');
const selectedImage = ref(null);
const imagePreview = ref('');
const imageInput = ref(null);
const categoryFilter = ref('');
const publicCategories = [uiText("Pelanggan ISP"), uiText("Layanan Terkelola"), uiText("Proyek FTTx"), 'FTTH'];
const categoryOptions = computed(() => [...new Set([
  ...publicCategories,
  ...projects.value.map((project) => project.category).filter(Boolean),
])]);
const filteredProjects = computed(() => categoryFilter.value
  ? projects.value.filter((project) => project.category === categoryFilter.value)
  : projects.value);

const form = ref({
  title: '',
  description: '',
  image_url: '',
  url: '',
  category: uiText("Layanan Terkelola"),
  order: 0,
  is_active: true,
});

const loadProjects = async () => {
  loading.value = true;
  loadError.value = '';
  try {
    const response = await api.get('/projects');
    projects.value = response.data;
  } catch (err) {
    console.error('Gagal memuat proyek:', err);
    loadError.value = err.response?.data?.message || uiText("Gagal memuat proyek");
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  clearSelectedImage();
  form.value = { title: '', description: '', image_url: '', url: '', category: uiText("Layanan Terkelola"), order: 0, is_active: true };
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
  error.value = '';
  resetForm();
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
  form.value = { ...item };
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
      await api.post(`/projects/${editingItem.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      await api.post('/projects', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }
    showModal.value = false;
    resetForm();
    await loadProjects();
  } catch (err) {
    const validationErrors = err.response?.data?.errors;
    error.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || uiText("Gagal menyimpan proyek");
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (id) => {
  if (!confirm(uiText("Apakah Anda yakin?"))) return;
  try {
    await api.delete(`/projects/${id}`);
    await loadProjects();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus proyek"));
  }
};

onMounted(() => loadProjects());
onBeforeUnmount(clearSelectedImage);
</script>
