<template>

  <div class="min-h-screen bg-linear-to-br from-slate-800 via-sky-900 to-gray-500 flex items-center justify-center p-6">
    <!-- Animated Background -->
    <div class="absolute inset-0 overflow-hidden opacity-20">
      <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-cyan-500 rounded-full filter blur-3xl animate-pulse"></div>
      <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-pink-500 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8 animate-fade-in">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl mb-4 shadow-2xl">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true" class="mb-3 w-16 h-16"
            viewBox="0 0 2 2">
            <path fill="#3636e0"
              d="M.892 1.548H.678v-.296q0-.034-.008-.047t-.027-.013q-.036 0-.036.061v.296H.394v-.296q0-.034-.008-.047t-.027-.014q-.036 0-.036.061v.296H.11V1.21q0-.092.064-.157T.331.988q.095 0 .17.077Q.585.988.669.988q.107 0 .172.075.051.058.051.17zM1.374.8h.214v.458q0 .126-.069.205-.04.046-.1.073t-.125.027q-.128 0-.215-.083t-.088-.202q0-.116.087-.201t.206-.085l.057.003v.227q-.026-.02-.053-.02-.033 0-.057.023t-.024.056q0 .032.024.055t.059.023q.082 0 .082-.11zm.578.199v.549h-.214V.999zM1.926.786q-.032-.03-.075-.03t-.075.03-.032.07q0 .014.003.026l.006.016q.007.016.021.028.03.028.077.028c.047 0 .057-.009.077-.028q.014-.013.021-.029l.002-.005q.006-.017.006-.037 0-.04-.032-.07m.065.016Q1.973.754 1.928.729T1.834.717q-.046.011-.068.048L1.763.77l-.001.002q.02-.036.066-.045.048-.01.092.014t.064.069q.017.042.001.077.021-.04.004-.087m.043-.02Q2.011.722 1.955.692c-.056-.03-.076-.025-.116-.015q-.056.014-.083.058l-.004.006-.001.002Q1.775.7 1.832.689q.058-.012.114.018t.078.085q.021.051.002.094.025-.049.004-.106" />
          </svg>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">{{ $t('login.title') }}</h1>
        <p class="text-gray-300">{{ $t('login.subtitle') }}</p>
      </div>

      <!-- Login Form -->
      <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/20">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-white mb-2">{{ $t('login.emailLabel') }}</label>
            <input
              v-model="email"
              type="email"
              required
              class="w-full px-4 py-3 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-white placeholder-gray-300 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
              placeholder="admin@warehouse.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-white mb-2">{{ $t('login.passwordLabel') }}</label>
            <input
              v-model="password"
              type="password"
              required
              class="w-full px-4 py-3 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-white placeholder-gray-300 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
              placeholder="••••••••"
            />
          </div>

          <div v-if="error" class="bg-red-500/20 backdrop-blur-sm border border-red-500/30 text-red-200 p-4 rounded-lg text-sm">
            {{ error }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 bg-linear-to-r from-cyan-500 to-blue-600 text-white font-semibold rounded-lg hover:from-cyan-600 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-cyan-500/50 disabled:opacity-50"
          >
            {{ loading ? $t('login.signingIn') : $t('login.signIn') }}
          </button>
        </form>

        <!-- Demo Accounts -->
        <div class="mt-8 pt-6 border-t border-white/20">
          <p class="text-sm text-gray-300 mb-3 font-medium">{{ $t('login.demoAccounts') }}</p>
          <div class="space-y-2 text-sm">
            <div class="bg-white/5 backdrop-blur-sm p-3 rounded-lg border border-white/10">
              <p class="text-white font-medium">{{ $t('login.superAdmin') }}</p>
              <p class="text-gray-300 text-xs">testmoimstone@gmail.com / password</p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm p-3 rounded-lg border border-white/10">
              <p class="text-white font-medium">{{ $t('login.adminUser') }}</p>
              <p class="text-gray-300 text-xs">admin@example.com / password</p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm p-3 rounded-lg border border-white/10">
              <p class="text-white font-medium">{{ $t('login.salesUser') }}</p>
              <p class="text-gray-300 text-xs">sales@example.com / password</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Back to Home -->
      <div class="text-center mt-6">
        <router-link to="/" class="text-gray-300 hover:text-white transition-colors">
          {{ $t('common.backToHome') }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await authStore.login({
      email: email.value,
      password: password.value,
    });
    
    // Check if 2FA is required
    if (response.requires_2fa) {
      router.push('/verify-2fa');
    } else {
      router.push('/dashboard');
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please check your credentials.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}
</style>
