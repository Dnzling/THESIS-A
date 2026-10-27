<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6">
    <header class="flex items-start gap-3"><Button icon="pi pi-arrow-left" text rounded severity="secondary" aria-label="Back to requests" @click="router.push({ name: 'merchandising.3d-requests' })" /><div><h1 class="text-2xl font-semibold text-slate-900">Request a 3D Model</h1><p class="mt-1 text-sm text-slate-500">Provide the product measurements and reference photos for an accurate quote.</p></div></header>
    <div v-if="loadingProducts" class="space-y-4"><Skeleton height="7rem" /><Skeleton height="24rem" /></div>
    <Message v-else-if="loadError" severity="error" :closable="false">{{ loadError }} <Button text label="Retry" @click="loadProducts" /></Message>
    <form v-else @submit.prevent="submit">
      <Card class="border border-slate-200 shadow-sm"><template #title>Product details</template><template #content><div class="grid gap-5 md:grid-cols-2">
        <div><label class="mb-1 block text-sm font-medium">Product *</label><Select v-model="form.product_id" :options="products" optionLabel="product_name" optionValue="id" filter fluid placeholder="Select product" /><small v-if="errors.product_id" class="text-red-600">{{ errors.product_id }}</small></div>
        <div><label class="mb-1 block text-sm font-medium">Materials and finish</label><InputText v-model="form.materials" fluid placeholder="e.g. oak frame, beige fabric" /></div>
      </div></template></Card>
      <Card class="mt-5 border border-slate-200 shadow-sm"><template #title>Dimensions and references</template><template #content><div class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-3"><div v-for="dimension in dimensions" :key="dimension.key"><label class="mb-1 block text-sm font-medium">{{ dimension.label }} (cm) *</label><InputNumber v-model="form[dimension.key]" :min="0.01" :maxFractionDigits="2" fluid /><small v-if="errors[dimension.key]" class="text-red-600">{{ errors[dimension.key] }}</small></div></div>
        <div>
          <div class="mb-1 flex items-center gap-1">
            <label class="block text-sm font-medium">Reference photos * (up to 12)</label>
            <Button type="button" icon="pi pi-lightbulb" text rounded severity="warn" size="small" aria-label="View photo tutorial" title="View photo tutorial" @click="tutorialVisible = true" />
          </div>
          <FileUpload mode="advanced" multiple accept="image/jpeg,image/png,image/webp" :maxFileSize="10485760" :customUpload="true" :showUploadButton="false" :showCancelButton="false" @select="onPhotosSelected" @remove="onPhotosRemoved" @clear="photos = []" />
          <small class="text-slate-500">Add clear photos from several angles; each file can be up to 10 MB.</small><small v-if="errors.photos" class="block text-red-600">{{ errors.photos }}</small>
        </div>
        <div><label class="mb-1 block text-sm font-medium">Modeling notes</label><Textarea v-model="form.notes" rows="4" fluid placeholder="Special details, textures, or instructions" /></div>
      </div></template></Card>
      <p v-if="submitError" class="mt-4 text-sm text-red-600">{{ submitError }}</p>
      <div class="mt-5 flex justify-end gap-2"><Button type="button" label="Cancel" outlined severity="secondary" @click="router.push({ name: 'merchandising.3d-requests' })" /><Button type="submit" label="Submit Request" icon="pi pi-send" :loading="saving" /></div>
    </form>

    <Dialog v-model:visible="tutorialVisible" modal header="How to photograph your furniture" class="w-full max-w-5xl" :draggable="false">
      <p class="mb-4 text-sm text-slate-600">Follow these steps to provide clear references for an accurate 3D model.</p>
      <div class="grid gap-4 sm:grid-cols-2">
        <article v-for="(step, index) in tutorialSteps" :key="step.image" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <img :src="step.image" :alt="step.alt" class="aspect-video w-full object-cover" />
          <div class="flex items-start gap-3 p-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-semibold text-orange-700">{{ index + 1 }}</span>
            <div><h3 class="text-sm font-semibold text-slate-900">{{ step.title }}</h3><p class="mt-0.5 text-xs leading-5 text-slate-600">{{ step.description }}</p></div>
          </div>
        </article>
      </div>
      <template #footer><Button type="button" label="Got it" size="small" @click="tutorialVisible = false" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import FileUpload from 'primevue/fileupload'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import axiosClient from '@/axios'
