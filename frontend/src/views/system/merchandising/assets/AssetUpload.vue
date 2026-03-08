<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Upload Assets</h2>
        <p class="text-sm text-gray-500 mt-1">Upload 3D models, images, videos, and documents for your products</p>
      </div>
      <Button 
        label="Back to Assets" 
        icon="pi pi-arrow-left" 
        text 
        @click="router.push({ name: 'merchandising.assets' })" 
      />
    </div>

    <!-- Upload Form -->
    <form @submit.prevent="handleSubmit">
      <!-- Asset Type Selection -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-file text-blue-600"></i>
            <span>Asset Type</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Select Asset Type <span class="text-red-500">*</span>
              </label>
              <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div 
                  v-for="type in assetTypeOptions" 
                  :key="type.value"
                  @click="selectAssetType(type.value)"
                  :class="[
                    'p-4 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md',
                    form.asset_type === type.value 
                      ? 'border-blue-600 bg-blue-50' 
                      : 'border-gray-200 hover:border-blue-300'
                  ]"
                >
                  <div class="text-center">
                    <i :class="[type.icon, 'text-3xl mb-2', form.asset_type === type.value ? 'text-blue-600' : 'text-gray-600']"></i>
                    <p :class="['text-sm font-semibold', form.asset_type === type.value ? 'text-blue-900' : 'text-gray-900']">
                      {{ type.label }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ type.description }}</p>
                  </div>
                </div>
              </div>
              <small v-if="errors.asset_type" class="text-red-500">{{ errors.asset_type }}</small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Product Selection -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-box text-purple-600"></i>
            <span>Product Assignment</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col gap-2">
              <label for="product_id" class="text-sm font-semibold text-gray-700">
                Assign to Product <span class="text-red-500">*</span>
              </label>
              <Select 
                id="product_id"
                v-model="form.product_id" 
                :options="products" 
                optionLabel="product_name" 
                optionValue="id"
                placeholder="Select a product" 
                :class="{ 'p-invalid': errors.product_id }"
                :loading="loadingProducts"
                filter
              />
              <small v-if="errors.product_id" class="text-red-500">{{ errors.product_id }}</small>
            </div>
          </div>
        </template>
      </Card>

      <!-- File Upload -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-cloud-upload text-green-600"></i>
            <span>File Upload</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <!-- File Upload Area -->
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Upload File <span class="text-red-500">*</span>
              </label>

              <!-- Custom Upload Area -->
              <div 
                v-if="!form.file"
                class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-blue-400 transition-colors cursor-pointer"
                @click="$refs.fileInput.click()"
                @dragover.prevent
                @drop.prevent="handleDrop"
              >
                <i class="pi pi-cloud-upload text-6xl text-gray-400 mb-4 block"></i>
                <p class="text-lg font-medium text-gray-700 mb-2">Drop your file here</p>
                <p class="text-sm text-gray-500 mb-4">or click to browse</p>
                <div class="text-xs text-gray-400">
                  <p v-if="form.asset_type === '3D_Model'">Accepted: GLB, GLTF (Max 100MB)</p>
                  <p v-else-if="form.asset_type.includes('Image')">Accepted: JPG, PNG, WebP (Max 10MB)</p>
                  <p v-else-if="form.asset_type === 'Video_Product'">Accepted: MP4, WebM (Max 200MB)</p>
                  <p v-else-if="form.asset_type === 'Manual_PDF'">Accepted: PDF (Max 20MB)</p>
                  <p v-else>Select an asset type first</p>
                </div>
              </div>

              <input 
                ref="fileInput"
                type="file" 
                :accept="getAcceptedFileTypes()"
                class="hidden"
                @change="handleFileSelect"
              />

              <!-- File Preview -->
              <div v-if="form.file" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg p-6">
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center gap-4">
                    <div class="bg-blue-600 rounded-lg p-4">
                      <i :class="getFileIcon(form.file.name)" class="text-white text-3xl"></i>
                    </div>
                    <div>
                      <p class="text-lg font-semibold text-gray-900">{{ form.file.name }}</p>
                      <p class="text-sm text-gray-600">{{ formatFileSize(form.file.size) }}</p>
                    </div>
                  </div>
                  <Button 
                    icon="pi pi-times" 
                    severity="danger"
                    text 
                    rounded 
                    @click="removeFile"
                  />
                </div>

                <!-- Image Preview -->
                <div v-if="filePreview && form.asset_type.includes('Image')" class="mt-4">
                  <img :src="filePreview" class="max-w-full h-64 object-contain rounded-lg border-2 border-gray-200" />
                </div>

                <!-- Upload Progress -->
                <div v-if="uploading" class="mt-4">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Uploading...</span>
                    <span class="text-sm font-medium text-blue-600">{{ uploadProgress }}%</span>
                  </div>
                  <ProgressBar :value="uploadProgress" :showValue="false" />
                </div>
              </div>

              <small v-if="errors.file" class="text-red-500">{{ errors.file }}</small>
            </div>

            <!-- Additional Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label for="alt_text" class="text-sm font-semibold text-gray-700">
                  Alt Text / Description
                </label>
                <InputText 
                  id="alt_text"
                  v-model="form.alt_text" 
                  placeholder="Describe the asset"
                />
                <small class="text-gray-500">For accessibility and SEO</small>
              </div>

              <div class="flex flex-col gap-2">
                <label for="display_order" class="text-sm font-semibold text-gray-700">
                  Display Order
                </label>
                <InputNumber 
                  id="display_order"
                  v-model="form.display_order" 
                  :min="0"
                  showButtons
                />
                <small class="text-gray-500">Lower numbers appear first</small>
              </div>
            </div>

            <!-- 3D Model Specific Settings -->
            <div v-if="form.asset_type === '3D_Model'" class="border-t border-gray-200 pt-4 mt-4">
              <h4 class="text-sm font-semibold text-gray-700 mb-3">3D Model Settings</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-2">
                  <label class="text-xs font-medium text-gray-600">Camera Angle X (°)</label>
                  <InputNumber 
                    v-model="form.default_camera_angle_x" 
                    :min="-180"
                    :max="180"
                    suffix="°"
                    showButtons
                    :step="5"
                  />
                </div>

                <div class="flex flex-col gap-2">
                  <label class="text-xs font-medium text-gray-600">Camera Angle Y (°)</label>
                  <InputNumber 
                    v-model="form.default_camera_angle_y" 
                    :min="-180"
                    :max="180"
                    suffix="°"
                    showButtons
                    :step="5"
                  />
                </div>

                <div class="flex flex-col gap-2">
                  <label class="text-xs font-medium text-gray-600">Zoom Level</label>
                  <InputNumber 
                    v-model="form.default_zoom_level" 
                    :min="0.1"
                    :max="10"
                    :minFractionDigits="1"
                    showButtons
                    :step="0.1"
                  />
                </div>
              </div>
            </div>

            <!-- Primary Asset Checkbox -->
            <div class="flex items-center gap-2 pt-3 border-t border-gray-200">
              <Checkbox v-model="form.is_primary" inputId="is_primary" :binary="true" />
              <label for="is_primary" class="text-sm font-semibold text-gray-700 cursor-pointer">
                Set as Primary Asset
              </label>
            </div>
          </div>
        </template>
      </Card>

      <!-- Upload Tips -->
      <Card class="mb-6">
        <template #content>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <i class="pi pi-info-circle text-blue-600 text-xl mt-0.5"></i>
              <div class="flex-1">
                <p class="text-sm font-semibold text-blue-900 mb-2">Upload Guidelines:</p>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                  <li><strong>3D Models:</strong> Use optimized GLB format for best web performance. Max 100MB.</li>
                  <li><strong>Images:</strong> Use WebP or JPG format. Recommended size: 1920x1080px. Max 10MB.</li>
                  <li><strong>Videos:</strong> Use MP4 format with H.264 codec. Max 200MB.</li>
                  <li><strong>PDFs:</strong> Keep file sizes reasonable for quick downloads. Max 20MB.</li>
                  <li>Set primary assets for featured display on product pages.</li>
                </ul>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3">
        <Button 
          label="Cancel" 
          severity="secondary" 
          outlined 
          @click="$router.push({ name: 'merchandising.assets' })" 
        />
        <Button 
          label="Upload Asset" 
          icon="pi pi-cloud-upload" 
          @click="handleSubmit"
          :loading="uploading"
          :disabled="!form.file || !form.product_id || !form.asset_type"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import ProgressBar from 'primevue/progressbar'

