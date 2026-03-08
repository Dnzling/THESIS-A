# Templates for Remaining Procurement Files

This document provides copy-paste ready templates for the remaining 18 files.

---

## 📁 MODAL COMPONENTS (4 remaining)

### 1. SupplierPerformanceModal.vue

**Path**: `components/Procurement/Modals/SupplierPerformanceModal.vue`

**Purpose**: Display detailed supplier performance metrics in a modal

**Template Pattern**:
```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Supplier, SupplierPerformance } from '@/types/procurement';

const props = defineProps<{
  visible: boolean;
  supplier: Supplier;
  performance: SupplierPerformance;
}>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
}>();

const handleClose = () => {
  emit('update:visible', false);
};
</script>

<template>
  <Dialog :visible="visible" @update:visible="handleClose" modal :style="{ width: '700px' }" 
    :header="`${supplier.name} - Performance Metrics`">
    
    <!-- Grid of performance metrics -->
    <div class="grid grid-cols-2 gap-4">
      <Card><template #content><!-- On-time delivery rate --></template></Card>
      <Card><template #content><!-- Quality score --></template></Card>
      <!-- Add more metrics -->
    </div>

    <!-- Charts (optional) -->
    
    <template #footer>
      <Button label="Close" @click="handleClose" />
    </template>
  </Dialog>
</template>
```

**Key Features**:
- Display `on_time_delivery_rate`, `quality_score`, `average_lead_time`
- Show `total_orders`, `total_amount`
- Optional: Add Chart.js for visual metrics
- Read-only modal (no submit action)

---

### 2. RFQSupplierSelectorModal.vue

**Path**: `components/Procurement/Modals/RFQSupplierSelectorModal.vue`

**Purpose**: Select multiple suppliers for an RFQ with rating display

**Template Pattern**:
```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Supplier } from '@/types/procurement';

const props = defineProps<{
  visible: boolean;
  suppliers: Supplier[];
  selectedIds?: number[];
}>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
  select: [supplierIds: number[]];
}>();

const selectedSuppliers = ref<number[]>(props.selectedIds || []);

const handleSubmit = () => {
  if (selectedSuppliers.value.length < 3) {
    // Show error: minimum 3 suppliers
    return;
  }
  emit('select', selectedSuppliers.value);
  emit('update:visible', false);
};
</script>

<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible', false)" 
    modal :style="{ width: '800px' }" header="Select Suppliers for RFQ">
    
    <DataTable v-model:selection="selectedSuppliers" :value="suppliers" 
      selectionMode="multiple" dataKey="id">
      <Column selectionMode="multiple" headerStyle="width: 3rem" />
      <Column field="name" header="Supplier" />
      <Column field="supplier_type" header="Type" />
      <Column header="Rating">
        <template #body="{ data }">
          <i class="pi pi-star-fill text-yellow-500"></i> {{ data.rating }}
        </template>
      </Column>
      <Column field="status" header="Status">
        <template #body="{ data }">
          <Tag :value="data.status" />
        </template>
      </Column>
    </DataTable>

    <small class="text-red-500 mt-2" v-if="selectedSuppliers.length < 3">
      * Minimum 3 suppliers required
    </small>

    <template #footer>
      <Button label="Cancel" severity="secondary" outlined @click="$emit('update:visible', false)" />
      <Button label="Select Suppliers" @click="handleSubmit" 
        :disabled="selectedSuppliers.length < 3" />
    </template>
  </Dialog>
</template>
```

---

### 3. POApprovalModal.vue

**Path**: `components/Procurement/Modals/POApprovalModal.vue`

**Purpose**: Approve or reject a Purchase Order

**Template Pattern**: Similar to `PRApprovalModal.vue`

**Differences**:
- Accepts `PurchaseOrder` instead of `PurchaseRequisition`
- Display PO number, supplier, total amount
- Show required approvers based on amount
- Use `usePurchaseOrder().approvePO()` and `.rejectPO()`

---

### 4. PaymentApprovalModal.vue

**Path**: `components/Procurement/Modals/PaymentApprovalModal.vue`

**Purpose**: Approve or reject a Supplier Payment

