<template>
  <div class="max-w-6xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="goBack" />
        <div>
          <h1 class="text-lg font-bold text-gray-800">{{ isEditMode ? 'Edit Product' : 'Create Product' }}</h1>
        </div>
      </div>

    </div>

    <Card>
      <template #content>
        <form class="grid grid-cols-1 gap-6 lg:grid-cols-[360px_minmax(0,1fr)]" @submit.prevent="handleSubmit">
          <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <div class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/40 p-4">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-gray-800">Item Image</p>
                  <p class="text-xs text-gray-500">Simple square preview</p>
                </div>
                <Button label="Upload" icon="pi pi-upload" size="small" class="text-sm" type="button"
                  @click="triggerImagePicker" />
              </div>
              <input ref="imageInput" type="file" accept="image/*" class="hidden" @change="onImageSelected" />
              <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="aspect-square w-full flex items-center justify-center bg-gray-50">
                  <img v-if="imagePreview" :src="imagePreview" alt="Product preview"
                    class="h-full w-full object-cover" />
                  <div v-else class="flex flex-col items-center gap-2 text-gray-400">
                    <i class="pi pi-image text-4xl"></i>
                    <span class="text-sm">No image selected</span>
                  </div>
                </div>
              </div>
              <div v-if="imageFile && imagePreview" class="mt-3 flex justify-end">
                <Button type="button" label="Remove Photo" icon="pi pi-trash" severity="danger" outlined size="small"
                  @click="openRemovePhotoPopover" />
              </div>
              <Popover ref="removePhotoPopover">
                <div class="max-w-xs space-y-3">
                  <p class="text-sm font-medium text-gray-800">Remove this selected photo?</p>
                  <p class="text-xs text-gray-500">The photo will not be uploaded when you save this item.</p>
                  <div class="flex justify-end gap-2">
                    <Button type="button" label="Keep Photo" text size="small" @click="removePhotoPopover?.hide()" />
                    <Button type="button" label="Remove" severity="danger" size="small" @click="removeSelectedPhoto" />
                  </div>
                </div>
              </Popover>
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <p class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-orange-700">Item details</p>
              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Item Name <span
                    class="text-red-500">*</span></label>
                <InputText v-model="form.product_name" class="w-full text-sm" size="small"
                  placeholder="e.g. Modern Chair" />
                <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
              </div>

              <div class="md:col-span-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Item Type <span
                    class="text-red-500">*</span></label>
                <Select v-model="form.product_type" :options="productTypeOptions" optionLabel="label"
                  optionValue="value" class="w-full text-sm" placeholder="Select product type" size="small" />
                <small v-if="errors.product_type" class="text-red-500">{{ errors.product_type }}</small>
              </div>

              <div v-if="form.product_type === 'others'" class="md:col-span-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Custom Item Type <span
                    class="text-red-500">*</span></label>
                <InputText v-model="form.custom_product_type" class="w-full text-sm" size="small"
                  placeholder="e.g. Packaging, Equipment" />
                <small v-if="errors.custom_product_type" class="text-red-500">{{ errors.custom_product_type }}</small>
              </div>

              <div v-if="showFinishedGoodFields && !isEditMode" class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <label class="mb-1 block text-sm font-medium text-gray-700">How is this product sold?</label>
                <Select v-model="form.has_variants" :options="variantModeOptions" optionLabel="label" optionValue="value"
                  class="w-full text-sm" size="small" />
                <small class="mt-1 block text-xs text-gray-500">Choose variants when options such as color or material have their own SKU, price, or stock. Variant products get their stock through each variant.</small>
              </div>


              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                <InputText v-model="form.brand" class="w-full text-sm" size="small"
                  placeholder="e.g. IKEA, Mandaue Foam" />
                <small v-if="errors.brand" class="text-red-500">{{ errors.brand }}</small>
              </div>
              <div v-if="form.product_type !== 'finished_good'" class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <Textarea v-model="form.description" class="w-full text-sm" rows="3" autoResize
                  placeholder="Describe this product, supply, or raw material" />
                <small v-if="errors.description" class="text-red-500">{{ errors.description }}</small>
              </div>

              <template v-if="showFinishedGoodFields">
                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700">Category <span
                      class="text-red-500">*</span></label>
                  <Select :modelValue="form.category_id" @update:modelValue="selectCategory" :options="parentCategories" optionLabel="category_name"
                    optionValue="id" class="w-full text-sm" placeholder="Select Main category" showClear filter
                    size="small">
                    <template #value="slotProps">
                      <span v-if="form.category_id">{{ parentCategoryName(form.category_id) }}</span>
                      <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                    </template>
                  </Select>
                  <small v-if="errors.category_id" class="text-red-500">{{ errors.category_id }}</small>
                  <small v-else-if="parentCategories.length === 0" class="text-amber-600">
                    No categories are available. Create a category before adding a finished good.
                  </small>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700">Subcategory</label>
                  <Select :modelValue="form.subcategory_id" @update:modelValue="selectSubcategory" :options="filteredSubcategories" optionLabel="category_name"
                    optionValue="id" class="w-full text-sm"
                    placeholder="Search subcategories" showClear filter size="small">
                    <template #option="slotProps">
                      <div class="flex flex-col">
                        <span>{{ slotProps.option.category_name }}</span>
                        <small class="text-gray-500">{{ parentCategoryName(slotProps.option.parent_category_id) }}</small>
                      </div>
                    </template>
                  </Select>
                  <small v-if="errors.subcategory_id" class="text-red-500">{{ errors.subcategory_id }}</small>
                </div>
              </template>

              <p v-if="showFinishedGoodFields" class="md:col-span-2 border-t border-gray-100 pt-4 text-xs font-semibold uppercase tracking-wide text-orange-700">Selling</p>
              <div v-if="showFinishedGoodFields">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ form.has_variants ? 'Default Variant Selling Price' : 'Selling Price' }}</label>
                <InputNumber v-model="form.base_price" mode="currency" currency="PHP" locale="en-PH" :min="0"
                  class="w-full text-sm" fluid size="small" placeholder="0.00" />
                <small v-if="errors.base_price" class="text-red-500">{{ errors.base_price }}</small>
                <small v-else-if="form.has_variants" class="mt-1 block text-xs text-gray-500">Used as the default for variants unless a variant has its own selling price.</small>
              </div>
              <div class="flex items-end gap-2 pb-2">
                <Checkbox v-model="form.is_active" :binary="true" inputId="product_active" />
                <label for="product_active" class="text-sm text-gray-700">Active</label>
              </div>

              <p class="md:col-span-2 border-t border-gray-100 pt-4 text-xs font-semibold uppercase tracking-wide text-orange-700">Stock and sourcing</p>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Unit Measure <span
                    class="text-red-500">*</span></label>
                <Select v-model="form.unit_of_measurement" :options="unitMeasureOptions" optionLabel="label"
                  optionValue="value" class="w-full text-sm" placeholder="Select unit" size="small" />
                <small v-if="errors.unit_of_measurement" class="text-red-500">{{ errors.unit_of_measurement }}</small>
              </div>

              <div v-if="showInitialStockToggle && !form.has_variants" class="md:col-span-2">
                <div class="flex items-center gap-2">
                  <Checkbox v-model="form.add_initial_stock" :binary="true" inputId="add_initial_stock" />
                  <label for="add_initial_stock" class="text-sm font-medium text-gray-700">Add initial stock</label>
                </div>
                <div v-if="form.add_initial_stock" class="mt-3 max-w-sm">
                  <label class="mb-1 block text-sm font-medium text-gray-700">Available Stock <span class="text-red-500">*</span></label>
                  <InputNumber v-model="form.initial_stock" :min="1" class="w-full text-sm" fluid size="small" placeholder="1" />
                  <small v-if="errors.initial_stock" class="text-red-500">{{ errors.initial_stock }}</small>
                </div>
              </div>
              <div v-else-if="!isEditMode && form.has_variants" class="md:col-span-2 rounded-lg bg-blue-50 p-3 text-xs text-blue-800">
                Opening stock is entered for each variant through Inventory after you add its options. This prevents parent stock from being mistaken for a sellable variant.
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Reorder Point</label>
                <InputNumber v-model="form.reorder_point" :min="0" class="w-full text-sm" fluid size="small"
                  placeholder="0" />
                <small class="mt-1 block text-xs text-gray-500">Used as this branch's default. Branch-specific forecast rules remain in effect.</small>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Current Inventory Cost / Unit</label>
                <small class="mb-1 block text-xs text-gray-500">Used for current stock valuation. Supplier prices are recorded separately below.</small>
                <InputNumber v-model="form.cost_price" mode="currency" currency="PHP" locale="en-PH" :min="0"
                  class="w-full text-sm" fluid size="small" placeholder="₱0.00" />
              </div>
              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Available Suppliers</label>
                <MultiSelect v-model="selectedSupplierIds" :options="suppliers" optionLabel="supplier_name"
                  optionValue="id" :filterFields="['supplier_name', 'company_name', 'supplier_code']"
                  class="w-full text-sm" placeholder="Select one or more suppliers" filter display="chip"
                  :loading="loadingSuppliers" size="small" />
                <p class="mt-1 text-xs text-gray-500">Set each supplier's price separately. One supplier can be marked as preferred.</p>
                <small v-if="errors.supplier_links" class="text-red-500">{{ errors.supplier_links }}</small>
                <small v-else-if="!loadingSuppliers && suppliers.length === 0" class="text-amber-600">
                  No active suppliers are available. Add a supplier from Inventory first.
                </small>

                <div v-if="form.supplier_links.length" class="mt-3 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200">
                  <div class="hidden grid-cols-[minmax(0,1.5fr)_minmax(130px,1fr)_100px_100px_100px] gap-3 bg-slate-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 md:grid">
                    <span>Supplier</span><span>Supplier Unit Price</span><span>Min. Qty</span><span>Lead Days</span><span>Preferred</span>
                  </div>
                  <div v-for="(link, index) in form.supplier_links" :key="link.supplier_id" class="grid gap-3 p-3 md:grid-cols-[minmax(0,1.5fr)_minmax(130px,1fr)_100px_100px_100px] md:items-center">
                    <div class="flex min-w-0 items-center gap-2">
                      <img v-if="supplierById(link.supplier_id)?.logo_url" :src="supplierById(link.supplier_id)?.logo_url"
                        :alt="`${supplierNameById(link.supplier_id)} logo`" class="h-8 w-8 shrink-0 rounded-lg border border-slate-200 object-cover" />
                      <span v-else class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-sm font-semibold text-orange-700">{{ supplierInitialById(link.supplier_id) }}</span>
                      <span class="truncate text-sm font-medium text-slate-800">{{ supplierNameById(link.supplier_id) }}</span>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs text-slate-500 md:hidden">Supplier unit price</label>
                      <InputNumber v-model="link.supplier_price" mode="currency" currency="PHP" locale="en-PH" :min="0" :maxFractionDigits="2" class="w-full" fluid size="small" />
                      <small v-if="errors[`supplier_links.${index}.supplier_price`]" class="mt-1 block text-xs text-red-600">{{ errors[`supplier_links.${index}.supplier_price`] }}</small>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs text-slate-500 md:hidden">Minimum quantity</label>
                      <InputNumber v-model="link.minimum_order_quantity" :min="1" :maxFractionDigits="0" class="w-full" fluid size="small" />
                    </div>
                    <div>
                      <label class="mb-1 block text-xs text-slate-500 md:hidden">Lead days</label>
                      <InputNumber v-model="link.lead_time_days" :min="0" :maxFractionDigits="0" class="w-full" fluid size="small" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                      <RadioButton v-model="preferredSupplierId" :value="link.supplier_id" :inputId="`preferred-supplier-${link.supplier_id}`" />
                      <span>Preferred</span>
                    </label>
                  </div>
                </div>
              </div>

              <p class="md:col-span-2 border-t border-gray-100 pt-4 text-xs font-semibold uppercase tracking-wide text-orange-700">Overall dimensions</p>
                <div class="md:col-span-2">
                  <p class="mb-2 text-xs text-gray-500">Enter the overall size in Width × Depth × Height order. All fields are optional.</p>
                  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <InputNumber v-model="form.width_cm" class="w-full text-sm" fluid size="small" :min="0" placeholder="Width" suffix=" cm" />
                    <InputNumber v-model="form.depth_cm" class="w-full text-sm" fluid size="small" :min="0" placeholder="Depth" suffix=" cm" />
                    <InputNumber v-model="form.height_cm" class="w-full text-sm" fluid size="small" :min="0" placeholder="Height" suffix=" cm" />
                    <InputNumber v-model="form.weight_kg" class="w-full text-sm" fluid size="small" :min="0" placeholder="Weight" suffix=" kg" />
                  </div>
                  <small v-if="errors.dimensions" class="text-red-500">{{ errors.dimensions }}</small>
                </div>

              <div class="md:col-span-2 rounded-xl border border-slate-200">
                <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                  :aria-expanded="additionalMeasurementsExpanded" @click="additionalMeasurementsExpanded = !additionalMeasurementsExpanded">
                  <span>
                    <span class="block text-sm font-semibold text-slate-800">Additional Measurements</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Optional furniture dimensions and custom measurements</span>
                  </span>
                  <i class="pi text-slate-500" :class="additionalMeasurementsExpanded ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                </button>
                <div v-if="additionalMeasurementsExpanded" class="space-y-4 border-t border-slate-200 p-4">
                  <div class="max-w-md">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Furniture template</label>
                    <Select v-model="selectedMeasurementTemplate" :options="measurementTemplateOptions" optionLabel="label" optionValue="value"
                      class="w-full text-sm" placeholder="Choose a furniture type" showClear size="small"
                      @change="addTemplateMeasurements" />
                    <small class="mt-1 block text-xs text-slate-500">Templates suggest relevant fields; fill in only the measurements you know.</small>
                  </div>

                  <div v-if="form.additional_measurements.length" class="space-y-3">
                    <div v-for="(measurement, index) in form.additional_measurements" :key="measurement.id"
                      class="grid grid-cols-1 gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(120px,0.7fr)_120px_auto] sm:items-end">
                      <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Measurement name</label>
                        <InputText v-model="measurement.label" class="w-full" size="small" placeholder="e.g. Seat Height" />
                      </div>
                      <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Value</label>
                        <InputNumber v-model="measurement.value" class="w-full" fluid size="small" :min="0.01" :maxFractionDigits="2" placeholder="Enter value" />
                      </div>
                      <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Unit</label>
                        <Select v-model="measurement.unit" :options="measurementUnitOptions" optionLabel="label" optionValue="value" class="w-full" size="small" />
                      </div>
                      <Button type="button" icon="pi pi-trash" severity="danger" text rounded aria-label="Remove measurement"
                        @click="removeAdditionalMeasurement(index)" />
                    </div>
                  </div>
                  <div class="flex justify-start">
                    <Button type="button" label="Add custom measurement" icon="pi pi-plus" severity="secondary" outlined size="small"
                      @click="addCustomMeasurement" />
                  </div>
                </div>
              </div>

              <!-- Temporarily hidden while bulky delivery handling is being revised.
              <div v-if="showFinishedGoodFields" class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Bulky delivery handling</label>
                <Select v-model="form.is_bulky" :options="bulkyOptions" optionLabel="label" optionValue="value" class="w-full text-sm" size="small" />
                <small class="text-gray-500">Auto uses the store's delivery size and weight thresholds. Override when special handling is needed.</small>
              </div>
              -->
            </div>

            <div v-if="submitError" role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {{ submitError }}
            </div>
            <p v-if="submitting" role="status" class="text-sm text-gray-600">Saving item, please wait...</p>
            <div class="flex justify-end gap-2 pt-2">
              <Button type="button" label="Cancel" severity="secondary" outlined size="small" class="text-sm" @click="goBack" />
              <Button type="submit" :label="isEditMode ? 'Update Item' : 'Create Item'" icon="pi pi-check"
                :loading="submitting" :disabled="submitting" severity="warn" size="small" class="text-sm" />
            </div>
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="successVisible" modal :closable="false" header="Item saved" :style="{ width: 'min(92vw, 26rem)' }">
      <p class="text-sm text-gray-600">{{ isEditMode ? 'Your changes were saved successfully.' : createdHasVariants ? 'Product created. Add its first sellable variant next.' : 'The item was created successfully.' }}</p>
      <template #footer>
        <Button v-if="!isEditMode && createdHasVariants && createdProductId" label="Add First Variant" severity="warn" size="small" @click="router.push({ name: 'inventory.product-variations.create', query: { product_id: createdProductId } })" />
        <Button label="View Products" severity="warn" size="small" @click="router.push({ name: productIndexRoute })" />
      </template>
    </Dialog>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'
