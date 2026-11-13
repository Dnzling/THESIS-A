<template>
  <div class="mx-auto max-w-5xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-chevron-left" rounded outlined severity="secondary" aria-label="Go back" title="Go back" size="small" @click="goBack" />
      <div><p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Supplier portal</p><h1 class="mt-1 text-xl font-semibold text-slate-900">Create Supplier Product</h1><p class="mt-1 text-sm text-slate-500">Create one supplier catalog item and its store-specific offers.</p></div>
    </header>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <form class="space-y-6" @submit.prevent="prepareConfirmation">
          <section class="space-y-4">
            <div><h2 class="text-sm font-semibold text-slate-900">Supplier product</h2><p class="mt-1 text-xs text-slate-500">A supplier SKU is generated when this product is created.</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Product name *</label><InputText v-model="form.product_name" fluid size="small" /><small v-if="errors.product_name" class="text-red-600">{{ errors.product_name }}</small></div>
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Product type *</label><Select v-model="form.product_type" :options="productTypes" optionLabel="label" optionValue="value" fluid size="small" /></div>
              <div><div class="mb-1 flex items-center justify-between gap-2"><label class="text-sm font-medium text-slate-700">Category *</label><Button label="Add category" icon="pi pi-plus" text size="small" type="button" @click="categoryDialogVisible = true" /></div><Select v-model="form.category" :options="categories" optionLabel="label" optionValue="value" filter fluid size="small" placeholder="Select or create a category" @change="onCategoryChange" /><small v-if="errors.category" class="text-red-600">{{ errors.category }}</small></div>
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Subcategory</label><Select v-model="form.subcategory" :options="subcategoryOptions" :disabled="!form.category" fluid size="small" showClear placeholder="Select a category first" /><small class="text-xs text-slate-500">Subcategories are filtered by the selected category.</small></div>
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Unit of measurement *</label><Select v-model="form.unit_of_measurement" :options="unitOptions" optionLabel="label" optionValue="value" fluid size="small" /></div>
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Base cost *</label><InputNumber v-model="form.base_cost" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid size="small" /><small v-if="errors.base_cost" class="text-red-600">{{ errors.base_cost }}</small></div>
              <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Description</label><Textarea v-model="form.description" rows="3" autoResize fluid /></div>
            </div>
          </section>

          <section class="space-y-3 border-t border-slate-200 pt-5">
            <div><h2 class="text-sm font-semibold text-slate-900">Standard Product Variation Attributes</h2><p class="mt-1 text-xs text-slate-500">Set the standard values first. New variants start with these values, then change any attribute that distinguishes the variant.</p></div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"><div v-for="attribute in attributeFields" :key="attribute.key"><label class="mb-1 block text-xs font-medium text-slate-600">{{ attribute.label }}</label><InputText v-model="form.variation_attributes[attribute.key]" fluid size="small" :placeholder="attribute.placeholder" /></div></div>
          </section>

          <section class="space-y-3 border-t border-slate-200 pt-5">
            <div><h2 class="text-sm font-semibold text-slate-900">Dimensions and measurements</h2><p class="mt-1 text-xs text-slate-500">Dimensions are in cm and weight is in kg. Leave unknown measurements blank.</p></div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
              <div v-for="dimension in dimensionFields" :key="dimension.key"><label class="mb-1 block text-xs font-medium text-slate-600">{{ dimension.label }}</label><InputNumber v-model="form[dimension.key]" :min="0" :maxFractionDigits="2" :suffix="dimension.suffix" fluid size="small" /></div>
            </div>
            <div class="space-y-2">
              <div v-for="(measurement, index) in form.additional_measurements" :key="measurement.id" class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1fr_0.7fr_0.6fr_auto] sm:items-end">
                <div><label class="mb-1 block text-xs text-slate-500">Measurement name</label><InputText v-model="measurement.label" size="small" fluid placeholder="Seat height" /></div>
                <div><label class="mb-1 block text-xs text-slate-500">Value</label><InputNumber v-model="measurement.value" :min="0.01" :maxFractionDigits="2" size="small" fluid /></div>
                <div><label class="mb-1 block text-xs text-slate-500">Unit</label><Select v-model="measurement.unit" :options="measurementUnits" size="small" fluid /></div>
                <Button icon="pi pi-trash" severity="danger" text rounded type="button" aria-label="Remove measurement" @click="form.additional_measurements.splice(index, 1)" />
              </div>
              <Button label="Add custom measurement" icon="pi pi-plus" severity="secondary" outlined size="small" type="button" @click="addMeasurement(form)" />
            </div>
          </section>

          <section class="space-y-3 border-t border-slate-200 pt-5">
            <div><div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="text-sm font-semibold text-slate-900">Product photos</h2><p class="mt-1 text-xs text-slate-500">Upload up to 5 product photos.</p></div><span class="text-xs text-slate-500">{{ productImages.length }}/5</span></div></div>
            <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-orange-700" @change="selectProductImages" />
            <small v-if="errors.product_images" class="text-red-600">{{ errors.product_images }}</small>
            <div v-if="productImages.length" class="grid grid-cols-2 gap-3 sm:grid-cols-5"><div v-for="(file, index) in productImages" :key="`${file.name}-${index}`" class="relative rounded-lg border border-slate-200 p-2"><img :src="preview(file)" :alt="file.name" class="h-24 w-full rounded object-cover" /><p class="mt-1 truncate text-xs text-slate-600">{{ file.name }}</p><Button icon="pi pi-times" rounded severity="danger" size="small" class="absolute right-1 top-1 !h-7 !w-7" aria-label="Remove photo" @click="removeProductImage(index)" /></div></div>
          </section>

          <section class="space-y-3 border-t border-slate-200 pt-5">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Variants</h2><p class="mt-1 text-xs text-slate-500">New variants are prefilled from the standard attributes. Change at least one attribute so the variant is distinct.</p></div><Button label="Add variant" icon="pi pi-plus" severity="secondary" outlined size="small" type="button" @click="addVariant" /></div>
            <small v-if="errors.variants" class="block text-red-600">{{ errors.variants }}</small>
            <div v-for="(variant, index) in variants" :key="variant.id" class="space-y-4 rounded-xl border border-slate-200 p-4">
              <div class="flex items-center justify-between"><h3 class="text-sm font-semibold text-slate-800">Variant {{ index + 1 }}</h3><Button icon="pi pi-trash" severity="danger" text rounded size="small" type="button" aria-label="Remove variant" @click="variants.splice(index, 1)" /></div>
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="mb-1 block text-xs text-slate-600">Variant name *</label><InputText v-model="variant.variation_name" fluid size="small" placeholder="Walnut / 3-seater" /></div>
                <div><label class="mb-1 block text-xs text-slate-600">Color</label><InputText v-model="variant.color" fluid size="small" /></div>
                <div><label class="mb-1 block text-xs text-slate-600">Size</label><InputText v-model="variant.size" fluid size="small" /></div>
                <div><label class="mb-1 block text-xs text-slate-600">Material</label><InputText v-model="variant.material" fluid size="small" /></div>
                <div><label class="mb-1 block text-xs text-slate-600">Texture</label><InputText v-model="variant.texture" fluid size="small" /></div>
                <div><label class="mb-1 block text-xs text-slate-600">Finish</label><InputText v-model="variant.finish" fluid size="small" /></div>
                <div><label class="mb-1 block text-xs text-slate-600">Variant cost (optional)</label><InputNumber v-model="variant.base_cost" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid size="small" /></div>
              </div>
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"><div v-for="dimension in dimensionFields" :key="dimension.key"><label class="mb-1 block text-xs text-slate-600">{{ dimension.label }}</label><InputNumber v-model="variant[dimension.key]" :min="0" :maxFractionDigits="2" :suffix="dimension.suffix" fluid size="small" /></div></div>
              <div class="space-y-2"><div v-for="(measurement, measureIndex) in variant.additional_measurements" :key="measurement.id" class="grid gap-2 sm:grid-cols-[1fr_0.7fr_0.6fr_auto] sm:items-end"><InputText v-model="measurement.label" size="small" fluid placeholder="Custom measurement" /><InputNumber v-model="measurement.value" :min="0.01" :maxFractionDigits="2" size="small" fluid /><Select v-model="measurement.unit" :options="measurementUnits" size="small" fluid /><Button icon="pi pi-times" text rounded severity="danger" size="small" type="button" @click="variant.additional_measurements.splice(measureIndex, 1)" /></div><Button label="Add variant measurement" icon="pi pi-plus" text size="small" type="button" @click="addMeasurement(variant)" /></div>
              <div><label class="mb-1 block text-xs font-medium text-slate-600">Variant photos (up to 5)</label><input type="file" multiple accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-orange-700" @change="selectVariantImages(index, $event)" /><small v-if="errors[`variant_images.${index}`]" class="text-red-600">{{ errors[`variant_images.${index}`] }}</small><div v-if="variant.images.length" class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5"><div v-for="(file, imageIndex) in variant.images" :key="`${file.name}-${imageIndex}`" class="relative"><img :src="preview(file)" :alt="file.name" class="h-20 w-full rounded border border-slate-200 object-cover" /><Button icon="pi pi-times" rounded severity="danger" size="small" class="absolute right-1 top-1 !h-6 !w-6" aria-label="Remove variant photo" @click="variant.images.splice(imageIndex, 1)" /></div></div></div>
            </div>
          </section>

          <section class="space-y-4 border-t border-slate-200 pt-5">
            <div><h2 class="text-sm font-semibold text-slate-900">Store offer defaults</h2><p class="mt-1 text-xs text-slate-500">These defaults create an offer for each store linked to your supplier account. Tax is calculated per store.</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Minimum order quantity *</label><InputNumber v-model="form.minimum_order_quantity" :min="1" :maxFractionDigits="0" fluid size="small" /></div>
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Lead time *</label><InputNumber v-model="form.lead_time_days" :min="0" :maxFractionDigits="0" suffix=" days" fluid size="small" /></div>
              <div><label class="mb-1 block text-sm font-medium text-slate-700">Availability *</label><Select v-model="form.availability_status" :options="availabilityOptions" optionLabel="label" optionValue="value" fluid size="small" /></div>
              <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700"><Checkbox v-model="form.is_active" binary /> Active offer</label>
            </div>
          </section>

          <Message severity="info" :closable="false">Stock is represented as Available, Limited, or Out of stock. Exact supplier quantities are not required.</Message>
          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4"><Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="goBack" /><Button type="submit" label="Review and Create" icon="pi pi-arrow-right" iconPos="right" severity="warn" size="small" :disabled="loadingOptions || !categories.length" /></div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="confirmationVisible" modal header="Confirm Supplier Product" :style="{ width: 'min(94vw, 40rem)' }">
      <div class="space-y-4 text-sm"><div class="rounded-lg bg-slate-50 p-3"><p class="font-semibold text-slate-900">{{ form.product_name }}</p><p class="mt-1 text-xs text-slate-500">{{ form.category }} · {{ form.unit_of_measurement }} · {{ variants.length }} variant{{ variants.length === 1 ? '' : 's' }}</p></div><div><p class="font-medium text-slate-800">Tax-inclusive store offer cost</p><div class="mt-2 divide-y divide-slate-200 rounded-lg border border-slate-200"><div v-for="store in stores" :key="store.id" class="flex items-center justify-between gap-3 px-3 py-2"><div><p class="font-medium text-slate-800">{{ store.name }}</p><p class="text-xs text-slate-500">{{ store.is_tax_exempt ? 'Tax exempt' : `${number(store.tax_rate)}% tax` }}</p></div><p class="font-semibold text-slate-900">{{ money(costWithTax(store.tax_rate)) }}</p></div></div></div></div>
      <template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="confirmationVisible = false" /><Button label="Confirm and Create" severity="warn" size="small" :loading="submitting" @click="submit" /></template>
    </Dialog>

    <Dialog v-model:visible="categoryDialogVisible" modal header="Add Supplier Category" :style="{ width: 'min(92vw, 28rem)' }">
      <label class="mb-1 block text-sm font-medium text-slate-700">Category name *</label><InputText v-model="newCategoryName" fluid size="small" maxlength="120" @keyup.enter="createCategory" /><small v-if="categoryError" class="mt-1 block text-red-600">{{ categoryError }}</small>
      <template #footer><Button label="Cancel" severity="secondary" outlined size="small" @click="categoryDialogVisible = false" /><Button label="Save Category" severity="warn" size="small" :loading="savingCategory" @click="createCategory" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loadingOptions = ref(false)
