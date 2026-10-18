<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $t('projectInvestment.po.title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.createAndManagePurchaseOrders') }}</p>
      </div>
      <button
        @click="$router.push({ name: 'PurchaseCreate' })"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        {{ $t('ui.createPurchaseOrder') }}
      </button>
    </div>

    <!-- Purchases Table -->
    

    <!-- Create Purchase Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="text-2xl font-bold mb-6 bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">{{ $t('ui.createPurchaseOrder2') }}</h3>
        
        <form @submit.prevent="savePurchase" class="space-y-6">
          <!-- Supplier Information -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-3">{{ $t('ui.supplierInformation') }}</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.poNumber') }}</label>
                <input v-model="form.po_number" required class="input" placeholder="PO-001" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.orderDate') }}</label>
                <input v-model="form.order_date" type="date" required class="input" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.supplier') }}</label>
                <div class="flex gap-2">
                  <select v-model="form.supplier_id" @change="onSupplierSelect" class="input flex-1">
                    <option value="">{{ $t('ui.selectSupplier') }}</option>
                    <option v-for="s in supplierList" :key="s.id" :value="s.id">
                      {{ s.company ? `${s.company} (${s.name})` : s.name }} — {{ s.phone || '' }}
                    </option>
                  </select>
                  <button type="button" @click="showSupplierModal = true" class="btn-secondary text-sm whitespace-nowrap">{{ $t('ui.newSupplier') }}</button>
                </div>
              </div>
              <div v-if="!form.supplier_id">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.supplierName') }}</label>
                <input v-model="form.supplier_name" :required="!form.supplier_id" class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('nav.warehouses') }}</label>
                <select v-model="form.warehouse_id" class="input">
                  <option value="">{{ $t('ui.defaultWarehouse') }}</option>
                  <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                </select>
              </div>
              <div v-if="!form.supplier_id">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.phone') }}</label>
                <input v-model="form.supplier_phone" class="input" />
              </div>
              <div v-if="!form.supplier_id" class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.address') }}</label>
                <textarea v-model="form.supplier_address" rows="2" class="input"></textarea>
              </div>
              <div v-if="form.supplier_id" class="col-span-2">
                <div class="p-2 bg-white rounded border border-blue-200 text-sm text-gray-600">
                  <strong>{{ selectedSupplierInfo?.company || selectedSupplierInfo?.name }}</strong>
                  <span v-if="selectedSupplierInfo?.phone"> — {{ selectedSupplierInfo.phone }}</span>
                  <span v-if="selectedSupplierInfo?.address" class="block text-xs text-gray-500 mt-1">{{ selectedSupplierInfo.address }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Line Items -->
          <div class="p-4 bg-cyan-50 rounded-lg">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-semibold text-gray-900">{{ $t('ui.purchaseItems') }}</h4>
              <div class="flex gap-2">
                <input 
                  v-model="productSearch" 
                  type="text" 
                  :placeholder="$t('sales.searchProducts')"
                  class="input text-sm w-48"
                />
                <button type="button" @click="showProductModal = true" class="btn-secondary text-sm">{{ $t('sales.newProduct') }}</button>
                <button type="button" @click="addLineItem" class="btn-primary text-sm">{{ $t('sales.addItem') }}</button>
              </div>
            </div>
            <div class="space-y-3">
              <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-2 items-end p-3 bg-white rounded-lg shadow-sm">
                <div class="col-span-5">
                  <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('sales.product') }}</label>
                  <select v-model="item.product_id" @change="updatePrice(index)" required class="input text-sm">
                    <option value="">{{ $t('sales.selectProduct') }}</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{ product.title || product.name }} - {{ product.brand }} ({{ product.sku }})
                    </option>
                  </select>
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('sales.quantity') }}</label>
                  <input v-model.number="item.quantity" type="number" min="1" required class="input text-sm" />
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('labels.pricing') }}</label>
                  <input v-model.number="item.unit_price" type="number" step="0.01" required class="input text-sm" />
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('sales.subtotal') }}</label>
                  <input :value="formatPrice(item.quantity * item.unit_price)" disabled class="input text-sm bg-gray-50" />
                </div>
                <div class="col-span-1">
                  <button type="button" @click="removeLineItem(index)" class="btn-danger text-sm w-full">×</button>
                </div>
              </div>
            </div>
            <div class="mt-4 text-right">
              <p class="text-lg font-bold text-gray-900">Total: Rp {{ formatPrice(calculateTotal()) }}</p>
            </div>
          </div>

          <!-- Additional Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="flex items-center space-x-2 h-full pt-6">
                <input v-model="form.is_for_asset" type="checkbox" class="w-5 h-5 text-blue-600 rounded border-gray-300" />
                <span class="text-sm font-medium text-gray-700">{{ $t('ui.purchaseForAssetNotForSale') }}</span>
              </label>
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
              <textarea v-model="form.notes" rows="2" class="input"></textarea>
            </div>
          </div>

          <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg text-sm text-blue-700">
            <strong>{{ $t('ui.note') }}</strong> {{ $t('ui.purchaseAkanDibuatDenganStatus') }} <strong>{{ $t('labels.unpaid') }}</strong>{{ $t('ui.paymentsAreMadeOnThe') }} <strong>{{ $t('pageTitles.Accounting') }}</strong>{{ $t('ui.stockAkanDitambahkanOtomatisSetelahPembayaranDiConfirm') }}
          </div>

          <div v-if="form.is_for_asset" class="bg-amber-50 border border-amber-200 p-3 rounded-lg text-sm text-amber-700">
            <strong>{{ $t('ui.note') }}</strong> {{ $t('ui.pembelianIniAkanDicatatSebagaiAssetProdukTidakAkanMasukStockPenjualan') }}
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? $t('sales.saving') : $t('ui.createPurchaseOrder2') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Quick Add Product Modal -->
    <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ $t('ui.quickAddProduct') }}</h3>
        
        <form @submit.prevent="saveProduct" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.productTitle') }}</label>
            <input v-model="productForm.title" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.brand2') }}</label>
            <input v-model="productForm.brand" required class="input" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.price') }}</label>
              <input v-model.number="productForm.price" type="number" step="0.01" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('sales.stock') }}</label>
              <input v-model.number="productForm.stock" type="number" class="input" />
            </div>
          </div>

          <div v-if="productError" class="bg-red-50 text-red-600 p-2 rounded text-sm">{{ $ui(productError) }}</div>

          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showProductModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="savingProduct" class="btn-primary">
              {{ savingProduct ? $t('sales.saving') : $t('ui.addProduct') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Quick Add Supplier Modal -->
    <div v-if="showSupplierModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-bold mb-4 bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">{{ $t('ui.quickAddSupplier') }}</h3>
        
        <form @submit.prevent="saveNewSupplier" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.contactName') }}</label>
            <input v-model="supplierForm.name" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.company') }}</label>
            <input v-model="supplierForm.company" class="input" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.phone') }}</label>
              <input v-model="supplierForm.phone" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.email') }}</label>
              <input v-model="supplierForm.email" type="email" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.address') }}</label>
            <textarea v-model="supplierForm.address" rows="2" class="input"></textarea>
          </div>

          <div v-if="supplierError" class="bg-red-50 text-red-600 p-2 rounded text-sm">{{ $ui(supplierError) }}</div>

          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showSupplierModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="savingSupplier" class="px-4 py-2 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-lg hover:from-amber-700 hover:to-orange-700 transition-all text-sm font-medium disabled:opacity-50">
              {{ savingSupplier ? $t('sales.saving') : $t('ui.addSupplier2') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';

const purchases = ref({ data: [] });
const products = ref([]);
const warehouses = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showProductModal = ref(false);
const showSupplierModal = ref(false);
const saving = ref(false);
const savingProduct = ref(false);
const savingSupplier = ref(false);
const error = ref('');
const productError = ref('');
const supplierError = ref('');
const productSearch = ref('');
const supplierList = ref([]);
const selectedSupplierInfo = ref(null);

const filters = ref({
  search: '',
  status: '',
  start_date: '',
  end_date: '',
});

const form = ref({
  po_number: '',
  supplier_id: '',
  supplier_name: '',
  supplier_address: '',
  supplier_phone: '',
  warehouse_id: '',
  order_date: new Date().toISOString().split('T')[0],
  is_for_asset: false,
  notes: '',
  items: [{ product_id: '', quantity: 1, unit_price: 0 }],
});

const supplierForm = ref({
  name: '',
  company: '',
  phone: '',
  email: '',
  address: '',
});

const productForm = ref({
  title: '',
  brand: '',
  price: 0,
  stock: 0,
  sku: '',
});

const filteredProducts = computed(() => {
  if (!productSearch.value) return products.value;
  const search = productSearch.value.toLowerCase();
  return products.value.filter(p => 
    (p.title?.toLowerCase().includes(search)) ||
    (p.name?.toLowerCase().includes(search)) ||
    (p.brand?.toLowerCase().includes(search)) ||
    (p.sku?.toLowerCase().includes(search))
  );
});

const formatPrice = (price) => {
  return new Intl.NumberFormat(uiLocale()).format(price || 0);
};

const getStatusBadgeClass = (status) => {
  const classes = {
    unpaid: 'bg-red-100 text-red-700',
    paid: 'bg-green-100 text-green-700',
    partial: 'bg-yellow-100 text-yellow-700',
    cancelled: 'bg-gray-100 text-gray-500',
  };
  return classes[status] || 'bg-gray-100 text-gray-700';
};

const loadPurchases = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page, ...filters.value };
    const response = await api.get('/purchases', { params });
    purchases.value = response.data;
  } catch (err) {
    console.error('Gagal memuat pembelian:', err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data;
  } catch (err) {
    console.error('Gagal memuat produk:', err);
  }
};

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 200 } });
    warehouses.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Gagal memuat gudang:', err);
  }
};

