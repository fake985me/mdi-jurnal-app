<template>
  <!-- ISP Customer Section -->
  <section id="ispcustomer" class="bg-white">
    <div class="max-w-5xl mx-auto px-2 py-20 space-y-12">
      <h1 class="text-center text-3xl font-bold tracking-tight sm:text-4xl text-gray-900">
        ISP Customer
      </h1>

      <p v-if="projectsLoading" class="text-center text-gray-500">Loading projects...</p>
      <p v-else-if="projectsError" class="text-center text-red-600">{{ projectsError }}</p>
      <p v-else-if="ispProjects.length === 0" class="text-center text-gray-500">No ISP customer projects are available.</p>
      <div v-else class="space-y-12">
        <article v-for="(item, index) in ispProjects" :key="item.id" class="flex flex-col sm:flex-row gap-6 items-start pb-6"
          :class="index < ispProjects.length - 1 ? 'border-b border-gray-200' : ''">
          <!-- Image -->
          <div class="w-full sm:w-48 flex-shrink-0">
            <img v-if="item.image_url" :src="item.image_url" :alt="item.title" class="w-32 sm:w-full h-auto max-h-[180px] p-2 sm:p-6 object-cover rounded-md shadow" />
          </div>

          <!-- Text -->
          <div class="flex-1">
            <a :href="item.url || '#'" class="text-blue-600 hover:underline">
              <h3 class="text-xl font-bold uppercase mb-2">{{ item.title }}</h3>
            </a>
            <p class="text-gray-700">{{ item.description }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
  <!-- Managed Services Section -->
  <section id="managedservices" class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 space-y-16">
      <h1 class="text-center text-3xl font-bold tracking-tight sm:text-4xl text-gray-900">
        Managed Services Project
      </h1>

      <p v-if="projectsLoading" class="text-center text-gray-500">Loading projects...</p>
      <p v-else-if="projectsError" class="text-center text-red-600">{{ projectsError }}</p>
      <p v-else-if="managedProjects.length === 0" class="text-center text-gray-500">No managed services projects are available.</p>
      <template v-else>
        <div v-for="(project, index) in managedProjects" :key="project.id"
          class="flex flex-col lg:flex-row items-center justify-between gap-10 pb-12" :class="[
            index % 2 !== 0 ? 'lg:flex-row-reverse' : '',
            index < managedProjects.length - 1 ? 'border-b border-gray-300' : '',
          ]">
          <div class="w-full lg:w-1/2 max-w-xl space-y-4 text-center lg:text-left">
            <p class="text-2xl font-bold tracking-tight text-sky-900 italic">Managed Services Project</p>
            <h2 class="text-2xl font-bold tracking-tight text-gray-800 underline underline-offset-auto">{{ project.title }}</h2>
            <p class="text-base text-gray-700">{{ project.description }}</p>
          </div>
          <div class="w-[full] sm:w-auto max-w-md">
            <img v-if="project.image_url" :src="project.image_url" :alt="project.title"
              class="w-[240px] h-auto max-h-[140px] object-cover rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5" />
          </div>
        </div>
      </template>
    </div>
  </section>
  <!-- Fttx Project Section -->
  <section id="fttxproject" class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 space-y-16">
      <h1 class="text-center text-3xl font-bold tracking-tight sm:text-4xl text-gray-900">
        FTTx Project
      </h1>

      <p v-if="projectsLoading" class="text-center text-gray-500">Loading projects...</p>
      <p v-else-if="projectsError" class="text-center text-red-600">{{ projectsError }}</p>
      <p v-else-if="fttxProjects.length === 0" class="text-center text-gray-500">No FTTx projects are available.</p>
      <template v-else>
        <div v-for="(fttx, index) in fttxProjects" :key="fttx.id"
          class="flex flex-col lg:flex-row items-center justify-between gap-10 pb-12" :class="[
            index % 2 !== 0 ? 'lg:flex-row-reverse' : '',
            index < fttxProjects.length - 1 ? 'border-b border-gray-300' : '',
          ]">
          <div class="w-full lg:w-1/2 max-w-xl space-y-4 text-center lg:text-left">
            <p class="text-2xl font-bold tracking-tight text-sky-900 italic">FTTx Project</p>
            <h2 class="text-2xl font-bold tracking-tight text-gray-800 underline underline-offset-auto">{{ fttx.title }}</h2>
            <p class="text-base text-gray-700">{{ fttx.description }}</p>
          </div>
          <div class="w-[full] sm:w-auto max-w-md">
            <img v-if="fttx.image_url" :src="fttx.image_url" :alt="fttx.title"
              class="w-[240px] h-auto max-h-[140px] object-cover rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5" />
          </div>
        </div>
      </template>
    </div>
  </section>
</template>
<script setup>
import { onMounted, watch, nextTick, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const projects = ref([])
const projectsLoading = ref(true)
const projectsError = ref('')
const ispProjects = computed(() => projects.value.filter((project) => project.category === 'ISP Customer'))
const managedProjects = computed(() => projects.value.filter((project) => project.category === 'Managed Services'))
const fttxProjects = computed(() => projects.value.filter(
  (project) => ['FTTx Project', 'FTTH'].includes(project.category),
))

const loadProjects = async () => {
  projectsLoading.value = true
  projectsError.value = ''

  try {
    const response = await api.get('/guest/projects')
    projects.value = response.data
  } catch (error) {
    console.error('Failed to load projects:', error)
    projectsError.value = error.response?.data?.message || 'Failed to load projects'
  } finally {
    projectsLoading.value = false
  }
}

function scrollToSection(id) {
  nextTick(() => {
    requestAnimationFrame(() => {
      const el = document.getElementById(id)
      if (el) el.scrollIntoView({ behavior: 'smooth' })
    }, 300)
  })
}

onMounted(() => {
  loadProjects()
  if (route.query.id) scrollToSection(route.query.id)
})

watch(
  () => route.query.id,
  (newId) => {
    if (newId) scrollToSection(newId)
  },
)

</script>
