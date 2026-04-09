<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Add Supplier to Store</h2>
        <p class="text-sm text-gray-500 mt-1">Browse verified suppliers first, or invite a new supplier if not listed.</p>
      </div>
    </div>

    <Card>

      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="rounded-xl border p-4 cursor-pointer" :class="mode === 'existing' ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200'">
              <div class="flex items-center gap-2">
                <RadioButton v-model="mode" inputId="mode-existing" value="existing" />
                <span class="font-semibold text-slate-800">Verified Supplier List</span>
              </div>
              <p class="text-xs text-slate-600 mt-2">View verified suppliers and open details before linking.</p>
            </label>

            <label class="rounded-xl border p-4 cursor-pointer" :class="mode === 'invite' ? 'border-blue-400 bg-blue-50' : 'border-slate-200'">
              <div class="flex items-center gap-2">
                <RadioButton v-model="mode" inputId="mode-invite" value="invite" />
                <span class="font-semibold text-slate-800">Invite New Supplier</span>
              </div>
              <p class="text-xs text-slate-600 mt-2">Use this if the supplier is not yet registered in the platform.</p>
            </label>
          </div>

          <div v-if="mode === 'existing'" class="space-y-4">
            <div class="flex items-end gap-2">
              <div class="flex-1">
                <label class="text-sm font-semibold text-gray-700">Search verified suppliers</label>
                <InputText v-model="directorySearch" placeholder="Search by supplier name, email, city, or province" class="w-full" @keyup.enter="loadVerifiedDirectory" />
              </div>
              <Button label="Search" icon="pi pi-search" size="small" :loading="directoryLoading" @click="loadVerifiedDirectory" type="button" />
            </div>

            <DataTable :value="verifiedDirectory" dataKey="supplier_portal_id" :loading="directoryLoading" stripedRows size="small" paginator :rows="10" responsiveLayout="scroll">
              <template #empty>
                <div class="py-6 text-center text-sm text-slate-500">No verified suppliers found.</div>
              </template>

              <Column field="supplier_name" header="Supplier" sortable>
                <template #body="{ data }">
                  <div class="font-semibold text-slate-900">{{ data.supplier_name || '-' }}</div>
                  <div class="text-xs text-slate-500">{{ data.email || '-' }}</div>
                </template>
              </Column>
              <Column field="province" header="Province" sortable />
              <Column field="city" header="City" sortable />
              <Column field="address" header="Address">
                <template #body="{ data }">
                  <span class="text-sm text-slate-700">{{ data.address || '-' }}</span>
                </template>
              </Column>
              <Column header="Action" style="width:140px">
                <template #body="{ data }">
                  <Button size="small" label="View" icon="pi pi-eye" outlined @click="openSupplierShow(data.supplier_portal_id)" />
                </template>
              </Column>
            </DataTable>
          </div>

          <div v-else>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-user-plus"></i> Invite Supplier
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Supplier Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.supplier_name" placeholder="e.g., Premium Furniture Co." />
                <small v-if="errors.supplier_name" class="text-red-600">{{ errors.supplier_name }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                <InputText v-model="form.email" type="email" placeholder="contact@supplier.com" />
                <small v-if="errors.email" class="text-red-600">{{ errors.email }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Contact Person First Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.contact_person_first" placeholder="First name" />
                <small v-if="errors.contact_person_first" class="text-red-600">{{ errors.contact_person_first }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Contact Person Last Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.contact_person_last" placeholder="Last name" />
                <small v-if="errors.contact_person_last" class="text-red-600">{{ errors.contact_person_last }}</small>
              </div>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-2 border-t">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.suppliers' })" />
            <Button v-if="mode === 'invite'" label="Invite Supplier" icon="pi pi-check" :loading="saving" type="submit" severity="success" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import RadioButton from 'primevue/radiobutton'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()
const saving = ref(false)
const mode = ref<'existing' | 'invite'>('existing')

const form = reactive({
  supplier_name: '',
  contact_person: '',
  contact_person_first: '',
  contact_person_last: '',
  email: '',
})

const errors = reactive<Record<string, string>>({})
const directoryLoading = ref(false)
const directorySearch = ref('')
const verifiedDirectory = ref<any[]>([])

const formProgress = computed(() => {
  if (mode.value === 'existing') {
    return 60
  }

  let filled = 0
  const fields = ['supplier_name', 'contact_person_first', 'contact_person_last', 'email']
  fields.forEach((field) => {
    if ((form as any)[field]) filled++
  })
  return Math.round((filled / fields.length) * 100)
})

const clearErrors = () => {
  Object.keys(errors).forEach((key) => delete errors[key])
}

const validateForm = (): boolean => {
  clearErrors()

  if (!form.supplier_name?.trim()) errors.supplier_name = 'Supplier name is required'
  if (!form.contact_person_first?.trim()) errors.contact_person_first = 'Contact person first name is required'
  if (!form.contact_person_last?.trim()) errors.contact_person_last = 'Contact person last name is required'
  if (!form.email?.trim()) errors.email = 'Email is required'
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address'
  }

  return Object.keys(errors).length === 0
}

const openSupplierShow = (portalId: number) => {
  router.push({ name: 'procurement.suppliers.verified.show', params: { portalId } })
}

const loadVerifiedDirectory = async () => {
  directoryLoading.value = true
  try {
    const response = await procurementService.getVerifiedSupplierDirectory({
      search: directorySearch.value,
      available_only: false,
      limit: 300,
    })

    const payload = response?.data ?? response ?? {}
    const list = payload?.data ?? payload ?? []
    verifiedDirectory.value = Array.isArray(list) ? list : []
  } catch (error) {
    verifiedDirectory.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load verified supplier directory',
      life: 3000,
    })
  } finally {
    directoryLoading.value = false
  }
}

const submitForm = async () => {
  if (mode.value !== 'invite') return

  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please fix all errors before submitting',
      life: 3000,
    })
    return
  }

  saving.value = true
  try {
    form.contact_person = [form.contact_person_first, form.contact_person_last].filter(Boolean).join(' ').trim()
    const payload = {
      supplier_name: form.supplier_name,
      contact_person: form.contact_person,
      email: form.email,
    }

    await procurementService.createSupplier(payload)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Supplier invited and added successfully.',
      life: 3000,
    })

    setTimeout(() => {
      router.push({ name: 'procurement.suppliers' })
    }, 1000)
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Failed to save supplier'
    const errorDetails = error.response?.data?.errors

    if (errorDetails) {
      Object.entries(errorDetails).forEach(([key, messages]: [string, any]) => {
        errors[key] = Array.isArray(messages) ? messages[0] : messages
      })
    }

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 4000,
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadVerifiedDirectory()
})
</script>