import Chip from 'primevue/chip'
import Popover from 'primevue/popover'
import Textarea from 'primevue/textarea'
import MultiSelect from 'primevue/multiselect'
import RadioButton from 'primevue/radiobutton'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const submitting = ref(false)
const successVisible = ref(false)
const createdProductId = ref<number | null>(null)
const createdHasVariants = ref(false)
const errors = ref<Record<string, string>>({})
const branchCount = ref(0)
const submitError = ref('')
const isEditMode = computed(() => Boolean(route.params.id))
const showInitialStockToggle = computed(() => !isEditMode.value && branchCount.value === 1)
const productIndexRoute = computed(() => String(route.name || '').startsWith('merchandising.products') ? 'merchandising.products' : 'inventory.products.index')
const imageInput = ref<HTMLInputElement | null>(null)
const imagePreview = ref<string | null>(null)
const imageFile = ref<File | null>(null)
const removePhotoPopover = ref<any>(null)
const allProductTypeOptions = [
  { label: 'Product', value: 'finished_good' },
  { label: 'Supplies', value: 'supply' },
  { label: 'Raw Material', value: 'raw_material' },
  { label: 'Others', value: 'others' },
]
const productTypeOptions = computed(() => isEditMode.value
  ? allProductTypeOptions
  : allProductTypeOptions.filter(option => option.value !== 'finished_good'))
