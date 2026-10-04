<template>
    <div class="space-y-4 text-xs">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Create payroll</h2>
            <p class="mt-1 text-slate-500">Choose a branch and completed date range to calculate and save draft payroll.</p>
        </div>

        <div class="grid gap-3 p-3 md:grid-cols-4 md:items-end">
            <div class="space-y-1 col-span-2">
                <label class="font-medium text-slate-700" for="payroll-create-branch">Branch</label>
                <Select
                    v-model="branchId"
                    inputId="payroll-create-branch"
                    :options="branches"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select a branch"
                    :loading="branchesLoading"
                    :disabled="generating"
                    class="w-full text-xs "
                    @change="resetResult"
                />
            </div>
            <div class="space-y-1">
                <label class="font-medium text-slate-700" for="payroll-create-range">Date range</label>
                <DatePicker
                    v-model="dateRange"
                    inputId="payroll-create-range"
                    selectionMode="range"
                    :maxDate="new Date()"
                    :manualInput="false"
                    :disabled="generating"
                    dateFormat="M d, yy"
                    showIcon
                    size="small"
                    fluid
                    @update:modelValue="resetResult"
                />
            </div>
            <Button
                label="Generate Payroll"
                severity="warn"
                size="small"
                :loading="generating"
                :disabled="!canGenerate"
                class=" md:w-auto"
                fluid
                @click="generatePayroll"
            />
        </div>

        <div v-if="message" :class="messageIsError ? 'border-red-200 bg-red-50 text-red-700' : 'border-orange-200 bg-orange-50 text-orange-900'" class="rounded-xl border px-3 py-2 text-xs" role="status">
            {{ message }}
        </div>

        <template v-if="generated">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <div v-for="card in summaryCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-3">
                    <div class="text-slate-500">{{ card.label }}</div>
                    <div class="mt-1 text-base font-semibold text-slate-900">{{ card.currency ? money(card.value) : card.value }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <DataTable
                    v-model:expandedRows="expandedRows"
                    :value="employees"
                    dataKey="employee_id"
                    size="small"
                    class="text-xs"
                    scrollable
                    scrollDirection="horizontal"
                    :tableStyle="{ minWidth: '1050px' }"
                >
                    <template #empty>
                        <div class="py-8 text-center text-slate-500">No eligible employees in this branch and date range.</div>
                    </template>
                    <Column expander style="width: 3rem" />
                    <Column header="Employee / Role" style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="font-semibold text-slate-900">{{ data.employee_name }}</div>
                            <div class="mt-0.5 text-slate-500">{{ data.role_name }}</div>
                        </template>
                    </Column>
                    <Column field="days" header="Days" />
                    <Column header="Worked Hrs">
                        <template #body="{ data }">{{ hours(data.worked_hours) }}</template>
                    </Column>
                    <Column field="absent" header="Absent" />
                    <Column field="on_leave" header="On Leave" />
                    <Column field="late_minutes" header="Late Min" />
                    <Column header="OT Hrs">
                        <template #body="{ data }">{{ hours(data.ot_hours) }}</template>
                    </Column>
                    <Column header="Gross">
                        <template #body="{ data }">{{ money(data.gross) }}</template>
                    </Column>
                    <Column header="Deductions">
                        <template #body="{ data }">{{ money(data.deductions) }}</template>
                    </Column>
                    <Column header="Net Pay">
                        <template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.net_pay) }}</span></template>
                    </Column>
                    <template #expansion="{ data }">
                        <div class="rounded-lg border border-orange-100 bg-orange-50 p-3">
                            <div class="mb-2 font-semibold text-orange-900">{{ data.employee_name }} · Daily work</div>
                            <DataTable :value="data.details" size="small" class="text-xs [&_.p-datatable-tbody>tr]:!bg-orange-50 [&_.p-datatable-thead>tr]:!bg-orange-100" scrollable scrollDirection="horizontal" :tableStyle="{ minWidth: '850px' }">
                                <template #empty>
                                    <div class="py-3 text-center text-orange-800">No attendance or scheduled workdays were recorded for this range.</div>
                                </template>
                                <Column field="date" header="Date" />
                                <Column header="Status">
                                    <template #body="{ data: day }"><span class="capitalize">{{ String(day.status || 'unknown').replace('_', ' ') }}</span></template>
                                </Column>
                                <Column header="Clock In">
                                    <template #body="{ data: day }">{{ time(day.clock_in) }}</template>
                                </Column>
                                <Column header="Clock Out">
                                    <template #body="{ data: day }">{{ time(day.clock_out) }}</template>
                                </Column>
                                <Column header="Worked Hrs">
                                    <template #body="{ data: day }">{{ hours(day.actual_hours) }}</template>
                                </Column>
                                <Column header="Paid Hrs">
                                    <template #body="{ data: day }">{{ hours(day.paid_hours) }}</template>
                                </Column>
                                <Column field="break_minutes" header="Break Min" />
                                <Column field="late_minutes" header="Late Min" />
                                <Column header="OT Hrs">
                                    <template #body="{ data: day }">{{ hours(day.ot_hours) }}</template>
                                </Column>
                            </DataTable>
                        </div>
                    </template>
                </DataTable>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import hrService from '@/services/hr.services'

type BranchOption = { id: number; name: string }
type PayrollRow = { employee_id: number; employee_name: string; role_name: string; worked_hours: number; has_source_data: boolean; details: any[]; [key: string]: any }

