import axiosClient from '../axios'

class PaymongoService {
  async createIntent(payload: {
    amount: number
    currency?: string
    description?: string
    statement_descriptor?: string
    payment_method_allowed: string[]
    metadata?: Record<string, unknown>
    store_id: number
    payable_type: string
    payable_id: number
  }) {
    const response = await axiosClient.post('/api/payments/paymongo/create', payload)
    return response.data
  }

  async getIntent(paymentIntentId: string) {
    const response = await axiosClient.get(`/api/payments/paymongo/${paymentIntentId}`)
    return response.data
  }

  async getLatestIntentByPayable(payableType: string, payableId: number) {
    const response = await axiosClient.get('/api/payments/paymongo/latest', {
      params: { payable_type: payableType, payable_id: payableId },
    })
    return response.data
  }

  async startGcash(
    paymentIntentId: string,
    payload: { name: string; email: string; phone: string; return_url?: string }
  ) {
    const response = await axiosClient.post(`/api/payments/paymongo/${paymentIntentId}/gcash-start`, payload)
    return response.data
  }
}

export default new PaymongoService()
