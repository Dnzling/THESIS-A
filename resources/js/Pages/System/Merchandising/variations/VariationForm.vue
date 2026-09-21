<template>
  <div :class="embedded ? 'space-y-4' : 'mx-auto max-w-7xl space-y-4 pb-6 text-sm'">
    <ConfirmDialog />
    <!-- Header -->
    <div v-if="!embedded" class="flex flex-col gap-3 rounded-2xl border border-orange-100 bg-gradient-to-r from-orange-50 to-white p-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-950">
          {{ isEditMode ? 'Edit Variation' : 'Add New Variation' }}
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ isEditMode ? 'Update variation details' : 'Create a new product variation' }}
        </p>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loadingData" class="space-y-4">
      <Skeleton height="300px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit">
      <div class="space-y-4">
      
      <!-- Basic Information Card -->
      <Card class="h-fit border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <span>Basic Information</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <!-- Product Selection -->
            <div v-if="!embedded && !isInventoryContext" class="flex flex-col gap-2">
              <label for="product_id" class="text-sm font-semibold text-gray-700">
                Product <span class="text-red-500">*</span>
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
                size="small"
                @change="onProductChange"
              />
              <small v-if="errors.product_id" class="text-red-500">{{ errors.product_id }}</small>
            </div>

            <div v-else class="rounded-xl border border-orange-100 bg-orange-50 p-3">
              <p class="text-xs font-medium text-orange-700">Standard product</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedProduct?.product_name || embeddedProduct?.product_name || 'Loading product...' }}</p>
              <p class="mt-1 text-xs text-slate-500">SKU: <span class="font-mono font-semibold">{{ selectedProduct?.sku || embeddedProduct?.sku || '-' }}</span></p>
            </div>

            <!-- Variation SKU (Auto-generated) -->
            <div class="flex flex-col gap-2">
              <label for="variation_sku" class="text-sm font-semibold text-gray-700">
                Variation SKU <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="variation_sku"
                v-model="form.variation_sku" 
                placeholder="Will be auto-generated" 
                :class="{ 'p-invalid': errors.variation_sku }"
                readonly
                class="bg-gray-100"
              />
              <small class="text-gray-500">Auto-generated from product SKU and attributes</small>
              <small v-if="errors.variation_sku" class="text-red-500">{{ errors.variation_sku }}</small>
            </div>

            <!-- Variation Name -->
            <div class="flex flex-col gap-2">
              <label for="variation_name" class="text-sm font-semibold text-gray-700">
                Variation Name <span class="text-red-500">*</span>
              </label>
              <InputText 
                id="variation_name"
                v-model="form.variation_name" 
                placeholder="e.g., Navy Blue - Large, Oak Wood Finish" 
                :class="{ 'p-invalid': errors.variation_name }"
                @input="generateSKU"
              />
              <small v-if="errors.variation_name" class="text-red-500">{{ errors.variation_name }}</small>
            </div>

          </div>
        </template>
      </Card>

      <!-- Attributes Card -->
      <Card class="h-fit border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">

            <span>Variation Attributes</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            
            <!-- Color -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label for="color" class="text-sm font-semibold text-gray-700">
                  Color
                </label>
                <InputText 
                  id="color"
                  v-model="form.color" 
                  placeholder="e.g., Navy Blue, Charcoal Gray" 
                  @input="generateSKU"
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="color_hex" class="text-sm font-semibold text-gray-700">
                  Color Code (Hex)
                </label>
                <div class="flex gap-2 items-center">
                  <ColorPicker v-model="colorHexValue" inputId="color_hex_picker" format="hex" />
                  <InputText 
                    id="color_hex"
                    v-model="form.color_hex" 
                    placeholder="#000000" 
                    class="flex-1 font-mono"
                    maxlength="7"
                  />
                </div>
              </div>
            </div>

            <!-- Size & Material -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label for="size" class="text-sm font-semibold text-gray-700">
                  Size
                </label>
                <InputText 
                  id="size"
                  v-model="form.size" 
                  placeholder="e.g., Small, Medium, Large, XL" 
                  @input="generateSKU"
                />
              </div>

              <div class="flex flex-col gap-2">
                <label for="material" class="text-sm font-semibold text-gray-700">
                  Material
                </label>
                <InputText 
                  id="material"
                  v-model="form.material" 
                  placeholder="e.g., Leather, Fabric, Wood" 
                  @input="generateSKU"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="flex flex-col gap-2"><label class="text-sm font-semibold text-gray-700">Texture</label><InputText v-model="form.texture" placeholder="e.g., Smooth, Woven" /></div>
              <div class="flex flex-col gap-2"><label class="text-sm font-semibold text-gray-700">Finish</label><InputText v-model="form.finish" placeholder="e.g., Matte, Glossy" /></div>
            </div>
            <p class="rounded-lg bg-slate-50 p-2 text-xs text-slate-500">Only enter attributes that distinguish this SKU from the standard product.</p>

          </div>
        </template>
      </Card>

      <!-- Pricing Card -->
      <Card class="h-fit border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <span>Pricing</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex flex-col gap-2">
              <label for="base_price" class="text-sm font-semibold text-gray-700">Selling Price <span class="text-red-500">*</span></label>
              <InputNumber id="base_price" v-model="form.base_price" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid size="small" :class="{ 'p-invalid': errors.base_price }" />
              <small v-if="errors.base_price" class="text-red-500">{{ errors.base_price }}</small>
              <small class="text-slate-500">Defaults to the parent product selling price.</small>
            </div>
            <div class="flex flex-col gap-2">
              <label for="discounted_price" class="text-sm font-semibold text-gray-700">Discounted Price</label>
              <InputNumber id="discounted_price" v-model="form.discounted_price" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid size="small" :class="{ 'p-invalid': errors.discounted_price }" />
              <small v-if="errors.discounted_price" class="text-red-500">{{ errors.discounted_price }}</small>
              <small class="text-slate-500">Optional and must be lower than the selling price.</small>
            </div>
            <div class="flex flex-col gap-2">
              <label for="cost_price" class="text-sm font-semibold text-gray-700">Unit Cost Price</label>
              <InputNumber id="cost_price" v-model="form.cost_price" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid size="small" />
              <small class="text-slate-500">Used for purchase and inventory cost estimates; hidden from suppliers and customers.</small>
            </div>
            <div class="flex flex-col gap-2">
              <label for="unit_of_measurement" class="text-sm font-semibold text-gray-700">Unit of Measurement</label>
              <Select
                id="unit_of_measurement"
                v-model="form.unit_of_measurement"
                :options="unitOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select unit"
                filter
                disabled
                size="small"
                class="w-full"
              />
              <small class="text-slate-500">Inherited from the parent product.</small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Variant 3D Model Card -->
      <Card v-if="!proposalId" class="h-fit border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <span>Variant 3D Model</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col gap-2">
              <label for="custom_3d_model_id" class="text-sm font-semibold text-gray-700">
                Existing Product 3D Model
              </label>
              <Select
                id="custom_3d_model_id"
                v-model="form.custom_3d_model_id"
                :options="product3DModels"
                optionLabel="file_name"
                optionValue="id"
                placeholder="Use existing 3D model (optional)"
                :disabled="!form.product_id || loadingProductModels"
                :loading="loadingProductModels"
                showClear
                filter
              />
              <small class="text-gray-500">Pick an existing model or upload a new one below for this variant.</small>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <div class="flex flex-col gap-2">
                <label for="default_camera_angle_x" class="text-sm font-semibold text-gray-700">Camera X</label>
                <InputNumber
                  id="default_camera_angle_x"
                  v-model="form.default_camera_angle_x"
                  :min="-180"
                  :max="180"
                  suffix=" deg"
                  showButtons
                  buttonLayout="horizontal"
                  :step="5"
                  fluid
                />
              </div>
              <div class="flex flex-col gap-2">
                <label for="default_camera_angle_y" class="text-sm font-semibold text-gray-700">Camera Y</label>
                <InputNumber
                  id="default_camera_angle_y"
                  v-model="form.default_camera_angle_y"
                  :min="-180"
                  :max="180"
                  suffix=" deg"
                  showButtons
                  buttonLayout="horizontal"
                  :step="5"
                  fluid
                />
              </div>
              <div class="flex flex-col gap-2">
                <label for="default_zoom_level" class="text-sm font-semibold text-gray-700">Zoom</label>
                <InputNumber
                  id="default_zoom_level"
                  v-model="form.default_zoom_level"
                  :min="0.1"
                  :max="20"
                  :step="0.1"
                  :minFractionDigits="1"
                  showButtons
                  buttonLayout="horizontal"
                  fluid
                />
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label for="variant_model_upload" class="text-sm font-semibold text-gray-700">
                Upload New 3D Model (Optional)
              </label>
              <InputText
                id="variant_model_upload"
                type="file"
                accept=".glb,.gltf,.obj,.fbx,.usdz"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                @change="handleVariantModelSelect"
              />
              <small v-if="variantModelFile" class="text-blue-600">
                Ready: {{ variantModelFile.name }}
              </small>
              <Button
                v-if="variantModelFile || form.custom_3d_model_id"
                type="button"
                label="Remove 3D model"
                icon="pi pi-trash"
                severity="danger"
                outlined
                size="small"
                class="self-start"
                @click="confirmRemoveModel"
              />
              <small class="text-gray-500">If uploaded, this will be linked automatically to the variant.</small>
            </div>

            <div v-if="selected3DModel?.previewUrl" class="space-y-2">
              <p class="text-sm font-semibold text-gray-700">
                3D Preview: {{ selected3DModel.file_name || 'Selected Model' }}
              </p>
              <Model3DPreview
                :model-url="selected3DModel.previewUrl"
                :model-format="selected3DModel.model_format"
                :camera-x="form.default_camera_angle_x"
                :camera-y="form.default_camera_angle_y"
                :zoom="form.default_zoom_level"
                height="280px"
              />
            </div>

          </div>
        </template>
      </Card>

      <!-- Variant Photo Card -->
      <Card class="h-fit border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <span>Variant Photo</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <div class="flex flex-col gap-2">
              <label for="variant_image_upload" class="text-sm font-semibold text-gray-700">
                Upload Photo <span class="text-red-500">*</span>
              </label>
              <InputText
                id="variant_image_upload"
                type="file"
                accept="image/*"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                @change="handleVariantImageSelect"
              />
              <small class="text-gray-500">Required for new variations. Used in ecommerce variation preview.</small>
              <small v-if="errors.custom_image_id" class="text-red-500">{{ errors.custom_image_id }}</small>
            </div>

            <div v-if="variantImagePreviewUrl" class="rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
              <img :src="variantImagePreviewUrl" alt="Variation photo preview" class="w-full aspect-square object-cover" />
            </div>
            <Button
              v-if="variantImageFile || form.custom_image_id"
              type="button"
              label="Remove photo"
              icon="pi pi-trash"
              severity="danger"
              outlined
              size="small"
              @click="confirmRemoveImage"
            />
          </div>
        </template>
      </Card>

      <!-- Variant Specs Card -->
      <Card class="h-fit border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center gap-2">
            <span>Variant Specs</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-xs font-semibold text-gray-700">Length (cm)</label>
              <InputNumber v-model="form.length_cm" :minFractionDigits="2" suffix=" cm" :min="0" fluid />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-xs font-semibold text-gray-700">Width (cm)</label>
              <InputNumber v-model="form.width_cm" :minFractionDigits="2" suffix=" cm" :min="0" fluid />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-xs font-semibold text-gray-700">Height (cm)</label>
              <InputNumber v-model="form.height_cm" :minFractionDigits="2" suffix=" cm" :min="0" fluid />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-xs font-semibold text-gray-700">Weight (kg)</label>
              <InputNumber v-model="form.weight_kg" :minFractionDigits="2" suffix=" kg" :min="0" fluid />
            </div>
          </div>
          <p class="mt-3 text-xs text-slate-500">Specifications start from the standard product and remain editable for this variant.</p>
        </template>
      </Card>

      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
        <Button 
          label="Cancel" 
          severity="secondary" 
          outlined 
          size="small"
          @click="embedded ? emit('cancel') : goBack()"
        />
        <Button 
          :label="isEditMode ? 'Update Variation' : 'Create Variation'" 
          icon="pi pi-check"
          severity="warn"
          size="small"
          @click="handleSubmit"
          :loading="submitting"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import merchandisingService from '../../../../services/merchandising.service'
