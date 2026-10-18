<template>
  <div class="space-y-6 relative">
    <!-- Toast Notification -->
    <Transition name="slide-fade">
      <div v-if="toast.show"
        :class="[
          'fixed top-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-lg text-sm font-medium',
          toast.type === 'error'
            ? 'bg-red-600 text-white'
            : 'bg-green-600 text-white'
        ]">
        <span>{{ toast.type === 'error' ? '✕' : '✓' }}</span>
        {{ $ui(toast.message) }}
      </div>
    </Transition>

    <!-- Info Banner -->
    <div class="bg-linear-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
      <div class="flex items-start gap-3">
        <span class="text-2xl">🌐</span>
        <div>
          <p class="text-sm font-semibold text-blue-900 mb-1">{{ $t('ui.publicPageStructure') }} <a href="http://moimstone.test/" target="_blank" class="underline hover:text-blue-600">moimstone.test</a></p>
          <p class="text-xs text-blue-700">
            {{ $t('ui.halamanDefaultBawaanWebsiteTidakBisaDihapusHalamanCustomBaruYangAndaTambahkanBisa') }}
          </p>
        </div>
      </div>
    </div>

    <!-- DEFAULT PAGES (Fixed — isi dari homepage moimstone.test) -->
    <div>
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t('ui.halamanDefaultFixed') }}</h2>
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('nav.pages') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('ui.publicUrl') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('ui.sectionsKonten') }}</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('ui.manage') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="page in defaultPages" :key="page.path" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="text-xl">{{ page.icon }}</span>
                  <div>
                    <div class="text-sm font-semibold text-gray-900">{{ $ui(page.name) }}</div>
                    <div class="text-xs text-gray-400">{{ $ui(page.description) }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <a :href="page.path" target="_blank"
                  class="text-xs text-indigo-600 hover:underline font-mono bg-indigo-50 px-2 py-0.5 rounded">
                  {{ page.path }}
                </a>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="sec in page.sections" :key="sec"
                    class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full border border-gray-200">
                    {{ $ui(sec) }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                  {{ $t('labels.active') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-2 flex-wrap">
                  <router-link v-for="action in page.actions" :key="action.label"
                    :to="action.to"
                    class="text-xs px-2.5 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-all">
                    {{ $ui(action.label) }}
                  </router-link>
                  <a :href="page.path" target="_blank"
                    class="text-xs px-2.5 py-1 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition-all">
                    {{ $t('ui.preview2') }}
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CUSTOM PAGES -->
    <div>
      <div class="flex justify-between items-center mb-3">
        <div>
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ $t('ui.halamanCustom') }}</h2>
          <p class="text-xs text-gray-400 mt-0.5">{{ $t('ui.newPagesCanAppearInThePublicNavigationMenu') }}</p>
        </div>
        <router-link
          to="/dashboard/pages/create"
          class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-1.5 shadow-sm"
        >
          <span class="text-base leading-none">+</span> {{ $t('ui.addPage') }}
        </router-link>
      </div>

      <div v-if="loading" class="py-10 text-center">
        <div class="animate-spin rounded-full h-7 w-7 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="text-gray-400 text-xs mt-3">{{ $t('ui.loadingPage') }}</p>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 text-sm">
        {{ $ui(error) }}
      </div>

      <div v-else-if="pages.length === 0"
        class="bg-white rounded-xl border border-dashed border-gray-300 py-14 text-center">
        <p class="text-gray-300 text-4xl mb-3">📄</p>
        <p class="text-gray-500 text-sm font-medium mb-1">{{ $t('ui.belumAdaHalamanCustom') }}</p>
        <p class="text-gray-400 text-xs mb-4">{{ $t('ui.tambahkanHalamanBaruSepertiBlogFaqAtauLandingPageKhusus') }}</p>
        <router-link to="/dashboard/pages/create"
          class="inline-flex items-center gap-1 text-indigo-600 hover:underline text-sm font-medium">
          {{ $t('ui.createYourFirstPage2') }}
        </router-link>
      </div>

      <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('nav.pages') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">URL</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('ui.inNav') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="page in pages" :key="page.id"
              :class="['transition-colors', confirmDeleteId === page.id ? 'bg-red-50' : 'hover:bg-gray-50']">
              <td class="px-4 py-3">
                <div class="text-sm font-medium text-gray-900">{{ page.title }}</div>
                <div class="text-xs text-gray-400">{{ page.sections?.length || 0 }} {{ $t('ui.sectionS') }}</div>
              </td>
              <td class="px-4 py-3">
                <a :href="`/pages/${page.slug}`" target="_blank"
                  class="text-xs text-indigo-600 hover:underline font-mono bg-indigo-50 px-2 py-0.5 rounded">
                  /pages/{{ page.slug }}
                </a>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="[
                  'inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium',
                  page.is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-50 text-yellow-700 border border-yellow-200'
                ]">
                  <span :class="['w-1.5 h-1.5 rounded-full inline-block', page.is_published ? 'bg-green-500' : 'bg-yellow-400']"></span>
                  {{ page.is_published ? $t('labels.published') : $t('labels.draft') }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="toggleNav(page)"
                  :disabled="!page.is_published"
                  :title="!page.is_published ? $t('ui.publishFirstToAppearInNavigation') : ''"
                  :class="[
                    'relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none',
                    !page.is_published ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                    page.show_in_nav ? 'bg-indigo-600' : 'bg-gray-200'
                  ]"
                >
                  <span :class="[
                    'inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform',
                    page.show_in_nav ? 'translate-x-4' : 'translate-x-0.5'
                  ]" />
                </button>
                <div class="text-xs mt-0.5" :class="page.show_in_nav ? 'text-indigo-600' : 'text-gray-400'">
                  {{ page.show_in_nav ? $t('ui.visible') : $t('ui.hidden') }}
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <!-- Mode normal -->
                <div v-if="confirmDeleteId !== page.id" class="flex justify-end items-center gap-1.5">
                  <router-link :to="`/dashboard/pages/${page.id}/edit`"
                    class="px-2.5 py-1 text-xs text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">{{ $t('common.edit') }}</router-link>
                  <button @click="togglePublish(page)"
                    class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                    {{ page.is_published ? $t('ui.unpublish') : $t('ui.publish') }}
                  </button>
                  <button @click="duplicatePage(page.id)"
                    class="px-2.5 py-1 text-xs text-green-600 hover:bg-green-50 rounded-lg transition-colors">{{ $t('ui.duplicate') }}</button>
                  <button @click="confirmDeleteId = page.id"
                    class="px-2.5 py-1 text-xs text-red-500 hover:bg-red-50 hover:border-red-200 border border-transparent rounded-lg transition-colors">{{ $t('common.delete') }}</button>
                </div>
                <!-- Mode konfirmasi hapus (inline — tidak butuh window.confirm) -->
                <div v-else class="flex justify-end items-center gap-2">
                  <span class="text-xs text-red-600 font-medium">{{ $t('ui.confirmDeletion2') }}</span>
                  <button
                    @click="deletePage(page.id)"
                    :disabled="deletingId === page.id"
                    class="px-3 py-1 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60">
                    {{ deletingId === page.id ? $t('ui.deleting') : $t('ui.yesDelete') }}
                  </button>
                  <button
                    @click="confirmDeleteId = null"
                    class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                    {{ $t('common.cancel') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../services/api'
import { useNavigationStore } from '../../stores/navigation'

const navigationStore = useNavigationStore()

// Halaman default moimstone.test — sesuai home.vue dan routes publik
const defaultPages = [
  {
    name: 'Beranda',
    icon: '🏠',
    description: uiText("Halaman utama situs"),
    path: '/',
    sections: [uiText("Carousel"), uiText("Solusi Produk"), 'Firmware', uiText("Solusi"), uiText("Tentang Kami"), uiText("Proyek")],
    actions: [
      { label: '🖼 Carousel', to: '/dashboard/cms/website-content' },
      { label: '📦 Products', to: '/dashboard/cms/website-content' },
      { label: uiText("Pengaturan"), to: '/dashboard/cms/settings' },
      { label: uiText("Solusi"), to: '/dashboard/cms/solutions' },
      { label: uiText("Proyek"), to: '/dashboard/cms/projects' },
    ]
  },
  {
    name: "Produk",
    icon: '📦',
    description: uiText("Katalog produk publik"),
    path: '/product',
    sections: [uiText("Tab Kategori"), uiText("Subkategori"), uiText("Kartu Produk")],
    actions: [{ label: uiText("📦 Kelola Produk"), to: '/dashboard/cms/website-content' }]
  },
  {
    name: "Layanan dan Solusi",
    icon: '🛠',
    description: uiText("Halaman layanan dan solusi"),
    path: '/solutions',
    sections: [uiText("Kartu Solusi"), uiText("Fitur")],
    actions: [{ label: uiText("Kelola"), to: '/dashboard/cms/solutions' }]
  },
  {
    name: "Proyek",
    icon: '📂',
    description: uiText("Portofolio proyek"),
    path: '/project',
    sections: [uiText("Kartu Proyek"), uiText("Statistik")],
    actions: [{ label: uiText("Kelola"), to: '/dashboard/cms/projects' }]
  },
  {
    name: "Kontak",
    icon: '📞',
    description: uiText("Informasi kontak dan penjualan"),
    path: '/contact',
    sections: [uiText("Informasi Kontak"), uiText("Penjualan"), uiText("Media Sosial")],
    actions: [{ label: uiText("📞 Kelola"), to: '/dashboard/cms/website-content' }]
  },
]

const pages = ref([])
const loading = ref(true)
const error = ref('')
const confirmDeleteId = ref(null)  // id page yang sedang dikonfirmasi hapus
const deletingId = ref(null)       // id page yang sedang proses dihapus
const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const loadPages = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/pages')
    pages.value = response.data.data || response.data
  } catch (err) {
    error.value = uiText("Gagal memuat halaman.")
  } finally {
    loading.value = false
  }
}

