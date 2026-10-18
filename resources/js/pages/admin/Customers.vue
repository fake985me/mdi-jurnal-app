<template>
  <div class="space-y-6" :class="{ 'form-page': formPage }">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-linear-to-r from-violet-600 to-purple-600 bg-clip-text text-transparent">
          {{ $t('pageTitles.Customers') }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.manageYourCustomerDatabase') }}</p>
      </div>
      <button @click="openCreateModal()" class="btn-primary flex items-center space-x-2">
        <span>{{ $t('ui.addCustomer') }}</span>
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" v-if="summary">
      <div class="card p-4 bg-linear-to-br from-violet-50 to-violet-100 border border-violet-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-violet-600 font-medium">{{ $t('ui.totalCustomers') }}</p>
            <p class="text-2xl font-bold text-violet-800">{{ summary.total_customers }}</p>
          </div>
          <div class="w-10 h-10 bg-violet-200 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-violet-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
        </div>
      </div>
      <div class="card p-4 bg-linear-to-br from-green-50 to-green-100 border border-green-200">
        <p class="text-sm text-green-600 font-medium">{{ $t('labels.active') }}</p>
        <p class="text-2xl font-bold text-green-800">{{ summary.active_customers }}</p>
      </div>
      <div class="card p-4 bg-linear-to-br from-blue-50 to-blue-100 border border-blue-200">
        <p class="text-sm text-blue-600 font-medium">{{ $t('labels.company') }}</p>
        <p class="text-2xl font-bold text-blue-800">{{ summary.companies }}</p>
      </div>
      <div class="card p-4 bg-linear-to-br from-amber-50 to-amber-100 border border-amber-200">
        <p class="text-sm text-amber-600 font-medium">{{ $t('labels.individual') }}</p>
        <p class="text-2xl font-bold text-amber-800">{{ summary.individuals }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="debouncedLoad" type="text"
          :placeholder="$t('ui.searchNameCompanyEmailPhone')" class="input" />
        <select v-model="filters.type" @change="loadCustomers" class="input">
          <option value="">{{ $t('ui.allTypes') }}</option>
          <option value="individual">{{ $t('labels.individual') }}</option>
          <option value="company">{{ $t('labels.company') }}</option>
        </select>
        <select v-model="filters.is_active" @change="loadCustomers" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="1">{{ $t('labels.active') }}</option>
          <option value="0">{{ $t('labels.inactive') }}</option>
        </select>
      </div>
    </div>

    <!-- Customers Table -->

      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.customerList') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-600">{{ $t('ui.loadingCustomers') }}</div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.code') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.name') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('labels.company') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('labels.contact') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.city') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.type') }}</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('labels.sale') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="c in customers.data" :key="c.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ c.customer_code }}</td>
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ c.name }}</div>
              <div v-if="c.email" class="text-xs text-gray-500">{{ c.email }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ c.company || '—' }}</td>
            <td class="px-4 py-3">
              <div v-if="c.phone" class="text-sm text-gray-700">{{ c.phone }}</div>
              <div v-if="c.phone_alt" class="text-xs text-gray-400">{{ c.phone_alt }}</div>
              <span v-if="!c.phone && !c.phone_alt" class="text-gray-400">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ c.city || '—' }}</td>
            <td class="px-4 py-3">
              <span :class="c.type === 'company' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                class="text-xs font-medium px-2 py-0.5 rounded-full capitalize">{{ $label(c.type) }}</span>
            </td>
            <td class="px-4 py-3 text-center text-sm font-semibold text-gray-700">{{ c.sales_count || 0 }}</td>
            <td class="px-4 py-3">
              <span :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs font-medium px-2 py-0.5 rounded-full">{{ c.is_active ? $t('labels.active') : $t('labels.inactive') }}</span>
            </td>
            <td class="px-4 py-3 text-right text-sm space-x-1">
              <button @click="viewCustomer(c)" class="text-indigo-600 hover:text-indigo-900 font-medium">{{ $t('projectInvestment.actions.view') }}</button>
              <button @click="editCustomer(c)" class="text-blue-600 hover:text-blue-900 font-medium">{{ $t('common.edit') }}</button>
              <button @click="deleteCustomer(c)" class="text-red-600 hover:text-red-900 font-medium">{{ $t('common.delete') }}</button>
            </td>
          </tr>
          <tr v-if="!customers.data?.length && !loading">
            <td colspan="9" class="px-4 py-12 text-center">
              <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <p class="text-gray-500 text-lg font-medium">{{ $t('ui.noCustomersFound') }}</p>
              <p class="text-gray-400 text-sm mt-1">{{ $t('ui.addYourFirstCustomerToGetStarted') }}</p>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="customers.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ customers.from }} {{ $t('ui.to') }} {{ customers.to }} {{ $t('sales.of') }} {{ customers.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadCustomers(customers.current_page - 1)" :disabled="!customers.prev_page_url"
            class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadCustomers(customers.current_page + 1)" :disabled="!customers.next_page_url"
            class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full overflow-y-auto shadow-md">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ detail?.name }}</h3>
            <p class="text-sm text-gray-500">{{ detail?.customer_code }}</p>
          </div>
          <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <div v-if="detailLoading" class="p-8 text-center text-gray-600">{{ $t('common.loading') }}</div>
        <div v-else-if="detail" class="space-y-4">
          <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
            <div><span class="text-xs text-gray-500">{{ $t('labels.company') }}</span><p class="text-sm font-medium">{{ detail.company || '—' }}</p></div>
            <div><span class="text-xs text-gray-500">{{ $t('ui.type') }}</span><p class="text-sm font-medium capitalize">{{ $label(detail.type) }}</p></div>
            <div><span class="text-xs text-gray-500">{{ $t('labels.email') }}</span><p class="text-sm font-medium">{{ detail.email || '—' }}</p></div>
            <div><span class="text-xs text-gray-500">{{ $t('labels.phone') }}</span><p class="text-sm font-medium">{{ detail.phone || '—' }}</p></div>
            <div><span class="text-xs text-gray-500">{{ $t('ui.altPhone') }}</span><p class="text-sm font-medium">{{ detail.phone_alt || '—' }}</p></div>
            <div><span class="text-xs text-gray-500">NPWP</span><p class="text-sm font-medium">{{ detail.npwp || '—' }}</p></div>
            <div class="col-span-2"><span class="text-xs text-gray-500">{{ $t('labels.address') }}</span><p class="text-sm font-medium">{{ [detail.address, detail.city, detail.province, detail.postal_code].filter(Boolean).join(', ') || '—' }}</p></div>
            <div><span class="text-xs text-gray-500">{{ $t('ui.totalSales') }}</span><p class="text-sm font-bold text-emerald-700">{{ formatCurrency(detail.total_sales) }}</p></div>
            <div><span class="text-xs text-gray-500">{{ $t('ui.salesCount') }}</span><p class="text-sm font-bold">{{ detail.sales_count || 0 }}</p></div>
          </div>

          <div v-if="detail.notes" class="p-3 bg-amber-50 rounded-lg">
            <p class="text-xs text-amber-600 font-medium mb-1">{{ $t('projectInvestment.progress.notes') }}</p>
            <p class="text-sm text-gray-700">{{ detail.notes }}</p>
          </div>

          <!-- Recent Sales -->
          <div v-if="detail.recent_sales?.length">
            <h4 class="font-semibold text-gray-900 mb-2">{{ $t('ui.recentSales') }}</h4>
            <div class="space-y-2">
              <div v-for="sale in detail.recent_sales" :key="sale.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ sale.invoice_number }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(sale.sale_date) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-gray-900">{{ formatCurrency(sale.grand_total || sale.total_amount) }}</p>
                  <span :class="sale.status === 'completed' ? 'text-green-600' : sale.status === 'cancelled' ? 'text-red-600' : 'text-amber-600'"
                    class="text-xs font-medium capitalize">{{ $label(sale.status) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="tab-form" :class="formPage ? 'mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 animate-fade-in'"
          @click.self="closeModalform">
      <div class="bg-white rounded-xl p-6 w-full overflow-y-auto shadow-md" :class="formPage">
        <h3 class="text-xl font-bold mb-4 text-gray-900">{{ editingId ? $t('ui.editCustomer') : $t('ui.addCustomer') }}</h3>
        <button type="button" @click="closeModalform" class="text-gray-400 hover:text-gray-600 text-3xl leading-none transition-colors">&times;</button>

        <form @submit.prevent="saveCustomer" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.name2') }}</label>
              <input v-model="form.name" type="text" required class="input" :placeholder="$t('ui.customerName2')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.company') }}</label>
              <input v-model="form.company" type="text" class="input" :placeholder="$t('ui.companyName')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.email') }}</label>
              <input v-model="form.email" type="email" class="input" placeholder="email@example.com" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.phone') }}</label>
              <input v-model="form.phone" type="text" class="input" placeholder="08123456789" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.altPhone') }}</label>
              <input v-model="form.phone_alt" type="text" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.type') }}</label>
              <select v-model="form.type" class="input">
                <option value="individual">{{ $t('labels.individual') }}</option>
                <option value="company">{{ $t('labels.company') }}</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.address') }}</label>
              <textarea v-model="form.address" rows="2" class="input" :placeholder="$t('ui.streetAddress')"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.city') }}</label>
              <input v-model="form.city" class="input" placeholder="Jakarta" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.province') }}</label>
              <input v-model="form.province" class="input" placeholder="DKI Jakarta" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.postalCode') }}</label>
              <input v-model="form.postal_code" class="input" placeholder="12345" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
              <input v-model="form.npwp" class="input" :placeholder="$t('ui.taxIdNumber')" />
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-blue-600 rounded" />
              <span class="text-sm text-gray-700">{{ $t('labels.active') }}</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="input" :placeholder="$t('ui.additionalNotes')"></textarea>
          </div>

          <div v-if="formError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm border border-red-200">{{ $ui(formError) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="closeModalform" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? $t('sales.saving') : (editingId ? $t('ui.saveChanges') : $t('ui.createCustomer')) }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, onMounted, onBeforeUnmount, onActivated, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';

const route = useRoute();
const formPage = computed(() => route.query.create === '1' || Boolean(route.query.edit));

const customers = ref({ data: [] });
const summary = ref(null);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const showDetailModal = ref(false);
const editingId = ref(null);
const formError = ref('');
const detail = ref(null);
const detailLoading = ref(false);

const filters = ref({ search: '', type: '', is_active: '' });

const form = ref({
  name: '', company: '', email: '', phone: '', phone_alt: '',
  address: '', city: '', province: '', postal_code: '',
  npwp: '', type: 'individual', is_active: true, notes: '',
});

const resetForm = () => {
  form.value = {
    name: '', company: '', email: '', phone: '', phone_alt: '',
    address: '', city: '', province: '', postal_code: '',
    npwp: '', type: 'individual', is_active: true, notes: '',
  };
  formError.value = '';
};

let debounceTimer = null;
const debouncedLoad = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadCustomers(), 300);
};

const formatCurrency = (v) => 'Rp ' + Number(v || 0).toLocaleString(uiLocale());
const formatDate = (d) => d ? new Date(d).toLocaleDateString(uiLocale()) : '—';

const loadCustomers = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/customers', { params: { page, per_page: 15, ...filters.value } });
    customers.value = res.data;
  } catch (err) { console.error(err); }
  finally { loading.value = false; }
};

