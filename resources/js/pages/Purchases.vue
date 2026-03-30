<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Purchase Management</h2>
        <p class="text-sm text-gray-600 mt-1">Manage purchase orders and inventory</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + Create Purchase Order
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          @input="loadPurchases"
          type="text"
          placeholder="Search by PO or supplier..."
          class="input"
        />
        <select v-model="filters.status" @change="loadPurchases" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="received">Received</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <input v-model="filters.start_date" @change="loadPurchases" type="date" class="input" />
        <input v-model="filters.end_date" @change="loadPurchases" type="date" class="input" />
      </div>
    </div>

    <!-- Purchases Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Purchase Orders</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading purchases...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warehouse</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="purchase in purchases.data" :key="purchase.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ purchase.po_number }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ purchase.supplier_name }}</div>
              <div class="text-sm text-gray-500">{{ purchase.supplier_phone }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(purchase.order_date).toLocaleDateString() }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ purchase.warehouse?.name || '-' }}</td>
            <td v-for="item in purchase.items" :key="item.id" class="px-6 py-4 text-sm text-gray-700">{{ item.product?.title }}</td>
            <td v-for="item in purchase.items" :key="item.id" class="px-6 py-4 text-sm text-gray-700">{{ item.quantity }}</td>
            <td class="px-6 py-4 text-sm font-bold text-gray-900">Rp {{ formatPrice(purchase.total_amount) }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadgeClass(purchase.status)">
                {{ purchase.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="openPaymentModal(purchase)" class="text-emerald-600 hover:text-emerald-900 font-medium">Payments</button>
              <button @click="viewPurchase(purchase)" class="text-blue-600 hover:text-blue-900 font-medium">View</button>
              <button @click="deletePurchase(purchase.id)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="purchases.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
        <p class="text-sm text-gray-700">Showing {{ purchases.from }} to {{ purchases.to }} of {{ purchases.total }} purchases</p>
        <div class="flex space-x-2">
          <button
            @click="loadPurchases(purchases.current_page - 1)"
            :disabled="!purchases.prev_page_url"
            class="btn-secondary disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="loadPurchases(purchases.current_page + 1)"
            :disabled="!purchases.next_page_url"
            class="btn-secondary disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create Purchase Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="text-2xl font-bold mb-6 bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Create Purchase Order</h3>
        
        <form @submit.prevent="savePurchase" class="space-y-6">
          <!-- Supplier Information -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-3">Supplier Information</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PO Number *</label>
                <input v-model="form.po_number" required class="input" placeholder="PO-001" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Date *</label>
                <input v-model="form.order_date" type="date" required class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Name *</label>
                <input v-model="form.supplier_name" required class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse</label>
                <select v-model="form.warehouse_id" class="input">
                  <option value="">Default Warehouse</option>
                  <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input v-model="form.supplier_phone" class="input" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="form.supplier_address" rows="2" class="input"></textarea>
              </div>
            </div>
          </div>

          <!-- Line Items -->
          <div class="p-4 bg-cyan-50 rounded-lg">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-semibold text-gray-900">Purchase Items</h4>
              <div class="flex gap-2">
                <input 
                  v-model="productSearch" 
                  type="text" 
                  placeholder="Search products..." 
                  class="input text-sm w-48"
                />
                <button type="button" @click="showProductModal = true" class="btn-secondary text-sm">+ New Product</button>
                <button type="button" @click="addLineItem" class="btn-primary text-sm">+ Add Item</button>
              </div>
            </div>
            <div class="space-y-3">
              <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-2 items-end p-3 bg-white rounded-lg shadow-sm">
                <div class="col-span-5">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Product</label>
                  <select v-model="item.product_id" @change="updatePrice(index)" required class="input text-sm">
                    <option value="">Select Product</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{ product.title || product.name }} - {{ product.brand }} ({{ product.sku }})
                    </option>
                  </select>
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Quantity</label>
                  <input v-model.number="item.quantity" type="number" min="1" required class="input text-sm" />
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Price</label>
                  <input v-model.number="item.unit_price" type="number" step="0.01" required class="input text-sm" />
                </div>
                <div class="col-span-2">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Subtotal</label>
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
              <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
              <select v-model="form.status" required class="input">
                <option value="pending">Pending</option>
                <option value="received">{{ form.is_for_asset ? 'Received (Create Assets)' : 'Received (Add to Stock)' }}</option>
              </select>
            </div>
            <div>
              <label class="flex items-center space-x-2 h-full pt-6">
                <input v-model="form.is_for_asset" type="checkbox" class="w-5 h-5 text-blue-600 rounded border-gray-300" />
                <span class="text-sm font-medium text-gray-700">Purchase for Asset (not for sale)</span>
              </label>
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="form.notes" rows="2" class="input"></textarea>
            </div>
          </div>

          <div v-if="form.is_for_asset" class="bg-amber-50 border border-amber-200 p-3 rounded-lg text-sm text-amber-700">
            <strong>Note:</strong> Pembelian ini akan dicatat sebagai Asset. Produk tidak akan masuk stock penjualan.
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Saving...' : 'Create Purchase Order' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Quick Add Product Modal -->
    <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Quick Add Product</h3>
        
        <form @submit.prevent="saveProduct" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Title *</label>
            <input v-model="productForm.title" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand *</label>
            <input v-model="productForm.brand" required class="input" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
              <input v-model.number="productForm.price" type="number" step="0.01" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
              <input v-model.number="productForm.stock" type="number" class="input" />
            </div>
          </div>

          <div v-if="productError" class="bg-red-50 text-red-600 p-2 rounded text-sm">{{ productError }}</div>

          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showProductModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="savingProduct" class="btn-primary">
              {{ savingProduct ? 'Saving...' : 'Add Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Payments Modal -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">Payments</h3>
            <p class="text-sm text-gray-600">PO: {{ paymentPurchase?.po_number }}</p>
          </div>
          <button @click="closePaymentModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 text-sm">
          <div class="p-3 bg-gray-50 rounded">
            <div class="text-gray-500">Total</div>
            <div class="font-semibold text-gray-800">Rp {{ formatPrice(purchaseTotal) }}</div>
          </div>
          <div class="p-3 bg-gray-50 rounded">
            <div class="text-gray-500">Paid</div>
            <div class="font-semibold text-emerald-600">Rp {{ formatPrice(totalPaid) }}</div>
          </div>
          <div class="p-3 bg-gray-50 rounded">
            <div class="text-gray-500">Remaining</div>
            <div class="font-semibold text-orange-600">Rp {{ formatPrice(remainingAmount) }}</div>
          </div>
        </div>

        <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
          <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-gray-800">{{ editingPaymentId ? 'Edit Payment' : 'Add Payment' }}</h4>
            <button v-if="editingPaymentId" @click="resetPaymentForm" type="button" class="text-sm text-gray-500 hover:text-gray-700">
              Cancel Edit
            </button>
          </div>
          <form @submit.prevent="submitPayment" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
              <select v-model="paymentForm.payment_type" required class="input">
                <option value="dp">DP</option>
                <option value="termin">Termin</option>
                <option value="full">Full Payment</option>
                <option value="sharing_profit">Sharing Profit</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
              <input v-model.number="paymentForm.amount" type="number" min="0.01" step="0.01" required class="input" />
              <p v-if="paymentForm.amount && purchaseTotal > 0 && paymentForm.amount > remainingAvailable" class="text-xs text-red-600 mt-1">
                Amount exceeds remaining balance.
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
              <input v-model="paymentForm.payment_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
              <input v-model="paymentForm.method" class="input" placeholder="Transfer/Cash/..." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reference No</label>
              <input v-model="paymentForm.reference_number" class="input" placeholder="Optional" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="paymentForm.status" class="input">
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="paymentForm.notes" rows="2" class="input" placeholder="Optional notes"></textarea>
            </div>
            <div v-if="paymentError" class="md:col-span-3 bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ paymentError }}</div>
            <div class="md:col-span-3 flex justify-end gap-3">
              <button type="button" @click="closePaymentModal" class="btn-secondary">Close</button>
              <button type="submit" :disabled="paymentSaving" class="btn-primary">
                {{ paymentSaving ? 'Saving...' : 'Save Payment' }}
              </button>
            </div>
          </form>
        </div>

        <div v-if="paymentsLoading" class="text-center py-4 text-gray-500">Loading payments...</div>
        <table v-else-if="purchasePayments.length" class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left">Date</th>
              <th class="px-4 py-2 text-left">Type</th>
              <th class="px-4 py-2 text-left">Method</th>
              <th class="px-4 py-2 text-left">Ref</th>
              <th class="px-4 py-2 text-right">Amount</th>
              <th class="px-4 py-2 text-right">Status</th>
              <th class="px-4 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="payment in purchasePayments" :key="payment.id">
              <td class="px-4 py-2">{{ payment.payment_date || '-' }}</td>
              <td class="px-4 py-2 capitalize">{{ payment.payment_type?.replace('_', ' ') }}</td>
              <td class="px-4 py-2">{{ payment.method || '-' }}</td>
              <td class="px-4 py-2">{{ payment.reference_number || '-' }}</td>
              <td class="px-4 py-2 text-right font-semibold">Rp {{ formatPrice(payment.amount || 0) }}</td>
              <td class="px-4 py-2 text-right">
                <span :class="payment.status === 'paid' ? 'text-emerald-600' : payment.status === 'pending' ? 'text-yellow-600' : 'text-gray-400'">
                  {{ payment.status }}
                </span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <button @click="editPayment(payment)" class="text-blue-600 hover:text-blue-900">Edit</button>
                <button @click="deletePayment(payment)" class="text-red-600 hover:text-red-900">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="text-center text-gray-500 py-4">No payments recorded</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const purchases = ref({ data: [] });
const products = ref([]);
const warehouses = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showProductModal = ref(false);
const showPaymentModal = ref(false);
const saving = ref(false);
const savingProduct = ref(false);
const error = ref('');
const productError = ref('');
const productSearch = ref('');
const paymentPurchase = ref(null);
const purchasePayments = ref([]);
const paymentsLoading = ref(false);
const paymentSaving = ref(false);
const paymentError = ref('');
const editingPaymentId = ref(null);
const editingPaymentAmount = ref(0);

const filters = ref({
  search: '',
  status: '',
  start_date: '',
  end_date: '',
});

const form = ref({
  po_number: '',
  supplier_name: '',
  supplier_address: '',
  supplier_phone: '',
  warehouse_id: '',
  order_date: new Date().toISOString().split('T')[0],
  status: 'received',
  is_for_asset: false,
  notes: '',
  items: [{ product_id: '', quantity: 1, unit_price: 0 }],
});

const productForm = ref({
  title: '',
  brand: '',
  price: 0,
  stock: 0,
  sku: '',
});

const paymentForm = ref({
  payment_type: 'dp',
  amount: null,
  payment_date: new Date().toISOString().split('T')[0],
  method: '',
  status: 'paid',
  reference_number: '',
  notes: ''
});

const purchaseTotal = computed(() => Number(paymentPurchase.value?.total_amount || 0));
const totalPaid = computed(() => purchasePayments.value.reduce((sum, p) => {
  if (p.status === 'cancelled') return sum;
  return sum + Number(p.amount || 0);
}, 0));
const remainingAmount = computed(() => Math.max(0, purchaseTotal.value - totalPaid.value));
const remainingAvailable = computed(() => {
  if (!editingPaymentId.value) return remainingAmount.value;
  return Math.max(0, remainingAmount.value + Number(editingPaymentAmount.value || 0));
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
  return new Intl.NumberFormat('id-ID').format(price || 0);
};

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'badge-warning',
    received: 'badge-success',
    cancelled: 'badge-danger',
  };
  return classes[status] || 'badge';
};

const loadPurchases = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page, ...filters.value };
    const response = await api.get('/purchases', { params });
    purchases.value = response.data;
  } catch (err) {
    console.error('Failed to load purchases:', err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data;
  } catch (err) {
    console.error('Failed to load products:', err);
  }
};

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 200 } });
    warehouses.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to load warehouses:', err);
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
    supplier_name: '',
    supplier_address: '',
    supplier_phone: '',
    warehouse_id: '',
    order_date: new Date().toISOString().split('T')[0],
    status: 'received',
    is_for_asset: false,
    notes: '',
    items: [{ product_id: '', quantity: 1, unit_price: 0 }],
  };
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
    error.value = err.response?.data?.message || 'Failed to create purchase order';
  } finally {
    saving.value = false;
  }
};

