<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          Sales Management</h2>
        <p class="text-sm text-gray-600 mt-1">Create and manage sales orders</p>
      </div>
      <button @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        + Create Sale
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input v-model="filters.search" @input="loadSales" type="text" placeholder="Search by invoice or customer..."
          class="input" />
        <select v-model="filters.status" @change="loadSales" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <input v-model="filters.start_date" @change="loadSales" type="date" class="input" />
        <input v-model="filters.end_date" @change="loadSales" type="date" class="input" />
      </div>
    </div>

    <!-- Sales Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Sales Orders</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading sales...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ sale.invoice_number }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ sale.customer_name }}</div>
              <div class="text-sm text-gray-500">{{ sale.customer_phone }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(sale.sale_date).toLocaleDateString() }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">
              <ul class="list-disc list-inside">
                <li v-for="item in sale.items" :key="item.id">
                  {{ item.product?.title }} (x{{ item.quantity }})
                </li>
              </ul>
            </td>
            <td class="px-6 py-4 text-sm font-bold text-gray-900">Rp {{ formatPrice(sale.total_amount) }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadgeClass(sale.status)">
                {{ sale.status }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span v-if="sale.delivery" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                🚚 {{ sale.delivery.tracking_number }}
              </span>
              <span v-else class="text-xs text-gray-400">No delivery</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="!sale.delivery && ['pending', 'completed'].includes(sale.status)" @click="openDeliveryModal(sale)" class="text-teal-600 hover:text-teal-900">+ Delivery</button>
              <button @click="exportExcel(sale.id, sale.invoice_number)" class="text-green-600 hover:text-green-900">Excel</button>
              <button @click="downloadPdf(sale.id, sale.invoice_number)" class="text-red-600 hover:text-red-900">PDF</button>
              <button @click="editSale(sale)" class="text-blue-600 hover:text-blue-900">Edit</button>
              <button @click="viewSale(sale)" class="text-indigo-600 hover:text-indigo-900">View</button>
              <button @click="deleteSale(sale.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="sales.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
        <p class="text-sm text-gray-700">Showing {{ sales.from }} to {{ sales.to }} of {{ sales.total }} sales</p>
        <div class="flex space-x-2">
          <button @click="loadSales(sales.current_page - 1)" :disabled="!sales.prev_page_url"
            class="btn-secondary disabled:opacity-50">
            Previous
          </button>
          <button @click="loadSales(sales.current_page + 1)" :disabled="!sales.next_page_url"
            class="btn-secondary disabled:opacity-50">
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create Sale Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="text-2xl font-bold mb-6 bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
          {{ editMode ? 'Edit Sales Order' : 'Create Sales Order' }}
        </h3>

        <form @submit.prevent="saveSale" class="space-y-6">
          <!-- Customer Information -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-3">Customer Information</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sale Date *</label>
                <input v-model="form.sale_date" type="date" required class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                <input v-model="form.customer_name" required class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input v-model="form.customer_phone" class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="form.customer_email" type="email" class="input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person</label>
                <select v-model="form.sales_person_id" class="input">
                  <option value="">None</option>
                  <option v-for="sp in salesPeople" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="form.customer_address" rows="2" class="input"></textarea>
              </div>
            </div>
          </div>

          <!-- Line Items -->
          <div class="p-4 bg-green-50 rounded-lg">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-semibold text-gray-900">Line Items</h4>
              <div class="flex gap-2">
                <input v-model="productSearch" type="text" placeholder="Search products..."
                  class="input text-sm w-48" />
                <button type="button" @click="showProductModal = true" class="btn-secondary text-sm">+ New
                  Product</button>
                <button type="button" @click="addLineItem" class="btn-primary text-sm">+ Add Item</button>
              </div>
            </div>
            <div class="space-y-3">
              <div v-for="(item, index) in form.items" :key="index"
                class="grid grid-cols-12 gap-2 items-end p-3 bg-white rounded-lg">
                <div class="col-span-5">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Product</label>
                  <select v-model="item.product_id" @change="updatePrice(index)" required class="input text-sm">
                    <option value="">Select Product</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{ product.title || product.name }} - {{ product.brand }} (Stock: {{ product.stock || 0 }})
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
                  <input :value="formatPrice(item.quantity * item.unit_price)" disabled
                    class="input text-sm bg-gray-50" />
                </div>
                <div class="col-span-1">
                  <button type="button" @click="removeLineItem(index)" class="btn-danger text-sm w-full">×</button>
                </div>
              </div>
            </div>
            <div class="mt-4 text-right space-y-1">
              <p class="text-sm text-gray-600">Subtotal: Rp {{ formatPrice(calculateTotal()) }}</p>
              <div class="flex justify-end items-center gap-2">
                <label class="text-sm text-gray-600">Tax:</label>
                <select v-model="form.tax_type" @change="updateTaxCalculation" class="input w-32 text-sm">
                  <option value="">No Tax</option>
                  <option value="ppn">PPN (11%)</option>
                  <option value="pph23">PPh 23 (2%)</option>
                </select>
              </div>
              <p v-if="form.tax_type" class="text-sm text-gray-600">Tax Amount: Rp {{ formatPrice(calculateTax()) }}</p>
              <p class="text-lg font-bold text-gray-900">Grand Total: Rp {{ formatPrice(calculateGrandTotal()) }}</p>
            </div>
          </div>

          <!-- Additional Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
              <input v-model.number="form.discount_amount" type="number" step="0.01" class="input" placeholder="0" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
              <select v-model="form.status" required class="input">
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="form.notes" rows="2" class="input"></textarea>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Saving...' : 'Create Sale' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Delivery Modal -->
    <div v-if="showDeliveryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4 text-teal-600">Create Delivery</h3>
        
        <form @submit.prevent="createDelivery" class="space-y-4">
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-700">Sale Information:</p>
            <p class="text-sm text-gray-600">Invoice: <span class="font-semibold">{{ selectedSale?.invoice_number }}</span></p>
            <p class="text-sm text-gray-600">Customer: <span class="font-semibold">{{ selectedSale?.customer_name }}</span></p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Courier *</label>
            <select v-model="deliveryForm.courier" required class="input">
              <option value="">Select Courier</option>
              <option value="JNE">JNE</option>
              <option value="J&T">J&T</option>
              <option value="SiCepat">SiCepat</option>
              <option value="Ninja Express">Ninja Express</option>
              <option value="AnterAja">AnterAja</option>
              <option value="Pickup">Pickup</option>
              <option value="Other">Other</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number</label>
            <input v-model="deliveryForm.tracking_number" class="input" placeholder="Leave empty to auto-generate" />
            <p class="text-xs text-gray-500 mt-1">Will be auto-generated if left empty</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="deliveryForm.notes" rows="3" class="input"></textarea>
          </div>

          <div v-if="deliveryError" class="bg-red-50 text-red-600 p-2 rounded text-sm">{{ deliveryError }}</div>

          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showDeliveryModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="savingDelivery" class="btn-primary">
              {{ savingDelivery ? 'Creating...' : 'Create Delivery' }}
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const sales = ref({ data: [] });
const products = ref([]);
const salesPeople = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showProductModal = ref(false);
const showDeliveryModal = ref(false);
const editMode = ref(false);
const editId = ref(null);
const saving = ref(false);
const savingProduct = ref(false);
const savingDelivery = ref(false);
const error = ref('');
const productError = ref('');
const deliveryError = ref('');
const productSearch = ref('');
const selectedSale = ref(null);

const filters = ref({
  search: '',
  status: '',
  start_date: '',
  end_date: '',
});

const form = ref({
  invoice_number: '',
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  customer_address: '',
  sales_person_id: '',
  sale_date: new Date().toISOString().split('T')[0],
  status: 'pending',
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

const deliveryForm = ref({
  courier: '',
  tracking_number: '',
  notes: '',
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
    completed: 'badge-success',
    cancelled: 'badge-danger',
  };
  return classes[status] || 'badge';
};

const loadSales = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page, ...filters.value };
    // Load sales with delivery relationship
    const response = await api.get('/sales', { params });
    sales.value = response.data;
  } catch (err) {
    console.error('Failed to load sales:', err);
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

const loadSalesPeople = async () => {
  try {
    const response = await api.get('/sales-people');
    salesPeople.value = response.data;
  } catch (err) {
    console.error('Failed to load sales people:', err);
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
  if (!form.value.items || !Array.isArray(form.value.items)) {
    return 0;
  }
  return form.value.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

const TAX_RATES = {
  ppn: 11,
  pph23: 2,
};

const calculateTax = () => {
  const subtotal = calculateTotal();
  const rate = TAX_RATES[form.value.tax_type] || 0;
  return Math.round(subtotal * (rate / 100));
};

const calculateGrandTotal = () => {
  const subtotal = calculateTotal();
  const tax = calculateTax();
  const discount = form.value.discount_amount || 0;
  return subtotal + tax - discount;
};

const updateTaxCalculation = () => {
  // This method triggers reactivity when tax type changes
};

const resetForm = () => {
  editMode.value = false;
  editId.value = null;
  form.value = {
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    customer_address: '',
    sales_person_id: '',
    sale_date: new Date().toISOString().split('T')[0],
    status: 'pending',
    notes: '',
    tax_type: '',
    discount_amount: 0,
    items: [{ product_id: '', quantity: 1, unit_price: 0 }],
  };
  error.value = '';
};

const saveSale = async () => {
  saving.value = true;
  error.value = '';

  try {
    if (editMode.value) {
      // Only update status and notes when editing
      await api.put(`/sales/${editId.value}`, {
        status: form.value.status,
        notes: form.value.notes
      });
    } else {
      await api.post('/sales', form.value);
    }
    showModal.value = false;
    loadSales();
    resetForm();
  } catch (err) {
    error.value = err.response?.data?.message || `Failed to ${editMode.value ? 'update' : 'create'} sale`;
  } finally {
    saving.value = false;
  }
};

const viewSale = (sale) => {
  alert(`Sale Details:\nInvoice: ${sale.invoice_number}\nCustomer: ${sale.customer_name}\nTotal: Rp ${formatPrice(sale.total_amount)}`);
};

const editSale = (sale) => {
  editMode.value = true;
  editId.value = sale.id;
  form.value = {
    invoice_number: sale.invoice_number,
    customer_name: sale.customer_name,
    customer_email: sale.customer_email || '',
    customer_phone: sale.customer_phone || '',
    customer_address: sale.customer_address || '',
    sales_person_id: sale.sales_person_id || '',
    sale_date: sale.sale_date,
    status: sale.status,
    notes: sale.notes || '',
    items: sale.items || [{ product_id: '', quantity: 1, unit_price: 0 }]
  };
  showModal.value = true;
};

const deleteSale = async (id) => {
  if (!confirm('Are you sure you want to delete this sale?')) return;

  try {
    await api.delete(`/sales/${id}`);
    loadSales();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete sale');
  }
};

const saveProduct = async () => {
  savingProduct.value = true;
  productError.value = '';

  try {
    productForm.value.sku = `SKU-${Date.now()}`;
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

const openDeliveryModal = (sale) => {
  selectedSale.value = sale;
  deliveryForm.value = {
    courier: '',
    tracking_number: '',
    notes: '',
  };
  deliveryError.value = '';
  showDeliveryModal.value = true;
};

const createDelivery = async () => {
  savingDelivery.value = true;
  deliveryError.value = '';
  
  try {
    const payload = {
      sale_id: selectedSale.value.id,
      courier: deliveryForm.value.courier,
      notes: deliveryForm.value.notes,
    };
    
    // Only include tracking_number if provided
    if (deliveryForm.value.tracking_number) {
      payload.tracking_number = deliveryForm.value.tracking_number;
    }
    
    const response = await api.post('/deliveries', payload);
    
    // Close modal and reload sales
    showDeliveryModal.value = false;
    loadSales();
    
    alert(`Delivery created successfully! Tracking: ${response.data.tracking_number}`);
  } catch (err) {
    deliveryError.value = err.response?.data?.message || 'Failed to create delivery';
  } finally {
    savingDelivery.value = false;
  }
};

const exportExcel = async (saleId, invoiceNumber) => {
  try {
    const response = await api.get(`/sales/${saleId}/export`, {
      responseType: 'blob'
    });
    
    // Create blob link to download
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `invoice_${invoiceNumber}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Export error:', err);
    alert('Failed to export Excel file');
  }
};

const downloadPdf = async (saleId, invoiceNumber) => {
  try {
    const response = await api.get(`/sales/${saleId}/pdf`, {
      responseType: 'blob'
    });
    
    // Create blob link to download
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `invoice_${invoiceNumber}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('PDF download error:', err);
    alert('Failed to download PDF file');
  }
};

onMounted(() => {
  loadSales();
  loadProducts();
  loadSalesPeople();
});
</script>
