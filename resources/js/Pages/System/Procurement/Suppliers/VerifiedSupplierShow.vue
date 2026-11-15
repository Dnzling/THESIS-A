<template>
  <div class="max-w-5xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers.create' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Verified Supplier Details</h2>
          <p class="text-sm text-gray-500 mt-1">Review the supplier profile before linking to your store.</p>
        </div>
      </div>
      <Button
        :label="detail?.already_linked ? 'View in Procurement' : 'Link Supplier'"
        :icon="detail?.already_linked ? 'pi pi-eye' : 'pi pi-link'"
        severity="success"
        :disabled="detail?.already_linked ? !detail?.linked_supplier_id : false"
        :loading="linking"
        @click="detail?.already_linked ? viewLinkedSupplier() : linkSupplier()"
      />
    </div>

    <Card v-if="loading">
      <template #content>
        <div class="py-8 text-center text-slate-500">Loading supplier details...</div>
      </template>
    </Card>

    <template v-else-if="detail">
      <Card>
        <template #content>
          <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
              <img v-if="detail.logo_url" :src="detail.logo_url" :alt="`${detail.company_name || detail.supplier_name} logo`" class="h-full w-full object-contain" />
              <i v-else class="pi pi-building text-3xl text-slate-400" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <h3 class="text-lg font-semibold text-slate-900">{{ detail.company_name || detail.supplier_name || 'Supplier' }}</h3>
                <Tag value="Verified" severity="success" />
              </div>
              <p v-if="detail.company_name && detail.supplier_name" class="mt-1 text-sm text-slate-500">{{ detail.supplier_name }}</p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <div v-if="detail.rating !== null" class="flex items-center gap-1.5">
                  <Rating :modelValue="Number(detail.rating)" :cancel="false" :readonly="true" />
                  <span class="text-sm font-medium text-slate-700">{{ Number(detail.rating).toFixed(1) }}/5</span>
                  <span class="text-xs text-slate-500">({{ detail.rating_count }} ratings from other stores)</span>
                </div>
                <span v-else class="text-sm text-slate-500">No ratings from other stores yet</span>
              </div>
            </div>
          </div>
          <div class="mt-5 grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 text-sm md:grid-cols-2">
            <div>
              <p class="text-slate-500">Contact Person</p>
              <p class="font-semibold text-slate-900">{{ detail.contact_person || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Email</p>
              <p class="font-semibold text-slate-900">{{ detail.email || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Phone</p>
              <p class="font-semibold text-slate-900">{{ detail.phone || '-' }}</p>
            </div>
            <div>
              <p class="text-slate-500">Verified At</p>
              <p class="font-semibold text-slate-900">{{ formatDate(detail.verified_at) }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-slate-500">Address</p>
              <p class="font-semibold text-slate-900">{{ fullAddress }}</p>
            </div>
          </div>

          <div v-if="detail.already_linked" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-700 text-sm">
            This supplier is already linked to your store.
          </div>
        </template>
      </Card>

      <Card>
        <template #header>
          <div class="flex items-center justify-between px-4 pt-4">
            <div><span class="font-semibold text-slate-800">Supplier Products</span><p class="mt-1 text-xs text-slate-500">Catalog, variants, specifications, and supplier terms.</p></div>
            <Tag :value="`${detail.products?.length || 0} products`" severity="secondary" />
          </div>
        </template>
        <template #content>
          <div v-if="detail.products?.length" class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="product in detail.products" :key="product.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div class="relative flex h-48 items-center justify-center bg-slate-50">
                <img v-if="product.images?.[0]?.url" :src="product.images[0].url" :alt="product.images[0].alt_text || product.product_name" class="h-full w-full object-contain p-3" />
                <i v-else class="pi pi-image text-4xl text-slate-300" />
                <Tag :value="product.is_active ? 'Active' : 'Inactive'" :severity="product.is_active ? 'success' : 'secondary'" class="absolute right-3 top-3" />
              </div>
              <div class="space-y-4 p-4">
                <div>
                  <h3 class="font-semibold text-slate-900">{{ product.product_name || product.name }}</h3>
                  <p class="mt-1 text-xs text-slate-500">Supplier SKU: {{ product.supplier_sku || '-' }}</p>
                  <p class="text-xs text-slate-500">Product SKU: {{ product.sku || '-' }}</p>
                  <p v-if="product.description" class="mt-2 line-clamp-3 whitespace-pre-line text-sm text-slate-600">{{ product.description }}</p>
                </div>

                <div class="flex flex-wrap gap-1.5">
                  <Tag v-if="product.category_name" :value="product.category_name" severity="secondary" />
                  <Tag v-if="product.subcategory_name" :value="product.subcategory_name" severity="secondary" />
                  <Tag v-if="product.product_type" :value="formatLabel(product.product_type)" severity="info" />
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 text-xs">
                  <div><p class="text-slate-500">Unit</p><p class="mt-1 font-medium text-slate-800">{{ product.unit_of_measurement || '-' }}</p></div>
                  <div><p class="text-slate-500">Availability</p><p class="mt-1 font-medium text-slate-800">{{ formatLabel(product.availability_status) }}</p></div>
                  <div><p class="text-slate-500">Minimum order</p><p class="mt-1 font-medium text-slate-800">{{ product.minimum_order_quantity }} {{ product.unit_of_measurement || 'units' }}</p></div>
                  <div><p class="text-slate-500">Lead time</p><p class="mt-1 font-medium text-slate-800">{{ product.lead_time_days }} days</p></div>
                  <div><p class="text-slate-500">Base cost</p><p class="mt-1 font-medium text-slate-800">{{ money(product.base_cost) }}</p></div>
                  <div><p class="text-slate-500">Cost incl. tax ({{ product.tax_rate }}%)</p><p class="mt-1 font-semibold text-slate-900">{{ money(product.cost_including_tax) }}</p></div>
                </div>

                <div v-if="product.dimensions" class="space-y-1 text-xs">
                  <p class="font-medium text-slate-700">Product dimensions</p>
                  <p class="text-slate-600">{{ dimensions(product.dimensions) }}</p>
                  <p v-for="measurement in product.dimensions.additional_measurements || []" :key="`${measurement.label}-${measurement.value}`" class="text-slate-600">{{ measurement.label }}: {{ measurement.value }} {{ measurement.unit }}</p>
                </div>

                <div v-if="product.variation_attributes && Object.values(product.variation_attributes).some(Boolean)" class="flex flex-wrap gap-1.5">
                  <template v-for="(value, attribute) in product.variation_attributes" :key="attribute"><Tag v-if="value" :value="`${formatLabel(String(attribute))}: ${value}`" severity="secondary" /></template>
                </div>

                <div v-if="product.images?.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                  <img v-for="image in product.images.slice(1)" :key="image.url" :src="image.url" :alt="image.alt_text || product.product_name" class="h-12 w-14 shrink-0 rounded-md border border-slate-200 object-cover" />
                </div>

                <div v-if="product.variants?.length" class="space-y-3 border-t border-slate-200 pt-4">
                  <div class="flex items-center justify-between"><h4 class="text-sm font-semibold text-slate-800">Variants</h4><Tag :value="`${product.variants.length}`" severity="secondary" /></div>
                  <article v-for="variant in product.variants" :key="variant.id" class="rounded-lg border border-slate-200 p-3">
                    <div class="flex gap-3">
                      <img v-if="variant.images?.[0]?.url" :src="variant.images[0].url" :alt="variant.images[0].alt_text || variant.name" class="h-16 w-16 rounded-md border border-slate-200 object-cover" />
                      <div v-else class="flex h-16 w-16 shrink-0 items-center justify-center rounded-md bg-slate-50"><i class="pi pi-image text-slate-300" /></div>
                      <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2"><p class="font-medium text-slate-900">{{ variant.name || 'Variant' }}</p><Tag :value="variant.is_active ? 'Active' : 'Inactive'" :severity="variant.is_active ? 'success' : 'secondary'" /></div>
                        <p class="mt-1 text-xs text-slate-500">SKU: {{ variant.sku || '-' }} <span v-if="variant.unit_of_measurement">· {{ variant.unit_of_measurement }}</span></p>
                        <p class="mt-1 text-xs text-slate-600">Base cost {{ money(variant.base_cost) }} · Tax {{ money(variant.tax_amount) }}</p>
                        <p class="text-sm font-semibold text-slate-800">{{ money(variant.cost_including_tax) }} <span class="font-normal text-slate-500">incl. tax</span></p>
                      </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                      <Tag v-for="attribute in variantAttributes(variant)" :key="attribute" :value="attribute" severity="secondary" />
                    </div>
                    <p class="mt-2 text-xs text-slate-600">{{ dimensions(variant.dimensions) }}</p>
                    <p v-for="measurement in variant.dimensions?.additional_measurements || []" :key="`${measurement.label}-${measurement.value}`" class="mt-1 text-xs text-slate-600">{{ measurement.label }}: {{ measurement.value }} {{ measurement.unit }}</p>
                    <div v-if="variant.images?.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                      <img v-for="image in variant.images.slice(1)" :key="image.url" :src="image.url" :alt="image.alt_text || variant.name" class="h-10 w-12 shrink-0 rounded border border-slate-200 object-cover" />
                    </div>
                  </article>
                </div>
                <p v-if="product.is_preferred_supplier" class="text-xs font-medium text-orange-700"><i class="pi pi-star-fill mr-1" />Preferred supplier item</p>
              </div>
            </article>
          </div>
          <div v-else class="px-4 py-8 text-center text-sm text-slate-500">This supplier has not listed products yet.</div>
        </template>
      </Card>

    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Rating from 'primevue/rating'
import Tag from 'primevue/tag'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const linking = ref(false)
const detail = ref<any>(null)

const portalId = computed(() => Number(route.params.portalId))

const fullAddress = computed(() => {
  if (!detail.value) return '-'
  return [detail.value.address, detail.value.city, detail.value.province, detail.value.country].filter(Boolean).join(', ') || '-'
})

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatLabel = (value?: string | null) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '-'
const dimensions = (value: any) => {
  if (!value) return '-'
  const parts = [
    value.length_cm != null ? `L ${value.length_cm} cm` : null,
    value.width_cm != null ? `W ${value.width_cm} cm` : null,
    value.height_cm != null ? `H ${value.height_cm} cm` : null,
    value.depth_cm != null ? `D ${value.depth_cm} cm` : null,
    value.weight_kg != null ? `${value.weight_kg} kg` : null,
  ].filter(Boolean)
  return parts.join(' · ') || 'No dimensions recorded'
}
const variantAttributes = (variant: any) => [
  variant.color ? `Color: ${variant.color}` : null,
  variant.size ? `Size: ${variant.size}` : null,
  variant.material ? `Material: ${variant.material}` : null,
  variant.texture ? `Texture: ${variant.texture}` : null,
  variant.finish ? `Finish: ${variant.finish}` : null,
].filter(Boolean)

const loadDetail = async () => {
  if (!portalId.value) return
  loading.value = true
  try {
    const response = await procurementService.getVerifiedSupplierDirectoryItem(portalId.value)
    const payload = response?.data ?? response ?? {}
    detail.value = payload?.data ?? payload ?? null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load supplier details', life: 3500 })
  } finally {
    loading.value = false
  }
}

const linkSupplier = async () => {
  if (!detail.value || detail.value.already_linked) return
  linking.value = true
  try {
    await procurementService.createSupplier({ supplier_portal_id: detail.value.supplier_portal_id })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Supplier linked successfully.', life: 2500 })
    router.push({ name: 'procurement.suppliers' })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to link supplier', life: 3500 })
  } finally {
    linking.value = false
  }
}

const viewLinkedSupplier = () => {
  const id = Number(detail.value?.linked_supplier_id || 0)
  if (!id) return
  router.push({ name: 'procurement.suppliers.detail', params: { id } })
}

onMounted(loadDetail)
</script>
