<template>
    <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
        <Toast />
        <ConfirmDialog />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-chevron-left"
                    text
                    rounded
                    @click="router.push({ name: 'finance.liquidations' })"
                />
                <div>
                    <h1
                        class="text-2xl font-semibold tracking-tight text-gray-900"
                    >
                        Cash Advance & Liquidation
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ detail?.advance_number || "Loading..." }}
                    </p>
                </div>
            </div>
            <div v-if="detail" class="flex flex-wrap items-center gap-2">
                <Button
                    v-if="
                        canApprove &&
                        ['pending_approval', 'approved'].includes(detail.status)
                    "
                    label="Reject"
                    icon="pi pi-times"
                    outlined
                    size="small"
                    @click="rejectVisible = true"
                />
                <Button
                    v-if="canApprove && detail.status === 'pending_approval'"
                    label="Approve"
                    icon="pi pi-check"
                    size="small"
                    @click="confirmAction('approve')"
                />
                <Button
                    v-if="canApprove && detail.status === 'approved'"
                    label="Release Funds"
                    icon="pi pi-wallet"
                    size="small"
                    @click="confirmAction('release')"
                />
                <Button
                    v-if="
                        canApprove && detail.status === 'liquidation_submitted'
                    "
                    label="Settle Liquidation"
                    icon="pi pi-verified"
                    size="small"
                    @click="confirmAction('settle')"
                />
                <Badge
                    :value="label(detail.status)"
                    :severity="severity(detail.status)"
                />
            </div>
        </div>

        <div v-if="loading" class="space-y-4">
            <Skeleton height="8rem" />
            <Skeleton height="24rem" />
        </div>
        <template v-else-if="detail">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="card in cards"
                    :key="card.label"
                    class="rounded-2xl border border-gray-100 shadow-sm"
                    ><template #content>
                        <div class="p-5">
                            <span
                                class="text-xs font-medium uppercase tracking-wider text-gray-500"
                                >{{ card.label }}</span
                            >
                            <p class="mt-3 text-xl font-semibold text-gray-900">
                                {{ card.value }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ card.help }}
                            </p>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <Card class="rounded-2xl border border-gray-100 shadow-sm"
                        ><template #title
                            ><span class="text-base"
                                >Advance Request</span
                            ></template
                        ><template #content>
                            <div class="grid gap-4 text-sm sm:grid-cols-2 p-1">
                                <Info
                                    label="Purpose"
                                    :value="detail.purpose"
                                    class="sm:col-span-2"
                                />
                                <Info
                                    label="Requested By"
                                    :value="person(detail.requester)"
                                />
                                <Info
                                    label="Branch"
                                    :value="detail.branch?.name || '—'"
                                />
                                <Info
                                    label="Needed Date"
                                    :value="date(detail.needed_date)"
                                />
                                <Info
                                    label="Payment Method"
                                    :value="label(detail.payment_method)"
                                />
                                <Info
                                    label="Requested On"
                                    :value="dateTime(detail.created_at)"
                                />
                                <Info
                                    label="Notes"
                                    :value="detail.notes || '—'"
                                    class="sm:col-span-2"
                                />
                            </div> </template
                    ></Card>

                    <Card
                        v-if="detail.items?.length"
                        class="rounded-2xl border border-gray-100 shadow-sm"
                        ><template #title
                            ><span class="text-base"
                                >Liquidation Items</span
                            ></template
                        ><template #content>
                            <DataTable
                                :value="detail.items"
                                rowHover
                                responsiveLayout="scroll"
                                class="p-datatable-sm text-xs"
                            >
                                <Column header="Date"
                                    ><template #body="{ data }">{{
                                        date(data.expense_date)
                                    }}</template></Column
                                >
                                <Column field="category" header="Category" />
                                <Column
                                    field="description"
                                    header="Description"
                                    style="min-width: 220px"
                                />
                                <Column header="Amount"
                                    ><template #body="{ data }"
                                        ><span class="font-semibold">{{
                                            money(data.amount)
                                        }}</span></template
                                    ></Column
                                >
                                <Column header="Receipt"
                                    ><template #body="{ data }"
                                        ><a
                                            v-if="data.receipt_url"
                                            :href="data.receipt_url"
                                            target="_blank"
                                            class="text-blue-600 hover:underline"
                                            ><i class="pi pi-paperclip mr-1"></i
                                            >{{
                                                data.receipt_name ||
                                                "View receipt"
                                            }}</a
                                        ><span v-else>—</span></template
                                    >
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <Card
                        v-if="detail.status === 'released' && canManage"
                        class="rounded-2xl border border-gray-100 shadow-sm"
                    >
                        <template #title>
                            <div class="flex items-center justify-between">
                                <span class="text-base">Submit Liquidation</span
                                ><Button
                                    label="Add Expense"
                                    icon="pi pi-plus"
                                    outlined
                                    size="small"
                                    @click="addItem"
                                />
                            </div> </template
                        ><template #content>
                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in items"
                                    :key="item.key"
                                    class="grid gap-3 border-b border-gray-100 pb-4 md:grid-cols-12"
                                >
                                    <div class="md:col-span-2">
                                        <label
                                            class="mb-1 block text-xs text-gray-500"
                                            >Date</label
                                        >
                                        <DatePicker
                                            v-model="item.expense_date"
                                            fluid
                                            size="small"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="mb-1 block text-xs text-gray-500"
                                            >Category</label
                                        >
                                        <InputText
                                            v-model="item.category"
                                            fluid
                                            size="small"
                                        />
                                    </div>
                                    <div class="md:col-span-3">
                                        <label
                                            class="mb-1 block text-xs text-gray-500"
                                            >Description</label
                                        >
                                        <InputText
                                            v-model="item.description"
                                            fluid
                                            size="small"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="mb-1 block text-xs text-gray-500"
                                            >Amount</label
                                        >
                                        <InputNumber
                                            v-model="item.amount"
                                            mode="currency"
                                            currency="PHP"
                                            locale="en-PH"
                                            :min="0.01"
                                            fluid
                                            size="small"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="mb-1 block text-xs text-gray-500"
                                            >Receipt</label
                                        >
                                        <FileUpload
                                            mode="basic"
                                            chooseLabel="Choose"
                                            accept="image/*,.pdf"
                                            :maxFileSize="5000000"
                                            customUpload
                                            @select="
                                                (event) =>
                                                    selectReceipt(index, event)
                                            "
                                        />
                                    </div>
                                    <div
                                        class="flex items-end justify-end md:col-span-1"
                                    >
                                        <Button
                                            icon="pi pi-trash"
                                            text
                                            rounded
                                            size="small"
                                            :disabled="items.length === 1"
                                            @click="removeItem(index)"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="flex justify-between border-t border-gray-200 pt-4"
                                >
                                    <span class="font-medium text-gray-600"
                                        >Total Expenses</span
                                    ><span class="text-lg font-semibold">{{
                                        money(itemTotal)
                                    }}</span>
                                </div>
                                <Textarea
                                    v-model="submissionNotes"
                                    rows="3"
                                    fluid
                                    placeholder="Optional liquidation notes"
                                />
                                <div class="flex justify-end">
                                    <Button
                                        label="Submit Liquidation"
                                        icon="pi pi-send"
                                        :loading="working"
                                        @click="confirmAction('submit')"
                                    />
                                </div>
                            </div> </template
                    ></Card>
                </div>

                <div class="space-y-6">
                    <Card class="rounded-2xl border border-gray-100 shadow-sm"
                        ><template #title
                            ><span class="text-base"
                                >Settlement Summary</span
                            ></template
                        ><template #content>
                            <div class="space-y-3 text-sm">
                                <Line
                                    label="Advance Released"
                                    :value="money(detail.advance_amount)"
                                />
                                <Line
                                    label="Expenses Liquidated"
                                    :value="money(detail.liquidated_amount)"
                                />
                                <Line
                                    label="Cash to Return"
                                    :value="money(detail.cash_returned)"
                                />
                                <Line
                                    label="Reimbursement Due"
                                    :value="money(detail.reimbursement_amount)"
                                />
                                <div class="border-t border-gray-200 pt-3">
                                    <Line
                                        label="Current Status"
                                        :value="label(detail.status)"
                                        strong
                                    />
                                </div>
                            </div> </template
                    ></Card>
                    <Card class="rounded-2xl border border-gray-100 shadow-sm"
                        ><template #title
                            ><span class="text-base">Activity</span></template
                        ><template #content>
                            <div class="space-y-4 text-sm">
                                <Activity
                                    label="Requested"
                                    :person="person(detail.requester)"
                                    :time="detail.created_at"
                                />
                                <Activity
                                    v-if="detail.approved_at"
                                    label="Approved"
                                    :person="person(detail.approver)"
                                    :time="detail.approved_at"
                                />
                                <Activity
                                    v-if="detail.released_at"
                                    label="Funds Released"
                                    :person="person(detail.releaser)"
                                    :time="detail.released_at"
                                />
                                <Activity
                                    v-if="detail.submitted_at"
                                    label="Liquidation Submitted"
                                    :person="person(detail.submitter)"
                                    :time="detail.submitted_at"
                                />
                                <Activity
                                    v-if="detail.settled_at"
                                    label="Settled"
                                    :person="person(detail.settler)"
                                    :time="detail.settled_at"
                                />
                                <p
                                    v-if="detail.review_notes"
                                    class="border-t pt-3 text-gray-600"
                                >
                                    {{ detail.review_notes }}
                                </p>
                            </div>
                        </template></Card
                    >
                </div>
            </div>
        </template>

        <Dialog
            v-model:visible="rejectVisible"
            header="Reject Cash Advance"
            modal
            :style="{ width: '440px' }"
        >
            <div class="space-y-2">
                <label class="text-sm text-gray-600">Reason *</label
                ><Textarea v-model="rejectReason" rows="4" fluid autoResize />
            </div>
            <template #footer
                ><Button
                    label="Cancel"
                    outlined
                    @click="rejectVisible = false" /><Button
                    label="Reject"
                    :disabled="!rejectReason.trim()"
                    :loading="working"
                    @click="confirmAction('reject')"
            /></template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { useAuthStore } from "@/stores/auth";
