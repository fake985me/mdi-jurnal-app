<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Site Settings</h2>
      <button
        @click="showModal = true; editingItem = null"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
      >
        + Add Setting
      </button>
    </div>

    <!-- Group Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex space-x-4">
        <button
          v-for="group in groups"
          :key="group"
          @click="selectedGroup = group"
          :class="[
            'px-4 py-2 font-medium text-sm border-b-2 transition-colors',
            selectedGroup === group
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          ]"
        >
          {{ group.charAt(0).toUpperCase() + group.slice(1) }}
        </button>
      </nav>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading settings...</p>
      </div>

      <div v-else class="p-6 space-y-4">
        <div v-for="setting in filteredSettings" :key="setting.id" class="border-b pb-4 last:border-0">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ setting.label || setting.key }}
              </label>
              <input
                v-if="setting.type === 'text'"
                v-model="setting.value"
                @change="updateSetting(setting)"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
              <textarea
                v-else-if="setting.type === 'textarea'"
                v-model="setting.value"
                @change="updateSetting(setting)"
                rows="3"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">Key: {{ setting.key }}</p>
            </div>
            <button
              @click="deleteSetting(setting.id)"
              class="ml-4 text-red-600 hover:text-red-900"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Add New Setting</h3>
        
        <form @submit.prevent="saveSetting" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Key *</label>
            <input v-model="form.key" required class="w-full px-3 py-2 border rounded-lg" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
            <input v-model="form.label" class="w-full px-3 py-2 border rounded-lg" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
            <textarea v-model="form.value" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
            <select v-model="form.group" class="w-full px-3 py-2 border rounded-lg">
              <option value="general">General</option>
              <option value="landing">Landing</option>
              <option value="contact">Contact</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select v-model="form.type" class="w-full px-3 py-2 border rounded-lg">
              <option value="text">Text</option>
              <option value="textarea">Textarea</option>
              <option value="image">Image</option>
            </select>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3">
            <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Cancel</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const settings = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingItem = ref(null);
const saving = ref(false);
const error = ref('');
const selectedGroup = ref('general');

const form = ref({
  key: '',
  value: '',
  label: '',
  group: 'general',
  type: 'text',
});

const groups = computed(() => {
  const groupSet = new Set(settings.value.map(s => s.group));
  return Array.from(groupSet);
});

const filteredSettings = computed(() => {
  return settings.value.filter(s => s.group === selectedGroup.value);
});

const loadSettings = async () => {
  loading.value = true;
  try {
    const response = await api.get('/site-settings');
    settings.value = response.data;
  } catch (err) {
    console.error('Failed to load settings:', err);
    settings.value = [];
  } finally {
    loading.value = false;
  }
};

const updateSetting = async (setting) => {
  try {
    await api.put(`/site-settings/${setting.id}`, { value: setting.value });
  } catch (err) {
    alert('Failed to update setting');
  }
};

const saveSetting = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/site-settings', form.value);
    showModal.value = false;
    loadSettings();
    form.value = { key: '', value: '', label: '', group: 'general', type: 'text' };
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save setting';
  } finally {
    saving.value = false;
  }
};

const deleteSetting = async (id) => {
  if (!confirm('Are you sure?')) return;
  try {
    await api.delete(`/site-settings/${id}`);
    loadSettings();
  } catch (err) {
    alert('Failed to delete setting');
  }
};

onMounted(() => loadSettings());
</script>
