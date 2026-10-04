<template>
  <div class="space-y-6 p-4 text-sm md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Cash Advances & Liquidations</h1>
        <p class="mt-1 text-sm text-gray-500">Track issued advances, expense liquidation, returned cash, and reimbursements.</p>
      </div>
      <Button v-if="canManage" label="Create Cash Advance" icon="pi pi-plus" size="small" @click="router.push({ name: 'finance.liquidations.create' })" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card v-for="card in summary" :key="card.label" class="rounded-2xl border border-gray-100 shadow-sm">
        <template #content><div class="p-5"><div class="flex items-center justify-between"><span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ card.label }}</span><i :class="[card.icon, card.color]"></i></div><p class="mt-3 text-2xl font-semibold text-gray-900">{{ card.money ? money(card.value) : card.value }}</p></div></template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-slate-200/70 shadow-sm">
      <template #header>
        <div class="m-4 mt-6 grid grid-cols-1 gap-4 md:grid-cols-4">
          <div class="space-y-2 md:col-span-2"><label class="text-xs font-medium uppercase tracking-wider text-gray-500">Search</label><InputText v-model="filters.search" placeholder="Advance number, purpose, requester" size="small" fluid @keyup.enter="load" /></div>
          <div class="space-y-2"><label class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</label><Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value" size="small" fluid @change="load" /></div>
          <div class="flex items-end"><Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" fluid :loading="loading" @click="load" /></div>
        </div>
      </template>
      <template #content>
        <DataTable :value="rows" :loading="loading" rowHover responsiveLayout="scroll" class="p-datatable-sm m-4 text-xs" paginator :rows="15" :rowsPerPageOptions="[15,20,50]">
          <Column header="Date"><template #body="{ data }">{{ date(data.created_at) }}</template></Column>
          <Column header="Advance No."><template #body="{ data }"><button class="font-medium text-blue-600 hover:underline" @click="open(data)">{{ data.advance_number }}</button></template></Column>
          <Column field="purpose" header="Purpose" style="min-width:220px" />
          <Column header="Requested By"><template #body="{ data }">{{ person(data.requester) }}</template></Column>
          <Column header="Advance"><template #body="{ data }"><span class="font-semibold">{{ money(data.advance_amount) }}</span></template></Column>
          <Column header="Liquidated"><template #body="{ data }">{{ money(data.liquidated_amount) }}</template></Column>
          <Column header="Status"><template #body="{ data }"><Badge :value="label(data.status)" :severity="severity(data.status)" /></template></Column>
          <Column header="Action"><template #body="{ data }"><Button icon="pi pi-eye" label="View" outlined rounded size="small" @click="open(data)" /></template></Column>
          <template #empty><div class="py-12 text-center text-gray-500">No cash advances or liquidations found.</div></template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import financeService from '@/services/finance.service'
import Button from 'primevue/button'; import Card from 'primevue/card'; import InputText from 'primevue/inputtext'; import Select from 'primevue/select'; import DataTable from 'primevue/datatable'; import Column from 'primevue/column'; import Badge from 'primevue/badge'
const router=useRouter(), toast=useToast(), auth=useAuthStore(); const loading=ref(false), rows=ref<any[]>([]); const canManage=computed(()=>auth.hasPermission('finance.liquidations.manage')); const filters=reactive({search:'',status:''})
const statuses=[{label:'All Statuses',value:''},{label:'Pending Approval',value:'pending_approval'},{label:'Approved',value:'approved'},{label:'Released',value:'released'},{label:'Liquidation Submitted',value:'liquidation_submitted'},{label:'Settled',value:'settled'},{label:'Rejected',value:'rejected'}]
const money=(v:any)=>new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'}).format(Number(v||0)); const date=(v:any)=>v?new Date(v).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}):'—'; const label=(v:any)=>String(v||'').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase()); const person=(u:any)=>u?[u.fname,u.lname].filter(Boolean).join(' '):'—'; const severity=(s:string)=>s==='settled'?'success':s==='rejected'?'danger':s==='liquidation_submitted'?'info':['approved','released'].includes(s)?'secondary':'warn'
const summary=computed(()=>[{label:'Pending Approval',value:rows.value.filter(x=>x.status==='pending_approval').length,icon:'pi pi-clock',color:'text-orange-500'},{label:'Released',value:rows.value.filter(x=>x.status==='released').length,icon:'pi pi-wallet',color:'text-blue-500'},{label:'For Settlement',value:rows.value.filter(x=>x.status==='liquidation_submitted').length,icon:'pi pi-receipt',color:'text-purple-500'},{label:'Total Advances',value:rows.value.reduce((s,x)=>s+Number(x.advance_amount||0),0),money:true,icon:'pi pi-money-bill',color:'text-emerald-500'}])
const load=async()=>{loading.value=true;try{const r=await financeService.getLiquidations({...filters,per_page:100});rows.value=r?.data?.data||[]}catch(e:any){toast.add({severity:'error',summary:'Load Failed',detail:e?.response?.data?.message||'Unable to load liquidations.',life:3000})}finally{loading.value=false}}; const open=(row:any)=>router.push({name:'finance.liquidations.detail',params:{id:row.id}}); onMounted(load)
</script>
