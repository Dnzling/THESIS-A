<template>
    <div class="space-y-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Promotions & Discounts
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Run scheduled offers for this store. Individual sale prices
                    stay on each product.
                </p>
            </div>
            <Button
                v-if="canManage"
                label="Create Promotion"
                icon="pi pi-plus"
                @click="openCreate"
                size="small"
            />
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <Card
                v-for="item in statCards"
                :key="item.label"
                class="border border-slate-200 shadow-none"
                ><template #content>
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        {{ item.label }}
                    </p>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-2xl font-bold">{{ item.value }}</p>
                        <i :class="[item.icon, item.color, 'text-xl']" />
                    </div>
                </template>
            </Card>
        </div>

        <Card class="border border-slate-200 shadow-none"
            ><template #content>
                <div class="grid gap-3 md:grid-cols-4">
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="filters.search"
                            placeholder="Search promotions"
                            fluid
                            @input="searchLater"
                        /> </IconField
                    ><Select
                        v-model="filters.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All statuses"
                        showClear
                        fluid
                        @change="loadPromotions"
                    /><Select
                        v-model="filters.sort_by"
                        :options="sortOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                        @change="loadPromotions"
                    />
                </div>
            </template>
        </Card>

        <div v-if="loading" class="space-y-3">
            <Skeleton v-for="n in 3" :key="n" height="10rem" />
        </div>
        <div v-else-if="promotions.length" class="grid gap-4 xl:grid-cols-2">
            <Card
                v-for="promotion in promotions"
                :key="promotion.id"
                class="border border-slate-200 shadow-none"
                ><template #content>
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-lg font-semibold">
                                        {{ promotion.name }}
                                    </h2>
                                    <Tag
                                        :value="statusLabel(promotion.status)"
                                        :severity="
                                            statusSeverity(promotion.status)
                                        "
                                    />
                                </div>
                                <p
                                    v-if="promotion.description"
                                    class="mt-1 line-clamp-2 text-sm text-slate-500"
                                >
                                    {{ promotion.description }}
                                </p>
                            </div>
                            <div v-if="canManage" class="flex shrink-0 gap-1">
                                <Button
                                    icon="pi pi-pencil"
                                    text
                                    rounded
                                    aria-label="Edit"
                                    @click="openEdit(promotion)"
                                /><Button
                                    icon="pi pi-copy"
                                    text
                                    rounded
                                    severity="secondary"
                                    aria-label="Duplicate"
                                    @click="duplicatePromotion(promotion)"
                                /><Button
                                    :icon="
                                        promotion.is_active
                                            ? 'pi pi-pause'
                                            : 'pi pi-play'
                                    "
                                    text
                                    rounded
                                    :severity="
                                        promotion.is_active ? 'warn' : 'success'
                                    "
                                    aria-label="Change status"
                                    @click="togglePromotion(promotion)"
                                /><Button
                                    icon="pi pi-trash"
                                    text
                                    rounded
                                    severity="danger"
                                    aria-label="Delete"
                                    @click="askDelete(promotion)"
                                />
                            </div>
                        </div>
                        <div
                            class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4"
                        >
                            <div>
                                <p class="detail-label">Offer</p>
                                <p class="detail-value text-emerald-700">
                                    {{ discountLabel(promotion) }}
                                </p>
                            </div>
                            <div>
                                <p class="detail-label">Products</p>
                                <p class="detail-value">
                                    {{ promotion.affected_products_count }}
                                </p>
                            </div>
                            <div>
                                <p class="detail-label">Minimum order</p>
                                <p class="detail-value">
                                    {{
                                        +promotion.minimum_order_amount
                                            ? money(
                                                  promotion.minimum_order_amount,
                                              )
                                            : "None"
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="detail-label">Priority</p>
                                <p class="detail-value">
                                    {{ promotion.priority }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex gap-2">
                                <i class="pi pi-box mt-0.5 text-slate-400" />
                                <p>
                                    <b>Applies to:</b>
                                    <span class="text-slate-500">{{
                                        promotion.target_label
                                    }}</span>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <i
                                    class="pi pi-calendar mt-0.5 text-slate-400"
                                />
                                <p>
                                    <b>Schedule:</b>
                                    <span class="text-slate-500">{{
                                        scheduleLabel(promotion)
                                    }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </template></Card
            >
        </div>
        <Card v-else class="border border-dashed border-slate-300 shadow-none"
            ><template #content>
                <div class="py-10 text-center">
                    <i class="pi pi-percentage text-5xl text-slate-300" />
                    <h2 class="mt-4 font-semibold">No promotions found</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Create a store-wide, category, or product promotion.
                    </p>
                    <Button
                        v-if="canManage"
                        label="Create Promotion"
                        icon="pi pi-plus"
                        class="mt-4"
                        @click="openCreate"
                    />
                </div>
            </template>
        </Card>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="editingId ? 'Edit Promotion' : 'Create Promotion'"
            class="w-full max-w-3xl"
        >
            <div class="space-y-6 pt-2">
                <section class="space-y-3">
                    <div>
                        <h3 class="font-semibold">Promotion details</h3>
                        <p class="hint">Use a name staff can recognize.</p>
                    </div>
                    <div class="field">
                        <label>Name *</label>
                        <InputText
                            v-model="form.name"
                            placeholder="Holiday Bedroom Sale"
                            fluid
                            :invalid="!!errors.name"
                        /><small v-if="errors.name" class="error">{{
                            errors.name
                        }}</small>
                    </div>
                    <div class="field">
                        <label>Description</label
                        ><Textarea
                            v-model="form.description"
                            rows="2"
                            fluid
                            placeholder="Internal campaign note"
                        />
                    </div>
                </section>
                <section class="section">
                    <div>
                        <h3 class="font-semibold">Discount</h3>
                        <p class="hint">Choose one simple offer.</p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="field">
                            <label>Discount type *</label
                            ><Select
                                v-model="form.discount_type"
                                :options="discountTypes"
                                optionLabel="label"
                                optionValue="value"
                                fluid
                            />
                        </div>
                        <div class="field">
                            <label>Value *</label>
                            <InputNumber
                                v-model="form.discount_value"
                                :min="0.01"
                                :max="
                                    form.discount_type === 'percentage'
                                        ? 100
                                        : undefined
                                "
                                :suffix="
                                    form.discount_type === 'percentage'
                                        ? '%'
                                        : undefined
                                "
                                :mode="
                                    form.discount_type === 'fixed'
                                        ? 'currency'
                                        : 'decimal'
                                "
                                currency="PHP"
                                locale="en-PH"
                                fluid
                            />
                            <small v-if="errors.discount_value" class="error">{{
                                errors.discount_value
                            }}</small>
                        </div>
                    </div>
                </section>
                <section class="section">
                    <div>
                        <h3 class="font-semibold">Eligible products</h3>
                        <p class="hint">
                            Only this store's catalog is available.
                        </p>
                    </div>
                    <div class="field">
                        <label>Apply to *</label>
                        <SelectButton
                            v-model="form.target_type"
                            :options="targetTypes"
                            optionLabel="label"
                            optionValue="value"
                            :allowEmpty="false"
                        />
                    </div>
                    <div v-if="form.target_type === 'categories'" class="field">
                        <label>Categories *</label>
                        <MultiSelect
                            v-model="form.category_ids"
                            :options="categories"
                            optionLabel="category_name"
                            optionValue="id"
                            filter
                            display="chip"
                            placeholder="Select categories"
                            fluid
                        /><small v-if="errors.category_ids" class="error">{{
                            errors.category_ids
                        }}</small>
                    </div>
                    <div v-if="form.target_type === 'products'" class="field">
                        <label>Products *</label>
                        <MultiSelect
                            v-model="form.product_ids"
                            :options="products"
                            optionLabel="product_name"
                            optionValue="id"
                            filter
                            display="chip"
                            :maxSelectedLabels="3"
                            placeholder="Select products"
                            fluid
                            ><template #option="{ option }">
                                <div>
                                    <p class="font-medium">
                                        {{ option.product_name }}
                                    </p>
                                    <p class="hint">
                                        {{ option.sku || "No SKU" }} ·
                                        {{
                                            money(
                                                option.discounted_price ||
                                                    option.base_price,
                                            )
                                        }}
                                    </p>
                                </div>
                            </template> </MultiSelect
                        ><small v-if="errors.product_ids" class="error">{{
                            errors.product_ids
                        }}</small>
                    </div>
                </section>
                <section class="section">
                    <div>
                        <h3 class="font-semibold">Schedule and eligibility</h3>
                        <p class="hint">
                            Leave dates empty for an ongoing offer.
                        </p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="field">
                            <label>Starts</label>
                            <DatePicker
                                v-model="form.starts_at"
                                showTime
                                hourFormat="12"
                                fluid
                                showButtonBar
                            />
                        </div>
                        <div class="field">
                            <label>Ends</label>
                            <DatePicker
                                v-model="form.ends_at"
                                showTime
                                hourFormat="12"
                                fluid
                                showButtonBar
                                :minDate="form.starts_at || undefined"
                            /><small v-if="errors.ends_at" class="error">{{
                                errors.ends_at
                            }}</small>
                        </div>
                        <div class="field">
                            <label>Minimum order</label>
                            <InputNumber
                                v-model="form.minimum_order_amount"
                                mode="currency"
                                currency="PHP"
                                locale="en-PH"
                                :min="0"
                                fluid
                            /><small>Use ₱0 for no minimum.</small>
                        </div>
                        <div class="field">
                            <label>Priority</label>
                            <InputNumber
                                v-model="form.priority"
                                :min="0"
                                :max="999"
                                showButtons
                                fluid
                            /><small>Higher wins only for equal prices.</small>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div>
                            <p class="text-sm font-medium">
                                Activate promotion
                            </p>
                            <p class="hint">
                                The schedule controls when an active promotion
                                runs.
                            </p>
                        </div>
                        <ToggleSwitch v-model="form.is_active" />
                    </div>
                </section>
            </div>
            <template #footer
                ><Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="dialogVisible = false" /><Button
                    :label="editingId ? 'Save Changes' : 'Create Promotion'"
                    icon="pi pi-check"
                    :loading="saving"
                    @click="savePromotion()"
            /></template>
        </Dialog>
        <Dialog v-model:visible="reviewVisible" modal header="Review existing discounts" :style="{ width: 'min(94vw, 760px)' }">
            <p class="mb-4 text-sm text-slate-600">
                These products already have individual discounts. Both rules will be kept, but customers will receive only the lower price. The individual discount remains when this promotion ends.
            </p>
            <p v-if="Number(form.minimum_order_amount) > 0" class="mb-3 text-xs text-slate-500">Promotional prices apply only when the order reaches {{ money(form.minimum_order_amount) }}.</p>
            <div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200">
                <div v-for="item in conflictingProducts" :key="item.id" class="grid grid-cols-2 gap-2 border-b border-slate-100 p-3 text-xs last:border-b-0 sm:grid-cols-4">
                    <div class="col-span-2 font-semibold text-slate-900 sm:col-span-1">{{ item.product_name }}</div>
                    <div><span class="block text-slate-500">Regular</span>{{ money(item.base_price) }}</div>
                    <div><span class="block text-slate-500">Individual</span>{{ money(item.discounted_price) }}</div>
                    <div><span class="block text-slate-500">Promotion</span>{{ money(promotionPrice(item)) }}<span class="block font-semibold text-orange-600">Customer pays {{ money(Math.min(Number(item.discounted_price), promotionPrice(item))) }}</span></div>
                </div>
            </div>
            <p v-if="form.target_type !== 'products'" class="mt-3 text-xs text-slate-500">Excluding products changes this promotion to target only the currently recorded eligible products.</p>
            <template #footer>
                <Button label="Cancel" severity="secondary" text size="small" @click="reviewVisible = false" />
                <Button label="Exclude these products" severity="secondary" outlined size="small" :disabled="!products.some((p) => eligibleProductIds.includes(p.id) && !conflictingProducts.some((c) => c.id === p.id))" @click="excludeConflictsAndSave" />
                <Button label="Keep both rules; apply better price" severity="warn" size="small" :loading="saving" @click="savePromotion(true)" />
            </template>
        </Dialog>
        <ConfirmDialog />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { useAuthStore } from "../../../../stores/auth";
import merchandisingService from "../../../../services/merchandising.service";
import Button from "primevue/button";
import Card from "primevue/card";
import ConfirmDialog from "primevue/confirmdialog";
import DatePicker from "primevue/datepicker";
import Dialog from "primevue/dialog";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputNumber from "primevue/inputnumber";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import SelectButton from "primevue/selectbutton";
import Skeleton from "primevue/skeleton";
import Tag from "primevue/tag";
import Textarea from "primevue/textarea";
import ToggleSwitch from "primevue/toggleswitch";

const toast = useToast(),
    confirm = useConfirm(),
    authStore = useAuthStore();
const canManage = computed(() =>
    authStore.hasPermission("merchandising.pricing.manage"),
);
const promotions = ref<any[]>([]),
    categories = ref<any[]>([]),
    products = ref<any[]>([]);
const loading = ref(false),
    saving = ref(false),
    reviewVisible = ref(false),
    dialogVisible = ref(false),
    editingId = ref<number | null>(null);
const errors = ref<Record<string, string>>({});
const stats = reactive({ total: 0, active: 0, scheduled: 0, inactive: 0 });
const filters = reactive({
    search: "",
    status: null as string | null,
    sort_by: "priority_desc",
});
let timer: ReturnType<typeof setTimeout> | null = null;
const blank = () => ({
    name: "",
    description: "",
    discount_type: "percentage",
    discount_value: 10,
    target_type: "all",
    category_ids: [] as number[],
    product_ids: [] as number[],
    minimum_order_amount: 0,
    starts_at: null as Date | null,
    ends_at: null as Date | null,
    priority: 0,
    is_active: true,
});
const form = reactive(blank());
const eligibleProductIds = computed(() => products.value
    .filter((product: any) => form.target_type === 'all'
        || (form.target_type === 'products' && form.product_ids.includes(product.id))
        || (form.target_type === 'categories' && (form.category_ids.includes(product.category_id) || form.category_ids.includes(product.subcategory_id))))
    .map((product: any) => product.id));
const conflictingProducts = computed(() => products.value.filter((product: any) =>
    eligibleProductIds.value.includes(product.id)
    && Number(product.base_price) > 0
    && product.discounted_price != null
    && Number(product.discounted_price) < Number(product.base_price)));
const promotionPrice = (product: any) => Math.max(0, Number(product.base_price) -
    (form.discount_type === 'percentage'
        ? Number(product.base_price) * Number(form.discount_value) / 100
        : Number(form.discount_value)));
const excludeConflictsAndSave = () => {
    const excluded = new Set(conflictingProducts.value.map((product: any) => product.id));
    form.product_ids = eligibleProductIds.value.filter((id: number) => !excluded.has(id));
    form.category_ids = [];
    form.target_type = 'products';
    reviewVisible.value = false;
    savePromotion(true);
};
const discountTypes = [
        { label: "Percentage off", value: "percentage" },
        { label: "Fixed amount off", value: "fixed" },
    ],
    targetTypes = [
        { label: "All products", value: "all" },
        { label: "Categories", value: "categories" },
        { label: "Specific products", value: "products" },
    ];
const statusOptions = [
        { label: "Running now", value: "active" },
        { label: "Scheduled", value: "scheduled" },
        { label: "Expired", value: "expired" },
        { label: "Paused", value: "inactive" },
    ],
    sortOptions = [
        { label: "Priority: high first", value: "priority_desc" },
        { label: "Priority: low first", value: "priority_asc" },
        { label: "Newest first", value: "newest" },
        { label: "Oldest first", value: "oldest" },
    ];
const statCards = computed(() => [
    {
        label: "Total",
        value: stats.total,
        icon: "pi pi-percentage",
        color: "text-blue-600",
    },
    {
        label: "Running now",
        value: stats.active,
        icon: "pi pi-play-circle",
        color: "text-emerald-600",
    },
    {
        label: "Scheduled",
        value: stats.scheduled,
        icon: "pi pi-clock",
        color: "text-amber-600",
    },
    {
        label: "Paused",
        value: stats.inactive,
        icon: "pi pi-pause-circle",
        color: "text-slate-500",
    },
]);
const loadPromotions = async () => {
    loading.value = true;
    try {
        const r = await merchandisingService.getPromotions(filters);
        promotions.value = r.data?.promotions || [];
        Object.assign(stats, r.data?.stats || {});
    } catch (e: any) {
        toast.add({
            severity: "error",
            summary: "Unable to load promotions",
            detail: e.response?.data?.message || "Please try again.",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};
const loadOptions = async () => {
    try {
        const r = await merchandisingService.getPromotionOptions();
        categories.value = r.data?.categories || [];
        products.value = r.data?.products || [];
    } catch {
        toast.add({
            severity: "warn",
            summary: "Catalog options unavailable",
            life: 3000,
        });
    }
};
const searchLater = () => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(loadPromotions, 300);
};
const openCreate = () => {
    editingId.value = null;
    Object.assign(form, blank());
    errors.value = {};
    dialogVisible.value = true;
};
const openEdit = (p: any) => {
    editingId.value = p.id;
    Object.assign(form, {
        name: p.name,
        description: p.description || "",
        discount_type: p.discount_type,
        discount_value: +p.discount_value,
        target_type: p.target_type,
        category_ids: [...(p.category_ids || [])],
        product_ids: [...(p.product_ids || [])],
        minimum_order_amount: +p.minimum_order_amount || 0,
        starts_at: p.starts_at ? new Date(p.starts_at) : null,
        ends_at: p.ends_at ? new Date(p.ends_at) : null,
        priority: +p.priority || 0,
        is_active: !!p.is_active,
    });
    errors.value = {};
    dialogVisible.value = true;
};
const savePromotion = async (reviewed = false) => {
    errors.value = {};
    if (!form.name.trim()) errors.value.name = "Enter a promotion name.";
    if (!form.discount_value || form.discount_value <= 0)
        errors.value.discount_value = "Enter a discount greater than zero.";
    if (form.target_type === "categories" && !form.category_ids.length)
        errors.value.category_ids = "Select at least one category.";
    if (form.target_type === "products" && !form.product_ids.length)
        errors.value.product_ids = "Select at least one product.";
    if (form.starts_at && form.ends_at && form.ends_at <= form.starts_at)
        errors.value.ends_at = "End must be after start.";
    if (Object.keys(errors.value).length) return;
    if (!reviewed && form.is_active && conflictingProducts.value.length) {
        reviewVisible.value = true;
        return;
    }
    saving.value = true;
    try {
        const payload = {
            ...form,
            name: form.name.trim(),
            starts_at: form.starts_at?.toISOString() || null,
            ends_at: form.ends_at?.toISOString() || null,
        };
        const r = editingId.value
            ? await merchandisingService.updatePromotion(
                  editingId.value,
                  payload,
              )
            : await merchandisingService.createPromotion(payload);
        toast.add({
            severity: "success",
            summary: editingId.value
                ? "Promotion updated"
                : "Promotion created",
            detail: r.message,
            life: 2500,
        });
        dialogVisible.value = false;
        reviewVisible.value = false;
        await loadPromotions();
    } catch (e: any) {
        const v = e.response?.data?.errors || {};
        errors.value = Object.fromEntries(
            Object.entries(v).map(([k, x]: any) => [
                k,
                Array.isArray(x) ? x[0] : x,
            ]),
        );
        toast.add({
            severity: "error",
            summary: "Could not save promotion",
            detail: e.response?.data?.message || "Review the form.",
            life: 3500,
        });
    } finally {
        saving.value = false;
    }
};
const togglePromotion = async (p: any) => {
    try {
        await merchandisingService.togglePromotion(p.id);
        await loadPromotions();
    } catch (e: any) {
        toast.add({
            severity: "error",
            summary: "Status not updated",
            detail: e.response?.data?.message,
            life: 3000,
        });
    }
};
const duplicatePromotion = async (p: any) => {
    try {
        await merchandisingService.duplicatePromotion(p.id);
        toast.add({
            severity: "success",
            summary: "Promotion duplicated",
            detail: "The copy is paused for review.",
            life: 2500,
        });
        await loadPromotions();
    } catch (e: any) {
        toast.add({
            severity: "error",
            summary: "Could not duplicate",
            detail: e.response?.data?.message,
            life: 3000,
        });
    }
};
const askDelete = (p: any) =>
    confirm.require({
        header: "Delete promotion?",
        message: `Delete “${p.name}”?`,
        icon: "pi pi-exclamation-triangle",
        rejectProps: { label: "Cancel", severity: "secondary", text: true },
        acceptProps: { label: "Delete", severity: "danger" },
        accept: async () => {
            try {
                await merchandisingService.deletePromotion(p.id);
                await loadPromotions();
            } catch (e: any) {
                toast.add({
                    severity: "error",
                    summary: "Could not delete",
                    detail: e.response?.data?.message,
                    life: 3000,
                });
            }
        },
    });
const money = (v: any) =>
        new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP",
        }).format(+v || 0),
    discountLabel = (p: any) =>
        p.discount_type === "percentage"
            ? `${+p.discount_value}% off`
            : `${money(p.discount_value)} off`;
const statusLabel = (s: string) =>
        ({
            active: "Running now",
            scheduled: "Scheduled",
            expired: "Expired",
            inactive: "Paused",
        })[s] || s,
    statusSeverity = (s: string) =>
        ({
            active: "success",
            scheduled: "warn",
            expired: "danger",
            inactive: "secondary",
        })[s] || "info";
const dt = (v: string) =>
        new Intl.DateTimeFormat("en-PH", {
            dateStyle: "medium",
            timeStyle: "short",
        }).format(new Date(v)),
    scheduleLabel = (p: any) =>
        p.starts_at || p.ends_at
            ? `${p.starts_at ? dt(p.starts_at) : "Immediately"} – ${p.ends_at ? dt(p.ends_at) : "No end date"}`
            : "Always while active";
onMounted(() => {
    loadPromotions();
    loadOptions();
});
</script>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.field label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
}

.field small,
.hint {
    font-size: 0.75rem;
    color: #64748b;
}

.field .error {
    color: #dc2626;
}

.section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1.25rem;
}

.detail-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
}

.detail-value {
    margin-top: 0.2rem;
    font-size: 0.875rem;
    font-weight: 700;
}
</style>
