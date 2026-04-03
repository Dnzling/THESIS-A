<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Supplier Performance</h2>
        <p class="text-sm text-gray-500 mt-1">Track and compare supplier metrics and ratings</p>
      </div>
      <Button
        icon="pi pi-refresh"
        rounded
        text
        @click="loadSupplierPerformance"
        :loading="loading"
      />
    </div>

    <!-- Performance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Total Suppliers</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ suppliers.length }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Avg On-Time Rate</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ avgOnTimeRate }}%</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Avg Quality Score</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ avgQualityScore }}/5.0</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Top Performer</p>
            <p class="text-lg font-bold text-orange-600 mt-2">{{ topPerformer.supplier_name || 'N/A' }}</p>
            <Rating v-model="topPerformer.rating" :readonly="true" :cancel="false" class="mt-1" />
          </div>
        </template>
      </Card>
    </div>

    <!-- Performance Distribution Chart -->
    <Card>
      <template #title>Supplier Performance Distribution</template>
      <template #content>
        <Chart
          type="scatter"
          :data="performanceChartData"
          :options="performanceChartOptions"
          class="w-full"
          style="height: 400px"
        />
      </template>
    </Card>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="flex gap-4 items-end">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Sort By</label>
            <Select
              v-model="sortBy"
              :options="[
                { label: 'Overall Rating', value: 'rating' },
                { label: 'On-Time Rate', value: 'on_time_rate' },
                { label: 'Quality Score', value: 'quality_score' },
                { label: 'Total Orders', value: 'total_orders' },
              ]"
              optionLabel="label"
              optionValue="value"
              placeholder="Sort by"
              class="w-full"
              @change="applySorting"
            />
          </div>
          <Button
            icon="pi pi-filter-slash"
            label="Reset"
            severity="secondary"
            @click="resetFilters"
          />
        </div>
      </template>
    </Card>

    <!-- Suppliers Table -->
    <Card>
      <template #title>Top 10 Suppliers</template>
      <template #content>
        <DataTable :value="displayedSuppliers" :loading="loading" class="p-datatable-sm">
          <Column field="supplier_name" header="Supplier" style="width: 20%">
            <template #body="{ data }">
              <div>
                <p class="font-semibold">{{ data.supplier_name }}</p>
                <p class="text-xs text-gray-500">{{ data.contact_person }}</p>
              </div>
            </template>
          </Column>

          <Column header="Rating" style="width: 12%">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Rating v-model="data.rating" :readonly="true" :cancel="false" />
                <span class="text-sm font-semibold">{{ data.rating }}/5</span>
              </div>
            </template>
          </Column>

          <Column header="On-Time %" style="width: 12%">
            <template #body="{ data }">
              <Badge
                :value="`${data.on_time_rate}%`"
                :severity="data.on_time_rate >= 90 ? 'success' : data.on_time_rate >= 75 ? 'warning' : 'danger'"
              />
            </template>
          </Column>

          <Column header="Quality" style="width: 12%">
            <template #body="{ data }">
              <ProgressBar :value="data.quality_score * 20" class="h-6"></ProgressBar>
              <span class="text-xs text-gray-600">{{ data.quality_score }}/5</span>
            </template>
          </Column>

          <Column header="Orders" style="width: 10%">
            <template #body="{ data }">
              {{ data.total_orders }} orders
            </template>
          </Column>

          <Column header="Total Spend" style="width: 12%">
            <template #body="{ data }">
              ₱ {{ formatNumber(data.total_spend) }}
            </template>
          </Column>

          <Column header="Status" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.status"
                :severity="data.status === 'active' ? 'success' : 'secondary'"
              />
            </template>
          </Column>

          <Column header="Details" style="width: 12%" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <Button
                icon="pi pi-chevron-right"
                text
                rounded
                @click="viewSupplierDetails(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Supplier Details Dialog -->
    <Dialog
      v-model:visible="showDetailsDialog"
      :header="`${selectedSupplier?.supplier_name} - Details`"
      :modal="true"
      :style="{ width: '50vw' }"
      :breakpoints="{ '960px': '75vw', '640px': '90vw' }"
    >
      <div v-if="selectedSupplier" class="space-y-4">
        <!-- Performance Metrics -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-3 bg-blue-50 rounded">
            <p class="text-xs text-gray-600">Overall Rating</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ selectedSupplier.rating }}/5</p>
            <Rating v-model="selectedSupplier.rating" :readonly="true" :cancel="false" class="mt-1" />
          </div>
          <div class="p-3 bg-green-50 rounded">
            <p class="text-xs text-gray-600">On-Time Rate</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ selectedSupplier.on_time_rate }}%</p>
          </div>
          <div class="p-3 bg-purple-50 rounded">
            <p class="text-xs text-gray-600">Quality Score</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ selectedSupplier.quality_score }}/5</p>
          </div>
          <div class="p-3 bg-orange-50 rounded">
            <p class="text-xs text-gray-600">Lead Time</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ selectedSupplier.avg_lead_time }} days</p>
          </div>
        </div>

        <!-- Contact Information -->
        <div class="border-t pt-4">
          <h4 class="font-semibold mb-2">Contact Information</h4>
          <div class="space-y-2 text-sm">
            <p><strong>Contact Person:</strong> {{ selectedSupplier.contact_person }}</p>
            <p><strong>Email:</strong> {{ selectedSupplier.email }}</p>
            <p><strong>Phone:</strong> {{ selectedSupplier.phone }}</p>
            <p><strong>Address:</strong> {{ selectedSupplier.address }}</p>
          </div>
        </div>

        <!-- Statistics -->
        <div class="border-t pt-4">
          <h4 class="font-semibold mb-2">Statistics</h4>
          <div class="space-y-2 text-sm">
            <p><strong>Total Orders:</strong> {{ selectedSupplier.total_orders }}</p>
            <p><strong>Total Spend:</strong> ₱ {{ formatNumber(selectedSupplier.total_spend) }}</p>
            <p><strong>Last Order:</strong> {{ formatDate(selectedSupplier.last_order_date) }}</p>
            <p><strong>Status:</strong> <Tag :value="selectedSupplier.status" :severity="selectedSupplier.status === 'active' ? 'success' : 'secondary'" /></p>
          </div>
        </div>
      </div>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const toast = useToast()