const variantModeOptions = [
  { label: 'One SKU (no options)', value: false },
  { label: 'Multiple variants (options have separate SKUs)', value: true },
]

const unitMeasureOptions = [
  { label: 'Pieces (pcs)', value: 'pcs' },
  { label: 'Set (set)', value: 'set' },
  { label: 'Pair (pair)', value: 'pair' },
  { label: 'Box (box)', value: 'box' },
  { label: 'Kilogram (kg)', value: 'kg' },
  { label: 'Gram (g)', value: 'g' },
  { label: 'Pound (lb)', value: 'lb' },
  { label: 'Meter (m)', value: 'm' },
  { label: 'Centimeter (cm)', value: 'cm' },
  { label: 'Millimeter (mm)', value: 'mm' },
  { label: 'Liter (L)', value: 'L' },
  { label: 'Milliliter (mL)', value: 'mL' },
  { label: 'Pack (pack)', value: 'pack' },
  { label: 'Roll (roll)', value: 'roll' },
  { label: 'Foot (ft)', value: 'ft' },
  { label: 'Inch (in)', value: 'in' }
]


const form = reactive({
  product_name: '',
  product_type: 'supply',
  has_variants: false,
  custom_product_type: '',
  brand: '',
  description: '',
  category_id: null as number | null,
  subcategory_id: null as number | null,
  supplier_links: [] as SupplierLink[],
  cost_price: null as number | null,
  base_price: null as number | null,
  length_cm: null as number | null,
  width_cm: null as number | null,
  height_cm: null as number | null,
  depth_cm: null as number | null,
  weight_kg: null as number | null,
  additional_measurements: [] as AdditionalMeasurement[],
  is_bulky: null as boolean | null,
  unit_of_measurement: '',
  initial_stock: null as number | null,
  add_initial_stock: false,
  reorder_point: 10 as number | null,
  is_active: true,
})
const bulkyOptions = [{ label: 'Auto-detect', value: null }, { label: 'Bulky — special handling', value: true }, { label: 'Not bulky', value: false }]

