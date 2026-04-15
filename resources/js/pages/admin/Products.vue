<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
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
                <button v-if="activeTab === 'products'" @click="showModal = true; editingProduct = null; resetForm()"
                    class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium text-sm">
                    + Add Product
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'products'"
                    :class="[
                        'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                        activeTab === 'products'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]">
                    Products
                </button>
                <button @click="activeTab = 'adjustments'; loadAdjustments()"
                    :class="[
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
                        placeholder="Search by title, SKU, brand..."
                        class="input" />
                    <select v-model="filters.category_id" @change="onFilterCategoryChange"
                        class="input">
                        <option value="">All Categories</option>
                        <option v-for="cat in categoryOptions" :key="cat.id" :value="cat.id">{{ cat.label }}</option>
                    </select>
                    <select v-model="filters.sub_category_id" @change="loadProducts"
                        :disabled="!filters.category_id"
                        class="input disabled:bg-gray-100 disabled:cursor-not-allowed">
                        <option value="">All Subcategories</option>
                        <option v-for="sub in getSubcategoryOptions(filters.category_id)" :key="sub.id" :value="sub.id">
                            {{ sub.label }}
                        </option>
                    </select>
                    <select v-model="filters.brand" @change="loadProducts"
                        class="input">
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
                    <h3 class="text-lg font-semibold text-gray-900">Product List</h3>
                    <span v-if="products.total" class="text-sm text-gray-500">{{ products.total }} products</span>
                </div>

                <div v-if="loading" class="p-12 text-center">
                    <div class="inline-flex items-center gap-2 text-gray-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Loading products...
                    </div>
                </div>

                <table v-else class="min-w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categories</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Min</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(product, index) in products.data" :key="product.id" class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ getRowNumber(index) }}</td>
                            <td class="px-6 py-4">
                                <span v-if="product.sku" class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{ product.sku }}</span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ product.title }}</div>
                                <div v-if="product.descriptions" class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ product.descriptions }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <template v-if="product.category_pairs && product.category_pairs.length > 0">
                                        <span v-for="(pair, idx) in product.category_pairs" :key="idx"
                                            class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ getCategoryPath(pair.category_id) || pair.category_name || '-' }}
                                            <span v-if="pair.sub_category_id || pair.sub_category_name" class="ml-1 text-indigo-500">
                                                / {{ getSubcategoryPath(pair.sub_category_id) || pair.sub_category_name || '-' }}
                                            </span>
                                        </span>
                                    </template>
                                    <template v-else-if="product.category">
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                                            {{ product.category }}
                                            <span v-if="product.sub_category" class="ml-1 text-gray-500">
                                                / {{ product.sub_category }}
                                            </span>
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-400 text-sm">—</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ product.brand || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="product.price > 0">Rp {{ formatPrice(product.price) }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="[
                                    'inline-flex items-center justify-center min-w-[2.5rem] px-2.5 py-1 text-xs font-semibold rounded-full',
                                    (product.stock || 0) <= 0 ? 'bg-red-100 text-red-700' :
                                    (product.stock || 0) <= (product.minimum_stock || 0) ? 'bg-amber-100 text-amber-700' :
                                    'bg-green-100 text-green-700'
                                ]">
                                    {{ product.stock || 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ product.minimum_stock || 0 }}</td>
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
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                No products found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="products.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
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
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-5xl max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        {{ editingProduct ? 'Edit Product' : 'Add New Product' }}
                    </h3>
                    <button @click="showModal = false; editingProduct = null"
                        class="text-gray-400 hover:text-gray-600 text-3xl leading-none transition-colors">&times;</button>
                </div>

                <form @submit.prevent="saveProduct" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-blue-50 p-5 rounded-lg">
                        <h4 class="text-base font-semibold mb-4 text-gray-800">Basic Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                <input v-model="form.title" type="text" required
                                    class="input" placeholder="Product name" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                                <input v-model="form.sku" type="text"
                                    class="input" placeholder="e.g. XG-OLT.001" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                <input v-model="form.brand" type="text"
                                    class="input" placeholder="e.g. DASAN" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Price (Rp)</label>
                                <input v-model.number="form.price" type="number" min="0" step="0.01"
                                    class="input" placeholder="0" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                                <input v-model="form.image" type="url"
                                    class="input" placeholder="https://..." />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="form.descriptions" rows="2"
                                    class="input" placeholder="Optional product description..."></textarea>
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
                            <h4 class="text-base font-semibold text-gray-800">Categories & Subcategories</h4>
                            <button type="button" @click="addCategoryPair"
                                class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                                + Add Category
                            </button>
                        </div>

                        <div v-if="form.categories.length === 0" class="text-gray-500 text-sm py-3 text-center border-2 border-dashed border-gray-200 rounded-lg">
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
                                    <select v-model="catPair.sub_category_id"
                                        class="input text-sm"
                                        :disabled="!catPair.category_id">
                                        <option value="">Select Subcategory</option>
                                        <option v-for="sub in getSubcategoryOptions(catPair.category_id)"
                                            :key="sub.id" :value="sub.id">
                                            {{ sub.label }}
                                        </option>
                                    </select>
                                </div>
                                <button type="button" @click="removeCategoryPair(index)"
                                    class="mt-5 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-150">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                                <input v-model.number="form.stock" type="number" min="0"
                                    class="input" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock (Alert)</label>
                                <input v-model.number="form.minimum_stock" type="number" min="0"
                                    class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" @click="showModal = false; editingProduct = null"
                            class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="btn-primary disabled:opacity-50">
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
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Before → After</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="adj in adjustments.data" :key="adj.id" class="hover:bg-gray-50 transition-colors duration-150">
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
                            <td class="px-6 py-4 text-sm capitalize text-gray-700">{{ adj.reason?.replace(/_/g, ' ') }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-center">{{ adj.quantity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ adj.before_qty }} → {{ adj.after_qty }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(adj.created_at).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ adj.user?.name || '-' }}</td>
                        </tr>
                        <tr v-if="!adjustments.data?.length">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                No adjustments found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="adjustments.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between items-center border-t">
                    <p class="text-sm text-gray-700">
                        Showing {{ adjustments.from }} to {{ adjustments.to }} of {{ adjustments.total }} adjustments
                    </p>
                    <div class="flex space-x-2">
                        <button @click="loadAdjustments(adjustments.current_page - 1)" :disabled="!adjustments.prev_page_url"
                            class="btn-secondary disabled:opacity-50">Previous</button>
                        <button @click="loadAdjustments(adjustments.current_page + 1)" :disabled="!adjustments.next_page_url"
                            class="btn-secondary disabled:opacity-50">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjustment Modal -->
        <div v-if="showAdjustModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showAdjustModal = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Stock Adjustment</h3>
                    <button @click="showAdjustModal = false"
                        class="text-gray-400 hover:text-gray-600 text-2xl leading-none transition-colors">&times;</button>
                </div>

                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <p class="font-medium text-gray-900">{{ adjustingProduct?.title }}</p>
                    <p class="text-sm text-gray-600">Current Stock: <span class="font-semibold text-indigo-600">{{ adjustingProduct?.stock || 0 }}</span></p>
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
                            <option v-for="(label, key) in adjustmentReasons" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="adjustForm.notes" rows="2" class="input" placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" @click="showAdjustModal = false" class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="adjustSaving"
                            class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg hover:from-amber-600 hover:to-amber-700 transition-all duration-200 disabled:opacity-50 font-medium">
                            {{ adjustSaving ? 'Saving...' : 'Save Adjustment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stock Breakdown Modal -->
        <div v-if="showStockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="closeStockModal">
            <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
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
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
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
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Last Updated</th>
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

        <!-- Upload Progress/Error Message -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform translate-y-2 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform translate-y-0 opacity-100"
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
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';

const route = useRoute();

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

const form = ref({
    title: '',
    sku: '',
    brand: '',
    image: '',
    descriptions: '',
    price: 0,
    stock: 0,
    minimum_stock: 0,
    is_asset: false,
    categories: [] // Array of {category_id, sub_category_id}
});

const resetForm = () => {
    form.value = {
        title: '',
        sku: '',
        brand: '',
        image: '',
        descriptions: '',
        price: 0,
        stock: 0,
        minimum_stock: 0,
        is_asset: false,
        categories: []
    };
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
    editingProduct.value = product;

    // Convert category_pairs to form.categories format
    const categoryPairs = product.category_pairs || [];
    const formCategories = categoryPairs.map(pair => ({
        category_id: pair.category_id || '',
        sub_category_id: pair.sub_category_id || ''
    }));

    form.value = {
        title: product.title || '',
        sku: product.sku || '',
        brand: product.brand || '',
        image: product.image || '',
        descriptions: product.descriptions || '',
        price: product.price || 0,
        stock: product.stock || 0,
        minimum_stock: product.minimum_stock || 0,
        is_asset: product.is_asset || false,
        categories: formCategories.length > 0 ? formCategories : []
    };
    showModal.value = true;
};

const saveProduct = async () => {
    saving.value = true;
    try {
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
        showModal.value = false;
        editingProduct.value = null;
        resetForm();
        loadProducts();

        uploadMessage.value = {
            type: 'success',
            title: editingProduct.value ? 'Product Updated' : 'Product Created',
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
        uploadMessage.value = {
            type: 'error',
            title: 'Upload Failed',
            message: error.response?.data?.message || 'Failed to upload file'
        };

        // Auto-hide error after 8 seconds
        setTimeout(() => {
            uploadMessage.value = null;
        }, 8000);
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

        showAdjustModal.value = false;
        loadProducts(); // Reload to update stock

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
    stockProduct.value = product;
    showStockModal.value = true;
    await loadProductStocks(product.id);
};

const closeStockModal = () => {
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
    const editId = Array.isArray(route.query.edit) ? route.query.edit[0] : route.query.edit;

    const openEditFromRoute = async () => {
        if (!editId) return;
        try {
            const response = await api.get(`/products/${editId}`);
            editProduct(response.data);
        } catch (error) {
            console.error('Failed to load product for edit:', error);
        }
    };

    loadFilterOptions().then(() => {
        openEditFromRoute();
    });
    loadProducts();
    loadAdjustmentReasons();
});
</script>
