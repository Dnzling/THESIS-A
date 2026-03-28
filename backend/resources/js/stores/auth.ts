import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

interface User {
    id: number
    user_id: string
    first_name: string
    last_name: string
    role: string
    email: string
    abilities?: string[]
}

interface NavigationItem {
    id: number
    name: string
    display_name: string
    module: string
    route_name: string
    route_path: string
    icon: string | null
    parent_id: number | null
    display_order: number
    section: string | null
    meta: Record<string, any> | null
    is_active: boolean
    badge_count?: number
}

export const useAuthStore = defineStore('auth', () => {
    // ==========================================
    // STATE
    // ==========================================
    const token = ref(localStorage.getItem('auth_token'))
    const user = ref<User | null>(JSON.parse(localStorage.getItem('user') || 'null'))
    const loading = ref(false)
    const error = ref<string | null>(null)

    // RBAC State
    const permissions = ref<string[]>([])
    const navigation = ref<NavigationItem[]>([])
    const permissionsLoaded = ref(false)
    const isLoadingPermissions = ref(false)
    let permissionsPromise: Promise<void> | null = null

    // ==========================================
    // GETTERS
    // ==========================================
    const isAuthenticated = computed(() => !!token.value)
    const currentUser = computed(() => user.value)
    const userRole = computed(() => user.value?.role || null)
    const userAbilities = computed(() => user.value?.abilities || [])
    const isCustomer = computed(() => {
        const role = String(user.value?.role || '').toLowerCase()
        return role.includes('customer')
    })

    const isCustomerRoleValue = (roleValue: unknown): boolean => {
        const normalized = String(roleValue || '').toLowerCase()
        return normalized.includes('customer')
    }

    const getFirstNavigationRoute = (): string => {
        if (isCustomer.value) {
            return '/shop'
        }

        if (user.value?.role === 'super_admin') {
            return '/admin/dashboard'
        }

        const activeNav = navigation.value
            .filter(item => item.is_active && item.route_path && !item.meta?.is_group && !item.route_path.startsWith('#'))
            .sort((a, b) => a.display_order - b.display_order)

        const firstAvailable = activeNav[0]
        return firstAvailable?.route_path || '/system/index'
    }

    // Default route: first active navigation item that matches role/permission (fallback to /system/index)
    const defaultRoute = computed(() => getFirstNavigationRoute())

    // ==========================================
    // RBAC ACTIONS
    // ==========================================

    /**
     * Load user permissions and navigation from backend
     */
    const loadPermissions = async () => {
        if (isCustomer.value) {
            permissions.value = []
            navigation.value = []
            permissionsLoaded.value = true
            return
        }

        if (permissionsLoaded.value) {
            console.log('Permissions already loaded, skipping...')
            return
        }

        if (permissionsPromise) {
            return permissionsPromise
        }

        if (!token.value) {
            console.warn('Cannot load permissions - no token')
            return
        }

        isLoadingPermissions.value = true
        permissionsPromise = (async () => {
            try {
                console.log('Loading user permissions and navigation...')
                const response = await axios.get('/api/user/navigation')

                permissions.value = response.data.permissions || []
                navigation.value = response.data.navigation || []
                permissionsLoaded.value = true

                localStorage.setItem('navigation', JSON.stringify(navigation.value))
                localStorage.setItem('permissions', JSON.stringify(permissions.value))

                console.log('Permissions loaded:', permissions.value.length, 'permissions')
                console.log('Navigation loaded:', navigation.value.length, 'items')
            } catch (err: any) {
                console.error('Failed to load permissions:', err)

                const cachedNav = localStorage.getItem('navigation')
                const cachedPerms = localStorage.getItem('permissions')

                if (cachedNav && cachedPerms) {
                    console.log('Loading navigation from cache...')
                    navigation.value = JSON.parse(cachedNav)
                    permissions.value = JSON.parse(cachedPerms)
                    permissionsLoaded.value = true
                } else {
                    permissionsLoaded.value = false
                }

                if (err.response?.status === 401) {
                    await logout()
                }
            } finally {
                isLoadingPermissions.value = false
                permissionsPromise = null
            }
        })()

        return permissionsPromise
    }

    /**
     * âœ… Fetch navigation (can be called separately to refresh)
     */
    const fetchNavigation = async () => {
        if (isCustomer.value) {
            permissions.value = []
            navigation.value = []
            return
        }

        if (!token.value) {
            console.warn('Cannot fetch navigation - no token')
            return
        }

        try {
            console.log('Fetching navigation...')
            const response = await axios.get('/api/user/navigation')
            
            permissions.value = response.data.permissions || []
            navigation.value = response.data.navigation || []
            
            // Update cache
            localStorage.setItem('navigation', JSON.stringify(navigation.value))
            localStorage.setItem('permissions', JSON.stringify(permissions.value))
            
            console.log('Navigation refreshed:', navigation.value.length, 'items')
        } catch (error) {
            console.error('Failed to fetch navigation:', error)
            
            // Fallback to cached navigation
            const cached = localStorage.getItem('navigation')
            if (cached) {
                navigation.value = JSON.parse(cached)
            }
        }
    }

    /**
     * Check if user has a specific permission
     */
    const hasPermission = (permission: string): boolean => {
        if (permissions.value.includes(permission)) {
            return true
        }

        // Module admin bypass: e.g. procurement.admin grants procurement.*
        const module = String(permission || '').split('.')[0]
        if (module && permissions.value.includes(`${module}.admin`)) {
            return true
        }

        return false
    }

    /**
     * Check if user has ANY of the permissions
     */
    const hasAnyPermission = (perms: string[]): boolean => {
        return perms.some((p) => hasPermission(p))
    }

    /**
     * Check if user has ALL permissions
     */
    const hasAllPermissions = (perms: string[]): boolean => {
        return perms.every((p) => hasPermission(p))
    }

    /**
     * Check if user has ability (legacy support)
     */
    const hasAbility = (ability: string): boolean => {
        return user.value?.abilities?.includes(ability) || false
    }

    /**
     * Get navigation items for a specific module
     */
    const getNavigationByModule = (module: string) => {
        return navigation.value
            .filter(item => item.module === module && !item.parent_id && item.is_active)
            .sort((a, b) => a.display_order - b.display_order)
    }

    /**
     * âœ… Get navigation items by module and section
     */
    const getNavigationBySection = (module: string, section: string) => {
        return navigation.value
            .filter(item => 
                item.module === module && 
                item.section === section && 
                !item.parent_id &&
                item.is_active
            )
            .sort((a, b) => a.display_order - b.display_order)
    }

    /**
     * Get child navigation items
     */
    const getChildNavigation = (parentId: number) => {
        return navigation.value
            .filter(item => item.parent_id === parentId && item.is_active)
            .sort((a, b) => a.display_order - b.display_order)
    }

    /**
     * âœ… Check if navigation has specific section
     */
    const hasNavigationSection = (module: string, section: string): boolean => {
        return navigation.value.some(item => 
            item.module === module && 
            item.section === section && 
            item.is_active
        )
    }

    // ==========================================
    // AUTH ACTIONS
    // ==========================================

    /**
     * Login user
     */
    const login = async (login: string, password: string) => {
        loading.value = true
        error.value = null

        try {
            // Get CSRF cookie
            await axios.get('/sanctum/csrf-cookie')

            // Make login request
            const response = await axios.post('/api/auth/login', {
                login,
                password,
                device_name: 'web_browser',
            })

            const payload = response.data || {}
            const accessToken = payload?.data?.access_token || payload?.access_token || payload?.token
            const userData =
                payload?.data?.user ??
                payload?.user ??
                payload?.data ??
                null
            const customerUser = isCustomerRoleValue(userData?.role)

            token.value = accessToken
            user.value = userData

            localStorage.setItem('auth_token', accessToken)
            localStorage.setItem('user', JSON.stringify(userData))

            axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`

            if (customerUser) {
                permissions.value = []
                navigation.value = []
                permissionsLoaded.value = true
            } else {
                // Load permissions ONCE
                await loadPermissions()

                // Clock in ONLY ONCE
                try {
                    await axios.post('/api/attendances/clock-in', {
                        user_id: userData.id,
                        method: 'web'
                    })
                    console.log('Clock-in successful')
                } catch (clockInError) {
                    console.warn('Clock-in failed:', clockInError)
                }
            }

            return response

        } catch (err: any) {
            console.error('Login error:', err)
            error.value = err.response?.data?.message || err.message || 'Login failed'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Logout user
     */
    const logout = async (options?: { redirect?: boolean }) => {
        try {
            if (token.value && user.value) {
                await axios.post('/api/auth/logout-with-clock-out', {
                    user_id: user.value.id,
                    method: 'web'
                })
            }
            // Always attempt to destroy the web session (prevents /login redirect back)
            await axios.post('/logout')
        } catch (err) {
            console.warn('Logout API error:', err)
        } finally {
            // Clear everything
            token.value = null
            user.value = null
            permissions.value = []
            navigation.value = []
            permissionsLoaded.value = false
            isLoadingPermissions.value = false

            localStorage.removeItem('auth_token')
            localStorage.removeItem('user')
            localStorage.removeItem('navigation')
            localStorage.removeItem('permissions')

            delete axios.defaults.headers.common['Authorization']

            // Clear all cookies
            document.cookie.split(";").forEach(c => {
                document.cookie = c.replace(/^ +/, "")
                    .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/")
            })

            if (options?.redirect !== false) {
                window.location.href = '/login'
            }
        }
    }

    /**
     * Fetch current user data
     */
    const fetchCurrentUser = async (options?: { reloadPermissions?: boolean }) => {
        if (!token.value) return

        try {
            // Prefer /api/auth/me (returns a richer user resource including store/branch).
            const response = await axios.get('/api/auth/me')
            const payload = response.data || {}
            const resolvedUser =
                payload?.data?.user ??
                payload?.data ??
                payload?.user ??
                payload

            user.value = resolvedUser
            localStorage.setItem('user', JSON.stringify(resolvedUser))

            const shouldReloadPermissions = options?.reloadPermissions === true
            if (shouldReloadPermissions) {
                // Explicit refresh requested (rare). Use this when role/permissions might have changed.
                permissionsLoaded.value = false
                isLoadingPermissions.value = false
                await loadPermissions()
            } else if (!permissionsLoaded.value && !isLoadingPermissions.value) {
                // If permissions weren't loaded yet, load once. Otherwise keep cache to avoid reloading per page.
                await loadPermissions()
            }

            return resolvedUser
        } catch (err: any) {
            if (err.response?.status === 401) {
                await logout()
            }
            throw err
        }
    }

    /**
     * Initialize auth on app load
     */
    const initialize = async () => {
        if (token.value && user.value && !permissionsLoaded.value && !isLoadingPermissions.value) {
            console.log('Initializing auth store...')
            axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
            await loadPermissions()
        }
    }

    // ==========================================
    // RETURN PUBLIC API
    // ==========================================
    return {
        // State
        token,
        user,
        loading,
        error,
        permissions,
        navigation,
        permissionsLoaded,
        isLoadingPermissions,

        // Getters
        isAuthenticated,
        currentUser,
        userRole,
        userAbilities,
        isCustomer,
        defaultRoute,

        // Auth Actions
        login,
        logout,
        fetchCurrentUser,
        initialize,

        // Permission Actions
        loadPermissions,
        fetchNavigation,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasAbility,

        // Navigation Helpers
        getNavigationByModule,
        getNavigationBySection,
        getChildNavigation,
        hasNavigationSection,
    }
})
