<template>
  <div class="mx-auto max-w-5xl space-y-4 px-4 py-6 text-sm sm:px-6">
    <header class="flex items-start justify-between gap-3">
      <div>
        <Button label="Back" icon="pi pi-arrow-left" text size="small" severity="secondary" @click="goBack" />
        <h1 class="mt-2 text-xl font-semibold text-slate-900">Replacement {{ item?.return_number || '' }}</h1>
        <p class="mt-1 text-xs text-slate-500">Return receipt, stock reservation, and customer delivery</p>
      </div>
      <Tag v-if="item" :value="label(item.replacement_status || item.status)" :severity="item.status === 'replaced' ? 'success' : 'warn'" />
    </header>

    <Card v-if="loading"><template #content><Skeleton height="12rem" /></template></Card>
    <div v-else-if="item" class="space-y-4">
      <Card><template #content>
        <div class="grid gap-3 sm:grid-cols-2">
          <div><span class="text-xs text-slate-500">Customer</span><p class="font-medium text-slate-900">{{ item.order?.shipping_name || '-' }}</p></div>
          <div><span class="text-xs text-slate-500">Order</span><p class="font-medium text-slate-900">{{ item.order?.order_number || '-' }}</p></div>
          <div><span class="text-xs text-slate-500">Exact item</span><p class="font-medium text-slate-900">{{ item.order_item?.product_name || '-' }} · {{ item.order_item?.sku || '-' }}</p></div>
          <div><span class="text-xs text-slate-500">Quantity</span><p class="font-medium text-slate-900">{{ item.received_quantity || item.requested_quantity }}</p></div>
          <div><span class="text-xs text-slate-500">Returned to</span><p class="font-medium text-slate-900">{{ item.pickup?.destination_branch?.name || 'Not assigned' }}</p></div>
          <div><span class="text-xs text-slate-500">Replacement sourced from</span><p class="font-medium text-slate-900">{{ item.replacement_branch?.name || 'Not reserved' }}</p></div>
          <div><span class="text-xs text-slate-500">Return pickup</span><p class="font-medium text-slate-900">{{ label(item.pickup?.status) }}</p></div>
          <div class="sm:col-span-2"><span class="text-xs text-slate-500">Replacement delivery address</span><p class="font-medium text-slate-900">{{ item.order?.shipping_address || '-' }}</p></div>
        </div>
      </template></Card>

      <Card v-if="item.status === 'approved' && item.pickup?.status === 'delivered' && !isLogistics">
        <template #title><span class="text-base">1. Receive returned item</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">This unit goes into quarantine. It will not increase sellable stock or issue a replacement yet.</p>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-medium">Received quantity</label><InputNumber v-model="receiveForm.received_quantity" :min="1" :max="item.requested_quantity" size="small" fluid /></div>
            <div><label class="mb-1 block text-xs font-medium">Condition</label><Select v-model="receiveForm.condition" :options="conditions" optionLabel="label" optionValue="value" size="small" fluid /></div>
            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Inspection notes</label><Textarea v-model="receiveForm.notes" rows="2" size="small" fluid /></div>
          </div>
          <Button label="Receive into quarantine" severity="warn" size="small" class="mt-3" :loading="saving" @click="receive" />
        </template>
      </Card>

      <Card v-if="item.status === 'received' && ['awaiting_stock', null].includes(item.replacement_status) && !isLogistics">
        <template #title><span class="text-base">2. Reserve replacement stock</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">Choose any active store or warehouse branch with the same SKU and variant. No stock is deducted until dispatch.</p>
          <Select v-model="selectedBranch" :options="branchOptions" optionLabel="label" optionValue="id" placeholder="Select fulfillment branch" size="small" fluid />
          <Button label="Reserve stock" severity="warn" size="small" class="mt-3" :disabled="!selectedBranch" :loading="saving" @click="reserve" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'reserved' && isLogistics">
        <template #title><span class="text-base">3. Assign delivery</span></template>
        <template #content>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-medium">Driver</label><Select v-model="driverId" :options="driverOptions" optionLabel="label" optionValue="id" filter size="small" fluid /></div>
            <div><label class="mb-1 block text-xs font-medium">Vehicle</label><Select v-model="vehicleId" :options="vehicleOptions" optionLabel="label" optionValue="id" filter size="small" fluid /></div>
          </div>
          <Button label="Assign driver" severity="warn" size="small" class="mt-3" :disabled="!driverId || !vehicleId" :loading="saving" @click="assign" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'assigned' && isLogistics">
        <template #title><span class="text-base">4. Dispatch replacement</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">Dispatch deducts the reserved units from the selected branch, including warehouse stock.</p>
          <Button label="Dispatch replacement" severity="warn" size="small" :loading="saving" @click="confirmDispatch" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'out_for_delivery' && isLogistics">
        <template #title><span class="text-base">5. Confirm customer delivery</span></template>
        <template #content>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-medium">Received by</label><InputText v-model="receivedBy" size="small" fluid /></div>
            <div><label class="mb-1 block text-xs font-medium">Delivery proof photo</label><input type="file" accept="image/*" class="block w-full text-xs" @change="onProofSelected" /></div>
          </div>
          <Button label="Complete replacement" severity="warn" size="small" class="mt-3" :disabled="!receivedBy.trim() || !proof" :loading="saving" @click="deliver" />
          <Button label="Delivery failed" severity="danger" outlined size="small" class="ml-2 mt-3" @click="failureDialog = true" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'delivery_failed'">
        <template #title><span class="text-base">Delivery needs reattempt</span></template>
        <template #content>
          <p class="text-xs text-slate-600">{{ item.replacement_failure_reason || 'Delivery was not completed.' }}</p>
          <Button v-if="!isDriver" label="Reattempt delivery" severity="warn" size="small" class="mt-3" :loading="saving" @click="act(`${endpoint}/reattempt`)" />
        </template>
      </Card>

      <Card v-if="item.status === 'replaced'"><template #content>
        <p class="font-semibold text-emerald-700">Replacement delivered and case closed.</p>
        <p class="mt-1 text-xs text-slate-500">Received by {{ item.replacement_received_by || '-' }} · {{ formatDate(item.replacement_delivered_at) }}</p>
        <a v-if="item.replacement_proof_path" :href="`/storage/${item.replacement_proof_path}`" target="_blank" rel="noopener" class="mt-2 inline-block text-xs text-orange-600 hover:underline">View delivery proof</a>
      </template></Card>
      <Card v-if="item.status === 'replaced' && !item.inventory_disposition && !isLogistics">
        <template #title><span class="text-base">Return quarantine disposition</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">After separate quality review, release a good item to sellable stock or discard it. Damaged items cannot be released.</p>
          <Button v-if="item.product_condition === 'good'" label="Release to stock" severity="warn" size="small" :loading="saving" @click="dispose('resell')" />
          <Button label="Discard returned item" severity="danger" outlined size="small" class="ml-2" :loading="saving" @click="dispose('discard')" />
        </template>
      </Card>
    </div>
    <Card v-else><template #content><p class="text-sm text-slate-500">Replacement case could not be loaded.</p></template></Card>
    <ConfirmDialog />
    <Dialog v-model:visible="failureDialog" header="Why did delivery fail?" modal class="w-full max-w-md">
      <Textarea v-model="failureReason" rows="3" size="small" fluid placeholder="Provide a reason for the next attempt" />
      <template #footer>
        <Button label="Cancel" text size="small" @click="failureDialog = false" />
        <Button label="Save failure" severity="danger" size="small" :disabled="!failureReason.trim()" :loading="saving" @click="markFailed" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const isDriver = window.location.pathname.startsWith('/driver/')
