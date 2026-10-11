<template>
  <div class="space-y-6">
    <template v-if="!formPage && !confirmPage">
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
              <div v-for="sub in category.sub_categories" :key="sub.id" class="p-4 bg-gray-50 rounded-lg">
                <!-- SubCategories Row -->
                <div class="flex items-center justify-between">

                  <div class="flex items-center gap-3">
                    <button @click="toggleExpand(sub.id)" class="text-gray-500 hover:text-gray-700">
                      <svg :class="['w-5 h-5 transition-transform', expandedCategories.includes(sub.id) ? 'rotate-90' : '']"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    <div>
                      <div class="font-medium text-gray-800">{{ sub.name }}</div>
                      <div class="text-xs text-gray-500">{{ sub.description || 'No description' }}</div>
                    </div>
                  </div>

                  <div class="flex items-center gap-3">
                    <div class="text-xs text-gray-500"> {{ sub.products_count || 0 }} products</div>
                    <button @click="openSubcategoryModal(category, sub)" class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</button>
                    <button @click="deleteSubcategory(sub)" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                  </div>

                </div>

                <div v-if="expandedCategories.includes(sub.id)" class="mt-3 ml-8">

                  <div v-if="loading" class="p-12 text-center">
                    <div class="inline-flex items-center gap-2 text-gray-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Loading products...
                    </div>
                  </div>
                  <table v-else class="w-full">
                      <thead class="table-header">
                        <tr>
                          <th class="table-header-cell">Name</th>
                          <th class="table-header-cell">Brand</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="product in sub.products" :key="product.id" class="table-row">
                          <td class="text-center table-cell">{{ product.title }}</td>
                          <td class="text-center table-cell">{{ product.brand }}</td>
                        </tr>

                        <tr v-if="!sub.products || !sub.products.length">
                          <td colspan="3" class="text-center py-4 text-gray-500">
                            No products found
                          </td>
                        </tr>
                      </tbody>
                    </table>
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
    </template>

    <section v-if="confirmPage" class="max-w-2xl mx-auto mt-8 rounded-lg bg-white p-6 shadow-md">
      <h3 class="text-xl font-bold text-gray-900">Confirm deletion</h3>
      <p class="mt-3 text-gray-600">{{ confirmMessage }}</p>
      <p v-if="confirmError" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ confirmError }}</p>
      <div class="mt-6 flex justify-end gap-3">
        <button type="button" @click="closeFormPage" class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700">Cancel</button>
        <button type="button" @click="confirmDelete" :disabled="saving" class="rounded-lg bg-red-600 px-4 py-2 text-white disabled:opacity-50">
          {{ saving ? 'Deleting...' : 'Delete' }}
        </button>
      </div>
    </section>

    <!-- Category Modal -->
    <div v-if="showCategoryModal" :class="formPage ? 'max-w-2xl mx-auto mt-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'">
      <div class="bg-white rounded-lg p-6 w-full max-w-md" :class="formPage ? 'max-w-2xl shadow-md' : ''">
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
              @click="closeFormPage"
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
    <div v-if="showSubcategoryModal" :class="formPage ? 'max-w-2xl mx-auto mt-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'">
      <div class="bg-white rounded-lg p-6 w-full max-w-md" :class="formPage ? 'max-w-2xl shadow-md' : ''">
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
              @click="closeFormPage"
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
import { ref, onMounted, onBeforeUnmount } from 'vue';
import api from '../../services/api';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const formPage = ref(false);
const confirmPage = ref(false);
const confirmType = ref('');
const confirmItem = ref(null);
const confirmError = ref('');
const confirmMessage = ref('');

const categories = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const expandedCategories = ref([]);
const subcategoryProducts = ref({ data: [] });

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

const openFormTab = (query) => {
  const target = router.resolve({ path: '/dashboard/categories', query }).href;
  window.open(target, '_blank');
};

