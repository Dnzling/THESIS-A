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
      <p class="mt-1 text-sm text-slate-500">Products connected to this supplier and store will appear here.</p>
    </div>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <article v-for="product in filteredProducts" :key="product.id" class="overflow-hidden rounded-xl border bg-white transition" :class="isSelected(product.id) ? 'border-orange-400 ring-2 ring-orange-100' : 'border-slate-200 hover:border-slate-300'">
        <div class="flex h-36 items-center justify-center overflow-hidden bg-white p-2 sm:h-40">
          <img v-if="product.image_url" :src="product.image_url" :alt="product.product_name" class="h-full w-full object-contain" />
          <div v-else class="flex h-full items-center justify-center"><i class="pi pi-box text-3xl text-slate-300" /></div>
        </div>
        <div class="space-y-2.5 p-3">
          <div>
            <h2 class="line-clamp-2 font-semibold leading-5 text-slate-900">{{ product.product_name }}</h2>
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
          <div v-if="isSelected(product.id)">
            <label class="mb-1 block text-xs font-medium text-slate-600">RFQ Quantity</label>
            <InputNumber :modelValue="quantityFor(product.id)" @update:modelValue="setQuantity(product.id, $event)" :min="product.minimum_order_quantity" :maxFractionDigits="0" fluid size="small" />
            <small v-if="quantityError(product)" class="text-red-600">{{ quantityError(product) }}</small>
          </div>
          <Button fluid size="small" :label="product.is_acquired ? 'Acquired' : isSelected(product.id) ? 'Remove' : 'Select Product'" :icon="product.is_acquired ? 'pi pi-check-circle' : isSelected(product.id) ? 'pi pi-times' : 'pi pi-plus'" :severity="product.is_acquired ? 'success' : isSelected(product.id) ? 'secondary' : 'warn'" :outlined="product.is_acquired || isSelected(product.id)" :disabled="!product.is_active || product.is_acquired" @click="toggleProduct(product)" />
        </div>
      </article>
    </div>

    <div v-if="selectedRows.length" class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-xl border border-orange-200 bg-white p-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
      <div><p class="font-semibold text-slate-900">{{ selectedRows.length }} product{{ selectedRows.length === 1 ? '' : 's' }} selected</p><p class="text-xs text-slate-500">Estimated tax-inclusive value: {{ money(estimatedTotal) }}</p></div>
      <Button label="Create RFQ" icon="pi pi-file-plus" severity="warn" :disabled="!canCreate" @click="openConfirmation" />
    </div>

    <Dialog v-model:visible="confirmationVisible" modal header="Confirm RFQ Creation" :style="{ width: 'min(94vw, 42rem)' }">
      <div class="space-y-4 text-sm">
        <div><label class="mb-1 block font-medium text-slate-700">RFQ Title</label><InputText v-model="rfqTitle" fluid size="small" /></div>
        <div class="max-h-72 divide-y divide-slate-200 overflow-y-auto rounded-lg border border-slate-200">
          <div v-for="row in selectedRows" :key="row.product.id" class="flex items-center justify-between gap-3 p-3"><div><p class="font-medium text-slate-900">{{ row.product.product_name }}</p><p class="text-xs text-slate-500">{{ row.quantity }} × {{ money(row.product.cost_including_tax) }}</p></div><p class="font-semibold text-slate-900">{{ money(row.quantity * row.product.cost_including_tax) }}</p></div>
        </div>
        <div class="flex justify-between border-t border-slate-200 pt-3 font-semibold"><span>Estimated total</span><span>{{ money(estimatedTotal) }}</span></div>
        <Message severity="info" :closable="false">This creates a draft RFQ for {{ supplier?.supplier_name }}. You can review and send it from RFQ Details.</Message>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="confirmationVisible = false" /><Button label="Confirm and Create" severity="warn" size="small" :loading="submitting" @click="createRfq" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
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

const route = useRoute(); const router = useRouter(); const toast = useToast(); const supplierId = Number(route.params.id)
const loading = ref(false); const submitting = ref(false); const products = ref<any[]>([]); const supplier = ref<any>(null); const search = ref(''); const selectedCategoryId = ref<number | null>(null); const quantities = ref<Record<number, number>>({}); const selectedIds = ref<number[]>([]); const confirmationVisible = ref(false); const rfqTitle = ref('')
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
const selectedRows = computed(() => selectedIds.value.map(id => ({ product: products.value.find(product => product.id === id), quantity: Number(quantities.value[id] || 0) })).filter(row => row.product))
const estimatedTotal = computed(() => selectedRows.value.reduce((sum, row) => sum + row.quantity * Number(row.product.cost_including_tax || 0), 0))
const canCreate = computed(() => supplier.value?.contract_status === 'active' && selectedRows.value.length > 0 && selectedRows.value.every(row => !row.product.is_acquired && !quantityError(row.product)))
const isSelected = (id: number) => selectedIds.value.includes(id); const quantityFor = (id: number) => quantities.value[id] || 1
const setQuantity = (id: number, value: number | null) => { quantities.value[id] = Number(value || 0) }
const quantityError = (product: any) => { if (!isSelected(product.id)) return ''; const quantity = quantityFor(product.id); if (quantity < Number(product.minimum_order_quantity || 1)) return `Minimum quantity is ${product.minimum_order_quantity}.`; return '' }
const toggleProduct = (product: any) => { if (product.is_acquired) return; if (isSelected(product.id)) { selectedIds.value = selectedIds.value.filter(id => id !== product.id); delete quantities.value[product.id] } else { selectedIds.value.push(product.id); quantities.value[product.id] = Number(product.minimum_order_quantity || 1) } }
const load = async () => { loading.value = true; try { const response = await procurementService.getSupplierAvailableProducts(supplierId); supplier.value = response.data?.supplier || null; products.value = response.data?.products || []; rfqTitle.value = `Request for quotation - ${supplier.value?.supplier_name || 'Supplier products'}` } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load products', detail: error?.response?.data?.message || 'Supplier products could not be loaded.', life: 3000 }) } finally { loading.value = false } }
const openConfirmation = () => { if (canCreate.value) confirmationVisible.value = true }
const createRfq = async () => { if (!canCreate.value || !rfqTitle.value.trim()) return; submitting.value = true; try { const response = await procurementService.createRFQ({ title: rfqTitle.value.trim(), description: `RFQ created from ${supplier.value.supplier_name}'s connected product listings.`, issue_date: new Date().toISOString().slice(0, 10), items: selectedRows.value.map(row => ({ product_id: row.product.id, variation_id: null, quantity: row.quantity, specifications: '', requirements: '' })), supplier_ids: [supplierId], rfq_type: 'purchase', currency: 'PHP', from_supplier_products: true } as any); confirmationVisible.value = false; toast.add({ severity: 'success', summary: 'RFQ created', detail: response.message || 'Draft RFQ created successfully.', life: 2500 }); router.push({ name: 'procurement.rfqs.detail', params: { id: response.data.id } }) } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to create RFQ', detail: error?.response?.data?.message || 'RFQ creation failed.', life: 3500 }) } finally { submitting.value = false } }
const goBack = () => router.push({ name: 'procurement.suppliers.detail', params: { id: supplierId } })
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0)); const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
onMounted(load)
</script>
