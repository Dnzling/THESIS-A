<template>
    <div class="space-y-5 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800">
                    Purchase Orders
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Order products directly from your suppliers.
                </p>
            </div>
            <Button
                label="New Purchase Order"
                icon="pi pi-plus"
                size="small"
                severity="warn"
                @click="router.visit('/inventory/purchase-orders/create')"
            />
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="card in cards"
                :key="card.label"
                class="border border-slate-200 shadow-sm"
                ><template #content
                    ><div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase text-slate-500"
                            >
                                {{ card.label }}
                            </p>
                            <Skeleton
                                v-if="loading"
                                class="mt-2"
                                width="5rem"
                                height="1.5rem"
                            />
                            <p
                                v-else
                                class="mt-2 text-xl font-bold text-slate-900"
                            >
                                {{ card.value }}
                            </p>
                        </div>
                        <i
                            :class="[card.icon, 'text-2xl text-orange-500']"
                        ></i></div></template
            ></Card>
        </div>

        <Card class="border border-slate-200 shadow-sm"
            ><template #content>
                <div class="mb-4 grid gap-3 md:grid-cols-4">
                    <InputText
                        v-model="filters.search"
                        placeholder="Search PO or supplier"
                        size="small"
                        @keyup.enter="load(1)"
                    />
                    <Select
                        v-model="filters.status"
                        :options="statuses"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All statuses"
                        showClear
                        size="small"
                        @change="load(1)"
                    />
                    <Select
                        v-model="filters.supplier_id"
                        :options="suppliers"
                        optionLabel="supplier_name"
                        optionValue="id"
                        placeholder="All suppliers"
                        showClear
                        filter
                        size="small"
                        @change="load(1)"
                    />
                    <div class="flex gap-2">
                        <Button
                            label="Search"
                            size="small"
                            @click="load(1)"
                        /><Button
                            label="Clear"
                            size="small"
                            outlined
                            severity="secondary"
                            @click="clearFilters"
                        />
                    </div>
                </div>
                <div
                    v-if="error"
                    class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700"
                >
                    {{ error }}
                </div>
                <div v-if="loading" class="space-y-3">
                    <Skeleton v-for="row in 5" :key="row" height="2.5rem" />
                </div>
                <DataTable
                    v-else
                    :value="orders"
                    rowHover
                    size="small"
                    scrollable
                >
                    <Column header="PO No."
                        ><template #body="{ data }"
                            ><a
                                :href="`/inventory/purchase-orders/${data.id}`"
                                class="font-semibold text-blue-600 hover:underline"
                                >{{ data.po_number }}</a
                            ></template
                        ></Column
                    >
                    <Column header="Order Date"
                        ><template #body="{ data }">{{
                            date(data.order_date)
                        }}</template></Column
                    >
                    <Column header="Supplier"
                        ><template #body="{ data }"
                            ><div class="font-medium">
                                {{ data.supplier?.supplier_name || "—" }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ data.supplier?.supplier_code }}
                            </div></template
                        ></Column
                    >
                    <Column header="Branch"
                        ><template #body="{ data }">{{
                            data.branch?.name || "—"
                        }}</template></Column
                    >
                    <Column header="Items"
                        ><template #body="{ data }">{{
                            data.items_count
                        }}</template></Column
                    >
                    <Column header="Total"
                        ><template #body="{ data }"
                            ><span class="font-semibold">{{
                                money(data.total_amount)
                            }}</span></template
                        ></Column
                    >
                    <Column header="Status"
                        ><template #body="{ data }"
                            ><Tag
                                :value="label(data.status)"
                                :severity="severity(data.status)" /></template
                    ></Column>
                    <Column header="Actions"
                        ><template #body="{ data }"
                            ><Button
                                label="View"
                                icon="pi pi-eye"
                                size="small"
                                text
                                @click="
                                    router.visit(
                                        `/inventory/purchase-orders/${data.id}`,
                                    )
                                " /></template
                    ></Column>
                    <template #empty
                        ><div class="py-10 text-center text-sm text-slate-500">
                            <i
                                class="pi pi-file mb-2 block text-3xl text-slate-300"
                            ></i
                            >No purchase orders found.
                        </div></template
                    >
                </DataTable>
                <div
                    v-if="total > perPage"
                    class="mt-4 flex items-center justify-end gap-3 text-sm"
                >
                    <span class="text-slate-500"
                        >Page {{ page }} of {{ lastPage }}</span
                    ><Button
                        icon="pi pi-chevron-left"
                        text
                        :disabled="page <= 1"
                        @click="load(page - 1)"
                    /><Button
                        icon="pi pi-chevron-right"
                        text
                        :disabled="page >= lastPage"
                        @click="load(page + 1)"
                    />
                </div> </template
        ></Card>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axiosClient from "@/axios";
import { useAuthStore } from "@/stores/auth";
import Button from "primevue/button";
import Card from "primevue/card";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Skeleton from "primevue/skeleton";
import Tag from "primevue/tag";

const auth = useAuthStore();
const canManage = computed(() =>
    auth.hasPermission("inventory.purchase_orders.manage"),
);
const loading = ref(true);
const error = ref("");
const orders = ref<any[]>([]);
const suppliers = ref<any[]>([]);
const stats = ref<any>({
    total_count: 0,
    sent_count: 0,
    total_amount: 0,
    delayed_count: 0,
});
const filters = reactive({
    search: "",
    status: null as string | null,
    supplier_id: null as number | null,
});
const page = ref(1);
const perPage = ref(15);
const total = ref(0);
const lastPage = computed(() =>
    Math.max(1, Math.ceil(total.value / perPage.value)),
);
const statuses = [
    "draft",
    "sent_to_supplier",
    "supplier_accepted",
    "in_transit",
    "delivered",
    "declined_supplier",
    "cancelled",
].map((value) => ({ label: label(value), value }));
const cards = computed(() => [
    { label: "Total POs", value: stats.value.total_count, icon: "pi pi-file" },
    {
        label: "Sent to Supplier",
        value: stats.value.sent_count,
        icon: "pi pi-send",
    },
    {
        label: "Total Amount",
        value: money(stats.value.total_amount),
        icon: "pi pi-money-bill",
    },
    {
        label: "Delayed Orders",
        value: stats.value.delayed_count,
        icon: "pi pi-clock",
    },
]);

function label(value: string) {
    return String(value || "Unknown")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
}
function severity(value: string) {
    return value === "delivered"
        ? "success"
        : value === "draft"
          ? "secondary"
          : value === "declined_supplier" || value === "cancelled"
            ? "danger"
            : "info";
}
function money(value: any) {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));
}
function date(value: string) {
    return value
        ? new Date(value).toLocaleDateString("en-PH", {
              year: "numeric",
              month: "short",
              day: "numeric",
          })
        : "—";
}
async function load(nextPage = 1) {
    loading.value = true;
    error.value = "";
    try {
        const response = await axiosClient.get(
            "/api/inventory/purchase-orders",
            { params: { ...filters, page: nextPage, per_page: perPage.value } },
        );
        orders.value = response.data?.data?.data || [];
        total.value = Number(response.data?.data?.total || 0);
        page.value = nextPage;
        stats.value = response.data?.stats || stats.value;
        suppliers.value = response.data?.suppliers || [];
    } catch (cause: any) {
        error.value =
            cause?.response?.data?.message || "Unable to load purchase orders.";
    } finally {
        loading.value = false;
    }
}
function clearFilters() {
    filters.search = "";
    filters.status = null;
    filters.supplier_id = null;
    load(1);
}
onMounted(() => load());
</script>
