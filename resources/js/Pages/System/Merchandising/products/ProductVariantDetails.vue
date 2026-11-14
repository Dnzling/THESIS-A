<template>
  <div class="mx-auto max-w-6xl space-y-4 px-4 py-5 text-sm sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button
          icon="pi pi-chevron-left"
          aria-label="Go back"
          v-tooltip.top="'Go back'"
          rounded
          class="h-9 w-9 border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50"
          @click="goBack"
        />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">{{ variant?.product?.product_name || 'Product Variant Details' }}</h1>
          <p class="mt-0.5 text-xs text-slate-500">{{ variant?.variation_name || 'Loading variant...' }}</p>
        </div>
      </div>
      <Button
        v-if="variant?.product"
        label="View Product Details"
        icon="pi pi-box"
        size="small"
        text
        @click="openProduct"
      />
      <div v-if="variant && authStore.hasPermission('merchandising.variations.manage')" class="flex gap-2">
        <Button label="Edit" icon="pi pi-pencil" size="small" severity="warn" @click="editVariant" />
        <Button
          v-if="variant.is_active"
          label="Archive Variant"
          icon="pi pi-box"
          size="small"
          severity="danger"
          outlined
          @click="archiveDialogVisible = true"
        />
      </div>
    </div>

    <div v-if="loading" class="grid gap-4 md:grid-cols-2">
      <Skeleton height="250px" class="rounded-xl" />
      <Skeleton height="250px" class="rounded-xl" />
    </div>

    <template v-else-if="variant">
      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm lg:col-span-2">
          <template #title>
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ variant.variation_name }}</h2>
                <p class="mt-1 text-xs text-slate-500">Parent product: {{ variant.product?.product_name || 'Product' }}</p>
              </div>
              <Badge :value="variant.is_active ? 'Active' : 'Inactive'" :severity="variant.is_active ? 'success' : 'secondary'" />
            </div>
          </template>
          <template #content>
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
              <div>
                <dt class="text-xs text-slate-500">Variant SKU</dt>
                <dd class="mt-1 font-mono font-medium text-slate-900">{{ variant.variation_sku || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Unit of Measurement</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ variant.unit_of_measurement || '—' }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-xs text-slate-500">Attributes</dt>
                <dd class="mt-2 flex flex-wrap gap-2">
                  <Tag v-if="variant.color" :value="`Color: ${variant.color}`" severity="info" />
                  <Tag v-if="variant.size" :value="`Size: ${variant.size}`" severity="secondary" />
                  <Tag v-if="variant.material" :value="`Material: ${variant.material}`" severity="success" />
                  <Tag v-if="variant.texture" :value="`Texture: ${variant.texture}`" />
                  <Tag v-if="variant.finish" :value="`Finish: ${variant.finish}`" />
                  <span v-if="!hasAttributes" class="text-slate-400">No attributes recorded</span>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Reorder Point</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ variant.reorder_point ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Supplier</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ variant.supplier_name || '—' }}</dd>
              </div>
            </dl>
          </template>
        </Card>

        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base font-semibold">Pricing</span></template>
          <template #content>
            <dl class="space-y-4">
              <div>
                <dt class="text-xs text-slate-500">Selling Price</dt>
                <dd class="mt-1 text-xl font-semibold text-slate-900">{{ formatMoney(variant.final_price) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Base Price</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ formatMoney(variant.base_price ?? variant.product?.base_price) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Discounted Price</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ formatMoney(variant.discounted_price) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Price Adjustment</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ formatMoney(variant.price_adjustment) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-slate-500">Cost Price</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ formatMoney(variant.cost_price) }}</dd>
              </div>
            </dl>
          </template>
        </Card>
      </div>

      <Card class="border border-slate-200 shadow-sm">
        <template #title><span class="text-base font-semibold">Dimensions & Media</span></template>
        <template #content>
          <div class="grid gap-6 md:grid-cols-2">
            <dl class="grid grid-cols-2 gap-4">
              <div v-for="item in dimensionItems" :key="item.label">
                <dt class="text-xs text-slate-500">{{ item.label }}</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ item.value }}</dd>
              </div>
            </dl>
            <div class="space-y-3">
              <div v-if="variant.customImage" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3">
                <i class="pi pi-image text-slate-500" />
                <div class="min-w-0">
                  <p class="text-xs text-slate-500">Variant Image</p>
                  <p class="truncate font-medium text-slate-900">{{ variant.customImage.file_name }}</p>
                </div>
                <Button label="Open" icon="pi pi-external-link" size="small" text @click="openAsset(variant.customImage.url)" />
              </div>
              <div v-if="variant.custom3dModel" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3">
                <i class="pi pi-box text-slate-500" />
                <div class="min-w-0">
                  <p class="text-xs text-slate-500">Variant 3D Model</p>
                  <p class="truncate font-medium text-slate-900">{{ variant.custom3dModel.file_name }}</p>
                </div>
                <Button label="Open" icon="pi pi-external-link" size="small" text @click="openAsset(variant.custom3dModel.url)" />
              </div>
              <p v-if="!variant.customImage && !variant.custom3dModel" class="text-sm text-slate-500">No variant-specific media attached.</p>
            </div>
          </div>
        </template>
      </Card>
    </template>

    <Dialog v-model:visible="archiveDialogVisible" modal header="Archive Variant" :closable="!archiving" :style="{ width: 'min(28rem, calc(100vw - 2rem))' }">
      <div class="flex items-start gap-3">
        <i class="pi pi-exclamation-triangle mt-0.5 text-xl text-amber-600" />
        <div>
          <p class="font-medium text-slate-900">Archive {{ variant?.variation_name }}?</p>
          <p class="mt-1 text-sm text-slate-600">A variant can only be archived when it has no stock in any branch and another active variant remains for this product.</p>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined size="small" :disabled="archiving" @click="archiveDialogVisible = false" />
          <Button label="Archive Variant" severity="warn" size="small" :loading="archiving" @click="archiveVariant" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'
