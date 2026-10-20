<template>
  <div class="space-y-5">
    <router-link :to="{ name: 'ProjectInvestments' }" class="text-indigo-600">{{ $t('ui.daftarProjectInvest') }}</router-link>
    <p v-if="loading" class="card p-8">{{ $t('ui.loadingProjects') }}</p>
    <template v-else-if="project">
      <div class="card p-6 flex flex-wrap items-start justify-between gap-4">
        <div><h2 class="text-2xl font-bold">{{ project.project_name }}</h2><p class="text-sm text-gray-500">{{ project.project_code }} · {{ $label(project.status) }}</p><p class="mt-3">{{ project.client_name }} · {{ project.client_contact || '-' }}</p><p class="text-sm text-gray-500">{{ project.project_location || '-' }} · {{ project.warehouse?.name || $t('ui.warehouseUnavailable') }}</p><p class="mt-2 font-medium">{{ $t('ui.itemAndMaterialValue') }} {{ currency(project.total_value) }}</p></div>
      </div>
      <div v-if="project.legacy_transfers?.length" class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold">{{ $t('projectShipping.legacyReview') }}</p>
        <p v-for="transfer in project.legacy_transfers" :key="transfer.id">{{ transfer.transfer_code }} / {{ transfer.product?.title }} x {{ transfer.quantity }} / {{ $label(transfer.status) }}</p>
      </div>
      <p v-if="success" class="rounded-lg bg-green-50 p-4 text-green-700">{{ $ui(success) }}</p>
      <ProjectManagementTabs :project="project" @refresh="refreshProject">
        <template #items>
      <router-link to="/dashboard/deliveries" class="block mb-3 text-sm text-indigo-600">{{ $t('projectShipping.manualHint') }}</router-link>
      <div class="table-wrapper">
        <div class="card-header flex items-center justify-between gap-3"><span class="font-semibold">{{ $t('ui.projectItems') }}</span><button v-if="canManage" @click="openForm('add')" class="btn-primary">{{ $t('ui.addItem') }}</button></div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="table-header"><tr><th class="p-4 text-left">{{ $t('sales.product') }}</th><th class="p-4 text-left">{{ $t('ui.activeSerialNumber') }}</th><th class="p-4 text-right">{{ $t('sales.quantity') }}</th><th class="p-4 text-right">{{ $t('ui.value') }}</th><th class="p-4 text-left">{{ $t('ui.source') }}</th><th class="p-4 text-right">{{ $t('sales.actions') }}</th></tr></thead>
          <tbody class="divide-y bg-white"><tr v-for="item in project.items" :key="item.id"><td class="p-4">{{ item.product?.title || $t('ui.productDeleted') }}<p class="text-xs text-gray-500">{{ item.notes }}</p><router-link v-for="transfer in item.transfers" :key="transfer.id" to="/dashboard/deliveries" class="block text-xs text-indigo-600">{{ transfer.transfer_code }} ? {{ $label(transfer.status) }}</router-link></td><td class="p-4"><p v-for="sn in item.serial_numbers" :key="sn" class="font-mono text-xs">{{ sn }}</p><span v-if="!item.serial_numbers?.length" class="text-gray-400">{{ $t('ui.serialNumberNotRecorded') }}</span></td><td class="p-4 text-right">{{ item.quantity }}</td><td class="p-4 text-right">{{ currency(item.total_price) }}<p class="text-xs text-gray-500">{{ $t('projectShipping.unitPrice') }}: {{ currency(item.unit_price) }}</p></td><td class="p-4">{{ item.returned_item_id ? $t('ui.rmaStockValueRp0') : $t('ui.regularStock2') }}</td><td class="p-4 text-right space-x-3"><template v-if="canManage"><button @click="openForm('valuation', item)" class="text-indigo-600">{{ $t('projectShipping.editPrice') }}</button><button v-if="!item.serial_numbers?.length" @click="openForm('serials', item)" class="text-indigo-600">{{ $t('ui.recordInitialSerialNumbers') }}</button><button v-else @click="openForm('replace', item)" class="text-amber-600">{{ $t('ui.replaceItem') }}</button></template></td></tr><tr v-if="!project.items?.length"><td colspan="6" class="p-6 text-center text-gray-500">{{ $t('ui.noItemsYet') }}</td></tr></tbody>
        </table></div>
      </div>
        </template>
        <template #history>
      <div class="table-wrapper"><div class="card-header font-semibold">{{ $t('ui.additionReplacementHistory') }}</div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="table-header"><tr><th class="p-4 text-left">{{ $t('sales.date') }}</th><th class="p-4 text-left">{{ $t('sales.actions') }}</th><th class="p-4 text-left">{{ $t('ui.previousSerialNumber') }}</th><th class="p-4 text-left">{{ $t('ui.newSerialNumber') }}</th><th class="p-4 text-left">{{ $t('projectInvestment.progress.notes') }}</th></tr></thead><tbody class="divide-y bg-white"><tr v-for="change in changes" :key="change.id"><td class="p-4">{{ new Date(change.created_at).toLocaleString($t('ui.enUs')) }}</td><td class="p-4">{{ { shipment_correction: $t('projectShipping.correction'), shipment: $t('projectShipping.shipment'), valuation: $t('projectShipping.valuation'), add: $t('ui.addItem2'), replace: $t('ui.replaceItem2'), msa_replace: $t('ui.replaceViaMsa'), serials: $t('ui.recordInitialSerialNumbers2') }[change.type] || change.type }} · {{ change.quantity }} {{ $t('ui.units2') }}</td><td class="p-4 font-mono text-xs">{{ change.old_serial_numbers?.join(', ') || '-' }}</td><td class="p-4 font-mono text-xs">{{ change.new_serial_numbers?.join(', ') || '-' }}</td><td class="p-4">{{ change.notes || '-' }}<p v-if="change.details" class="text-xs text-gray-500">{{ change.details.product }} ? {{ currency(change.details.total_price ?? change.details.after?.total_price) }}</p></td></tr><tr v-if="!changes.length"><td colspan="5" class="p-6 text-center text-gray-500">{{ $t('ui.noChangesYet') }}</td></tr></tbody></table></div></div>
        </template>
      </ProjectManagementTabs>
    </template>
    <p v-if="error && !mode" class="p-4 text-red-700">{{ $ui(error) }}</p>
    <div v-if="mode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <form @submit.prevent="save" class="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 space-y-4">
        <h3 class="text-lg font-bold">{{ { add: $t('ui.addProjectItem'), replace: $t('ui.projectItemReplacement'), serials: $t('ui.recordInitialSerialNumbers'), valuation: $t('projectShipping.editPrice') }[mode] }}</h3>
        <template v-if="mode === 'add'">
          <div><label class="block mb-1 text-sm">{{ $t('ui.itemSource') }}</label><select v-model="source" class="input" @change="form.returned_item_id = ''; form.unit_price = source === 'rma' ? 0 : null"><option value="regular">{{ $t('ui.regularStock2') }}</option><option value="rma">{{ $t('ui.usableRmaStockRp0') }}</option></select></div>
          <div v-if="source === 'regular'"><label class="block mb-1 text-sm">{{ $t('sales.product') }}</label><select v-model="form.product_id" @change="selectProduct" required class="input"><option value="">{{ $t('ui.selectProduct2') }}</option><option v-for="product in products" :key="product.id" :value="product.id">{{ product.title }}</option></select></div>
          <div v-else><label class="block mb-1 text-sm">{{ $t('ui.rmaStockItems') }}</label><select v-model="form.returned_item_id" @change="selectRecovered" required class="input"><option value="">{{ $t('ui.selectItem') }}</option><option v-for="item in recovered" :key="item.id" :value="item.id">{{ item.product?.title }} · {{ item.serial_number || $t('ui.noSerialNumber') }} · {{ $label(item.condition) }} {{ $t('ui.available2') }} {{ item.available_quantity }}</option></select><p v-if="selectedRecovered" class="mt-1 text-xs text-amber-700">{{ $t('ui.condition3') }} {{ $label(selectedRecovered.condition) }}. {{ selectedRecovered.notes }}</p></div>
          <div class="grid grid-cols-2 gap-3"><div><label class="block mb-1 text-sm">{{ $t('sales.quantity') }}</label><input v-model.number="form.quantity" type="number" min="1" :max="source === 'rma' ? selectedRecovered?.available_quantity : undefined" required class="input" /></div><div><label class="block mb-1 text-sm">{{ $t('ui.valuePerUnit') }}</label><input v-model.number="form.unit_price" type="number" min="0" step="0.01" :disabled="source === 'rma'" required class="input" /></div></div>
        </template>
        <div v-else-if="mode === 'valuation'"><label class="block text-sm">{{ $t('projectShipping.unitPrice') }}</label><input v-model.number="form.unit_price" type="number" min="0" step="0.01" required :disabled="!!selectedItem?.returned_item_id" class="input" /></div>
        <p v-else>{{ selectedItem?.product?.title }} · {{ selectedItem?.quantity }} {{ $t('ui.units2') }}</p>
        <div v-if="mode === 'replace'"><label class="block mb-1 text-sm">{{ $t('ui.damagedReturnedItemSerialNumber') }}</label><textarea v-model="oldSerials" rows="3" required class="input" :placeholder="$t('ui.oneSerialNumberPerLine')"></textarea></div>
        <div v-if="mode !== 'valuation' && (source !== 'rma' || mode !== 'add')"><label class="block mb-1 text-sm">{{ mode === 'replace' ? $t('ui.replacementSerialNumber') : $t('ui.itemSerialNumbersOnePerUnit') }}</label><textarea v-model="newSerials" rows="3" :required="mode !== 'add'" class="input" :placeholder="$t('ui.oneSerialNumberPerLine')"></textarea></div>
        <div><label class="block mb-1 text-sm">{{ $t('ui.notesReason') }}</label><textarea v-model="form.notes" rows="3" :required="mode === 'replace'" maxlength="5000" class="input"></textarea></div>
        <p v-if="mode === 'replace'" class="text-xs text-gray-500">{{ $t('ui.unitRusakMasukDefectiveItemsUnitPenggantiDiambilDariStokRegulerDanSnProyek') }}</p>
        <p v-if="error" class="text-red-700 text-sm">{{ $ui(error) }}</p>
        <div class="flex justify-end gap-2"><button type="button" @click="mode = ''" :disabled="saving" class="btn-secondary">{{ $t('common.cancel') }}</button><button :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('common.save') }}</button></div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import ProjectManagementTabs from '../../components/ProjectManagementTabs.vue';
