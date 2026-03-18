<template>
  <div class="supplier-po-index">
    <div class="flex items-center justify-between">
     <div class="text-2xl font-semibold">Purchase Orders</div>
      <Button
        v-if="lastDeliveryPoId"
        label="Last Delivery Form"
        icon="pi pi-map-marker"
        severity="secondary"
        class="p-button-sm"
        @click="goToDeliveryForm(lastDeliveryPoId)"
      />
    </div>

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
    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="i in 6" :key="i" height="64px" class="rounded-xl" />
        </div>
        <DataTable
          v-else
          :value="pos"
          :paginator="true"
          :first="first"
          :rows="rows"
          :totalRecords="totalRecords"
          :lazy="true"
          dataKey="id"
          @page="onPageChange"
          @row-click="(event) => viewDetail(event.data.id, event.data.status)"
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
              ₱ {{ parseFloat(data.total_amount || 0).toFixed(2) }}
            </template>
          </Column>
          <Column header="Expected Delivery">
            <template #body="{ data }">
              {{ formatDate(data.expected_delivery_date) }}
            </template>
          </Column>
          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Action" style="width: 180px">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button
                  :label="['supplier_accepted', 'in_transit', 'delivered', 'declined_supplier'].includes(data.status) ? 'View' : 'Review'"
                  icon="pi pi-arrow-right"
                  text
                  @click.stop="viewDetail(data.id, data.status)"
                />
                <Button
                  v-if="data.status === 'supplier_accepted'"
                  label="Delivery"
                  icon="pi pi-truck"
                  text
                  severity="secondary"
                  @click.stop="goToDeliveryForm(data.id)"
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
import Skeleton from 'primevue/skeleton'
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
const lastDeliveryPoId = ref<number | null>(null)

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Finance Approval', value: 'pending_finance_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Supplier Accepted', value: 'supplier_accepted' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Rejected by Finance', value: 'rejected_finance' },
  { label: 'Declined by Supplier', value: 'declined_supplier' },
  { label: 'Cancelled', value: 'cancelled' },
]

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    pending_finance_approval: 'warning',
    approved: 'success',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warning',
    delivered: 'success',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  const map: Record<string, string> = {
    draft: 'Draft',
    pending_finance_approval: 'Pending Finance Approval',
    approved: 'Approved',
    sent_to_supplier: 'Sent to Supplier',
    supplier_accepted: 'Supplier Accepted',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    rejected_finance: 'Rejected by Finance',
    declined_supplier: 'Declined by Supplier',
    cancelled: 'Cancelled',
  }
  return map[status] || status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString()
}

const loadPOs = async () => {
  try {
    loading.value = true
    const params: Record<string, any> = {
      page: Math.floor(first.value / rows.value) + 1,
      per_page: rows.value,
    }
    if (searchQuery.value?.trim()) {
      params.search = searchQuery.value.trim()
    }
    if (statusFilter.value) {
      params.status = statusFilter.value
    }
    const res = await supplierService.getSupplierPOs(params)
    const payload = res.data
  
    console.log('POs loaded:', payload)
    const pageData = payload?.data
    pos.value = Array.isArray(pageData?.data) ? [...pageData.data] : (Array.isArray(pageData) ? [...pageData] : [])
    totalRecords.value = Number(pageData?.total ?? (Array.isArray(pos.value) ? pos.value.length : 0))

    // Load feedback for each PO
    pos.value.forEach(async (po: any) => {
      try {
        const feedbackRes = await supplierService.getMyPOFeedbacks({ purchase_order_id: po.id })
        const feedbackPayload = feedbackRes.data || feedbackRes
        const feedbackList = feedbackPayload?.data?.data || feedbackPayload?.data || []
        if (feedbackList.length > 0) {
          poFeedback.value[po.id] = feedbackList[0]
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

const viewDetail = (id: number, status?: string) => {
  if (status === 'in_transit' || status === 'delivered' || status === 'declined_supplier' || status === 'declined_by_supplier') {
    router.push(`/supplier-portal/pos/${id}/view`)
    return
  }
  router.push(`/supplier-portal/pos/${id}`)
}

const goToDeliveryForm = (id: number) => {
  localStorage.setItem('last_delivery_form_po_id', String(id))
  lastDeliveryPoId.value = id
  router.push(`/supplier-portal/pos/${id}/delivery-template`)
}

onMounted(() => {
  const stored = localStorage.getItem('last_delivery_form_po_id')
  lastDeliveryPoId.value = stored ? Number(stored) : null
  loadPOs()
})
</script>

<style scoped lang="scss">
.supplier-po-index {
  padding: 20px;
}
</style>
