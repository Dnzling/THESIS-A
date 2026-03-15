// Supplier Module Routes
// Add these routes to your main router configuration (router/index.ts or router.ts)

import SupplierList from '../../components/system/supplier/SupplierList.vue'
import SupplierDetail from '../../components/system/supplier/SupplierDetail.vue'
import SupplierDashboard from '../../components/system/supplier/SupplierDashboard.vue'

// Supplier Portal Routes
import SupplierPortalDashboard from '../../views/system/supplier/SupplierPortalDashboard.vue'
import SupplierPortalRegistration from '../../views/system/supplier/SupplierPortalRegistration.vue'
import SupplierRFQIndex from '../../views/system/supplier/SupplierRFQIndex.vue'
import SupplierRFQDetail from '../../views/system/supplier/SupplierRFQDetail.vue'
import SupplierPOIndex from '../../views/system/supplier/SupplierPOIndex.vue'
import SupplierPODetail from '../../views/system/supplier/SupplierPODetail.vue'
import SupplierVerificationsIndex from '../../views/system/supplier/SupplierVerificationsIndex.vue'

export const supplierRoutes = [
  {
    path: '/suppliers',
    redirect: '/suppliers/list',
    meta: {
      title: 'Supplier Management',
      breadcrumb: 'Suppliers',
    },
  },
  {
    path: '/suppliers/list',
    component: SupplierList,
    meta: {
      title: 'Supplier List',
      breadcrumb: 'Suppliers',
      requiresAuth: true,
    },
  },
  {
    path: '/suppliers/:id',
    component: SupplierDetail,
    meta: {
      title: 'Supplier Details',
      breadcrumb: 'Supplier Details',
      requiresAuth: true,
    },
  },
  {
    path: '/suppliers/dashboard',
    component: SupplierDashboard,
    meta: {
      title: 'Supplier Dashboard',
      breadcrumb: 'Dashboard',
      requiresAuth: true,
    },
  },

  // ========== SUPPLIER PORTAL ROUTES ==========
  {
    path: '/supplier-portal',
    redirect: '/supplier-portal/dashboard',
    meta: {
      title: 'Supplier Portal',
      breadcrumb: 'Portal',
    },
  },
  {
    path: '/supplier-portal/dashboard',
    component: SupplierPortalDashboard,
    meta: {
      title: 'Supplier Portal Dashboard',
      breadcrumb: 'Dashboard',
      requiresAuth: true,
    },
  },
  {
    path: '/supplier-portal/registration',
    component: SupplierPortalRegistration,
    meta: {
      title: 'Supplier Registration',
      breadcrumb: 'Registration',
      requiresAuth: true,
    },
  },
  {
    path: '/supplier-portal/rfqs',
    component: SupplierRFQIndex,
    meta: {
      title: 'RFQ Requests',
      breadcrumb: 'RFQs',
      requiresAuth: true,
    },
  },
  {
    path: '/supplier-portal/rfqs/:id',
    component: SupplierRFQDetail,
    meta: {
      title: 'RFQ Details',
      breadcrumb: 'RFQ Details',
      requiresAuth: true,
    },
  },
  {
    path: '/supplier-portal/pos',
    component: SupplierPOIndex,
    meta: {
      title: 'Purchase Orders',
      breadcrumb: 'Purchase Orders',
      requiresAuth: true,
    },
  },
  {
    path: '/supplier-portal/pos/:id',
    component: SupplierPODetail,
    meta: {
      title: 'Purchase Order Details',
      breadcrumb: 'PO Details',
      requiresAuth: true,
    },
  },

  // ========== ADMIN ROUTES ==========
  {
    path: '/admin/supplier-verifications',
    component: SupplierVerificationsIndex,
    meta: {
      title: 'Supplier Verification',
      breadcrumb: 'Verifications',
      requiresAuth: true,
      roles: ['admin', 'super_admin'],
    },
  },
]

// Add to your main router:
/*
import { createRouter, createWebHistory } from 'vue-router'
import { supplierRoutes } from './supplier'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ... other routes
    ...supplierRoutes,
  ],
})
*/

// Navigation Menu Item to Add
export const supplierNavigation = {
  label: 'Supplier Management',
  icon: 'pi pi-briefcase',
  items: [
    {
      label: 'Suppliers',
      icon: 'pi pi-list',
      to: '/suppliers/list',
    },
    {
      label: 'Dashboard',
      icon: 'pi pi-chart-bar',
      to: '/suppliers/dashboard',
    },
  ],
}
