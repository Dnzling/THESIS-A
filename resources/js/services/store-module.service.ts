import axiosClient from '../axios'

const baseUrl = '/api/store-module'

export const storeModuleService = {
  async getDashboard() {
    const response = await axiosClient.get(`${baseUrl}/dashboard`)
    return response.data
  },

  async getSettings() {
    const response = await axiosClient.get(`${baseUrl}/settings`)
    return response.data
  },

  async updateSettings(payload: FormData) {
    payload.set('_method', 'PUT')
    const response = await axiosClient.post(`${baseUrl}/settings`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async getEcommercePerformance(days = 30) {
    const response = await axiosClient.get(`${baseUrl}/ecommerce`, { params: { days } })
    return response.data
  },
}
