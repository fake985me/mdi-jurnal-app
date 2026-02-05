<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
            <!-- Header -->
            <div class="sticky top-0 bg-gray-100 border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-2xl font-bold text-gray-900">
                    Specification
                </h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- Module Selector -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Product Module
                    </label>
                    <select v-model="selectedModule" @change="loadModuleSpecs"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Module</option>
                        <option value="A">Module A (OLT)</option>
                        <option value="B">Module B (ONT)</option>
                        <option value="C">Module C (Switch)</option>
                        <option value="D">Module D (Router)</option>
                        <option value="E">Module E (Media Converter)</option>
                        <option value="F">Module F (SFP Module)</option>
                        <option value="G">Module G (Accessories)</option>
                        <option value="H">Module H (Other)</option>
                    </select>
                </div>

                <!-- Specification Table -->
                <div class="border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">
                                    Specification
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Value
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(spec, index) in currentSpecs" :key="index" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                    {{ spec.label }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <input v-if="spec.type === 'text'" 
                                        v-model="localSpecs[spec.key]" 
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        :placeholder="spec.placeholder" />
                                    <textarea v-else-if="spec.type === 'textarea'"
                                        v-model="localSpecs[spec.key]"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        :placeholder="spec.placeholder"></textarea>
                                    <select v-else-if="spec.type === 'select'"
                                        v-model="localSpecs[spec.key]"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Select...</option>
                                        <option v-for="opt in spec.options" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
                <button @click="$emit('close')"
                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="save" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Save Specifications
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    product: Object
});

const emit = defineEmits(['close', 'save']);

const selectedModule = ref('');
const localSpecs = ref({});

