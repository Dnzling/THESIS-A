<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Refunds</h1>
        <p class="text-sm text-gray-500">Review and manage refund requests.</p>
      </div>
      <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable :value="refunds" :loading="loading" stripedRows>
          <Column field="order_number" header="Order" />
          <Column field="customer_name" header="Customer" />
          <Column field="amount" header="Amount">
            <template #body="{ data }">PHP {{ Number(data.amount).toLocaleString('en-PH') }}</template>
          </Column>
          <Column field="reason" header="Reason" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button
                  text
                  severity="secondary"
                  icon="pi pi-eye"
                  label="Detail"
                  @click="openDetail(data)"
                />
                <Button
                  text
                  severity="success"
                  icon="pi pi-check"
                  label="Approve"
                  :disabled="!canManageRefunds || String(data.status).toLowerCase() !== 'pending'"
                  @click="setStatus(data, 'Approved')"
                />
                <Button
                  text
                  severity="danger"
                  icon="pi pi-times"
                  label="Reject"
                  :disabled="!canManageRefunds || String(data.status).toLowerCase() !== 'pending'"
                  @click="setStatus(data, 'Rejected')"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const authStore = useAuthStore()
const canManageRefunds = authStore.hasPermission('sales.refunds.manage')
const router = useRouter()

const toast = useToast()
const loading = ref(false)
const refunds = ref<any[]>([])

const loadRefunds = async () => {
  loading.value = true
  try {
    const res = await salesService.getRefunds()
    refunds.value = res?.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load refunds', life: 3000 })
  } finally {
    loading.value = false
  }
}

const setStatus = async (refund: any, status: string) => {
  try {
    await salesService.updateRefundStatus(refund.id, { status: status.toLowerCase() as 'approved' | 'rejected' })
    toast.add({ severity: 'success', summary: 'Updated', detail: `Refund ${status.toLowerCase()}.`, life: 2000 })
    await loadRefunds()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update refund', life: 3000 })
  }
}

const openDetail = (refund: any) => {
  router.push({ name: 'sales.refunds.detail', params: { id: refund.id } })
}

const statusSeverity = (status: string) => {
  if (status === 'approved' || status === 'Approved') return 'success'
  if (status === 'rejected' || status === 'Rejected') return 'danger'
  return 'warning'
}

onMounted(loadRefunds)
</script>
