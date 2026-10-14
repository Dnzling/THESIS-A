<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Branches</h1>
      </div>
      <Button label="Add Branch" icon="pi pi-plus" size="small" severity="warn" @click="openCreateDialog" />
    </div>

    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #content>
        <div class="flex flex-col gap-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <span class="text-xs font-semibold text-slate-500 uppercase">Search</span>
            <InputText v-model="search" placeholder="Search branch name or code" size="small" @input="loadBranches" />
          </div>

          <div v-if="loading" class="space-y-3 py-2" aria-label="Loading branches">
            <div class="grid grid-cols-4 gap-3">
              <Skeleton v-for="column in 4" :key="`branch-heading-${column}`" height="1.25rem" />
            </div>
            <div v-for="row in 5" :key="`branch-row-${row}`" class="grid grid-cols-4 gap-3">
              <Skeleton v-for="column in 4" :key="`branch-${row}-${column}`" height="2.5rem" />
            </div>
          </div>
          <DataTable v-else
            :value="filteredBranches"
            :paginator="true"
            :rows="10"
            size="small"
            rowHover
            dataKey="id"
            class="p-datatable-sm text-sm"
          >
            <Column field="name" header="Branch" sortable>
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-semibold text-slate-900">{{ data.name || data.branch_name || 'Branch' }}</span>
                  <span class="text-xs text-slate-500">{{ data.branch_code || data.code || '—' }}</span>
                </div>
              </template>
            </Column>
            <Column field="city" header="City" sortable />
            <Column field="address" header="Address" />
            <Column header="Action" style="width: 110px">
              <template #body="{ data }">
                <Button icon="pi pi-eye" text size="small"  @click="viewBranch(data.id)" />
              </template>
            </Column>
            <template #empty>
              <div class="flex flex-col items-center justify-center gap-2 py-10 text-center">
                <i class="pi pi-building text-3xl text-slate-300" />
                <p class="font-semibold text-slate-700">{{ search ? 'No branches match this search' : 'No branches registered yet' }}</p>
                <p class="max-w-md text-sm text-slate-500">{{ search ? 'Try another branch name or code.' : 'Add a branch to manage this store’s locations.' }}</p>
                <Button v-if="!search" label="Add Branch" icon="pi pi-plus" size="small" severity="warn" class="mt-1" @click="openCreateDialog" />
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showCreateDialog" modal header="Add Branch" class="w-full max-w-4xl">
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Branch Name</label>
            <InputText v-model="form.name" placeholder="Main Branch" class="w-full" :class="{ 'p-invalid': validationErrors.name }" />
            <small v-if="validationErrors.name" class="text-xs text-red-600">{{ validationErrors.name }}</small>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Branch Type</label>
            <Dropdown v-model="form.branch_type" :options="branchTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-600">Address</label>
          <InputText v-model="form.address" placeholder="Street, building, area" class="w-full" :class="{ 'p-invalid': validationErrors.address }" />
          <small v-if="validationErrors.address" class="text-xs text-red-600">{{ validationErrors.address }}</small>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Province</label>
            <Select v-if="form.branch_type === 'warehouse'" v-model="form.provinceId"
              :options="provinceOptions" optionLabel="label" optionValue="value"
              placeholder="Select province" filter fluid class="w-full"
              :class="{ 'p-invalid': validationErrors.province }"
              @change="onProvinceChange" />
            <InputText v-else v-model="form.province" class="w-full" readonly />
            <small v-if="validationErrors.province" class="text-xs text-red-600">{{ validationErrors.province }}</small>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">City</label>
            <Select
              v-model="form.cityId"
              :options="cityOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select city"
              class="w-full"
              fluid
              :loading="isCitiesLoading"
              :disabled="!form.provinceId || isCitiesLoading"
              @change="onCityChange"
              :class="{ 'p-invalid': validationErrors.city }"
            />
            <small v-if="validationErrors.city" class="text-xs text-red-600">{{ validationErrors.city }}</small>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-600">Barangay</label>
            <Select
              v-model="form.barangayCode"
              :options="barangayOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select barangay"
              class="w-full"
              fluid
              :loading="isBarangaysLoading"
              :disabled="!form.cityId || isBarangaysLoading"
              @change="resolveBarangayName"
              :class="{ 'p-invalid': validationErrors.barangay }"
            />
            <small v-if="validationErrors.barangay" class="text-xs text-red-600">{{ validationErrors.barangay }}</small>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <InputSwitch v-model="form.is_main_branch" />
          <span class="text-sm text-slate-700">Main Branch</span>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" text @click="showCreateDialog = false" />
          <Button label="Save Branch" :loading="saving" @click="createBranch" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputSwitch from 'primevue/inputswitch'
