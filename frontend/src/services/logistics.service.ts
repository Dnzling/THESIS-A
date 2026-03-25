import axiosClient from '../axios'

class LogisticsService {
  private baseUrl = '/api/logistics'

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
}

export default new LogisticsService()