const isLogistics = window.location.pathname.startsWith('/logistics/') || isDriver
const item = ref<any>(null)
const branches = ref<any[]>([])
const stock = ref<any[]>([])
const drivers = ref<any[]>([])
const vehicles = ref<any[]>([])
const loading = ref(true)
const saving = ref(false)
const selectedBranch = ref<number | null>(null)
const driverId = ref<number | null>(null)
const vehicleId = ref<number | null>(null)
const receivedBy = ref('')
const proof = ref<File | null>(null)
const failureDialog = ref(false)
const failureReason = ref('')
const receiveForm = reactive({ received_quantity: 1, condition: 'good', notes: '' })
const conditions = [{ label: 'Good (quarantine)', value: 'good' }, { label: 'Damaged (quarantine)', value: 'bad' }]
const branchOptions = computed(() => branches.value.map(branch => {
  const available = Number(stock.value.find(row => Number(row.branch_id) === Number(branch.id))?.quantity_available || 0)
  return { id: branch.id, label: `${branch.name} (${branch.branch_type === 'warehouse' ? 'Warehouse' : 'Store'}) · ${available} available` }
}))
const driverOptions = computed(() => drivers.value.map(driver => ({ id: driver.id, label: [driver.fname, driver.lname].filter(Boolean).join(' ') })))
const vehicleOptions = computed(() => vehicles.value.map(vehicle => ({ id: vehicle.id, label: `${vehicle.vehicle_name} · ${vehicle.plate_number || '-'}` })))
const label = (value: any) => String(value || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, (char: string) => char.toUpperCase())
const formatDate = (value: any) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'
const endpoint = `/api/logistics/replacements/${route.params.id}`