import Select from 'primevue/select'
import inventoryService from '@/services/inventory.service'
import axiosClient from '@/axios'
import ecommerceService from '@/services/ecommerce.service'
import { showResponseDialog } from '@/utils/responseDialogBus'

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const branches = ref<any[]>([])
const registeredStoreLocation = ref<any>(null)
const search = ref('')
const showCreateDialog = ref(false)
const validationErrors = ref<Record<string, string>>({})
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const isCitiesLoading = ref(false)
const isBarangaysLoading = ref(false)

const branchTypeOptions = [
  { label: 'Storefront', value: 'storefront' },
  { label: 'Wholesale', value: 'wholesale' },
  { label: 'Warehouse', value: 'warehouse' },
]

const emptyForm = () => ({
  name: '',
  branch_type: 'storefront',
  address: '',
  provinceId: '',
  city: '',
  province: 'Cavite',
  barangay: '',
  cityId: '',
  barangayCode: '',
  is_main_branch: false,
})

const form = ref(emptyForm())

const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name || c.city_name, value: String(c.city_id || c.code || c.id || '') })))
const provinceOptions = computed(() => provinces.value.map((p: any) => ({
  label: p.name || p.province_name,
  value: String(p.province_id || p.code || p.id || ''),
})))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name || b.barangay_name, value: String(b.code || b.id || '') })))

const normalize = (value: unknown) => String(value ?? '').trim().toLowerCase()

const applyDefaultProvince = () => {
  const cavite = provinces.value.find((p: any) => normalize(p.name || p.province_name) === 'cavite')
  if (cavite) {
    form.value.provinceId = String(cavite.province_id || cavite.code || cavite.id || '')
    form.value.province = cavite.name || cavite.province_name
  }
}

const fetchProvinces = async () => {
  const response = await ecommerceService.getProvinces()
  const data = response.data?.data ?? response.data ?? []
  provinces.value = Array.isArray(data) ? data : []
  if (form.value.branch_type !== 'warehouse') applyDefaultProvince()
}

const fetchCities = async (provinceId: string) => {
  if (!provinceId) {
    cities.value = []
    return
  }
  try {
    isCitiesLoading.value = true
    const response = await ecommerceService.getCities(provinceId)
    const data = response.data?.data ?? response.data ?? []
    cities.value = Array.isArray(data) ? data : []
  } finally {
    isCitiesLoading.value = false
  }
}

const fetchBarangays = async (cityId: string) => {
  if (!cityId) {
    barangays.value = []
    return
  }
  try {
    isBarangaysLoading.value = true
    const response = await ecommerceService.getBarangays(cityId)
    const data = response.data?.data ?? response.data ?? []
    barangays.value = Array.isArray(data) ? data : []
  } finally {
    isBarangaysLoading.value = false
  }
}

const resolveSelectedCityName = () => {
  const match = cities.value.find((c: any) => String(c.city_id || c.code || c.id || '') === String(form.value.cityId))
  return match?.name || match?.city_name || ''
}

const resolveSelectedBarangayName = () => {
  const match = barangays.value.find((b: any) => String(b.code || b.id || '') === String(form.value.barangayCode))
  return match?.name || match?.barangay_name || ''
}

const onCityChange = async () => {
  form.value.barangayCode = ''
  form.value.barangay = ''
  await fetchBarangays(String(form.value.cityId || ''))
}

const onProvinceChange = () => {
  const selected = provinces.value.find((p: any) =>
    String(p.province_id || p.code || p.id || '') === String(form.value.provinceId))
  form.value.province = selected?.name || selected?.province_name || ''
  form.value.cityId = ''
  form.value.city = ''
  form.value.barangayCode = ''
  form.value.barangay = ''
  cities.value = []
  barangays.value = []
}

