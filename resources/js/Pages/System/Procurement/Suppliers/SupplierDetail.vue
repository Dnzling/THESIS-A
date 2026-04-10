<template>
  <div class="max-w-6xl mx-auto space-y-4 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text rounded size="small" @click="router.push({ name: 'procurement.suppliers' })" />
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Supplier Relationship</h2>
          <p class="text-xs text-gray-500">Supplier profile and linked store relationship details</p>
        </div>
      </div>
      <Tag :value="(supplier?.status || 'active').toUpperCase()" :severity="statusSeverity(supplier?.status || 'active')" />
    </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
    </div>

    <template v-else-if="supplier">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card class="lg:col-span-2">
          <template #content>
            <div class="space-y-3">
              <div>
                <h3 class="text-base font-semibold text-slate-900">{{ supplier.supplier_name || '-' }}</h3>
                <p class="text-xs text-slate-500">{{ supplier.company_name || 'No company name' }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <InfoRow label="Supplier Code" :value="supplier.supplier_code" />
                <InfoRow label="Supplier Type" :value="humanize(supplier.supplier_type)" />
                <InfoRow label="Contact Person" :value="supplier.contact_person" />
                <InfoRow label="Payment Terms" :value="humanize(supplier.payment_terms)" />
                <InfoRow label="Email" :value="supplier.email" />
                <InfoRow label="Phone" :value="supplier.phone || supplier.mobile" />
                <InfoRow label="TIN" :value="supplier.tin" />
                <InfoRow label="Website" :value="supplier.website" />
              </div>

              <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-500">Address</p>
                <p class="text-sm text-slate-800">{{ supplierAddress }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #content>
            <div class="space-y-2 text-sm">
              <h4 class="text-sm font-semibold text-slate-900">Store Relationship</h4>
              <InfoRow label="Linked Store" :value="linkedStoreName" />
              <InfoRow label="Store ID" :value="supplier.store_id" />
              <InfoRow label="Linked Since" :value="formatDateTime(supplier.created_at)" />
              <InfoRow label="Active Contracts" :value="activeContractsCount" />
              <InfoRow label="Total Contracts" :value="contracts.length" />
              <InfoRow label="Products Linked" :value="productsCount" />
              <InfoRow label="Total Orders" :value="performance?.total_orders ?? supplier.total_orders ?? 0" />
              <InfoRow label="Rating" :value="`${performance?.rating ?? supplier.rating ?? 0}/5`" />
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card class="lg:col-span-2">
          <template #header>
            <div class="px-4 pt-4 pb-2 flex items-center justify-between">
              <div class="text-sm font-semibold text-slate-900">Recent Purchase Orders</div>
              <Tag :value="`${purchaseOrders.length}`" severity="secondary" />
            </div>
          </template>
          <template #content>
            <div v-if="purchaseOrders.length" class="space-y-2">
              <div
                v-for="po in purchaseOrders"
                :key="po.id"
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm flex items-center justify-between gap-3"
              >
                <div>
                  <div class="font-semibold text-slate-900">{{ po.po_number || `PO #${po.id}` }}</div>
                  <div class="text-xs text-slate-500">Order Date: {{ formatDate(po.order_date || po.created_at) }}</div>
                </div>
                <div class="text-right">
                  <Tag :value="humanize(po.status)" :severity="poStatusSeverity(po.status)" />
                  <div class="text-xs text-slate-600 mt-1">PHP {{ formatCurrency(po.total_amount) }}</div>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-500">No purchase orders yet.</div>
          </template>
        </Card>

        <Card>
          <template #header>
            <div class="px-4 pt-4 pb-2 text-sm font-semibold text-slate-900">Performance</div>
          </template>
          <template #content>
            <div class="space-y-2 text-sm">
              <InfoRow label="On-Time Delivery" :value="`${performance?.on_time_delivery_rate ?? 0}%`" />
              <InfoRow label="Total Spend" :value="`PHP ${formatCurrency(performance?.total_amount_purchased)}`" />
              <InfoRow label="Average Order" :value="`PHP ${formatCurrency(performance?.average_order_value)}`" />
              <InfoRow label="Late Deliveries" :value="performance?.late_deliveries ?? 0" />
              <InfoRow label="Current Balance" :value="`PHP ${formatCurrency(performance?.current_balance)}`" />
              <InfoRow label="Credit Available" :value="`PHP ${formatCurrency(performance?.credit_available)}`" />
            </div>
          </template>
        </Card>
      </div>

      <Card>
        <template #header>
          <div class="px-4 pt-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-900">Contracts</div>
            <Tag :value="`${contracts.length}`" severity="info" />
          </div>
        </template>
        <template #content>
          <div v-if="contracts.length" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="contract in contracts" :key="contract.id" class="rounded-lg border border-slate-200 p-3">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-slate-900">{{ contract.contract_title || '-' }}</p>
                  <p class="text-xs text-slate-500">{{ contract.contract_number || '-' }}</p>
                </div>
                <Tag :value="humanize(contract.status || 'draft')" :severity="contractStatusSeverity(contract.status || 'draft')" />
              </div>

              <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-700">
                <span>Type: {{ humanize(contract.contract_type) }}</span>
                <span>Discount: {{ contract.discount_percentage || 0 }}%</span>
                <span>Tax: {{ contract.tax_rate || 0 }}%</span>
                <span>Start: {{ formatDate(contract.start_date) }}</span>
                <span>End: {{ formatDate(contract.end_date) }}</span>
              </div>

              <div class="mt-2 flex justify-end">
                <Button
                  label="View Contract"
                  icon="pi pi-eye"
                  text
                  size="small"
                  @click="router.push({ name: 'procurement.supplier-contracts.detail', params: { id: contract.id } })"
                />
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-slate-500">No contracts created yet for this supplier.</div>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const supplierId = Number(route.params.id)

const loading = ref(false)
const supplier = ref<any>(null)
const contracts = ref<any[]>([])
const performance = ref<any>(null)
const purchaseOrders = ref<any[]>([])

const InfoRow = defineComponent({
  name: 'InfoRow',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
  },
  setup(props) {
    return () =>
      h('div', { class: 'rounded-md border border-slate-200 px-2 py-1.5 bg-white' }, [
        h('p', { class: 'text-[11px] uppercase tracking-wide text-slate-500' }, props.label),
        h('p', { class: 'text-sm font-medium text-slate-900 truncate' }, String(props.value ?? '-')),
      ])
  },
})

const supplierAddress = computed(() => {
  if (!supplier.value) return '-'
  return [supplier.value.address, supplier.value.city, supplier.value.province, supplier.value.country]
    .filter(Boolean)
    .join(', ') || '-'
})

const linkedStoreName = computed(() => {
  if (!supplier.value) return '-'
  return supplier.value.store?.name || supplier.value.store?.store_name || '-'
})

const activeContractsCount = computed(() => {
  if (performance.value?.active_contracts !== undefined && performance.value?.active_contracts !== null) {
    return performance.value.active_contracts
  }
  return contracts.value.filter((c: any) => c?.status === 'active').length
})

const productsCount = computed(() => {
  if (!supplier.value) return 0
  return Array.isArray(supplier.value.products) ? supplier.value.products.length : 0
})

const loadSupplierData = async () => {
  loading.value = true
  try {
    const supplierResponse = await procurementService.getSupplier(supplierId)
    const supplierPayload = supplierResponse?.data ?? supplierResponse ?? null
    supplier.value = supplierPayload
    contracts.value = Array.isArray(supplierPayload?.contracts) ? supplierPayload.contracts : []
    purchaseOrders.value = Array.isArray(supplierPayload?.purchase_orders) ? supplierPayload.purchase_orders : []

    try {
      const performanceResponse = await procurementService.getSupplierPerformance(supplierId)
      performance.value = performanceResponse?.data ?? performanceResponse ?? null
    } catch (error) {
      console.warn('Failed to load performance metrics', error)
      performance.value = null
    }
  } catch (error) {
    console.error('Failed to load supplier details', error)
    supplier.value = null
    contracts.value = []
    purchaseOrders.value = []
    performance.value = null
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'blacklisted') return 'danger'
  return 'secondary'
}

const contractStatusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'terminated') return 'danger'
  if (status === 'draft') return 'warning'
  if (status === 'expiring') return 'warning'
  return 'secondary'
}

const poStatusSeverity = (status: string) => {
  if (['delivered'].includes(status)) return 'success'
  if (['in_transit', 'supplier_accepted', 'sent_to_supplier'].includes(status)) return 'info'
  if (['pending_finance_approval', 'approved'].includes(status)) return 'warning'
  if (['rejected_finance', 'declined_supplier', 'cancelled'].includes(status)) return 'danger'
  return 'secondary'
}

const humanize = (value: string | null | undefined): string => {
  if (!value) return '-'
  return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatCurrency = (value: any): string => {
  if (!value) return '0.00'
  return Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString: string): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (dateString: string): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return 'N/A'
  return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  loadSupplierData()
})
</script>
