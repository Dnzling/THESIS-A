<template>
    <div class="min-h-screen space-y-4 p-4">
        <header>
            <h1 class="text-xl font-bold text-slate-900">{{ isWarehouse ? 'Warehouse Returns' : 'Inventory Stock Returns' }}</h1>
            <p class="text-xs text-slate-500">Review returned items delivered to {{ isWarehouse ? 'warehouse' : 'store' }} branches.</p>
        </header>

        <Card class="border border-slate-200 shadow-sm">
            <template #content>
                <div class="mb-4 flex flex-wrap items-end gap-3">
                    <div class="w-full md:w-72">
                        <IconField class="w-full">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" placeholder="Search return, order, customer, or item" class="w-full text-xs" size="small" @keyup.enter="load" />
                        </IconField>
                    </div>
                    <div class="w-full md:w-48">
                        <Select v-model="status" :options="statuses" optionLabel="label" optionValue="value" placeholder="All Statuses" showClear class="w-full text-xs" size="small" @change="load" />
                    </div>
                    <div class="w-full md:w-60">
                        <DatePicker v-model="dateRange" selectionMode="range" placeholder="Date range" dateFormat="M d, yy" showIcon iconDisplay="input" class="w-full text-xs" size="small" @date-select="load" />
                    </div>
                    <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" class="text-xs" @click="clear" />
                    <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" class="text-xs" :loading="loading" @click="load" />
                </div>

                <div v-if="loading" class="space-y-2">
                    <div v-for="row in 7" :key="row" class="grid grid-cols-7 gap-2">
                        <Skeleton v-for="cell in 7" :key="cell" height="2.5rem" />
                    </div>
                </div>
                <DataTable v-else :value="rows" rowHover paginator :rows="15" :rowsPerPageOptions="[10, 15, 25, 50]" class="p-datatable-sm text-xs" responsiveLayout="scroll">
                    <template #empty>
                        <div class="py-12 text-center">
                            <i class="pi pi-inbox text-3xl text-slate-300" />
                            <p class="mt-3 text-slate-500">No returns found for this destination.</p>
                            <p class="mt-1 text-xs text-slate-400">Delivered returns awaiting inspection will appear here.</p>
                        </div>
                    </template>
                    <Column field="created_at" header="Date" sortable style="min-width: 140px"><template #body="{ data }"><span class="text-xs text-slate-600">{{ date(data.created_at) }}</span></template></Column>
                    <Column header="Return">
                        <template #body="{ data }">
                            <span class="font-semibold text-slate-900">{{ data.return_request?.return_number || `Return #${data.return_id}` }}</span>
                            <small class="block text-slate-500">{{ data.return_request?.order?.order_number || 'Order not available' }}</small>
                        </template>
                    </Column>
                    <Column header="Customer">
                        <template #body="{ data }">
                            {{ data.pickup_name || data.return_request?.order?.shipping_name || '-' }}
                            <small class="block text-slate-500">{{ data.pickup_phone || data.return_request?.order?.shipping_phone || 'No phone recorded' }}</small>
                        </template>
                    </Column>
                    <Column header="Returned Item">
                        <template #body="{ data }">
                            {{ data.return_request?.order_item?.product_name || '-' }}
                            <small class="block text-slate-500">{{ data.return_request?.order_item?.sku || 'No SKU' }} · Qty {{ data.return_request?.requested_quantity || 1 }}</small>
                        </template>
                    </Column>
                    <Column header="Destination">
                        <template #body="{ data }"><span class="font-medium text-slate-800">{{ data.destination_branch?.name || '-' }}</span></template>
                    </Column>
                    <Column header="Driver">
                        <template #body="{ data }">{{ [data.driver?.fname, data.driver?.lname].filter(Boolean).join(' ') || 'Unassigned' }}</template>
                    </Column>
                    <Column header="Status" style="min-width: 130px">
                        <template #body="{ data }"><Badge :value="label(data.status)" :severity="severity(data.status)" /></template>
                    </Column>
                    <Column header="Actions" style="min-width: 120px">
                        <template #body="{ data }">
                            <div class="flex gap-2" @click.stop>
                                <Button v-if="canInspect(data)" label="Inspect" severity="warn" size="small" @click="openInspection(data)" />
                                <Button v-if="data.return_request?.return_type === 'replacement' && data.return_request?.status === 'received'" label="Replacement" severity="warn" outlined size="small" @click="openReplacement(data)" />
                                <Button v-if="!canInspect(data) && !(data.return_request?.return_type === 'replacement' && data.return_request?.status === 'received')" label="View" icon="pi pi-eye" outlined rounded size="small" class="text-xs" aria-label="View delivery" @click="open(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="inspectionVisible" modal header="Inspect Returned Item" class="w-full max-w-lg">
            <div v-if="selected" class="space-y-4 text-sm">
                <div class="rounded-xl bg-orange-50 p-3 text-orange-900">
                    <p class="font-semibold">{{ selected.return_request?.order_item?.product_name || 'Returned item' }}</p>
                    <p class="mt-1 text-xs">Inventory will be recorded at {{ selected.destination_branch?.name || 'the selected branch' }}.</p>
                </div>
                <p class="text-slate-600">{{ selected.return_request?.return_type === 'replacement' ? 'The returned item will be quarantined while replacement stock is prepared.' : 'The item condition determines whether it returns to sellable stock.' }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-medium">Received quantity</label><InputNumber v-model="receivedQuantity" :min="1" :max="Number(selected.return_request?.requested_quantity || 1)" size="small" fluid /></div>
                    <div><label class="mb-1 block text-xs font-medium">Condition</label><Select v-model="condition" :options="conditions" optionLabel="label" optionValue="value" size="small" fluid /></div>
                </div>
                <div><label class="mb-1 block text-xs font-medium">Inspection notes</label><Textarea v-model="notes" rows="3" fluid /></div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text size="small" :disabled="submitting" @click="inspectionVisible = false" />
                <Button label="Complete Inspection" severity="warn" size="small" :loading="submitting" :disabled="!validInspection || submitting" @click="submitInspection" />
            </template>
        </Dialog>
        <ConfirmDialog />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import Skeleton from 'primevue/skeleton'

const props = defineProps<{ mode: 'warehouse' | 'inventory' }>()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const isWarehouse = computed(() => props.mode === 'warehouse')
const loading = ref(true)
const submitting = ref(false)
const rows = ref<any[]>([])
const search = ref('')
const status = ref('')
const dateRange = ref<Date[] | null>(null)
const hasActiveFilters = computed(() => Boolean(search.value.trim() || status.value || dateRange.value?.some(Boolean)))
const inspectionVisible = ref(false)
const selected = ref<any>(null)
const receivedQuantity = ref(1)
const condition = ref('good')
const notes = ref('')
const statuses = [
    { label: 'All Statuses', value: '' },
    { label: 'Assigned', value: 'assigned' },
    { label: 'Picked Up', value: 'picked_up' },
    { label: 'Out for Delivery', value: 'out_for_delivery' },
    { label: 'Delivered', value: 'delivered' },
]
const conditions = [
    { label: 'Good condition (resell)', value: 'good' },
    { label: 'Bad condition (discard)', value: 'bad' },
]
const label = (value: any) => String(value || '-').replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase())
const severity = (value: string) => value === 'delivered' ? 'success' : value === 'out_for_delivery' || value === 'assigned' ? 'info' : value === 'picked_up' ? 'warn' : 'secondary'
const date = (value: string) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value)) : '-'
const canInspect = (row: any) => row.status === 'delivered' && row.return_request?.status === 'approved' && ['refund', 'replacement'].includes(row.return_request?.return_type)
const validInspection = computed(() => selected.value && Number.isInteger(receivedQuantity.value) && receivedQuantity.value >= 1 && receivedQuantity.value <= Number(selected.value.return_request?.requested_quantity || 1) && ['good', 'bad'].includes(condition.value))

