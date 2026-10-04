<template>
  <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">
    <div class="flex items-center gap-3"><Button icon="pi pi-arrow-left" text rounded @click="router.push({name:'finance.liquidations'})"/><div><h1 class="text-xl font-semibold text-gray-900">Create Cash Advance</h1><p class="text-sm text-gray-500">Request funds before the business expense is incurred.</p></div></div>
    <form @submit.prevent="confirmSubmit">
      <Card class="rounded-2xl border border-gray-100 shadow-sm"><template #title><span class="text-base">Advance Request</span></template><template #content>
        <div class="grid gap-5 p-1 md:grid-cols-2">
          <div class="space-y-2 md:col-span-2"><label class="text-sm font-medium text-gray-700">Purpose *</label><InputText v-model="form.purpose" fluid placeholder="Purpose of the cash advance"/><small v-if="errors.purpose" class="text-red-500">{{ errors.purpose }}</small></div>
          <div class="space-y-2"><label class="text-sm font-medium text-gray-700">Advance Amount *</label><InputNumber v-model="form.advance_amount" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid/></div>
          <div class="space-y-2"><label class="text-sm font-medium text-gray-700">Funds Needed On *</label><DatePicker v-model="form.needed_date" :minDate="new Date()" showIcon fluid/></div>
          <div class="space-y-2"><label class="text-sm font-medium text-gray-700">Preferred Payment Method</label><Select v-model="form.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" fluid/></div>
          <div class="space-y-2 md:col-span-2"><label class="text-sm font-medium text-gray-700">Notes</label><Textarea v-model="form.notes" rows="4" fluid autoResize/></div>
        </div>
      </template></Card>
      <div class="mt-6 flex justify-end gap-2 border-t border-gray-200 pt-5"><Button label="Cancel" outlined type="button" @click="router.push({name:'finance.liquidations'})"/><Button label="Submit Request" icon="pi pi-send" type="submit" :loading="saving"/></div>
    </form>
    <ConfirmDialog/>
  </div>
</template>
<script setup lang="ts">
import { reactive, ref } from 'vue'; import { useRouter } from 'vue-router'; import { useToast } from 'primevue/usetoast'; import { useConfirm } from 'primevue/useconfirm'; import financeService from '@/services/finance.service'; import Button from 'primevue/button'; import Card from 'primevue/card'; import InputText from 'primevue/inputtext'; import InputNumber from 'primevue/inputnumber'; import DatePicker from 'primevue/datepicker'; import Select from 'primevue/select'; import Textarea from 'primevue/textarea'; import ConfirmDialog from 'primevue/confirmdialog'
const router=useRouter(),toast=useToast(),confirm=useConfirm();const saving=ref(false),errors=reactive<any>({});const form=reactive({purpose:'',advance_amount:null as number|null,needed_date:null as Date|null,payment_method:'cash',notes:''});const paymentMethods=[{label:'Cash',value:'cash'},{label:'Bank Transfer',value:'bank_transfer'},{label:'Check',value:'check'},{label:'GCash',value:'gcash'}]
const apiDate=(d:Date)=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`
const confirmSubmit=()=>{Object.keys(errors).forEach(k=>delete errors[k]);if(!form.purpose.trim())errors.purpose='Purpose is required.';if(!form.advance_amount||form.advance_amount<=0||!form.needed_date){toast.add({severity:'warn',summary:'Incomplete',detail:'Complete all required fields.',life:2500});return}confirm.require({header:'Submit cash advance?',message:'This request will be sent to Finance for approval.',rejectProps:{label:'No',outlined:true},acceptProps:{label:'Confirm'},accept:submit})}
const submit=async()=>{saving.value=true;try{const r=await financeService.createLiquidation({...form,needed_date:apiDate(form.needed_date!)});toast.add({severity:'success',summary:'Submitted',detail:r.message,life:2500});router.push({name:'finance.liquidations.detail',params:{id:r.data.id}})}catch(e:any){Object.assign(errors,e?.response?.data?.errors||{});toast.add({severity:'error',summary:'Save Failed',detail:e?.response?.data?.message||'Unable to create request.',life:3000})}finally{saving.value=false}}
</script>
