<template>
  <div v-if="location" class="min-h-screen p-4 md:p-6 space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <Button label="Back to Locations" icon="pi pi-arrow-left" text size="small" @click="goBack" />
        <div class="mt-2 flex flex-wrap items-center gap-3">
          <h1 class="text-2xl font-semibold text-slate-900">{{ location.name || 'Storage Location' }}</h1>
          <Badge :value="label(location.status)" :severity="statusSeverity(location.status)" />
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ location.location_code || 'No code' }} · {{ location.warehouse?.name || 'Warehouse location' }}</p>
      </div>
      <div class="flex gap-2">
        <Button label="Delete" icon="pi pi-trash" severity="danger" size="small" @click="confirmDelete" />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
      <Card v-for="card in summary" :key="card.label" class="border border-slate-200 shadow-sm">
        <template #content><p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ card.value }}</p></template>
      </Card>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Location Information</span></template>
          <template #content><div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3"><div v-for="field in informationFields" :key="field.label"><p class="text-xs uppercase tracking-wide text-slate-500">{{ field.label }}</p><p class="mt-1 font-medium text-slate-900">{{ field.value || '—' }}</p></div></div></template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Storage Coordinates</span></template>
          <template #content><div class="grid gap-3 sm:grid-cols-3"><div v-for="field in coordinateFields" :key="field.label" class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">{{ field.label }}</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ field.value || '—' }}</p></div></div></template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Inventory in This Location</span></template>
          <template #content>
            <div v-if="productsLoading" class="flex justify-center py-8"><ProgressSpinner /></div>
            <DataTable v-else-if="products.length" :value="products" size="small" stripedRows>
              <Column header="Item"><template #body="{ data }"><div><p class="font-medium text-slate-900">{{ data.name || data.product_name || data.product?.name || 'Unnamed item' }}</p><p class="text-xs text-slate-500">{{ data.code || data.sku || data.product?.sku || '—' }}</p></div></template></Column>
              <Column header="Quantity"><template #body="{ data }"><span class="font-semibold">{{ number(data.quantity ?? data.quantity_on_hand) }}</span></template></Column>
              <Column header="Status"><template #body="{ data }"><Tag :value="label(data.status || 'active')" :severity="statusSeverity(data.status || 'active')" /></template></Column>
              <Column header="Action" style="width: 90px"><template #body="{ data }"><Button icon="pi pi-eye" text rounded @click="viewProduct(data)" /></template></Column>
            </DataTable>
            <div v-else class="py-10 text-center"><i class="pi pi-box text-3xl text-slate-300"></i><p class="mt-2 text-sm text-slate-500">No inventory recorded in this location.</p><Button label="View Inventory" text size="small" class="mt-2" @click="viewProducts" /></div>
          </template>
        </Card>
      </div>

      <div class="space-y-6">
        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Capacity</span></template>
          <template #content>
            <div class="flex items-end justify-between"><div><p class="text-xs uppercase tracking-wide text-slate-500">Used</p><p class="text-2xl font-semibold text-slate-900">{{ number(location.current_stock_units) }}</p></div><div class="text-right"><p class="text-xs uppercase tracking-wide text-slate-500">Maximum</p><p class="font-semibold text-slate-900">{{ location.max_capacity_units ? number(location.max_capacity_units) : '—' }}</p></div></div>
            <ProgressBar :value="capacityPercent" :showValue="false" class="mt-4" /><p class="mt-2 text-right text-xs text-slate-500">{{ capacityPercent }}% used</p><Divider />
            <div class="flex justify-between text-sm"><span class="text-slate-500">Weight limit</span><strong>{{ location.max_weight_kg ? `${number(location.max_weight_kg)} kg` : '—' }}</strong></div>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Dimensions</span></template>
          <template #content><dl class="space-y-3 text-sm"><div v-for="field in dimensionFields" :key="field.label" class="flex justify-between gap-4 border-b border-slate-100 pb-2"><dt class="text-slate-500">{{ field.label }}</dt><dd class="font-medium text-slate-900">{{ field.value || '—' }}</dd></div></dl></template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Warehouse</span></template>
          <template #content><dl class="space-y-3 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="text-right font-medium text-slate-900">{{ location.warehouse?.name || '—' }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Code</dt><dd class="text-right font-medium text-slate-900">{{ location.warehouse?.warehouse_code || location.warehouse?.code || '—' }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Branch</dt><dd class="text-right font-medium text-slate-900">{{ location.warehouse?.branch?.name || '—' }}</dd></div></dl></template>
        </Card>

        <Card v-if="location.description" class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Notes</span></template><template #content><p class="text-sm leading-6 text-slate-700 whitespace-pre-wrap">{{ location.description }}</p></template></Card>
      </div>
    </div>
  </div>

  <div v-else class="flex min-h-[60vh] items-center justify-center"><ProgressSpinner v-if="loading" /><Message v-else severity="error">The location could not be loaded.</Message></div>

  <Dialog v-model:visible="deleteDialog" modal header="Delete Location" :style="{ width: '450px' }">
    <p class="text-sm text-slate-700">Delete <strong>{{ location?.name }}</strong>? This cannot be undone.</p>
    <template #footer><Button label="Cancel" severity="secondary" @click="deleteDialog = false" /><Button label="Delete" severity="danger" :loading="deleteLoading" @click="deleteLocation" /></template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(true)
