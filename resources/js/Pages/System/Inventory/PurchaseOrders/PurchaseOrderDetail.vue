<template>
    <div class="mx-auto max-w-6xl space-y-5 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <Button
                    label="Purchase Orders"
                    icon="pi pi-arrow-left"
                    text
                    severity="secondary"
                    class="!pl-0"
                    @click="router.visit('/inventory/purchase-orders')"
                />
                <h1 class="text-xl font-bold text-slate-800">
                    {{ order?.po_number || "Purchase Order" }}
                </h1>
                <p class="text-sm text-slate-500">
                    Supplier purchase order details
                </p>
            </div>
            <div v-if="order" class="flex flex-wrap items-center gap-2">
                <Tag
                    :value="label(order.status)"
                    :severity="severity(order.status)"
                /><Button
                    label="Print"
                    icon="pi pi-print"
                    outlined
                    severity="secondary"
                    @click="windowPrint"
                />
            </div>
        </div>
        <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {{ error }}
        </div>
        <div v-if="loading" class="space-y-3">
            <Skeleton height="10rem" /><Skeleton height="14rem" />
        </div>
        <template v-else-if="order">
            <div
                v-if="canReceive"
                class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800"
            >
                Create a goods receipt when products arrive. Stock increases
                only after the receipt is completed.
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <Card
                    ><template #title>Supplier</template
                    ><template #content
                        ><div class="space-y-2 text-sm">
                            <p class="font-semibold text-slate-900">
                                {{ order.supplier?.supplier_name || "—" }}
                            </p>
                            <p>{{ order.supplier?.supplier_code || "—" }}</p>
                            <p v-if="order.supplier?.contact_person">
                                Contact: {{ order.supplier.contact_person }}
                            </p>
                            <p v-if="order.supplier?.phone">
                                Phone: {{ order.supplier.phone }}
                            </p>
                            <p v-if="order.supplier?.email">
                                Email: {{ order.supplier.email }}
                            </p>
                        </div></template
                    ></Card
                >
                <Card
                    ><template #title>Order information</template
                    ><template #content
                        ><dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Branch</dt>
                                <dd class="font-medium">
                                    {{ order.branch?.name || "—" }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Order date</dt>
                                <dd class="font-medium">
                                    {{ date(order.order_date) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">
                                    Expected delivery
                                </dt>
                                <dd class="font-medium">
                                    {{ date(order.expected_delivery_date) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Payment terms</dt>
                                <dd class="font-medium">
                                    {{ label(order.payment_terms) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Fulfillment</dt>
                                <dd class="font-medium">
                                    {{ label(order.fulfillment_method) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Created by</dt>
                                <dd class="font-medium">
                                    {{
                                        [
                                            order.created_by?.user?.fname,
                                            order.created_by?.user?.lname,
                                        ]
                                            .filter(Boolean)
                                            .join(" ") || "—"
                                    }}
                                </dd>
                            </div>
                        </dl></template
                    ></Card
                >
            </div>
            <Card
                ><template #title>Ordered products</template
                ><template #content>
                    <DataTable
                        :value="order.items || []"
                        size="small"
                        scrollable
                    >
                        <Column header="Product"
                            ><template #body="{ data }"
                                ><div class="font-medium">
                                    {{
                                        data.product?.product_name ||
                                        "Product unavailable"
                                    }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ data.product?.sku || "—" }}
                                </div></template
                            ></Column
                        >
                        <Column header="Variation"
                            ><template #body="{ data }">{{
                                data.variation?.variation_name || "—"
                            }}</template></Column
                        >
                        <Column header="Qty ordered" field="quantity_ordered" />
                        <Column header="Unit"
                            ><template #body="{ data }">{{
                                data.product?.unit_of_measurement || "—"
                            }}</template></Column
                        >
                        <Column
                            header="Qty received"
                            field="quantity_received"
                        />
                        <Column header="Unit cost"
                            ><template #body="{ data }">{{
                                money(data.unit_cost)
                            }}</template></Column
                        >
                        <Column header="Line total"
                            ><template #body="{ data }"
                                ><span class="font-medium">{{
                                    money(data.line_total)
                                }}</span></template
                            ></Column
                        >
                    </DataTable>
                </template></Card
            >
            <Card v-if="order.goods_receipts?.length"
                ><template #title>Goods Receipts</template
                ><template #content
                    ><div class="space-y-2">
                        <a
                            v-for="receipt in order.goods_receipts"
                            :key="receipt.id"
                            :href="`/inventory/goods-receipts/${receipt.id}`"
                            class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50"
                            ><span class="font-semibold text-blue-700">{{
                                receipt.grn_number
                            }}</span
                            ><span class="text-slate-500"
                                >{{ date(receipt.receipt_date) }} ·
                                {{ label(receipt.receipt_status) }}</span
                            ></a
                        >
                    </div></template
                ></Card
            >
            <div class="grid gap-4 md:grid-cols-2">
                <Card
                    ><template #title>Notes</template
                    ><template #content
                        ><p class="whitespace-pre-wrap text-sm text-slate-600">
                            {{ order.notes || "No notes added." }}
                        </p></template
                    ></Card
                ><Card
                    ><template #title>Order total</template
                    ><template #content
                        ><dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt>Subtotal</dt>
                                <dd>{{ money(order.subtotal) }}</dd>
                            </div>
                            <div
                                v-if="Number(order.discount_amount) > 0"
                                class="flex justify-between"
                            >
                                <dt>Discount</dt>
                                <dd>-{{ money(order.discount_amount) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Tax</dt>
                                <dd>{{ money(order.tax_amount) }}</dd>
                            </div>
                            <div
                                v-if="Number(order.shipping_cost) > 0"
                                class="flex justify-between"
                            >
                                <dt>Shipping</dt>
                                <dd>{{ money(order.shipping_cost) }}</dd>
                            </div>
                            <div
                                class="flex justify-between border-t pt-3 text-base font-bold"
                            >
                                <dt>Total</dt>
                                <dd>{{ money(order.total_amount) }}</dd>
                            </div>
                        </dl></template
                    ></Card
                >
            </div>
            <div class="flex justify-end gap-2">
                <Button
                    v-if="order.status === 'draft' && canManage"
                    label="Submit PO"
                    :loading="sending"
                    @click="submitOrder"
                />
                <Button
                    v-if="canReceive"
                    label="Create Goods Receipt"
                    severity="success"
                    @click="
                        router.visit(
                            `/inventory/goods-receipts/create?po_id=${order.id}`,
                        )
                    "
                />
            </div>
        </template>
    </div>
</template>

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
import Tag from "primevue/tag";

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
        : value === "draft"
          ? "secondary"
          : value === "partially_received"
            ? "warn"
            : value === "cancelled" || value === "declined_supplier"
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
              month: "long",
              day: "numeric",
          })
        : "—";
}
function windowPrint() {
    window.print();
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