type AdditionalMeasurement = { id: string; label: string; value: number | null; unit: string }
const additionalMeasurementsExpanded = ref(false)
const selectedMeasurementTemplate = ref<string | null>(null)
const measurementUnitOptions = [
  { label: 'Centimeters (cm)', value: 'cm' }, { label: 'Millimeters (mm)', value: 'mm' },
  { label: 'Meters (m)', value: 'm' }, { label: 'Inches (in)', value: 'in' },
  { label: 'Feet (ft)', value: 'ft' }, { label: 'Kilograms (kg)', value: 'kg' },
  { label: 'Grams (g)', value: 'g' }, { label: 'Pounds (lb)', value: 'lb' },
]
const measurementTemplates: Record<string, string[]> = {
  'Sofa/Couch': ['Seat Height', 'Seat Depth', 'Seat Width', 'Arm Height', 'Back Height'],
  'Dining Chair': ['Seat Height', 'Seat Depth', 'Seat Width', 'Back Height'],
  'Bar Stool': ['Seat Height', 'Footrest Height', 'Seat Diameter'],
  'Dining Table': ['Tabletop Thickness', 'Apron Clearance'],
  'Coffee Table': ['Tabletop Thickness', 'Shelf Height'],
  'Bed Frame': ['Mattress Width', 'Mattress Length', 'Headboard Height', 'Footboard Height', 'Under-bed Clearance'],
  'Wardrobe': ['Hanging Height', 'Shelf Spacing', 'Drawer Height'],
  'Dresser': ['Drawer Interior Height', 'Drawer Interior Width', 'Drawer Depth'],
  'Cabinet': ['Shelf Spacing', 'Internal Width', 'Internal Depth'],
  'Bookshelf': ['Shelf Spacing', 'Shelf Depth'],
  'Office Desk': ['Work Surface Thickness', 'Knee Clearance'],
  'TV Stand': ['Shelf Spacing', 'Internal Shelf Height'],
  'Recliner': ['Seat Height', 'Seat Depth', 'Arm Height', 'Reclined Depth'],
  'Ottoman': ['Seat Height'],
  'Round Table': ['Diameter', 'Tabletop Thickness', 'Base Diameter'],
  'L-Shaped Sofa': ['Seat Height', 'Seat Depth', 'Left Section Width', 'Right Section Width', 'Arm Height'],
  'Bunk Bed': ['Mattress Width', 'Mattress Length', 'Clearance Between Bunks', 'Guardrail Height', 'Under-bed Clearance'],
}
const measurementTemplateOptions = Object.keys(measurementTemplates).map(value => ({ label: value, value }))
const newMeasurement = (label = ''): AdditionalMeasurement => ({
  id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
  label,
  value: null,
  unit: 'cm',
})
const addTemplateMeasurements = () => {
  const template = selectedMeasurementTemplate.value
  if (!template) return
  const existingLabels = new Set(form.additional_measurements.map(item => item.label.trim().toLowerCase()))
  const suggestions = measurementTemplates[template] || []
  form.additional_measurements.push(...suggestions
    .filter(label => !existingLabels.has(label.toLowerCase()))
    .map(newMeasurement))
}
const addCustomMeasurement = () => form.additional_measurements.push(newMeasurement())
const removeAdditionalMeasurement = (index: number) => form.additional_measurements.splice(index, 1)