import inventoryService from '../../../../services/inventory.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import ColorPicker from 'primevue/colorpicker'
import ConfirmDialog from 'primevue/confirmdialog'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'

const props = withDefaults(defineProps<{
  embedded?: boolean
  embeddedProduct?: any
  embeddedVariation?: any
}>(), {
  embedded: false,
  embeddedProduct: null,
  embeddedVariation: null
})

const emit = defineEmits<{
  (e: 'saved'): void
  (e: 'cancel'): void
}>()

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const embedded = computed(() => !!props.embedded)
const embeddedProduct = computed(() => props.embeddedProduct)
const embeddedVariation = computed(() => props.embeddedVariation)
const isInventoryContext = computed(() => String(route.name || '').startsWith('inventory.'))

const isEditMode = computed(() => {
  if (embedded.value) return !!embeddedVariation.value?.id
  return !!route.params.id
})
const submitting = ref(false)
const loadingData = ref(false)
const loadingProducts = ref(false)
const loadingProductModels = ref(false)
const uploadingModel = ref(false)
const products = ref([])
const product3DModels = ref<any[]>([])
const units = ref<any[]>([])
const selectedProduct = ref<any>(null)
const variantModelFile = ref<File | null>(null)
const variantImageFile = ref<File | null>(null)
const variantImagePreviewUrl = ref('')
const proposalId = computed(() => Number(route.query.proposal_id || 0) || null)
const proposal = ref<any>(null)

