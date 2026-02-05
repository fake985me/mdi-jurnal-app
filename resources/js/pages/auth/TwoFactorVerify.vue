<template>
  <div class="min-h-screen bg-linear-to-br from-slate-800 via-sky-900 to-gray-500 flex items-center justify-center p-6">
    <!-- Animated Background -->
    <div class="absolute inset-0 overflow-hidden opacity-20">
      <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-cyan-500 rounded-full filter blur-3xl animate-pulse"></div>
      <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-pink-500 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <!-- 2FA Card -->
    <div class="relative z-10 w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8 animate-fade-in">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl mb-4 shadow-2xl">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-cyan-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Verifikasi Dua Faktor</h1>
        <p class="text-gray-300">Masukkan kode 6 digit yang dikirim ke</p>
        <p class="text-cyan-400 font-medium">{{ maskedEmail }}</p>
      </div>

      <!-- 2FA Form -->
      <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/20">
        <form @submit.prevent="handleVerify" class="space-y-6">
          <!-- Code Input -->
          <div>
            <label class="block text-sm font-medium text-white mb-4 text-center">Kode Verifikasi</label>
            <div class="flex justify-center gap-2">
              <input
                v-for="(digit, index) in codeDigits"
                :key="index"
                :ref="el => codeInputs[index] = el"
                v-model="codeDigits[index]"
                type="text"
                maxlength="1"
                inputmode="numeric"
                pattern="[0-9]*"
                class="w-12 h-14 text-center text-2xl font-bold bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                @input="handleInput(index, $event)"
                @keydown="handleKeydown(index, $event)"
                @paste="handlePaste"
              />
            </div>
          </div>

          <!-- Timer -->
          <div class="text-center">
            <p v-if="countdown > 0" class="text-gray-300 text-sm">
              Kode berlaku dalam <span class="text-cyan-400 font-bold">{{ formatTime(countdown) }}</span>
            </p>
            <p v-else class="text-red-400 text-sm">
              Kode sudah kadaluarsa
            </p>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="bg-red-500/20 backdrop-blur-sm border border-red-500/30 text-red-200 p-4 rounded-lg text-sm">
            {{ error }}
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="bg-green-500/20 backdrop-blur-sm border border-green-500/30 text-green-200 p-4 rounded-lg text-sm">
            {{ successMessage }}
          </div>

          <!-- Verify Button -->
          <button
            type="submit"
            :disabled="loading || !isCodeComplete"
            class="w-full py-3 bg-linear-to-r from-cyan-500 to-blue-600 text-white font-semibold rounded-lg hover:from-cyan-600 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-cyan-500/50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Memverifikasi...' : 'Verifikasi' }}
          </button>

          <!-- Resend Code -->
          <div class="text-center">
            <button
              type="button"
              :disabled="resendCooldown > 0 || resending"
              @click="handleResend"
              class="text-cyan-400 hover:text-cyan-300 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <template v-if="resending">Mengirim ulang...</template>
              <template v-else-if="resendCooldown > 0">Kirim ulang kode ({{ resendCooldown }}s)</template>
              <template v-else>Kirim ulang kode</template>
            </button>
          </div>
        </form>
      </div>

      <!-- Back to Login -->
      <div class="text-center mt-6">
        <router-link to="/login" class="text-gray-300 hover:text-white transition-colors">
          ← Kembali ke Login
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Code input state
const codeDigits = ref(['', '', '', '', '', '']);
const codeInputs = ref([]);

// UI state
const loading = ref(false);
const resending = ref(false);
const error = ref('');
const successMessage = ref('');

// Timer state
const countdown = ref(600); // 10 minutes in seconds
const resendCooldown = ref(0);
let countdownInterval = null;
let resendInterval = null;

// Computed
const maskedEmail = computed(() => authStore.twoFactorEmail || '***@***.com');
const isCodeComplete = computed(() => codeDigits.value.every(d => d !== ''));

// Methods
const handleInput = (index, event) => {
  const value = event.target.value;
  
  // Only allow digits
  if (!/^\d*$/.test(value)) {
    codeDigits.value[index] = '';
    return;
  }

  // Move to next input
  if (value && index < 5) {
    codeInputs.value[index + 1]?.focus();
  }
};

const handleKeydown = (index, event) => {
  // Handle backspace - move to previous input
  if (event.key === 'Backspace' && !codeDigits.value[index] && index > 0) {
    codeInputs.value[index - 1]?.focus();
  }
};

const handlePaste = (event) => {
  event.preventDefault();
  const pastedData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
  
  for (let i = 0; i < pastedData.length; i++) {
    codeDigits.value[i] = pastedData[i];
  }
  
  // Focus last filled input or next empty
  const nextIndex = Math.min(pastedData.length, 5);
  codeInputs.value[nextIndex]?.focus();
};

const formatTime = (seconds) => {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const handleVerify = async () => {
  if (!isCodeComplete.value) return;
  
  loading.value = true;
  error.value = '';
  
  try {
    const code = codeDigits.value.join('');
    await authStore.verify2fa(code);
    router.push('/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Kode verifikasi tidak valid.';
    // Clear the code on error
    codeDigits.value = ['', '', '', '', '', ''];
    codeInputs.value[0]?.focus();
  } finally {
    loading.value = false;
  }
};

const handleResend = async () => {
  resending.value = true;
  error.value = '';
  successMessage.value = '';
  
  try {
    await authStore.resend2faCode();
    successMessage.value = 'Kode verifikasi baru telah dikirim.';
    
    // Reset countdown
    countdown.value = 600;
    
    // Start resend cooldown (60 seconds)
    resendCooldown.value = 60;
    resendInterval = setInterval(() => {
      resendCooldown.value--;
      if (resendCooldown.value <= 0) {
        clearInterval(resendInterval);
      }
    }, 1000);
    
    // Clear code inputs
    codeDigits.value = ['', '', '', '', '', ''];
    codeInputs.value[0]?.focus();
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal mengirim ulang kode.';
  } finally {
    resending.value = false;
  }
};

// Lifecycle
onMounted(() => {
  // Check if we have required 2FA data
  if (!authStore.twoFactorUserId) {
    router.push('/login');
    return;
  }
  
  // Focus first input
  codeInputs.value[0]?.focus();
  
  // Start countdown
  countdownInterval = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--;
    }
  }, 1000);
});

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval);
  if (resendInterval) clearInterval(resendInterval);
});
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

/* Hide number input spinners */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
