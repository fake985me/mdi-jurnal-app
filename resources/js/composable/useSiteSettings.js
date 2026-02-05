import { ref, onMounted } from 'vue'

// Use window.axios which has baseURL configured in bootstrap.js
const axios = window.axios

export default function useSiteSettings() {
  const settings = ref({})
  const loading = ref(false)
  const error = ref(null)

  const fetchSettings = async (group = null) => {
    loading.value = true
    error.value = null

    try {
      const params = group ? { group } : {}
      const response = await axios.get('/api/guest/site-settings', { params })
      settings.value = response.data
    } catch (err) {
      console.error('Failed to fetch site settings:', err)
      error.value = err.message
      settings.value = {}
    } finally {
      loading.value = false
    }
  }

  const getSetting = (key, defaultValue = '') => {
    return settings.value[key] || defaultValue
  }

  // Auto-fetch on mount
  onMounted(() => {
    fetchSettings()
  })

  return {
    settings,
    loading,
    error,
    fetchSettings,
    getSetting
  }
}