const form = reactive({
  product_id: null,
  variation_sku: '',
  variation_name: '',
  color: '',
  color_hex: '#3B82F6',
  size: '',
  material: '',
  texture: '',
  finish: '',
  base_price: null as number | null,
  discounted_price: null as number | null,
  cost_price: null as number | null,
  unit_of_measurement: '',
  custom_3d_model_id: null as number | null,
  custom_image_id: null as number | null,
  default_camera_angle_x: 0,
  default_camera_angle_y: 15,
  default_zoom_level: 1.5,
  length_cm: null as number | null,
  width_cm: null as number | null,
  height_cm: null as number | null,
  weight_kg: null as number | null,
  is_active: true
})

const fallbackUnits = ['pcs', 'set', 'piece', 'box', 'kg', 'meter', 'liter', 'pack', 'roll']
const unitOptions = computed(() => {
  const source = units.value.length
    ? units.value.map((unit: any) => ({
        label: unit.unit_symbol ? `${unit.unit_name} (${unit.unit_symbol})` : unit.unit_name,
        value: unit.unit_code || unit.unit_symbol || unit.unit_name,
      }))
    : fallbackUnits.map((unit) => ({ label: unit, value: unit }))
  const current = form.unit_of_measurement
  if (current && !source.some((option: any) => option.value === current)) {
    source.unshift({ label: current, value: current })
  }
  return source
})

