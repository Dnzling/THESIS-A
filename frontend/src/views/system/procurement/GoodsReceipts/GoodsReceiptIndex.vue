<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Goods Receipts</h1>
      <p class="text-gray-600 mt-1">Record and verify delivered goods</p>
    </div>

    <Card>
      <template #content>
        <DataTable :value="receipts" :loading="loading" class="p-datatable-sm" stripedRows>
          <Column field="grn_number" header="GRN No." />
          <Column field="purchase_order_id" header="PO ID" />
          <Column field="receipt_date" header="Receipt Date" />
          <Column field="receipt_status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.receipt_status" :severity="statusSeverity(data.receipt_status)" />
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <Button icon="pi pi-check" label="Verify" text size="small" @click="verify(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import procurementService from '../../../../services/procurement.service'

const loading = ref(false)
const receipts = ref<any[]>([])

const loadReceipts = async () => {
  loading.value = true
  try {
    const response = await procurementService.getGoodsReceipts()
    receipts.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load goods receipts', error)
    receipts.value = []
  } finally {
    loading.value = false
  }
}

const verify = async (id: number) => {
  try {
    await procurementService.verifyGoodsReceipt(id)
    await loadReceipts()
  } catch (error) {
    console.error('Failed to verify goods receipt', error)
  }
}

const statusSeverity = (status: string) => {
  if (status === 'full') return 'success'
  if (status === 'partial') return 'warning'
  if (['damaged', 'rejected'].includes(status)) return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadReceipts()
})
</script>
