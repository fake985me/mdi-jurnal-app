<template>
  <div class="public-products-page">
    <div class="page-header">
      <h1>Public Products</h1>
      <div class="header-actions">
        <button @click="exportAll" class="btn btn-secondary">
          <i class="fas fa-download"></i> Export All
        </button>
        <button @click="exportTemplate" class="btn btn-secondary">
          <i class="fas fa-file-download"></i> Template
        </button>
        <button @click="triggerImport" class="btn btn-secondary">
          <i class="fas fa-upload"></i> Import
        </button>
        <input 
          ref="fileInput" 
          type="file" 
          accept=".csv" 
          @change="handleFileImport" 
          style="display: none" 
        />
        <button @click="openCreateForm" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Product
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <input
        v-model="filters.search"
        @input="fetchProducts"
        type="text"
        placeholder="Search products..."
        class="search-input"
      />
      
      <select v-model="filters.category_id" @change="fetchProducts" class="filter-select">
        <option value="">All Categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>

      <select v-model="filters.brand" @change="fetchProducts" class="filter-select">
        <option value="">All Brands</option>
        <option v-for="brand in brands" :key="brand" :value="brand">{{ brand }}</option>
      </select>

      <select v-model="filters.is_active" @change="fetchProducts" class="filter-select">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
    </div>

    <!-- Products Table -->
    <div class="table-container">
      <table class="products-table">
        <thead>
          <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Categories</th>
            <th>Brand</th>
            <th>Status</th>
            <th>Sort Order</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products.data" :key="product.id">
            <td>
              <img v-if="product.image" :src="product.image" :alt="product.title" class="product-image" />
              <span v-else class="no-image">No Image</span>
            </td>
            <td>
              <div class="product-title">{{ product.title }}</div>
              <div class="product-subtitle">{{ product.subtitle }}</div>
            </td>
            <td>
              <!-- Display category pairs as tags -->
              <div class="category-tags">
                <template v-if="product.category_pairs && product.category_pairs.length > 0">
                  <span v-for="(pair, idx) in product.category_pairs" :key="idx" class="category-tag">
                    {{ pair.category_name }}
                    <span v-if="pair.sub_category_name" class="sub-category">
                      / {{ pair.sub_category_name }}
                    </span>
                  </span>
                </template>
                <template v-else-if="product.category">
                  <span class="category-tag legacy">
                    {{ product.category }}
                    <span v-if="product.sub_category" class="sub-category">
                      / {{ product.sub_category }}
                    </span>
                  </span>
                </template>
                <span v-else class="no-category">-</span>
              </div>
            </td>
            <td>{{ product.brand }}</td>
            <td>
              <span :class="['status-badge', product.is_active ? 'active' : 'inactive']">
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>{{ product.sort_order }}</td>
            <td class="actions">
              <button @click="toggleActive(product)" class="btn-icon" :title="product.is_active ? 'Deactivate' : 'Activate'">
                <i :class="['fas', product.is_active ? 'fa-eye-slash' : 'fa-eye']"></i>
              </button>
              <button @click="editProduct(product)" class="btn-icon" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="deleteProduct(product)" class="btn-icon btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <tr v-if="loading">
            <td colspan="8" class="text-center">Loading...</td>
          </tr>
          <tr v-if="!loading && products.data?.length === 0">
            <td colspan="8" class="text-center">No products found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="products.last_page > 1" class="pagination">
      <button @click="changePage(products.current_page - 1)" :disabled="products.current_page === 1" class="btn">
        Previous
      </button>
      <span class="page-info">Page {{ products.current_page }} of {{ products.last_page }}</span>
      <button @click="changePage(products.current_page + 1)" :disabled="products.current_page === products.last_page" class="btn">
        Next
      </button>
    </div>

    <!-- Product Form Modal -->
    <div v-if="showForm" class="modal" @click.self="closeForm">
      <div class="modal-content large">
        <div class="modal-header">
          <h2>{{ isEditing ? 'Edit Product' : 'Create Product' }}</h2>
          <button @click="closeForm" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveProduct">
            <div class="form-grid">
              <!-- Basic Info -->
              <div class="form-group col-span-2">
                <label>Title *</label>
                <input v-model="form.title" type="text" required class="form-input" />
              </div>

              <div class="form-group">
                <label>Subtitle</label>
                <input v-model="form.subtitle" type="text" class="form-input" />
              </div>

              <div class="form-group">
                <label>SKU</label>
                <input v-model="form.sku" type="text" class="form-input" />
              </div>

              <div class="form-group">
                <label>Category</label>
                <input v-model="form.category" type="text" class="form-input" />
              </div>

              <div class="form-group">
                <label>Sub Category</label>
                <input v-model="form.sub_category" type="text" class="form-input" />
              </div>

              <div class="form-group">
                <label>Brand</label>
                <input v-model="form.brand" type="text" class="form-input" />
              </div>

              <div class="form-group">
                <label>Module</label>
                <input v-model="form.module" type="text" class="form-input" />
              </div>

              <!-- Categories Section -->
              <div class="form-section col-span-2">
                <div class="section-header">
                  <h3>Categories & Subcategories</h3>
                  <button type="button" @click="addCategoryPair" class="btn btn-sm btn-primary">
                    + Add Category
                  </button>
                </div>
                
                <div v-if="form.categories.length === 0" class="no-categories">
                  No categories added. Click "Add Category" to add one.
                </div>

                <div class="category-pairs">
                  <div v-for="(catPair, index) in form.categories" :key="index" class="category-pair-row">
                    <div class="form-group">
                      <label>Category</label>
                      <select v-model="catPair.category_id" @change="onCategoryChange(index)" class="form-input">
                        <option value="">Select Category</option>
                        <option v-for="cat in categoriesList" :key="cat.id" :value="cat.id">
                          {{ cat.name }}
                        </option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Subcategory</label>
                      <select v-model="catPair.sub_category_id" class="form-input" :disabled="!catPair.category_id">
                        <option value="">Select Subcategory</option>
                        <option v-for="sub in getSubcategoriesForCategory(catPair.category_id)" 
                            :key="sub.id" :value="sub.id">
                          {{ sub.name }}
                        </option>
                      </select>
                    </div>
                    <button type="button" @click="removeCategoryPair(index)" class="btn-icon btn-danger">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>

              <div class="form-group col-span-2">
                <label>Image URL</label>
                <input v-model="form.image" type="url" class="form-input" />
              </div>

              <div class="form-group col-span-2">
                <label>Description</label>
                <textarea v-model="form.descriptions" rows="4" class="form-input"></textarea>
              </div>

              <!-- Specifications -->
              <div class="form-section col-span-2">
                <h3>Specifications</h3>
                <div class="form-grid">
                  <div v-for="i in 7" :key="'spec' + i" class="form-group">
                    <label>Spec {{ i }}</label>
                    <input v-model="form['spec' + i]" type="text" class="form-input" />
                  </div>
                </div>
              </div>

              <!-- Features -->
              <div class="form-section col-span-2">
                <h3>Features</h3>
                <div class="form-grid">
                  <div v-for="i in 15" :key="'fitur' + i" class="form-group">
                    <label>Feature {{ i }}</label>
                    <input v-model="form['fitur' + i]" type="text" class="form-input" />
                  </div>
                </div>
              </div>

              <!-- Product Specifications -->
              <div class="form-section col-span-2">
                <h3>Product Specifications</h3>
                <div class="form-grid">
                  <!-- Wireless Specs -->
                  <div class="form-group">
                    <label>Wireless Standard</label>
                    <input v-model="form.wirelessStandard" type="text" class="form-input" placeholder="e.g., 802.11ax" />
                  </div>
                  <div class="form-group">
                    <label>Wireless Stream</label>
                    <input v-model="form.wirelessStream" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Wireless Stream 1</label>
                    <input v-model="form.wirelessStream1" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Wireless Stream 2</label>
                    <input v-model="form.wirelessStream2" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Wireless Stream 3</label>
                    <input v-model="form.wirelessStream3" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Wireless Stream 4</label>
                    <input v-model="form.wirelessStream4" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Wireless Stream 5</label>
                    <input v-model="form.wirelessStream5" type="text" class="form-input" />
                  </div>

                  <!-- Controller Specs -->
                  <div class="form-group">
                    <label>Manageable APs</label>
                    <input v-model="form.manageableAps" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>AP Number</label>
                    <input v-model="form.APnumber" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Number of Clients</label>
                    <input v-model="form.numberOfClients" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Capacity</label>
                    <input v-model="form.capacity" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>IP Rating</label>
                    <input v-model="form.iprating" type="text" class="form-input" placeholder="e.g., IP67" />
                  </div>

                  <!-- Switch Specs -->
                  <div class="form-group">
                    <label>Switching Capacity</label>
                    <input v-model="form.switching" type="text" class="form-input" placeholder="e.g., 10 Gbps" />
                  </div>
                  <div class="form-group">
                    <label>Throughput</label>
                    <input v-model="form.throughput" type="text" class="form-input" />
                  </div>

                  <!-- Memory Specs -->
                  <div class="form-group">
                    <label>Flash Memory</label>
                    <input v-model="form.flashmemory" type="text" class="form-input" placeholder="e.g., 128MB" />
                  </div>
                  <div class="form-group">
                    <label>SDRAM Memory</label>
                    <input v-model="form.sdrammemory" type="text" class="form-input" placeholder="e.g., 256MB" />
                  </div>

                  <!-- Interface Specs -->
                  <div class="form-group">
                    <label>Interface</label>
                    <input v-model="form.Interface" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Interface 1</label>
                    <input v-model="form.Interface1" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Interface 2</label>
                    <input v-model="form.Interface2" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Interface 3</label>
                    <input v-model="form.Interface3" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Interface 4</label>
                    <input v-model="form.Interface4" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Interface 5</label>
                    <input v-model="form.Interface5" type="text" class="form-input" />
                  </div>

                  <!-- Antenna -->
                  <div class="form-group">
                    <label>Antena</label>
                    <input v-model="form.antena" type="text" class="form-input" />
                  </div>

                  <!-- Control Unit -->
                  <div class="form-group">
                    <label>Control Unit</label>
                    <input v-model="form.cu" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Control Unit 1</label>
                    <input v-model="form.cu1" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Control Unit 2</label>
                    <input v-model="form.cu2" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Control Unit 3</label>
                    <input v-model="form.cu3" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Control Unit 4</label>
                    <input v-model="form.cu4" type="text" class="form-input" />
                  </div>

                  <!-- Additional Interface -->
                  <div class="form-group">
                    <label>Additional Interface 1</label>
                    <input v-model="form.aditionalinterface1" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Additional Interface 2</label>
                    <input v-model="form.aditionalinterface2" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Additional Interface 3</label>
                    <input v-model="form.aditionalinterface3" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Additional Interface 4</label>
                    <input v-model="form.aditionalinterface4" type="text" class="form-input" />
                  </div>

                  <!-- Network Specs -->
                  <div class="form-group">
                    <label>MAC Address</label>
                    <input v-model="form.macaddress" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Routing Table</label>
                    <input v-model="form.routingtable" type="text" class="form-input" />
                  </div>

                  <!-- Environmental Specs -->
                  <div class="form-group">
                    <label>Dustproof & Waterproof</label>
                    <input v-model="form.dustproofwaterproof" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Noise</label>
                    <input v-model="form.noise" type="text" class="form-input" placeholder="e.g., <30dB" />
                  </div>
                  <div class="form-group">
                    <label>MTBF</label>
                    <input v-model="form.mtbf" type="text" class="form-input" placeholder="Mean Time Between Failures" />
                  </div>

                  <!-- Temperature & Humidity -->
                  <div class="form-group">
                    <label>Operating Temperature</label>
                    <input v-model="form.operatingtemperature" type="text" class="form-input" placeholder="e.g., 0°C to 40°C" />
                  </div>
                  <div class="form-group">
                    <label>Storage Temperature</label>
                    <input v-model="form.storagetemperature" type="text" class="form-input" placeholder="e.g., -20°C to 70°C" />
                  </div>
                  <div class="form-group">
                    <label>Operating Humidity</label>
                    <input v-model="form.operatinghumidity" type="text" class="form-input" placeholder="e.g., 10% to 90%" />
                  </div>

                  <!-- Power Specs -->
                  <div class="form-group">
                    <label>Power 1</label>
                    <input v-model="form.power1" type="text" class="form-input" placeholder="e.g., DC 12V" />
                  </div>
                  <div class="form-group">
                    <label>Power 2</label>
                    <input v-model="form.power2" type="text" class="form-input" placeholder="e.g., PoE 802.3af" />
                  </div>
                  <div class="form-group">
                    <label>Power 3</label>
                    <input v-model="form.power3" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>Power Consumptions</label>
                    <input v-model="form.powercomsumptions" type="text" class="form-input" placeholder="e.g., Max 25W" />
                  </div>

                  <!-- Physical Specs -->
                  <div class="form-group col-span-2">
                    <label>Dimensions</label>
                    <input v-model="form.dimensions" type="text" class="form-input" placeholder="e.g., 440 x 220 x 44mm" />
                  </div>

                  <!-- Diagram -->
                  <div class="form-group">
                    <label>Diagram</label>
                    <input v-model="form.diagram" type="text" class="form-input" placeholder="Diagram filename (without extension)" />
                  </div>
                  <div class="form-group">
                    <label>Network Diagram</label>
                    <input v-model="form.networkDIagram" type="text" class="form-input" placeholder="e.g., gpon, xgspon, switch" />
                  </div>
                </div>
              </div>

              <!-- Visibility & Sort -->
              <div class="form-group">
                <label>
                  <input v-model="form.is_active" type="checkbox" />
                  Active
                </label>
              </div>

              <div class="form-group">
                <label>Sort Order</label>
                <input v-model.number="form.sort_order" type="number" class="form-input" />
              </div>
            </div>

            <div class="form-actions">
              <button type="button" @click="closeForm" class="btn">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'Saving...' : 'Save' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../services/api';

