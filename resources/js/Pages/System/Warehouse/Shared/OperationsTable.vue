<template>
    <div class="min-h-screen space-y-5 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ title }}</h1>
            <p class="text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <Card class="border border-slate-200 shadow-sm"
            ><template #content>
                <div class="mb-5 flex flex-col gap-3 sm:flex-row">
                    <IconField class="w-full sm:max-w-md">
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="search"
                            :placeholder="
                                mode === 'receiving'
                                    ? 'Search GRN'
                                    : 'Search transfer number'
                            "
                            fluid
                            @keyup.enter="load"
                        />
                    </IconField>
                    <Select
                        v-if="mode !== 'history'"
                        v-model="status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Status"
                        showClear
                        class="w-full sm:w-56"
                        @change="load"
                    />
                    <Button
                        label="Refresh"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        @click="load"
                    />
                </div>
                <DataTable
                    :value="rows"
                    :loading="loading"
                    rowHover
                    paginator
                    lazy
                    :rows="meta.per_page"
                    class="text-xs"
                    :totalRecords="meta.total"
                    :rowClass="rowClass"
                    @page="onPage"
                    @row-click="openRow"
                    responsiveLayout="scroll"
                >
                    <template #empty>
                        <div class="py-12 text-center">
                            <i
                                :class="[
                                    mode === 'receiving'
                                        ? 'pi pi-inbox'
                                        : 'pi pi-arrow-right-arrow-left',
                                    'text-3xl text-slate-300',
                                ]"
                            />
                            <p class="mt-3 text-slate-500">No records found.</p>
                        </div>
                    </template>
                    <template v-if="mode === 'receiving'">
                        <Column header="Date"
                            ><template #body="{ data }">{{
                                date(data.receipt_date)
                            }}</template></Column
                        >
                        <Column field="grn_number" header="GRN" />
                        <Column header="PO"
                            ><template #body="{ data }">{{
                                data.purchase_order?.po_number || "—"
                            }}</template></Column
                        >
                        <Column header="Supplier"
                            ><template #body="{ data }">{{
                                data.purchase_order?.supplier?.supplier_name ||
                                "—"
                            }}</template></Column
                        >
                        <Column header="Branch"
                            ><template #body="{ data }">{{
                                data.branch?.name ||
                                data.branch?.branch_name ||
                                "—"
                            }}</template></Column
                        >
                        <!-- <Column field="total_items" header="Items" /> -->
                        <Column field="total_quantity" header="Received Qty" />
                        <Column header="Received By"
                            ><template #body="{ data }">{{
                                data.received_by_name || "—"
                            }}</template></Column
                        >
                        <Column header="Statsaus" style="width: 200px" class="text-xs"
                            ><template #body="{ data }">
                                <Badge
                                    :value="label(data.receipt_status)"
                                    :severity="severity(data.receipt_status)" size="large"
                                /> </template
                        ></Column>
                    </template>
                    <template v-else>
                        <Column header="Date"
                            ><template #body="{ data }">{{
                                date(data.requested_date || data.created_at)
                            }}</template>
                        </Column>
                        <Column field="transfer_number" header="Transfer No." />
                        <Column header="From"
                            ><template #body="{ data }">{{
                                data.from_branch?.name ||
                                data.from_branch?.branch_name ||
                                "—"
                            }}</template></Column
                        >
                        <Column header="To"
                            ><template #body="{ data }">{{
                                data.to_branch?.name ||
                                data.to_branch?.branch_name ||
                                "—"
                            }}</template></Column
                        >
                        <!-- <Column field="total_items" header="Items" /> -->
                        <Column field="total_quantity" header="Quantity" />
                        <Column header="Shipping Fee"
                            ><template #body="{ data }">{{
                                money(data.transfer_cost)
                            }}</template></Column
                        >
                          <Column header="Status" style="width: 150px" class="text-xs"
                            ><template #body="{ data }">
                                <Badge
                                    :value="label(data.status)"
                                    :severity="severity(data.status)"
                                /> </template
                        ></Column>
                        <Column header="Requested By"
                            ><template #body="{ data }">{{
                                data.requested_by_name || "—"
                            }}</template>
                        </Column>
                      
                        <Column header="Action"
                            ><template #body="{ data }"
                                ><Button
                                    icon="pi pi-eye"
                                    outlined
                                    label="View"
                                    rounded
                                    size="small"
                                    aria-label="View transfer"
                                    @click.stop="
                                        openTransfer(data.id)
                                    " /></template
                        ></Column>
                    </template>
                </DataTable> </template
        ></Card>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";
import WarehouseService from "@/services/warehouse.service";
const props = defineProps<{ mode: "requests" | "history" | "receiving" }>();
const rows = ref<any[]>([]),
    loading = ref(false),
    search = ref(""),
    status = ref<string | null>(null),
    meta = reactive({ page: 1, per_page: 15, total: 0 });
const title = computed(() =>
    props.mode === "requests"
        ? "Transfer Requests"
        : props.mode === "history"
          ? "Transfer History"
          : "Receiving",
);
const subtitle = computed(() =>
    props.mode === "requests"
        ? "Review stock movements awaiting warehouse action."
        : props.mode === "history"
          ? "Completed, cancelled, and rejected stock transfers."
          : "Goods received into store and warehouse inventory.",
);
const statusOptions = computed(() =>
    props.mode === "receiving"
        ? [
              { label: "Complete", value: "complete" },
              { label: "Partial", value: "partial" },
              { label: "Damaged", value: "damaged" },
              { label: "Rejected", value: "rejected" },
          ]
        : [
              { label: "Draft", value: "draft" },
              { label: "Requested", value: "requested" },
              { label: "Sender Approved", value: "sender_approved" },
          ],
);
const label = (value: string) =>
    String(value || "—")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
const date = (value: string) =>
    value
        ? new Intl.DateTimeFormat("en-PH", { dateStyle: "medium" }).format(
              new Date(value),
          )
        : "—";
const money = (value: any) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));
const severity = (value: string) =>
    ["received", "complete"].includes(value)
        ? "success"
        : ["rejected", "cancelled", "damaged"].includes(value)
          ? "danger"
          : ["partial", "requested"].includes(value)
            ? "warn"
            : "info";
const openTransfer = (id: number) =>
    router.visit(`/warehouse/transfer-requests/${id}`);
const openRow = (event: any) => {
    if (props.mode !== "receiving" && event?.data?.id)
        openTransfer(event.data.id);
};
const rowClass = () => (props.mode === "receiving" ? "" : "cursor-pointer");
const load = async () => {
    loading.value = true;
    try {
        const fn =
            props.mode === "requests"
                ? WarehouseService.transferRequests
                : props.mode === "history"
                  ? WarehouseService.transferHistory
                  : WarehouseService.receiving;
        const page = await fn({
            search: search.value,
            status: status.value,
            page: meta.page,
            per_page: meta.per_page,
        });
        rows.value = page.data || [];
        meta.total = page.total || 0;
        meta.per_page = page.per_page || 15;
    } finally {
        loading.value = false;
    }
};
const onPage = (event: any) => {
    meta.page = event.page + 1;
    load();
};
onMounted(load);
</script>
