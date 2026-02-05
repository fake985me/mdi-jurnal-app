<template>
  <div class="drag-drop-builder grid grid-cols-12 gap-6 h-screen overflow-hidden">
    <!-- Left Sidebar - Section Library -->
    <div class="col-span-3 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
        <h3 class="font-semibold text-lg">Section Library</h3>
        <p class="text-sm text-gray-600">Drag sections to canvas</p>
      </div>

      <!-- Category Tabs -->
      <div class="p-4">
        <div class="space-y-2 mb-4">
          <button
            v-for="category in categories"
            :key="category"
            @click="selectedCategory = category"
            :class="[
              'w-full text-left px-3 py-2 rounded-lg transition-colors capitalize',
              selectedCategory === category
                ? 'bg-indigo-100 text-indigo-700 font-medium'
                : 'hover:bg-gray-100 text-gray-700'
            ]"
          >
            {{ category }}
          </button>
        </div>

        <!-- Template Cards -->
        <div class="space-y-3">
          <div
            v-for="template in filteredTemplates"
            :key="template.id"
            draggable="true"
            @dragstart="handleDragStart($event, template)"
            class="p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-400 hover:bg-indigo-50 cursor-move transition-all"
          >
            <div class="flex items-center gap-3">
              <span class="text-3xl">{{ template.thumbnail }}</span>
              <div class="flex-1">
                <div class="font-medium text-sm">{{ template.name }}</div>
                <div class="text-xs text-gray-500 capitalize">{{ template.category }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Center - Canvas -->
    <div class="col-span-6 bg-gray-100 overflow-y-auto">
      <div class="p-6">
        <div class="bg-white rounded-lg shadow-lg min-h-screen">
          <!-- Page Header Info -->
          <div class="p-6 border-b border-gray-200 bg-gray-50">
            <h2 class="text-2xl font-bold text-gray-800">{{ pageTitle || 'Untitled Page' }}</h2>
            <p class="text-sm text-gray-600 mt-1">{{ sections.length }} section(s)</p>
          </div>

          <!-- Drop Zone -->
          <div
            ref="dropZone"
            @dragover.prevent="handleDragOver"
            @drop="handleDrop"
            class="min-h-[600px]"
            :class="{ 'bg-indigo-50 border-2 border-dashed border-indigo-400': isDragging }"
          >
            <!-- Empty State -->
            <div v-if="sections.length === 0" class="flex items-center justify-center h-96 text-center">
              <div>
                <div class="text-6xl mb-4">📄</div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Start Building Your Page</h3>
                <p class="text-gray-500">Drag sections from the library to get started</p>
              </div>
            </div>

            <!-- Sections -->
            <draggable
              v-model="sections"
              :component-data="{ class: 'sections-list' }"
              item-key="uid"
              handle=".drag-handle"
              @start="isDraggingSection = true"
              @end="isDraggingSection = false"
            >
              <template #item="{ element, index }">
                <div
                  :class="[
                    'section-item group relative',
                    selectedSectionIndex === index ? 'ring-2 ring-indigo-500' : ''
                  ]"
                  @click="selectSection(index)"
                >
                  <!-- Section Toolbar -->
                  <div class="absolute top-2 right-2 z-20 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      class="drag-handle p-2 bg-white rounded shadow hover:bg-gray-100 cursor-move"
                      title="Drag to reorder"
                    >
                      ⬍
                    </button>
                    <button
                      @click.stop="duplicateSection(index)"
                      class="p-2 bg-white rounded shadow hover:bg-gray-100"
                      title="Duplicate"
                    >
                      📋
                    </button>
                    <button
                      @click.stop="toggleSectionVisibility(index)"
                      class="p-2 bg-white rounded shadow hover:bg-gray-100"
                      :title="element.is_visible ? 'Hide' : 'Show'"
                    >
                      {{ element.is_visible ? '👁️' : '👁️‍🗨️' }}
                    </button>
                    <button
                      @click.stop="deleteSection(index)"
                      class="p-2 bg-red-100 text-red-600 rounded shadow hover:bg-red-200"
                      title="Delete"
                    >
                      🗑️
                    </button>
                  </div>

                  <!-- Section Badge -->
                  <div class="absolute top-2 left-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="px-2 py-1 bg-gray-900 text-white text-xs rounded">
                      {{ element.section_type }}
                    </span>
                  </div>

                  <!-- Render Section -->
                  <SectionRenderer :section="element" :is-preview="true" />

                  <!-- Invisible visibility overlay -->
                  <div v-if="!element.is_visible" class="absolute inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
                    <span class="text-white font-semibold text-lg">Hidden Section</span>
                  </div>
                </div>
              </template>
            </draggable>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Sidebar - Properties Editor -->
    <div class="col-span-3 bg-white border-l border-gray-200 overflow-y-auto">
      <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
        <h3 class="font-semibold text-lg">Properties</h3>
      </div>

      <div v-if="selectedSection" class="p-4 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Section Type</label>
          <input
            v-model="selectedSection.section_type"
            disabled
            class="w-full px-3 py-2 border rounded-lg bg-gray-50 text-gray-500"
          />
        </div>

        <!-- Heading -->
        <div v-if="selectedSection.content.heading !== undefined">
          <label class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
          <input
            v-model="selectedSection.content.heading"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <!-- Subheading -->
        <div v-if="selectedSection.content.subheading !== undefined">
          <label class="block text-sm font-medium text-gray-700 mb-1">Subheading</label>
          <input
            v-model="selectedSection.content.subheading"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <!-- Text -->
        <div v-if="selectedSection.content.text !== undefined">
          <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
          <textarea
            v-model="selectedSection.content.text"
            rows="4"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
          ></textarea>
        </div>

        <!-- Button Text & URL -->
        <div v-if="selectedSection.content.button_text !== undefined">
          <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
          <input
            v-model="selectedSection.content.button_text"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div v-if="selectedSection.content.button_url !== undefined">
          <label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label>
          <input
            v-model="selectedSection.content.button_url"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <!-- Image URL -->
        <div v-if="selectedSection.content.image_url !== undefined">
          <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
          <input
            v-model="selectedSection.content.image_url"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
          />
          <img v-if="selectedSection.content.image_url" :src="selectedSection.content.image_url" class="mt-2 rounded border w-full h-32 object-cover" />
        </div>

        <!-- Visibility Toggle -->
        <div>
          <label class="flex items-center cursor-pointer">
            <input
              v-model="selectedSection.is_visible"
              type="checkbox"
              class="mr-2 rounded"
            />
            <span class="text-sm font-medium text-gray-700">Visible on Page</span>
          </label>
        </div>

        <!-- JSON Editor for Advanced Users -->
        <details class="mt-6">
          <summary class="cursor-pointer text-sm font-medium text-gray-700 mb-2">Advanced (JSON)</summary>
          <textarea
            :value="JSON.stringify(selectedSection, null, 2)"
            @input="updateSectionFromJSON($event.target.value)"
            rows="10"
            class="w-full px-3 py-2 border rounded-lg font-mono text-xs"
          ></textarea>
        </details>
      </div>

      <div v-else class="p-8 text-center text-gray-500">
        <div class="text-4xl mb-2">👆</div>
        <p>Select a section to edit its properties</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import { sectionTemplates, getCategoryNames } from './SectionTemplates';