const deleteLoading = ref(false)
const deleteDialog = ref(false)
const productsLoading = ref(false)
const location = ref<any>(null)
const products = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()
const warehouseRoute = computed(() => route.path.startsWith('/warehouse/'))

const number = (value: any) => Number(value || 0).toLocaleString()
const label = (value: any) => String(value || '—').replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase())
const statusSeverity = (value: string) => value === 'active' ? 'success' : value === 'inactive' ? 'warn' : 'secondary'
const capacityPercent = computed(() => { const max = Number(location.value?.max_capacity_units || 0); return max ? Math.min(100, Math.round((Number(location.value?.current_stock_units || 0) / max) * 100)) : 0 })
const summary = computed(() => [
  { label: 'Capacity Used', value: `${capacityPercent.value}%` },
  { label: 'Units Stored', value: number(location.value?.current_stock_units) },
  { label: 'Capacity', value: location.value?.max_capacity_units ? number(location.value.max_capacity_units) : '—' },
  { label: 'Products', value: number(products.value.length) },
  { label: 'Type', value: label(location.value?.type) },
])
const informationFields = computed(() => [
  { label: 'Location Name', value: location.value?.name }, { label: 'Location Code', value: location.value?.location_code },
  { label: 'Type', value: label(location.value?.type) }, { label: 'Status', value: label(location.value?.status) },
  { label: 'Created', value: formatDate(location.value?.created_at) }, { label: 'Updated', value: formatDate(location.value?.updated_at) },
])
const coordinateFields = computed(() => [
  { label: 'Aisle', value: location.value?.aisle }, { label: 'Rack', value: location.value?.rack }, { label: 'Shelf', value: location.value?.shelf },
  { label: 'Bin', value: location.value?.bin }, { label: 'Level', value: location.value?.level }, { label: 'Position', value: location.value?.position },
])
const dimensionFields = computed(() => { const d = location.value?.dimensions || {}; return [
  { label: 'Length', value: d.depth ? `${d.depth} cm` : '—' }, { label: 'Width', value: d.width ? `${d.width} cm` : '—' },
  { label: 'Height', value: d.height ? `${d.height} cm` : '—' }, { label: 'Weight limit', value: location.value?.max_weight_kg ? `${number(location.value.max_weight_kg)} kg` : '—' },
] })
const formatDate = (date: string | null | undefined) => date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'

const loadLocation = async () => { loading.value = true; try { const response = await inventoryService.getLocation(route.params.id as string); if (!response.success) throw new Error(response.message); location.value = response.data; await loadProducts() } catch (error: any) { toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || error.message || 'Failed to load location', life: 3000 }) } finally { loading.value = false } }
const loadProducts = async () => { productsLoading.value = true; try { const response = await inventoryService.getProducts({ location_id: route.params.id, per_page: 100 }); const page = response.data ?? {}; const rows = Array.isArray(page) ? page : (page.data ?? []); products.value = rows.map((row: any) => { const stock = row.inventory?.[0] || {}; return { ...row, quantity: stock.quantity_available ?? 0, status: stock.quantity_available > 0 ? 'in_stock' : 'out_of_stock' } }) } catch { products.value = [] } finally { productsLoading.value = false } }
const goBack = () => router.push({ name: warehouseRoute.value ? 'warehouse.locations' : 'inventory.locations.index' })
const viewProducts = () => router.push({ name: 'inventory.products.index', query: { location_id: route.params.id } })
const viewProduct = (product: any) => router.push({ name: 'inventory.products.detail', params: { id: product.id || product.product_id } })
const confirmDelete = () => { deleteDialog.value = true }
const deleteLocation = async () => { deleteLoading.value = true; try { const response = await inventoryService.deleteLocation(route.params.id as string); if (!response.success) throw new Error(response.message); toast.add({ severity: 'success', summary: 'Deleted', detail: 'Location deleted successfully', life: 2500 }); goBack() } catch (error: any) { toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || error.message || 'Failed to delete location', life: 3000 }) } finally { deleteLoading.value = false } }
onMounted(loadLocation)
</script>
