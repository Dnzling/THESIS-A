<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-2xl font-bold text-gray-900">My Cart</h1>
    </div>

    <div v-if="loading" class="space-y-3">
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3">
            <Skeleton v-for="idx in 5" :key="idx" height="3.25rem" />
          </div>
        </template>
      </Card>
    </div>

    <Card v-else-if="!cartGroups.length" class="border border-slate-200 shadow-none">
      <template #content>
        <div class="py-10 text-center text-gray-500">Your cart is empty.</div>
      </template>
    </Card>

    <div v-else class="space-y-4">
      <Card v-for="group in cartGroups" :key="group.store_id" class="border border-slate-200 shadow-none">
        <template #content>
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ group.store_name }}</p>
              <p class="text-xs text-slate-500">{{ group.items.length }} item(s)</p>
            </div>
          </div>

          <div class="mt-3 overflow-x-auto">
            <DataTable :value="group.items" class="p-datatable-sm" responsiveLayout="scroll" tableStyle="min-width: 44rem">
              <Column header="" style="width: 56px">
                <template #body="{ data }">
                  <Checkbox
                    :modelValue="selectedIdsForStore(group.store_id).includes(data.id)"
                    :binary="true"
                    @update:modelValue="(checked) => toggleSelected(group.store_id, data.id, checked)"
                  />
                </template>
              </Column>

              <Column header="Product" style="min-width: 320px">
                <template #body="{ data }">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                      <img :src="data.image || '/F.svg'" :alt="data.product_name" class="h-14 w-14 rounded-lg border border-slate-200 object-cover" />
                      <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ data.product_name }}</p>
                        <p class="truncate text-xs text-slate-500">Variant: {{ data.variation_name || data.variation_sku || 'Standard' }}</p>
                      </div>
                    </div>
                    <p class="shrink-0 text-sm font-semibold text-slate-900">PHP {{ Number(data.unit_price).toFixed(2) }}</p>
                  </div>
                </template>
              </Column>

              <Column field="quantity" header="Qty">
                <template #body="{ data }">
                  <InputNumber
                    :modelValue="Number(data.quantity)"
                    :inputId="`qty-${data.id}`"
                    showButtons
                    buttonLayout="horizontal"
                    :step="1"
                    :min="1"
                    fluid
                    @update:modelValue="(value) => updateQty(group.store_id, data, Number(value || 1))"
                  >
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
                    <Button icon="pi pi-trash" text severity="danger" @click="removeItem(group.store_id, data.id)" />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>

          <div class="mt-3 flex flex-wrap items-center justify-end gap-6 text-sm text-slate-700">
            <div class="flex items-center gap-2">
              <span class="text-slate-500">Selected:</span>
              <span class="font-semibold">{{ selectedCountForStore(group.store_id) }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-slate-500">Checkout Total:</span>
              <span class="font-semibold">PHP {{ selectedTotalForStore(group.store_id).toFixed(2) }}</span>
            </div>
          </div>
          <div class="flex items-center justify-end mt-5 gap-2">
              <Button
                label="Delete"
                icon="pi pi-trash"
                severity="danger"
                size="small"
                text
                :disabled="!selectedIdsForStore(group.store_id).length"
                @click="deleteSelected(group.store_id)"
              />
              <Button
                label="Check Out"
                severity="warn"
                size="small"

                :disabled="!selectedIdsForStore(group.store_id).length"
                @click="goCheckout(group.store_id)"
              />
            </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import { confirmAlert, showAlert } from '@/utils/swal'
import Message from 'primevue/message'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const router = useRouter()
const loading = ref(false)

type CartGroup = {
  store_id: number
  store_name: string
  items: any[]
  summary: any
}

const cartGroups = ref<CartGroup[]>([])
const selectedItemIdsByStore = reactive<Record<number, number[]>>({})

async function loadCartGroups() {
  loading.value = true
  try {
    const cartsRes = await ecommerceService.getCarts()
    const carts = cartsRes.data?.data || []

    const details = await Promise.all(
      carts.map(async (c: any) => {
        const storeId = Number(c.store_id)
        const cartRes = await ecommerceService.getCart({ store_id: storeId })
        const data = cartRes.data?.data || {}
        return {
          store_id: storeId,
          store_name: String(c.store_name || data?.store_name || 'Store'),
          items: Array.isArray(data.items) ? data.items : [],
          summary: data.summary || {},
        } as CartGroup
      }),
    )

    cartGroups.value = details.filter((g) => g.items.length)

    for (const group of cartGroups.value) {
      const storeId = group.store_id
      const validIds = new Set(group.items.map((item) => Number(item.id)))
      const current = selectedItemIdsByStore[storeId] || []
      selectedItemIdsByStore[storeId] = current.filter((id) => validIds.has(Number(id)))
    }
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load cart' })
  } finally {
    loading.value = false
  }
}

const selectedIdsForStore = (storeId: number) => selectedItemIdsByStore[storeId] || []

const selectedCountForStore = (storeId: number) => {
  const ids = new Set(selectedIdsForStore(storeId))
  const group = cartGroups.value.find((g) => g.store_id === storeId)
  if (!group) return 0
  return group.items.filter((item) => ids.has(Number(item.id))).reduce((sum, item) => sum + Number(item.quantity || 0), 0)
}

const selectedTotalForStore = (storeId: number) => {
  const ids = new Set(selectedIdsForStore(storeId))
  const group = cartGroups.value.find((g) => g.store_id === storeId)
  if (!group) return 0
  return group.items.filter((item) => ids.has(Number(item.id))).reduce((sum, item) => sum + Number(item.line_total || 0), 0)
}

function toggleSelected(storeId: number, itemId: number, checked: boolean) {
  const current = selectedItemIdsByStore[storeId] || []
  if (checked) {
    if (!current.includes(itemId)) current.push(itemId)
    selectedItemIdsByStore[storeId] = current
    return
  }
  selectedItemIdsByStore[storeId] = current.filter((id) => id !== itemId)
}

async function updateQty(storeId: number, item: any, quantity: number) {
  if (!quantity || quantity < 1) return
  try {
    const response = await ecommerceService.updateCartItem(item.id, { quantity })
    const data = response.data?.data || {}
    const group = cartGroups.value.find((g) => g.store_id === storeId)
    if (group) {
      group.items = data.items || group.items
      group.summary = data.summary || group.summary
    }
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
  } catch (error: any) {
    if (error?.response?.status === 404) {
      showAlert({ severity: 'warn', summary: 'Cart updated', detail: 'Your cart changed. Refreshing items...' })
      await loadCartGroups()
      return
    }
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update quantity' })
  }
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

async function removeItem(storeId: number, itemId: number) {
  try {
    await ecommerceService.removeCartItem(itemId)
    selectedItemIdsByStore[storeId] = selectedIdsForStore(storeId).filter((id) => id !== itemId)
    await loadCartGroups()
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to remove item' })
  }
}

async function deleteSelected(storeId: number) {
  const ids = selectedIdsForStore(storeId)
  if (!ids.length) return
  const confirmed = await confirmAlert({
    title: 'Delete selected items?',
    text: `Delete ${ids.length} selected item(s) from cart?`,
    confirmText: 'Delete',
  })
  if (!confirmed) return

  try {
    await Promise.all(ids.map((itemId) => ecommerceService.removeCartItem(itemId)))
    selectedItemIdsByStore[storeId] = []
    showAlert({ severity: 'success', summary: 'Deleted', detail: 'Selected items removed from cart.' })
    await loadCartGroups()
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to delete selected items' })
  }
}

function goCheckout(storeId: number) {
  const ids = selectedIdsForStore(storeId)
  if (!ids.length) {
    showAlert({ severity: 'warn', summary: 'No items selected', detail: 'Please select at least one item to checkout.' })
    return
  }
  router.push({ name: 'ecommerce.checkout', query: { store_id: String(storeId), item_ids: ids.join(',') } })
}

onMounted(loadCartGroups)
</script>