const router = useRouter()
const toast = useToast()

const products = ref([])
const loadingProducts = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const filePreview = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const form = reactive({
  asset_type: '',
  product_id: null,
  file: null as File | null,
  alt_text: '',
  display_order: 0,
  is_primary: false,
  // 3D Model specific
  default_camera_angle_x: 0,
  default_camera_angle_y: 15,
  default_zoom_level: 1.5
})

const errors = ref<Record<string, string>>({})

const assetTypeOptions = [
  {
    value: '3D_Model',
    label: '3D Model',
    description: 'GLB/GLTF',
    icon: 'pi pi-box'
  },
  {
    value: 'Image_Main',
    label: 'Main Image',
    description: 'Primary photo',
    icon: 'pi pi-image'
  },
  {
    value: 'Image_Gallery',
    label: 'Gallery',
    description: 'More photos',
    icon: 'pi pi-images'
  },
  {
    value: 'Video_Product',
    label: 'Video',
    description: 'Product demo',
    icon: 'pi pi-video'
  },
  {
    value: 'Manual_PDF',
    label: 'PDF/Manual',
    description: 'Documents',
    icon: 'pi pi-file-pdf'
  }
]

// Watch file changes for preview
watch(() => form.file, (newFile) => {
  if (newFile && form.asset_type.includes('Image')) {
    const reader = new FileReader()
    reader.onload = (e) => {
      filePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(newFile)
  } else {
    filePreview.value = null
  }
})

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await merchandisingService.getProducts({ per_page: 1000 })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loadingProducts.value = false
  }
}