const load = async () => {
    loading.value = true
    try {
        const url = isWarehouse.value ? '/api/warehouse/returns' : '/api/inventory/returns/customer/destinations'
        const [start, end] = dateRange.value || []
        const response = await axiosClient.get(url, { params: { search: search.value || undefined, status: status.value || undefined, start_date: start ? isoDate(start) : undefined, end_date: end ? isoDate(end) : undefined } })
        rows.value = response.data?.data || []
    } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Unable to load destination returns.', life: 3000 })
    } finally {
        loading.value = false
    }
}
const clear = () => { search.value = ''; status.value = ''; dateRange.value = null; void load() }
const open = (row: any) => router.push({ name: isWarehouse.value ? 'warehouse.returns.view' : 'logistics.return-pickups.detail', params: { id: isWarehouse.value ? row.id : row.id } })
const isoDate = (value: Date) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
const openReplacement = (row: any) => router.push({ name: isWarehouse.value ? 'warehouse.replacements.detail' : 'inventory.replacements.detail', params: { id: row.return_id } })
const openInspection = (row: any) => {
    selected.value = row
    receivedQuantity.value = Number(row.return_request?.requested_quantity || 1)
    condition.value = 'good'
    notes.value = ''
    inspectionVisible.value = true
}
const submitInspection = () => {
    if (!validInspection.value || !selected.value) return
    confirm.require({
        header: 'Complete return inspection?',
        message: `This will record the returned item at ${selected.value.destination_branch?.name || 'the destination branch'}.`,
        rejectProps: { label: 'Cancel', outlined: true, size: 'small' },
        acceptProps: { label: 'Confirm', severity: 'warn', size: 'small' },
        accept: async () => {
            submitting.value = true
            try {
                const url = isWarehouse.value ? `/api/warehouse/returns/${selected.value.return_id}/inspect` : `/api/inventory/returns/customer/${selected.value.return_id}/inspect`
                await axiosClient.post(url, { received_quantity: receivedQuantity.value, condition: condition.value, notes: notes.value || undefined })
                inspectionVisible.value = false
                toast.add({ severity: 'success', summary: 'Return Inspected', detail: 'The returned item was recorded at the destination branch.', life: 3000 })
                await load()
            } catch (error: any) {
                toast.add({ severity: 'error', summary: 'Inspection Failed', detail: error?.response?.data?.message || 'Unable to inspect this return.', life: 4000 })
            } finally {
                submitting.value = false
            }
        },
    })
}
onMounted(load)
</script>