import merchandisingService from '@/services/merchandising.service'

const route = useRoute()
const router = useRouter()
const products = ref<any[]>([])
const photos = ref<File[]>([])
const loadingProducts = ref(true)
const loadError = ref('')
const saving = ref(false)
const submitError = ref('')
const tutorialVisible = ref(false)
const errors = reactive<Record<string, string>>({})
const form = reactive({ product_id: route.query.product_id ? Number(route.query.product_id) : null as number | null, length_cm: null as number | null, width_cm: null as number | null, height_cm: null as number | null, materials: '', notes: '' })
const dimensions = [{ key: 'length_cm', label: 'Length' }, { key: 'width_cm', label: 'Width' }, { key: 'height_cm', label: 'Height' }] as const
const tutorialSteps = [
  { image: '/images/3d-request-tutorial/01-prepare-area.png', alt: 'Sofa in a bright and uncluttered area', title: 'Prepare the area', description: 'Use bright, even lighting and keep the space around the furniture clear.' },
  { image: '/images/3d-request-tutorial/02-capture-front.png', alt: 'Taking a complete front photo of a sofa', title: 'Capture the full furniture', description: 'Keep the entire piece visible, sharp, and centered in every photo.' },
  { image: '/images/3d-request-tutorial/03-capture-angles.png', alt: 'Overlapping photos taken from several furniture angles', title: 'Photograph every angle', description: 'Take overlapping photos of the front, sides, corners, and back.' },
  { image: '/images/3d-request-tutorial/04-measure-furniture.png', alt: 'Width, height, and depth measurements on a sofa', title: 'Add accurate measurements', description: 'Measure the furniture\'s width, height, and depth before submitting.' },
]
const onPhotosSelected = (event: any) => { photos.value = (event.files || []).slice(0, 12) }
const onPhotosRemoved = (event: any) => { photos.value = photos.value.filter(file => file !== event.file) }
const loadProducts = async () => { loadingProducts.value = true; loadError.value = ''; try { const response = await merchandisingService.getProducts({ per_page: 100, product_type: 'finished_good' }); products.value = (response?.data?.data || response?.data || []).filter((product: any) => product.product_type === 'finished_good'); if (form.product_id && !products.value.some(product => product.id === form.product_id)) form.product_id = null } catch (error: any) { loadError.value = error?.response?.data?.message || 'Could not load products.' } finally { loadingProducts.value = false } }
const submit = async () => {
  Object.keys(errors).forEach(key => delete errors[key]); submitError.value = ''
  if (!form.product_id) errors.product_id = 'Select a product.'
  for (const dimension of dimensions) if (!form[dimension.key] || form[dimension.key]! <= 0) errors[dimension.key] = 'Enter a positive measurement.'
  if (!photos.value.length) errors.photos = 'Add at least one reference photo.'
  if (Object.keys(errors).length) return
  saving.value = true
  try {
    const payload = new FormData()
    payload.append('product_id', String(form.product_id))
    for (const dimension of dimensions) payload.append(dimension.key, String(form[dimension.key]))
    payload.append('materials', form.materials)
    payload.append('notes', form.notes)
    photos.value.forEach(file => payload.append('photos[]', file))
    await axiosClient.post('/api/3d-model-requests', payload)
    router.push({ name: 'merchandising.3d-requests' })
  } catch (error: any) {
    const fieldErrors = error?.response?.data?.errors || {}
    Object.entries(fieldErrors).forEach(([key, value]) => { errors[key] = Array.isArray(value) ? String(value[0]) : String(value) })
    submitError.value = error?.response?.data?.message || 'Could not submit the request.'
  } finally { saving.value = false }
}
onMounted(loadProducts)
</script>
