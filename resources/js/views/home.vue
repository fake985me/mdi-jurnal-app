<script setup>
import Carousel from '../components/Carousel.vue'
import ProductView from '../views/partial/ProductView.vue'
import SolutionsView from '../views/partial/SolutionsView.vue'
import ProjectView from '../views/partial/ProjectView.vue'
import AboutUs from '../views/partial/AboutUs.vue'
import FirmwareModification from '../views/partial/Firmware Modification.vue'
</script>

<template>
  <div>
    <main class="min-h-screen">
      <Carousel />
      <ProductView />
      <FirmwareModification/>
      <SolutionsView />
      <AboutUs />
      <ProjectView />
    </main>
  </div>
</template>
