<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Payroll</h1>
      <p class="text-gray-500 mt-1">Review and approve payroll runs</p>
    </div>

    <Card>
      <template #content>
        <DataTable :value="payrolls" :loading="loading" stripedRows responsiveLayout="scroll">
          <Column header="Employee">
            <template #body="{ data }">
              <div>
                <p class="font-semibold">{{ data.employee?.fname }} {{ data.employee?.lname }}</p>
                <p class="text-xs text-gray-500">{{ data.employee?.employee_number }}</p>
              </div>
            </template>
          </Column>
          <Column header="Pay Period">
            <template #body="{ data }">
              {{ data.pay_period?.name || data.pay_period_id || '-' }}
            </template>
          </Column>
          <Column header="Net Salary" style="width: 160px">
            <template #body="{ data }">₱ {{ formatMoney(data.net_salary) }}</template>
          </Column>
          <Column header="Status" style="width: 140px">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" style="width: 160px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button v-if="data.status === 'submitted'" icon="pi pi-check" text rounded severity="success"
                  @click="approvePayroll(data.id)" />
                <Button v-if="data.status === 'approved'" icon="pi pi-wallet" text rounded severity="info"
                  @click="markPaid(data.id)" />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No payroll records found</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const payrolls = ref<any[]>([])

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const statusSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'paid') return 'info'
  if (status === 'submitted') return 'warning'
  return 'secondary'
}

const loadPayrolls = async () => {
  loading.value = true
  try {
    const res = await financeService.getPayroll()
    payrolls.value = res.data?.data || res.data || []
  } finally {
    loading.value = false
  }
}

const approvePayroll = async (id: number) => {
  await financeService.approvePayroll(id)
  loadPayrolls()
}

const markPaid = async (id: number) => {
  await financeService.markPayrollPaid(id)
  loadPayrolls()
}

onMounted(loadPayrolls)
</script>
