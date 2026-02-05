<template>
  <navbar v-if="isPublic" />
  <router-view />
  <customer-view v-if="isPublic" />
  <foo v-if="isPublic" />
</template>

<script setup>
import { RouterView } from 'vue-router'
import { computed } from 'vue';
import { useRoute } from 'vue-router';

import Navbar from './components/Navbar.vue';
import CustomerView from './views/partial/CustomerView.vue';
import Foo from './components/Footer.vue';
import '../css/app.css';

const route = useRoute();
const isPublic = computed(() => {
  return route.meta.public === true;
});
</script>

<style>
/* Removed height: 100% to allow sticky navbar to work properly */
</style>