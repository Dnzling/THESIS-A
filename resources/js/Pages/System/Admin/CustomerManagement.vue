<template>
  <div class="mx-auto max-w-7xl space-y-6 pb-10">
    <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-xl sm:px-9">
      <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full bg-sky-400/20 blur-3xl"></div>
      <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="mb-3 text-xs font-bold uppercase tracking-[.22em] text-sky-300">Identity &amp; access</p><h1 class="text-3xl font-bold sm:text-4xl">Customer verification</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Monitor customer identities, contact verification, and account access from one workspace.</p></div>
        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-5 py-4"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-300 text-slate-950"><i class="pi pi-users"></i></span><div><p class="text-2xl font-bold">{{ customers.length }}</p><p class="text-xs uppercase tracking-wider text-slate-400">Customer accounts</p></div></div>
      </div>
    </section>
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <button v-for="card in cards" :key="card.value" class="rounded-2xl border bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="filter === card.value ? card.active : 'border-slate-200'" @click="filter = card.value">
        <div class="flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl" :class="card.color"><i :class="card.icon"></i></span><strong class="text-2xl text-slate-900">{{ card.count }}</strong></div><p class="mt-4 font-semibold text-slate-800">{{ card.label }}</p><p class="mt-1 text-xs text-slate-500">{{ card.help }}</p>
      </button>
    </section>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-col gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center lg:justify-between"><div><h2 class="text-lg font-bold text-slate-900">{{ activeTitle }}</h2><p class="mt-1 text-sm text-slate-500">{{ filtered.length }} customer{{ filtered.length === 1 ? '' : 's' }}</p></div><div class="flex gap-3"><IconField class="w-full sm:w-72"><InputIcon class="pi pi-search"/><InputText v-model="search" class="w-full" placeholder="Search name, email, or ID"/></IconField><Button icon="pi pi-refresh" severity="secondary" outlined :loading="loading" @click="load"/></div></div>
      <div v-if="loading" class="space-y-4 p-6"><Skeleton v-for="i in 3" :key="i" height="7rem" border-radius="1rem"/></div>
      <div v-else-if="filtered.length" class="divide-y divide-slate-100">
        <article v-for="customer in filtered" :key="customer.id" class="grid gap-5 p-5 transition hover:bg-slate-50 md:grid-cols-[minmax(0,1.4fr)_minmax(220px,.8fr)_auto] md:items-center lg:p-6">
          <div class="flex min-w-0 items-center gap-4"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 font-bold text-white">{{ initials(customer.name) }}</span><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="truncate font-bold text-slate-900">{{ customer.name }}</h3><Tag :value="customer.emailVerified ? 'Email verified' : 'Email unverified'" :severity="customer.emailVerified ? 'success' : 'warn'" rounded/></div><p class="mt-1 truncate text-sm text-slate-600">{{ customer.email }}</p><p class="mt-2 text-xs text-slate-500">{{ customer.code }} · {{ customer.phone }}</p></div></div>
          <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Account</p><p class="mt-2 font-medium text-slate-700">{{ customer.active ? 'Active access' : 'Access disabled' }}</p><p class="mt-1 text-xs text-slate-500">Joined {{ date(customer.createdAt) }}</p></div>
          <Button label="View details" icon="pi pi-arrow-right" icon-pos="right" severity="secondary" outlined @click="open(customer.id)"/>
        </article>
      </div>
      <div v-else class="px-6 py-16 text-center"><i class="pi pi-inbox text-2xl text-slate-400"></i><h3 class="mt-4 font-bold text-slate-800">No customers found</h3><p class="mt-1 text-sm text-slate-500">Try another status or search term.</p></div>
    </section>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'; import IconField from 'primevue/iconfield'; import InputIcon from 'primevue/inputicon'; import InputText from 'primevue/inputtext'; import Skeleton from 'primevue/skeleton'; import Tag from 'primevue/tag'
import axiosClient from '../../../axios'
const router = useRouter(); const loading = ref(false); const customers = ref<any[]>([]); const search = ref(''); const filter = ref('all')
const map = (u:any) => ({ id:u.id, code:u.user_id || `CUST-${String(u.id).padStart(6,'0')}`, name:u.full_name || `${u.fname || ''} ${u.lname || ''}`.trim() || 'Unknown customer', email:u.email || 'No email', phone:u.customer?.contact_number || u.phone_number || 'No phone', active:Boolean(u.is_active), emailVerified:Boolean(u.email_verified_at), createdAt:u.created_at })
const load = async () => { loading.value=true; try { const r=await axiosClient.get('/api/admin/customers',{params:{per_page:200}}); customers.value=(r.data?.data?.data || r.data?.data || []).map(map) } finally { loading.value=false } }
const cards = computed(() => [{value:'all',label:'All customers',help:'Complete customer directory',count:customers.value.length,icon:'pi pi-users',color:'bg-sky-100 text-sky-700',active:'border-sky-400 ring-2 ring-sky-100'},{value:'verified',label:'Email verified',help:'Confirmed contact identities',count:customers.value.filter(x=>x.emailVerified).length,icon:'pi pi-verified',color:'bg-emerald-100 text-emerald-700',active:'border-emerald-400 ring-2 ring-emerald-100'},{value:'unverified',label:'Email unverified',help:'Awaiting email confirmation',count:customers.value.filter(x=>!x.emailVerified).length,icon:'pi pi-exclamation-circle',color:'bg-amber-100 text-amber-700',active:'border-amber-400 ring-2 ring-amber-100'},{value:'disabled',label:'Disabled',help:'Accounts without access',count:customers.value.filter(x=>!x.active).length,icon:'pi pi-ban',color:'bg-rose-100 text-rose-700',active:'border-rose-400 ring-2 ring-rose-100'}])
const activeTitle=computed(()=>cards.value.find(x=>x.value===filter.value)?.label || 'Customers'); const filtered=computed(()=>{const q=search.value.toLowerCase().trim(); return customers.value.filter(x=>(filter.value==='all'||filter.value==='verified'&&x.emailVerified||filter.value==='unverified'&&!x.emailVerified||filter.value==='disabled'&&!x.active)&&(!q||[x.name,x.email,x.code,x.phone].some((v:string)=>v.toLowerCase().includes(q))))})
const open=(id:number)=>router.push({name:'admin.customer-management.show',params:{id}}); const initials=(v:string)=>v.split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase(); const date=(v:string)=>v?new Intl.DateTimeFormat('en-PH',{dateStyle:'medium'}).format(new Date(v)):'Not available'; onMounted(load)
</script>
