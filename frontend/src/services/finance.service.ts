import axiosClient from '../axios'

const baseUrl = '/api/finance'

const financeService = {
  async getDashboard() {
    const response = await axiosClient.get(`${baseUrl}/dashboard`)
    return response.data
  },

  async approvePurchaseOrder(id: number) {
    const response = await axiosClient.post(`${baseUrl}/purchase-orders/${id}/approve`)
    return response.data
  },

  async rejectPurchaseOrder(id: number, payload: { reason: string }) {
      const response = await axiosClient.post(`${baseUrl}/purchase-orders/${id}/reject`, payload)
      return response.data
    },

  async getPurchaseOrders(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/purchase-orders`, { params })
    return response.data
  },

  async getPurchaseOrder(id: number | string) {
    const response = await axiosClient.get(`${baseUrl}/purchase-orders/${id}`)
    return response.data
  },

  async createPurchaseOrder(payload: any) {
    const response = await axiosClient.post(`${baseUrl}/purchase-orders`, payload)
    return response.data
  },

  async updatePurchaseOrder(id: number | string, payload: any) {
    const response = await axiosClient.put(`${baseUrl}/purchase-orders/${id}`, payload)
    return response.data
  },

  async deletePurchaseOrder(id: number | string) {
    const response = await axiosClient.delete(`${baseUrl}/purchase-orders/${id}`)
    return response.data
  },

  async sendPurchaseOrder(id: number | string) {
    const response = await axiosClient.post(`${baseUrl}/purchase-orders/${id}/send`)
    return response.data
  },

  async cancelPurchaseOrder(id: number | string) {
    const response = await axiosClient.post(`${baseUrl}/purchase-orders/${id}/cancel`)
    return response.data
  },

  async summaryPurchaseOrders() {
    const response = await axiosClient.get(`${baseUrl}/purchase-orders/summary`)
    return response.data
  },

  async printPurchaseOrder(id: number | string) {
    const response = await axiosClient.get(`${baseUrl}/purchase-orders/${id}/print`, { responseType: 'blob' })
    return response
  },

  async emailPurchaseOrder(id: number | string, payload: any) {
    const response = await axiosClient.post(`${baseUrl}/purchase-orders/${id}/email`, payload)
    return response.data
  },

  async labelPurchaseOrder(id: number | string) {
    const response = await axiosClient.get(`${baseUrl}/purchase-orders/${id}/label`, { responseType: 'blob' })
    return response
  },

  async requestRevisionPurchaseOrder(id: number | string, payload: any) {
    const response = await axiosClient.post(`${baseUrl}/purchase-orders/${id}/request-revision`, payload)
    return response.data
  },

  async getPendingReceipt(id: number | string) {
    const response = await axiosClient.get(`${baseUrl}/purchase-orders/${id}/pending-receipt`)
    return response.data
  },

  async getPayables(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/payables`, { params })
    return response.data
  },

  async getInvoice(id: number) {
    const response = await axiosClient.get(`${baseUrl}/invoices/${id}`)
    return response.data
  },

  async getInvoices(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/invoices`, { params })
    return response.data
  },

  async matchInvoice(id: number) {
    const response = await axiosClient.post(`${baseUrl}/invoices/${id}/match`)
    return response.data
  },

  async approveInvoice(id: number) {
    const response = await axiosClient.post(`${baseUrl}/invoices/${id}/approve`)
    return response.data
  },

  async markInvoicePaid(id: number, payload: { payment_method: string; payment_amount: number }) {
    const response = await axiosClient.post(`${baseUrl}/invoices/${id}/mark-paid`, payload)
    return response.data
  },

  async getSupplierPayments(params?: any) {
    const response = await axiosClient.get('/api/procurement/payments', { params })
    return response.data
  },

  async approveSupplierPayment(id: number) {
    const response = await axiosClient.post(`/api/procurement/payments/${id}/approve`)
    return response.data
  },

  async processSupplierPayment(id: number) {
    const response = await axiosClient.post(`/api/procurement/payments/${id}/process`)
    return response.data
  },

  async getReceivables(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/receivables`, { params })
    return response.data
  },

  async getExpenses(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/expenses`, { params })
    return response.data
  },

  async createExpense(payload: any) {
    const response = await axiosClient.post(`${baseUrl}/expenses`, payload)
    return response.data
  },

  async updateExpense(id: number, payload: any) {
    const response = await axiosClient.put(`${baseUrl}/expenses/${id}`, payload)
    return response.data
  },

  async approveExpense(id: number) {
    const response = await axiosClient.post(`${baseUrl}/expenses/${id}/approve`)
    return response.data
  },

  async rejectExpense(id: number, payload: { notes: string }) {
    const response = await axiosClient.post(`${baseUrl}/expenses/${id}/reject`, payload)
    return response.data
  },

  async markExpensePaid(id: number, payload: any) {
    const response = await axiosClient.post(`${baseUrl}/expenses/${id}/mark-paid`, payload)
    return response.data
  },

  async deleteExpense(id: number) {
    const response = await axiosClient.delete(`${baseUrl}/expenses/${id}`)
    return response.data
  },

  async getBudgets(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/budgets`, { params })
    return response.data
  },

  async createBudget(payload: any) {
    const response = await axiosClient.post(`${baseUrl}/budgets`, payload)
    return response.data
  },

  async updateBudget(id: number, payload: any) {
    const response = await axiosClient.put(`${baseUrl}/budgets/${id}`, payload)
    return response.data
  },

  async deleteBudget(id: number) {
    const response = await axiosClient.delete(`${baseUrl}/budgets/${id}`)
    return response.data
  },

  async getPayroll(params?: any) {
    const response = await axiosClient.get(`${baseUrl}/payroll`, { params })
    return response.data
  },

  async approvePayroll(id: number) {
    const response = await axiosClient.post(`/api/payroll/${id}/approve`)
    return response.data
  },

  async markPayrollPaid(id: number) {
    const response = await axiosClient.post(`/api/payroll/${id}/mark-paid`)
    return response.data
  },
}

export default financeService
