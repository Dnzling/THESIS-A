<template>
  <div class="supplier-rfq-index">
    <PageHeader title="Request for Quotations" />

    <!-- Filters -->
    <Card class="mb-6 ios-card">
      <template #content>
        <div class="filter-container">
          <div class="search-field">
            <label class="ios-label">Search RFQ</label>
            <InputText 
              v-model="searchQuery"
              placeholder="Search by RFQ number"
              class="ios-input w-full"
              @input="onSearch"
            />
          </div>
          <div class="reset-button">
            <Button 
              label="Reset"
              icon="pi pi-times"
              @click="resetFilters"
              class="ios-button-secondary"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- RFQ Table -->
    <Card class="ios-card">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="i in 6" :key="i" height="72px" class="ios-skeleton" />
        </div>
        <DataTable
          v-else
          :value="rfqs"
          :paginator="true"
          :rows="rows"
          :totalRecords="totalRecords"
          :lazy="true"
          dataKey="id"
          @page="onPageChange"
          @row-click="(event) => viewDetail(event.data.id)"
          class="ios-table"
          :pt="{
            root: { class: 'ios-table-root' },
            header: { class: 'ios-table-header' },
            paginator: { class: 'ios-paginator' }
          }"
        >
          <template #empty>
            <div class="ios-empty-state">
              <p>No RFQ requests available at this time.</p>
            </div>
          </template>
          <Column field="rfq_number" header="RFQ">
            <template #body="{ data }">
              <div class="rfq-cell">
                <div class="rfq-number">#{{ data.rfq_number }}</div>
                <div class="rfq-date">{{ formatDate(data.created_at) }}</div>
              </div>
            </template>
          </Column>
          <Column field="items" header="Items">
            <template #body="{ data }">
              <div class="items-count">{{ data.items?.length || 0 }}</div>
            </template>
          </Column>
          <Column field="deadline_date" header="Deadline">
            <template #body="{ data }">
              <div class="deadline-date">{{ formatDate(data.deadline_date) }}</div>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="ios-tag" />
            </template>
          </Column>
          <Column header="Action" style="width: 100px">
            <template #body="{ data }">
              <Button 
                label="View" 
                icon="pi pi-arrow-right" 
                text 
                @click.stop="viewDetail(data.id)"
                class="ios-button-text"
              />
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
import Skeleton from 'primevue/skeleton'
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
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
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
  padding: 24px;
  background: #f8f9fc;
  min-height: 100vh;

  @media (max-width: 768px) {
    padding: 16px;
  }
}

.ios-card {
  background: white;
  border-radius: 20px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  transition: all 0.2s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }
}

.filter-container {
  display: flex;
  gap: 16px;
  align-items: flex-end;
  
  @media (max-width: 768px) {
    flex-direction: column;
    gap: 20px;
  }
}

.search-field {
  flex: 1;
}

.ios-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #1c1c1e;
  margin-bottom: 8px;
  letter-spacing: -0.2px;
}

.ios-input {
  width: 100%;
  background: #f9f9fb;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 15px;
  color: #1c1c1e;
  transition: all 0.2s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;

  &:focus {
    outline: none;
    border-color: #007aff;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
  }

  &::placeholder {
    color: #8e8e93;
    font-weight: 400;
  }
}

.ios-button-secondary {
  background: #f9f9fb !important;
  border: 1px solid #e5e5ea !important;
  color: #007aff !important;
  padding: 12px 20px !important;
  border-radius: 12px !important;
  font-weight: 500 !important;
  font-size: 15px !important;
  transition: all 0.2s ease !important;
  min-width: 100px;

  &:hover {
    background: #f2f2f6 !important;
    border-color: #d9d9df !important;
  }

  &:active {
    transform: scale(0.98);
  }
}

.ios-button-text {
  color: #007aff !important;
  font-weight: 500 !important;
  font-size: 15px !important;
  padding: 8px 12px !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;

  &:hover {
    background: rgba(0, 122, 255, 0.08) !important;
  }

  &:active {
    transform: scale(0.96);
  }
}

.ios-table {
  :deep(.ios-table-root) {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }

  :deep(.p-datatable-thead) {
    tr {
      background: #f9f9fb;
    }
    
    th {
      background: transparent;
      color: #6c6c70;
      font-weight: 500;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 16px 12px;
      border-bottom: 1px solid #e5e5ea;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
  }

  :deep(.p-datatable-tbody) {
    tr {
      cursor: pointer;
      transition: background 0.2s ease;
      
      &:hover {
        background: #f9f9fb;
      }
      
      td {
        padding: 16px 12px;
        border-bottom: 1px solid #f0f0f2;
        font-size: 15px;
        color: #1c1c1e;
        vertical-align: middle;
      }
    }
  }
}

.rfq-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rfq-number {
  font-weight: 600;
  color: #1c1c1e;
  font-size: 15px;
}

.rfq-date {
  font-size: 13px;
  color: #8e8e93;
}

.items-count {
  font-weight: 500;
  color: #1c1c1e;
}

.deadline-date {
  color: #1c1c1e;
}

.ios-tag {
  border-radius: 12px !important;
  padding: 4px 12px !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  
  :deep(.p-tag-value) {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }
  
  &.p-tag-info {
    background: #e8f0fe !important;
    color: #0066cc !important;
  }
  
  &.p-tag-success {
    background: #e8f5e9 !important;
    color: #2e7d32 !important;
  }
  
  &.p-tag-warning {
    background: #fff4e5 !important;
    color: #ed6c02 !important;
  }
  
  &.p-tag-danger {
    background: #feeceb !important;
    color: #d32f2f !important;
  }
  
  &.p-tag-secondary {
    background: #e9ecef !important;
    color: #6c757d !important;
  }
}

.ios-paginator {
  :deep(.p-paginator) {
    background: transparent;
    border: none;
    padding: 20px 16px;
    justify-content: center;
    
    .p-paginator-page, .p-paginator-next, .p-paginator-last, .p-paginator-first, .p-paginator-prev {
      min-width: 36px;
      height: 36px;
      border-radius: 8px;
      color: #007aff;
      font-weight: 500;
      transition: all 0.2s ease;
      
      &:hover {
        background: #f2f2f6;
      }
      
      &.p-highlight {
        background: #007aff;
        color: white;
      }
    }
  }
}

.ios-skeleton {
  border-radius: 12px;
  background: linear-gradient(90deg, #f0f0f2 25%, #f9f9fb 50%, #f0f0f2 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.ios-empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #8e8e93;
  font-size: 15px;
  
  p {
    margin: 0;
  }
}

.mb-6 {
  margin-bottom: 24px;
}

.space-y-3 > * + * {
  margin-top: 12px;
}

.w-full {
  width: 100%;
}
</style>