import axiosClient from '@/axios'
import { useAuthStore } from '@/stores/auth'
import { showResponseDialog } from '@/utils/responseDialogBus'
import merchandisingService from '@/services/merchandising.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const variant = ref<any>(null)
const loading = ref(true)
const archiving = ref(false)
const archiveDialogVisible = ref(false)
const productId = computed(() => Number(route.params.id))
const variantId = computed(() => Number(route.params.variantId))
const hasAttributes = computed(() => ['color', 'size', 'material', 'texture', 'finish'].some((key) => variant.value?.[key]))
const dimensionItems = computed(() => [
  { label: 'Length', value: formatDimension(variant.value?.length_cm) },
  { label: 'Width', value: formatDimension(variant.value?.width_cm) },
  { label: 'Height', value: formatDimension(variant.value?.height_cm) },
  { label: 'Depth', value: formatDimension(variant.value?.depth_cm) },
  { label: 'Weight', value: variant.value?.weight_kg == null ? '—' : `${variant.value.weight_kg} kg` },
])

const loadVariant = async () => {
  loading.value = true
  try {
    const response = await merchandisingService.getVariation(variantId.value)
    const data = response?.data
    if (!data || Number(data.product_id) !== productId.value) {
      throw new Error('This variant does not belong to the selected product.')
    }
    variant.value = data
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load variant', detail: error?.response?.data?.message || error?.message || 'Failed to load product variant details.', life: 4000 })
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  if (window.history.length > 1) router.back()
  else openProduct()
}

const openProduct = () => router.push({ name: 'merchandising.products.view', params: { id: productId.value } })
const editVariant = () => router.push({ name: 'merchandising.variations.edit', params: { id: variantId.value } })
const formatMoney = (value: unknown) => value == null || value === '' ? '—' : new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value))
const formatDimension = (value: unknown) => value == null || value === '' ? '—' : `${value} cm`

const archiveVariant = async () => {
  if (!variant.value || archiving.value) return
  archiving.value = true
  try {
    await merchandisingService.archiveVariation(variantId.value)
    archiveDialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Variant Archived', detail: 'The variant was archived and removed from the active product listing.', life: 3500 })
    openProduct()
  } catch (error: any) {
    const response = error?.response
    if (response?.status === 422) {
      showResponseDialog({
        severity: 'warn',
        title: 'Cannot Archive Variant',
        message: response.data?.message || 'Resolve the variant archive conditions and try again.'
      })
    } else {
      toast.add({ severity: 'error', summary: 'Archive Failed', detail: response?.data?.message || 'Failed to archive the variant.', life: 4000 })
    }
  } finally {
    archiving.value = false
  }
}

const openAsset = async (url?: string) => {
  if (!url) return
  const mediaWindow = window.open('about:blank', '_blank')
  if (!mediaWindow) return
  mediaWindow.opener = null
  try {
    const response = await axiosClient.get(url, { responseType: 'blob' })
    const objectUrl = URL.createObjectURL(response.data)
    mediaWindow.location.href = objectUrl
    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60_000)
  } catch (error: any) {
    mediaWindow.close()
    toast.add({ severity: 'error', summary: 'Unable to open media', detail: error?.response?.data?.message || 'The variant media could not be loaded.', life: 4000 })
  }
}

onMounted(loadVariant)
</script>
