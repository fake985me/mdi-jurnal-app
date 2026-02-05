<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto m-4">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">
                    Product Image
                </h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-4">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="activeTab = 'upload'" :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'upload'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            Upload File
                        </button>
                        <button @click="activeTab = 'url'" :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'url'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                            Image URL
                        </button>
                    </nav>
                </div>

                <!-- Upload Tab -->
                <div v-if="activeTab === 'upload'" class="space-y-4">
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <input type="file" ref="fileInput" @change="handleFileSelect" accept="image/*" class="hidden" />
                        <button @click="$refs.fileInput.click()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Choose Image File
                        </button>
                        <p class="mt-2 text-sm text-gray-500">PNG, JPG, GIF up to 10MB</p>
                    </div>

                    <div v-if="uploadedFile" class="text-sm text-gray-600">
                        Selected: {{ uploadedFile.name }}
                    </div>
                </div>

                <!-- URL Tab -->
                <div v-if="activeTab === 'url'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                        <input v-model="imageUrl" type="url" placeholder="https://example.com/image.jpg"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>

                <!-- Image Preview -->
                <div v-if="previewUrl" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <img :src="previewUrl" alt="Preview" class="max-w-full h-auto max-h-64 mx-auto rounded" />
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
                <button @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="save" :disabled="!canSave"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Save Image
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
    show: Boolean,
    product: Object
})

const emit = defineEmits(['close', 'save'])

const activeTab = ref('upload')
const uploadedFile = ref(null)
const imageUrl = ref('')
const previewUrl = ref('')

// Initialize with product image if exists
watch(() => props.product, (newProduct) => {
    if (newProduct?.image) {
        imageUrl.value = newProduct.image
        previewUrl.value = newProduct.image
        activeTab.value = 'url'
    } else {
        imageUrl.value = ''
        previewUrl.value = ''
        uploadedFile.value = null
    }
}, { immediate: true })

// Watch URL changes for preview
watch(imageUrl, (newUrl) => {
    if (newUrl && activeTab.value === 'url') {
        previewUrl.value = newUrl
    }
})

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        uploadedFile.value = file
        // Create preview URL
        const reader = new FileReader()
        reader.onload = (e) => {
            previewUrl.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

const canSave = computed(() => {
    return (activeTab.value === 'upload' && uploadedFile.value) ||
        (activeTab.value === 'url' && imageUrl.value)
})

const save = () => {
    if (activeTab.value === 'upload' && uploadedFile.value) {
        emit('save', { type: 'file', file: uploadedFile.value })
    } else if (activeTab.value === 'url' && imageUrl.value) {
        emit('save', { type: 'url', url: imageUrl.value })
    }
}
</script>
