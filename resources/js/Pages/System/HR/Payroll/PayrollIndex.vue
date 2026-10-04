<template>
    <div class="space-y-4 p-4 text-xs">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Payroll</h1>
            <p class="mt-1 text-slate-500">
                Preview, review, and manage store payroll.
            </p>
        </div>
        <Card>
            <template #content>
                <Tabs v-model:value="activeTab">
                    <TabList>
                        <Tab value="run">Payroll Run</Tab>
                        <Tab value="create">Create Payroll</Tab>
                        <Tab value="payslip">History</Tab>
                        <Tab value="thirteenth">13th Month</Tab>
                        <Tab value="deductions">Deductions</Tab>
                        <Tab value="settings">Settings</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="create">
                            <PayrollCreateTab />
                        </TabPanel>
                        <TabPanel value="run">
                            <div class="space-y-4">
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-for="preset in presets"
                                        :key="preset.label"
                                        :label="preset.label"
                                        size="small"
                                        :severity="
                                            preset.active ? 'warn' : 'secondary'
                                        "
                                        :outlined="!preset.active"
                                        @click="applyPreset(preset.key)"
                                    />
                                </div>
                                <div
                                    class="flex flex-col gap-3 md:flex-row md:items-end lg:justify-between"
                                >
                                    <div class="col-span-2">
                                        <label class="mb-1 block font-medium"
                                            >Start Date</label
                                        >
                                        <DatePicker
                                            v-model="dateRange"
                                            selectionMode="range"
                                            :maxDate="new Date()"
                                            dateFormat="MM dd, yy"
                                            :manualInput="false"
                                            showIcon
                                            size="small"
                                            fluid
                                        />
                                    </div>
                                    <Button
                                        label="Generate Preview"
                                        severity="warn"
                                        size="small"
                                        :loading="loading"
                                        @click="loadPreview"
                                    />
                                </div>
                                <div
                                    class="grid grid-cols-2 gap-3 md:grid-cols-5"
                                >
                                    <div
                                        v-for="card in summaryCards"
                                        :key="card.label"
                                        class="rounded-lg border border-slate-100 bg-slate-50 p-3"
                                    >
                                        <div class="text-slate-500">
                                            {{ card.label }}
                                        </div>
                                        <div
                                            class="mt-1 text-lg font-semibold text-slate-900"
                                        >
                                            {{
                                                card.money
                                                    ? money(card.value)
                                                    : card.value
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <DataTable
                                    :value="employees"
                                    dataKey="employee_id"
                                    size="small"
                                    class="text-xs"
                                    :loading="loading"
                                >
                                    <template #loading>
                                        <Skeleton
                                            v-for="n in 6"
                                            :key="n"
                                            height="2rem"
                                            class="mb-2"
                                        /> </template
                                    ><template #empty>
                                        <div
                                            class="py-10 text-center text-slate-500"
                                        >
                                            Select a period and generate a
                                            payroll preview.
                                        </div>
                                    </template>
                                    <Column expander style="width: 2rem" />
                                    <Column
                                        field="employee_name"
                                        header="Employee Name"
                                    />
                                    <Column field="days" header="Days" />
                                    <Column field="absent" header="Absent" />
                                    <Column
                                        field="on_leave"
                                        header="On Leave"
                                    />
                                    <Column
                                        field="break_minutes"
                                        header="Break Time"
                                    />
                                    <Column
                                        field="allowances"
                                        header="Allowances"
                                    />
                                    <Column
                                        field="incentives"
                                        header="Incentives"
                                    />
                                    <Column
                                        field="late_minutes"
                                        header="Late Min"
                                    />
                                    <Column header="Late Ded."
                                        ><template #body="{ data }">{{
                                            money(data.late_deduction)
                                        }}</template>
                                    </Column>
                                    <Column field="ot_hours" header="OT Hrs" />
                                    <Column header="OT Pay"
                                        ><template #body="{ data }">{{
                                            money(data.ot_pay)
                                        }}</template></Column
                                    >
                                    <Column header="Gross"
                                        ><template #body="{ data }">{{
                                            money(data.gross)
                                        }}</template></Column
                                    >
                                    <Column header="Deductions"
                                        ><template #body="{ data }">{{
                                            money(data.deductions)
                                        }}</template>
                                    </Column>
                                    <Column header="Net Pay"
                                        ><template #body="{ data }"
                                            ><span class="font-semibold">{{
                                                money(data.net_pay)
                                            }}</span></template
                                        ></Column
                                    ><template #expansion="{ data }">
                                        <DataTable
                                            :value="data.details"
                                            size="small"
                                            class="text-xs"
                                        >
                                            <Column
                                                field="date"
                                                header="Date"
                                            />
                                            <Column
                                                field="status"
                                                header="Status"
                                            />
                                            <Column header="Clock In"
                                                ><template #body="{ data }">{{
                                                    time(data.clock_in)
                                                }}</template>
                                            </Column>
                                            <Column header="Clock Out"
                                                ><template #body="{ data }">{{
                                                    time(data.clock_out)
                                                }}</template>
                                            </Column>
                                            <Column
                                                field="actual_hours"
                                                header="Actual Hrs"
                                            />
                                            <Column
                                                field="paid_hours"
                                                header="Paid Hrs"
                                            />
                                            <Column
                                                field="break_minutes"
                                                header="Break Time"
                                            />
                                            <Column
                                                field="late_minutes"
                                                header="Late Min"
                                            />
                                            <Column header="Late Ded."
                                                ><template #body="{ data }">{{
                                                    money(data.late_deduction)
                                                }}</template>
                                            </Column>
                                            <Column
                                                field="ot_hours"
                                                header="OT Hrs"
                                            />
                                            <Column header="OT Pay"
                                                ><template #body="{ data }">{{
                                                    money(data.ot_pay)
                                                }}</template></Column
                                            >
                                            <Column header="Daily Gross"
                                                ><template #body="{ data }">{{
                                                    money(data.daily_gross)
                                                }}</template>
                                            </Column>
                                            <Column header="Daily Net"
                                                ><template #body="{ data }">{{
                                                    money(data.daily_net)
                                                }}</template> </Column
                                            ><template #empty>
                                                <div class="py-4 text-center">
                                                    No attendance records in
                                                    this period.
                                                </div>
                                            </template>
                                        </DataTable>
                                    </template>
                                </DataTable>
                            </div>
                        </TabPanel>
                        <TabPanel value="payslip">
                            <PayrollList />
                        </TabPanel>
                        <TabPanel value="thirteenth">
                            <div class="space-y-4">
                                <div
                                    class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
                                >
                                    <div>
                                        <h2
                                            class="font-semibold text-slate-900"
                                        >
                                            13th Month Pay
                                        </h2>
                                        <p class="mt-1 text-slate-500">
                                            Estimate each eligible employee's
                                            13th-month pay from basic salary
                                            earned during the year.
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <Select
                                            v-model="thirteenthYear"
                                            :options="thirteenthYears"
                                            size="small"
                                        /><Button
                                            label="Calculate"
                                            severity="warn"
                                            size="small"
                                            :loading="thirteenthLoading"
                                            @click="calculateThirteenthMonth"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="grid grid-cols-1 gap-3 sm:grid-cols-3"
                                >
                                    <div
                                        class="rounded-lg border border-slate-100 bg-slate-50 p-3"
                                    >
                                        <div class="text-slate-500">
                                            Eligible Employees
                                        </div>
                                        <div class="mt-1 text-lg font-semibold">
                                            {{ thirteenthSummary.employees }}
                                        </div>
                                    </div>
                                    <div
                                        class="rounded-lg border border-slate-100 bg-slate-50 p-3"
                                    >
                                        <div class="text-slate-500">
                                            Basic Salary Earned
                                        </div>
                                        <div class="mt-1 text-lg font-semibold">
                                            {{
                                                money(
                                                    thirteenthSummary.basicSalary,
                                                )
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        class="rounded-lg border border-orange-100 bg-orange-50 p-3"
                                    >
                                        <div class="text-slate-500">
                                            Estimated 13th Month
                                        </div>
                                        <div
                                            class="mt-1 text-lg font-semibold text-orange-600"
                                        >
                                            {{
                                                money(
                                                    thirteenthSummary.thirteenthMonth,
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <DataTable
                                    :value="thirteenthRows"
                                    size="small"
                                    class="text-xs"
                                    :loading="thirteenthLoading"
                                    ><template #loading>
                                        <Skeleton
                                            v-for="n in 5"
                                            :key="n"
                                            height="2rem"
                                            class="mb-2"
                                        /> </template
                                    ><template #empty>
                                        <div
                                            class="py-8 text-center text-slate-500"
                                        >
                                            Click Calculate to generate the
                                            yearly breakdown.
                                        </div>
                                    </template>
                                    <Column
                                        field="employee_name"
                                        header="Employee"
                                    />
                                    <Column
                                        field="hire_date"
                                        header="Hire Date"
                                    />
                                    <Column
                                        field="months_covered"
                                        header="Months Covered"
                                    />
                                    <Column header="Basic Salary Earned"
                                        ><template #body="{ data }">{{
                                            money(data.basic_salary)
                                        }}</template></Column
                                    >
                                    <Column header="13th Month Pay"
                                        ><template #body="{ data }"
                                            ><span class="font-semibold">{{
                                                money(data.thirteenth_month)
                                            }}</span></template
                                        ></Column
                                    >
                                </DataTable>
                                <p class="text-xs text-slate-500">
                                    MVP formula: total basic salary earned
                                    during the selected year divided by 12.
                                    Overtime, allowances, incentives, and
                                    deductions are excluded.
                                </p>
                            </div>
                        </TabPanel>
                        <TabPanel value="deductions">
                            <DeductionType />
                        </TabPanel>
                        <TabPanel value="settings">
                            <PayrollSettings />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </template>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import DatePicker from "primevue/datepicker";
import Skeleton from "primevue/skeleton";
import PayrollList from "../PayrollList.vue";
import DeductionType from "../DeductionType.vue";
import PayrollSettings from "./PayrollSettings.vue";
import PayrollCreateTab from "./PayrollCreateTab.vue";
import hrService from "@/services/hr.services";
import { useToast } from "primevue/usetoast";
const toast = useToast();
const activeTab = ref("run");
const loading = ref(false);
const dateRange = ref<Date[]>([]);
const employees = ref<any[]>([]);
const summary = ref<any>({
    employees: 0,
    gross: 0,
    incentives: 0,
    deductions: 0,
    net_pay: 0,
});
const thirteenthYear = ref(new Date().getFullYear());
const thirteenthYears = [
    new Date().getFullYear() - 1,
    new Date().getFullYear(),
    new Date().getFullYear() + 1,
];
const thirteenthLoading = ref(false);
const thirteenthRows = ref<any[]>([]);
const thirteenthSummary = ref({
    employees: 0,
    basicSalary: 0,
    thirteenthMonth: 0,
});
const presets = ref([
    { label: "1-15 Cutoff", key: "current_first", active: false },
    { label: "16-End Cutoff", key: "current_second", active: false },
    { label: "Last Month 1-15", key: "last_first", active: false },
    { label: "Last Month 16-End", key: "last_second", active: false },
    { label: "This Month", key: "month", active: false },
    { label: "This Week", key: "week", active: false },
    { label: "Yesterday", key: "yesterday", active: false },
    { label: "Today", key: "today", active: false },
]);
const summaryCards = computed(() => [
    { label: "Employees", value: summary.value.employees },
    { label: "Total Gross", value: summary.value.gross, money: true },
    { label: "Total Incentives", value: summary.value.incentives, money: true },
    { label: "Total Deductions", value: summary.value.deductions, money: true },
    { label: "Total Net Pay", value: summary.value.net_pay, money: true },
]);
const money = (value: any) =>
    `₱${Number(value || 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const time = (value: string | null) =>
    value
        ? new Date(value).toLocaleTimeString("en-PH", {
              hour: "numeric",
              minute: "2-digit",
          })
        : "—";
const formatLocal = (date: Date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
const setRange = (start: Date, end: Date) => {
    dateRange.value = [start, end];
};
const applyPreset = (key: string) => {
    const now = new Date();
    const start = new Date(now);
    const end = new Date(now);
    if (key === "current_first") {
        start.setDate(1);
        end.setDate(15);
    } else if (key === "current_second") {
        start.setDate(16);
        end.setMonth(end.getMonth() + 1, 0);
    } else if (key === "last_first") {
        start.setMonth(start.getMonth() - 1, 1);
        end.setMonth(end.getMonth() - 1, 15);
    } else if (key === "last_second") {
        start.setMonth(start.getMonth() - 1, 16);
        end.setDate(0);
    } else if (key === "month") {
        start.setDate(1);
        end.setMonth(end.getMonth() + 1, 0);
    } else if (key === "week") {
        start.setDate(now.getDate() - now.getDay());
        end.setDate(start.getDate() + 6);
    } else if (key === "yesterday") {
        start.setDate(now.getDate() - 1);
        end.setDate(now.getDate() - 1);
    }
    setRange(start, end);
    presets.value.forEach((item) => {
        item.active = item.key === key;
    });
    loadPreview();
};
const loadPreview = async () => {
    if (!dateRange.value[0]) return;
    loading.value = true;
    try {
        const response = await hrService.api.get("/api/payroll/preview", {
            params: {
                start_date: formatLocal(dateRange.value[0]),
                end_date: formatLocal(dateRange.value[1] || dateRange.value[0]),
            },
        });
        employees.value = response.data?.data?.employees || [];
        summary.value = response.data?.data?.summary || summary.value;
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Preview Failed",
            detail:
                error?.response?.data?.message ||
                "Unable to generate payroll preview.",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};
const calculateThirteenthMonth = async () => {
    thirteenthLoading.value = true;
    try {
        const response = await hrService.api.get("/api/employees");
        const records = response.data?.data || [];
        const year = thirteenthYear.value;
        thirteenthRows.value = records
            .filter((employee: any) =>
                ["active", "on_leave"].includes(
                    String(employee.status || "").toLowerCase(),
                ),
            )
            .map((employee: any) => {
                const salary = Number(
                    employee.salary ?? employee.monthly_salary ?? 0,
                );
                const hireDate = employee.hire_date || employee.hireDate;
                const hire = hireDate
                    ? new Date(hireDate)
                    : new Date(`${year}-01-01`);
                const startMonth =
                    hire.getFullYear() < year ? 1 : hire.getMonth() + 1;
                const endMonth = hire.getFullYear() > year ? 0 : 12;
                const months = Math.max(
                    0,
                    Math.min(12, endMonth - startMonth + 1),
                );
                const basic = salary * months;
                return {
                    employee_name:
                        `${employee.fname || ""} ${employee.lname || ""}`.trim(),
                    hire_date: hireDate || "Not set",
                    months_covered: months,
                    basic_salary: basic,
                    thirteenth_month: basic / 12,
                };
            });
        thirteenthSummary.value = {
            employees: thirteenthRows.value.length,
            basicSalary: thirteenthRows.value.reduce(
                (sum, row) => sum + row.basic_salary,
                0,
            ),
            thirteenthMonth: thirteenthRows.value.reduce(
                (sum, row) => sum + row.thirteenth_month,
                0,
            ),
        };
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Calculation Failed",
            detail:
                error?.response?.data?.message ||
                "Unable to calculate 13th month pay.",
            life: 3000,
        });
    } finally {
        thirteenthLoading.value = false;
    }
};
</script>
