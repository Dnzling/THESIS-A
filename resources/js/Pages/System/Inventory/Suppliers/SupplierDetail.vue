<template>
  <div class="space-y-6 px-4 sm:px-6 lg:px-8">
    <ConfirmDialog />
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" severity="secondary" text rounded
        @click="router.push({ name: 'inventory.suppliers' })" />
      <div>
        <h1 class="text-lg font-bold text-gray-800">Supplier Details</h1>
        <p class="mt-1 text-xs text-gray-500">Supplier information and connected products.</p>
      </div>
    </div>

    <div v-if="loadingSupplier" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <Skeleton height="420px" />
      <Skeleton height="420px" class="lg:col-span-2" />
    </div>

    <div v-else-if="supplier" class="space-y-6">
      <Card>
        <template #content>
          <div class="space-y-5">
            <div class="flex flex-col items-center border-b border-gray-100 pb-5 text-center">
              <img v-if="supplier.logo_url" :src="supplier.logo_url" :alt="`${supplier.supplier_name} logo`"
                class="h-28 w-28 rounded-2xl border border-gray-200 object-cover" />
              <div v-else class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                <i class="pi pi-building text-4xl" />
              </div>
              <h2 class="mt-3 text-lg font-bold text-gray-900">{{ supplier.supplier_name }}</h2>
              <Tag class="mt-2" :value="humanize(supplier.supplier_type)" severity="info" />
              <Button label="Edit" icon="pi pi-pencil" size="small" severity="warn" outlined
                class="mt-4" @click="editSupplier" />
            </div>

            <InfoRow icon="pi pi-user" label="Contact Person" :value="supplier.contact_person" />
            <InfoRow icon="pi pi-phone" label="Phone Number" :value="supplier.phone" />
            <InfoRow icon="pi pi-envelope" label="Email" :value="supplier.email" />
            <InfoRow icon="pi pi-map-marker" label="Address" :value="fullAddress" />
            <InfoRow icon="pi pi-star" label="Rating" :value="formatRating(supplier.rating)" />
            <InfoRow icon="pi pi-circle-fill" label="Status" :value="humanize(supplier.status)" />
          </div>
        </template>
      </Card>

    </div>

    <Card v-if="!loadingSupplier && supplier">
      <template #title>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-gray-800">Supplier Contracts</h2>
            <p class="mt-1 text-xs font-normal text-gray-500">Agreements and documents for {{ supplier.supplier_name }}.</p>
          </div>
          <Button v-if="!loadingContracts && !contractLoadError && !hasActiveContract" label="Create Contract" icon="pi pi-plus" severity="warn" size="small"
            @click="createContract" />
        </div>
      </template>
      <template #content>
        <div v-if="loadingContracts" class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <Skeleton v-for="item in 2" :key="item" height="120px" />
        </div>
        <div v-else-if="contractLoadError" class="flex flex-col items-start justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center">
          <p class="text-sm text-amber-900">{{ contractLoadError }}</p>
          <Button label="Retry" icon="pi pi-refresh" outlined size="small" @click="loadContracts" />
        </div>
        <div v-else-if="contracts.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div v-for="contract in contracts" :key="contract.id" class="rounded-lg border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">{{ contract.contract_title || 'Supplier Contract' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ contract.contract_number || `Contract #${contract.id}` }}</p>
              </div>
              <Tag :value="humanize(contract.status)" :severity="contractStatusSeverity(contract.status)" />
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
              <span>Type: {{ humanize(contract.contract_type) }}</span>
              <span>Discount: {{ Number(contract.discount_percentage || 0) }}%</span>
              <span>Start: {{ formatDate(contract.start_date) }}</span>
              <span>End: {{ formatDate(contract.end_date) }}</span>
            </div>
            <div class="mt-3 flex justify-end">
              <Button label="View Contract" icon="pi pi-eye" outlined rounded size="small"
                @click="viewContract(contract)" />
            </div>
          </div>
        </div>
        <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
          <i class="pi pi-file-edit mb-2 text-2xl text-slate-400" />
          <p class="text-sm font-medium text-slate-700">No contracts recorded</p>
          <p class="mt-1 text-xs text-slate-500">Create a supplier agreement to keep its terms and validity dates on file.</p>
        </div>
      </template>
    </Card>

    <Card v-if="!loadingSupplier && supplier" class="min-w-0">
      <template #title>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-gray-800">Linked Products</h2>
            <p class="mt-1 text-xs font-normal text-gray-500">Products supplied by {{ supplier.supplier_name }}.</p>
          </div>
          <IconField class="w-full sm:w-72">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" class="w-full" size="small" placeholder="Search product or SKU"
              @keyup.enter="applySearch" />
          </IconField>
        </div>
      </template>

      <template #content>
        <DataTable :value="products" :loading="loadingProducts" class="text-sm" rowHover
          responsiveLayout="scroll" paginator lazy :rows="perPage" :totalRecords="totalProducts"
          :first="(currentPage - 1) * perPage" :rowsPerPageOptions="[10, 15, 25, 50]" @page="onPageChange">
          <template #empty>
            <div class="py-12 text-center">
              <i class="pi pi-box mb-3 text-4xl text-gray-300" />
              <p class="text-gray-600">No linked products found</p>
            </div>
          </template>

          <Column field="product_name" header="Product" style="min-width: 210px">
            <template #body="{ data }">
              <p class="font-semibold text-gray-900">{{ data.product_name }}</p>
              <p class="text-xs text-gray-500">SKU: {{ data.sku || '-' }}</p>
            </template>
          </Column>
          <Column header="Category" style="min-width: 150px">
            <template #body="{ data }">
              <p class="text-sm text-gray-700">{{ data.category?.category_name || '-' }}</p>
              <p v-if="data.subcategory?.category_name" class="text-xs text-gray-500">{{ data.subcategory.category_name }}</p>
            </template>
          </Column>
          <Column field="brand" header="Brand" style="min-width: 120px">
            <template #body="{ data }">{{ data.brand || '-' }}</template>
          </Column>
          <Column header="Supplier Unit Price" style="min-width: 145px">
            <template #body="{ data }">{{ formatCurrency(data.pivot?.supplier_price) }}</template>
          </Column>
          <Column header="Min. Order Qty" style="min-width: 115px">
            <template #body="{ data }">{{ data.pivot?.minimum_order_quantity ?? 1 }}</template>
          </Column>
          <Column header="Lead Time" style="min-width: 105px">
            <template #body="{ data }">{{ data.pivot?.lead_time_days ?? 7 }} days</template>
          </Column>
          <Column header="Preferred" style="width: 105px">
            <template #body="{ data }">
              <Tag :value="data.pivot?.is_preferred_supplier ? 'Yes' : 'No'"
                :severity="data.pivot?.is_preferred_supplier ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column header="Action" style="width: 80px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" outlined rounded size="small" v-tooltip="'View Product'"
                @click="viewProduct(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="contractDialogVisible" modal :header="selectedContract?.contract_title || 'Supplier Contract'"
      :style="{ width: 'min(48rem, 95vw)' }">
      <div v-if="loadingSelectedContract" class="space-y-3 py-2">
        <Skeleton height="2rem" />
        <Skeleton height="7rem" />
      </div>
      <div v-else-if="selectedContract" class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Contract Reference</p>
            <p class="mt-1 font-semibold text-slate-900">{{ selectedContract.contract_number || `Contract #${selectedContract.id}` }}</p>
          </div>
          <Tag :value="humanize(selectedContract.status)" :severity="contractStatusSeverity(selectedContract.status)" />
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <InfoRow icon="pi pi-file" label="Contract Type" :value="humanize(selectedContract.contract_type)" />
          <InfoRow icon="pi pi-calendar" label="Validity" :value="`${formatDate(selectedContract.start_date)} - ${formatDate(selectedContract.end_date)}`" />
          <InfoRow icon="pi pi-percentage" label="Discount" :value="`${Number(selectedContract.discount_percentage || 0)}%`" />
          <InfoRow icon="pi pi-wallet" label="Minimum Order Value" :value="formatCurrency(selectedContract.minimum_order_value)" />
          <InfoRow icon="pi pi-percentage" label="VAT Rate" :value="selectedContract.is_tax_exempt ? 'Tax-exempt' : `${Number(selectedContract.tax_rate || 0)}%`" />
        </div>
        <div v-if="selectedContract.tax_note" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tax Note</p>
          <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ selectedContract.tax_note }}</p>
        </div>
        <div v-if="selectedContract.terms_conditions" class="rounded-lg border border-slate-200 p-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Terms &amp; Conditions</p>
          <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ selectedContract.terms_conditions }}</p>
        </div>
        <a v-if="contractDocumentUrl" :href="contractDocumentUrl" target="_blank" rel="noopener"
          class="inline-flex items-center gap-2 text-sm font-medium text-orange-700 hover:underline">
          <i class="pi pi-paperclip" /> View contract document
        </a>
        <div v-if="selectedContract.status === 'rejected'" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
          <p class="font-semibold">Rejection details</p>
          <p class="mt-1 whitespace-pre-line">{{ selectedContract.rejection_reason || 'No reason provided.' }}</p>
        </div>
      </div>
      <template #footer>
        <div class="flex flex-wrap justify-end gap-2">
          <Button label="Close" severity="secondary" outlined size="small" @click="contractDialogVisible = false" />
          <Button v-if="selectedContract?.status === 'pending'" label="Activate Contract" icon="pi pi-check-circle"
            severity="success" size="small" :loading="activatingContractId === Number(selectedContract.id)"
            @click="confirmActivateContract(selectedContract)" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import inventoryService from '../../../../services/inventory.service'