import SectionRenderer from './SectionRenderer.vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  pageTitle: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

// State
const sections = ref([...props.modelValue]);
const selectedCategory = ref('hero');
const selectedSectionIndex = ref(null);
const isDragging = ref(false);
const isDraggingSection = ref(false);
const dropZone = ref(null);

// Categories
const categories = ref(getCategoryNames());

// Computed
const filteredTemplates = computed(() => {
  return sectionTemplates.filter(t => t.category === selectedCategory.value);
});

const selectedSection = computed(() => {
  return selectedSectionIndex.value !== null ? sections.value[selectedSectionIndex.value] : null;
});

// Watch sections and emit changes
watch(sections, (newSections) => {
  emit('update:modelValue', newSections);
}, { deep: true });

// Methods
let draggedTemplate = null;

const handleDragStart = (event, template) => {
  draggedTemplate = template;
  isDragging.value = true;
  event.dataTransfer.effectAllowed = 'copy';
};

const handleDragOver = (event) => {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'copy';
};

const handleDrop = (event) => {
  event.preventDefault();
  isDragging.value = false;

  if (draggedTemplate) {
    const newSection = {
      ...JSON.parse(JSON.stringify(draggedTemplate.template)),
      uid: Date.now() + Math.random(), // Unique ID for draggable
      order: sections.value.length,
    };
    sections.value.push(newSection);
    draggedTemplate = null;
  }
};

const selectSection = (index) => {
  selectedSectionIndex.value = index;
};

const deleteSection = (index) => {
  if (confirm('Delete this section?')) {
    sections.value.splice(index, 1);
    if (selectedSectionIndex.value === index) {
      selectedSectionIndex.value = null;
    }
  }
};

const duplicateSection = (index) => {
  const duplicate = JSON.parse(JSON.stringify(sections.value[index]));
  duplicate.uid = Date.now() + Math.random();
  sections.value.splice(index + 1, 0, duplicate);
};

const toggleSectionVisibility = (index) => {
  sections.value[index].is_visible = !sections.value[index].is_visible;
};

const updateSectionFromJSON = (jsonString) => {
  try {
    const parsed = JSON.parse(jsonString);
    if (selectedSectionIndex.value !== null) {
      sections.value[selectedSectionIndex.value] = { ...parsed, uid: sections.value[selectedSectionIndex.value].uid };
    }
  } catch (e) {
    console.error('Invalid JSON');
  }
};
</script>

<style scoped>
.drag-drop-builder {
  height: calc(100vh - 64px);
}

.section-item {
  position: relative;
  border-bottom: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.section-item:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.sections-list {
  min-height: 400px;
}
</style>
