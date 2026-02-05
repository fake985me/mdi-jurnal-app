<template>
  <!-- ISP Customer Section -->
  <section id="ispcustomer" class="bg-white">
    <div class="max-w-5xl mx-auto px-2 py-20 space-y-12">
      <h1 class="text-center text-3xl font-bold tracking-tight sm:text-4xl text-gray-900">
        ISP Customer
      </h1>

      <div class="space-y-12">
        <article v-for="(item, index) in articles" :key="index" class="flex flex-col sm:flex-row gap-6 items-start pb-6"
          :class="index < articles.length - 1 ? 'border-b border-gray-200' : ''">
          <!-- Image -->
          <div class="w-full sm:w-48 flex-shrink-0">
            <img :src="item.img" :alt="item.title" class="w-32 sm:w-full h-auto max-h-[180px] p-2 sm:p-6 object-cover rounded-md shadow" />
          </div>

          <!-- Text -->
          <div class="flex-1">
            <a :href="item.link" class="text-blue-600 hover:underline">
              <h3 class="text-xl font-bold uppercase mb-2">{{ item.title }}</h3>
            </a>
            <p class="text-gray-700">
              {{ item.text }}
              <span v-if="item.hoverText" class="text-sky-500 hover:text-sky-700">{{
                item.hoverText
              }}</span>
            </p>
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

      <div v-for="(project, index) in managedProjects" :key="index"
        class="flex flex-col lg:flex-row items-center justify-between gap-10 pb-12" :class="[
          index % 2 !== 0 ? 'lg:flex-row-reverse' : '',
          index < managedProjects.length - 1 ? 'border-b border-gray-300' : '',
        ]">
        <!-- Text -->
        <div class="w-full lg:w-1/2 max-w-xl space-y-4 text-center lg:text-left">
          <p class="text-2xl font-bold tracking-tight text-sky-900 italic">Managed Services Project</p>
          <h2 class="text-2xl font-bold tracking-tight text-gray-800 underline underline-offset-auto">{{ project.title
          }}</h2>
          <p class="text-base text-gray-700">{{ project.address }}</p>
        </div>

        <!-- Image -->
        <div class="w-[full] sm:w-auto max-w-md">
          <img :src="project.image" :alt="project.title"
            class="w-[240px] h-auto max-h-[140px] object-cover rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5" />
        </div>
      </div>
    </div>
  </section>
  <!-- Fttx Project Section -->
  <section id="fttxproject" class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 space-y-16">
      <h1 class="text-center text-3xl font-bold tracking-tight sm:text-4xl text-gray-900">
        FTTx Project
      </h1>

      <div v-for="(fttx, index) in fttxs" :key="index"
        class="flex flex-col lg:flex-row items-center justify-between gap-10 pb-12" :class="[
          index % 2 !== 0 ? 'lg:flex-row-reverse' : '',
          index < fttxs.length - 1 ? 'border-b border-gray-300' : '',
        ]">
        <!-- Text -->
        <div class="w-full lg:w-1/2 max-w-xl space-y-4 text-center lg:text-left">
          <p class="text-2xl font-bold tracking-tight text-sky-900 italic">FTTx Project</p>
          <h2 class="text-2xl font-bold tracking-tight text-gray-800 underline underline-offset-auto">{{ fttx.title }}
          </h2>
          <p class="text-base text-gray-700">{{ fttx.address }}</p>
        </div>

        <!-- Image -->
        <div class="w-[full] sm:w-auto max-w-md">
          <img :src="fttx.image" :alt="fttx.title"
            class="w-[240px] h-auto max-h-[140px] object-cover rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5" />
        </div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'

// Images from public folder
const a = '/assets/static/project/moratel.jpg'
const b = '/assets/static/project/indonet.png'
const menaradanareksa = '/assets/static/project/menaradanareksa.jpg'
const tamanmelatidepok = '/assets/static/project/tamanmelatidepok.jpg'
const tamanmelatisurabaya = '/assets/static/project/tamanmelatisurabaya.jpg'
const pollux = '/assets/static/project/pollux.jpg'
const lampung = '/assets/static/project/lampung.jpg'
const chadstone = '/assets/static/project/chadstone-building-.jpg'
const pelindo = '/assets/static/project/pelindo.jpg'
const sopodel = '/assets/static/project/sopodel.jpg'
const capitol = '/assets/static/project/capitol.jpg'
const padma = '/assets/static/project/padma.png'
const patra = '/assets/static/project/patracirebon.png'
const nihi = '/assets/static/project/nihisumba.jpg'
const kemenko = '/assets/static/project/kemenko.jpg'
const asndua = '/assets/static/project/asn2.jpg'
const asntiga = '/assets/static/project/asn3.jpg'

