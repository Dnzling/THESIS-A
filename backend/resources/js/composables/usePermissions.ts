import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

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
      .replace(/[.+?^${}()|[\]\\]/g, '\\$&')
      .replace(/\*/g, '.*')

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
    return hasWildcardPermission('*.all.*') ||
      hasWildcardPermission('*.all.approve') ||
      authStore.userRole === 'super_admin'
  })

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasWildcardPermission,
    userPermissions,
    permissionsByModule,
    isSuperAdmin,
  }
}
