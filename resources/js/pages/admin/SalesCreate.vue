<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="$router.push({ name: 'Sales' })" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <span class="text-xl">←</span>
        </button>
        <div>
          <p class="text-xs text-indigo-600 font-medium cursor-pointer hover:underline" @click="$router.push({ name: 'Sales' })">
            {{ $t('labels.sale') }}
          </p>
          <h2 class="text-2xl font-bold text-gray-900">
            {{ isViewing ? $t('ui.viewSalesOrder') : isEditing ? $t('ui.editSalesOrder') : $t('ui.createSalesOrder') }}
          </h2>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <select v-if="!isViewing" v-model="form.status" class="input w-40 text-sm">
          <option value="pending">{{ $t('sales.salesOrders') }}</option>
          <option value="completed">{{ $t('labels.completed') }}</option>
        </select>
        <!-- Export & Delivery buttons shown only when editing -->
        <template v-if="isEditing && viewingSale">
          <button type="button" @click="exportExcel(viewingSale.id, viewingSale.invoice_number,)" class="px-4 py-2 border border-green-600 text-green-600 rounded-lg hover:bg-green-50 text-sm font-medium transition-colors">{{ $t('sales.excel') }}
          </button>
          <button type="button" @click="downloadPdf(viewingSale.id, viewingSale.invoice_number,)" class="px-4 py-2 bg-linear-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 text-sm font-medium shadow-sm transition-all">{{ $t('sales.pdf') }}
          </button>
          <button type="button" @click=" printPdf(viewingSale.id, viewingSale.invoice_number)" :disabled="printing"
            class="px-4 py-2 bg-linear-to-r from-indigo-500 to-indigo-600 text-white rounded-lg hover:from-indigo-600 hover:to-indigo-700 text-sm font-medium disabled:opacity-50">
            {{ printing ? $t('ui.loadingPdf') : $t('ui.printPdf') }}
          </button>
          <button v-if=" !isViewing && !viewingSale.delivery && ['pending', 'completed'].includes( viewingSale.status, )" type="button" @click="openDeliveryModal"
            class="px-4 py-2 bg-linear-to-r from-teal-500 to-teal-600 text-white rounded-lg hover:from-teal-600 hover:to-teal-700 text-sm font-medium shadow-sm transition-all">{{ $t('sales.delivery') }}
          </button>
        </template>
        <p class="text-2xl font-bold text-red-600">
          Total Rp{{ formatPrice(calculateGrandTotal()) }}
        </p>
      </div>
    </div>

    <!-- Wrapper container to center it on screens, but remove styling when printing -->
    <div v-if="isViewing && viewingSale" class="bg-gray-100 min-h-screen py-8 print:bg-white print:py-0">
      <!-- The Letter Page -->
      <div class="mx-auto bg-white shadow-lg w-204 h-264 p-24 print:w-204 print:h-264 print:p-24 print:shadow-none print:mx-0">
        <!-- Page Content Goes Here -->
        <!-- Invoice Header -->
        <div class="flex items-center justify-between border-b border-blue-900 p-0" style="margin-bottom: 20px; padding-bottom: -12%; margin-top: -12%;">
          <div>
            <!-- Your Company Logo (Optional) -->
            <img src="/public/assets/static/kop/header mdi kiri.png" :alt="$t('ui.companyLogo')" class="flex items-start justify-between relative w-28 top-6" style="margin-left: 0px; margin-right: 12%" />
            <img src="/public/assets/static/kop/header mdi kanan.png" :alt="$t('ui.companyLogo')" class="flex items-end justify-between relative w-72" style="margin-left: 340px; margin-right: 0px; top: -24px;"/>
          </div>
        </div>

        <p v-if="isViewing && error" class="rounded-lg bg-red-50 p-1 text-sm text-red-700">
          {{ $ui(error) }}
        </p>
        <!-- Invoice Detail Preview -->
        <div v-if="viewingSale">
          <!-- Customer & Sale Info -->
          <div style="display: grid; grid-template-columns: 2fr 2fr; gap: 185px; margin-bottom: 24px;">
            <div>
              <p style="font-weight: bold; font-size: 16px">{{ $t('ui.to2') }}</p>
              <p v-if="viewingSale.customer_name" style="font-size: 14px; margin-left: 40px; text-decoration-line: underline; font-style: italic;">{{ viewingSale.customer_name }}</p>
              <p v-if="viewingSale.customer_address" style="font-size: 12px; color: #666; margin-left: 40px; margin-top: 2px;">{{ viewingSale.customer_address }}</p>
              <p v-if="viewingSale.customer_phone" style="font-size: 12px; color: #666; margin-left: 40px; margin-top: 2px;">{{ viewingSale.customer_phone }}</p>
              <p v-if="viewingSale.customer_email" style="font-size: 12px; color: #666; margin-left: 40px; margin-top: 2px;">{{ viewingSale.customer_email }}</p>
            </div>
            <div>
              <div class="space-y-1 text-[12px] p-3.5" style="background-color: #f3f4f6; padding-top: 12px;">
                <div class="flex justify-between">
                  <span class="text-gray-500">{{ $t('ui.invoiceNo') }}
                    {{ viewingSale.invoice_number }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">{{ $t('ui.date') }}
                    {{formatDateLong(viewingSale.sale_date,)}}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">{{ $t('ui.referencePo2') }}
                    {{ viewingSale.po_number || "-" }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
            <table class="min-w-full">
              <thead>
                <tr class="bg-blue-300 border-b border-gray-200">
                  <th class="px-5 py-3 text-left text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                    {{ $t('sales.product') }}
                  </th>
                  <th class="px-5 py-3 text-left text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                    {{ $t('projectInvestment.description') }}
                  </th>
                  <th class="px-5 py-3 text-center text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                    {{ $t('sales.quantity') }}
                  </th>
                  <th class="px-5 py-3 text-right text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                    {{ $t('ui.unitPrice') }}
                  </th>
                  <th class="px-5 py-3 text-right text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                    {{ $t('ui.amount') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in viewingSale.items" :key="item.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-4">
                    <span class="text-[11px] font-medium text-indigo-700">
                      {{item.product?.title || $t('ui.unknownProduct')}}
                    </span>
                  </td>
                  <td class="px-5 py-4">
                    <span class="text-[11px] text-gray-600">
                      {{item.description ?? item.product?.brand ?? "—"}}
                    </span>
                    <span v-if="item.product?.sku" class="block text-xs text-gray-400 mt-0.5">SKU: 
                      {{item.product.sku}}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-center text-[11px] text-gray-900">
                    {{ item.quantity }}
                  </td>
                  <td class="px-5 py-4 text-right text-[11px] text-gray-900">Rp. 
                    {{ formatPrice(item.unit_price) }}
                  </td>
                  <td class="px-5 py-4 text-right text-[11px] font-semibold text-gray-900">Rp.
                    {{formatPrice(item.quantity * item.unit_price,)}}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Financial Summary -->
          <div class="flex justify-end">
            <div class="w-72 space-y-2">
              <div class="flex justify-between items-center py-1">
                <span class="text-[12px] font-semibold text-gray-700">{{ $t('sales.subtotal') }}</span>
                <span class="text-[12px] font-bold text-gray-900">Rp.{{ formatPrice(calcViewSubtotal()) }}</span>
              </div>
              <div v-if="viewingSale.discount_amount > 0" class="flex justify-between items-center py-1 text-[12px]">
                <span class="text-gray-500">{{ $t('sales.discount') }}</span>
                <span class="text-red-600">- Rp.{{formatPrice(viewingSale.discount_amount)}}</span>
              </div>
              <div v-if="viewingSale.tax_type"
                class="flex justify-between items-center py-1.5 text-[12px] bg-amber-50 px-3 rounded">
                <span class="text-amber-700 font-medium">{{taxDisplayName(viewingSale.tax_type)}} ({{ viewingSale.tax_rate }}%)</span>
                <span class="text-amber-800 font-semibold">+ Rp.{{formatPrice(viewingSale.tax_amount)}}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-t border-gray-200">
                <span class="text-[14px] font-bold text-gray-900">{{ $t('sales.grandTotal') }}</span>
                <span class="text-[14px] font-bold text-gray-900">Rp.{{formatPrice(viewingSale.grand_total || viewingSale.total_amount,)}}</span>
              </div>
            </div>
          </div>

          <!-- Notes / Payment Terms -->
          <div class="mb-8">
            <p class="text-md font-semibold text-gray-700 mb-2">
              {{ $t('ui.note') }}
            </p>
            <textarea id="invoiceNotes"
              class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:border-blue-400 h-24 text-sm resize-y">
          {{ $t('ui.pleaseMakePaymentWithin30DaysThankYouForYourBusiness') }}</textarea>
          </div>

          <!-- Last Updated -->
          <div class="text-xs text-indigo-600 underline mt-3">
            {{ $t('ui.lastUpdatedBy') }}
            {{ viewingSale.user?.name || $t('ui.system') }} {{ $t('ui.on') }}
            {{ formatDateTimeLong(viewingSale.updated_at) }}
          </div>
        </div>
      </div>
    </div>

    <p v-if="isViewing && error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
      {{ $ui(error) }}
    </p>
    <!-- Invoice Detail Preview -->
    <div v-if="isViewing && viewingSale" class="card p-6 border-l-4 border-indigo-500">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg font-bold text-gray-900">
            {{ viewingSale.invoice_number }}
          </h3>
          <div class="flex items-center gap-3 mt-1">
            <span :class="getStatusBadgeClass(viewingSale.status)">{{ $label(viewingSale.status) }}</span>
            <span class="text-sm text-gray-500">{{
              formatDateLong(viewingSale.sale_date)
            }}</span>
          </div>
        </div>
        <div v-if="viewingSale.delivery"
          class="flex items-center gap-2 px-3 py-1.5 bg-teal-50 border border-teal-200 rounded-lg">
          <span class="text-xs bg-teal-100 text-teal-700 px-2 py-0.5 rounded font-medium">🚚 {{
            viewingSale.delivery.courier
            }}</span>
          <span class="text-sm text-gray-700 font-mono">{{
            viewingSale.delivery.tracking_number
          }}</span>
        </div>
      </div>

      <!-- Customer & Sale Info -->
      <div class="grid grid-cols-2 gap-8 mb-6">
        <div>
          <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
            {{ $t('sales.customer') }}
          </h4>
          <p class="text-base font-semibold text-gray-900">
            {{
              viewingSale.customer?.company ||
              viewingSale.customer_name
            }}
          </p>
          <p v-if="viewingSale.customer?.company" class="text-sm text-gray-600">
            {{ viewingSale.customer_name }}
          </p>
          <p v-if="viewingSale.customer_phone" class="text-sm text-gray-500 mt-1">
            📞 {{ viewingSale.customer_phone }}
          </p>
          <p v-if="viewingSale.customer_email" class="text-sm text-gray-500">
            ✉️ {{ viewingSale.customer_email }}
          </p>
          <p v-if="viewingSale.customer_address" class="text-sm text-gray-500 mt-1">
            📍 {{ viewingSale.customer_address }}
          </p>
        </div>
        <div>
          <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
            {{ $t('ui.saleDetails') }}
          </h4>
          <div class="space-y-1 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">{{ $t('sales.invoice') }}</span>
              <span class="font-medium text-gray-900">{{
                viewingSale.invoice_number
              }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">{{ $t('ui.referencePo') }}</span>
              <span class="font-medium text-gray-900">{{
                viewingSale.po_number || "-"
              }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">{{ $t('sales.date') }}</span>
              <span class="font-medium text-gray-900">{{
                formatDateLong(viewingSale.sale_date)
              }}</span>
            </div>
            <div v-if="viewingSale.warehouse" class="flex justify-between">
              <span class="text-gray-500">{{ $t('nav.warehouses') }}</span>
              <span class="font-medium text-gray-900">{{
                viewingSale.warehouse.name
              }}</span>
            </div>
            <div v-if="viewingSale.sales_person" class="flex justify-between">
              <span class="text-gray-500">{{ $t('labels.salesperson') }}</span>
              <span class="font-medium text-gray-900">{{
                viewingSale.sales_person?.name || "—"
              }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="min-w-full">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ $t('sales.product') }}
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ $t('projectInvestment.description') }}
              </th>
              <th class="px-5 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ $t('sales.quantity') }}
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ $t('ui.unitPrice') }}
              </th>
              <th class="px-5 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ $t('sales.tax') }}
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                {{ $t('ui.amount') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in viewingSale.items" :key="item.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-4">
                <span class="text-sm font-medium text-indigo-700">{{
                  item.product?.title || $t('ui.unknownProduct')
                }}</span>
              </td>
              <td class="px-5 py-4">
                <span class="text-sm text-gray-600">{{
                  item.description ??
                  item.product?.brand ??
                  "—"
                }}</span>
                <span v-if="item.product?.sku" class="block text-xs text-gray-400 mt-0.5">SKU: {{ item.product.sku
                  }}</span>
              </td>
              <td class="px-5 py-4 text-center text-sm text-gray-900">
                {{ item.quantity }}
              </td>
              <td class="px-5 py-4 text-right text-sm text-gray-900">
                Rp. {{ formatPrice(item.unit_price) }}
              </td>
              <td class="px-5 py-4 text-center">
                <span v-if="viewingSale.tax_type"
                  class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded font-medium uppercase">{{
                  viewingSale.tax_type }}</span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-5 py-4 text-right text-sm font-semibold text-gray-900">
                Rp.
                {{
                  formatPrice(item.quantity * item.unit_price)
                }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Financial Summary -->
      <div class="flex justify-end">
        <div class="w-72 space-y-2">
          <div class="flex justify-between items-center py-1">
            <span class="text-sm font-semibold text-gray-700">{{ $t('sales.subtotal') }}</span>
            <span class="text-sm font-bold text-gray-900">Rp. {{ formatPrice(calcViewSubtotal()) }}</span>
          </div>
          <div v-if="viewingSale.discount_amount > 0" class="flex justify-between items-center py-1 text-sm">
            <span class="text-gray-500">{{ $t('sales.discount') }}</span>
            <span class="text-red-600">- Rp.
              {{ formatPrice(viewingSale.discount_amount) }}</span>
          </div>
          <div v-if="viewingSale.tax_type"
            class="flex justify-between items-center py-1.5 text-sm bg-amber-50 px-3 rounded">
            <span class="text-amber-700 font-medium">{{ taxDisplayName(viewingSale.tax_type) }} ({{
              viewingSale.tax_rate
            }}%)</span>
            <span class="text-amber-800 font-semibold">+ Rp.
              {{ formatPrice(viewingSale.tax_amount) }}</span>
          </div>
          <div class="flex justify-between items-center py-2 border-t border-gray-200">
            <span class="text-base font-bold text-gray-900">{{ $t('sales.grandTotal') }}</span>
            <span class="text-base font-bold text-gray-900">Rp.
              {{
                formatPrice(
                  viewingSale.grand_total ||
                  viewingSale.total_amount,
                )
              }}</span>
          </div>
          <div class="flex justify-between items-center py-3 border-t-2 border-gray-300">
            <span class="text-base font-bold text-gray-900">{{ $t('ui.balanceDue') }}</span>
            <span class="text-lg font-bold" :class="viewingSaleBalance > 0
                ? 'text-red-600'
                : 'text-green-600'
              ">
              Rp. {{ formatPrice(viewingSaleBalance) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Last Updated -->
      <div class="text-xs text-indigo-600 underline mt-3">
        {{ $t('ui.lastUpdatedBy') }} {{ viewingSale.user?.name || $t('ui.system') }} {{ $t('ui.on') }}
        {{ formatDateTimeLong(viewingSale.updated_at) }}
      </div>
    </div>

    <form v-if="!isViewing" @submit.prevent="saveSale" class="space-y-6">
      <!-- Top Section: Customer & Email -->
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Customer -->
          <div>
            <label class="block text-sm font-semibold text-indigo-700 mb-1">{{ $t('ui.customer') }}</label>
            <div class="flex gap-2">
              <select v-model="form.customer_id" @change="onCustomerSelect" class="input flex-1">
                <option value="">{{ $t('ui.chooseCustomer') }}</option>
                <option v-for="c in customerList" :key="c.id" :value="c.id">
                  {{
                    c.company
                      ? `${c.company} (${c.name})`
                      : c.name
                  }}
                </option>
              </select>
              <button type="button" @click="showCustomerModal = true"
                class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 text-sm font-medium whitespace-nowrap">
                +
              </button>
            </div>
          </div>
          <!-- Email -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('labels.email') }}</label>
            <input v-model="form.customer_email" type="email" :placeholder="$t('ui.enterEmail')" class="input" />
          </div>
          <!-- Customer Name -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.customerName') }}</label>
            <input v-model="form.customer_name" required class="input" :placeholder="$t('ui.customerName2')" />
          </div>
        </div>
      </div>

      <!-- Middle Section: Address, Dates, References -->
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- Billing Address -->
          <div class="md:row-span-3">
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.billingAddress') }}</label>
            <textarea v-model="form.customer_address" rows="5" class="input"
              :placeholder="$t('ui.eGJalanIndonesiaBlkCNo22')"></textarea>
          </div>
          <!-- Transaction Date -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.transactionDate') }}</label>
            <input v-model="form.sale_date" type="date" required class="input" />
          </div>
          <!-- Transaction No -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.transactionNo') }}</label>
            <input v-model="form.invoice_number" class="input" :placeholder="$t('ui.auto')" />
          </div>
          <!-- Tag / Sales Person -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.referencePoNumber') }}</label>
            <input v-model="form.po_number" maxlength="255" class="input"
              :placeholder="$t('ui.customerPoNumberForTheInvoice')" />
          </div>
          <!-- Sales Person -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('labels.salesperson') }}</label>
            <select v-model="form.sales_person_id" class="input">
              <option value="">{{ $t('sales.none') }}</option>
              <option v-for="sp in salesPeople" :key="sp.id" :value="sp.id">
                {{ sp.name }}
              </option>
            </select>
          </div>
          <!-- Due Date -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.dueDate2') }}</label>
            <input v-model="form.due_date" type="date" class="input" />
          </div>
          <!-- Customer Phone -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('ui.customerPhone') }}</label>
            <input v-model="form.customer_phone" class="input" :placeholder="$t('ui.phoneNumber')" />
          </div>
          <!-- Warehouse -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $t('nav.warehouses') }}</label>
            <select v-model="form.warehouse_id" class="input">
              <option value="">{{ $t('ui.selectWarehouse') }}</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                {{ wh.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Currency & Tax Toggle -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
          <div class="flex items-center gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('ui.currency') }}</label>
              <select class="input w-24 text-sm">
                <option>IDR</option>
              </select>
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" v-model="priceIncludesTax"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            {{ $t('ui.priceIncludesTax') }}
          </label>
        </div>
      </div>

      <!-- Product Line Items Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-indigo-700 uppercase w-1/4">
                  {{ $t('sales.product') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                  {{ $t('projectInvestment.description') }}
                </th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-20">
                  {{ $t('sales.quantity') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-24">
                  {{ $t('projectInvestment.materials.unit') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-700 uppercase w-32">
                  {{ $t('ui.unitPrice2') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-700 uppercase w-28">
                  {{ $t('sales.discount') }}
                </th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-24">
                  {{ $t('sales.tax') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-700 uppercase w-32">
                  {{ $t('ui.amount') }}
                </th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-blue-50/30 transition-colors">
                <td class="px-2 py-3">
                  <select v-model="item.product_id" @change="updatePrice(index)" required class="input text-xs">
                    <option value="">{{ $t('ui.selectProduct2') }}</option>
                    <option v-for="product in filteredProducts" :key="product.id" :value="product.id">
                      {{
                        product.title || product.name
                      }}
                      ({{ product.stock || 0 }})
                    </option>
                  </select>
                </td>
                <td class="px-2 py-3">
                  <input v-model="item.description" type="text" class="input text-xs" :placeholder="$t('projectInvestment.description')" />
                </td>
                <td class="px-2 py-3">
                  <input v-model.number="item.quantity" type="number" min="1" required
                    class="input text-xs text-center" />
                </td>
                <td class="px-2 py-3">
                  <select v-model="item.unit" class="input text-xs">
                    <option value="pcs">{{ $t('ui.pcs') }}</option>
                    <option value="unit">{{ $t('projectInvestment.materials.unit') }}</option>
                    <option value="set">{{ $t('ui.set') }}</option>
                    <option value="box">{{ $t('ui.box') }}</option>
                  </select>
                </td>
                <td class="px-2 py-3">
                  <input v-model.number="item.unit_price" type="number" step="0.01" required
                    class="input text-xs text-right" />
                </td>
                <td class="px-2 py-3">
                  <input v-model.number="item.discount" type="number" step="0.01" class="input text-xs text-right"
                    placeholder="0" />
                </td>
                <td class="px-2 py-3 text-center">
                  <select v-model="item.tax_code" class="input text-xs">
                    <option value="">—</option>
                    <option v-for="tax in taxRates" :key="tax.id" :value="tax.code">
                      {{ tax.name }}
                    </option>
                  </select>
                </td>
                <td class="px-2 py-3 text-right">
                  <span class="text-sm font-semibold text-gray-900">Rp{{
                    formatPrice(getLineAmount(item))
                  }}</span>
                </td>
                <td class="px-2 py-3">
                  <button type="button" @click="removeLineItem(index)"
                    class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
          <button type="button" @click="addLineItem"
            class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
            <span class="text-lg">+</span> {{ $t('ui.addNewLine') }}
          </button>
        </div>
      </div>

      <!-- Bottom Section: Message/Memo + Summary -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left: Message, Memo, Attachments -->
        <div class="space-y-4">
          <div class="card p-5">
            <label class="block text-sm font-semibold text-indigo-700 mb-2">{{ $t('ui.deliveryInformation') }}</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.courier') }}</label>
                <select v-model="deliveryForm.courier" class="input">
                  <option value="">{{ $t('ui.selectCourier') }}</option>
                  <option value="JNE">JNE</option>
                  <option value="J&T">J&T</option>
                  <option value="SiCepat">SiCepat</option>
                  <option value="Ninja Express">
                    Ninja Express
                  </option>
                  <option value="AnterAja">AnterAja</option>
                  <option value="Pickup">{{ $t('ui.pickup') }}</option>
                  <option value="Other">{{ $t('labels.other') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.trackingNumber') }}</label>
                <input v-model="deliveryForm.tracking_number" class="input" :placeholder="$t('deliveries.trackingNumber')" />
              </div>
            </div>
          </div>
          <div class="card p-5">
            <label class="block text-sm font-semibold text-indigo-700 mb-2">{{ $t('ui.message') }}</label>
            <textarea v-model="form.notes" rows="3" class="input" :placeholder="$t('ui.message')"></textarea>
          </div>
          <div class="card p-5">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Memo</label>
            <textarea v-model="form.memo" rows="2" class="input" placeholder="Memo"></textarea>
          </div>
        </div>

        <!-- Right: Financial Summary -->
        <div class="card p-5 space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-sm font-semibold text-indigo-700">{{ $t('sales.subtotal') }}</span>
            <span class="text-sm font-bold text-gray-900">Rp{{ formatPrice(calculateTotal()) }}</span>
          </div>
          <!-- Discount per lines -->
          <div class="flex justify-between items-center text-sm">
            <span class="text-indigo-600">{{ $t('ui.discountPerLines') }}</span>
            <span class="text-indigo-600">Rp{{ formatPrice(calculateLineDiscounts()) }}</span>
          </div>
          <!-- Global Discount -->
          <div class="flex justify-between items-center gap-4">
            <span class="text-sm text-gray-600">{{ $t('sales.discount') }}</span>
            <div class="flex items-center gap-2">
              <select v-model="discountType" class="input w-16 text-sm py-1">
                <option value="%">%</option>
                <option value="fixed">Rp</option>
              </select>
              <input v-model.number="discountValue" type="number" step="0.01" class="input w-24 text-sm text-right py-1" placeholder="0" />
            </div>
            <span class="text-sm text-gray-900">Rp{{formatPrice(calculateGlobalDiscount())}}</span>
          </div>
          <!-- Tax -->
          <div v-if="form.tax_type" class="flex justify-between items-center py-1 text-sm bg-amber-50 px-3 rounded">
            <span class="text-amber-700 font-medium">{{ taxDisplayName(form.tax_type) }} ({{getSelectedTaxRate()}}%)</span>
            <span class="text-amber-800 font-semibold">+ Rp{{ formatPrice(calculateTax()) }}</span>
          </div>
          <div class="flex justify-between items-center gap-2">
            <label class="text-sm text-gray-600">{{ $t('ui.tax') }}</label>
            <select v-model="form.tax_type" class="input w-48 text-sm py-1">
              <option value="">{{ $t('sales.noTax') }}</option>
              <option v-for="tax in taxRates" :key="tax.id" :value="tax.code">
                {{ tax.name }} ({{ tax.rate }}%)
              </option>
            </select>
          </div>
          <div class="border-t border-gray-200 pt-3">
            <div class="flex justify-between items-center">
              <span class="text-base font-bold text-gray-900">{{ $t('sales.total') }}</span>
              <span class="text-base font-bold text-gray-900">Rp{{formatPrice(calculateGrandTotal())}}</span>
            </div>
          </div>
          <!-- Deposit checkbox -->
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" v-model="hasDeposit" class="rounded border-gray-300 text-indigo-600" />
            {{ $t('labels.deposit') }}
          </label>
          <div v-if="hasDeposit">
            <input v-model.number="depositAmount" type="number" step="0.01" class="input text-sm" :placeholder="$t('ui.depositAmount')" />
          </div>
          <div class="border-t-2 border-gray-300 pt-3">
            <div class="flex justify-between items-center">
              <span class="text-base font-bold text-indigo-700">{{ $t('ui.balanceDue') }}</span>
              <span class="text-lg font-bold text-indigo-700">Rp{{formatPrice(calculateBalanceDue())}}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg text-sm border border-red-200">
        {{ $ui(error) }}
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between items-center pb-6">
        <button v-if="isEditing && viewingSale" type="button" @click="deleteSale" class="text-sm text-red-500 hover:text-red-700 font-medium transition-colors">
          {{ $t('ui.deleteSale') }}
        </button>
        <div class="flex items-center gap-3 ml-auto">
          <button type="button" @click="$router.push({ name: 'Sales' })" class="btn-secondary px-6">
            {{ $t('common.cancel') }}
          </button>
          <button type="submit" :disabled="saving" class="px-8 py-3 bg-linear-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium disabled:opacity-50">
            {{saving ? $t('sales.saving') : isEditing ? $t('ui.update') : $t('ui.create')}}
          </button>
        </div>
      </div>
    </form>

    <!-- Quick Add Customer Modal -->
    <div v-if="showCustomerModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-bold mb-4 bg-linear-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          {{ $t('ui.quickAddCustomer') }}
        </h3>
        <form @submit.prevent="saveNewCustomer" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.customerName') }}</label>
            <input v-model="customerForm.name" required class="input" :placeholder="$t('ui.contactPersonName')" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.company') }}</label>
            <input v-model="customerForm.company" class="input" :placeholder="$t('ui.companyName')" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.phone') }}</label>
              <input v-model="customerForm.phone" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.email') }}</label>
              <input v-model="customerForm.email" type="email" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.address') }}</label>
            <textarea v-model="customerForm.address" rows="2" class="input"></textarea>
          </div>
          <div v-if="customerError" class="bg-red-50 text-red-600 p-2 rounded text-sm">
            {{ customerError }}
          </div>
          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showCustomerModal = false" class="btn-secondary">
              {{ $t('common.cancel') }}
            </button>
            <button type="submit" :disabled="savingCustomer" class="btn-primary">
              {{ savingCustomer ? $t('sales.saving') : $t('ui.addCustomer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Delivery Modal -->
    <div v-if="showDeliveryModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-teal-600">
          {{ $t('deliveries.createDelivery') }}
        </h3>

        <form @submit.prevent="createDelivery" class="space-y-4">
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-700">
              {{ $t('ui.saleInformation') }}
            </p>
            <p class="text-sm text-gray-600">{{ $t('ui.invoice') }}
              <span class="font-semibold">{{viewingSale?.invoice_number}}</span>
            </p>
            <p class="text-sm text-gray-600">{{ $t('ui.customer2') }}
              <span class="font-semibold">{{viewingSale?.customer_name}}</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.courier') }}</label>
            <select v-model="deliveryModalForm.courier" required class="input">
              <option value="">{{ $t('ui.selectCourier') }}</option>
              <option value="JNE">JNE</option>
              <option value="J&T">J&T</option>
              <option value="SiCepat">SiCepat</option>
              <option value="Ninja Express">Ninja Express</option>
              <option value="AnterAja">AnterAja</option>
              <option value="Pickup">{{ $t('ui.pickup') }}</option>
              <option value="Other">{{ $t('labels.other') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('deliveries.trackingNumber') }}</label>
            <input v-model="deliveryModalForm.tracking_number" class="input" :placeholder="$t('ui.leaveEmptyToAutoGenerate')" />
            <p class="text-xs text-gray-500 mt-1">
              {{ $t('ui.willBeAutoGeneratedIfLeftEmpty') }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="deliveryModalForm.notes" rows="3" class="input"></textarea>
          </div>

          <div v-if="deliveryError" class="bg-red-50 text-red-600 p-2 rounded text-sm">
            {{ deliveryError }}
          </div>

          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="showDeliveryModal = false" class="btn-secondary">
              {{ $t('common.cancel') }}
            </button>
            <button type="submit" :disabled="savingDelivery" class="btn-primary">
              {{savingDelivery ? $t('ui.creating') : $t('deliveries.createDelivery')}}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import api from "../../services/api";

const router = useRouter();
const route = useRoute();

const isEditing = computed(() => !!route.params.id);
const isViewing = computed(() => route.name === "SalesView");
const printing = ref(false);

// Data lists
const products = ref([]);
const salesPeople = ref([]);
const warehouses = ref([]);
const customerList = ref([]);
const taxRates = ref([]);

// UI state
const saving = ref(false);
const error = ref("");
const showCustomerModal = ref(false);
const savingCustomer = ref(false);
const customerError = ref("");
const priceIncludesTax = ref(false);
const hasDeposit = ref(false);
const depositAmount = ref(0);
const discountType = ref("%");
const discountValue = ref(0);

// Delivery modal state
const showDeliveryModal = ref(false);
const savingDelivery = ref(false);
const deliveryError = ref("");

// View sale state (for invoice preview when editing)
const viewingSale = ref(null);

// Form
const form = ref({
  invoice_number: "",
  po_number: "",
  customer_id: "",
  customer_name: "",
  customer_email: "",
  customer_phone: "",
  customer_address: "",
  sales_person_id: "",
  warehouse_id: "",
  sale_date: new Date().toISOString().split("T")[0],
  due_date: "",
  status: "pending",
  notes: "",
  memo: "",
  tax_type: "",
  discount_amount: 0,
  items: [
    {
      product_id: "",
      quantity: 1,
      unit_price: 0,
      description: "",
      unit: "pcs",
      discount: 0,
      tax_code: "",
    },
  ],
});

const customerForm = ref({
  name: "",
  company: "",
  phone: "",
  email: "",
  address: "",
  npwp: "",
});

// Delivery form in the main form (for new sales)
const deliveryForm = ref({ courier: "", tracking_number: "" });

// Separate delivery form for the modal (when adding delivery to existing sale)
const deliveryModalForm = ref({ courier: "", tracking_number: "", notes: "" });

const filteredProducts = computed(() => products.value);

// Helpers
const formatPrice = (price) =>
  new Intl.NumberFormat(uiLocale()).format(price || 0);

const taxDisplayName = (code) => {
  const names = { ppn: "PPN", pph23: "PPh 23" };
  return names[code] || code?.toUpperCase() || "";
};

const formatDateLong = (date) => {
  if (!date) return "—";
  return new Date(date).toLocaleDateString(uiLocale(), {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatDateTimeLong = (date) => {
  if (!date) return "—";
  return new Date(date).toLocaleDateString(uiLocale(), {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: "badge-warning",
    completed: "badge-success",
    cancelled: "badge-danger",
  };
  return classes[status] || "badge";
};

const getLineAmount = (item) => {
  const subtotal = (item.quantity || 0) * (item.unit_price || 0);
  return subtotal - (item.discount || 0);
};

const calculateTotal = () => {
  if (!form.value.items) return 0;
  return form.value.items.reduce(
    (sum, item) => sum + (item.quantity || 0) * (item.unit_price || 0),
    0,
  );
};

const calculateLineDiscounts = () => {
  if (!form.value.items) return 0;
  return form.value.items.reduce(
    (sum, item) => sum + (item.discount || 0),
    0,
  );
};

const calculateGlobalDiscount = () => {
  if (!discountValue.value) return 0;
  if (discountType.value === "%") {
    return Math.round(calculateTotal() * (discountValue.value / 100));
  }
  return discountValue.value;
};

const getSelectedTaxRate = () => {
  if (!form.value.tax_type) return 0;
  const tax = taxRates.value.find((t) => t.code === form.value.tax_type);
  return tax ? parseFloat(tax.rate) : 0;
};

const calculateTax = () => {
  const subtotal =
    calculateTotal() - calculateLineDiscounts() - calculateGlobalDiscount();
  const rate = getSelectedTaxRate();
  return Math.round(subtotal * (rate / 100));
};

const calculateGrandTotal = () => {
  const subtotal = calculateTotal();
  const lineDisc = calculateLineDiscounts();
  const globalDisc = calculateGlobalDiscount();
  const tax = calculateTax();
  return subtotal - lineDisc - globalDisc + tax;
};

const calculateBalanceDue = () => {
  return (
    calculateGrandTotal() -
    (hasDeposit.value ? depositAmount.value || 0 : 0)
  );
};

const calcViewSubtotal = () => {
  if (!viewingSale.value?.items) return 0;
  return viewingSale.value.items.reduce(
    (sum, item) => sum + item.quantity * item.unit_price,
    0,
  );
};

const viewingSaleBalance = computed(() => {
  if (!viewingSale.value) return 0;
  const total = Number(
    viewingSale.value.grand_total || viewingSale.value.total_amount || 0,
  );
  const paid = (viewingSale.value.payments || [])
    .filter((p) => p.status === "paid")
    .reduce((sum, p) => sum + Number(p.amount), 0);
  return Math.max(0, total - paid);
});

// Actions
const onCustomerSelect = () => {
  const cid = form.value.customer_id;
  if (!cid) return;
  const customer = customerList.value.find((c) => c.id == cid);
  if (customer) {
    form.value.customer_name = customer.name;
    form.value.customer_phone = customer.phone || "";
    form.value.customer_email = customer.email || "";
    form.value.customer_address = customer.address || "";
  }
};

const addLineItem = () => {
  form.value.items.push({
    product_id: "",
    quantity: 1,
    unit_price: 0,
    description: "",
    unit: "pcs",
    discount: 0,
    tax_code: "",
  });
};

const removeLineItem = (index) => {
  if (form.value.items.length > 1) form.value.items.splice(index, 1);
};

const updatePrice = (index) => {
  const product = products.value.find(
    (p) => p.id == form.value.items[index].product_id,
  );
  if (product) {
    form.value.items[index].unit_price = product.price;
    form.value.items[index].description = product.brand || "";
  }
};

const saveSale = async () => {
  if (isViewing.value) return;
  saving.value = true;
  error.value = "";
  try {
    const payload = {
      ...form.value,
      discount_amount:
        calculateLineDiscounts() + calculateGlobalDiscount(),
      delivery_info:
        isEditing.value ||
          deliveryForm.value.courier ||
          deliveryForm.value.tracking_number
          ? deliveryForm.value
          : null,
    };
    if (isEditing.value) {
      await api.put(`/sales/${route.params.id}`, payload);
    } else {
      await api.post("/sales", payload);
    }
    router.push({ name: 'Sales' });
  } catch (err) {
    error.value =
      Object.values(err.response?.data?.errors || {}).flat()[0] ||
      err.response?.data?.message ||
      uiText("Gagal menyimpan penjualan");
  } finally {
    saving.value = false;
  }
};

const deleteSale = async () => {
  if (!confirm(uiText("Yakin ingin menghapus penjualan ini?"))) return;
  try {
    await api.delete(`/sales/${route.params.id}`);
    router.push({ name: 'Sales' });
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus penjualan"));
  }
};

const saveNewCustomer = async () => {
  savingCustomer.value = true;
  customerError.value = "";
  try {
    const response = await api.post("/customers", customerForm.value);
    customerList.value.push(response.data);
    form.value.customer_id = response.data.id;
    onCustomerSelect();
    showCustomerModal.value = false;
    customerForm.value = {
      name: "",
      company: "",
      phone: "",
      email: "",
      address: "",
      npwp: "",
    };
  } catch (err) {
    customerError.value =
      err.response?.data?.message || uiText("Gagal menambahkan pelanggan");
  } finally {
    savingCustomer.value = false;
  }
};

const openDeliveryModal = () => {
  deliveryModalForm.value = { courier: "", tracking_number: "", notes: "" };
  deliveryError.value = "";
  showDeliveryModal.value = true;
};

const createDelivery = async () => {
  savingDelivery.value = true;
  deliveryError.value = "";
  try {
    const payload = {
      sale_id: viewingSale.value.id,
      courier: deliveryModalForm.value.courier,
      notes: deliveryModalForm.value.notes,
    };
    if (deliveryModalForm.value.tracking_number) {
      payload.tracking_number = deliveryModalForm.value.tracking_number;
    }
    const response = await api.post("/deliveries", payload);
    showDeliveryModal.value = false;
    // Reload the sale to show updated delivery info
    await loadSale();
    alert(
      uiText('ui.shipmentCreatedTracking', { p0: response.data.tracking_number }),
    );
  } catch (err) {
    deliveryError.value =
      err.response?.data?.message || uiText("Gagal membuat pengiriman");
  } finally {
    savingDelivery.value = false;
  }
};

const exportExcel = async (saleId, invoiceNumber) => {
  try {
    const response = await api.get(`/sales/${saleId}/export`, {
      responseType: "blob",
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `invoice_${invoiceNumber}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert(uiText("Gagal mengekspor berkas Excel"));
  }
};

const downloadPdf = async (saleId, invoiceNumber) => {
  try {
    const response = await api.get(`/sales/${saleId}/pdf`, {
      responseType: "blob",
    });
    const url = window.URL.createObjectURL(
      new Blob([response.data], { type: "application/pdf" }),
    );
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `invoice_${invoiceNumber}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert(uiText("Gagal mengunduh berkas PDF"));
  }
};

const printPdf = async (saleId, invoiceNumber) => {
  const printWindow = window.open("", "_blank");
  if (!printWindow) {
    alert(uiText("Izinkan jendela pop-up peramban untuk mencetak faktur PDF."));
    return;
  }
  printing.value = true;
  printWindow.document.title = uiText('ui.invoiceNumber', { p0: invoiceNumber });
  printWindow.document.body.textContent = uiText("Memuat tampilan cetak faktur...");
  try {
    const response = await api.get(`/sales/${saleId}/print`, {
      responseType: "text",
      headers: { Accept: "text/html" },
    });
    if (printWindow.closed) return;
    const doc = printWindow.document;
    doc.open();
    doc.write(response.data);
    doc.close();
    doc.title = uiText('ui.invoiceNumber', { p0: invoiceNumber });
    const style = doc.createElement("style");
    style.textContent =
      "@page { size: A4; margin: 12mm; } img { max-width: 100%; height: auto; } @media print { .invoice-print-toolbar { display: none !important; } body { margin: 0; background: white !important; } }";
    doc.head.appendChild(style);
    // Print the displayed values instead of editable inputs in the invoice template.
    doc.querySelectorAll("input, textarea, select").forEach((input) => {
      const text = doc.createElement("span");
      text.textContent =
        input.tagName === "SELECT"
          ? input.selectedOptions[0]?.textContent || ""
          : input.value;
      text.className = input.className;
      input.replaceWith(text);
    });
    const toolbar = doc.createElement("div");
    toolbar.className = "invoice-print-toolbar";
    toolbar.style.cssText =
      "padding:12px;margin-bottom:16px;background:#f3f4f6;text-align:right";
    const button = doc.createElement("button");
    button.type = "button";
    button.textContent = uiText("Cetak Faktur");
    button.onclick = () => {
      printWindow.focus();
      printWindow.print();
    };
    toolbar.appendChild(button);
    doc.body.prepend(toolbar);
    await Promise.all(
      Array.from(doc.images).map((image) =>
        image.complete
          ? Promise.resolve()
          : new Promise((resolve) => {
            image.addEventListener("load", resolve, {
              once: true,
            });
            image.addEventListener("error", resolve, {
              once: true,
            });
          }),
      ),
    );
    if (doc.fonts) await doc.fonts.ready;
    if (!printWindow.closed) {
      printWindow.focus();
      printWindow.print();
    }
  } catch (err) {
    printWindow.close();
    alert(
      err.response?.data?.message ||
      uiText("Gagal membuka tampilan cetak faktur."),
    );
  } finally {
    printing.value = false;
  }
};

// Load data
const loadProducts = async () => {
  try {
    const whResponse = await api.get("/warehouses", {
      params: { per_page: 200 },
    });
    const allWarehouses = whResponse.data.data || whResponse.data || [];
    const defaultWarehouse = allWarehouses.find((w) => w.is_default);
    if (defaultWarehouse) {
      const response = await api.get("/stock", {
        params: { warehouse_id: defaultWarehouse.id, per_page: 1000 },
      });
      const stocks = response.data.data || response.data || [];
      products.value = stocks
        .filter((s) => s.product)
        .map((s) => ({ ...s.product, stock: s.quantity || 0 }));
    } else {
      const response = await api.get("/products", {
        params: { per_page: 1000 },
      });
      products.value = response.data.data;
    }
  } catch (err) {
    console.error(err);
  }
};

const loadSale = async () => {
  if (!route.params.id) return;
  try {
    const response = await api.get(`/sales/${route.params.id}`);
    const sale = response.data;
    // Store full sale data for invoice preview
    viewingSale.value = sale;
    deliveryForm.value = {
      courier: sale.delivery?.courier || "",
      tracking_number: sale.delivery?.tracking_number || "",
    };
    for (const item of sale.items || []) {
      if (
        item.product &&
        !products.value.some(
          (product) => product.id === item.product.id,
        )
      )
        products.value.push(item.product);
    }
    // Populate edit form
    form.value = {
      invoice_number: sale.invoice_number,
      po_number: sale.po_number || "",
      customer_id: sale.customer_id || "",
      customer_name: sale.customer_name,
      customer_email: sale.customer_email || "",
      customer_phone: sale.customer_phone || "",
      customer_address: sale.customer_address || "",
      sales_person_id: sale.sales_person_id || "",
      warehouse_id: sale.warehouse_id || "",
      sale_date: sale.sale_date?.slice(0, 10) || "",
      due_date: sale.due_date?.slice(0, 10) || "",
      status: sale.status,
      notes: sale.notes || "",
      memo: sale.memo || "",
      tax_type: sale.tax_type || "",
      discount_amount: sale.discount_amount || 0,
      items: (sale.items || []).map((item) => ({
        id: item.id,
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: parseFloat(item.unit_price),
        description: item.description ?? item.product?.brand ?? "",
        unit: item.unit || "pcs",
        discount: Number(item.discount || 0),
        tax_code: item.tax_code || "",
      })),
    };
    discountType.value = "fixed";
    discountValue.value = Math.max(
      0,
      Number(sale.discount_amount || 0) -
      form.value.items.reduce((sum, item) => sum + item.discount, 0),
    );
    if (!form.value.items.length) {
      form.value.items = [
        {
          product_id: "",
          quantity: 1,
          unit_price: 0,
          description: "",
          unit: "pcs",
          discount: 0,
          tax_code: "",
        },
      ];
    }
  } catch (err) {
    console.error(err);
    error.value = uiText("Gagal memuat data penjualan");
  }
};

onMounted(async () => {
  if (isViewing.value) {
    await loadSale();
    return;
  }
  await Promise.all([
    loadProducts(),
    api
      .get("/sales-people")
      .then((r) => (salesPeople.value = r.data))
      .catch(() => { }),
    api
      .get("/warehouses", { params: { per_page: 200 } })
      .then((r) => (warehouses.value = r.data.data || r.data || []))
      .catch(() => { }),
    api
      .get("/customers", { params: { all: true } })
      .then((r) => (customerList.value = r.data))
      .catch(() => { }),
    api
      .get("/tax-rates/active")
      .then((r) => (taxRates.value = r.data))
      .catch(() => {
        taxRates.value = [
          { id: 1, name: "PPN", code: "ppn", rate: "11.00" },
          { id: 2, name: "PPh 23", code: "pph23", rate: "2.00" },
        ];
      }),
  ]);
  if (isEditing.value) await loadSale();
});
</script>

<style>
body {
  font-family: Arial, sans-serif;
  font-size: 14px;
  line-height: 1.6;
  color: #333;
}

.header {
  border-bottom: 2px solid #4f46e5;
}

.header h1 {
  color: #4f46e5;
  margin: 0;
  font-size: 28px;
}

.header p {
  margin: 5px 0;
  color: #666;
}

.invoice-info {
  margin-bottom: 30px;
}

.invoice-info table {
  width: 100%;
}

.invoice-info td {
  padding: 5px 0;
}

.invoice-info .label {
  font-weight: bold;
  width: 150px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.items-table th {
  background-color: #4f46e5;
  color: white;
  padding: 12px;
  text-align: left;
  font-weight: bold;
}

.items-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #ddd;
}

.items-table tr:nth-child(even) {
  background-color: #f9f9f9;
}

.text-right {
  text-align: right;
}

.total-section {
  margin-top: 30px;
  text-align: right;
}

.total-row {
  margin: 5px 0;
  font-size: 16px;
}

.total-row.grand-total {
  font-size: 20px;
  font-weight: bold;
  color: #4f46e5;
  padding-top: 10px;
  border-top: 2px solid #4f46e5;
}

.footer {
  margin-top: 50px;
  text-align: center;
  font-size: 12px;
  color: #666;
  border-top: 1px solid #ddd;
  padding-top: 20px;
}
</style>