const selectAssetType = (type: string) => {
  form.asset_type = type
  // Reset file if type changes
  if (form.file) {
    form.file = null
    filePreview.value = null
  }
}

const getAcceptedFileTypes = () => {
  switch (form.asset_type) {
    case '3D_Model':
      return '.glb,.gltf'
    case 'Image_Main':
    case 'Image_Gallery':
      return 'image/jpeg,image/png,image/webp'
    case 'Video_Product':
      return 'video/mp4,video/webm'
    case 'Manual_PDF':
      return 'application/pdf'
    default:
      return '*'
  }
}

const getMaxFileSize = () => {
  switch (form.asset_type) {
    case '3D_Model':
      return 100 * 1024 * 1024 // 100MB
    case 'Image_Main':
    case 'Image_Gallery':
      return 10 * 1024 * 1024 // 10MB
    case 'Video_Product':
      return 200 * 1024 * 1024 // 200MB
    case 'Manual_PDF':
      return 20 * 1024 * 1024 // 20MB
    default:
      return 10 * 1024 * 1024
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const handleDrop = (event: DragEvent) => {
  const file = event.dataTransfer?.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const validateAndSetFile = (file: File) => {
  const maxSize = getMaxFileSize()
  
  if (file.size > maxSize) {
    toast.add({
      severity: 'error',
      summary: 'File Too Large',
      detail: `File must be less than ${formatFileSize(maxSize)}`,
      life: 3000
    })
    return
  }

  form.file = file
  errors.value.file = ''
}

const removeFile = () => {
  form.file = null
  filePreview.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const getFileIcon = (filename: string) => {
  const ext = filename.split('.').pop()?.toLowerCase()
  
  if (ext === 'glb' || ext === 'gltf') return 'pi pi-cube'
  if (['jpg', 'jpeg', 'png', 'webp'].includes(ext || '')) return 'pi pi-image'
  if (['mp4', 'webm'].includes(ext || '')) return 'pi pi-video'
  if (ext === 'pdf') return 'pi pi-file-pdf'
  
  return 'pi pi-file'
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.asset_type) {
    errors.value.asset_type = 'Please select an asset type'
  }
  
  if (!form.product_id) {
    errors.value.product_id = 'Please select a product'
  }
  
  if (!form.file) {
    errors.value.file = 'Please upload a file'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please fill in all required fields',
      life: 3000
    })
    return
  }
  
  uploading.value = true
  uploadProgress.value = 0
  
  try {
    const formData = new FormData()
    formData.append('product_id', form.product_id!.toString())
    formData.append('asset_type', form.asset_type)
    formData.append('asset_file', form.file!)
    formData.append('is_primary', form.is_primary ? '1' : '0')
    formData.append('display_order', form.display_order.toString())
    
    if (form.alt_text) {
      formData.append('alt_text', form.alt_text)
    }
    
    // 3D Model specific fields
    if (form.asset_type === '3D_Model') {
      formData.append('model_format', form.file!.name.split('.').pop()?.toLowerCase() || 'glb')
      formData.append('default_camera_angle_x', form.default_camera_angle_x.toString())
      formData.append('default_camera_angle_y', form.default_camera_angle_y.toString())
      formData.append('default_zoom_level', form.default_zoom_level.toString())
    }

    // Simulate progress
    const progressInterval = setInterval(() => {
      uploadProgress.value = Math.min(uploadProgress.value + 10, 90)
    }, 200)

    await merchandisingService.uploadAsset(formData)
    
    clearInterval(progressInterval)
    uploadProgress.value = 100

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Asset uploaded successfully',
      life: 3000
    })
    
    setTimeout(() => {
      router.push({ name: 'merchandising.assets' })
    }, 1000)
    
  } catch (error: any) {
    console.error('Upload error:', error)
    
    toast.add({
      severity: 'error',
      summary: 'Upload Failed',
      detail: error.response?.data?.message || 'Failed to upload asset',
      life: 5000
    })
  } finally {
    uploading.value = false
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

// Load products on mount
loadProducts()
</script>

<style scoped>
:deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}

:deep(.p-card-content) {
  padding-top: 1rem;
}
</style>