<template>
  <OverlayPanel ref="op" :pt="{
    root: { class: 'shadow-lg border border-gray-200' },
    content: { class: 'p-0' }
  }">
    <div class="flex flex-col min-w-50">
      <!-- Menu Items -->
      <div class="py-2">
        <!-- Profile -->
        <button
          @click="goProfile"
          class="w-full px-4 py-2.5 flex items-center gap-3 hover:bg-gray-100 transition-colors duration-150 text-left group"
        >
          <i class="pi pi-user text-gray-600 group-hover:text-gray-900 text-sm"></i>
          <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Profile</span>
        </button>

        <!-- Settings (Optional) -->
        <button
          @click="goSettings"
          class="w-full px-4 py-2.5 flex items-center gap-3 hover:bg-gray-100 transition-colors duration-150 text-left group"
        >
          <i class="pi pi-cog text-gray-600 group-hover:text-gray-900 text-sm"></i>
          <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Settings</span>
        </button>
      </div>

      <div class="border-t border-gray-200"></div>

      <!-- Logout Section -->
      <div class="py-2">
        <button
          @click="handleLogout"
          class="w-full px-4 py-2.5 flex items-center gap-3 hover:bg-red-50 transition-colors duration-150 text-left group"
        >
          <i class="pi pi-power-off text-red-600 group-hover:text-red-700 text-sm"></i>
          <span class="text-sm font-medium text-red-600 group-hover:text-red-700">Logout</span>
        </button>
      </div>
    </div>
  </OverlayPanel>

  <!-- Enhanced Logout Dialog -->

   <Dialog v-model:visible="isLoggingOut" modal :closable="false" :showHeader="false" :style="{ width: '350px' }">
    <div class="flex flex-col items-center justify-center p-6">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" fill="transparent" animationDuration=".5s" />
      <p class="mt-4 text-lg font-medium text-gray-700">Logging you out...</p>
      <p class="text-gray-500">Please wait a moment</p>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import OverlayPanel from "primevue/overlaypanel";
import Dialog from "primevue/dialog";
import ProgressSpinner from "primevue/progressspinner";
import { ref, defineExpose } from "vue";
import { useAuthStore } from "../../stores/auth";
import { useRouter } from "vue-router";
import axios from "axios";

const authStore = useAuthStore();
const router = useRouter();
const op = ref(null);
const isLoggingOut = ref<boolean>(false);

/* EXPOSED METHODS */
const toggle = (event: Event) => {
  op.value?.toggle(event);
};

const show = (event: Event) => {
  op.value?.show(event);
};

const hide = () => {
  op.value?.hide();
};

/* ACTIONS */
const goProfile = () => {
  hide();
  router.push('/profile'); // Better than window.location.href for SPA
};

const goSettings = () => {
  hide();
  router.push('/settings');
};

const handleLogout = async () => {
  hide(); // Close popover immediately
  isLoggingOut.value = true;

  try {
    console.log('LOGGING OUT.... 1. Current token before logout:', authStore.token);
    console.log('2. Current localStorage:', {
      auth_token: localStorage.getItem('auth_token'),
      user: localStorage.getItem('user')
    });
    
    // Uncomment when backend is ready
    // await axios.post('/api/auth/logout');
    
    authStore.logout();
    delete axios.defaults.headers.common['Authorization'];
    
    // Add a small delay for better UX
    await new Promise(resolve => setTimeout(resolve, 500));
    
    router.replace('/login');
  } catch (error) {
    console.error('Logout error:', error);

    // Emergency cleanup
    localStorage.clear();
    sessionStorage.clear();
    delete axios.defaults.headers.common['Authorization'];
    
    router.replace('/login');
  } finally {
    isLoggingOut.value = false;
  }
};

defineExpose({
  toggle,
  show,
  hide,
});
</script>

<style scoped>
/* Optional: Add custom animations */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>