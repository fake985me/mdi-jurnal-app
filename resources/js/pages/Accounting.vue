<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2
                    class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent"
                >
                    Accounting
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Manage invoices, tax calculations, and financial reports
                </p>
            </div>
            <div class="flex space-x-2">
                <button @click="showTaxModal = true" class="btn-secondary">
                    ⚙️ Tax Settings
                </button>
                <button
                    @click="
                        showModal = true;
                        resetForm();
                    "
                    class="btn-primary"
                >
                    + Create Invoice
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8">
                <button
                    @click="activeTab = 'invoices'"
                    :class="[
                        'py-2 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'invoices'
                            ? 'border-emerald-500 text-emerald-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Invoices
                </button>
                <button
                    @click="activeTab = 'reports'"
                    :class="[
                        'py-2 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'reports'
                            ? 'border-emerald-500 text-emerald-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Tax Reports
                </button>
                <button
                    @click="activeTab = 'summary'"
                    :class="[
                        'py-2 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'summary'
                            ? 'border-emerald-500 text-emerald-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Monthly Summary
                </button>
            </nav>
        </div>

        <!-- Invoices Tab -->
        <div v-if="activeTab === 'invoices'" class="space-y-4">
            <!-- Filters -->
            <div class="card p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input
                        v-model="filters.search"
                        @input="loadInvoices"
                        type="text"
                        placeholder="Search invoices..."
                        class="input"
                    />
                    <select
                        v-model="filters.status"
                        @change="loadInvoices"
                        class="input"
                    >
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                    <input
                        v-model="filters.start_date"
                        @change="loadInvoices"
                        type="date"
                        class="input"
                    />
                    <input
                        v-model="filters.end_date"
                        @change="loadInvoices"
                        type="date"
                        class="input"
                    />
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="table-wrapper">
                <div class="card-header">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Invoices
                    </h3>
                </div>

                <div v-if="loading" class="p-8 text-center">
                    <p class="text-gray-600">Loading...</p>
                </div>

                <table v-else class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Invoice #
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Customer
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                Subtotal
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                Tax
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                Total
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                            class="hover:bg-gray-50"
                        >
                            <td
                                class="px-6 py-4 text-sm font-medium text-gray-900"
                            >
                                {{ invoice.invoice_number }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ invoice.sale?.customer_name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ formatDate(invoice.invoice_date) }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-900 text-right"
                            >
                                {{ formatCurrency(invoice.subtotal) }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-500 text-right"
                            >
                                <span class="text-xs text-gray-400"
                                    >{{ invoice.tax_type?.toUpperCase() }}
                                    {{ invoice.tax_rate }}%</span
                                >
                                <br />{{ formatCurrency(invoice.tax_amount) }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm font-bold text-gray-900 text-right"
                            >
                                {{ formatCurrency(invoice.total) }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusBadge(invoice.status)">{{
                                    invoice.status
                                }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <button
                                    v-if="invoice.status === 'draft'"
                                    @click="markSent(invoice)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    Send
                                </button>
                                <button
                                    v-if="
                                        ['sent', 'overdue'].includes(
                                            invoice.status
                                        )
                                    "
                                    @click="markPaid(invoice)"
                                    class="text-green-600 hover:text-green-900"
                                >
                                    Mark Paid
                                </button>
                                <button
                                    @click="downloadPdf(invoice)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    PDF
                                </button>
                                <button
                                    @click="deleteInvoice(invoice.id)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="invoices.data?.length"
                    class="px-6 py-4 bg-gray-50 flex justify-between"
                >
                    <p class="text-sm text-gray-700">
                        Showing {{ invoices.from }} to {{ invoices.to }} of
                        {{ invoices.total }}
                    </p>
                    <div class="flex space-x-2">
                        <button
                            @click="loadInvoices(invoices.current_page - 1)"
                            :disabled="!invoices.prev_page_url"
                            class="btn-secondary disabled:opacity-50"
                        >
                            Previous
                        </button>
                        <button
                            @click="loadInvoices(invoices.current_page + 1)"
                            :disabled="!invoices.next_page_url"
                            class="btn-secondary disabled:opacity-50"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Reports Tab -->
        <div v-if="activeTab === 'reports'" class="space-y-4">
            <div class="card p-4">
                <div class="flex items-center gap-4">
                    <input
                        v-model="reportFilters.start_date"
                        type="date"
                        class="input w-48"
                    />
                    <span>to</span>
                    <input
                        v-model="reportFilters.end_date"
                        type="date"
                        class="input w-48"
                    />
                    <button @click="loadTaxReport" class="btn-primary">
                        Generate Report
                    </button>
                </div>
            </div>

            <div v-if="taxReport" class="space-y-4">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div
                        class="card p-4 bg-gradient-to-br from-blue-50 to-blue-100"
                    >
                        <p class="text-sm text-blue-600 font-medium">
                            Total Invoices
                        </p>
                        <p class="text-2xl font-bold text-blue-800">
                            {{ taxReport.summary.total_invoices }}
                        </p>
                    </div>
                    <div
                        class="card p-4 bg-gradient-to-br from-gray-50 to-gray-100"
                    >
                        <p class="text-sm text-gray-600 font-medium">
                            Subtotal
                        </p>
                        <p class="text-2xl font-bold text-gray-800">
                            {{
                                formatCurrency(taxReport.summary.total_subtotal)
                            }}
                        </p>
                    </div>
                    <div
                        class="card p-4 bg-gradient-to-br from-yellow-50 to-yellow-100"
                    >
                        <p class="text-sm text-yellow-600 font-medium">
                            Total Tax (PPN)
                        </p>
                        <p class="text-2xl font-bold text-yellow-800">
                            {{ formatCurrency(taxReport.summary.total_tax) }}
                        </p>
                    </div>
                    <div
                        class="card p-4 bg-gradient-to-br from-green-50 to-green-100"
                    >
                        <p class="text-sm text-green-600 font-medium">
                            Total Revenue
                        </p>
                        <p class="text-2xl font-bold text-green-800">
                            {{
                                formatCurrency(taxReport.summary.total_revenue)
                            }}
                        </p>
                    </div>
                </div>

                <!-- By Tax Type -->
                <div class="card p-4">
                    <h4 class="font-semibold text-gray-800 mb-3">
                        By Tax Type
                    </h4>
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Tax Type</th>
                                <th class="px-4 py-2 text-right">Count</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                                <th class="px-4 py-2 text-right">Tax Amount</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in taxReport.by_tax_type"
                                :key="item.tax_type"
                                class="border-b"
                            >
                                <td class="px-4 py-2">{{ item.tax_type }}</td>
                                <td class="px-4 py-2 text-right">
                                    {{ item.count }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(item.subtotal) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(item.tax_amount) }}
                                </td>
                                <td class="px-4 py-2 text-right font-medium">
                                    {{ formatCurrency(item.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Monthly Summary Tab -->
        <div v-if="activeTab === 'summary'" class="space-y-4">
            <div class="card p-4">
                <div class="flex items-center gap-4">
                    <select
                        v-model="summaryYear"
                        @change="loadMonthlySummary"
                        class="input w-32"
                    >
                        <option
                            v-for="year in availableYears"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="monthlySummary" class="space-y-4">
                <!-- Totals -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div
                        class="card p-4 bg-gradient-to-br from-blue-50 to-blue-100"
                    >
                        <p class="text-sm text-blue-600 font-medium">
                            Total Invoices
                        </p>
                        <p class="text-2xl font-bold text-blue-800">
                            {{ monthlySummary.totals.total_invoices }}
                        </p>
                    </div>
                    <div
                        class="card p-4 bg-gradient-to-br from-yellow-50 to-yellow-100"
                    >
                        <p class="text-sm text-yellow-600 font-medium">
                            Total Tax
                        </p>
                        <p class="text-2xl font-bold text-yellow-800">
                            {{
                                formatCurrency(monthlySummary.totals.tax_amount)
                            }}
                        </p>
                    </div>
                    <div
                        class="card p-4 bg-gradient-to-br from-green-50 to-green-100"
                    >
                        <p class="text-sm text-green-600 font-medium">Paid</p>
                        <p class="text-2xl font-bold text-green-800">
                            {{ formatCurrency(monthlySummary.totals.paid) }}
                        </p>
                    </div>
                    <div
                        class="card p-4 bg-gradient-to-br from-red-50 to-red-100"
                    >
                        <p class="text-sm text-red-600 font-medium">Unpaid</p>
                        <p class="text-2xl font-bold text-red-800">
                            {{ formatCurrency(monthlySummary.totals.unpaid) }}
                        </p>
                    </div>
                </div>

                <!-- Monthly Table -->
                <div class="card p-4">
                    <h4 class="font-semibold text-gray-800 mb-3">
                        Monthly Breakdown
                    </h4>
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Month</th>
                                <th class="px-4 py-2 text-right">Invoices</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                                <th class="px-4 py-2 text-right">Tax</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-right">Paid</th>
                                <th class="px-4 py-2 text-right">Unpaid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in monthlySummary.data"
                                :key="row.month"
                                class="border-b"
                            >
                                <td class="px-4 py-2">{{ row.month_name }}</td>
                                <td class="px-4 py-2 text-right">
                                    {{ row.total_invoices }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(row.subtotal) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    {{ formatCurrency(row.tax_amount) }}
                                </td>
                                <td class="px-4 py-2 text-right font-medium">
                                    {{ formatCurrency(row.total) }}
                                </td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    {{ formatCurrency(row.paid) }}
                                </td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    {{ formatCurrency(row.unpaid) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Invoice Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Create Invoice from Sale</h3>

                <form @submit.prevent="createInvoice" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Select Sale *</label
                        >
                        <select v-model="form.sale_id" required class="input">
                            <option value="">Select a sale</option>
                            <option
                                v-for="sale in availableSales"
                                :key="sale.id"
                                :value="sale.id"
                            >
                                {{ sale.invoice_number }} -
                                {{ sale.customer_name }} ({{
                                    formatCurrency(sale.total_amount)
                                }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Invoice Date *</label
                        >
                        <input
                            v-model="form.invoice_date"
                            type="date"
                            required
                            class="input"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Due Date</label
                        >
                        <input
                            v-model="form.due_date"
                            type="date"
                            class="input"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Tax Type</label
                        >
                        <select v-model="form.tax_type" class="input">
                            <option
                                v-for="tr in taxRates"
                                :key="tr.id"
                                :value="tr.code"
                            >
                                {{ tr.name }} ({{ tr.rate }}%)
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Discount</label
                        >
                        <input
                            v-model.number="form.discount_amount"
                            type="number"
                            step="0.01"
                            class="input"
                            placeholder="0"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Notes</label
                        >
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="input"
                        ></textarea>
                    </div>

                    <div
                        v-if="error"
                        class="bg-red-50 text-red-600 p-2 rounded text-sm"
                    >
                        {{ error }}
                    </div>

                    <div class="flex justify-end space-x-2 pt-3">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="btn-secondary"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="btn-primary"
                        >
                            {{ saving ? "Creating..." : "Create Invoice" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tax Settings Modal -->
        <div
            v-if="showTaxModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h3 class="text-xl font-bold mb-4">Tax Settings</h3>

                <table class="min-w-full text-sm mb-4">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Code</th>
                            <th class="px-4 py-2 text-right">Rate (%)</th>
                            <th class="px-4 py-2 text-center">Active</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="tr in taxRates"
                            :key="tr.id"
                            class="border-b"
                        >
                            <td class="px-4 py-2">{{ tr.name }}</td>
                            <td class="px-4 py-2">{{ tr.code }}</td>
                            <td class="px-4 py-2 text-right">{{ tr.rate }}%</td>
                            <td class="px-4 py-2 text-center">
                                <span
                                    :class="
                                        tr.is_active
                                            ? 'text-green-600'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{ tr.is_active ? "✓" : "○" }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button
                                    @click="toggleTaxRate(tr)"
                                    class="text-blue-600 hover:text-blue-900 text-xs"
                                >
                                    {{ tr.is_active ? "Disable" : "Enable" }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end">
                    <button @click="showTaxModal = false" class="btn-secondary">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../services/api";

const activeTab = ref("invoices");
const invoices = ref({ data: [] });
const taxRates = ref([]);
const taxReport = ref(null);
const monthlySummary = ref(null);
const availableSales = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showTaxModal = ref(false);
const saving = ref(false);
const error = ref("");

const filters = ref({
    search: "",
    status: "",
    start_date: "",
    end_date: "",
});

const reportFilters = ref({
    start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1)
        .toISOString()
        .split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
});

const summaryYear = ref(new Date().getFullYear());
const availableYears = computed(() => {
    const years = [];
    for (let y = new Date().getFullYear(); y >= 2020; y--) {
        years.push(y);
    }
    return years;
});

const form = ref({
    sale_id: "",
    invoice_date: new Date().toISOString().split("T")[0],
    due_date: "",
    tax_type: "ppn",
    discount_amount: 0,
    notes: "",
});

const formatCurrency = (value) =>
    "Rp " + Number(value || 0).toLocaleString("id-ID");
const formatDate = (date) =>
    date ? new Date(date).toLocaleDateString("id-ID") : "-";

const getStatusBadge = (status) => {
    const badges = {
        draft: "badge",
        sent: "badge-info",
        paid: "badge-success",
        overdue: "badge-danger",
        cancelled: "badge-warning",
    };
    return badges[status] || "badge";
};

const loadInvoices = async (page = 1) => {
    loading.value = true;
    try {
        const response = await api.get("/invoices", {
            params: { page, ...filters.value },
        });
        invoices.value = response.data;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const loadTaxRates = async () => {
    try {
        const response = await api.get("/tax-rates");
        taxRates.value = response.data;
    } catch (err) {
        console.error(err);
    }
};

const loadAvailableSales = async () => {
    try {
        // Load sales that don't have invoices yet
        const response = await api.get("/sales", { params: { per_page: 100 } });
        // Filter sales without invoices (we'd need backend support, for now show all)
        availableSales.value = response.data.data || [];
    } catch (err) {
        console.error(err);
    }
};

const loadTaxReport = async () => {
    try {
        const response = await api.get("/invoices/tax-report", {
            params: reportFilters.value,
        });
        taxReport.value = response.data;
    } catch (err) {
        console.error(err);
    }
};

const loadMonthlySummary = async () => {
    try {
        const response = await api.get("/invoices/monthly-summary", {
            params: { year: summaryYear.value },
        });
        monthlySummary.value = response.data;
    } catch (err) {
        console.error(err);
    }
};

const resetForm = () => {
    form.value = {
        sale_id: "",
        invoice_date: new Date().toISOString().split("T")[0],
        due_date: "",
        tax_type: "ppn",
        discount_amount: 0,
        notes: "",
    };
    error.value = "";
};

const createInvoice = async () => {
    saving.value = true;
    error.value = "";

    try {
        await api.post("/invoices", form.value);
        showModal.value = false;
        loadInvoices();
    } catch (err) {
        error.value = err.response?.data?.message || "Failed to create invoice";
    } finally {
        saving.value = false;
    }
};

const markSent = async (invoice) => {
    try {
        await api.post(`/invoices/${invoice.id}/mark-sent`);
        loadInvoices();
    } catch (err) {
        alert("Failed to update invoice");
    }
};

const markPaid = async (invoice) => {
    try {
        await api.post(`/invoices/${invoice.id}/mark-paid`);
        loadInvoices();
    } catch (err) {
        alert("Failed to update invoice");
    }
};

const downloadPdf = async (invoice) => {
    try {
        const response = await api.get(`/invoices/${invoice.id}/pdf`, {
            responseType: "blob",
        });
        const url = window.URL.createObjectURL(
            new Blob([response.data], { type: "application/pdf" })
        );
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", `invoice_${invoice.invoice_number}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (err) {
        alert("Failed to download PDF");
    }
};

const deleteInvoice = async (id) => {
    if (!confirm("Are you sure you want to delete this invoice?")) return;
    try {
        await api.delete(`/invoices/${id}`);
        loadInvoices();
    } catch (err) {
        alert("Failed to delete invoice");
    }
};

const toggleTaxRate = async (taxRate) => {
    try {
        await api.post(`/tax-rates/${taxRate.id}/toggle`);
        loadTaxRates();
    } catch (err) {
        alert("Failed to toggle tax rate");
    }
};

onMounted(() => {
    loadInvoices();
    loadTaxRates();
    loadAvailableSales();
    loadTaxReport();
    loadMonthlySummary();
});
</script>