const errors = ref<Record<string, string>>({})

const colorHexValue = computed({
  get: () => String(form.color_hex || '').replace('#', ''),
  set: (value: string) => {
    form.color_hex = value ? `#${value}` : ''
  }
})

const getModelUrl = (asset: any): string => {
  return asset?.url || asset?.auth_url || asset?.file_url || asset?.model_url || ''
}

const selected3DModel = computed(() => {
  const selected = product3DModels.value.find((asset: any) => Number(asset.id) === Number(form.custom_3d_model_id))
  if (!selected) return null
  return {
    ...selected,
    previewUrl: getModelUrl(selected),
  }
})

// Watch product selection
watch(() => form.product_id, (newVal) => {
  if (newVal) {
    selectedProduct.value = products.value.find((p: any) => p.id === newVal)
      || (Number(embeddedProduct.value?.id) === Number(newVal) ? embeddedProduct.value : null)
    if (!isEditMode.value && selectedProduct.value) {
      form.base_price = Number(selectedProduct.value.base_price ?? 0)
      form.discounted_price = selectedProduct.value.discounted_price == null ? null : Number(selectedProduct.value.discounted_price)
      form.unit_of_measurement = selectedProduct.value.unit_of_measurement || ''
      form.length_cm = selectedProduct.value.length_cm == null ? null : Number(selectedProduct.value.length_cm)
      form.width_cm = selectedProduct.value.width_cm == null ? null : Number(selectedProduct.value.width_cm)
      form.height_cm = selectedProduct.value.height_cm == null ? null : Number(selectedProduct.value.height_cm)
      form.weight_kg = selectedProduct.value.weight_kg == null ? null : Number(selectedProduct.value.weight_kg)
    }
    generateSKU()
    loadProductModels(newVal)
  } else {
    product3DModels.value = []
    form.custom_3d_model_id = null
  }
})