type ProductCategory = {
  id: number
  category_name: string
  parent_category_id: number | null
  is_active?: boolean
}

type SupplierOption = {
  id: number
  supplier_code?: string | null
  supplier_name?: string | null
  company_name?: string | null
  logo_url?: string | null
  pivot?: {
    supplier_price?: string | number | null
    minimum_order_quantity?: number | null
    lead_time_days?: number | null
    is_preferred_supplier?: boolean | number
  }
}

type SupplierLink = {
  supplier_id: number
  supplier_price: number | null
  minimum_order_quantity: number
  lead_time_days: number
  is_preferred_supplier: boolean
}

const categories = ref<ProductCategory[]>([])
const canManageFinishedGoods = computed(() => authStore.hasPermission('merchandising.products.manage'))
const showFinishedGoodFields = computed(() => form.product_type === 'finished_good' && canManageFinishedGoods.value)
const suppliers = ref<SupplierOption[]>([])
const loadingSuppliers = ref(false)
const parentCategories = computed(() => {
  const parentIds = new Set(categories.value.filter(category => category.is_active !== false && category.parent_category_id != null)
    .map(category => Number(category.parent_category_id)))
  return categories.value.filter(category => (category.parent_category_id == null && category.is_active !== false)
    || parentIds.has(Number(category.id)))
})
const filteredSubcategories = computed(() =>
  categories.value.filter(category =>
    category.parent_category_id != null
    && category.is_active !== false
    && (form.category_id == null || Number(category.parent_category_id) === Number(form.category_id))
  )
)
const parentCategoryName = (parentId: number | null) =>
  categories.value.find(category => Number(category.id) === Number(parentId))?.category_name || 'Main category'
