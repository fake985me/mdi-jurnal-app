<template>
  <main class="delivery-note">
    <table class="letterhead"><tbody><tr>
      <td><img class="logo" :src="'/assets/static/kop/header mdi kiri.png'" alt="MDI" /></td>
      <td class="right"><img class="company" :src="'/assets/static/kop/header mdi kanan.png'" alt="PT. Moimstone Dasan Indonesia" /></td>
    </tr></tbody></table>
    <h1>{{ t('deliveryNote.title') }}</h1>
    <p class="subtitle">Delivery Note &middot; {{ uiText(note.type) }}</p>
    <table class="details"><tbody><tr>
      <td class="recipient">
        <strong>{{ t('deliveryNote.recipient') }}</strong>
        <p>{{ note.recipient || '—' }}</p><p>{{ note.destination || '—' }}</p>
        <p>{{ t('deliveryNote.phone') }}: {{ note.contact || '—' }}</p>
      </td>
      <td><table class="meta"><tbody>
        <tr><td>{{ t('deliveryNote.number') }}</td><td><strong>{{ note.number }}</strong></td></tr>
        <tr><td>{{ t('sales.date') }}</td><td>{{ displayDate(note.date, { day: '2-digit', month: 'long', year: 'numeric' }) }}</td></tr>
        <tr><td>{{ t('deliveryNote.reference') }}</td><td>{{ note.reference || '—' }}</td></tr>
        <tr v-if="note.invoice && note.invoice !== note.reference"><td>{{ t('deliveryNote.invoice') }}</td><td>{{ note.invoice }}</td></tr>
        <tr v-if="note.po"><td>{{ t('deliveryNote.po') }}</td><td>{{ note.po }}</td></tr>
        <tr><td>{{ t('deliveryNote.courier') }}</td><td>{{ note.courier || '—' }}</td></tr>
        <tr><td>{{ t('deliveryNote.tracking') }}</td><td>{{ note.tracking || '—' }}</td></tr>
        <tr v-if="note.warehouse"><td>{{ t('deliveryNote.warehouse') }}</td><td>{{ note.warehouse }}</td></tr>
        <tr><td>{{ t('projectInvestment.materials.status') }}</td><td>{{ statusLabel }}</td></tr>
      </tbody></table></td>
    </tr></tbody></table>
    <table class="items">
      <colgroup><col style="width:6%" /><col style="width:30%" /><col style="width:25%" /><col style="width:10%" /><col style="width:29%" /></colgroup>
      <thead><tr><th>No.</th><th>{{ t('deliveryNote.product') }}</th><th>{{ t('projectInvestment.description') }}</th><th class="quantity">{{ t('sales.quantity') }}</th><th>{{ t('deliveryNote.serial') }}</th></tr></thead>
      <tbody>
        <tr v-for="(item, index) in note.items" :key="index">
          <td>{{ index + 1 }}</td><td><strong>{{ item.product }}</strong><div v-if="item.sku" class="muted">SKU: {{ item.sku }}</div></td>
          <td>{{ item.description || '—' }}</td><td class="quantity">{{ item.quantity }}</td><td class="serial">{{ hasSerial(item) ? item.serial : t('deliveryNote.missingSerial') }}</td>
        </tr>
        <tr v-if="!note.items.length"><td colspan="5">{{ t('deliveryNote.empty') }}</td></tr>
      </tbody>
    </table>
    <div class="total">{{ t('deliveryNote.total') }}: {{ note.totalQuantity }} {{ t('deliveryNote.units') }}</div>
    <p v-if="note.items.some(item => !hasSerial(item))" class="muted">{{ t('deliveryNote.missingSerialHint') }}</p>
    <div class="notes"><strong>{{ t('deliveryNote.notes') }}</strong><br />{{ note.notes || '—' }}</div>
    <p class="receipt">{{ t('deliveryNote.receipt') }}</p>
    <table class="signatures"><tbody>
      <tr><td>{{ t('deliveryNote.preparedBy') }}</td><td>{{ t('deliveryNote.sender') }}</td><td>{{ t('deliveryNote.recipient') }}</td></tr>
      <tr><td class="signature-space"></td><td></td><td></td></tr>
      <tr><td><div class="signature-name">{{ t('deliveryNote.signature') }}</div></td><td><div class="signature-name">{{ t('deliveryNote.signature') }}</div></td><td><div class="signature-name">{{ t('deliveryNote.recipientSignature') }}</div></td></tr>
    </tbody></table>
    <p class="footer">{{ t('deliveryNote.receivedDate') }}: ____________________ &nbsp; {{ t('deliveryNote.receivedTime') }}: __________</p>
  </main>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { displayDate, uiText } from '../../utils/uiText';

const props = defineProps({ note: { type: Object, required: true } });
const { t, te } = useI18n();
const hasSerial = item => item.serial !== null && item.serial !== undefined && String(item.serial).trim() !== '';
const statusLabel = computed(() => {
  const key = `labels.${props.note.status}`;
  return te(key) ? t(key) : props.note.status;
});
</script>

<style scoped>
.delivery-note { box-sizing: border-box; width: 182mm; margin: 0; background: white; color: #172033; font: 12px/1.5 Arial, sans-serif; }
.delivery-note * { box-sizing: border-box; }
table { border-spacing: 0; }
.letterhead { width: 100%; border-bottom: 2px solid #214f85; margin-bottom: 20px; padding-bottom: 12px; }
.letterhead td { vertical-align: middle; }
.logo { display: inline-block; width: 108px; height: auto; }
.company { display: inline-block; width: 285px; max-width: 100%; height: auto; }
.right { text-align: right; }
h1 { margin: 0; font-size: 22px; font-weight: bold; letter-spacing: 2px; }
.subtitle { color: #64748b; margin: 2px 0 18px; }
.details { width: 100%; margin-bottom: 22px; table-layout: fixed; }
.details td { vertical-align: top; overflow-wrap: anywhere; }
.recipient { padding-right: 24px; width: 48%; }
.recipient p { margin: 3px 0; white-space: pre-line; }
.meta { width: 100%; font-size: 11px; }
.meta td { padding: 2px 0; }
.meta td:first-child { color: #64748b; width: 42%; }
.items { width: 100%; border-collapse: collapse; table-layout: fixed; }
.items th { background: #dbeafe; color: #214f85; text-align: left; font-size: 10px; text-transform: uppercase; }
.items th, .items td { padding: 10px 8px; border: 1px solid #cbd5e1; vertical-align: top; overflow-wrap: anywhere; }
.items .quantity { text-align: center; }
.items .serial { font-family: Consolas, monospace; white-space: pre-wrap; word-break: break-all; }
.muted { color: #64748b; font-size: 10px; }
.total { text-align: right; margin-top: 10px; font-weight: bold; }
.notes { border: 1px solid #cbd5e1; border-radius: 4px; padding: 12px; margin: 18px 0; white-space: pre-wrap; }
.receipt { margin: 18px 0; font-size: 11px; }
.signatures { width: 100%; text-align: center; table-layout: fixed; margin-top: 24px; }
.signatures td { padding: 0 12px; }
.signature-space { height: 75px; }
.signature-name { border-top: 1px solid #94a3b8; padding-top: 5px; }
.footer { margin-top: 22px; color: #64748b; font-size: 10px; }
thead { display: table-header-group; }
tr, .notes, .receipt, .signatures { break-inside: avoid; page-break-inside: avoid; }
</style>
