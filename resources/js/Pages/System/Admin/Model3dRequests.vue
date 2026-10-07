<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">3D Model Service Requests</h1><p class="mt-1 text-sm text-slate-500">Review store submissions and deliver models for approval.</p></div><Button label="Refresh" icon="pi pi-refresh" outlined @click="load" /></div>
    <Card class="rounded-3xl border border-slate-200 shadow-sm"><template #content>
      <DataTable :value="requests" :loading="loading" dataKey="id" stripedRows paginator :rows="15">
        <template #empty><div class="py-8 text-center text-slate-500">No requests received.</div></template>
        <Column header="Reference"><template #body="{ data }"><span class="font-semibold">{{ data.reference_number || `#${data.id}` }}</span></template></Column>
        <Column header="Store"><template #body="{ data }">{{ data.store?.name || '-' }}</template></Column>
        <Column header="Product"><template #body="{ data }">{{ data.product?.product_name || '-' }}</template></Column>
        <Column header="Status"><template #body="{ data }"><Tag :value="label(data.status)" :severity="severity(data.status)" /></template></Column>
        <Column header="Action"><template #body="{ data }"><div class="flex gap-1"><Button label="Review" icon="pi pi-eye" text @click="selectRequest(data.id)" /><Button v-if="data.model_path" label="View 3D" icon="pi pi-box" text @click="previewing = data" /></div></template></Column>
      </DataTable>
    </template></Card>
    <Dialog v-model:visible="visible" modal :header="selected?.reference_number || '3D Model Request'" class="w-full max-w-3xl">
      <div v-if="selected" class="space-y-5 text-sm">
        <div class="grid gap-3 sm:grid-cols-2"><p><span class="text-slate-500">Store:</span> {{ selected.store?.name }}</p><p><span class="text-slate-500">Product:</span> {{ selected.product?.product_name }}</p><p><span class="text-slate-500">Materials:</span> {{ selected.materials || '—' }}</p><p><span class="text-slate-500">Status:</span> {{ label(selected.status) }}</p><p class="sm:col-span-2"><span class="text-slate-500">Dimensions:</span> {{ selected.length_cm }} × {{ selected.width_cm }} × {{ selected.height_cm }} cm</p><p class="sm:col-span-2"><span class="text-slate-500">Notes:</span> {{ selected.notes || '—' }}</p></div>
        <div v-if="selected.revision_reason" class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="font-semibold text-amber-900">Revision requested by store</p><p class="mt-1 whitespace-pre-wrap text-amber-800">{{ selected.revision_reason }}</p></div>
        <div><p class="mb-2 font-semibold">Reference photos</p><div class="flex flex-wrap gap-2"><a v-for="path in selected.reference_photos || []" :key="path" :href="fileUrl(path)" target="_blank" rel="noopener"><img :src="fileUrl(path)" alt="Product reference" class="h-24 w-24 rounded-xl border object-cover" /></a></div></div>
        <div v-if="['in_production', 'changes_requested'].includes(selected.status)"><label class="mb-1 block">Finished GLB model</label><FileUpload mode="basic" accept=".glb,model/gltf-binary,application/octet-stream" :maxFileSize="52428800" :customUpload="true" :auto="false" chooseLabel="Select GLB" @select="modelFile = $event.files?.[0] || null" /><p v-if="modelFile" class="mt-2 text-xs text-slate-600">Selected: {{ modelFile.name }}</p><p v-if="uploadError" class="mt-2 text-xs text-red-600">{{ uploadError }}</p></div>
        <div v-if="selected.model_path"><Button label="View 3D Model" icon="pi pi-eye" outlined @click="visible = false; previewing = selected" /></div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <Button label="Close" outlined @click="visible = false" />
          <Button v-if="['submitted', 'quoted', 'accepted', 'changes_requested'].includes(selected.status)" label="Start Production" icon="pi pi-play" :loading="saving" @click="act('start')" />
          <Button v-if="['in_production', 'changes_requested'].includes(selected.status)" label="Submit for Review" icon="pi pi-upload" :disabled="!modelFile" :loading="saving" @click="act('upload_model')" />
        </div>
      </div>
    </Dialog>
    <Dialog v-model:visible="previewVisible" modal :header="previewing?.reference_number || '3D Model Preview'" class="w-full max-w-4xl" @hide="previewing = null">
      <div v-if="previewing?.model_path" class="space-y-3"><Model3DPreview :model-url="fileUrl(previewing.model_path)" model-format="glb" height="min(65vh, 520px)" /><p class="text-sm text-slate-500">{{ previewing.product?.product_name }} · {{ label(previewing.status) }}</p></div>
      <template #footer><Button label="Close" outlined @click="previewVisible = false" /><Button v-if="previewing?.status === 'published'" label="Download GLB" icon="pi pi-download" :loading="downloading" @click="download(previewing)" /></template>
    </Dialog>
  </div>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import axiosClient from '@/axios'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
const toast = useToast()
const requests = ref<any[]>([]), selected = ref<any>(null), visible = ref(false), loading = ref(false), saving = ref(false), modelFile = ref<File | null>(null)
const uploadError = ref('')
const previewing = ref<any>(null), downloading = ref(false)
const previewVisible = computed({ get: () => Boolean(previewing.value), set: value => { if (!value) previewing.value = null } })
const label = (value: string) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, x => x.toUpperCase())
const severity = (value: string) => value === 'published' ? 'success' : value === 'ready_for_review' ? 'info' : value === 'changes_requested' ? 'warn' : 'secondary'
const fileUrl = (path: string) => `/storage/${path}`
const load = async () => { loading.value = true; try { const response = await axiosClient.get('/api/3d-model-requests'); requests.value = response.data?.data?.data || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error.response?.data?.message || 'Could not load requests.', life: 3500 }) } finally { loading.value = false } }
const selectRequest = async (id: number) => { try { const response = await axiosClient.get(`/api/3d-model-requests/${id}`); selected.value = response.data?.data; modelFile.value = null; uploadError.value = ''; visible.value = true } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error.response?.data?.message || 'Could not open request.', life: 3500 }) } }
const finish = async () => { await load(); if (selected.value?.id) await selectRequest(selected.value.id) }
const act = async (action: string) => { if (!selected.value) return; uploadError.value = ''; saving.value = true; try { const payload = new FormData(); payload.append('action', action); if (modelFile.value) payload.append('model', modelFile.value); await axiosClient.post(`/api/3d-model-requests/${selected.value.id}/admin-action`, payload); await finish() } catch (error: any) { uploadError.value = error.response?.data?.errors?.model?.[0] || ''; toast.add({ severity: 'error', summary: 'Action Failed', detail: uploadError.value || error.response?.data?.message || 'Could not update request.', life: 3500 }) } finally { saving.value = false } }
const download = async (request: any) => { if (request.status !== 'published') return; downloading.value = true; try { const response = await axiosClient.get(`/api/3d-model-requests/${request.id}/download`, { responseType: 'blob' }); const url = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = url; link.download = `${request.reference_number || '3d-model'}.glb`; link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000) } catch { toast.add({ severity: 'error', summary: 'Download Failed', detail: 'Could not download the approved model.', life: 3500 }) } finally { downloading.value = false } }
void load()
</script>