// Module-specific specification templates
const moduleSpecsTemplate = {
    A: [ // OLT Module
        { key: 'flash_memory', label: 'Flash Memory', type: 'text', placeholder: 'e.g., 8MB(Boot) + 512MB(NOS)' },
        { key: 'sdram_memory', label: 'SDRAM Memory', type: 'text', placeholder: 'e.g., 4GB (DDR4)' },
        { key: 'interface', label: 'Interface', type: 'textarea', placeholder: 'e.g., IU_GPON8 for GPON(SFP)' },
        { key: 'operating_temp', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'storage_temp', label: 'Storage Temp', type: 'text', placeholder: 'e.g., -20 ~ 70°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'power_consumption', label: 'Power Consumption', type: 'text', placeholder: 'e.g., 875W' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 443.8 x 265.9 x 280 mm' }
    ],
    B: [ // ONT Module
        { key: 'interface', label: 'Interface', type: 'text', placeholder: 'e.g., Realtek RTL9601CI' },
        { key: 'operating_temp', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'storage_temp', label: 'Storage Temp', type: 'text', placeholder: 'e.g., -20 ~ 70°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'power_consumption', label: 'Power Consumption', type: 'text', placeholder: 'e.g., 875W' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 443.8 x 265.9 x 280 mm' }
    ],
    C: [ // Switch Module
        { key: 'flash_memory', label: 'Flash Memory', type: 'text', placeholder: 'e.g., 8MB(Boot) + 512MB(NOS)' },
        { key: 'sdram_memory', label: 'SDRAM Memory', type: 'text', placeholder: 'e.g., 4GB (DDR4)' },
        { key: 'interface', label: 'Interface', type: 'textarea', placeholder: 'e.g., IU_GPON8 for GPON(SFP)' },
        { key: 'operating_temp', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., AC 100-240V' }
    ],
    D: [ // Router Module
        { key: 'flash_memory', label: 'Flash Memory', type: 'text', placeholder: 'e.g., 8MB(Boot) + 512MB(NOS)' },
        { key: 'sdram_memory', label: 'SDRAM Memory', type: 'text', placeholder: 'e.g., 4GB (DDR4)' },
        { key: 'interface', label: 'Interface', type: 'textarea', placeholder: 'e.g., IU_GPON8 for GPON(SFP)' },
        { key: 'antena', label: 'Antena', type: 'text', placeholder: 'e.g., IPSec, PPTP, L2TP' },
        { key: 'operating_temp', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 200 x 150 x 40 mm' }
    ],
    E: [ // Media Converter Module  
        { key: 'switching_capacity', label: 'Switching Capacity', type: 'text', placeholder: 'e.g., 1x SC, Single-mode' },
        { key: 'throughput', label: 'Throughput', type: 'text', placeholder: 'e.g., 1x RJ45 10/100/1000M' },
        { key: 'interface', label: 'Interface', type: 'text', placeholder: 'e.g., Tx:1310nm, Rx:1550nm' },
        { key: 'countrol_unit', label: 'Control Unit', type: 'text', placeholder: 'e.g., Up to 20km' },
        { key: 'additional_interface', label: 'Additional Interface', type: 'text', placeholder: 'e.g., DC 5V/1A' },
        { key: 'mac_address', label: 'MAC Address', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' },
        { key: 'routing_table', label: 'Routing Table', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' },
        { key: 'dust_and_waterproff', label: 'Dust and Waterproof', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' },
        { key: 'noise', label: 'Noise', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' },
        { key: 'operating_temp', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'storage_temp', label: 'Storage Temp', type: 'text', placeholder: 'e.g., -20 ~ 70°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' }
    ],
    F: [ // SFP Module
        { key: 'switching_capacity', label: 'Switching Capacity', type: 'text', placeholder: 'e.g., 1x SC, Single-mode' },
        { key: 'throughput', label: 'Throughput', type: 'text', placeholder: 'e.g., 1x RJ45 10/100/1000M' },
        { key: 'interface', label: 'Interface', type: 'text', placeholder: 'e.g., Tx:1310nm, Rx:1550nm' },
        { key: 'additional_interface', label: 'Additional Interface', type: 'text', placeholder: 'e.g., DC 5V/1A' },
        { key: 'operating_temp', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'storage_temp', label: 'Storage Temp', type: 'text', placeholder: 'e.g., -20 ~ 70°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'power_consumption', label: 'Power Consumption', type: 'text', placeholder: 'e.g., 875W' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' }
    ],
    G: [ // Accessories
        { key: 'wireless_standard', label: 'Wireless Standard', type: 'text', placeholder: 'e.g., Patch Cord, Adapter, Splitter' },
        { key: 'wireless_stream', label: 'Wireless Stream', type: 'text', placeholder: 'e.g., 2.4GHz, 5GHz' },
        { key: 'client_capacity', label: 'Client Capacity', type: 'text', placeholder: 'e.g., 2.4GHz, 5GHz' },
        { key: 'anntena', label: 'Antenna', type: 'text', placeholder: 'e.g., 1m, 3m, 5m' },
        { key: 'interface', label: 'Interface', type: 'text', placeholder: 'e.g., SC/UPC, LC/APC' },
        { key: 'ip_rating', label: 'IP Rating', type: 'text', placeholder: 'e.g., IP67' },
        { key: 'operating_temp ', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'storage_temp', label: 'Storage Temp', type: 'text', placeholder: 'e.g., -20 ~ 70°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'power_consumption', label: 'Power Consumption', type: 'text', placeholder: 'e.g., 875W' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' }
    ],
    H: [ // Generic/Other
        { key: 'number_of_manageble_aps', label: 'Number of Manageable APs', type: 'text', placeholder: 'Enter specification...' },
        { key: 'maximum_configurable_ap_number', label: 'Maximum Configurable AP Number', type: 'text', placeholder: 'Enter specification...' },
        { key: 'maximum_number_of_clients', label: 'Maximum Number of Clients', type: 'text', placeholder: 'Enter specification...' },
        { key: 'interface', label: 'Interface', type: 'text', placeholder: 'Enter specification...' },
        { key: 'operating_temp ', label: 'Operating Temp', type: 'text', placeholder: 'e.g., 0 ~ 50°C' },
        { key: 'storage_temp', label: 'Storage Temp', type: 'text', placeholder: 'e.g., -20 ~ 70°C' },
        { key: 'humidity', label: 'Humidity', type: 'text', placeholder: 'e.g., 20 ~ 80% (non-condensing)' },
        { key: 'power', label: 'Power', type: 'text', placeholder: 'e.g., -48VDC' },
        { key: 'power_consumption', label: 'Power Consumption', type: 'text', placeholder: 'e.g., 875W' },
        { key: 'dimensions', label: 'Dimensions', type: 'text', placeholder: 'e.g., 100 x 80 x 25 mm' }
    ]
};

const currentSpecs = ref([]);

// Initialize local specs when product changes
watch(() => props.product, (newProduct) => {
    if (newProduct) {
        selectedModule.value = newProduct.module || '';
        loadModuleSpecs();
        
        // Load existing spec values
        localSpecs.value = {};
        for (let i = 1; i <= 7; i++) {
            localSpecs.value[`spec${i}`] = newProduct[`spec${i}`] || '';
        }
    }
}, { immediate: true });

const loadModuleSpecs = () => {
    if (selectedModule.value && moduleSpecsTemplate[selectedModule.value]) {
        currentSpecs.value = moduleSpecsTemplate[selectedModule.value];
        
        // Initialize empty values for new keys if not exists
        currentSpecs.value.forEach(spec => {
            if (!(spec.key in localSpecs.value)) {
                localSpecs.value[spec.key] = '';
            }
        });
    } else {
        // Default to generic specs
        currentSpecs.value = moduleSpecsTemplate.H;
    }
};

const save = () => {
    // Map local specs to spec1-spec7 format for database
    const specsData = {};
    currentSpecs.value.forEach((spec, index) => {
        specsData[`spec${index + 1}`] = localSpecs.value[spec.key] || '';
    });
    
    emit('save', specsData);
};
</script>
