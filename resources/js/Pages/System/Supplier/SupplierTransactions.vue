<template>
  <div class="max-w-6xl mx-auto space-y-4 px-4 py-4 sm:px-6 pb-6">
    <PageHeader title="Transactions" subtitle="Supplier billing and payment activity" icon="pi pi-credit-card" />

    <Card class="rounded-xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-col items-center py-12 text-center">
          <i class="pi pi-receipt mb-3 text-3xl text-slate-400"></i>
          <h2 class="text-sm font-semibold text-slate-800">No transactions yet</h2>
          <p class="mt-1 max-w-sm text-sm text-slate-500">Supplier billing and payment activity will appear here when transactions are available.</p>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue'
import Card from 'primevue/card'
</script>
