import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp, computed } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createMemoryHistory } from 'vue-router';
import { renderToString } from '@vue/server-renderer';
import { parse, compileTemplate } from '@vue/compiler-sfc';
import { baseCompile } from '@intlify/message-compiler';
import { parse as parseTemplate } from '@vue/compiler-dom';

const messages = Object.fromEntries(['id', 'en'].map(locale => [
    locale, JSON.parse(fs.readFileSync(`resources/js/locales/${locale}.json`, 'utf8')),
]));

function flatten(object, prefix = '') {
    return Object.entries(object).flatMap(([key, value]) => typeof value === 'object'
        ? flatten(value, `${prefix}${key}.`)
        : [[`${prefix}${key}`, value]]);
}

const entries = Object.fromEntries(Object.entries(messages).map(([locale, data]) => [locale, new Map(flatten(data))]));
assert.deepEqual([...entries.id.keys()].sort(), [...entries.en.keys()].sort(), 'Locale keys must match');
for (const [locale, values] of Object.entries(entries)) {
    for (const [key, message] of values) {
        baseCompile(message, { onError(error) { throw new Error(`${locale}:${key}: ${error.message}`); } });
    }
}
const untranslatedIndonesian = /\b(tidak|belum|gagal|pilih|tambah|hapus|riwayat|memuat|pelanggan|penjualan|pembelian|gudang|manajemen|kelola|simpan|semua|tanggal|kembali|produk|halaman|berhasil)\b/i;
for (const [key, value] of entries.en) {
    assert.ok(!untranslatedIndonesian.test(value), `English message still contains Indonesian: ${key}`);
}

function vueFiles(directory) {
    return fs.readdirSync(directory, { withFileTypes: true }).flatMap(entry => entry.isDirectory()
        ? vueFiles(path.join(directory, entry.name))
        : entry.name.endsWith('.vue') ? [path.join(directory, entry.name)] : []);
}

const files = vueFiles('resources/js');
for (const filename of files) {
    const source = fs.readFileSync(filename, 'utf8');
    const { descriptor, errors } = parse(source);
    assert.equal(errors.length, 0, `${filename}: invalid Vue component`);
    if (descriptor.template) {
        const result = compileTemplate({ source: descriptor.template.content, filename, id: 'i18n-check' });
        assert.equal(result.errors.length, 0, `${filename}: ${result.errors.join(', ')}`);
        const inspect = node => {
            if (node.type === 2) assert.ok(!untranslatedIndonesian.test(node.content), `${filename}: hardcoded UI text ${node.content.trim()}`);
            if (node.type === 1) {
                for (const prop of node.props) {
                    if (prop.type === 6 && ['placeholder', 'title', 'aria-label'].includes(prop.name)) {
                        assert.ok(!untranslatedIndonesian.test(prop.value?.content || ''), `${filename}: hardcoded ${prop.name}`);
                    }
                }
            }
            for (const child of node.children || []) inspect(child);
        };
        inspect(parseTemplate(descriptor.template.content));
    }
    for (const match of source.matchAll(/\$t\(\s*['"]([^'"]+)['"]\s*\)/g)) {
        assert.ok(entries.id.has(match[1]), `${filename}: missing translation ${match[1]}`);
    }
}

globalThis.localStorage = {
    getItem(key) { return key === 'locale' ? 'id' : null; },
    setItem() {}, removeItem() {},
};

// Render real page components with empty API fixtures. Charts need a browser,
// so the chart component is stubbed while its surrounding UI is rendered.
const server = await createServer({
    configFile: false,
    // Keep this SSR check from replacing the running dev server's dependency cache.
    cacheDir: 'node_modules/.vite-i18n',
    resolve: { alias: { '@': path.resolve('resources/js') } },
    plugins: [{
        name: 'i18n-test-fixtures', enforce: 'pre',
        resolveId(id) { if (id === 'vue3-apexcharts') return '\0chart-stub'; },
        load(id) {
            if (id === '\0chart-stub') return 'export default { render() { return null } }';
            if (id.endsWith('/services/api.js')) return 'export default { get: async () => ({ data: [] }), post: async () => ({ data: {} }) }';
        },
        transform(code, id) {
            if (id.endsWith('/Dashboard.vue')) return code.replace('const salesLoading = ref(true)', 'const salesLoading = ref(false)');
        },
    }, vue()],
    server: { middlewareMode: true }, appType: 'custom',
    ssr: { noExternal: ['vue-i18n', 'vue3-apexcharts'] },
});