const submitting = ref(false)
const savingCategory = ref(false)
const confirmationVisible = ref(false)
const categoryDialogVisible = ref(false)
const categoryError = ref('')
const newCategoryName = ref('')
const categories = ref<any[]>([])
const stores = ref<any[]>([])
const units = ref<string[]>([])
const productImages = ref<File[]>([])
const variants = ref<any[]>([])
const errors = ref<Record<string, string>>({})
const dimensionFields = [{ key: 'length_cm', label: 'Length', suffix: ' cm' }, { key: 'width_cm', label: 'Width', suffix: ' cm' }, { key: 'height_cm', label: 'Height', suffix: ' cm' }, { key: 'depth_cm', label: 'Depth', suffix: ' cm' }, { key: 'weight_kg', label: 'Weight', suffix: ' kg' }] as const
const attributeFields = [{ key: 'color', label: 'Color', placeholder: 'e.g. Walnut' }, { key: 'size', label: 'Size', placeholder: 'e.g. Standard' }, { key: 'material', label: 'Material', placeholder: 'e.g. Oak wood' }, { key: 'texture', label: 'Texture', placeholder: 'e.g. Smooth' }, { key: 'finish', label: 'Finish', placeholder: 'e.g. Matte' }] as const
const measurementUnits = ['cm', 'mm', 'm', 'in', 'ft', 'kg', 'g', 'pcs']
const availabilityOptions = [{ label: 'Available', value: 'available' }, { label: 'Limited', value: 'limited' }, { label: 'Out of stock', value: 'out_of_stock' }]
const productTypes = [{ label: 'Product', value: 'finished_good' }, { label: 'Supply', value: 'supply' }, { label: 'Raw Material', value: 'raw_material' }, { label: 'Other', value: 'other' }]
const unitLabels: Record<string, string> = { pcs: 'Pieces (pcs)', set: 'Set', pair: 'Pair', box: 'Box', kg: 'Kilogram', g: 'Gram', lb: 'Pound', m: 'Meter', cm: 'Centimeter', mm: 'Millimeter', L: 'Liter', mL: 'Milliliter', pack: 'Pack', roll: 'Roll', ft: 'Foot', in: 'Inch' }
const form = reactive<any>({ product_name: '', product_type: 'finished_good', category: null, subcategory: null, description: '', unit_of_measurement: null, base_cost: null, minimum_order_quantity: 1, lead_time_days: 7, availability_status: 'available', is_active: true, length_cm: null, width_cm: null, height_cm: null, depth_cm: null, weight_kg: null, additional_measurements: [] as any[], variation_attributes: { color: '', size: '', material: '', texture: '', finish: '' } })
const unitOptions = computed(() => units.value.map(value => ({ value, label: unitLabels[value] || value })))
const subcategoryOptions = computed(() => categories.value.find(category => category.value === form.category)?.subcategories || [])

