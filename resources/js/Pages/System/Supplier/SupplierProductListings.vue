<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Supplier portal</p>
        <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Supplier Products</h1>
        <p class="mt-1 text-sm text-slate-500">Your supplier catalog. Expand a product to view its store-specific offers and terms.</p>
      </div>
      <div class="flex gap-2">
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadListings(1)" />
        <Button icon="pi pi-plus" label="Create Product" severity="warn" size="small" @click="router.push({ name: 'supplier.products.create' })" />
      </div>
    </header>

    <Card class="border border-slate-200 shadow-none"><template #content>
      <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-center">
        <div class="w-full lg:w-72"><IconField fluid><InputIcon class="pi pi-search" /><InputText v-model="filters.search" placeholder="Search product or supplier SKU" size="small" fluid @input="scheduleSearch" /></IconField></div>
        <div class="w-full lg:w-56"><Select v-model="filters.store_id" :options="stores" optionLabel="store_name" optionValue="id" placeholder="All linked stores" showClear fluid size="small" @change="loadListings(1)" /></div>
        <div class="w-full lg:w-40"><Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" showClear fluid size="small" @change="loadListings(1)" /></div>
        <Button v-if="hasFilters" label="Reset" icon="pi pi-filter-slash" severity="secondary" text size="small" @click="resetFilters" />
      </div>
      <div v-if="loading && !rows.length" class="space-y-2 py-4"><Skeleton v-for="item in 5" :key="item" height="3.5rem" borderRadius="0.5rem" /></div>
      <DataTable v-else v-model:expandedRows="expandedRows" :value="rows" dataKey="id" rowHover responsiveLayout="scroll" class="mt-3 text-sm" paginator lazy :first="(page - 1) * perPage" :rows="perPage" :totalRecords="total" :rowsPerPageOptions="[10, 25, 50]" :loading="loading" @page="onPage">
        <Column expander style="width: 3rem" />
        <Column header="Supplier Product" style="min-width: 19rem"><template #body="{ data }"><div class="flex items-center gap-3"><img v-if="data.image_url" :src="data.image_url" :alt="data.product_name" class="h-10 w-10 rounded-lg border border-slate-200 object-cover" /><div v-else class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400"><i class="pi pi-box" /></div><div class="min-w-0"><p class="truncate font-medium text-slate-900">{{ data.product_name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ [data.category_name, data.subcategory_name].filter(Boolean).join(' · ') || formatType(data.product_type) }}</p></div></div></template></Column>
        <Column header="Supplier SKU" style="min-width: 11rem"><template #body="{ data }"><span class="font-medium text-slate-700">{{ data.supplier_sku || data.product_sku || '—' }}</span></template></Column>
        <Column header="Store Offers" style="width: 9rem"><template #body="{ data }"><Badge :value="`${data.offers?.length || 0} offer${data.offers?.length === 1 ? '' : 's'}`" severity="secondary" /></template></Column>
        <Column header="Catalog Status" style="width: 9rem"><template #body="{ data }"><Badge :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" /></template></Column>
        <Column header="Actions" style="width: 7rem"><template #body="{ data }"><Button label="View" icon="pi pi-eye" outlined rounded size="small" @click="router.push({ name: 'supplier.products.detail', params: { id: data.id } })" /></template></Column>
        <template #expansion="{ data }"><div class="rounded-lg border border-slate-200 bg-slate-50 p-3 sm:p-4"><div class="mb-3"><h2 class="text-sm font-semibold text-slate-900">Store Offers</h2><p class="mt-1 text-xs text-slate-500">Pricing, tax, minimum order, lead time, availability, and active status are set per store.</p></div><div class="space-y-3"><div v-for="offer in data.offers" :key="offer.id" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 md:grid-cols-[1.2fr_1fr_1fr_0.8fr_auto] md:items-center"><div><p class="font-medium text-slate-900">{{ offer.store_name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ offer.minimum_order_quantity }} {{ data.unit_of_measurement || 'unit' }} minimum · {{ offer.lead_time_days }} lead day{{ offer.lead_time_days === 1 ? '' : 's' }}</p></div><div><p class="font-semibold text-slate-900">{{ money(offer.cost_including_tax) }}</p><p class="text-xs text-slate-500">Base {{ money(offer.base_cost) }} · {{ offer.is_tax_exempt ? 'Tax exempt' : `${number(offer.tax_rate)}% tax` }}</p></div><div><Badge :value="availabilityLabel(offer.availability_status)" :severity="availabilitySeverity(offer.availability_status)" /><Badge class="ml-2" :value="offer.is_active ? 'Active' : 'Inactive'" :severity="offer.is_active ? 'success' : 'secondary'" /></div><p class="text-xs text-slate-500">{{ offer.is_tax_exempt ? 'No tax' : `${money(offer.tax_amount)} tax` }}</p><Button label="Edit Offer" icon="pi pi-pencil" severity="secondary" outlined size="small" @click="openOfferEditor(offer, data)" /></div></div></div></template>
        <template #empty><div class="py-12 text-center"><i class="pi pi-box text-3xl text-slate-300" /><p class="mt-3 font-medium text-slate-700">No supplier products found</p><p class="mt-1 text-xs text-slate-500">Create a product to add it to your supplier catalog.</p></div></template>
      </DataTable>
    </template></Card>

    <Dialog v-model:visible="editVisible" modal header="Edit Store Offer" :style="{ width: 'min(94vw, 34rem)' }">
      <form class="space-y-4" @submit.prevent="saveOffer"><p class="text-sm text-slate-600">{{ editingProduct?.product_name }} · {{ editingOffer?.store_name }}</p>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-medium text-slate-600">Base cost</label><InputNumber v-model="editForm.supplier_price" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid size="small" /></div><div><label class="mb-1 block text-xs font-medium text-slate-600">Minimum order quantity</label><InputNumber v-model="editForm.minimum_order_quantity" :min="1" :maxFractionDigits="0" fluid size="small" /></div><div><label class="mb-1 block text-xs font-medium text-slate-600">Lead time</label><InputNumber v-model="editForm.lead_time_days" :min="0" :maxFractionDigits="0" suffix=" days" fluid size="small" /></div><div><label class="mb-1 block text-xs font-medium text-slate-600">Availability</label><Select v-model="editForm.availability_status" :options="availabilityOptions" optionLabel="label" optionValue="value" fluid size="small" /></div><label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2"><Checkbox v-model="editForm.is_active" binary /> Offer is active</label></div>
      </form>
      <template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="editVisible = false" /><Button label="Save Offer" severity="warn" size="small" :loading="savingOffer" @click="saveOffer" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'

