import axiosClient from '../axios'

export interface Supplier {
  id?: number
  supplier_name: string
  company_name: string
  contact_person: string
  email: string
  phone: string
  address: string
  city: string
  state: string
  postal_code: string
  country: string
  payment_terms: string
  tax_id?: string
  category: 'raw_materials' | 'furniture' | 'accessories' | 'services'
  status?: 'active' | 'inactive' | 'blacklisted'
  rating?: number
  quality_score?: number
  average_delivery_days?: number
  recent_delay_percentage?: number
}

// ==================== SUPPLIER PORTAL INTERFACES ====================
export interface SupplierPortal {
  id: number
  user_id: number
  supplier_id?: number
  supplier?: any
  status: 'pending' | 'approved' | 'rejected'
  rejection_reason?: string
  verified_by?: number
  verified_at?: string
  resubmission_count: number
  last_submission_at?: string
  created_at: string
  updated_at: string
}

export interface SupplierVerificationDocument {
  id: number
  supplier_portal_id: number
  document_type: 'business_license' | 'tax_id' | 'company_registration' | 'bank_details'
  file_path: string
  original_filename: string
  file_mime_type: string
  file_size: number
  status: 'pending' | 'approved' | 'rejected'
  rejection_reason?: string
  reviewed_by?: number
  reviewed_at?: string
  created_at: string
  updated_at: string
}

export interface SupplierRFQFeedback {
  id: number
  supplier_portal_id: number
  rfq_id: number
  rfq_item_id: number
  quoted_price: number
  description?: string
  submitted_at: string
  created_at: string
  updated_at: string
}

export interface SupplierPOFeedback {
  id: number
  supplier_portal_id: number
  purchase_order_id: number
  response: 'accepted' | 'rejected'
  rejection_reason?: string
  receipt_status: 'pending' | 'confirmed'
  expected_delivery_date?: string
  delivery_quantity?: number
  delivery_notes?: string
  receipt_confirmed_at?: string
  submitted_at: string
  created_at: string
  updated_at: string
}

export interface SupplierPOShipment {
  id: number
  purchase_order_id: number
  supplier_id: number
  branch_id: number
  truck_number?: string
  plate_number?: string
  driver_name: string
  driver_contact?: string
  origin_address?: string
  destination_address?: string
  current_latitude?: number
  current_longitude?: number
  distance_km?: number
  cost_per_km?: number
  shipping_cost?: number
  dispatched_at?: string
  delivered_at?: string
  status?: 'pending' | 'in_transit' | 'delivered' | 'cancelled'
  created_at?: string
  updated_at?: string
}

export interface SupplierDeliveryTemplate {
  id: number
  supplier_portal_id: number
  supplier_id: number
  truck_brand?: string
  truck_type?: string
  wheel_count?: number
  plate_number?: string
  driver_name?: string
  driver_contact?: string
  cost_per_km?: number
  created_at?: string
  updated_at?: string
}

export interface SupplierPortalRegistrationData {
  company_name: string
  contact_person: string
  phone: string
  address: string
  city: string
  province: string
  postal_code: string
  country: string
  tin?: string
  payment_terms: string
  supplier_type: 'raw_materials' | 'furniture' | 'accessories' | 'services'
}

class SupplierService {
  private baseUrl = '/api/suppliers'
  private portalBaseUrl = '/api/supplier-portal'
  private verificationBaseUrl = '/api/supplier-verifications'