const togglePublish = async (page) => {
  try {
    await api.post(`/pages/${page.id}/${page.is_published ? 'unpublish' : 'publish'}`)
    await loadPages()
    await navigationStore.refreshNavigation()
    showToast(page.is_published ? uiText("Penerbitan halaman dibatalkan") : uiText("Halaman diterbitkan"))
  } catch {
    showToast(uiText("Gagal mengubah status halaman"), 'error')
  }
}

const toggleNav = async (page) => {
  if (!page.is_published) {
    showToast(uiText("Terbitkan halaman terlebih dahulu agar tampil di menu navigasi"), 'error')
    return
  }
  try {
    await api.put(`/pages/${page.id}`, { show_in_nav: !page.show_in_nav })
    await loadPages()
    await navigationStore.refreshNavigation()
    showToast(page.show_in_nav ? uiText("Halaman disembunyikan dari menu navigasi") : uiText("Halaman ditampilkan di menu navigasi"))
  } catch {
    showToast(uiText("Gagal mengubah status menu navigasi"), 'error')
  }
}

const duplicatePage = async (id) => {
  try {
    await api.post(`/pages/${id}/duplicate`)
    await loadPages()
    showToast(uiText("Halaman berhasil diduplikat"))
  } catch {
    showToast(uiText("Gagal menduplikat halaman"), 'error')
  }
}

const deletePage = async (id) => {
  deletingId.value = id
  try {
    await api.delete(`/pages/${id}`)
    confirmDeleteId.value = null
    await loadPages()
    await navigationStore.refreshNavigation()
    showToast(uiText("Halaman berhasil dihapus"))
  } catch (err) {
    console.error('Delete error:', err)
    showToast(uiText("Gagal menghapus halaman"), 'error')
  } finally {
    deletingId.value = null
  }
}

onMounted(() => loadPages())
</script>
