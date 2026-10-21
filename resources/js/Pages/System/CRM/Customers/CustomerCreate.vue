<template>
  <div class="mx-auto max-w-5xl space-y-5 p-4 pb-8">
    <div class="flex items-center gap-3"><Button icon="pi pi-arrow-left" text rounded @click="router.visit('/crm/customers')"/><div><h1 class="text-xl font-semibold text-slate-900">Create Customer</h1><p class="text-sm text-slate-500">Add a retail customer or business account to this store.</p></div></div>
    <form class="grid gap-4 lg:grid-cols-3" @submit.prevent="save">
      <Card class="border shadow-sm lg:col-span-2"><template #title>Customer information</template><template #content><div class="grid gap-4 md:grid-cols-2">
        <div class="field md:col-span-2"><label>Customer type *</label><Select v-model="form.customer_type" :options="types" optionLabel="label" optionValue="value" fluid/></div>
        <div class="field"><label>{{ form.customer_type==='business'?'Contact person':'Full name' }} *</label><InputText v-model="form.full_name" fluid :invalid="!!errors.full_name"/><small v-if="errors.full_name" class="text-red-600">{{ errors.full_name }}</small></div>
        <div v-if="form.customer_type==='business'" class="field"><label>Business name *</label><InputText v-model="form.company_name" fluid :invalid="!!errors.company_name"/><small v-if="errors.company_name" class="text-red-600">{{ errors.company_name }}</small></div>
        <div class="field"><label>Email address</label><InputText v-model="form.email" type="email" fluid :invalid="!!errors.email"/></div><div class="field"><label>Phone number</label><InputText v-model="form.phone" fluid/></div>
        <div class="field md:col-span-2"><label>Address</label><Textarea v-model="form.address" rows="3" fluid/></div>
      </div></template></Card>
      <div class="space-y-4"><Card class="border shadow-sm"><template #title>Relationship details</template><template #content><div class="space-y-4"><div class="field"><label>Source</label><Select v-model="form.source" :options="sources" optionLabel="label" optionValue="value" fluid/></div><div class="field"><label>Notes</label><Textarea v-model="form.notes" rows="4" fluid/></div></div></template></Card><Message severity="info" :closable="false">The customer will be available immediately when creating wholesale quotations.</Message></div>
      <div class="flex justify-end gap-2 lg:col-span-3"><Button type="button" label="Cancel" severity="secondary" outlined @click="router.visit('/crm/customers')"/><Button type="submit" label="Save Customer" icon="pi pi-check" :loading="saving"/></div>
    </form>
  </div>
</template>
<script setup lang="ts">
import { reactive,ref } from 'vue'; import { router } from '@inertiajs/vue3'; import { useToast } from 'primevue/usetoast'; import crmService from '@/services/crm.service'; import Button from 'primevue/button'; import Card from 'primevue/card'; import InputText from 'primevue/inputtext'; import Message from 'primevue/message'; import Select from 'primevue/select'; import Textarea from 'primevue/textarea'
const toast=useToast(),saving=ref(false),errors=reactive<Record<string,string>>({}),types=[{label:'Individual / Retail',value:'individual'},{label:'Business / Wholesale',value:'business'}],sources=['walk_in','website','social','referral','phone','other'].map(value=>({label:value.replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase()),value})),form=reactive<any>({customer_type:'individual',full_name:'',company_name:'',email:'',phone:'',address:'',source:'walk_in',notes:''})
const save=async()=>{Object.keys(errors).forEach(k=>delete errors[k]);saving.value=true;try{const r=await crmService.createCustomer({...form,company_name:form.customer_type==='business'?form.company_name:null});toast.add({severity:'success',summary:'Customer created',detail:'The customer is ready for CRM and wholesale transactions.',life:2500});router.visit(`/crm/customers/${r.data.id}`)}catch(e:any){const fields=e.response?.data?.errors||{};Object.entries(fields).forEach(([k,v])=>errors[k]=Array.isArray(v)?String(v[0]):String(v));toast.add({severity:'error',summary:'Could not create customer',detail:Object.values(errors)[0]||e.response?.data?.message||'Check the form and try again.',life:3500})}finally{saving.value=false}}
</script>
<style scoped>.field{display:flex;flex-direction:column;gap:.4rem}.field label{font-size:.875rem;font-weight:600;color:#334155}</style>
