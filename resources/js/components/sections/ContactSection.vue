<template>
  <section class="contact-section py-16 px-4 bg-white">
    <div class="container mx-auto max-w-5xl">
      <!-- Section Header -->
      <div v-if="content.title" class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
          {{ content.title }}
        </h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Contact Info -->
        <div class="space-y-6">
          <div v-if="content.email" class="flex items-start">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
              <span class="text-2xl">📧</span>
            </div>
            <div>
              <h3 class="font-semibold mb-1">Email</h3>
              <a :href="`mailto:${content.email}`" class="text-indigo-600 hover:underline">
                {{ content.email }}
              </a>
            </div>
          </div>

          <div v-if="content.phone" class="flex items-start">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
              <span class="text-2xl">📞</span>
            </div>
            <div>
              <h3 class="font-semibold mb-1">Phone</h3>
              <a :href="`tel:${content.phone}`" class="text-indigo-600 hover:underline">
                {{ content.phone }}
              </a>
            </div>
          </div>

          <div v-if="content.address" class="flex items-start">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
              <span class="text-2xl">📍</span>
            </div>
            <div>
              <h3 class="font-semibold mb-1">Address</h3>
              <p class="text-gray-600">{{ content.address }}</p>
            </div>
          </div>
        </div>

        <!-- Contact Form (placeholder) -->
        <div class="bg-gray-50 p-6 rounded-lg">
          <h3 class="text-xl font-semibold mb-4">Send us a message</h3>
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Name</label>
              <input 
                v-model="form.name"
                type="text" 
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Email</label>
              <input 
                v-model="form.email"
                type="email" 
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Message</label>
              <textarea 
                v-model="form.message"
                rows="4" 
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              ></textarea>
            </div>
            <button 
              type="submit"
              class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition-colors"
            >
              Send Message
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  content: {
    type: Object,
    default: () => ({
      title: '',
      email: '',
      phone: '',
      address: '',
    })
  },
  settings: {
    type: Object,
    default: () => ({})
  }
})

const form = ref({
  name: '',
  email: '',
  message: '',
})

const handleSubmit = () => {
  // Future: implement form submission
  console.log('Form submitted:', form.value)
  alert('Thank you for your message! (This is a demo)')
  form.value = { name: '', email: '', message: '' }
}
</script>
