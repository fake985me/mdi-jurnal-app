import { ref, computed } from 'vue'

// Use window.axios which has baseURL configured in bootstrap.js
const axios = window.axios

// Singleton instances to share across components
const products = ref([])
const categories = ref([])
const loading = ref(false)
const error = ref(null)
let isInitialized = false

export default function useProducts() {
  const transformProduct = (apiProduct) => {
    let categoryStr = apiProduct.category || ''
    let subCategoryStr = apiProduct.sub_category || ''

    // Always prefer category_pairs (pivot table data) when available
    if (apiProduct.category_pairs && apiProduct.category_pairs.length > 0) {
      categoryStr = apiProduct.category_pairs.map(p => p.category_name).filter(Boolean).join(' | ')
      subCategoryStr = apiProduct.category_pairs.map(p => p.sub_category_name).filter(Boolean).join(' | ')
    }

    // Multi-category & Multi-subcategory lists
    const catList = categoryStr ? categoryStr.split('|').map(s => s.trim()).filter(Boolean) : []
    const subList = subCategoryStr ? subCategoryStr.split('|').map(s => s.trim()).filter(Boolean) : []

    // Also ensure any additional entries from category_pairs are included
    if (apiProduct.category_pairs && Array.isArray(apiProduct.category_pairs)) {
      apiProduct.category_pairs.forEach(p => {
        if (p.category_name && !catList.includes(p.category_name)) catList.push(p.category_name)
        if (p.sub_category_name && !subList.includes(p.sub_category_name)) subList.push(p.sub_category_name)
      })
    }

    // Multi-diagram lists
    const rawDiagram = apiProduct.diagram || ''
    const rawNetworkDiagram = apiProduct.network_diagram || apiProduct.networkDIagram || ''
    const diagrams = rawDiagram ? rawDiagram.split('|').map(s => s.trim()).filter(Boolean) : []
    const networkDiagrams = rawNetworkDiagram ? rawNetworkDiagram.split('|').map(s => s.trim()).filter(Boolean) : []

    const transformed = {
      id: String(apiProduct.id),
      sku: apiProduct.sku || '',
      module: apiProduct.module || 'A',
      image: apiProduct.image || '',
      category: categoryStr,
      subCategory: subCategoryStr,
      categories: catList,
      subCategories: subList,
      category_pairs: apiProduct.category_pairs || [],
      brand: apiProduct.brand || '',
      title: apiProduct.title || '',
      subtitle: apiProduct.subtitle || '',
      slug: apiProduct.slug || apiProduct.title?.toLowerCase().replace(/\s+/g, '-'),
      descriptions: apiProduct.descriptions || '',
      stock: apiProduct.stock ?? apiProduct.current_stock?.quantity ?? 0,

      // Wireless
      wirelessStandard: apiProduct.wireless_standard || apiProduct.wirelessStandard || '',
      wirelessStream: apiProduct.wireless_stream || apiProduct.wirelessStream || '',
      wirelessStream1: apiProduct.wireless_stream1 || apiProduct.wirelessStream1 || '',
      wirelessStream2: apiProduct.wireless_stream2 || apiProduct.wirelessStream2 || '',
      wirelessStream3: apiProduct.wireless_stream3 || apiProduct.wirelessStream3 || '',
      wirelessStream4: apiProduct.wireless_stream4 || apiProduct.wirelessStream4 || '',
      wirelessStream5: apiProduct.wireless_stream5 || apiProduct.wirelessStream5 || '',

      // Capacity & Controller
      manageableAps: apiProduct.manageable_aps || apiProduct.manageableAps || '',
      APnumber: apiProduct.ap_number || apiProduct.APnumber || '',
      numberOfClients: apiProduct.number_of_clients || apiProduct.numberOfClients || '',
      capacity: apiProduct.capacity || '',
      iprating: apiProduct.ip_rating || apiProduct.iprating || '',
      switching: apiProduct.switching || '',
      throughput: apiProduct.throughput || '',

      // Memory
      flashmemory: apiProduct.flash_memory || apiProduct.flashmemory || '',
      sdrammemory: apiProduct.sdram_memory || apiProduct.sdrammemory || '',

      // Interfaces
      Interface: apiProduct.interface_main || apiProduct.Interface || '',
      Interface1: apiProduct.interface1 || apiProduct.Interface1 || '',
      Interface2: apiProduct.interface2 || apiProduct.Interface2 || '',
      Interface3: apiProduct.interface3 || apiProduct.Interface3 || '',
      Interface4: apiProduct.interface4 || apiProduct.Interface4 || '',
      Interface5: apiProduct.interface5 || apiProduct.Interface5 || '',
      antena: apiProduct.antena || apiProduct.antenna || '',

      // Control Unit
      cu: apiProduct.cu || '',
      cu1: apiProduct.cu1 || '',
      cu2: apiProduct.cu2 || '',
      cu3: apiProduct.cu3 || '',
      cu4: apiProduct.cu4 || '',
      cu5: apiProduct.cu5 || '',

      // Additional Interfaces
      aditionalinterface1: apiProduct.additional_interface1 || apiProduct.aditionalinterface1 || '',
      aditionalinterface2: apiProduct.additional_interface2 || apiProduct.aditionalinterface2 || '',
      aditionalinterface3: apiProduct.additional_interface3 || apiProduct.aditionalinterface3 || '',
      aditionalinterface4: apiProduct.additional_interface4 || apiProduct.aditionalinterface4 || '',
      aditionalinterface5: apiProduct.additional_interface5 || apiProduct.aditionalinterface5 || '',

      // Network
      macaddress: apiProduct.mac_address || apiProduct.macaddress || '',
      routingtable: apiProduct.routing_table || apiProduct.routingtable || '',

      // Environmental
      dustproofwaterproof: apiProduct.dustproof_waterproof || apiProduct.dustproofwaterproof || '',
      noise: apiProduct.noise || '',
      mtbf: apiProduct.mtbf || '',
      operatingtemperature: apiProduct.operating_temperature || apiProduct.operatingtemperature || '',
      storagetemperature: apiProduct.storage_temperature || apiProduct.storagetemperature || '',
      operatinghumidity: apiProduct.operating_humidity || apiProduct.operatinghumidity || '',

      // Power
      power1: apiProduct.power1 || '',
      power2: apiProduct.power2 || '',
      power3: apiProduct.power3 || '',
      power4: apiProduct.power4 || '',
      power5: apiProduct.power5 || '',
      powercomsumptions: apiProduct.power_consumptions || apiProduct.powercomsumptions || '',
      powerConsumptionWithPoE: apiProduct.power_consumption_with_poe || apiProduct['PowerConsumption(withPoE)'] || '',
      powerConsumptionExcludingPoE: apiProduct.power_consumption_excluding_poe || apiProduct['PowerConsumption(ExcludingPoE)'] || '',
      poeBudget: apiProduct.poe_budget || apiProduct.PoEBudget || '',

      // Physical & Diagram
      dimensions: apiProduct.dimensions || '',
      rawCategory: apiProduct.category || '',
      rawSubCategory: apiProduct.sub_category || '',
      diagram: rawDiagram,
      diagrams: diagrams.length > 0 ? diagrams : (rawDiagram ? [rawDiagram] : []),
      networkDiagram: rawNetworkDiagram,
      networkDiagrams: networkDiagrams.length > 0 ? networkDiagrams : (rawNetworkDiagram ? [rawNetworkDiagram] : [])
    }

    // Specs 1-20
    for (let i = 1; i <= 20; i++) {
      transformed[`spec${i}`] = apiProduct[`spec${i}`] || ''
    }

    // Features 1-20
    for (let i = 1; i <= 20; i++) {
      transformed[`fitur${i}`] = apiProduct[`fitur${i}`] || ''
    }

    return transformed
  }

  const fetchProducts = async (forceRefresh = false) => {
    if (products.value.length > 0 && !forceRefresh) return

    loading.value = true
    error.value = null

    try {
      // Also refresh categories when force-refreshing
      if (forceRefresh) {
        fetchCategories(true)
      }

      // Fetch all public products with specifications
      const response = await axios.get('/api/guest/public-products', {
        params: {
          per_page: 200,
          _t: Date.now()
        }
      })

      const list = response.data.data || response.data || []
      products.value = list.map(transformProduct)
      isInitialized = true
    } catch (err) {
      console.error('Failed to fetch products:', err)
      error.value = err.message
      products.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchCategories = async (forceRefresh = false) => {
    if (categories.value.length > 0 && !forceRefresh) return categories.value

    try {
      const response = await axios.get('/api/guest/categories', {
        params: { _t: Date.now() }
      })
      categories.value = response.data || []
      return categories.value
    } catch (err) {
      console.error('Failed to fetch categories:', err)
      return []
    }
  }

  const clearCache = () => {
    products.value = []
    categories.value = []
    isInitialized = false
  }

  // Auto-fetch on first use
  if (!isInitialized) {
    fetchProducts()
    fetchCategories()
  }

  return {
    products,
    categories,
    loading,
    error,
    fetchProducts,
    fetchCategories,
    clearCache
  }
}

/**
 * Find a product by slug
 */
export function useProductBySlug(slug) {
  const { products } = useProducts()

  return computed(() => {
    if (!slug || !products.value) return null
    return products.value.find(p => p.slug === slug || String(p.id) === String(slug)) || null
  })
}