const InfoRow = defineComponent({
  props: { icon: String, label: String, value: [String, Number] },
  setup(props) {
    return () => h('div', { class: 'flex items-start gap-3' }, [
      h('i', { class: `${props.icon} mt-1 text-gray-400` }),
      h('div', { class: 'min-w-0' }, [
        h('p', { class: 'text-xs text-gray-500' }, props.label),
        h('p', { class: 'break-words text-sm font-medium text-gray-800' }, String(props.value || '-')),
      ]),
    ])
  },
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const supplierId = Number(route.params.id)
const supplier = ref<any>(null)
const products = ref<any[]>([])
const contracts = ref<any[]>([])
const selectedContract = ref<any>(null)
const contractDialogVisible = ref(false)
const loadingSelectedContract = ref(false)
const contractLoadError = ref('')
const search = ref('')
const loadingSupplier = ref(true)
const loadingProducts = ref(false)
const loadingContracts = ref(false)
const activatingContractId = ref<number | null>(null)
const currentPage = ref(1)
const perPage = ref(10)
const totalProducts = ref(0)

const hasActiveContract = computed(() => contracts.value.some((contract: any) => contract.status === 'active'))

const fullAddress = computed(() => [
  supplier.value?.address,
  supplier.value?.barangay,
  supplier.value?.city,
  supplier.value?.province,
].filter(Boolean).join(', ') || '-')

const loadSupplier = async (includeProfile = false) => {
  if (includeProfile) loadingSupplier.value = true
  loadingProducts.value = true
  loadingContracts.value = includeProfile
  try {
    const [supplierResult, contractResult] = await Promise.allSettled([
      inventoryService.getSupplier(supplierId, {
        search: search.value || undefined,
        page: currentPage.value,
        per_page: perPage.value,
      }),
      includeProfile
        ? inventoryService.getSupplierContracts({ supplier_id: supplierId, per_page: 100 })
        : Promise.resolve(null),
    ])
    if (supplierResult.status === 'rejected') throw supplierResult.reason

    const supplierResponse = supplierResult.value
    const payload = supplierResponse.data || supplierResponse
    supplier.value = payload.supplier
    products.value = payload.products?.data || []
    totalProducts.value = Number(payload.products?.total || products.value.length)
    currentPage.value = Number(payload.products?.current_page || currentPage.value)
    if (includeProfile) {
      if (contractResult.status === 'fulfilled') {
        const contractPayload = contractResult.value.data || contractResult.value
        const contractRows = contractPayload.data?.data ?? contractPayload.data
        contracts.value = Array.isArray(contractRows) ? contractRows : []
        contractLoadError.value = ''
      } else {
        contracts.value = []
        contractLoadError.value = 'Supplier contract records could not be loaded.'
        console.error('Failed to load supplier contracts', contractResult.reason)
      }
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to Load Supplier',
      detail: error.response?.data?.message || 'Supplier details could not be loaded.', life: 4000 })
  } finally {
    loadingSupplier.value = false
    loadingProducts.value = false
    loadingContracts.value = false
  }
}

const loadContracts = async () => {
  loadingContracts.value = true
  contractLoadError.value = ''
  try {
    const response = await inventoryService.getSupplierContracts({ supplier_id: supplierId, per_page: 100 })
    const payload = response.data || response
    const rows = payload.data?.data ?? payload.data
    contracts.value = Array.isArray(rows) ? rows : []
  } catch (error) {
    contracts.value = []
    contractLoadError.value = 'Supplier contract records could not be loaded.'
    console.error('Failed to load supplier contracts', error)
  } finally {
    loadingContracts.value = false
  }
}

const applySearch = () => {
  currentPage.value = 1
  loadSupplier()
}

const onPageChange = (event: any) => {
  currentPage.value = event.page + 1
  perPage.value = event.rows
  loadSupplier()
}

const viewProduct = (id: number) => router.push({ name: 'inventory.products.detail', params: { id } })
const editSupplier = () => router.push({ name: 'inventory.suppliers.edit', params: { id: supplierId } })
const createContract = () => router.push({
  name: 'procurement.supplier-contracts.create',
  query: { supplier_id: String(supplierId), supplier_name: supplier.value?.supplier_name || '', from_inventory: '1' },
})
const viewContract = async (contract: any) => {
  selectedContract.value = contract
  contractDialogVisible.value = true
  loadingSelectedContract.value = true
  try {
    const response = await inventoryService.getSupplierContract(contract.id)
    selectedContract.value = response?.data?.data ?? response?.data ?? response
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to Load Contract',
      detail: error.response?.data?.message || 'Contract details could not be loaded.', life: 4000 })
  } finally {
    loadingSelectedContract.value = false
  }
}
const confirmActivateContract = (contract: any) => {
  if (!contract?.id || contract.status !== 'pending') return
  confirm.require({
    header: 'Activate Supplier Contract',
    message: 'Confirm that the supplier has agreed to these terms. The contract will become active immediately.',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Confirm & Activate',
    rejectLabel: 'Keep Pending',
    acceptProps: { severity: 'success' },
    accept: async () => {
      activatingContractId.value = Number(contract.id)
      try {
        await inventoryService.activateSupplierContract(contract.id)
        toast.add({ severity: 'success', summary: 'Contract activated', detail: 'The supplier contract is now active.', life: 3000 })
        await Promise.all([loadContracts(), viewContract(contract)])
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Activation failed', detail: error?.response?.data?.message || 'Could not activate the contract.', life: 4000 })
      } finally {
        activatingContractId.value = null
      }
    },
  })
}
const humanize = (value?: string) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '-'
const formatRating = (value: any) => Number.isFinite(Number(value)) ? `${Number(value).toFixed(1)} / 5` : '-'
const formatCurrency = (value: any) => value == null ? '-' : new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value))
const formatDate = (value?: string | null) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'
const contractDocumentUrl = computed(() => {
  const path = String(selectedContract.value?.contract_file_path || '').trim()
  if (!path) return ''
  if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path
  return `/storage/${path.replace(/^storage\//, '')}`
})
const contractStatusSeverity = (status?: string) => {
  if (status === 'active') return 'success'
  if (status === 'rejected' || status === 'terminated') return 'danger'
  if (status === 'pending') return 'warn'
  return 'secondary'
}

onMounted(() => loadSupplier(true))
</script>
