<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">{{ $t('pageTitles.Assets') }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $t('ui.companyAssetsCreatedFromPurchases') }}</p>
            </div>
            <div class="text-sm text-gray-500 bg-gray-100 px-4 py-2 rounded-lg">
                <span class="font-medium">💡 Tip:</span> {{ $t('ui.asetDibuatOtomatisDariPembelianDenganOpsiPurchaseForAsset') }}
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input v-model="filters.search" @input="loadAssets" type="text" :placeholder="$t('ui.searchAssets')"
                    class="input" />
                <select v-model="filters.category" @change="loadAssets" class="input">
                    <option value="">{{ $t('ui.allCategories') }}</option>
                    <option v-for="cat in filterOptions.categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <select v-model="filters.location" @change="loadAssets" class="input">
                    <option value="">{{ $t('ui.allLocations') }}</option>
                    <option v-for="loc in filterOptions.locations" :key="loc" :value="loc">{{ loc }}</option>
                </select>
                <select v-model="filters.condition" @change="loadAssets" class="input">
                    <option value="">{{ $t('ui.allConditions') }}</option>
                    <option value="good">{{ $t('labels.good') }}</option>
                    <option value="fair">{{ $t('labels.fair') }}</option>
                    <option value="poor">{{ $t('labels.poor') }}</option>
                    <option value="damaged">{{ $t('labels.defective') }}</option>
                </select>
                <select v-model="filters.status" @change="loadAssets" class="input">
                    <option value="">{{ $t('sales.allStatus') }}</option>
                    <option value="active">{{ $t('labels.active') }}</option>
                    <option value="in_use">{{ $t('labels.in_use') }}</option>
                    <option value="maintenance">{{ $t('labels.maintenance') }}</option>
                    <option value="disposed">{{ $t('labels.disposed') }}</option>
                </select>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="table-wrapper">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.assets') }}</h3>
            </div>

            <div v-if="loading" class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto"></div>
                <p class="text-gray-600 mt-2">{{ $t('ui.loadingAssets') }}</p>
            </div>

            <div v-else-if="!assets.data?.length" class="p-8 text-center text-gray-500">
                {{ $t('ui.noAssetsFoundClickAddAssetToCreateOne') }}
            </div>

            <table v-else class="min-w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.assetCode') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('dashboard.category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.projectLocation') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.condition') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.value') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.source') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
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
                            <button @click="editAsset(asset)" class="text-indigo-600 hover:text-indigo-900">{{ $t('common.edit') }}</button>
                            <button @click="deleteAsset(asset.id)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="assets.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
                <p class="text-sm text-gray-700">
                    {{ $t('sales.showing') }} {{ assets.from }} {{ $t('ui.to') }} {{ assets.to }} {{ $t('sales.of') }} {{ assets.total }} {{ $t('ui.assets2') }}
                </p>
                <div class="flex space-x-2">
                    <button @click="loadAssets(assets.current_page - 1)" :disabled="!assets.prev_page_url"
                        class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
                    <button @click="loadAssets(assets.current_page + 1)" :disabled="!assets.next_page_url"
                        class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
                </div>
            </div>
        </div>

        <!-- Asset Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ editingAsset ? $t('ui.editAsset') : $t('ui.addNewAsset') }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>

                <form @submit.prevent="saveAsset" class="space-y-6">
                    <!-- Source from Purchase -->
                    <div class="bg-emerald-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-emerald-700">{{ $t('ui.sourceOptional') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.fromPurchaseItem') }}</label>
                                <select v-model="form.purchase_item_id" @change="onPurchaseItemChange" class="input">
                                    <option value="">{{ $t('ui.selectPurchaseItem') }}</option>
                                    <option v-for="item in filterOptions.purchase_items" :key="item.id" :value="item.id">
                                        {{ $ui(item.label) }} {{ $t('ui.qty') }} {{ item.quantity }})
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">{{ $t('ui.pilihUntukAutoFillDataDariPembelian') }}</p>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('sales.product') }}</label>
                                    <button type="button" @click="showProductModal = true" 
                                        class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                                        {{ $t('ui.addNewProduct') }}
                                    </button>
                                </div>
                                <select v-model="form.product_id" class="input">
                                    <option value="">{{ $t('ui.selectProduct') }}</option>
                                    <option v-for="product in filterOptions.products" :key="product.id" :value="product.id">
                                        {{ product.title || product.sku }} {{ product.title && product.sku ? `(${product.sku})` : '' }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Total: {{ filterOptions.products?.length || 0 }} {{ $t('ui.productsAvailable') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">{{ $t('ui.basicInformation') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.assetName') }}</label>
                                <input v-model="form.name" required class="input" :placeholder="$t('ui.eGLaptopDellLatitude')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.serialNumber') }}</label>
                                <input v-model="form.serial_number" class="input" placeholder="S/N" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('dashboard.category') }}</label>
                                <input v-model="form.category" class="input" :placeholder="$t('ui.electronicsFurnitureEtc')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.brand') }}</label>
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
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">{{ $t('ui.locationStatus') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.projectLocation') }}</label>
                                <input v-model="form.location" class="input" :placeholder="$t('ui.officeWarehouseEtc')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.condition') }}</label>
                                <select v-model="form.condition" class="input">
                                    <option value="good">{{ $t('labels.good') }}</option>
                                    <option value="fair">{{ $t('labels.fair') }}</option>
                                    <option value="poor">{{ $t('labels.poor') }}</option>
                                    <option value="damaged">{{ $t('labels.defective') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.materials.status') }}</label>
                                <select v-model="form.status" class="input">
                                    <option value="active">{{ $t('labels.active') }}</option>
                                    <option value="in_use">{{ $t('labels.in_use') }}</option>
                                    <option value="maintenance">{{ $t('labels.maintenance') }}</option>
                                    <option value="disposed">{{ $t('labels.disposed') }}</option>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.assignedTo') }}</label>
                                <input v-model="form.assigned_to" class="input" :placeholder="$t('ui.personOrDepartment')" />
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">{{ $t('ui.financialInformation') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.purchaseDate') }}</label>
                                <input v-model="form.purchase_date" type="date" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.purchasePrice') }}</label>
                                <input v-model.number="form.purchase_price" type="number" step="0.01" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.currentValue') }}</label>
                                <input v-model.number="form.current_value" type="number" step="0.01" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.warrantyEndDate') }}</label>
                                <input v-model="form.warranty_end_date" type="date" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
                        <textarea v-model="form.notes" rows="3" class="input"></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
                        <button type="submit" :disabled="saving" class="btn-primary">
                            {{ saving ? $t('sales.saving') : $t('ui.saveAsset') }}
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
                    <h3 class="text-xl font-bold text-gray-900">{{ $t('ui.addNewAssetProduct') }}</h3>
                    <button @click="showProductModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-700">
                        <strong>{{ $t('ui.note') }}</strong> {{ $t('ui.produkYangDibuatDiSiniAkanDitandaiSebagaiAssetProductDan') }}
                        <strong>{{ $t('ui.tidakAkanDihitungSebagaiStockPenjualan') }}</strong>.
                    </p>
                </div>

                <form @submit.prevent="saveAssetProduct" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.productName') }}</label>
                        <input v-model="productForm.title" required class="input" :placeholder="$t('ui.eGDellLatitude5520')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.skuOptional') }}</label>
                            <input v-model="productForm.sku" class="input" :placeholder="$t('ui.autoGeneratedIfEmpty')" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('dashboard.category') }}</label>
                            <input v-model="productForm.category" class="input" :placeholder="$t('ui.electronics')" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.brand') }}</label>
                        <input v-model="productForm.brand" class="input" :placeholder="$t('ui.dellHpEtc')" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.description') }}</label>
                        <textarea v-model="productForm.descriptions" rows="2" class="input" :placeholder="$t('ui.optionalProductDescription')"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showProductModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
                        <button type="submit" :disabled="savingProduct" class="btn-primary">
                            {{ savingProduct ? $t('ui.creating') : $t('ui.createProduct') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import api from '../../services/api';

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
        console.error('Gagal memuat pilihan filter:', error);
    }
};

const loadAssets = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, ...filters.value };
        const response = await api.get('/assets', { params });
        assets.value = response.data;
    } catch (error) {
        console.error('Gagal memuat aset:', error);
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
        console.error('Gagal menyimpan aset:', error);
        alert('Gagal menyimpan aset: ' + (error.response?.data?.message || uiText("Kesalahan tidak diketahui")));
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
        
        alert(uiText("Produk berhasil dibuat!"));
    } catch (error) {
        console.error('Gagal membuat produk:', error);
        alert('Gagal membuat produk: ' + (error.response?.data?.message || uiText("Kesalahan tidak diketahui")));
    } finally {
        savingProduct.value = false;
    }
};

const deleteAsset = async (id) => {
    if (!confirm(uiText("Yakin ingin menghapus aset ini?"))) return;
    try {
        await api.delete(`/assets/${id}`);
        loadAssets();
    } catch (error) {
        console.error('Gagal menghapus aset:', error);
        alert(uiText("Gagal menghapus aset"));
    }
};

// Formatting helpers
const formatCurrency = (value) => {
    return new Intl.NumberFormat(uiLocale(), {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const formatCondition = (condition) => {
    const labels = { good: uiText("Baik"), fair: uiText("Cukup Baik"), poor: uiText("Kurang Baik"), damaged: uiText("Rusak") };
    return labels[condition] || condition;
};

const formatStatus = (status) => {
    const labels = { active: uiText("Aktif"), in_use: uiText("Digunakan"), maintenance: uiText("Pemeliharaan"), disposed: uiText("Dihapuskan") };
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
