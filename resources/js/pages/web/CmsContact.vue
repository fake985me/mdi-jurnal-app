<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">{{ $t('pageTitles.CmsContact') }}</h2>
      <button
        @click="showModal = true; editingItem = null"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
      >
        {{ $t('ui.addContactInfo') }}
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('ui.loadingContactInfo') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.order') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.type') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.value') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in contacts" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.order }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                {{ $label(item.type) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $ui(item.label) }}</td>
            <td class="px-6 py-4 text-sm">{{ item.value }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="[
                'px-2 py-1 text-xs rounded-full',
                item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ item.is_active ? $t('labels.active') : $t('labels.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
              <button @click="editItem(item)" class="text-indigo-600 hover:text-indigo-900">{{ $t('common.edit') }}</button>
              <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ editingItem ? $t('ui.editContactInformation') : $t('ui.addNewContactInformation') }}</h3>
        
        <form @submit.prevent="saveItem" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.type2') }}</label>
            <select v-model="form.type" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
              <option value="description">{{ $t('ui.descriptionCompanyInfo') }}</option>
              <option value="address">{{ $t('labels.address') }}</option>
              <option value="phone">{{ $t('labels.phone') }}</option>
              <option value="email">{{ $t('labels.email') }}</option>
              <option value="sales">{{ $t('ui.salesMarketing') }}</option>
              <option value="social">{{ $t('labels.social') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
            <input v-model="form.label" :placeholder="$t('ui.eGOfficeMailOurAddress')" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
            <small class="text-gray-500">{{ $t('ui.optionalLeaveEmptyForItemsThatDonTNeedALabel') }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.value3') }}</label>
            <textarea
              v-model="form.value"
              required
              rows="3"
              :placeholder="getPlaceholder(form.type)"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
            ></textarea>
            <small class="text-gray-500">{{ getHint(form.type) }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.icon') }}</label>
            <input v-model="form.icon" :placeholder="$t('ui.eGEnvelopePhoneMapMarker')" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.order') }}</label>
            <input v-model.number="form.order" type="number" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500" />
          </div>

          <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="mr-2" />
              <span class="text-sm font-medium text-gray-700">{{ $t('labels.active') }}</span>
            </label>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3">
            <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? $t('sales.saving') : $t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import api from '../../services/api';

const contacts = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingItem = ref(null);
const saving = ref(false);
const error = ref('');

const form = ref({
  type: 'description',
  label: '',
  value: '',
  icon: '',
  order: 0,
  is_active: true,
});

const getPlaceholder = (type) => {
  const placeholders = {
    description: uiText("Masukkan deskripsi perusahaan..."),
    address: 'Gedung Tifa Arum Realty\n3th Floor Suite 301',
    phone: '021 2930-6714',
    email: 'info@mdi-solutions.com',
    sales: 'Hadi : +62 887-0978-7005',
    social: '@company_social'
  }
  return placeholders[type] || uiText("Masukkan nilai...")
}

const getHint = (type) => {
  const hints = {
    description: uiText("Deskripsi perusahaan di bagian atas halaman kontak"),
    address: uiText("Gunakan baris baru untuk alamat beberapa baris"),
    phone: uiText("Nomor telepon dengan atau tanpa label"),
    email: uiText("Alamat email untuk kontak"),
    sales: uiText("Format: Nama : Nomor Telepon"),
    social: uiText("Nama akun atau URL media sosial")
  }
  return hints[type] || ''
}

const loadContacts = async () => {
  loading.value = true;
  try {
    const response = await api.get('/contact-info');
    contacts.value = response.data;
  } catch (err) {
    console.error('Gagal memuat informasi kontak:', err);
  } finally {
    loading.value = false;
  }
};

const editItem = (item) => {
  editingItem.value = item;
  form.value = { ...item };
  showModal.value = true;
};

const saveItem = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    if (editingItem.value) {
      await api.put(`/contact-info/${editingItem.value.id}`, form.value);
    } else {
      await api.post('/contact-info', form.value);
    }
    showModal.value = false;
    loadContacts();
    form.value = { type: 'description', label: '', value: '', icon: '', order: 0, is_active: true };
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal menyimpan informasi kontak");
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (id) => {
  if (!confirm(uiText("Apakah Anda yakin?"))) return;
  try {
    await api.delete(`/contact-info/${id}`);
    loadContacts();
  } catch (err) {
    alert(uiText("Gagal menghapus informasi kontak"));
  }
};

onMounted(() => loadContacts());
</script>
