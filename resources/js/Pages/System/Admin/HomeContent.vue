<template>
  <div class="space-y-5 text-sm">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-950">Home Content</h1>
        <p class="mt-1 text-xs text-slate-500">Manage module showcases and the four interactive furniture previews.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading"
        @click="loadContent" />
    </div>
  
    <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
      <div class="flex gap-1 border-b border-slate-100 px-2 pt-1">
        <button v-for="tab in tabs" :key="tab.value" type="button"
          class="border-b-2 px-4 py-3 text-xs font-semibold transition"
          :class="activeTab === tab.value ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
          @click="activeTab = tab.value">
          {{ tab.label }}
        </button>
      </div>
  
      <div v-if="loading" class="grid gap-4 p-4 md:grid-cols-2">
        <Skeleton v-for="index in 4" :key="index" height="250px" borderRadius="16px" />
      </div>
  
      <div v-else-if="activeTab === 'furniture'" class="p-4">
        <div class="mb-5 rounded-xl border border-orange-100 bg-orange-50 p-4 text-xs leading-5 text-orange-900">
          Exactly four slots appear on the Home page. Upload a square button image and a GLB, GLTF, OBJ, or PLY model for
          each slot.
        </div>
        <div class="grid gap-5 xl:grid-cols-2">
          <article v-for="item in furniture" :key="item.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex items-center justify-between">
              <h2 class="font-semibold text-slate-900">Furniture slot {{ item.slot }}</h2>
              <ToggleSwitch v-model="item.is_active" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-[150px_1fr]">
              <div>
                <div class="aspect-square overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
                  <img v-if="item.previewUrl || item.thumbnail_url" :src="item.previewUrl || item.thumbnail_url"
                    :alt="item.label" class="h-full w-full object-cover" />
                  <div v-else class="flex h-full items-center justify-center px-4 text-center text-xs text-slate-400">No
                    button image</div>
                </div>
                <label class="mt-2 block"><span class="sr-only">Choose button image</span><input type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-[11px] text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-orange-700"
                    @change="selectImage($event, 'furniture', item)" /></label>
                <p class="mt-1 text-[11px] text-slate-400">Cropped to 512 x 512 px</p>
              </div>
              <div class="space-y-3">
                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-600">Button label</span>
                  <InputText v-model="item.label" size="small" fluid />
                </label>
                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-600">Short
                    description</span><Textarea v-model="item.description" rows="3" size="small" fluid /></label>
                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-600">3D asset</span><input
                    type="file" accept=".glb,.gltf,.obj,.ply"
                    class="block w-full text-[11px] text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700"
                    @change="selectModel($event, item)" /></label>
                <p class="text-[11px] text-slate-400">{{ item.modelFile?.name || item.model_path || 'No model uploaded' }}
                </p>
                <Button label="Save slot" severity="warn" size="small" :loading="savingKey === `furniture-${item.id}`"
                  @click="saveFurniture(item)" />
              </div>
            </div>
          </article>
        </div>
      </div>
  
      <div v-else class="p-4">
        <div
          class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sky-100 bg-sky-50 p-4 text-xs leading-5 text-sky-900">
          <span>Add as many module slides as needed. Each supports a title, description, up to five checked benefits, and
            a 16:9 interface screenshot.</span>
          <Button label="Add module" icon="pi pi-plus" severity="warn" size="small" @click="addModule" />
        </div>
        <div class="space-y-5">
          <article v-for="module in modules" :key="module.id"
            class="grid gap-5 rounded-2xl border border-slate-200 p-4 lg:grid-cols-[280px_1fr]">
            <div>
              <div class="aspect-video overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
                <img v-if="module.previewUrl || module.image_url" :src="module.previewUrl || module.image_url"
                  :alt="module.name" class="h-full w-full object-cover" />
                <div v-else class="flex h-full items-center justify-center px-4 text-center text-xs text-slate-400">No
                  module screenshot</div>
              </div>
              <label class="mt-2 block"><span class="sr-only">Choose module screenshot</span><input type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="block w-full text-[11px] text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-orange-700"
                  @change="selectImage($event, 'module', module)" /></label>
              <p class="mt-1 text-[11px] text-slate-400">Cropped to 1280 x 720 px</p>
            </div>
            <div class="space-y-3">
              <div class="grid gap-3 sm:grid-cols-[1fr_110px_auto]">
                <label><span class="mb-1 block text-xs font-medium text-slate-600">Module name</span>
                  <InputText v-model="module.name" size="small" fluid />
                </label>
                <label><span class="mb-1 block text-xs font-medium text-slate-600">Order</span>
                  <InputNumber v-model="module.sort_order" :min="1" :max="9999" size="small" fluid />
                </label>
                <label class="flex items-end gap-2 pb-2 text-xs text-slate-600">
                  <ToggleSwitch v-model="module.is_active" /> Active
                </label>
              </div>
              <label class="block"><span class="mb-1 block text-xs font-medium text-slate-600">Description</span><Textarea
                  v-model="module.description" rows="3" size="small" fluid /></label>
              <label class="block"><span class="mb-1 block text-xs font-medium text-slate-600">Checked benefits (one per
                  line)</span><Textarea v-model="module.benefitsText" rows="3" size="small" fluid
                  placeholder="Faster stock decisions&#10;Clear branch visibility" /></label>
              <div class="flex items-center justify-between gap-2">
                <Button label="Delete" severity="danger" text size="small" :disabled="!module.id"
                  @click="confirmDeleteModule(module)" />
                <Button :label="module.id ? 'Save module' : 'Create module'" severity="warn" size="small"
                  :loading="savingKey === `module-${module.id || 'new'}`" @click="saveModule(module)" />
              </div>
            </div>
          </article>
        </div>
      </div>
    </div>
  
    <MarketingImageCropDialog v-model:visible="crop.visible" :source="crop.source" :file-name="crop.fileName"
      :aspect-ratio="crop.aspectRatio" :output-width="crop.outputWidth" :output-height="crop.outputHeight"
      @cropped="acceptCrop" />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import { useConfirm } from 'primevue/useconfirm'
