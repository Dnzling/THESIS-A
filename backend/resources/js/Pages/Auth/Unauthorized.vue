<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(249,115,22,0.20),_transparent_45%),linear-gradient(180deg,_#fff7ed_0%,_#ffe9d2_35%,_#ffffff_100%)]">
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-6 py-16">
      <div class="w-full max-w-4xl rounded-[32px] border border-orange-100 bg-white/90 shadow-[0_30px_80px_rgba(15,23,42,0.12)] backdrop-blur">
        <div class="grid gap-10 p-10 lg:grid-cols-[1.05fr_0.95fr]">
          <section class="flex flex-col justify-between gap-8 rounded-[24px] bg-gradient-to-br from-orange-500 via-orange-600 to-amber-600 p-10 text-white">
            <div class="space-y-3">
              <p class="text-xs uppercase tracking-[0.35em] text-orange-100">Furnisync Access</p>
              <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">Unauthorized</h1>
              <p class="text-sm text-orange-100 sm:text-base">
                You do not have permission to view this page. If you think this is a mistake, contact your administrator.
              </p>
            </div>
            <div class="rounded-2xl bg-white/15 p-6">
              <p class="text-sm text-orange-50">Signing you out in</p>
              <div class="mt-3 flex items-center gap-3 text-4xl font-semibold">
                <span>{{ countdown }}</span>
                <span class="text-base font-normal text-orange-100">seconds</span>
              </div>
            </div>
          </section>

          <section class="flex flex-col justify-center gap-6 px-2 sm:px-6">
            <div class="space-y-2">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
              <p class="text-2xl font-semibold text-slate-900">Access Denied</p>
              <p class="text-sm text-slate-500">Choose what to do next.</p>
            </div>

            <div class="grid gap-3">
              <Button label="Go to Login Now" severity="warn" @click="handleLogout" />
              <Button label="Go Back" severity="secondary" outlined @click="goBack" />
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
              If the previous page was protected, you will be redirected to login automatically.
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { router } from '@inertiajs/vue3'

const authStore = useAuthStore()
const countdown = ref(8)
let timer: number | null = null

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    handleLogout()
  }
}

const handleLogout = async () => {
  await authStore.logout({ redirect: false })
  router.visit('/login')
}

onMounted(() => {
  timer = window.setInterval(() => {
    countdown.value -= 1
    if (countdown.value <= 0) {
      if (timer) window.clearInterval(timer)
      handleLogout()
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) window.clearInterval(timer)
})
</script>
