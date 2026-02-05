<template>
  <div class="pages-container">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Pages</h1>
        <p class="text-gray-600 mt-1">Manage your dynamic pages</p>
      </div>
      <router-link 
        to="/dashboard/pages/create"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        + Create Page
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
      <p class="text-gray-600 mt-4">Loading pages...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      {{ error }}
    </div>

    <!-- Pages Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sections</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">In Nav</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="page in pages" :key="page.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ page.title }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <a :href="`/pages/${page.slug}`" target="_blank" class="text-sm text-indigo-600 hover:underline">
                /{{ page.slug }}
              </a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ page.sections?.length || 0 }} sections
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  page.is_published 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-800'
                ]"
              >
                {{ page.is_published ? 'Published' : 'Draft' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <button 
                @click="toggleNav(page)"
                :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full transition-colors',
                  page.show_in_nav 
                    ? 'bg-blue-100 text-blue-800 hover:bg-blue-200' 
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
                :disabled="!page.is_published"
                :title="!page.is_published ? 'Page must be published first' : ''"
              >
                {{ page.show_in_nav ? '✓ In Nav' : '○ Hidden' }}
              </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(page.updated_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end gap-2">
                <router-link 
                  :to="`/dashboard/pages/${page.id}/edit`"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  Edit
                </router-link>
                <button 
                  @click="togglePublish(page)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  {{ page.is_published ? 'Unpublish' : 'Publish' }}
                </button>
                <button 
                  @click="duplicatePage(page.id)"
                  class="text-green-600 hover:text-green-900"
                >
                  Duplicate
                </button>
                <button 
                  @click="deletePage(page.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="pages.length === 0" class="text-center py-12">
        <p class="text-gray-500 mb-4">No pages created yet</p>
        <router-link 
          to="/dashboard/pages/create"
          class="text-indigo-600 hover:underline"
        >
          Create your first page
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { useNavigationStore } from '../stores/navigation'

const navigationStore = useNavigationStore()

const pages = ref([])
const loading = ref(true)
const error = ref('')

const loadPages = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/pages')
    pages.value = response.data.data || response.data
  } catch (err) {
    console.error('Failed to load pages:', err)
    error.value = 'Failed to load pages. Please try again.'
  } finally {
    loading.value = false
  }
}

const togglePublish = async (page) => {
  try {
    const endpoint = page.is_published ? 'unpublish' : 'publish'
    await api.post(`/pages/${page.id}/${endpoint}`)
    await loadPages()
    // Refresh navigation when publish status changes
    await navigationStore.refreshNavigation()
  } catch (err) {
    console.error('Failed to toggle publish status:', err)
    alert('Failed to update page status')
  }
}

const toggleNav = async (page) => {
  if (!page.is_published) {
    alert('Page must be published first to appear in navigation')
    return
  }

  try {
    await api.put(`/pages/${page.id}`, {
      show_in_nav: !page.show_in_nav
    })
    await loadPages()
    // Refresh navigation to update navbar immediately
    await navigationStore.refreshNavigation()
  } catch (err) {
    console.error('Failed to toggle nav status:', err)
    alert('Failed to toggle navigation status')
  }
}

const duplicatePage = async (id) => {
  if (!confirm('Duplicate this page?')) return

  try {
    await api.post(`/pages/${id}/duplicate`)
    await loadPages()
  } catch (err) {
    console.error('Failed to duplicate page:', err)
    alert('Failed to duplicate page')
  }
}

const deletePage = async (id) => {
  if (!confirm('Are you sure you want to delete this page? This action cannot be undone.')) return

  try {
    await api.delete(`/pages/${id}`)
    await loadPages()
  } catch (err) {
    console.error('Failed to delete page:', err)
    alert('Failed to delete page')
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  loadPages()
})
</script>
