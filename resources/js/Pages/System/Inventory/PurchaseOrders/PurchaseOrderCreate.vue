<template>
  <div class="mx-auto max-w-6xl space-y-5 p-4 md:p-6">
    <ConfirmDialog />
    <div class="flex items-center justify-between gap-3">
      <div><h1 class="text-xl font-bold text-slate-800">New Purchase Order</h1><p class="text-sm text-slate-500">Create a direct supplier order from inventory.</p></div>
      <Button label="Back to Purchase Orders" icon="pi pi-arrow-left" text severity="secondary" @click="router.visit('/inventory/purchase-orders')" />
    </div>
    <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>
    <div v-if="loading" class="space-y-3"><Skeleton height="9rem" /><Skeleton height="12rem" /></div>
    <template v-else>
      <Card><template #title>Order details</template><template #content>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div><label class="mb-1 block text-sm font-medium">Supplier *</label><Select v-model="form.supplier_id" :options="suppliers" optionLabel="supplier_name" optionValue="id" filter placeholder="Select supplier" class="w-full" @change="onSupplierChange" /><small v-if="fieldError('supplier_id')" class="text-red-600">{{ fieldError('supplier_id') }}</small></div>
          <div><label class="mb-1 block text-sm font-medium">Receiving branch *</label><Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" filter placeholder="Select branch" class="w-full" @change="onBranchChange" /><small v-if="fieldError('branch_id')" class="text-red-600">{{ fieldError('branch_id') }}</small></div>
          <div><label class="mb-1 block text-sm font-medium">Expected delivery</label><DatePicker v-model="form.expected_delivery_date" dateFormat="MM d, yy" showIcon :manualInput="false" :minDate="new Date()" class="w-full" inputClass="w-full" placeholder="Month Day, Year" /><small v-if="fieldError('expected_delivery_date')" class="text-red-600">{{ fieldError('expected_delivery_date') }}</small></div>
          <div><label class="mb-1 block text-sm font-medium">Payment terms *</label><Select v-model="form.payment_terms" :options="paymentTerms" optionLabel="label" optionValue="value" class="w-full" /></div>
          <div><label class="mb-1 block text-sm font-medium">Fulfillment *</label><Select v-model="form.fulfillment_method" :options="fulfillmentMethods" optionLabel="label" optionValue="value" class="w-full" /></div>
        </div>
      </template></Card>
      <Card><template #title><div class="flex items-center justify-between"><span>Products</span><Button label="Add item" icon="pi pi-plus" size="small" outlined @click="addItem" /></div></template><template #content>
        <div v-if="!form.supplier_id" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Select a supplier to see its products.</div>
        <div v-else-if="!filteredProducts.length" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">No active products are linked to this supplier. <a href="/inventory/products/create" class="underline">Add or link a product first.</a></div>
        <div v-for="(item, index) in form.items" :key="item.key" class="mb-3 grid items-end gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-12">
          <div class="md:col-span-4"><label class="mb-1 block text-sm font-medium">Product *</label><Select v-model="item.product_id" :options="filteredProducts" optionLabel="name" optionValue="id" filter placeholder="Choose product" class="w-full" :disabled="!form.supplier_id" @change="onProductChange(item, index)" /><small v-if="fieldError(`items.${index}.product_id`)" class="text-red-600">{{ fieldError(`items.${index}.product_id`) }}</small></div>
          <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Variation</label><Select v-model="item.variation_id" :options="productFor(item)?.variations || []" optionLabel="name" optionValue="id" showClear placeholder="No variation" class="w-full" :disabled="!productFor(item)?.variations?.length" @change="onVariationChange(item, index)" /></div>
          <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Quantity *</label><InputNumber v-model="item.quantity_ordered" :min="0" :maxFractionDigits="0" class="w-full" inputClass="w-full" @blur="validateQuantity(index)" /><small v-if="fieldError(`items.${index}.quantity_ordered`)" class="text-red-600">{{ fieldError(`items.${index}.quantity_ordered`) }}</small></div>
          <div class="md:col-span-1"><label class="mb-1 block text-sm font-medium">Unit</label><p class="min-h-10 py-2 text-sm text-slate-700">{{ unitFor(item) }}</p></div>
          <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Unit cost</label><p class="min-h-10 py-2 text-sm font-medium text-slate-800">{{ money(item.unit_cost) }}</p></div>
          <Button icon="pi pi-trash" severity="danger" text aria-label="Remove item" class="md:col-span-1" :disabled="form.items.length === 1" @click="confirmRemoveItem(index)" />
          <div class="text-right text-sm font-medium text-slate-600 md:col-span-12">Line total: {{ money(Number(item.quantity_ordered || 0) * Number(item.unit_cost || 0)) }}</div>
        </div>
        <small v-if="fieldError('items')" class="text-red-600">{{ fieldError('items') }}</small>
      </template></Card>
      <Card><template #title>Notes and total</template><template #content>
        <div class="grid gap-5 lg:grid-cols-2">
          <div><label class="mb-1 block text-sm font-medium">Notes</label><Textarea v-model="form.notes" rows="4" class="w-full" placeholder="Optional instructions for this order" /></div>
          <div class="rounded-lg bg-slate-50 p-4 text-sm"><div class="flex justify-between py-1"><span>Subtotal</span><span>{{ money(subtotal) }}</span></div><div class="flex justify-between py-1"><span>Tax ({{ taxRate }}%)</span><span>{{ money(tax) }}</span></div><div class="mt-2 flex justify-between border-t pt-3 text-base font-bold"><span>Estimated total</span><span>{{ money(total) }}</span></div><p class="mt-2 text-xs text-slate-500">The final total is calculated when the order is saved.</p></div>
        </div>
      </template></Card>
      <div class="flex justify-end gap-2"><Button label="Cancel" outlined severity="secondary" :disabled="!!saving" @click="router.visit('/inventory/purchase-orders')" /><Button label="Save Draft" icon="pi pi-save" outlined :loading="saving === 'draft'" :disabled="!!saving && saving !== 'draft'" @click="save('draft')" /><Button label="Submit" icon="pi pi-send" :loading="saving === 'submit'" :disabled="!!saving && saving !== 'submit'" @click="save('submit')" /></div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'