const route = useRoute()

function scrollToSection(id) {
  nextTick(() => {
    requestAnimationFrame(() => {
      const el = document.getElementById(id)
      if (el) el.scrollIntoView({ behavior: 'smooth' })
    }, 300)
  })
}

onMounted(() => {
  if (route.query.id) scrollToSection(route.query.id)
})

watch(
  () => route.query.id,
  (newId) => {
    if (newId) scrollToSection(newId)
  },
)

const articles = [
  {
    img: a,
    title: 'Moratelindo',
    text: ' Moratelindo is an Indonesian telecommunications company that provides a range of telecommunications and network services. We signed and implemented FTTH Project for Moratelindo at 2015',
    link: '#',
  },
  {
    img: b,
    title: 'Indonet',
    text: 'Founded in 1994, Indonet is a pioneer in the provision of digital infrastructure services. offering various solutions and services designed to meet the evolving needs of modern businesses. We signed and implemented for FTTH Project',
    link: '#',
  },
]

const managedProjects = [
  {
    title: 'Menara Danareksa',
    address: 'Jl. Medan Merdeka Sel. No.14, Gambir, Jakarta Pusat, DKI Jakarta 10110',
    image: menaradanareksa,
  },
  {
    title: 'Taman Melati Margonda',
    address: 'Jl. Margonda Raya No.525A, Pondok Cina, Beji, Kota Depok, Jawa Barat 16424',
    image: tamanmelatidepok,
  },
  {
    title: 'Taman Melati Merr Surabaya',
    address: 'Jl. Mulyorejo Utara No.201, Mulyorejo, Kota SBY, Jawa Timur 60115',
    image: tamanmelatisurabaya,
  },
  {
    title: 'Pollux Meisterstadt Batam',
    address: 'Jl. Ahmad Yani, Simpang Franky, Batam Kota, Kepulauan Riau 29463',
    image: pollux,
  },
  {
    title: 'Lampung City Mall',
    address: 'Jl. Yos Sudarso No.80, Bumi Waras, Kota Bandar Lampung, Lampung 35225',
    image: lampung,
  },
  {
    title: 'Chadstone Cikarang',
    address:
      'Jalan Raya Cikarang - Cibarusah, Exit Toll KM31 Cikarang Barat, Kabupaten Bekasi, Jawa Barat 17530',
    image: chadstone,
  },
]

const fttxs = [
  {
    title: 'Pelindo Place Office Tower',
    address: 'Jl. Perak Timur No.478, Perak Utara, Kec. Pabean Cantikan, Surabaya, Jawa Timur 60165',
    image: pelindo,
  },
  {
    title: 'Sopo Del Office Tower',
    address: 'Jl. Mega Kuningan Barat III, Lot 10. 1-6. Kawasan Mega Kuningan. Jakarta 12950.',
    image: sopodel,
  },
  {
    title: 'Capitol Suites',
    address: 'Jl.kko usman dan harun kavling 22-24, RT.1/RW.5, Kwitang, Kec. Senen, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10410',
    image: capitol,
  },
  {
    title: 'Padma Hotel Semarang',
    address: 'Jl. Sultan Agung No.86, Wonotingal, Kec. Candisari, Kota Semarang, Jawa Tengah 50252',
    image: padma,
  },
  {
    title: 'Patra Cirebon Hotel & Convention',
    address: 'Jl. Tuparev No.11, Kedawung, Kec. Kedawung, Kabupaten Cirebon, Jawa Barat 45153',
    image: patra,
  },
  {
    title: 'Nihi Sumba Island',
    address: 'Nihi Sumba Island, Desa Hobawawi, Kecamatan Wanukaka, Kabupaten Sumba Barat Daya, Nusa Tenggara Timur 87254',
    image: nihi,
  },
  {
    title: 'Kemenko 4 IKN',
    address: 'Jl. Sumbu sisi Barat, Kecamatan Sepaku, Kabupaten Penajam Paser Utara, Kalimantan Timur 76200',
    image: kemenko,
  },
  {
    title: 'Rusun ASN 2 IKN',
    address: 'Pemaluan, Sepaku, Penajam North Paser Regency, East Kalimantan 76147',
    image: asndua,
  },
  {
    title: 'Rusun ASN 3 IKN',
    address: 'Pemaluan, Sepaku, Penajam North Paser Regency, East Kalimantan 76147',
    image: asntiga,
  },
]
</script>
