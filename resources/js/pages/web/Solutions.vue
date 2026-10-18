<template>
  <div class="py-12 px-4 max-w-7xl mx-auto">
    <div class="text-center mb-4">
      <h2 class="text-3xl font-bold text-gray-900">{{ $t('sales.product') }}</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="(solution, index) in productSolutions" :key="index"
        class="bg-gray-300 shadow-black rounded-lg p-6 hover:shadow-md transition cursor-pointer"
        @click="() => $router.push(`/product?category=${solution.title.toUpperCase()}`)">
        <img v-if="solution.image" :src="solution.image" :alt="solution.title"
          class="w-full h-82 object-contain mb-4" />
        <h3 class="text-xl font-semibold mb-2 text-gray-800">{{ solution.title }}</h3>
        <p class="text-gray-600 mb-4">{{ solution.desc || $t('ui.noDescriptionAvailable') }}</p>
        <router-link :to="`/product?category=${solution.title.toUpperCase()}`"
          class="text-blue-600 hover:underline text-sm font-medium">
          {{ $t('ui.viewProduct') }}
        </router-link>
      </div>
    </div>
  </div>
  <!-- ====== -->
    <FirmwareModification />
  <!-- ====== -->

  <div class="py-4 mt-12">
    <div class="text-center mb-14">
      <h2 class="text-3xl font-bold text-gray-900">{{ $t('ui.ourService') }}</h2>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
      <p v-if="loading" class="text-center text-gray-500">{{ $t('ui.loadingServices') }}</p>
      <p v-else-if="error" class="text-center text-red-600">{{ $ui(error) }}</p>
      <p v-else-if="solutions.length === 0" class="text-center text-gray-500">{{ $t('ui.noServicesAreAvailableRightNow') }}</p>
      <template v-else>
        <div
          v-for="(solution, index) in solutions"
          :key="solution.id"
          class="grid grid-cols-1 md:grid-cols-2 items-center gap-8"
        >
          <div v-if="solution.image_url && index % 2 === 0">
            <img :src="solution.image_url" :alt="solution.title"
              class="w-full h-[240px] object-cover rounded-xl shadow-xl ring-1 ring-black ring-opacity-5">
          </div>
          <div
            :class="[
              'text-center md:text-left',
              !solution.image_url ? 'md:col-span-2' : '',
              index % 2 === 1 ? 'md:order-last' : '',
            ]"
          >
            <h2 class="text-2xl font-bold text-gray-700 italic text-shadow-2xl text-shadow-blue-500">
              {{ solution.title }}
            </h2>
            <p class="mt-2 text-gray-600 text-base">{{ solution.description }}</p>
          </div>
          <div v-if="solution.image_url && index % 2 === 1">
            <img :src="solution.image_url" :alt="solution.title"
              class="w-full h-[240px] object-cover rounded-xl shadow-xl ring-1 ring-black ring-opacity-5">
          </div>
        </div>
      </template>
    </div>
  </div>



</template>

<script setup>
import FirmwareModification from '../../views/partial/FirmwareModification.vue'
import useSolutions from '../../composable/useSolutions.js'

const xgspon = '/assets/static/solutions/xgspon.png'
const gpon = '/assets/static/solutions/gpon.png'
const lswitch = '/assets/static/solutions/switch.png'
const wireless = '/assets/static/solutions/wireless.png'
const { solutions, loading, error } = useSolutions()

const productSolutions = [
  {
    title: 'XGSPON',
    image: xgspon,
    desc: 'XGSPON is a next-generation fiber access technology that provides high-speed broadband services.',
    features: ['EPON / 10GE-PON', 'GPON / XGS-PON', 'L2, L3 Access Switch', 'Copper Access'],
  },
  {
    title: 'GPON',
    image: gpon,
    desc: 'GPON is a widely deployed fiber access technology that delivers high-speed internet and voice services.',
    features: [
      'O-RAN Compliant Fronthaul Switch',
      'M-series Any-haul Switch',
      'PON based Mobile Transport',
    ],
  },
  {
    title: 'SWITCH',
    image: lswitch,
    desc: 'Switches for various applications including residential, enterprise, and mobile networks.',
    features: [
      'Fiber Termination Points – ONT (xPON)',
      'Wi-Fi Residential Gateways',
      'Enterprise / Mobile ONTs',
      'Fixed Wireless Access',
    ],
  },
  {
    title: 'WIRELESS',
    image: wireless,
    desc: 'Wireless solutions for broadband access and mobile transport.',
    features: ['NMS/EMS', 'SDN Controller & Orchestrator', 'CPE Management System'],
  },
]
</script>
