<template>
  <div class="relative group w-full bg-gray-50 rounded-lg shadow-md p-4 border border-gray-200" @click="restartAnimation">
    <div class="relative w-full" style="aspect-ratio: 815.1 / 538.2;">
      <!-- Background Diagram -->
      <img v-if="imageDiagramSrc" :src="imageDiagramSrc"
        class="absolute top-0 left-0 w-full h-auto object-contain pointer-events-none" alt="Network Diagram" />

      <!-- Marker + Text -->
      <div v-for="(m, i) in resolvedMarkers" :key="`marker-${i}-${svgKey}`" class="absolute" :style="m.style">
        <!-- Layer posisi (translate) -->
        <div class="marker-pos">
          <!-- Layer scale -->
          <div class="marker-scale" :class="{ 'mirror-x': m.mirror }">
            <img :src="m.image" :alt="m.title || 'Marker'" class="marker-img cursor-pointer" />
          </div>

          <!-- Text (muncul saat hover marker) -->
          <span
            class="marker-text absolute w-max left-1/2 top-full mt-1 px-2 py-1 text-xs font-semibold bg-black/70 text-white rounded opacity-100 scale-100 transition-all duration-300">
            {{ m.title }}
          </span>
        </div>
      </div>

      <!-- SVG Overlay -->
      <div class="svg">
        <svg :key="svgKey" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 815.1 538.2">
          <!-- Light Green omni-->
          <line x1="53.3" y1="335.3" x2="90" y2="391.7" fill="none" class="line green" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="3" />
          <line x1="110.2" y1="417.9" x2="164.7" y2="456.1" fill="none" class="line green" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="3" />
          <!-- Biru (OLT) -->
          <line x1="172.1" y1="453.7" x2="226.8" y2="427.8" fill="none" class="line blues" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="3" />

          <!-- Biru (distribusi) -->
          <polyline points="248.1 421.1 453.1 324.2 486 341" fill="none" class="line blues" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2" />
          <polyline points="250.5 428.4 366.8 372.1 393.5 391.3" fill="none" class="line blues" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2" />
          <polyline points="241.7 416.2 478.3 304.8 430.7 280.4" fill="none" class="line blues" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2" />
          <polyline points="235.2 411.7 376.1 346.2 332.4 319" fill="none" class="line blues" stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2" />


          <!-- Hijau (ONU/ONT branches) -->
          <polyline points="495.2 460.2 560.2 421.4 590.5 421.4" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="495.2 460.2 563.8 438.6 586.4 438.6" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <line x1="495.2" y1="460.2" x2="585.7" y2="459.5" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="495.2 460.2 558.8 477.2 584 477.2" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="495.2 460.2 560.3 500.5 585.1 500.5" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />

          <polyline points="611.8 252.7 651.6 158.6 681.4 158.6" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="611.8 252.7 655.1 175.8 677.9 175.8" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="611.8 252.7 653.2 197 677.5 197" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="611.8 252.7 650.1 214.4 675.3 214.4" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="611.8 252.7 651.8 247.9 676.6 247.9" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />

          <polyline points="590.8 317.6 612.9 301 628 300.9" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <line x1="590.8" y1="317.6" x2="632.4" y2="317.6" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="590.8 317.7 614.3 330.8 636 330.9" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />

          <polyline points="323.4 146.4 283.7 52.3 253.8 52.3" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="323.4 146.4 280.1 69.4 257.3 69.5" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="323.4 146.4 282.1 90.6 257.7 90.6" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="323.4 146.4 285.1 108.1 259.9 108.1" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="323.4 146.4 283.4 141.6 258.6 141.6" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />

          <polyline points="326.5 205.7 304.4 189.1 289.3 189" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <line x1="326.5" y1="205.7" x2="284.9" y2="205.7" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="326.5 205.8 303 218.9 281.3 219" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />

          <polyline points="252.8 331.7 230.6 315.1 215.6 314.9" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <line x1="252.8" y1="331.7" x2="211.1" y2="331.7" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />
          <polyline points="252.8 331.7 229.3 344.9 207.5 344.9" fill="none" class="line black" stroke-linecap="round"
            stroke-linejoin="round" />


          <polygon points="261.8 428.2 261.8 439.8 213.4 439.8 213.4 428.2 227.7 404.9 261.8 428.2" fill="#6b6c6d" />
          <rect x="213.4" y="428.2" width="48.4" height="11.6" />

          <rect x=".5" y=".5" width="814.1" height="577.2" fill="none" opacity="0" stroke="#f5f5f5"
            stroke-miterlimit="10" />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue'
import { useRoute } from 'vue-router'

// ======= Props =======
const props = defineProps({
  product: { type: Object, required: true },           // { title, image, subCategory, diagram? }
  diagram: { type: String, required: false },          // nama file png tanpa ekstensi (mis. 'optic_olt')
})

// ======= Route (opsional jika perlu slug) =======
useRoute()

// ======= Load background diagram dari public folder =======
const imageDiagramSrc = computed(() => {
  const p = props.product
  if (!p) return ''

  // nama file dari field diagram
  const name = props.diagram || props.product?.diagram
  if (!name) return ''

  // Gunakan path public yang bisa diakses langsung
  return `/assets/static/network_diagram/xgspon/${name}.png`
})

// ======= Konstanta ukuran viewBox SVG =======
const VIEW_W = 815.1
const VIEW_H = 538.2

