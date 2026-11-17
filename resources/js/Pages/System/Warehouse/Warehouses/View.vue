<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6" v-if="warehouse">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3"><div><Button label="Back to Warehouses" icon="pi pi-arrow-left" text size="small" @click="router.visit('/warehouse/warehouses')"/><h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ warehouse.name }}</h1><p class="text-sm text-slate-500">{{ warehouse.warehouse_code }} · {{ warehouse.branch?.name || warehouse.branch?.branch_name || 'Unassigned branch' }}</p></div><Badge :value="label(warehouse.status)" :severity="warehouse.status === 'active' ? 'success' : 'secondary'" /></div>
    <Message v-if="result.uses_branch_fallback" severity="warn" :closable="false" class="mb-5">This facility is showing legacy branch stock that has not yet been assigned a warehouse section.</Message>
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5"><Card v-for="item in summaryCards" :key="item.label" class="border border-slate-200 shadow-sm"><template #content><p class="text-xs uppercase tracking-wide text-slate-500">{{ item.label }}</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ item.value }}</p></template></Card></div>
    <div class="grid gap-6 lg:grid-cols-3">
      <Card class="border border-slate-200 shadow-sm lg:col-span-2"><template #title><span class="text-base">Stock in this warehouse</span></template><template #content>
        <DataTable :value="result.stock || []" stripedRows rowHover size="small" paginator :rows="10" responsiveLayout="scroll">
          <template #empty><div class="py-10 text-center text-slate-500">No stock has been assigned to this warehouse.</div></template>
          <Column header="SKU"><template #body="{data}">{{ data.variation?.variation_sku || data.product?.sku || '—' }}</template></Column>
          <Column header="Product"><template #body="{data}"><div class="font-medium">{{ data.product?.product_name || '—' }}</div><small v-if="data.variation" class="text-slate-500">{{ data.variation.variation_name }}</small></template></Column>
          <Column field="quantity_on_hand" header="On Hand" />
          <Column field="quantity_available" header="Available" />
          <Column header="Location"><template #body="{data}">{{ [data.aisle,data.rack,data.shelf,data.bin_code].filter(Boolean).join(' / ') || '—' }}</template></Column>
          <Column header="Status"><template #body="{data}"><Badge :value="label(data.stock_status)" :severity="stockSeverity(data.stock_status)" /></template></Column>
        </DataTable>
      </template></Card>
      <div class="space-y-6">
        <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Warehouse information</span></template><template #content><dl class="space-y-4 text-sm"><div v-for="d in details" :key="d.label" class="flex justify-between gap-4 border-b border-slate-100 pb-3"><dt class="text-slate-500">{{ d.label }}</dt><dd class="text-right font-medium text-slate-800">{{ d.value || '—' }}</dd></div></dl></template></Card>
        <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Storage locations</span></template><template #content><div v-if="warehouse.locations?.length" class="grid gap-2"><div v-for="loc in warehouse.locations" :key="loc.id" class="rounded-lg border border-slate-200 p-3"><p class="font-medium text-slate-800">{{ loc.location_code || loc.name || `Location ${loc.id}` }}</p><small class="text-slate-500">{{ [loc.aisle,loc.rack,loc.shelf,loc.bin_code].filter(Boolean).join(' / ') || 'Storage location' }}</small></div></div><p v-else class="text-sm text-slate-500">No storage locations configured.</p></template></Card>
      </div>
    </div>
  </div>
  <div v-else class="flex min-h-[60vh] items-center justify-center"><ProgressSpinner v-if="loading"/><Message v-else severity="error">Warehouse could not be loaded.</Message></div>
</template>
<script setup lang="ts">
import { computed,onMounted,ref } from 'vue'; import { router,usePage } from '@inertiajs/vue3'; import WarehouseService from '@/services/warehouse.service'
const page=usePage(), loading=ref(true), result=ref<any>({}), warehouse=computed(()=>result.value.warehouse); const id=computed(()=>String(page.url).match(/warehouses\/(\d+)/)?.[1]||'')
const label=(v:string)=>(v||'—').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase()); const num=(v:any)=>Number(v||0).toLocaleString(); const stockSeverity=(v:string)=>v==='out_of_stock'?'danger':v==='low_stock'?'warn':'success'
const summaryCards=computed(()=>[{label:'Total SKUs',value:num(result.value.summary?.total_skus)},{label:'On Hand',value:num(result.value.summary?.quantity_on_hand)},{label:'Available',value:num(result.value.summary?.available)},{label:'Reserved',value:num(result.value.summary?.reserved)},{label:'Capacity Used',value:`${result.value.summary?.capacity_utilization||0}%`}])
const details=computed(()=>[{label:'Type',value:label(warehouse.value?.type)},{label:'Manager',value:warehouse.value?.manager_name},{label:'Phone',value:warehouse.value?.phone||warehouse.value?.manager_phone},{label:'Area',value:warehouse.value?.total_area_sqm?`${warehouse.value.total_area_sqm} m²`:null},{label:'Capacity',value:warehouse.value?.max_capacity_units?`${num(warehouse.value.max_capacity_units)} units`:null},{label:'Address',value:[warehouse.value?.address_line_1,warehouse.value?.city,warehouse.value?.state].filter(Boolean).join(', ')}])
onMounted(async()=>{try{result.value=await WarehouseService.warehouse(id.value)}finally{loading.value=false}})
</script>
