// axios.ts
import axios from 'axios'
import router from './router'

// ==================== PENDING REQUEST DEDUPLICATION ====================
// Tracks in-flight GET requests by key. If the same GET fires again before
// the first one completes, the duplicate is cancelled immediately.
// POST/PUT/DELETE are never deduplicated (they are intentional mutations).
const pendingRequests = new Map<string, AbortController>()

const generateKey = (config: any): string => {
    // Only deduplicate GET requests
    if (config.method?.toLowerCase() !== 'get') return ''
    return `GET:${config.url}:${JSON.stringify(config.params ?? {})}`
}

// ==================== AXIOS INSTANCE ====================
const axiosClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    timeout: 30000,
})

// ==================== REQUEST INTERCEPTOR ====================
axiosClient.interceptors.request.use(
    (config) => {
        // Attach auth token
        const token = localStorage.getItem('auth_token') || localStorage.getItem('access_token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        const key = generateKey(config)

        if (key) {
            // If an identical GET is already in-flight, cancel the NEW duplicate
            if (pendingRequests.has(key)) {
                const controller = new AbortController()
                controller.abort(`Duplicate GET cancelled: ${config.url}`)
                config.signal = controller.signal
            } else {
                // Register this request
                const controller = new AbortController()
                pendingRequests.set(key, controller)
                config.signal = controller.signal
            }
        }

        return config
    },
    (error) => Promise.reject(error)
)

// ==================== RESPONSE INTERCEPTOR ====================
axiosClient.interceptors.response.use(
    (response) => {
        // Clean up on success
        const key = generateKey(response.config)
        if (key) pendingRequests.delete(key)
        return response
    },
    (error) => {
        // Clean up on error/cancel
        if (error.config) {
            const key = generateKey(error.config)
            if (key) pendingRequests.delete(key)
        }

        // Silently swallow cancelled duplicate requests
        if (axios.isCancel(error) || error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
            return new Promise(() => {}) // never resolves — caller gets no response
        }

        // Handle 401 Unauthorized
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token')
            localStorage.removeItem('access_token')
            localStorage.removeItem('user')

            const currentRoute = router.currentRoute.value?.name
            if (currentRoute !== 'Login') {
                router.push({ name: 'Login' })
            }
        }

        return Promise.reject(error)
    }
)

export default axiosClient
