<template>
    <div class="space-y-4 p-4 text-xs md:p-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">
                {{ warehouse ? "Warehouse" : "Inventory" }} Forecasting
            </h1>
            <p class="mt-1 text-slate-500">
                A transparent 30-day projection from recent stock outflow.
                Review low-cover items before creating a replenishment request.
            </p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="card in cards"
                :key="card.label"
                class="border border-slate-200 shadow-sm"
                ><template #content>
                    <div class="text-slate-500">{{ card.label }}</div>
                    <div class="mt-1 text-xl font-semibold text-slate-900">
                        {{ card.value }}
                    </div>
                </template></Card
            >
        </div>
        <Card class="border border-slate-200 shadow-sm"
            ><template #content>
                <div
                    class="mb-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Recent stock outflow
                        </h2>
                        <p class="text-slate-500">
                            Daily units removed from stock in the selected
                            period
                        </p>
                    </div>
                    <DatePicker
                        v-model="dateRange"
                        selectionMode="range"
                        dateFormat="M d, yy"
                        :manualInput="false"
                        :maxDate="today"
                        showIcon
                        size="small"
                        placeholder="Select date range"
                        @date-select="dateRangeChanged"
                    />
                </div>
                <div v-if="loading" class="space-y-2">
                    <Skeleton v-for="n in 3" :key="n" height="36px" />
                </div>
                <Chart
                    v-else-if="trend.length"
                    type="line"
                    :data="chartData"
                    :options="chartOptions"
                    class="h-60"
                />
                <div v-else class="py-10 text-center text-slate-500">
                    No stock outflow recorded for this period. Forecast demand
                    requires transaction history.
                </div>
            </template></Card
        >
        <Card class="border border-slate-200 shadow-sm"
            ><template #content>
                <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <IconField
                        ><InputIcon class="pi pi-search" /><InputText
                            v-model="search"
                            placeholder="Search item or SKU"
                            size="small"
                            fluid
                            @input="searchChanged"
                    /></IconField>
                    <Select
                        v-if="branches.length > 1"
                        v-model="branchId"
                        :options="branches"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All branches"
                        showClear
                        size="small"
                        fluid
                        @change="refresh"
                    />
                    <Select
                        v-model="status"
                        :options="statuses"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All statuses"
                        showClear
                        size="small"
                        fluid
                        @change="refresh"
                    />
                </div>
                <Message
                    v-if="error"
                    severity="error"
                    :closable="false"
                    class="mb-3"
                    >{{ error }}</Message
                >
                <DataTable
                    :value="rows"
                    :loading="loading"
                    rowHover
                    responsiveLayout="scroll"
                    size="small"
                >
                    <template #empty
                        ><div class="py-10 text-center text-slate-500">
                            No finished-good items match these filters.
                        </div></template
                    >
                    <Column field="sku" header="SKU" />
                    <Column header="Product" style="min-width: 190px"
                        ><template #body="{ data }"
                            ><span class="font-medium text-slate-900">{{
                                data.product_name
                            }}</span></template
                        ></Column
                    >
                    <Column
                        v-if="branches.length > 1"
                        field="branch_name"
                        header="Branch"
                    />
                    <Column field="available" header="Available" />
                    <Column field="reorder_point" header="Reorder at" />
                    <Column field="sold" header="Used in period" />
                    <Column header="30-day forecast"
                        ><template #body="{ data }">{{
                            number(data.forecast_30_days)
                        }}</template></Column
                    >
                    <Column header="Days cover"
                        ><template #body="{ data }">{{
                            data.days_cover === null
                                ? "No demand yet"
                                : number(data.days_cover)
                        }}</template></Column
                    >
                    <Column field="recommended" header="Suggested qty" />
                    <Column header="Status" style="min-width: 145px"
                        ><template #body="{ data }"
                            ><Badge
                                :value="data.status"
                                :severity="severity(data.status)"
                                class="whitespace-nowrap" /></template
                    ></Column>
                    <Column header="Action" style="min-width: 170px"
                        ><template #body="{ data }"
                            ><div class="flex w-max flex-nowrap items-center gap-2"><Button label="View" size="small" outlined rounded severity="secondary" @click="viewItem(data)" /><Button v-if="data.action_key === 'create_restock'"
                                label="Restock"
                                size="small"
                                outlined
                                severity="warn"
                                @click="performAction(data)" /></div></template
                    ></Column>
                </DataTable>
                <Paginator
                    v-if="total > 15"
                    :rows="15"
                    :first="(page - 1) * 15"
                    :totalRecords="total"
                    @page="
                        page = $event.page + 1;
                        load();
                    "
                /> </template
        ></Card>
        <Card
            v-if="consolidatedEnabled"
            class="border border-slate-200 shadow-sm"
            ><template #content>
                <h2 class="text-sm font-semibold text-slate-900">
                    Storefront demand and warehouse supply
                </h2>
                <p class="mb-4 mt-1 text-slate-500">
                    Store-wide stock outflow is compared with pooled warehouse
                    availability. This does not assign a specific warehouse to a
                    storefront.
                </p>
                <DataTable
                    :value="consolidated"
                    :loading="loading"
                    rowHover
                    size="small"
                    paginator
                    :rows="10"
                >
                    <template #empty
                        ><div class="py-8 text-center text-slate-500">
                            No storefront demand in this period.
                        </div></template
                    >
                    <Column field="product_name" header="Product" />
                    <Column field="sku" header="SKU" />
                    <Column
                        field="storefront_units"
                        header="Storefront units"
                    />
                    <Column field="forecast_30_days" header="30-day forecast" />
                    <Column
                        field="warehouse_available"
                        header="Warehouse available"
                    />
                    <Column header="Supply gap"
                        ><template #body="{ data }"
                            ><Badge
                                :value="number(data.gap)"
                                :severity="
                                    data.gap > 0 ? 'warn' : 'success'
                                " /></template
                    ></Column>
                </DataTable> </template
        ></Card>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import Card from "primevue/card";
