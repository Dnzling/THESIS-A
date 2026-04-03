<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Monthly Deductions</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatCurrency(deductions?.total_monthly || 0) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Estimated Yearly Deductions</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatCurrency(deductions?.total_yearly || 0) }}</p>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
        <h3 class="text-sm font-semibold text-slate-800">Active Deduction Items</h3>
      </div>

      <div v-if="!items.length" class="p-6 text-sm text-slate-500">
        No active deductions assigned to this employee.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-white text-slate-500 uppercase tracking-wide text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Deduction</th>
              <th class="px-4 py-3 text-left">Code</th>
              <th class="px-4 py-3 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(item, index) in items" :key="`${item.code}-${index}`" class="bg-white hover:bg-slate-50">
              <td class="px-4 py-3 text-slate-800 font-medium">{{ item.name || '-' }}</td>
              <td class="px-4 py-3 text-slate-600">{{ item.code || '-' }}</td>
              <td class="px-4 py-3 text-right text-slate-900 font-semibold">{{ formatCurrency(item.amount || 0) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  deductions?: {
    total_monthly?: number
    total_yearly?: number
    items?: Array<{
      name?: string
      code?: string
      amount?: number
      formatted?: string
    }>
  }
}>()

const items = computed(() => props.deductions?.items || [])

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(Number(amount || 0))
}
</script>