const load = async () => {
  loading.value = true
  try {
    const response = await axios.get(endpoint)
    item.value = response.data?.data || null
    branches.value = response.data?.branches || []
    stock.value = response.data?.stock || []
    drivers.value = response.data?.drivers || []
    vehicles.value = response.data?.vehicles || []
    receiveForm.received_quantity = Number(item.value?.requested_quantity || 1)
    selectedBranch.value = item.value?.replacement_branch_id || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load failed', detail: error?.response?.data?.message || 'Unable to load replacement.', life: 3500 })
  } finally { loading.value = false }
}

const act = async (url: string, payload?: any) => {
  saving.value = true
  try {
    const response = await axios.post(url, payload)
    toast.add({ severity: 'success', summary: 'Updated', detail: response.data?.message || 'Replacement updated.', life: 3000 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update failed', detail: error?.response?.data?.message || 'Please try again.', life: 4000 })
  } finally { saving.value = false }
}
const receive = () => act(`/api/crm/returns/${route.params.id}/receive`, receiveForm)
const reserve = () => act(`${endpoint}/reserve`, { branch_id: selectedBranch.value })
const assign = () => act(`${endpoint}/assign`, { driver_user_id: driverId.value, vehicle_id: vehicleId.value })
const confirmDispatch = () => confirm.require({
  header: 'Dispatch replacement?', message: 'This will deduct the reserved units from stock.',
  rejectProps: { label: 'Cancel', outlined: true }, acceptProps: { label: 'Dispatch', severity: 'warn' },
  accept: () => act(`${endpoint}/dispatch`),
})
const onProofSelected = (event: Event) => { proof.value = (event.target as HTMLInputElement).files?.[0] || null }
const deliver = () => {
  const data = new FormData()
  data.append('received_by', receivedBy.value.trim())
  if (proof.value) data.append('proof', proof.value)
  act(`${endpoint}/deliver`, data)
}
const markFailed = async () => {
  await act(`${endpoint}/fail`, { reason: failureReason.value.trim() })
  if (item.value?.replacement_status === 'delivery_failed') {
    failureDialog.value = false
    failureReason.value = ''
  }
}
const dispose = (disposition: 'resell' | 'discard') => confirm.require({
  header: disposition === 'resell' ? 'Release returned item?' : 'Discard returned item?',
  message: disposition === 'resell' ? 'This will add the quarantined unit to sellable stock.' : 'This will remove the unit from quarantine without adding sellable stock.',
  rejectProps: { label: 'Cancel', outlined: true }, acceptProps: { label: 'Confirm', severity: 'warn' },
  accept: () => act(`${endpoint}/disposition`, { disposition }),
})
const goBack = () => router.push({ name: isDriver ? 'driver.deliveries' : isLogistics ? 'logistics.deliveries' : window.location.pathname.startsWith('/warehouse/') ? 'warehouse.returns' : 'inventory.stock-returns' })
onMounted(load)
</script>