const route = useRoute();
const project = ref(null), changes = ref([]), products = ref([]), recovered = ref([]);
const loading = ref(true), saving = ref(false), error = ref(''), success = ref(''), mode = ref('');
const selectedItem = ref(null), source = ref('regular'), oldSerials = ref(''), newSerials = ref('');
const form = ref({ product_id: '', quantity: 1, unit_price: null, notes: '', returned_item_id: '' });
const canManage = computed(() => ['approved', 'active'].includes(project.value?.status));
const selectedRecovered = computed(() => recovered.value.find(item => item.id === Number(form.value.returned_item_id)));
const currency = amount => new Intl.NumberFormat(uiLocale(), { style: 'currency', currency: 'IDR' }).format(Number(amount || 0));
const split = value => value.split(/[\n,]+/).map(sn => sn.trim()).filter(Boolean);
const load = async () => {
  const [response, details] = await Promise.all([
    api.get(`/project-investments/${route.params.id}/items-detail`),
    api.get(`/project-investments/${route.params.id}`),
  ]);
  project.value = details.data; changes.value = response.data.changes;
  recovered.value = (await api.get('/stock/rma-inventory')).data;
};
const refreshProject = async () => {
  try { await load(); error.value = ''; }
  catch (err) { error.value = err.response?.data?.message || uiText("Gagal memperbarui detail proyek."); }
};
onMounted(async () => {
  try { await load(); const response = await api.get('/products', { params: { per_page: 1000 } }); products.value = response.data.data || response.data; }
  catch (err) { error.value = err.response?.data?.message || uiText("Gagal memuat detail proyek."); }
  finally { loading.value = false; }
});
const openForm = (action, item = null) => {
  mode.value = action; selectedItem.value = item; source.value = 'regular'; oldSerials.value = ''; newSerials.value = ''; error.value = ''; success.value = '';
  form.value = { product_id: item?.product_id || '', quantity: 1, unit_price: item?.unit_price ?? null, notes: '', returned_item_id: '' };
};
const selectProduct = () => { const product = products.value.find(p => p.id === Number(form.value.product_id)); form.value.unit_price = Number(product?.price ?? product?.sell_price ?? 0); };
const selectRecovered = () => { form.value.product_id = selectedRecovered.value?.product_id || ''; form.value.quantity = 1; form.value.unit_price = 0; };
const save = async () => {
  saving.value = true; error.value = '';
  try {
    const base = `/project-investments/${route.params.id}/items`;
    if (mode.value === 'add') await api.post(base, { ...form.value, returned_item_id: source.value === 'rma' ? form.value.returned_item_id : null, serial_numbers: source.value === 'rma' ? [] : split(newSerials.value) });
    else if (mode.value === 'valuation') await api.put(`${base}/${selectedItem.value.id}/valuation`, { unit_price: form.value.unit_price, notes: form.value.notes });
    else if (mode.value === 'serials') await api.post(`${base}/${selectedItem.value.id}/serials`, { serial_numbers: split(newSerials.value) });
    else await api.post(`${base}/${selectedItem.value.id}/replace`, { old_serial_numbers: split(oldSerials.value), new_serial_numbers: split(newSerials.value), notes: form.value.notes });
    mode.value = ''; success.value = uiText("Perubahan barang proyek berhasil disimpan."); await load();
  } catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || uiText("Gagal menyimpan perubahan."); }
  finally { saving.value = false; }
};
</script>
