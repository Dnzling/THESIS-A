<template>
  <div class="min-h-screen p-4">
    <ConfirmDialog />
    <div class="mx-auto max-w-7xl space-y-4">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-slate-800">
            Create Warehouse Purchase Requisition
          </h1>
          <p class="text-xs text-slate-500">
            Request replenishment for your assigned branch.
          </p>
        </div>
      </div>
      <Card class="border border-slate-200 shadow-sm"><template #content>
          <form class="space-y-5" @submit.prevent="confirmSubmit">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="rounded-xl border border-orange-100 bg-orange-50/60 p-3">
                <label class="text-xs font-semibold text-slate-700">Requesting Branch
                  <span class="text-red-500">*</span></label>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                  {{ branchLabel }}
                </p>
                <small class="text-slate-600">Automatically assigned from your user
                  profile.</small><small v-if="errors.branch_id" class="mt-1 block text-red-500">{{ errors.branch_id
                  }}</small>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-slate-700">Reason / Notes
                  <span class="text-red-500">*</span></label><Textarea v-model="form.reason" rows="2"
                  placeholder="Why is replenishment required?" /><small v-if="errors.reason" class="text-red-500">{{
                  errors.reason }}</small>
              </div>
            </div>
            <div class="rounded-lg border border-slate-200 p-3">
              <div class="mb-3 flex items-center justify-between">
                <div>
                  <h2 class="text-sm font-semibold text-slate-800">
                    Items to Replenish
                  </h2>
                  <p class="text-xs text-slate-500">
                    Add products that need stock for
                    {{ branchLabel }}.
                  </p>
                </div>
                <Button type="button" label="Add Item" icon="pi pi-plus" size="small"
                  :disabled="!form.branch_id || loading" @click="addItem" />
              </div>
              <DataTable :value="form.items" rowHover responsiveLayout="scroll" class="text-sm">
                <template #empty>
                  <div class="py-8 text-center text-slate-500">
                    Select a warehouse branch and add an
                    item.
                  </div>
                </template>
                <Column header="Inventory Item" style="min-width: 300px"><template #body="{ data, index }"><Select
                      v-model="data.branch_inventory_id" :options="inventoryOptions" optionLabel="searchText"
                      optionValue="value" filter fluid placeholder="Select product"
                      @change="selectItem(index)"><template #option="{ option }">
                        <div>
                          <div class="font-medium">
                            {{ option.title }}
                          </div>
                          <small class="text-slate-500">{{
                            option.subtitle
                          }}</small>
                        </div>
                      </template></Select></template></Column>
                <Column header="Available"><template #body="{ data }">{{
                  stock(data)?.quantity_available ?? "—"
                }}</template></Column>
                <Column header="Requested Qty"><template #body="{ data }">
                    <InputNumber v-model="data.quantity_requested" :min="1" :useGrouping="false" fluid />
                  </template></Column>
                <Column header="Unit"><template #body="{ data }">{{
                  stock(data)?.product
                    ?.unit_of_measurement || "—"
                }}</template></Column>
                <Column header="Unit Cost"><template #body="{ data }">{{
                  money(unitCost(stock(data)))
                }}</template></Column>
                <Column header="Unit Total"><template #body="{ data }">{{
                  money(
                    unitCost(stock(data)) *
                    Number(
                      data.quantity_requested ||
                      0,
                    ),
                  )
                }}</template></Column>
                <Column style="width: 70px"><template #body="{ index }"><Button type="button" icon="pi pi-trash"
                      severity="danger" text size="small" @click="
                        confirmRemoveItem(index)
                        " /></template></Column>
              </DataTable><small v-if="errors.items" class="mt-2 block text-red-500">{{ errors.items }}</small>
              <div class="mt-4 flex justify-end">
                <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Overall unit total</span>
                    <span class="font-semibold text-slate-900">{{
                      totalRequestedUnits.toLocaleString()
                    }}
                      units</span>
                  </div>
                  <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                    <span>Estimated request value</span>
                    <span class="font-medium text-slate-700">{{
                      money(totalRequestedValue)
                    }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
              <Button type="button" label="Cancel" severity="secondary" size="small" @click="goBack" /><Button
                type="submit" label="Create Request" size="small" :loading="saving" :disabled="!isFormValid" />
            </div>
          </form>
        </template></Card>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import ConfirmDialog from "primevue/confirmdialog";
import WarehouseService from "@/services/warehouse.service";
const confirm = useConfirm(),
  toast = useToast(),
  loading = ref(true),
  saving = ref(false),
  branches = ref<any[]>([]),
  inventory = ref<any[]>([]),
  errors = reactive<Record<string, string>>({});
const form = reactive<any>({ branch_id: null, reason: "", items: [] });
const branchInventory = computed(() =>
  inventory.value.filter(
    (row) => Number(row.branch_id) === Number(form.branch_id),
  ),
);
const inventoryOptions = computed(() =>
  branchInventory.value
    .map((row) => {
      const name = row.product?.product_name || "Unknown",
        sku = row.variation?.variation_sku || row.product?.sku || "—";
      const title = `${name}${row.variation?.variation_name ? ` - ${row.variation.variation_name}` : ""}`;
      return {
        value: row.id,
        title,
        subtitle: `SKU: ${sku} | Stock: ${row.quantity_available || 0}`,
        searchText: `${title} ${sku}`,
        stockQuantity: Number(row.quantity_available || 0),
      };
    })
    .sort((a, b) => a.stockQuantity - b.stockQuantity),
);
const branchLabel = computed(
  () => branches.value[0]?.name || "No branch assigned",
),
  blank = () => ({ branch_inventory_id: null, quantity_requested: 1 }),
  addItem = () => form.items.push(blank()),
  removeItem = (i: number) => form.items.splice(i, 1),
  resetItems = () => {
    form.items = [blank()];
  };
const stock = (item: any) =>
  inventory.value.find(
    (row) => Number(row.id) === Number(item.branch_inventory_id),
  );
const unitCost = (row: any) =>
  Number(row?.variation?.cost_price ?? row?.product?.cost_price ?? 0);
const money = (v: any) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(Number(v || 0));
const totalRequestedUnits = computed(() =>
  form.items.reduce(
    (total: number, item: any) =>
      total + Number(item.quantity_requested || 0),
    0,
  ),
);
const totalRequestedValue = computed(() =>
  form.items.reduce(
    (total: number, item: any) =>
      total +
      unitCost(stock(item)) * Number(item.quantity_requested || 0),
    0,
  ),
);
const selectItem = (i: number) => {
  const row = stock(form.items[i]);
  form.items[i].quantity_requested = Number(row?.reorder_quantity || 1);
};
const goBack = () => router.visit("/warehouse/stock");
const pendingRemoveIndex = ref<number | null>(null);
const confirmRemoveItem = (i: number) => {
  pendingRemoveIndex.value = i;
  confirm.require({
    header: "Remove item?",
    message: `Remove ${stock(form.items[i])?.product?.product_name || "this item"} from the request?`,
    icon: "pi pi-exclamation-triangle",
    rejectLabel: "Keep",
    acceptLabel: "Remove",
    rejectClass: "p-button-secondary",
    acceptClass: "p-button-danger",
    accept: () => {
      removeItem(i);
      pendingRemoveIndex.value = null;
    },
  });
};
const isFormValid = computed(() =>
  Boolean(
    form.branch_id &&
    form.reason.trim() &&
    form.items.length > 0 &&
    form.items.every(
      (item: any) =>
        item.branch_inventory_id && Number(item.quantity_requested) > 0,
    ),
  ),
);
const submit = async () => {
  Object.keys(errors).forEach((k) => delete errors[k]);
  const items = form.items.filter(
    (i: any) => i.branch_inventory_id && i.quantity_requested > 0,
  );
  if (!form.branch_id) errors.branch_id = "Select a warehouse branch.";
  if (!form.reason.trim()) errors.reason = "Reason is required.";
  if (!items.length) errors.items = "Add at least one valid item.";
  if (Object.keys(errors).length) return;
  saving.value = true;
  try {
    await WarehouseService.createPurchaseRequisition({
      branch_id: form.branch_id,
      reason: form.reason,
      items,
    });
    toast.add({
      severity: "success",
      summary: "Created",
      detail: "Warehouse purchase requisition submitted.",
      life: 3000,
    });
    router.visit("/warehouse/purchsase-requisitions");
  } catch (e: any) {
    toast.add({
      severity: "error",
      summary: "Unable to Create",
      detail:
        e?.response?.data?.message ||
        "Purchase requisition could not be created.",
      life: 4500,
    });
  } finally {
    saving.value = false;
  }
};
const confirmSubmit = () =>
  confirm.require({
    header: "Create Purchase Requisition?",
    message: "Submit this warehouse replenishment request to Procurement?",
    rejectLabel: "No",
    rejectProps: {
      severity: "secondary",
      outlined: true,
    },
    acceptLabel: "Confirm",
    accept: submit,
  });
onMounted(async () => {
  try {
    const data = await WarehouseService.purchaseRequisitionOptions();
    branches.value = data.branches || [];
    inventory.value = data.inventory || [];
    if (branches.value.length === 1) {
      form.branch_id = branches.value[0].id;
      resetItems();
    }
  } catch (e: any) {
    errors.branch_id =
      e?.response?.data?.message ||
      "Could not load your assigned warehouse branch.";
  } finally {
    loading.value = false;
  }
});
</script>
