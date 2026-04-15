<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent">RMA
          Management</h2>
        <p class="text-sm text-gray-600 mt-1">Manage product returns and warranty claims</p>
      </div>
      <button @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        + New RMA
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input v-model="filters.search" @input="loadRMAs" type="text" placeholder="Search by code, customer, or SN..."
          class="input" />
        <select v-model="filters.status" @change="loadRMAs" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="received">Received</option>
          <option value="processed">Processed</option>
          <option value="completed">Completed</option>
        </select>
        <select v-model="filters.reason" @change="loadRMAs" class="input">
          <option value="">All Reasons</option>
          <option value="warranty_claim">Warranty Claim</option>
          <option value="damaged_shipment">Damaged Shipment</option>
          <option value="defective">Defective</option>
          <option value="dead_on_arrival">DOA</option>
        </select>
      </div>
    </div>

    <!-- RMAs Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">RMA Records</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RMA Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SN</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evidence</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="rma in rmas.data" :key="rma.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ rma.rma_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ rma.product?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ rma.serial_number || '-' }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ rma.customer_name }}</div>
              <div class="text-xs text-gray-500">{{ rma.customer_contact }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ rma.quantity }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <span class="capitalize">{{ formatReason(rma.reason) }}</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span v-if="rma.condition" :class="getConditionBadge(rma.condition)" class="capitalize">{{
                rma.condition.replace('_', ' ') }}</span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <button v-if="rma.evidence_files && rma.evidence_files.length" @click="viewEvidence(rma)"
                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors cursor-pointer">
                📎 {{ rma.evidence_files.length }} file{{ rma.evidence_files.length > 1 ? 's' : '' }}
              </button>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(rma.status)">{{ rma.status }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="rma.status === 'pending'" @click="approveRMA(rma)"
                class="text-green-600 hover:text-green-900">Approve</button>
              <button v-if="rma.status === 'approved'" @click="markReceived(rma)"
                class="text-blue-600 hover:text-blue-900">Received</button>
              <button @click="deleteRMA(rma.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="rmas.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ rmas.from }} to {{ rmas.to }} of {{ rmas.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadRMAs(rmas.current_page - 1)" :disabled="!rmas.prev_page_url"
            class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadRMAs(rmas.current_page + 1)" :disabled="!rmas.next_page_url"
            class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">New RMA</h3>

        <form @submit.prevent="saveRMA" class="space-y-4">
          <!-- Step 1: Select Sale -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Sale with Warranty/MSA *</label>
            <select v-model="form.sale_id" @change="onSaleSelect" required class="input">
              <option value="">-- Select a Sale --</option>
              <option v-for="sale in sales" :key="sale.id" :value="sale.id">
                {{ sale.invoice_number }} - {{ sale.customer_name }} ({{ formatDate(sale.sale_date) }})
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Only sales with active warranty or MSA are shown</p>
          </div>

          <!-- Step 2: Select Products (multi-select with checkboxes) -->
          <div v-if="form.sale_id && saleItems.length" class="p-4 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center mb-2">
              <label class="block text-sm font-medium text-gray-700">Select Products * <span
                  class="text-gray-400 font-normal">(bisa pilih satu atau banyak)</span></label>
              <button v-if="saleItems.length > 1" type="button" @click="toggleSelectAll"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                {{ allSelected ? 'Batal Semua' : 'Pilih Semua' }}
              </button>
            </div>
            <div class="space-y-2">
              <label v-for="item in saleItems" :key="item.id"
                class="flex items-center p-3 border rounded-lg cursor-pointer transition-all"
                :class="isProductSelected(item.product_id) ? 'bg-white border-blue-500 shadow-sm' : 'hover:bg-gray-100'">
                <input type="checkbox" :checked="isProductSelected(item.product_id)" @change="toggleProduct(item)"
                  class="mr-3 h-4 w-4 text-blue-600 rounded" />
                <div class="flex-1">
                  <span class="font-medium">{{ item.product?.title }}</span>
                  <span class="text-sm text-gray-500 ml-2">(Qty: {{ item.quantity }})</span>
                </div>
                <!-- Inline eligibility indicator -->
                <span v-if="getProductEligibility(item.product_id)?.checked" class="ml-2">
                  <span v-if="getProductEligibility(item.product_id)?.valid" class="text-green-600 text-sm">✓</span>
                  <span v-else class="text-red-600 text-sm">✗</span>
                </span>
              </label>
            </div>
          </div>

          <!-- Per-product SN & Qty (for each selected product) -->
          <div v-if="selectedProducts.length" class="space-y-3">
            <div v-for="(sp, idx) in selectedProducts" :key="sp.product_id" class="p-4 rounded-lg border transition-all"
              :class="getProductCardClass(sp)">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-gray-900">{{ sp.product_title }}</h4>
                <div class="flex items-center space-x-2">
                  <!-- Eligibility badge -->
                  <span v-if="sp.eligibility?.checked" class="text-xs px-2 py-0.5 rounded-full"
                    :class="sp.eligibility?.valid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                    {{ sp.eligibility?.valid ? '✓ Eligible' : '✗ Not eligible' }}
                  </span>
                  <span v-else class="text-xs text-gray-400">Checking...</span>
                </div>
              </div>

              <!-- Eligibility reason -->
              <p v-if="sp.eligibility?.reason" class="text-xs mb-3"
                :class="sp.eligibility?.valid ? 'text-green-600' : 'text-red-600'">
                {{ sp.eligibility.reason }}
              </p>

              <div class="grid grid-cols-2 gap-3">
                <!-- Serial Number -->
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Serial Number (SN)</label>
                  <div class="relative">
                    <input v-model="sp.serial_number" type="text" class="input text-sm pr-8 font-mono"
                      :placeholder="sp.warranty_sn ? 'SN: ' + sp.warranty_sn : 'No SN registered'"
                      :required="!!sp.warranty_sn" />
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                      <span v-if="getSnStatus(sp) === 'match'" class="text-green-600">✓</span>
                      <span v-else-if="getSnStatus(sp) === 'mismatch'" class="text-red-600">✗</span>
                      <span v-else-if="getSnStatus(sp) === 'no_sn'" class="text-gray-400">—</span>
                    </div>
                  </div>
                  <p v-if="sp.warranty_sn" class="text-xs mt-0.5"
                    :class="getSnStatus(sp) === 'match' ? 'text-green-600' : getSnStatus(sp) === 'mismatch' ? 'text-red-600' : 'text-gray-400'">
                    <span v-if="getSnStatus(sp) === 'match'">✓ SN cocok</span>
                    <span v-else-if="getSnStatus(sp) === 'mismatch'">✗ SN tidak cocok ({{ sp.warranty_sn }})</span>
                    <span v-else>Masukkan SN</span>
                  </p>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                    <select v-model="form.reason" required class="input">
                      <option value="">Select Reason</option>
                      <option value="warranty_claim">Warranty Claim</option>
                      <option value="damaged_shipment">Damaged Shipment</option>
                      <option value="defective">Defective</option>
                      <option value="dead_on_arrival">Dead on Arrival (DOA)</option>
                    </select>
                  </div>
                </div>

                <!-- Quantity -->
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                  <input v-model.number="sp.quantity" type="number" :min="1" :max="sp.available_qty || 999" required
                    class="input text-sm" />
                  <p v-if="sp.available_qty !== null" class="text-xs text-gray-500 mt-0.5">
                    Max: {{ sp.available_qty }}
                    <span v-if="sp.existing_rma_qty > 0" class="text-orange-600">
                      (sudah diklaim: {{ sp.existing_rma_qty }})
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Shared form fields -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
              <input v-model="form.customer_name" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Customer Contact</label>
              <input v-model="form.customer_contact" class="input" />
            </div>
            <!-- <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
              <select v-model="form.reason" required class="input">
                <option value="">Select Reason</option>
                <option value="warranty_claim">Warranty Claim</option>
                <option value="damaged_shipment">Damaged Shipment</option>
                <option value="defective">Defective</option>
                <option value="dead_on_arrival">Dead on Arrival (DOA)</option>
              </select>
            </div> -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date *</label>
              <input v-model="form.issue_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3" class="input"></textarea>
          </div>

          <!-- Evidence Upload -->
          <div class="p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <label class="block text-sm font-medium text-gray-700 mb-2">📎 Evidence / Bukti (Opsional)</label>
            <p class="text-xs text-gray-500 mb-3">Upload foto atau dokumen sebagai bukti (max 5 file, masing-masing max
              5MB). Format: JPG, PNG, WebP, PDF</p>

            <div @click="$refs.evidenceInput.click()" @dragover.prevent="dragOver = true" @dragleave="dragOver = false"
              @drop.prevent="handleFileDrop"
              class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200"
              :class="dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-100'">
              <div class="text-gray-500">
                <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-sm">Klik atau drag & drop file di sini</p>
                <p class="text-xs text-gray-400 mt-1">{{ evidenceFiles.length }}/5 file</p>
              </div>
            </div>
            <input ref="evidenceInput" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
              @change="handleFileSelect" class="hidden" />

            <!-- File Previews -->
            <div v-if="evidenceFiles.length" class="mt-3 space-y-2">
              <div v-for="(file, index) in evidenceFiles" :key="index"
                class="flex items-center justify-between p-2 bg-white rounded-lg border">
                <div class="flex items-center space-x-3 min-w-0">
                  <img v-if="file.preview && file.type.startsWith('image/')" :src="file.preview"
                    class="w-10 h-10 object-cover rounded flex-shrink-0" />
                  <div v-else class="w-10 h-10 bg-red-100 rounded flex items-center justify-center flex-shrink-0">
                    <span class="text-red-600 text-xs font-bold">PDF</span>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                  </div>
                </div>
                <button type="button" @click="removeFile(index)"
                  class="text-red-500 hover:text-red-700 ml-2 flex-shrink-0">
                  ✕
                </button>
              </div>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <!-- Submit info -->
          <div v-if="selectedProducts.length" class="bg-blue-50 text-blue-800 p-3 rounded-lg text-sm">
            📋 {{ selectedProducts.length }} produk dipilih — akan dibuat {{ selectedProducts.length }} RMA
          </div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving || !canSubmit" class="btn-primary disabled:opacity-50">
              {{ saving ? 'Saving...' : `Create ${selectedProducts.length || ''} RMA` }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Condition Modal -->
    <div v-if="showConditionModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Select Item Condition</h3>
        <p class="text-sm text-gray-600 mb-4">RMA: {{ selectedRMA?.rma_code }}</p>

        <div class="space-y-3">
          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
            :class="conditionForm.condition === 'working' ? 'border-green-500 bg-green-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="working" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">✅ Working / Functional</p>
              <p class="text-sm text-gray-500">Item is in good working condition</p>
            </div>
          </label>

          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
            :class="conditionForm.condition === 'damaged' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="damaged" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">⚠️ Damaged</p>
              <p class="text-sm text-gray-500">Has physical damage but may be repairable</p>
            </div>
          </label>

          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
            :class="conditionForm.condition === 'broken' ? 'border-red-500 bg-red-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="broken" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">❌ Broken / Not Working</p>
              <p class="text-sm text-gray-500">Not functioning, needs repair or replacement</p>
            </div>
          </label>

          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
            :class="conditionForm.condition === 'parts_only' ? 'border-gray-500 bg-gray-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="parts_only" class="mr-3" />
            <div>
              <p class="font-medium text-gray-900">🔧 Parts Only</p>
              <p class="text-sm text-gray-500">Can only be used for spare parts</p>
            </div>
          </label>

          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
            :class="conditionForm.condition === 'other' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
            <input v-model="conditionForm.condition" type="radio" value="other" class="mr-3" />
            <div class="flex-1">
              <p class="font-medium text-gray-900">✏️ Other / Custom</p>
              <p class="text-sm text-gray-500">Describe condition manually</p>
            </div>
          </label>

          <div v-if="conditionForm.condition === 'other'" class="ml-8 mt-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Describe Condition *</label>
            <textarea v-model="conditionForm.customCondition" rows="3" class="input w-full"
              placeholder="e.g., Screen cracked, battery swollen, etc." required></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
          <button type="button" @click="showConditionModal = false" class="btn-secondary">Cancel</button>
          <button @click="confirmReceived" :disabled="!canConfirmReceived || saving" class="btn-primary">
            {{ saving ? 'Processing...' : 'Confirm Received' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Evidence Viewer Modal -->
    <div v-if="showEvidenceModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">Evidence / Bukti — {{ evidenceRMA?.rma_code }}</h3>
          <button @click="showEvidenceModal = false" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <div v-if="evidenceLoading" class="p-8 text-center text-gray-500">Loading evidence...</div>

        <div v-else-if="evidenceItems.length" class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="(file, index) in evidenceItems" :key="index" class="border rounded-lg overflow-hidden">
            <div v-if="file.mime_type && file.mime_type.startsWith('image/')" class="aspect-square bg-gray-100">
              <img :src="file.url" :alt="file.original_name"
                class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition"
                @click="openFileInNewTab(file.url)" />
            </div>
            <div v-else
              class="aspect-square bg-red-50 flex items-center justify-center cursor-pointer hover:bg-red-100 transition"
              @click="openFileInNewTab(file.url)">
              <div class="text-center">
                <div class="text-4xl mb-2">📄</div>
                <p class="text-sm text-red-700 font-medium">PDF</p>
              </div>
            </div>
            <div class="p-2">
              <p class="text-xs text-gray-700 truncate font-medium">{{ file.original_name }}</p>
              <p class="text-xs text-gray-400">{{ formatFileSize(file.size) }}</p>
            </div>
          </div>
        </div>

        <div v-else class="p-8 text-center text-gray-500">Tidak ada evidence</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';

const rmas = ref({ data: [] });
const sales = ref([]);
const saleItems = ref([]);
const selectedProducts = ref([]); // Array of { product_id, product_title, serial_number, quantity, warranty_sn, available_qty, existing_rma_qty, eligibility }
const loading = ref(true);
const showModal = ref(false);
const showConditionModal = ref(false);
const showEvidenceModal = ref(false);
const selectedRMA = ref(null);
const saving = ref(false);
const error = ref('');
const dragOver = ref(false);
const evidenceFiles = ref([]);
const evidenceRMA = ref(null);
const evidenceItems = ref([]);
const evidenceLoading = ref(false);

const filters = ref({ search: '', status: '', reason: '' });

const conditionForm = ref({
  condition: '',
  customCondition: ''
});

const form = ref({
  sale_id: '',
  customer_name: '',
  customer_contact: '',
  reason: '',
  issue_date: new Date().toISOString().split('T')[0],
  notes: '',
});

// Check if all products are selected
const allSelected = computed(() => {
  return saleItems.value.length > 0 && selectedProducts.value.length === saleItems.value.length;
});

// Check if a product is selected
const isProductSelected = (productId) => {
  return selectedProducts.value.some(sp => sp.product_id === productId);
};

// Get eligibility for a product
const getProductEligibility = (productId) => {
  const sp = selectedProducts.value.find(s => s.product_id === productId);
  return sp?.eligibility || null;
};

// Get SN match status for a selected product
const getSnStatus = (sp) => {
  if (!sp.warranty_sn) return 'no_sn';
  if (!sp.serial_number) return 'pending';
  if (sp.serial_number.trim().toLowerCase() === sp.warranty_sn.trim().toLowerCase()) return 'match';
  return 'mismatch';
};

// Get product card CSS class based on validation state
const getProductCardClass = (sp) => {
  if (!sp.eligibility?.checked) return 'border-gray-200 bg-gray-50';
  if (!sp.eligibility?.valid) return 'border-red-200 bg-red-50';
  if (sp.warranty_sn && getSnStatus(sp) === 'mismatch') return 'border-orange-200 bg-orange-50';
  if (sp.warranty_sn && getSnStatus(sp) === 'match') return 'border-green-200 bg-green-50';
  return 'border-blue-200 bg-blue-50';
};

// Computed: can submit form
const canSubmit = computed(() => {
  if (!selectedProducts.value.length) return false;

  // Check each selected product
  for (const sp of selectedProducts.value) {
    if (!sp.eligibility?.valid) return false;
    // SN must match if warranty has SN
    if (sp.warranty_sn && getSnStatus(sp) !== 'match') return false;
    // Qty must be valid
    if (sp.available_qty !== null && sp.quantity > sp.available_qty) return false;
    if (sp.available_qty !== null && sp.available_qty <= 0) return false;
    if (!sp.quantity || sp.quantity < 1) return false;
  }
  return true;
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID');
};

const formatReason = (reason) => {
  return reason.replace(/_/g, ' ');
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    approved: 'badge-info',
    rejected: 'badge-danger',
    received: 'badge-primary',
    processed: 'badge-success',
    completed: 'badge-success',
  };
  return badges[status] || 'badge';
};

const getConditionBadge = (condition) => {
  const badges = {
    working: 'badge-success',
    damaged: 'badge-warning',
    broken: 'badge-danger',
    parts_only: 'badge',
    other: 'badge-info',
  };
  return badges[condition] || 'badge';
};

const canConfirmReceived = computed(() => {
  if (!conditionForm.value.condition) return false;
  if (conditionForm.value.condition === 'other') {
    return conditionForm.value.customCondition && conditionForm.value.customCondition.trim().length > 0;
  }
  return true;
});

const loadRMAs = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/rmas', { params: { page, ...filters.value } });
    rmas.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadSalesWithWarranty = async () => {
  try {
    const response = await api.get('/rmas/sales-with-warranty');
    sales.value = response.data || [];
  } catch (err) {
    console.error('Failed to load sales:', err);
  }
};

const onSaleSelect = () => {
  selectedProducts.value = [];

  if (form.value.sale_id) {
    const sale = sales.value.find(s => s.id === parseInt(form.value.sale_id));
    if (sale) {
      saleItems.value = sale.items || [];
      form.value.customer_name = sale.customer_name || '';
      form.value.customer_contact = sale.customer_phone || sale.customer_email || '';
    }
  } else {
    saleItems.value = [];
  }
};

// Toggle product selection
const toggleProduct = async (item) => {
  const idx = selectedProducts.value.findIndex(sp => sp.product_id === item.product_id);

  if (idx >= 0) {
    // Deselect
    selectedProducts.value.splice(idx, 1);
  } else {
    // Select — add new entry and check eligibility
    const newProduct = {
      product_id: item.product_id,
      product_title: item.product?.title || `Product #${item.product_id}`,
      serial_number: '',
      quantity: 1,
      warranty_sn: null,
      available_qty: item.quantity,
      existing_rma_qty: 0,
      sale_item_qty: item.quantity,
      eligibility: { checked: false, valid: false, reason: 'Checking...' },
    };
    selectedProducts.value.push(newProduct);

    // Check eligibility for this product
    await checkProductEligibility(newProduct);
  }
};

// Toggle select all
const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedProducts.value = [];
  } else {
    // Select all not-yet-selected
    for (const item of saleItems.value) {
      if (!isProductSelected(item.product_id)) {
        toggleProduct(item);
      }
    }
  }
};

// Check eligibility for a specific product
const checkProductEligibility = async (sp) => {
  if (!form.value.sale_id || !sp.product_id) return;

  try {
    const response = await api.post('/rmas/check-eligibility', {
      sale_id: form.value.sale_id,
      product_id: sp.product_id,
    });

    sp.eligibility = { checked: true, ...response.data };
    sp.warranty_sn = response.data.warranty_serial_number || null;

    if (response.data.available_quantity !== undefined) {
      sp.available_qty = response.data.available_quantity;
      sp.existing_rma_qty = response.data.existing_rma_quantity || 0;
      sp.sale_item_qty = response.data.sale_item_quantity || sp.sale_item_qty;
      sp.quantity = Math.min(1, response.data.available_quantity);
    }
  } catch (err) {
    sp.eligibility = { checked: true, valid: false, reason: 'Failed to check eligibility' };
  }
};

const resetForm = () => {
  form.value = {
    sale_id: '',
    customer_name: '',
    customer_contact: '',
    reason: '',
    issue_date: new Date().toISOString().split('T')[0],
    notes: '',
  };
  saleItems.value = [];
  selectedProducts.value = [];
  evidenceFiles.value = [];
  error.value = '';
};

// File handling
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  addFiles(files);
  event.target.value = '';
};

const handleFileDrop = (event) => {
  dragOver.value = false;
  const files = Array.from(event.dataTransfer.files);
  addFiles(files);
};

const addFiles = (files) => {
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
  const maxSize = 5 * 1024 * 1024;

  for (const file of files) {
    if (evidenceFiles.value.length >= 5) {
      alert('Maksimal 5 file');
      break;
    }
    if (!allowedTypes.includes(file.type)) {
      alert(`File "${file.name}" tidak didukung. Gunakan JPG, PNG, WebP, atau PDF.`);
      continue;
    }
    if (file.size > maxSize) {
      alert(`File "${file.name}" terlalu besar (max 5MB).`);
      continue;
    }

    const fileObj = {
      file: file,
      name: file.name,
      size: file.size,
      type: file.type,
      preview: null,
    };

    if (file.type.startsWith('image/')) {
      fileObj.preview = URL.createObjectURL(file);
    }

    evidenceFiles.value.push(fileObj);
  }
};

const removeFile = (index) => {
  const file = evidenceFiles.value[index];
  if (file.preview) {
    URL.revokeObjectURL(file.preview);
  }
  evidenceFiles.value.splice(index, 1);
};

const saveRMA = async () => {
  saving.value = true;
  error.value = '';

  try {
    // Build items array
    const items = selectedProducts.value.map(sp => ({
      product_id: sp.product_id,
      product_title: sp.product_title,
      serial_number: sp.serial_number || '',
      quantity: sp.quantity,
    }));

    // Use FormData for file uploads
    const formData = new FormData();
    formData.append('sale_id', form.value.sale_id);
    formData.append('customer_name', form.value.customer_name);
    formData.append('customer_contact', form.value.customer_contact || '');
    formData.append('reason', form.value.reason);
    formData.append('issue_date', form.value.issue_date);
    formData.append('notes', form.value.notes || '');
    formData.append('items', JSON.stringify(items));

    // Append evidence files
    for (const item of evidenceFiles.value) {
      formData.append('evidence[]', item.file);
    }

    const response = await api.post('/rmas/batch', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    showModal.value = false;
    loadRMAs();

    // Show success with possible partial errors
    if (response.data.errors?.length) {
      alert(response.data.message);
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save RMA';
  } finally {
    saving.value = false;
  }
};

const approveRMA = async (rma) => {
  if (!confirm('Approve this RMA?')) return;
  try {
    await api.put(`/rmas/${rma.id}`, { status: 'approved' });
    loadRMAs();
  } catch (err) {
    alert('Failed to approve RMA');
  }
};

const markReceived = (rma) => {
  selectedRMA.value = rma;
  conditionForm.value.condition = '';
  conditionForm.value.customCondition = '';
  showConditionModal.value = true;
};

const confirmReceived = async () => {
  saving.value = true;
  try {
    const finalCondition = conditionForm.value.condition === 'other'
      ? conditionForm.value.customCondition
      : conditionForm.value.condition;

    await api.post(`/rmas/${selectedRMA.value.id}/mark-received`, {
      condition: finalCondition
    });
    showConditionModal.value = false;
    loadRMAs();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to mark as received');
  } finally {
    saving.value = false;
  }
};

const viewEvidence = async (rma) => {
  evidenceRMA.value = rma;
  evidenceItems.value = [];
  evidenceLoading.value = true;
  showEvidenceModal.value = true;

  try {
    const response = await api.get(`/rmas/${rma.id}/evidence`);
    evidenceItems.value = response.data;
  } catch (err) {
    console.error('Failed to load evidence:', err);
  } finally {
    evidenceLoading.value = false;
  }
};

const openFileInNewTab = (url) => {
  window.open(url, '_blank');
};

const deleteRMA = async (id) => {
  if (!confirm('Delete this RMA?')) return;
  try {
    await api.delete(`/rmas/${id}`);
    loadRMAs();
  } catch (err) {
    alert('Failed to delete');
  }
};

onMounted(() => {
  loadRMAs();
  loadSalesWithWarranty();
});
</script>