const closeFormPage = () => {
  if (!formPage.value) {
    showCategoryModal.value = false;
    showSubcategoryModal.value = false;
    return;
  }

  if (window.opener) {
    window.opener.postMessage({ type: 'admin-data-saved', resource: 'categories' }, window.location.origin);
    window.close();
    window.setTimeout(() => {
      if (!window.closed) router.replace('/dashboard/categories');
    }, 150);
    return;
  }
  router.replace('/dashboard/categories');
};

const syncWithFormTab = (event) => {
  if (event.origin === window.location.origin && event.data?.type === 'admin-data-saved' && event.data.resource === 'categories') {
    loadCategories();
  }
};

// Category CRUD
const openCategoryModal = (category) => {
  if (!formPage.value) {
    openFormTab({ form: 'category', ...(category ? { id: category.id } : {}) });
    return;
  }

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
    if (formPage.value) {
      closeFormPage();
    } else {
      showCategoryModal.value = false;
      loadCategories();
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save category';
  } finally {
    saving.value = false;
  }
};

const deleteCategory = async (category) => {
  if (!formPage.value) {
    window.open(`/dashboard/categories?confirm=category&id=${encodeURIComponent(category.id)}`, '_blank');
    return;
  }
  confirmType.value = 'category';
  confirmItem.value = category;
  await confirmDelete();
};

const confirmDelete = async () => {
  if (!confirmItem.value) return;
  saving.value = true;
  confirmError.value = '';
  try {
    await api.delete(`/${confirmType.value === 'category' ? 'categories' : 'subcategories'}/${confirmItem.value.id}`);
    if (confirmPage.value) {
      closeFormPage();
    } else {
      loadCategories();
    }
  } catch (err) {
    confirmError.value = err.response?.data?.message || 'Failed to delete item';
  } finally {
    saving.value = false;
  }
};

// Subcategory CRUD
const openSubcategoryModal = (category, subcategory) => {
  if (!formPage.value) {
    openFormTab({
      form: 'subcategory',
      categoryId: category.id,
      ...(subcategory ? { subcategoryId: subcategory.id } : {}),
    });
    return;
  }

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
    if (formPage.value) {
      closeFormPage();
    } else {
      showSubcategoryModal.value = false;
      loadCategories();
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save subcategory';
  } finally {
    saving.value = false;
  }
};

const deleteSubcategory = async (subcategory) => {
  if (!formPage.value) {
    window.open(`/dashboard/categories?confirm=subcategory&id=${encodeURIComponent(subcategory.id)}`, '_blank');
    return;
  }
  confirmType.value = 'subcategory';
  confirmItem.value = subcategory;
  await confirmDelete();
};

onMounted(async () => {
  window.addEventListener('message', syncWithFormTab);
  await loadCategories();

  const confirm = route.query.confirm;
  if (confirm === 'category' || confirm === 'subcategory') {
    formPage.value = true;
    confirmPage.value = true;
    confirmType.value = confirm;
    const id = String(route.query.id || '');
    confirmItem.value = confirm === 'category'
      ? categories.value.find((item) => String(item.id) === id)
      : categories.value.flatMap((item) => item.sub_categories || []).find((item) => String(item.id) === id);
    if (!confirmItem.value) {
      closeFormPage();
      return;
    }
    confirmMessage.value = confirm === 'category'
      ? `Are you sure you want to delete "${confirmItem.value.name}"? This will also delete all subcategories.`
      : `Are you sure you want to delete subcategory "${confirmItem.value.name}"?`;
    return;
  }

  if (route.query.form === 'category') {
    formPage.value = true;
    const category = categories.value.find((item) => String(item.id) === String(route.query.id));
    openCategoryModal(category || null);
  } else if (route.query.form === 'subcategory') {
    formPage.value = true;
    const category = categories.value.find((item) => String(item.id) === String(route.query.categoryId));
    const subcategory = category?.sub_categories?.find(
      (item) => String(item.id) === String(route.query.subcategoryId),
    );
    if (category) {
      openSubcategoryModal(category, subcategory || null);
    } else {
      closeFormPage();
    }
  }
});

onBeforeUnmount(() => window.removeEventListener('message', syncWithFormTab));
</script>