<template>
    <div class="space-y-6" :class="{ 'products-form-tab': formPage }">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2
                    class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                    Product Management</h2>
                <p class="text-sm text-gray-600 mt-1">Manage your product catalog, stock levels, and categories</p>
            </div>
            <div class="flex gap-2">
                <!-- Excel Buttons -->
                <button v-if="activeTab === 'products'" @click="downloadExcel"
                    class="px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </button>
                <button v-if="activeTab === 'products'" @click="downloadTemplate"
                    class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Template
                </button>
                <label v-if="activeTab === 'products'"
                    class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg hover:from-amber-600 hover:to-amber-700 cursor-pointer transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Import
                    <input type="file" @change="uploadExcel" accept=".xlsx,.xls,.csv" class="hidden" ref="fileInput" />
                </label>
                <!-- Sync Semua ke Publik -->
                <button v-if="activeTab === 'products'" @click="syncAllToPublic"
                    :disabled="syncAllLoading"
                    class="px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-medium disabled:opacity-50">
                    <svg v-if="syncAllLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ syncAllLoading ? 'Syncing...' : 'Sync Semua ke Publik' }}
                </button>
                <button v-if="activeTab === 'products'" @click="openProductForm()"
                    class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium text-sm">
                    + Add Product
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'products'" :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                    activeTab === 'products'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]">
                    Products
                </button>
                <button @click="activeTab = 'categories'; loadCategories()" :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                    activeTab === 'categories'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]">
                    Categories
                </button>
                <button @click="activeTab = 'adjustments'; loadAdjustments()" :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                    activeTab === 'adjustments'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]">
                    Stock Adjustments
                </button>
            </nav>
        </div>

        <!-- Products Tab Content -->
        <div v-if="activeTab === 'products'">
            <!-- Search & Filter -->
            <div class="card p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <input v-model="filters.search" @input="loadProducts" type="text"
                        placeholder="Search by title, SKU, brand..." class="input" />
                    <select v-model="filters.category_id" @change="onFilterCategoryChange" class="input">
                        <option value="">All Categories</option>
                        <option v-for="cat in categoryOptions" :key="cat.id" :value="cat.id">{{ cat.label }}</option>
                    </select>
                    <select v-model="filters.sub_category_id" @change="loadProducts" :disabled="!filters.category_id"
                        class="input disabled:bg-gray-100 disabled:cursor-not-allowed">
                        <option value="">All Subcategories</option>
                        <option v-for="sub in getSubcategoryOptions(filters.category_id)" :key="sub.id" :value="sub.id">
                            {{ sub.label }}
                        </option>
                    </select>
                    <select v-model="filters.brand" @change="loadProducts" class="input">
                        <option value="">All Brands</option>
                        <option v-for="brand in brands" :key="brand" :value="brand">{{ brand }}</option>
                    </select>
                    <button @click="resetFilters"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium text-sm">
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-wrapper">
                <div class="card-header flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Product List</h3>
                        <span v-if="products.total" class="text-sm text-gray-500">{{ products.total }} products</span>
                    </div>
                    <!-- Bulk Sync Bar -->
                    <div class="flex items-center gap-3">
                        <Transition name="fade">
                            <div v-if="selectedProducts.length > 0" class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-1.5">
                                <span class="text-sm text-indigo-700 font-medium">{{ selectedProducts.length }} dipilih</span>
                                <button @click="bulkSyncToPublic"
                                    :disabled="bulkSyncing"
                                    class="px-3 py-1 text-xs font-semibold bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-1.5">
                                    <svg v-if="bulkSyncing" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ bulkSyncing ? 'Syncing...' : 'Sync ke Publik' }}
                                </button>
                                <button @click="selectedProducts = []" class="text-indigo-400 hover:text-indigo-600 text-xs">
                                    Batal
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>

                <div v-if="loading" class="p-12 text-center">
                    <div class="inline-flex items-center gap-2 text-gray-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                fill="none" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Loading products...
                    </div>
                </div>

                <table v-else class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <!-- Checkbox select all -->
                            <th class="px-4 py-3 text-left w-10">
                                <input type="checkbox"
                                    :checked="isAllSelected"
                                    :indeterminate="isSomeSelected"
                                    @change="toggleSelectAll"
                                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 cursor-pointer"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(product, index) in products.data" :key="product.id"
                            class="hover:bg-gray-50 transition-colors duration-150">
                            <!-- Checkbox -->
                            <td class="px-4 py-4 w-10">
                                <input type="checkbox"
                                    :value="product.id"
                                    v-model="selectedProducts"
                                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 cursor-pointer"
                                />
                            </td>
                            <!-- No = id -->
                            <td class="px-4 py-4 text-sm font-mono text-gray-700 font-semibold">{{ product.id }}</td>
                            <!-- Image -->
                            <td class="px-4 py-4">
                                <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-200 overflow-hidden flex items-center justify-center shrink-0">
                                    <img v-if="product.image" :src="product.image" :alt="product.title" class="w-full h-full object-contain" @error="(e) => e.target.style.display = 'none'" />
                                    <svg v-else class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </td>
                            <!-- SKU -->
                            <td class="px-4 py-4">
                                <span v-if="product.sku"
                                    class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{
                                        product.sku }}</span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </td>
                            <!-- Produk = title -->
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ product.title }}</div>
                                <div v-if="product.subtitle" class="text-xs text-indigo-600 font-medium mt-0.5">{{ product.subtitle }}</div>
                                <div v-else-if="product.descriptions" class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">
                                    {{ product.descriptions }}</div>
                            </td>
                            <!-- Kategori = category, subcategory -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <template v-if="product.category_pairs && product.category_pairs.length > 0">
                                        <span v-for="(pair, idx) in product.category_pairs" :key="idx"
                                            class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ getCategoryPath(pair.category_id) || pair.category_name || '-' }}
                                            <span v-if="pair.sub_category_id || pair.sub_category_name"
                                                class="ml-1 text-indigo-500">
                                                / {{ getSubcategoryPath(pair.sub_category_id) || pair.sub_category_name
                                                    || '-' }}
                                            </span>
                                        </span>
                                    </template>
                                    <template v-else-if="product.category">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                                            {{ product.category }}
                                            <span v-if="product.sub_category" class="ml-1 text-gray-500">
                                                / {{ product.sub_category }}
                                            </span>
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-400 text-sm">—</span>
                                </div>
                            </td>
                            <!-- Brand -->
                            <td class="px-4 py-4 text-sm text-gray-700">{{ product.brand || '—' }}</td>
                            <!-- Stock -->
                            <td class="px-4 py-4 text-center">
                                <span :class="[
                                    'inline-flex items-center justify-center min-w-[2.5rem] px-2.5 py-1 text-xs font-semibold rounded-full',
                                    (product.stock || 0) <= 0 ? 'bg-red-100 text-red-700' :
                                        (product.stock || 0) <= (product.minimum_stock || 0) ? 'bg-amber-100 text-amber-700' :
                                            'bg-green-100 text-green-700'
                                ]">
                                    {{ product.stock || 0 }}
                                </span>
                            </td>
                            <!-- Aksi -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openAdjustModal(product)"
                                        class="px-2 py-1 text-xs font-medium text-amber-700 bg-amber-50 rounded hover:bg-amber-100 transition-colors duration-150"
                                        title="Stock Adjustment">
                                        Adjust
                                    </button>
                                    <button @click="openStockModal(product)"
                                        class="px-2 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 rounded hover:bg-emerald-100 transition-colors duration-150"
                                        title="View Warehouse Stock">
                                        Stock
                                    </button>
                                    <button @click="editProduct(product)"
                                        class="px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded hover:bg-indigo-100 transition-colors duration-150"
                                        title="Edit Product">
                                        Edit
                                    </button>
                                    <!-- Sync to Public -->
                                    <button @click="syncProductToPublic(product)"
                                        :disabled="syncingId === product.id"
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1',
                                            product.public_product_id ? 'text-blue-700 bg-blue-50 hover:bg-blue-100' : 'text-purple-700 bg-purple-50 hover:bg-purple-100'
                                        ]"
                                        :title="product.public_product_id ? 'Update data di website' : 'Publikasikan ke website'">
                                        <svg v-if="syncingId === product.id" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        {{ syncingId === product.id ? '...' : (product.public_product_id ? 'Update' : 'Publis') }}
                                    </button>
                                    <button @click="deleteProduct(product.id)"
                                        class="px-2 py-1 text-xs font-medium text-red-700 bg-red-50 rounded hover:bg-red-100 transition-colors duration-150"
                                        title="Delete Product">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!products.data?.length">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                No products found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="products.data?.length"
                    class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
                    <p class="text-sm text-gray-700">
                        Showing {{ products.from }} to {{ products.to }} of {{ products.total }} products
                    </p>
                    <div class="flex space-x-2">
                        <button @click="loadProducts(products.current_page - 1)" :disabled="!products.prev_page_url"
                            class="btn-secondary disabled:opacity-50">Previous</button>
                        <button @click="loadProducts(products.current_page + 1)" :disabled="!products.next_page_url"
                            class="btn-secondary disabled:opacity-50">Next</button>
                    </div>
                </div>
            </div>
        </div><!-- End Products Tab Content -->

        <!-- Product Form Modal -->
        <div v-if="showModal" class="product-form-tab tab-form-page"
            :class="formPage ? 'max-w-6xl mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'"
            @click.self="cancelProductForm">
            <div class="bg-white rounded-xl p-6 w-full max-w-5xl max-h-[90vh] overflow-y-auto shadow-2xl"
                :class="formPage ? 'max-h-none' : ''">
                <div class="flex justify-between items-center mb-6">
                    <h3
                        class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        {{ editingProduct ? 'Edit Product' : 'Add New Product' }}
                    </h3>
                    <button @click="cancelProductForm"
                        class="text-gray-400 hover:text-gray-600 text-3xl leading-none transition-colors">&times;</button>
                </div>
                <!-- Modal Tabs -->
                <div class="flex border-b border-gray-200 mb-6 gap-2">
                    <button type="button" @click="formModalTab = 'basic'"
                        :class="['px-4 py-2 text-sm font-semibold border-b-2 transition-all', formModalTab === 'basic' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        Basic & Stock
                    </button>
                    <button type="button" @click="formModalTab = 'specs'"
                        :class="['px-4 py-2 text-sm font-semibold border-b-2 transition-all', formModalTab === 'specs' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        Spesifikasi (1-20)
                    </button>
                    <button type="button" @click="formModalTab = 'features'"
                        :class="['px-4 py-2 text-sm font-semibold border-b-2 transition-all', formModalTab === 'features' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        Fitur (1-20)
                    </button>
                    <button type="button" @click="formModalTab = 'tech'"
                        :class="['px-4 py-2 text-sm font-semibold border-b-2 transition-all', formModalTab === 'tech' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                        Technical Specs & Diagram
                    </button>
                </div>

                <form @submit.prevent="saveProduct" class="space-y-6">
                    <!-- Tab 1: Basic Information & Stock & Categories -->
                    <div v-show="formModalTab === 'basic'" class="space-y-6">
                        <div class="bg-blue-50 p-5 rounded-lg">
                            <h4 class="text-base font-semibold mb-4 text-gray-800">Basic Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                    <input v-model="form.title" type="text" required class="input"
                                        placeholder="Product name" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                                    <input v-model="form.subtitle" type="text" class="input" placeholder="e.g. Subtitle or series" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                                    <input v-model="form.sku" type="text" class="input" placeholder="e.g. XG-OLT.001" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                    <input v-model="form.brand" type="text" class="input" placeholder="e.g. DASAN" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                                    <input v-model="form.module" type="text" class="input" placeholder="e.g. A" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (Rp)</label>
                                    <input v-model.number="form.price" type="number" min="0" step="0.01" class="input"
                                        placeholder="0" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Warranty (Months)</label>
                                    <input v-model.number="form.warranty_period_months" type="number" min="0" step="1"
                                        class="input" placeholder="12" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                                    <input v-model="form.image" type="url" class="input" placeholder="https://..." />
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea v-model="form.descriptions" rows="2" class="input"
                                        placeholder="Optional product description..."></textarea>
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input v-model="form.is_asset" type="checkbox"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                        <span class="text-sm font-medium text-gray-700">Is Asset</span>
                                        <span class="text-xs text-gray-400">(not for sale)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Categories Section -->
                        <div class="bg-purple-50 p-5 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h4 class="text-base font-semibold text-gray-800">Categories & Subcategories</h4>
                                    <p class="text-xs text-gray-500">Mendukung multi-kategori dan multi-subkategori</p>
                                </div>
                                <button type="button" @click="addCategoryPair"
                                    class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                                    + Add Category
                                </button>
                            </div>

                            <div v-if="form.categories.length === 0"
                                class="text-gray-500 text-sm py-3 text-center border-2 border-dashed border-gray-200 rounded-lg">
                                No categories added. Click "+ Add Category" to add one.
                            </div>

                            <div class="space-y-3">
                                <div v-for="(catPair, index) in form.categories" :key="index"
                                    class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 shadow-sm">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                                        <select v-model="catPair.category_id" @change="onCategoryChange(index)"
                                            class="input text-sm">
                                            <option value="">Select Category</option>
                                            <option v-for="cat in categoryOptions" :key="cat.id" :value="cat.id">
                                                {{ cat.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Subcategory</label>
                                        <select v-model="catPair.sub_category_id" class="input text-sm"
                                            :disabled="!catPair.category_id">
                                            <option value="">Select Subcategory</option>
                                            <option v-for="sub in getSubcategoryOptions(catPair.category_id)" :key="sub.id"
                                                :value="sub.id">
                                                {{ sub.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <button type="button" @click="removeCategoryPair(index)"
                                        class="mt-5 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-150">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Management -->
                        <div class="bg-green-50 p-5 rounded-lg">
                            <h4 class="text-base font-semibold mb-4 text-gray-800">Stock Management</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Stock</label>
                                    <input v-model.number="form.stock" type="number" min="0" class="input" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock (Alert)</label>
                                    <input v-model.number="form.minimum_stock" type="number" min="0" class="input" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Specifications 1 to 20 -->
                    <div v-show="formModalTab === 'specs'" class="space-y-4">
                        <div class="bg-amber-50 p-5 rounded-lg">
                            <h4 class="text-base font-semibold mb-2 text-gray-800">Product Specifications (spec1 s/d spec20)</h4>
                            <p class="text-xs text-gray-600 mb-4">Input baris spesifikasi produk (menampilkan ringkasan spesifikasi di kartu / detail produk)</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="i in 20" :key="'spec' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Spec {{ i }}</label>
                                    <input v-model="form['spec' + i]" type="text" class="input text-sm" :placeholder="`Spec line ${i}...`" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Features 1 to 20 -->
                    <div v-show="formModalTab === 'features'" class="space-y-4">
                        <div class="bg-indigo-50 p-5 rounded-lg">
                            <h4 class="text-base font-semibold mb-2 text-gray-800">Product Features (fitur1 s/d fitur20)</h4>
                            <p class="text-xs text-gray-600 mb-4">Daftar keunggulan / fitur utama produk</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="i in 20" :key="'fitur' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Fitur {{ i }}</label>
                                    <input v-model="form['fitur' + i]" type="text" class="input text-sm" :placeholder="`Fitur line ${i}...`" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 4: Technical Specs & Diagrams -->
                    <div v-show="formModalTab === 'tech'" class="space-y-6">
                        <!-- Wireless & Controller -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-gray-800 text-sm mb-3">Wireless & Controller</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Wireless Standard</label>
                                    <input v-model="form.wireless_standard" type="text" class="input text-sm" placeholder="e.g. 802.11ax" />
                                </div>
                                <div v-for="i in 5" :key="'ws' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Wireless Stream {{ i }}</label>
                                    <input v-model="form['wireless_stream' + i]" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Manageable APs</label>
                                    <input v-model="form.manageable_aps" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">AP Number</label>
                                    <input v-model="form.ap_number" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Number of Clients</label>
                                    <input v-model="form.number_of_clients" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Capacity</label>
                                    <input v-model="form.capacity" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">IP Rating</label>
                                    <input v-model="form.ip_rating" type="text" class="input text-sm" placeholder="e.g. IP67" />
                                </div>
                            </div>
                        </div>

                        <!-- Switch & Memory -->
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-gray-800 text-sm mb-3">Switch & Memory</h5>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Switching Capacity</label>
                                    <input v-model="form.switching" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Throughput</label>
                                    <input v-model="form.throughput" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Flash Memory</label>
                                    <input v-model="form.flash_memory" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">SDRAM Memory</label>
                                    <input v-model="form.sdram_memory" type="text" class="input text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Interface & Control Unit -->
                        <div class="bg-teal-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-gray-800 text-sm mb-3">Interfaces & Control Unit</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Interface Main</label>
                                    <input v-model="form.interface_main" type="text" class="input text-sm" />
                                </div>
                                <div v-for="i in 5" :key="'iface' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Interface {{ i }}</label>
                                    <input v-model="form['interface' + i]" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Antenna</label>
                                    <input v-model="form.antenna" type="text" class="input text-sm" />
                                </div>
                                <div v-for="i in 5" :key="'cu' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Control Unit {{ i }} (cu{{ i }})</label>
                                    <input v-model="form['cu' + i]" type="text" class="input text-sm" />
                                </div>
                                <div v-for="i in 5" :key="'addif' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Additional Interface {{ i }}</label>
                                    <input v-model="form['additional_interface' + i]" type="text" class="input text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Network & Environment -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-gray-800 text-sm mb-3">Network & Environment</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">MAC Address</label>
                                    <input v-model="form.mac_address" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Routing Table</label>
                                    <input v-model="form.routing_table" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Dustproof / Waterproof</label>
                                    <input v-model="form.dustproof_waterproof" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Noise</label>
                                    <input v-model="form.noise" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">MTBF</label>
                                    <input v-model="form.mtbf" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Operating Temp</label>
                                    <input v-model="form.operating_temperature" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Storage Temp</label>
                                    <input v-model="form.storage_temperature" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Operating Humidity</label>
                                    <input v-model="form.operating_humidity" type="text" class="input text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Power Specs -->
                        <div class="bg-amber-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-gray-800 text-sm mb-3">Power & Physical Specifications</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div v-for="i in 5" :key="'pwr' + i">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Power {{ i }}</label>
                                    <input v-model="form['power' + i]" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Power Consumptions</label>
                                    <input v-model="form.power_consumptions" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Power Consumption (with PoE)</label>
                                    <input v-model="form.power_consumption_with_poe" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Power Consumption (Excl PoE)</label>
                                    <input v-model="form.power_consumption_excluding_poe" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">PoE Budget</label>
                                    <input v-model="form.poe_budget" type="text" class="input text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Dimensions</label>
                                    <input v-model="form.dimensions" type="text" class="input text-sm" placeholder="e.g. 440 x 220 x 44mm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Diagram (Multi: pisahkan |)</label>
                                    <input v-model="form.diagram" type="text" class="input text-sm" placeholder="e.g. xgspon_olt | gpon_olt" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Network Diagram (Multi: pisahkan |)</label>
                                    <input v-model="form.network_diagram" type="text" class="input text-sm" placeholder="e.g. gpon | xgspon" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" @click="cancelProductForm"
                            class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
                            {{ saving ? 'Saving...' : (editingProduct ? 'Update Product' : 'Save Product') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Adjustments Tab Content -->
        <div v-if="activeTab === 'adjustments'">
            <div class="table-wrapper">
                <div class="card-header">
                    <h3 class="text-lg font-semibold text-gray-900">Stock Adjustments</h3>
                </div>

                <div v-if="adjustmentsLoading" class="p-12 text-center">
                    <div class="inline-flex items-center gap-2 text-gray-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                fill="none" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Loading adjustments...
                    </div>
                </div>

                <table v-else class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Before → After
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="adj in adjustments.data" :key="adj.id"
                            class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ adj.adjustment_code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ adj.product?.title || '-' }}</td>
                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full',
                                    adj.adjustment_type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]">
                                    {{ adj.adjustment_type === 'in' ? '+ IN' : '- OUT' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm capitalize text-gray-700">{{ adj.reason?.replace(/_/g, ' ') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-center">{{ adj.quantity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ adj.before_qty }} → {{ adj.after_qty }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(adj.created_at).toLocaleDateString()
                            }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ adj.user?.name || '-' }}</td>
                        </tr>
                        <tr v-if="!adjustments.data?.length">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                No adjustments found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="adjustments.data?.length"
                    class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
                    <p class="text-sm text-gray-700">
                        Showing {{ adjustments.from }} to {{ adjustments.to }} of {{ adjustments.total }} adjustments
                    </p>
                    <div class="flex space-x-2">
                        <button @click="loadAdjustments(adjustments.current_page - 1)"
                            :disabled="!adjustments.prev_page_url"
                            class="btn-secondary disabled:opacity-50">Previous</button>
                        <button @click="loadAdjustments(adjustments.current_page + 1)"
                            :disabled="!adjustments.next_page_url"
                            class="btn-secondary disabled:opacity-50">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjustment Modal -->
        <div v-if="showAdjustModal" class="tab-form-page"
            :class="formPage ? 'max-w-4xl mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'"
            @click.self="closeProductSubview">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl" :class="formPage ? 'max-w-4xl' : ''">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Stock Adjustment</h3>
                    <button @click="closeProductSubview"
                        class="text-gray-400 hover:text-gray-600 text-2xl leading-none transition-colors">&times;</button>
                </div>

                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <p class="font-medium text-gray-900">{{ adjustingProduct?.title }}</p>
                    <p class="text-sm text-gray-600">Current Stock: <span class="font-semibold text-indigo-600">{{
                        adjustingProduct?.stock || 0 }}</span></p>
                </div>

                <form @submit.prevent="submitAdjustment" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                            <select v-model="adjustForm.adjustment_type" required class="input">
                                <option value="in">Stock In (+)</option>
                                <option value="out">Stock Out (-)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                            <input v-model.number="adjustForm.quantity" type="number" min="1" required class="input" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                        <select v-model="adjustForm.reason" required class="input">
                            <option value="">Select Reason</option>
                            <option v-for="(label, key) in adjustmentReasons" :key="key" :value="key">{{ label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="adjustForm.notes" rows="2" class="input"
                            placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" @click="closeProductSubview" class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="adjustSaving"
                            class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg hover:from-amber-600 hover:to-amber-700 transition-all duration-200 disabled:opacity-50 font-medium">
                            {{ adjustSaving ? 'Saving...' : 'Save Adjustment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stock Breakdown Modal -->
        <div v-if="showStockModal" class="tab-form-page"
            :class="formPage ? 'max-w-6xl mx-auto py-8' : 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4'"
            @click.self="closeStockModal">
            <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl" :class="formPage ? 'max-w-6xl max-h-none' : ''">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Stock Breakdown</h3>
                        <p class="text-sm text-gray-600">{{ stockProduct?.title || '-' }}</p>
                    </div>
                    <button @click="closeStockModal"
                        class="text-gray-400 hover:text-gray-600 text-2xl leading-none transition-colors">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div class="p-4 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-lg border border-indigo-100">
                        <div class="text-xs text-gray-500 uppercase font-medium">Product Stock</div>
                        <div class="text-xl font-bold text-indigo-600 mt-1">{{ stockProduct?.stock || 0 }}</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-emerald-50 to-green-50 rounded-lg border border-emerald-100">
                        <div class="text-xs text-gray-500 uppercase font-medium">Total Warehouses</div>
                        <div class="text-xl font-bold text-emerald-600 mt-1">{{ totalWarehouseStock }}</div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-purple-50 to-violet-50 rounded-lg border border-purple-100">
                        <div class="text-xs text-gray-500 uppercase font-medium">Locations</div>
                        <div class="text-xl font-bold text-purple-600 mt-1">{{ productStocks.length }}</div>
                    </div>
                </div>

                <div v-if="stockLoading" class="text-center py-6 text-gray-500">
                    <div class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                fill="none" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Loading stock data...
                    </div>
                </div>
                <table v-else-if="productStocks.length" class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Warehouse</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Last Updated
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="stock in productStocks" :key="stock.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-900">{{ stock.warehouse?.name || '-' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ stock.location?.name || 'No Location' }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-indigo-600">{{ stock.quantity }}</td>
                            <td class="px-4 py-2 text-right text-gray-500">
                                {{ stock.last_updated ? new Date(stock.last_updated).toLocaleDateString() : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-center text-gray-500 py-6">No warehouse stock data found.</p>

                <div class="flex justify-end mt-4">
                    <button @click="closeStockModal" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>

        <!-- Categories Tab Content -->
        <div v-if="activeTab === 'categories'">
            <Categories />
        </div>

        <!-- Upload Progress/Error Message -->
        <Transition enter-active-class="transition ease-out duration-300"
            enter-from-class="transform translate-y-2 opacity-0" enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200" leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-2 opacity-0">
            <div v-if="uploadMessage" class="fixed bottom-4 right-4 bg-white rounded-xl shadow-2xl p-4 max-w-md border"
                :class="uploadMessage.type === 'success' ? 'border-green-200' : 'border-red-200'">
                <div class="flex items-start gap-3">
                    <div v-if="uploadMessage.type === 'success'" class="text-green-500 flex-shrink-0">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div v-else class="text-red-500 flex-shrink-0">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">{{ uploadMessage.title }}</p>
                        <p class="text-sm text-gray-600">{{ uploadMessage.message }}</p>
                    </div>
                    <button @click="uploadMessage = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onActivated, onBeforeUnmount, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import Categories from './Categories.vue'

const route = useRoute();
const formPage = ref(false);

const products = ref({ data: [] });
const loading = ref(true);
const showModal = ref(false);
const editingProduct = ref(null);
const saving = ref(false);

// Upload states
const fileInput = ref(null);
const uploadMessage = ref(null);

const categories = ref([]);
const brands = ref([]);

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID').format(price || 0);
};

const getRowNumber = (index) => {
    const from = products.value.from || 1;
    return from + index;
};

const buildHierarchyOptions = (items, parentKey = 'parent_id') => {
    const childrenMap = new Map();
    items.forEach((item) => {
        const parentId = item[parentKey] || null;
        if (!childrenMap.has(parentId)) childrenMap.set(parentId, []);
        childrenMap.get(parentId).push(item);
    });

    const result = [];
    const walk = (parentId, depth) => {
        const children = (childrenMap.get(parentId) || [])
            .slice()
            .sort((a, b) => (a.name || '').localeCompare(b.name || ''));
        children.forEach((child) => {
            const prefix = depth > 0 ? `${'--'.repeat(depth)} ` : '';
            result.push({
                id: child.id,
                label: `${prefix}${child.name}`,
                raw: child,
                depth
            });
            walk(child.id, depth + 1);
        });
    };

    walk(null, 0);
    return result;
};

const categoryOptions = computed(() => buildHierarchyOptions(categories.value, 'parent_id'));

const categoryMap = computed(() => {
    const map = new Map();
    categories.value.forEach((cat) => map.set(cat.id, cat));
    return map;
});

const subcategoryMap = computed(() => {
    const map = new Map();
    categories.value.forEach((cat) => {
        const subcategories = cat.sub_categories || cat.subCategories || [];
        subcategories.forEach((sub) => map.set(sub.id, sub));
    });
    return map;
});

const buildPath = (id, map, parentKey = 'parent_id') => {
    if (!id || !map.has(id)) return '';
    const parts = [];
    let currentId = id;
    while (currentId && map.has(currentId)) {
        const current = map.get(currentId);
        parts.unshift(current.name);
        currentId = current[parentKey];
    }
    return parts.join(' / ');
};

// Tab state
const activeTab = ref('products');

// Sync state
const selectedProducts = ref([]);
const syncingId = ref(null);
const bulkSyncing = ref(false);

// Computed: apakah semua produk di halaman ini dipilih
const isAllSelected = computed(() => {
    const pageIds = products.value.data?.map(p => p.id) || [];
    return pageIds.length > 0 && pageIds.every(id => selectedProducts.value.includes(id));
});

// Computed: sebagian dipilih (untuk indeterminate state di checkbox header)
const isSomeSelected = computed(() => {
    const pageIds = products.value.data?.map(p => p.id) || [];
    return pageIds.some(id => selectedProducts.value.includes(id)) && !isAllSelected.value;
});

const toggleSelectAll = () => {
    const pageIds = products.value.data?.map(p => p.id) || [];
    if (isAllSelected.value) {
        // Deselect semua yang ada di halaman ini
        selectedProducts.value = selectedProducts.value.filter(id => !pageIds.includes(id));
    } else {
        // Select semua yang ada di halaman ini
        const newIds = pageIds.filter(id => !selectedProducts.value.includes(id));
        selectedProducts.value = [...selectedProducts.value, ...newIds];
    }
};

// Adjustment states
const adjustments = ref({ data: [] });
const adjustmentsLoading = ref(false);
const showAdjustModal = ref(false);
const adjustingProduct = ref(null);
const adjustSaving = ref(false);
const adjustmentReasons = ref({});
const showStockModal = ref(false);
const stockProduct = ref(null);
const productStocks = ref([]);
const stockLoading = ref(false);

const totalWarehouseStock = computed(() => {
    return productStocks.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
});

const adjustForm = ref({
    adjustment_type: 'out',
    quantity: 1,
    reason: '',
    notes: ''
});

const filters = ref({
    search: '',
    category_id: '',
    sub_category_id: '',
    brand: ''
});

const formModalTab = ref('basic');

const openProductForm = (productId = null) => {
    const target = productId
        ? `/dashboard/products?edit=${encodeURIComponent(productId)}`
        : '/dashboard/products?create=1';
    window.open(target, '_blank');
};

const cancelProductForm = () => {
    if (!formPage.value) {
        showModal.value = false;
        editingProduct.value = null;
        return;
    }
    if (window.opener) {
        window.opener.postMessage({ type: 'admin-data-saved', resource: 'products' }, window.location.origin);
        window.close();
        window.setTimeout(() => {
            if (!window.closed) window.location.replace('/dashboard/products');
        }, 150);
        return;
    }
    window.location.replace('/dashboard/products');
};

const closeProductSubview = () => {
    if (formPage.value) {
        cancelProductForm();
        return;
    }
    showAdjustModal.value = false;
};

const onProductSaved = (event) => {
    if (event.origin === window.location.origin && event.data?.type === 'admin-data-saved' && event.data.resource === 'products') {
        loadProducts();
    }
};

const getEmptyForm = () => {
    const f = {
        title: '',
        subtitle: '',
        sku: '',
        brand: '',
        module: 'A',
        image: '',
        descriptions: '',
        price: 0,
        stock: 0,
        minimum_stock: 0,
        warranty_period_months: 12,
        is_asset: false,
        categories: [],
        wireless_standard: '',
        wireless_stream: '',
        wireless_stream1: '',
        wireless_stream2: '',
        wireless_stream3: '',
        wireless_stream4: '',
        wireless_stream5: '',
        manageable_aps: '',
        ap_number: '',
        number_of_clients: '',
        capacity: '',
        ip_rating: '',
        switching: '',
        throughput: '',
        flash_memory: '',
        sdram_memory: '',
        interface_main: '',
        interface1: '',
        interface2: '',
        interface3: '',
        interface4: '',
        interface5: '',
        antenna: '',
        cu: '',
        cu1: '',
        cu2: '',
        cu3: '',
        cu4: '',
        cu5: '',
        additional_interface1: '',
        additional_interface2: '',
        additional_interface3: '',
        additional_interface4: '',
        additional_interface5: '',
        mac_address: '',
        routing_table: '',
        dustproof_waterproof: '',
        noise: '',
        mtbf: '',
        operating_temperature: '',
        storage_temperature: '',
        operating_humidity: '',
        power1: '',
        power2: '',
        power3: '',
        power4: '',
        power5: '',
        power_consumptions: '',
        power_consumption_with_poe: '',
        power_consumption_excluding_poe: '',
        poe_budget: '',
        dimensions: '',
        diagram: '',
        network_diagram: ''
    };
    for (let i = 1; i <= 20; i++) f[`spec${i}`] = '';
    for (let i = 1; i <= 20; i++) f[`fitur${i}`] = '';
    return f;
};

const form = ref(getEmptyForm());

const resetForm = () => {
    formModalTab.value = 'basic';
    form.value = getEmptyForm();
};

const addCategoryPair = () => {
    form.value.categories.push({
        category_id: '',
        sub_category_id: ''
    });
};

const removeCategoryPair = (index) => {
    form.value.categories.splice(index, 1);
};

const onCategoryChange = (index) => {
    // Reset subcategory when category changes
    form.value.categories[index].sub_category_id = '';
};

const getSubcategoryOptions = (categoryId) => {
    if (!categoryId) return [];
    const category = categories.value.find(c => c.id === categoryId);
    const subcategories = category?.sub_categories || category?.subCategories || [];
    return buildHierarchyOptions(subcategories, 'parent_id');
};

const getCategoryPath = (categoryId) => buildPath(categoryId, categoryMap.value, 'parent_id');
const getSubcategoryPath = (subCategoryId) => buildPath(subCategoryId, subcategoryMap.value, 'parent_id');

const loadCategories = async () => {
    try {
        const response = await api.get('/categories');
        categories.value = response.data || [];
    } catch (error) {
        console.error('Failed to load categories:', error);
    }
};

const loadFilterOptions = async () => {
    try {
        const [categoriesResponse, filterResponse] = await Promise.all([
            api.get('/categories'),
            api.get('/products/filter-options')
        ]);
        categories.value = categoriesResponse.data || [];
        brands.value = filterResponse.data.brands || [];
    } catch (error) {
        console.error('Failed to load filter options:', error);
    }
};

const loadProducts = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, ...filters.value };
        // Clean up empty params
        Object.keys(params).forEach(key => {
            if (params[key] === '') delete params[key];
        });
        const response = await api.get('/products', { params });
        products.value = response.data;
    } catch (error) {
        console.error('Failed to load products:', error);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.value = { search: '', category_id: '', sub_category_id: '', brand: '' };
    loadProducts();
};

const onFilterCategoryChange = () => {
    filters.value.sub_category_id = '';
    loadProducts();
};

const editProduct = (product) => {
    if (!formPage.value) {
        openProductForm(product.id);
        return;
    }

    editingProduct.value = product;
    formModalTab.value = 'basic';

    // Convert category_pairs to form.categories format
    const categoryPairs = product.category_pairs || [];
    const formCategories = categoryPairs.map(pair => ({
        category_id: pair.category_id || '',
        sub_category_id: pair.sub_category_id || ''
    }));

    const f = getEmptyForm();
    f.title = product.title || '';
    f.subtitle = product.subtitle || '';
    f.sku = product.sku || '';
    f.brand = product.brand || '';
    f.module = product.module || 'A';
    f.image = product.image || '';
    f.descriptions = product.descriptions || '';
    f.price = product.price || 0;
    f.stock = product.stock || 0;
    f.minimum_stock = product.minimum_stock || 0;
    f.warranty_period_months = product.warranty_period_months ?? 12;
    f.is_asset = product.is_asset || false;
    f.categories = formCategories.length > 0 ? formCategories : [];

    // Specs 1-20
    for (let i = 1; i <= 20; i++) {
        f[`spec${i}`] = product[`spec${i}`] || '';
    }
    // Features 1-20
    for (let i = 1; i <= 20; i++) {
        f[`fitur${i}`] = product[`fitur${i}`] || '';
    }

    // Wireless & Controllers
    f.wireless_standard = product.wireless_standard || product.wirelessStandard || '';
    f.wireless_stream = product.wireless_stream || product.wirelessStream || '';
    f.wireless_stream1 = product.wireless_stream1 || product.wirelessStream1 || '';
    f.wireless_stream2 = product.wireless_stream2 || product.wirelessStream2 || '';
    f.wireless_stream3 = product.wireless_stream3 || product.wirelessStream3 || '';
    f.wireless_stream4 = product.wireless_stream4 || product.wirelessStream4 || '';
    f.wireless_stream5 = product.wireless_stream5 || product.wirelessStream5 || '';
    f.manageable_aps = product.manageable_aps || product.manageableAps || '';
    f.ap_number = product.ap_number || product.APnumber || '';
    f.number_of_clients = product.number_of_clients || product.numberOfClients || '';
    f.capacity = product.capacity || '';
    f.ip_rating = product.ip_rating || product.iprating || '';
    f.switching = product.switching || '';
    f.throughput = product.throughput || '';
    f.flash_memory = product.flash_memory || product.flashmemory || '';
    f.sdram_memory = product.sdram_memory || product.sdrammemory || '';
    f.interface_main = product.interface_main || product.Interface || '';
    f.interface1 = product.interface1 || product.Interface1 || '';
    f.interface2 = product.interface2 || product.Interface2 || '';
    f.interface3 = product.interface3 || product.Interface3 || '';
    f.interface4 = product.interface4 || product.Interface4 || '';
    f.interface5 = product.interface5 || product.Interface5 || '';
    f.antenna = product.antenna || product.antena || '';
    f.cu = product.cu || '';
    f.cu1 = product.cu1 || '';
    f.cu2 = product.cu2 || '';
    f.cu3 = product.cu3 || '';
    f.cu4 = product.cu4 || '';
    f.cu5 = product.cu5 || '';
    f.additional_interface1 = product.additional_interface1 || product.aditionalinterface1 || '';
    f.additional_interface2 = product.additional_interface2 || product.aditionalinterface2 || '';
    f.additional_interface3 = product.additional_interface3 || product.aditionalinterface3 || '';
    f.additional_interface4 = product.additional_interface4 || product.aditionalinterface4 || '';
    f.additional_interface5 = product.additional_interface5 || product.aditionalinterface5 || '';
    f.mac_address = product.mac_address || product.macaddress || '';
    f.routing_table = product.routing_table || product.routingtable || '';
    f.dustproof_waterproof = product.dustproof_waterproof || product.dustproofwaterproof || '';
    f.noise = product.noise || '';
    f.mtbf = product.mtbf || '';
    f.operating_temperature = product.operating_temperature || product.operatingtemperature || '';
    f.storage_temperature = product.storage_temperature || product.storagetemperature || '';
    f.operating_humidity = product.operating_humidity || product.operatinghumidity || '';
    f.power1 = product.power1 || '';
    f.power2 = product.power2 || '';
    f.power3 = product.power3 || '';
    f.power4 = product.power4 || '';
    f.power5 = product.power5 || '';
    f.power_consumptions = product.power_consumptions || product.powercomsumptions || '';
    f.power_consumption_with_poe = product.power_consumption_with_poe || product['PowerConsumption(withPoE)'] || '';
    f.power_consumption_excluding_poe = product.power_consumption_excluding_poe || product['PowerConsumption(ExcludingPoE)'] || '';
    f.poe_budget = product.poe_budget || product.PoEBudget || '';
    f.dimensions = product.dimensions || '';
    f.diagram = product.diagram || '';
    f.network_diagram = product.network_diagram || product.networkDIagram || '';

    form.value = f;
    showModal.value = true;
};

const saveProduct = async () => {
    saving.value = true;
    try {
        const wasEditing = Boolean(editingProduct.value);
        // Filter out empty category pairs
        const validCategories = form.value.categories.filter(c => c.category_id);
        const payload = {
            ...form.value,
            categories: validCategories
        };

        if (editingProduct.value) {
            await api.put(`/products/${editingProduct.value.id}`, payload);
        } else {
            await api.post('/products', payload);
        }
        if (formPage.value) {
            if (window.opener) {
                window.opener.postMessage({ type: 'admin-data-saved', resource: 'products' }, window.location.origin);
                window.close();
                window.setTimeout(() => {
                    if (!window.closed) window.location.replace('/dashboard/products');
                }, 150);
                return;
            }
            window.location.replace('/dashboard/products');
            return;
        }

        showModal.value = false;
        editingProduct.value = null;
        resetForm();
        loadProducts();

        uploadMessage.value = {
            type: 'success',
            title: wasEditing ? 'Product Updated' : 'Product Created',
            message: 'Product saved successfully'
        };
        setTimeout(() => { uploadMessage.value = null; }, 3000);
    } catch (error) {
        console.error('Failed to save product:', error);
        uploadMessage.value = {
            type: 'error',
            title: 'Save Failed',
            message: error.response?.data?.message || 'Failed to save product'
        };
        setTimeout(() => { uploadMessage.value = null; }, 8000);
    } finally {
        saving.value = false;
    }
};

const deleteProduct = async (id) => {
    if (!confirm('Are you sure you want to delete this product?')) return;
    try {
        await api.delete(`/products/${id}`);
        loadProducts();
        uploadMessage.value = {
            type: 'success',
            title: 'Deleted',
            message: 'Product deleted successfully'
        };
        setTimeout(() => { uploadMessage.value = null; }, 3000);
    } catch (error) {
        console.error('Failed to delete product:', error);
        uploadMessage.value = {
            type: 'error',
            title: 'Delete Failed',
            message: error.response?.data?.message || 'Failed to delete product'
        };
        setTimeout(() => { uploadMessage.value = null; }, 8000);
    }
};

/**
 * Sync satu produk ke catalog publik website
 */
const syncProductToPublic = async (product) => {
    syncingId.value = product.id;
    try {
        const response = await api.post(`/products/${product.id}/sync-to-public`);
        const data = response.data;

        // Update produk di list lokal agar badge langsung berubah
        const idx = products.value.data?.findIndex(p => p.id === product.id);
        if (idx !== undefined && idx >= 0) {
            products.value.data[idx].public_product_id = data.product?.public_product_id ?? data.public_product?.id;
        }

        uploadMessage.value = {
            type: 'success',
            title: data.is_new ? '✅ Produk Dipublikasikan!' : '✅ Data Publik Diperbarui',
            message: data.message,
        };
        setTimeout(() => { uploadMessage.value = null; }, 5000);
    } catch (error) {
        console.error('Sync failed:', error);
        uploadMessage.value = {
            type: 'error',
            title: 'Sync Gagal',
            message: error.response?.data?.message || 'Gagal sync produk ke website'
        };
        setTimeout(() => { uploadMessage.value = null; }, 8000);
    } finally {
        syncingId.value = null;
    }
};

/**
 * Bulk sync beberapa produk yang dipilih ke catalog publik
 */
const bulkSyncToPublic = async () => {
    if (selectedProducts.value.length === 0) return;
    if (!confirm(`Sync ${selectedProducts.value.length} produk ke website publik?`)) return;

    bulkSyncing.value = true;
    try {
        const response = await api.post('/products/bulk-sync-to-public', {
            product_ids: selectedProducts.value
        });
        const data = response.data;

        // Reload untuk update badge status semua produk
        await loadProducts();
        selectedProducts.value = [];

        uploadMessage.value = {
            type: data.success ? 'success' : 'error',
            title: data.success ? '✅ Bulk Sync Selesai' : '⚠️ Sync Selesai dengan Error',
            message: data.message,
        };
        setTimeout(() => { uploadMessage.value = null; }, 6000);
    } catch (error) {
        console.error('Bulk sync failed:', error);
        uploadMessage.value = {
            type: 'error',
            title: 'Bulk Sync Gagal',
            message: error.response?.data?.message || 'Gagal melakukan bulk sync'
        };
        setTimeout(() => { uploadMessage.value = null; }, 8000);
    } finally {
        bulkSyncing.value = false;
    }
};

/**
 * Sync SEMUA produk di database ke catalog publik website
 */
const syncAllLoading = ref(false);
const syncAllToPublic = async () => {
    if (!confirm('Apakah Anda yakin ingin menyinkronkan SEMUA produk ke website publik?')) return;

    syncAllLoading.value = true;
    try {
        const response = await api.post('/products/sync-all-to-public');
        const data = response.data;

        await loadProducts();

        uploadMessage.value = {
            type: data.success ? 'success' : 'error',
            title: data.success ? '✅ Sinkronisasi Selesai' : '⚠️ Sinkronisasi dengan Error',
            message: data.message || 'Semua produk berhasil disinkronkan ke website publik.'
        };
        setTimeout(() => { uploadMessage.value = null; }, 6000);
    } catch (error) {
        console.error('Sync all failed:', error);
        uploadMessage.value = {
            type: 'error',
            title: 'Sinkronisasi Gagal',
            message: error.response?.data?.message || 'Gagal menyinkronkan semua produk ke website'
        };
        setTimeout(() => { uploadMessage.value = null; }, 8000);
    } finally {
        syncAllLoading.value = false;
    }
};

// Excel functions
const downloadExcel = async () => {
    try {
        const response = await api.get('/products/export/all', {
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `products_${new Date().toISOString().split('T')[0]}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to download Excel:', error);
        alert('Failed to download Excel file');
    }
};

const downloadTemplate = async () => {
    try {
        const response = await api.get('/products/export/template', {
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'products_template.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to download template:', error);
        alert('Failed to download template');
    }
};

const uploadExcel = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await api.post('/products/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        uploadMessage.value = {
            type: 'success',
            title: 'Upload Successful',
            message: response.data.message
        };

        // Clear file input
        event.target.value = '';

        // Reload products
        loadProducts();

        // Auto-hide message after 5 seconds
        setTimeout(() => {
            uploadMessage.value = null;
        }, 5000);
    } catch (error) {
        console.error('Failed to upload Excel:', error);
        const validationErrors = Object.values(error.response?.data?.errors || {}).flat();
        uploadMessage.value = {
            type: 'error',
            title: 'Upload Failed',
            message: validationErrors.join(' ') || error.response?.data?.message || 'Failed to upload file'
        };

        // Auto-hide error after 8 seconds
        setTimeout(() => {
            uploadMessage.value = null;
        }, 8000);
    } finally {
        event.target.value = '';
    }
};

// Adjustment Functions
const loadAdjustments = async (page = 1) => {
    adjustmentsLoading.value = true;
    try {
        const response = await api.get('/stock-adjustments', {
            params: { page, per_page: 15 }
        });
        adjustments.value = response.data;
    } catch (error) {
        console.error('Failed to load adjustments:', error);
    } finally {
        adjustmentsLoading.value = false;
    }
};

const loadAdjustmentReasons = async () => {
    try {
        const response = await api.get('/stock-adjustments/reasons');
        adjustmentReasons.value = response.data;
    } catch (error) {
        console.error('Failed to load adjustment reasons:', error);
        // Fallback reasons
        adjustmentReasons.value = {
            damaged: 'Damaged',
            expired: 'Expired',
            lost: 'Lost',
            found: 'Found',
            correction: 'Correction',
            audit: 'Stock Opname/Audit',
            theft: 'Theft',
            donation: 'Donation',
            return_from_lending: 'Return from Lending',
            warranty_replacement: 'Warranty Replacement',
            other: 'Other'
        };
    }
};

const openAdjustModal = (product) => {
    if (!formPage.value) {
        window.open(`/dashboard/products?adjust=${encodeURIComponent(product.id)}`, '_blank');
        return;
    }
    adjustingProduct.value = product;
    resetAdjustForm();
    showAdjustModal.value = true;
};

const resetAdjustForm = () => {
    adjustForm.value = {
        adjustment_type: 'out',
        quantity: 1,
        reason: '',
        notes: ''
    };
};

const submitAdjustment = async () => {
    if (!adjustingProduct.value) return;

    adjustSaving.value = true;
    try {
        await api.post('/stock-adjustments', {
            product_id: adjustingProduct.value.id,
            adjustment_type: adjustForm.value.adjustment_type,
            quantity: adjustForm.value.quantity,
            reason: adjustForm.value.reason,
            notes: adjustForm.value.notes
        });

        if (formPage.value) {
            cancelProductForm();
        } else {
            showAdjustModal.value = false;
            loadProducts(); // Reload to update stock
        }

        // Show success message
        uploadMessage.value = {
            type: 'success',
            title: 'Adjustment Saved',
            message: `Stock adjusted successfully for ${adjustingProduct.value.title}`
        };
        setTimeout(() => { uploadMessage.value = null; }, 5000);
    } catch (error) {
        console.error('Failed to save adjustment:', error);
        uploadMessage.value = {
            type: 'error',
            title: 'Adjustment Failed',
            message: error.response?.data?.message || 'Failed to save adjustment'
        };
        setTimeout(() => { uploadMessage.value = null; }, 8000);
    } finally {
        adjustSaving.value = false;
    }
};

const openStockModal = async (product) => {
    if (!formPage.value) {
        window.open(`/dashboard/products?stock=${encodeURIComponent(product.id)}`, '_blank');
        return;
    }
    stockProduct.value = product;
    showStockModal.value = true;
    await loadProductStocks(product.id);
};

const closeStockModal = () => {
    if (formPage.value) {
        cancelProductForm();
        return;
    }
    showStockModal.value = false;
    stockProduct.value = null;
    productStocks.value = [];
};

const loadProductStocks = async (productId) => {
    stockLoading.value = true;
    try {
        const response = await api.get('/stock', {
            params: { product_id: productId, per_page: 200 }
        });
        productStocks.value = response.data.data || response.data || [];
    } catch (error) {
        console.error('Failed to load product stocks:', error);
        productStocks.value = [];
    } finally {
        stockLoading.value = false;
    }
};

onMounted(() => {
    window.addEventListener('message', onProductSaved);
    const adjustId = Array.isArray(route.query.adjust) ? route.query.adjust[0] : route.query.adjust;
    const stockId = Array.isArray(route.query.stock) ? route.query.stock[0] : route.query.stock;
    formPage.value = route.query.create === '1' || Boolean(route.query.edit) || Boolean(adjustId) || Boolean(stockId);
    const editId = Array.isArray(route.query.edit) ? route.query.edit[0] : route.query.edit;

    const openEditFromRoute = async () => {
        if (editId) {
            try {
                const response = await api.get(`/products/${editId}`);
                editProduct(response.data);
            } catch (error) {
                console.error('Failed to load product for edit:', error);
            }
        } else if (formPage.value) {
            if (adjustId || stockId) {
                try {
                    const response = await api.get(`/products/${adjustId || stockId}`);
                    if (adjustId) {
                        adjustingProduct.value = response.data;
                        resetAdjustForm();
                        showAdjustModal.value = true;
                    } else {
                        stockProduct.value = response.data;
                        showStockModal.value = true;
                        await loadProductStocks(response.data.id);
                    }
                } catch (error) {
                    console.error('Failed to load product stock details:', error);
                    uploadMessage.value = {
                        type: 'error',
                        title: 'Unable to open product',
                        message: error.response?.data?.message || 'Failed to load product details'
                    };
                }
            } else {
                editingProduct.value = null;
                resetForm();
                showModal.value = true;
            }
        }
    };

    loadFilterOptions().then(() => {
        openEditFromRoute();
    });

    loadProducts();
    loadAdjustmentReasons();
});

onBeforeUnmount(() => window.removeEventListener('message', onProductSaved));

// Reload stock when navigating back to this page (e.g., after approving a project investment)
onActivated(() => {
    loadProducts();
});
</script>

<style scoped>
/* Animasi fade untuk bulk sync bar */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

.products-form-tab > :not(.tab-form-page) {
    display: none !important;
}
</style>
