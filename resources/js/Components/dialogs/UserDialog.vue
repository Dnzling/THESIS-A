<template>
  <OverlayPanel ref="op" :pt="{
    root: { class: 'shadow-lg border border-gray-200' },
    content: { class: 'p-0' }
  }">
    <div class="flex flex-col min-w-50">
      <div class="py-2">
        <button
          @click="goProfile"
          class="w-full px-4 py-2.5 flex items-center gap-3 hover:bg-gray-100 transition-colors duration-150 text-left group"
        >
          <i class="pi pi-user text-gray-600 group-hover:text-gray-900 text-sm"></i>
          <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Account Profile</span>
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

  <Dialog
    v-model:visible="confirmLogout"
    modal
    :closable="!isLoggingOut"
    :closeOnEscape="!isLoggingOut"
    :header="isLoggingOut ? '' : 'Log out of FurniSync?'"
    :style="{ width: 'min(92vw, 400px)' }"
  >
    <div v-if="isLoggingOut" class="flex flex-col items-center justify-center p-4">
      <ProgressSpinner style="width: 42px; height: 42px" strokeWidth="4" fill="transparent" animationDuration=".5s" />
      <p class="mt-4 text-base font-medium text-gray-700">Logging you out...</p>
      <p class="text-sm text-gray-500">Please wait a moment</p>
    </div>
    <p v-if="!isLoggingOut" class="text-sm text-slate-600">Are you sure you want to end your current session?</p>
    <template v-if="!isLoggingOut" #footer>
      <button class="rounded-lg px-4 py-2 text-sm text-slate-600" @click="confirmLogout = false">Cancel</button>
      <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white" @click="performLogout">Log Out</button>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import OverlayPanel from "primevue/overlaypanel";
import Dialog from "primevue/dialog";
import ProgressSpinner from "primevue/progressspinner";
import { computed, ref } from "vue";
import { useAuthStore } from "../../stores/auth";
import { router } from "@inertiajs/vue3";
import axios from "@/axios";

const authStore = useAuthStore();
const op = ref(null);
const isLoggingOut = ref<boolean>(false);
const confirmLogout = ref(false);

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
const userRole = computed(() => {
  const role = authStore.user?.role as any;
  return String(typeof role === 'string' ? role : role?.name || authStore.userRole || '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_');
});
const isCustomer = computed(() => userRole.value === 'customer');
const isSupplier = computed(() => userRole.value === 'supplier');

const goProfile = async () => {
  hide();
  if (isCustomer.value) {
    router.visit('/shop/profile');
    return;
  }
  if (isSupplier.value) {
    router.visit('/supplier-portal/profile');
    return;
  }

  router.visit('/employee-profile');
};

const handleLogout = async () => {
  hide();
  confirmLogout.value = true;
};

const performLogout = async () => {
  confirmLogout.value = false;
  hide(); // Close popover immediately
  isLoggingOut.value = true;

  try {
    // console.log('LOGGING OUT.... 1. Current token before logout:', authStore.token);
    // console.log('2. Current localStorage:', {
    //   auth_token: localStorage.getItem('auth_token'),
    //   user: localStorage.getItem('user')
    // });
    
    // Uncomment when backend is ready
    // await axios.post('/api/auth/logout');
    
    await authStore.logout({ redirect: false });
    delete axios.defaults.headers.common['Authorization'];
    
    // Add a small delay for better UX
    await new Promise(resolve => setTimeout(resolve, 500));
    
    window.location.href = '/login';
  } catch (error) {
    console.error('Logout error:', error);

    // Emergency cleanup
    localStorage.clear();
    sessionStorage.clear();
    delete axios.defaults.headers.common['Authorization'];
    
    window.location.href = '/login';
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
