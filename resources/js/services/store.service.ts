import axios from '../axios'

export const storeService = {
    async getDashboard() {
        const response = await axios.get('/api/store/dashboard')
        return response.data
    },
}