const selectedSupplierIds = computed<number[]>({
  get: () => form.supplier_links.map(link => Number(link.supplier_id)),
  set: (ids) => {
    const existing = new Map(form.supplier_links.map(link => [Number(link.supplier_id), link]))
    form.supplier_links = ids.map(id => existing.get(Number(id)) || ({
      supplier_id: Number(id),
      supplier_price: null,
      minimum_order_quantity: 1,
      lead_time_days: 7,
      is_preferred_supplier: false,
    }))
    if (!form.supplier_links.some(link => link.is_preferred_supplier) && form.supplier_links.length) {
      form.supplier_links[0].is_preferred_supplier = true
    }
  },
})

const preferredSupplierId = computed<number | null>({
  get: () => form.supplier_links.find(link => link.is_preferred_supplier)?.supplier_id ?? null,
  set: (id) => form.supplier_links.forEach(link => {
    link.is_preferred_supplier = Number(link.supplier_id) === Number(id)
  }),
})

const supplierById = (id: number) => suppliers.value.find(supplier => Number(supplier.id) === Number(id)) || null
const supplierNameById = (id: number) => {
  const supplier = supplierById(id)
  return supplier ? supplierDisplayName(supplier) : `Supplier #${id}`
}
const supplierInitialById = (id: number) => supplierNameById(id).charAt(0).toUpperCase()

const supplierDisplayName = (supplier: SupplierOption) =>
  supplier.supplier_name || supplier.company_name || `Supplier #${supplier.id}`

const supplierInitial = (supplier: SupplierOption) =>
  supplierDisplayName(supplier).charAt(0).toUpperCase()

const loadCategories = async () => {
  try {
    const response = await inventoryService.getCategories()
    categories.value = Array.isArray(response.data) ? response.data : []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load categories', life: 3000 })
  }
}

const loadSuppliers = async () => {
  loadingSuppliers.value = true
  try {
    const response = await inventoryService.getSuppliers({ status: 'active', per_page: 100 })
    const result = response.data
    suppliers.value = Array.isArray(result) ? result : (result?.data || [])
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load suppliers', life: 3000 })
  } finally {
    loadingSuppliers.value = false
  }
}