const loadOptions = async () => { loadingOptions.value = true; try { const response = await supplierService.getProductListingOptions(); categories.value = response.data?.categories || []; stores.value = response.data?.stores || []; units.value = response.data?.units || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load form', detail: error?.response?.data?.message || 'Product options could not be loaded.', life: 3000 }) } finally { loadingOptions.value = false } }
const addMeasurement = (target: any) => target.additional_measurements.push({ id: `${Date.now()}-${Math.random()}`, label: '', value: null, unit: 'cm' })
const onCategoryChange = () => { form.subcategory = null }
const addVariant = () => variants.value.push({ id: `${Date.now()}-${Math.random()}`, variation_name: '', ...Object.fromEntries(attributeFields.map(attribute => [attribute.key, form.variation_attributes[attribute.key] || ''])), base_cost: null, length_cm: null, width_cm: null, height_cm: null, depth_cm: null, weight_kg: null, additional_measurements: [], images: [] as File[] })
const selectProductImages = (event: Event) => { const files = Array.from((event.target as HTMLInputElement).files || []); if (files.length + productImages.value.length > 5) { errors.value.product_images = 'You can upload up to 5 product photos.'; return } errors.value.product_images = ''; productImages.value.push(...files) }
const selectVariantImages = (index: number, event: Event) => { const files = Array.from((event.target as HTMLInputElement).files || []); const variant = variants.value[index]; if (!variant) return; if (files.length + variant.images.length > 5) { errors.value[`variant_images.${index}`] = 'You can upload up to 5 photos per variant.'; return } errors.value[`variant_images.${index}`] = ''; variant.images.push(...files) }
const removeProductImage = (index: number) => productImages.value.splice(index, 1)
const preview = (file: File) => URL.createObjectURL(file)
const attributeSignature = (attributes: any) => JSON.stringify(attributeFields.map(attribute => String(attributes?.[attribute.key] || '').trim().toLocaleLowerCase()))
const validate = () => { errors.value = {}; if (!form.product_name.trim()) errors.value.product_name = 'Product name is required.'; if (!form.product_type) errors.value.product_type = 'Product type is required.'; if (!form.category) errors.value.category = 'Category is required.'; if (!form.unit_of_measurement) errors.value.unit_of_measurement = 'Unit of measurement is required.'; if (!form.base_cost || form.base_cost <= 0) errors.value.base_cost = 'Base cost must be greater than zero.'; if (form.minimum_order_quantity < 1) errors.value.minimum_order_quantity = 'Minimum order quantity must be at least 1.'; if (form.lead_time_days < 0) errors.value.lead_time_days = 'Lead time cannot be negative.'; const seen = new Set([attributeSignature(form.variation_attributes)]); variants.value.forEach((variant, index) => { if (!variant.variation_name.trim()) errors.value[`variants.${index}.variation_name`] = 'Variant name is required.'; const signature = attributeSignature(variant); if (seen.has(signature)) errors.value.variants = `Variant ${index + 1} duplicates the standard product or another variant. Change at least one attribute.`; seen.add(signature) }); return Object.keys(errors.value).length === 0 }
const prepareConfirmation = () => { if (validate()) confirmationVisible.value = true }
const costWithTax = (rate: unknown) => Number(form.base_cost || 0) * (1 + Number(rate || 0) / 100)
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
const goBack = () => window.history.length > 1 ? router.back() : router.push({ name: 'supplier.products' })

const createCategory = async () => { const name = newCategoryName.value.trim(); if (!name) { categoryError.value = 'Enter a category name.'; return } savingCategory.value = true; categoryError.value = ''; try { const response = await supplierService.createProductCategory(name); const category = response.data; categories.value = [...categories.value.filter(item => item.value !== category.value), category].sort((a, b) => a.label.localeCompare(b.label)); form.category = category.value; form.subcategory = null; newCategoryName.value = ''; categoryDialogVisible.value = false } catch (error: any) { categoryError.value = error?.response?.data?.errors?.category_name?.[0] || error?.response?.data?.message || 'Unable to save this category.' } finally { savingCategory.value = false } }

const submit = async () => {
  submitting.value = true; errors.value = {}
  try {
    const payload = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (key === 'additional_measurements' || key === 'variation_attributes') payload.append(key, JSON.stringify(value))
      else payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value ?? ''))
    })
    payload.append('variants', JSON.stringify(variants.value.map(({ images, id, ...variant }) => variant)))
    productImages.value.forEach((file, index) => payload.append(`product_images[${index}]`, file))
    variants.value.forEach((variant, index) => variant.images.forEach((file: File, imageIndex: number) => payload.append(`variant_images[${index}][${imageIndex}]`, file)))
    const response = await supplierService.createProductListing(payload)
    confirmationVisible.value = false
    toast.add({ severity: 'success', summary: 'Supplier product created', detail: response.message, life: 2500 })
    router.push({ name: 'supplier.products.detail', params: { id: response.data.id } })
  } catch (error: any) {
    const apiErrors = error?.response?.data?.errors || {}
    Object.entries(apiErrors).forEach(([key, messages]: [string, any]) => { errors.value[key] = Array.isArray(messages) ? messages[0] : String(messages) })
    const variantError = Object.entries(apiErrors).find(([key]) => key.startsWith('variants.'))?.[1] as any
    if (variantError) errors.value.variants = Array.isArray(variantError) ? variantError[0] : String(variantError)
    confirmationVisible.value = false
    toast.add({ severity: 'error', summary: 'Unable to create product', detail: error?.response?.data?.message || 'Please check the form and try again.', life: 3500 })
  } finally { submitting.value = false }
}

onMounted(loadOptions)
</script>
