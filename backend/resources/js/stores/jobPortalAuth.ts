import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import hrService from '../services/hr.services'

interface PortalUser {
  id: number
  fname: string
  lname: string
  email: string
  role?: string
  verified?: boolean
}

export const useJobPortalAuthStore = defineStore('jobPortalAuth', () => {
  const token = ref(localStorage.getItem('job_portal_token'))
  const user = ref<PortalUser | null>(JSON.parse(localStorage.getItem('job_portal_user') || 'null'))
  const pendingRedirect = ref(localStorage.getItem('job_portal_redirect') || '')

  const isAuthenticated = computed(() => !!token.value)

  const persist = () => {
    if (token.value) localStorage.setItem('job_portal_token', token.value)
    else localStorage.removeItem('job_portal_token')

    if (user.value) localStorage.setItem('job_portal_user', JSON.stringify(user.value))
    else localStorage.removeItem('job_portal_user')

    if (pendingRedirect.value) localStorage.setItem('job_portal_redirect', pendingRedirect.value)
    else localStorage.removeItem('job_portal_redirect')
  }

  const setPendingRedirect = (path: string) => {
    pendingRedirect.value = path
    persist()
  }

  const consumePendingRedirect = () => {
    const target = pendingRedirect.value
    pendingRedirect.value = ''
    persist()
    return target
  }

  const register = async (payload: { fname: string; lname: string; email: string; password: string }) => {
    const response = await hrService.portalRegister(payload)
    token.value = response.token
    user.value = response.user
    persist()
    return response
  }

  const login = async (payload: { email: string; password: string }) => {
    const response = await hrService.portalLogin(payload)
    if (response.token) token.value = response.token
    if (response.user) user.value = response.user
    persist()
    return response
  }

  const verifyOtp = async (otp: string) => {
    const response = await hrService.portalVerifyOtp(otp)
    if (user.value) {
      user.value = { ...user.value, verified: true }
      persist()
    }
    return response
  }

  const resendOtp = async () => {
    return hrService.portalResendOtp()
  }

  const fetchMe = async () => {
    const response = await hrService.portalMe()
    user.value = response.data
    persist()
    return response.data
  }

  const logout = async () => {
    try {
      await hrService.portalLogout()
    } finally {
      token.value = null
      user.value = null
      pendingRedirect.value = ''
      persist()
    }
  }

  return {
    token,
    user,
    pendingRedirect,
    isAuthenticated,
    setPendingRedirect,
    consumePendingRedirect,
    register,
    login,
    verifyOtp,
    resendOtp,
    fetchMe,
    logout,
  }
})
