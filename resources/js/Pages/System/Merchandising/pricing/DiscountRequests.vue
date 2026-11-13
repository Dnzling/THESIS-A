<template>
  <div class="space-y-5 text-sm">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Promotions &amp; Discounts</p>
        <h1 class="mt-1 text-xl font-semibold text-slate-900">Discount Requests</h1>
        <p class="mt-1 text-sm text-slate-500">Review promotion proposals raised from slow-moving inventory reports.</p>
      </div>
      <Button label="Promotions & Discounts" icon="pi pi-arrow-left" severity="secondary" outlined size="small" @click="router.push({ name: 'merchandising.pricing' })" />
    </header>

    <Message severity="info" :closable="false">
      These are proposals only. Accepting a request here does not change selling prices; create a promotion separately after review.
    </Message>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <IconField class="w-full sm:max-w-sm">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search product, SKU, or branch" size="small" fluid @input="searchLater" />
          </IconField>
          <span class="text-xs text-slate-500">{{ requests.length }} request{{ requests.length === 1 ? '' : 's' }}</span>
        </div>

        <div v-if="loading" class="space-y-2"><Skeleton v-for="item in 5" :key="item" height="2.5rem" /></div>
        <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
        <DataTable v-else :value="requests" size="small" rowHover responsiveLayout="scroll" class="text-xs">
          <template #empty><div class="py-12 text-center text-sm text-slate-500">No discount requests yet. Requests from Inventory Reports will appear here.</div></template>
          <Column header="Product" style="min-width: 210px">
            <template #body="{ data }">
              <p class="font-semibold text-slate-900">{{ data.product_name }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ data.variation_name || data.sku || 'Finished good' }}</p>
            </template>
          </Column>
          <Column field="branch_name" header="Branch" />
          <Column header="Proposed discount" style="min-width: 140px">
            <template #body="{ data }"><span class="font-semibold text-orange-700">{{ discountLabel(data) }}</span></template>
          </Column>
          <Column header="Sales / Stock" style="min-width: 120px">
            <template #body="{ data }">{{ data.units_sold }} sold / {{ data.available_stock }} available</template>
          </Column>
          <Column header="Requested by" field="requested_by" />
          <Column header="Submitted" style="min-width: 130px">
            <template #body="{ data }">{{ dateTime(data.created_at) }}</template>
          </Column>
          <Column header="Status">
            <template #body="{ data }"><Badge :value="label(data.status)" severity="warn" /></template>
          </Column>
          <Column header="Action" style="width: 90px">
            <template #body="{ data }"><Button label="View" severity="secondary" outlined rounded size="small" @click="openRequest(data)" /></template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Drawer v-model:visible="detailVisible" position="right" modal header="Discount Request Details" class="w-full sm:w-[34rem]">
      <div v-if="activeRequest" class="space-y-5">
        <section>
          <h2 class="text-lg font-semibold text-slate-900">{{ activeRequest.product_name }}</h2>
          <p class="mt-1 text-xs text-slate-500">{{ activeRequest.variation_name || activeRequest.sku || 'Finished good' }} · {{ activeRequest.branch_name }}</p>
        </section>
        <section class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
          <div><p class="text-xs text-slate-500">Proposed discount</p><p class="mt-1 font-semibold text-orange-700">{{ discountLabel(activeRequest) }}</p></div>
          <div><p class="text-xs text-slate-500">Report period</p><p class="mt-1 font-medium">{{ date(activeRequest.report_period_start) }} – {{ date(activeRequest.report_period_end) }}</p></div>
          <div><p class="text-xs text-slate-500">Units sold</p><p class="mt-1 font-medium">{{ activeRequest.units_sold }}</p></div>
          <div><p class="text-xs text-slate-500">Available stock</p><p class="mt-1 font-medium">{{ activeRequest.available_stock }}</p></div>
          <div><p class="text-xs text-slate-500">Days of cover</p><p class="mt-1 font-medium">{{ activeRequest.days_cover ?? 'No sales pace' }}</p></div>
          <div><p class="text-xs text-slate-500">Days without sale</p><p class="mt-1 font-medium">{{ activeRequest.days_without_sale ?? 'No recorded sale' }}</p></div>
          <div><p class="text-xs text-slate-500">Days listed</p><p class="mt-1 font-medium">{{ activeRequest.days_listed }}</p></div>
          <div><p class="text-xs text-slate-500">Stock value</p><p class="mt-1 font-medium">{{ money(activeRequest.stock_value) }}</p></div>
        </section>
        <section>
          <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reason for promotion</h3>
          <p class="mt-2 whitespace-pre-line leading-6 text-slate-700">{{ activeRequest.reason }}</p>
        </section>
        <p class="border-t border-slate-200 pt-4 text-xs leading-5 text-slate-500">This request is a recommendation, not an active discount. Create a promotion separately to publish an offer.</p>
      </div>
    </Drawer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Drawer from 'primevue/drawer'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'

const router = useRouter()
const toast = useToast()
const requests = ref<any[]>([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const detailVisible = ref(false)
const activeRequest = ref<any>(null)
let searchTimer: ReturnType<typeof setTimeout> | undefined

const loadRequests = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await merchandisingService.getDiscountRequests({ search: search.value.trim() || undefined })
    requests.value = response.data?.requests || []
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to load discount requests.'
    toast.add({ severity: 'error', summary: 'Requests unavailable', detail: error.value, life: 3500 })
  } finally {
    loading.value = false
  }
}
const searchLater = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(loadRequests, 300)
}
const openRequest = (request: any) => {
  activeRequest.value = request
  detailVisible.value = true
}
const label = (value: string) => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const discountLabel = (request: any) => request.discount_type === 'percentage'
  ? `${Number(request.discount_value)}% off`
  : `${money(request.discount_value)} off`
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const date = (value: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'
const dateTime = (value: string) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'

onMounted(loadRequests)
</script>
