<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-2xl font-bold text-gray-900">My Cart</h1>
    </div>
  
    <div v-if="loading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <Card class="border border-slate-200 shadow-none lg:col-span-2">
        <template #content>
          <div class="space-y-3">
            <Skeleton v-for="idx in 4" :key="idx" height="3.25rem" />
          </div>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3">
            <Skeleton height="1.2rem" />
            <Skeleton height="1.2rem" />
            <Skeleton height="1.2rem" />
            <Skeleton height="2.5rem" />
          </div>
        </template>
      </Card>
    </div>
  
    <Card v-else-if="!items.length" class="border border-slate-200 shadow-none">
      <template #content>
        <div class="py-10 text-center text-gray-500">Your cart is empty.</div>
      </template>
    </Card>
  
    <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <div class="space-y-3 lg:col-span-2">
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <Checkbox :modelValue="allSelected" :binary="true" @update:modelValue="toggleAll" />
                <span class="text-sm text-slate-700">Select all ({{ items.length }}) - Selected {{ selectedItems.length
                  }}</span>
              </div>
  
              <div class="flex items-center gap-2">
                <Button label="Delete" icon="pi pi-trash" severity="danger" text :disabled="!selectedItemIds.length"
                  @click="deleteSelected" />
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="border border-slate-200 shadow-none">
          <template #content>
            <div class="overflow-x-auto">
              <DataTable :value="items" class="p-datatable-sm" responsiveLayout="scroll" tableStyle="min-width: 44rem">
              <Column header="" style="width: 56px">
                <template #body="{ data }">
                  <Checkbox :modelValue="selectedItemIds.includes(data.id)" :binary="true"
                    @update:modelValue="(checked) => toggleSelected(data.id, checked)" />
                </template>
              </Column>
  
              <Column header="Product" style="min-width: 320px">
                <template #body="{ data }">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                      <img :src="data.image || '/F.svg'" :alt="data.product_name"
                        class="h-14 w-14 rounded-lg border border-slate-200 object-cover" />
                      <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">{{ data.store_name || 'Store' }}</p>
                        <p class="truncate text-sm font-semibold text-slate-900">{{ data.product_name }}</p>
                        <p class="truncate text-xs text-slate-500">Variant: {{ data.variation_name || data.variation_sku || 'Standard' }}</p>
                      </div>
                    </div>
                    <p class="shrink-0 text-sm font-semibold text-slate-900">PHP {{ Number(data.unit_price).toFixed(2) }}
                    </p>
                  </div>
                </template>
              </Column>
  
              <Column field="quantity" header="Qty">
                <template #body="{ data }">
                  <InputNumber :modelValue="Number(data.quantity)" :inputId="`qty-${data.id}`" showButtons
                    buttonLayout="horizontal" :step="1" :min="1" fluid
                    @update:modelValue="(value) => updateQty(data, Number(value || 1))">
                    <template #incrementbuttonicon>
                      <span class="pi pi-plus" />
                    </template>
                    <template #decrementbuttonicon>
                      <span class="pi pi-minus" />
                    </template>
                  </InputNumber>
                </template>
              </Column>
  
              <Column header="">
                <template #body="{ data }">
                  <div class="flex items-center justify-end gap-1">
                    <Button
                      :icon="data.is_favorite ? 'pi pi-heart-fill' : 'pi pi-heart'"
                      text
                      :severity="data.is_favorite ? 'danger' : 'secondary'"
                      @click="toggleFavorite(data)"
                    />
                    <Button icon="pi pi-trash" text severity="danger" @click="removeItem(data.id)" />
                  </div>
                </template>
              </Column>
              </DataTable>
            </div>
          </template>
        </Card>
      </div>
  
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-400 font-light">Subtotal</span>
              <span>PHP {{ Number(summary.subtotal || 0).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-400 font-light">Shipping</span>
              <span>PHP {{ Number(summary.shipping || 0).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-400 font-light">Selected Items</span>
              <span>{{ selectedItemsCount }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-400 font-light">Selected Total</span>
              <span>PHP {{ selectedTotal.toFixed(2) }}</span>
            </div>
            <Divider />
            <div class="flex justify-between text-base">
              <span class="text-gray-400 font-light">Cart Total</span>
              <span>PHP {{ Number(summary.total_amount || 0).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-base font-semibold text-slate-900">
              <span>Checkout Total</span>
              <span>PHP {{ selectedTotal.toFixed(2) }}</span>
            </div>
            <Button label="Check Out" severity="warn" class="mt-2 w-full" :disabled="!selectedItemIds.length"
              @click="goCheckout" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import { confirmAlert, showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const router = useRouter()
const items = ref<any[]>([])
const summary = ref<any>({})
const loading = ref(false)
const selectedItemIds = ref<number[]>([])

const selectedItems = computed(() => items.value.filter((item) => selectedItemIds.value.includes(item.id)))
const selectedTotal = computed(() => selectedItems.value.reduce((sum, item) => sum + Number(item.line_total || 0), 0))
const selectedItemsCount = computed(() => selectedItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0))
const allSelected = computed(() => !!items.value.length && selectedItemIds.value.length === items.value.length)

async function loadCart() {
  loading.value = true
  try {
    const response = await ecommerceService.getCart()
    items.value = response.data?.data?.items || []
    summary.value = response.data?.data?.summary || {}

    const validIds = new Set(items.value.map((item) => item.id))
    selectedItemIds.value = selectedItemIds.value.filter((id) => validIds.has(id))
    if (!selectedItemIds.value.length && items.value.length) {
      selectedItemIds.value = items.value.map((item) => item.id)
    }
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load cart' })
  } finally {
    loading.value = false
  }
}

async function updateQty(item: any, quantity: number) {
  if (!quantity || quantity < 1) return
  try {
    const response = await ecommerceService.updateCartItem(item.id, { quantity })
    items.value = response.data?.data?.items || []
    summary.value = response.data?.data?.summary || {}
  } catch (error: any) {
    if (error?.response?.status === 404) {
      showAlert({ severity: 'warn', summary: 'Cart updated', detail: 'Your cart changed. Refreshing items...' })
      await loadCart()
      return
    }
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update quantity' })
  }
}

function toggleSelected(itemId: number, checked: boolean) {
  if (checked) {
    if (!selectedItemIds.value.includes(itemId)) {
      selectedItemIds.value.push(itemId)
    }
    return
  }
  selectedItemIds.value = selectedItemIds.value.filter((id) => id !== itemId)
}

function toggleAll(checked: boolean) {
  if (!checked) {
    selectedItemIds.value = []
    return
  }
  selectedItemIds.value = items.value.map((item) => item.id)
}

function toggleFavorite(item: any) {
  const productId = Number(item?.product_id || 0)
  if (!productId) return

  const nextState = !Boolean(item.is_favorite)
  item.is_favorite = nextState

  ecommerceService
    .toggleFavorite(productId)
    .then((res) => {
      const isFavorite = Boolean(res.data?.data?.is_favorite)
      item.is_favorite = isFavorite
    })
    .catch((error: any) => {
      item.is_favorite = !nextState
      showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update favorites.' })
    })
}

async function removeItem(itemId: number) {
  try {
    await ecommerceService.removeCartItem(itemId)
    selectedItemIds.value = selectedItemIds.value.filter((id) => id !== itemId)
    await loadCart()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to remove item' })
  }
}

async function deleteSelected() {
  if (!selectedItemIds.value.length) return
  const confirmed = await confirmAlert({
    title: 'Delete selected items?',
    text: `Delete ${selectedItemIds.value.length} selected item(s) from cart?`,
    confirmText: 'Delete',
  })
  if (!confirmed) return

  try {
    await Promise.all(selectedItemIds.value.map((itemId) => ecommerceService.removeCartItem(itemId)))
    selectedItemIds.value = []
    showAlert({ severity: 'success', summary: 'Deleted', detail: 'Selected items removed from cart.' })
    await loadCart()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to delete selected items' })
  }
}

function goCheckout() {
  if (!selectedItemIds.value.length) {
    showAlert({ severity: 'warn', summary: 'No items selected', detail: 'Please select at least one item to checkout.' })
    return
  }
  router.push({ name: 'ecommerce.checkout', query: { item_ids: selectedItemIds.value.join(',') } })
}

onMounted(loadCart)
</script>
