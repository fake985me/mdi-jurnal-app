<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
          Warehouse Management
        </h2>
        <p class="text-sm text-gray-600 mt-1">Manage your warehouses and storage locations</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="openAddProductModal()"
          class="px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-lg hover:from-orange-600 hover:to-amber-600 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
          + Add Product to Warehouse
        </button>
        <button
          @click="showModal = true; resetForm()"
          class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
          + New Warehouse
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card p-4 bg-gradient-to-br from-emerald-50 to-teal-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Warehouses</p>
            <p class="text-2xl font-bold text-emerald-600">{{ stats.total_warehouses || 0 }}</p>
          </div>
          <span class="text-3xl">🏭</span>
        </div>
      </div>
      <div class="card p-4 bg-gradient-to-br from-blue-50 to-indigo-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Active Warehouses</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.active_warehouses || 0 }}</p>
          </div>
          <span class="text-3xl">✅</span>
        </div>
      </div>
      <div class="card p-4 bg-gradient-to-br from-purple-50 to-pink-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Locations</p>
            <p class="text-2xl font-bold text-purple-600">{{ stats.total_locations || 0 }}</p>
          </div>
          <span class="text-3xl">📍</span>
        </div>
      </div>
      <div class="card p-4 bg-gradient-to-br from-orange-50 to-amber-50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Active Locations</p>
            <p class="text-2xl font-bold text-orange-600">{{ stats.active_locations || 0 }}</p>
          </div>
          <span class="text-3xl">📦</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="filters.search"
          @input="loadWarehouses"
          type="text"
          placeholder="Search warehouses..."
          class="input" />
        <select v-model="filters.is_active" @change="loadWarehouses" class="input">
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Warehouse Tabs Navigation -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="border-b border-gray-200">
        <nav class="flex overflow-x-auto" aria-label="Warehouse Tabs">
          <button
            v-for="warehouse in allWarehouses"
            :key="warehouse.id"
            @click="selectWarehouseTab(warehouse)"
            :class="[
              'px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 flex items-center gap-2',
              activeWarehouseId === warehouse.id
                ? 'border-emerald-500 text-emerald-600 bg-emerald-50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50'
            ]">
            <span>{{ warehouse.name }}</span>
            <span v-if="warehouse.is_default" class="px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-700">Default</span>
            <span v-if="warehouse.project_investment" class="px-1.5 py-0.5 text-xs rounded bg-indigo-100 text-indigo-700">Project</span>
            <span class="px-1.5 py-0.5 text-xs rounded bg-gray-100 text-gray-600">{{ warehouse.stocks_sum_quantity || 0 }}</span>
          </button>
        </nav>
      </div>

      <!-- Selected Warehouse Content -->
      <div v-if="selectedWarehouse" class="p-6">
        <!-- Warehouse Header -->
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">{{ selectedWarehouse.name }}</h3>
            <p class="text-sm text-gray-500">{{ selectedWarehouse.code }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span :class="selectedWarehouse.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              class="px-3 py-1 text-sm rounded-full">
              {{ selectedWarehouse.is_active ? 'Active' : 'Inactive' }}
            </span>
            <button @click="editWarehouse(selectedWarehouse)" class="text-blue-600 hover:text-blue-900 text-sm">Edit</button>
            <button v-if="!selectedWarehouse.is_default" @click="setDefault(selectedWarehouse)" class="text-yellow-600 hover:text-yellow-900 text-sm">Set Default</button>
            <button @click="deleteWarehouse(selectedWarehouse)" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
          </div>
        </div>

        <!-- Warehouse Info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
          <div><span class="text-gray-500">Address:</span> {{ selectedWarehouse.address || '-' }}</div>
          <div><span class="text-gray-500">Phone:</span> {{ selectedWarehouse.phone || '-' }}</div>
          <div><span class="text-gray-500">Email:</span> {{ selectedWarehouse.email || '-' }}</div>
          <div><span class="text-gray-500">Total Stock:</span> <strong class="text-emerald-600">{{ selectedWarehouse.stocks_sum_quantity || 0 }}</strong></div>
        </div>

        <!-- Project & Contract Section -->
        <div v-if="selectedWarehouse.project_investment" class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="font-semibold text-indigo-900">Project Investment</h4>
              <p class="text-sm text-indigo-700">
                {{ selectedWarehouse.project_investment.project_name }} ({{ selectedWarehouse.project_investment.project_code }})
              </p>
              <p class="text-xs text-indigo-600">
                Type: {{ selectedWarehouse.project_investment.type || '-' }} |
                Status: {{ selectedWarehouse.project_investment.status || '-' }}
              </p>
            </div>
            <span class="px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700">Project Warehouse</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3 text-sm">
            <div><span class="text-gray-500">Client:</span> {{ selectedWarehouse.project_investment.client_name || '-' }}</div>
            <div><span class="text-gray-500">Start:</span> {{ formatDate(selectedWarehouse.project_investment.start_date) }}</div>
            <div><span class="text-gray-500">Expected End:</span> {{ formatDate(selectedWarehouse.project_investment.expected_end_date) }}</div>
          </div>

          <div v-if="selectedContract" class="mt-4 p-3 bg-white rounded-lg border border-indigo-100">
            <div class="flex justify-between items-center mb-2">
              <h5 class="font-semibold text-gray-800">Contract</h5>
              <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ selectedContract.status || '-' }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
              <div><span class="text-gray-500">Contract No:</span> {{ selectedContract.contract_number || '-' }}</div>
              <div><span class="text-gray-500">Contract Date:</span> {{ formatDate(selectedContract.contract_date) }}</div>
              <div><span class="text-gray-500">Value:</span> {{ formatCurrency(selectedContract.contract_value || 0) }}</div>
            </div>
          </div>
        </div>

        <!-- Contract Payments -->
        <div v-if="selectedContract" class="border-t pt-4 mt-4">
          <div class="flex justify-between items-center mb-2">
            <h4 class="font-semibold text-gray-800">Contract Payments</h4>
            <button @click="togglePaymentForm" class="text-sm text-indigo-600 hover:text-indigo-800">
              {{ showPaymentForm ? 'Hide Form' : '+ Add Payment' }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm mb-4">
            <div class="p-3 bg-gray-50 rounded">
              <div class="text-gray-500">Contract Total</div>
              <div class="font-semibold text-gray-800">{{ formatCurrency(contractTotal) }}</div>
            </div>
            <div class="p-3 bg-gray-50 rounded">
              <div class="text-gray-500">Paid</div>
              <div class="font-semibold text-emerald-600">{{ formatCurrency(totalPaid) }}</div>
            </div>
            <div class="p-3 bg-gray-50 rounded">
              <div class="text-gray-500">Remaining</div>
              <div class="font-semibold text-orange-600">{{ formatCurrency(remainingAmount) }}</div>
            </div>
          </div>

          <div v-if="showPaymentForm" class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
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
                <p v-if="paymentForm.amount && contractTotal > 0 && paymentForm.amount > remainingAvailable"
                  class="text-xs text-red-600 mt-1">
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
                <button type="button" @click="togglePaymentForm" class="btn-secondary">Cancel</button>
                <button type="submit" :disabled="paymentSaving" class="btn-primary">
                  {{ paymentSaving ? 'Saving...' : 'Save Payment' }}
                </button>
              </div>
            </form>
          </div>

          <div v-if="paymentsLoading" class="text-center py-4 text-gray-500">Loading payments...</div>
          <table v-else-if="contractPayments.length" class="min-w-full text-sm">
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
            <tr v-for="payment in contractPayments" :key="payment.id">
                <td class="px-4 py-2">{{ formatDate(payment.payment_date) }}</td>
                <td class="px-4 py-2 capitalize">{{ payment.payment_type?.replace('_', ' ') }}</td>
                <td class="px-4 py-2">{{ payment.method || '-' }}</td>
                <td class="px-4 py-2">{{ payment.reference_number || '-' }}</td>
                <td class="px-4 py-2 text-right font-semibold">{{ formatCurrency(payment.amount || 0) }}</td>
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

        <!-- Product Stocks Section -->
        <div class="border-t pt-4 mt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">Product Stocks ({{ warehouseStocks.length }})</h4>
            <button v-if="selectedWarehouse.is_default" @click="openAddProductModal()" 
              class="text-sm text-orange-600 hover:text-orange-800">+ Add Product</button>
          </div>

          <div v-if="loadingStocks" class="text-center py-4 text-gray-500">Loading stocks...</div>
          
          <table v-else-if="warehouseStocks.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">SKU</th>
                <th class="px-4 py-2 text-left">Product</th>
                <th class="px-4 py-2 text-left">Brand</th>
                <th class="px-4 py-2 text-left">Location</th>
                <th class="px-4 py-2 text-right">Quantity</th>
                <th class="px-4 py-2 text-right">Last Updated</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="stock in warehouseStocks" :key="stock.id">
                <td class="px-4 py-2 font-mono text-sm">{{ stock.product?.sku || '-' }}</td>
                <td class="px-4 py-2 font-medium">{{ stock.product?.title || '-' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ stock.product?.brand || '-' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ stock.location?.name || 'No Location' }}</td>
                <td class="px-4 py-2 text-right">
                  <span class="font-bold text-emerald-600">{{ stock.quantity }}</span>
                </td>
                <td class="px-4 py-2 text-right text-gray-500 text-xs">
                  {{ stock.last_updated ? new Date(stock.last_updated).toLocaleDateString() : '-' }}
                </td>
              <td class="px-4 py-2 text-right text-gray-500 text-xs">
                <button
                  :disabled="!stock.product"
                  @click="editProduct(stock.product)"
                  class="text-indigo-600 hover:text-indigo-900 disabled:text-gray-400 disabled:cursor-not-allowed">
                  Edit
                </button>
              </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">No products in this warehouse</p>
        </div>

        <!-- Locations Section -->
        <div class="border-t pt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">Locations ({{ selectedWarehouse.locations?.length || 0 }})</h4>
            <button @click="showLocationModal = true; resetLocationForm()" class="text-sm text-emerald-600 hover:text-emerald-800">+ Add Location</button>
          </div>

          <table v-if="selectedWarehouse.locations?.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Zone/Aisle/Rack</th>
                <th class="px-4 py-2 text-right">Capacity</th>
                <th class="px-4 py-2 text-right">Stock</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="loc in selectedWarehouse.locations" :key="loc.id">
                <td class="px-4 py-2 font-medium">{{ loc.code }}</td>
                <td class="px-4 py-2">{{ loc.name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ [loc.zone, loc.aisle, loc.rack, loc.shelf].filter(Boolean).join('-') || '-' }}</td>
                <td class="px-4 py-2 text-right">{{ loc.capacity || '-' }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ loc.stocks_sum_quantity || 0 }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="loc.is_active ? 'text-green-600' : 'text-gray-400'">{{ loc.is_active ? '●' : '○' }}</span>
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <button @click="editLocation(loc)" class="text-blue-600 hover:text-blue-900">Edit</button>
                  <button @click="deleteLocation(loc)" class="text-red-600 hover:text-red-900">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">No locations defined</p>
        </div>

      </div>

      <div v-else class="p-8 text-center text-gray-500">
        <p v-if="loading">Loading warehouses...</p>
        <p v-else>Select a warehouse tab to view details</p>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">{{ editingId ? 'Edit Warehouse' : 'New Warehouse' }}</h3>
        
        <form @submit.prevent="saveWarehouse" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
              <input v-model="form.code" class="input" placeholder="Auto-generated if empty" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
              <input v-model="form.name" required class="input" placeholder="Warehouse name" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea v-model="form.address" rows="2" class="input" placeholder="Full address"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input v-model="form.phone" class="input" placeholder="Phone number" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="form.email" type="email" class="input" placeholder="Email address" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="input" placeholder="Optional description"></textarea>
          </div>

          <div class="flex items-center space-x-6">
            <label class="flex items-center">
              <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-emerald-600 rounded" />
              <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
            <label class="flex items-center">
              <input v-model="form.is_default" type="checkbox" class="h-4 w-4 text-yellow-600 rounded" />
              <span class="ml-2 text-sm text-gray-700">Set as Default</span>
            </label>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save Warehouse' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Warehouse Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">{{ selectedWarehouse?.name }}</h3>
            <p class="text-sm text-gray-500">{{ selectedWarehouse?.code }}</p>
          </div>
          <span :class="selectedWarehouse?.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
            class="px-3 py-1 text-sm rounded-full">
            {{ selectedWarehouse?.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <!-- Warehouse Info -->
        <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
          <div><span class="text-gray-500">Address:</span> {{ selectedWarehouse?.address || '-' }}</div>
          <div><span class="text-gray-500">Phone:</span> {{ selectedWarehouse?.phone || '-' }}</div>
          <div><span class="text-gray-500">Email:</span> {{ selectedWarehouse?.email || '-' }}</div>
          <div><span class="text-gray-500">Total Stock:</span> <strong>{{ selectedWarehouse?.stocks_sum_quantity || 0 }}</strong></div>
        </div>

        <!-- Locations Section -->
        <div class="border-t pt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">Locations ({{ selectedWarehouse?.locations?.length || 0 }})</h4>
            <button @click="showLocationModal = true; resetLocationForm()" class="text-sm text-emerald-600 hover:text-emerald-800">+ Add Location</button>
          </div>

          <table v-if="selectedWarehouse?.locations?.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Zone/Aisle/Rack</th>
                <th class="px-4 py-2 text-right">Capacity</th>
                <th class="px-4 py-2 text-right">Stock</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="loc in selectedWarehouse?.locations" :key="loc.id">
                <td class="px-4 py-2 font-medium">{{ loc.code }}</td>
                <td class="px-4 py-2">{{ loc.name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ [loc.zone, loc.aisle, loc.rack, loc.shelf].filter(Boolean).join('-') || '-' }}</td>
                <td class="px-4 py-2 text-right">{{ loc.capacity || '-' }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ loc.stocks_sum_quantity || 0 }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="loc.is_active ? 'text-green-600' : 'text-gray-400'">{{ loc.is_active ? '●' : '○' }}</span>
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <button @click="editLocation(loc)" class="text-blue-600 hover:text-blue-900">Edit</button>
                  <button @click="deleteLocation(loc)" class="text-red-600 hover:text-red-900">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">No locations defined</p>
        </div>

        <!-- Product Stocks Section -->
        <div class="border-t pt-4 mt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-semibold text-gray-800">Product Stocks ({{ warehouseStocks.length }})</h4>
            <button v-if="selectedWarehouse?.is_default" @click="openAddProductModal()" 
              class="text-sm text-orange-600 hover:text-orange-800">+ Add Product</button>
          </div>

          <div v-if="loadingStocks" class="text-center py-4 text-gray-500">Loading stocks...</div>
          
          <table v-else-if="warehouseStocks.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">SKU</th>
                <th class="px-4 py-2 text-left">Product</th>
                <th class="px-4 py-2 text-left">Brand</th>
                <th class="px-4 py-2 text-left">Location</th>
                <th class="px-4 py-2 text-right">Quantity</th>
                <th class="px-4 py-2 text-right">Last Updated</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="stock in warehouseStocks" :key="stock.id">
                <td class="px-4 py-2 font-mono text-sm">{{ stock.product?.sku || '-' }}</td>
                <td class="px-4 py-2 font-medium">{{ stock.product?.title || '-' }}</td>
                <td class="px-4 py-2 text-gray-600">{{ stock.product?.brand || '-' }}</td>
                <td class="px-4 py-2 text-gray-500">{{ stock.location?.name || 'No Location' }}</td>
                <td class="px-4 py-2 text-right">
                  <span class="font-bold text-emerald-600">{{ stock.quantity }}</span>
                </td>
                <td class="px-4 py-2 text-right text-gray-500 text-xs">
                  {{ stock.last_updated ? new Date(stock.last_updated).toLocaleDateString() : '-' }}
                </td>
              <td class="px-4 py-2 text-right text-gray-500 text-xs">
                <button
                  :disabled="!stock.product"
                  @click="editProduct(stock.product)"
                  class="text-indigo-600 hover:text-indigo-900 disabled:text-gray-400 disabled:cursor-not-allowed">
                  Edit
                </button>
              </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center text-gray-500 py-4">No products in this warehouse</p>
        </div>

        <div class="flex justify-end mt-6">
          <button @click="showViewModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- Location Modal -->
    <div v-if="showLocationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ editingLocationId ? 'Edit Location' : 'New Location' }}</h3>
        
        <form @submit.prevent="saveLocation" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
              <input v-model="locationForm.code" class="input" placeholder="Auto-generated" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
              <input v-model="locationForm.name" required class="input" placeholder="Location name" />
            </div>
          </div>

          <div class="grid grid-cols-4 gap-2">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Zone</label>
              <input v-model="locationForm.zone" class="input" placeholder="A" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Aisle</label>
              <input v-model="locationForm.aisle" class="input" placeholder="1" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Rack</label>
              <input v-model="locationForm.rack" class="input" placeholder="01" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Shelf</label>
              <input v-model="locationForm.shelf" class="input" placeholder="A" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
            <input v-model.number="locationForm.capacity" type="number" min="0" class="input" placeholder="Max items (optional)" />
          </div>

          <label class="flex items-center">
            <input v-model="locationForm.is_active" type="checkbox" class="h-4 w-4 text-emerald-600 rounded" />
            <span class="ml-2 text-sm text-gray-700">Active</span>
          </label>

          <div v-if="locationError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ locationError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showLocationModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="savingLocation" class="btn-primary">{{ savingLocation ? 'Saving...' : 'Save Location' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Product Stock Modal -->
    <div v-if="showAddProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-xl font-bold mb-4">Add Product to Default Warehouse</h3>
        
        <div v-if="!defaultWarehouse" class="bg-yellow-50 text-yellow-700 p-4 rounded-lg mb-4">
          <p class="font-medium">No default warehouse set!</p>
          <p class="text-sm">Please set a warehouse as default first by clicking "Set Default" on a warehouse.</p>
        </div>
        
        <div v-else class="mb-4 p-3 bg-emerald-50 rounded-lg">
          <p class="text-sm text-gray-600">Adding to:</p>
          <p class="font-medium text-emerald-700">{{ defaultWarehouse.name }} ({{ defaultWarehouse.code }})</p>
        </div>
        
        <form @submit.prevent="submitAddProduct" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Product *</label>

            <select v-model="addProductForm.product_id" @change="onProductSelect" required class="input text-sm">
                    <option value="">Select Product</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{ product.title || product.name }} - {{ product.brand }} (Stock: {{ product.stock || 0 }})
                    </option>
                  </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
            <input v-model.number="addProductForm.quantity" type="number" min="1" required class="input" placeholder="Enter quantity" :disabled="!defaultWarehouse" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="addProductForm.notes" rows="2" class="input" placeholder="Optional notes..." :disabled="!defaultWarehouse"></textarea>
          </div>

          <div v-if="addProductError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ addProductError }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showAddProductModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="addingProduct || !defaultWarehouse || !addProductForm.product_id" class="btn-primary">
              {{ editingProduct ? 'Edit Product' : 'Add Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();

const warehouses = ref({ data: [] });
const stats = ref({});
const loading = ref(true);
const showModal = ref(false);
const showViewModal = ref(false);
const showLocationModal = ref(false);
const selectedWarehouse = ref(null);
const activeWarehouseId = ref(null);
const allWarehouses = ref([]);
const editingId = ref(null);
const editingLocationId = ref(null);
const editingProduct = ref(null);
const saving = ref(false);
const savingLocation = ref(false);
const error = ref('');
const locationError = ref('');
const contractPayments = ref([]);
const paymentsLoading = ref(false);
const showPaymentForm = ref(false);
const paymentSaving = ref(false);
const paymentError = ref('');
const editingPaymentId = ref(null);
const editingPaymentAmount = ref(0);

const filters = ref({ search: '', is_active: '' });

const form = ref({
  code: '',
  name: '',
  address: '',
  phone: '',
  email: '',
  description: '',
  is_active: true,
  is_default: false,
});

const locationForm = ref({
  code: '',
  name: '',
  zone: '',
  aisle: '',
  rack: '',
  shelf: '',
  capacity: null,
  is_active: true,
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

const selectedContract = computed(() => selectedWarehouse.value?.project_investment?.contract || null);
const contractTotal = computed(() => Number(selectedContract.value?.contract_value || 0));
const totalPaid = computed(() => contractPayments.value.reduce((sum, p) => {
  if (p.status === 'cancelled') return sum;
  return sum + Number(p.amount || 0);
}, 0));
const remainingAmount = computed(() => Math.max(0, contractTotal.value - totalPaid.value));
const remainingAvailable = computed(() => {
  if (!editingPaymentId.value) return remainingAmount.value;
  return Math.max(0, remainingAmount.value + Number(editingPaymentAmount.value || 0));
});

const loadWarehouses = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warehouses', { params: { page, per_page: 100, ...filters.value } });
    warehouses.value = response.data;
    allWarehouses.value = response.data.data || [];
    
    // Auto-select first warehouse or default warehouse
    if (allWarehouses.value.length > 0 && !activeWarehouseId.value) {
      const defaultWh = allWarehouses.value.find(w => w.is_default) || allWarehouses.value[0];
      selectWarehouseTab(defaultWh);
    }
  } catch (err) {
    console.error('Failed to load warehouses:', err);
  } finally {
    loading.value = false;
  }
};

const selectWarehouseTab = async (warehouse) => {
  activeWarehouseId.value = warehouse.id;
  try {
    const response = await api.get(`/warehouses/${warehouse.id}`);
    selectedWarehouse.value = response.data;
    loadWarehouseStocks(warehouse.id);
    showPaymentForm.value = false;
    resetPaymentForm();
    await loadContractPayments(response.data?.project_investment?.contract?.id);
  } catch (err) {
    console.error('Failed to load warehouse details:', err);
  }
};

const loadStats = async () => {
  try {
    const response = await api.get('/warehouses/statistics');
    stats.value = response.data;
  } catch (err) {
    console.error('Failed to load statistics:', err);
  }
};

const resetForm = () => {
  form.value = { code: '', name: '', address: '', phone: '', email: '', description: '', is_active: true, is_default: false };
  editingId.value = null;
  error.value = '';
};

const resetLocationForm = () => {
  locationForm.value = { code: '', name: '', zone: '', aisle: '', rack: '', shelf: '', capacity: null, is_active: true };
  editingLocationId.value = null;
  locationError.value = '';
};

const saveWarehouse = async () => {
  saving.value = true;
  error.value = '';
  try {
    if (editingId.value) {
      await api.put(`/warehouses/${editingId.value}`, form.value);
    } else {
      await api.post('/warehouses', form.value);
    }
    showModal.value = false;
    loadWarehouses();
    loadStats();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save warehouse';
  } finally {
    saving.value = false;
  }
};

const editWarehouse = (warehouse) => {
  form.value = { ...warehouse };
  editingId.value = warehouse.id;
  showModal.value = true;
  error.value = '';
};

// Warehouse stocks
const warehouseStocks = ref([]);
const loadingStocks = ref(false);

const loadWarehouseStocks = async (warehouseId) => {
  loadingStocks.value = true;
  try {
    const response = await api.get('/stock', {
      params: { warehouse_id: warehouseId, per_page: 100 }
    });
    // Filter stocks for this warehouse AND only show items with quantity > 0
    const allStocks = response.data.data || response.data || [];
    warehouseStocks.value = allStocks.filter(s => s.warehouse_id === warehouseId && s.quantity > 0);
  } catch (err) {
    console.error('Failed to load warehouse stocks:', err);
    // Try alternate endpoint
    try {
      const res = await api.get('/current-stocks', { params: { warehouse_id: warehouseId } });
      const stocks = res.data.data || res.data || [];
      warehouseStocks.value = stocks.filter(s => s.quantity > 0);
    } catch (e) {
      warehouseStocks.value = [];
    }
  } finally {
    loadingStocks.value = false;
  }
};

const viewWarehouse = async (warehouse) => {
  try {
    const response = await api.get(`/warehouses/${warehouse.id}`);
    selectedWarehouse.value = response.data;
    showViewModal.value = true;
    // Load stocks for this warehouse
    loadWarehouseStocks(warehouse.id);
    await loadContractPayments(response.data?.project_investment?.contract?.id);
  } catch (err) {
    alert('Failed to load warehouse details');
  }
};

const deleteWarehouse = async (warehouse) => {
  if (!confirm(`Delete warehouse "${warehouse.name}"?`)) return;
  try {
    await api.delete(`/warehouses/${warehouse.id}`);
    loadWarehouses();
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete warehouse');
  }
};

const setDefault = async (warehouse) => {
  try {
    await api.post(`/warehouses/${warehouse.id}/set-default`);
    loadWarehouses();
  } catch (err) {
    alert('Failed to set default warehouse');
  }
};

const saveLocation = async () => {
  savingLocation.value = true;
  locationError.value = '';
  try {
    if (editingLocationId.value) {
      await api.put(`/warehouse-locations/${editingLocationId.value}`, locationForm.value);
    } else {
      await api.post(`/warehouses/${selectedWarehouse.value.id}/locations`, locationForm.value);
    }
    showLocationModal.value = false;
    viewWarehouse(selectedWarehouse.value); // Refresh
    loadStats();
  } catch (err) {
    locationError.value = err.response?.data?.message || 'Failed to save location';
  } finally {
    savingLocation.value = false;
  }
};

const editLocation = (location) => {
  locationForm.value = { ...location };
  editingLocationId.value = location.id;
  showLocationModal.value = true;
  locationError.value = '';
};

const deleteLocation = async (location) => {
  if (!confirm(`Delete location "${location.name}"?`)) return;
  try {
    await api.delete(`/warehouse-locations/${location.id}`);
    viewWarehouse(selectedWarehouse.value); // Refresh
    loadStats();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete location');
  }
};

const editProduct = (product) => {
    if (!product?.id) {
        console.warn('editProduct called without product');
        return;
    }

    router.push({ name: 'Products', query: { edit: product.id } });
};

const saveProduct = async () => {
    saving.value = true;
    try {
        // Filter out empty category pairs
        const validCategories = form.value.categories.filter(c => c.category_id);
        const payload = {
            ...form.value,
            categories: validCategories
        };

        if (editingProduct.value) {
            await api.put(`/products/${editingProduct.value.id}`, payload);
        } else {
            await api.post('/products', payload);
        }
        showModal.value = false;
        editingProduct.value = null;
        resetForm();
        loadProducts();
    } catch (error) {
        console.error('Failed to save product:', error);
        alert('Failed to save product: ' + (error.response?.data?.message || 'Unknown error'));
    } finally {
        saving.value = false;
    }
};

const deleteProduct = async (id) => {
    if (!confirm('Are you sure you want to delete this product?')) return;
    try {
        await api.delete(`/products/${id}`);
        loadProducts();
    } catch (error) {
        console.error('Failed to delete product:', error);
        alert('Failed to delete product');
    }
};

// Add Product Stock states
const showAddProductModal = ref(false);
const defaultWarehouse = ref(null);
const productSearch = ref('');
const productSearchResults = ref([]);
const selectedProduct = ref(null);
const products = ref({ data: [] });
const addingProduct = ref(false);
const addProductError = ref('');
const addProductForm = ref({
  product_id: '',
  quantity: 1,
  notes: ''
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

const productForm = ref({
  title: '',
  brand: '',
  price: 0,
  stock: 0,
  sku: '',
});

const loadDefaultWarehouse = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100 } });
    const warehouses = response.data.data || [];
    defaultWarehouse.value = warehouses.find(w => w.is_default) || null;
  } catch (err) {
    console.error('Failed to load default warehouse:', err);
  }
};

const openAddProductModal = () => {
  showAddProductModal.value = true;
  resetAddProductForm();
  loadProducts();
};

const resetAddProductForm = () => {
  productSearch.value = '';
  productSearchResults.value = [];
  selectedProduct.value = null;
  addProductError.value = '';
  addProductForm.value = { product_id: '', quantity: 1, notes: '' };
};

let searchTimeout = null;
const searchProducts = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    if (productSearch.value.length < 2) {
      productSearchResults.value = [];
      return;
    }
    try {
      const response = await api.get('/warehouses/products', {
        params: { search: productSearch.value }
      });
      productSearchResults.value = response.data;
    } catch (err) {
      console.error('Failed to search products:', err);
    }
  }, 300);
};

