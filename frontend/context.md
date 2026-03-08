I'm building an Inventory Management UI module for a Furniture IMS using Vue 3 + TypeScript + PrimeVue + Tailwind CSS.

## CONTEXT & REQUIREMENTS:

### Tech Stack:
- Vue 3 with Composition API
- TypeScript (strict mode)
- PrimeVue v4 for UI components
- Tailwind CSS for styling
- Pinia for state management
- Vue Router for navigation
- Axios for API calls

### Project Structure:

frontend/src/ ├── views/Inventory/ │ ├── Dashboard/Index.vue │ ├── BranchInventory/ │ ├── StockAdjustment/ │ ├── StockTransfer/ │ ├── StockAlert/ │ ├── Transactions/ │ └── Reports/ ├── components/Inventory/ │ ├── Widgets/ │ ├── Cards/ │ ├── Tables/ │ ├── Forms/ │ ├── Modals/ │ └── Charts/ ├── composables/inventory/ ├── types/inventory.ts ├── services/api/inventory.ts └── router/modules/inventory.ts


### Already Completed (DON'T recreate these):
1. ✅ types/inventory.ts - TypeScript interfaces
2. ✅ services/api/inventory.ts - API service layer
3. ✅ composables/inventory/useInventoryPermissions.ts
4. ✅ composables/inventory/useInventory.ts
5. ✅ composables/inventory/useStockAdjustment.ts
6. ✅ composables/inventory/useStockTransfer.ts
7. ✅ composables/inventory/useStockAlert.ts
8. ✅ router/modules/inventory.ts
9. ✅ components/Inventory/Widgets/StockStatusBadge.vue
10. ✅ components/Inventory/Widgets/StockLevelBadge.vue
11. ✅ components/Inventory/Widgets/LocationDisplay.vue
12. ✅ components/Inventory/Widgets/TransactionTypeIcon.vue
13. ✅ components/Inventory/Widgets/AlertPriorityBadge.vue
14. ✅ components/Inventory/Cards/InventoryCard.vue
15. ✅ components/Inventory/Cards/StockLevelCard.vue
16. ✅ components/Inventory/Cards/AlertCard.vue
17. ✅ components/Inventory/Cards/TransactionCard.vue

### PERMISSION-BASED ACCESS CONTROL:
All components MUST use permission checks from `useInventoryPermissions()`:

**Available Permissions:**
- inventory.dashboard.view
- inventory.items.view / create / edit / delete
- inventory.adjustments.view / create / approve / reject
- inventory.transfers.view / create / approve / ship / receive / cancel
- inventory.alerts.view / acknowledge / resolve / generate / delete
- inventory.transactions.view / export
- inventory.reports.view / export

**Example Usage:**
```vue
<script setup lang="ts">
import { useInventoryPermissions } from '@/composables/inventory/useInventoryPermissions';

const { 
  canViewInventory, 
  canCreateInventory, 
  canEditInventory 
} = useInventoryPermissions();
</script>

<template>
  <Button 
    v-if="canCreateInventory"
    label="Create" 
    @click="create"
  />
</template>

PRIMEVUE COMPONENTS TO USE:
DataTable, Column - for tables
Card - for card layouts
Button - for actions
InputText, Dropdown, Calendar, InputNumber, Textarea - for forms
Tag, Badge, Chip - for status indicators
Dialog, ConfirmDialog - for modals
Toast - for notifications (via useToast())
Skeleton - for loading states
Paginator - for pagination
Menu, ContextMenu - for action menus
Chart - for charts (PrimeVue Chart wrapper)

CODE STYLE REQUIREMENTS:
Component Structure:
Vue
<template>
  <!-- PrimeVue components with Tailwind classes -->
</template>

<script setup lang="ts">
// Imports
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

// Types
interface Props {
  // ...
}

// Props & Emits
const props = defineProps<Props>();
const emit = defineEmits<{
  event: [payload: Type];
}>();

// Composables
const router = useRouter();
const toast = useToast();

// State
const loading = ref(false);

// Computed
const computed = computed(() => {});

// Methods
const handleAction = async () => {};

// Lifecycle
onMounted(() => {});
</script>

<style scoped>
/* Minimal custom styles - prefer Tailwind */
</style>
Error Handling:
TypeScript
try {
  loading.value = true;
  await someAction();
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Action completed',
    life: 3000,
  });
} catch (error: any) {
  toast.add({
    severity: 'error',
    summary: 'Error',
    detail: error.response?.data?.message || 'Action failed',
    life: 3000,
  });
} finally {
  loading.value = false;
}
Currency Formatting:
TypeScript
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(value);
};
Date Formatting:
TypeScript
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
TASK: Create [COMPONENT_NAME]
File Path: frontend/src/[PATH]/[FILENAME].vue

Requirements:

Use TypeScript with proper type imports from @/types/inventory
Import and use the appropriate composable from @/composables/inventory/
Implement permission-based UI using useInventoryPermissions()
Use PrimeVue components with Tailwind CSS for styling
Include loading states with Skeleton loaders
Add proper error handling with Toast notifications
Make it fully responsive (mobile-first)
Add confirmation dialogs for destructive actions
Include JSDoc comments for complex methods
Follow the code style requirements above
Component Should Include:

[List specific features for this component]
[Data tables with filtering, sorting, pagination]
[Forms with validation]
[Action buttons with permission checks]
[Status badges and indicators]
[Responsive grid layouts]
API Endpoints Used:

TypeScript
import { inventoryApi } from '@/services/api/inventory';

// Example:
await inventoryApi.getBranchInventory(branchId, filters);
await inventoryApi.createInventory(data);
Related Components to Import:

TypeScript
// Widgets
import StockStatusBadge from '@/components/Inventory/Widgets/StockStatusBadge.vue';
import LocationDisplay from '@/components/Inventory/Widgets/LocationDisplay.vue';

// Cards
import InventoryCard from '@/components/Inventory/Cards/InventoryCard.vue';
Please generate the complete Vue 3 SFC component with full TypeScript support, proper error handling, and responsive design.

Code

---

## 📋 **HOW TO USE THIS PROMPT:**

### **Step 1: Open GitHub Copilot Chat in VS Code**
- Press `Ctrl+Shift+I` (Windows/Linux) or `Cmd+Shift+I` (Mac)
- Or click the Copilot icon in the sidebar

### **Step 2: Customize the Prompt**
Replace the `[COMPONENT_NAME]` section with specifics:

**Example 1 - For Table Component:**
```markdown
## TASK: Create InventoryTable Component