import financeService from "@/services/finance.service";
import Button from "primevue/button";
import Card from "primevue/card";
import Badge from "primevue/badge";
import Skeleton from "primevue/skeleton";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import DatePicker from "primevue/datepicker";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import FileUpload from "primevue/fileupload";
import Dialog from "primevue/dialog";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
const Info = defineComponent({
    props: { label: String, value: String },
    setup: (p) => () =>
        h("div", [
            h(
                "p",
                {
                    class: "text-xs font-medium uppercase tracking-wide text-gray-500",
                },
                p.label,
            ),
            h("p", { class: "mt-1 font-medium text-gray-900" }, p.value || "—"),
        ]),
});
const Line = defineComponent({
    props: { label: String, value: String, strong: Boolean },
    setup: (p) => () =>
        h("div", { class: "flex justify-between gap-3" }, [
            h("span", { class: "text-gray-500" }, p.label),
            h(
                "span",
                {
                    class: p.strong
                        ? "font-semibold text-gray-900"
                        : "font-medium text-gray-900",
                },
                p.value,
            ),
        ]),
});
const Activity = defineComponent({
    props: { label: String, person: String, time: String },
    setup: (p) => () =>
        h("div", { class: "border-l-2 border-orange-300 pl-3" }, [
            h("p", { class: "font-medium text-gray-900" }, p.label),
            h(
                "p",
                { class: "text-xs text-gray-500" },
                `${p.person || "—"} · ${p.time ? new Date(p.time).toLocaleString("en-PH") : "—"}`,
            ),
        ]),
});
const route = useRoute(),
    router = useRouter(),
    toast = useToast(),
    confirm = useConfirm(),
    auth = useAuthStore();
