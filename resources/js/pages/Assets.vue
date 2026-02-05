<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Asset Management</h2>
                <p class="text-gray-500 text-sm mt-1">Daftar aset perusahaan dari pembelian</p>
            </div>
            <div class="text-sm text-gray-500 bg-gray-100 px-4 py-2 rounded-lg">
                <span class="font-medium">💡 Tip:</span> Aset dibuat otomatis dari pembelian dengan opsi "Purchase for Asset"
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input v-model="filters.search" @input="loadAssets" type="text" placeholder="Search assets..."
                    class="input" />
                <select v-model="filters.category" @change="loadAssets" class="input">
                    <option value="">All Categories</option>
                    <option v-for="cat in filterOptions.categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <select v-model="filters.location" @change="loadAssets" class="input">
                    <option value="">All Locations</option>
                    <option v-for="loc in filterOptions.locations" :key="loc" :value="loc">{{ loc }}</option>
                </select>
                <select v-model="filters.condition" @change="loadAssets" class="input">
                    <option value="">All Conditions</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                    <option value="damaged">Damaged</option>
                </select>
                <select v-model="filters.status" @change="loadAssets" class="input">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="in_use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="disposed">Disposed</option>
                </select>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="table-wrapper">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Assets</h3>
            </div>

            <div v-if="loading" class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto"></div>
                <p class="text-gray-600 mt-2">Loading assets...</p>
            </div>

            <div v-else-if="!assets.data?.length" class="p-8 text-center text-gray-500">
                No assets found. Click "Add Asset" to create one.
            </div>

            <table v-else class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ asset.asset_code }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ asset.name }}</div>
                            <div class="text-sm text-gray-500">{{ asset.brand }} {{ asset.model }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ asset.category || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ asset.location || '-' }}</td>
                        <td class="px-6 py-4">
                            <span :class="conditionClass(asset.condition)">
                                {{ formatCondition(asset.condition) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="statusClass(asset.status)">
                                {{ formatStatus(asset.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ formatCurrency(asset.current_value) }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div v-if="asset.purchase_item?.purchase" class="text-emerald-600">
                                {{ asset.purchase_item.purchase.po_number }}
                            </div>
                            <div v-else class="text-gray-400">-</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <button @click="editAsset(asset)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button @click="deleteAsset(asset.id)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="assets.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
                <p class="text-sm text-gray-700">
                    Showing {{ assets.from }} to {{ assets.to }} of {{ assets.total }} assets
                </p>
                <div class="flex space-x-2">
                    <button @click="loadAssets(assets.current_page - 1)" :disabled="!assets.prev_page_url"
                        class="btn-secondary disabled:opacity-50">Previous</button>
                    <button @click="loadAssets(assets.current_page + 1)" :disabled="!assets.next_page_url"
                        class="btn-secondary disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>

        <!-- Asset Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ editingAsset ? 'Edit Asset' : 'Add New Asset' }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>

                <form @submit.prevent="saveAsset" class="space-y-6">
                    <!-- Source from Purchase -->
                    <div class="bg-emerald-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-emerald-700">Source (Optional)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">From Purchase Item</label>
                                <select v-model="form.purchase_item_id" @change="onPurchaseItemChange" class="input">
                                    <option value="">-- Select Purchase Item --</option>
                                    <option v-for="item in filterOptions.purchase_items" :key="item.id" :value="item.id">
                                        {{ item.label }} (Qty: {{ item.quantity }})
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Pilih untuk auto-fill data dari pembelian</p>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-sm font-medium text-gray-700">Product</label>
                                    <button type="button" @click="showProductModal = true" 
                                        class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                                        + Add New Product
                                    </button>
                                </div>
                                <select v-model="form.product_id" class="input">
                                    <option value="">-- Select Product --</option>
                                    <option v-for="product in filterOptions.products" :key="product.id" :value="product.id">
                                        {{ product.title || product.sku }} {{ product.title && product.sku ? `(${product.sku})` : '' }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Total: {{ filterOptions.products?.length || 0 }} produk tersedia</p>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">Basic Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Asset Name *</label>
                                <input v-model="form.name" required class="input" placeholder="e.g. Laptop Dell Latitude" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                <input v-model="form.serial_number" class="input" placeholder="S/N" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <input v-model="form.category" class="input" placeholder="Electronics, Furniture, etc." />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                <input v-model="form.brand" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                <input v-model="form.model" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- Location & Status -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">Location & Status</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input v-model="form.location" class="input" placeholder="Office, Warehouse, etc." />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                                <select v-model="form.condition" class="input">
                                    <option value="good">Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="form.status" class="input">
                                    <option value="active">Active</option>
                                    <option value="in_use">In Use</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="disposed">Disposed</option>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                                <input v-model="form.assigned_to" class="input" placeholder="Person or department" />
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">Financial Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                                <input v-model="form.purchase_date" type="date" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price</label>
                                <input v-model.number="form.purchase_price" type="number" step="0.01" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Value</label>
                                <input v-model.number="form.current_value" type="number" step="0.01" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Warranty End Date</label>
                                <input v-model="form.warranty_end_date" type="date" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="input"></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Saving...' : 'Save Asset' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Add Product Modal -->
        <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4"
            @click.self="showProductModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Add New Asset Product</h3>
                    <button @click="showProductModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-700">
                        <strong>Note:</strong> Produk yang dibuat di sini akan ditandai sebagai "Asset Product" dan 
                        <strong>tidak akan dihitung sebagai stock penjualan</strong>.
                    </p>
                </div>

                <form @submit.prevent="saveAssetProduct" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                        <input v-model="productForm.title" required class="input" placeholder="e.g. Dell Latitude 5520" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU (Optional)</label>
                            <input v-model="productForm.sku" class="input" placeholder="Auto-generated if empty" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input v-model="productForm.category" class="input" placeholder="Electronics" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <input v-model="productForm.brand" class="input" placeholder="Dell, HP, etc." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="productForm.descriptions" rows="2" class="input" placeholder="Optional product description"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showProductModal = false" class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="savingProduct" class="btn-primary">
                            {{ savingProduct ? 'Creating...' : 'Create Product' }}
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

const assets = ref({ data: [] });
const filterOptions = ref({
    categories: [],
    locations: [],
    products: [],
    purchase_items: [],
});
const loading = ref(true);
const showModal = ref(false);
const showProductModal = ref(false);
const editingAsset = ref(null);
const saving = ref(false);
const savingProduct = ref(false);

const filters = ref({
    search: '',
    category: '',
    location: '',
    condition: '',
    status: '',
});

const form = ref({
    name: '',
    product_id: '',
    purchase_item_id: '',
    category: '',
    brand: '',
    model: '',
    serial_number: '',
    location: '',
    condition: 'good',
    status: 'active',
    purchase_date: '',
    purchase_price: 0,
    current_value: 0,
    assigned_to: '',
    warranty_end_date: '',
    notes: '',
    image: '',
});

const productForm = ref({
    title: '',
    sku: '',
    category: '',
    brand: '',
    descriptions: '',
});

const loadFilterOptions = async () => {
    try {
        const response = await api.get('/assets/filter-options');
        filterOptions.value = response.data;
    } catch (error) {
        console.error('Failed to load filter options:', error);
    }
};

const loadAssets = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, ...filters.value };
        const response = await api.get('/assets', { params });
        assets.value = response.data;
    } catch (error) {
        console.error('Failed to load assets:', error);
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    editingAsset.value = null;
    form.value = {
        name: '',
        product_id: '',
        purchase_item_id: '',
        category: '',
        brand: '',
        model: '',
        serial_number: '',
        location: '',
        condition: 'good',
        status: 'active',
        purchase_date: '',
        purchase_price: 0,
        current_value: 0,
        assigned_to: '',
        warranty_end_date: '',
        notes: '',
        image: '',
    };
    showModal.value = true;
};

const editAsset = (asset) => {
    editingAsset.value = asset;
    form.value = {
        name: asset.name || '',
        product_id: asset.product_id || '',
        purchase_item_id: asset.purchase_item_id || '',
        category: asset.category || '',
        brand: asset.brand || '',
        model: asset.model || '',
        serial_number: asset.serial_number || '',
        location: asset.location || '',
        condition: asset.condition || 'good',
        status: asset.status || 'active',
        purchase_date: asset.purchase_date?.split('T')[0] || '',
        purchase_price: asset.purchase_price || 0,
        current_value: asset.current_value || 0,
        assigned_to: asset.assigned_to || '',
        warranty_end_date: asset.warranty_end_date?.split('T')[0] || '',
        notes: asset.notes || '',
        image: asset.image || '',
    };
    showModal.value = true;
};

const onPurchaseItemChange = () => {
    if (form.value.purchase_item_id) {
        const item = filterOptions.value.purchase_items.find(i => i.id === form.value.purchase_item_id);
        if (item) {
            // Will be auto-filled by backend on save
        }
    }
};

const saveAsset = async () => {
    saving.value = true;
    try {
        const data = { ...form.value };
        // Clean empty strings to null
        Object.keys(data).forEach(key => {
            if (data[key] === '') data[key] = null;
        });

        if (editingAsset.value) {
            await api.put(`/assets/${editingAsset.value.id}`, data);
        } else {
            await api.post('/assets', data);
        }
        showModal.value = false;
        loadAssets();
        loadFilterOptions(); // Refresh filter options
    } catch (error) {
        console.error('Failed to save asset:', error);
        alert('Failed to save asset: ' + (error.response?.data?.message || 'Unknown error'));
    } finally {
        saving.value = false;
    }
};

const saveAssetProduct = async () => {
    savingProduct.value = true;
    try {
        const response = await api.post('/assets/create-product', productForm.value);
        
        // Add new product to dropdown and select it
        const newProduct = response.data.product;
        filterOptions.value.products.push(newProduct);
        form.value.product_id = newProduct.id;
        
        // Auto-fill some fields
        form.value.category = form.value.category || newProduct.category;
        form.value.brand = form.value.brand || newProduct.brand;
        
        // Reset and close
        productForm.value = { title: '', sku: '', category: '', brand: '', descriptions: '' };
        showProductModal.value = false;
        
        alert('Product created successfully!');
    } catch (error) {
        console.error('Failed to create product:', error);
        alert('Failed to create product: ' + (error.response?.data?.message || 'Unknown error'));
    } finally {
        savingProduct.value = false;
    }
};

const deleteAsset = async (id) => {
    if (!confirm('Are you sure you want to delete this asset?')) return;
    try {
        await api.delete(`/assets/${id}`);
        loadAssets();
    } catch (error) {
        console.error('Failed to delete asset:', error);
        alert('Failed to delete asset');
    }
};

// Formatting helpers
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const formatCondition = (condition) => {
    const labels = { good: 'Good', fair: 'Fair', poor: 'Poor', damaged: 'Damaged' };
    return labels[condition] || condition;
};

const formatStatus = (status) => {
    const labels = { active: 'Active', in_use: 'In Use', maintenance: 'Maintenance', disposed: 'Disposed' };
    return labels[status] || status;
};

const conditionClass = (condition) => {
    const classes = {
        good: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800',
        fair: 'px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800',
        poor: 'px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800',
        damaged: 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800',
    };
    return classes[condition] || 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800';
};

const statusClass = (status) => {
    const classes = {
        active: 'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800',
        in_use: 'px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800',
        maintenance: 'px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800',
        disposed: 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800',
    };
    return classes[status] || 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800';
};

onMounted(() => {
    loadFilterOptions();
    loadAssets();
});
</script>
