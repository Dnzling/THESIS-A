<template>
  <div class="min-h-screen p-4 md:p-6 space-y-5">
    <div><h1 class="text-2xl font-semibold text-slate-900">Warehouses</h1><p class="text-sm text-slate-500">View every storage facility registered to this store.</p></div>
    <Card class="border border-slate-200 shadow-sm"><template #content>
      <div class="mb-5 grid gap-3 md:grid-cols-3">
        <IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.search" placeholder="Search name, code, or city" fluid @keyup.enter="load" /></IconField>
        <Select v-model="filters.type" :options="types" optionLabel="label" optionValue="value" placeholder="Warehouse type" showClear fluid @change="load" />
        <Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value" placeholder="Status" showClear fluid @change="load" />
      </div>
      <DataTable :value="rows" :loading="loading" stripedRows rowHover paginator lazy :rows="meta.per_page" :totalRecords="meta.total" @page="onPage" responsiveLayout="scroll">
        <template #empty><div class="py-12 text-center"><i class="pi pi-building text-3xl text-slate-300"/><p class="mt-3 text-slate-500">No warehouses found.</p></div></template>
        <Column header="Branch" style="min-width:170px"><template #body="{ data }">{{ data.branch?.name || data.branch?.branch_name || '—' }}</template></Column>
        <Column field="warehouse_code" header="Code" />
        <Column header="Warehouse" style="min-width:220px"><template #body="{ data }"><div class="font-medium text-slate-900">{{ data.name }}</div><small class="text-slate-500">{{ [data.city, data.state].filter(Boolean).join(', ') || 'No address' }}</small></template></Column>
        <Column header="Type"><template #body="{ data }"><Badge :value="label(data.type)" severity="secondary" /></template></Column>
        <Column field="stock_skus" header="SKUs" />
        <Column header="On Hand"><template #body="{ data }">{{ number(data.quantity_on_hand) }}</template></Column>
        <Column header="Status"><template #body="{ data }"><Badge :value="label(data.status)" :severity="data.status === 'active' ? 'success' : 'secondary'" /></template></Column>
        <Column header="Action" frozen alignFrozen="right"><template #body="{ data }"><Button icon="pi pi-eye" label="View" size="small" text @click="router.visit(`/warehouse/warehouses/${data.id}`)" /></template></Column>
      </DataTable>
    </template></Card>
  </div>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'; import { router } from '@inertiajs/vue3'; import WarehouseService from '@/services/warehouse.service'
const rows=ref<any[]>([]), loading=ref(false), meta=reactive({page:1,per_page:15,total:0}); const filters=reactive({search:'',type:null as string|null,status:null as string|null})
const types=[{label:'Main',value:'main'},{label:'Satellite',value:'satellite'},{label:'Cold Storage',value:'cold_storage'},{label:'Hazardous',value:'hazardous'},{label:'Quarantine',value:'quarantine'}]; const statuses=[{label:'Active',value:'active'},{label:'Inactive',value:'inactive'},{label:'Maintenance',value:'maintenance'},{label:'Closed',value:'closed'}]
const label=(v:string)=>(v||'—').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase()); const number=(v:any)=>Number(v||0).toLocaleString()
const load=async()=>{loading.value=true;try{const p=await WarehouseService.warehouses({...filters,page:meta.page,per_page:meta.per_page});rows.value=p.data||[];meta.total=p.total||0;meta.per_page=p.per_page||15}catch{rows.value=[]}finally{loading.value=false}}
const onPage=(e:any)=>{meta.page=e.page+1;load()}; onMounted(load)
</script>
