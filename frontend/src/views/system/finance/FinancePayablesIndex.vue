<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Accounts Payable</h1>
        <p class="text-gray-500 mt-1">Pending payments from procurement</p>
      </div>
    </div>

    <Card>
      <template #content>
        <TabView>
          <TabPanel header="Payables">
            <DataTable :value="payables" :loading="loading" stripedRows responsiveLayout="scroll">
              <Column field="reference" header="Invoice #" />
              <Column field="supplier" header="Supplier" />
              <Column field="amount" header="Amount" style="width: 160px">
                <template #body="{ data }">₱ {{ formatMoney(data.amount) }}</template>
              </Column>
              <Column field="due_date" header="Due Date" style="width: 160px">
                <template #body="{ data }">{{ formatDate(data.due_date) }}</template>
              </Column>
              <Column field="status" header="Status" style="width: 160px">
                <template #body="{ data }">
                  <Tag :value="formatStatus(data.status)" :severity="paymentSeverity(data.status)" />
                </template>
              </Column>
              <Column header="Actions" style="width: 160px">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button icon="pi pi-eye" text rounded @click="viewInvoice(data)" />
                    <Button
                      v-if="data.status === 'pending_approval'"
                      icon="pi pi-check"
                      text
                      rounded
                      severity="success"
                      @click="approveInvoice(data.id)"
                    />
                  </div>
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-8 text-gray-500">No payables found</div>
              </template>
            </DataTable>
          </TabPanel>

          <TabPanel header="Supplier Payments">
            <DataTable :value="supplierPayments" :loading="loadingPayments" stripedRows responsiveLayout="scroll">
              <Column field="payment_number" header="Payment #" />
              <Column field="supplier.supplier_name" header="Supplier">
                <template #body="{ data }">{{ data.supplier?.supplier_name || '-' }}</template>
              </Column>
              <Column field="payment_amount" header="Amount" style="width: 160px">
                <template #body="{ data }">₱ {{ formatMoney(data.payment_amount) }}</template>
              </Column>
              <Column field="payment_date" header="Date" style="width: 140px">
                <template #body="{ data }">{{ formatDate(data.payment_date) }}</template>
              </Column>
              <Column field="status" header="Status" style="width: 140px">
                <template #body="{ data }">
                  <Tag :value="data.status" :severity="paymentSeverity(data.status)" />
                </template>
              </Column>
              <Column header="Actions" style="width: 160px">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button
                      v-if="data.status === 'pending_approval'"
                      icon="pi pi-check"
                      text
                      rounded
                      severity="success"
                      @click="approvePayment(data.id)"
                    />
                    <Button
                      v-if="data.status === 'approved'"
                      icon="pi pi-wallet"
                      text
                      rounded
                      severity="info"
                      @click="processPayment(data.id)"
                    />
                  </div>
                </template>
              </Column>
              <template #empty>
                <div class="text-center py-8 text-gray-500">No supplier payments found</div>
              </template>
            </DataTable>
          </TabPanel>
        </TabView>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const payables = ref<any[]>([])
const supplierPayments = ref<any[]>([])
const loadingPayments = ref(false)
const router = useRouter()

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const loadPayables = async () => {
  loading.value = true
  try {
    const res = await financeService.getPayables()
    payables.value = res.data || []
  } finally {
    loading.value = false
  }
}

const loadSupplierPayments = async () => {
  loadingPayments.value = true
  try {
    const res = await financeService.getSupplierPayments({ per_page: 50 })
    supplierPayments.value = res.data?.data || res.data || []
  } finally {
    loadingPayments.value = false
  }
}

const paymentSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'completed') return 'info'
  if (status === 'pending_approval') return 'warning'
  return 'secondary'
}

const approvePayment = async (id: number) => {
  await financeService.approveSupplierPayment(id)
  loadSupplierPayments()
}

const processPayment = async (id: number) => {
  await financeService.processSupplierPayment(id)
  loadSupplierPayments()
}

const approveInvoice = async (id: number) => {
  await financeService.approveInvoice(id)
  loadPayables()
}

const viewInvoice = (row: any) => {
  if (!row?.id) return
  router.push({ name: 'finance.invoices.detail', params: { id: row.id } })
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  if (status === 'pending_approval') return 'Pending Approval'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

onMounted(() => {
  loadPayables()
  loadSupplierPayments()
})
</script>
