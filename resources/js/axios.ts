import axios, { type AxiosInstance } from 'axios'
import { router } from '@inertiajs/vue3'
import { showResponseDialog } from './utils/responseDialogBus'
const pendingRequests = new Map<string, AbortController>()
let csrfPromise: Promise<any> | null = null

const hasXsrfCookie = () => false

const ensureCsrfCookie = async () => {
    return
}

const getRequestKey = (config: any): string => {
    if (config.method?.toLowerCase() !== 'get') return ''
    const baseUrl = config.baseURL || ''
    const url = config.url || ''
    return `GET:${baseUrl}${url}:${JSON.stringify(config.params ?? {})}`
}

const applyBaseConfig = (client: AxiosInstance) => {
    client.defaults.baseURL = import.meta.env.VITE_API_BASE_URL
    client.defaults.withCredentials = false
    client.defaults.timeout = 30000
    client.defaults.headers.common.Accept = 'application/json'
    client.defaults.headers.common['Content-Type'] = 'application/json'
    delete client.defaults.xsrfCookieName
    delete client.defaults.xsrfHeaderName
}

const attachInterceptors = (client: AxiosInstance) => {
    client.interceptors.request.use(
        async (config) => {
            await ensureCsrfCookie()
            const token = localStorage.getItem('auth_token') || localStorage.getItem('access_token')
            if (token && !config.headers.Authorization) {
                config.headers.Authorization = `Bearer ${token}`
            }

            if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
                if (config.headers) {
                    delete config.headers['Content-Type']
                    delete config.headers['content-type']
                }
            }

            const key = getRequestKey(config)
            if (!key) return config

            if (pendingRequests.has(key)) {
                const controller = new AbortController()
                controller.abort(`Duplicate GET cancelled: ${config.url}`)
                config.signal = controller.signal
                return config
            }

            const controller = new AbortController()
            pendingRequests.set(key, controller)
            config.signal = controller.signal
            return config
        },
        (error) => Promise.reject(error)
    )

    client.interceptors.response.use(
        (response) => {
            const key = getRequestKey(response.config)
            if (key) pendingRequests.delete(key)
            const method = String(response.config?.method || '').toLowerCase()
            const suppress = response.config?.headers?.['X-Suppress-Dialog']
            if (method && method !== 'get' && method !== 'head' && !suppress) {
                const message =
                    response.data?.message ||
                    (response.data?.success ? 'Request completed successfully.' : null) ||
                    'Request completed successfully.'
                showResponseDialog({
                    severity: 'success',
                    title: 'Success',
                    message,
                })
            }
            return response
        },
        async (error) => {
            if (error.config) {
                const key = getRequestKey(error.config)
                if (key) pendingRequests.delete(key)
            }

            if (axios.isCancel(error) || error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
                return new Promise(() => { })
            }

            if (error.response?.status === 419 && error.config && !error.config.__retriedCsrf) {
                error.config.__retriedCsrf = true
                try {
                    await client.get('/sanctum/csrf-cookie', {
                        headers: { 'X-Suppress-Dialog': '1' },
                    })
                    return client.request(error.config)
                } catch (csrfError) {
                    // fall through to normal error handling
                }
            }

            if (error.response?.status === 401) {
                localStorage.removeItem('auth_token')
                localStorage.removeItem('access_token')
                localStorage.removeItem('user')
                document.cookie = 'auth_token=; Max-Age=0; path=/; SameSite=Lax'

                void redirectToLogin()
            }
            
            if (error.response?.status === 403) {
                void redirectToUnauthorized()
            }

            const method = String(error.config?.method || '').toLowerCase()
            const suppress = error.config?.headers?.['X-Suppress-Dialog']
            if (method && method !== 'get' && method !== 'head' && !suppress) {
                const message =
                    error.response?.data?.message ||
                    error.response?.data?.error ||
                    error.message ||
                    'Request failed.'
                showResponseDialog({
                    severity: 'error',
                    title: 'Error',
                    message,
                })
            }

            return Promise.reject(error)
        }
    )
}

const redirectToLogin = async () => {
    router.visit('/login')
}

const redirectToUnauthorized = async () => {
    router.visit('/unauthorized')
}

applyBaseConfig(axios)
attachInterceptors(axios)

const axiosClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    timeout: 30000,
})

attachInterceptors(axiosClient)

export { applyBaseConfig, attachInterceptors }
export default axiosClient
