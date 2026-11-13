<template>
  <div class="mx-auto max-w-4xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-chevron-left" rounded outlined severity="secondary" aria-label="Go back" title="Go back" size="small" @click="goBack" />
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Supplier portal</p>
        <h1 class="mt-1 text-xl font-semibold text-slate-900">Create Product</h1>
        <p class="mt-1 text-sm text-slate-500">The product will be made available to every store linked to your supplier account.</p>
      </div>
    </header>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <form class="space-y-5" @submit.prevent="prepareConfirmation">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Product Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.product_name" fluid size="small" placeholder="Enter product name" />
              <small v-if="errors.product_name" class="text-red-600">{{ errors.product_name }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Product Type <span class="text-red-500">*</span></label>
              <Select v-model="form.product_type" :options="productTypes" optionLabel="label" optionValue="value" fluid size="small" placeholder="Select type" />
              <small v-if="errors.product_type" class="text-red-600">{{ errors.product_type }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Category <span class="text-red-500">*</span></label>
              <Select v-model="form.category" :options="categories" optionLabel="label" optionValue="value" filter fluid size="small" placeholder="Select premade category" :loading="loadingOptions" />
              <small v-if="errors.category" class="text-red-600">{{ errors.category }}</small>
              <small v-else-if="!loadingOptions && !categories.length" class="text-amber-700">No shared category is available across all linked stores.</small>
            </div>
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
              <Textarea v-model="form.description" rows="3" autoResize fluid placeholder="Optional product description" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Unit of Measurement <span class="text-red-500">*</span></label>
              <Select v-model="form.unit_of_measurement" :options="unitOptions" optionLabel="label" optionValue="value" fluid size="small" placeholder="Select unit" />
              <small v-if="errors.unit_of_measurement" class="text-red-600">{{ errors.unit_of_measurement }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Base Cost Price <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.base_cost" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid size="small" />
              <small v-if="errors.base_cost" class="text-red-600">{{ errors.base_cost }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Minimum Order Quantity <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.minimum_order_quantity" :min="1" :maxFractionDigits="0" fluid size="small" />
              <small v-if="errors.minimum_order_quantity" class="text-red-600">{{ errors.minimum_order_quantity }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Lead Time <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.lead_time_days" :min="0" :maxFractionDigits="0" suffix=" days" fluid size="small" />
              <small v-if="errors.lead_time_days" class="text-red-600">{{ errors.lead_time_days }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Available Stock <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.available_stock" :min="1" :maxFractionDigits="0" fluid size="small" />
              <small v-if="errors.available_stock" class="text-red-600">{{ errors.available_stock }}</small>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Product Image</label>
              <input type="file" accept="image/*" class="block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-orange-700" @change="selectImage" />
              <small v-if="errors.product_image" class="text-red-600">{{ errors.product_image }}</small>
            </div>
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
              <Checkbox v-model="form.is_active" binary /> Active product
            </label>
          </div>

          <Message severity="info" :closable="false">
            Supplier and catalog SKUs are generated automatically. Cost including tax is calculated separately for each linked store.
          </Message>

          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
            <Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="goBack" />
            <Button type="submit" label="Review and Create" icon="pi pi-arrow-right" iconPos="right" severity="warn" size="small" :disabled="loadingOptions || !categories.length" />
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="confirmationVisible" modal header="Confirm Product Creation" :style="{ width: 'min(94vw, 38rem)' }">
      <div class="space-y-4 text-sm">
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="font-semibold text-slate-900">{{ form.product_name }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ categoryLabel }} · {{ unitLabel }} · {{ form.available_stock }} available</p>
        </div>
        <div>
          <p class="font-medium text-slate-800">Cost by linked store</p>
          <div class="mt-2 divide-y divide-slate-200 rounded-lg border border-slate-200">
            <div v-for="store in stores" :key="store.id" class="flex items-center justify-between gap-3 px-3 py-2">
              <div><p class="font-medium text-slate-800">{{ store.name }}</p><p class="text-xs text-slate-500">{{ store.is_tax_exempt ? 'Tax exempt' : `${number(store.tax_rate)}% tax` }}</p></div>
              <p class="font-semibold text-slate-900">{{ money(costWithTax(store.tax_rate)) }}</p>
            </div>
          </div>
        </div>
        <Message severity="warn" :closable="false">Create this product for all {{ stores.length }} currently linked store{{ stores.length === 1 ? '' : 's' }}?</Message>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined size="small" @click="confirmationVisible = false" />
        <Button label="Confirm and Create" severity="warn" size="small" :loading="submitting" @click="submit" />
      </template>
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
const confirmationVisible = ref(false)
const categories = ref<any[]>([])
const stores = ref<any[]>([])
const units = ref<string[]>([])
const image = ref<File | null>(null)
const errors = ref<Record<string, string>>({})
const form = reactive({ product_name: '', product_type: null as string | null, category: null as string | null, description: '', unit_of_measurement: null as string | null, base_cost: null as number | null, minimum_order_quantity: 1, lead_time_days: 7, available_stock: 1, is_active: true })
const productTypes = [{ label: 'Product', value: 'finished_good' }, { label: 'Supply', value: 'supply' }, { label: 'Raw Material', value: 'raw_material' }, { label: 'Other', value: 'other' }]
const unitLabels: Record<string, string> = { pcs: 'Pieces (pcs)', set: 'Set', pair: 'Pair', box: 'Box', kg: 'Kilogram', g: 'Gram', lb: 'Pound', m: 'Meter', cm: 'Centimeter', mm: 'Millimeter', L: 'Liter', mL: 'Milliliter', pack: 'Pack', roll: 'Roll', ft: 'Foot', in: 'Inch' }
const unitOptions = computed(() => units.value.map(value => ({ value, label: unitLabels[value] || value })))
const categoryLabel = computed(() => categories.value.find(item => item.value === form.category)?.label || 'Category')
const unitLabel = computed(() => unitOptions.value.find(item => item.value === form.unit_of_measurement)?.label || 'Unit')

const loadOptions = async () => { loadingOptions.value = true; try { const response = await supplierService.getProductListingOptions(); categories.value = response.data?.categories || []; stores.value = response.data?.stores || []; units.value = response.data?.units || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load form', detail: error?.response?.data?.message || 'Product options could not be loaded.', life: 3000 }) } finally { loadingOptions.value = false } }
const validate = () => { errors.value = {}; if (!form.product_name.trim()) errors.value.product_name = 'Product name is required.'; if (!form.product_type) errors.value.product_type = 'Product type is required.'; if (!form.category) errors.value.category = 'Category is required.'; if (!form.unit_of_measurement) errors.value.unit_of_measurement = 'Unit of measurement is required.'; if (!form.base_cost || form.base_cost <= 0) errors.value.base_cost = 'Base cost must be greater than zero.'; if (form.minimum_order_quantity < 1) errors.value.minimum_order_quantity = 'Minimum order quantity must be at least 1.'; if (form.lead_time_days < 0) errors.value.lead_time_days = 'Lead time cannot be negative.'; if (form.available_stock < 1) errors.value.available_stock = 'Available stock must be at least 1.'; return Object.keys(errors.value).length === 0 }
const prepareConfirmation = () => { if (validate()) confirmationVisible.value = true }
const selectImage = (event: Event) => { image.value = (event.target as HTMLInputElement).files?.[0] || null }
const costWithTax = (rate: unknown) => Number(form.base_cost || 0) * (1 + Number(rate || 0) / 100)
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
const goBack = () => window.history.length > 1 ? router.back() : router.push('/supplier-portal/products')

const submit = async () => { submitting.value = true; errors.value = {}; try { const payload = new FormData(); Object.entries(form).forEach(([key, value]) => payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value ?? ''))); if (image.value) payload.append('product_image', image.value); const response = await supplierService.createProductListing(payload); confirmationVisible.value = false; toast.add({ severity: 'success', summary: 'Product created', detail: response.message, life: 2500 }); router.push(`/supplier-portal/products/${response.data.id}`) } catch (error: any) { const apiErrors = error?.response?.data?.errors || {}; Object.entries(apiErrors).forEach(([key, messages]: [string, any]) => { errors.value[key] = Array.isArray(messages) ? messages[0] : String(messages) }); confirmationVisible.value = false; toast.add({ severity: 'error', summary: 'Unable to create product', detail: error?.response?.data?.message || 'Please check the form and try again.', life: 3000 }) } finally { submitting.value = false } }

onMounted(loadOptions)
</script>
