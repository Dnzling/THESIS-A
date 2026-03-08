<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'


export const ROLES = {
  SUPER_ADMIN: 'super_admin',
  STORE_ADMIN: 'store_admin',
  STORE_MANAGER: 'store_manager',
  HR_MANAGER: 'hr_manager',
  ACCOUNTANT: 'accountant',
  CASHIER: 'cashier',
  WAREHOUSE_MANAGER: 'warehouse_manager',
  INVENTORY_STAFF: 'inventory_staff',
  SUPPLIER_COORDINATOR: 'supplier_coordinator',
  SALES_STAFF: 'sales_staff',
  DELIVERY_STAFF: 'delivery_staff'
} as const

export type Role = typeof ROLES[keyof typeof ROLES]

export const usePermissions = () => {
  const authStore = useAuthStore()

  const userRole = computed(() => authStore.userRole as Role)

  // Check if user has any of the specified roles
  const hasAnyRole = (roles: Role[]): boolean => {
    return roles.includes(userRole.value)
  }

  // Check if user has a specific role
  const hasRole = (role: Role): boolean => {
    return userRole.value === role
  }

  // Check if user can access merchandising features
  const canAccessMerchandising = computed(() => {
    return hasAnyRole([
      ROLES.SUPER_ADMIN,
      ROLES.STORE_ADMIN,
      ROLES.STORE_MANAGER,
      ROLES.WAREHOUSE_MANAGER,
      ROLES.INVENTORY_STAFF,
      ROLES.SALES_STAFF,
      ROLES.SUPPLIER_COORDINATOR,
      ROLES.CASHIER
    ])
  })

  // Check if user can edit products
  const canEditProducts = computed(() => {
    return hasAnyRole([
      ROLES.SUPER_ADMIN,
      ROLES.STORE_ADMIN,
      ROLES.STORE_MANAGER
    ])
  })

  // Check if user can manage inventory
  const canManageInventory = computed(() => {
    return hasAnyRole([
      ROLES.SUPER_ADMIN,
      ROLES.STORE_ADMIN,
      ROLES.STORE_MANAGER,
      ROLES.WAREHOUSE_MANAGER,
      ROLES.INVENTORY_STAFF
    ])
  })

  // Check if user can view only (read-only access)
  const isViewOnly = computed(() => {
    return hasAnyRole([
      ROLES.SALES_STAFF,
      ROLES.CASHIER,
      ROLES.SUPPLIER_COORDINATOR
    ])
  })

  // Check if user can manage pricing
  const canManagePricing = computed(() => {
    return hasAnyRole([
      ROLES.SUPER_ADMIN,
      ROLES.STORE_ADMIN,
      ROLES.STORE_MANAGER
    ])
  })

  // Check if user can manage categories/attributes
  const canManageCatalogSettings = computed(() => {
    return hasAnyRole([
      ROLES.SUPER_ADMIN,
      ROLES.STORE_ADMIN,
      ROLES.STORE_MANAGER
    ])
  })

  return {
    userRole,
    hasRole,
    hasAnyRole,
    canAccessMerchandising,
    canEditProducts,
    canManageInventory,
    canManagePricing,
    canManageCatalogSettings,
    isViewOnly
  }
}
</script>
