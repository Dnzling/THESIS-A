<template>
  <div
    class="min-h-screen bg-linear-to-br from-slate-100 via-blue-50 to-indigo-100 flex flex-col items-center justify-center px-4 relative overflow-hidden">
  
    <!-- Subtle Background Pattern -->
    <div class="absolute inset-0 opacity-30">
      <div
        class="absolute top-20 left-20 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl animate-blob">
      </div>
      <div
        class="absolute top-40 right-20 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000">
      </div>
      <div
        class="absolute bottom-20 left-1/2 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000">
      </div>
    </div>
  
  
    <div class="relative z-10 text-center max-w-2xl w-full space-y-8">
      <!-- 403 Error Code -->
      <div>
        <h1 class="text-8xl md:text-9xl font-black text-slate-700 tracking-tight">
          403
        </h1>
      </div>
  
      <!-- Heading -->
      <div class="space-y-4">
        <h2 class="text-4xl md:text-5xl font-bold text-slate-800 tracking-tight">
          Access Denied
        </h2>
        <p class="text-lg md:text-xl text-slate-600 max-w-md mx-auto">
          You don't have the permissions to access this page
        </p>
      </div>
    </div>
  
    <!-- Current Role Display -->
    <!-- <div
                  class="inline-flex items-center gap-3 px-6 py-3 bg-white/60 backdrop-blur-sm rounded-full border border-slate-200 shadow-lg">
                  <div
                    class="w-10 h-10 bg-linear-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md">
                    <i class="pi pi-user text-white text-sm"></i>
                  </div>
                  <div class="text-left">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Your Role</p>
                    <p class="text-base font-bold text-slate-800">{{ authStore.userRole || 'Unknown' }}</p>
                  </div>
                </div> -->
  
    <!-- Countdown Timer -->
    <div class="space-y-4 py-6">
      <div class="flex items-center justify-center gap-3">
        <div class="text-left">
          <p class="text-sm text-slate-600 font-medium">Auto logout in</p>
          <p class="text-4xl font-bold text-slate-800 tabular-nums text-center">{{ countdown }}</p>
        </div>
      </div>
  
      <!-- Progress Bar -->
      <div class="max-w-xs mx-19-auto">
        <div class="h-2 bg-white/60 backdrop-blur-sm rounded-full overflow-hidden shadow-inner border border-slate-200">
          <div
            class="h-full bg-linear-to-r from-blue-400 via-blue-500 to-violet-500 transition-all duration-1000 ease-linear rounded-full"
            :style="{ width: progressWidth + '%' }"></div>
        </div>
      </div>
    </div>
  
    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
      <Button @click="cancelAndGoBack" label="Go to Dashboard" severity="info" />
      <!-- <span class="flex items-center gap-2">
                        <i class="pi pi-arrow-left text-lg group-hover:-translate-x-1 transition-transform"></i>
                        Go Back
                      </span>
                    </Button> -->
  
      <Button @click="handleLogout" :disabled="isLoggingOut" :label="isLoggingOut ? 'Logging out...' : 'Logout Now'"
        :icon="isLoggingOut ? 'pi pi-spinner pi-spin' : ''" icon-pos="right" severity="danger" />
    </div>
  
    <!-- Help Text -->
    <div class="pt-8">
      <p class="text-sm text-slate-600 flex items-center justify-center gap-2">
        <i class="pi pi-info-circle text-blue-500"></i>
        Need access? Contact your system administrator
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const countdown = ref(5)
const progressWidth = ref(100)
const isLoggingOut = ref(false)
// let countdownInterval: number | null = null

const handleLogout = async () => {
  if (isLoggingOut.value) return

  isLoggingOut.value = true

  // Clear the countdown interval
  if (countdownInterval !== null) {
    clearInterval(countdownInterval)
  }

  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
    // Force redirect to login even if logout fails
    router.push('/login')
  } finally {
    isLoggingOut.value = false
  }
}

const cancelAndGoBack = () => {
  // Clear the countdown
  if (countdownInterval !== null) {
    clearInterval(countdownInterval)
  }

  // Go back to previous page or default route
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push(authStore.defaultRoute)
  }
}

const startCountdown = () => {
  countdownInterval = setInterval(() => {
    countdown.value--
    progressWidth.value = (countdown.value / 5) * 100

    if (countdown.value <= 0) {
      if (countdownInterval !== null) {
        clearInterval(countdownInterval)
      }
      handleLogout()
    }
  }, 1000) as unknown as number
}

onMounted(() => {
  startCountdown()
})

onUnmounted(() => {
  if (countdownInterval !== null) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
/* Blob animation */
@keyframes blob {

  0%,
  100% {
    transform: translate(0, 0) scale(1);
  }

  25% {
    transform: translate(20px, -20px) scale(1.1);
  }

  50% {
    transform: translate(-20px, 20px) scale(0.9);
  }

  75% {
    transform: translate(20px, 20px) scale(1.05);
  }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

/* Smooth number transition */
.tabular-nums {
  font-variant-numeric: tabular-nums;
}
</style>