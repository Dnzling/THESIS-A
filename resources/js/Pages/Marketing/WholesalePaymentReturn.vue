<template>
  <main class="flex min-h-screen items-center justify-center bg-orange-50 px-4 py-12 text-slate-900">
    <section class="w-full max-w-lg rounded-3xl border border-orange-100 bg-white p-8 text-center shadow-sm">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-orange-100 text-orange-600">
        <i :class="cancelled ? 'pi pi-times' : 'pi pi-check'" class="text-xl" />
      </div>
      <h1 class="mt-5 text-2xl font-semibold">{{ cancelled ? 'Checkout cancelled' : 'Payment submitted' }}</h1>
      <p class="mt-3 text-sm leading-6 text-slate-600">
        {{ cancelled
          ? 'No payment was confirmed. Contact the store if you need a new payment link.'
          : 'The store will confirm your payment with PayMongo before releasing your wholesale order.' }}
      </p>
    </section>
  </main>
</template>

<script setup lang="ts">
const cancelled = new URLSearchParams(window.location.search).get('result') === 'cancelled'
</script>
