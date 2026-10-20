<template>
  <div class="space-y-5">
    <header class="flex flex-wrap justify-between items-center gap-4">
      <div>
        <h2 class="text-2xl font-semibold text-slate-900">{{ t('expenses.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ t('expenses.subtitle') }}</p>
      </div>
      <button v-if="canWrite" @click="openForm()" :disabled="!optionsReady" class="btn-primary disabled:opacity-40">+ {{ t('expenses.create') }}</button>
    </header>
    <div v-if="optionsError" role="alert" class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">{{ optionsError }} <button @click="loadOptions" class="underline">{{ t('expenses.retry') }}</button></div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="stat in ['total', 'paid', 'unpaid', 'overdue']" :key="stat" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ t(`expenses.${stat}`) }}</p>
        <p class="mt-3 text-xl font-semibold" :class="stat === 'overdue' ? 'text-red-600' : 'text-slate-900'">{{ loading ? '…' : money(rows.summary?.[stat]) }}</p>
        <p class="mt-2 text-xs text-slate-400">{{ t('expenses.filtered') }}</p>
      </div>
    </div>
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="p-5 space-y-4 w-full">
        <div class="flex flex-wrap justify-between items-center gap-3">
          <h3 class="font-semibold text-slate-800">{{ t('expenses.list') }}</h3>
          <input v-model="filters.search" :placeholder="t('expenses.search')" :aria-label="t('expenses.search')" class="field w-full sm:w-80" />
        </div>
        <div class="flex flex-wrap gap-3 items-end">
          <label class="filter-label">{{ t('expenses.status') }}
            <select v-model="filters.status" class="field">
              <option value="">{{ t('expenses.allStatus') }}</option>
              <option v-for="status in statuses" :key="status" :value="status">{{ t(`expenses.${status}`) }}</option>
            </select>
          </label>
          <label class="filter-label">{{ t('expenses.category') }}
            <select v-model="filters.category_id" class="field">
              <option value="">{{ t('expenses.allCategories') }}</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </label>
          <label class="filter-label">{{ t('expenses.from') }}<input v-model="filters.start_date" type="date" class="field" /></label>
          <label class="filter-label">{{ t('expenses.to') }}<input v-model="filters.end_date" type="date" :min="filters.start_date" class="field" /></label>
          <button @click="resetFilters" class="field text-blue-600">{{ t('expenses.reset') }}</button>
          <label class="filter-label ml-auto">{{ t('ui.rows') }}
            <select v-model.number="filters.per_page" class="field">
              <option v-for="size in [15,25,50]" :key="size" :value="size">{{ size }}</option>
            </select>
          </label>
        </div>
      </div>
      <div v-if="loadError" role="alert" class="p-8 text-center text-red-600">{{ loadError }} 
        <button @click="loadExpenses()" class="underline">{{ t('expenses.retry') }}</button>
      </div>
      <div v-else-if="loading" role="status" class="p-12 text-center text-slate-500">{{ t('expenses.loading') }}</div>
      <div v-else-if="!rows.data.length" class="p-12 text-center text-sm text-slate-500">{{ t('expenses.empty') }}</div>
      <div v-else class="overflow-x-auto w-full">
        <table class="w-full text-sm whitespace-nowrap">
          <thead class="bg-slate-50 text-xs text-slate-500 border-y border-slate-200">
            <tr>
              <th class="cell text-left">{{ t('expenses.date') }}</th>
              <th class="cell text-left">{{ t('expenses.number') }}</th>
              <th class="cell text-left">{{ t('expenses.vendor') }}</th>
              <th class="cell text-left">{{ t('expenses.category') }}</th>
              <th class="cell text-left">{{ t('expenses.dueDate') }}</th>
              <th class="cell text-left">{{ t('expenses.status') }}</th>
              <th class="cell text-right">{{ t('expenses.total') }}</th>
              <th class="cell text-right">{{ t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100"><tr v-for="expense in rows.data" :key="expense.id" class="hover:bg-blue-50/30">
            <td class="cell">{{ displayDate(expense.expense_date) }}</td>
            <td class="cell">
              <button @click="openDetail(expense.id)" class="text-blue-600 font-medium hover:underline">{{ expense.expense_number }}</button>
              <p class="mt-1 text-xs text-slate-400">{{ expense.reference }}</p>
            </td>
            <td class="cell">
              {{ expense.vendor_name || '—' }}
            </td>
            <td class="cell">
              <p v-for="name in categoryNames(expense)" :key="name">{{ name }}</p>
            </td>
            <td class="cell" :class="isOverdue(expense) ? 'text-red-600' : 'text-slate-500'">
              {{ displayDate(expense.due_date) }}
            </td>
            <td class="cell">
              <span :class="badge(expense.status)" class="rounded-full px-2.5 py-1 text-xs font-medium">
                {{ t(`expenses.${expense.status}`) }}
              </span>
            </td>
            <td class="cell text-right font-semibold">
              {{ money(expense.total_amount) }}
            </td>
            <td class="cell text-right">
              <button @click="openDetail(expense.id)" class="text-blue-600">{{ t('expenses.detail') }}</button>
            </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="rows.total && !loading && !loadError" class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-4 text-sm text-slate-500"><p>{{ t('sales.showing') }} {{ rows.from }}–{{ rows.to }} {{ t('sales.of') }} {{ rows.total }}</p><div class="flex gap-3 items-center"><button @click="loadExpenses(rows.current_page - 1)" :disabled="!rows.prev_page_url" class="field disabled:opacity-40">{{ t('sales.previous') }}</button><span>{{ rows.current_page }} / {{ rows.last_page }}</span><button @click="loadExpenses(rows.current_page + 1)" :disabled="!rows.next_page_url" class="field disabled:opacity-40">{{ t('sales.next') }}</button></div></div>
    </section>

    <Dialog :open="!!mode" @close="closeModal" class="relative z-50">
      <div class="fixed inset-0 bg-slate-900/40" aria-hidden="true" />
      <div class="fixed inset-0 overflow-y-auto p-4 sm:p-8"><DialogPanel class="mx-auto max-w-4xl rounded-xl bg-white p-5 sm:p-7 shadow-xl">
        <div class="flex justify-between items-start gap-4 mb-6"><div><DialogTitle class="text-xl font-semibold">{{ t(mode === 'create' ? 'expenses.create' : mode === 'edit' ? 'expenses.edit' : mode === 'pay' ? 'expenses.pay' : 'expenses.detail') }}</DialogTitle><p v-if="selected" class="mt-1 text-sm text-slate-500">{{ selected.expense_number }}</p></div><button @click="closeModal" :disabled="saving" :aria-label="t('expenses.close')" class="text-xl">×</button></div>
        <p v-if="formError" role="alert" class="mb-5 rounded-lg bg-red-50 p-3 text-sm text-red-700 whitespace-pre-line">{{ formError }}</p>
        <div v-if="mode === 'detail' && selected" class="space-y-5">
          <div class="flex flex-wrap gap-3 items-center"><span :class="badge(selected.status)" class="rounded-full px-3 py-1 text-sm">{{ t(`expenses.${selected.status}`) }}</span><span class="text-sm text-slate-500">{{ t('expenses.recordedBy') }}: {{ selected.user?.name || '—' }}</span></div>
          <dl class="grid sm:grid-cols-2 gap-4 text-sm"><div v-for="field in ['vendor_name','expense_date','due_date','reference']" :key="field"><dt class="text-slate-500">{{ t(fieldLabels[field]) }}</dt><dd class="mt-1">{{ field.endsWith('date') ? displayDate(selected[field]) : selected[field] || '—' }}</dd></div></dl>
          <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="cell text-left">{{ t('expenses.category') }}</th><th class="cell text-left">{{ t('expenses.description') }}</th><th class="cell text-right">{{ t('expenses.amount') }}</th><th class="cell text-right">{{ t('expenses.taxRate') }}</th><th class="cell text-right">{{ t('expenses.total') }}</th></tr></thead><tbody><tr v-for="item in selected.items" :key="item.id" class="border-b"><td class="cell">{{ item.category?.name }}</td><td class="cell whitespace-pre-wrap">{{ item.description || '—' }}</td><td class="cell text-right">{{ money(item.amount) }}</td><td class="cell text-right">{{ item.tax_rate }}%</td><td class="cell text-right">{{ money(item.total) }}</td></tr></tbody></table></div>
          <div class="ml-auto max-w-sm space-y-2 text-sm"><p class="flex justify-between"><span>{{ t('expenses.subtotal') }}</span><span>{{ money(selected.subtotal) }}</span></p><p class="flex justify-between"><span>{{ t('expenses.tax') }}</span><span>{{ money(selected.tax_amount) }}</span></p><p class="flex justify-between border-t pt-3 text-lg font-semibold"><span>{{ t('expenses.grandTotal') }}</span><span>{{ money(selected.total_amount) }}</span></p></div>
          <div v-if="selected.status === 'paid'" class="rounded-lg bg-emerald-50 p-4 text-sm space-y-1"><p>{{ t('expenses.account') }}: {{ selected.payment?.bank_account?.bank_name }} · {{ selected.payment?.bank_account?.account_number }}</p><p>{{ t('expenses.paymentDate') }}: {{ displayDate(selected.payment?.payment_date) }}</p><p>{{ t('expenses.method') }}: {{ methodLabel(selected.payment?.method) }}</p></div>
          <p v-if="selected.notes" class="whitespace-pre-wrap text-sm text-slate-600">{{ selected.notes }}</p>
          <div v-if="selected.files?.length"><p class="text-sm font-semibold mb-2">{{ t('expenses.attachments') }}</p><button v-for="file in selected.files" :key="file.id" @click="downloadFile(file)" class="block py-1 text-sm text-blue-600 hover:underline">{{ file.name }}</button></div>
          <div v-if="canWrite" class="flex flex-wrap justify-end gap-3 pt-4 border-t"><template v-if="selected.status === 'unpaid'"><button @click="removeExpense" :disabled="saving" class="field text-red-600">{{ t('expenses.delete') }}</button><button @click="openForm(selected)" :disabled="saving || !optionsReady" class="btn-secondary">{{ t('expenses.edit') }}</button><button @click="openPayment" :disabled="saving || !optionsReady" class="btn-primary">{{ t('expenses.pay') }}</button></template><button v-if="selected.status !== 'cancelled'" @click="cancelExpense" :disabled="saving" class="field text-red-600">{{ t('expenses.cancel') }}</button></div>
        </div>
        <form v-else-if="mode === 'create' || mode === 'edit' || mode === 'pay'" @submit.prevent="save" class="space-y-5">
          <template v-if="mode !== 'pay'">
            <div class="grid sm:grid-cols-2 gap-4"><label class="form-label">{{ t('expenses.vendor') }}<input v-model="form.vendor_name" maxlength="255" class="input" /></label><label class="form-label">{{ t('expenses.reference') }}<input v-model="form.reference" maxlength="255" class="input" /></label><label class="form-label">{{ t('expenses.date') }}<input v-model="form.expense_date" type="date" required class="input" /></label><label class="form-label">{{ t('expenses.dueDate') }}<input v-model="form.due_date" type="date" :min="form.expense_date" class="input" /></label></div>
            <div class="flex flex-wrap justify-between items-center gap-3"><h4 class="font-semibold">{{ t('expenses.category') }}</h4><button type="button" @click="categoryFormOpen = !categoryFormOpen" class="text-sm text-blue-600">+ {{ t('expenses.newCategory') }}</button></div>
            <div v-if="categoryFormOpen" class="flex gap-2 rounded-lg bg-blue-50 p-3"><input v-model="categoryName" maxlength="150" :placeholder="t('expenses.categoryName')" :aria-label="t('expenses.categoryName')" class="input" /><button type="button" @click="addCategory" :disabled="categorySaving || !categoryName.trim()" class="btn-secondary disabled:opacity-40">{{ t('common.save') }}</button></div>
            <div v-for="(item,index) in form.items" :key="index" class="grid sm:grid-cols-12 gap-3 rounded-lg border border-slate-200 p-4">
              <label class="form-label sm:col-span-4">{{ t('expenses.category') }}<select v-model="item.expense_category_id" required class="input"><option value="">{{ t('expenses.selectCategory') }}</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
              <label class="form-label sm:col-span-4">{{ t('expenses.description') }}<input v-model="item.description" maxlength="1000" class="input" /></label><label class="form-label sm:col-span-3">{{ t('expenses.amount') }}<input v-model.number="item.amount" type="number" required min="0.01" max="9999999999.99" step="0.01" class="input" /></label><button type="button" @click="form.items.splice(index,1)" :disabled="form.items.length === 1" :aria-label="t('expenses.removeItem')" class="sm:col-span-1 text-red-600 disabled:opacity-30">×</button>
              <label class="form-label sm:col-span-3">{{ t('expenses.taxRate') }}<input v-model.number="item.tax_rate" type="number" required min="0" max="100" step="0.01" class="input" /></label><p class="sm:col-span-9 text-right self-end text-sm font-medium">{{ money(lineTotal(item)) }}</p>
            </div>
            <div class="flex justify-between gap-4"><button type="button" @click="form.items.push(newItem())" :disabled="form.items.length >= 100" class="text-sm text-blue-600">+ {{ t('expenses.addItem') }}</button><div class="text-right space-y-1 text-sm"><p>{{ t('expenses.subtotal') }}: {{ money(formSubtotal) }}</p><p>{{ t('expenses.tax') }}: {{ money(formTax) }}</p><p class="text-lg font-semibold">{{ t('expenses.grandTotal') }}: {{ money(formSubtotal + formTax) }}</p></div></div>
            <label class="form-label">{{ t('expenses.notes') }}<textarea v-model="form.notes" rows="3" maxlength="10000" class="input" /></label>
            <label class="form-label">{{ t('expenses.attachments') }}<input type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" @change="chooseFiles" class="block text-sm mt-2" /><span class="text-xs font-normal text-slate-400">{{ t('expenses.attachmentHint') }}</span><span v-for="file in files" :key="file.name" class="block text-xs text-slate-600">{{ file.name }}</span><span v-for="file in selected?.files || []" :key="file.id" class="block text-xs text-slate-400">{{ file.name }}</span></label>
            <label class="form-label">{{ t('expenses.status') }}<select v-model="form.status" class="input"><option value="unpaid">{{ t('expenses.payLater') }}</option><option value="paid">{{ t('expenses.payNow') }}</option></select></label>
          </template>
          <div v-if="mode === 'pay' || form.status === 'paid'" class="rounded-lg border border-blue-100 bg-blue-50 p-4 space-y-4">
            <p v-if="mode === 'pay'" class="font-semibold">{{ t('expenses.grandTotal') }}: {{ money(selected.total_amount) }}</p>
            <div class="grid sm:grid-cols-2 gap-4"><label class="form-label sm:col-span-2">{{ t('expenses.account') }}<select v-model="form.bank_account_id" required class="input"><option value="">{{ t('expenses.selectAccount') }}</option><option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.bank_name }} · {{ account.account_number }} ({{ money(account.current_balance) }})</option></select></label><label class="form-label">{{ t('expenses.paymentDate') }}<input v-model="form.payment_date" type="date" required class="input" /></label><label class="form-label">{{ t('expenses.method') }}<select v-model="form.method" required class="input"><option v-for="method in methods" :key="method" :value="method">{{ methodLabel(method) }}</option></select></label></div><p class="text-xs text-blue-700">{{ t('expenses.paymentHint') }}</p>
          </div>
          <div class="flex justify-end gap-3 border-t pt-5"><button type="button" @click="closeModal" :disabled="saving" class="btn-secondary">{{ t('common.cancel') }}</button><button type="submit" :disabled="saving || !optionsReady || categorySaving" class="btn-primary disabled:opacity-40">{{ saving ? t('expenses.saving') : t(mode === 'pay' ? 'expenses.pay' : 'expenses.save') }}</button></div>
        </form>
      </DialogPanel></div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import { displayDate, uiLocale } from '../../utils/uiText';

const { t } = useI18n();
const auth = useAuthStore();
const route = useRoute();
const canWrite = computed(() => ['superadmin','admin','staff','sales'].includes(auth.user?.role));
const today = () => { const date = new Date(); return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`; };
const newItem = () => ({ expense_category_id: '', description: '', amount: 0, tax_rate: 0 });
const defaults = () => ({ search: '', status: '', category_id: '', start_date: '', end_date: '', per_page: 15 });
const newForm = () => ({ expense_date: today(), due_date: '', vendor_name: '', reference: '', status: 'unpaid', notes: '', bank_account_id: '', payment_date: today(), method: 'transfer', items: [newItem()] });
const rows = ref({ data: [], summary: {} });
const filters = ref(defaults());
const categories = ref([]), accounts = ref([]), selected = ref(null), form = ref(newForm()), files = ref([]);
const loading = ref(true), saving = ref(false), optionsReady = ref(false), mode = ref('');
const loadError = ref(''), formError = ref(''), optionsError = ref('');
const categoryFormOpen = ref(false), categoryName = ref(''), categorySaving = ref(false);
const statuses = ['unpaid','paid','cancelled'], methods = ['cash','transfer','debit','credit','other'];
const fieldLabels = { vendor_name: 'expenses.vendor', expense_date: 'expenses.date', due_date: 'expenses.dueDate', reference: 'expenses.reference' };
let timer, requestId = 0;
const money = amount => new Intl.NumberFormat(uiLocale(), { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(amount) || 0);
const round = amount => Math.round((Number(amount) + Number.EPSILON) * 100) / 100;
const tax = item => round((Number(item.amount) || 0) * (Number(item.tax_rate) || 0) / 100);
const lineTotal = item => round(Number(item.amount || 0) + tax(item));
const formSubtotal = computed(() => round(form.value.items.reduce((sum,item) => sum + Number(item.amount || 0),0)));
const formTax = computed(() => round(form.value.items.reduce((sum,item) => sum + tax(item),0)));
const methodLabel = method => methods.includes(method) ? t(`expenses.${method}`) : method || '—';
const categoryNames = expense => [...new Set(expense.items.map(item => item.category?.name).filter(Boolean))];
const badge = status => ({ paid: 'bg-emerald-50 text-emerald-700', unpaid: 'bg-amber-50 text-amber-700', cancelled: 'bg-slate-100 text-slate-500' }[status]);
const isOverdue = expense => expense.status === 'unpaid' && expense.due_date && expense.due_date.slice(0,10) < today();
const message = (error, fallback) => error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join('\n') : error.response?.data?.message || t(fallback);
async function loadOptions() {
  optionsError.value = ''; optionsReady.value = false;
  const result = await Promise.allSettled([api.get('/expense-categories'),api.get('/bank-accounts', { params: { is_active: 1 } })]);
  if (result[0].status === 'fulfilled') categories.value = result[0].value.data;
  if (result[1].status === 'fulfilled') accounts.value = result[1].value.data.filter(account => account.currency === 'IDR');
  optionsReady.value = result.every(item => item.status === 'fulfilled');
  if (!optionsReady.value) optionsError.value = t('expenses.optionsError');
}
async function loadExpenses(page = 1) {
  const id = ++requestId; loading.value = true; loadError.value = '';
  try { const response = await api.get('/expenses', { params: { ...filters.value, page } }); if (id === requestId) rows.value = response.data; }
  catch (error) { if (id === requestId) loadError.value = message(error,'expenses.loadError'); }
  finally { if (id === requestId) loading.value = false; }
}
watch(filters, () => { clearTimeout(timer); ++requestId; loading.value = true; timer = setTimeout(() => loadExpenses(),300); }, { deep: true });
const resetFilters = () => { filters.value = defaults(); };
function closeModal() { if (!saving.value) { mode.value = ''; selected.value = null; files.value = []; formError.value = ''; } }
function openForm(expense = null) {
  selected.value = expense; formError.value = ''; files.value = []; categoryFormOpen.value = false; categoryName.value = '';
  form.value = newForm();
  if (expense) {
    Object.assign(form.value, { expense_date: expense.expense_date.slice(0,10), due_date: expense.due_date?.slice(0,10) || '', vendor_name: expense.vendor_name || '', reference: expense.reference || '', notes: expense.notes || '', items: expense.items.map(item => ({ expense_category_id: item.expense_category_id, description: item.description || '', amount: Number(item.amount), tax_rate: Number(item.tax_rate) })) });
  }
  mode.value = expense ? 'edit' : 'create';
}
async function openDetail(id) {
  try { selected.value = (await api.get(`/expenses/${id}`)).data; formError.value = ''; mode.value = 'detail'; }
  catch (error) { loadError.value = message(error,'expenses.loadError'); }
}
function openPayment() { form.value = newForm(); mode.value = 'pay'; formError.value = ''; }
function chooseFiles(event) {
  const chosen = [...event.target.files];
  if (chosen.length + (selected.value?.files?.length || 0) > 5 || chosen.some(file => file.size > 10*1024*1024 || !/\.(jpe?g|png|webp|pdf)$/i.test(file.name))) {
    formError.value = t('expenses.fileError'); event.target.value = ''; files.value = []; return;
  }
  files.value = chosen; formError.value = '';
}
async function addCategory() {
  if (!categoryName.value.trim()) return;
  categorySaving.value = true; formError.value = '';
  try { const response = await api.post('/expense-categories', { name: categoryName.value.trim() }); categories.value.push(response.data); categoryName.value = ''; categoryFormOpen.value = false; }
  catch (error) { formError.value = message(error,'expenses.newCategoryError'); }
  finally { categorySaving.value = false; }
}
async function save() {
  saving.value = true; formError.value = '';
  try {
    let response;
    if (mode.value === 'pay') response = await api.post(`/expenses/${selected.value.id}/pay`, { bank_account_id: form.value.bank_account_id, payment_date: form.value.payment_date, method: form.value.method });
    else {
      const data = new FormData();
      for (const key of ['expense_date','due_date','vendor_name','reference','status','notes']) data.append(key,form.value[key] || '');
      if (form.value.status === 'paid') for (const key of ['bank_account_id','payment_date','method']) data.append(key,form.value[key]);
      form.value.items.forEach((item,index) => Object.entries(item).forEach(([key,value]) => data.append(`items[${index}][${key}]`,value ?? '')));
      files.value.forEach(file => data.append('attachments[]',file));
      if (mode.value === 'edit') data.append('_method','PUT');
      response = await api.post(mode.value === 'edit' ? `/expenses/${selected.value.id}` : '/expenses',data, { headers: { 'Content-Type': 'multipart/form-data' } });
    }
    selected.value = response.data; mode.value = 'detail'; files.value = [];
    await Promise.all([loadExpenses(),loadOptions()]);
  } catch (error) { formError.value = message(error,'expenses.saveError'); }
  finally { saving.value = false; }
}
async function cancelExpense() {
  if (!confirm(t('expenses.confirmCancel',{ number: selected.value.expense_number }))) return;
  saving.value = true; formError.value = '';
  try { selected.value = (await api.post(`/expenses/${selected.value.id}/cancel`)).data; await Promise.all([loadExpenses(),loadOptions()]); }
  catch (error) { formError.value = message(error,'expenses.actionError'); }
  finally { saving.value = false; }
}
async function removeExpense() {
  if (!confirm(t('expenses.confirmDelete',{ number: selected.value.expense_number }))) return;
  saving.value = true; formError.value = '';
  try { await api.delete(`/expenses/${selected.value.id}`); mode.value = ''; selected.value = null; await loadExpenses(rows.value.data.length === 1 ? Math.max(1,rows.value.current_page - 1) : rows.value.current_page); }
  catch (error) { formError.value = message(error,'expenses.actionError'); }
  finally { saving.value = false; }
}
async function downloadFile(file) {
  try {
    const response = await api.get(`/expenses/${selected.value.id}/attachments/${file.id}`, { responseType: 'blob' });
    const url = URL.createObjectURL(response.data), link = document.createElement('a'); link.href = url; link.download = file.name; document.body.append(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(url),1000);
  } catch (error) { formError.value = t('expenses.downloadError'); }
}
watch(() => route.query.expense, id => { if (id) openDetail(id); });
onMounted(() => { loadExpenses(); loadOptions(); if (route.query.expense) openDetail(route.query.expense); });
onBeforeUnmount(() => { clearTimeout(timer); ++requestId; });
</script>

<style scoped>
.field { border: 1px solid #e2e8f0; border-radius: .5rem; background: white; padding: .55rem .75rem; font-size: .875rem; color: #475569; }
.field:focus { outline: 2px solid #93c5fd; outline-offset: 1px; }
.filter-label { display: flex; flex-direction: column; gap: .25rem; font-size: .75rem; color: #64748b; }
.form-label { display: flex; flex-direction: column; gap: .375rem; font-size: .875rem; font-weight: 500; color: #475569; }
.cell { padding: .875rem 1rem; }
</style>
