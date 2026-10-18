<template>
  <div class="space-y-0">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('nav.websiteContent') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('ui.manageAllPublicPageContentInOnePlace') }}</p>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-0" :aria-label="$t('ui.tabs')">
        <button
          v-for="tab in tabList"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
            activeTab === tab.key
              ? 'border-indigo-600 text-indigo-600 bg-indigo-50'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <span>{{ tab.icon }}</span>
          {{ $ui(tab.label) }}
        </button>
      </nav>
    </div>

    <!-- Tab: Pages -->
    <div v-show="activeTab === 'pages'">
      <PagesTab />
    </div>

    <!-- Tab: Products -->
    <div v-show="activeTab === 'products'">
      <PublicProducts />
    </div>

    <!-- Tab: Carousel -->
    <div v-show="activeTab === 'carousel'">
      <CmsCarousel />
    </div>

    <!-- Tab: Contact -->
    <div v-show="activeTab === 'contact'">
      <CmsContact />
    </div>
  </div>
</template>

<script setup>
import { uiText } from '../../utils/uiText';

import { ref } from 'vue'
import PublicProducts from './PublicProducts.vue'
import CmsCarousel from './CmsCarousel.vue'
import CmsContact from './CmsContact.vue'
import PagesTab from './PagesTab.vue'

const activeTab = ref('pages')

const tabList = [
  { key: 'pages', label: uiText("Halaman"), icon: '📄' },
  { key: 'products', label: uiText("Produk"), icon: '📦' },
  { key: 'carousel', label: uiText("Carousel"), icon: '🖼️' },
  { key: 'contact', label: uiText("Informasi Kontak"), icon: '📞' },
]
</script>
