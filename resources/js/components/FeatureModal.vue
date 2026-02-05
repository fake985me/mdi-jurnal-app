<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">
                    Edit Product Features
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
                <!-- Description Field -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Product Description
                    </label>
                    <textarea v-model="localDescription" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter product description..."></textarea>
                </div>

                <hr class="mb-6" />

                <h4 class="text-md font-semibold text-gray-700 mb-4">Product Features</h4>

                <div class="grid grid-cols-1 gap-4">
                    <div v-for="i in 15" :key="i" class="flex items-center gap-3">
                        <label class="w-20 text-sm font-medium text-gray-700">
                            Fitur {{ i }}
                        </label>
                        <input v-model="localFeatures[`fitur${i}`]" type="text"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :placeholder="`Feature ${i}`" />
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
                <button @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="save" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    product: Object
})

const emit = defineEmits(['close', 'save'])

const localFeatures = ref({})
const localDescription = ref('')

// Initialize local features and description when product changes
watch(() => props.product, (newProduct) => {
    if (newProduct) {
        localFeatures.value = {}
        for (let i = 1; i <= 15; i++) {
            localFeatures.value[`fitur${i}`] = newProduct[`fitur${i}`] || ''
        }
        localDescription.value = newProduct.descriptions || ''
    }
}, { immediate: true })

const save = () => {
    emit('save', {
        ...localFeatures.value,
        descriptions: localDescription.value
    })
}
</script>
