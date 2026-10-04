<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">3D Model Requests</h1><p class="mt-1 text-sm text-slate-500">Request a model for your product and review its progress.</p></div><Button label="New Request" icon="pi pi-plus" @click="openCreate" /></div>
    <Card v-if="creating" class="rounded-3xl border border-slate-200 shadow-sm">
      <template #title>Request a 3D Model</template>
      <template #content>
        <form class="space-y-4" @submit.prevent="submit">
          <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm text-slate-600">Product *</label><Select v-model="form.product_id" :options="products" optionLabel="product_name" optionValue="id" filter fluid placeholder="Select product" /></div>
            <div><label class="mb-1 block text-sm text-slate-600">Materials and finish</label><InputText v-model="form.materials" fluid placeholder="e.g. oak frame, beige fabric" /></div>
          </div>
          <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="mb-1 block text-sm text-slate-600">Length (cm) *</label><InputNumber v-model="form.length_cm" :min="0.01" :maxFractionDigits="2" fluid /></div>
            <div><label class="mb-1 block text-sm text-slate-600">Width (cm) *</label><InputNumber v-model="form.width_cm" :min="0.01" :maxFractionDigits="2" fluid /></div>
            <div><label class="mb-1 block text-sm text-slate-600">Height (cm) *</label><InputNumber v-model="form.height_cm" :min="0.01" :maxFractionDigits="2" fluid /></div>
          </div>
          <div><label class="mb-1 block text-sm text-slate-600">Reference photos * (up to 12)</label><FileUpload mode="advanced" multiple accept="image/jpeg,image/png,image/webp" :maxFileSize="10485760" :customUpload="true" :showUploadButton="false" :showCancelButton="false" @select="onPhotosSelected" @remove="onPhotosRemoved" /><small class="text-slate-500">Include several angles. Product dimensions should be accurate before requesting.</small></div>
          <div><label class="mb-1 block text-sm text-slate-600">Notes</label><Textarea v-model="form.notes" rows="4" fluid placeholder="Unique details, measurements, or modeling instructions" /></div>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><Button type="button" label="Cancel" outlined @click="creating = false" /><Button type="submit" label="Submit Request" icon="pi pi-send" :loading="saving" :disabled="!form.product_id || !form.length_cm || !form.width_cm || !form.height_cm || !photos.length" /></div>
        </form>
      </template>
    </Card>
    <div v-if="loading" class="rounded-3xl bg-white p-8 text-sm text-slate-500">Loading requests...</div>
    <div v-else-if="!requests.length" class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">No 3D model requests yet.</div>
    <Card v-for="request in requests" :key="request.id" class="rounded-3xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs uppercase tracking-wide text-slate-500">Request #{{ request.id }}</p><h2 class="mt-1 font-semibold text-slate-900">{{ request.product?.product_name || 'Product' }}</h2><p class="text-sm text-slate-500">{{ request.product?.sku || '-' }}</p></div><Tag :value="statusLabel(request.status)" :severity="severity(request.status)" /></div>
        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2"><p>Materials: {{ request.materials || '—' }}</p><p>Quote: {{ request.quoted_price ? money(request.quoted_price) : 'Awaiting quote' }}</p><p class="sm:col-span-2">{{ request.quote_notes || request.notes || 'No notes' }}</p></div>
        <div v-if="request.model_path" class="mt-3 text-sm"><a :href="fileUrl(request.model_path)" target="_blank" rel="noopener" class="text-blue-700 underline">Preview / download proposed GLB</a></div>
        <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <Button v-if="request.status === 'quoted'" label="Accept Quote" icon="pi pi-check" :loading="acting === request.id" @click="act(request, 'accept_quote')" />
          <Button v-if="request.status === 'ready_for_review'" label="Request Revision" outlined :loading="acting === request.id" @click="act(request, 'request_revision')" />
          <Button v-if="request.status === 'ready_for_review'" label="Approve & Publish" icon="pi pi-check" severity="success" :loading="acting === request.id" @click="act(request, 'approve')" />
          <Button v-if="request.status === 'published'" label="View Product" icon="pi pi-arrow-right" outlined @click="router.push({ name: 'merchandising.products.view', params: { id: request.product_id } })" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload'
import Tag from 'primevue/tag'
import axiosClient from '@/axios'
import merchandisingService from '@/services/merchandising.service'

const route = useRoute(), router = useRouter(), toast = useToast()
const requests = ref<any[]>([]), products = ref<any[]>([]), photos = ref<File[]>([])
const loading = ref(false), saving = ref(false), creating = ref(false), acting = ref<number | null>(null)
const form = reactive({ product_id: null as number | null, length_cm: null as number | null, width_cm: null as number | null, height_cm: null as number | null, materials: '', notes: '' })
const statusLabel = (status: string) => String(status || '').replace(/_/g, ' ').replace(/\b\w/g, x => x.toUpperCase())
const severity = (status: string) => status === 'published' ? 'success' : status === 'ready_for_review' ? 'info' : status === 'changes_requested' ? 'warn' : 'secondary'
const money = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const fileUrl = (path: string) => `/storage/${path}`
const openCreate = () => { creating.value = true; if (route.query.product_id) form.product_id = Number(route.query.product_id) }
const onPhotosSelected = (event: any) => { photos.value = (event.files || []).slice(0, 12) }
const onPhotosRemoved = (event: any) => { photos.value = photos.value.filter(file => file !== event.file) }
const load = async () => { loading.value = true; try { const res = await axiosClient.get('/api/3d-model-requests'); requests.value = res.data?.data?.data || [] } catch (e: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: e.response?.data?.message || 'Could not load requests.', life: 3500 }) } finally { loading.value = false } }
const submit = async () => { if (!form.product_id || !form.length_cm || !form.width_cm || !form.height_cm || !photos.value.length) return; saving.value = true; try { const payload = new FormData(); payload.append('product_id', String(form.product_id)); payload.append('length_cm', String(form.length_cm)); payload.append('width_cm', String(form.width_cm)); payload.append('height_cm', String(form.height_cm)); payload.append('materials', form.materials); payload.append('notes', form.notes); photos.value.forEach(file => payload.append('photos[]', file)); await axiosClient.post('/api/3d-model-requests', payload); toast.add({ severity: 'success', summary: 'Request Submitted', detail: 'Your request is now in review.', life: 3000 }); creating.value = false; photos.value = []; await load() } catch (e: any) { toast.add({ severity: 'error', summary: 'Submit Failed', detail: e.response?.data?.message || 'Could not submit request.', life: 4000 }) } finally { saving.value = false } }
const act = async (request: any, action: string) => { acting.value = request.id; try { await axiosClient.post(`/api/3d-model-requests/${request.id}/store-action`, { action }); await load() } catch (e: any) { toast.add({ severity: 'error', summary: 'Action Failed', detail: e.response?.data?.message || 'Could not update request.', life: 3500 }) } finally { acting.value = null } }
onMounted(async () => { const res = await merchandisingService.getProducts({ per_page: 100 }); products.value = res?.data?.data || res?.data || []; if (route.query.product_id) openCreate(); await load() })
</script>