**File Path:** `frontend/src/components/Inventory/Tables/InventoryTable.vue`

**Requirements:**
[Keep all 10 requirements from above]

**Component Should Include:**
- PrimeVue DataTable with all inventory items
- Columns: Product Name, SKU, Quantity On Hand, Available, Reserved, Stock Status, Location, Value, Actions
- Filter inputs for: Search, Stock Status, Warehouse Section
- Sorting on all columns
- Row actions: View, Edit, Delete (with permission checks)
- Pagination with configurable items per page
- Export to CSV functionality
- Bulk selection for batch operations
- Empty state when no data
- Loading skeleton during data fetch

**API Endpoints Used:**
```typescript
await inventoryApi.getBranchInventory(branchId, filters);
await inventoryApi.deleteInventory(id);
Related Components to Import:

TypeScript
import StockStatusBadge from '@/components/Inventory/Widgets/StockStatusBadge.vue';
import LocationDisplay from '@/components/Inventory/Widgets/LocationDisplay.vue';
Code

**Example 2 - For View Component:**
```markdown
## TASK: Create Branch Inventory Index Page

**File Path:** `frontend/src/views/Inventory/BranchInventory/Index.vue`

**Requirements:**
[Keep all 10 requirements from above]

**Component Should Include:**
- Page header with title, breadcrumb, and "Create New" button
- Branch selector dropdown
- Filter panel (Search, Stock Status, Warehouse Section)
- Summary cards showing: Total Items, In Stock, Low Stock, Out of Stock, Total Value
- InventoryTable component
- Refresh button to reload data
- Export functionality
- Responsive grid layout

**API Endpoints Used:**
```typescript
await inventoryApi.getBranchInventory(branchId, filters);
await inventoryApi.getInventorySummary(branchId);
Related Components to Import:

TypeScript
import InventoryTable from '../components/Inventory/Tables/InventoryTable.vue';
import StockLevelCard from '../components/Inventory/Cards/StockLevelCard.vue';
Code

---

## 🎯 **QUICK TEMPLATES FOR DIFFERENT COMPONENT TYPES:**

### **For Tables:**
```markdown
## TASK: Create [TableName]Table Component

**File Path:** `frontend/src/components/Inventory/Tables/[TableName]Table.vue`

**Component Should Include:**
- PrimeVue DataTable with filters, sorting, pagination
- Column definitions with proper types
- Row actions menu (View, Edit, Delete)
- Bulk selection and actions
- Export to CSV
- Empty state and loading skeleton
- Responsive design
For Forms:
Markdown
## TASK: Create [FormName]Form Component

**File Path:** `frontend/src/components/Inventory/Forms/[FormName]Form.vue`

**Component Should Include:**
- Form fields with validation
- Submit and Cancel buttons
- Loading state during submission
- Error handling with toast notifications
- Responsive two-column layout
- Disabled state when loading
For Modals:
Markdown
## TASK: Create [ModalName]Modal Component

**File Path:** `frontend/src/components/Inventory/Modals/[ModalName]Modal.vue`

**Component Should Include:**
- PrimeVue Dialog component
- v-model for visibility
- Header, content, footer sections
- Confirm and Cancel actions
- Loading state
- Proper keyboard handling (ESC to close)
For View Pages:
Markdown
## TASK: Create [PageName] View

**File Path:** `frontend/src/views/Inventory/[Module]/[PageName].vue`

**Component Should Include:**
- Page layout with breadcrumb
- Summary statistics cards
- Main data table or content
- Filter panel
- Action buttons (Create, Export, Refresh)
- Permission-based UI elements
- Responsive grid layout