const branches = ref<BranchOption[]>([])
const branchesLoading = ref(false)
const branchId = ref<number | null>(null)
const dateRange = ref<Date[] | null>(null)
const generating = ref(false)
const generated = ref(false)
const employees = ref<PayrollRow[]>([])
const expandedRows = ref<Record<string, boolean>>({})
const summary = ref({ employees: 0, worked_hours: 0, gross: 0, incentives: 0, deductions: 0, net_pay: 0 })
const message = ref('')
const messageIsError = ref(false)

const canGenerate = computed(() => branchId.value !== null && dateRange.value?.length === 2 && dateRange.value.every(Boolean) && !generating.value)
const summaryCards = computed(() => [
    { label: 'Employees', value: summary.value.employees },
    { label: 'Worked Hours', value: hours(summary.value.worked_hours) },
    { label: 'Total Gross', value: summary.value.gross, currency: true },
    { label: 'Incentives', value: summary.value.incentives, currency: true },
    { label: 'Deductions', value: summary.value.deductions, currency: true },
    { label: 'Net Pay', value: summary.value.net_pay, currency: true },
])

const formatLocal = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const money = (value: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const hours = (value: number) => Number(value || 0).toFixed(2)
const time = (value: string | null) => value ? new Date(value).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '—'

function resetResult() {
    generated.value = false
    employees.value = []
    expandedRows.value = {}
    message.value = ''
}

async function loadBranches() {
    branchesLoading.value = true
    try {
        const response = await hrService.getBranches()
        const rows = response?.data || []
        branches.value = (Array.isArray(rows) ? rows : []).map((branch: any) => ({
            id: Number(branch.id),
            name: String(branch.name || `Branch ${branch.id}`),
        }))
    } catch {
        message.value = 'Branches could not be loaded. Please try again.'
        messageIsError.value = true
    } finally {
        branchesLoading.value = false
    }
}

async function generatePayroll() {
    if (!canGenerate.value || !dateRange.value || branchId.value === null) return

    const start = formatLocal(dateRange.value[0])
    const end = formatLocal(dateRange.value[1])
    generating.value = true
    resetResult()
    try {
        const previewResponse = await hrService.api.get('/api/payroll/preview', {
            params: { start_date: start, end_date: end, branch_id: branchId.value },
        })
        const previewRows: PayrollRow[] = previewResponse.data?.data?.employees || []
        const eligible = previewRows.filter((row) => row.has_source_data)
        if (!eligible.length) {
            throw new Error('No employees have attendance, approved paid leave, or scheduled work in this range.')
        }

        const periodsResponse = await hrService.api.get('/api/payroll/periods', {
            params: { start_date: start, end_date: end },
        })
        const periods: any[] = periodsResponse.data?.data || []
        const exact = periods.find((period) => period.start_date === start && period.end_date === end)
        if (!exact && periods.length) {
            throw new Error('This range overlaps another pay period. Choose that period or select a different date range.')
        }
        if (exact && ['completed', 'locked'].includes(String(exact.status).toLowerCase())) {
            throw new Error('This pay period is completed or locked.')
        }

        const periodId = exact?.id || (await hrService.api.post('/api/payroll/periods', {
            start_date: start,
            end_date: end,
            cutoff_date: end,
        })).data?.data?.id
        if (!periodId) throw new Error('Could not create the pay period.')

        const generatedResponse = await hrService.api.post('/api/payroll/generate', {
            pay_period_id: periodId,
            branch_id: branchId.value,
            employee_ids: eligible.map((row) => row.employee_id),
            recalculate: true,
            initial_status: 'draft',
        })
        const result = generatedResponse.data?.data
        if (!generatedResponse.data?.success || !result) throw new Error(generatedResponse.data?.message || 'Payroll generation failed.')

        const savedIds = new Set((result.payrolls || []).map((payroll: any) => Number(payroll.employee_id)))
        employees.value = eligible.filter((row) => savedIds.has(Number(row.employee_id)))
        if (!employees.value.length) {
            throw new Error(result.errors?.[0]?.error || 'No payroll records were saved. Review the employee errors and try again.')
        }
        summary.value = {
            employees: employees.value.length,
            worked_hours: employees.value.reduce((sum, row) => sum + Number(row.worked_hours || 0), 0),
            gross: employees.value.reduce((sum, row) => sum + Number(row.gross || 0), 0),
            incentives: employees.value.reduce((sum, row) => sum + Number(row.incentives || 0), 0),
            deductions: employees.value.reduce((sum, row) => sum + Number(row.deductions || 0), 0),
            net_pay: employees.value.reduce((sum, row) => sum + Number(row.net_pay || 0), 0),
        }
        generated.value = true
        const errorCount = Array.isArray(result.errors) ? result.errors.length : 0
        const skippedCount = Number(result.skipped || 0)
        message.value = `${Number(result.generated || 0)} payroll record(s) ready for ${start} to ${end}. New records were saved as drafts.${skippedCount ? ` ${skippedCount} skipped.` : ''}${errorCount ? ` ${errorCount} failed: ${result.errors[0].error}` : ''}`
        messageIsError.value = errorCount > 0
    } catch (error: any) {
        message.value = error?.response?.data?.message || error?.message || 'Could not generate payroll.'
        messageIsError.value = true
    } finally {
        generating.value = false
    }
}

onMounted(loadBranches)
</script>