import axiosClient from '@/axios'
import MarketingImageCropDialog from '@/Components/MarketingImageCropDialog.vue'

const tabs = [{ label: 'Furniture 3D showcase', value: 'furniture' }, { label: 'Module showcase', value: 'modules' }]
const confirm = useConfirm()
const activeTab = ref('furniture')
const loading = ref(true)
const savingKey = ref('')
const furniture = ref<any[]>([])
const modules = ref<any[]>([])
const objectUrls = new Set<string>()
const crop = reactive({ visible: false, source: '', fileName: '', aspectRatio: 1, outputWidth: 512, outputHeight: 512, target: null as any })

const loadContent = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/home-content')
    const data = response.data?.data || {}
    furniture.value = (data.furniture || []).map((item: any) => ({ ...item, thumbnailFile: null, modelFile: null, previewUrl: '' }))
    modules.value = (data.modules || []).map((item: any) => ({ ...item, imageFile: null, previewUrl: '', benefitsText: (item.benefits || []).join('\n') }))
  } finally {
    loading.value = false
  }
}

const selectImage = (event: Event, type: 'furniture' | 'module', target: any) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return
  const source = URL.createObjectURL(file)
  objectUrls.add(source)
  Object.assign(crop, {
    visible: true,
    source,
    fileName: file.name,
    aspectRatio: type === 'furniture' ? 1 : 16 / 9,
    outputWidth: type === 'furniture' ? 512 : 1280,
    outputHeight: type === 'furniture' ? 512 : 720,
    target: { type, record: target },
  })
}

const acceptCrop = (file: File) => {
  const target = crop.target
  if (!target) return
  const previewUrl = URL.createObjectURL(file)
  objectUrls.add(previewUrl)
  target.record.previewUrl = previewUrl
  if (target.type === 'furniture') target.record.thumbnailFile = file
  else target.record.imageFile = file
}

const selectModel = (event: Event, item: any) => {
  const input = event.target as HTMLInputElement
  item.modelFile = input.files?.[0] || null
}

const addModule = () => {
  modules.value.unshift({
    id: null,
    name: '',
    description: '',
    benefitsText: '',
    sort_order: Math.max(0, ...modules.value.map((item: any) => Number(item.sort_order) || 0)) + 1,
    is_active: true,
    imageFile: null,
    previewUrl: '',
    image_url: null,
  })
}

const confirmDeleteModule = (module: any) => {
  if (!module.id) return
  confirm.require({
    header: 'Delete module slide',
    message: `Remove ${module.name || 'this module'} from the Home carousel?`,
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancel',
    acceptLabel: 'Delete',
    acceptClass: 'p-button-danger',
    accept: async () => {
      savingKey.value = `module-${module.id}`
      try {
        await axiosClient.delete(`/api/admin/home-content/modules/${module.id}`)
        await loadContent()
      } finally {
        savingKey.value = ''
      }
    },
  })
}

const saveFurniture = async (item: any) => {
  savingKey.value = `furniture-${item.id}`
  try {
    const data = new FormData()
    data.append('label', item.label || '')
    data.append('description', item.description || '')
    data.append('is_active', item.is_active ? '1' : '0')
    if (item.thumbnailFile) data.append('thumbnail', item.thumbnailFile)
    if (item.modelFile) data.append('model', item.modelFile)
    await axiosClient.post(`/api/admin/home-content/furniture/${item.id}`, data)
    await loadContent()
  } finally {
    savingKey.value = ''
  }
}

const saveModule = async (module: any) => {
  savingKey.value = `module-${module.id || 'new'}`
  try {
    const data = new FormData()
    data.append('name', module.name || '')
    data.append('description', module.description || '')
    data.append('sort_order', String(module.sort_order || 1))
    data.append('is_active', module.is_active ? '1' : '0')
    module.benefitsText.split(/\r?\n/).map((value: string) => value.trim()).filter(Boolean).slice(0, 5).forEach((benefit: string, index: number) => data.append(`benefits[${index}]`, benefit))
    if (module.imageFile) data.append('image', module.imageFile)
    const endpoint = module.id ? `/api/admin/home-content/modules/${module.id}` : '/api/admin/home-content/modules'
    await axiosClient.post(endpoint, data)
    await loadContent()
  } finally {
    savingKey.value = ''
  }
}

onMounted(loadContent)
onBeforeUnmount(() => objectUrls.forEach((url) => URL.revokeObjectURL(url)))
</script>
