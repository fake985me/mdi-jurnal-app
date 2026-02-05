<template>
  <section id="aboutus">
    <div class="container mx-auto py-16 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-8">
        <div class="max-w-lg">
          <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">About Us</h2>
          <p class="mt-4 text-gray-600 text-lg">
            Established in 2012, As value added partner of Dasan Networks, Moimstone Dasan Indonesia
            (MDI) specializes in providing reliable and efficient system integration solutions to
            help businesses streamline their operations. With 12 years of experience in EDGE Access
            Industry, we've earned a reputation as a trusted partner, known for our unwavering
            commitment to delivering dependable technology solutions
          </p>
        </div>
        <div class="mt-12 md:mt-0">
          <img :src="aboutUsImg" alt="About Us Image"
            class="object-cover rounded-lg shadow-md" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const aboutUsImg = '/assets/static/images/aboutus.jpg'
</script>
