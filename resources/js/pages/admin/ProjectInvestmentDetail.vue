<template>
  <div class="space-y-5">
    <router-link :to="{ name: 'ProjectInvestments' }" class="text-indigo-600">← Daftar Project Invest</router-link>
    <p v-if="loading" class="card p-8">Memuat proyek...</p>
    <template v-else-if="project">
      <div class="card p-6 flex flex-wrap items-start justify-between gap-4">
        <div><h2 class="text-2xl font-bold">{{ project.project_name }}</h2><p class="text-sm text-gray-500">{{ project.project_code }} · {{ project.status }}</p><p class="mt-3">{{ project.client_name }} · {{ project.client_contact || '-' }}</p><p class="text-sm text-gray-500">{{ project.project_location || '-' }} · {{ project.warehouse?.name || 'Gudang belum tersedia' }}</p><p class="mt-2 font-medium">Nilai barang dan material: {{ currency(project.total_value) }}</p></div>
      </div>
      <p v-if="success" class="rounded-lg bg-green-50 p-4 text-green-700">{{ success }}</p>
      <ProjectManagementTabs :project="project" @refresh="refreshProject">
        <template #items>
      <div class="table-wrapper">
        <div class="card-header flex items-center justify-between gap-3"><span class="font-semibold">Barang Proyek</span><button v-if="canManage" @click="openForm('add')" class="btn-primary">Tambah Barang</button></div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="table-header"><tr><th class="p-4 text-left">Produk</th><th class="p-4 text-left">SN Aktif</th><th class="p-4 text-right">Qty</th><th class="p-4 text-right">Nilai</th><th class="p-4 text-left">Sumber</th><th class="p-4 text-right">Aksi</th></tr></thead>
          <tbody class="divide-y bg-white"><tr v-for="item in project.items" :key="item.id"><td class="p-4">{{ item.product?.title || 'Produk dihapus' }}<p class="text-xs text-gray-500">{{ item.notes }}</p></td><td class="p-4"><p v-for="sn in item.serial_numbers" :key="sn" class="font-mono text-xs">{{ sn }}</p><span v-if="!item.serial_numbers?.length" class="text-gray-400">SN belum dicatat</span></td><td class="p-4 text-right">{{ item.quantity }}</td><td class="p-4 text-right">{{ currency(item.total_price) }}</td><td class="p-4">{{ item.returned_item_id ? 'Stok RMA · nilai Rp 0' : 'Stok reguler' }}</td><td class="p-4 text-right space-x-3"><template v-if="canManage"><button v-if="!item.serial_numbers?.length" @click="openForm('serials', item)" class="text-indigo-600">Catat SN Awal</button><button v-else @click="openForm('replace', item)" class="text-amber-600">Ganti Barang</button></template></td></tr><tr v-if="!project.items?.length"><td colspan="6" class="p-6 text-center text-gray-500">Belum ada barang.</td></tr></tbody>
        </table></div>
      </div>
        </template>
        <template #history>
      <div class="table-wrapper"><div class="card-header font-semibold">Riwayat Penambahan / Penggantian</div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="table-header"><tr><th class="p-4 text-left">Tanggal</th><th class="p-4 text-left">Aksi</th><th class="p-4 text-left">SN Lama</th><th class="p-4 text-left">SN Baru</th><th class="p-4 text-left">Catatan</th></tr></thead><tbody class="divide-y bg-white"><tr v-for="change in changes" :key="change.id"><td class="p-4">{{ new Date(change.created_at).toLocaleString('id-ID') }}</td><td class="p-4">{{ { add: 'Tambah barang', replace: 'Ganti barang', msa_replace: 'Ganti via MSA', serials: 'Catat SN awal' }[change.type] || change.type }} · {{ change.quantity }} unit</td><td class="p-4 font-mono text-xs">{{ change.old_serial_numbers?.join(', ') || '-' }}</td><td class="p-4 font-mono text-xs">{{ change.new_serial_numbers?.join(', ') || '-' }}</td><td class="p-4">{{ change.notes || '-' }}</td></tr><tr v-if="!changes.length"><td colspan="5" class="p-6 text-center text-gray-500">Belum ada perubahan.</td></tr></tbody></table></div></div>
        </template>
      </ProjectManagementTabs>
    </template>
    <p v-if="error && !mode" class="p-4 text-red-700">{{ error }}</p>
    <div v-if="mode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <form @submit.prevent="save" class="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 space-y-4">
        <h3 class="text-lg font-bold">{{ { add: 'Tambah Barang Proyek', replace: 'Penggantian Barang Proyek', serials: 'Catat SN Awal' }[mode] }}</h3>
        <template v-if="mode === 'add'">
          <div><label class="block mb-1 text-sm">Sumber Barang</label><select v-model="source" class="input" @change="form.returned_item_id = ''; form.unit_price = 0"><option value="regular">Stok reguler</option><option value="rma">Stok RMA layak pakai (Rp 0)</option></select></div>
          <div v-if="source === 'regular'"><label class="block mb-1 text-sm">Produk</label><select v-model="form.product_id" required class="input"><option value="">Pilih produk</option><option v-for="product in products" :key="product.id" :value="product.id">{{ product.title }}</option></select></div>
          <div v-else><label class="block mb-1 text-sm">Barang Stok RMA</label><select v-model="form.returned_item_id" @change="selectRecovered" required class="input"><option value="">Pilih barang</option><option v-for="item in recovered" :key="item.id" :value="item.id">{{ item.product?.title }} · {{ item.serial_number || 'Tanpa SN' }} · {{ item.condition }} · tersedia {{ item.available_quantity }}</option></select><p v-if="selectedRecovered" class="mt-1 text-xs text-amber-700">Kondisi: {{ selectedRecovered.condition }}. {{ selectedRecovered.notes }}</p></div>
          <div class="grid grid-cols-2 gap-3"><div><label class="block mb-1 text-sm">Qty</label><input v-model.number="form.quantity" type="number" min="1" :max="source === 'rma' ? selectedRecovered?.available_quantity : undefined" required class="input" /></div><div><label class="block mb-1 text-sm">Nilai Per Unit</label><input v-model.number="form.unit_price" type="number" min="0" step="0.01" :disabled="source === 'rma'" required class="input" /></div></div>
        </template>
        <p v-else>{{ selectedItem?.product?.title }} · {{ selectedItem?.quantity }} unit</p>
        <div v-if="mode === 'replace'"><label class="block mb-1 text-sm">SN Barang Rusak / Retur</label><textarea v-model="oldSerials" rows="3" required class="input" placeholder="Satu SN per baris"></textarea></div>
        <div v-if="source !== 'rma' || mode !== 'add'"><label class="block mb-1 text-sm">{{ mode === 'replace' ? 'SN Barang Pengganti' : 'SN Barang (satu per unit)' }}</label><textarea v-model="newSerials" rows="3" :required="mode !== 'add'" class="input" placeholder="Satu SN per baris"></textarea></div>
        <div><label class="block mb-1 text-sm">Catatan / Alasan</label><textarea v-model="form.notes" rows="3" :required="mode === 'replace'" maxlength="5000" class="input"></textarea></div>
        <p v-if="mode === 'replace'" class="text-xs text-gray-500">Unit rusak masuk Defective Items. Unit pengganti diambil dari stok reguler dan SN proyek diperbarui.</p>
        <p v-if="error" class="text-red-700 text-sm">{{ error }}</p>
        <div class="flex justify-end gap-2"><button type="button" @click="mode = ''" :disabled="saving" class="btn-secondary">Batal</button><button :disabled="saving" class="btn-primary">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button></div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import ProjectManagementTabs from '../../components/ProjectManagementTabs.vue';
