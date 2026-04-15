<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Category Management</h2>
      <div class="flex gap-3">
        <button
          @click="openCategoryModal(null)"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          + Add Category
        </button>
      </div>
    </div>

    <!-- Categories List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading categories...</p>
      </div>

      <div v-else class="divide-y divide-gray-200">
        <div v-for="category in categories" :key="category.id" class="p-4">
          <!-- Category Row -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <button @click="toggleExpand(category.id)" class="text-gray-500 hover:text-gray-700">
                <svg :class="['w-5 h-5 transition-transform', expandedCategories.includes(category.id) ? 'rotate-90' : '']"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
              <div>
                <div class="font-semibold text-gray-900">{{ category.name }}</div>
                <div class="text-sm text-gray-500">{{ category.description || 'No description' }}</div>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="text-sm text-gray-500">
                <span class="font-medium">{{ category.sub_categories?.length || 0 }}</span> subcategories
              </div>
              <div class="flex gap-2">
                <button
                  @click="openSubcategoryModal(category, null)"
                  class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200"
                >
                  + Subcategory
                </button>
                <button
                  @click="openCategoryModal(category)"
                  class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200"
                >
                  Edit
                </button>
                <button
                  @click="deleteCategory(category)"
                  class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>

          <!-- Subcategories (Expandable) -->
          <div v-if="expandedCategories.includes(category.id)" class="mt-3 ml-8">
            <div v-if="!category.sub_categories?.length" class="text-sm text-gray-500 italic py-2">
              No subcategories yet
            </div>
            <div v-else class="space-y-2">
              <div
                v-for="sub in category.sub_categories"
                :key="sub.id"
                class="flex items-center justify-between py-2 px-4 bg-gray-50 rounded-lg"
              >
                <div>
                  <div class="font-medium text-gray-800">{{ sub.name }}</div>
                  <div class="text-xs text-gray-500">{{ sub.description || 'No description' }}</div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-xs text-gray-500">
                    {{ sub.products_count || 0 }} products
                  </div>
                  <button
                    @click="openSubcategoryModal(category, sub)"
                    class="text-indigo-600 hover:text-indigo-800 text-sm"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteSubcategory(sub)"
                    class="text-red-600 hover:text-red-800 text-sm"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!categories.length && !loading" class="p-8 text-center text-gray-500">
          No categories found. Click "Add Category" to create one.
        </div>
      </div>
    </div>

    <!-- Category Modal -->
    <div v-if="showCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ editingCategory ? 'Edit Category' : 'Add New Category' }}</h3>
        
        <form @submit.prevent="saveCategory" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
            <input
              v-model="categoryForm.name"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., XGSPON, SWITCH, WIRELESS"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="categoryForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="Optional description..."
            ></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showCategoryModal = false"
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

    <!-- Subcategory Modal -->
    <div v-if="showSubcategoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">
          {{ editingSubcategory ? 'Edit Subcategory' : 'Add New Subcategory' }}
        </h3>
        <p class="text-sm text-gray-600 mb-4">
          Parent Category: <span class="font-semibold">{{ selectedCategory?.name }}</span>
        </p>
        
        <form @submit.prevent="saveSubcategory" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subcategory Name *</label>
            <input
              v-model="subcategoryForm.name"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., ONU, OLT, L2 SWITCH"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="subcategoryForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="Optional description..."
            ></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showSubcategoryModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save Subcategory' }}
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

const categories = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const expandedCategories = ref([]);

// Category Modal
const showCategoryModal = ref(false);
const editingCategory = ref(null);
const categoryForm = ref({
  name: '',
  description: '',
});

// Subcategory Modal
const showSubcategoryModal = ref(false);
const editingSubcategory = ref(null);
const selectedCategory = ref(null);
const subcategoryForm = ref({
  name: '',
  description: '',
});

const loadCategories = async () => {
  loading.value = true;
  try {
    const response = await api.get('/categories');
    categories.value = response.data;
    // Auto-expand all categories
    expandedCategories.value = categories.value.map(c => c.id);
  } catch (err) {
    console.error('Failed to load categories:', err);
  } finally {
    loading.value = false;
  }
};

const toggleExpand = (categoryId) => {
  const index = expandedCategories.value.indexOf(categoryId);
  if (index > -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(categoryId);
  }
};

const toCount = (value) => {
  const numberValue = Number(value);
  return Number.isFinite(numberValue) ? numberValue : 0;
};

const getCategoryProductCount = (category) => {
  return (
    toCount(category?.products_count) +
    toCount(category?.pivot_products_count) +
    toCount(category?.public_products_count)
  );
};

// Category CRUD
const openCategoryModal = (category) => {
  editingCategory.value = category;
  error.value = '';
  if (category) {
    categoryForm.value = {
      name: category.name,
      description: category.description || '',
    };
  } else {
    categoryForm.value = { name: '', description: '' };
  }
  showCategoryModal.value = true;
};

const saveCategory = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    if (editingCategory.value) {
      await api.put(`/categories/${editingCategory.value.id}`, categoryForm.value);
    } else {
      await api.post('/categories', categoryForm.value);
    }
    showCategoryModal.value = false;
    loadCategories();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save category';
  } finally {
    saving.value = false;
  }
};

const deleteCategory = async (category) => {
  if (!confirm(`Are you sure you want to delete "${category.name}"? This will also delete all subcategories.`)) return;
  
  try {
    await api.delete(`/categories/${category.id}`);
    loadCategories();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete category');
  }
};

// Subcategory CRUD
const openSubcategoryModal = (category, subcategory) => {
  selectedCategory.value = category;
  editingSubcategory.value = subcategory;
  error.value = '';
  if (subcategory) {
    subcategoryForm.value = {
      name: subcategory.name,
      description: subcategory.description || '',
    };
  } else {
    subcategoryForm.value = { name: '', description: '' };
  }
  showSubcategoryModal.value = true;
};

const saveSubcategory = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    const payload = {
      ...subcategoryForm.value,
      category_id: selectedCategory.value.id,
    };
    
    if (editingSubcategory.value) {
      await api.put(`/subcategories/${editingSubcategory.value.id}`, payload);
    } else {
      await api.post('/subcategories', payload);
    }
    showSubcategoryModal.value = false;
    loadCategories();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save subcategory';
  } finally {
    saving.value = false;
  }
};

const deleteSubcategory = async (subcategory) => {
  if (!confirm(`Are you sure you want to delete subcategory "${subcategory.name}"?`)) return;
  
  try {
    await api.delete(`/subcategories/${subcategory.id}`);
    loadCategories();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete subcategory');
  }
};

onMounted(() => {
  loadCategories();
});
</script>