**Template Pattern**: Similar to `PRApprovalModal.vue`

**Differences**:
- Accepts `SupplierPayment` prop
- Display payment details: amount, method, PO reference
- Use `useSupplierPayment().approvePayment()` and `.rejectPayment()`

---

## 📄 VIEW PAGES (12 remaining)

### Pattern for Index Views

All index views follow this structure:

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { use[Entity] } from '@/composables/procurement/use[Entity]';
import { useProcurementPermissions } from '@/composables/procurement/useProcurementPermissions';
import [Entity]Table from '@/components/Procurement/Tables/[Entity]Table.vue';

const router = useRouter();
const { [entities], loading, fetch[Entities] } = use[Entity]();
const { canCreate } = useProcurementPermissions();

const searchQuery = ref('');
// Add filter refs

onMounted(() => {
  load[Entities]();
});

const load[Entities] = async () => {
  await fetch[Entities]({ /* filters */ });
};

const handleCreate = () => {
  router.push('/procurement/[entity-route]/create');
};

const handleRowClick = (item: any) => {
  router.push(`/procurement/[entity-route]/${item.id}`);
};
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header with Create button -->
    <!-- Filters Card -->
    <!-- Table Card -->
  </div>
</template>
```

---

### 1. views/Procurement/Supplier/Edit.vue

**Pattern**: Similar to `Create.vue`

**Differences**:
```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSupplier } from '@/composables/procurement/useSupplier';
import SupplierForm from '@/components/Procurement/Forms/SupplierForm.vue';

const route = useRoute();
const router = useRouter();
const { supplier, loading, fetchSupplierById, updateSupplier } = useSupplier();

const supplierId = computed(() => Number(route.params.id));

onMounted(async () => {
  await fetchSupplierById(supplierId.value);
});

const handleSubmit = async (data: any) => {
  await updateSupplier(supplierId.value, data);
  // Show success toast
  router.push(`/procurement/suppliers/${supplierId.value}`);
};
</script>

<template>
  <div class="p-6">
    <h1>Edit Supplier</h1>
    <Card>
      <template #content>
        <SupplierForm v-if="supplier" :supplier="supplier" 
          @submit="handleSubmit" @cancel="router.back()" />
      </template>
    </Card>
  </div>
</template>
```

---

### 2. views/Procurement/Dashboard/Index.vue

**Purpose**: Overview dashboard with KPIs and charts

**Template**:
```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const stats = ref({
  total_suppliers: 0,
  pending_prs: 0,
  active_rfqs: 0,
  pending_pos: 0,
  total_spend_month: 0
});

onMounted(async () => {
  // Fetch dashboard stats from API
});
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-3xl font-bold">Procurement Dashboard</h1>

    <!-- KPI Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <Card>
        <template #content>
          <div class="text-center">
            <div class="text-sm text-gray-600">Total Suppliers</div>
            <div class="text-3xl font-bold text-blue-600">{{ stats.total_suppliers }}</div>
          </div>
        </template>
      </Card>
      <!-- Add more KPI cards -->
    </div>

    <!-- Quick Actions -->
    <Card>
      <template #title>Quick Actions</template>
      <template #content>
        <div class="flex gap-3">
          <Button label="Create PR" icon="pi pi-plus" @click="router.push('/procurement/purchase-requisitions/create')" />
          <Button label="Create RFQ" icon="pi pi-plus" @click="router.push('/procurement/rfqs/create')" />
          <Button label="Add Supplier" icon="pi pi-plus" @click="router.push('/procurement/suppliers/create')" />
        </div>
      </template>
    </Card>

    <!-- Recent Activity / Pending Approvals -->
  </div>