const products = ref({ data: [], last_page: 1, current_page: 1 });
const categories = ref([]);
const categoriesList = ref([]);
const brands = ref([]);
const loading = ref(false);
const showForm = ref(false);
const isEditing = ref(false);
const saving = ref(false);

const filters = reactive({
  search: '',
  category_id: '',
  brand: '',
  is_active: ''
});

const form = reactive({
  title: '',
  subtitle: '',
  sku: '',
  module: '',
  slug: '',
  image: '',
  category: '',
  sub_category: '',
  brand: '',
  descriptions: '',
  is_active: true,
  sort_order: 0,
  categories: [], // Array of {category_id, sub_category_id}
  // Wireless specs
  wirelessStandard: '',
  wirelessStream: '',
  wirelessStream1: '',
  wirelessStream2: '',
  wirelessStream3: '',
  wirelessStream4: '',
  wirelessStream5: '',
  // Controller specs
  manageableAps: '',
  APnumber: '',
  numberOfClients: '',
  capacity: '',
  iprating: '',
  // Switch specs
  switching: '',
  throughput: '',
  // Memory specs
  flashmemory: '',
  sdrammemory: '',
  // Interface specs
  Interface: '',
  Interface1: '',
  Interface2: '',
  Interface3: '',
  Interface4: '',
  Interface5: '',
  // Antenna
  antena: '',
  // Control Unit
  cu: '',
  cu1: '',
  cu2: '',
  cu3: '',
  cu4: '',
  // Additional Interface
  aditionalinterface1: '',
  aditionalinterface2: '',
  aditionalinterface3: '',
  aditionalinterface4: '',
  // Network specs
  macaddress: '',
  routingtable: '',
  // Environmental specs
  dustproofwaterproof: '',
  noise: '',
  mtbf: '',
  // Temperature & Humidity
  operatingtemperature: '',
  storagetemperature: '',
  operatinghumidity: '',
  // Power specs
  power1: '',
  power2: '',
  power3: '',
  powercomsumptions: '',
  // Physical specs
  dimensions: '',
  // Diagram
  diagram: '',
  networkDIagram: ''
});

