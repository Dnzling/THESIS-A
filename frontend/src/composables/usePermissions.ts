import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

/**
 * usePermissions - Global Permission Checker Composable
 * 
 * Provides fine-grained permission checking based on backend permission strings.
 * Designed to work with RBAC system using permission patterns like:
 *   - "inventory.products.view"
 *   - "procurement.suppliers.edit.store"
 *   - "finance.documents.approve.all"
 * 
 * Supports wildcard matching for grouped permission checks.
 * 
 * @example
 * const { hasPermission, hasAnyPermission, hasWildcardPermission } = usePermissions()
 * 
 * // Single permission check
 * if (hasPermission('inventory.products.edit.store')) {
 *   showEditButton = true
 * }
 * 
 * // Multiple permissions - user needs ANY of these
 * if (hasAnyPermission(['inventory.products.view', 'inventory.dashboard.view'])) {
 *   showInventorySection = true
 * }
 * 
 * // All permissions - user needs ALL of these
 * if (hasAllPermissions(['inventory.transfers.create', 'inventory.transfers.submit'])) {
 *   enableTransferWorkflow = true
 * }
 * 
 * // Wildcard - match permission patterns
 * if (hasWildcardPermission('inventory.*.view')) {
 *   // User can view anything in inventory module
 * }
 */

export const usePermissions = () => {
  const authStore = useAuthStore()

  /**
   * Check if user has a specific permission
   * @param permission - Exact permission string to check
   * @returns true if user has the permission
   */
  const hasPermission = (permission: string): boolean => {
    if (!authStore.permissions || authStore.permissions.length === 0) {
      return false
    }
    return authStore.permissions.includes(permission)
  }

  /**
   * Check if user has ANY of the provided permissions
   * Useful for displaying features that have multiple permission paths
   * 
   * @param permissions - Array of permission strings
   * @returns true if user has at least one of the permissions
   */
  const hasAnyPermission = (permissions: string[]): boolean => {
    if (!authStore.permissions || authStore.permissions.length === 0) {
      return false
    }
    if (!Array.isArray(permissions) || permissions.length === 0) {
      return false
    }
    return permissions.some(p => authStore.permissions.includes(p))
  }

  /**
   * Check if user has ALL of the provided permissions
   * Useful for complex workflows requiring multiple permissions
   * 
   * @param permissions - Array of permission strings
   * @returns true if user has all of the permissions
   */
  const hasAllPermissions = (permissions: string[]): boolean => {
    if (!authStore.permissions || authStore.permissions.length === 0) {
      return false
    }
    if (!Array.isArray(permissions) || permissions.length === 0) {
      return false
    }
    return permissions.every(p => authStore.permissions.includes(p))
  }

  /**
   * Check permission using wildcard patterns
   * Converts patterns to regex for flexible matching
   * 
   * Patterns:
   *   - "inventory.*.view" matches "inventory.products.view", "inventory.categories.view", etc.
   *   - "inventory.products.*" matches "inventory.products.view", "inventory.products.edit", etc.
   *   - "*.view.store" matches "inventory.products.view.store", "procurement.*.view.store", etc.
   * 
   * @param pattern - Permission pattern with wildcards (*)
   * @returns true if any permission matches the pattern
   */
  const hasWildcardPermission = (pattern: string): boolean => {
    if (!authStore.permissions || authStore.permissions.length === 0) {
      return false
    }
    if (!pattern || typeof pattern !== 'string') {
      return false
    }

    // Escape special regex characters except * (which we'll use for wildcards)
    const escapedPattern = pattern
      .replace(/[.+?^${}()|[\]\\]/g, '\\$&')  // Escape special chars
      .replace(/\*/g, '.*')                     // Replace * with .* for wildcard matching

    const regex = new RegExp(`^${escapedPattern}$`)
    return authStore.permissions.some(p => regex.test(p))
  }

  /**
   * Get all user permissions as a readonly computed value
   * Use for conditional rendering or advanced permission logic
   */
  const userPermissions = computed(() => authStore.permissions || [])

  /**
   * Get all user permissions grouped by module
   * Useful for debugging or building permission-aware UIs
   */
  const permissionsByModule = computed(() => {
    const grouped: Record<string, string[]> = {}
    userPermissions.value.forEach(perm => {
      const module = perm.split('.')[0] || 'other'
      if (!grouped[module]) {
        grouped[module] = []
      }
      grouped[module].push(perm)
    })
    return grouped
  })

  /**
   * Check if user is a super admin (usually has all permissions or special flag)
   * This is a convenience method - backends might set a special role
   */
  const isSuperAdmin = computed(() => {
    // Check if user has the wildcard all permission
    return hasWildcardPermission('*.all.*') || 
           hasWildcardPermission('*.all.approve') ||
           authStore.userRole === 'super_admin'
  })

  return {
    /**
     * Check single permission
     */
    hasPermission,

    /**
     * Check if user has ANY permission from array
     */
    hasAnyPermission,

    /**
     * Check if user has ALL permissions from array
     */
    hasAllPermissions,

    /**
     * Check permission with wildcard pattern
     */
    hasWildcardPermission,

    /**
     * Get all user permissions
     */
    userPermissions,

    /**
     * Get permissions grouped by module
     */
    permissionsByModule,

    /**
     * Check if user is super admin
     */
    isSuperAdmin,
  }
}

/**
 * Common Permission Patterns Reference
 * 
 * View Permissions (read-only):
 *   - inventory.products.view
 *   - procurement.suppliers.view
 *   - finance.documents.view
 * 
 * Create Permissions:
 *   - inventory.products.create
 *   - procurement.orders.create
 *   - finance.documents.create
 * 
 * Edit Permissions:
 *   - inventory.products.edit.store (edit own store items)
 *   - inventory.products.edit.all (edit any item)
 *   - procurement.suppliers.edit (edit without scope)
 * 
 * Delete Permissions:
 *   - inventory.products.delete.own (delete own items)
 *   - inventory.products.delete.store (delete store items)
 *   - inventory.products.delete.all (delete any item)
 * 
 * Approval Permissions (workflow):
 *   - procurement.orders.approve.store
 *   - finance.documents.approve.all
 *   - inventory.adjustments.approve
 * 
 * Wildcard Examples:
 *   - "inventory.*.view" - View anything in inventory
 *   - "*.products.*" - All actions on products in any module
 *   - "procurement.*.approve" - Approve anything in procurement
 *   - "finance.*.*" - All finance operations
 */
