<template>
  <Disclosure v-slot="{ open }" :default-open="isActive">
    <!-- Parent -->
    <div>
      <router-link v-if="!item.children" :to="item.to" class="flex items-center p-2 rounded-lg hover:bg-gray-100"
        :class="isActive && 'bg-gray-200 font-semibold'">
        <span>{{ item.icon }}</span>
        <span v-if="!collapsed" class="ml-3">{{ item.name }}</span>
      </router-link>

      <DisclosureButton v-else class="flex items-center w-full p-2 rounded-lg hover:bg-gray-100"
        :class="isActive && 'bg-gray-200 font-semibold'">
        <span>{{ item.icon }}</span>
        <span v-if="!collapsed" class="ml-3 flex-1 text-left">
          {{ item.name }}
        </span>
        <span v-if="!collapsed">▾</span>
      </DisclosureButton>

      <!-- Children -->
      <TransitionRoot :show="open">
        <TransitionChild enter="transition duration-200 ease-out" enter-from="transform opacity-0 scale-95"
          enter-to="transform opacity-100 scale-100" leave="transition duration-150 ease-in"
          leave-from="transform opacity-100 scale-100" leave-to="transform opacity-0 scale-95">
          <DisclosurePanel v-if="item.children && !collapsed" class="ml-8 mt-1 space-y-1">
            <router-link v-for="child in filteredChildren" :key="child.name" :to="child.to"
              class="block p-2 rounded text-sm hover:bg-gray-100"
              :class="isChildActive(child) && 'bg-gray-200 font-semibold'">
              {{ child.name }}
            </router-link>
          </DisclosurePanel>
        </TransitionChild>
      </TransitionRoot>

    </div>
  </Disclosure>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  Disclosure,
  DisclosureButton,
  DisclosurePanel,
  TransitionRoot,
  TransitionChild
} from '@headlessui/vue'

const props = defineProps({
  item: Object,
  collapsed: Boolean,
  userRole: String
})

const route = useRoute()

const filteredChildren = computed(() =>
  props.item.children?.filter(c => c.roles.includes(props.userRole))
)

const isChildActive = child => route.path.startsWith(child.to)

const isActive = computed(() => {
  if (props.item.to) return route.path === props.item.to
  return filteredChildren.value?.some(isChildActive)
})
</script>