  // ==================== SUPPLIER MANAGEMENT ====================
  async getSuppliers(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}`, { params })
    return response.data
  }

  async searchSuppliers(query: string) {
    const response = await axiosClient.get(`${this.baseUrl}/search`, { params: { q: query } })
    return response.data
  }

  async createSupplier(data: Supplier) {
    const response = await axiosClient.post(`${this.baseUrl}`, data)
    return response.data
  }

  async updateSupplier(id: number, data: Partial<Supplier>) {
    const response = await axiosClient.put(`${this.baseUrl}/${id}`, data)
    return response.data
  }

  async deleteSupplier(id: number) {
    const response = await axiosClient.delete(`${this.baseUrl}/${id}`)
    return response.data
  }

  async getSuppliersByCategory(category: string) {
    const response = await axiosClient.get(`${this.baseUrl}/category/${category}`)
    return response.data
  }

  // ==================== SUPPLIER PERFORMANCE ====================
  async getPerformanceMetrics(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/${id}/performance`)
    return response.data
  }

  async getPerformanceHistory(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/${id}/performance-history`)
    return response.data
  }

  async getAtRiskSuppliers() {
    const response = await axiosClient.get(`${this.baseUrl}/at-risk`)
    return response.data
  }

  async getTopPerformers(limit: number = 5) {
    const response = await axiosClient.get(`${this.baseUrl}/top-performers`, { params: { limit } })
    return response.data
  }

  // ==================== SUPPLIER PAYMENTS ====================
  async getPaymentHistory(id: number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/${id}/payments`, { params })
    return response.data
  }

  async recordPayment(id: number, data: any) {
    const response = await axiosClient.post(`${this.baseUrl}/${id}/payments/record`, data)
    return response.data
  }

  async getAgingReport(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/${id}/payments/aging`)
    return response.data
  }

  async getPaymentStatus(id: number) {
    const response = await axiosClient.get(`${this.baseUrl}/${id}/payment-status`)
    return response.data
  }

  // ==================== SUPPLIER RECOMMENDATIONS ====================
  async getRecommendedSuppliers(productId: number) {
    const response = await axiosClient.get(`${this.baseUrl}/product/${productId}/recommended`)
    return response.data
  }

  async getSuppliersForCategory(category: string) {
    const response = await axiosClient.get(`${this.baseUrl}/category/${category}/list`)
    return response.data
  }

  async getAlternativeSuppliers(supplierId: number, category?: string) {
    const response = await axiosClient.get(`${this.baseUrl}/${supplierId}/alternatives`, { 
      params: { category } 
    })
    return response.data
  }

  // ==================== DASHBOARD ====================
  async getDashboard() {
    const response = await axiosClient.get(`${this.baseUrl}/dashboard`)
    return response.data
  }

  // ==================== SUPPLIER PORTAL ====================
  
  // Supplier Portal Registration & Management
  async registerSupplierPortal(data: SupplierPortalRegistrationData) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/register`, data)
    return response.data
  }

  async getMyPortal(): Promise<{ success: boolean; data: SupplierPortal }> {
    const response = await axiosClient.get(`${this.portalBaseUrl}/my-portal`)
    return response.data
  }

  async updatePortalCoordinates(data: { latitude: number; longitude: number }) {
    const response = await axiosClient.put(`${this.portalBaseUrl}/coordinates`, data)
    return response.data
  }

  async getPortalStats(): Promise<{ success: boolean; data: any }> {
    const response = await axiosClient.get(`${this.portalBaseUrl}/stats`)
    return response.data
  }

  async getLinkedStores() {
    const response = await axiosClient.get(`${this.portalBaseUrl}/stores/linked`)
    return response.data
  }

  async searchStores(params?: { search?: string; limit?: number }) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/stores/search`, { params })
    return response.data
  }

  async linkStore(storeId: number) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/stores/link`, { store_id: storeId })
    return response.data
  }

  async getLinkedStoreDetail(storeId: number) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/stores/${storeId}`)
    return response.data
  }

  // Document Management
  async uploadVerificationDocument(file: File, documentType: string) {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('document_type', documentType)
    
    const response = await axiosClient.post(`${this.portalBaseUrl}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  }

  async getMyDocuments(): Promise<{ success: boolean; data: SupplierVerificationDocument[] }> {
    const response = await axiosClient.get(`${this.portalBaseUrl}/my-documents`)
    return response.data
  }

  async downloadDocument(documentId: number) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/documents/${documentId}/download`, {
      responseType: 'blob',
    })
    return response.data
  }

  // RFQ Feedback
  async getSupplierRFQs(params?: any) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/rfqs`, { params })
    return response.data
  }

  async getSupplierRFQDetail(rfqId: number): Promise<{ success: boolean; data: any }> {
    const response = await axiosClient.get(`${this.portalBaseUrl}/rfqs/${rfqId}`)
    return response.data
  }

  async submitRFQFeedback(data: {
    rfq_id: number
    rfq_item_id: number
    quoted_price: number
    description?: string
  }) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/rfq-feedbacks`, data)
    return response.data
  }

  async getMyRFQFeedbacks(params?: any) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/rfq-feedbacks`, { params })
    return response.data
  }

  async acceptNegotiation(id: number) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/rfq-negotiations/${id}/accept`)
    return response.data
  }

  async rejectNegotiation(id: number) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/rfq-negotiations/${id}/reject`)
    return response.data
  }

  // PO Feedback
  async getSupplierPOs(params?: any) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/pos`, { params })
    return response.data
  }

  async getSupplierPODetail(poId: number): Promise<{ success: boolean; data: any }> {
    const response = await axiosClient.get(`${this.portalBaseUrl}/pos/${poId}`)
    return response.data
  }

  async createInvoiceFromGoodsReceipt(data: { purchase_order_id: number; goods_receipt_id: number }) {
    const response = await axiosClient.post(`/api/procurement/invoices/from-grn`, data)
    return response.data
  }

  async submitPOFeedback(data: {
    purchase_order_id: number
    response: 'accepted' | 'rejected'
    rejection_reason?: string
    expected_delivery_date?: string
    delivery_quantity?: number
    delivery_notes?: string
  }) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/po-feedbacks`, data)
    return response.data
  }

  async confirmPOReceipt(feedbackId: number, data: {
    delivery_quantity: number
    delivery_notes?: string
  }) {
    const response = await axiosClient.post(
      `${this.portalBaseUrl}/po-feedbacks/${feedbackId}/confirm-receipt`,
      data
    )
    return response.data
  }

  async getMyPOFeedbacks(params?: any) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/po-feedbacks`, { params })
    return response.data
  }

  async getPOShipment(poId: number) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/po-shipments/${poId}`)
    return response.data
  }

  async getShipments(params?: any) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/shipments`, { params })
    return response.data
  }

  async getShipment(shipmentId: number) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/shipments/${shipmentId}`)
    return response.data
  }

  async createPOShipment(data: {
    purchase_order_id: number
    truck_number?: string
    truck_brand?: string
    truck_type?: string
    wheel_count?: number
    plate_number?: string
    driver_name: string
    driver_contact?: string
    cost_per_km: number
    distance_km?: number | null
    current_latitude?: number | null
    current_longitude?: number | null
    dispatched_at?: string
    tax_rate?: number
    expected_delivery_date?: string | null
  }) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/po-shipments`, data)
    return response.data
  }

  async getShipmentLogs(shipmentId: number) {
    const response = await axiosClient.get(`${this.portalBaseUrl}/shipments/${shipmentId}/logs`)
    return response.data
  }

  async addShipmentLog(shipmentId: number, data: {
    event_type: string
    notes?: string
    latitude?: number | null
    longitude?: number | null
  }) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/shipments/${shipmentId}/logs`, data)
    return response.data
  }

  async markShipmentDelivered(shipmentId: number, data: {
    notes?: string
    latitude?: number | null
    longitude?: number | null
    receiver_name: string
    attachments: File[]
  }) {
    const formData = new FormData()
    if (data.notes) {
      formData.append('notes', data.notes)
    }
    if (data.receiver_name) {
      formData.append('receiver_name', data.receiver_name)
    }
    if (data.latitude !== undefined && data.latitude !== null) {
      formData.append('latitude', String(data.latitude))
    }
    if (data.longitude !== undefined && data.longitude !== null) {
      formData.append('longitude', String(data.longitude))
    }
    data.attachments.forEach((attachment) => {
      formData.append('attachments[]', attachment)
    })

    const response = await axiosClient.post(
      `${this.portalBaseUrl}/shipments/${shipmentId}/deliver`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      }
    )
    return response.data
  }

  async getDeliveryTemplates() {
    const response = await axiosClient.get(`${this.portalBaseUrl}/delivery-templates`)
    return response.data
  }

  async createDeliveryTemplate(data: {
    truck_brand?: string
    truck_type?: string
    wheel_count?: number
    plate_number?: string
    driver_name?: string
    driver_contact?: string
    cost_per_km?: number
  }) {
    const response = await axiosClient.post(`${this.portalBaseUrl}/delivery-templates`, data)
    return response.data
  }

  async updateDeliveryTemplate(id: number, data: {
    truck_brand?: string
    truck_type?: string
    wheel_count?: number
    plate_number?: string
    driver_name?: string
    driver_contact?: string
    cost_per_km?: number
  }) {
    const response = await axiosClient.put(`${this.portalBaseUrl}/delivery-templates/${id}`, data)
    return response.data
  }

  async deleteDeliveryTemplate(id: number) {
    const response = await axiosClient.delete(`${this.portalBaseUrl}/delivery-templates/${id}`)
    return response.data
  }

  // ==================== SUPPLIER VERIFICATION (Admin) ====================
  
  async getPendingVerifications(params?: any) {
    const response = await axiosClient.get(`${this.verificationBaseUrl}/pending`, { params })
    return response.data
  }

  async getAllVerifications(params?: any) {
    const response = await axiosClient.get(`${this.verificationBaseUrl}`, { params })
    return response.data
  }

  async getVerificationDetail(id: number) {
    const response = await axiosClient.get(`${this.verificationBaseUrl}/${id}`)
    return response.data
  }

  async approveSupplierVerification(id: number) {
    const response = await axiosClient.post(`${this.verificationBaseUrl}/${id}/approve`)
    return response.data
  }

  async rejectSupplierVerification(id: number, data: { rejection_reason: string }) {
    const response = await axiosClient.post(`${this.verificationBaseUrl}/${id}/reject`, data)
    return response.data
  }

  async reviewDocument(documentId: number, data: {
    status: 'approved' | 'rejected'
    rejection_reason?: string
  }) {
    const response = await axiosClient.post(
      `${this.verificationBaseUrl}/documents/${documentId}/review`,
      data
    )
    return response.data
  }
}

export default new SupplierService()
