<template>
  <div
    class="min-h-screen bg-[radial-gradient(circle_at_top,rgba(249,115,22,0.28),transparent_38%),linear-gradient(180deg,#fff2e5_0%,#ffd9b3_40%,#ffffff_100%)]">
    <Toast />
    <ConfirmDialog />
  
    <header class="sticky top-0 z-20 border-b border-blue-100 bg-white/90 backdrop-blur">
      <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-3">
        <button type="button"
          class="inline-flex items-center gap-2 text-xl font-semibold text-orange-500 transition hover:text-orange-800"
          @click="router.push({ name: 'job-portal.index' })">
          <span class="portal-brand">FURNISYNC JOBS</span>
        </button>
  
        <!-- Authenticated User View -->
        <div v-if="portalAuth.isAuthenticated" class="flex flex-wrap items-center gap-2">
          <Tag v-if="hiredApplication" severity="success"
            :value="`Currently hired: ${hiredApplication.jobPosting?.title || 'Employee'}`" />
  
          <Button severity="contrast" rounded text class="h-10 w-10" @click="toggleProfileMenu">
            <template #icon>
              <Avatar shape="circle" class="bg-blue-600 text-white" :label="userInitials" />
            </template>
          </Button>
          <Menu ref="profileMenu" :model="profileItems" popup />
        </div>
  
        <!-- Guest User View - Hide on auth pages -->
        <div v-else-if="!isAuthPage" class="flex flex-wrap items-center gap-2">
          <Button label="Applicant Login" severity="warn" outlined 
            @click="router.push({ name: 'job-portal.login' })" />
          <Button label="Create Account" severity="warn" 
            @click="router.push({ name: 'job-portal.register' })" />
        </div>
      </div>
    </header>
  
    <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import hrService from '../../../../services/hr.services'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

const router = useRouter()
const route = useRoute()
const confirm = useConfirm()
const toast = useToast()
const portalAuth = useJobPortalAuthStore()

// State
const loading = ref(false)
const profileMenu = ref()
const applications = ref<any[]>([])

// Computed
const isAuthPage = computed(() => 
  ['job-portal.login', 'job-portal.register'].includes(route.name as string)
)

const hiredApplication = computed(() =>
  applications.value.find((item) => String(item.status || '').toLowerCase() === 'hired')
)

const userInitials = computed(() => {
  const fname = portalAuth.user?.fname?.[0] || ''
  const lname = portalAuth.user?.lname?.[0] || ''
  return `${fname}${lname}`.toUpperCase() || 'AP'
})

const profileItems = computed(() => [
  { 
    label: portalAuth.user 
      ? `${portalAuth.user.fname} ${portalAuth.user.lname}` 
      : 'Applicant', 
    disabled: true 
  },
  { 
    label: 'My Applications', 
    icon: 'pi pi-file', 
    command: () => router.push({ name: 'job-portal.dashboard' }) 
  },
  { 
    label: 'My Profile', 
    icon: 'pi pi-user', 
    command: () => router.push({ name: 'job-portal.profile' }) 
  },
  { separator: true },
  {
    label: 'Logout',
    icon: 'pi pi-sign-out',
    command: async () => {
      await portalAuth.logout()
      toast.add({ 
        severity: 'success', 
        summary: 'Logged out', 
        detail: 'You have been signed out.', 
        life: 2200 
      })
      router.push({ name: 'job-portal.login' })
    },
  },
])

// Methods
const openJobPosts = () => {
  if (!hiredApplication.value) {
    router.push({ name: 'job-portal.index' })
    return
  }

  confirm.require({
    header: 'You are already hired',
    message: 'You currently have an active hired record. Continue browsing job posts?',
    icon: 'pi pi-warn-circle',
    rejectProps: { label: 'Stay Here', severity: 'secondary', outlined: true },
    acceptProps: { label: 'Continue', severity: 'warn' },
    accept: () => router.push({ name: 'job-portal.index' }),
  })
}

const toggleProfileMenu = (event: Event) => {
  profileMenu.value?.toggle(event)
}

const loadApplications = async () => {
  if (!portalAuth.isAuthenticated) return

  // The dashboard page already fetches the same applications list for its own content.
  // Skip the layout-level fetch there to avoid duplicate requests to the same endpoint.
  if (route.name === 'job-portal.dashboard') {
    applications.value = []
    return
  }
  
  loading.value = true
  try {
    const response = await hrService.getPortalApplications()
    applications.value = response?.data?.data || response?.data || []
  } catch (error) {
    console.error('Failed to load applications:', error)
    applications.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load applications',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(loadApplications)
watch(() => route.name, () => {
  loadApplications()
})
</script>

<style scoped>
@font-face {
  font-family: 'Barabara';
  src: url('/fonts/BARABARA-final.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

.portal-brand {
  font-family: 'Barabara', sans-serif;

}
</style>
