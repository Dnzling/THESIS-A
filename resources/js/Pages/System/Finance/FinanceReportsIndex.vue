<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Finance Reports</h1>
      <p class="text-gray-500 mt-1">High-level reporting (MVP)</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <Card>
        <template #content>
          <p class="text-sm font-semibold text-gray-700">Cashflow Summary</p>
          <p class="text-xs text-gray-500 mt-1">Coming soon</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-sm font-semibold text-gray-700">AP Aging</p>
          <p class="text-xs text-gray-500 mt-1">Coming soon</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-sm font-semibold text-gray-700">Expense Breakdown</p>
          <p class="text-xs text-gray-500 mt-1">Coming soon</p>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import Card from 'primevue/card'
</script>
