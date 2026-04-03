import axiosClient from '../axios'

class LogisticsService {
  private baseUrl = '/api/logistics'

  async getDeliveryOrders(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/delivery-orders`, { params })
    return response.data
  }

  async getDeliveryOrderDetail(source: 'ecommerce' | 'sales', orderId: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/delivery-orders/${source}/${orderId}`)
    return response.data
  }

  async getLogisticsEmployees(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/delivery-orders/logistics-employees`, { params })
    return response.data
  }

  async estimateDeliveryDistance(payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/delivery-orders/distance-estimate`, payload)
    return response.data
  }

  async assignDelivery(payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/delivery-orders/assign`, payload)
    return response.data
  }

  async updateUnifiedDeliveryStatus(source: 'ecommerce' | 'sales', orderId: string | number, payload: any) {
    const response = await axiosClient.put(`${this.baseUrl}/delivery-orders/${source}/${orderId}/status`, payload)
    return response.data
  }

  async getUnifiedDeliveryLogs(source: 'ecommerce' | 'sales', orderId: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/delivery-orders/${source}/${orderId}/logs`)
    return response.data
  }

  async addUnifiedDeliveryLog(source: 'ecommerce' | 'sales', orderId: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/delivery-orders/${source}/${orderId}/logs`, payload)
    return response.data
  }

  async markUnifiedDelivered(source: 'ecommerce' | 'sales', orderId: string | number, payload: FormData) {
    const response = await axiosClient.post(`${this.baseUrl}/delivery-orders/${source}/${orderId}/delivered`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  }

  async getDeliveries(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/deliveries`, { params })
    return response.data
  }

  async getDelivery(id: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/deliveries/${id}`)
    return response.data
  }

  async updateDeliveryStatus(id: string | number, payload: any) {
    const response = await axiosClient.put(`${this.baseUrl}/deliveries/${id}/status`, payload)
    return response.data
  }

  async getDrivers(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/deliveries/drivers`, { params })
    return response.data
  }

  async assignDriver(id: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/deliveries/${id}/assign-driver`, payload)
    return response.data
  }

  async uploadProof(id: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/deliveries/${id}/proof`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  }

  async getDeliveryLogs(id: string | number, params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/deliveries/${id}/logs`, { params })
    return response.data
  }

  async addDeliveryLog(id: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/deliveries/${id}/logs`, payload)
    return response.data
  }

  async getVehicles(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/vehicles`, { params })
    return response.data
  }

  async getVehicle(id: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/vehicles/${id}`)
    return response.data
  }

  async createVehicle(payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/vehicles`, payload)
    return response.data
  }

  async updateVehicle(id: string | number, payload: any) {
    const response = await axiosClient.put(`${this.baseUrl}/vehicles/${id}`, payload)
    return response.data
  }

  async getZones(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/zones`, { params })
    return response.data
  }

  async createZone(payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/zones`, payload)
    return response.data
  }

  async updateZone(id: string | number, payload: any) {
    const response = await axiosClient.put(`${this.baseUrl}/zones/${id}`, payload)
    return response.data
  }

  async getZoneRates(zoneId: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/zones/${zoneId}/rates`)
    return response.data
  }

  async addZoneRate(zoneId: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/zones/${zoneId}/rates`, payload)
    return response.data
  }

  async updateZoneRate(zoneId: string | number, rateId: string | number, payload: any) {
    const response = await axiosClient.put(`${this.baseUrl}/zones/${zoneId}/rates/${rateId}`, payload)
    return response.data
  }

  async deleteZoneRate(zoneId: string | number, rateId: string | number) {
    const response = await axiosClient.delete(`${this.baseUrl}/zones/${zoneId}/rates/${rateId}`)
    return response.data
  }

  async getTrips(params?: any) {
    const response = await axiosClient.get(`${this.baseUrl}/trips`, { params })
    return response.data
  }

  async getTrip(id: string | number) {
    const response = await axiosClient.get(`${this.baseUrl}/trips/${id}`)
    return response.data
  }

  async createTrip(payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/trips`, payload)
    return response.data
  }

  async updateTripStatus(id: string | number, payload: any) {
    const response = await axiosClient.put(`${this.baseUrl}/trips/${id}/status`, payload)
    return response.data
  }

  async addOrdersToTrip(id: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/trips/${id}/orders`, payload)
    return response.data
  }

  async removeOrdersFromTrip(id: string | number, payload: any) {
    const response = await axiosClient.post(`${this.baseUrl}/trips/${id}/orders/remove`, payload)
    return response.data
  }
}

export default new LogisticsService()
