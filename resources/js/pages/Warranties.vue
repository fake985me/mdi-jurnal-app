<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Warranty Management</h2>
        <p class="text-sm text-gray-600 mt-1">Track product warranties and claims</p>
      </div>
      <router-link :to="{ name: 'WarrantyCreate' }"
        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + Add Warranty
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input
          v-model="filters.search"
          @input="loadWarranties"
          type="text"
          placeholder="Search by code, serial number, or invoice..."
          class="input"
        />
        <select v-model="filters.status" @change="loadWarranties" class="input">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="claimed">Claimed</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <div class="inline-flex items-center gap-2 text-gray-500">
        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Loading warranties...
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!warranties.data?.length" class="card p-12 text-center">
      <div class="text-gray-400 text-5xl mb-4">🛡️</div>
      <h3 class="text-lg font-semibold text-gray-700 mb-2">No Warranties Found</h3>
      <p class="text-sm text-gray-500">Create a new warranty to get started.</p>
    </div>

    <!-- Grouped Warranties by Sale -->
    <div v-else class="space-y-4">
      <div v-for="(group, saleId) in groupedWarranties" :key="saleId" class="card overflow-hidden">
        <!-- Sale Group Header -->
        <div
          @click="toggleGroup(saleId)"
          class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-gray-50 to-white cursor-pointer hover:from-purple-50 hover:to-white transition-all duration-200 border-b border-gray-100"
        >
          <div class="flex items-center gap-4">
            <!-- Expand/Collapse Icon -->
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center transition-transform duration-200"
              :class="{ 'rotate-90': expandedGroups[saleId] }">
              <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
            <!-- Sale Info -->
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-900">{{ group.invoiceNumber }}</span>
                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">
                  {{ group.items.length }} produk
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">
                Sale ID: #{{ saleId }}
                <span v-if="group.saleDate"> · {{ formatDate(group.saleDate) }}</span>
              </p>
            </div>
          </div>

          <!-- Status Summary -->
          <div class="flex items-center gap-2">
            <span v-if="group.activeCount > 0" class="px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">
              {{ group.activeCount }} active
            </span>
            <span v-if="group.expiredCount > 0" class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
              {{ group.expiredCount }} expired
            </span>
            <span v-if="group.claimedCount > 0" class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">
              {{ group.claimedCount }} claimed
            </span>
          </div>
        </div>

        <!-- Warranty Items Table (collapsible) -->
        <transition name="slide">
          <div v-show="expandedGroups[saleId]">
            <table class="min-w-full">
              <thead class="table-header">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial Number</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="warranty in group.items" :key="warranty.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-gray-900 font-mono">{{ warranty.warranty_code }}</td>
                  <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ warranty.serial_number || '-' }}</td>
                  <td class="px-6 py-4 text-sm text-gray-900">{{ warranty.product?.title || warranty.product?.name || '-' }}</td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(warranty.start_date) }}</td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(warranty.end_date) }}</td>
                  <td class="px-6 py-4">
                    <span :class="getStatusBadge(warranty.status)">{{ warranty.status }}</span>
                  </td>
                  <td class="px-6 py-4 text-right text-sm space-x-2">
                    <router-link :to="{ name: 'WarrantyEdit', params: { id: warranty.id } }" class="text-purple-600 hover:text-purple-900 font-medium">Edit</router-link>
                    <button @click="deleteWarranty(warranty.id)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </transition>
      </div>

      <!-- Pagination -->
      <div v-if="warranties.data?.length" class="card px-6 py-4 flex justify-between items-center">
        <p class="text-sm text-gray-700">Showing {{ warranties.from }} to {{ warranties.to }} of {{ warranties.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadWarranties(warranties.current_page - 1)" :disabled="!warranties.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadWarranties(warranties.current_page + 1)" :disabled="!warranties.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const warranties = ref({ data: [] });
const loading = ref(true);
const expandedGroups = ref({});

const filters = ref({ search: '', status: '' });

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
};

const getStatusBadge = (status) => {
  const badges = {
    active: 'badge-success',
    expired: 'badge-danger',
    claimed: 'badge-warning',
  };
  return badges[status] || 'badge';
};

// Group warranties by sale_id
const groupedWarranties = computed(() => {
  if (!warranties.value.data?.length) return {};

  const groups = {};
  for (const warranty of warranties.value.data) {
    const saleId = warranty.sale_id || 'no-sale';

    if (!groups[saleId]) {
      groups[saleId] = {
        invoiceNumber: warranty.sale?.invoice_number || 'No Invoice',
        saleDate: warranty.sale?.created_at || null,
        items: [],
        activeCount: 0,
        expiredCount: 0,
        claimedCount: 0,
      };
    }

    groups[saleId].items.push(warranty);

    if (warranty.status === 'active') groups[saleId].activeCount++;
    else if (warranty.status === 'expired') groups[saleId].expiredCount++;
    else if (warranty.status === 'claimed') groups[saleId].claimedCount++;
  }

  return groups;
});

const toggleGroup = (saleId) => {
  expandedGroups.value[saleId] = !expandedGroups.value[saleId];
};

const loadWarranties = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/warranties', { params: { page, per_page: 100, ...filters.value } });
    warranties.value = response.data;

    // Auto-expand all groups on load
    if (warranties.value.data?.length) {
      for (const warranty of warranties.value.data) {
        const saleId = warranty.sale_id || 'no-sale';
        if (expandedGroups.value[saleId] === undefined) {
          expandedGroups.value[saleId] = true;
        }
      }
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const deleteWarranty = async (id) => {
  if (!confirm('Delete this warranty?')) return;
  try {
    await api.delete(`/warranties/${id}`);
    loadWarranties();
  } catch (err) {
    alert('Failed to delete');
  }
};

onMounted(() => {
  loadWarranties();
});
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
}

.slide-enter-to,
.slide-leave-from {
  max-height: 1000px;
  opacity: 1;
}

.rotate-90 {
  transform: rotate(90deg);
}
</style>
