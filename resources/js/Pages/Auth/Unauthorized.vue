<template>
  <main class="flex min-h-screen items-center justify-center bg-white px-5 py-12 text-center">
    <section class="flex w-full max-w-2xl flex-col items-center">
      <svg class="h-56 w-64" viewBox="0 0 260 220" fill="none" role="img" aria-label="Two purple padlocks">
        <ellipse cx="132" cy="202" rx="48" ry="6" fill="#A78BCA" />
        <g transform="rotate(-17 92 122)">
          <path d="M59 96V69C59 48 72 34 91 34C110 34 123 48 123 69V94" stroke="#18212F" stroke-width="8" stroke-linecap="round" />
          <path d="M65 94V69C65 52 75 40 91 40C107 40 117 52 117 69V93" stroke="#87949E" stroke-width="4" stroke-linecap="round" />
          <rect x="49" y="88" width="91" height="91" rx="12" fill="#F97316" stroke="#18212F" stroke-width="5" />
          <path d="M55 101C55 97 58 94 62 94H83C83 110 70 123 55 123V101Z" fill="#FDBA74" />
          <circle cx="95" cy="128" r="10" fill="#9A3412" />
          <path d="M91 134H99L102 150H88L91 134Z" fill="#9A3412" />
        </g>
        <g transform="rotate(16 169 104)">
          <path d="M141 77V49C141 29 154 16 173 16C192 16 205 29 205 49V75" stroke="#18212F" stroke-width="8" stroke-linecap="round" />
          <path d="M147 75V49C147 33 157 22 173 22C189 22 199 33 199 49V74" stroke="#87949E" stroke-width="4" stroke-linecap="round" />
          <rect x="131" y="70" width="91" height="91" rx="12" fill="#F97316" stroke="#18212F" stroke-width="5" />
          <path d="M137 83C137 79 140 76 144 76H165C165 92 152 105 137 105V83Z" fill="#FDBA74" />
          <circle cx="177" cy="110" r="10" fill="#9A3412" />
          <path d="M173 116H181L184 132H170L173 116Z" fill="#9A3412" />
        </g>
        <path d="M35 119L38 128L47 131L38 134L35 143L32 134L23 131L32 128L35 119Z" fill="#F97316" />
        <path d="M226 57L229 66L238 69L229 72L226 81L223 72L214 69L223 66L226 57Z" fill="#F97316" />
        <path d="M217 147L220 155L228 158L220 161L217 169L214 161L206 158L214 155L217 147Z" fill="#F97316" />
        <circle cx="54" cy="67" r="5" fill="#F97316" />
        <circle cx="112" cy="27" r="5" fill="#F97316" />
        <circle cx="217" cy="107" r="5" fill="#F97316" />
      </svg>

      <h1 class="mt-5 text-5xl font-bold tracking-tight text-slate-900 sm:text-6xl">Access Denied</h1>
      <p class="mt-4 text-base text-slate-500 sm:text-lg">You don’t have the permissions to access this page.</p>
      <div class="mt-7 flex flex-wrap justify-center gap-3">
        <Button label="Go to Dashboard" severity="warn" @click="goToDashboard" />
        <Button v-if="showLogout" label="Log Out" severity="secondary" outlined @click="handleLogout" />
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { onBeforeMount, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const showLogout = ref(false)
let logoutButtonTimer: number | undefined

const goToDashboard = async () => {
  const dashboard = authStore.navigation.find((item: any) => {
    const routeName = String(item.route_name || '').toLowerCase()
    const routePath = String(item.route_path || '').toLowerCase()
    return item.is_active && !item.meta?.is_group && (routeName.endsWith('.dashboard') || routePath.endsWith('/dashboard'))
  })

  if (dashboard?.route_name) {
    try {
      await router.push({ name: dashboard.route_name })
      return
    } catch {
      // Use the backend-provided dashboard path when this router has no matching named route.
    }
  }
  if (dashboard?.route_path) {
    await router.push(dashboard.route_path)
    return
  }

  const fallback = authStore.getFirstNavigationRoute()
  if (fallback && fallback !== '/unauthorized') await router.push(fallback)
}

const handleLogout = async () => {
  await authStore.logout({ redirect: false })
  window.location.assign('/login')
}

onBeforeMount(() => {
  showLogout.value = false
})

onMounted(() => {
  logoutButtonTimer = window.setTimeout(() => {
    showLogout.value = true
  }, 5000)
})

onUnmounted(() => {
  if (logoutButtonTimer) window.clearTimeout(logoutButtonTimer)
})
</script>
