<template>
  <div
    v-if="item"
    class="min-h-screen p-4 md:p-6 space-y-6"
  >
    <!-- Header -->
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <Button
          label="Back to Warehouse Stock"
          icon="pi pi-arrow-left"
          text
          size="small"
          @click="router.visit('/warehouse-operations/stock')"
        />

        <div class="mt-2 flex flex-wrap items-center gap-3">
          <h1 class="text-2xl font-semibold text-slate-900">
            {{ product.product_name }}
          </h1>

          <Badge
            :value="label(item.stock_status)"
            :severity="stockSeverity(item.stock_status)"
          />
        </div>

        <p class="mt-1 text-sm text-slate-500">
          {{ variation?.variation_sku || product.sku }}
          ·
          {{ item.branch?.name }}
        </p>
      </div>

      <Button
        label="Create PR"
        icon="pi pi-plus"
        size="small"
        @click="createPr"
      />
    </div>

    <!-- Stock Summary -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
      <Card
        v-for="card in summary"
        :key="card.label"
        class="border border-slate-200 shadow-sm"
      >
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">
            {{ card.label }}
          </p>

          <p class="mt-1 text-xl font-semibold text-slate-900">
            {{ card.value }}
          </p>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Left Column -->
      <div class="space-y-6 lg:col-span-2">
        <!-- Product Information -->
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base">Product Information</span>
          </template>

          <template #content>
            <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="field in productFields"
                :key="field.label"
              >
                <p class="text-xs uppercase tracking-wide text-slate-500">
                  {{ field.label }}
                </p>

                <p class="mt-1 font-medium text-slate-900">
                  {{ field.value || '—' }}
                </p>
              </div>
            </div>

            <Divider />

            <div>
              <p class="text-xs uppercase tracking-wide text-slate-500">
                Description
              </p>

              <div
                class="mt-2 text-sm leading-6 text-slate-700"
                v-html="product.description || 'No description available.'"
              />
            </div>
          </template>
        </Card>

        <!-- Stock Breakdown -->
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base">Stock Breakdown</span>
          </template>

          <template #content>
            <DataTable
              :value="stockBreakdown"
              size="small"
              stripedRows
            >
              <Column
                field="label"
                header="Stock Metric"
              />

              <Column header="Quantity">
                <template #body="{ data }">
                  <span class="font-semibold">
                    {{ number(data.value) }}
                  </span>
                  {{ product.unit_of_measurement || 'unit' }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Attachments -->
        <Card
          v-if="images.length"
          class="border border-slate-200 shadow-sm"
        >
          <template #title>
            <span class="text-base">Attachments</span>
          </template>

          <template #content>
            <Carousel
              v-if="images.length > 1"
              :value="images"
              :numVisible="2"
              :numScroll="1"
            >
              <template #item="{ data }">
                <button
                  class="mx-2 block aspect-square w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                  @click="preview = data"
                >
                  <img
                    :src="data.url"
                    :alt="data.file_name"
                    class="h-full w-full object-contain p-3"
                  />
                </button>
              </template>
            </Carousel>

            <button
              v-else
              class="mx-auto block aspect-square w-full max-w-sm overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
              @click="preview = images[0]"
            >
              <img
                :src="images[0].url"
                :alt="images[0].file_name"
                class="h-full w-full object-contain p-3"
              />
            </button>
          </template>
        </Card>
      </div>

      <!-- Right Column -->
      <div class="space-y-6">
        <!-- Cost Summary -->
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base">Cost Summary</span>
          </template>

          <template #content>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">Unit Cost</span>
                <strong>{{ money(item.unit_cost) }}</strong>
              </div>

              <div class="flex justify-between">
                <span class="text-slate-500">Quantity on Hand</span>
                <strong>{{ number(item.quantity_on_hand) }}</strong>
              </div>

              <Divider />

              <div class="flex justify-between text-base">
                <span class="font-semibold">Stock Value</span>
                <strong class="text-slate-900">
                  {{ money(item.stock_value) }}
                </strong>
              </div>
            </div>
          </template>
        </Card>

        <!-- Warehouse & Location -->
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base">Warehouse & Location</span>
          </template>

          <template #content>
            <dl class="space-y-4 text-sm">
              <div
                v-for="field in locationFields"
                :key="field.label"
                class="flex justify-between gap-4 border-b border-slate-100 pb-3"
              >
                <dt class="text-slate-500">
                  {{ field.label }}
                </dt>

                <dd class="text-right font-medium text-slate-900">
                  {{ field.value || '—' }}
                </dd>
              </div>
            </dl>
          </template>
        </Card>

        <!-- Replenishment Controls -->
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base">Replenishment Controls</span>
          </template>

          <template #content>
            <dl class="space-y-4 text-sm">
              <div
                v-for="field in reorderFields"
                :key="field.label"
                class="flex justify-between"
              >
                <dt class="text-slate-500">
                  {{ field.label }}
                </dt>

                <dd class="font-semibold text-slate-900">
                  {{ number(field.value) }}
                </dd>
              </div>
            </dl>
          </template>
        </Card>

        <!-- Suppliers -->
        <Card class="border border-slate-200 shadow-sm">
          <template #title>
            <span class="text-base">Suppliers</span>
          </template>

          <template #content>
            <div
              v-if="product.suppliers?.length"
              class="space-y-2"
            >
              <div
                v-for="supplier in product.suppliers"
                :key="supplier.id"
                class="rounded-lg border border-slate-200 p-3"
              >
                <p class="font-medium">
                  {{ supplier.supplier_name || supplier.company_name }}
                </p>

                <small class="text-slate-500">
                  {{
                    supplier.pivot?.is_preferred_supplier
                      ? 'Preferred supplier'
                      : 'Approved supplier'
                  }}
                </small>
              </div>
            </div>

            <p
              v-else
              class="text-sm text-slate-500"
            >
              No linked suppliers.
            </p>
          </template>
        </Card>
      </div>
    </div>

    <!-- Attachment Preview -->
    <Dialog
      v-model:visible="previewVisible"
      modal
      header="Product Attachment"
      :style="{ width: 'min(90vw, 760px)' }"
    >
      <img
        v-if="preview"
        :src="preview.url"
        :alt="preview.file_name"
        class="max-h-[70vh] w-full object-contain"
      />
    </Dialog>
  </div>

  <!-- Loading / Error -->
  <div
    v-else
    class="flex min-h-[60vh] items-center justify-center"
  >
    <ProgressSpinner v-if="loading" />

    <Message
      v-else
      severity="error"
    >
      The warehouse stock record could not be loaded.
    </Message>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import WarehouseService from '@/services/warehouse.service'

const page = usePage()

const loading = ref(true)
const item = ref<any>(null)
const preview = ref<any>(null)

const id = computed(() => {
  return String(page.url).match(/stock\/(\d+)/)?.[1] || ''
})

const product = computed(() => item.value?.product || {})

const variation = computed(() => item.value?.variation || null)

const previewVisible = computed({
  get: () => Boolean(preview.value),
  set: (value) => {
    if (!value) {
      preview.value = null
    }
  },
})

const label = (value: any) => {
  return String(value || '—')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const number = (value: any) => {
  return Number(value || 0).toLocaleString()
}

const money = (value: any) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(Number(value || 0))
}

const stockSeverity = (value: string) => {
  if (value === 'out_of_stock') {
    return 'danger'
  }

  if (value === 'low_stock') {
    return 'warn'
  }

  if (value === 'overstock') {
    return 'info'
  }

  return 'success'
}

const summary = computed(() => [
  {
    label: 'On Hand',
    value: number(item.value.quantity_on_hand),
  },
  {
    label: 'Available',
    value: number(item.value.quantity_available),
  },
  {
    label: 'Reserved',
    value: number(item.value.quantity_reserved),
  },
  {
    label: 'Incoming',
    value: number(item.value.quantity_incoming),
  },
  {
    label: 'Damaged',
    value: number(item.value.quantity_damaged),
  },
])

const productFields = computed(() => [
  {
    label: 'SKU',
    value: variation.value?.variation_sku || product.value.sku,
  },
  {
    label: 'Product Type',
    value: label(product.value.product_type),
  },
  {
    label: 'Category',
    value: product.value.category?.category_name,
  },
  {
    label: 'Variation',
    value: variation.value?.variation_name || 'Standard',
  },
  {
    label: 'Unit of Measurement',
    value: product.value.unit_of_measurement,
  },
  {
    label: 'Brand',
    value: product.value.brand,
  },
  {
    label: 'Dimensions',
    value: [
      product.value.length_cm,
      product.value.width_cm,
      product.value.height_cm,
    ].some(Boolean)
      ? `${product.value.length_cm || 0} × ${product.value.width_cm || 0} × ${
          product.value.height_cm || 0
        } cm`
      : '—',
  },
  {
    label: 'Weight',
    value: product.value.weight_kg
      ? `${product.value.weight_kg} kg`
      : '—',
  },
  {
    label: 'Active',
    value: product.value.is_active ? 'Yes' : 'No',
  },
])

const stockBreakdown = computed(() => [
  {
    label: 'Quantity on Hand',
    value: item.value.quantity_on_hand,
  },
  {
    label: 'Available',
    value: item.value.quantity_available,
  },
  {
    label: 'Reserved',
    value: item.value.quantity_reserved,
  },
  {
    label: 'Incoming',
    value: item.value.quantity_incoming,
  },
  {
    label: 'Damaged',
    value: item.value.quantity_damaged,
  },
])

const locationFields = computed(() => [
  {
    label: 'Warehouse Branch',
    value: item.value.branch?.name,
  },
  {
    label: 'Warehouse',
    value: item.value.warehouse?.name || 'Unassigned',
  },
  {
    label: 'Section',
    value: item.value.warehouse_section,
  },
  {
    label: 'Aisle',
    value: item.value.aisle,
  },
  {
    label: 'Rack',
    value: item.value.rack,
  },
  {
    label: 'Shelf',
    value: item.value.shelf,
  },
  {
    label: 'Bin',
    value: item.value.bin_code,
  },
  {
    label: 'Last Counted By',
    value: item.value.last_counted_by?.user?.full_name,
  },
])

const reorderFields = computed(() => [
  {
    label: 'Reorder Point',
    value: item.value.reorder_point,
  },
  {
    label: 'Reorder Quantity',
    value: item.value.reorder_quantity,
  },
  {
    label: 'Safety Stock',
    value: item.value.safety_stock,
  },
  {
    label: 'Maximum Stock',
    value: item.value.maximum_stock,
  },
])

const images = computed(() =>
  (product.value.assets || []).filter((asset: any) =>
    ['Image_Main', 'Image_Gallery', 'Image_360'].includes(asset.asset_type),
  ),
)

const createPr = () => {
  router.visit(
    `/warehouse-operations/purchase-requisitions/create?branch_inventory_id=${item.value.id}`,
  )
}

onMounted(async () => {
  try {
    item.value = await WarehouseService.stockItem(id.value)
  } finally {
    loading.value = false
  }
})
</script>