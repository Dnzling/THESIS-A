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
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="min-w-0 lg:col-span-2">
              <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                  <p class="text-base font-semibold text-slate-900">{{ group.store_name }}</p>
                  <p class="text-xs text-slate-500">{{ group.items.length }} product{{ group.items.length === 1 ? '' : 's' }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <Button
                    :label="allSelectedForStore(group.store_id) ? 'Clear Selection' : 'Select All'"
                    :icon="allSelectedForStore(group.store_id) ? 'pi pi-times' : 'pi pi-check-square'"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="toggleSelectAll(group.store_id)"
                  />
                  <Button
                    label="Delete"
                    icon="pi pi-trash"
                    severity="danger"
                    size="small"
                    text
                    :disabled="!selectedIdsForStore(group.store_id).length"
                    @click="deleteSelected(group.store_id)"
                  />
                </div>
              </div>

              <div class="mt-3 md:hidden">
                <div v-for="item in group.items" :key="item.id" class="border-b border-slate-200 px-1 py-4 last:border-b-0">
                  <div class="flex items-start gap-3">
                    <Checkbox :modelValue="selectedIdsForStore(group.store_id).includes(item.id)" binary @update:modelValue="(checked) => toggleSelected(group.store_id, item.id, checked)" />
                    <img :src="item.image || '/F.svg'" :alt="item.product_name" class="h-16 w-16 shrink-0 rounded-lg border border-slate-200 object-cover" />
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-semibold text-slate-900">{{ item.product_name }}</p>
                      <p class="text-xs text-slate-500">{{ item.variation_name || item.variation_sku || 'Standard' }}</p>
                      <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatMoney(item.unit_price) }}</p>
                      <p v-if="item.customization_request" class="mt-1 line-clamp-2 text-xs text-orange-700">Customization: {{ item.customization_request }}</p>
                    </div>
                  </div>
                  <Button v-if="item.is_customizable" :label="item.customization_request ? 'Edit customization' : 'Customize this item'" icon="pi pi-palette" size="small" text severity="warn" class="mt-2" @click="openCustomization(item)" />
                  <div class="mt-3 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2">
                    <InputNumber :modelValue="Number(item.quantity)" showButtons buttonLayout="horizontal" :step="1" :min="1" class="!w-28 max-w-full" inputClass="!w-10 min-w-0 text-center" @update:modelValue="(value) => updateQty(group.store_id, item, Number(value || 1))">
                      <template #incrementbuttonicon><span class="pi pi-plus" /></template>
                      <template #decrementbuttonicon><span class="pi pi-minus" /></template>
                    </InputNumber>
                    <div class="flex shrink-0 items-center gap-1">
                      <Button :icon="item.is_favorite ? 'pi pi-heart-fill' : 'pi pi-heart'" text size="small" :severity="item.is_favorite ? 'danger' : 'secondary'" aria-label="Toggle favorite" @click="toggleFavorite(item)" />
                      <Button icon="pi pi-trash" text size="small" severity="danger" aria-label="Remove item" @click="removeItem(group.store_id, item.id)" />
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-3 hidden overflow-x-auto md:block">
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
                            <p v-if="data.customization_request" class="mt-1 line-clamp-1 text-xs text-orange-700">Customization: {{ data.customization_request }}</p>
                          </div>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-slate-900">
                          {{ formatMoney(data.unit_price) }}
                          <span class="font-normal text-slate-500">/ {{ formatUom(data.unit_of_measurement) }}</span>
                        </p>
                      </div>
                      <Button v-if="data.is_customizable" :label="data.customization_request ? 'Edit customization' : 'Customize'" icon="pi pi-palette" size="small" text severity="warn" class="mt-2" @click="openCustomization(data)" />
                    </template>
                  </Column>

                  <Column field="quantity" header="Quantity">
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
                        <template #incrementbuttonicon><span class="pi pi-plus" /></template>
                        <template #decrementbuttonicon><span class="pi pi-minus" /></template>
                      </InputNumber>
                    </template>
                  </Column>

                  <Column header="" style="width: 110px">
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
            </section>

            <aside class="lg:col-span-1">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 lg:sticky lg:top-24">
                <h2 class="text-base font-semibold text-slate-900">Order Summary</h2>
                <p class="mt-1 text-xs text-slate-500">{{ group.store_name }}</p>

                <div class="mt-5 space-y-3 text-sm">
                  <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-600">Subtotal ({{ selectedCountForStore(group.store_id) }} item{{ selectedCountForStore(group.store_id) === 1 ? '' : 's' }})</span>
                    <span class="font-medium text-slate-900">{{ formatMoney(selectedTotalForStore(group.store_id)) }}</span>
                  </div>
                </div>

                <Divider />

                <div class="flex items-end justify-between gap-3">
                  <span class="font-semibold text-slate-900">Selected subtotal</span>
                  <span class="text-xl font-bold text-slate-900">{{ formatMoney(selectedTotalForStore(group.store_id)) }}</span>
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">
                  Delivery options and any fee are confirmed at checkout.
                </p>

                <Button
                  label="Proceed to Checkout"
                  icon="pi pi-arrow-right"
                  iconPos="right"
                  severity="warn"
                  class="mt-5"
                  fluid
                  :loading="checkingOutStoreId === group.store_id"
                  :disabled="!selectedIdsForStore(group.store_id).length"
                  @click="goCheckout(group.store_id)"
                />
              </div>
            </aside>
          </div>
        </template>
      </Card>
    </div>

    <Dialog v-model:visible="customizationDialogVisible" modal :closable="false" :closeOnEscape="false" :dismissableMask="false" class="mx-4 w-full max-w-xl" :breakpoints="{ '640px': 'calc(100vw - 2rem)' }">
      <template #header>
        <div class="flex min-w-0 items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600"><i class="pi pi-palette" /></div>
          <div class="min-w-0"><p class="font-semibold text-slate-900">Customize your item</p><p class="truncate text-xs font-normal text-slate-500">{{ activeCustomizationItem?.product_name }}</p></div>
        </div>
      </template>
      <div class="space-y-4">
        <div class="rounded-xl border border-orange-100 bg-orange-50/70 p-4 text-sm text-orange-900">
          <p class="font-medium">Tell the store what you would like customized.</p>
          <p class="mt-1 text-xs leading-5 text-orange-800">Your request will be reviewed by the store before it is confirmed.</p>
        </div>
        <div>
          <label for="cart-customization-request" class="mb-1.5 block text-sm font-medium text-slate-700">Customization instructions</label>
          <Textarea id="cart-customization-request" v-model="customizationDraft" rows="5" maxlength="1000" fluid autoResize placeholder="Describe the color, finish, dimensions, or other changes you are requesting..." />
          <p class="mt-1 text-right text-xs text-slate-400">{{ customizationDraft.length }}/1000</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="savingCustomization" @click="cancelCustomization" />
        <Button label="Save instructions" icon="pi pi-check" severity="warn" :loading="savingCustomization" @click="saveCustomization" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import ecommerceService from '@/services/ecommerce.service'
