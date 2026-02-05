<template>
  <footer class="bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 py-16 px-2">
    <div class="mx-auto px-6 flex flex-col md:flex-row md:justify-between gap-4">
      <!-- Logo & Description -->
      <div class="w-full md:w-1/3">
        <a href="/" class="flex items-center space-x-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true" class="mb-3 w-16 h-16"
            viewBox="0 0 2 2">
            <path fill="#3636e0"
              d="M.892 1.548H.678v-.296q0-.034-.008-.047t-.027-.013q-.036 0-.036.061v.296H.394v-.296q0-.034-.008-.047t-.027-.014q-.036 0-.036.061v.296H.11V1.21q0-.092.064-.157T.331.988q.095 0 .17.077Q.585.988.669.988q.107 0 .172.075.051.058.051.17zM1.374.8h.214v.458q0 .126-.069.205-.04.046-.1.073t-.125.027q-.128 0-.215-.083t-.088-.202q0-.116.087-.201t.206-.085l.057.003v.227q-.026-.02-.053-.02-.033 0-.057.023t-.024.056q0 .032.024.055t.059.023q.082 0 .082-.11zm.578.199v.549h-.214V.999zM1.926.786q-.032-.03-.075-.03t-.075.03-.032.07q0 .014.003.026l.006.016q.007.016.021.028.03.028.077.028c.047 0 .057-.009.077-.028q.014-.013.021-.029l.002-.005q.006-.017.006-.037 0-.04-.032-.07m.065.016Q1.973.754 1.928.729T1.834.717q-.046.011-.068.048L1.763.77l-.001.002q.02-.036.066-.045.048-.01.092.014t.064.069q.017.042.001.077.021-.04.004-.087m.043-.02Q2.011.722 1.955.692c-.056-.03-.076-.025-.116-.015q-.056.014-.083.058l-.004.006-.001.002Q1.775.7 1.832.689q.058-.012.114.018t.078.085q.021.051.002.094.025-.049.004-.106" />
          </svg>
          <span class="text-[24px] font-semibold text-nowrap text-pretty-blue">Moimstone Dasan Indonesia</span>
        </a>
        <a class="flex items-center space-x-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="#3636e0" class="w-24 h-16" viewBox="0 0 172.85 26.63">
            <g>
              <path class="cls-1"
                d="M102.02 17.63v2.15c0 3.64-2.95 6.58-6.58 6.58H74.97c-1.5 0-2.71-1.22-2.71-2.71 0-1.5 1.22-2.71 2.71-2.71h19.22a2.4 2.4 0 0 0 2.4-2.4v-.23a2.4 2.4 0 0 0-2.4-2.4H77.8c-3.51 0-6.35-2.84-6.35-6.35V7.59c0-4.19 3.39-7.58 7.58-7.58h19.22c1.5 0 2.71 1.22 2.71 2.71 0 1.5-1.22 2.71-2.71 2.71H79.21c-1.28 0-2.32 1.04-2.32 2.32v.4c0 1.28 1.04 2.32 2.32 2.32h15.66a7.16 7.16 0 0 1 7.16 7.16Zm70.83-10.67v17.09c0 1.28-1.04 2.32-2.32 2.32h-.78c-1.29 0-2.33-1.04-2.33-2.32V7.79c0-1.13-.92-2.04-2.04-2.04h-16.13c-1.13 0-2.04.92-2.04 2.04v16.25c0 1.28-1.05 2.33-2.33 2.33h-.78c-1.29 0-2.33-1.04-2.33-2.33V6.96c0-3.67 2.97-6.64 6.64-6.64h17.81c3.67 0 6.64 2.97 6.64 6.64ZM55.37 26.4h-8.9c-.86 0-1.36-.98-.86-1.68l2.54-3.51 1.88-2.59c.42-.57 1.27-.58 1.69-.01l1.86 2.53 2.63 3.58c.51.7.02 1.68-.85 1.68Z" />
              <path class="cls-1"
                d="m67.82 25.67-.63.46c-1.03.76-2.49.54-3.25-.5l-6.48-8.82-3.87-5.26-1.86-2.53c-.42-.57-1.27-.56-1.69.01l-1.88 2.59-3.76 5.19-6.41 8.86a2.333 2.333 0 0 1-3.25.52l-.63-.46a2.333 2.333 0 0 1-.52-3.25l4.13-5.7 7.47-10.32 3.33-4.59h.01c.41-.63 1.13-1.05 1.95-1.05h.79c.83 0 1.56.44 1.97 1.09l3.35 4.55L62 13.82l6.32 8.6c.76 1.03.54 2.49-.5 3.25m58.48.73h-8.9c-.86 0-1.36-.98-.86-1.68l2.54-3.51 1.88-2.59c.42-.57 1.27-.58 1.69-.01l1.86 2.53 2.63 3.58c.51.7.02 1.68-.85 1.68Z" />
              <path class="cls-1"
                d="m138.75 25.67-.63.46c-1.03.76-2.49.54-3.25-.5l-6.48-8.82-3.87-5.26-1.86-2.53c-.42-.57-1.27-.56-1.69.01l-1.88 2.59-3.76 5.19-6.41 8.86a2.333 2.333 0 0 1-3.25.52l-.63-.46a2.333 2.333 0 0 1-.52-3.25l4.13-5.7 7.47-10.32 3.33-4.59h.01c.41-.63 1.13-1.05 1.95-1.05h.79c.83 0 1.56.44 1.97 1.09l3.35 4.55 5.41 7.36 6.32 8.6c.76 1.03.54 2.49-.5 3.25M27.69 4.76a12.64 12.64 0 0 0-8.96-3.71H3.19C1.43 1.05 0 2.48 0 4.24v18.97c0 1.76 1.43 3.19 3.19 3.19h15.54c7 0 12.67-5.67 12.67-12.67 0-3.5-1.42-6.67-3.71-8.97M17.68 22.02H5.42V5.43h12.26c2.3 0 4.37.93 5.87 2.43s2.43 3.58 2.43 5.87c0 4.58-3.71 8.29-8.3 8.29" />
            </g>
          </svg>
          <span class="text-xl font-semibold">Networks</span>
        </a>
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
          Provide your network connectivity solutions. Trusted distributor and experienced technical
          support for DASAN equipment in Indonesia. Offers something that is beyond your reach.
        </p>
      </div>

      <!-- Dynamic 5 Column Section -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 w-full text-sm">
        <div v-for="section in linkSections" :key="section.title">
          <h3
            class="text-gray-900 dark:text-gray-100 font-semibold uppercase tracking-tight mb-2 border-b-2 border-gray-300 dark:border-gray-700 pb-2">
            {{ section.title }}
          </h3>
          <ul v-if="section.links" class="space-y-1">
            <li v-for="link in section.links" :key="link.name">
              <a :href="link.url" class="hover:underline">{{ link.name }}</a>
            </li>
          </ul>
        </div>

        <!-- Contact Us Section (Dynamic) -->
        <div>
          <h3 class="text-gray-900 dark:text-gray-100 font-semibold uppercase tracking-tight mb-2 border-b-2 border-gray-300 dark:border-gray-700 pb-2">
            Contact Us
          </h3>
          <ul class="space-y-2 text-xs text-gray-700 dark:text-gray-400 text-nowrap">
            <!-- Address -->
            <li v-if="contactsByType.address?.length">
              <strong>Address:</strong><br>
              <span v-for="(line, index) in contactsByType.address[0].value.split('\n')" :key="index">
                {{ line }}<br>
              </span>
            </li>

            <!-- Phone -->
            <li v-if="contactsByType.phone?.length">
              <strong>Phone:</strong><br>
              <span v-for="phone in contactsByType.phone" :key="phone.id">
                {{ phone.value }}<br>
              </span>
            </li>

            <!-- Email -->
            <li v-if="contactsByType.email?.length">
              <strong>Email:</strong><br>
              <a v-for="email in contactsByType.email" :key="email.id"
                 :href="`mailto:${email.value}`"
                 class="hover:underline block">
                {{ email.value }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Bottom Footer -->
    <div
      class="max-w-7xl mx-auto mt-10 pt-6 border-t border-gray-300 dark:border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4 text-sm px-4">
      <p class="text-gray-600 dark:text-gray-400 text-center md:text-left">
        &copy; {{ new Date().getFullYear() }} Moimstone Dasan Indonesia. All rights reserved.
      </p>
      <div class="flex space-x-4">
        <a v-for="social in socials" :key="social.name" :href="social.url" :aria-label="social.name"
          class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">
          <component :is="social.icon" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
        </a>
      </div>
    </div>
  </footer>
</template>


<script setup>
import { h } from 'vue'
import useContact from '@/composable/useContact.js'

const { contactsByType } = useContact()

// Social icons
const FacebookIcon = {
  render() {
    return h('svg', {
      fill: 'currentColor',
      viewBox: '0 0 24 24',
      innerHTML:
        '<path d="M22 12A10 10 0 1 0 10 21.9V14.89h-2.5v-3H10V9.75c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.24.19 2.24.19v2.47h-1.26c-1.25 0-1.64.78-1.64 1.57v1.88h2.78l-.44 3H13.1v7.01A10 10 0 0 0 22 12Z"/>',
    })
  },
}

const InstagramIcon = {
  render() {
    return h('svg', {
      fill: 'currentColor',
      viewBox: '0 0 24 24',
      innerHTML:
        '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>',
    })
  },
}

const linkSections = [
  {
    title: 'LINKS',
    links: [
      { name: 'Home', url: '/' },
      { name: 'Products', url: '/product' },
      { name: 'Service & Solutions', url: '/solutions' },
      { name: 'Projects', url: '/project' },
      { name: 'Contact Us', url: '/contact' },
    ],
  },
  {
    title: 'Product',
    links: [
      { name: 'OLT', url: '/product' },
      { name: 'ONU', url: '/product' },
      { name: 'ONU PoE', url: '/product' },
      { name: 'ONT', url: '/product' },
      { name: 'SWITCH', url: '/product' },
      { name: 'PoE Switch', url: '/product' },
      { name: 'Wireless', url: '/product' },
    ],
  },
  {
    title: 'Service & Solutions',
    links: [
      { name: 'Managed Services', url: '/solutions' },
      { name: 'Maintenance and Support', url: '/solutions' },
      { name: 'Design and Build', url: '/solutions' },
      { name: 'Training Dasan Product', url: '/solutions' },
    ],
  },
  {
    title: 'Our Projects',
    links: [
      { name: 'ISP Partner', url: '/project?id=ispcustomer' },
      { name: 'Managed Service', url: '/project?id=managedservices' },
      { name: 'FTTx Project', url: '/project?id=fttxproject' },
    ],
  },
]

const socials = [
  { name: 'Facebook', url: '#', icon: FacebookIcon },
  { name: 'Instagram', url: '#', icon: InstagramIcon },
]
</script>