// State
const loading = ref(false)
const suppliers = ref<any[]>([])
const sortBy = ref('rating')
const showDetailsDialog = ref(false)
const selectedSupplier = ref<any>(null)

// Computed
const avgOnTimeRate = computed(() => {
  return suppliers.value.length > 0
    ? Math.round(suppliers.value.reduce((sum, s) => sum + s.on_time_rate, 0) / suppliers.value.length)
    : 0
})

const avgQualityScore = computed(() => {
  if (suppliers.value.length === 0) return 0
  const avg = suppliers.value.reduce((sum, s) => sum + s.quality_score, 0) / suppliers.value.length
  return avg.toFixed(1)
})

const topPerformer = computed(() => {
  return suppliers.value.length > 0 ? suppliers.value[0] : {}
})

const displayedSuppliers = computed(() => {
  const sorted = [...suppliers.value]
  sorted.sort((a, b) => {
    switch (sortBy.value) {
      case 'on_time_rate':
        return b.on_time_rate - a.on_time_rate
      case 'quality_score':
        return b.quality_score - a.quality_score
      case 'total_orders':
        return b.total_orders - a.total_orders
      default:
        return b.rating - a.rating
    }
  })
  return sorted.slice(0, 10)
})

// Chart Data
const performanceChartData = computed(() => {
  const data = suppliers.value.map(s => ({
    x: s.on_time_rate,
    y: s.quality_score,
    r: Math.sqrt(s.total_spend) / 100,
  }))

  return {
    datasets: [
      {
        label: 'Supplier Performance',
        data: data,
        backgroundColor: '#3b82f6',
        borderColor: '#1e40af',
        borderWidth: 1,
      },
    ],
  }
})

const performanceChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  scales: {
    x: {
      min: 0,
      max: 100,
      title: { display: true, text: 'On-Time Rate (%)' },
    },
    y: {
      min: 0,
      max: 5,
      title: { display: true, text: 'Quality Score' },
    },
  },
}

// Methods
async function loadSupplierPerformance() {
  loading.value = true
  try {
    const response = await procurementService.getSupplierPerformance()
    suppliers.value = response.data?.data || []
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load supplier performance data',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function applySorting() {
  // Sorting is handled via computed property
}

function resetFilters() {
  sortBy.value = 'rating'
}

function viewSupplierDetails(supplier: any) {
  selectedSupplier.value = supplier
  showDetailsDialog.value = true
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(Math.round(value))
}

function formatDate(date: string): string {
  return date ? new Date(date).toLocaleDateString('en-PH') : 'N/A'
}

onMounted(() => {
  loadSupplierPerformance()
})
</script>
