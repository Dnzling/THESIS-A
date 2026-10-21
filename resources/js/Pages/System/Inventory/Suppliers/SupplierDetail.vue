<template>
  <div class="space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" severity="secondary" text rounded
        @click="router.push({ name: 'inventory.suppliers' })" />
      <div>
        <h1 class="text-lg font-bold text-gray-800">Supplier Details</h1>
        <p class="mt-1 text-xs text-gray-500">Supplier information and connected products.</p>
      </div>
    </div>

    <div v-if="loadingSupplier" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <Skeleton height="420px" />
      <Skeleton height="420px" class="lg:col-span-2" />
    </div>

    <div v-else-if="supplier" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
      <Card class="lg:col-span-1">
        <template #content>
          <div class="space-y-5">
            <div class="flex flex-col items-center border-b border-gray-100 pb-5 text-center">
              <img v-if="supplier.logo_url" :src="supplier.logo_url" :alt="`${supplier.supplier_name} logo`"
                class="h-28 w-28 rounded-2xl border border-gray-200 object-cover" />
              <div v-else class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                <i class="pi pi-building text-4xl" />
              </div>
              <h2 class="mt-3 text-lg font-bold text-gray-900">{{ supplier.supplier_name }}</h2>
              <p class="text-xs text-gray-500">{{ supplier.supplier_code }}</p>
              <Tag class="mt-2" :value="humanize(supplier.supplier_type)" severity="info" />
              <Button label="Edit" icon="pi pi-pencil" size="small" severity="warn" outlined
                class="mt-4" @click="editSupplier" />
            </div>

            <InfoRow icon="pi pi-user" label="Contact Person" :value="supplier.contact_person" />
            <InfoRow icon="pi pi-phone" label="Phone Number" :value="supplier.phone" />
            <InfoRow icon="pi pi-envelope" label="Email" :value="supplier.email" />
            <InfoRow icon="pi pi-map-marker" label="Address" :value="fullAddress" />
            <InfoRow icon="pi pi-star" label="Rating" :value="formatRating(supplier.rating)" />
            <InfoRow icon="pi pi-circle-fill" label="Status" :value="humanize(supplier.status)" />
          </div>
        </template>
      </Card>

      <Card class="min-w-0 lg:col-span-2">
        <template #title>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-base font-semibold text-gray-800">Connected Products</h2>
              <p class="mt-1 text-xs font-normal text-gray-500">Products supplied by {{ supplier.supplier_name }}.</p>
            </div>
            <IconField class="w-full sm:w-72">
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" class="w-full" size="small" placeholder="Search product or SKU"
                @keyup.enter="applySearch" />
            </IconField>
          </div>
        </template>

        <template #content>
          <DataTable :value="products" :loading="loadingProducts" class="text-sm" rowHover
            responsiveLayout="scroll" paginator lazy :rows="perPage" :totalRecords="totalProducts"
            :first="(currentPage - 1) * perPage" :rowsPerPageOptions="[10, 15, 25, 50]" @page="onPageChange">
            <template #empty>
              <div class="py-12 text-center">
                <i class="pi pi-box mb-3 text-4xl text-gray-300" />
                <p class="text-gray-600">No connected products found</p>
              </div>
            </template>

            <Column field="product_name" header="Product" style="min-width: 210px">
              <template #body="{ data }">
                <p class="font-semibold text-gray-900">{{ data.product_name }}</p>
                <p class="text-xs text-gray-500">SKU: {{ data.sku || '-' }}</p>
              </template>
            </Column>
            <Column header="Category" style="min-width: 150px">
              <template #body="{ data }">
                <p class="text-sm text-gray-700">{{ data.category?.category_name || '-' }}</p>
                <p v-if="data.subcategory?.category_name" class="text-xs text-gray-500">{{ data.subcategory.category_name }}</p>
              </template>
            </Column>
            <Column field="brand" header="Brand" style="min-width: 120px">
              <template #body="{ data }">{{ data.brand || '-' }}</template>
            </Column>
            <Column header="Supplier Price" style="min-width: 130px">
              <template #body="{ data }">{{ formatCurrency(data.pivot?.supplier_price) }}</template>
            </Column>
            <Column header="Preferred" style="width: 105px">
              <template #body="{ data }">
                <Tag :value="data.pivot?.is_preferred_supplier ? 'Yes' : 'No'"
                  :severity="data.pivot?.is_preferred_supplier ? 'success' : 'secondary'" />
              </template>
            </Column>
            <Column header="Action" style="width: 80px">
              <template #body="{ data }">
                <Button icon="pi pi-eye" outlined rounded size="small" v-tooltip="'View Product'"
                  @click="viewProduct(data.id)" />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const InfoRow = defineComponent({
  props: { icon: String, label: String, value: [String, Number] },
  setup(props) {
    return () => h('div', { class: 'flex items-start gap-3' }, [
      h('i', { class: `${props.icon} mt-1 text-gray-400` }),
      h('div', { class: 'min-w-0' }, [
        h('p', { class: 'text-xs text-gray-500' }, props.label),
        h('p', { class: 'break-words text-sm font-medium text-gray-800' }, String(props.value || '-')),
      ]),
    ])
  },
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const supplierId = Number(route.params.id)
const supplier = ref<any>(null)
const products = ref<any[]>([])
const search = ref('')
const loadingSupplier = ref(true)
const loadingProducts = ref(false)
const currentPage = ref(1)
const perPage = ref(10)
const totalProducts = ref(0)

const fullAddress = computed(() => [
  supplier.value?.address,
  supplier.value?.barangay,
  supplier.value?.city,
  supplier.value?.province,
].filter(Boolean).join(', ') || '-')

const loadSupplier = async (includeProfile = false) => {
  if (includeProfile) loadingSupplier.value = true
  loadingProducts.value = true
  try {
    const response = await inventoryService.getSupplier(supplierId, {
      search: search.value || undefined,
      page: currentPage.value,
      per_page: perPage.value,
    })
    const payload = response.data || response
    supplier.value = payload.supplier
    products.value = payload.products?.data || []
    totalProducts.value = Number(payload.products?.total || products.value.length)
    currentPage.value = Number(payload.products?.current_page || currentPage.value)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to Load Supplier',
      detail: error.response?.data?.message || 'Supplier details could not be loaded.', life: 4000 })
  } finally {
    loadingSupplier.value = false
    loadingProducts.value = false
  }
}

const applySearch = () => {
  currentPage.value = 1
  loadSupplier()
}

const onPageChange = (event: any) => {
  currentPage.value = event.page + 1
  perPage.value = event.rows
  loadSupplier()
}

const viewProduct = (id: number) => router.push({ name: 'inventory.products.detail', params: { id } })
const editSupplier = () => router.push({ name: 'inventory.suppliers.edit', params: { id: supplierId } })
const humanize = (value?: string) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '-'
const formatRating = (value: any) => Number.isFinite(Number(value)) ? `${Number(value).toFixed(1)} / 5` : '-'
const formatCurrency = (value: any) => value == null ? '-' : new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value))

onMounted(() => loadSupplier(true))
</script>