watch(
  () => form.custom_3d_model_id,
  (newVal) => {
    if (!newVal) return
    const selected = product3DModels.value.find((asset: any) => Number(asset.id) === Number(newVal))
    if (!selected) return
    form.default_camera_angle_x = Number(selected.default_camera_angle_x ?? 0)
    form.default_camera_angle_y = Number(selected.default_camera_angle_y ?? 15)
    form.default_zoom_level = Number(selected.default_zoom_level ?? 1.5)
  }
)

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await merchandisingService.getProducts({ per_page: 1000, product_type: 'finished_good' })
    const payload = response.data || {}
    products.value = Array.isArray(payload?.data) ? payload.data : (payload?.data?.data || [])
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loadingProducts.value = false
  }
}

const loadUnits = async () => {
  try {
    const response = await inventoryService.getUnits({ is_active: true })
    units.value = Array.isArray(response?.data) ? response.data : []
  } catch (error) {
    console.error('Failed to load units:', error)
    units.value = []
  }
}

const loadProductModels = async (productId: number) => {
  if (!productId) return

  loadingProductModels.value = true
  try {
    const response = await merchandisingService.getAssetsByProduct(productId)
    const payload = response.data || {}
    const grouped = payload.assets_by_type?.['3D_Model'] || []
    const flatAssets = payload.assets || []
    product3DModels.value = grouped.length
      ? grouped
      : flatAssets.filter((asset: any) => asset.asset_type === '3D_Model')
  } catch (error) {
    console.error('Failed to load product 3D models:', error)
    product3DModels.value = []
  } finally {
    loadingProductModels.value = false
  }
}