const loadSummary = async () => {
  try {
    const res = await api.get('/customers/summary');
    summary.value = res.data;
  } catch (err) { console.error(err); }
};

const openCreateModal = (cId = null ) => {
  const target = cId
      ? `/dashboard/customers?edit=${encodeURIComponent(cId)}`
      : '/dashboard/customers?create=1';
    window.open(target, '_blank');
  };

const closeModalform = () => {
    if (!formPage.value) {
        showModal.value = false;
        editingId.value = null;
        return;
    }
    if (window.opener) {
        window.close();
        window.setTimeout(() => {
            if (!window.closed) window.location.replace('/dashboard/customers');
        }, 150);
        return;
    }
    window.location.replace('/dashboard/customers');
};

const onCustomerSaved = (event) => {
    if (event.origin === window.location.origin && event.data?.type === 'admin-data-saved' && event.data.resource === 'customers') {
        loadCustomers();
        loadSummary();
    }
};

const editCustomer = (c) => {
  if (!formPage.value) {
    openCreateModal(c.id);
    return;
  }
  editingId.value = c.id;
  form.value = { ...c };
  formError.value = '';
  showModal.value = true;
};

// const editCustomer = (c) => {
//   editingId.value = c.id;
//   form.value = { ...c };
//   formError.value = '';
//   showModal.value = true;
// };

