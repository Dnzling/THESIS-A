<template>
  <div class="mx-auto max-w-7xl space-y-5 p-4 pb-8">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3"><Button icon="pi pi-arrow-left" text rounded
          @click="router.visit('/sales/wholesale-quotes')" />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Create Wholesale Quotation</h1>
          <p class="text-sm text-slate-500">Set negotiated prices for a business customer without applying retail
            promotions.</p>
        </div>
      </div>
    </div>
    <Message severity="info" :closable="false">Wholesale prices are entered manually and remain separate from
      online-store promotions and product discounts.</Message>
    <div v-if="loading" class="space-y-3">
      <Skeleton height="9rem" />
      <Skeleton height="20rem" />
    </div>
    <template v-else>
      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm lg:col-span-2"><template #title>Customer and
            branch</template><template #content>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="field">
                <label>Customer / Business Name *</label>
                <InputText v-model.trim="form.customer_name" fluid placeholder="Enter customer or business name"
                  :invalid="Boolean(customerNameError)" @blur="touched.customer_name = true" />
                <small v-if="customerNameError" class="text-red-600">{{ customerNameError }}</small>
              </div>
              <div class="field">
                <label>Contact Email *</label>
                <InputText v-model.trim="form.customer_email" type="email" fluid placeholder="name@business.com"
                  :invalid="Boolean(customerEmailError)" @blur="touched.customer_email = true" />
                <small v-if="customerEmailError" class="text-red-600">{{ customerEmailError }}</small>
              </div>
              <div class="field">
                <label>Contact Number *</label>
                <InputMask v-model="form.customer_phone" mask="0999 999 9999" placeholder="09XX XXX XXXX"
                  :autoClear="false" fluid :invalid="Boolean(customerPhoneError)" @blur="touched.customer_phone = true" />
                <small v-if="customerPhoneError" class="text-red-600">{{ customerPhoneError }}</small>
              </div>
              <div class="field"><label>Fulfilment branch *</label><Select v-model="form.branch_id" :options="branches"
                  optionLabel="name" optionValue="id" fluid placeholder="Select branch" :disabled="!branches.length" @change="resetItems"><template #option="{ option }"><div><p class="font-medium">{{ option.name }}</p><p class="text-xs capitalize text-slate-500">{{ option.branch_type }} branch</p></div></template></Select><small v-if="!branches.length" class="text-amber-700">No active storefront or wholesale branches are available.</small></div>
              <div class="field"><label>Valid until</label>
                <DatePicker v-model="form.valid_until" showIcon fluid :minDate="new Date()" />
              </div>
              <div class="field"><label>Payment terms *</label>
                <Select v-model="form.payment_terms" :options="paymentTerms" optionLabel="label" optionValue="value"
                  fluid placeholder="Select payment terms"><template #option="{ option }"><div><p class="font-medium">{{ option.label }}</p><p class="text-xs text-slate-500">{{ option.help }}</p></div></template></Select>
              </div>
            </div>
          </template></Card>
        <Card class="border border-slate-200 shadow-sm"><template #title>Quotation summary</template><template #content>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between text-slate-500"><span>Line items</span><span>{{ form.items.length
              }}</span></div>
              <div class="flex justify-between text-slate-500"><span>Total quantity</span><span>{{ totalQuantity
              }}</span></div>
              <div class="border-t pt-3 flex justify-between text-base font-semibold"><span>Quoted total</span><span>{{
                money(total) }}</span></div>
              <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Stock is validated again when an accepted
                quotation becomes an order.</p>
            </div>
          </template>
        </Card>
      </div>
      <Card class="border border-slate-200 shadow-sm"><template #title>
          <div class="flex items-center justify-between"><span>Quoted products</span><Button label="Add item"
              icon="pi pi-plus" text size="small" :disabled="!form.branch_id" @click="form.items.push(newItem())" />
          </div>
        </template><template #content>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-sm">
              <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th class="px-3 py-3">Product</th>
                  <th class="px-3 py-3 w-28">Available</th>
                  <th class="px-3 py-3 w-28">Quantity</th>
                  <th class="px-3 py-3 w-44">Retail reference</th>
                  <th class="px-3 py-3 w-44">Agreed unit price</th>
                  <th class="px-3 py-3 w-40 text-right">Line total</th>
                  <th class="w-12"></th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="(item, index) in form.items" :key="index">
                  <td class="p-3"><Select v-model="item.branch_inventory_id" :options="availableFor(index)"
                      optionLabel="label" optionValue="id" filter fluid placeholder="Select product"
                      @change="selectProduct(item)" /></td>
                  <td class="p-3">{{ selected(item)?.available ?? '—' }}</td>
                  <td class="p-3">
                    <InputNumber v-model="item.quantity" :min="1" :max="selected(item)?.available || undefined" fluid />
                  </td>
                  <td class="p-3 text-slate-500">{{ money(selected(item)?.base_price) }}</td>
                  <td class="p-3">
                    <InputNumber v-model="item.unit_price" mode="currency" currency="PHP" locale="en-PH" :min="0.01"
                      fluid />
                  </td>
                  <td class="p-3 text-right font-semibold">{{ money(item.quantity * item.unit_price) }}</td>
                  <td><Button icon="pi pi-trash" severity="danger" text rounded :disabled="form.items.length === 1"
                      @click="form.items.splice(index, 1)" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #title>Internal notes</template><template
          #content><Textarea v-model="form.notes" rows="4" fluid
            placeholder="Delivery expectations, negotiated terms, or customer requests" /></template>
      </Card>
      <div class="flex justify-end gap-2"><Button label="Cancel" severity="secondary" outlined
          @click="router.visit('/sales/wholesale-quotes')" /><Button label="Save Draft" icon="pi pi-save"
          :loading="saving" @click="save" /></div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DatePicker from 'primevue/datepicker'
