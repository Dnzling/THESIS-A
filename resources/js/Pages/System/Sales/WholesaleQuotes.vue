<template>
  <div class="space-y-5 p-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-2xl font-semibold text-slate-900">Wholesale quotations</h1><p class="text-sm text-slate-500">Prepare a bulk offer for a CRM customer, then convert an accepted offer into a Sales order.</p></div>
      <div class="flex gap-2"><Button label="Orders" severity="secondary" outlined @click="router.push({ name: 'sales.orders' })" /><Button v-if="canManage" label="New quotation" icon="pi pi-plus" @click="openCreate" /></div>
    </div>
    <Card class="border border-slate-200 shadow-sm"><template #content>
      <div class="mb-4 flex gap-2"><InputText v-model="search" placeholder="Search quotation or customer" class="w-full max-w-sm" @keyup.enter="load" /><Button icon="pi pi-search" severity="secondary" outlined @click="load" /></div>
      <DataTable :value="rows" :loading="loading" dataKey="id" paginator :rows="15" size="small" stripedRows>
        <Column field="quote_number" header="Quotation"><template #body="{ data }"><button class="font-semibold text-orange-600 hover:underline" @click="openDetail(data.id)">{{ data.quote_number }}</button></template></Column>
        <Column header="CRM customer"><template #body="{ data }">{{ data.crm_lead?.full_name || '—' }}</template></Column>
        <Column header="Items"><template #body="{ data }">{{ data.items?.length || 0 }}</template></Column>
        <Column header="Value"><template #body="{ data }">{{ money(data.subtotal) }}</template></Column>
        <Column header="Valid until"><template #body="{ data }">{{ dateLabel(data.valid_until) }}</template></Column>
        <Column header="Status"><template #body="{ data }"><Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
        <template #empty><div class="py-10 text-center text-sm text-slate-500">No wholesale quotations yet.</div></template>
      </DataTable>
    </template></Card>

    <Dialog v-model:visible="createVisible" modal header="New wholesale quotation" :style="{ width: 'min(94vw, 58rem)' }">
      <div class="space-y-4">
        <Message severity="info" :closable="false">Select a CRM customer and products from one branch. An agreed unit price can differ from the retail price. Sharing the quotation with the customer is manual; “Mark sent” only updates its status.</Message>
        <div class="grid gap-3 sm:grid-cols-2">
          <div><label class="mb-1 block text-sm font-medium">CRM customer *</label><Select v-model="form.crm_lead_id" :options="leads" optionLabel="full_name" optionValue="id" filter class="w-full" placeholder="Select customer" /></div>
          <div><label class="mb-1 block text-sm font-medium">Branch *</label><Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" class="w-full" placeholder="Select branch" @change="form.items = [newItem()]" /></div>
          <div><label class="mb-1 block text-sm font-medium">Valid until</label><DatePicker v-model="form.valid_until" dateFormat="MM d, yy" showIcon fluid :minDate="new Date()" /></div>
          <div><label class="mb-1 block text-sm font-medium">Payment terms</label><InputText v-model="form.payment_terms" class="w-full" placeholder="e.g. Cash on delivery" /></div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3">
          <div class="mb-3 flex items-center justify-between"><h2 class="font-semibold">Products</h2><Button label="Add item" icon="pi pi-plus" text size="small" @click="form.items.push(newItem())" /></div>
          <div v-for="(item, index) in form.items" :key="index" class="mb-3 grid items-end gap-2 border-b border-slate-100 pb-3 sm:grid-cols-[minmax(0,2fr)_6rem_8rem_2rem]">
            <div><label class="mb-1 block text-xs text-slate-500">Product</label><Select v-model="item.branch_inventory_id" :options="branchProducts" optionLabel="label" optionValue="id" filter class="w-full" placeholder="Select product" @change="setSuggestedPrice(item)" /></div>
            <div><label class="mb-1 block text-xs text-slate-500">Quantity</label><InputNumber v-model="item.quantity" :min="1" fluid /></div>
            <div><label class="mb-1 block text-xs text-slate-500">Agreed unit price</label><InputNumber v-model="item.unit_price" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid /></div>
            <Button icon="pi pi-trash" severity="danger" text :disabled="form.items.length === 1" @click="form.items.splice(index, 1)" />
          </div>
          <p class="text-right font-semibold">Total: {{ money(total) }}</p>
        </div>
        <div><label class="mb-1 block text-sm font-medium">Notes</label><Textarea v-model="form.notes" rows="2" class="w-full" /></div>
      </div>
      <template #footer><Button label="Cancel" severity="secondary" text @click="createVisible = false" /><Button label="Save draft" :loading="saving" @click="createQuote" /></template>
    </Dialog>

    <Dialog v-model:visible="detailVisible" modal :header="detail?.quote_number || 'Quotation'" :style="{ width: 'min(94vw, 48rem)' }">
      <div v-if="detail" class="space-y-4">
        <div class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
          <div><span class="text-slate-500">CRM customer</span><p class="font-semibold">{{ detail.crm_lead?.full_name }}</p><p>{{ detail.crm_lead?.email || detail.crm_lead?.phone }}</p></div>
          <div><span class="text-slate-500">Status</span><p><Tag :value="statusLabel(detail.status)" :severity="statusSeverity(detail.status)" /></p></div>
          <div><span class="text-slate-500">Valid until</span><p class="font-medium">{{ dateLabel(detail.valid_until) }}</p></div>
          <div><span class="text-slate-500">Payment terms</span><p class="font-medium">{{ detail.payment_terms || 'Not specified' }}</p></div>
        </div>
        <DataTable :value="detail.items || []" size="small"><Column field="product_name" header="Product" /><Column field="quantity" header="Qty" /><Column header="Unit price"><template #body="{ data }">{{ money(data.unit_price) }}</template></Column><Column header="Total"><template #body="{ data }">{{ money(data.line_total) }}</template></Column></DataTable>
        <p class="text-right text-lg font-semibold">Total: {{ money(detail.subtotal) }}</p>
        <p v-if="detail.notes" class="text-sm text-slate-600">{{ detail.notes }}</p>
        <Message v-if="detail.sales_order_id" severity="success" :closable="false">Converted to Sales order. Payment: {{ detail.order_payment_status || 'pending' }}. <button class="font-semibold underline" @click="router.push({ name: 'sales.pos.order-detail', params: { id: detail.sales_order_id } })">View order</button></Message>
      </div>
      <template #footer><Button label="Close" severity="secondary" text @click="detailVisible = false" /><Button v-if="canManage && detail?.status === 'draft'" label="Mark sent" @click="changeStatus('sent')" /><Button v-if="canManage && detail?.status === 'sent'" label="Decline" severity="danger" outlined @click="changeStatus('declined')" /><Button v-if="canManage && detail?.status === 'sent'" label="Mark accepted" @click="changeStatus('accepted')" /><Button v-if="canManage && detail?.status === 'accepted'" label="Create Sales order" icon="pi pi-check" :loading="saving" @click="convertQuote" /><Button v-if="canManage && detail?.status === 'converted' && detail?.order_payment_status !== 'paid'" label="Record payment" icon="pi pi-wallet" @click="paymentVisible = true" /></template>
    </Dialog>

    <Dialog v-model:visible="paymentVisible" modal header="Record wholesale payment" :style="{ width: '26rem' }">
      <div class="space-y-3"><Message severity="warn" :closable="false">Confirm the full payment was received. Recording it will deduct the sold stock and issue a receipt.</Message><div><label class="mb-1 block text-sm font-medium">Payment method</label><Select v-model="paymentMethod" :options="[{ label: 'Cash', value: 'cash' }, { label: 'Card', value: 'card' }]" optionLabel="label" optionValue="value" class="w-full" /></div><div><label class="mb-1 block text-sm font-medium">Payment reference (optional)</label><InputText v-model="paymentReference" class="w-full" /></div></div>
      <template #footer><Button label="Cancel" severity="secondary" text @click="paymentVisible = false" /><Button label="Confirm payment" :loading="saving" @click="recordPayment" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import salesService from '@/services/sales.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('sales.orders.manage'))
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const rows = ref<any[]>([])
const leads = ref<any[]>([])
const products = ref<any[]>([])
const createVisible = ref(false)
const detailVisible = ref(false)
const paymentVisible = ref(false)
const paymentMethod = ref('cash')
const paymentReference = ref('')
const detail = ref<any>(null)
const newItem = () => ({ branch_inventory_id: null as number | null, quantity: 1, unit_price: 0 })
const form = reactive({ crm_lead_id: null as number | null, branch_id: null as number | null, valid_until: null as Date | null, payment_terms: 'Cash on delivery', notes: '', items: [newItem()] })
const branches = computed(() => [...new Map(products.value.map(row => [row.branch_id, { id: row.branch_id, name: row.branch_name }])).values()])
const branchProducts = computed(() => products.value.filter(row => row.branch_id === form.branch_id).map(row => ({ ...row, label: `${row.product_name} (${row.sku || 'No SKU'}) · ${row.available} available` })))
const total = computed(() => form.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0))
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
const dateLabel = (value: string | null) => value ? new Intl.DateTimeFormat('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(`${value.slice(0, 10)}T12:00:00+08:00`)) : 'No expiry'
const dateValue = (value: Date | null) => value ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}` : null
const statusLabel = (value: string) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase())
const statusSeverity = (value: string) => value === 'converted' ? 'success' : value === 'accepted' ? 'info' : value === 'declined' ? 'danger' : value === 'sent' ? 'warn' : 'secondary'
const errorText = (error: any) => error?.response?.data?.message || Object.values(error?.response?.data?.errors || {})[0] || 'Unable to complete request.'
const load = async () => { loading.value = true; try { rows.value = (await salesService.getWholesaleQuotes({ search: search.value })).data?.data || [] } catch (error) { toast.add({ severity: 'error', summary: 'Load failed', detail: String(errorText(error)), life: 3500 }) } finally { loading.value = false } }
const openCreate = async () => { try { const result = await salesService.getWholesaleOptions(); leads.value = result.data?.leads || []; products.value = result.data?.products || []; Object.assign(form, { crm_lead_id: null, branch_id: null, valid_until: null, payment_terms: 'Cash on delivery', notes: '', items: [newItem()] }); createVisible.value = true } catch (error) { toast.add({ severity: 'error', summary: 'Options unavailable', detail: String(errorText(error)), life: 3500 }) } }
const setSuggestedPrice = (item: ReturnType<typeof newItem>) => { item.unit_price = Number(products.value.find(row => row.id === item.branch_inventory_id)?.base_price || 0) }
const createQuote = async () => { if (!form.crm_lead_id || !form.branch_id || form.items.some(item => !item.branch_inventory_id || item.quantity < 1 || item.unit_price < 0)) { toast.add({ severity: 'warn', summary: 'Complete the quotation', life: 3000 }); return } saving.value = true; try { await salesService.createWholesaleQuote({ ...form, valid_until: dateValue(form.valid_until) }); createVisible.value = false; await load(); toast.add({ severity: 'success', summary: 'Quotation saved', life: 2500 }) } catch (error) { toast.add({ severity: 'error', summary: 'Save failed', detail: String(errorText(error)), life: 3500 }) } finally { saving.value = false } }
const openDetail = async (id: number) => { try { detail.value = (await salesService.getWholesaleQuote(id)).data; detailVisible.value = true } catch (error) { toast.add({ severity: 'error', summary: 'Load failed', detail: String(errorText(error)), life: 3500 }) } }
const changeStatus = async (status: string) => { if (!detail.value) return; try { detail.value = (await salesService.updateWholesaleQuoteStatus(detail.value.id, status)).data; await openDetail(detail.value.id); await load() } catch (error) { toast.add({ severity: 'error', summary: 'Update failed', detail: String(errorText(error)), life: 3500 }) } }
const convertQuote = async () => { if (!detail.value) return; saving.value = true; try { await salesService.convertWholesaleQuote(detail.value.id); await openDetail(detail.value.id); await load() } catch (error) { toast.add({ severity: 'error', summary: 'Conversion failed', detail: String(errorText(error)), life: 3500 }) } finally { saving.value = false } }
const recordPayment = async () => { if (!detail.value) return; saving.value = true; try { await salesService.recordWholesalePayment(detail.value.id, { payment_method: paymentMethod.value, payment_reference: paymentReference.value || undefined }); paymentVisible.value = false; await openDetail(detail.value.id); await load(); toast.add({ severity: 'success', summary: 'Payment recorded', life: 2500 }) } catch (error) { toast.add({ severity: 'error', summary: 'Payment failed', detail: String(errorText(error)), life: 3500 }) } finally { saving.value = false } }
onMounted(load)
</script>
