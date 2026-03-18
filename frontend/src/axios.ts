import axios, { type AxiosInstance } from 'axios'
const pendingRequests = new Map<string, AbortController>()

const getRequestKey = (config: any): string => {
    if (config.method?.toLowerCase() !== 'get') return ''
    const baseUrl = config.baseURL || ''
    const url = config.url || ''
    return `GET:${baseUrl}${url}:${JSON.stringify(config.params ?? {})}`
}

const applyBaseConfig = (client: AxiosInstance) => {
    client.defaults.baseURL = import.meta.env.VITE_API_BASE_URL
    client.defaults.withCredentials = true
    client.defaults.timeout = 30000
    client.defaults.headers.common.Accept = 'application/json'
    client.defaults.headers.common['Content-Type'] = 'application/json'
}

const attachInterceptors = (client: AxiosInstance) => {
    client.interceptors.request.use(
        (config) => {
            const token = localStorage.getItem('auth_token') || localStorage.getItem('access_token')
            if (token && !config.headers.Authorization) {
                config.headers.Authorization = `Bearer ${token}`
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
            return response
        },
        (error) => {
            if (error.config) {
                const key = getRequestKey(error.config)
                if (key) pendingRequests.delete(key)
            }

            if (axios.isCancel(error) || error.name === 'CanceledError' || error.code === 'ERR_CANCELED') {
                return new Promise(() => { })
            }

            if (error.response?.status === 401) {
                localStorage.removeItem('auth_token')
                localStorage.removeItem('access_token')
                localStorage.removeItem('user')

                void redirectToLogin()
            }

            return Promise.reject(error)
        }
    )
}

const redirectToLogin = async () => {
    const { default: router } = await import('./router')
    const currentRoute = router.currentRoute.value?.name
    if (currentRoute !== 'Login') {
        router.push({ name: 'Login' })
    }
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