const loadProduct = async (id: number) => {
  try {
    const response = await inventoryService.getProduct(id)
    const product = response.data
    form.product_name = product.product_name || ''
    form.description = product.description || ''
    const knownTypes = ['finished_good', 'supply', 'raw_material']
    form.product_type = knownTypes.includes(product.product_type) ? product.product_type : 'others'
    form.custom_product_type = knownTypes.includes(product.product_type) ? '' : (product.product_type || '')
    form.brand = product.brand || ''
    form.category_id = product.category_id ? Number(product.category_id) : null
    form.subcategory_id = product.subcategory_id ? Number(product.subcategory_id) : null
    const linkedSuppliers: SupplierOption[] = Array.isArray(product.suppliers) ? product.suppliers : []
    form.supplier_links = linkedSuppliers.map(supplier => ({
      supplier_id: Number(supplier.id),
      supplier_price: supplier.pivot?.supplier_price == null ? null : Number(supplier.pivot.supplier_price),
      minimum_order_quantity: Number(supplier.pivot?.minimum_order_quantity ?? 1),
      lead_time_days: Number(supplier.pivot?.lead_time_days ?? 7),
      is_preferred_supplier: Boolean(supplier.pivot?.is_preferred_supplier),
    }))
    if (form.supplier_links.length && !form.supplier_links.some(link => link.is_preferred_supplier)) {
      form.supplier_links[0].is_preferred_supplier = true
    }
    form.cost_price = product.inventory_cost_price ?? product.cost_price ?? null
    form.base_price = product.base_price != null ? Number(product.base_price) : null
    form.length_cm = product.length_cm != null ? Number(product.length_cm) : null
    form.width_cm = product.width_cm != null ? Number(product.width_cm) : null
    form.height_cm = product.height_cm != null ? Number(product.height_cm) : null
    form.depth_cm = product.depth_cm != null ? Number(product.depth_cm) : null
    form.weight_kg = product.weight_kg != null ? Number(product.weight_kg) : null
    form.additional_measurements = Array.isArray(product.additional_measurements)
      ? product.additional_measurements.map((measurement: any, index: number) => ({
        id: `saved-${measurement.id ?? index}`,
        label: String(measurement.label || ''),
        value: measurement.value == null ? null : Number(measurement.value),
        unit: measurement.unit || 'cm',
      }))
      : []
    form.is_bulky = product.is_bulky == null ? null : Boolean(product.is_bulky)
    form.unit_of_measurement = product.unit_of_measurement || ''
    form.reorder_point = product.inventory?.[0]?.reorder_point ?? 10
    form.is_active = product.is_active !== false
    const imageAssets = (Array.isArray(product.assets) ? product.assets : [])
      .filter((asset: any) => ['Image_Main', 'Image_Gallery', 'Image_360'].includes(asset.asset_type))
      .sort((a: any, b: any) => Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary))
        || Number(a.display_order || 0) - Number(b.display_order || 0))
    imagePreview.value = imageAssets[0]?.thumbnail_url || imageAssets[0]?.url || null
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load Item', life: 3000 })
  }
}

const selectCategory = (categoryId: number | null) => {
  form.category_id = categoryId == null ? null : Number(categoryId)
  const selectedSubcategory = categories.value.find(category => Number(category.id) === Number(form.subcategory_id))
  if (selectedSubcategory && Number(selectedSubcategory.parent_category_id) !== Number(form.category_id)) {
    form.subcategory_id = null
  }
}

const selectSubcategory = (subcategoryId: number | null) => {
  form.subcategory_id = subcategoryId == null ? null : Number(subcategoryId)
  if (!form.subcategory_id) return
  const selectedSubcategory = categories.value.find(category => Number(category.id) === form.subcategory_id)
  if (selectedSubcategory?.parent_category_id != null) {
    form.category_id = Number(selectedSubcategory.parent_category_id)
  }
}

watch(() => form.product_type, productType => {
  if (productType !== 'finished_good' && canManageFinishedGoods.value) {
    form.category_id = null
    form.subcategory_id = null
  }
})

const triggerImagePicker = () => {
  imageInput.value?.click()
}

const onImageSelected = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
}

const openRemovePhotoPopover = (event: MouseEvent) => {
  removePhotoPopover.value?.toggle(event)
}

const removeSelectedPhoto = () => {
  imageFile.value = null
  imagePreview.value = null
  if (imageInput.value) imageInput.value.value = ''
  removePhotoPopover.value?.hide()
}

const validate = () => {
  errors.value = {}
  submitError.value = ''
  if (!form.product_name) errors.value.product_name = 'Item name is required'
  if (!form.product_type) errors.value.product_type = 'Item type is required'
  if (form.product_type === 'others' && !form.custom_product_type.trim()) {
    errors.value.custom_product_type = 'Custom item type is required'
  }
  if (showFinishedGoodFields.value && !form.category_id) {
    errors.value.category_id = parentCategories.value.length === 0
      ? 'Create a category before adding a finished good'
      : 'Category is required for a finished good'
  }
  if (!form.unit_of_measurement) errors.value.unit_of_measurement = 'Unit measure is required'
  if (showInitialStockToggle.value && form.add_initial_stock && !form.has_variants
    && (form.initial_stock == null || !Number.isFinite(Number(form.initial_stock)) || Number(form.initial_stock) < 1)) {
    errors.value.initial_stock = 'Available stock must be at least 1.'
  }
  form.supplier_links.forEach((link, index) => {
    if (link.supplier_price == null || !Number.isFinite(Number(link.supplier_price)) || Number(link.supplier_price) < 0) {
      errors.value[`supplier_links.${index}.supplier_price`] = "Enter this supplier's unit price."
    }
  })
  if (Object.keys(errors.value).length > 0) {
    submitError.value = 'Please correct the highlighted fields before saving.'
    return false
  }
  return true
}

