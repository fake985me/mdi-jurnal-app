<template>
  <section class="content-section py-16 px-4 bg-white">
    <div class="container mx-auto max-w-4xl">
      <!-- Title -->
      <h2 v-if="content.title" class="text-3xl md:text-4xl font-bold mb-6">
        {{ content.title }}
      </h2>

      <!-- Image (if layout includes image) -->
      <div v-if="content.image && (settings.layout === 'image-left' || settings.layout === 'image-right')" 
           :class="[
             'grid gap-8 mb-8',
             settings.layout === 'image-left' ? 'md:grid-cols-2' : 'md:grid-cols-2'
           ]"
      >
        <div :class="settings.layout === 'image-right' ? 'md:order-2' : ''">
          <img 
            :src="content.image" 
            :alt="content.title || 'Content image'"
            class="w-full rounded-lg shadow-md"
          />
        </div>
        <div>
          <div v-html="content.body" class="prose prose-lg max-w-none"></div>
        </div>
      </div>

      <!-- Body (text only or with top image) -->
      <div v-else>
        <img 
          v-if="content.image && settings.layout === 'image-top'"
          :src="content.image" 
          :alt="content.title || 'Content image'"
          class="w-full rounded-lg shadow-md mb-8"
        />
        <div v-html="content.body" class="prose prose-lg max-w-none"></div>
      </div>
    </div>
  </section>
</template>

<script setup>
const props = defineProps({
  content: {
    type: Object,
    default: () => ({
      title: '',
      body: '',
      image: '',
    })
  },
  settings: {
    type: Object,
    default: () => ({
      layout: 'text-only', // text-only, image-top, image-left, image-right
    })
  }
})
</script>

<style scoped>
/* Prose styling for rich text content */
.prose {
  color: #374151;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3) {
  font-weight: 700;
  color: #111827;
  margin-bottom: 1rem;
}

.prose :deep(h1) {
  font-size: 2.25rem;
}

.prose :deep(h2) {
  font-size: 1.875rem;
}

.prose :deep(h3) {
  font-size: 1.5rem;
}

.prose :deep(p) {
  margin-bottom: 1rem;
  line-height: 1.75;
}

.prose :deep(a) {
  color: #4f46e5;
  text-decoration: none;
}

.prose :deep(a:hover) {
  text-decoration: underline;
}

.prose :deep(ul) {
  list-style-type: disc;
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.prose :deep(ol) {
  list-style-type: decimal;
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.prose :deep(li) {
  margin-bottom: 0.5rem;
}

.prose :deep(strong) {
  font-weight: 600;
}

.prose :deep(em) {
  font-style: italic;
}
</style>
