<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Carousel Management</h2>
            <button @click="showModal = true; editingItem = null"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                + Add Slide
            </button>
        </div>

        <!-- Slides Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div v-if="loading" class="p-8 text-center">
                <p class="text-gray-600">Loading carousel slides...</p>
            </div>

            <table v-else class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Text</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Images</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in slides" :key="item.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.order }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ item.text.substring(0, 60) }}...</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <span v-if="item.use_component"
                                class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Component</span>
                            <span v-else class="text-xs">{{ countImages(item) }} images</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="[
                                'px-2 py-1 text-xs rounded-full',
                                item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button @click="editItem(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-bold mb-4">{{ editingItem ? 'Edit Carousel Slide' : 'Add New Carousel Slide' }}
                </h3>

                <form @submit.prevent="saveItem" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Caption Text *</label>
                        <textarea v-model="form.text" required rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="Carousel slide caption text"></textarea>
                    </div>

                    <div class="flex items-center space-x-2 p-4 bg-purple-50 rounded-lg">
                        <input v-model="form.use_component" type="checkbox" id="use_component" class="mr-2" />
                        <label for="use_component" class="text-sm font-medium text-gray-700">
                            Use LineAnimation Component (instead of background image)
                        </label>
                    </div>

                    <div v-if="form.use_component" class="bg-purple-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Component Name</label>
                        <input v-model="form.component_name" value="LineAnimationSVG" disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" />
                        <small class="text-gray-500">Currently only LineAnimationSVG is supported</small>
                    </div>

                    <div v-if="!form.use_component">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Background Image URL</label>
                        <input v-model="form.bg_image"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="/assets/static/carousel/2.jpg" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Center Image URL</label>
                        <input v-model="form.center_img"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="/assets/static/carousel/dasan.png" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Left Image URL</label>
                            <input v-model="form.img_left"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Right Image URL</label>
                            <input v-model="form.img_right"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mid-Left Image URL</label>
                            <input v-model="form.mid_left"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mid-Right Image URL</label>
                            <input v-model="form.mid_right"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                            <input v-model.number="form.order" type="number"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.is_active" type="checkbox" class="mr-2" />
                                <span class="text-sm font-medium text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
                        {{ error }}
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ saving ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const slides = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingItem = ref(null);
const saving = ref(false);
const error = ref('');

const form = ref({
    title: '',
    text: '',
    bg_image: '',
    center_img: '',
    img_left: '',
    mid_left: '',
    mid_right: '',
    img_right: '',
    use_component: false,
    component_name: 'LineAnimationSVG',
    order: 0,
    is_active: true,
});

const countImages = (item) => {
    let count = 0;
    if (item.bg_image) count++;
    if (item.center_img) count++;
    if (item.img_left) count++;
    if (item.mid_left) count++;
    if (item.mid_right) count++;
    if (item.img_right) count++;
    return count;
};

const loadSlides = async () => {
    loading.value = true;
    try {
        const response = await api.get('/carousel-slides');
        slides.value = response.data;
    } catch (err) {
        console.error('Failed to load carousel slides:', err);
    } finally {
        loading.value = false;
    }
};

const editItem = (item) => {
    editingItem.value = item;
    form.value = {
        title: item.title || '',
        text: item.text,
        bg_image: item.bg_image || '',
        center_img: item.center_img || '',
        img_left: item.img_left || '',
        mid_left: item.mid_left || '',
        mid_right: item.mid_right || '',
        img_right: item.img_right || '',
        use_component: item.use_component,
        component_name: item.component_name || 'LineAnimationSVG',
        order: item.order,
        is_active: item.is_active,
    };
    showModal.value = true;
};

const saveItem = async () => {
    saving.value = true;
    error.value = '';

    try {
        if (editingItem.value) {
            await api.put(`/carousel-slides/${editingItem.value.id}`, form.value);
        } else {
            await api.post('/carousel-slides', form.value);
        }
        showModal.value = false;
        loadSlides();
        form.value = {
            title: '',
            text: '',
            bg_image: '',
            center_img: '',
            img_left: '',
            mid_left: '',
            mid_right: '',
            img_right: '',
            use_component: false,
            component_name: 'LineAnimationSVG',
            order: 0,
            is_active: true,
        };
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save carousel slide';
    } finally {
        saving.value = false;
    }
};

const deleteItem = async (id) => {
    if (!confirm('Are you sure you want to delete this carousel slide?')) return;

    try {
        await api.delete(`/carousel-slides/${id}`);
        loadSlides();
    } catch (err) {
        alert('Failed to delete carousel slide');
    }
};

onMounted(() => {
    loadSlides();
});
</script>