watch(() => form.value.branch_type, (type, previous) => {
  if (type === previous) return
  form.value.provinceId = ''
  form.value.province = ''
  form.value.cityId = ''
  form.value.city = ''
  form.value.barangayCode = ''
  form.value.barangay = ''
  cities.value = []
  barangays.value = []
  if (type !== 'warehouse') applyDefaultProvince()
})

watch(
  () => form.value.provinceId,
  async (provinceId) => {
    if (!provinceId) {
      cities.value = []
      return
    }
    await fetchCities(String(provinceId))
  },
)

watch(
  () => form.value.cityId,
  async (cityId) => {
    if (!cityId) {
      barangays.value = []
      return
    }
    await fetchBarangays(String(cityId))
  },
)

const loadBranches = async () => {
  try {
    loading.value = true
    const res = await inventoryService.getBranches()
    const payload = res?.data ?? res ?? {}
    branches.value = payload.data ?? payload ?? []
    registeredStoreLocation.value = payload.store_location ?? null
  } finally {
    loading.value = false
  }
}

const filteredBranches = computed(() => {
  if (!search.value) return branches.value
  const term = search.value.toLowerCase()
  return branches.value.filter((b: any) =>
    [b.name, b.branch_name, b.branch_code, b.code]
      .filter(Boolean)
      .some((v: string) => v.toLowerCase().includes(term))
  )
})

const viewBranch = (id: number) => {
  router.push(`/store/branches/${id}`)
}

const openCreateDialog = async () => {
  form.value = emptyForm()
  validationErrors.value = {}
  showCreateDialog.value = true
  if (!provinces.value.length) {
    await fetchProvinces()
  } else {
    applyDefaultProvince()
  }
  if (form.value.provinceId) {
    await fetchCities(String(form.value.provinceId))
  }

  const storeLocation = registeredStoreLocation.value
  if (storeLocation && normalize(storeLocation.province) === 'cavite') {
    form.value.province = storeLocation.province || form.value.province
    const matchingCity = cities.value.find(
      (city: any) => normalize(city.name || city.city_name) === normalize(storeLocation.city),
    )
    if (matchingCity) {
      form.value.cityId = String(matchingCity.city_id || matchingCity.id || matchingCity.code || '')
      form.value.city = matchingCity.name || matchingCity.city_name || storeLocation.city || ''
      await fetchBarangays(form.value.cityId)

      const matchingBarangay = barangays.value.find(
        (barangay: any) => normalize(barangay.name || barangay.barangay_name) === normalize(storeLocation.barangay),
      )
      if (matchingBarangay) {
        form.value.barangayCode = String(matchingBarangay.code || matchingBarangay.id || '')
        form.value.barangay = matchingBarangay.name || matchingBarangay.barangay_name || storeLocation.barangay || ''
      } else {
        // Keep the registered value even if the external location API is unavailable.
        form.value.barangay = storeLocation.barangay || ''
      }
    }
  }
}

const createBranch = async () => {
  validationErrors.value = {}
  form.value.city = resolveSelectedCityName()
  form.value.barangay = resolveSelectedBarangayName() || form.value.barangay

  const errors: Record<string, string> = {}
  if (!form.value.name.trim()) errors.name = 'Branch name is required.'
  if (!form.value.address.trim()) errors.address = 'Address is required.'
  if (!form.value.provinceId || !form.value.province) errors.province = 'Province is required.'
  if (!form.value.city) errors.city = 'City is required.'
  if (!form.value.barangay) errors.barangay = 'Barangay is required.'

  if (Object.keys(errors).length) {
    validationErrors.value = errors
    return
  }

  try {
    saving.value = true
    await axiosClient.post('/api/branches', {
      name: form.value.name.trim(),
      branch_type: form.value.branch_type,
      address: form.value.address.trim(),
      province: form.value.province,
      city: form.value.city,
      barangay: form.value.barangay,
      is_main_branch: form.value.is_main_branch,
    })
    showCreateDialog.value = false
    await loadBranches()
  } catch (error: any) {
    showResponseDialog({
      severity: 'error',
      title: 'Branch Save Failed',
      message: error?.response?.data?.message || error?.response?.data?.error || 'Failed to save branch.',
    })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadBranches(), fetchProvinces()])
})
</script>
