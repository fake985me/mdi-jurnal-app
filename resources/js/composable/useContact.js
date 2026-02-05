import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

export default function useContact() {
  const contacts = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchContacts = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.get('/api/guest/contact-info')
      contacts.value = response.data
    } catch (err) {
      console.error('Failed to fetch contact info:', err)
      error.value = err.message
      // Fallback data
      contacts.value = [
        { type: 'description', label: 'Description', value: 'Provide your network connectivity solutions Trusted distributor and Experienced technical support for DASAN equipment in Indonesia Offers something that is beyond your reach', order: 1, is_active: true },
        { type: 'address', label: 'Our Address', value: 'Gedung Tifa Arum Realty\n3th Floor Suite 301', order: 1, is_active: true },
        { type: 'phone', label: 'Office', value: '021 2930-6714', order: 1, is_active: true },
        { type: 'email', label: 'Mail', value: 'support@mdi-solutions.com', order: 1, is_active: true },
        { type: 'email', label: '', value: 'info@mdi-solutions.com', order: 2, is_active: true },
        { type: 'sales', label: 'Sales and Marketing', value: 'Hadi : +62 887-0978-7005', order: 1, is_active: true },
        { type: 'sales', label: '', value: 'Karma : +62 877-8900-8833', order: 2, is_active: true },
      ]
    } finally {
      loading.value = false
    }
  }

  // Group contacts by type
  const contactsByType = computed(() => {
    const grouped = {}
    contacts.value.forEach(contact => {
      if (!grouped[contact.type]) {
        grouped[contact.type] = []
      }
      grouped[contact.type].push(contact)
    })
    return grouped
  })

  onMounted(() => {
    fetchContacts()
  })

  return {
    contacts,
    contactsByType,
    loading,
    error,
    fetchContacts
  }
}