const loading = ref(false),
    working = ref(false),
    detail = ref<any>(null),
    rejectVisible = ref(false),
    rejectReason = ref(""),
    submissionNotes = ref("");
const canApprove = computed(() =>
        auth.hasPermission("finance.liquidations.approve"),
    ),
    canManage = computed(() =>
        auth.hasPermission("finance.liquidations.manage"),
    );
type Item = {
    key: number;
    expense_date: Date | null;
    category: string;
    description: string;
    amount: number | null;
    receipt: File | null;
};
let key = 1;
const newItem = (): Item => ({
    key: key++,
    expense_date: new Date(),
    category: "",
    description: "",
    amount: null,
    receipt: null,
});
const items = reactive<Item[]>([newItem()]);
const addItem = () => items.push(newItem());
const removeItem = (i: number) => items.splice(i, 1);
const selectReceipt = (i: number, e: any) => {
    items[i].receipt = e.files?.[0] || null;
};
const itemTotal = computed(() =>
    items.reduce((s, x) => s + Number(x.amount || 0), 0),
);
const money = (v: any) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(v || 0));
const date = (v: any) =>
    v
        ? new Date(v).toLocaleDateString("en-PH", {
              month: "short",
              day: "numeric",
              year: "numeric",
          })
        : "—";
const dateTime = (v: any) => (v ? new Date(v).toLocaleString("en-PH") : "—");
const label = (v: any) =>
    String(v || "—")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
