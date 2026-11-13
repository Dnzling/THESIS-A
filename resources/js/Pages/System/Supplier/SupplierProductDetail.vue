<template>
  <div class="mx-auto max-w-5xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-chevron-left" rounded outlined severity="secondary" aria-label="Go back" title="Go back" size="small" @click="goBack" />
      <div><p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Product listing</p><h1 class="mt-1 text-xl font-semibold text-slate-900">{{ product?.product_name || 'Product Details' }}</h1></div>
    </header>
    <div v-if="loading" class="space-y-3"><Skeleton height="9rem" /><Skeleton height="15rem" /></div>
    <template v-else-if="product">
      <Card class="border border-slate-200 shadow-none"><template #content><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><div><p class="text-xs text-slate-500">Category</p><p class="mt-1 font-medium text-slate-900">{{ product.category_name }}</p></div><div><p class="text-xs text-slate-500">Type</p><p class="mt-1 font-medium text-slate-900">{{ label(product.product_type) }}</p></div><div><p class="text-xs text-slate-500">Available stock</p><p class="mt-1 font-medium text-slate-900">{{ product.available_stock }} {{ product.unit_of_measurement }}</p></div><div><p class="text-xs text-slate-500">Status</p><Badge class="mt-1" :value="product.is_active ? 'Active' : 'Inactive'" :severity="product.is_active ? 'info' : 'secondary'" /></div></div><p v-if="product.description" class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600">{{ product.description }}</p></template></Card>
      <Card class="border border-slate-200 shadow-none"><template #title><div><h2 class="text-base font-semibold text-slate-900">Linked Stores</h2><p class="mt-1 text-xs font-normal text-slate-500">Stores currently able to purchase this product and their tax-inclusive cost.</p></div></template><template #content><DataTable :value="product.stores" rowHover responsiveLayout="scroll" class="text-sm"><Column field="store_name" header="Store" /><Column header="Base Cost"><template #body="{ data }">{{ money(data.base_cost) }}</template></Column><Column header="Tax"><template #body="{ data }">{{ number(data.tax_rate) }}% · {{ money(data.tax_amount) }}</template></Column><Column header="Cost Including Tax"><template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.cost_including_tax) }}</span></template></Column></DataTable></template></Card>
    </template>
  </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Skeleton from 'primevue/skeleton'
import supplierService from '../../../services/supplier.service'
const route = useRoute(); const router = useRouter(); const loading = ref(false); const product = ref<any>(null)
const load = async () => { loading.value = true; try { product.value = (await supplierService.getProductListing(String(route.params.id))).data } finally { loading.value = false } }
const goBack = () => window.history.length > 1 ? router.back() : router.push('/supplier-portal/products')
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
const label = (value: string) => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())
onMounted(load)
</script>
