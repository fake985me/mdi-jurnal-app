<template>
  <div class="page-form-container">
    <!-- Header Bar -->
    <div class="fixed top-0 left-64 right-0 bg-white border-b border-gray-200 z-50 shadow-sm">
      <div class="flex justify-between items-center px-6 py-3">
        <div class="flex items-center gap-4">
          <router-link to="/dashboard/pages" class="text-gray-600 hover:text-gray-900">
            ← Back
          </router-link>
          <div class="border-l border-gray-300 pl-4">
            <h1 class="text-lg font-bold text-gray-900">{{ isEdit ? 'Edit Page' : 'Create Page' }}</h1>
            <p class="text-xs text-gray-500">{{ page.title || 'Untitled Page' }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <!-- Responsive Preview Selector (only in preview mode) -->
          <div v-if="showPreview" class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg">
            <button
              @click="previewMode = 'mobile'"
              :class="['px-3 py-1 rounded text-sm', previewMode === 'mobile' ? 'bg-white shadow' : 'hover:bg-gray-200']"
              title="Mobile Preview"
            >
              📱
            </button>
            <button
              @click="previewMode = 'tablet'"
              :class="['px-3 py-1 rounded text-sm', previewMode === 'tablet' ? 'bg-white shadow' : 'hover:bg-gray-200']"
              title="Tablet Preview"
            >
              💻
            </button>
            <button
              @click="previewMode = 'desktop'"
              :class="['px-3 py-1 rounded text-sm', previewMode === 'desktop' ? 'bg-white shadow' : 'hover:bg-gray-200']"
              title="Desktop Preview"
            >
              🖥️
            </button>
          </div>

          <button
            @click="showMetaModal = true"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm"
          >
            ⚙️ Page Settings
          </button>
          <button
            @click="togglePreview"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
          >
            {{ showPreview ? '✏️ Edit' : '👁️ Preview' }}
          </button>
          <button
            @click="undoAction"
            :disabled="!canUndo"
            class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-30 text-sm"
            title="Undo"
          >
            ↶
          </button>
          <button
            @click="redoAction"
            :disabled="!canRedo"
            class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-30 text-sm"
            title="Redo"
          >
            ↷
          </button>
          <button
            @click="exportHTML"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
            title="Export HTML"
          >
            📤 Export
          </button>
          <button
            @click="savePage"
            :disabled="saving"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 text-sm font-medium"
          >
            {{ saving ? 'Saving...' : '💾 Save Page' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center h-screen">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Loading page...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="pt-16">
      <!-- Preview Mode -->
      <div v-if="showPreview" class="preview-container bg-gray-100 flex justify-center items-start min-h-screen py-8">
        <div
          :class="[
            'preview-frame bg-white shadow-2xl transition-all duration-300',
            previewMode === 'mobile' ? 'w-[375px]' : '',
            previewMode === 'tablet' ? 'w-[768px]' : '',
            previewMode === 'desktop' ? 'w-full max-w-[1440px]' : ''
          ]"
        >
          <SectionRenderer
            v-for="(section, index) in sections"
            :key="index"
            :section="section"
            :is-preview="false"
          />
        </div>
      </div>

      <!-- Edit Mode - Drag & Drop Builder -->
      <DragDropBuilder
        v-else
        v-model="sections"
        :page-title="page.title"
        @update:modelValue="recordHistory"
      />
    </div>

    <!-- Page Settings Modal -->
    <div v-if="showMetaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
        <h3 class="text-xl font-bold mb-4">Page Settings</h3>

        <form @submit.prevent="showMetaModal = false" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Page Title *</label>
            <input
              v-model="page.title"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="My Awesome Page"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug *</label>
            <div class="flex items-center gap-2">
              <span class="text-gray-500">/pages/</span>
              <input
                v-model="page.slug"
                required
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                placeholder="my-awesome-page"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">URL-friendly name (lowercase, hyphens only)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
            <textarea
              v-model="page.meta_description"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="A brief description for search engines (150-160 characters)"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
            <input
              v-model="page.meta_keywords"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="keyword1, keyword2, keyword3"
            />
          </div>

          <!-- Navigation Settings -->
          <div class="border-t border-gray-200 pt-4 mt-4">
            <h4 class="text-md font-semibold text-gray-800 mb-3">🧭 Navigation Settings</h4>
            
            <div class="flex items-center gap-3 mb-3">
              <input
                type="checkbox"
                id="showInNav"
                v-model="page.show_in_nav"
                class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
              />
              <label for="showInNav" class="text-sm text-gray-700">Show this page in navigation menu</label>
            </div>

            <div v-if="page.show_in_nav" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nav Label</label>
                <input
                  v-model="page.nav_label"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                  placeholder="Menu label (default: page title)"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nav Order</label>
                <input
                  type="number"
                  v-model.number="page.nav_order"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                  placeholder="100"
                />
                <p class="text-xs text-gray-500 mt-1">Lower = appears first</p>
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Menu (optional)</label>
                <select
                  v-model="page.nav_parent"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">-- Top Level (no parent) --</option>
                  <option value="Product">Product</option>
                  <option value="Projects">Projects</option>
                  <option value="Service & Solutions">Service & Solutions</option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showMetaModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Close
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import DragDropBuilder from '../components/PageBuilder/DragDropBuilder.vue'
import SectionRenderer from '../components/PageBuilder/SectionRenderer.vue'
import { useNavigationStore } from '../stores/navigation'

const route = useRoute()
const router = useRouter()
const navigationStore = useNavigationStore()

const isEdit = computed(() => !!route.params.id)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const showMetaModal = ref(false)
const showPreview = ref(false)
const previewMode = ref('desktop') // mobile, tablet, desktop

const page = ref({
  title: '',
  slug: '',
  meta_description: '',
  meta_keywords: '',
  show_in_nav: false,
  nav_order: 100,
  nav_parent: '',
  nav_label: '',
})

const sections = ref([])

// Undo/Redo functionality
const history = ref([])
const historyIndex = ref(-1)
const maxHistory = 50

const canUndo = computed(() => historyIndex.value > 0)
const canRedo = computed(() => historyIndex.value < history.value.length - 1)

const loadPage = async () => {
  if (!isEdit.value) return

  loading.value = true
  try {
    const response = await api.get(`/pages/${route.params.id}`)
    const data = response.data

    page.value = {
      title: data.title,
      slug: data.slug,
      meta_description: data.meta_description || '',
      meta_keywords: data.meta_keywords || '',
      show_in_nav: data.show_in_nav || false,
      nav_order: data.nav_order || 100,
      nav_parent: data.nav_parent || '',
      nav_label: data.nav_label || '',
    }

    sections.value = (data.sections || []).map(section => ({
      id: section.id,
      uid: section.id,
      section_type: section.section_type,
      content: section.content || {},
      settings: section.settings || {},
      is_visible: section.is_visible !== false,
      order: section.order,
    }))
  } catch (err) {
    console.error('Failed to load page:', err)
    error.value = 'Failed to load page'
  } finally {
    loading.value = false
  }
}

const togglePreview = () => {
  showPreview.value = !showPreview.value
}

// History Management
const recordHistory = () => {
  // Remove any redo history when making a new change
  if (historyIndex.value < history.value.length - 1) {
    history.value = history.value.slice(0, historyIndex.value + 1)
  }

  // Add current state to history
  history.value.push(JSON.parse(JSON.stringify(sections.value)))
  historyIndex.value++

  // Limit history size
  if (history.value.length > maxHistory) {
    history.value.shift()
    historyIndex.value--
  }
}

const undoAction = () => {
  if (canUndo.value) {
    historyIndex.value--
    sections.value = JSON.parse(JSON.stringify(history.value[historyIndex.value]))
  }
}

const redoAction = () => {
  if (canRedo.value) {
    historyIndex.value++
    sections.value = JSON.parse(JSON.stringify(history.value[historyIndex.value]))
  }
}

// Export HTML
const exportHTML = () => {
  // Build HTML parts separately to avoid Vue parsing issues
  const htmlParts = []

  htmlParts.push('<!DOCTYPE html>')
  htmlParts.push('<html lang="en">')
  htmlParts.push('<head>')
  htmlParts.push('  <meta charset="UTF-8">')
  htmlParts.push('  <meta name="viewport" content="width=device-width, initial-scale=1.0">')
  htmlParts.push('  <title>' + (page.value.title || 'Untitled Page') + '</title>')

  if (page.value.meta_description) {
    htmlParts.push('  <meta name="description" content="' + page.value.meta_description + '">')
  }
  if (page.value.meta_keywords) {
    htmlParts.push('  <meta name="keywords" content="' + page.value.meta_keywords + '">')
  }

  htmlParts.push('  <scr' + 'ipt src="https://cdn.tailwindcss.com"></scr' + 'ipt>')
  htmlParts.push('  <style>')
  htmlParts.push('    body { margin: 0; padding: 0; }')
  htmlParts.push('  </style>')
  htmlParts.push('</head>')
  htmlParts.push('<body>')

  // Render each section as HTML
  sections.value.forEach(section => {
    if (!section.is_visible) return

    htmlParts.push('')
    htmlParts.push('  <!-- Section: ' + section.section_type + ' -->')
    htmlParts.push('  <section class="section-' + section.section_type + '">')

    if (section.content.heading) {
      htmlParts.push('    <h2>' + section.content.heading + '</h2>')
    }
    if (section.content.subheading) {
      htmlParts.push('    <p>' + section.content.subheading + '</p>')
    }
    if (section.content.text) {
      htmlParts.push('    <div>' + section.content.text + '</div>')
    }
    if (section.content.image_url) {
      htmlParts.push('    <img src="' + section.content.image_url + '" alt="' + (section.content.heading || '') + '">')
    }
    if (section.content.button_text) {
      htmlParts.push('    <a href="' + (section.content.button_url || '#') + '">' + section.content.button_text + '</a>')
    }

    htmlParts.push('  </section>')
  })

  htmlParts.push('')
  htmlParts.push('</body>')
  htmlParts.push('</html>')

  const html = htmlParts.join('\n')

  // Download HTML file
  const blob = new Blob([html], { type: 'text/html' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = (page.value.slug || 'page') + '.html'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const savePage = async () => {
  if (!page.value.title || !page.value.slug) {
    error.value = 'Please fill in required fields (Title and Slug)'
    showMetaModal.value = true
    return
  }

  saving.value = true
  error.value = ''

  try {
    let pageResponse

    if (isEdit.value) {
      // Update existing page
      pageResponse = await api.put(`/pages/${route.params.id}`, page.value)
      const pageId = route.params.id

      // Delete all existing sections and recreate
      const existingSections = sections.value.filter(s => s.id)
      for (const section of existingSections) {
        await api.delete(`/page-sections/${section.id}`)
      }

      // Create all sections fresh
      for (const section of sections.value) {
        await api.post('/page-sections', {
          page_id: pageId,
          section_type: section.section_type,
          content: section.content,
          settings: section.settings,
          is_visible: section.is_visible,
          order: section.order,
        })
      }
    } else {
      // Create new page
      pageResponse = await api.post('/pages', page.value)
      const pageId = pageResponse.data.id

      // Create sections
      for (const section of sections.value) {
        await api.post('/page-sections', {
          page_id: pageId,
          section_type: section.section_type,
          content: section.content,
          settings: section.settings,
          is_visible: section.is_visible,
          order: section.order,
        })
      }
    }

    // Refresh navigation to update navbar if show_in_nav changed
    if (page.value.show_in_nav) {
      await navigationStore.refreshNavigation()
    }

    router.push('/dashboard/pages')
  } catch (err) {
    console.error('Failed to save page:', err)
    error.value = err.response?.data?.message || 'Failed to save page'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (isEdit.value) {
    loadPage()
  }

  // Initialize history with current state
  recordHistory()

  // Keyboard shortcuts
  const handleKeyDown = (e) => {
    // Ctrl+Z or Cmd+Z for undo
    if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
      e.preventDefault()
      undoAction()
    }
    // Ctrl+Shift+Z or Cmd+Shift+Z or Ctrl+Y for redo
    if (((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'z') || (e.ctrlKey && e.key === 'y')) {
      e.preventDefault()
      redoAction()
    }
    // Ctrl+S or Cmd+S for save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault()
      savePage()
    }
  }

  window.addEventListener('keydown', handleKeyDown)

  // Cleanup
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
  })
})
</script>

<style scoped>
.page-form-container {
  min-height: 100vh;
  background: #f9fafb;
}

.preview-container {
  min-height: calc(100vh - 64px);
}
</style>