const person = (u: any) =>
    u ? [u.fname, u.lname].filter(Boolean).join(" ") : "—";
const severity = (s: string) =>
    s === "settled"
        ? "success"
        : s === "rejected"
          ? "danger"
          : s === "liquidation_submitted"
            ? "info"
            : ["approved", "released"].includes(s)
              ? "secondary"
              : "warn";
const cards = computed(() => [
    {
        label: "Advance Number",
        value: detail.value.advance_number,
        help: `Created ${date(detail.value.created_at)}`,
    },
    {
        label: "Advance Amount",
        value: money(detail.value.advance_amount),
        help: "Funds requested",
    },
    {
        label: "Liquidated Amount",
        value: money(detail.value.liquidated_amount),
        help: `${detail.value.items?.length || 0} expense item(s)`,
    },
    {
        label: "Balance",
        value: money(
            Number(detail.value.advance_amount) -
                Number(detail.value.liquidated_amount),
        ),
        help: "Positive means cash to return",
    },
]);
const load = async () => {
    loading.value = true;
    try {
        const r = await financeService.getLiquidation(String(route.params.id));
        detail.value = r.data;
    } catch (e: any) {
        toast.add({
            severity: "error",
            summary: "Load Failed",
            detail: e?.response?.data?.message || "Unable to load liquidation.",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};
const apiDate = (d: Date) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
const confirmAction = (
    action: "approve" | "reject" | "release" | "submit" | "settle",
) => {
    if (
        action === "submit" &&
        items.some(
            (x) =>
                !x.expense_date ||
                !x.category.trim() ||
                !x.description.trim() ||
                !x.amount,
        )
    ) {
        toast.add({
            severity: "warn",
            summary: "Incomplete Items",
            detail: "Complete every liquidation item.",
            life: 2500,
        });
        return;
    }
    const messages: any = {
        approve: "Approve this cash advance request?",
        reject: "Reject this cash advance request?",
        release: "Release the advance and deduct it from cashflow?",
        submit: "Submit these expenses for Finance settlement?",
        settle: "Settle this liquidation and post the balance to cashflow?",
    };
    confirm.require({
        header: "Confirm Action",
        message: messages[action],
        rejectProps: { label: "Cancel", outlined: true },
        acceptProps: { label: "Confirm" },
        accept: () => runAction(action),
    });
};
const runAction = async (action: string) => {
    working.value = true;
    try {
        let r: any;
        if (action === "approve")
            r = await financeService.approveLiquidation(detail.value.id);
        if (action === "reject")
            r = await financeService.rejectLiquidation(detail.value.id, {
                reason: rejectReason.value,
            });
        if (action === "release")
            r = await financeService.releaseLiquidation(detail.value.id);
        if (action === "settle")
            r = await financeService.settleLiquidation(detail.value.id);
        if (action === "submit") {
            const fd = new FormData();
            items.forEach((x, i) => {
                fd.append(
                    `items[${i}][expense_date]`,
                    apiDate(x.expense_date!),
                );
                fd.append(`items[${i}][category]`, x.category);
                fd.append(`items[${i}][description]`, x.description);
                fd.append(`items[${i}][amount]`, String(x.amount));
                if (x.receipt) fd.append(`items[${i}][receipt]`, x.receipt);
            });
            if (submissionNotes.value)
                fd.append("notes", submissionNotes.value);
            r = await financeService.submitLiquidation(detail.value.id, fd);
        }
        toast.add({
            severity: "success",
            summary: "Updated",
            detail: r?.message || "Action completed.",
            life: 2500,
        });
        rejectVisible.value = false;
        await load();
    } catch (e: any) {
        toast.add({
            severity: "error",
            summary: "Action Failed",
            detail:
                e?.response?.data?.message ||
                Object.values(e?.response?.data?.errors || {})[0] ||
                "Unable to complete action.",
            life: 3500,
        });
    } finally {
        working.value = false;
    }
};
onMounted(load);
</script>
