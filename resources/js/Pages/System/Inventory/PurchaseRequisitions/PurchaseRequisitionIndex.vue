<template>
  <div class="min-h-screen p-4">
    <div class="max-w-6xl mx-auto">
      <div class="mb-4 flex items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-gray-800">Purchase Requisitions</h1>
          <p class="text-xs text-gray-500 mt-0.5">Replenishment requests for your branch.</p>
        </div>
        <Button
          v-if="canManage"
          label="Create"
          icon="pi pi-plus"
          severity="success"
          size="small"
          @click="router.push({ name: 'inventory.requisites.create' })"
        />
      </div>

      <Card class="mb-3">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Search</label>
              <InputText v-model="filters.search" placeholder="Product, SKU, branch" />
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Status</label>
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All"
                showClear
              />
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Per Page</label>
              <Select v-model="perPage" :options="[10, 15, 20, 50]" />
            </div>
            <div class="flex justify-end gap-2">
              <Button
                type="button"
                label="Refresh"
                icon="pi pi-refresh"
                severity="secondary"
                outlined
                size="small"
                :loading="loading"
                @click="load()"
              />
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div v-if="loading" class="space-y-3">
            <div class="grid grid-cols-6 gap-3 text-xs text-gray-400">
              <Skeleton height="24px" class="col-span-1" />
              <Skeleton height="24px" class="col-span-1" />
              <Skeleton height="24px" class="col-span-1" />
              <Skeleton height="24px" class="col-span-1" />
              <Skeleton height="24px" class="col-span-1" />
              <Skeleton height="24px" class="col-span-1" />
            </div>
            <div v-for="i in 8" :key="i" class="grid grid-cols-6 gap-3">
              <Skeleton height="20px" class="col-span-1" />
              <Skeleton height="20px" class="col-span-1" />
              <Skeleton height="20px" class="col-span-1" />
              <Skeleton height="20px" class="col-span-1" />
              <Skeleton height="20px" class="col-span-1" />
              <Skeleton height="20px" class="col-span-1" />
            </div>
          </div>

          <DataTable
            v-else
            :value="rows"
            class="p-datatable-sm text-xs p-datatable-fluid"
            responsiveLayout="scroll"
            paginator
            :rows="perPage"
            :totalRecords="total"
            :first="(page - 1) * perPage"
            @page="onPageChange"
            :sortField="sortField"
            :sortOrder="sortOrder"
            @sort="onSort"
            @row-click="onRowClick"
            :rowClass="rowClass"
          >
            <Column field="created_at" header="Date" sortable style="width: 130px">
              <template #body="{ data }">
                <div class="text-xs">
                  <div>{{ formatDate(data.created_at) }}</div>
                  <div class="text-gray-500 text-xs">{{ formatTime(data.created_at) }}</div>
                </div>
              </template>
            </Column>

            <Column field="pr_number" header="PR No." style="width: 170px">
              <template #body="{ data }">
                <span class="font-semibold text-gray-900">{{ data.pr_number || `PR #${data.id}` }}</span>
              </template>
            </Column>

            <Column header="Status" style="width: 170px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>

            <Column header="Items" style="width: 90px">
              <template #body="{ data }">
                <span class="font-semibold text-gray-900">{{ (data.items || []).length }}</span>
              </template>
            </Column>

            <Column header="Reason" style="min-width: 260px">
              <template #body="{ data }">
                <span class="text-gray-700">{{ String(data.reason || '—') }}</span>
              </template>
            </Column>

            <Column header="Actions" style="width: 120px">
              <template #body="{ data }">
                <div class="flex gap-2 items-center">
                  <Button
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    rounded
                    @click="router.push({ name: 'inventory.requisites.detail', params: { id: data.id } })"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const rows = ref<any[]>([])
const total = ref(0)
const page = ref(1)
const perPage = ref(15)
const sortField = ref('created_at')
const sortOrder = ref(-1)

const canManage = computed(() => authStore.hasPermission('inventory.requisites.manage'))

const filters = reactive<{ search: string; status: string | null }>({
  search: '',
  status: null,
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending' },
  { label: 'Procurement Processing', value: 'procurement_processing' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]

const statusSeverity = (status: string) => {
  const s = String(status || '').toLowerCase()
  if (s === 'procurement_processing' || s === 'approved') return 'success'
  if (s === 'rejected' || s === 'cancelled') return 'danger'
  if (s === 'draft') return 'secondary'
  return 'warning'
}

const formatStatus = (status: any) => {
  const s = String(status || '').replace(/_/g, ' ').trim()
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Unknown'
}

const formatDate = (value: any) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

const rowClass = (data: any) => ({ 'cursor-pointer hover:bg-gray-50': true })

const onRowClick = (event: any) => {
  const id = event?.data?.id
  if (id) router.push({ name: 'inventory.requisites.detail', params: { id } })
}

const formatTime = (value: any) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const load = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getPurchaseRequisitions({
      page: page.value,
      per_page: perPage.value,
      sort_by: sortField.value,
      sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
      status: filters.status || undefined,
      search: filters.search || undefined,
    })

    if (response?.success) {
      const payload = response.data
      rows.value = payload?.data || []
      total.value = payload?.total || 0
    } else {
      rows.value = []
      total.value = 0
    }
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: e?.response?.data?.message || 'Failed to load requisitions',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (e: any) => {
  page.value = Math.floor(e.first / e.rows) + 1
  perPage.value = e.rows
  load()
}

const onSort = (e: any) => {
  sortField.value = e.sortField || 'created_at'
  sortOrder.value = e.sortOrder || -1
  load()
}

watch(
  () => [filters.search, filters.status, perPage.value],
  () => {
    page.value = 1
    load()
  }
)

onMounted(async () => {
  try {
    if (!authStore.user) await authStore.fetchCurrentUser()
  } catch {}
  await load()
})
</script>
