<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent">User Management</h2>
        <p class="text-sm text-gray-600 mt-1">Manage system users and roles (Superadmin Only)</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium"
      >
        + Add User
      </button>
    </div>

    <!-- Role Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Super Admin</p>
            <p class="text-3xl font-bold text-red-600">{{ roleCount('superadmin') }}</p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-2xl">⚡</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Admin</p>
            <p class="text-3xl font-bold text-blue-600">{{ roleCount('admin') }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-2xl">👨‍💼</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Sales</p>
            <p class="text-3xl font-bold text-green-600">{{ roleCount('sales') }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-2xl">💼</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">Guest</p>
            <p class="text-3xl font-bold text-gray-600">{{ roleCount('guest') }}</p>
          </div>
          <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-2xl">👤</div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">System Users</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading users...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="flex items-center">
                <div :class="`w-10 h-10 ${getRoleColor(user.role)} rounded-full flex items-center justify-center text-white font-bold`">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <span class="ml-3 text-sm font-medium text-gray-900">{{ user.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
            <td class="px-6 py-4">
              <span :class="getRoleBadge(user.role)">{{ user.role }}</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(user.created_at) }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="editUser(user)" class="text-blue-600 hover:text-blue-900">Edit</button>
              <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl">
        <h3 class="text-2xl font-bold mb-6">{{ editingUser ? 'Edit User' : 'Add New User' }}</h3>
        
        <form @submit.prevent="saveUser" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
              <input v-model="form.name" required class="input" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input v-model="form.email" type="email" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password {{ editingUser ? '(leave blank to keep)' : '*' }}</label>
              <input v-model="form.password" type="password" :required="!editingUser" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
              <select v-model="form.role" required class="input">
                <option value="superadmin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="sales">Sales</option>
                <option value="guest">Guest</option>
              </select>
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save User' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';

const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingUser = ref(null);
const saving = ref(false);
const error = ref('');

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'admin',
});

const roleCount = (role) => users.value.filter(u => u.role === role).length;

const getRoleColor = (role) => {
  const colors = {
    superadmin: 'bg-gradient-to-r from-red-500 to-pink-500',
    admin: 'bg-gradient-to-r from-blue-500 to-indigo-500',
    sales: 'bg-gradient-to-r from-green-500 to-teal-500',
    guest: 'bg-gradient-to-r from-gray-500 to-gray-600',
  };
  return colors[role] || 'bg-gray-500';
};

const getRoleBadge = (role) => {
  const badges = {
    superadmin: 'badge bg-red-100 text-red-800',
    admin: 'badge bg-blue-100 text-blue-800',
    sales: 'badge bg-green-100 text-green-800',
    guest: 'badge bg-gray-100 text-gray-800',
  };
  return badges[role] || 'badge';
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const loadUsers = async () => {
  loading.value = true;
  try {
    const response = await api.get('/users');
    users.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    password: '',
    role: 'admin',
  };
  editingUser.value = null;
  error.value = '';
};

const editUser = (user) => {
  editingUser.value = user;
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
  };
  showModal.value = true;
};

const saveUser = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.id}`, form.value);
    } else {
      await api.post('/users', form.value);
    }
    showModal.value = false;
    loadUsers();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save user';
  } finally {
    saving.value = false;
  }
};

const deleteUser = async (id) => {
  if (!confirm('Delete this user? This action cannot be undone.')) return;
  try {
    await api.delete(`/users/${id}`);
    loadUsers();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete user');
  }
};

onMounted(() => {
  loadUsers();
});
</script>