import Chart from "primevue/chart";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Badge from "primevue/badge";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Skeleton from "primevue/skeleton";
import Paginator from "primevue/paginator";
import Message from "primevue/message";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";

const warehouse = window.location.pathname.startsWith("/warehouse/");
const endpoint = warehouse
    ? "/api/warehouse/forecasting"
    : "/api/inventory/forecasting";
const statuses = ["Out of stock", "Reorder now", "Watch", "Projected excess", "Healthy"].map(
    (value) => ({ label: value, value }),
);
const today = new Date();
today.setHours(0, 0, 0, 0);
const defaultStart = new Date(today);
defaultStart.setDate(defaultStart.getDate() - 29);
const dateRange = ref<Date[]>([defaultStart, new Date(today)]);
const selectedPeriodDays = computed(() => {
    if (dateRange.value?.length !== 2 || !dateRange.value[0] || !dateRange.value[1]) return 30;
    const start = new Date(dateRange.value[0]);
    const end = new Date(dateRange.value[1]);
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    return Math.max(1, Math.floor((end.getTime() - start.getTime()) / 86400000) + 1);
});
const branchId = ref<number | null>(null);
const status = ref<string | null>(null);
const search = ref("");
const page = ref(1);
const total = ref(0);
const loading = ref(true);
const error = ref("");
const rows = ref<any[]>([]);
const branches = ref<any[]>([]);
const trend = ref<any[]>([]);
const consolidated = ref<any[]>([]);
const consolidatedEnabled = ref(false);
const warehouseEnabled = ref(false);
const summary = ref<any>({
    products: 0,
    reorder_now: 0,
    forecast_units: 0,
    recommended_units: 0,
});
const number = (value: any) =>
    new Intl.NumberFormat("en-PH", { maximumFractionDigits: 1 }).format(
        Number(value) || 0,
    );
