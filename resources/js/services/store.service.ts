import axios from '../axios'

export const storeService = {
    async getDashboard() {
        const response = await axios.get('/api/store/dashboard')
        return response.data
    },

    async getBranchExpenses(params: Record<string, any> = {}) {
        const response = await axios.get('/api/store-module/expenses', { params })
        return response.data
    },

    async getBranchExpense(id: number | string) {
        const response = await axios.get(`/api/store-module/expenses/${id}`)
        return response.data
    },

    async createBranchExpense(payload: FormData) {
        const response = await axios.post('/api/store-module/expenses', payload)
        return response.data
    },

    async approveBranchExpense(id: number | string, notes?: string) {
        const response = await axios.post(`/api/store-module/expenses/${id}/approve`, { notes })
        return response.data
    },

    async rejectBranchExpense(id: number | string, notes: string) {
        const response = await axios.post(`/api/store-module/expenses/${id}/reject`, { notes })
        return response.data
    },

    async submitBranchExpenseActual(id: number | string, payload: FormData) {
        const response = await axios.post(`/api/store-module/expenses/${id}/submit-actual`, payload)
        return response.data
    },
}

export default storeService