const loadVariation = async () => {
  if (!isEditMode.value) return
  
  loadingData.value = true
  try {
    const variationId = embedded.value ? Number(embeddedVariation.value?.id) : Number(route.params.id)
    const response = await merchandisingService.getVariation(variationId)
    const payload = response.data || {}
    const variation = payload?.data || payload || {}
    
    Object.assign(form, {
      product_id: variation.product_id,
      variation_sku: variation.variation_sku,
      variation_name: variation.variation_name,
      color: variation.color || '',
      color_hex: variation.color_hex || '#3B82F6',
      size: variation.size || '',
      material: variation.material || '',
      texture: variation.texture || '',
      finish: variation.finish || '',
      base_price: Number(variation.base_price ?? variation.product?.base_price ?? 0),
      discounted_price: variation.discounted_price == null ? null : Number(variation.discounted_price),
      cost_price: variation.cost_price == null ? null : Number(variation.cost_price),
      unit_of_measurement: variation.unit_of_measurement || variation.product?.unit_of_measurement || '',
      custom_3d_model_id: variation.custom_3d_model_id || null,
      custom_image_id: variation.custom_image_id || null,
      default_camera_angle_x: Number(variation?.custom_3d_model?.default_camera_angle_x ?? 0),
      default_camera_angle_y: Number(variation?.custom_3d_model?.default_camera_angle_y ?? 15),
      default_zoom_level: Number(variation?.custom_3d_model?.default_zoom_level ?? 1.5),
      length_cm: variation.length_cm ?? variation.product?.length_cm ?? null,
      width_cm: variation.width_cm ?? variation.product?.width_cm ?? null,
      height_cm: variation.height_cm ?? variation.product?.height_cm ?? null,
      weight_kg: variation.weight_kg ?? variation.product?.weight_kg ?? null,
      is_active: variation.is_active
    })

    selectedProduct.value = variation.product
    variantImagePreviewUrl.value = getModelUrl(variation.custom_image)
    if (variation.product_id) {
      await loadProductModels(variation.product_id)
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load variation',
      life: 5000
    })
    if (!embedded.value) {
      if (isInventoryContext.value) {
        router.push({ name: 'inventory.products.index' })
      } else {
        router.push({ name: 'merchandising.variations' })
      }
    }
  } finally {
    loadingData.value = false
  }
}

const onProductChange = () => {
  generateSKU()
}

const handleVariantModelSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input?.files?.[0] || null
  if (file && file.size > 100 * 1024 * 1024) {
    toast.add({
      severity: 'warn',
      summary: 'File too large',
      detail: '3D model must be 100MB or smaller.',
      life: 3000
    })
    input.value = ''
    variantModelFile.value = null
    return
  }
  variantModelFile.value = file
}

const handleVariantImageSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input?.files?.[0] || null
  variantImageFile.value = file

  if (variantImagePreviewUrl.value) {
    URL.revokeObjectURL(variantImagePreviewUrl.value)
    variantImagePreviewUrl.value = ''
  }

  if (file) {
    variantImagePreviewUrl.value = URL.createObjectURL(file)
  }
}

const clearImagePreview = () => {
  if (variantImagePreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(variantImagePreviewUrl.value)
  }
  variantImagePreviewUrl.value = ''
}

const confirmRemoveModel = () => {
  confirm.require({
    header: 'Remove 3D model?',
    message: 'The 3D model will be unlinked from this variation after you save.',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Keep',
    acceptLabel: 'Remove',
    acceptClass: 'p-button-danger',
    accept: () => {
      variantModelFile.value = null
      form.custom_3d_model_id = null
      const input = document.getElementById('variant_model_upload') as HTMLInputElement | null
      if (input) input.value = ''
    },
  })
}

const confirmRemoveImage = () => {
  confirm.require({
    header: 'Remove photo?',
    message: 'The photo will be unlinked from this variation after you save.',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Keep',
    acceptLabel: 'Remove',
    acceptClass: 'p-button-danger',
    accept: () => {
      variantImageFile.value = null
      form.custom_image_id = null
      clearImagePreview()
      const input = document.getElementById('variant_image_upload') as HTMLInputElement | null
      if (input) input.value = ''
    },
  })
}

const uploadVariantModelIfNeeded = async (): Promise<number | null> => {
  if (!variantModelFile.value) return form.custom_3d_model_id || null
  if (!form.product_id) return null

  uploadingModel.value = true
  try {
    const formData = new FormData()
    formData.append('product_id', String(form.product_id))
    formData.append('asset_type', '3D_Model')
    formData.append('asset_file', variantModelFile.value)
    const ext = variantModelFile.value.name.split('.').pop()?.toLowerCase() || 'glb'
    if (['glb', 'gltf', 'obj', 'fbx', 'usdz'].includes(ext)) {
      formData.append('model_format', ext)
    }
    formData.append('is_primary', '0')
    formData.append('default_camera_angle_x', String(form.default_camera_angle_x ?? 0))
    formData.append('default_camera_angle_y', String(form.default_camera_angle_y ?? 15))
    formData.append('default_zoom_level', String(form.default_zoom_level ?? 1.5))

    const response = await merchandisingService.uploadAsset(formData)
    const uploaded = response.data || null
    return uploaded?.id || null
  } catch (error: any) {
    const validationErrors = error?.response?.data?.errors
    const firstValidationMessage = validationErrors
      ? Object.values(validationErrors)?.[0]?.[0]
      : null
    toast.add({
      severity: 'error',
      summary: 'Model Upload Failed',
      detail: firstValidationMessage || error?.response?.data?.message || 'Unable to upload variant 3D model',
      life: 4000
    })
    throw error
  } finally {
    uploadingModel.value = false
  }
}

