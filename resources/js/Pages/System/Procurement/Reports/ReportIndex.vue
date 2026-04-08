<template>
  <div class="p-6 bg-gray-50 min-h-screen space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Procurement Reports</h1>
      <p class="text-gray-600 mt-1">Preview spend and supplier analytics</p>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Button label="Spend Analysis" icon="pi pi-chart-line" class="w-full" @click="loadSpend" />
          <Button label="Supplier Performance" icon="pi pi-users" severity="secondary" class="w-full" @click="loadSupplierPerformance" />
          <Button label="Cycle Time" icon="pi pi-clock" severity="info" class="w-full" @click="loadCycleTime" />
        </div>
      </template>
    </Card>

    <Card>
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-file text-blue-600"></i>
          <span>Report Preview</span>
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="i in 4" :key="i" height="52px" class="rounded-lg" />
        </div>
        <DataTable v-else :value="rows" class="p-datatable-sm" stripedRows>
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-file text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">Select a report type to preview</p>
            </div>
          </template>
          <Column field="label" header="Metric" />
          <Column field="value" header="Value" />
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import procurementService from '../../../../services/procurement.service'

const loading = ref(false)
const rows = ref<Array<{ label: string; value: string | number }>>([])

const normalize = (data: any) => {
  if (Array.isArray(data)) return data
  if (data && typeof data === 'object') {
    return Object.entries(data).map(([label, value]) => ({ label, value: String(value) }))
  }
  return []
}

const loadSpend = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSpendAnalysisReport()
    rows.value = normalize(response.data)
  } catch (error) {
    console.error('Failed to load spend report', error)
    rows.value = []
  } finally {
    loading.value = false
  }
}

const loadSupplierPerformance = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSupplierPerformanceReport()
    rows.value = normalize(response.data)
  } catch (error) {
    console.error('Failed to load supplier performance report', error)
    rows.value = []
  } finally {
    loading.value = false
  }
}

const loadCycleTime = async () => {
  loading.value = true
  try {
    const response = await procurementService.getProcurementCycleTimeReport()
    rows.value = normalize(response.data)
  } catch (error) {
    console.error('Failed to load cycle time report', error)
    rows.value = []
  } finally {
    loading.value = false
  }
}
</script>
