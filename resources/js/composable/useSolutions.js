import { onMounted, ref } from 'vue'
import api from '../services/api'

export default function useSolutions() {
  const solutions = ref([])
  const loading = ref(false)
  const error = ref('')

  const loadSolutions = async () => {
    loading.value = true
    error.value = ''

    try {
      const response = await api.get('/guest/solutions')
      solutions.value = response.data
    } catch (err) {
      console.error('Failed to load solutions:', err)
      error.value = err.response?.data?.message || 'Failed to load solutions'
    } finally {
      loading.value = false
    }
  }

  onMounted(loadSolutions)

  return {
    solutions,
    loading,
    error,
  }
}