// Category handling functions
function addCategoryPair() {
  form.categories.push({
    category_id: '',
    sub_category_id: ''
  });
}

function removeCategoryPair(index) {
  form.categories.splice(index, 1);
}

function onCategoryChange(index) {
  // Reset subcategory when category changes
  form.categories[index].sub_category_id = '';
}

function getSubcategoriesForCategory(categoryId) {
  if (!categoryId) return [];
  const category = categoriesList.value.find(c => c.id === categoryId);
  return category?.sub_categories || [];
}

// Initialize specs and features
for (let i = 1; i <= 7; i++) form[`spec${i}`] = '';
for (let i = 1; i <= 15; i++) form[`fitur${i}`] = '';

onMounted(() => {
  fetchProducts();
  fetchCategories();
  fetchBrands();
});

async function fetchProducts(page = 1) {
  loading.value = true;
  try {
    const params = { page, ...filters };
    console.log('[DEBUG] Fetching products with params:', params);
    const response = await api.get('/public-products', { params });
    console.log('[DEBUG] API Response:', response);
    console.log('[DEBUG] Response data:', response.data);
    console.log('[DEBUG] Response data.data:', response.data.data);
    console.log('[DEBUG] Response data.total:', response.data.total);
    products.value = response.data;
    console.log('[DEBUG] products.value set to:', products.value);
  } catch (error) {
    console.error('Error fetching products:', error);
    console.error('Error response:', error.response);
    alert('Failed to fetch products');
  } finally {
    loading.value = false;
  }
}