const toast = useToast(); const router = useRouter(); const loading = ref(false); const savingOffer = ref(false)
const rows = ref<any[]>([]); const stores = ref<any[]>([]); const expandedRows = ref<any[]>([]); const page = ref(1); const perPage = ref(25); const total = ref(0)
const editVisible = ref(false); const editingOffer = ref<any>(null); const editingProduct = ref<any>(null)
const editForm = reactive<any>({ supplier_price: null, minimum_order_quantity: 1, lead_time_days: 0, availability_status: 'available', is_active: true })
let searchTimer: ReturnType<typeof setTimeout> | null = null
const filters = reactive({ search: '', store_id: null as number | null, status: null as 'active' | 'inactive' | null })
const statusOptions = [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]
const availabilityOptions = [{ label: 'Available', value: 'available' }, { label: 'Limited', value: 'limited' }, { label: 'Out of stock', value: 'out_of_stock' }]
const hasFilters = computed(() => Boolean(filters.search || filters.store_id || filters.status))
const loadListings = async (requestedPage = page.value) => { loading.value = true; try { const response = await supplierService.getProductListings({ page: requestedPage, per_page: perPage.value, search: filters.search || undefined, store_id: filters.store_id || undefined, status: filters.status || undefined }); const pagination = response.data || {}; rows.value = pagination.data || []; total.value = Number(pagination.total || 0); page.value = Number(pagination.current_page || requestedPage); stores.value = response.stores || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load products', detail: error?.response?.data?.message || 'Supplier products could not be loaded.', life: 3000 }) } finally { loading.value = false } }
const scheduleSearch = () => { if (searchTimer) clearTimeout(searchTimer); searchTimer = setTimeout(() => loadListings(1), 350) }
const onPage = (event: any) => { perPage.value = Number(event.rows || 25); loadListings(Number(event.page || 0) + 1) }
const resetFilters = () => { filters.search = ''; filters.store_id = null; filters.status = null; loadListings(1) }
const openOfferEditor = (offer: any, product: any) => { editingOffer.value = offer; editingProduct.value = product; Object.assign(editForm, { supplier_price: offer.base_cost, minimum_order_quantity: offer.minimum_order_quantity, lead_time_days: offer.lead_time_days, availability_status: offer.availability_status || 'available', is_active: offer.is_active }); editVisible.value = true }
const saveOffer = async () => { if (!editingOffer.value) return; savingOffer.value = true; try { await supplierService.updateProductListing(editingOffer.value.id, editForm); toast.add({ severity: 'success', summary: 'Store offer updated', detail: 'The store-specific terms were saved.', life: 2500 }); editVisible.value = false; await loadListings(page.value) } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to save offer', detail: error?.response?.data?.message || 'Check the offer details and try again.', life: 3500 }) } finally { savingOffer.value = false } }
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
const formatType = (value: string) => String(value || 'Product').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())
const availabilityLabel = (value: string) => ({ available: 'Available', limited: 'Limited', out_of_stock: 'Out of stock' } as Record<string, string>)[value] || 'Available'
const availabilitySeverity = (value: string) => ({ available: 'success', limited: 'warn', out_of_stock: 'danger' } as Record<string, string>)[value] || 'success'
onMounted(() => loadListings(1)); onBeforeUnmount(() => { if (searchTimer) clearTimeout(searchTimer) })
</script>