</template>
```

---

### 3-5. Purchase Requisition Views

#### Index.vue
- Use `PurchaseRequisitionTable` component
- Filters: branch, status, date range, procurement route
- Create button → `/procurement/purchase-requisitions/create`

#### Create.vue
- Use `PurchaseRequisitionForm` component
- On submit (draft): save and stay on page
- On submit (approval): save and redirect to detail page

#### Detail.vue
- Display PR details (read-only sections)
- Show items table
- Display status badge
- Show approval history if available
- Actions: Approve, Reject, Convert to RFQ/PO (based on procurement route)
- Use `PRApprovalModal` for approval workflow

---

### 6-8. RFQ Views

#### Index.vue
- Use `RFQTable` component
- Filters: status, date range
- Create button → `/procurement/rfqs/create`

#### Create.vue
- Use `RFQForm` component
- Need to fetch and pass `purchaseRequisitions` and `suppliers` props
- On submit: redirect to RFQ detail page

#### Detail.vue
- Display RFQ details
- Show suppliers list (with contact info)
- Show quotations received (use `SupplierQuotationTable`)
- Actions: Send to Suppliers, Close RFQ, Award Quote
- Use `QuotationComparisonModal` to compare quotations

---

### 9-11. Purchase Order Views

#### Index.vue
- Use `PurchaseOrderTable` component
- Filters: supplier, branch, status, date range
- Create button → `/procurement/purchase-orders/create`

#### Create.vue
- Use `PurchaseOrderForm` component
- Fetch and pass `suppliers` and `quotations` props
- On submit: redirect to PO detail page

#### Detail.vue
- Display PO header (number, supplier, dates, amounts)
- Show items table
- Display approval status and required approvers
- Show goods receipts related to this PO
- Show payments related to this PO
- Actions: Approve, Reject, Send to Supplier, Print PO
- Use `POApprovalModal` for approval

---

### 12. Goods Receipt View

#### Index.vue (List all receipts)
- Use `GoodsReceiptTable` component
- Filters: PO, date range, status
- Create button → Open modal to select PO, then open form

**Note**: Goods Receipt typically doesn't have Detail page (simple entity)

**Alternative**: Add a "Create Receipt" button that:
1. Opens a dialog to select a PO
2. Then navigates to a Create page with PO pre-selected

---

## 🎨 COMPONENT COMPOSITION EXAMPLES

### Example: Detail View with Tabs

```vue
<TabView v-model:activeIndex="activeTab">
  <TabPanel header="Details">
    <!-- Entity details -->
  </TabPanel>
  <TabPanel header="Items">
    <!-- Items table -->
  </TabPanel>
  <TabPanel header="History">
    <!-- Approval history / Activity log -->
  </TabPanel>
</TabView>
```

### Example: Filter Section

```vue
<Card>
  <template #content>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <IconField iconPosition="left">
        <InputIcon><i class="pi pi-search" /></InputIcon>
        <InputText v-model="searchQuery" placeholder="Search..." class="w-full" />
      </IconField>
      <Dropdown v-model="selectedStatus" :options="statuses" placeholder="All Statuses" showClear />
      <Calendar v-model="dateRange" selectionMode="range" placeholder="Date Range" showIcon />
      <div class="flex gap-2">
        <Button label="Search" @click="handleSearch" />
        <Button label="Reset" severity="secondary" outlined @click="handleReset" />
      </div>
    </div>
  </template>