const selectProduct = (product) => {
  selectedProduct.value = product;
  productSearch.value = product.title;
  productSearchResults.value = [];
  addProductForm.value.product_id = product.id;
};

const onProductSelect = () => {
  const selectedId = addProductForm.value.product_id;
  selectedProduct.value = products.value.find(p => p.id == selectedId) || null;
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to load products:', err);
    products.value = [];
  }
};

const submitAddProduct = async () => {
  if (!addProductForm.value.product_id || !defaultWarehouse.value) return;
  
  addingProduct.value = true;
  addProductError.value = '';
  try {
    await api.post('/warehouses/add-product-stock', {
      product_id: addProductForm.value.product_id,
      quantity: addProductForm.value.quantity,
      notes: addProductForm.value.notes
    });
    showAddProductModal.value = false;
    loadWarehouses();
    loadStats();
    alert('Product stock added successfully!');
  } catch (err) {
    addProductError.value = err.response?.data?.message || 'Failed to add product stock';
  } finally {
    addingProduct.value = false;
  }
};

const loadContractPayments = async (contractId) => {
  if (!contractId) {
    contractPayments.value = [];
    return;
  }

  paymentsLoading.value = true;
  try {
    const response = await api.get('/payments', {
      params: { payable_type: 'project_contract', payable_id: contractId, per_page: 100 }
    });
    contractPayments.value = response.data.data || response.data || [];
  } catch (err) {
    console.error('Failed to load contract payments:', err);
    contractPayments.value = [];
  } finally {
    paymentsLoading.value = false;
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

const togglePaymentForm = () => {
  if (showPaymentForm.value) {
    showPaymentForm.value = false;
    resetPaymentForm();
    return;
  }
  showPaymentForm.value = true;
  resetPaymentForm();
};

const submitPayment = async () => {
  if (!selectedContract.value) return;
  paymentError.value = '';

  const amount = Number(paymentForm.value.amount || 0);
  if (amount <= 0) {
    paymentError.value = 'Amount must be greater than 0.';
    return;
  }
  if (contractTotal.value > 0 && amount > remainingAvailable.value + 0.0001) {
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
        payable_type: 'project_contract',
        payable_id: selectedContract.value.id,
        ...payload
      });
    }
    showPaymentForm.value = false;
    resetPaymentForm();
    await loadContractPayments(selectedContract.value.id);
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
  paymentError.value = '';
  showPaymentForm.value = true;
};

const deletePayment = async (payment) => {
  if (!confirm('Delete this payment?')) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    await loadContractPayments(selectedContract.value?.id);
    if (editingPaymentId.value === payment.id) {
      resetPaymentForm();
    }
  } catch (err) {
    paymentError.value = err.response?.data?.message || 'Failed to delete payment';
  }
};

const formatCurrency = (value) => {
  const num = Number(value || 0);
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
};

const formatDate = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleDateString();
};

onMounted(() => {
  loadWarehouses();
  loadStats();
  loadDefaultWarehouse();
});
</script>
