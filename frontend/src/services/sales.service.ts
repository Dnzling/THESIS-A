import axiosClient from '@/axios'

class SalesService {
  async getDashboard(params?: any) {
    const res = await axiosClient.get('/api/sales/dashboard', { params })
    return res.data
  }

  async getLeads(params?: any) {
    const res = await axiosClient.get('/api/sales/crm/leads', { params })
    return res.data
  }

  async createLead(payload: any) {
    const res = await axiosClient.post('/api/sales/crm/leads', payload)
    return res.data
  }

  async updateLead(id: number | string, payload: any) {
    const res = await axiosClient.put(`/api/sales/crm/leads/${id}`, payload)
    return res.data
  }

  async updateLeadStage(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/crm/leads/${id}/stage`, payload)
    return res.data
  }

  async getLeadActivities(id: number | string, params?: any) {
    const res = await axiosClient.get(`/api/sales/crm/leads/${id}/activities`, { params })
    return res.data
  }

  async addLeadActivity(id: number | string, payload: any) {
    const res = await axiosClient.post(`/api/sales/crm/leads/${id}/activities`, payload)
    return res.data
  }

  async getPosProducts(params?: any) {
    const res = await axiosClient.get('/api/sales/pos/products', { params })
    return res.data
  }

  async checkout(payload: any) {
    const res = await axiosClient.post('/api/sales/pos/checkout', payload)
    return res.data
  }

  async getPosOrders(params?: any) {
    const res = await axiosClient.get('/api/sales/pos/orders', { params })
    return res.data
  }

  async getPosOrder(id: number | string) {
    const res = await axiosClient.get(`/api/sales/pos/orders/${id}`)
    return res.data
  }
}

export default new SalesService()