const saveProduct = async () => {
  savingProduct.value = true;
  productError.value = '';
  
  try {
    productForm.value.sku = `SKU-${Date.now()}`;
    // Ensure stock is always 0 (not null)
    productForm.value.stock = productForm.value.stock || 0;
    const response = await api.post('/products', productForm.value);
    
    // Add to products list
    products.value.push(response.data);
    
    // Close modal and reset
    showProductModal.value = false;
    productForm.value = { title: '', brand: '', price: 0, stock: 0, sku: '' };
    
    alert('Product added successfully!');
  } catch (err) {
    productError.value = err.response?.data?.message || 'Failed to add product';
  } finally {
    savingProduct.value = false;
  }
};

const viewPurchase = (purchase) => {
  alert(`Purchase Order Details:\nPO: ${purchase.po_number}\nSupplier: ${purchase.supplier_name}\nTotal: Rp ${formatPrice(purchase.total_amount)}`);
};

const deletePurchase = async (id) => {
  if (!confirm('Are you sure you want to delete this purchase order?')) return;
  
  try {
    await api.delete(`/purchases/${id}`);
    loadPurchases();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete purchase');
  }
};

const resetPaymentForm = () => {
  paymentForm.value = {
    payment_type: 'dp',
    amount: null,
    payment_date: new Date().toISOString().split('T')[0],
    method: '',
    status: 'paid',
    reference_number: '',
    notes: ''
  };
  paymentError.value = '';
  editingPaymentId.value = null;
  editingPaymentAmount.value = 0;
};