const cards = computed(() => [
    { label: "Finished goods", value: number(summary.value.products) },
    { label: "Reorder now", value: number(summary.value.reorder_now) },
    {
        label: "Projected 30-day use",
        value: number(summary.value.forecast_units),
    },
    {
        label: "Suggested units",
        value: number(summary.value.recommended_units),
    },
]);
const severity = (value: string) =>
    value === "Out of stock"
        ? "danger"
        : value === "Reorder now"
          ? "warn"
          : value === "Watch"
            ? "info"
            : value === "Projected excess" ? "warn" : "success";
const viewItem = (data: any) => router.visit(warehouse ? `/warehouse/stock/${data.id}` : `/inventory/items/${data.id}`);
const performAction = (data: any) => {
    if (data.action_key !== "create_restock") {
        viewItem(data);
        return;
    }
    const useWarehouseTransfer = !warehouse && warehouseEnabled.value;
    const path = warehouse
        ? "/warehouse/purchase-requisitions/create"
        : useWarehouseTransfer
          ? "/inventory/stock-requests/create"
          : "/inventory/purchase-orders/create";
    const params = warehouse
        ? new URLSearchParams({
              branch_inventory_id: String(data.id),
              branch_id: String(data.branch_id),
              product_id: String(data.product_id),
              requested_quantity: String(Math.max(1, Number(data.recommended || 0))),
              notes: `Restock requested from the ${selectedPeriodDays.value}-day forecast.`,
          })
        : useWarehouseTransfer
          ? new URLSearchParams({
                type: "transfer",
                stock_view_prefill: "1",
                product_id: String(data.product_id),
                variation_id: String(data.variation_id || 0),
                requested_quantity: String(Math.max(1, Number(data.recommended || 0))),
                receiving_branch_id: String(data.branch_id),
                notes: `Restock requested from the ${selectedPeriodDays.value}-day forecast.`,
            })
        : new URLSearchParams({
              forecast_prefill: "1",
              product_id: String(data.product_id),
              variation_id: String(data.variation_id || 0),
              quantity_ordered: String(Math.max(1, Number(data.recommended || 0))),
              branch_id: String(data.branch_id),
              forecast_days: String(selectedPeriodDays.value),
          });
    router.visit(`${path}?${params.toString()}`);
};
const chartData = computed(() => ({
    labels: trend.value.map((row) => row.day),
    datasets: [
        {
            label: "Units out",
            data: trend.value.map((row) => Number(row.units)),
            borderColor: "#f97316",
            backgroundColor: "rgba(249,115,22,.12)",
            fill: true,
            tension: 0.3,
        },
    ],
}));
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } },
};
let searchTimer: ReturnType<typeof setTimeout> | null = null;
function searchChanged() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(refresh, 300);
}
function refresh() {
    page.value = 1;
    load();
}
function dateRangeChanged() {
    if (dateRange.value?.length === 2 && dateRange.value[0] && dateRange.value[1]) refresh();
}
function dateParam(date: Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}
async function load() {
    if (dateRange.value?.length !== 2 || !dateRange.value[0] || !dateRange.value[1]) return;
    loading.value = true;
    error.value = "";
    try {
        const response = await axios.get(endpoint, {
            params: {
                from_date: dateParam(dateRange.value[0]),
                to_date: dateParam(dateRange.value[1]),
                branch_id: branchId.value,
                search: search.value,
                status: status.value,
                page: page.value,
            },
        });
        const data = response.data.data;
        rows.value = data.rows || [];
        total.value = data.total || 0;
        branches.value = data.branches || [];
        trend.value = data.trend || [];
        consolidated.value = data.consolidated || [];
        consolidatedEnabled.value = !!data.plan_features?.consolidated;
        warehouseEnabled.value = !!data.plan_features?.warehouse;
        summary.value = data.summary || {};
    } catch (err: any) {
        error.value =
            err.response?.data?.message || "Could not load the forecast.";
        rows.value = [];
    } finally {
        loading.value = false;
    }
}
onMounted(load);
</script>