const saveCustomer = async () => {
  saving.value = true;
  formError.value = '';
  try {
    if (editingId.value) {
      await api.put(`/customers/${editingId.value}`, form.value);
    } else {
      await api.post('/customers', form.value);
    }
    if (formPage.value) {
      if (window.opener) {
          window.opener.postMessage({ type: 'admin-data-saved', resource: 'customers' }, window.location.origin);
          window.close();
          window.setTimeout(() => {
              if (!window.closed) window.location.replace('/dashboard/customers');
          }, 150);
          return;
      }
      window.location.replace('/dashboard/customers');
      return;
    }

    showModal.value = false;
    editingId.value = null;
    resetForm();
    loadCustomers();
    loadSummary();
  } catch (err) {
    formError.value = err.response?.data?.message || uiText("Gagal menyimpan pelanggan");
  } finally { saving.value = false; }
};


// const saveCustomer = async () => {
//   saving.value = true;
//   formError.value = '';
//   try {
//     if (editingId.value) {
//       await api.put(`/customers/${editingId.value}`, form.value);
//     } else {
//       await api.post('/customers', form.value);
//     }
//     showModal.value = false;
//     loadCustomers();
//     loadSummary();
//   } catch (err) {
//     formError.value = err.response?.data?.message || 'Failed to save customer';
//   } finally { saving.value = false; }
// };

