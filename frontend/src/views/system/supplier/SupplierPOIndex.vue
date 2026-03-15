<template>
  <div class="supplier-po-index">
    <PageHeader title="Purchase Orders" icon="pi pi-shopping-cart" />

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">Search by PO Number</label>
            <InputText 
              v-model="searchQuery"
              placeholder="Search"
              class="w-full"
              @input="onSearch"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Filter by Status</label>
            <Select 
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Statuses"
              class="w-full"
              @change="onFilterChange"
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

    <!-- PO Table -->
    <Card>
      <template #content>
        <DataTable
          :value="pos"
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
            <div class="text-center text-gray-500 py-6">No purchase orders available.</div>
          </template>
          <Column header="PO">
            <template #body="{ data }">
              <div>
                <div class="font-semibold">#{{ data.po_number }}</div>
                <div class="text-xs text-gray-500">{{ formatDate(data.created_at) }}</div>
              </div>
            </template>
          </Column>
          <Column header="Items">
            <template #body="{ data }">
              {{ data.items?.length || 0 }}
            </template>
          </Column>
          <Column header="Total">
            <template #body="{ data }">
              ${{ parseFloat(data.total_amount || 0).toFixed(2) }}
            </template>
          </Column>
          <Column header="Expected Delivery">
            <template #body="{ data }">
              {{ formatDate(data.expected_delivery_date) }}
            </template>
          </Column>
          <Column header="Your Response">
            <template #body="{ data }">
              <Tag 
                v-if="poFeedback[data.id]"
                :value="poFeedback[data.id].response"
                :severity="poFeedback[data.id].response === 'accepted' ? 'success' : 'danger'"
                class="text-xs"
              />
              <span v-else class="text-gray-400 text-sm">Pending</span>
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
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import PageHeader from '../../../components/PageHeader.vue'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const pos = ref([])
const searchQuery = ref('')
const statusFilter = ref('')
const first = ref(0)
const rows = ref(10)
const totalRecords = ref(0)
const poFeedback = ref({})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Partially Approved', value: 'partially_approved' },
  { label: 'Fully Approved', value: 'fully_approved' },
  { label: 'Finance Approved', value: 'finance_approved' },
  { label: 'Ordered', value: 'ordered' },
  { label: 'Partially Received', value: 'partially_received' },
  { label: 'Received', value: 'received' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    pending_approval: 'warning',
    partially_approved: 'warning',
    fully_approved: 'success',
    finance_approved: 'success',
    ordered: 'info',
    received: 'success',
    partially_received: 'warning',
    rejected: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const loadPOs = async () => {
  try {
    loading.value = true
    const res = await supplierService.getSupplierPOs({
      page: Math.floor(first.value / rows.value) + 1,
      per_page: rows.value,
      search: searchQuery.value,
      status: statusFilter.value,
    })
    pos.value = res.data.data
    totalRecords.value = res.data.total

    // Load feedback for each PO
    pos.value.forEach(async (po: any) => {
      try {
        const feedbackRes = await supplierService.getMyPOFeedbacks({ purchase_order_id: po.id })
        if (feedbackRes.data.data && feedbackRes.data.data.length > 0) {
          poFeedback.value[po.id] = feedbackRes.data.data[0]
        }
      } catch (error) {
        console.error('Error loading PO feedback:', error)
      }
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load POs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onSearch = () => {
  first.value = 0
  loadPOs()
}

const onFilterChange = () => {
  first.value = 0
  loadPOs()
}

const onPageChange = (event: any) => {
  first.value = event.first
  loadPOs()
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  first.value = 0
  loadPOs()
}

const viewDetail = (id: number) => {
  router.push(`/supplier-portal/pos/${id}`)
}

onMounted(() => {
  loadPOs()
})
</script>

<style scoped lang="scss">
.supplier-po-index {
  padding: 20px;
}
</style>
