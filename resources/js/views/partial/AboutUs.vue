<template>
  <section id="aboutus">
    <div class="container mx-auto py-16 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-8">
        <div class="max-w-lg">
          <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ $t('ui.aboutUs') }}</h2>
          <p class="mt-4 text-gray-600 text-lg">
            {{ $t('ui.establishedIn2012AsValueAddedPartnerOfDasanNetworksMoimstoneDasanIndonesiaMdi') }}
          </p>
        </div>
        <div class="mt-12 md:mt-0">
          <img :src="aboutUsImg" :alt="$t('ui.aboutUsImage')"
            class="object-cover rounded-lg shadow-md" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const aboutUsImg = '/assets/static/images/aboutus.jpg'
</script>
