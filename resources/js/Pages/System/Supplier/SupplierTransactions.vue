<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-6">
    <PageHeader title="Transactions" subtitle="Supplier billing and payment activity" icon="pi pi-credit-card" />

    <Card>
      <template #content>
        <div class="text-center py-12 text-gray-500">
          <i class="pi pi-receipt text-4xl mb-3 block"></i>
          <p class="text-sm">No transactions available yet.</p>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue'
import Card from 'primevue/card'
</script>
