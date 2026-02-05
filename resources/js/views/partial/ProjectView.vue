<template>
  <section id="project" class="container relative mx-auto px-5 flex flex-col md:flex-row gap-10 py-10 h-full">
    <!-- Title -->
    <div class="md:w-1/3 text-center md:text-left">
      <h2 class="text-3xl md:text-4xl font-bold font-theme-heading">Our Projects</h2>
      <p class="text-theme-grayish-blue mt-2 font-theme-content text-lg">
        We are not just clients, but we are partners
      </p>
    </div>
    <div class="w-full md:w-2/3">
      <div class="flex flex-col items-center">
        <!-- Card Utama -->
        <div class="card w-full max-w-[850px] bg-white rounded-[25px] shadow-xl overflow-hidden relative">
          <!-- Input radio: pakai v-model untuk selected index -->
          <div v-for="(city, index) in cities" :key="'input-' + index">
            <input type="radio" name="select" :id="`slide_${index}`" class="hidden" v-model="selected" :value="index" />
          </div>

          <!-- Swipe Area -->
          <div
            class="inner_part flex flex-col md:flex-row items-center justify-center gap-4 p-4 transition-all duration-300 relative touch-pan-x"
            ref="swipeArea" @touchstart="onTouchStart" @touchend="onTouchEnd">
            <!-- Gambar (bisa fullscreen) -->
            <label for="slideImg"
              class="img-container block rounded-[20px] overflow-hidden shadow-lg w-full md:w-[260px] h-[260px] cursor-pointer relative"
              :class="{
                'fixed inset-0 w-screen h-screen bg-black z-50 flex items-center justify-center rounded-none':
                  isExpanded,
              }">
              <img :src="cities[selected]?.image" :alt="cities[selected]?.title"
                class="w-full h-full object-cover transition-opacity duration-500" :class="{
                  'rounded-none': isExpanded,
                }" />
              <!-- Tombol Close (hanya muncul saat fullscreen) -->
              <button v-if="isExpanded" @click="isExpanded = false"
                class="absolute top-4 right-4 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75">
                ✕
              </button>
            </label>

            <!-- Konten: judul, deskripsi, tombol (non-fullscreen saja) -->
            <div v-if="!isExpanded" class="content w-full md:w-[530px] transition-opacity duration-500">
              <h2 class="text-[30px] font-bold text-[#0d0925] mb-4">
                {{ cities[selected]?.title }}
              </h2>
              <p class="text-[19px] text-[#4e4a67] mb-6 leading-[1.5em] text-justify">
                {{ cities[selected]?.description }}
              </p>
            </div>
          </div>
        </div>

        <!-- Navigasi Slider (dots) Bawah card (non-fullscreen) -->
        <div v-if="!isExpanded && cities.length > 0" class="slider mt-4 flex space-x-2 justify-center">
          <label v-for="(city, index) in cities" :key="'nav-' + index" :for="`slide_${index}`"
            class="slide relative h-[10px] w-[50px] bg-gray-300 rounded cursor-pointer">
            <span class="absolute top-0 left-0 h-full w-full bg-black rounded transition-transform origin-left"
              :class="{ 'scale-x-100': selected === index, 'scale-x-0': selected !== index }"></span>
          </label>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

const a = '/assets/static/project/menaradanareksa.jpg'
const b = '/assets/static/project/tamanmelatidepok.jpg'
const c = '/assets/static/project/pelindo.jpg'
const d = '/assets/static/project/tamanmelatisurabaya.jpg'
const e = '/assets/static/project/axia.jpg'
const f = '/assets/static/project/cartenz.png'
const g = '/assets/static/project/chadstone-building-.jpg'

const cities = ref([])
const selected = ref(0)
const isExpanded = ref(false)

const fetchCities = async () => {
  // Using static project data
  cities.value = [
    {
      title: 'Menara Danareksa',
      image: a,
      description:
        'Jl. Medan Merdeka Sel. No.14, Gambir, Kecamatan Gambir, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10110',
    },
    {
      title: 'Apartement Merr Taman Melati Margonda',
      image: b,
      description:
        'Jl. Margonda Raya No.525A, Pondok Cina, Kecamatan Beji, Kota Depok, Jawa Barat 16424',
    },
    {
      title: 'Pelindo Place Office Tower',
      image: c,
      description:
        'Jl. Perak Timur No.478, Perak Utara, Kec. Pabean Cantikan, Surabaya, Jawa Timur 60165',
    },
    {
      title: 'Apartement Taman Melati Merr Surabaya',
      image: d,
      description:
        'Jl. Mulyorejo Utara No.201, RT.006/RW.001, Mulyorejo, Kec. Mulyorejo, Kota SBY,Jawa Timur 60115',
    },
    {
      title: 'Axia South Cikarang Tower 3',
      image: e,
      description:
        'Jl. M.H. Thamrin No.101, Cibatu, Cikarang Sel., Kabupaten Bekasi, Jawa Barat 17550',
    },
    {
      title: 'Pollux Chadstone Cikarang',
      image: g,
      description:
        'Jalan Raya Cikarang - Cibarusah, Exit Toll KM31 Cikarang Barat, Pasirsari, Cikarang Sel, Kabupaten Bekasi, Jawa Barat 17530',
    },
    {
      title: 'Carstensz Residence and Mall',
      image: f,
      description:
        'Jl. Jenderal Sudirman No.1, Cihuni, Kec. Pagedangan, Kabupaten Tangerang, Banten 15332',
    },
  ]
}

let interval = null
onMounted(() => {
  fetchCities()

  // Autoplay hanya jika tidak dalam mode fullscreen
  interval = setInterval(() => {
    if (cities.value.length > 0 && !isExpanded.value) {
      selected.value = (selected.value + 1) % cities.value.length
    }
  }, 5000)
})

onBeforeUnmount(() => {
  clearInterval(interval)
})

// Swipe support
const touchStartX = ref(0)
const touchEndX = ref(0)
const swipeArea = ref(null)

const onTouchStart = (e) => {
  touchStartX.value = e.changedTouches[0].screenX
}

const onTouchEnd = (e) => {
  touchEndX.value = e.changedTouches[0].screenX
  const diff = touchStartX.value - touchEndX.value

  if (Math.abs(diff) > 50 && cities.value.length > 0 && !isExpanded.value) {
    if (diff > 0) {
      // Next
      selected.value = (selected.value + 1) % cities.value.length
    } else {
      // Prev
      selected.value = (selected.value - 1 + cities.value.length) % cities.value.length
    }
  }
}
</script>

<style scoped>
/* Transisi untuk navigation dots */
.slide span {
  transform: scaleX(0);
  transition: transform 0.4s ease;
}

.slide span.scale-x-100 {
  transform: scaleX(1);
}

/* Saat fullscreen (isExpanded), hilangkan border-radius */
.img-container img.rounded-none {
  border-radius: 0 !important;
}
</style>
