<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">3D Model Service Requests</h1><p class="mt-1 text-sm text-slate-500">Review store submissions, quote production, and deliver models for approval.</p></div><Button label="Refresh" icon="pi pi-refresh" outlined @click="load" /></div>
    <Card class="rounded-3xl border border-slate-200 shadow-sm"><template #content>
      <DataTable :value="requests" :loading="loading" dataKey="id" stripedRows paginator :rows="15">
        <template #empty><div class="py-8 text-center text-slate-500">No requests received.</div></template>
        <Column header="Request"><template #body="{ data }"><span class="font-semibold">#{{ data.id }}</span></template></Column>
        <Column header="Store"><template #body="{ data }">{{ data.store?.name || '-' }}</template></Column>
        <Column header="Product"><template #body="{ data }">{{ data.product?.product_name || '-' }}</template></Column>
        <Column header="Status"><template #body="{ data }"><Tag :value="label(data.status)" :severity="severity(data.status)" /></template></Column>
        <Column header="Quote"><template #body="{ data }">{{ data.quoted_price ? money(data.quoted_price) : '—' }}</template></Column>
        <Column header="Action"><template #body="{ data }"><Button label="Review" icon="pi pi-eye" text @click="selectRequest(data.id)" /></template></Column>
      </DataTable>
    </template></Card>
    <Dialog v-model:visible="visible" modal header="3D Model Request" class="w-full max-w-3xl">
      <div v-if="selected" class="space-y-5 text-sm">
        <div class="grid gap-3 sm:grid-cols-2"><p><span class="text-slate-500">Store:</span> {{ selected.store?.name }}</p><p><span class="text-slate-500">Product:</span> {{ selected.product?.product_name }}</p><p><span class="text-slate-500">Materials:</span> {{ selected.materials || '—' }}</p><p><span class="text-slate-500">Status:</span> {{ label(selected.status) }}</p><p class="sm:col-span-2"><span class="text-slate-500">Dimensions:</span> {{ selected.length_cm }} × {{ selected.width_cm }} × {{ selected.height_cm }} cm</p><p class="sm:col-span-2"><span class="text-slate-500">Notes:</span> {{ selected.notes || '—' }}</p></div>
        <div><p class="mb-2 font-semibold">Reference photos</p><div class="flex flex-wrap gap-2"><a v-for="path in selected.reference_photos || []" :key="path" :href="fileUrl(path)" target="_blank" rel="noopener"><img :src="fileUrl(path)" alt="Product reference" class="h-24 w-24 rounded-xl border object-cover" /></a></div></div>
        <div v-if="['submitted', 'quoted'].includes(selected.status)" class="grid gap-3 sm:grid-cols-2"><div><label class="mb-1 block">Quoted price (PHP)</label><InputNumber v-model="quote.price" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid /></div><div><label class="mb-1 block">Included revisions</label><InputNumber v-model="quote.revisions" :min="0" :max="20" fluid /></div><div class="sm:col-span-2"><label class="mb-1 block">Quote notes</label><Textarea v-model="quote.notes" rows="3" fluid /></div></div>
        <div v-if="['in_production', 'changes_requested'].includes(selected.status)"><label class="mb-1 block">Finished GLB model</label><FileUpload mode="basic" accept=".glb" :maxFileSize="52428800" :customUpload="true" :auto="false" chooseLabel="Select GLB" @select="modelFile = $event.files?.[0] || null" /></div>
        <div v-if="selected.model_path"><a :href="fileUrl(selected.model_path)" target="_blank" rel="noopener" class="text-blue-700 underline">Open current model</a></div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <Button label="Close" outlined @click="visible = false" />
          <Button v-if="['submitted', 'quoted'].includes(selected.status)" label="Send Quote" icon="pi pi-send" :loading="saving" @click="sendQuote" />
          <Button v-if="['accepted', 'changes_requested'].includes(selected.status)" label="Start Production" icon="pi pi-play" :loading="saving" @click="act('start')" />
          <Button v-if="['in_production', 'changes_requested'].includes(selected.status)" label="Submit for Review" icon="pi pi-upload" :disabled="!modelFile" :loading="saving" @click="act('upload_model')" />
        </div>
      </div>
    </Dialog>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload'
import axiosClient from '@/axios'
const toast = useToast()
const requests = ref<any[]>([]), selected = ref<any>(null), visible = ref(false), loading = ref(false), saving = ref(false), modelFile = ref<File | null>(null)
const quote = ref({ price: 0, revisions: 1, notes: '' })
const label = (value: string) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, x => x.toUpperCase())
const severity = (value: string) => value === 'published' ? 'success' : value === 'ready_for_review' ? 'info' : value === 'changes_requested' ? 'warn' : 'secondary'
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const fileUrl = (path: string) => `/storage/${path}`
const load = async () => { loading.value = true; try { const response = await axiosClient.get('/api/3d-model-requests'); requests.value = response.data?.data?.data || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error.response?.data?.message || 'Could not load requests.', life: 3500 }) } finally { loading.value = false } }
const selectRequest = async (id: number) => { try { const response = await axiosClient.get(`/api/3d-model-requests/${id}`); selected.value = response.data?.data; quote.value = { price: Number(selected.value?.quoted_price || 0), revisions: Number(selected.value?.included_revisions || 1), notes: selected.value?.quote_notes || '' }; modelFile.value = null; visible.value = true } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error.response?.data?.message || 'Could not open request.', life: 3500 }) } }
const finish = async () => { await load(); if (selected.value?.id) await selectRequest(selected.value.id) }
const sendQuote = async () => { if (!selected.value) return; saving.value = true; try { await axiosClient.post(`/api/3d-model-requests/${selected.value.id}/quote`, { quoted_price: quote.value.price, included_revisions: quote.value.revisions, quote_notes: quote.value.notes }); await finish() } catch (error: any) { toast.add({ severity: 'error', summary: 'Quote Failed', detail: error.response?.data?.message || 'Could not send quote.', life: 3500 }) } finally { saving.value = false } }
const act = async (action: string) => { if (!selected.value) return; saving.value = true; try { const payload = new FormData(); payload.append('action', action); if (modelFile.value) payload.append('model', modelFile.value); await axiosClient.post(`/api/3d-model-requests/${selected.value.id}/admin-action`, payload); await finish() } catch (error: any) { toast.add({ severity: 'error', summary: 'Action Failed', detail: error.response?.data?.message || 'Could not update request.', life: 3500 }) } finally { saving.value = false } }
void load()
</script>