import { confirmAlert, showAlert } from '@/utils/swal'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const router = useRouter()
const loading = ref(false)
const checkingOutStoreId = ref<number | null>(null)
const customizationDialogVisible = ref(false)
const activeCustomizationItem = ref<any | null>(null)
const customizationDraft = ref('')
const originalCustomization = ref('')
const savingCustomization = ref(false)

type CartGroup = {
  store_id: number
  store_name: string
  items: any[]
  summary: any
}

const cartGroups = ref<CartGroup[]>([])
const selectedItemIdsByStore = reactive<Record<number, number[]>>({})
const savedQuantities = reactive<Record<number, number>>({})
const dirtyItemIds = reactive(new Set<number>())
const quantityVersions = new Map<number, number>()
const quantitySaveTimers = new Map<number, ReturnType<typeof setTimeout>>()
const activeQuantitySaves = new Map<number, { version: number; promise: Promise<boolean> }>()

function openCustomization(item: any) {
  activeCustomizationItem.value = item
  customizationDraft.value = item.customization_request || ''
  originalCustomization.value = customizationDraft.value
  customizationDialogVisible.value = true
}

async function cancelCustomization() {
  if (savingCustomization.value) return
  if (customizationDraft.value.trim() !== originalCustomization.value.trim()) {
    const confirmed = await confirmAlert({
      title: 'Discard customization changes?',
      text: 'Your unsaved customization instructions will be lost.',
      confirmText: 'Discard changes',
      cancelText: 'Keep editing',
    })
    if (!confirmed) return
  }
  customizationDialogVisible.value = false
  activeCustomizationItem.value = null
}

