<template>
  <div v-if="loading" class="min-h-screen flex items-center justify-center">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
      <p class="text-gray-600">Loading page...</p>
    </div>
  </div>

  <div v-else-if="error" class="min-h-screen flex items-center justify-center">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Page Not Found</h1>
      <p class="text-gray-600 mb-8">{{ error }}</p>
      <router-link to="/" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        Go Home
      </router-link>
    </div>
  </div>

  <div v-else>
    <!-- SEO Meta Tags (set in head) -->
    <component 
      v-for="(section, index) in visibleSections" 
      :key="`section-${section.id}-${index}`"
      :is="getSectionComponent(section.section_type)"
      :content="section.content"
      :settings="section.settings || {}"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { SECTION_COMPONENTS } from '@/components/sections/index.js'

const route = useRoute()
const page = ref(null)
const loading = ref(true)
const error = ref('')

// Fetch page data by slug
const fetchPage = async (slug) => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await axios.get(`/api/guest/pages/slug/${slug}`)
    page.value = response.data
    
    // Set page title
    if (page.value) {
      document.title = page.value.title
      
      // Set meta tags
      if (page.value.meta_description) {
        let metaDesc = document.querySelector('meta[name="description"]')
        if (!metaDesc) {
          metaDesc = document.createElement('meta')
          metaDesc.name = 'description'
          document.head.appendChild(metaDesc)
        }
        metaDesc.content = page.value.meta_description
      }
    }
  } catch (err) {
    console.error('Failed to fetch page:', err)
    error.value = err.response?.status === 404 
      ? 'The page you are looking for does not exist.' 
      : 'Failed to load page. Please try again later.'
  } finally {
    loading.value = false
  }
}

// Get visible sections only
const visibleSections = computed(() => {
  if (!page.value?.sections) return []
  return page.value.sections.filter(s => s.is_visible !== false)
})

// Map section type to component
const getSectionComponent = (type) => {
  const component = SECTION_COMPONENTS[type]
  if (!component) {
    console.warn(`Unknown section type: ${type}`)
    return null
  }
  return component
}

// Watch route changes
watch(() => route.params.slug, (newSlug) => {
  if (newSlug) {
    fetchPage(newSlug)
  }
}, { immediate: true })

onMounted(() => {
  if (route.params.slug) {
    fetchPage(route.params.slug)
  }
})
</script>
