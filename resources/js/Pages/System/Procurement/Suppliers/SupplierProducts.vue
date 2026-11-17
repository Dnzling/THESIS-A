<template>
  <div class="mx-auto max-w-7xl space-y-5 pb-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-chevron-left" rounded outlined severity="secondary" aria-label="Go back" title="Go back" size="small" @click="goBack" />
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Procurement</p>
          <h1 class="mt-1 text-xl font-semibold text-slate-900">Get Products from {{ supplier?.supplier_name || 'Supplier' }}</h1>
          <p class="mt-1 text-sm text-slate-500">Select available products and create a draft request for quotation.</p>
        </div>
      </div>
      <Button label="View RFQs" icon="pi pi-file" severity="secondary" outlined size="small" @click="router.push({ name: 'procurement.rfqs' })" />
    </header>

    <Message v-if="supplier && supplier.contract_status !== 'active'" severity="warn" :closable="false">
      An active supplier contract is required before an RFQ can be created.
    </Message>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <IconField class="w-full sm:w-80" fluid><InputIcon class="pi pi-search" /><InputText v-model="search" placeholder="Search products" fluid size="small" /></IconField>
            <Select v-model="selectedCategoryId" :options="categoryOptions" optionLabel="label" optionValue="value" class="w-full sm:w-56" size="small" aria-label="Filter by category" />
          </div>
          <div class="text-xs text-slate-500">{{ selectedRows.length }} selected · {{ filteredProducts.length }} products</div>
        </div>
      </template>
    </Card>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <Skeleton v-for="item in 8" :key="item" height="18rem" borderRadius="0.75rem" />
    </div>
    <div v-else-if="!filteredProducts.length" class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-16 text-center">
      <i class="pi pi-box text-4xl text-slate-300" />
      <p class="mt-3 font-medium text-slate-700">No supplier products found</p>
      <p class="mt-1 text-sm text-slate-500">Supplier catalog offers appear here. Unmatched products need linking to a store product before they can be added to an RFQ.</p>
    </div>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <article v-for="product in filteredProducts" :key="rowKey(product)" class="overflow-hidden rounded-xl border bg-white transition" :class="selectedIds.includes(rowKey(product)) ? 'border-orange-400 ring-2 ring-orange-100' : 'border-slate-200 hover:border-slate-300'">
        <div class="flex h-36 items-center justify-center overflow-hidden bg-white p-2 sm:h-40">
          <img v-if="product.image_url" :src="product.image_url" :alt="product.product_name" class="h-full w-full object-contain" />
          <div v-else class="flex h-full items-center justify-center"><i class="pi pi-box text-3xl text-slate-300" /></div>
        </div>
        <div class="space-y-2.5 p-3">
          <div>
            <h2 class="line-clamp-2 font-semibold leading-5 text-slate-900">{{ product.product_name }}</h2>
            <p v-if="product.variation_sku || product.supplier_sku" class="mt-1 text-xs text-slate-500">SKU: {{ product.variation_sku || product.supplier_sku }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <Badge v-if="product.match_status === 'needs_match'" value="Needs product match" severity="warn" />
              <Badge :value="availabilityLabel(product.availability_status)" :severity="availabilitySeverity(product.availability_status)" />
            </div>
            <p v-if="product.category_name || product.subcategory_name" class="mt-1 text-xs text-slate-500">
              {{ [product.category_name, product.subcategory_name].filter(Boolean).join(' · ') }}
            </p>
          </div>
          <div class="rounded-lg bg-slate-50 p-2.5">
            <p class="text-xs text-slate-500">Cost including tax</p>
            <p class="mt-1 text-base font-semibold text-slate-900">{{ money(product.cost_including_tax) }}</p>
            <p class="mt-1 text-xs text-slate-500">Base {{ money(product.base_cost) }} · {{ product.is_tax_exempt ? 'Tax exempt' : `${number(product.tax_rate)}% tax` }}</p>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs text-slate-600"><p class="col-span-2"><span class="text-slate-400">MOQ:</span> {{ product.minimum_order_quantity }}</p><p class="col-span-2"><span class="text-slate-400">Lead time:</span> {{ product.lead_time_days }} day{{ product.lead_time_days === 1 ? '' : 's' }}</p></div>
          <div v-if="hasActiveContract && selectedIds.includes(rowKey(product))">
            <label class="mb-1 block text-xs font-medium text-slate-600">RFQ Quantity</label>
            <InputNumber :modelValue="quantities[rowKey(product)] || 1" @update:modelValue="quantities[rowKey(product)] = Number($event || 0)" :min="product.minimum_order_quantity" :maxFractionDigits="0" fluid size="small" />
            <small v-if="quantityError(product)" class="text-red-600">{{ quantityError(product) }}</small>
          </div>
          <Button v-if="product.match_status === 'needs_match' && canManageSupplierOffers" fluid size="small" label="Match store product" icon="pi pi-link" severity="secondary" outlined @click="openMatchDialog(product)" />
          <Button v-else-if="hasActiveContract" fluid size="small" :label="product.is_acquired ? 'Acquired' : !product.is_active ? 'Unavailable' : selectedIds.includes(rowKey(product)) ? 'Remove' : 'Select Product'" :icon="product.is_acquired ? 'pi pi-check-circle' : !product.is_active ? 'pi pi-ban' : selectedIds.includes(rowKey(product)) ? 'pi pi-times' : 'pi pi-plus'" :severity="product.is_acquired ? 'success' : !product.is_active ? 'secondary' : selectedIds.includes(rowKey(product)) ? 'secondary' : 'warn'" :outlined="product.is_acquired || !product.is_active || selectedIds.includes(rowKey(product))" :disabled="!product.is_active || product.is_acquired" @click="toggleProduct(product)" />
        </div>
      </article>
    </div>

    <div v-if="hasActiveContract && selectedRows.length" class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-xl border border-orange-200 bg-white p-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
      <div><p class="font-semibold text-slate-900">{{ selectedRows.length }} product{{ selectedRows.length === 1 ? '' : 's' }} selected</p><p class="text-xs text-slate-500">Estimated tax-inclusive value: {{ money(estimatedTotal) }}</p></div>
      <Button label="Create RFQ" icon="pi pi-file-plus" severity="warn" :disabled="!canCreate" @click="openConfirmation" />
    </div>

    <Dialog v-model:visible="confirmationVisible" modal header="Confirm RFQ Creation" :style="{ width: 'min(94vw, 42rem)' }">
      <div class="space-y-4 text-sm">
        <div><label class="mb-1 block font-medium text-slate-700">RFQ Title</label><InputText v-model="rfqTitle" fluid size="small" /></div>
        <div class="max-h-72 divide-y divide-slate-200 overflow-y-auto rounded-lg border border-slate-200">
          <div v-for="row in selectedRows" :key="rowKey(row.product)" class="flex items-center justify-between gap-3 p-3"><div><p class="font-medium text-slate-900">{{ row.product.product_name }}</p><p class="text-xs text-slate-500">{{ row.quantity }} × {{ money(row.product.cost_including_tax) }}</p></div><p class="font-semibold text-slate-900">{{ money(row.quantity * row.product.cost_including_tax) }}</p></div>
        </div>
        <div class="flex justify-between border-t border-slate-200 pt-3 font-semibold"><span>Estimated total</span><span>{{ money(estimatedTotal) }}</span></div>
        <Message severity="info" :closable="false">This creates a draft RFQ for {{ supplier?.supplier_name }}. You can review and send it from RFQ Details.</Message>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="confirmationVisible = false" /><Button label="Confirm and Create" severity="warn" size="small" :loading="submitting" @click="createRfq" /></template>
    </Dialog>

    <Dialog v-model:visible="matchDialogVisible" modal header="Match supplier offer" :style="{ width: 'min(94vw, 36rem)' }">
      <div class="space-y-4">
        <Message severity="info" :closable="false">Choose the matching product already recorded in this store. Products with variations must have the same variation SKUs or attributes.</Message>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs text-slate-500">Supplier offer</p>
          <p class="mt-1 font-medium text-slate-900">{{ matchingOffer?.product_name }}</p>
          <p class="mt-1 text-xs text-slate-500">Supplier SKU: {{ matchingOffer?.variation_sku || matchingOffer?.supplier_sku || '—' }}</p>
        </div>
        <IconField fluid><InputIcon class="pi pi-search" /><InputText v-model="matchSearch" placeholder="Search store products by name or SKU" fluid size="small" @input="loadMatchableProducts" /></IconField>
        <Select v-model="selectedStoreProductId" :options="matchableProducts" optionLabel="label" optionValue="id" placeholder="Select a store product" filter fluid size="small" :loading="loadingMatchableProducts" />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="matchDialogVisible = false" />
        <Button label="Save match" severity="warn" size="small" :loading="matching" :disabled="!selectedStoreProductId" @click="saveProductMatch" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import procurementService from '../../../../services/procurement.service'

const route = useRoute(); const router = useRouter(); const toast = useToast(); const authStore = useAuthStore(); const supplierId = Number(route.params.id)
const loading = ref(false); const submitting = ref(false); const products = ref<any[]>([]); const supplier = ref<any>(null); const search = ref(''); const selectedCategoryId = ref<number | null>(null); const quantities = ref<Record<string, number>>({}); const selectedIds = ref<string[]>([]); const confirmationVisible = ref(false); const rfqTitle = ref('')
const matchDialogVisible = ref(false); const matching = ref(false); const loadingMatchableProducts = ref(false); const matchingOffer = ref<any>(null); const matchableProducts = ref<any[]>([]); const matchSearch = ref(''); const selectedStoreProductId = ref<number | null>(null)
const hasActiveContract = computed(() => supplier.value?.contract_status === 'active')
const canManageSupplierOffers = computed(() => authStore.hasPermission('procurement.suppliers.manage'))
const categoryOptions = computed(() => {
  const categories = new Map<number, string>()
  products.value.forEach(product => {
    if (product.category_id != null && product.category_name) categories.set(Number(product.category_id), String(product.category_name))
  })
  return [
    { label: 'All categories', value: null },
    ...[...categories.entries()]
      .map(([value, label]) => ({ label, value }))
      .sort((a, b) => a.label.localeCompare(b.label)),
  ]
})
const filteredProducts = computed(() => {
  const q = search.value.trim().toLowerCase()
  return products.value.filter(product => {
    const matchesSearch = !q || String(product.product_name || '').toLowerCase().includes(q)
    const matchesCategory = selectedCategoryId.value == null || Number(product.category_id) === Number(selectedCategoryId.value)
    return matchesSearch && matchesCategory
  })
})
const rowKey = (product: any) => `${product.offer_id}:${product.variation_id || 0}`
const selectedRows = computed(() => selectedIds.value.map(key => ({ product: products.value.find(product => rowKey(product) === key), quantity: Number(quantities.value[key] || 0) })).filter(row => row.product))
const estimatedTotal = computed(() => selectedRows.value.reduce((sum, row) => sum + row.quantity * Number(row.product.cost_including_tax || 0), 0))
const canCreate = computed(() => supplier.value?.contract_status === 'active' && selectedRows.value.length > 0 && selectedRows.value.every(row => row.product.matched_product_id && !row.product.is_acquired && !quantityError(row.product)))
const quantityError = (product: any) => { const key = rowKey(product); if (!selectedIds.value.includes(key)) return ''; const quantity = Number(quantities.value[key] || 0); if (quantity < Number(product.minimum_order_quantity || 1)) return `Minimum quantity is ${product.minimum_order_quantity}.`; return '' }
const toggleProduct = (product: any) => { if (!product.matched_product_id || product.is_acquired || !product.is_active) return; const key = rowKey(product); if (selectedIds.value.includes(key)) { selectedIds.value = selectedIds.value.filter(id => id !== key); delete quantities.value[key] } else { selectedIds.value.push(key); quantities.value[key] = Number(product.minimum_order_quantity || 1) } }
const load = async () => { loading.value = true; try { const response = await procurementService.getSupplierAvailableProducts(supplierId); supplier.value = response.data?.supplier || null; products.value = response.data?.products || []; rfqTitle.value = `Request for quotation - ${supplier.value?.supplier_name || 'Supplier products'}` } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load products', detail: error?.response?.data?.message || 'Supplier products could not be loaded.', life: 3000 }) } finally { loading.value = false } }
const loadMatchableProducts = async () => { loadingMatchableProducts.value = true; try { const response = await procurementService.getSupplierMatchableProducts(supplierId, matchSearch.value); matchableProducts.value = (response.data || []).map((product: any) => ({ ...product, label: `${product.product_name} (${product.sku || 'No SKU'})` })) } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load store products', detail: error?.response?.data?.message || 'Store products could not be loaded.', life: 3000 }) } finally { loadingMatchableProducts.value = false } }
const openMatchDialog = async (product: any) => { matchingOffer.value = product; matchSearch.value = ''; selectedStoreProductId.value = null; matchDialogVisible.value = true; await loadMatchableProducts() }
const saveProductMatch = async () => { if (!matchingOffer.value || !selectedStoreProductId.value || matching.value) return; matching.value = true; try { await procurementService.matchSupplierProduct(supplierId, matchingOffer.value.offer_id, selectedStoreProductId.value); matchDialogVisible.value = false; selectedIds.value = selectedIds.value.filter(key => !key.startsWith(`${matchingOffer.value.offer_id}:`)); await load(); toast.add({ severity: 'success', summary: 'Product matched', detail: 'Supplier offer is now linked to the store product.', life: 2500 }) } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to match product', detail: error?.response?.data?.message || 'The supplier offer could not be matched.', life: 3500 }) } finally { matching.value = false } }
const openConfirmation = () => { if (canCreate.value) confirmationVisible.value = true }
const createRfq = async () => { if (!canCreate.value || !rfqTitle.value.trim()) return; submitting.value = true; try { const response = await procurementService.createRFQ({ title: rfqTitle.value.trim(), description: `RFQ created from ${supplier.value.supplier_name}'s connected product listings.`, issue_date: new Date().toISOString().slice(0, 10), items: selectedRows.value.map(row => ({ product_id: row.product.matched_product_id, variation_id: row.product.variation_id || null, quantity: row.quantity, specifications: '', requirements: '' })), supplier_ids: [supplierId], rfq_type: 'purchase', currency: 'PHP', from_supplier_products: true } as any); confirmationVisible.value = false; toast.add({ severity: 'success', summary: 'RFQ created', detail: response.message || 'Draft RFQ created successfully.', life: 2500 }); router.push({ name: 'procurement.rfqs.detail', params: { id: response.data.id } }) } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to create RFQ', detail: error?.response?.data?.message || 'RFQ creation failed.', life: 3500 }) } finally { submitting.value = false } }
const goBack = () => router.push({ name: 'procurement.suppliers.detail', params: { id: supplierId } })
const availabilityLabel = (value: string) => ({ available: 'Available', limited: 'Limited', out_of_stock: 'Out of stock', unavailable: 'Unavailable', inactive: 'Inactive' } as Record<string, string>)[value] || 'Available'
const availabilitySeverity = (value: string) => ({ available: 'success', limited: 'warn', out_of_stock: 'danger', unavailable: 'secondary', inactive: 'secondary' } as Record<string, string>)[value] || 'success'
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0)); const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
onMounted(load)
</script>