const loadSuppliers = async () => {
  try {
    const response = await api.get('/suppliers', { params: { all: true } });
    supplierList.value = response.data;
  } catch (err) {
    console.error('Gagal memuat pemasok:', err);
  }
};

const onSupplierSelect = () => {
  const supplier = supplierList.value.find(s => s.id == form.value.supplier_id);
  selectedSupplierInfo.value = supplier || null;
  if (supplier) {
    form.value.supplier_name = supplier.company ? `${supplier.company} (${supplier.name})` : supplier.name;
    form.value.supplier_phone = supplier.phone || '';
    form.value.supplier_address = supplier.address || '';
  }
};

const addLineItem = () => {
  form.value.items.push({ product_id: '', quantity: 1, unit_price: 0 });
};

const removeLineItem = (index) => {
  form.value.items.splice(index, 1);
};

const updatePrice = (index) => {
  const product = products.value.find(p => p.id == form.value.items[index].product_id);
  if (product) {
    form.value.items[index].unit_price = product.price;
  }
};

const calculateTotal = () => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

const resetForm = () => {
  form.value = {
    po_number: 'PO-' + Date.now(),
    supplier_id: '',
    supplier_name: '',
    supplier_address: '',
    supplier_phone: '',
    warehouse_id: '',
    order_date: new Date().toISOString().split('T')[0],
    is_for_asset: false,
    notes: '',
    items: [{ product_id: '', quantity: 1, unit_price: 0 }],
  };
  selectedSupplierInfo.value = null;
  error.value = '';
};

