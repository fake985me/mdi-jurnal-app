<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ $t('assetPage.title') }}</h2>
                <p class="text-gray-500 text-sm mt-1">{{ $t('assetPage.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" @click="openModal" class="btn-primary whitespace-nowrap">
                    + {{ $t('ui.addNewAsset') }}
                </button>
                <p class="text-xs text-gray-500 max-w-sm">{{ $t('ui.asetDibuatOtomatisDariPembelianDenganOpsiPurchaseForAsset') }}</p>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="key in ['count','cost','depreciation','book_value']" :key="key" class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $t(`assetPage.${key}`) }}</p><p class="mt-3 text-2xl font-semibold text-slate-900">{{ loading ? '…' : key === 'count' ? assets.summary?.count || 0 : formatCurrency(assets.summary?.[key]) }}</p><p class="mt-2 text-xs text-slate-400">{{ $t('assetPage.filtered') }}</p>
            </div>
        </div>
        <div class="flex overflow-x-auto border-b border-slate-200 bg-white rounded-t-xl">
            <button v-for="status in ['', 'active', 'in_use', 'maintenance', 'disposed']" :key="status" @click="filters.status = status; loadAssets()" :aria-pressed="filters.status === status" :class="['whitespace-nowrap px-5 py-4 border-b-2 text-sm font-medium', filters.status === status ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500']">{{ status ? formatStatus(status) : $t('assetPage.all') }}</button>
        </div>
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input v-model="filters.search" @input="loadAssets()" type="text" :placeholder="$t('ui.searchAssets')"
                    class="input" />
                <select v-model="filters.category" @change="loadAssets()" class="input">
                    <option value="">{{ $t('ui.allCategories') }}</option>
                    <option v-for="cat in filterOptions.categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <select v-model="filters.location" @change="loadAssets()" class="input">
                    <option value="">{{ $t('ui.allLocations') }}</option>
                    <option v-for="loc in filterOptions.locations" :key="loc" :value="loc">{{ loc }}</option>
                </select>
                <select v-model="filters.condition" @change="loadAssets()" class="input">
                    <option value="">{{ $t('ui.allConditions') }}</option>
                    <option value="good">{{ $t('labels.good') }}</option>
                    <option value="fair">{{ $t('labels.fair') }}</option>
                    <option value="poor">{{ $t('labels.poor') }}</option>
                    <option value="damaged">{{ $t('labels.defective') }}</option>
                </select>
                <select v-model="filters.status" @change="loadAssets()" class="input">
                    <option value="">{{ $t('sales.allStatus') }}</option>
                    <option value="active">{{ $t('labels.active') }}</option>
                    <option value="in_use">{{ $t('labels.in_use') }}</option>
                    <option value="maintenance">{{ $t('labels.maintenance') }}</option>
                    <option value="disposed">{{ $t('labels.disposed') }}</option>
                </select>
            </div>
            <div class="mt-3 flex justify-between items-center"><button @click="resetFilters" class="text-sm text-blue-600">{{ $t('assetPage.reset') }}</button><select v-model.number="filters.per_page" @change="loadAssets()" :aria-label="$t('ui.rows')" class="input !w-auto"><option :value="15">15</option><option :value="25">25</option><option :value="50">50</option></select></div>
        </div>

        <!-- Assets Table -->
        <div class="table-wrapper">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.assets') }}</h3>
            </div>

            <div v-if="loadError" role="alert" class="p-8 text-center text-red-600">{{ loadError }} <button @click="loadAssets()" class="underline">{{ $t('assetPage.retry') }}</button></div>
            <div v-else-if="loading" class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto"></div>
                <p class="text-gray-600 mt-2">{{ $t('ui.loadingAssets') }}</p>
            </div>

            <div v-else-if="!assets.data?.length" class="p-8 text-center text-gray-500">
                {{ $t('assetPage.empty') }}
            </div>

            <div v-else class="overflow-x-auto"><table class="min-w-full whitespace-nowrap">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.assetCode') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('dashboard.category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.projectLocation') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.condition') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('assetPage.cost') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('assetPage.depreciation') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('assetPage.book_value') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.source') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium"><button @click="viewAsset(asset.id)" class="text-blue-600 hover:underline">{{ asset.asset_code }}</button></td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ asset.name }}</div>
                            <div class="text-sm text-gray-500">{{ asset.brand }} {{ asset.model }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ asset.category || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ asset.location || '-' }}</td>
                        <td class="px-6 py-4">
                            <span :class="conditionClass(asset.condition)">
                                {{ formatCondition(asset.condition) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="statusClass(asset.status)">
                                {{ formatStatus(asset.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-right">{{ formatCurrency(asset.purchase_price) }}</td>
                        <td class="px-6 py-4 text-sm text-right">{{ formatCurrency(asset.accumulated_depreciation) }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-right">{{ formatCurrency(asset.status === 'disposed' ? 0 : asset.current_value) }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div v-if="asset.purchase_item?.purchase" class="text-emerald-600">
                                {{ asset.purchase_item.purchase.po_number }}
                            </div>
                            <div v-else class="text-gray-400">{{ $t('assetPage.manual') }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <button @click="viewAsset(asset.id)" class="text-blue-600">{{ $t('assetPage.detail') }}</button>
                            <button v-if="asset.status !== 'disposed'" @click="editWithHistory(asset.id)" class="text-indigo-600 hover:text-indigo-900">{{ $t('common.edit') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table></div>

            <!-- Pagination -->
            <div v-if="assets.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
                <p class="text-sm text-gray-700">
                    {{ $t('sales.showing') }} {{ assets.from }} {{ $t('ui.to') }} {{ assets.to }} {{ $t('sales.of') }} {{ assets.total }} {{ $t('ui.assets2') }}
                </p>
                <div class="flex space-x-2">
                    <button @click="loadAssets(assets.current_page - 1)" :disabled="!assets.prev_page_url"
                        class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
                    <button @click="loadAssets(assets.current_page + 1)" :disabled="!assets.next_page_url"
                        class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
                </div>
            </div>
        </div>

        <Dialog :open="!!selectedAsset" @close="closeDetail" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40" aria-hidden="true" />
            <div class="fixed inset-0 overflow-y-auto p-4 sm:p-8"><DialogPanel v-if="selectedAsset" class="mx-auto max-w-4xl rounded-xl bg-white p-6 shadow-xl">
                <div class="flex justify-between items-start gap-4 mb-5"><div><p class="text-sm text-blue-600">{{ selectedAsset.asset_code }}</p><DialogTitle class="text-xl font-semibold mt-1">{{ selectedAsset.name }}</DialogTitle><p class="text-sm text-slate-500 mt-1">{{ selectedAsset.brand }} {{ selectedAsset.model }}</p></div><button @click="closeDetail" :disabled="actionBusy" :aria-label="$t('assetPage.close')" class="text-xl">×</button></div>
                <p v-if="actionError" role="alert" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 whitespace-pre-line">{{ actionError }}</p>
                <p v-if="actionMessage" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ actionMessage }}</p>
                <div class="grid sm:grid-cols-3 gap-3 mb-5"><div v-for="[key,label] in [['purchase_price','cost'],['accumulated_depreciation','depreciation'],['current_value','book_value']]" :key="key" class="bg-slate-50 rounded-lg p-4"><p class="text-xs text-slate-500">{{ $t(`assetPage.${label}`) }}</p><p class="mt-2 font-semibold">{{ formatCurrency(key === 'current_value' && selectedAsset.status === 'disposed' ? 0 : selectedAsset[key]) }}</p></div></div>
                <dl class="grid sm:grid-cols-2 gap-4 text-sm mb-6">
                    <div><dt class="text-slate-500">{{ $t('ui.serialNumber') }}</dt><dd>{{ selectedAsset.serial_number || '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('projectInvestment.materials.status') }}</dt><dd>{{ formatStatus(selectedAsset.status) }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('dashboard.category') }}</dt><dd>{{ selectedAsset.category || '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('projectInvestment.projectLocation') }}</dt><dd>{{ selectedAsset.location || '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('ui.assignedTo') }}</dt><dd>{{ selectedAsset.assigned_to || '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('ui.purchaseDate') }}</dt><dd>{{ displayDate(selectedAsset.purchase_date) }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('assetPage.method') }}</dt><dd>{{ $t(`assetPage.${selectedAsset.depreciation_method || 'none'}`) }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('assetPage.life') }}</dt><dd>{{ selectedAsset.useful_life_months || '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('assetPage.start') }}</dt><dd>{{ displayDate(selectedAsset.depreciation_start_date) }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('assetPage.residual') }}</dt><dd>{{ formatCurrency(selectedAsset.residual_value) }}</dd></div>
                    <div><dt class="text-slate-500">{{ $t('ui.source') }}</dt><dd>{{ selectedAsset.purchase_item?.purchase?.po_number || $t('assetPage.manual') }}</dd></div>
                </dl>
                <p v-if="selectedAsset.notes" class="text-sm text-slate-600 whitespace-pre-wrap mb-5">{{ selectedAsset.notes }}</p>
                <h4 class="font-semibold mb-3">{{ $t('assetPage.history') }}</h4>
                <div v-if="selectedAsset.depreciations?.length" class="overflow-x-auto mb-5"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-3 text-left">{{ $t('assetPage.period') }}</th><th class="p-3 text-right">{{ $t('assetPage.amount') }}</th><th class="p-3 text-right">{{ $t('assetPage.book_value') }}</th></tr></thead><tbody><tr v-for="entry in selectedAsset.depreciations" :key="entry.id" class="border-b"><td class="p-3">{{ displayDate(entry.period) }}</td><td class="p-3 text-right">{{ formatCurrency(entry.amount) }}</td><td class="p-3 text-right">{{ formatCurrency(entry.book_value_after) }}</td></tr></tbody></table></div>
                <p v-else class="text-sm text-slate-400 mb-5">{{ $t('assetPage.noHistory') }}</p>
                <div v-if="selectedAsset.disposal_date" class="rounded-lg bg-slate-50 p-4 text-sm space-y-2 mb-5"><p>{{ $t('assetPage.disposeDate') }}: {{ displayDate(selectedAsset.disposal_date) }}</p><p>{{ $t('assetPage.bookAtDisposal') }}: {{ formatCurrency(selectedAsset.disposal_book_value) }}</p><p>{{ $t('assetPage.proceeds') }}: {{ formatCurrency(selectedAsset.disposal_proceeds) }}</p><p class="font-semibold">{{ $t('assetPage.gainLoss') }}: {{ formatCurrency(Number(selectedAsset.disposal_proceeds) - Number(selectedAsset.disposal_book_value)) }}</p><p class="whitespace-pre-wrap">{{ selectedAsset.disposal_reason }}</p></div>
                <div v-if="selectedAsset.status !== 'disposed'" class="flex flex-wrap gap-3 border-t pt-4">
                    <button @click="editFromDetail" :disabled="actionBusy" class="btn-secondary">{{ $t('common.edit') }}</button>
                    <button v-if="!selectedAsset.depreciations?.length" @click="deleteAsset(selectedAsset.id)" :disabled="actionBusy" class="btn-secondary text-red-600">{{ $t('common.delete') }}</button>
                    <button v-if="selectedAsset.depreciation_method === 'straight_line'" @click="actionMode = 'depreciate'" :disabled="actionBusy" class="btn-primary">{{ $t('assetPage.post') }}</button>
                    <button @click="actionMode = 'dispose'" :disabled="actionBusy" class="btn-secondary text-red-600">{{ $t('assetPage.dispose') }}</button>
                </div>
                <form v-if="actionMode === 'depreciate'" @submit.prevent="postDepreciation" class="mt-5 rounded-lg bg-blue-50 p-4 space-y-3"><p class="text-sm text-blue-800">{{ $t('assetPage.policy') }}</p><label class="block text-sm">{{ $t('assetPage.asOf') }}<input v-model="asOf" type="date" :max="today()" required class="input mt-1" /></label><button type="submit" :disabled="actionBusy" class="btn-primary">{{ actionBusy ? $t('sales.saving') : $t('assetPage.post') }}</button></form>
                <form v-if="actionMode === 'dispose'" @submit.prevent="disposeAsset" class="mt-5 rounded-lg bg-amber-50 p-4 space-y-3"><label class="block text-sm">{{ $t('assetPage.disposeDate') }}<input v-model="disposal.disposal_date" type="date" :max="today()" :min="selectedAsset.purchase_date?.slice(0,10)" required class="input mt-1" /></label><label class="block text-sm">{{ $t('assetPage.proceeds') }}<input v-model.number="disposal.disposal_proceeds" type="number" min="0" max="9999999999.99" step="0.01" required class="input mt-1" /></label><label class="block text-sm">{{ $t('assetPage.reason') }}<textarea v-model="disposal.disposal_reason" required maxlength="2000" class="input mt-1" /></label><p class="text-xs text-amber-800">{{ $t('assetPage.ledgerNote') }}</p><button type="submit" :disabled="actionBusy" class="btn-primary">{{ actionBusy ? $t('sales.saving') : $t('assetPage.saveDisposal') }}</button></form>
            </DialogPanel></div>
        </Dialog>

        <!-- Asset Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ editingAsset ? $t('ui.editAsset') : $t('ui.addNewAsset') }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>

                <form @submit.prevent="saveAsset" class="space-y-6">
                    <p v-if="saveError" role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 whitespace-pre-line">{{ saveError }}</p>
                    <!-- Source from Purchase -->
                    <div class="bg-emerald-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-emerald-700">{{ $t('ui.sourceOptional') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.fromPurchaseItem') }}</label>
                                <select v-model="form.purchase_item_id" :disabled="financialLocked" @change="onPurchaseItemChange" class="input">
                                    <option value="">{{ $t('ui.selectPurchaseItem') }}</option>
                                    <option v-for="item in filterOptions.purchase_items" :key="item.id" :value="item.id">
                                        {{ $ui(item.label) }} {{ $t('ui.qty') }} {{ item.quantity }})
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">{{ $t('ui.pilihUntukAutoFillDataDariPembelian') }}</p>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('sales.product') }}</label>
                                    <button type="button" @click="showProductModal = true" 
                                        class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                                        {{ $t('ui.addNewProduct') }}
                                    </button>
                                </div>
                                <select v-model="form.product_id" class="input">
                                    <option value="">{{ $t('ui.selectProduct') }}</option>
                                    <option v-for="product in filterOptions.products" :key="product.id" :value="product.id">
                                        {{ product.title || product.sku }} {{ product.title && product.sku ? `(${product.sku})` : '' }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Total: {{ filterOptions.products?.length || 0 }} {{ $t('ui.productsAvailable') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">{{ $t('ui.basicInformation') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.assetName') }}</label>
                                <input v-model="form.name" required class="input" :placeholder="$t('ui.eGLaptopDellLatitude')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.serialNumber') }}</label>
                                <input v-model="form.serial_number" class="input" placeholder="S/N" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('dashboard.category') }}</label>
                                <input v-model="form.category" class="input" :placeholder="$t('ui.electronicsFurnitureEtc')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.brand') }}</label>
                                <input v-model="form.brand" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                <input v-model="form.model" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- Location & Status -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">{{ $t('ui.locationStatus') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.projectLocation') }}</label>
                                <input v-model="form.location" class="input" :placeholder="$t('ui.officeWarehouseEtc')" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.condition') }}</label>
                                <select v-model="form.condition" class="input">
                                    <option value="good">{{ $t('labels.good') }}</option>
                                    <option value="fair">{{ $t('labels.fair') }}</option>
                                    <option value="poor">{{ $t('labels.poor') }}</option>
                                    <option value="damaged">{{ $t('labels.defective') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.materials.status') }}</label>
                                <select v-model="form.status" class="input">
                                    <option value="active">{{ $t('labels.active') }}</option>
                                    <option value="in_use">{{ $t('labels.in_use') }}</option>
                                    <option value="maintenance">{{ $t('labels.maintenance') }}</option>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.assignedTo') }}</label>
                                <input v-model="form.assigned_to" class="input" :placeholder="$t('ui.personOrDepartment')" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 space-y-4">
                        <label class="block text-sm font-medium">{{ $t('assetPage.method') }}<select v-model="form.depreciation_method" :disabled="financialLocked" class="input mt-1"><option value="none">{{ $t('assetPage.none') }}</option><option value="straight_line">{{ $t('assetPage.straight_line') }}</option></select></label>
                        <div v-if="form.depreciation_method === 'straight_line'" class="grid sm:grid-cols-3 gap-4"><label class="text-sm">{{ $t('assetPage.life') }}<input v-model.number="form.useful_life_months" type="number" min="1" max="600" required :disabled="financialLocked" class="input mt-1" /></label><label class="text-sm">{{ $t('assetPage.start') }}<input v-model="form.depreciation_start_date" type="date" :min="form.purchase_date" required :disabled="financialLocked" class="input mt-1" /></label><label class="text-sm">{{ $t('assetPage.residual') }}<input v-model.number="form.residual_value" type="number" min="0" :max="form.purchase_price" step="0.01" required :disabled="financialLocked" class="input mt-1" /></label></div>
                        <p class="text-xs text-blue-800">{{ $t(financialLocked ? 'assetPage.locked' : 'assetPage.policy') }}</p>
                    </div>
                    <!-- Financial Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold mb-3 text-gray-700">{{ $t('ui.financialInformation') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.purchaseDate') }}</label>
                                <input v-model="form.purchase_date" :disabled="financialLocked" type="date" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.purchasePrice') }}</label>
                                <input v-model.number="form.purchase_price" @input="syncPurchasePrice" :disabled="financialLocked" type="number" min="0" max="9999999999.99" step="0.01" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.currentValue') }}</label>
                                <input v-model.number="form.current_value" :disabled="form.depreciation_method === 'straight_line'" type="number" min="0" step="0.01" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.warrantyEndDate') }}</label>
                                <input v-model="form.warranty_end_date" type="date" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
                        <textarea v-model="form.notes" rows="3" class="input"></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
                        <button type="submit" :disabled="saving" class="btn-primary">
                            {{ saving ? $t('sales.saving') : $t('ui.saveAsset') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Add Product Modal -->
        <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4"
            @click.self="showProductModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">{{ $t('ui.addNewAssetProduct') }}</h3>
                    <button @click="showProductModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-700">
                        <strong>{{ $t('ui.note') }}</strong> {{ $t('ui.produkYangDibuatDiSiniAkanDitandaiSebagaiAssetProductDan') }}
                        <strong>{{ $t('ui.tidakAkanDihitungSebagaiStockPenjualan') }}</strong>.
                    </p>
                </div>

                <form @submit.prevent="saveAssetProduct" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.productName') }}</label>
                        <input v-model="productForm.title" required class="input" :placeholder="$t('ui.eGDellLatitude5520')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.skuOptional') }}</label>
                            <input v-model="productForm.sku" class="input" :placeholder="$t('ui.autoGeneratedIfEmpty')" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('dashboard.category') }}</label>
                            <input v-model="productForm.category" class="input" :placeholder="$t('ui.electronics')" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.brand') }}</label>
                        <input v-model="productForm.brand" class="input" :placeholder="$t('ui.dellHpEtc')" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.description') }}</label>
                        <textarea v-model="productForm.descriptions" rows="2" class="input" :placeholder="$t('ui.optionalProductDescription')"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showProductModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
                        <button type="submit" :disabled="savingProduct" class="btn-primary">
                            {{ savingProduct ? $t('ui.creating') : $t('ui.createProduct') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { displayDate, uiText, uiLocale } from '../../utils/uiText';

import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import api from '../../services/api';

const assets = ref({ data: [] });
const filterOptions = ref({
    categories: [],
    locations: [],
    products: [],
    purchase_items: [],
});
const loading = ref(true);
const showModal = ref(false);
const showProductModal = ref(false);
const editingAsset = ref(null);
const saving = ref(false);
const saveError = ref('');
const savingProduct = ref(false);

const filters = ref({
    search: '',
    category: '',
    location: '',
    condition: '',
    status: '',
    per_page: 15,
});

const form = ref({
    name: '',
    product_id: '',
    purchase_item_id: '',
    category: '',
    brand: '',
    model: '',
    serial_number: '',
    location: '',
    condition: 'good',
    status: 'active',
    purchase_date: '',
    depreciation_method: 'none',
    depreciation_start_date: '',
    useful_life_months: 60,
    residual_value: 0,
    purchase_price: 0,
    current_value: 0,
    assigned_to: '',
    warranty_end_date: '',
    notes: '',
    image: '',
});

const productForm = ref({
    title: '',
    sku: '',
    category: '',
    brand: '',
    descriptions: '',
});

const loadFilterOptions = async () => {
    try {
        const response = await api.get('/assets/filter-options');
        filterOptions.value = response.data;
    } catch (error) {
        console.error('Gagal memuat pilihan filter:', error);
    }
};

const loadAssets = async (page = 1) => {
    const id = ++listRequest;
    loadError.value = '';
    loading.value = true;
    try {
        const params = { page, ...filters.value };
        const response = await api.get('/assets', { params });
        if (id === listRequest) assets.value = response.data;
    } catch (error) {
        if (id === listRequest) loadError.value = uiText('assetPage.loadError');
    } finally {
        if (id === listRequest) loading.value = false;
    }
};

const openModal = () => {
    saveError.value = '';
    editingAsset.value = null;
    form.value = {
        name: '',
        product_id: '',
        purchase_item_id: '',
        category: '',
        brand: '',
        model: '',
        serial_number: '',
        location: '',
        condition: 'good',
        status: 'active',
        purchase_date: '',
        depreciation_method: 'none',
        depreciation_start_date: '',
        useful_life_months: 60,
        residual_value: 0,
        purchase_price: 0,
        current_value: 0,
        assigned_to: '',
        warranty_end_date: '',
        notes: '',
        image: '',
    };
    showModal.value = true;
};

const editAsset = (asset) => {
    saveError.value = '';
    editingAsset.value = asset;
    form.value = {
        name: asset.name || '',
        product_id: asset.product_id || '',
        purchase_item_id: asset.purchase_item_id || '',
        category: asset.category || '',
        brand: asset.brand || '',
        model: asset.model || '',
        serial_number: asset.serial_number || '',
        location: asset.location || '',
        condition: asset.condition || 'good',
        status: asset.status || 'active',
        purchase_date: asset.purchase_date?.split('T')[0] || '',
        depreciation_method: asset.depreciation_method || 'none',
        depreciation_start_date: asset.depreciation_start_date?.slice(0,10) || '',
        useful_life_months: asset.useful_life_months || 60,
        residual_value: Number(asset.residual_value || 0),
        purchase_price: asset.purchase_price || 0,
        current_value: asset.current_value || 0,
        assigned_to: asset.assigned_to || '',
        warranty_end_date: asset.warranty_end_date?.split('T')[0] || '',
        notes: asset.notes || '',
        image: asset.image || '',
    };
    showModal.value = true;
};

const onPurchaseItemChange = () => {
    const item = filterOptions.value.purchase_items.find(item => String(item.id) === String(form.value.purchase_item_id));
    if (!item || financialLocked.value) return;
    Object.assign(form.value, { name: form.value.name || item.product_title, product_id: item.product_id,
        purchase_price: Number(item.unit_price), current_value: Number(item.unit_price),
        purchase_date: item.purchase_date?.slice(0,10) || '', depreciation_start_date: item.purchase_date?.slice(0,10) || '' });
};

const saveAsset = async () => {
    saving.value = true;
    saveError.value = '';
    try {
        const data = { ...form.value };
        // Clean empty strings to null
        Object.keys(data).forEach(key => {
            if (data[key] === '') data[key] = null;
        });

        if (editingAsset.value) {
            await api.put(`/assets/${editingAsset.value.id}`, data);
        } else {
            await api.post('/assets', data);
        }
        showModal.value = false;
        loadAssets();
        loadFilterOptions(); // Refresh filter options
    } catch (error) {
        console.error('Gagal menyimpan aset:', error);
        saveError.value = error.response?.data?.errors
            ? Object.values(error.response.data.errors).flat().join('\n')
            : error.response?.data?.message || `${uiText('ui.failedToSaveAsset')} ${uiText('Kesalahan tidak diketahui')}`;
    } finally {
        saving.value = false;
    }
};

const saveAssetProduct = async () => {
    savingProduct.value = true;
    try {
        const response = await api.post('/assets/create-product', productForm.value);
        
        // Add new product to dropdown and select it
        const newProduct = response.data.product;
        filterOptions.value.products.push(newProduct);
        form.value.product_id = newProduct.id;
        
        // Auto-fill some fields
        form.value.category = form.value.category || newProduct.category;
        form.value.brand = form.value.brand || newProduct.brand;
        
        // Reset and close
        productForm.value = { title: '', sku: '', category: '', brand: '', descriptions: '' };
        showProductModal.value = false;
        
        alert(uiText("Produk berhasil dibuat!"));
    } catch (error) {
        console.error('Gagal membuat produk:', error);
        alert('Gagal membuat produk: ' + (error.response?.data?.message || uiText("Kesalahan tidak diketahui")));
    } finally {
        savingProduct.value = false;
    }
};

const deleteAsset = async (id) => {
    if (!confirm(uiText("Yakin ingin menghapus aset ini?"))) return;
    try {
        await api.delete(`/assets/${id}`);
        selectedAsset.value = null;
        await Promise.all([loadAssets(), loadFilterOptions()]);
    } catch (error) {
        console.error('Gagal menghapus aset:', error);
        actionError.value = error.response?.data?.message || uiText('assetPage.actionError');
    }
};

const loadError = ref('');
let listRequest = 0;
const selectedAsset = ref(null), actionMode = ref(''), actionError = ref(''), actionMessage = ref(''), actionBusy = ref(false);
const today = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); };
const asOf = ref(today());
const disposal = ref({ disposal_date: today(), disposal_proceeds: 0, disposal_reason: '' });
const financialLocked = computed(() => Boolean(editingAsset.value?.depreciations?.length));
const resetFilters = () => { Object.assign(filters.value, { search: '', category: '', location: '', condition: '', status: '' }); loadAssets(); };
const syncPurchasePrice = () => { if (!editingAsset.value || form.value.depreciation_method === 'straight_line') form.value.current_value = form.value.purchase_price; };
const errorMessage = error => error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join('\n') : error.response?.data?.message || uiText('assetPage.actionError');
const closeDetail = () => { if (!actionBusy.value) selectedAsset.value = null; };
const viewAsset = async id => {
    try {
        selectedAsset.value = (await api.get(`/assets/${id}`)).data;
        actionMode.value = ''; actionError.value = ''; actionMessage.value = ''; asOf.value = today();
        disposal.value = { disposal_date: today(), disposal_proceeds: 0, disposal_reason: '' };
    } catch(error) { loadError.value = errorMessage(error); }
};
const editWithHistory = async id => {
    try { editAsset((await api.get(`/assets/${id}`)).data); }
    catch(error) { loadError.value = errorMessage(error); }
};
const editFromDetail = () => { const asset = selectedAsset.value; selectedAsset.value = null; editAsset(asset); };
const postDepreciation = async () => {
    actionBusy.value = true; actionError.value = ''; actionMessage.value = '';
    try {
        const response = await api.post(`/assets/${selectedAsset.value.id}/depreciate`, { as_of: asOf.value });
        selectedAsset.value = response.data.asset;
        actionMessage.value = uiText('assetPage.postResult', { count: response.data.posted });
        await loadAssets();
    } catch(error) { actionError.value = errorMessage(error); }
    finally { actionBusy.value = false; }
};
const disposeAsset = async () => {
    if (!confirm(uiText('assetPage.confirmDispose', { name: selectedAsset.value.name }))) return;
    actionBusy.value = true; actionError.value = ''; actionMessage.value = '';
    try {
        selectedAsset.value = (await api.post(`/assets/${selectedAsset.value.id}/dispose`, disposal.value)).data;
        actionMode.value = ''; await loadAssets();
    } catch(error) { actionError.value = errorMessage(error); }
    finally { actionBusy.value = false; }
};

// Formatting helpers
const formatCurrency = (value) => {
    return new Intl.NumberFormat(uiLocale(), {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const formatCondition = (condition) => {
    const labels = { good: uiText("Baik"), fair: uiText("Cukup Baik"), poor: uiText("Kurang Baik"), damaged: uiText("Rusak") };
    return labels[condition] || condition;
};

const formatStatus = (status) => {
    const labels = { active: uiText("Aktif"), in_use: uiText("Digunakan"), maintenance: uiText("Pemeliharaan"), disposed: uiText("Dihapuskan") };
    return labels[status] || status;
};

const conditionClass = (condition) => {
    const classes = {
        good: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800',
        fair: 'px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800',
        poor: 'px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800',
        damaged: 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800',
    };
    return classes[condition] || 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800';
};

const statusClass = (status) => {
    const classes = {
        active: 'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800',
        in_use: 'px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800',
        maintenance: 'px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800',
        disposed: 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800',
    };
    return classes[status] || 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800';
};

onBeforeUnmount(() => { ++listRequest; });

onMounted(() => {
    loadFilterOptions();
    loadAssets();
});
</script>
