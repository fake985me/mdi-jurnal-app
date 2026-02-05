<template>
  <section class="testimonials-section py-16 px-4 bg-gray-50">
    <div class="container mx-auto max-w-7xl">
      <!-- Section Header -->
      <div v-if="content.title || content.subtitle" class="text-center mb-12">
        <h2 v-if="content.title" class="text-3xl md:text-4xl font-bold mb-4">
          {{ content.title }}
        </h2>
        <p v-if="content.subtitle" class="text-xl text-gray-600">
          {{ content.subtitle }}
        </p>
      </div>

      <!-- Testimonials Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="(testimonial, index) in content.testimonials" 
          :key="index"
          class="testimonial-card bg-white p-6 rounded-lg shadow-md"
        >
          <!-- Quote -->
          <div class="mb-4">
            <span class="text-4xl text-indigo-600">"</span>
            <p class="text-gray-700 italic mt-2">{{ testimonial.quote }}</p>
          </div>

          <!-- Author -->
          <div class="flex items-center">
            <img 
              v-if="testimonial.avatar" 
              :src="testimonial.avatar" 
              :alt="testimonial.name"
              class="w-12 h-12 rounded-full mr-4"
            />
            <div v-else class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
              <span class="text-indigo-600 font-bold">{{ getInitials(testimonial.name) }}</span>
            </div>
            <div>
              <p class="font-semibold">{{ testimonial.name }}</p>
              <p class="text-sm text-gray-500">{{ testimonial.company }}</p>
            </div>
          </div>
        </div>
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
      subtitle: '',
      testimonials: []
    })
  },
  settings: {
    type: Object,
    default: () => ({})
  }
})

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>
