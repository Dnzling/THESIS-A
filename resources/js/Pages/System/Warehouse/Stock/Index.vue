<template>
    <div class="min-h-screen p-4 md:p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    Warehouse Stock
                </h1>
                <p class="text-sm text-slate-500">
                    Store-wide stock locations across branches and warehouses.
                </p>
            </div>
            <Button
                label="Create PR"
                icon="pi pi-plus"
                size="small"
                @click="router.visit('/warehouse/purchase-requisitions/create')"
            />
        </div>
        <Card class="border border-slate-200 shadow-sm"
            ><template #content>
                <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="filters.search"
                            placeholder="Search item or SKU"
                            fluid
                            @keyup.enter="load"
                        />
                    </IconField>
                    <Select
                        v-model="filters.warehouse_id"
                        :options="warehouses"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All warehouses"
                        showClear
                        fluid
                        @change="load"
                    />
                    <Select
                        v-model="filters.status"
                        :options="statuses"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Stock status"
                        showClear
                        fluid
                        @change="load"
                    />
                    <Select
                        v-model="filters.product_type"
                        :options="types"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Product type"
                        showClear
                        fluid
                        @change="load"
                    />
                </div>
                <DataTable
                    :value="rows"
                    :loading="loading"
                    class="text-sm"
                    rowHover
                    paginator
                    lazy
                    :rows="meta.per_page"
                    :totalRecords="meta.total"
                    @page="onPage"
                    responsiveLayout="scroll"
                >
                    <template #empty>
                        <div class="py-12 text-center">
                            <i class="pi pi-box text-3xl text-slate-300" />
                            <p class="mt-3 text-slate-500">
                                No warehouse stock found.
                            </p>
                        </div>
                    </template>
                  
                    <Column header="SKU"
                        ><template #body="{ data }">{{
                            data.variation?.variation_sku ||
                            data.product?.sku ||
                            "—"
                        }}</template></Column
                    >
                    <Column header="Product" style="min-width: 200px"
                        ><template #body="{ data }">
                            <div class="font-medium">
                                {{ data.product?.product_name || "—" }}
                            </div>
                            <small class="text-slate-500">{{
                                label(data.product?.product_type)
                            }}</small>
                        </template></Column
                    >

                    <Column header="Cost / Unit" style="width: 150px"
                        ><template #body="{ data }"
                            >{{ money(data.product?.cost_price) }} /
                            <b>{{
                                data.product?.unit_of_measurement || "unit"
                            }}</b></template
                        >
                    </Column>
                    <Column field="reorder_point" header="Reorder" />
                    <Column field="quantity_on_hand" header="On Hand" />
                    <Column header="Stock Value" class="font-bold text-green-600"
                        ><template #body="{ data }">{{
                            money(data.stock_value)
                        }}</template></Column
                    >
                    <Column header="Status" style="width: 150px" class="text-xs"
                        ><template #body="{ data }">
                            <Badge
                                :value="label(data.stock_status)"
                                :severity="severity(data.stock_status)"
                            /> </template
                    ></Column>
                    <Column header="Action" style="width: 90px"
                        ><template #body="{ data }"
                            ><Button
                                icon="pi pi-eye"
                                outlined
                                rounded
                                label="View"
                                size="small"
                                aria-label="View stock"
                                @click="
                                    router.visit(
                                        `/warehouse/stock/${data.id}`,
                                    )
                                "
                        /></template>
                    </Column>
                </DataTable> </template
        ></Card>
    </div>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";
import WarehouseService from "@/services/warehouse.service";
const rows = ref<any[]>([]),
    warehouses = ref<any[]>([]),
    loading = ref(false),
    meta = reactive({ page: 1, per_page: 15, total: 0 });
const filters = reactive({
    search: "",
    warehouse_id: null as number | null,
    status: null as string | null,
    product_type: null as string | null,
});
const statuses = [
    { label: "In Stock", value: "in_stock" },
    { label: "Low Stock", value: "low_stock" },
    { label: "Out of Stock", value: "out_of_stock" },
    { label: "Overstock", value: "overstock" },
];
const types = [
    { label: "Product", value: "finished_good" },
    { label: "Supply", value: "supply" },
    { label: "Raw Material", value: "raw_material" },
    { label: "Other", value: "other" },
];
const label = (v: string) =>
    (v || "—").replaceAll("_", " ").replace(/\b\w/g, (c) => c.toUpperCase());
const money = (v: any) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(v || 0));
const severity = (v: string) =>
    v === "out_of_stock"
        ? "danger"
        : v === "low_stock"
          ? "warn"
          : v === "overstock"
            ? "info"
            : "success";
const load = async () => {
    loading.value = true;
    try {
        const p = await WarehouseService.stock({
            ...filters,
            page: meta.page,
            per_page: meta.per_page,
        });
        rows.value = p.data || [];
        meta.total = p.total || 0;
        meta.per_page = p.per_page || 15;
    } finally {
        loading.value = false;
    }
};
const onPage = (e: any) => {
    meta.page = e.page + 1;
    load();
};
onMounted(async () => {
    const p = await WarehouseService.warehouses({
        per_page: 100,
        status: "active",
    });
    warehouses.value = p.data || [];
    await load();
});
</script>
