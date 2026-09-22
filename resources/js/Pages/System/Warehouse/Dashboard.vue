<template>
    <div class="md:p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Warehouse Dashboard
            </h1>
            <p class="text-sm text-slate-500">
                Store-wide warehouse capacity, stock health, and transfers.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <Card
                v-for="card in cards"
                :key="card.label"
                class="border border-slate-200 shadow-sm"
            >
                <template #content
                    ><div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-slate-900"
                            >
                                {{ formatNumber(card.value) }}
                            </p>
                        </div>
                        <i :class="[card.icon, card.color, 'text-2xl']" /></div
                ></template>
            </Card>
        </div>
        <div class="grid gap-6 xl:grid-cols-2">
            <Card class="border border-slate-200 shadow-sm"
                ><template #title
                    ><span class="text-base"
                        >Stock requiring attention</span
                    ></template
                ><template #content>
                    <DataTable
                        :value="data.low_stock_items || []"
                        size="small"
                        :loading="loading"
                        stripedRows
                    >
                        <template #empty
                            ><div class="py-8 text-center text-slate-500">
                                No low-stock items.
                            </div></template
                        >
                        <Column header="Product"
                            ><template #body="{ data: row }"
                                ><div class="font-medium">
                                    {{ row.product?.product_name || "—" }}
                                </div>
                                <small class="text-slate-500">{{
                                    row.product?.sku
                                }}</small></template
                            ></Column
                        >
                        <Column header="Branch"
                            ><template #body="{ data: row }">{{
                                row.branch?.name ||
                                row.branch?.branch_name ||
                                "—"
                            }}</template></Column
                        >
                        <Column field="quantity_on_hand" header="On Hand" />
                        <Column header="Status"
                            ><template #body="{ data: row }"
                                ><Badge
                                    :value="formatLabel(row.stock_status)"
                                    :severity="
                                        stockSeverity(row.stock_status)
                                    " /></template
                        ></Column>
                    </DataTable> </template
            ></Card>
            <Card class="border border-slate-200 shadow-sm"
                ><template #title
                    ><span class="text-base">Recent transfers</span></template
                ><template #content>
                    <DataTable
                        :value="data.recent_transfers || []"
                        size="small"
                        :loading="loading"
                        stripedRows
                    >
                        <template #empty
                            ><div class="py-8 text-center text-slate-500">
                                No transfer activity.
                            </div></template
                        >
                        <Column field="transfer_number" header="Transfer" />
                        <Column header="Route"
                            ><template #body="{ data: row }"
                                ><span
                                    >{{
                                        row.from_branch?.name ||
                                        row.from_branch?.branch_name ||
                                        "—"
                                    }}
                                    →
                                    {{
                                        row.to_branch?.name ||
                                        row.to_branch?.branch_name ||
                                        "—"
                                    }}</span
                                ></template
                            ></Column
                        >
                        <Column header="Status"
                            ><template #body="{ data: row }"
                                ><Badge
                                    :value="formatLabel(row.status)"
                                    :severity="
                                        statusSeverity(row.status)
                                    " /></template
                        ></Column>
                    </DataTable> </template
            ></Card>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import WarehouseService from "@/services/warehouse.service";
const loading = ref(true);
const data = ref<any>({
    summary: {},
    low_stock_items: [],
    recent_transfers: [],
});
const cards = computed(() => [
    {
        label: "Active Warehouses",
        value: data.value.summary?.active_warehouses,
        icon: "pi pi-building",
        color: "text-blue-600",
    },
    {
        label: "Quantity on Hand",
        value: data.value.summary?.quantity_on_hand,
        icon: "pi pi-box",
        color: "text-emerald-600",
    },
    {
        label: "Low / Out of Stock",
        value:
            (data.value.summary?.low_stock || 0) +
            (data.value.summary?.out_of_stock || 0),
        icon: "pi pi-exclamation-triangle",
        color: "text-amber-600",
    },
    {
        label: "Pending Transfers",
        value: data.value.summary?.pending_transfers,
        icon: "pi pi-arrow-right-arrow-left",
        color: "text-violet-600",
    },
]);
const formatNumber = (v: any) => Number(v || 0).toLocaleString();
const formatLabel = (v: string) =>
    (v || "unknown")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
const stockSeverity = (v: string) =>
    v === "out_of_stock" ? "danger" : v === "low_stock" ? "warn" : "success";
const statusSeverity = (v: string) =>
    v === "received"
        ? "success"
        : v === "rejected" || v === "cancelled"
          ? "danger"
          : "info";
onMounted(async () => {
    try {
        data.value = await WarehouseService.dashboard();
    } finally {
        loading.value = false;
    }
});
</script>
