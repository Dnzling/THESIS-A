import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import router from '../router'

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

    // ==========================================
    // GETTERS
    // ==========================================
    const isAuthenticated = computed(() => !!token.value)
    const currentUser = computed(() => user.value)
    const userRole = computed(() => user.value?.role || null)
    const userAbilities = computed(() => user.value?.abilities || [])

    // Default route based on role
    const defaultRoute = computed(() => {
        switch (user.value?.role) {
            case 'super_admin':
                return '/admin/dashboard'
            case 'store_admin':
            case 'store_manager':
                return '/system/index'
            case 'hr_manager':
                return '/hr/index'
            case 'warehouse_manager':
            case 'inventory_staff':
                return '/merchandising/products'
            case 'sales_staff':
                return '/merchandising/products'
            default:
                return '/login'
        }
    })

    // ==========================================
    // RBAC ACTIONS
    // ==========================================

    /**
     * Load user permissions and navigation from backend
     */
    const loadPermissions = async () => {
        // Prevent duplicate calls
        if (isLoadingPermissions.value) {
            console.log('⏸️ Permissions already loading, skipping...')
            return
        }

        if (permissionsLoaded.value) {
            console.log('✅ Permissions already loaded, skipping...')
            return
        }

        if (!token.value) {
            console.warn('⚠️ Cannot load permissions - no token')
            return
        }

        isLoadingPermissions.value = true

        try {
            console.log('📥 Loading user permissions and navigation...')
            const response = await axios.get('/api/user/navigation')

            // ✅ Direct assignment from API response
            permissions.value = response.data.permissions || []
            navigation.value = response.data.navigation || []
            permissionsLoaded.value = true

            // Cache in localStorage
            localStorage.setItem('navigation', JSON.stringify(navigation.value))
            localStorage.setItem('permissions', JSON.stringify(permissions.value))

            console.log('✅ Permissions loaded:', permissions.value.length, 'permissions')
            console.log('✅ Navigation loaded:', navigation.value.length, 'items')
        } catch (err: any) {
            console.error('❌ Failed to load permissions:', err)
            
            // Try to load from localStorage if API fails
            const cachedNav = localStorage.getItem('navigation')
            const cachedPerms = localStorage.getItem('permissions')
            
            if (cachedNav && cachedPerms) {
                console.log('📦 Loading navigation from cache...')
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
        }
    }

    /**
     * ✅ Fetch navigation (can be called separately to refresh)
     */
    const fetchNavigation = async () => {
        if (!token.value) {
            console.warn('⚠️ Cannot fetch navigation - no token')
            return
        }

        try {
            console.log('🔄 Fetching navigation...')
            const response = await axios.get('/api/user/navigation')
            
            permissions.value = response.data.permissions || []
            navigation.value = response.data.navigation || []
            
            // Update cache
            localStorage.setItem('navigation', JSON.stringify(navigation.value))
            localStorage.setItem('permissions', JSON.stringify(permissions.value))
            
            console.log('✅ Navigation refreshed:', navigation.value.length, 'items')
        } catch (error) {
            console.error('❌ Failed to fetch navigation:', error)
            
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
        return permissions.value.includes(permission)
    }

    /**
     * Check if user has ANY of the permissions
     */
    const hasAnyPermission = (perms: string[]): boolean => {
        return perms.some(p => permissions.value.includes(p))
    }

    /**
     * Check if user has ALL permissions
     */
    const hasAllPermissions = (perms: string[]): boolean => {
        return perms.every(p => permissions.value.includes(p))
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
     * ✅ Get navigation items by module and section
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
     * ✅ Check if navigation has specific section
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
    const login = async (email: string, password: string) => {
        loading.value = true
        error.value = null

        try {
            // Get CSRF cookie
            await axios.get('/sanctum/csrf-cookie')

            // Make login request
            const response = await axios.post('/api/auth/login', {
                email,
                password,
                device_name: 'web_browser',
            })

            const accessToken = response.data.data?.access_token || response.data.token
            const userData = response.data.data?.user || response.data.user

            token.value = accessToken
            user.value = userData

            localStorage.setItem('auth_token', accessToken)
            localStorage.setItem('user', JSON.stringify(userData))

            axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`

            // Load permissions ONCE
            await loadPermissions()

            // Clock in ONLY ONCE
            try {
                await axios.post('/api/attendances/clock-in', {
                    user_id: userData.id,
                    method: 'web'
                })
                console.log('✅ Clock-in successful')
            } catch (clockInError) {
                console.warn('⚠️ Clock-in failed:', clockInError)
                // Don't block login if clock-in fails
            }

            return response

        } catch (err: any) {
            console.error('❌ Login error:', err)
            error.value = err.response?.data?.message || err.message || 'Login failed'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Logout user
     */
    const logout = async () => {
        try {
            if (token.value && user.value) {
                await axios.post('/api/auth/logout-with-clock-out', {
                    user_id: user.value.id,
                    method: 'web'
                })
            }
        } catch (err) {
            console.warn('⚠️ Logout API error:', err)
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

            router.push({ name: 'Login' })
        }
    }

    /**
     * Fetch current user data
     */
    const fetchCurrentUser = async () => {
        if (!token.value) return

        try {
            const response = await axios.get('/api/auth/user')
            user.value = response.data
            localStorage.setItem('user', JSON.stringify(response.data))

            // Reload permissions when user data is refreshed
            permissionsLoaded.value = false
            isLoadingPermissions.value = false
            await loadPermissions()

            return response.data
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
            console.log('🔄 Initializing auth store...')
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