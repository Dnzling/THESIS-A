<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Invoices</h1>
        <p class="text-gray-500 mt-1">Manage supplier invoices and 3-way matching</p>
      </div>
      <Button
        label="New Invoice"
        icon="pi pi-plus"
        class="p-button-lg"
        @click="goToCreateInvoice"
      />
    </div>

    <!-- 3-Way Matching Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Total Invoices</p>
            <p class="text-3xl font-bold text-blue-600">{{ stats.totalCount }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Matched</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.matchedCount }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Pending Match</p>
            <p class="text-2xl font-bold text-orange-600">{{ stats.pendingCount }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Exceptions</p>
            <p class="text-2xl font-bold text-red-600 cursor-pointer" @click="viewExceptions">
              {{ stats.exceptionCount }}
            </p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-500 text-sm">Total Amount</p>
            <p class="text-2xl font-bold text-purple-600">₱ {{ formatNumber(stats.totalAmount) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Tabs for different views -->
    <TabView v-model:activeIndex="activeTab">
      <!-- All Invoices -->
      <TabPanel header="All Invoices" headerIcon="pi pi-file">
        <template #header>
          <i class="pi pi-list mr-2"></i>
          <span>All Invoices</span>
        </template>

        <!-- Filters -->
        <Card class="mb-4">
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
              <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText
                  v-model="filters.search"
                  placeholder="Search invoice no"
                  class="w-full"
                  @keyup.enter="loadInvoices"
                />
              </span>

              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                placeholder="All Status"
                showClear
                @change="loadInvoices"
              />

              <Dropdown
                v-model="filters.match_status"
                :options="matchStatusOptions"
                placeholder="Match Status"
                showClear
                @change="loadInvoices"
              />

              <InputText
                v-model="filters.date_from"
                type="date"
                placeholder="From Date"
                @change="loadInvoices"
              />

              <Button
                icon="pi pi-refresh"
                class="p-button-outlined p-button-rounded"
                @click="loadInvoices"
              />
            </div>
          </template>
        </Card>

        <!-- Invoices Table -->
        <Card>
          <template #content>
            <DataTable
              v-if="!loading"
              :value="invoices"
              :loading="loading"
              :paginator="true"
              :rows="15"
              responsive-layout="scroll"
              class="p-datatable-sm"
            >
              <!-- Invoice Number -->
              <Column field="invoice_number" header="Invoice No." style="width: 12%" sortable>
                <template #body="{ data }">
                  <RouterLink
                    :to="`/procurement/invoices/${data.id}`"
                    class="text-blue-600 hover:underline font-semibold"
                  >
                    {{ data.invoice_number }}
                  </RouterLink>
                </template>
              </Column>

              <!-- Supplier -->
              <Column header="Supplier" style="width: 16%">
                <template #body="{ data }">
                  <div>
                    <p class="font-semibold">{{ data.supplier_name }}</p>
                    <p class="text-xs text-gray-500">{{ data.supplier_code }}</p>
                  </div>
                </template>
              </Column>

              <!-- Dates -->
              <Column header="Invoice / Due Date" style="width: 14%">
                <template #body="{ data }">
                  <div class="text-sm space-y-1">
                    <p>{{ formatDate(data.invoice_date) }}</p>
                    <p class="font-semibold text-orange-600">
                      {{ formatDate(data.due_date) }}
                    </p>
                  </div>
                </template>
              </Column>

              <!-- Amount -->
              <Column header="Amount" style="width: 12%">
                <template #body="{ data }">
                  <p class="text-green-600 font-bold">₱ {{ formatNumber(data.total_amount) }}</p>
                </template>
              </Column>

              <!-- 3-Way Match Status -->
              <Column header="Match Status" style="width: 14%">
                <template #body="{ data }">
                  <div class="space-y-2">
                    <div>
                      <Badge
                        :value="data.match_status"
                        :severity="
                          data.match_status === 'matched' ? 'success' :
                          data.match_status === 'pending' ? 'warning' : 'danger'
                        "
                      />
                    </div>
                    <Button
                      v-if="data.match_status === 'pending'"
                      icon="pi pi-search"
                      class="p-button-sm p-button-text"
                      @click="performMatch(data)"
                    >
                      Try Match
                    </Button>
                  </div>
                </template>
              </Column>

              <!-- Status -->
              <Column header="Status" style="width: 10%">
                <template #body="{ data }">
                  <div class="space-y-1">
                    <Badge
                      :value="data.status"
                      :severity="
                        data.status === 'approved' ? 'success' :
                        data.status === 'draft' ? 'secondary' :
                        data.status === 'pending_approval' ? 'info' : 'danger'
                      "
                    />
                    <Badge
                      v-if="data.payment_status"
                      :value="`Finance: ${formatFinanceStatus(data.payment_status)}`"
                      :severity="financeSeverity(data.payment_status)"
                      class="text-xs"
                    />
                  </div>
                </template>
              </Column>

              <!-- Actions -->
              <Column header="Actions" style="width: 10%" headerStyle="text-align: center">
                <template #body="{ data }">
                  <div class="flex gap-2 justify-center">
                    <Button
                      icon="pi pi-eye"
                      text
                      rounded
                      @click="viewInvoice(data)"
                      v-tooltip="'View'"
                    />
                    <Button
                      v-if="data.status === 'draft'"
                      icon="pi pi-pencil"
                      text
                      rounded
                      severity="info"
                      @click="editInvoice(data)"
                      v-tooltip="'Edit'"
                    />
                  </div>
                </template>
              </Column>

              <!-- Empty -->
              <template #empty>
                <div class="text-center py-8">
                  <i class="pi pi-inbox text-4xl text-gray-300" />
                  <p class="text-gray-500 mt-2">No invoices found</p>
                </div>
              </template>
            </DataTable>

            <div v-if="loading" class="flex justify-center py-8">
              <ProgressSpinner />
            </div>
          </template>
        </Card>
      </TabPanel>

      <!-- Exceptions -->
      <TabPanel header="Exceptions" headerIcon="pi pi-exclamation-triangle">
        <template #header>
          <i class="pi pi-exclamation-triangle mr-2"></i>
          <span>Exceptions ({{ exceptionCount }})</span>
        </template>

        <Card>
          <template #content>
            <DataTable
              v-if="!loading"
              :value="exceptions"
              :loading="loading"
              responsive-layout="scroll"
              class="p-datatable-sm"
            >
              <!-- Invoice -->
              <Column header="Invoice" style="width: 15%">
                <template #body="{ data }">
                  <div>
                    <p class="font-semibold">{{ data.invoice_number }}</p>
                    <p class="text-xs text-gray-500">{{ data.supplier_name }}</p>
                  </div>
                </template>
              </Column>

              <!-- Exception Details -->
              <Column header="Exception Details" style="width: 40%">
                <template #body="{ data }">
                  <div class="space-y-2">
                    <div v-if="data.match_result?.issues" class="text-sm">
                      <div v-for="(issue, idx) in data.match_result.issues" :key="idx" class="flex gap-2">
                        <i class="pi pi-times text-red-600" />
                        <span class="text-gray-700">{{ issue }}</span>
                      </div>
                    </div>
                    <div v-else class="text-gray-500">No specific issues found</div>
                  </div>
                </template>
              </Column>

              <!-- Amount Details -->
              <Column header="Amounts" style="width: 20%">
                <template #body="{ data }">
                  <div class="text-sm space-y-1">
                    <p><span class="text-gray-500">PO:</span> ₱ {{ formatNumber(data.po_amount) }}</p>
                    <p><span class="text-gray-500">Invoice:</span> ₱ {{ formatNumber(data.invoice_amount) }}</p>
                    <p v-if="data.variance" class="text-red-600 font-semibold">
                      Variance: ₱ {{ formatNumber(Math.abs(data.variance)) }}
                    </p>
                  </div>
                </template>
              </Column>

              <!-- Actions -->
              <Column header="Actions" style="width: 15%">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button
                      label="Resolve"
                      icon="pi pi-check"
                      class="p-button-sm p-button-success"
                      @click="resolveException(data)"
                    />
                    <Button
                      label="View"
                      icon="pi pi-eye"
                      class="p-button-sm p-button-info text-white"
                      @click="viewInvoice(data)"
                    />
                  </div>
                </template>
              </Column>

              <template #empty>
                <div class="text-center py-8">
                  <i class="pi pi-check-circle text-4xl text-green-300" />
                  <p class="text-gray-500 mt-2">No exceptions found</p>
                </div>
              </template>
            </DataTable>

            <div v-if="loading" class="flex justify-center py-8">
              <ProgressSpinner />
            </div>
          </template>
        </Card>
      </TabPanel>

      <!-- Pending Approval -->
      <TabPanel header="Pending Approval" headerIcon="pi pi-clock">
        <template #header>
          <i class="pi pi-clock mr-2"></i>
          <span>Pending Approval ({{ pendingApprovalCount }})</span>
        </template>

        <Card>
          <template #content>
            <DataTable
              v-if="!loading"
              :value="pendingApprovalInvoices"
              :loading="loading"
              responsive-layout="scroll"
              class="p-datatable-sm"
            >
              <Column field="invoice_number" header="Invoice" style="width: 15%" />
              <Column field="supplier_name" header="Supplier" style="width: 20%" />
              <Column header="Amount" style="width: 15%">
                <template #body="{ data }">
                  ₱ {{ formatNumber(data.total_amount) }}
                </template>
              </Column>
              <Column field="invoice_date" header="Date" style="width: 15%">
                <template #body="{ data }">
                  {{ formatDate(data.invoice_date) }}
                </template>
              </Column>
              <Column header="Actions" style="width: 15%">
                <template #body="{ data }">
                  <Button
                    label="Approve"
                    icon="pi pi-check"
                    class="p-button-sm p-button-success"
                    @click="approveInvoice(data)"
                  />
                </template>
              </Column>

              <template #empty>
                <div class="text-center py-8">
                  <p class="text-gray-500">No pending approvals</p>
                </div>
              </template>
            </DataTable>

            <div v-if="loading" class="flex justify-center py-8">
              <ProgressSpinner />
            </div>
          </template>
        </Card>
      </TabPanel>
    </TabView>

    <!-- Resolve Exception Dialog -->
    <Dialog v-model:visible="showResolveDialog" header="Resolve Exception" modal style="width: 500px">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-2">Resolution Type</label>
          <Dropdown
            v-model="resolveData.resolution_type"
            :options="resolutionTypes"
            placeholder="Select resolution"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Notes</label>
          <Textarea
            v-model="resolveData.notes"
            placeholder="Add resolution notes"
            rows="3"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" text @click="showResolveDialog = false" />
        <Button label="Resolve" @click="submitResolve" :loading="resolving" />
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()

// State
const invoices = ref<any[]>([])
const exceptions = ref<any[]>([])
const pendingApprovalInvoices = ref<any[]>([])
const loading = ref(false)
const activeTab = ref(0)
const showResolveDialog = ref(false)
const resolving = ref(false)

const stats = ref({
  totalCount: 0,
  matchedCount: 0,
  pendingCount: 0,
  exceptionCount: 0,
  totalAmount: 0,
})

const filters = ref({
  search: '',
  status: '',
  match_status: '',
  date_from: '',
})

const resolveData = ref({
  invoice_id: null,
  resolution_type: '',
  notes: '',
})

const statusOptions = ref([
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Paid', value: 'paid' },
])

const matchStatusOptions = ref([
  { label: 'Matched', value: 'matched' },
  { label: 'Pending', value: 'pending' },
  { label: 'Exception', value: 'exception' },
])

const resolutionTypes = ref([
  { label: 'PO Adjustment', value: 'po_adjust' },
  { label: 'Invoice Correction', value: 'invoice_correct' },
  { label: 'Approve Override', value: 'approve_override' },
  { label: 'Reject Invoice', value: 'reject' },
])

// Computed
const exceptionCount = computed(() => exceptions.value.length)
const pendingApprovalCount = computed(() => pendingApprovalInvoices.value.length)

// Methods
async function loadInvoices() {
  loading.value = true
  try {
    const response = await procurementService.getInvoices(filters.value)
    invoices.value = response.data?.data || []

    // Separate by status
    exceptions.value = invoices.value.filter((i: any) => i.match_status === 'exception')
    pendingApprovalInvoices.value = invoices.value.filter((i: any) => i.status === 'pending_approval')

    calculateStats()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load invoices',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function calculateStats() {
  stats.value.totalCount = invoices.value.length
  stats.value.matchedCount = invoices.value.filter((i: any) => i.match_status === 'matched').length
  stats.value.pendingCount = invoices.value.filter((i: any) => i.match_status === 'pending').length
  stats.value.exceptionCount = invoices.value.filter((i: any) => i.match_status === 'exception').length
  stats.value.totalAmount = invoices.value.reduce((sum: number, i: any) => sum + (i.total_amount || 0), 0)
}

async function performMatch(invoice: any) {
  try {
    loading.value = true
    await procurementService.performInvoiceMatch(invoice.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice matched successfully',
      life: 3000,
    })
    loadInvoices()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to match invoice',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

async function approveInvoice(invoice: any) {
  try {
    loading.value = true
    await procurementService.approveInvoice(invoice.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice approved',
      life: 3000,
    })
    loadInvoices()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to approve invoice',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function viewInvoice(invoice: any) {
  router.push({
    name: 'procurement.invoices.detail',
    params: { id: invoice.id },
  })
}

function editInvoice(invoice: any) {
  router.push({
    name: 'procurement.invoices.edit',
    params: { id: invoice.id },
  })
}

function resolveException(invoice: any) {
  resolveData.value.invoice_id = invoice.id
  showResolveDialog.value = true
}

async function submitResolve() {
  if (!resolveData.value.resolution_type) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please select a resolution type',
      life: 3000,
    })
    return
  }

  try {
    resolving.value = true
    // TODO: Implement resolution endpoint
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Exception resolved',
      life: 3000,
    })
    showResolveDialog.value = false
    loadInvoices()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to resolve exception',
      life: 3000,
    })
  } finally {
    resolving.value = false
  }
}

function viewExceptions() {
  activeTab.value = 1
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value)
}

function financeSeverity(status: string): string {
  if (status === 'paid') return 'success'
  if (status === 'pending') return 'warning'
  return 'secondary'
}

function formatFinanceStatus(status: string): string {
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

function goToCreateInvoice() {
  router.push({ name: 'procurement.invoices.create' })
}

onMounted(() => {
  loadInvoices()
})
</script>
