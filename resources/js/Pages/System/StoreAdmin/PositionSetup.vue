<template>
  <div class="mx-auto max-w-5xl space-y-6 p-4 text-slate-900 sm:p-8">
    <header><p class="text-sm font-semibold uppercase tracking-wider text-orange-600">Store setup · Step {{ step }} of 4</p><h1 class="mt-1 text-2xl font-semibold">{{ stepTitle }}</h1><p class="mt-2 text-sm text-slate-600">Complete these basics before opening your store workspace.</p></header>
    <ProgressBar :value="step * 25" :showValue="false" />
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <section v-if="step === 1" class="space-y-4">
      <p class="text-sm text-slate-600">Choose positions for your team. We’ll create them with permissions included in your plan.</p>
      <div v-if="loading" class="rounded-xl border bg-white p-8 text-sm text-slate-500">Loading positions…</div>
      <div v-else class="space-y-4">
        <div v-for="(items, module) in grouped" :key="module" class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-base font-semibold capitalize">{{ String(module).replace(/_/g, ' ') }}</h2>
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><label v-for="position in items" :key="position.key" class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 hover:border-orange-300"><Checkbox v-model="selected" :value="position.key" /><span><span class="block text-sm font-semibold">{{ position.name }}</span><span class="text-xs text-slate-500">{{ position.department }}</span><details class="mt-2 text-xs text-slate-600"><summary class="cursor-pointer text-orange-600">Review permissions</summary><ul class="mt-2 max-h-32 list-inside list-disc overflow-y-auto"><li v-for="permission in position.permissions" :key="permission">{{ permission }}</li></ul></details></span></label></div>
        </div>
      </div>
    </section>
    <section v-else-if="step === 2" class="rounded-xl border border-slate-200 bg-white p-5">
      <p class="mb-4 text-sm text-slate-600">Enable each operating day and set its hours. Enabled days start at 8:00 AM–5:00 PM.</p>
      <div class="space-y-3"><div v-for="day in dayOptions" :key="day.value" class="grid items-center gap-3 rounded-lg border border-slate-100 p-3 sm:grid-cols-[1fr_1fr_1fr]"><div class="flex items-center gap-3"><Checkbox v-model="operating[day.value].enabled" binary /><span class="text-sm font-medium">{{ day.label }}</span></div><label class="text-xs text-slate-500">Opens <DatePicker v-model="operating[day.value].opens" timeOnly hourFormat="12" :disabled="!operating[day.value].enabled" class="mt-1 w-full" /></label><label class="text-xs text-slate-500">Closes <DatePicker v-model="operating[day.value].closes" timeOnly hourFormat="12" :disabled="!operating[day.value].enabled" class="mt-1 w-full" /></label></div></div>
    </section>
    <section v-else-if="step === 3 || step === 4" class="grid gap-4 md:grid-cols-2">
      <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-5"><div><h2 class="text-lg font-semibold">{{ fulfillmentKey === 'pickup' ? 'Pickup' : 'Delivery' }} availability</h2><p class="text-sm text-slate-600">Choose available days and the service window.</p></div><div class="flex flex-wrap gap-3"><label v-for="day in dayOptions" :key="day.value" class="flex items-center gap-2 text-sm"><Checkbox v-model="fulfillment[fulfillmentKey].days" :value="day.value" /><span>{{ day.label.slice(0, 3) }}</span></label></div><label class="block text-xs text-slate-500">Starts <DatePicker v-model="fulfillment[fulfillmentKey].start" timeOnly hourFormat="12" class="mt-1 w-full" /></label><label class="block text-xs text-slate-500">Ends <DatePicker v-model="fulfillment[fulfillmentKey].end" timeOnly hourFormat="12" class="mt-1 w-full" /></label><label class="block text-xs text-slate-500">Lead time (days)<InputNumber v-model="fulfillment[fulfillmentKey].lead_days" :min="0" :max="14" showButtons class="mt-1 w-full" /></label></div>
      <aside class="rounded-xl bg-orange-50 p-5 text-sm text-orange-900"><i class="pi pi-info-circle mr-2"></i>Customers will only see dates and service times that match the availability you configure here.</aside>
    </section>
    <footer class="flex flex-wrap items-center justify-between gap-3"><span class="text-sm text-slate-500">{{ step === 1 ? `${selected.length} positions selected` : 'Your setup is saved together at the end.' }}</span><div class="flex gap-2"><Button v-if="step > 1" label="Back" severity="secondary" outlined @click="step--" /><Button v-if="step < 4" label="Continue" severity="warn" :disabled="step === 1 && loading" @click="continueStep" /><Button v-else label="Finish setup" severity="warn" :loading="saving" @click="save" /></div></footer>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import ProgressBar from 'primevue/progressbar'
