<template>
  <div class="relative group w-full bg-gray-50 rounded-lg shadow-md p-4 border border-gray-200" @click="restartAnimation">
    <div class="relative w-full" style="aspect-ratio: 815.1 / 538.2;">
      <!-- Background Diagram -->
      <img v-if="imageDiagramSrc" :src="imageDiagramSrc"
        class="absolute top-0 left-0 w-full h-auto object-contain pointer-events-none" alt="Network Diagram" />

      <!-- Marker + Text -->
<div
  v-for="(m, i) in resolvedMarkers"
  :key="`marker-${i}-${svgKey}`"
  class="absolute"
  :style="m.style"
>
  <!-- Layer posisi (translate) -->
  <div class="marker-pos">
    <!-- Layer scale -->
    <div class="marker-scale">
      <!-- Marker -->
      <img
        :src="m.image"
        :alt="m.title || 'Marker'"
        class="marker-img cursor-pointer"
      />
    </div>

    <!-- Text (muncul saat hover marker) -->
    <span
            class="marker-text absolute left-1/2 top-full mt-1 px-2 py-1 text-xs font-semibold bg-black/70 text-white rounded opacity-100 scale-100 transition-all duration-300">
            {{ m.title }}
          </span>
  </div>
</div>


      <!-- SVG Overlay -->
      <div class="svg">
        <svg :key="svgKey" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 815.1 538.2">

          <line x1="55.16" y1="221.38" x2="114.01" y2="311.75" fill="none" class="line green" stroke-miterlimit="10"
            stroke-width="4" />

          <line x1="146.33" y1="353.67" x2="233.39" y2="414.75" fill="none" class="line green" stroke-miterlimit="10"
            stroke-width="4" />

          <polyline points="233.39 414.75 329.33 380.44 602.54 251.26 655.31 278.19" class="line red"
            stroke-miterlimit="10" stroke-width="4" />
          <polyline points="234.13 414.29 335.43 390.44 464.61 327.94 507.32 358.57" class="line red" stroke-miterlimit="10"
            stroke-width="4" />
          <polyline points="233.39 414.75 324.04 370.38 642.91 220.21 566.84 181.23" class="line red" stroke-miterlimit="10"
            stroke-width="4" />
          <polyline points="233.39 414.75 318.66 361.17 479.44 286.49 409.45 242.91" class="line red"
            stroke-miterlimit="10" stroke-width="4" />


          <polyline points="534.21 372.98 567.57 394.59 589.3 381.03 632.76 409.73" fill="none" class="line black" stroke-miterlimit="10" />
          <line x1="508.28" y1="371.32" x2="576.52" y2="418.33" fill="none" class="line black" stroke-miterlimit="10" />
          <polyline points="489.6 372.98 533.8 404.27 502.04 423.78 514.13 430.6" fill="none" class="line black"
            stroke-miterlimit="10" />

          <polyline points="584.13 171.46 543.25 149.08 573.91 136.69 554.25 126.59" fill="none" class="line black" stroke-miterlimit="10" />
          <line x1="555.53" y1="170.83" x2="505.27" y2="143" fill="none" class="line black" stroke-miterlimit="10" />
          <polyline points="544.49 180.17 510.22 161.59 473.37 175.83 435.39 156.92" fill="none" class="line black" stroke-miterlimit="10" />

          <polyline points="682.24 296.8 715.6 318.41 737.33 304.85 780.79 333.55" fill="none" class="line black"
            stroke-miterlimit="10" />
          <line x1="656.31" y1="295.14" x2="724.55" y2="342.15" fill="none" class="line black" stroke-miterlimit="10" />
          <polyline points="637.64 296.8 681.83 328.09 650.07 347.59 662.17 354.42" fill="none" class="line black" stroke-miterlimit="10" />

          <polyline points="413.07 226.65 372.19 204.27 402.85 191.89 383.19 181.79" fill="none" class="line black"
            stroke-miterlimit="10" />
          <line x1="384.47" y1="226.02" x2="334.21" y2="198.19" fill="none" class="line black" stroke-miterlimit="10" />
          <polyline points="373.43 235.36 339.17 216.78 302.31 231.02 268.24 209.39" fill="none" class="line black"
            stroke-miterlimit="10" />

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
  const name = props.diagram || p.diagram
  if (!name) return ''

  // Gunakan path public yang bisa diakses langsung
  return `/assets/static/network_diagram/switch/${name}.png`
})

