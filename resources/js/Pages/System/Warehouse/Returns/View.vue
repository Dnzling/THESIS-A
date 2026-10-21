<template>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Back to returns"
                    @click="router.push({ name: 'warehouse.returns' })" />
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Return Details</h1>
                    <p class="text-sm text-slate-500">{{ detail?.return_request?.return_number || 'Loading return…' }}
                    </p>
                </div>
            </div>
            <Badge v-if="detail" :value="label(detail.status)" :severity="statusSeverity(detail.status)" />
        </div>

        <div v-if="loading" class="grid gap-4 md:grid-cols-3">
            <Card v-for="index in 3" :key="index" class="border border-slate-200"><template #content>
                    <Skeleton height="5rem" />
                </template>
            </Card>
        </div>

        <template v-else-if="detail">
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border border-slate-200 shadow-sm"><template #content>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Return Request</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ detail.return_request?.return_number ||
                            `Return #${detail.return_id}` }}</p>
                        <p class="mt-1 text-xs text-slate-500">Created {{ formatDate(detail.return_request?.created_at)
                            }}</p>
                    </template>
                </Card>
                <Card class="border border-slate-200 shadow-sm"><template #content>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Original Order</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{
                            detail.return_request?.order?.order_number || '—' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ label(detail.return_request?.return_type ||
                            'Resolution pending') }}</p>
                    </template>
                </Card>
                <Card
                    class="border shadow-sm">
                    <template #content>
                        <p class="text-xs font-medium uppercase tracking-wide ">Pickup Status</p>
                        <p class="mt-2 text-lg font-semibold">{{ label(detail.status) }}</p>
                        <p class="mt-1 text-xs ">{{ detail.delivered_at ? `Received
                            ${formatDate(detail.delivered_at)}` : 'Tracking return to warehouse' }}</p>
                    </template>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border border-slate-200 shadow-sm"><template #title><span
                            class="text-base">Customer</span></template><template #content>
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-slate-500">Name</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ detail.pickup_name ||
                                    detail.return_request?.order?.shipping_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Phone</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ detail.pickup_phone ||
                                    detail.return_request?.order?.shipping_phone || '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-slate-500">Email</dt>
                                <dd class="mt-1 break-all font-medium text-slate-900">{{
                                    detail.return_request?.order?.shipping_email || detail.return_request?.user?.email
                                    || '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-slate-500">Pickup Address</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ detail.pickup_address ||
                                    detail.return_request?.order?.shipping_address || '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-slate-500">Return Reason</dt>
                                <dd class="mt-1 whitespace-pre-line font-medium text-slate-900">{{
                                    detail.return_request?.reason || '—' }}<span v-if="detail.return_request?.details"
                                        class="block font-normal text-slate-600">{{ detail.return_request.details
                                        }}</span></dd>
                            </div>
                        </dl>
                    </template>
                </Card>
                <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Warehouse
                            Destination</span></template><template #content>
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-slate-500">Branch</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ detail.destination_branch?.name || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Contact</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ detail.destination_branch?.contact_number
                                    || '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-slate-500">Address</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ detail.destination_branch?.address || '—'
                                    }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Driver</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ person(detail.driver) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Vehicle</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ vehicleName }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Scheduled</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ formatDate(detail.scheduled_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Delivered</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ formatDate(detail.delivered_at) }}</dd>
                            </div>
                        </dl>
                    </template>
                </Card>
            </div>

            <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Returned
                        Item</span></template><template #content>
                    <DataTable :value="itemRows" size="small" class="text-xs">
                        <Column field="product_name" header="Product"><template #body="{ data }"><span
                                    class="font-semibold text-slate-900">{{ data.product_name || '—' }}</span><small
                                    v-if="data.branch_inventory?.variation" class="block text-slate-500">{{
                                        data.branch_inventory.variation.variation_name }}</small><small
                                    class="block text-slate-500">SKU: {{ data.branch_inventory?.variation?.variation_sku
                                    || data.sku || '—' }}</small></template>
                        </Column>
                        <Column field="requested_quantity" header="Requested Qty" />
                        <Column field="unit_price" header="Unit Price"><template #body="{ data }">{{
                                currency(data.unit_price) }}</template>
                        </Column>
                        <Column header="Return Type"><template #body>{{ label(detail.return_request?.return_type ||
                                'Pending') }}</template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card class="border border-slate-200 shadow-sm"><template #title><span class="text-base">Delivery
                        Timeline</span></template><template #content>
                    <div v-if="detail.logs?.length" class="space-y-0">
                        <div v-for="(event, index) in detail.logs" :key="event.id || index"
                            class="relative flex gap-4 pb-6 last:pb-0">
                            <div class="flex w-5 shrink-0 flex-col items-center"><span class="mt-1 h-3 w-3 rounded-full"
                                    :class="index === 0 ? 'bg-orange-500' : 'bg-slate-300'" /><span
                                    v-if="index < detail.logs.length - 1" class="mt-1 w-px flex-1 bg-slate-200" /></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-medium text-slate-900">{{ label(event.status_to || event.event_type)
                                        }}</p><time class="text-xs text-slate-500">{{ formatDateTime(event.created_at)
                                        }}</time>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ event.message || event.notes || 'Return progress updated.' }}</p>
                                <p v-if="event.location_address" class="mt-1 text-xs text-slate-500">{{
                                    event.location_address }}</p><a v-if="event.proof_photo_url"
                                    :href="event.proof_photo_url" target="_blank" rel="noopener"
                                    class="mt-3 block h-24 w-24 overflow-hidden rounded-lg border border-slate-200"><img
                                        :src="event.proof_photo_url" alt="Return delivery proof"
                                        class="h-full w-full object-cover" /></a>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-5 text-center text-sm text-slate-500">No delivery timeline has been recorded.
                    </p>
                </template></Card>
        </template>

        <Card v-else class="border border-slate-200 text-center"><template #content>
                <p class="py-8 text-sm text-slate-500">{{ loadError || 'Warehouse return could not be found.' }}</p>
                <Button label="Back to Returns" severity="warn" size="small"
                    @click="router.push({ name: 'warehouse.returns' })" />
            </template>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(true)
const detail = ref<any>(null)
const loadError = ref('')
const itemRows = computed(() => {
    const request = detail.value?.return_request
    const item = request?.order_item
    return item ? [{ ...item, requested_quantity: request.requested_quantity || 1 }] : []
})
const vehicleName = computed(() => {
    const vehicle = detail.value?.vehicle
    return [vehicle?.vehicle_name, vehicle?.plate_number].filter(Boolean).join(' · ') || '—'
})
const person = (user: any) => [user?.fname, user?.lname].filter(Boolean).join(' ') || '—'
const label = (value: any) => String(value || '—').replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase())
const statusSeverity = (value: string) => value === 'delivered' ? 'success' : value === 'out_for_delivery' ? 'info' : value === 'picked_up' ? 'warn' : 'secondary'
const formatDate = (value?: string) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value)) : '—'
const formatDateTime = (value?: string) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
const currency = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const load = async () => {
    loading.value = true
    loadError.value = ''
    try {
        const response = await axiosClient.get(`/api/warehouse/returns/${route.params.id}`)
        detail.value = response.data?.data || null
        if (!detail.value) loadError.value = 'Warehouse return could not be found.'
    } catch (error: any) {
        loadError.value = error?.response?.data?.message || 'Unable to load this warehouse return.'
        toast.add({ severity: 'error', summary: 'Return Load Failed', detail: loadError.value, life: 3500 })
    } finally {
        loading.value = false
    }
}
onMounted(load)
</script>