try {
    const { default: i18n } = await server.ssrLoadModule('/resources/js/i18n.js');
    const { uiText, displayDate } = await server.ssrLoadModule('/resources/js/utils/uiText.js');
    const { displayLabel } = await server.ssrLoadModule('/resources/js/utils/displayLabel.js');
    const pages = {
        Dashboard: ['Statistik Penjualan', 'Sales Statistics'],
        Customers: ['Manajemen Pelanggan', 'Customer Management'],
        Products: ['Manajemen Produk', 'Product Management'],
        Warehouses: ['Manajemen Gudang', 'Warehouse Management'],
        Sales: ['Daftar Penjualan', 'Sales List'],
        Categories: ['Manajemen Kategori', 'Category Management'],
        Suppliers: ['Manajemen Pemasok', 'Supplier Management'],
        Purchases: ['Manajemen Pembelian', 'Purchase Management'],
        Expenses: ['Biaya / Expenses', 'Expenses'],
        Assets: ['Manajemen Aset Tetap', 'Fixed Asset Management'],
        Lendings: ['Manajemen Peminjaman', 'Lending Management'],
        Stock: ['Manajemen Stok', 'Stock Management'],
        BankAccounts: ['Rekening Bank', 'Bank Accounts'],
        Accounting: ['Akuntansi', 'Accounting'],
        Users: ['Manajemen Pengguna', 'User Management'],
        TaxRates: ['Manajemen Pajak', 'Tax Management'],
        MSAProjects: ['Proyek MSA', 'MSA Project'],
        RMAs: ['Manajemen RMA', 'RMA Management'],
        RMAShipments: ['Pengiriman RMA', 'RMA Shipments'],
    };
    for (const [name, headings] of Object.entries(pages)) {
        const { default: page } = await server.ssrLoadModule(`/resources/js/pages/admin/${name}.vue`);
        const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:pathMatch(.*)*', component: page }] });
        await router.push('/');
        await router.isReady();
        for (const [index, locale] of ['id', 'en'].entries()) {
            i18n.global.locale.value = locale;
            const app = createSSRApp(page);
            app.use(createPinia());
            app.use(router);
            app.use(i18n);
            Object.assign(app.config.globalProperties, { $ui: uiText, $date: displayDate, $label: displayLabel });
            const output = await renderToString(app);
            assert.ok(output.includes(headings[index]), `${name}: missing ${locale} heading ${headings[index]}`);
            if (name === 'Customers') {
                assert.ok(output.includes('value="company"'), 'Translated filters must retain API values');
                assert.ok(output.includes(locale === 'en' ? 'Add Customer' : 'Tambah Pelanggan'));
            }
            if (name === 'Dashboard' && locale === 'en') {
                for (const text of ['Summary by transaction status', 'Last 6 Months', 'No sales data yet', 'Top Products', 'Units sold this period', 'Pending', 'Completed', 'Cancelled', 'All Categories']) {
                    assert.ok(output.includes(text), `Dashboard: missing English text ${text}`);
                }
                for (const text of ['Statistik Penjualan', 'Belum ada data penjualan', 'Semua Kategori', 'Menunggu']) {
                    assert.ok(!output.includes(text), `Dashboard: Indonesian text remains in English UI: ${text}`);
                }
            }
        }
    }

    // Render the Vue print template with many serials and untrusted customer text.
    const { default: DeliveryNote } = await server.ssrLoadModule('/resources/js/components/print/DeliveryNote.vue');
    const note = {
        number: 'DN-SALE-000001', type: 'Penjualan', recipient: '<script>alert(1)</script>',
        contact: '0812345678', destination: 'Test destination', date: '2026-10-19',
        reference: 'INV-PRINT', invoice: 'INV-PRINT', po: 'PO-PRINT', courier: 'Test courier',
        tracking: 'TRACK-PRINT', warehouse: 'Test warehouse', status: 'shipped',
        notes: '<img src=x onerror=alert(1)>', totalQuantity: 60,
        items: Array.from({ length: 60 }, (_, index) => ({
            product: 'Router', sku: 'RTR-1', description: 'Test item', quantity: 1,
            serial: index === 59 ? null : `SERIAL-${String(index + 1).padStart(4, '0')}`,
        })),
    };
    for (const [locale, title, missing] of [['id', 'SURAT JALAN', 'Belum diisi'], ['en', 'DELIVERY NOTE', 'Not recorded']]) {
        i18n.global.locale.value = locale;
        const app = createSSRApp(DeliveryNote, { note });
        app.use(i18n);
        const output = await renderToString(app);
        assert.ok(output.includes(title));
        assert.equal((output.match(/class="serial"/g) || []).length, 60);
        for (const value of ['SERIAL-0001', 'SERIAL-0059', 'PO-PRINT', 'TRACK-PRINT', missing]) assert.ok(output.includes(value));
        assert.ok(output.includes('&lt;script&gt;'));
        assert.ok(output.includes('&lt;img'));
        assert.ok(!output.includes('<script>alert(1)</script>'));
    }

    // Cached option labels must also change without recreating the component.
    i18n.global.locale.value = 'id';
    const cachedLabel = uiText('Ubah');
    const label = computed(() => uiText(cachedLabel));
    const date = computed(() => displayDate('2026-10-17T12:00:00', { weekday: 'long', month: 'long' }));
    assert.equal(label.value, 'Ubah');
    assert.match(date.value, /Sabtu|Oktober/);
    i18n.global.locale.value = 'en';
    assert.equal(label.value, 'Edit');
    assert.match(date.value, /Saturday|October/);
    assert.equal(displayLabel('in_repair'), 'In Repair');
    assert.equal(uiText('ui.deleteCustomerNamed', { p0: 'Example' }), 'Delete customer "Example"?');
    assert.equal(uiText('contoh: gpon | xgspon'), 'e.g. gpon | xgspon');
    i18n.global.locale.value = 'id';
    assert.equal(label.value, 'Ubah');
    assert.equal(displayLabel('in_repair'), 'Dalam Perbaikan');
    assert.equal(uiText('Custom customer name'), 'Custom customer name');

    console.log(`Passed: ${entries.id.size} matching locale keys, ${files.length} Vue components, ${Object.keys(pages).length} pages and the delivery note in both languages, 60 serial rows, escaped customer text, and reactive label/date switching.`);
} finally {
    await server.close();
}