const loadPurchasePayments = async (purchaseId) => {
  if (!purchaseId) {
    purchasePayments.value = [];
    return;
  }

  paymentsLoading.value = true;
  try {
    const response = await api.get('/payments', {
      params: { payable_type: 'purchase', payable_id: purchaseId, per_page: 100 }
    });
    purchasePayments.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to load payments:', err);
    purchasePayments.value = [];
  } finally {
    paymentsLoading.value = false;
  }
};

const openPaymentModal = async (purchase) => {
  paymentPurchase.value = purchase;
  showPaymentModal.value = true;
  resetPaymentForm();
  await loadPurchasePayments(purchase.id);
};

const closePaymentModal = () => {
  showPaymentModal.value = false;
  paymentPurchase.value = null;
  purchasePayments.value = [];
  resetPaymentForm();
};

const submitPayment = async () => {
  if (!paymentPurchase.value) return;
  paymentError.value = '';

  const amount = Number(paymentForm.value.amount || 0);
  if (amount <= 0) {
    paymentError.value = 'Amount must be greater than 0.';
    return;
  }
  if (purchaseTotal.value > 0 && amount > remainingAvailable.value + 0.0001) {
    paymentError.value = 'Payment exceeds remaining balance.';
    return;
  }

  paymentSaving.value = true;
  try {
    const payload = {
      payment_type: paymentForm.value.payment_type,
      amount: amount,
      payment_date: paymentForm.value.payment_date,
      method: paymentForm.value.method,
      status: paymentForm.value.status,
      reference_number: paymentForm.value.reference_number,
      notes: paymentForm.value.notes
    };

    if (editingPaymentId.value) {
      await api.put(`/payments/${editingPaymentId.value}`, payload);
    } else {
      await api.post('/payments', {
        payable_type: 'purchase',
        payable_id: paymentPurchase.value.id,
        ...payload
      });
    }
    await loadPurchasePayments(paymentPurchase.value.id);
    resetPaymentForm();
  } catch (err) {
    paymentError.value = err.response?.data?.message || 'Failed to save payment';
  } finally {
    paymentSaving.value = false;
  }
};

const editPayment = (payment) => {
  editingPaymentId.value = payment.id;
  editingPaymentAmount.value = Number(payment.amount || 0);
  paymentForm.value = {
    payment_type: payment.payment_type || 'dp',
    amount: Number(payment.amount || 0),
    payment_date: payment.payment_date || new Date().toISOString().split('T')[0],
    method: payment.method || '',
    status: payment.status || 'paid',
    reference_number: payment.reference_number || '',
    notes: payment.notes || ''
  };
};

const deletePayment = async (payment) => {
  if (!confirm('Delete this payment?')) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    await loadPurchasePayments(paymentPurchase.value?.id);
    if (editingPaymentId.value === payment.id) {
      resetPaymentForm();
    }
  } catch (err) {
    paymentError.value = err.response?.data?.message || 'Failed to delete payment';
  }
};

onMounted(() => {
  loadPurchases();
  loadProducts();
  loadWarehouses();
});
</script>
