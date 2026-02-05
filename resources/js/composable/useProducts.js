import { ref, computed } from 'vue'

// Use window.axios which has baseURL configured in bootstrap.js
const axios = window.axios

// Singleton instance to share products across components
const products = ref([])
const loading = ref(false)
const error = ref(null)
let isInitialized = false

export default function useProducts() {
  const transformProduct = (apiProduct) => {
    // The API returns individual fields (fitur1-fitur15, spec1-spec7, interface1-interface5)
    // We need to extract them directly, not parse JSON

    return {
      id: String(apiProduct.id),
      sku: apiProduct.sku || '',
      module: apiProduct.module || 'A',
      image: apiProduct.image || '',
      category: apiProduct.category || '',
      subCategory: apiProduct.sub_category || '',
      brand: apiProduct.brand || '',
      title: apiProduct.title || '',
      subtitle: apiProduct.subtitle || '',
      slug: apiProduct.slug || apiProduct.title?.toLowerCase().replace(/\s+/g, '-'),

      // Specs - use directly from API response
      spec1: apiProduct.spec1 || '',
      spec2: apiProduct.spec2 || '',
      spec3: apiProduct.spec3 || '',
      spec4: apiProduct.spec4 || '',
      spec5: apiProduct.spec5 || '',
      spec6: apiProduct.spec6 || '',
      spec7: apiProduct.spec7 || '',

      // Description
      descriptions: apiProduct.descriptions || '',

      // Features - use directly from API response
      fitur1: apiProduct.fitur1 || '',
      fitur2: apiProduct.fitur2 || '',
      fitur3: apiProduct.fitur3 || '',
      fitur4: apiProduct.fitur4 || '',
      fitur5: apiProduct.fitur5 || '',
      fitur6: apiProduct.fitur6 || '',
      fitur7: apiProduct.fitur7 || '',
      fitur8: apiProduct.fitur8 || '',
      fitur9: apiProduct.fitur9 || '',
      fitur10: apiProduct.fitur10 || '',
      fitur11: apiProduct.fitur11 || '',
      fitur12: apiProduct.fitur12 || '',
      fitur13: apiProduct.fitur13 || '',
      fitur14: apiProduct.fitur14 || '',
      fitur15: apiProduct.fitur15 || '',

      // Interfaces - use directly from API response
      Interface: apiProduct.interface_main || '',
      Interface1: apiProduct.interface1 || '',
      Interface2: apiProduct.interface2 || '',
      Interface3: apiProduct.interface3 || '',
      Interface4: apiProduct.interface4 || '',
      Interface5: apiProduct.interface5 || '',

      // Stock information
      stock: apiProduct.current_stock?.quantity || 0,

      // Additional specification fields from database
      wirelessStandard: apiProduct.wireless_standard || '',
      wirelessStream: apiProduct.wireless_stream || '',
      manageableAps: apiProduct.manageable_aps || '',
      APnumber: apiProduct.ap_number || '',
      numberOfClients: apiProduct.number_of_clients || '',
      capacity: apiProduct.capacity || '',
      iprating: apiProduct.ip_rating || '',
      switching: apiProduct.switching || '',
      throughput: apiProduct.throughput || '',
      flashmemory: apiProduct.flash_memory || '',
      sdrammemory: apiProduct.sdram_memory || '',
      antena: apiProduct.antena || '',
      cu: apiProduct.cu || '',
      macaddress: apiProduct.mac_address || '',
      routingtable: apiProduct.routing_table || '',
      dustproofwaterproof: apiProduct.dustproof_waterproof || '',
      noise: apiProduct.noise || '',
      mtbf: apiProduct.mtbf || '',
      operatingtemperature: apiProduct.operating_temperature || '',
      storagetemperature: apiProduct.storage_temperature || '',
      operatinghumidity: apiProduct.operating_humidity || '',
      power1: apiProduct.power1 || '',
      power2: apiProduct.power2 || '',
      power3: apiProduct.power3 || '',
      powercomsumptions: apiProduct.power_consumptions || '',
      dimensions: apiProduct.dimensions || '',
      diagram: apiProduct.diagram || '',
      networkDiagram: apiProduct.network_diagram || ''
    }
  }

  const fetchProducts = async (forceRefresh = false) => {
    if (products.value.length > 0 && !forceRefresh) return // Already loaded, skip unless force refresh

    loading.value = true
    error.value = null

    try {
      // Fetch all PUBLIC products with specifications (no pagination limit)
      const response = await axios.get('/api/guest/public-products', {
        params: {
          per_page: 100, // Get all products
          _t: Date.now() // Cache buster
        }
      })

      products.value = response.data.data.map(transformProduct)
      isInitialized = true
    } catch (err) {
      console.error('Failed to fetch products:', err)
      error.value = err.message
      products.value = []
    } finally {
      loading.value = false
    }
  }

  // Clear cache and force reload
  const clearCache = () => {
    products.value = []
    isInitialized = false
  }

  // Auto-fetch on first use
  if (!isInitialized) {
    fetchProducts()
  }

  return {
    products,
    loading,
    error,
    fetchProducts,
    clearCache
  }
}

/**
 * Find a product by slug
 * @param {string} slug - Product slug to search for
 * @returns {ComputedRef<object|null>} - Computed ref of found product or null
 */
export function useProductBySlug(slug) {
  const { products } = useProducts()

  return computed(() => {
    if (!slug || !products.value) return null
    return products.value.find(p => p.slug === slug) || null
  })
}
