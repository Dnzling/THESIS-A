<template>
    <div class="po-detail-page mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
        <div class="print-header">
            <div class="flex items-start justify-between gap-6 border-b-2 border-slate-800 pb-4">
                <div>
                    <p class="text-2xl font-bold tracking-wide text-slate-900">FURNISYNC</p>
                    <p class="mt-1 text-xs uppercase tracking-widest text-slate-500">Inventory Purchase Order</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-slate-500">PO Reference</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ order?.po_number || '—' }}</p>
                    <p class="mt-1 text-sm">{{ label(order?.status || '') }}</p>
                </div>
            </div>
        </div>
        <div class="page-toolbar flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    rounded
                    severity="white"
                    size="small"
                    class="shrink-0"
                    @click="router.visit('/inventory/stock-requests')"
                />
                <div class="min-w-0">
                    <h1 class="mt-1 break-words text-xl font-bold text-slate-900 sm:text-2xl">
                        {{ order?.po_number || 'Purchase Order' }}
                    </h1>
                </div>
            </div>
            <div v-if="order" class="flex shrink-0 items-center gap-2">
                <Badge :value="label(order.status)" :severity="severity(order.status)" />
                <Button
                    label="Print"
                    icon="pi pi-print"
                    outlined
                    rounded
                    severity="secondary"
                    size="small"
                    :loading="printing"
                    @click="windowPrint"
                />
            </div>
        </div>

        <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ error }}
        </div>
        <div v-if="loading" class="space-y-4">
            <Skeleton height="8rem" />
            <Skeleton height="16rem" />
            <Skeleton height="10rem" />
        </div>

        <template v-else-if="order">
            <div
                v-if="canReceive"
                class="screen-only flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
            >
                <i class="pi pi-info-circle mt-0.5 text-blue-600" />
                <p>Create a goods receipt when the shipment arrives. Inventory updates only after the receipt is completed.</p>
            </div>

            <div class="grid gap-4 print:grid-cols-2 lg:grid-cols-5 print:break-inside-avoid">
                <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
                    <template #title>
                        <span class="text-base">Supplier</span>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Supplier name</p>
                                <p class="mt-1 text-base font-semibold text-slate-900">{{ order.supplier?.supplier_name || '—' }}</p>
                            </div>
                            <div class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-slate-500">Contact person</p>
                                    <p class="mt-1 text-sm text-slate-800">{{ order.supplier?.contact_person || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Contact number</p>
                                    <p class="mt-1 text-sm text-slate-800">{{ order.supplier?.phone || '—' }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-xs text-slate-500">Email</p>
                                    <p class="mt-1 break-all text-sm text-slate-800">{{ order.supplier?.email || '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm lg:col-span-3">
                    <template #title>
                        <span class="text-base">Order Information</span>
                    </template>
                    <template #content>
                        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Receiving branch</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ order.branch?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Order date</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800">{{ date(order.order_date) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Expected delivery</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800">{{ date(order.expected_delivery_date) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Payment terms</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800">{{ label(order.payment_terms) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Fulfillment</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800">{{ label(order.fulfillment_method) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800">{{ [order.created_by?.user?.fname, order.created_by?.user?.lname].filter(Boolean).join(' ') || '—' }}</dd>
                            </div>
                        </dl>
                    </template>
                </Card>
            </div>

            <Card class="print-card overflow-hidden rounded-2xl border border-slate-200 shadow-sm print:break-inside-avoid">
                <template #title>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-base">Purchase Items</span>
                        <span class="text-xs font-normal text-slate-500">{{ order.items?.length || 0 }} line(s)</span>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="order.items || []" size="small" rowHover responsiveLayout="scroll">
                        <Column header="Product" style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="font-semibold text-slate-900">{{ data.product?.product_name || 'Product unavailable' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">SKU: {{ data.variation?.variation_sku || data.product?.sku || '—' }}</div>
                            </template>
                        </Column>
                        <Column header="Variant" style="min-width: 9rem">
                            <template #body="{ data }">{{ data.variation?.variation_name || 'Standard' }}</template>
                        </Column>
                        <Column header="Qty ordered" field="quantity_ordered" />
                        <Column header="Qty received" field="quantity_received" />
                        <Column header="Unit">
                            <template #body="{ data }">{{ data.product?.unit_of_measurement || '—' }}</template>
                        </Column>
                        <Column header="Unit cost">
                            <template #body="{ data }">{{ money(data.unit_cost) }}</template>
                        </Column>
                        <Column header="Line total">
                            <template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(data.line_total) }}</span></template>
                        </Column>
                        <template #empty>
                            <div class="py-8 text-center text-sm text-slate-500">No purchase items were found.</div>
                        </template>
                    </DataTable>
                </template>
            </Card>

            <Card v-if="order.goods_receipts?.length" class="print-card overflow-hidden rounded-2xl border border-slate-200 shadow-sm print:break-inside-avoid">
                <template #title><span class="text-base">Goods Receipts</span></template>
                <template #content>
                    <div class="divide-y divide-slate-100">
                        <a
                            v-for="receipt in order.goods_receipts"
                            :key="receipt.id"
                            :href="`/inventory/goods-receipts/${receipt.id}`"
                            class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm hover:text-orange-700"
                        >
                            <span class="font-semibold text-slate-900">{{ receipt.grn_number }}</span>
                            <span class="flex items-center gap-2 text-slate-500">
                                {{ date(receipt.receipt_date) }}
                                <Badge :value="label(receipt.receipt_status)" :severity="severity(receipt.receipt_status)" />
                            </span>
                        </a>
                    </div>
                </template>
            </Card>

            <div class="grid gap-4 print:grid-cols-2 lg:grid-cols-5 print:break-inside-avoid">
                <Card class="rounded-2xl border border-slate-200 shadow-sm lg:col-span-3">
                    <template #title><span class="text-base">Notes / Special Instructions</span></template>
                    <template #content>
                        <p class="min-h-12 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ order.notes || 'No notes added.' }}</p>
                    </template>
                </Card>
                <Card class="rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
                    <template #title><span class="text-base">Order Summary</span></template>
                    <template #content>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4 text-slate-600"><dt>Subtotal</dt><dd class="font-medium text-slate-900">{{ money(order.subtotal) }}</dd></div>
                            <div v-if="Number(order.discount_amount) > 0" class="flex justify-between gap-4 text-slate-600"><dt>Discount</dt><dd class="font-medium text-slate-900">-{{ money(order.discount_amount) }}</dd></div>
                            <div class="flex justify-between gap-4 text-slate-600"><dt>Tax</dt><dd class="font-medium text-slate-900">{{ money(order.tax_amount) }}</dd></div>
                            <div v-if="Number(order.shipping_cost) > 0" class="flex justify-between gap-4 text-slate-600"><dt>Shipping</dt><dd class="font-medium text-slate-900">{{ money(order.shipping_cost) }}</dd></div>
                            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 text-base font-bold text-slate-900"><dt>Total</dt><dd>{{ money(order.total_amount) }}</dd></div>
                        </dl>
                    </template>
                </Card>
            </div>

            <div class="screen-only flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                <Button
                    v-if="order.status === 'draft' && canManage"
                    label="Submit PO"
                    icon="pi pi-send"
                    size="small"
                    :loading="sending"
                    @click="submitOrder"
                />
                <Button
                    v-if="canReceive"
                    label="Create Goods Receipt"
                    icon="pi pi-inbox"
                    severity="success"
                    size="small"
                    @click="router.visit(`/inventory/goods-receipts/create?po_id=${order.id}`)"
                />
            </div>
        </template>
    </div>
</template>

<style>
.print-header {
    display: none;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    body * {
        visibility: hidden !important;
    }

    .po-detail-page,
    .po-detail-page * {
        visibility: visible !important;
    }

    .po-detail-page {
        position: fixed !important;
        inset: 0 auto auto 0 !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #111827 !important;
        background: #fff !important;
        font-size: 10pt !important;
    }

    .print-header {
        display: block !important;
        margin-bottom: 18px;
    }

    .page-toolbar,
    .screen-only {
        display: none !important;
    }

    .po-detail-page .p-card,
    .po-detail-page .print-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        box-shadow: none !important;
        break-inside: avoid;
    }

    .po-detail-page .p-card-body,
    .po-detail-page .p-card-content {
        padding: 12px !important;
    }

    .po-detail-page .p-datatable-wrapper {
        overflow: visible !important;
    }

    .po-detail-page .p-datatable-table {
        width: 100% !important;
        min-width: 0 !important;
        font-size: 8.5pt !important;
    }

    .po-detail-page .p-datatable-thead > tr > th,
    .po-detail-page .p-datatable-tbody > tr > td {
        padding: 6px !important;
        color: #111827 !important;
        border-color: #cbd5e1 !important;
        background: #fff !important;
    }

    .po-detail-page a {
        color: #111827 !important;
        text-decoration: none !important;
    }
}
</style>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axiosClient from "@/axios";
import { useAuthStore } from "@/stores/auth";
import Button from "primevue/button";
import Card from "primevue/card";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Skeleton from "primevue/skeleton";
import Badge from "primevue/badge";

const page = usePage();
const orderId = computed(() =>
    page.url.split("?")[0].split("/").filter(Boolean).pop(),
);
const auth = useAuthStore();
const canManage = computed(() =>
    auth.hasPermission("inventory.purchase_orders.manage"),
);
const canReceive = computed(
    () =>
        canManage.value &&
        order.value &&
        [
            "pending_receipt",
            "partially_received",
            "sent_to_supplier",
            "supplier_accepted",
            "in_transit",
            "delivered",
        ].includes(order.value.status) &&
        (order.value.items || []).some(
            (item: any) =>
                Number(item.quantity_ordered || 0) >
                Number(item.quantity_received || 0) +
                    Number(item.quantity_rejected || 0),
        ),
);
const loading = ref(true);
const sending = ref(false);
const printing = ref(false);
const error = ref("");
const order = ref<any>(null);
function label(value: string) {
    return value
        ? String(value)
              .replace(/_/g, " ")
              .replace(/\b\w/g, (char) => char.toUpperCase())
        : "—";
}
function severity(value: string) {
    return ["goods_received", "delivered"].includes(value)
        ? "success"
        : ["draft", "cancelled"].includes(value)
          ? "secondary"
          : value === "partially_received"
            ? "warn"
            : ["rejected", "declined_supplier"].includes(value)
              ? "danger"
              : ["pending_receipt", "pending", "awaiting_approval"].includes(value)
                ? "warn"
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
              month: "long",
              day: "numeric",
          })
        : "—";
}
async function windowPrint() {
    if (printing.value) return;
    printing.value = true;
    try {
        const response = await axiosClient.get(`/api/inventory/purchase-orders/${orderId.value}/print`, { responseType: "blob" });
        const url = URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }));
        const printWindow = window.open(url, "_blank");
        if (printWindow) printWindow.opener = null;
        setTimeout(() => URL.revokeObjectURL(url), 60_000);
    } catch (cause: any) {
        error.value = cause?.response?.data?.message || "Unable to create the purchase order PDF.";
    } finally {
        printing.value = false;
    }
}
async function load() {
    loading.value = true;
    error.value = "";
    try {
        const response = await axiosClient.get(
            `/api/inventory/purchase-orders/${orderId.value}`,
        );
        order.value = response.data.data;
    } catch (cause: any) {
        error.value =
            cause?.response?.data?.message ||
            "Unable to load this purchase order.";
    } finally {
        loading.value = false;
    }
}
async function submitOrder() {
    if (!window.confirm("Submit this purchase order for receiving?")) return;
    sending.value = true;
    error.value = "";
    try {
        await axiosClient.post(
            `/api/inventory/purchase-orders/${orderId.value}/send`,
        );
        await load();
    } catch (cause: any) {
        error.value =
            cause?.response?.data?.message ||
            "Unable to submit the purchase order.";
    } finally {
        sending.value = false;
    }
}
onMounted(load);
</script>