import InputMask from 'primevue/inputmask'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
const toast = useToast(),
  loading = ref(true),
  saving = ref(false),
  products = ref<any[]>([]),
  branches = ref<any[]>([])
const newItem = () => ({
  branch_inventory_id: null as number | null,
  quantity: 1,
  unit_price: 0
})
const form = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  branch_id: null as number | null,
  valid_until: null as Date | null,
  payment_terms: 'online_payment',
  notes: '',
  items: [newItem()]
})
const touched = reactive({ customer_name: false, customer_email: false, customer_phone: false })
const customerNameError = computed(() => touched.customer_name && form.customer_name.trim().length < 2
  ? 'Enter at least 2 characters.' : '')
const customerEmailError = computed(() => touched.customer_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email.trim())
  ? 'Enter a valid email address.' : '')
const customerPhoneError = computed(() => touched.customer_phone && !/^09\d{2}\s?\d{3}\s?\d{4}$/.test(form.customer_phone.trim())
  ? 'Enter a valid 11-digit Philippine mobile number.' : '')
const paymentTerms = [
  { label: 'PayMongo payment link', value: 'online_payment', help: 'Share a secure payment link and wait for confirmation.' },
  { label: 'Verified bank transfer', value: 'bank_transfer', help: 'Confirm the funds in your bank before releasing the order.' }
]
const branchProducts = computed(() =>
  products.value
    .filter(x => Number(x.branch_id) === Number(form.branch_id))
    .map(x => ({
      ...x,
      label: `${x.product_name} (${x.sku || 'No SKU'}) · ${x.available
        } available`
    }))
)
const selected = (item: any) =>
  products.value.find(x => x.id === item.branch_inventory_id)
const availableFor = (index: number) =>
  branchProducts.value.filter(
    x =>
      !form.items.some(
        (item, i) => i !== index && item.branch_inventory_id === x.id
      )
  )
const total = computed(() =>
  form.items.reduce(
    (s, x) => s + (+x.quantity || 0) * (+x.unit_price || 0),
    0
  )
)
const totalQuantity = computed(() =>
  form.items.reduce((s, x) => s + (+x.quantity || 0), 0)
)
const money = (v: any) =>
  new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(+v || 0)
const resetItems = () => (form.items = [newItem()])
const selectProduct = (item: any) =>
  (item.unit_price = +selected(item)?.base_price || 0)
const date = (v: Date | null) =>
  v
    ? `${v.getFullYear()}-${String(v.getMonth() + 1).padStart(
      2,
      '0'
    )}-${String(v.getDate()).padStart(2, '0')}`
    : null
const save = async () => {
  touched.customer_name = true
  touched.customer_email = true
  touched.customer_phone = true
  if (
    customerNameError.value || customerEmailError.value || customerPhoneError.value ||
    !form.branch_id ||
    !form.payment_terms ||
    form.items.some(
      x => !x.branch_inventory_id || x.quantity < 1 || x.unit_price <= 0
    )
  ) {
    toast.add({
      severity: 'warn',
      summary: 'Complete required fields',
      detail: 'Enter valid customer details, choose a branch, and complete the product lines.',
      life: 3000
    })
    return
  }
  saving.value = true
  try {
    const r = await salesService.createWholesaleQuote({
      ...form,
      valid_until: date(form.valid_until)
    })
    toast.add({
      severity: 'success',
      summary: 'Quotation saved',
      life: 2000
    })
    router.visit(`/sales/wholesale-quotes/${r.data.id}`)
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to save',
      detail:
        e.response?.data?.message ||
        Object.values(e.response?.data?.errors || {})[0] ||
        'Please try again.',
      life: 3500
    })
  } finally {
    saving.value = false
  }
}
onMounted(async () => {
  try {
    const r = await salesService.getWholesaleOptions()
    products.value = r.data?.products || []
    branches.value = r.data?.branches || []
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to load form',
      detail: e.response?.data?.message || 'Please try again.',
      life: 3500
    })
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: .4rem
}

.field label {
  font-size: .875rem;
  font-weight: 600;
  color: #334155
}
</style>
