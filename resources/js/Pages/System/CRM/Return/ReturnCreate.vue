<template>
  <div class="mx-auto max-w-4xl space-y-5 px-4 py-5 sm:px-6">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded aria-label="Go back" @click="router.back()" />
      <div>
        <h1 class="text-xl font-bold text-slate-900">Create In Store Return</h1>
        <p class="text-sm text-slate-500">Record a customer return for an eligible in-store sale.</p>
      </div>
    </header>

    <Message severity="info" :closable="false">Select a paid, completed or delivered in-store sale from the last 7 days. Returned items stay linked to their original receipt, branch, and price.</Message>

    <Skeleton v-if="loading" height="24rem" borderRadius="1rem" />
    <Card v-else class="border border-slate-200 shadow-sm">
      <template #content>
        <form class="space-y-5" @submit.prevent="confirmVisible = true">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5 sm:col-span-2">
              <label class="text-sm font-medium text-slate-700">In-store receipt <span class="text-red-600">*</span></label>
              <Select v-model="form.sales_order_id" :options="orders" optionValue="id" filter showClear fluid
                :loading="ordersLoading" placeholder="Search and select an in-store receipt" @change="form.sales_order_item_id = null">
                <template #value="{ value, placeholder }">
                  <span v-if="selectedOrder" class="text-sm">{{ selectedOrder.order_number }} | {{ selectedOrder.customer_name || 'Walk-in customer' }}</span>
                  <span v-else class="text-slate-400">{{ placeholder }}</span>
                </template>
                <template #option="{ option }">
                  <div class="min-w-0">
                    <p class="font-medium text-slate-900">{{ option.order_number }} · {{ option.customer_name || 'Walk-in customer' }}</p>
                    <p class="text-xs text-slate-500">{{ option.branch_name || 'Branch unavailable' }} | {{ formatDate(option.purchased_at) }} | {{ option.items.length }} eligible item(s)</p>
                  </div>
                </template>
                <template #empty><p class="p-3 text-sm text-slate-500">No paid, completed or delivered in-store receipts with unreturned items were found in the last 7 days.</p></template>
              </Select>
            </div>

            <div v-if="selectedOrder" class="sm:col-span-2 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-3">
              <div><p class="text-xs text-slate-500">Customer</p><p class="font-medium text-slate-900">{{ selectedOrder.customer_name || 'Walk-in customer' }}</p></div>
              <div><p class="text-xs text-slate-500">Contact</p><p class="font-medium text-slate-900">{{ selectedOrder.customer_phone || 'Not recorded' }}</p></div>
              <div><p class="text-xs text-slate-500">Original branch</p><p class="font-medium text-slate-900">{{ selectedOrder.branch_name || 'Not recorded' }}</p></div>
            </div>

            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Pickup contact <span class="text-red-600">*</span></label>
              <InputText v-model="form.return_contact_name" fluid maxlength="255" placeholder="Customer or authorized recipient" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Contact number <span class="text-red-600">*</span></label>
              <InputText v-model="form.return_contact_phone" fluid maxlength="50" placeholder="09xx xxx xxxx" />
            </div>
            <div class="space-y-1.5 sm:col-span-2">
              <label class="text-sm font-medium text-slate-700">Return pickup address <span class="text-red-600">*</span></label>
              <Textarea v-model="form.return_pickup_address" rows="2" autoResize fluid maxlength="2000" placeholder="Where Logistics should collect the returned item" />
              <small class="text-xs text-slate-500">Prefilled from the in-store delivery address when available. Confirm or update it for this return.</small>
            </div>

            <div class="space-y-1.5 sm:col-span-2">
              <label class="text-sm font-medium text-slate-700">Purchased item <span class="text-red-600">*</span></label>
              <Select v-model="form.sales_order_item_id" :options="selectedOrder?.items || []" optionValue="id" filter fluid
                :disabled="!selectedOrder" placeholder="Select an item from the receipt" @change="form.requested_quantity = 1">
                <template #value="{ value, placeholder }">
                  <span v-if="selectedItem" class="text-sm">{{ selectedItem.product_name }} | Qty {{ selectedItem.quantity }} | {{ money(selectedItem.unit_price) }}</span>
                  <span v-else class="text-slate-400">{{ placeholder }}</span>
                </template>
                <template #option="{ option }">
                  <div><p class="font-medium text-slate-900">{{ option.product_name }}<span v-if="option.variant_name"> | {{ option.variant_name }}</span></p>
                    <p class="text-xs text-slate-500">{{ option.sku || 'No SKU' }} | Sold {{ option.quantity }} | {{ money(option.unit_price) }} per unit</p></div>
                </template>
              </Select>
            </div>

            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Return quantity <span class="text-red-600">*</span></label>
              <InputNumber v-model="form.requested_quantity" :min="1" :max="selectedItem?.quantity || 1" :disabled="!selectedItem" :useGrouping="false" showButtons fluid />
              <small v-if="selectedItem" class="text-xs text-slate-500">Maximum {{ selectedItem.quantity }} sold unit(s).</small>
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Reason <span class="text-red-600">*</span></label>
              <Select v-model="form.reason" :options="reasonOptions" optionLabel="label" optionValue="value" placeholder="Select a reason" fluid />
            </div>

            <div class="space-y-1.5 sm:col-span-2">
              <label class="text-sm font-medium text-slate-700">Additional details</label>
              <Textarea v-model="form.details" rows="3" autoResize fluid maxlength="2000" placeholder="Describe the customer's concern or condition of the item." />
            </div>

            <div class="space-y-2 sm:col-span-2">
              <div>
                <label class="text-sm font-medium text-slate-700">Evidence photos <span class="text-red-600">*</span></label>
                <p class="text-xs text-slate-500">At least 1, up to 5 JPG, PNG, or WebP images; 4 MB each.</p>
              </div>
              <div class="flex items-center gap-2">
                <Button type="button" label="Choose photos" icon="pi pi-upload" severity="secondary" outlined size="small" :disabled="photos.length >= 5" @click="fileInput?.click()" />
                <span class="text-xs text-slate-500">{{ photos.length }}/5 selected</span>
                <input ref="fileInput" class="hidden" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="onFilesSelected" />
              </div>
              <p v-if="photoError" class="text-sm text-red-600">{{ photoError }}</p>
              <div v-if="photos.length" class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div v-for="photo in photos" :key="photo.id" class="relative overflow-hidden rounded-lg border border-slate-200">
                  <img :src="photo.url" :alt="photo.file.name" class="h-28 w-full object-cover" />
                  <Button type="button" icon="pi pi-times" rounded severity="danger" size="small" aria-label="Remove photo" class="!absolute right-1 top-1 !h-7 !w-7" @click="removePhoto(photo.id)" />
                  <p class="truncate px-2 py-1 text-xs text-slate-600">{{ photo.file.name }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
            <Button label="Cancel" severity="secondary" outlined type="button" @click="router.back()" />
            <Button label="Submit Return" icon="pi pi-send" severity="warn" type="submit" :disabled="!validForm" />
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="confirmVisible" modal header="Submit in-store return?" :style="{ width: 'min(30rem, calc(100vw - 2rem))' }">
      <p class="text-sm leading-6 text-slate-600">Create a return request for {{ selectedItem?.product_name || 'the selected item' }} from receipt {{ selectedOrder?.order_number }}? It will enter the same CRM investigation and approval workflow as ecommerce returns.</p>
      <template #footer>
        <Button label="Keep Editing" severity="secondary" outlined @click="confirmVisible = false" />
        <Button label="Submit Request" severity="warn" :loading="submitting" @click="submit" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import crmService from '@/services/crm.service'

const router = useRouter()
const toast = useToast()
const loading = ref(true)
const ordersLoading = ref(false)
const submitting = ref(false)
const confirmVisible = ref(false)
const orders = ref<any[]>([])
const fileInput = ref<HTMLInputElement | null>(null)
const photoError = ref('')
const photos = ref<Array<{ id: string; file: File; url: string }>>([])
const form = reactive({ sales_order_id: null as number | null, sales_order_item_id: null as number | null, return_contact_name: '', return_contact_phone: '', return_pickup_address: '', requested_quantity: 1, reason: '', details: '' })
const reasonOptions = [
  { label: 'Damaged item', value: 'Damaged item' },
  { label: 'Wrong item received', value: 'Wrong item received' },
  { label: 'Missing parts', value: 'Missing parts' },
  { label: 'Quality issue', value: 'Quality issue' },
  { label: 'Other', value: 'Other' },
]
const selectedOrder = computed(() => orders.value.find(order => Number(order.id) === Number(form.sales_order_id)) || null)
const selectedItem = computed(() => selectedOrder.value?.items?.find((item: any) => Number(item.id) === Number(form.sales_order_item_id)) || null)
const validForm = computed(() => Boolean(selectedOrder.value && selectedItem.value && form.return_contact_name.trim() && form.return_contact_phone.trim() && form.return_pickup_address.trim() && form.reason && form.requested_quantity >= 1 && form.requested_quantity <= selectedItem.value.quantity && photos.value.length > 0))
const money = (value: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('en-PH', { dateStyle: 'medium' }) : 'Date unavailable'

watch(selectedOrder, order => {
  form.sales_order_item_id = null
  form.requested_quantity = 1
  form.return_contact_name = order?.customer_name || ''
  form.return_contact_phone = order?.customer_phone || ''
  form.return_pickup_address = order?.delivery_address || ''
})

function onFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const selected = Array.from(input.files || [])
  input.value = ''
  photoError.value = ''
  if (photos.value.length + selected.length > 5) {
    photoError.value = 'You can attach up to 5 photos.'
    return
  }
  const invalid = selected.find(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 4 * 1024 * 1024)
  if (invalid) {
    photoError.value = `${invalid.name} must be a JPG, PNG, or WebP image no larger than 4 MB.`
    return
  }
  selected.forEach(file => photos.value.push({ id: `${Date.now()}-${Math.random()}`, file, url: URL.createObjectURL(file) }))
}

function removePhoto(id: string) {
  const index = photos.value.findIndex(photo => photo.id === id)
  if (index < 0) return
  URL.revokeObjectURL(photos.value[index].url)
  photos.value.splice(index, 1)
}

async function submit() {
  if (!validForm.value || submitting.value) return
  submitting.value = true
  try {
    const payload = new FormData()
    payload.append('sales_order_item_id', String(form.sales_order_item_id))
    payload.append('return_contact_name', form.return_contact_name.trim())
    payload.append('return_contact_phone', form.return_contact_phone.trim())
    payload.append('return_pickup_address', form.return_pickup_address.trim())
    payload.append('requested_quantity', String(form.requested_quantity))
    payload.append('reason', form.reason)
    if (form.details.trim()) payload.append('details', form.details.trim())
    photos.value.forEach(photo => payload.append('evidence_images[]', photo.file))
    const response = await crmService.createPosReturn(payload)
    const returnId = response?.data?.id
    confirmVisible.value = false
    toast.add({ severity: 'success', summary: 'Return submitted', detail: 'The in-store return is now in CRM investigation.', life: 3500 })
    if (returnId) router.push(`/crm/returns/${returnId}`)
    else router.push('/crm/returns')
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to submit', detail: error?.response?.data?.message || 'The return request could not be created.', life: 5000 })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    ordersLoading.value = true
    const response = await crmService.getPosReturnOrders()
    orders.value = response?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load receipts', detail: error?.response?.data?.message || 'Eligible in-store receipts could not be loaded.', life: 5000 })
  } finally {
    ordersLoading.value = false
    loading.value = false
  }
})

onBeforeUnmount(() => photos.value.forEach(photo => URL.revokeObjectURL(photo.url)))
</script>
