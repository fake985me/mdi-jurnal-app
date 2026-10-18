<template>
  <div class="mx-auto max-w-2xl space-y-5">
    <router-link :to="{ name: 'WarrantiesCopy' }" class="text-indigo-600">{{ $t('ui.kembaliKeWarrantyStatus') }}</router-link>
    <h2 class="text-2xl font-bold">{{ $t('labels.warranty_claim') }}</h2>
    <p v-if="loading">{{ $t('ui.loadingWarrantyData') }}</p>
    <form v-else-if="warranty" @submit.prevent="save" class="card p-6 space-y-4">
      <div class="rounded-lg bg-slate-50 p-4 space-y-1">
        <p>{{ warranty.product?.title }} · {{ warranty.serial_number }}</p>
        <p class="text-sm text-slate-500">{{ warranty.sale?.invoice_number }} · {{ warranty.sale?.customer_name }}</p>
      </div>
      <div><label class="block mb-1 text-sm font-medium">{{ $t('ui.claimDate') }}</label><input v-model="form.issue_date" type="date" required class="input" /></div>
      <div><label class="block mb-1 text-sm font-medium">{{ $t('ui.customerContact') }}</label><input v-model="form.customer_contact" class="input" /></div>
      <div><label class="block mb-1 text-sm font-medium">{{ $t('ui.issueDamage') }}</label><textarea v-model="form.notes" required maxlength="5000" rows="4" class="input"></textarea></div>
      <p class="text-sm text-slate-500">{{ $t('ui.setelahDisimpanKlaimMasukKeRmaManagementUntukPengecekanBarang') }}</p>
      <p v-if="error" class="text-sm text-red-700">{{ $ui(error) }}</p>
      <button :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('ui.saveClaim') }}</button>
    </form>
    <p v-else-if="error" class="text-red-700">{{ $ui(error) }}</p>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
const route = useRoute();
const router = useRouter();
const warranty = ref(null);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const now = new Date();
const form = ref({ issue_date: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`, customer_contact: '', notes: '' });
onMounted(async () => {
  try {
    warranty.value = (await api.get(`/warranties/${route.params.id}`)).data;
    form.value.customer_contact = warranty.value.sale?.customer_phone || '';
  } catch (err) { error.value = err.response?.data?.message || uiText("Gagal memuat garansi."); }
  finally { loading.value = false; }
});
const save = async () => {
  saving.value = true;
  error.value = '';
  try {
    await api.post(`/warranties/${route.params.id}/claim`, form.value);
    await router.push({ name: 'RMAs' });
  } catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || uiText("Gagal menyimpan klaim."); }
  finally { saving.value = false; }
};
</script>