// ======= Titik anchor per subCategory =======
// Setiap item: { x, y, sizePct } — sizePct dipakai untuk penyesuaian ukuran per titik
// KEY HARUS SESUAI DENGAN subCategory dari database (termasuk prefix "XGSPON ")
const ANCHORS = {
  // XGSPON OLT: 1 titik di ujung polyline biru
  'XGSPON OLT': [
    { x: 170.7, y: 445.8, sizePct: 8 },
  ],

  // XGSPON ONU & ONU PoE:
  // - Ujung polyline kuning (334.1, 301.6)
  // - Ujung 3 cabang hijau (710.2,349.3), (702.1,322.2), (702.1,288.1)
  // - Ujung line kuning lurus ke kiri (334.1,309.7)
  'XGSPON ONU': [
    { x: 370, y: 160, sizePct: 8 },
    { x: 600, y: 265, sizePct: 8 },
    { x: 485, y: 475, sizePct: 8 },
  ],
  'XGSPON ONU PoE': [
    { x: 365, y: 155, sizePct: 8 },
    { x: 600, y: 260, sizePct: 8, mirror: true },
    { x: 485, y: 470, sizePct: 8, mirror: true },
  ],

  // XGSPON ONT:
  // - Ujung polyline kuning atas (334.1, 294.1)
  // - Ujung polyline kuning bawah (334.1, 317.3)
  // - Ujung 3 cabang hijau bawah (532.9,491.2), (521.9,457.5), (528.6,423.9)
  'XGSPON ONT': [
    { x: 350, y: 212.1, sizePct: 6 },
    { x: 280, y: 340, sizePct: 6 },
    { x: 590, y: 325, sizePct: 6 },
  ],
}

// fallback jika subCategory tidak dikenali
const FALLBACK = [{ x: 400, y: 300, sizePct: 7 }]

// ======= Util: konversi titik -> style absolut =======
function toStyle(pt, extra = {}) {
  return {
    position: 'absolute',
    top: `${(pt.y / VIEW_H) * 100}%`,
    left: `${(pt.x / VIEW_W) * 100}%`,
    width: `${pt.sizePct ?? 6}%`,
    // Transform dihandle oleh .marker-pos, tidak perlu di sini
    ...extra,
  }
}


// ======= Markers otomatis dari subCategory =======
const resolvedMarkers = computed(() => {
  const p = props.product
  if (!p) return []

  const sub = p.subCategory || ''
  const points = ANCHORS[sub] || FALLBACK

  const markers = points.map((pt) => {
    const style = toStyle(pt)
    return {
      image: p.image,
      title: p.title,
      mirror: !!pt.mirror,
      style: style,
    }
  })

  return markers
})

// ======= Re-trigger animasi garis =======
const svgKey = ref(0)
function restartAnimation() {
  svgKey.value += 1
}

</script>

<style scoped>
/* ======= POSITIONING MARKER ======= */
.marker-pos {
  position: relative;
  transform: translate(-50%, -50%);
  transform-origin: center center;
}

.marker-scale {
  display: inline-block;
  transform-origin: center center;
  transition: transform 0.3s ease;
}

.marker-img {
  display: block;
  width: auto;
  height: auto;
}

/* Marker Zoom saat hover */
.marker-pos:hover .marker-scale {
  opacity: 1;
  transform: scale(1.4);
}

/* Teks marker muncul saat hover */
.marker-pos:hover .marker-text {
  opacity: 1;
  transform: translateX(-50%) scale(1);
}

/* Default teks marker */
.marker-text {
  transform-origin: top center;
  transform: translateX(-50%) scale(1);
}

.mirror-x {
  transform: scaleX(-1);
}

/* ======= ANIMASI GARIS BERJALAN TERUS ======= */

/* Keyframe animasi garis dash */
@keyframes dashLoop {
  0% {
    stroke-dashoffset: 1000;
  }

  100% {
    stroke-dashoffset: 0;
  }
}

/* SVG full width */
svg {
  width: 100%;
  height: auto;
}

/* Semua garis */
.line {
  fill: none;
  stroke-dasharray: 20, 5;
  stroke-dashoffset: 1000;
  animation: dashLoop 4s linear infinite;
}

/* Warna dan ketebalan garis */
.black {
  stroke: #020202;
  stroke-width: 1.5px;
}

.blues {
  stroke: #64abf9;
  stroke-width: 2px;
}

.green {
  stroke: #32f93c;
  stroke-width: 3px;
}

/* (Opsional) Variasi kecepatan animasi per warna */
.line.blues {
  animation-duration: 14s;
}

.line.green {
  animation-duration: 10s;
}

.line.black {
  animation-duration: 14s;
}

/* (Opsional) Delay berurutan jika pakai class step-X */
.line.step-1 {
  animation-delay: 0s;
}

.line.step-2 {
  animation-delay: 0.2s;
}

.line.step-3 {
  animation-delay: 0.5s;
}

.line.step-4 {
  animation-delay: 0.7s;
}

.line.step-5 {
  animation-delay: 1s;
}

.line.step-6 {
  animation-delay: 1.3s;
}

.line.step-7 {
  animation-delay: 1.6s;
}

.line.step-8 {
  animation-delay: 1.9s;
}

.line.step-9 {
  animation-delay: 2.2s;
}

.line.step-10 {
  animation-delay: 2.5s;
}

.line.step-11 {
  animation-delay: 2.8s;
}

.line.step-12 {
  animation-delay: 3.1s;
}

.line.step-13 {
  animation-delay: 3.4s;
}

.line.step-14 {
  animation-delay: 3.7s;
}

.line.step-15 {
  animation-delay: 4s;
}
</style>
