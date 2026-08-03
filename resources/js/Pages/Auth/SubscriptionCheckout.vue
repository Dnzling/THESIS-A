<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 text-slate-700">
    Redirecting to subscription plans...
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

onMounted(() => {
  router.visit('/subscription-plans')
})
</script>
