<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">3D Model Requests</h1><p class="mt-1 text-sm text-slate-500">Request a model for your product and review its progress.</p><p v-if="allowance" class="mt-1 text-sm text-slate-600">{{ allowance.used }} of {{ allowance.limit }} monthly requests used</p></div><Button label="New Request" icon="pi pi-plus" :disabled="!allowance?.can_request" @click="router.push({ name: 'merchandising.3d-requests.create' })" /></div>
    <div v-if="loading" class="rounded-3xl bg-white p-8 text-sm text-slate-500">Loading requests...</div>
    <div v-else-if="!requests.length" class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">No 3D model requests yet.</div>
    <Card v-for="request in requests" :key="request.id" class="rounded-3xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs uppercase tracking-wide text-slate-500">{{ request.reference_number || `Request #${request.id}` }}</p><h2 class="mt-1 font-semibold text-slate-900">{{ request.product?.product_name || 'Product' }}</h2><p class="text-sm text-slate-500">{{ request.product?.sku || '-' }}</p></div><Tag :value="statusLabel(request.status)" :severity="severity(request.status)" /></div>
        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2"><p>Materials: {{ request.materials || '—' }}</p><p class="sm:col-span-2">{{ request.notes || 'No notes' }}</p></div>
        <div v-if="request.revision_reason" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm"><p class="font-semibold text-amber-900">Revision requested</p><p class="mt-1 whitespace-pre-wrap text-amber-800">{{ request.revision_reason }}</p></div>
        <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <Button v-if="request.model_path" label="View 3D Model" icon="pi pi-eye" outlined @click="previewing = request" />
          <Button v-if="request.status === 'published' && request.model_path" label="Download GLB" icon="pi pi-download" outlined :loading="downloading === request.id" @click="download(request)" />
          <Button v-if="request.status === 'ready_for_review'" label="Request Revision" outlined @click="openRevision(request)" />
          <Button v-if="request.status === 'ready_for_review'" label="Approve & Publish" icon="pi pi-check" severity="success" :loading="acting === request.id" @click="act(request, 'approve')" />
          <Button v-if="request.status === 'published'" label="View Product" icon="pi pi-arrow-right" outlined @click="router.push({ name: 'merchandising.products.view', params: { id: request.product_id } })" />
        </div>
      </template>
    </Card>
    <Dialog v-model:visible="revisionVisible" modal header="Request a Revision" class="w-full max-w-lg">
      <div class="space-y-2"><label for="revision-reason" class="block text-sm font-medium text-slate-700">What needs to be revised? *</label><Textarea id="revision-reason" v-model="revisionReason" rows="5" maxlength="3000" fluid placeholder="Describe the changes needed in the 3D model" /><small v-if="revisionError" class="text-red-600">{{ revisionError }}</small></div>
      <template #footer><Button label="Cancel" outlined severity="secondary" @click="revisionVisible = false" /><Button label="Submit Revision" :loading="acting === revising?.id" @click="submitRevision" /></template>
    </Dialog>
    <Dialog v-model:visible="previewVisible" modal :header="previewing?.reference_number || '3D Model Preview'" class="w-full max-w-4xl" @hide="previewing = null">
      <div v-if="previewing?.model_path" class="space-y-3"><Model3DPreview :model-url="fileUrl(previewing.model_path)" model-format="glb" height="min(65vh, 520px)" /><p class="text-sm text-slate-500">{{ previewing.product?.product_name }} · {{ statusLabel(previewing.status) }}</p></div>
      <template #footer><Button label="Close" outlined @click="previewVisible = false" /><Button v-if="previewing?.status === 'published'" label="Download GLB" icon="pi pi-download" :loading="downloading === previewing?.id" @click="download(previewing)" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'
import axiosClient from '@/axios'

const router = useRouter(), toast = useToast()
const requests = ref<any[]>([])
const allowance = ref<{ limit: number; used: number; remaining: number; can_request: boolean } | null>(null)
const loading = ref(false), acting = ref<number | null>(null)
const previewing = ref<any>(null), downloading = ref<number | null>(null)
const revising = ref<any>(null), revisionVisible = ref(false), revisionReason = ref(''), revisionError = ref('')
const previewVisible = computed({ get: () => Boolean(previewing.value), set: value => { if (!value) previewing.value = null } })
const statusLabel = (status: string) => String(status || '').replace(/_/g, ' ').replace(/\b\w/g, x => x.toUpperCase())
const severity = (status: string) => status === 'published' ? 'success' : status === 'ready_for_review' ? 'info' : status === 'changes_requested' ? 'warn' : 'secondary'
const fileUrl = (path: string) => `/storage/${path}`
const load = async () => { loading.value = true; try { const res = await axiosClient.get('/api/3d-model-requests'); requests.value = res.data?.data?.data || []; allowance.value = res.data?.monthly_allowance || null } catch (e: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: e.response?.data?.message || 'Could not load requests.', life: 3500 }) } finally { loading.value = false } }
const act = async (request: any, action: string) => { acting.value = request.id; try { await axiosClient.post(`/api/3d-model-requests/${request.id}/store-action`, { action }); await load() } catch (e: any) { toast.add({ severity: 'error', summary: 'Action Failed', detail: e.response?.data?.message || 'Could not update request.', life: 3500 }) } finally { acting.value = null } }
const openRevision = (request: any) => { revising.value = request; revisionReason.value = ''; revisionError.value = ''; revisionVisible.value = true }
const submitRevision = async () => { if (!revising.value) return; const reason = revisionReason.value.trim(); if (!reason) { revisionError.value = 'Describe the changes needed.'; return } revisionError.value = ''; acting.value = revising.value.id; try { await axiosClient.post(`/api/3d-model-requests/${revising.value.id}/store-action`, { action: 'request_revision', revision_reason: reason }); revisionVisible.value = false; await load() } catch (e: any) { revisionError.value = e.response?.data?.errors?.revision_reason?.[0] || e.response?.data?.message || 'Could not request a revision.' } finally { acting.value = null } }
const download = async (request: any) => { if (request.status !== 'published') return; downloading.value = request.id; try { const response = await axiosClient.get(`/api/3d-model-requests/${request.id}/download`, { responseType: 'blob' }); const url = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = url; link.download = `${request.reference_number || '3d-model'}.glb`; link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000) } catch { toast.add({ severity: 'error', summary: 'Download Failed', detail: 'Could not download the approved model.', life: 3500 }) } finally { downloading.value = null } }
onMounted(load)
</script>