const deleteCustomer = async (c) => {
  if (!confirm(uiText('ui.deleteCustomerNamed', { p0: c.name }))) return;
  try {
    await api.delete(`/customers/${c.id}`);
    loadCustomers();
    loadSummary();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus pelanggan"));
  }
};

const viewCustomer = async (c) => {
  detailLoading.value = true;
  showDetailModal.value = true;
  try {
    const res = await api.get(`/customers/${c.id}`);
    detail.value = res.data;
  } catch (err) { console.error(err); }
  finally { detailLoading.value = false; }
};

onMounted(async () => {
  window.addEventListener('message', onCustomerSaved);
  if (!formPage.value) {
    loadCustomers();
    loadSummary();
    return;
  }
  const editId = Array.isArray(route.query.edit) ? route.query.edit[0] : route.query.edit;

  const openEditFromRoute = async () => {
    if (editId) {
      try {
        const response = await api.get(`/customers/${encodeURIComponent(editId)}`);
        editCustomer(response.data);
      } catch (error) {
          console.error('Gagal memuat pelanggan untuk diubah:', error);
          alert(error.response?.data?.message || uiText("Gagal memuat pelanggan untuk diubah"));
          closeModalform();
      }
    } else {
      editingId.value = null;
      resetForm();
      showModal.value = true;
    }
  }
  await openEditFromRoute();
}
);

onBeforeUnmount(() => {
  window.removeEventListener('message', onCustomerSaved);
  clearTimeout(debounceTimer);
});

onActivated(() => {
  if (!formPage.value) {
    loadCustomers();
    loadSummary();
  }
}
);
// onMounted(() => { loadCustomers(); loadSummary(); });
</script>

<style scoped>
.form-page > :not(.tab-form) {
  display: none !important;
}
</style>
