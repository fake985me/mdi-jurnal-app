import { createApp, nextTick } from 'vue';
import { createI18n } from 'vue-i18n';
import api from '../services/api';
import i18n from '../i18n';
import DeliveryNote from '../components/print/DeliveryNote.vue';
import { uiText } from './uiText';
import { printVueDocument } from './printDocument';

export async function printDeliveryNote(key) {
  const printWindow = window.open('', '_blank');
  if (!printWindow) throw new Error(uiText('deliveryNote.popup'));
  printWindow.document.body.textContent = uiText('deliveryNote.loading');
  let app;
  let host;
  try {
    const response = await api.get(`/delivery-requests/${encodeURIComponent(key)}/print-data`);
    if (printWindow.closed) return;
    // Render Vue in the current document so scoped styles are resolved before printing.
    host = document.createElement('div');
    host.style.cssText = 'position:fixed;left:-10000px;top:0;width:182mm;pointer-events:none';
    host.setAttribute('aria-hidden', 'true');
    document.body.append(host);
    app = createApp(DeliveryNote, { note: response.data });
    // Unmounting a print app must not dispose the main application's i18n instance.
    app.use(createI18n({
      legacy: false,
      locale: i18n.global.locale.value,
      fallbackLocale: i18n.global.fallbackLocale.value,
      messages: Object.fromEntries(i18n.global.availableLocales.map(locale => [locale, i18n.global.getLocaleMessage(locale)])),
    }));
    app.mount(host);
    await nextTick();
    await printVueDocument(host.firstElementChild, {
      printWindow,
      title: `${uiText('deliveryNote.title')} ${response.data.number}`,
      buttonLabel: uiText('deliveryNote.print'),
    });
  } catch (error) {
    if (!printWindow.closed) printWindow.close();
    throw new Error(uiText('deliveryNote.error'));
  } finally {
    app?.unmount();
    host?.remove();
  }
}
