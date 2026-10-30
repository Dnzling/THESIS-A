<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-2xl font-semibold text-slate-900">3D Model Requests</h1><p class="mt-1 text-sm text-slate-500">Review store submissions, manage production, and deliver completed models.</p></div>
      <Button label="Refresh" icon="pi pi-refresh" size="small" severity="secondary" outlined rounded :loading="loading" @click="load" />
    </div>
    <Card class="rounded-xl border border-slate-200 shadow-sm"><template #content>
      <div class="mb-4 flex flex-wrap items-center gap-2">
        <InputText v-model="search" placeholder="Search reference, store, or product" size="small" class="w-full sm:w-72" />
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" size="small" class="w-full sm:w-48" />
        <Button v-if="hasFilters" label="Clear Filter" icon="pi pi-filter-slash" size="small" severity="danger" outlined rounded @click="clearFilters" />
      </div>
      <DataTable :value="filteredRequests" :loading="loading" dataKey="id" rowHover paginator :rows="10" :rowsPerPageOptions="[10, 15, 25]" size="small">
        <template #empty><div class="py-8 text-center text-sm text-slate-500">{{ requests.length ? 'No requests match these filters.' : 'No 3D model requests have been submitted yet.' }}</div></template>
        <Column field="created_at" header="Date" sortable style="min-width: 150px"><template #body="{ data }"><span class="whitespace-nowrap text-slate-700">{{ formatDate(data.created_at) }}</span></template></Column>
        <Column field="reference_number" header="Reference" sortable style="min-width: 160px"><template #body="{ data }"><span class="font-mono text-xs font-semibold text-slate-800">{{ data.reference_number || `#${data.id}` }}</span></template></Column>
        <Column header="Store" style="min-width: 160px"><template #body="{ data }"><span class="font-medium text-slate-800">{{ data.store?.name || '—' }}</span></template></Column>
        <Column header="Product" style="min-width: 200px"><template #body="{ data }"><div><p class="font-medium text-slate-800">{{ data.product?.product_name || '—' }}</p><p v-if="data.product?.sku" class="text-xs text-slate-500">{{ data.product.sku }}</p></div></template></Column>
        <Column field="status" header="Status" sortable style="min-width: 150px"><template #body="{ data }"><Badge :value="label(data.status)" :severity="severity(data.status)" /></template></Column>
        <Column header="Actions" style="width: 190px"><template #body="{ data }"><div class="flex items-center gap-2"><Button label="View" icon="pi pi-eye" size="small" outlined rounded @click="selectRequest(data.id)" /><Button v-if="data.model_path" v-tooltip.top="'Preview 3D model'" icon="pi pi-box" aria-label="Preview 3D model" size="small" severity="secondary" outlined rounded @click="previewing = data" /></div></template></Column>
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
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import axiosClient from '@/axios'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
const toast = useToast()
const requests = ref<any[]>([]), selected = ref<any>(null), visible = ref(false), loading = ref(false), saving = ref(false), modelFile = ref<File | null>(null)
const uploadError = ref('')
const search = ref(''), statusFilter = ref<string | null>(null)
const previewing = ref<any>(null), downloading = ref(false)
const previewVisible = computed({ get: () => Boolean(previewing.value), set: value => { if (!value) previewing.value = null } })
const statusOptions = [
  { label: 'Submitted', value: 'submitted' },
  { label: 'Quoted', value: 'quoted' },
  { label: 'Accepted', value: 'accepted' },
  { label: 'In Production', value: 'in_production' },
  { label: 'Ready for Review', value: 'ready_for_review' },
  { label: 'Changes Requested', value: 'changes_requested' },
  { label: 'Published', value: 'published' },
]
const filteredRequests = computed(() => {
  const term = search.value.trim().toLowerCase()
  return requests.value.filter(request => {
    const matchesStatus = !statusFilter.value || request.status === statusFilter.value
    const searchable = [request.reference_number, request.store?.name, request.product?.product_name, request.product?.sku]
      .filter(Boolean).join(' ').toLowerCase()
    return matchesStatus && (!term || searchable.includes(term))
  })
})
const hasFilters = computed(() => Boolean(search.value.trim() || statusFilter.value))
const clearFilters = () => { search.value = ''; statusFilter.value = null }
const label = (value: string) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, x => x.toUpperCase())
const severity = (value: string) => {
  if (value === 'published' || value === 'accepted') return 'success'
  if (value === 'in_production' || value === 'ready_for_review') return 'info'
  if (value === 'submitted' || value === 'quoted' || value === 'changes_requested') return 'warn'
  return 'secondary'
}
const formatDate = (value?: string) => value
  ? new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
  : '—'
const fileUrl = (path: string) => `/storage/${path}`
const load = async () => { loading.value = true; try { const response = await axiosClient.get('/api/3d-model-requests'); requests.value = response.data?.data?.data || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error.response?.data?.message || 'Could not load requests.', life: 3500 }) } finally { loading.value = false } }
const selectRequest = async (id: number) => { try { const response = await axiosClient.get(`/api/3d-model-requests/${id}`); selected.value = response.data?.data; modelFile.value = null; uploadError.value = ''; visible.value = true } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error.response?.data?.message || 'Could not open request.', life: 3500 }) } }
const finish = async () => { await load(); if (selected.value?.id) await selectRequest(selected.value.id) }
const act = async (action: string) => { if (!selected.value) return; uploadError.value = ''; saving.value = true; try { const payload = new FormData(); payload.append('action', action); if (modelFile.value) payload.append('model', modelFile.value); await axiosClient.post(`/api/3d-model-requests/${selected.value.id}/admin-action`, payload); await finish() } catch (error: any) { uploadError.value = error.response?.data?.errors?.model?.[0] || ''; toast.add({ severity: 'error', summary: 'Action Failed', detail: uploadError.value || error.response?.data?.message || 'Could not update request.', life: 3500 }) } finally { saving.value = false } }
const download = async (request: any) => { if (request.status !== 'published') return; downloading.value = true; try { const response = await axiosClient.get(`/api/3d-model-requests/${request.id}/download`, { responseType: 'blob' }); const url = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = url; link.download = `${request.reference_number || '3d-model'}.glb`; link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000) } catch { toast.add({ severity: 'error', summary: 'Download Failed', detail: 'Could not download the approved model.', life: 3500 }) } finally { downloading.value = false } }
void load()
</script>
