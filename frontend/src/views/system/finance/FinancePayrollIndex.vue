<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Payroll</h1>
        <p class="mt-0.5 text-sm text-gray-500">Grouped by pay period for easier review and approval workflow.</p>
      </div>
      <Button icon="pi pi-refresh" label="Refresh" :loading="loading" @click="loadPayrolls" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Pay Periods</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ periodSummary.totalPeriods }}</p>
          </div>
        </template>
      </Card>
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Employees</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ periodSummary.totalEmployees }}</p>
          </div>
        </template>
      </Card>
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Needs Approval</p>
            <p class="mt-2 text-2xl font-semibold text-orange-600">{{ periodSummary.pendingApproval }}</p>
          </div>
        </template>
      </Card>
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Net Salary</p>
            <p class="mt-2 text-xl font-semibold text-green-700">P {{ formatMoney(periodSummary.totalNetSalary) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Payroll Periods</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable
            :value="payPeriods"
            :loading="loading"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <Column header="Pay Period" style="min-width: 220px">
              <template #body="{ data }">
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ data.payPeriodName }}</p>
                  <p class="text-xs text-gray-500">ID: {{ data.pay_period_id }}</p>
                </div>
              </template>
            </Column>

            <Column header="Employees" style="width: 120px">
              <template #body="{ data }">
                <span class="text-sm font-medium text-gray-800">{{ data.employeeCount }}</span>
              </template>
            </Column>

            <Column header="Total Net Salary" style="width: 180px">
              <template #body="{ data }">
                <span class="text-sm font-semibold text-green-700">P {{ formatMoney(data.totalNetSalary) }}</span>
              </template>
            </Column>

            <Column header="Status Mix" style="min-width: 240px">
              <template #body="{ data }">
                <div class="flex flex-wrap gap-1">
                  <Tag v-if="data.counts.submitted" :value="`Submitted ${data.counts.submitted}`" severity="warn" />
                  <Tag v-if="data.counts.processing" :value="`Processing ${data.counts.processing}`" severity="warning" />
                  <Tag v-if="data.counts.approved" :value="`Approved ${data.counts.approved}`" severity="success" />
                  <Tag v-if="data.counts.paid" :value="`Paid ${data.counts.paid}`" severity="info" />
                  <Tag v-if="data.counts.draft" :value="`Draft ${data.counts.draft}`" severity="secondary" />
                </div>
              </template>
            </Column>

            <Column header="Actions" style="width: 120px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <Button icon="pi pi-eye" text rounded severity="secondary" @click="openPeriodDetail(data)" />
                </div>
              </template>
            </Column>

            <template #empty>
              <div class="py-12 text-center text-gray-500">No payroll records found</div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import financeService from '../../../services/finance.service'

const router = useRouter()
const loading = ref(false)
const payrolls = ref<any[]>([])

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const payPeriods = computed(() => {
  const groups = new Map<number, any>()

  payrolls.value.forEach((row: any) => {
    const periodId = Number(row?.pay_period_id || 0)
    if (!periodId) return

    if (!groups.has(periodId)) {
      groups.set(periodId, {
        pay_period_id: periodId,
        payPeriodName: row?.pay_period?.name || row?.payPeriod?.name || `Pay Period ${periodId}`,
        employeeCount: 0,
        totalNetSalary: 0,
        counts: { draft: 0, submitted: 0, processing: 0, approved: 0, paid: 0 },
      })
    }

    const bucket = groups.get(periodId)
    bucket.employeeCount += 1
    bucket.totalNetSalary += Number(row?.net_salary || 0)

    const status = String(row?.status || '').toLowerCase()
    if (status in bucket.counts) {
      bucket.counts[status] += 1
    }
  })

  return Array.from(groups.values()).sort((a, b) => b.pay_period_id - a.pay_period_id)
})

const periodSummary = computed(() => {
  const pendingApproval = payPeriods.value.reduce(
    (sum, p) => sum + Number(p.counts.submitted || 0) + Number(p.counts.processing || 0) + Number(p.counts.draft || 0),
    0
  )

  return {
    totalPeriods: payPeriods.value.length,
    totalEmployees: payPeriods.value.reduce((sum, p) => sum + Number(p.employeeCount || 0), 0),
    pendingApproval,
    totalNetSalary: payPeriods.value.reduce((sum, p) => sum + Number(p.totalNetSalary || 0), 0),
  }
})

const loadPayrolls = async () => {
  loading.value = true
  try {
    const res = await financeService.getPayroll({ per_page: 500 })
    payrolls.value = res.data?.data || res.data || []
  } finally {
    loading.value = false
  }
}

const openPeriodDetail = (period: any) => {
  if (!period?.pay_period_id) return
  router.push({ name: 'finance.payroll.detail', params: { payPeriodId: String(period.pay_period_id) } })
}

onMounted(loadPayrolls)
</script>
