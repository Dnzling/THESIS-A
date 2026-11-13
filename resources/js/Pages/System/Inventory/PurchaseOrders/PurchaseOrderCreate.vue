<template>
    <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8">
        <ConfirmDialog />
        <div class="flex items-center gap-3 px-5 py-4 shadow-sm">
            <Button
                icon="pi pi-arrow-left"
                text
                rounded
                severity="secondary"
                aria-label="Back to purchase orders"
                @click="router.visit('/inventory/stock-requests')"
            />
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-slate-900">
                    Create Purchase Order
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Review the assigned supplier, items, and
                    totals before submission.
                </p>
            </div>
        </div>
        <Message
            v-if="error"
            severity="error"
            :closable="false"
            class="text-sm"
            >{{ error }}</Message
        >
        <div
            v-if="requisitionPrefill"
            class="flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900"
        >
            <i class="pi pi-file-edit mt-0.5 text-orange-600" />
            <p>
                Creating an Inventory PO from approved request
                <strong>{{ requisitionPrefill.pr_number }}</strong>. Choose a contracted supplier that can provide every requested item; the item list and quantities are prefilled.
            </p>
        </div>
        <div
            v-if="reorderSuggestions.length"
            class="flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900"
        >
            <i class="pi pi-info-circle mt-0.5 text-orange-600" />
            <p>
                Creating one PO from {{ reorderSuggestions.length }} reorder
                suggestion{{ reorderSuggestions.length === 1 ? '' : 's' }}.
                Choose a supplier linked to every item, then review the order
                before submitting.
            </p>
        </div>
        <div
            v-else-if="forecastPrefill"
            class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900"
        >
            <i class="pi pi-chart-line mt-0.5 text-sky-600" />
            <p>
                Forecast restock prefilled for
                <strong>{{ forecastPrefill.product_name }}</strong>
                <span v-if="forecastPrefill.variant_name">({{ forecastPrefill.variant_name }})</span>:
                SKU {{ forecastPrefill.sku || '—' }} · {{ forecastPrefill.suggested_quantity }} suggested units;
                PO quantity prefilled to {{ forecastPrefill.po_quantity }} to meet the current 10-unit minimum.
                Review the supplier, quantity, and total before submitting.
            </p>
        </div>
        <div v-if="loading" class="space-y-4">
            <Skeleton height="8rem" /><Skeleton height="16rem" /><Skeleton
                height="12rem"
            />
        </div>
        <template v-else>
            <Card
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                ><template #content>
                    <form
                        class="space-y-8"
                        @submit.prevent="confirmSave('submit')"
                    >
                        <section class="border-b border-slate-200 pb-7">
                            <div class="mb-4">
                                <h3
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    Order Information
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Select the supplier and confirm the order's
                                    delivery and payment details.
                                </p>
                            </div>
                            <div
                                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold text-slate-600"
                                        >Supplier
                                        <span class="text-red-500">*</span></label
                                    ><Select
                                        v-model="form.supplier_id"
                                        :options="eligibleSuppliers"
                                        optionLabel="supplier_name"
                                        optionValue="id"
                                        filter
                                        placeholder="Select supplier"
                                        size="small"
                                        fluid
                                        @change="onSupplierChange"
                                    /><small
                                        v-if="fieldError('supplier_id')"
                                        class="text-red-600"
                                        >{{ fieldError('supplier_id') }}</small
                                    >
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold text-slate-600"
                                        >Expected delivery</label
                                    ><DatePicker
                                        v-model="form.expected_delivery_date"
                                        dateFormat="MM d, yy"
                                        showIcon
                                        :manualInput="false"
                                        :minDate="new Date()"
                                        size="small"
                                        fluid
                                        placeholder="Select a date"
                                    /><small
                                        v-if="
                                            fieldError('expected_delivery_date')
                                        "
                                        class="text-red-600"
                                        >{{
                                            fieldError('expected_delivery_date')
                                        }}</small
                                    >
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold text-slate-600"
                                        >Payment terms
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    ><Select
                                        v-model="form.payment_terms"
                                        :options="paymentTerms"
                                        optionLabel="label"
                                        optionValue="value"
                                        size="small"
                                        fluid
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold text-slate-600"
                                        >Fulfillment
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    ><Select
                                        v-model="form.fulfillment_method"
                                        :options="fulfillmentMethods"
                                        optionLabel="label"
                                        optionValue="value"
                                        size="small"
                                        fluid
                                    />
                                </div>
                            </div>
                            <div
                                v-if="selectedSupplier"
                                class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <div class="mb-3 flex items-center gap-2">
                                    <i class="pi pi-building text-slate-500" />
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                            Selected supplier
                                        </p>
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ selectedSupplier.supplier_name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="grid gap-3 border-t border-slate-200 pt-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="flex min-w-0 items-start gap-2">
                                        <i class="pi pi-user mt-0.5 text-xs text-slate-400" />
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Contact person</p>
                                            <p class="mt-1 truncate text-sm text-slate-800">{{ selectedSupplier.contact_person || '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex min-w-0 items-start gap-2">
                                        <i class="pi pi-phone mt-0.5 text-xs text-slate-400" />
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Contact number</p>
                                            <p class="mt-1 truncate text-sm text-slate-800">{{ selectedSupplier.phone || '-' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex min-w-0 items-start gap-2">
                                        <i class="pi pi-envelope mt-0.5 text-xs text-slate-400" />
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Email</p>
                                            <p class="mt-1 break-all text-sm text-slate-800">{{ selectedSupplier.email || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="border-b border-slate-200 pb-7">
                            <div
                                class="mb-4 flex items-start justify-between gap-4"
                            >
                                <div>
                                    <h3
                                        class="text-lg font-semibold text-slate-900"
                                    >
                                        Purchase Items
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Verify the ordered quantity and
                                        supplier-linked unit cost for each item.
                                    </p>
                                </div>
                                <Button
                                    v-if="form.supplier_id && !reorderSuggestions.length && !forecastPrefill && filteredProducts.length"
                                    label="Add Item"
                                    icon="pi pi-plus"
                                    size="small"
                                    @click="addItem"
                                />
                            </div>
                            <div
                                v-if="!form.supplier_id"
                                class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600"
                            >
                                <i
                                    class="pi pi-truck mb-2 block text-xl text-slate-400"
                                />{{ forecastPrefill ? 'Select an eligible supplier to load the prefilled forecast item and its supplier price.' : 'Select a supplier to load its linked items.' }}
                            </div>
                            <div
                                v-else-if="!filteredProducts.length"
                                class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800"
                            >
                                No active products, raw materials, or supplies
                                are linked to this supplier.
                                <a
                                    href="/inventory/products/create"
                                    class="underline"
                                    >Add or link an item</a
                                >.
                            </div>
                            <template v-else>
                            <div
                                v-for="(item, index) in form.items"
                                :key="item.key"
                                class="rounded-xl border border-slate-200 bg-slate-50/50 p-4"
                            >
                                <div
                                    class="mb-4 flex items-center justify-between"
                                >
                                    <span
                                        class="text-xs font-semibold uppercase tracking-wider text-slate-500"
                                        >Item {{ index + 1 }}</span
                                    ><Button
                                        v-if="!reorderSuggestions.length"
                                        icon="pi pi-trash"
                                        text
                                        severity="danger"
                                        size="small"
                                        :disabled="form.items.length === 1"
                                        aria-label="Remove item"
                                        @click="confirmRemoveItem(index)"
                                    />
                                </div>
                                <div
                                    class="grid grid-cols-1 items-start gap-4 md:grid-cols-12"
                                >
                                    <div class="md:col-span-5">
                                        <label
                                            class="mb-1 block text-sm font-medium"
                                            >Item *</label
                                        ><Select
                                            v-model="item.selection_key"
                                            :options="productOptionsFor(item)"
                                            optionLabel="name"
                                            optionValue="selection_key"
                                            filter
                                            placeholder="Choose product or variant"
                                            class="w-full"
                                            :disabled="!form.supplier_id"
                                            @change="
                                                onItemSelectionChange(item, index)
                                            "
                                            ><template #option="{ option }">
                                                <div class="flex min-w-0 flex-col gap-0.5">
                                                    <span class="truncate font-medium">{{ option.name }}</span>
                                                    <small class="truncate text-slate-500">
                                                        SKU: {{ option.sku || '—' }}
                                                        <span v-if="option.variant_label"> · Variant: {{ option.variant_label }}</span>
                                                        · Stock: {{ option.stock }}
                                                    </small>
                                                </div>
                                            </template>
                                            <template #value="{ placeholder: selectPlaceholder }">
                                                <div v-if="selectedItemOption(item)" class="flex min-w-0 flex-col gap-0.5 py-1">
                                                    <span class="truncate text-sm font-medium">{{ selectedItemOption(item)?.name }}</span>
                                                    <small class="truncate text-xs text-slate-500">
                                                        SKU: {{ selectedItemOption(item)?.sku || '—' }}
                                                        <span v-if="selectedItemOption(item)?.variant_label"> · Variant: {{ selectedItemOption(item)?.variant_label }}</span>
                                                        · Stock: {{ selectedItemOption(item)?.stock }}
                                                    </small>
                                                </div>
                                                <span v-else class="text-slate-400">{{ selectPlaceholder }}</span>
                                            </template></Select
                                        ><small
                                            v-if="
                                                fieldError(
                                                    `items.${index}.product_id`
                                                )
                                            "
                                            class="text-red-600"
                                            >{{
                                                fieldError(
                                                    `items.${index}.product_id`
                                                )
                                            }}</small
                                        >
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                                            >Quantity *</label
                                        ><InputNumber
                                            v-model="item.quantity_ordered"
                                            :min="0"
                                            :maxFractionDigits="0"
                                            size="small"
                                            fluid
                                            @blur="validateQuantity(index)"
                                        /><small
                                            v-if="
                                                fieldError(
                                                    `items.${index}.quantity_ordered`
                                                )
                                            "
                                            class="text-red-600"
                                            >{{
                                                fieldError(
                                                    `items.${index}.quantity_ordered`
                                                )
                                            }}</small
                                        >
                                    </div>
                                    <div class="md:col-span-1">
                                        <label
                                            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-600"
                                            >Unit</label
                                        >
                                        <p
                                            class="flex h-[3.25rem] items-center px-3 text-sm font-semibold text-slate-700"
                                        >
                                            {{ unitFor(item) }}
                                        </p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label
                                            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-600"
                                            >Unit Cost</label
                                        >
                                        <p
                                            class="flex h-[3.25rem] items-center whitespace-nowrap px-2 text-sm font-bold text-green-600"
                                        >
                                            {{ money(item.unit_cost) }}
                                        </p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div
                                            class="rounded-lg border border-slate-200 bg-white px-3 py-2"
                                        >
                                            <label
                                                class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-600"
                                                >Line Total</label
                                            >
                                            <div
                                                class="text-lg font-semibold text-slate-900"
                                            >
                                                {{
                                                    money(
                                                        Number(
                                                            item.quantity_ordered ||
                                                                0
                                                        ) *
                                                            Number(
                                                                item.unit_cost ||
                                                                    0
                                                            )
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small
                                v-if="fieldError('items')"
                                class="text-red-600"
                                >{{ fieldError('items') }}</small
                            >
                            </template>
                        </section>
                        <section
                            class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2"
                        >
                            <div
                                class="order-2 w-full max-w-md justify-self-end rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:order-2"
                            >
                                <h3
                                    class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-700"
                                >
                                    Cost Summary
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div
                                        class="flex items-center justify-between gap-6 text-slate-600"
                                    >
                                        <span>Items subtotal</span
                                        ><span
                                            class="font-medium text-slate-900"
                                            >{{ money(subtotal) }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between gap-6 text-slate-600"
                                    >
                                        <span
                                            >VAT
                                            <span class="text-xs text-slate-400"
                                                >({{
                                                    selectedSupplier?.is_tax_exempt
                                                        ? 'Exempt'
                                                        : `${taxRate}%`
                                                }})</span
                                            ></span
                                        ><span
                                            class="font-medium text-slate-900"
                                            >{{ money(tax) }}</span
                                        >
                                    </div>
                                    <div
                                        class="my-4 border-t border-slate-200"
                                    />
                                    <div
                                        class="flex items-center justify-between gap-4 rounded-xl bg-slate-900 px-4 py-3 text-white"
                                    >
                                        <div>
                                            <span class="block font-semibold"
                                                >Total payable</span
                                            ><span
                                                class="text-xs text-slate-300"
                                                >Includes VAT</span
                                            >
                                        </div>
                                        <span class="text-xl font-bold">{{
                                            money(total)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="order-1 lg:order-1">
                                <label
                                    for="po-notes"
                                    class="mb-2 block text-sm font-semibold text-slate-900"
                                    >Notes / Special Instructions</label
                                >
                                <p class="mb-3 text-sm text-slate-500">
                                    Add delivery instructions, packaging
                                    requirements, or other supplier notes.
                                </p>
                                <Textarea
                                    id="po-notes"
                                    v-model="form.notes"
                                    rows="4"
                                    auto-resize
                                    class="w-full"
                                    placeholder="Add any special instructions or notes for this purchase order..."
                                />
                            </div>
                        </section>
                        <div
                            class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-end"
                        >
                            <Button
                                label="Cancel"
                                severity="secondary"
                                text
                                type="button"
                                :disabled="!!saving"
                                @click="
                                    router.visit('/inventory/stock-requests')
                                "
                            />
                            <Button
                                label="Save as Draft"
                                severity="secondary"
                                outlined
                                type="button"
                                :loading="saving === 'draft'"
                                :disabled="!!saving"
                                @click="confirmSave('draft')"
                            />
                            <Button
                                label="Create & Submit"
                                type="submit"
                                severity="warn"
                                :loading="saving === 'submit'"
                                :disabled="!!saving"
                            />
                        </div>
                    </form> </template
            ></Card>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'

type OrderItem = {
    key: number
    product_id: number | null
    variation_id: number | null
    selection_key: string | null
    quantity_ordered: number
    unit_cost: number
}
const loading = ref(true)
const saving = ref<'draft' | 'submit' | null>(null)
const confirm = useConfirm()
const error = ref('')
const errors = ref<Record<string, string[]>>({})
const quantityErrors = ref<Record<number, string>>({})
const suppliers = ref<any[]>([])
const branches = ref<any[]>([])
const products = ref<any[]>([])
const reorderSuggestions = ref<any[]>([])
const forecastPrefill = ref<any | null>(null)
const requisitionPrefill = ref<any | null>(null)
const assignedBranchName = ref('')
let nextKey = 1
const dateAfterDays = (days: number) => {
    const date = new Date()
    date.setDate(date.getDate() + days)
    return date
}
const form = reactive({
    purchase_requisition_id: null as number | null,
    supplier_id: null as number | null,
    branch_id: null as number | null,
    expected_delivery_date: dateAfterDays(2),
    payment_terms: 'cash_on_delivery',
    fulfillment_method: 'supplier_delivery',
    notes: '',
    items: [] as OrderItem[]
})
const paymentTerms = [
    { label: 'Cash on delivery', value: 'cash_on_delivery' },
    { label: 'Net 7', value: 'net_7' },
    { label: 'Net 15', value: 'net_15' },
    { label: 'Net 30', value: 'net_30' },
    { label: 'Net 60', value: 'net_60' },
    { label: 'Advance payment', value: 'advance_payment' }
]
const fulfillmentMethods = [
    { label: 'Supplier delivery', value: 'supplier_delivery' },
    { label: 'Store pickup', value: 'store_pickup' }
]
const selectedSupplier = computed(() =>
    suppliers.value.find(s => s.id === form.supplier_id)
)
const eligibleSuppliers = computed(() => {
    if (requisitionPrefill.value) {
        const requestedProductIds = [...new Set((requisitionPrefill.value.items || []).map((item: any) => Number(item.product_id)))]
        const linkedIds = requestedProductIds.map(productId => {
            const product = products.value.find(item => Number(item.id) === productId)
            return new Set((product?.suppliers || []).map((supplier: any) => Number(supplier.id)))
        })
        if (!linkedIds.length) return []
        return suppliers.value.filter(supplier => linkedIds.every(ids => ids.has(Number(supplier.id))))
    }
    if (!reorderSuggestions.value.length && !forecastPrefill.value) return suppliers.value
    if (forecastPrefill.value) {
        const product = products.value.find(
            item => Number(item.id) === Number(forecastPrefill.value.product_id)
        )
        const linkedIds = (product?.suppliers || []).map((supplier: any) => Number(supplier.id))
        return suppliers.value.filter(supplier => linkedIds.includes(Number(supplier.id)))
    }
    const firstProduct = products.value.find(
        product =>
            Number(product.id) ===
            Number(reorderSuggestions.value[0].product_id)
    )
    const commonIds = (firstProduct?.suppliers || [])
        .map((supplier: any) => Number(supplier.id))
        .filter((supplierId: number) =>
            reorderSuggestions.value.every(suggestion =>
                products.value
                    .find(
                        product =>
                            Number(product.id) === Number(suggestion.product_id)
                    )
                    ?.suppliers?.some(
                        (supplier: any) => Number(supplier.id) === supplierId
                    )
            )
        )
    return suppliers.value.filter(supplier =>
        commonIds.includes(Number(supplier.id))
    )
})
const filteredProducts = computed(() =>
    products.value.filter(
        product =>
            product.suppliers?.some(
                (supplier: any) => supplier.id === form.supplier_id
            ) &&
            ['raw_material', 'finished_good', 'supply'].includes(
                product.product_type
            )
    )
)
function productOptionsFor(item: OrderItem) {
    const selected = products.value.find(
        product => Number(product.id) === Number(item.product_id)
    )
    const source = selected &&
        !filteredProducts.value.some(product => Number(product.id) === Number(selected.id))
        ? [selected, ...filteredProducts.value]
        : filteredProducts.value

    return source.flatMap((product: any) => {
        const stockFor = (variationId: number | null) => Number(
            product.inventory_points?.find((point: any) =>
                Number(point.branch_id) === Number(form.branch_id) &&
                Number(point.variation_id || 0) === Number(variationId || 0)
            )?.quantity_available || 0
        )
        const standard = {
            selection_key: `${product.id}:0`,
            product_id: Number(product.id),
            variation_id: null,
            name: product.name,
            sku: product.sku,
            variant_label: 'Standard',
            stock: stockFor(null),
            product_type: product.product_type
        }
        const variants = (product.variations || []).map((variation: any) => ({
            selection_key: `${product.id}:${variation.id}`,
            product_id: Number(product.id),
            variation_id: Number(variation.id),
            name: product.name,
            sku: variation.sku || product.sku,
            variant_label: variation.name,
            stock: stockFor(Number(variation.id)),
            product_type: product.product_type
        }))

        return [standard, ...variants]
    })
}
function selectedItemOption(item: OrderItem) {
    return productOptionsFor(item).find(
        (option: any) => option.selection_key === item.selection_key
    )
}
const taxRate = computed(() =>
    selectedSupplier.value?.is_tax_exempt
        ? 0
        : Number(selectedSupplier.value?.default_tax_rate ?? 12)
)
const subtotal = computed(() =>
    form.items.reduce(
        (sum, item) =>
            sum +
            Number(item.quantity_ordered || 0) * Number(item.unit_cost || 0),
        0
    )
)
const tax = computed(() => (subtotal.value * taxRate.value) / 100)
const total = computed(() => subtotal.value + tax.value)
function money(value: any) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(Number(value || 0))
}
function fieldError(field: string) {
    const index = /^items\.(\d+)\.quantity_ordered$/.exec(field)
    return (
        (index ? quantityErrors.value[Number(index[1])] : '') ||
        errors.value[field]?.[0] ||
        ''
    )
}
function addItem() {
    form.items.push({
        key: nextKey++,
        product_id: null,
        variation_id: null,
        selection_key: null,
        quantity_ordered: 10,
        unit_cost: 0
    })
}
function confirmRemoveItem(index: number) {
    if (form.items.length <= 1) return
    const item = form.items[index]
    confirm.require({
        header: 'Remove product?',
        message: `Remove ${
            productFor(item)?.name || 'this item'
        } from the purchase order?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Remove',
        acceptProps: { severity: 'danger' },
        rejectLabel: 'Keep item',
        rejectProps: { severity: 'secondary', outlined: true },
        accept: () => {
            const currentIndex = form.items.findIndex(
                row => row.key === item.key
            )
            if (currentIndex < 0 || form.items.length <= 1) return
            form.items.splice(currentIndex, 1)
            quantityErrors.value = {}
            errors.value = {}
        }
    })
}
function productFor(item: OrderItem) {
    return products.value.find(product => product.id === item.product_id)
}
function unitFor(item: OrderItem) {
    return productFor(item)?.unit || '—'
}
function orderPointFor(item: OrderItem) {
    const product = productFor(item)
    const branchId = Number(form.branch_id)
    const inventoryPoint = Number(
        product?.inventory_points?.find(
            (point: any) =>
                Number(point.branch_id) === branchId &&
                Number(point.variation_id || 0) ===
                    Number(item.variation_id || 0)
        )?.reorder_point || 0
    )
    const rulePoint = Number(
        product?.reorder_rules?.find(
            (rule: any) => Number(rule.branch_id) === branchId
        )?.reorder_point || 0
    )
    const basePoint = Number(
        product?.inventory_points?.find(
            (point: any) =>
                Number(point.branch_id) === branchId && !point.variation_id
        )?.reorder_point || 0
    )
    return Math.max(10, inventoryPoint || rulePoint || basePoint)
}
function validateQuantity(index: number) {
    const quantity = form.items[index]?.quantity_ordered
    if (quantity == null || !Number.isInteger(quantity) || quantity < 10)
        quantityErrors.value[index] = 'Quantity must be at least 10.'
    else delete quantityErrors.value[index]
    delete errors.value[`items.${index}.quantity_ordered`]
}
function onSupplierChange() {
    quantityErrors.value = {}
    errors.value = {}
    if (reorderSuggestions.value.length) {
        form.items.forEach(item => {
            item.unit_cost = Number(
                productFor(item)?.suppliers?.find(
                    (supplier: any) => supplier.id === form.supplier_id
                )?.unit_cost || 0
            )
        })
        return
    }
    if (forecastPrefill.value) {
        const item = form.items[0]
        if (!item) return
        item.unit_cost = Number(
            productFor(item)?.suppliers?.find(
                (supplier: any) => Number(supplier.id) === Number(form.supplier_id)
            )?.unit_cost || 0
        )
        return
    }
    if (requisitionPrefill.value) {
        const lines = (requisitionPrefill.value.items || []).map((requested: any) => {
            const product = products.value.find(item => Number(item.id) === Number(requested.product_id))
            const supplier = product?.suppliers?.find((item: any) => Number(item.id) === Number(form.supplier_id))
            return {
                key: nextKey++,
                product_id: Number(requested.product_id),
                variation_id: requested.variation_id ? Number(requested.variation_id) : null,
                selection_key: `${requested.product_id}:${requested.variation_id || 0}`,
                quantity_ordered: Math.max(10, Number(requested.quantity_requested || 0)),
                unit_cost: Number(supplier?.unit_cost || 0),
            }
        })
        form.items.splice(0, form.items.length, ...lines)
        return
    }
    form.items.forEach(item => {
        item.product_id = null
        item.variation_id = null
        item.selection_key = null
        item.quantity_ordered = 10
        item.unit_cost = 0
    })
}
function onBranchChange() {
    form.items.forEach((item, index) => {
        if (!item.product_id) return
        item.quantity_ordered = orderPointFor(item)
        validateQuantity(index)
    })
}
function onItemSelectionChange(item: OrderItem, index: number) {
    const selected = selectedItemOption(item) as any
    item.product_id = selected?.product_id ?? null
    item.variation_id = selected?.variation_id ?? null
    item.unit_cost = Number(
        productFor(item)?.suppliers?.find(
            (supplier: any) => supplier.id === form.supplier_id
        )?.unit_cost || 0
    )
    item.quantity_ordered = orderPointFor(item)
    validateQuantity(index)
}
function formatDate(value: Date | null) {
    if (!value) return null
    return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(
        2,
        '0'
    )}-${String(value.getDate()).padStart(2, '0')}`
}
function confirmSave(mode: 'draft' | 'submit') {
    const isDraft = mode === 'draft'
    confirm.require({
        header: isDraft
            ? 'Save purchase order as draft?'
            : 'Submit this purchase order?',
        message: isDraft
            ? 'Save this PO as a draft? It will not be sent for receiving yet.'
            : 'Submit this PO to the receiving workflow? Have you reviewed the supplier, branch, items, and total?',
        acceptLabel: isDraft ? 'Save Draft' : 'Submit PO',
        rejectLabel: 'Review Again',
        acceptProps: { severity: isDraft ? 'secondary' : 'warn' },
        rejectProps: { severity: 'secondary', outlined: true },
        accept: () => save(mode)
    })
}
async function save(mode: 'draft' | 'submit') {
    error.value = ''
    errors.value = {}
    form.items.forEach((_, index) => validateQuantity(index))
    if (
        !form.supplier_id ||
        !form.branch_id ||
        form.items.some(item => !item.product_id)
    ) {
        error.value = 'Choose a supplier, branch, and product for each item.'
        return
    }
    if (reorderSuggestions.value.length) {
        const selectedLinesMatch =
            reorderSuggestions.value.length === form.items.length &&
            reorderSuggestions.value.every(suggestion =>
                form.items.some(
                    item =>
                        Number(item.product_id) ===
                            Number(suggestion.product_id) &&
                        Number(item.variation_id || 0) ===
                            Number(suggestion.variation_id || 0) &&
                        Number(item.quantity_ordered || 0) >=
                            Math.ceil(
                                Number(suggestion.suggested_quantity || 0)
                            )
                )
            )
        if (
            !selectedLinesMatch ||
            Number(form.branch_id) !==
                Number(reorderSuggestions.value[0].branch_id)
        ) {
            error.value =
                'Keep each suggested item and its branch on the PO, and order at least the suggested quantity.'
            return
        }
        if (
            reorderSuggestions.value.some(
                suggestion =>
                    !productFor(
                        form.items.find(
                            item =>
                                Number(item.product_id) ===
                                Number(suggestion.product_id)
                        )!
                    )?.suppliers?.some(
                        (supplier: any) =>
                            Number(supplier.id) === Number(form.supplier_id)
                    )
            )
        ) {
            error.value = 'Choose a supplier linked to every suggested item.'
            return
        }
    }
    if (Object.keys(quantityErrors.value).length) {
        error.value = 'Correct the quantity for each item before saving.'
        return
    }
    saving.value = mode
    try {
        const payload = {
            ...form,
            reorder_suggestion_ids: reorderSuggestions.value.length
                ? reorderSuggestions.value.map(suggestion => suggestion.id)
                : null,
            submit: mode === 'submit',
            expected_delivery_date: formatDate(form.expected_delivery_date),
            items: form.items.map(({ key, unit_cost, selection_key, ...item }) => item)
        }
        const response = await axiosClient.post(
            '/api/inventory/purchase-orders',
            payload
        )
        router.visit(`/inventory/purchase-orders/${response.data.data.id}`)
    } catch (cause: any) {
        errors.value = cause?.response?.data?.errors || {}
        error.value =
            cause?.response?.data?.message ||
            (mode === 'submit'
                ? 'Unable to submit the purchase order.'
                : 'Unable to save the purchase order.')
    } finally {
        saving.value = null
    }
}
onMounted(async () => {
    addItem()
    try {
        const response = await axiosClient.get(
            '/api/inventory/purchase-orders/options'
        )
        suppliers.value = response.data.data.suppliers || []
        branches.value = response.data.data.branches || []
        products.value = response.data.data.products || []
        form.branch_id =
            Number(response.data.data.assigned_branch_id || 0) || null
        assignedBranchName.value =
            branches.value.find(
                (branch: any) => Number(branch.id) === Number(form.branch_id)
            )?.name || ''
        const query = new URLSearchParams(window.location.search)
        const requisitionId = Number(query.get('requisition_id') || 0)
        if (requisitionId) {
            const response = await inventoryService.getPurchaseRequisition(requisitionId)
            const requisition = response?.data
            if (!response?.success || requisition?.status !== 'inventory_po_pending') {
                throw new Error('This supplier request is not approved for an Inventory PO, or it was already converted.')
            }
            if (Number(requisition.branch_id) !== Number(form.branch_id)) {
                throw new Error('This supplier request belongs to a different branch.')
            }
            const requestedItems = Array.isArray(requisition.items) ? requisition.items : []
            if (!requestedItems.length || requestedItems.some((requested: any) => !products.value.some(product => Number(product.id) === Number(requested.product_id)))) {
                throw new Error('One or more requested items are unavailable for an Inventory PO. Confirm they have an active supplier contract.')
            }
            requisitionPrefill.value = {
                id: requisition.id,
                pr_number: requisition.pr_number,
                items: requestedItems,
            }
            form.purchase_requisition_id = Number(requisition.id)
            form.notes = `Created from approved supplier request ${requisition.pr_number}. ${requisition.reason || ''}`.trim()
            form.items.splice(0, form.items.length, ...requestedItems.map((requested: any) => ({
                key: nextKey++,
                product_id: Number(requested.product_id),
                variation_id: requested.variation_id ? Number(requested.variation_id) : null,
                selection_key: `${requested.product_id}:${requested.variation_id || 0}`,
                quantity_ordered: Math.max(10, Number(requested.quantity_requested || 0)),
                unit_cost: 0,
            })))
            if (!eligibleSuppliers.value.length) {
                throw new Error('No active contracted supplier is linked to every item in this request. Update supplier links or contracts before creating the PO.')
            }
            if (eligibleSuppliers.value.length === 1) {
                form.supplier_id = Number(eligibleSuppliers.value[0].id)
                onSupplierChange()
            }
            return
        }
        const ids = (
            query.get('reorder_suggestion_ids') ||
            query.get('reorder_suggestion_id') ||
            ''
        )
            .split(',')
            .filter(Boolean)
            .map(Number)
        if (ids.length) {
            if (
                ids.length > 100 ||
                ids.some(id => !Number.isSafeInteger(id) || id <= 0) ||
                new Set(ids).size !== ids.length
            )
                throw new Error('Invalid reorder suggestion selection.')
            const responses = await Promise.all(
                ids.map(id =>
                    axiosClient.get(`/api/inventory/reorder-suggestions/${id}`)
                )
            )
            const selected = responses.map(response => response.data.data)
            const branchId = Number(selected[0]?.branch_id)
            if (
                selected.some(
                    suggestion =>
                        suggestion.status !== 'pending' ||
                        Number(suggestion.branch_id) !== branchId ||
                        !products.value.some(
                            product => product.id === suggestion.product_id
                        )
                )
            ) {
                throw new Error(
                    'Selected suggestions must be pending, from one branch, and available for direct ordering.'
                )
            }
            if (!form.branch_id || branchId !== Number(form.branch_id)) {
                throw new Error(
                    'This reorder suggestion belongs to a different branch than your assigned branch.'
                )
            }
            reorderSuggestions.value = selected
            form.items.splice(
                0,
                form.items.length,
                ...selected.map(suggestion => ({
                    key: nextKey++,
                    product_id: suggestion.product_id,
                    variation_id: suggestion.variation_id || null,
                    selection_key: `${suggestion.product_id}:${suggestion.variation_id || 0}`,
                    quantity_ordered: Math.max(
                        10,
                        Math.ceil(Number(suggestion.suggested_quantity || 0))
                    ),
                    unit_cost: 0
                }))
            )
            const commonSupplierIds =
                products.value
                    .find(product => product.id === selected[0].product_id)
                    ?.suppliers?.map((supplier: any) => supplier.id) || []
            const eligibleIds = commonSupplierIds.filter((id: number) =>
                selected.every(suggestion =>
                    products.value
                        .find(product => product.id === suggestion.product_id)
                        ?.suppliers?.some((supplier: any) => supplier.id === id)
                )
            )
            const preferredId = Number(query.get('supplier_id') || 0)
            if (preferredId && !eligibleIds.includes(preferredId))
                throw new Error(
                    'The selected supplier is not linked to every suggested item.'
                )
            if (preferredId || eligibleIds.length === 1) {
                form.supplier_id = preferredId || eligibleIds[0]
                onSupplierChange()
            }
        } else if (query.get('forecast_prefill') === '1') {
            const productId = Number(query.get('product_id') || 0)
            const variationId = Number(query.get('variation_id') || 0) || null
            const requestedQuantity = Number(query.get('quantity_ordered') || 0)
            const forecastBranchId = Number(query.get('branch_id') || 0)
            const forecastDays = Number(query.get('forecast_days') || 30)
            const product = products.value.find(item => Number(item.id) === productId)
            const variation = variationId
                ? product?.variations?.find((item: any) => Number(item.id) === variationId)
                : null

            if (!product || (variationId && !variation)) {
                throw new Error('This forecast item is unavailable for purchase. Check that it is active and linked to a supplier with an active contract.')
            }
            if (!form.branch_id || forecastBranchId !== Number(form.branch_id)) {
                throw new Error('This forecast item belongs to a different branch than your assigned branch.')
            }

            const suggestedQuantity = Math.max(1, Math.ceil(requestedQuantity))
            forecastPrefill.value = {
                product_id: productId,
                product_name: product.name,
                sku: variation?.sku || product.sku,
                variant_name: variation?.name || null,
                suggested_quantity: suggestedQuantity,
                po_quantity: Math.max(10, suggestedQuantity),
            }
            form.items.splice(0, form.items.length, {
                key: nextKey++,
                product_id: productId,
                variation_id: variationId,
                selection_key: `${productId}:${variationId || 0}`,
                quantity_ordered: forecastPrefill.value.po_quantity,
                unit_cost: 0,
            })
            form.notes = `Restock based on the ${forecastDays}-day inventory forecast.`

            const availableSuppliers = eligibleSuppliers.value
            if (availableSuppliers.length === 1) {
                form.supplier_id = Number(availableSuppliers[0].id)
                onSupplierChange()
            }
        } else if (query.get('stock_view_prefill') === '1') {
            const productId = Number(query.get('product_id') || 0)
            const variationId = Number(query.get('variation_id') || 0) || null
            const requestedQuantity = Math.max(1, Math.ceil(Number(query.get('requested_quantity') || 1)))
            const product = products.value.find(item => Number(item.id) === productId)
            const variation = variationId
                ? product?.variations?.find((item: any) => Number(item.id) === variationId)
                : null

            if (!product || (variationId && !variation)) {
                throw new Error('This inventory item or variant is not available in the purchase-order catalog.')
            }

            const linkedSupplierIds = (product.suppliers || []).map((supplier: any) => Number(supplier.id))
            const preselectedSupplier = suppliers.value.find((supplier: any) => linkedSupplierIds.includes(Number(supplier.id)))
            if (!preselectedSupplier) {
                throw new Error('This product has no supplier available under an active store contract. Add or activate a supplier contract before creating a purchase order.')
            }

            form.supplier_id = Number(preselectedSupplier.id)
            onSupplierChange()
            const supplierProduct = product.suppliers.find((supplier: any) => Number(supplier.id) === Number(form.supplier_id))
            form.items.splice(0, form.items.length, {
                key: nextKey++,
                product_id: productId,
                variation_id: variationId,
                selection_key: `${productId}:${variationId || 0}`,
                quantity_ordered: Math.max(10, requestedQuantity),
                unit_cost: Number(supplierProduct?.unit_cost || 0),
            })
            form.notes = query.get('notes') || 'Restock request from inventory item details.'
        }
    } catch (cause: any) {
        error.value =
            cause?.response?.data?.message ||
            cause?.message ||
            'Unable to load order options.'
    } finally {
        loading.value = false
    }
})
</script>
