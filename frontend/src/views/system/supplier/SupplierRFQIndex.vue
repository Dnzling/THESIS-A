<template>
  <div class="supplier-rfq-index">
    <PageHeader title="Request for Quotations" icon="pi pi-list" />

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">Search RFQ</label>
            <InputText 
              v-model="searchQuery"
              placeholder="Search by RFQ number"
              class="w-full"
              @input="onSearch"
            />
          </div>
          <div class="flex items-end">
            <Button 
              label="Reset"
              icon="pi pi-times"
              @click="resetFilters"
              class="p-button-secondary w-full"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- RFQ Table -->
    <Card>
      <template #content>
        <DataTable
          :value="rfqs"
          :loading="loading"
          :paginator="true"
          :rows="rows"
          :totalRecords="totalRecords"
          :lazy="true"
          dataKey="id"
          @page="onPageChange"
          @row-click="(event) => viewDetail(event.data.id)"
          class="w-full"
        >
          <template #empty>
            <div class="text-center text-gray-500 py-6">No RFQ requests available at this time.</div>
          </template>
          <Column header="RFQ">
            <template #body="{ data }">
              <div>
                <div class="font-semibold">#{{ data.rfq_number }}</div>
                <div class="text-xs text-gray-500">{{ formatDate(data.created_at) }}</div>
              </div>
            </template>
          </Column>
          <Column header="Items">
            <template #body="{ data }">
              {{ data.items?.length || 0 }}
            </template>
          </Column>
          <Column header="Deadline">
            <template #body="{ data }">
              {{ formatDate(data.deadline_date) }}
            </template>
          </Column>
          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Action" style="width: 140px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-arrow-right" text @click.stop="viewDetail(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import PageHeader from '../../../components/PageHeader.vue'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const rfqs = ref([])
const searchQuery = ref('')
const first = ref(0)
const rows = ref(10)
const totalRecords = ref(0)

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    sent: 'info',
    receiving: 'warning',
    awarded: 'success',
    completed: 'success',
    quotes_received: 'warning',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const loadRFQs = async () => {
  try {
    loading.value = true
    const res = await supplierService.getSupplierRFQs({
      page: Math.floor(first.value / rows.value) + 1,
      per_page: rows.value,
      search: searchQuery.value,
    })
    rfqs.value = res.data.data
    totalRecords.value = res.data.total
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load RFQs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  first.value = 0
  loadRFQs()
}

const onPageChange = (event: any) => {
  first.value = event.first
  loadRFQs()
}

const resetFilters = () => {
  searchQuery.value = ''
  first.value = 0
  loadRFQs()
}

const viewDetail = (id: number) => {
  router.push(`/supplier-portal/rfqs/${id}`)
}

onMounted(() => {
  loadRFQs()
})
</script>

<style scoped lang="scss">
.supplier-rfq-index {
  padding: 20px;
}
</style>
