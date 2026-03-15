<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <!-- Total Due -->
    <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
      <div class="text-sm text-gray-600 font-semibold mb-1">Total Due</div>
      <div class="text-2xl font-bold text-blue-600">
        {{ formatCurrency(paymentStatus.total_due) }}
      </div>
      <div class="text-xs text-gray-500 mt-2">
        {{ paymentStatus.due_count || 0 }} invoices
      </div>
    </div>

    <!-- Total Paid -->
    <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
      <div class="text-sm text-gray-600 font-semibold mb-1">Total Paid</div>
      <div class="text-2xl font-bold text-green-600">
        {{ formatCurrency(paymentStatus.total_paid) }}
      </div>
      <div class="text-xs text-gray-500 mt-2">
        {{ paymentStatus.paid_count || 0 }} payments
      </div>
    </div>

    <!-- Overdue Count -->
    <div class="bg-red-50 rounded-lg p-4 border-l-4 border-red-500">
      <div class="text-sm text-gray-600 font-semibold mb-1">Overdue</div>
      <div class="text-2xl font-bold text-red-600">
        {{ paymentStatus.overdue_count || 0 }}
      </div>
      <div class="text-xs text-gray-500 mt-2">
        {{ formatCurrency(paymentStatus.overdue_amount || 0) }}
      </div>
    </div>

    <!-- Upcoming -->
    <div class="bg-amber-50 rounded-lg p-4 border-l-4 border-amber-500">
      <div class="text-sm text-gray-600 font-semibold mb-1">Upcoming</div>
      <div class="text-2xl font-bold text-amber-600">
        {{ formatCurrency(paymentStatus.upcoming_amount || 0) }}
      </div>
      <div class="text-xs text-gray-500 mt-2">
        Due in {{ paymentStatus.upcoming_days || 0 }} days
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PaymentStatusData {
  total_due: number
  total_paid: number
  due_count: number
  paid_count: number
  overdue_count: number
  overdue_amount: number
  upcoming_amount: number
  upcoming_days: number
}

defineProps<{
  paymentStatus: PaymentStatusData
}>()

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}
</script>