const route = useRoute();
const project = ref(null), changes = ref([]), products = ref([]), recovered = ref([]);
const loading = ref(true), saving = ref(false), error = ref(''), success = ref(''), mode = ref('');
const selectedItem = ref(null), source = ref('regular'), oldSerials = ref(''), newSerials = ref('');
const form = ref({ product_id: '', quantity: 1, unit_price: 0, notes: '', returned_item_id: '' });
const canManage = computed(() => ['approved', 'active'].includes(project.value?.status));
const selectedRecovered = computed(() => recovered.value.find(item => item.id === Number(form.value.returned_item_id)));
const currency = amount => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount || 0));
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
  catch (err) { error.value = err.response?.data?.message || 'Gagal memperbarui detail proyek.'; }
};
onMounted(async () => {
  try { await load(); const response = await api.get('/products', { params: { per_page: 1000 } }); products.value = response.data.data || response.data; }
  catch (err) { error.value = err.response?.data?.message || 'Gagal memuat detail proyek.'; }
  finally { loading.value = false; }
});
const openForm = (action, item = null) => {
  mode.value = action; selectedItem.value = item; source.value = 'regular'; oldSerials.value = ''; newSerials.value = ''; error.value = ''; success.value = '';
  form.value = { product_id: item?.product_id || '', quantity: 1, unit_price: 0, notes: '', returned_item_id: '' };
};
const selectRecovered = () => { form.value.product_id = selectedRecovered.value?.product_id || ''; form.value.quantity = 1; form.value.unit_price = 0; };
const save = async () => {
  saving.value = true; error.value = '';
  try {
    const base = `/project-investments/${route.params.id}/items`;
    if (mode.value === 'add') await api.post(base, { ...form.value, returned_item_id: source.value === 'rma' ? form.value.returned_item_id : null, serial_numbers: source.value === 'rma' ? [] : split(newSerials.value) });
    else if (mode.value === 'serials') await api.post(`${base}/${selectedItem.value.id}/serials`, { serial_numbers: split(newSerials.value) });
    else await api.post(`${base}/${selectedItem.value.id}/replace`, { old_serial_numbers: split(oldSerials.value), new_serial_numbers: split(newSerials.value), notes: form.value.notes });
    mode.value = ''; success.value = 'Perubahan barang proyek berhasil disimpan.'; await load();
  } catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || 'Gagal menyimpan perubahan.'; }
  finally { saving.value = false; }
};
</script>
