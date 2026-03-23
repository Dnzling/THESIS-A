<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">In-Store POS</h1>
            <p class="text-sm text-gray-500">Fast checkout with live stock control.</p>
          </div>
          <Button severity="info" outlined label="Refresh Products" icon="pi pi-refresh" @click="loadProducts" />
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl lg:col-span-2">
        <template #title>Products</template>
        <template #content>
          <InputText v-model="search" fluid placeholder="Search products..." class="mb-3" />
          <DataTable :value="products" :loading="loadingProducts" stripedRows>
            <Column field="product.product_name" header="Product" />
            <Column field="product.sku" header="SKU" />
            <Column field="quantity_available" header="Stock" />
            <Column header="Price"><template #body="{data}">{{ money(data.product?.discounted_price || data.product?.base_price || 0) }}</template></Column>
            <Column header="Action"><template #body="{data}"><Button text severity="info" icon="pi pi-plus" @click="addToCart(data)" /></template></Column>
          </DataTable>
        </template>
      </Card>

      <Card class="border border-gray-100 shadow-sm rounded-2xl">
        <template #title>Cart</template>
        <template #content>
          <div v-if="!cart.length" class="text-sm text-gray-500">No items yet.</div>
          <div v-else class="space-y-2">
            <div v-for="item in cart" :key="item.branch_inventory_id" class="rounded-lg border border-gray-100 p-2">
              <p class="font-medium text-sm">{{ item.product_name }}</p>
              <p class="text-xs text-gray-500">{{ money(item.unit_price) }}</p>
              <div class="flex items-center justify-between mt-2">
                <InputNumber v-model="item.quantity" :min="1" fluid />
                <Button text severity="danger" icon="pi pi-trash" @click="removeCart(item.branch_inventory_id)" />
              </div>
            </div>
          </div>
          <Divider />
          <InputText v-model="customerName" fluid placeholder="Customer name (optional)" class="mb-2" />
          <InputText v-model="customerPhone" fluid placeholder="Customer phone (optional)" class="mb-2" />
          <Select v-model="paymentMethod" :options="paymentOptions" optionLabel="label" optionValue="value" fluid class="mb-2" />
          <InputNumber v-model="amountTendered" fluid mode="currency" currency="PHP" locale="en-PH" placeholder="Amount tendered" class="mb-3" />
          <div class="text-sm space-y-1 mb-3">
            <p>Subtotal: <span class="font-semibold">{{ money(subtotal) }}</span></p>
            <p>Total: <span class="font-semibold">{{ money(total) }}</span></p>
            <p>Change: <span class="font-semibold">{{ money(changeAmount) }}</span></p>
          </div>
          <Button severity="info" fluid :loading="checkingOut" label="Checkout" @click="checkout" />
        </template>
      </Card>
    </div>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #title>Recent POS Orders</template>
      <template #content>
        <DataTable :value="orders" :loading="loadingOrders" stripedRows>
          <Column field="order_number" header="Order" />
          <Column field="customer_name" header="Customer" />
          <Column field="payment_method" header="Payment" />
          <Column field="total_amount" header="Total"><template #body="{data}">{{ money(data.total_amount) }}</template></Column>
          <Column field="created_at" header="Date"><template #body="{data}">{{ new Date(data.created_at).toLocaleString('en-PH') }}</template></Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Divider from 'primevue/divider'
import { useToast } from 'primevue/usetoast'

const toast = useToast()
const search = ref('')
const products = ref<any[]>([])
const orders = ref<any[]>([])
const loadingProducts = ref(false)
const loadingOrders = ref(false)
const checkingOut = ref(false)
const cart = ref<any[]>([])
const customerName = ref('')
const customerPhone = ref('')
const paymentMethod = ref('cash')
const amountTendered = ref<number | null>(null)
const paymentOptions = [
  { label: 'Cash', value: 'cash' },
  { label: 'Card', value: 'card' },
  { label: 'GCash', value: 'gcash' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'Mixed', value: 'mixed' },
]

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const res = await salesService.getPosProducts({ per_page: 40, search: search.value || undefined })
    products.value = res?.data?.data || []
  } finally { loadingProducts.value = false }
}
const loadOrders = async () => {
  loadingOrders.value = true
  try {
    const res = await salesService.getPosOrders({ per_page: 20 })
    orders.value = res?.data?.data || []
  } finally { loadingOrders.value = false }
}

const addToCart = (row: any) => {
  const id = row.id
  const existing = cart.value.find((i) => i.branch_inventory_id === id)
  if (existing) { existing.quantity += 1; return }
  cart.value.push({
    branch_inventory_id: id,
    product_name: row.product?.product_name || 'Product',
    unit_price: Number(row.product?.discounted_price || row.product?.base_price || 0),
    quantity: 1,
  })
}
const removeCart = (id: number) => { cart.value = cart.value.filter((i) => i.branch_inventory_id !== id) }

const subtotal = computed(() => cart.value.reduce((s, i) => s + (Number(i.unit_price) * Number(i.quantity || 0)), 0))
const total = computed(() => subtotal.value)
const changeAmount = computed(() => Math.max(0, Number(amountTendered.value || 0) - total.value))

const checkout = async () => {
  if (!cart.value.length) return
  checkingOut.value = true
  try {
    await salesService.checkout({
      customer_name: customerName.value || undefined,
      customer_phone: customerPhone.value || undefined,
      payment_method: paymentMethod.value,
      amount_tendered: amountTendered.value || 0,
      items: cart.value.map((i) => ({ branch_inventory_id: i.branch_inventory_id, quantity: i.quantity })),
    })
    toast.add({ severity: 'success', summary: 'Success', detail: 'POS checkout completed.', life: 2500 })
    cart.value = []
    customerName.value = ''
    customerPhone.value = ''
    amountTendered.value = null
    loadProducts()
    loadOrders()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Checkout failed', detail: error?.response?.data?.message || 'Failed checkout.', life: 3000 })
  } finally { checkingOut.value = false }
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))

watch(search, () => loadProducts())
onMounted(() => { loadProducts(); loadOrders() })
</script>