type OrderItem = { key: number; product_id: number | null; variation_id: number | null; quantity_ordered: number; unit_cost: number }
const loading = ref(true)
const saving = ref<'draft' | 'submit' | null>(null)
const confirm = useConfirm()
const error = ref('')
const errors = ref<Record<string, string[]>>({})
const quantityErrors = ref<Record<number, string>>({})
const suppliers = ref<any[]>([])
const branches = ref<any[]>([])
const products = ref<any[]>([])
let nextKey = 1
const form = reactive({ supplier_id: null as number | null, branch_id: null as number | null, expected_delivery_date: null as Date | null, payment_terms: 'cash_on_delivery', fulfillment_method: 'supplier_delivery', notes: '', items: [] as OrderItem[] })
const paymentTerms = [{ label: 'Cash on delivery', value: 'cash_on_delivery' }, { label: 'Net 7', value: 'net_7' }, { label: 'Net 15', value: 'net_15' }, { label: 'Net 30', value: 'net_30' }, { label: 'Net 60', value: 'net_60' }, { label: 'Advance payment', value: 'advance_payment' }]
const fulfillmentMethods = [{ label: 'Supplier delivery', value: 'supplier_delivery' }, { label: 'Store pickup', value: 'store_pickup' }]
const selectedSupplier = computed(() => suppliers.value.find(s => s.id === form.supplier_id))
const filteredProducts = computed(() => products.value.filter(product => product.suppliers?.some((supplier: any) => supplier.id === form.supplier_id)))
const taxRate = computed(() => selectedSupplier.value?.is_tax_exempt ? 0 : Number(selectedSupplier.value?.default_tax_rate ?? 12))
const subtotal = computed(() => form.items.reduce((sum, item) => sum + Number(item.quantity_ordered || 0) * Number(item.unit_cost || 0), 0))
const tax = computed(() => subtotal.value * taxRate.value / 100)
const total = computed(() => subtotal.value + tax.value)
function money(value: any) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0)) }
function fieldError(field: string) {
  const index = /^items\.(\d+)\.quantity_ordered$/.exec(field)
  return (index ? quantityErrors.value[Number(index[1])] : '') || errors.value[field]?.[0] || ''
}
function addItem() { form.items.push({ key: nextKey++, product_id: null, variation_id: null, quantity_ordered: 10, unit_cost: 0 }) }
function confirmRemoveItem(index: number) {
  if (form.items.length <= 1) return
  const item = form.items[index]
  confirm.require({
    header: 'Remove product?',
    message: `Remove ${productFor(item)?.name || 'this item'} from the purchase order?`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Remove',
    acceptProps: { severity: 'danger' },
    rejectLabel: 'Keep item',
    rejectProps: { severity: 'secondary', outlined: true },
    accept: () => {
      const currentIndex = form.items.findIndex(row => row.key === item.key)
      if (currentIndex < 0 || form.items.length <= 1) return
      form.items.splice(currentIndex, 1)
      quantityErrors.value = {}
      errors.value = {}
    },
  })
}
function productFor(item: OrderItem) { return products.value.find(product => product.id === item.product_id) }
function unitFor(item: OrderItem) { return productFor(item)?.unit || '—' }
function orderPointFor(item: OrderItem) {
  const product = productFor(item)
  const branchId = Number(form.branch_id)
  const inventoryPoint = Number(product?.inventory_points?.find((point: any) =>
    Number(point.branch_id) === branchId && Number(point.variation_id || 0) === Number(item.variation_id || 0)
  )?.reorder_point || 0)
  const rulePoint = Number(product?.reorder_rules?.find((rule: any) => Number(rule.branch_id) === branchId)?.reorder_point || 0)
  const basePoint = Number(product?.inventory_points?.find((point: any) =>
    Number(point.branch_id) === branchId && !point.variation_id
  )?.reorder_point || 0)
  return Math.max(10, inventoryPoint || rulePoint || basePoint)
}
function validateQuantity(index: number) {
  const quantity = form.items[index]?.quantity_ordered
  if (quantity == null || !Number.isInteger(quantity) || quantity < 10) quantityErrors.value[index] = 'Quantity must be at least 10.'
  else delete quantityErrors.value[index]
  delete errors.value[`items.${index}.quantity_ordered`]
}
function onSupplierChange() {
  quantityErrors.value = {}
  errors.value = {}
  form.items.forEach(item => { item.product_id = null; item.variation_id = null; item.quantity_ordered = 10; item.unit_cost = 0 })
}
function onBranchChange() {
  form.items.forEach((item, index) => {
    if (!item.product_id) return
    item.quantity_ordered = orderPointFor(item)
    validateQuantity(index)
  })
}
function onProductChange(item: OrderItem, index: number) {
  item.variation_id = null
  item.unit_cost = Number(productFor(item)?.suppliers?.find((supplier: any) => supplier.id === form.supplier_id)?.unit_cost || 0)
  item.quantity_ordered = orderPointFor(item)
  validateQuantity(index)
}
function onVariationChange(item: OrderItem, index: number) { item.quantity_ordered = orderPointFor(item); validateQuantity(index) }
function formatDate(value: Date | null) {
  if (!value) return null
  return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
}
async function save(mode: 'draft' | 'submit') {
  error.value = ''; errors.value = {}
  form.items.forEach((_, index) => validateQuantity(index))
  if (!form.supplier_id || !form.branch_id || form.items.some(item => !item.product_id)) { error.value = 'Choose a supplier, branch, and product for each item.'; return }
  if (Object.keys(quantityErrors.value).length) { error.value = 'Correct the quantity for each item before saving.'; return }
  saving.value = mode
  try {
    const payload = { ...form, submit: mode === 'submit', expected_delivery_date: formatDate(form.expected_delivery_date), items: form.items.map(({ key, unit_cost, ...item }) => item) }
    const response = await axiosClient.post('/api/inventory/purchase-orders', payload)
    router.visit(`/inventory/purchase-orders/${response.data.data.id}`)
  } catch (cause: any) { errors.value = cause?.response?.data?.errors || {}; error.value = cause?.response?.data?.message || (mode === 'submit' ? 'Unable to submit the purchase order.' : 'Unable to save the purchase order.') }
  finally { saving.value = null }
}
onMounted(async () => {
  addItem()
  try {
    const response = await axiosClient.get('/api/inventory/purchase-orders/options')
    suppliers.value = response.data.data.suppliers || []
    branches.value = response.data.data.branches || []
    products.value = response.data.data.products || []
    if (branches.value.length === 1) form.branch_id = branches.value[0].id
  } catch (cause: any) { error.value = cause?.response?.data?.message || 'Unable to load order options.' }
  finally { loading.value = false }
})
</script>
