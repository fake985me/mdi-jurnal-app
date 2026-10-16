<template>
  <div class="mx-auto max-w-2xl space-y-5">
    <router-link :to="{ name: 'RMAs' }" class="text-indigo-600">← Kembali ke RMA Management</router-link>
    <h2 class="text-2xl font-bold">Hasil Pengecekan RMA</h2>
    <p v-if="loading">Memuat data RMA...</p>
    <form v-else-if="rma && !saved" @submit.prevent="save" class="card p-6 space-y-4">
      <div class="rounded-lg bg-slate-50 p-4 space-y-1">
        <p class="font-semibold">{{ rma.rma_code }} · {{ rma.product?.title }}</p>
        <p class="text-sm">SN: {{ rma.serial_number }} · Qty: {{ rma.quantity }}</p>
        <p class="text-sm text-slate-500">{{ rma.customer_name }} · {{ rma.notes }}</p>
      </div>
      <p v-if="correctionMode" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Koreksi SN penggantian pada RMA yang sudah diproses. Riwayat pengiriman tetap disimpan.</p>
      <div v-else><label class="block mb-1 text-sm font-medium">Hasil Pengecekan</label><select v-model="form.result" required class="input"><option value="normal">Barang kembali normal / kirim kembali</option><option value="replace">Perlu penggantian barang</option></select></div>
      <div v-if="!correctionMode"><label class="block mb-1 text-sm font-medium">Catatan Hasil Pengecekan</label><textarea v-model="form.condition" required maxlength="5000" rows="4" class="input"></textarea></div>
      <div v-if="form.result === 'replace'"><label class="block mb-1 text-sm font-medium">SN Barang Pengganti</label><textarea v-model="replacementSerials" required rows="3" class="input" placeholder="Isi satu SN per baris"></textarea><p class="mt-1 text-xs text-slate-500">Isi {{ rma.quantity }} SN. SN pada RMA, pengiriman asal, dan garansi diperbarui otomatis; SN lama disimpan sebagai riwayat.</p></div>
      <p class="text-sm text-slate-500">Barang yang sudah diperiksa akan masuk ke Delivery untuk proses pengiriman.</p>
      <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
      <button :disabled="saving || rma.status === 'rejected'" class="btn-primary">{{ saving ? 'Menyimpan...' : correctionMode ? 'Simpan SN Pengganti' : 'Simpan Hasil Pengecekan' }}</button>
    </form>
    <div v-else-if="saved" class="card p-6 space-y-4"><p>{{ correctionMode ? 'SN pengganti diperbarui. Barang retur tercatat pada Defective Items.' : 'Hasil pengecekan disimpan. Permintaan pengiriman sudah tersedia.' }}</p><router-link :to="{ name: 'Deliveries' }" class="btn-primary inline-block">Buka Delivery</router-link></div>
    <p v-else-if="error" class="text-red-700">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
const route = useRoute();
const rma = ref(null);
const loading = ref(true);
const saving = ref(false);
const saved = ref(false);
const error = ref('');
const replacementSerials = ref('');
const form = ref({ result: 'normal', condition: '' });
const correctionMode = computed(() => ['processed', 'completed'].includes(rma.value?.status));
onMounted(async () => {
  try { rma.value = (await api.get(`/rmas/${route.params.id}`)).data; if (correctionMode.value) form.value.result = 'replace'; }
  catch (err) { error.value = err.response?.data?.message || 'Gagal memuat RMA.'; }
  finally { loading.value = false; }
});
const save = async () => {
  saving.value = true;
  error.value = '';
  try {
    const payload = { ...form.value };
    if (payload.result === 'replace') payload.replacement_serial_numbers = replacementSerials.value.split(/[\n,]+/).map(sn => sn.trim()).filter(Boolean);
    await api.post(`/rmas/${route.params.id}/${correctionMode.value ? 'replacement-serials' : 'inspect'}`, payload);
    saved.value = true;
  } catch (err) { error.value = Object.values(err.response?.data?.errors || {}).flat()[0] || err.response?.data?.message || 'Gagal menyimpan hasil pengecekan.'; }
  finally { saving.value = false; }
};
</script>