async function fetchCategories() {
  try {
    const response = await api.get('/public-products/filters/categories');
    // New structure returns { categories: [...], legacy_categories: [...] }
    categoriesList.value = response.data.categories || [];
    categories.value = response.data.categories || [];
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
}

async function fetchBrands() {
  try {
    const response = await api.get('/public-products/filters/brands');
    brands.value = response.data;
  } catch (error) {
    console.error('Error fetching brands:', error);
  }
}

function changePage(page) {
  fetchProducts(page);
}

function openCreateForm() {
  isEditing.value = false;
  resetForm();
  showForm.value = true;
}

function editProduct(product) {
  isEditing.value = true;
  
  // Convert category_pairs to form.categories format
  const categoryPairs = product.category_pairs || [];
  const formCategories = categoryPairs.map(pair => ({
    category_id: pair.category_id || '',
    sub_category_id: pair.sub_category_id || ''
  }));
  
  Object.assign(form, product);
  form.categories = formCategories.length > 0 ? formCategories : [];
  
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  resetForm();
}

function resetForm() {
  form.id = null;
  form.title = '';
  form.subtitle = '';
  form.sku = '';
  form.module = '';
  form.slug = '';
  form.image = '';
  form.category = '';
  form.sub_category = '';
  form.brand = '';
  form.descriptions = '';
  form.is_active = true;
  form.sort_order = 0;
  form.categories = []; // Reset categories array
  // Reset wireless specs
  form.wirelessStandard = '';
  form.wirelessStream = '';
  form.wirelessStream1 = '';
  form.wirelessStream2 = '';
  form.wirelessStream3 = '';
  form.wirelessStream4 = '';
  form.wirelessStream5 = '';
  // Reset controller specs
  form.manageableAps = '';
  form.APnumber = '';
  form.numberOfClients = '';
  form.capacity = '';
  form.iprating = '';
  // Reset switch specs
  form.switching = '';
  form.throughput = '';
  // Reset memory specs
  form.flashmemory = '';
  form.sdrammemory = '';
  // Reset interface specs
  form.Interface = '';
  form.Interface1 = '';
  form.Interface2 = '';
  form.Interface3 = '';
  form.Interface4 = '';
  form.Interface5 = '';
  // Reset antenna
  form.antena = '';
  // Reset control unit
  form.cu = '';
  form.cu1 = '';
  form.cu2 = '';
  form.cu3 = '';
  form.cu4 = '';
  // Reset additional interface
  form.aditionalinterface1 = '';
  form.aditionalinterface2 = '';
  form.aditionalinterface3 = '';
  form.aditionalinterface4 = '';
  // Reset network specs
  form.macaddress = '';
  form.routingtable = '';
  // Reset environmental specs
  form.dustproofwaterproof = '';
  form.noise = '';
  form.mtbf = '';
  // Reset temperature & humidity
  form.operatingtemperature = '';
  form.storagetemperature = '';
  form.operatinghumidity = '';
  // Reset power specs
  form.power1 = '';
  form.power2 = '';
  form.power3 = '';
  form.powercomsumptions = '';
  // Reset physical specs
  form.dimensions = '';
  // Reset diagram
  form.diagram = '';
  form.networkDIagram = '';
  for (let i = 1; i <= 7; i++) form[`spec${i}`] = '';
  for (let i = 1; i <= 15; i++) form[`fitur${i}`] = '';
}

async function saveProduct() {
  saving.value = true;
  try {
    // Filter out empty category pairs and prepare payload
    const validCategories = form.categories.filter(c => c.category_id);
    const payload = { ...form, categories: validCategories };
    
    if (isEditing.value) {
      await api.put(`/public-products/${form.id}`, payload);
      alert('Product updated successfully');
    } else {
      await api.post('/public-products', payload);
      alert('Product created successfully');
    }
    closeForm();
    fetchProducts(products.value.current_page);
    fetchCategories();
    fetchBrands();
  } catch (error) {
    console.error('Error saving product:', error);
    alert(error.response?.data?.message || 'Failed to save product');
  } finally {
    saving.value = false;
  }
}

async function toggleActive(product) {
  try {
    await api.post(`/public-products/${product.id}/toggle-active`);
    product.is_active = !product.is_active;
  } catch (error) {
    console.error('Error toggling status:', error);
    alert('Failed to update product status');
  }
}

async function deleteProduct(product) {
  if (!confirm(`Are you sure you want to delete "${product.title}"?`)) return;

  try {
    await api.delete(`/public-products/${product.id}`);
    alert('Product deleted successfully');
    fetchProducts(products.value.current_page);
  } catch (error) {
    console.error('Error deleting product:', error);
    alert('Failed to delete product');
  }
}

// Excel Export/Import Functions
const fileInput = ref(null);

function exportAll() {
  window.location.href = '/api/public-products/export/all';
}

function exportTemplate() {
  window.location.href = '/api/public-products/export/template';
}

function triggerImport() {
  fileInput.value.click();
}

async function handleFileImport(event) {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await api.post('/public-products/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    const result = response.data;
    let message = `Import completed!\n\nImported: ${result.imported}\nUpdated: ${result.updated}`;
    
    if (result.errors && result.errors.length > 0) {
      message += `\n\nErrors (${result.errors.length}):`;
      result.errors.slice(0, 5).forEach((err, idx) => {
        message += `\n${idx + 1}. ${err.row}: ${err.error}`;
      });
      if (result.errors.length > 5) {
        message += `\n... and ${result.errors.length - 5} more errors`;
      }
    }

    alert(message);
    fetchProducts(products.value.current_page);
    fetchCategories();
    fetchBrands();
  } catch (error) {
    console.error('Error importing products:', error);
    alert(error.response?.data?.message || 'Failed to import products');
  } finally {
    // Reset file input
    event.target.value = '';
  }
}
</script>

<style scoped>
.public-products-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.filters-section {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.search-input {
  flex: 1;
  min-width: 200px;
}

.filter-select {
  min-width: 150px;
}

.table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.products-table {
  width: 100%;
  border-collapse: collapse;
}

.products-table th,
.products-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.products-table th {
  background: #f5f5f5;
  font-weight: 600;
}

.product-image {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
}

.no-image {
  display: inline-block;
  width: 60px;
  height: 60px;
  background: #f0f0f0;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: #999;
}

.product-title {
  font-weight: 600;
}

.product-subtitle {
  font-size: 12px;
  color: #666;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.active {
  background: #d4edda;
  color: #155724;
}

.status-badge.inactive {
  background: #f8d7da;
  color: #721c24;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  color: #666;
  transition: color 0.2s;
}

.btn-icon:hover {
  color: #333;
}

.btn-icon.btn-danger:hover {
  color: #dc3545;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
}

.page-info {
  padding: 0 10px;
}

.btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:hover:not(:disabled) {
  background: #f5f5f5;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
  border-color: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-secondary:hover:not(:disabled) {
  background: #5a6268;
  border-color: #545b62;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 5px;
  font-weight: 500;
  font-size: 14px;
}

.form-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-input:focus {
  outline: none;
  border-color: #007bff;
}

.col-span-2 {
  grid-column: span 2;
}

.form-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.form-section h3 {
  margin: 0 0 15px 0;
  font-size: 16px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.text-center {
  text-align: center;
  padding: 20px;
}

/* Category display styles */
.category-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.category-tag {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  font-size: 11px;
  border-radius: 12px;
  background: #e0e7ff;
  color: #4338ca;
}

.category-tag.legacy {
  background: #f3f4f6;
  color: #374151;
}

.category-tag .sub-category {
  color: #6366f1;
  margin-left: 2px;
}

.no-category {
  color: #9ca3af;
}

/* Category pair form styles */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.section-header h3 {
  margin: 0;
}

.btn-sm {
  padding: 4px 12px;
  font-size: 12px;
}

.no-categories {
  color: #6b7280;
  font-size: 14px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  text-align: center;
}

.category-pairs {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.category-pair-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.category-pair-row .form-group {
  flex: 1;
  margin-bottom: 0;
}

.category-pair-row .btn-icon {
  margin-bottom: 4px;
}
</style>
