import axiosClient from '../axios'

class PaymongoService {
  async createIntent(payload: {
    amount: number
    currency?: string
    description?: string
    statement_descriptor?: string
    payment_method_allowed: string[]
    metadata?: Record<string, unknown>
    store_id?: number | null
    payable_type: string
    payable_id?: number | null
  }) {
    const response = await axiosClient.post('/api/payments/paymongo/create', payload)
    return response.data
  }

  async getIntent(paymentIntentId: string) {
    const response = await axiosClient.get(`/api/payments/paymongo/${paymentIntentId}`)
    return response.data
  }

  async getLatestIntentByPayable(payableType: string, payableId: number, opts?: { sync?: boolean }) {
    const response = await axiosClient.get('/api/payments/paymongo/latest', {
      params: { payable_type: payableType, payable_id: payableId, sync: opts?.sync ? 1 : undefined },
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

  async startWallet(
    paymentIntentId: string,
    walletType: 'gcash' | 'grab_pay' | 'paymaya',
    payload: { name: string; email: string; phone: string; return_url?: string }
  ) {
    const response = await axiosClient.post(`/api/payments/paymongo/${paymentIntentId}/wallet/${walletType}/start`, payload)
    return response.data
  }

  async createCheckoutSession(payload: {
    amount: number
    currency?: string
    description?: string
    payment_method_allowed?: string[]
    metadata?: Record<string, unknown>
    store_id?: number | null
    payable_type: string
    payable_id?: number | null
    success_url: string
    cancel_url: string
  }) {
    const response = await axiosClient.post('/api/payments/paymongo/checkout-session', payload)
    return response.data
  }

  async getPublicKey() {
    const response = await axiosClient.get('/api/payments/paymongo/public-key')
    return response.data
  }

  async startCard(
    paymentIntentId: string,
    payload: {
      name: string
      email: string
      phone?: string
      return_url?: string
      card_number: string
      exp_month: number
      exp_year: number
      cvc: string
    }
  ) {
    const response = await axiosClient.post(`/api/payments/paymongo/${paymentIntentId}/card-start`, payload)
    return response.data
  }
}

export default new PaymongoService()