const savePurchase = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/purchases', form.value);
    showModal.value = false;
    loadPurchases();
    resetForm();
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal membuat pesanan pembelian");
  } finally {
    saving.value = false;
  }
};

const saveProduct = async () => {
  savingProduct.value = true;
  productError.value = '';
  
  try {
    productForm.value.sku = `SKU-${Date.now()}`;
    productForm.value.stock = productForm.value.stock || 0;
    const response = await api.post('/products', productForm.value);
    
    products.value.push(response.data);
    showProductModal.value = false;
    productForm.value = { title: '', brand: '', price: 0, stock: 0, sku: '' };
    
    alert(uiText("Produk berhasil ditambahkan!"));
  } catch (err) {
    productError.value = err.response?.data?.message || uiText("Gagal menambahkan produk");
  } finally {
    savingProduct.value = false;
  }
};

const viewPurchase = (purchase) => {
  alert(uiText('ui.purchaseOrderDetails', { p0: purchase.po_number, p1: purchase.supplier_name, p2: formatPrice(purchase.total_amount), p3: purchase.status }));
};

const deletePurchase = async (id) => {
  if (!confirm(uiText("Yakin ingin menghapus pesanan pembelian ini?"))) return;
  
  try {
    await api.delete(`/purchases/${id}`);
    loadPurchases();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus pembelian"));
  }
};

const saveNewSupplier = async () => {
  savingSupplier.value = true;
  supplierError.value = '';
  try {
    const response = await api.post('/suppliers', supplierForm.value);
    supplierList.value.push(response.data);
    form.value.supplier_id = response.data.id;
    onSupplierSelect();
    showSupplierModal.value = false;
    supplierForm.value = { name: '', company: '', phone: '', email: '', address: '' };
  } catch (err) {
    supplierError.value = err.response?.data?.message || uiText("Gagal menambahkan pemasok");
  } finally {
    savingSupplier.value = false;
  }
};

onMounted(() => {
  loadPurchases();
  loadProducts();
  loadWarehouses();
  loadSuppliers();
});
</script>