import axiosClient from '@/axios'

type Day = 'monday'|'tuesday'|'wednesday'|'thursday'|'friday'|'saturday'|'sunday'
type Suggestion = { key: string; module: string; name: string; department: string; permissions: string[]; recommended: boolean }
const dayOptions: {label:string;value:Day}[] = [{label:'Monday',value:'monday'},{label:'Tuesday',value:'tuesday'},{label:'Wednesday',value:'wednesday'},{label:'Thursday',value:'thursday'},{label:'Friday',value:'friday'},{label:'Saturday',value:'saturday'},{label:'Sunday',value:'sunday'}]
const asTime = (time: string) => { const [h,m] = time.split(':').map(Number); const d = new Date(); d.setHours(h,m,0,0); return d }
const toTime = (value: Date) => `${String(value.getHours()).padStart(2,'0')}:${String(value.getMinutes()).padStart(2,'0')}`
const step = ref(1), suggestions = ref<Suggestion[]>([]), selected = ref<string[]>([]), loading = ref(true), saving = ref(false), error = ref('')
const operating = reactive(Object.fromEntries(dayOptions.map(day => [day.value,{enabled:day.value!=='sunday',opens:asTime('08:00'),closes:asTime('17:00')}])) as Record<Day,{enabled:boolean;opens:Date;closes:Date}>)
const fulfillment = reactive({pickup:{days:['monday','tuesday','wednesday','thursday','friday','saturday'] as Day[],start:asTime('09:00'),end:asTime('18:00'),lead_days:1},delivery:{days:['monday','tuesday','wednesday','thursday','friday','saturday'] as Day[],start:asTime('08:00'),end:asTime('20:00'),lead_days:2}})
const grouped = computed(() => suggestions.value.reduce<Record<string,Suggestion[]>>((groups,item) => { (groups[item.module] ||= []).push(item); return groups },{}))
const fulfillmentKey = computed(() => step.value === 3 ? 'pickup' : 'delivery')
const stepTitle = computed(() => ['','Choose Positions','Set Operating Hours','Set Pickup Window','Set Delivery Window'][step.value])
onMounted(async () => { try { const response=await axiosClient.get('/api/store/position-setup'); if(!response.data?.required){router.visit('/store/index');return} suggestions.value=response.data?.suggestions||[]; selected.value=suggestions.value.filter(item=>item.recommended).map(item=>item.key) } catch(cause:any) { error.value=cause?.response?.data?.message||'Unable to load setup.' } finally { loading.value=false } })
function continueStep(){ error.value=''; if(step.value===2&&!dayOptions.some(day=>operating[day.value].enabled)){error.value='Enable at least one operating day.';return} if(step.value===3&&!fulfillment.pickup.days.length){error.value='Choose at least one pickup day.';return} step.value++ }
async function save(){ error.value=''; if(!fulfillment.delivery.days.length){error.value='Choose at least one delivery day.';return} const hours=dayOptions.filter(day=>operating[day.value].enabled).map(day=>({day:day.value,opens_at:toTime(operating[day.value].opens),closes_at:toTime(operating[day.value].closes)})); if(hours.some(row=>row.closes_at<=row.opens_at)){error.value='Each closing time must be later than its opening time.';return} if([fulfillment.pickup,fulfillment.delivery].some(w=>toTime(w.end)<=toTime(w.start))){error.value='Each fulfillment end time must be later than its start time.';return} saving.value=true; try { await axiosClient.post('/api/store/position-setup',{positions:selected.value,operating_hours:hours,fulfillment_windows:{pickup:{...fulfillment.pickup,start:toTime(fulfillment.pickup.start),end:toTime(fulfillment.pickup.end)},delivery:{...fulfillment.delivery,start:toTime(fulfillment.delivery.start),end:toTime(fulfillment.delivery.end)}}}); router.visit('/store/index') } catch(cause:any){error.value=cause?.response?.data?.message||'Unable to save store setup.'} finally{saving.value=false} }
</script>