async function saveCustomization() {
  const item = activeCustomizationItem.value
  if (!item) return
  savingCustomization.value = true
  try {
    const value = customizationDraft.value.trim()
    const response = await ecommerceService.updateCartItem(Number(item.id), { customization_request: value || null })
    const updatedItem = response.data?.data?.items?.find((candidate: any) => Number(candidate.id) === Number(item.id))
    if (updatedItem) Object.assign(item, updatedItem)
    else item.customization_request = value || null
    customizationDialogVisible.value = false
    activeCustomizationItem.value = null
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
    showAlert({ severity: 'success', summary: value ? 'Customization saved' : 'Customization removed', detail: value ? 'Your instructions will be sent with the order.' : 'The customization instructions were cleared.' })
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Unable to save', detail: error?.response?.data?.errors?.customization_request?.[0] || error?.response?.data?.message || 'Please try again.' })
  } finally {
    savingCustomization.value = false
  }
}

const formatMoney = (value: number | string | null | undefined) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
}).format(Number(value || 0))

const formatUom = (value: unknown) => String(value || 'unit').replaceAll('_', ' ')

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
      for (const item of group.items) {
        savedQuantities[Number(item.id)] = Number(item.quantity || 1)
      }
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

const allSelectedForStore = (storeId: number) => {
  const group = cartGroups.value.find((candidate) => candidate.store_id === storeId)
  if (!group?.items.length) return false

  const selectedIds = new Set(selectedIdsForStore(storeId).map(Number))
  return group.items.every((item) => selectedIds.has(Number(item.id)))
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

function toggleSelectAll(storeId: number) {
  const group = cartGroups.value.find((candidate) => candidate.store_id === storeId)
  if (!group) return

  selectedItemIdsByStore[storeId] = allSelectedForStore(storeId)
    ? []
    : group.items.map((item) => Number(item.id))
}

function recalculateGroup(group: CartGroup) {
  for (const cartItem of group.items) {
    const lineSubtotal = Number(cartItem.unit_price || 0) * Number(cartItem.quantity || 0)
    cartItem.line_subtotal = lineSubtotal
    cartItem.line_tax = lineSubtotal > 0 ? lineSubtotal - (lineSubtotal / 1.12) : 0
    cartItem.line_total = lineSubtotal
  }

  group.summary = {
    ...group.summary,
    subtotal: group.items.reduce((sum, cartItem) => sum + Number(cartItem.line_subtotal || 0), 0),
    tax_amount: group.items.reduce((sum, cartItem) => sum + Number(cartItem.line_tax || 0), 0),
    total_amount: group.items.reduce((sum, cartItem) => sum + Number(cartItem.line_total || 0), 0),
    items_count: group.items.reduce((sum, cartItem) => sum + Number(cartItem.quantity || 0), 0),
  }
}

async function persistQuantity(storeId: number, item: any, version: number): Promise<boolean> {
  const itemId = Number(item.id)
  const quantity = Number(item.quantity || 1)
  quantitySaveTimers.delete(itemId)

  try {
    const response = await ecommerceService.updateCartItem(itemId, { quantity })
    if (quantityVersions.get(itemId) !== version) return true

    const data = response.data?.data || {}
    const group = cartGroups.value.find((g) => g.store_id === storeId)
    if (group) {
      const serverItem = Array.isArray(data.items)
        ? data.items.find((candidate: any) => Number(candidate.id) === itemId)
        : null
      if (serverItem) Object.assign(item, serverItem)
      recalculateGroup(group)
    }
    savedQuantities[itemId] = quantity
    dirtyItemIds.delete(itemId)
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
    return true
  } catch (error: any) {
    if (quantityVersions.get(itemId) !== version) return false

    if (error?.response?.status === 404) {
      showAlert({ severity: 'warn', summary: 'Cart updated', detail: 'Your cart changed. Refreshing items...' })
      await loadCartGroups()
      return false
    }

    item.quantity = savedQuantities[itemId] || 1
    const group = cartGroups.value.find((g) => g.store_id === storeId)
    if (group) recalculateGroup(group)
    dirtyItemIds.delete(itemId)
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update quantity' })
    return false
  }
}

function saveQuantityNow(storeId: number, item: any, version: number): Promise<boolean> {
  const itemId = Number(item.id)
  const activeSave = activeQuantitySaves.get(itemId)
  if (activeSave?.version === version) return activeSave.promise

  const promise = persistQuantity(storeId, item, version)
  activeQuantitySaves.set(itemId, { version, promise })
  void promise.finally(() => {
    if (activeQuantitySaves.get(itemId)?.promise === promise) {
      activeQuantitySaves.delete(itemId)
    }
  })

  return promise
}

function updateQty(storeId: number, item: any, quantity: number) {
  const normalizedQuantity = Math.max(1, Math.trunc(Number(quantity || 1)))
  const itemId = Number(item.id)
  item.quantity = normalizedQuantity

  const group = cartGroups.value.find((candidate) => candidate.store_id === storeId)
  if (group) recalculateGroup(group)

  const version = (quantityVersions.get(itemId) || 0) + 1
  quantityVersions.set(itemId, version)
  dirtyItemIds.add(itemId)

  const existingTimer = quantitySaveTimers.get(itemId)
  if (existingTimer) clearTimeout(existingTimer)

  quantitySaveTimers.set(itemId, setTimeout(() => {
    void saveQuantityNow(storeId, item, version)
  }, 3000))
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
  const pendingTimer = quantitySaveTimers.get(itemId)
  if (pendingTimer) clearTimeout(pendingTimer)
  quantitySaveTimers.delete(itemId)
  dirtyItemIds.delete(itemId)
  try {
    const activeSave = activeQuantitySaves.get(itemId)
    if (activeSave) await activeSave.promise
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
    for (const itemId of ids) {
      const pendingTimer = quantitySaveTimers.get(itemId)
      if (pendingTimer) clearTimeout(pendingTimer)
      quantitySaveTimers.delete(itemId)
      dirtyItemIds.delete(itemId)
    }
    await Promise.all(ids.map((itemId) => activeQuantitySaves.get(itemId)?.promise ?? Promise.resolve(true)))
    await Promise.all(ids.map((itemId) => ecommerceService.removeCartItem(itemId)))
    selectedItemIdsByStore[storeId] = []
    showAlert({ severity: 'success', summary: 'Deleted', detail: 'Selected items removed from cart.' })
    await loadCartGroups()
    window.dispatchEvent(new Event('ecommerce-cart-updated'))
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to delete selected items' })
  }
}

async function goCheckout(storeId: number) {
  const ids = selectedIdsForStore(storeId)
  if (!ids.length) {
    showAlert({ severity: 'warn', summary: 'No items selected', detail: 'Please select at least one item to checkout.' })
    return
  }

  checkingOutStoreId.value = storeId
  const group = cartGroups.value.find((candidate) => candidate.store_id === storeId)
  const dirtyItems = group?.items.filter((item) => dirtyItemIds.has(Number(item.id))) || []
  const saved = await Promise.all(dirtyItems.map((item) => {
    const itemId = Number(item.id)
    const pendingTimer = quantitySaveTimers.get(itemId)
    if (pendingTimer) clearTimeout(pendingTimer)
    return saveQuantityNow(storeId, item, quantityVersions.get(itemId) || 0)
  }))
  if (saved.some((success) => !success)) {
    checkingOutStoreId.value = null
    return
  }

  router.push({ name: 'ecommerce.checkout', query: { store_id: String(storeId), item_ids: ids.join(',') } })
  checkingOutStoreId.value = null
}

onMounted(loadCartGroups)
onBeforeUnmount(() => {
  for (const group of cartGroups.value) {
    for (const item of group.items) {
      const itemId = Number(item.id)
      if (!dirtyItemIds.has(itemId)) continue
      const pendingTimer = quantitySaveTimers.get(itemId)
      if (pendingTimer) clearTimeout(pendingTimer)
      void saveQuantityNow(group.store_id, item, quantityVersions.get(itemId) || 0)
    }
  }
})
</script>
