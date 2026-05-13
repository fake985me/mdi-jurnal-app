<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="$router.push({ name: 'Sales' })" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <span class="text-xl">←</span>
        </button>
        <div>
          <p class="text-xs text-indigo-600 font-medium cursor-pointer hover:underline" @click="$router.push({ name: 'Sales' })">Sales</p>
          <h2 class="text-2xl font-bold text-gray-900">{{ isEditing ? 'Edit Sales Order' : 'Create Sales Order' }}</h2>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <select v-model="form.status" class="input w-40 text-sm">
          <option value="pending">Sales Order</option>
          <option value="completed">Completed</option>
        </select>
        <p class="text-2xl font-bold text-red-600">Total Rp{{ formatPrice(calculateGrandTotal()) }}</p>
      </div>
    </div>

    <form @submit.prevent="saveSale" class="space-y-6">
      <!-- Top Section: Customer & Email -->
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Customer -->
          <div>
            <label class="block text-sm font-semibold text-indigo-700 mb-1">Customer *</label>
            <div class="flex gap-2">
              <select v-model="form.customer_id" @change="onCustomerSelect" class="input flex-1">
                <option value="">Choose customer</option>
                <option v-for="c in customerList" :key="c.id" :value="c.id">
                  {{ c.company ? `${c.company} (${c.name})` : c.name }}
                </option>
              </select>
              <button type="button" @click="showCustomerModal = true"
                class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 text-sm font-medium whitespace-nowrap">+</button>
            </div>
          </div>
          <!-- Email -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input v-model="form.customer_email" type="email" placeholder="Enter email" class="input" />
          </div>
          <!-- Customer Name -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Customer Name *</label>
            <input v-model="form.customer_name" required class="input" placeholder="Customer name" />
          </div>
        </div>
      </div>

      <!-- Middle Section: Address, Dates, References -->
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- Billing Address -->
          <div class="md:row-span-3">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Billing address</label>
            <textarea v-model="form.customer_address" rows="5" class="input" placeholder="e.g. Jalan Indonesia Blk C No. 22"></textarea>
          </div>
          <!-- Transaction Date -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Transaction date</label>
            <input v-model="form.sale_date" type="date" required class="input" />
          </div>
          <!-- Transaction No -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Transaction no.</label>
            <input v-model="form.invoice_number" class="input" placeholder="[Auto]" />
          </div>
          <!-- Tag / Sales Person -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Sales Person</label>
            <select v-model="form.sales_person_id" class="input">
              <option value="">None</option>
              <option v-for="sp in salesPeople" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
            </select>
          </div>
          <!-- Due Date -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Due date</label>
            <input v-model="form.due_date" type="date" class="input" />
          </div>
          <!-- Customer Phone -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Customer phone</label>
            <input v-model="form.customer_phone" class="input" placeholder="Phone number" />
          </div>
          <!-- Warehouse -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Warehouse</label>
            <select v-model="form.warehouse_id" class="input">
              <option value="">Select warehouse</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>
        </div>

        <!-- Currency & Tax Toggle -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
          <div class="flex items-center gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Currency</label>
              <select class="input w-24 text-sm">
                <option>IDR</option>
              </select>
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" v-model="priceIncludesTax" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            Price includes tax
          </label>
        </div>
      </div>

      <!-- Product Line Items Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-indigo-700 uppercase w-1/4">Product</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Description</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-20">Qty</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-24">Units</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-700 uppercase w-32">Unit price</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-700 uppercase w-28">Discount</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-24">Tax</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-700 uppercase w-32">Amount</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-blue-50/30 transition-colors">
                <td class="px-4 py-3">
                  <select v-model="item.product_id" @change="updatePrice(index)" required class="input text-sm">
                    <option value="">Select product</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{ product.title || product.name }} ({{ product.stock || 0 }})
                    </option>
                  </select>
                </td>
                <td class="px-4 py-3">
                  <input v-model="item.description" type="text" class="input text-sm" placeholder="Description" />
                </td>
                <td class="px-4 py-3">
                  <input v-model.number="item.quantity" type="number" min="1" required class="input text-sm text-center" />
                </td>
                <td class="px-4 py-3">
                  <select v-model="item.unit" class="input text-sm">
                    <option value="pcs">Pcs</option>
                    <option value="unit">Unit</option>
                    <option value="set">Set</option>
                    <option value="box">Box</option>
                  </select>
                </td>
                <td class="px-4 py-3">
                  <input v-model.number="item.unit_price" type="number" step="0.01" required class="input text-sm text-right" />
                </td>
                <td class="px-4 py-3">
                  <input v-model.number="item.discount" type="number" step="0.01" class="input text-sm text-right" placeholder="0" />
                </td>
                <td class="px-4 py-3 text-center">
                  <select v-model="item.tax_code" class="input text-sm">
                    <option value="">—</option>
                    <option v-for="tax in taxRates" :key="tax.id" :value="tax.code">{{ tax.name }}</option>
                  </select>
                </td>
                <td class="px-4 py-3 text-right">
                  <span class="text-sm font-semibold text-gray-900">Rp{{ formatPrice(getLineAmount(item)) }}</span>
                </td>
                <td class="px-4 py-3">
                  <button type="button" @click="removeLineItem(index)"
                    class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
          <button type="button" @click="addLineItem"
            class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
            <span class="text-lg">+</span> Add new line
          </button>
        </div>
      </div>

      <!-- Bottom Section: Message/Memo + Summary -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left: Message, Memo, Attachments -->
        <div class="space-y-4">
          <div class="card p-5">
            <label class="block text-sm font-semibold text-indigo-700 mb-2">Message</label>
            <textarea v-model="form.notes" rows="3" class="input" placeholder="Message"></textarea>
          </div>
          <div class="card p-5">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Memo</label>
            <textarea v-model="form.memo" rows="2" class="input" placeholder="Memo"></textarea>
          </div>
        </div>

        <!-- Right: Financial Summary -->
        <div class="card p-5 space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-sm font-semibold text-indigo-700">Subtotal</span>
            <span class="text-sm font-bold text-gray-900">Rp{{ formatPrice(calculateTotal()) }}</span>
          </div>
          <!-- Discount per lines -->
          <div class="flex justify-between items-center text-sm">
            <span class="text-indigo-600">Discount per lines</span>
            <span class="text-indigo-600">Rp{{ formatPrice(calculateLineDiscounts()) }}</span>
          </div>
          <!-- Global Discount -->
          <div class="flex justify-between items-center gap-4">
            <span class="text-sm text-gray-600">Discount</span>
            <div class="flex items-center gap-2">
              <select v-model="discountType" class="input w-16 text-sm py-1">
                <option value="%">%</option>
                <option value="fixed">Rp</option>
              </select>
              <input v-model.number="discountValue" type="number" step="0.01" class="input w-24 text-sm text-right py-1" placeholder="0" />
            </div>
            <span class="text-sm text-gray-900">Rp{{ formatPrice(calculateGlobalDiscount()) }}</span>
          </div>
          <!-- Tax -->
          <div v-if="form.tax_type" class="flex justify-between items-center py-1 text-sm bg-amber-50 px-3 rounded">
            <span class="text-amber-700 font-medium">{{ taxDisplayName(form.tax_type) }} ({{ getSelectedTaxRate() }}%)</span>
            <span class="text-amber-800 font-semibold">+ Rp{{ formatPrice(calculateTax()) }}</span>
          </div>
          <div class="flex justify-between items-center gap-2">
            <label class="text-sm text-gray-600">Tax:</label>
            <select v-model="form.tax_type" class="input w-48 text-sm py-1">
              <option value="">No Tax</option>
              <option v-for="tax in taxRates" :key="tax.id" :value="tax.code">{{ tax.name }} ({{ tax.rate }}%)</option>
            </select>
          </div>
          <div class="border-t border-gray-200 pt-3">
            <div class="flex justify-between items-center">
              <span class="text-base font-bold text-gray-900">Total</span>
              <span class="text-base font-bold text-gray-900">Rp{{ formatPrice(calculateGrandTotal()) }}</span>
            </div>
          </div>
          <!-- Deposit checkbox -->
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" v-model="hasDeposit" class="rounded border-gray-300 text-indigo-600" />
            Deposit
          </label>
          <div v-if="hasDeposit">
            <input v-model.number="depositAmount" type="number" step="0.01" class="input text-sm" placeholder="Deposit amount" />
          </div>
          <div class="border-t-2 border-gray-300 pt-3">
            <div class="flex justify-between items-center">
              <span class="text-base font-bold text-indigo-700">Balance due</span>
              <span class="text-lg font-bold text-indigo-700">Rp{{ formatPrice(calculateBalanceDue()) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg text-sm border border-red-200">{{ error }}</div>

      <!-- Action Buttons -->
      <div class="flex justify-end items-center gap-3 pb-6">
        <button type="button" @click="$router.push({ name: 'Sales' })" class="btn-secondary px-6">Cancel</button>
        <button type="submit" :disabled="saving"
          class="px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium disabled:opacity-50">
          {{ saving ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
        </button>
      </div>
    </form>

    <!-- Quick Add Customer Modal -->
    <div v-if="showCustomerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-bold mb-4 bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Quick Add Customer</h3>
        <form @submit.prevent="saveNewCustomer" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
            <input v-model="customerForm.name" required class="input" placeholder="Contact person name" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
            <input v-model="customerForm.company" class="input" placeholder="Company name" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input v-model="customerForm.phone" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="customerForm.email" type="email" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea v-model="customerForm.address" rows="2" class="input"></textarea>
          </div>
          <div v-if="customerError" class="bg-red-50 text-red-600 p-2 rounded text-sm">{{ customerError }}</div>
          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showCustomerModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="savingCustomer" class="btn-primary">
              {{ savingCustomer ? 'Saving...' : 'Add Customer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const route = useRoute();

const isEditing = computed(() => !!route.params.id);

// Data lists
const products = ref([]);
const salesPeople = ref([]);
const warehouses = ref([]);
const customerList = ref([]);
const taxRates = ref([]);

// UI state
const saving = ref(false);
const error = ref('');
const showCustomerModal = ref(false);
const savingCustomer = ref(false);
const customerError = ref('');
const priceIncludesTax = ref(false);
const hasDeposit = ref(false);
const depositAmount = ref(0);
const discountType = ref('%');
const discountValue = ref(0);

// Form
const form = ref({
  invoice_number: '',
  customer_id: '',
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  customer_address: '',
  sales_person_id: '',
  warehouse_id: '',
  sale_date: new Date().toISOString().split('T')[0],
  due_date: '',
  status: 'pending',
  notes: '',
  memo: '',
  tax_type: '',
  discount_amount: 0,
  items: [{ product_id: '', quantity: 1, unit_price: 0, description: '', unit: 'pcs', discount: 0, tax_code: '' }],
});

const customerForm = ref({ name: '', company: '', phone: '', email: '', address: '', npwp: '' });

const filteredProducts = computed(() => products.value);

// Helpers
const formatPrice = (price) => new Intl.NumberFormat('id-ID').format(price || 0);

const taxDisplayName = (code) => {
  const names = { ppn: 'PPN', pph23: 'PPh 23' };
  return names[code] || code?.toUpperCase() || '';
};

const getLineAmount = (item) => {
  const subtotal = (item.quantity || 0) * (item.unit_price || 0);
  return subtotal - (item.discount || 0);
};

const calculateTotal = () => {
  if (!form.value.items) return 0;
  return form.value.items.reduce((sum, item) => sum + ((item.quantity || 0) * (item.unit_price || 0)), 0);
};

const calculateLineDiscounts = () => {
  if (!form.value.items) return 0;
  return form.value.items.reduce((sum, item) => sum + (item.discount || 0), 0);
};

const calculateGlobalDiscount = () => {
  if (!discountValue.value) return 0;
  if (discountType.value === '%') {
    return Math.round(calculateTotal() * (discountValue.value / 100));
  }
  return discountValue.value;
};

const getSelectedTaxRate = () => {
  if (!form.value.tax_type) return 0;
  const tax = taxRates.value.find(t => t.code === form.value.tax_type);
  return tax ? parseFloat(tax.rate) : 0;
};

const calculateTax = () => {
  const subtotal = calculateTotal() - calculateLineDiscounts() - calculateGlobalDiscount();
  const rate = getSelectedTaxRate();
  return Math.round(subtotal * (rate / 100));
};

const calculateGrandTotal = () => {
  const subtotal = calculateTotal();
  const lineDisc = calculateLineDiscounts();
  const globalDisc = calculateGlobalDiscount();
  const tax = calculateTax();
  return subtotal - lineDisc - globalDisc + tax;
};

const calculateBalanceDue = () => {
  return calculateGrandTotal() - (hasDeposit.value ? (depositAmount.value || 0) : 0);
};

// Actions
const onCustomerSelect = () => {
  const cid = form.value.customer_id;
  if (!cid) return;
  const customer = customerList.value.find(c => c.id == cid);
  if (customer) {
    form.value.customer_name = customer.name;
    form.value.customer_phone = customer.phone || '';
    form.value.customer_email = customer.email || '';
    form.value.customer_address = customer.address || '';
  }
};

const addLineItem = () => {
  form.value.items.push({ product_id: '', quantity: 1, unit_price: 0, description: '', unit: 'pcs', discount: 0, tax_code: '' });
};

const removeLineItem = (index) => {
  if (form.value.items.length > 1) form.value.items.splice(index, 1);
};

const updatePrice = (index) => {
  const product = products.value.find(p => p.id == form.value.items[index].product_id);
  if (product) {
    form.value.items[index].unit_price = product.price;
    form.value.items[index].description = product.brand || '';
  }
};

const saveSale = async () => {
  saving.value = true;
  error.value = '';
  try {
    const payload = {
      ...form.value,
      discount_amount: calculateLineDiscounts() + calculateGlobalDiscount(),
    };
    if (isEditing.value) {
      await api.put(`/sales/${route.params.id}`, { status: payload.status, notes: payload.notes });
    } else {
      await api.post('/sales', payload);
    }
    router.push({ name: 'Sales' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save sale';
  } finally {
    saving.value = false;
  }
};

const saveNewCustomer = async () => {
  savingCustomer.value = true;
  customerError.value = '';
  try {
    const response = await api.post('/customers', customerForm.value);
    customerList.value.push(response.data);
    form.value.customer_id = response.data.id;
    onCustomerSelect();
    showCustomerModal.value = false;
    customerForm.value = { name: '', company: '', phone: '', email: '', address: '', npwp: '' };
  } catch (err) {
    customerError.value = err.response?.data?.message || 'Failed to add customer';
  } finally {
    savingCustomer.value = false;
  }
};

// Load data
const loadProducts = async () => {
  try {
    const whResponse = await api.get('/warehouses', { params: { per_page: 200 } });
    const allWarehouses = whResponse.data.data || whResponse.data || [];
    const defaultWarehouse = allWarehouses.find(w => w.is_default);
    if (defaultWarehouse) {
      const response = await api.get('/stock', { params: { warehouse_id: defaultWarehouse.id, per_page: 1000 } });
      const stocks = response.data.data || response.data || [];
      products.value = stocks.filter(s => s.product).map(s => ({ ...s.product, stock: s.quantity || 0 }));
    } else {
      const response = await api.get('/products', { params: { per_page: 1000 } });
      products.value = response.data.data;
    }
  } catch (err) { console.error(err); }
};

const loadSale = async () => {
  if (!route.params.id) return;
  try {
    const response = await api.get(`/sales/${route.params.id}`);
    const sale = response.data;
    form.value = {
      invoice_number: sale.invoice_number,
      customer_id: sale.customer_id || '',
      customer_name: sale.customer_name,
      customer_email: sale.customer_email || '',
      customer_phone: sale.customer_phone || '',
      customer_address: sale.customer_address || '',
      sales_person_id: sale.sales_person_id || '',
      warehouse_id: sale.warehouse_id || '',
      sale_date: sale.sale_date,
      due_date: '',
      status: sale.status,
      notes: sale.notes || '',
      memo: '',
      tax_type: sale.tax_type || '',
      discount_amount: sale.discount_amount || 0,
      items: (sale.items || []).map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: parseFloat(item.unit_price),
        description: item.product?.brand || '',
        unit: 'pcs',
        discount: 0,
        tax_code: '',
      })),
    };
    if (!form.value.items.length) {
      form.value.items = [{ product_id: '', quantity: 1, unit_price: 0, description: '', unit: 'pcs', discount: 0, tax_code: '' }];
    }
  } catch (err) {
    console.error(err);
    error.value = 'Failed to load sale data';
  }
};

onMounted(async () => {
  await Promise.all([
    loadProducts(),
    api.get('/sales-people').then(r => salesPeople.value = r.data).catch(() => {}),
    api.get('/warehouses', { params: { per_page: 200 } }).then(r => warehouses.value = r.data.data || r.data || []).catch(() => {}),
    api.get('/customers', { params: { all: true } }).then(r => customerList.value = r.data).catch(() => {}),
    api.get('/tax-rates/active').then(r => taxRates.value = r.data).catch(() => {
      taxRates.value = [{ id: 1, name: 'PPN', code: 'ppn', rate: '11.00' }, { id: 2, name: 'PPh 23', code: 'pph23', rate: '2.00' }];
    }),
  ]);
  if (isEditing.value) await loadSale();
});
</script>
