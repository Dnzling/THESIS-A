<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Sales Reports</h1>
        <p class="text-sm text-gray-500">Quick summaries for store performance.</p>
      </div>
      <Button severity="info" outlined icon="pi pi-download" label="Export" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <p class="text-xs uppercase text-gray-500">Today</p>
          <p class="text-2xl font-semibold">{{ formatMoney(summary.today_total) }}</p>
        </template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <p class="text-xs uppercase text-gray-500">This Week</p>
          <p class="text-2xl font-semibold">{{ formatMoney(summary.week_total) }}</p>
        </template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <p class="text-xs uppercase text-gray-500">This Month</p>
          <p class="text-2xl font-semibold">{{ formatMoney(summary.month_total) }}</p>
        </template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <p class="text-xs uppercase text-gray-500">Top Product</p>
          <p class="text-lg font-semibold">{{ summary.top_product || '—' }}</p>
        </template>
      </Card>
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #title>Sales Highlights</template>
      <template #content>
        <ul class="text-sm text-gray-600 space-y-2">
          <li>Total Orders: {{ summary.orders_count }}</li>
          <li>POS Total: {{ formatMoney(summary.pos_total) }}</li>
          <li>E-commerce Total: {{ formatMoney(summary.ecommerce_total) }}</li>
        </ul>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive } from 'vue'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'

const toast = useToast()
const summary = reactive({
  today_total: 0,
  week_total: 0,
  month_total: 0,
  orders_count: 0,
  pos_total: 0,
  ecommerce_total: 0,
  top_product: '',
})

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
}).format(Number(value || 0))

const loadSummary = async () => {
  try {
    const res = await salesService.getReportsSummary()
    const data = res?.data || {}
    summary.pos_total = Number(data.pos_total || 0)
    summary.ecommerce_total = Number(data.ecommerce_total || 0)
    summary.orders_count = Number(data.orders_count || 0)
    summary.month_total = Number(data.grand_total || 0)
    summary.week_total = Number(data.grand_total || 0)
    summary.today_total = Number(data.grand_total || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load reports', life: 3000 })
  }
}

onMounted(loadSummary)
</script>