const handleSubmit = async () => {
  if (!validate()) return

  submitting.value = true
  try {
    const payload = new FormData()
    payload.append('product_name', form.product_name)
    payload.append('product_type', form.product_type === 'others' ? form.custom_product_type.trim() : form.product_type)
    payload.append('brand', form.brand.trim())
    if (form.product_type !== 'finished_good') payload.append('description', form.description.trim())
    if (showFinishedGoodFields.value) {
      if (form.category_id != null) payload.append('category_id', String(form.category_id))
      payload.append('subcategory_id', form.subcategory_id != null ? String(form.subcategory_id) : '')
    }
    if (form.cost_price != null) payload.append('cost_price', String(form.cost_price))
    if (showFinishedGoodFields.value) {
      if (form.base_price != null) payload.append('base_price', String(form.base_price))
      payload.append('is_bulky', form.is_bulky === null ? '' : form.is_bulky ? '1' : '0')
      if (!isEditMode.value) payload.append('has_variants', form.has_variants ? '1' : '0')
    }
    payload.append('length_cm', form.length_cm == null ? '' : String(form.length_cm))
    payload.append('width_cm', form.width_cm == null ? '' : String(form.width_cm))
    payload.append('height_cm', form.height_cm == null ? '' : String(form.height_cm))
    payload.append('depth_cm', form.depth_cm == null ? '' : String(form.depth_cm))
    payload.append('weight_kg', form.weight_kg == null ? '' : String(form.weight_kg))
    const additionalMeasurements = form.additional_measurements
      .filter(measurement => measurement.label.trim() && measurement.value != null && Number(measurement.value) > 0)
      .map(({ label, value, unit }) => ({ label: label.trim(), value, unit }))
    payload.append('additional_measurements', JSON.stringify(additionalMeasurements))
    payload.append('supplier_links', JSON.stringify(form.supplier_links))
    if (form.unit_of_measurement) payload.append('unit_of_measurement', form.unit_of_measurement)
    if (showInitialStockToggle.value && form.add_initial_stock && !form.has_variants && form.initial_stock != null) {
      payload.append('add_initial_stock', '1')
      payload.append('initial_stock', String(form.initial_stock))
    } else if (!isEditMode.value) {
      payload.append('add_initial_stock', '0')
    }
    if (form.reorder_point != null) payload.append('reorder_point', String(form.reorder_point))
    payload.append('is_active', form.is_active ? '1' : '0')
    if (imageFile.value) {
      payload.append('product_image', imageFile.value)
    }

    if (isEditMode.value) {
      const response = await inventoryService.updateProduct(Number(route.params.id), payload, true)
      if (response?.success === false) throw new Error(response.message || 'Unable to update item.')
      successVisible.value = true
    } else {
      const response = await inventoryService.createProduct(payload, true)
      if (response?.success === false) throw new Error(response.message || 'Unable to create item.')
      createdProductId.value = Number(response?.data?.id || response?.data?.data?.id) || null
      createdHasVariants.value = form.has_variants
      successVisible.value = true
      window.dispatchEvent(new Event('setup-attention-refresh'))
    }
  } catch (error: any) {
    const apiErrors = error.response?.data?.errors || {}
    if (error.response?.status === 422) {
      errors.value = {
        ...errors.value,
        ...Object.fromEntries(
          Object.entries(apiErrors).map(([key, value]) => [
            key,
            Array.isArray(value) ? value.join(', ') : String(value)
          ])
        )
      }
    }
    submitError.value =
      (error.response?.status === 422 && Object.keys(apiErrors).length
        ? 'Please correct the highlighted fields before saving.'
        : null) ||
      error.response?.data?.message ||
      error.response?.data?.error ||
      error.message ||
      (Object.keys(apiErrors).length
        ? Object.entries(apiErrors)
          .map(([key, value]) => `${key}: ${Array.isArray(value) ? value.join(', ') : String(value)}`)
          .join('\n')
        : 'Failed to save Item')

  } finally {
    submitting.value = false
  }
}

const goBack = () => router.push({ name: isEditMode.value ? 'inventory.products.index' : productIndexRoute.value })

onMounted(async () => {
  const requestedType = String(route.query.type || '')
  if (['supply', 'raw_material', 'others'].includes(requestedType)) {
    form.product_type = requestedType
  } else if (route.name === 'merchandising.products.supplies.create') {
    form.product_type = 'supply'
  } else if (route.name === 'merchandising.products.materials.create' || route.name === 'merchandising.products.raw.create') {
    form.product_type = 'raw_material'
  } else if (!isEditMode.value) {
    form.product_type = 'supply'
  }
  await Promise.all([loadCategories(), loadSuppliers(), loadBranchCount()])
  if (isEditMode.value) {
    await loadProduct(Number(route.params.id))
  }
})

const loadBranchCount = async () => {
  if (isEditMode.value) return
  try {
    const response = await inventoryService.getBranches()
    const branches = response?.data?.data ?? response?.data ?? []
    branchCount.value = Array.isArray(branches) ? branches.length : 0
  } catch {
    branchCount.value = 0
  }
}
</script>
