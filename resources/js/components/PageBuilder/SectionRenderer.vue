<template>
  <div :class="['section-container', sectionClasses]">
    <!-- Hero Section -->
    <div v-if="section.section_type === 'hero'" :class="['hero-section', `h-${section.settings?.height || 'screen'}`, getAlignmentClass(section.settings?.alignment)]" :style="getBackgroundStyle()">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center" :class="{ 'text-white': section.content.text_color === 'white' }">
          <h1 class="text-5xl md:text-6xl font-bold mb-6">{{ section.content.heading }}</h1>
          <p class="text-xl md:text-2xl mb-8 opacity-90">{{ section.content.subheading }}</p>
          <a v-if="section.content.button_text" :href="section.content.button_url || '#'" class="inline-block px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
            {{ section.content.button_text }}
          </a>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div v-else-if="section.section_type === 'features'" class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold mb-4">{{ section.content.heading }}</h2>
          <p v-if="section.content.subheading" class="text-xl text-gray-600">{{ section.content.subheading }}</p>
        </div>
        <div :class="`grid grid-cols-1 md:grid-cols-${section.settings?.columns || 3} gap-8`">
          <div v-for="(feature, index) in section.content.features" :key="index" class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
            <div class="text-5xl mb-4">{{ feature.icon }}</div>
            <h3 class="text-xl font-semibold mb-2">{{ feature.title }}</h3>
            <p class="text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div v-else-if="section.section_type === 'content'" class="py-20">
      <div class="max-w-7xl mx-auto px-4">
        <div v-if="section.settings?.layout === 'two-column'" class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div :class="{ 'order-2': section.content.image_position === 'left' }">
            <h2 class="text-4xl font-bold mb-6">{{ section.content.heading }}</h2>
            <p class="text-lg text-gray-600 leading-relaxed">{{ section.content.text }}</p>
          </div>
          <div :class="{ 'order-1': section.content.image_position === 'left' }">
            <img :src="section.content.image_url" :alt="section.content.heading" class="rounded-lg shadow-xl" />
          </div>
        </div>
        <div v-else-if="section.settings?.layout === 'centered'" class="text-center max-w-4xl mx-auto">
          <h2 class="text-4xl font-bold mb-6">{{ section.content.heading }}</h2>
          <p class="text-lg text-gray-600 leading-relaxed">{{ section.content.text }}</p>
        </div>
      </div>
    </div>

    <!-- Gallery Section -->
    <div v-else-if="section.section_type === 'gallery'" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold mb-4">{{ section.content.heading }}</h2>
          <p v-if="section.content.subheading" class="text-xl text-gray-600">{{ section.content.subheading }}</p>
        </div>
        <div :class="`grid grid-cols-1 md:grid-cols-${section.settings?.columns || 3} gap-8`">
          <div v-for="(item, index) in section.content.items" :key="index" class="group relative overflow-hidden rounded-lg shadow-lg hover:shadow-2xl transition-shadow">
            <img :src="item.image_url" :alt="item.title" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300" />
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
              <h3 class="text-xl font-semibold mb-1">{{ item.title }}</h3>
              <p class="text-sm opacity-90">{{ item.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div v-else-if="section.section_type === 'cta'" :class="['py-20', `bg-gradient-to-r ${section.settings?.gradient || 'from-indigo-600 to-purple-600'}`]">
      <div class="max-w-4xl mx-auto px-4 text-center text-white">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">{{ section.content.heading }}</h2>
        <p class="text-xl mb-8 opacity-90">{{ section.content.text }}</p>
        <div class="flex justify-center gap-4">
          <a v-if="section.content.button_text" :href="section.content.button_url || '#'" class="px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
            {{ section.content.button_text }}
          </a>
          <a v-if="section.content.secondary_button_text" :href="section.content.secondary_button_url || '#'" class="px-8 py-4 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-indigo-600 transition-all">
            {{ section.content.secondary_button_text }}
          </a>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div v-else-if="section.section_type === 'stats'" :class="['py-20', section.settings?.background === 'gray' ? 'bg-gray-50' : 'bg-white']">
      <div class="max-w-7xl mx-auto px-4">
        <div :class="`grid grid-cols-2 md:grid-cols-${section.settings?.columns || 4} gap-8`">
          <div v-for="(stat, index) in section.content.stats" :key="index" class="text-center">
            <div class="text-5xl font-bold text-indigo-600 mb-2">{{ stat.number }}</div>
            <div class="text-gray-600">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Section -->
    <div v-else-if="section.section_type === 'contact'" class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold mb-4">{{ section.content.heading }}</h2>
          <p v-if="section.content.subheading" class="text-xl text-gray-600">{{ section.content.subheading }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
          <div v-if="section.content.show_form" class="bg-white p-8 rounded-xl shadow-lg">
            <form class="space-y-4">
              <input type="text" placeholder="Your Name" class="w-full px-4 py-3 border rounded-lg" />
              <input type="email" placeholder="Your Email" class="w-full px-4 py-3 border rounded-lg" />
              <textarea placeholder="Your Message" rows="4" class="w-full px-4 py-3 border rounded-lg"></textarea>
              <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Send Message</button>
            </form>
          </div>
          <div class="space-y-6">
            <div v-for="(info, index) in section.content.contact_info" :key="index" class="flex items-start gap-4">
              <span class="text-3xl">{{ info.icon }}</span>
              <div>
                <div class="font-semibold text-gray-900">{{ info.type }}</div>
                <div class="text-gray-600">{{ info.value }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Default/Custom Section -->
    <div v-else class="py-20">
      <div class="max-w-7xl mx-auto px-4">
        <h2 v-if="section.content.heading" class="text-4xl font-bold mb-6">{{ section.content.heading }}</h2>
        <p v-if="section.content.text" class="text-lg text-gray-600">{{ section.content.text }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
  isPreview: {
    type: Boolean,
    default: false,
  },
});

const sectionClasses = computed(() => {
  return props.isPreview ? 'preview-mode' : '';
});

const getBackgroundStyle = () => {
  const content = props.section.content;
  const settings = props.section.settings;

  if (content.background_type === 'gradient') {
    return {
      background: `linear-gradient(to right, var(--tw-gradient-stops))`,
      '--tw-gradient-from': content.gradient?.split(' ')[0] || '#4F46E5',
      '--tw-gradient-to': content.gradient?.split(' ')[2] || '#7C3AED',
    };
  } else if (content.background_type === 'image' && content.image_url) {
    return {
      backgroundImage: `${content.overlay ? 'linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), ' : ''}url(${content.image_url})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    };
  }

  return {};
};

const getAlignmentClass = (alignment) => {
  const alignments = {
    left: 'text-left',
    center: 'text-center flex items-center justify-center',
    right: 'text-right',
  };
  return alignments[alignment] || alignments.center;
};
</script>

<style scoped>
.section-container {
  position: relative;
}

.preview-mode {
  pointer-events: none;
}

.hero-section {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 500px;
}

.h-screen {
  min-height: 100vh;
}
</style>
