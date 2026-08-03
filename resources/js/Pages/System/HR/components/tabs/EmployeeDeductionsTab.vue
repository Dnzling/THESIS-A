<template>
  <div class="space-y-6">


    <div class="rounded-2xl border border-slate-200 overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-800">Deduction Types Recorded From Payslips</h3>
      </div>

      <div v-if="!items.length" class="p-6 text-sm text-slate-500">
        No paid payslip deduction records available yet.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-white text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left">Deduction Type</th>
      
              <th class="px-4 py-3 text-right">Amount Recorded</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="item in items" :key="item.key" class="bg-white hover:bg-slate-50">
              <td class="px-4 py-3 font-medium text-slate-800">{{ item.name }}</td>


              <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ formatCurrency(item.amount) }}</td>
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
  contributions?: any
}>()

const items = computed(() => {
  const payroll = props.contributions || {}
  const recentPayslips = Array.isArray(payroll?.recent_payslips) ? payroll.recent_payslips : []
  const grouped = new Map<string, { key: string; name: string; code: string; amount: number; payslip_count: number }>()

  recentPayslips.forEach((payslip: any) => {
    const deductionItems = Array.isArray(payslip?.items) ? payslip.items : []

    deductionItems.forEach((item: any) => {
      const typeName = String(item?.name || 'Deduction').trim()
      const typeCode = String(item?.code || item?.type || '').trim()
      const amount = Number(item?.amount || 0)
      const key = `${typeName}::${typeCode}`
      const existing = grouped.get(key)

      if (existing) {
        existing.amount += amount
        existing.payslip_count += 1
        return
      }

      grouped.set(key, {
        key,
        name: typeName,
        code: typeCode,
        amount,
        payslip_count: 1,
      })
    })
  })

  return Array.from(grouped.values()).sort((a, b) => a.name.localeCompare(b.name))
})

const summary = computed(() => {
  const payroll = props.contributions || {}
  const recentPayslips = Array.isArray(payroll?.recent_payslips) ? payroll.recent_payslips : []
  const total = items.value.reduce((sum: number, item: any) => sum + Number(item.amount || 0), 0)

  return {
    total_contributions: Number(total || 0),
    payslip_count: recentPayslips.length,
    type_count: items.value.length,
  }
})

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(Number(amount || 0))
}
</script>
