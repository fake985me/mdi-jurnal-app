<template>
    <div class="space-y-6">
        <h2 class="text-2xl font-bold">{{ $t('sales.product') }}</h2>

        <div v-if="loading" class="text-center p-8">
            <p>{{ $t('common.loading') }}</p>
        </div>

        <table v-else class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('dashboard.category') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.brand') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.module') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.stock') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in products.data" :key="product.id" class="border-b">
                    <td class="px-6 py-4">{{ product.id }}</td>
                    <td class="px-6 py-4">
                        <div class="font-medium">{{ product.title }}</div>
                        <div class="text-sm text-gray-500">{{ product.subtitle }}</div>
                    </td>
                    <td class="px-6 py-4">{{ product.category }}</td>
                    <td class="px-6 py-4">{{ product.brand || '-' }}</td>
                    <td class="px-6 py-4">{{ product.module || '-' }}</td>
                    <td class="px-6 py-4">{{ product.current_stock?.quantity || 0 }}</td>
                </tr>
            </tbody>
        </table>

        <div v-if="products.data?.length" class="flex justify-between items-center p-4">
            <p>{{ $t('sales.showing') }} {{ products.from }} {{ $t('ui.to') }} {{ products.to }} {{ $t('sales.of') }} {{ products.total }}</p>
            <div class="space-x-2">
                <button @click="loadProducts(products.current_page - 1)" :disabled="!products.prev_page_url"
                    class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">{{ $t('sales.previous') }}</button>
                <button @click="loadProducts(products.current_page + 1)" :disabled="!products.next_page_url"
                    class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">{{ $t('sales.next') }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const products = ref({ data: [] });
const loading = ref(true);

const loadProducts = async (page = 1) => {
    loading.value = true;
    try {
        const response = await api.get('/products', { params: { page } });
        console.log('Products loaded:', response.data);
        products.value = response.data;
    } catch (error) {
        console.error('Gagal memuat produk:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadProducts();
});
</script>