// ======= Konstanta ukuran viewBox SVG =======
const VIEW_W = 815.1
const VIEW_H = 538.2

// ======= Titik anchor per subCategory =======
// Setiap item: { x, y, sizePct } — sizePct dipakai untuk penyesuaian ukuran per titik
const ANCHORS = {
  // OLT: 1 titik di ujung polyline biru
  BACKBONE: [
    { x: 280, y: 430, sizePct: 10 },
  ],

  // ONU & ONU PoE:
  // - Ujung polyline kuning (334.1, 301.6)
  // - Ujung 3 cabang hijau (710.2,349.3), (702.1,322.2), (702.1,288.1)
  // - Ujung line kuning lurus ke kiri (334.1,309.7)
  'L3 SWITCH': [
    { x: 440, y: 250, sizePct: 12 },
    { x: 610, y: 190, sizePct: 12 },
    { x: 560, y: 380, sizePct: 12 },
    { x: 710, y: 305, sizePct: 12 },
  ],
  'L2 SWITCH': [
    { x: 440, y: 250, sizePct: 12 },
    { x: 610, y: 190, sizePct: 12 },
    { x: 560, y: 380, sizePct: 12 },
    { x: 710, y: 305, sizePct: 12 },
  ],
  'PoE SWITCH': [
    { x: 440, y: 250, sizePct: 12 },
    { x: 610, y: 190, sizePct: 12 },
    { x: 560, y: 380, sizePct: 12 },
    { x: 710, y: 305, sizePct: 12 },
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
  const cat = p.category || ''
  const points = ANCHORS[sub] || FALLBACK

  console.log('=== SwitchLine Debug ===')
  console.log('Product:', p.title)
  console.log('subCategory:', sub)
  console.log('Points from ANCHORS:', points)
  console.log('Available ANCHORS keys:', Object.keys(ANCHORS))

  const markers = points.map((pt) => {
    const style = toStyle(pt)
    console.log('Point:', pt, '→ Style:', style)
    return {
      image: p.image,
      title: p.title,
      style: style,
    }
  })

  console.log('Final markers:', markers)
  return markers
})

/* =======================
   Animation reload
   ======================= */
const svgKey = ref(0)
function restartAnimation() {
  svgKey.value += 1
}
watchEffect(() => {
  // debug aktif untuk troubleshooting
  console.log('watchEffect triggered - subCategory:', props.product?.subCategory)
  console.log('watchEffect triggered - markers:', resolvedMarkers.value)
})
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

.red {
  stroke: #ba112d;
  stroke-width: 2px;
}

.green {
  stroke: #32f93c;
  stroke-width: 3px;
}

/* (Opsional) Variasi kecepatan animasi per warna */
.line.red {
  animation-duration: 14s;
}

.line.green {
  animation-duration: 10s;
}

.line.black {
  animation-duration: 14s;
}

/* (Opsional) Delay berurutan jika pakai class step-X */
.line.step-1 { animation-delay: 0s; }
.line.step-2 { animation-delay: 0.2s; }
.line.step-3 { animation-delay: 0.5s; }
.line.step-4 { animation-delay: 0.7s; }
.line.step-5 { animation-delay: 1s; }
.line.step-6 { animation-delay: 1.3s; }
.line.step-7 { animation-delay: 1.6s; }
.line.step-8 { animation-delay: 1.9s; }
.line.step-9 { animation-delay: 2.2s; }
.line.step-10 { animation-delay: 2.5s; }
.line.step-11 { animation-delay: 2.8s; }
.line.step-12 { animation-delay: 3.1s; }
.line.step-13 { animation-delay: 3.4s; }
.line.step-14 { animation-delay: 3.7s; }
.line.step-15 { animation-delay: 4s; }

</style>