const uploadVariantImageIfNeeded = async (): Promise<number | null> => {
  if (!variantImageFile.value) return form.custom_image_id || null
  if (!form.product_id) return null

  try {
    const formData = new FormData()
    formData.append('product_id', String(form.product_id))
    formData.append('asset_type', 'Image_Gallery')
    formData.append('asset_file', variantImageFile.value)
    formData.append('is_primary', '0')
    formData.append('display_order', '0')

    const response = await merchandisingService.uploadAsset(formData)
    const uploaded = response.data || null
    return uploaded?.id || null
  } catch (error: any) {
    const validationErrors = error?.response?.data?.errors
    const firstValidationMessage = validationErrors
      ? Object.values(validationErrors)?.[0]?.[0]
      : null
    toast.add({
      severity: 'error',
      summary: 'Photo Upload Failed',
      detail: firstValidationMessage || error?.response?.data?.message || 'Unable to upload variant photo',
      life: 4000
    })
    throw error
  }
}

const generateSKU = () => {
  if (!selectedProduct.value) return
  
  const baseSKU = selectedProduct.value.sku
  const attributes = [
    form.color?.substring(0, 3).toUpperCase(),
    form.size?.substring(0, 2).toUpperCase(),
    form.material?.substring(0, 3).toUpperCase(),
    !form.color && !form.size && !form.material
      ? form.variation_name?.replace(/[^a-zA-Z0-9]/g, '').substring(0, 4).toUpperCase()
      : ''
  ].filter(Boolean).join('-')
  
  form.variation_sku = attributes ? `${baseSKU}-${attributes}` : baseSKU
}

const ensureUniqueVariationSku = async () => {
  if (!form.product_id || !form.variation_sku) return

  try {
    const response = await merchandisingService.getVariationsByProduct(Number(form.product_id))
    const payload = response.data || {}
    const rows = payload?.variations || payload?.data?.variations || []

    const currentId = isEditMode.value
      ? (embedded.value ? Number(embeddedVariation.value?.id) : Number(route.params.id))
      : null
    const existing = rows
      .filter((v: any) => !currentId || Number(v.id) !== currentId)
      .map((v: any) => String(v.variation_sku || '').toUpperCase())

    let candidate = String(form.variation_sku).toUpperCase()
    if (!existing.includes(candidate)) {
      form.variation_sku = candidate
      return
    }

    let idx = 2
    while (existing.includes(`${candidate}-${idx}`)) idx++
    form.variation_sku = `${candidate}-${idx}`
  } catch {
    // non-blocking; backend will still enforce uniqueness
  }
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.product_id) {
    errors.value.product_id = 'Please select a product'
  }
  
  if (!form.variation_name) {
    errors.value.variation_name = 'Variation name is required'
  }
  
  if (!form.variation_sku) {
    errors.value.variation_sku = 'Variation SKU is required'
  }

  if (form.base_price == null || Number(form.base_price) < 0) {
    errors.value.base_price = 'Selling price is required'
  }

  if (form.discounted_price != null && Number(form.discounted_price) >= Number(form.base_price || 0)) {
    errors.value.discounted_price = 'Discounted price must be lower than the selling price'
  }

  if (!isEditMode.value && !variantImageFile.value && !proposal.value?.image_urls?.length) {
    errors.value.custom_image_id = 'Variation photo is required'
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
  
  submitting.value = true
  
  try {
    // Calculate final price
    await ensureUniqueVariationSku()
    const uploadedModelId = await uploadVariantModelIfNeeded()
    const uploadedImageId = await uploadVariantImageIfNeeded()

    const submitData = {
      ...form,
      proposal_id: proposalId.value,
      custom_3d_model_id: uploadedModelId,
      custom_image_id: uploadedImageId
    }

    if (isEditMode.value) {
      const variationId = embedded.value ? Number(embeddedVariation.value?.id) : Number(route.params.id)
      await merchandisingService.updateVariation(variationId, submitData)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Variation updated successfully',
        life: 3000
      })
    } else {
      await merchandisingService.createVariation(submitData as any)
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Variation created successfully',
        life: 3000
      })
    }

    if (embedded.value) {
      emit('saved')
      return
    }

    router.push({ name: 'inventory.products.detail', params: { id: form.product_id } })
  } catch (error: any) {
    console.error('Form submission error:', error)
    
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    const firstValidationMessage = error?.response?.data?.errors
      ? Object.values(error.response.data.errors)?.[0]?.[0]
      : null
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: firstValidationMessage || error.response?.data?.message || 'Failed to save variation',
      life: 5000
    })
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  if (form.product_id) {
    router.push({ name: 'inventory.products.detail', params: { id: form.product_id } })
    return
  }
  router.push({ name: 'inventory.products.index' })
}

