<template>
  <div class="relative" :class="{ 'pl-4': isMobile }">
    <!-- Menu with children -->
    <div v-if="hasChildren">
      <div
        @click="toggleMobileDropdown"
        @mouseenter="!isMobile && (dropdownOpen = true)"
        @mouseleave="!isMobile && (dropdownOpen = false)"
        class="flex justify-between items-center cursor-pointer py-2 font-medium"
        :class="isItemActive ? 'text-blue-600 font-semibold' : 'text-gray-800 hover:text-blue-600'"
      >
        <router-link :to="item.link" class="flex-1">
          {{ item.label }}
        </router-link>
        <svg
          class="ml-2 h-4 w-4 transition-transform duration-200"
          :class="{
            'rotate-180': dropdownOpen
          }"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
            clip-rule="evenodd"
          />
        </svg>
      </div>

      <!-- Desktop submenu -->
      <div
        v-if="!isMobile && dropdownOpen"
        class="absolute left-0 mt-2 w-48 bg-white border rounded-md shadow-lg z-20"
      >
        <DropdownItem
          v-for="(child, idx) in item.children"
          :key="idx"
          :item="child"
        />
      </div>

      <!-- Mobile submenu -->
      <div v-if="isMobile && dropdownOpen" class="mt-1 space-y-1 pl-4">
        <DropdownItem
          v-for="(child, idx) in item.children"
          :key="'mobile-sub-' + idx"
          :item="child"
          :isMobile="true"
        />
      </div>
    </div>

    <!-- No children -->
    <div v-else>
      <router-link
        :to="item.link"
        class="block py-2 font-medium"
        :class="isItemActive ? 'text-blue-600 font-semibold' : 'text-gray-800 hover:text-blue-600'"
      >
        {{ item.label }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  item: Object,
  isMobile: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const dropdownOpen = ref(false)

const hasChildren = computed(() => !!props.item?.children)

const isItemActive = computed(() => {
  const isActive = (item) => {
    if (!item || !item.link) return false
    if (route.path.startsWith(item.link)) return true
    return item.children?.some(isActive)
  }
  return isActive(props.item)
})

// Auto-expand on mobile if active
watch(
  () => isItemActive.value,
  (val) => {
    if (props.isMobile && val) {
      dropdownOpen.value = true
    }
  },
  { immediate: true }
)

function toggleMobileDropdown() {
  if (props.isMobile && hasChildren.value) {
    dropdownOpen.value = !dropdownOpen.value
  }
}
</script>

<script>
export default {
  name: 'DropdownItem'
}
</script>