</Card>
```

---

## 🔗 ROUTER CONFIGURATION

Add to `frontend/src/router/index.ts`:

```typescript
{
  path: '/procurement',
  component: () => import('@/layouts/MainLayout.vue'),
  meta: { requiresAuth: true },
  children: [
    {
      path: 'dashboard',
      name: 'ProcurementDashboard',
      component: () => import('@/views/Procurement/Dashboard/Index.vue')
    },
    
    // Suppliers
    {
      path: 'suppliers',
      name: 'SupplierIndex',
      component: () => import('@/views/Procurement/Supplier/Index.vue')
    },
    {
      path: 'suppliers/create',
      name: 'SupplierCreate',
      component: () => import('@/views/Procurement/Supplier/Create.vue')
    },
    {
      path: 'suppliers/:id',
      name: 'SupplierDetail',
      component: () => import('@/views/Procurement/Supplier/Detail.vue')
    },
    {
      path: 'suppliers/:id/edit',
      name: 'SupplierEdit',
      component: () => import('@/views/Procurement/Supplier/Edit.vue')
    },

    // Purchase Requisitions
    {
      path: 'purchase-requisitions',
      name: 'PurchaseRequisitionIndex',
      component: () => import('@/views/Procurement/PurchaseRequisition/Index.vue')
    },
    {
      path: 'purchase-requisitions/create',
      name: 'PurchaseRequisitionCreate',
      component: () => import('@/views/Procurement/PurchaseRequisition/Create.vue')
    },
    {
      path: 'purchase-requisitions/:id',
      name: 'PurchaseRequisitionDetail',
      component: () => import('@/views/Procurement/PurchaseRequisition/Detail.vue')
    },

    // RFQs
    {
      path: 'rfqs',
      name: 'RFQIndex',
      component: () => import('@/views/Procurement/RFQ/Index.vue')
    },
    {
      path: 'rfqs/create',
      name: 'RFQCreate',
      component: () => import('@/views/Procurement/RFQ/Create.vue')
    },
    {
      path: 'rfqs/:id',
      name: 'RFQDetail',
      component: () => import('@/views/Procurement/RFQ/Detail.vue')
    },

    // Purchase Orders
    {
      path: 'purchase-orders',
      name: 'PurchaseOrderIndex',
      component: () => import('@/views/Procurement/PurchaseOrder/Index.vue')
    },
    {
      path: 'purchase-orders/create',
      name: 'PurchaseOrderCreate',
      component: () => import('@/views/Procurement/PurchaseOrder/Create.vue')
    },
    {
      path: 'purchase-orders/:id',
      name: 'PurchaseOrderDetail',
      component: () => import('@/views/Procurement/PurchaseOrder/Detail.vue')
    },

    // Goods Receipts
    {
      path: 'goods-receipts',
      name: 'GoodsReceiptIndex',
      component: () => import('@/views/Procurement/GoodsReceipt/Index.vue')
    }
  ]
}
```

---

## 📦 NAVIGATION MENU

Add to your main navigation:

```typescript
{
  label: 'Procurement',
  icon: 'pi pi-shopping-cart',
  items: [
    { label: 'Dashboard', icon: 'pi pi-chart-bar', to: '/procurement/dashboard' },
    { label: 'Suppliers', icon: 'pi pi-users', to: '/procurement/suppliers' },
    { label: 'Purchase Requisitions', icon: 'pi pi-file', to: '/procurement/purchase-requisitions' },
    { label: 'RFQs', icon: 'pi pi-send', to: '/procurement/rfqs' },
    { label: 'Purchase Orders', icon: 'pi pi-shopping-bag', to: '/procurement/purchase-orders' },
    { label: 'Goods Receipts', icon: 'pi pi-box', to: '/procurement/goods-receipts' }
  ]
}
```

---

## ⚡ QUICK REFERENCE

### Common Imports
```typescript
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { use[Entity] } from '@/composables/procurement/use[Entity]';
import { useProcurementPermissions } from '@/composables/procurement/useProcurementPermissions';
```

### Common Patterns

**Currency Formatting**:
```typescript
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(value);
};
```

**Date Formatting**:
```typescript
new Date(dateString).toLocaleDateString('en-PH')
```

**Status Severity Mapping**:
```typescript
const getStatusSeverity = (status: string) => {
  const severities: Record<string, any> = {
    'Draft': 'secondary',
    'Pending': 'warning',
    'Approved': 'success',
    'Rejected': 'danger',
    'Active': 'success',
    'Completed': 'info'
  };
  return severities[status] || 'secondary';
};
```

---

## ✅ CHECKLIST FOR EACH FILE

- [ ] Import all required types from `@/types/procurement`
- [ ] Import composable from `@/composables/procurement/use[Entity]`
- [ ] Import `useProcurementPermissions` if using permission checks
- [ ] Add proper TypeScript types to all props and emits
- [ ] Include loading states and error handling
- [ ] Add toast notifications for success/error
- [ ] Format currency using PHP locale
- [ ] Format dates using en-PH locale
- [ ] Include empty states for tables
- [ ] Add permission-based rendering with `v-if`
- [ ] Test navigation (router.push works correctly)

---

**Ready to use!** Copy these templates and customize with entity-specific details.