onMounted(() => {
  loadUnits()
  if (embedded.value) {
    const productId = Number(embeddedProduct.value?.id || 0)
    if (productId) {
      form.product_id = productId
      selectedProduct.value = embeddedProduct.value
      loadProductModels(productId)
    }
    if (embeddedVariation.value?.id) {
      loadVariation()
    } else {
      generateSKU()
    }
    return
  }

  Promise.resolve(loadProducts()).then(async () => {
    const productFromQuery = route.query.product_id
      ? Number(route.query.product_id)
      : (route.params.productId ? Number(route.params.productId) : null)
    if (!isEditMode.value && productFromQuery) {
      form.product_id = productFromQuery
      selectedProduct.value = products.value.find((p: any) => p.id === productFromQuery) || null
      await loadProductModels(productFromQuery)
    }
    if (!isEditMode.value && proposalId.value) {
      const response = await merchandisingService.getVariationRequests()
      const rows = response?.data || []
      proposal.value = rows.find((item: any) => Number(item.id) === Number(proposalId.value)) || null
      if (proposal.value) {
        const p = proposal.value
        form.product_id = Number(p.rfq_item?.product_id || p.rfq_item?.product?.id)
        selectedProduct.value = products.value.find((item: any) => Number(item.id) === Number(form.product_id)) || p.rfq_item?.product || null
        form.variation_name = p.variant_name || ''
        form.color = p.variant_color || ''
        form.size = p.variant_size || ''
        form.material = p.variant_material || ''
        form.texture = p.variant_texture || ''
        form.finish = p.variant_finish || ''
        form.base_price = Number(selectedProduct.value?.base_price || 0)
        form.unit_of_measurement = p.unit_of_measurement || ''
        form.length_cm = Number(p.length_cm || 0) || null
        form.width_cm = Number(p.width_cm || 0) || null
        form.height_cm = Number(p.height_cm || 0) || null
        form.weight_kg = Number(p.weight_kg || 0) || null
        variantImagePreviewUrl.value = p.image_urls?.[0] || ''
        generateSKU()
      }
    }
    await loadVariation()
  })
})

onBeforeUnmount(() => {
  if (variantImagePreviewUrl.value) {
    URL.revokeObjectURL(variantImagePreviewUrl.value)
  }
})
</script>

<style scoped>
:deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}

:deep(.p-card-content) {
  padding-top: 0.75rem;
}

:deep(.p-inputtext),
:deep(.p-select-label),
:deep(.p-inputnumber-input),
:deep(.p-button) {
  font-size: 0.8125rem;
}
</style>
