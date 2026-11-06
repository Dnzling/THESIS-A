import axiosClient from '@/axios'

export default {
  staff: () => axiosClient.get('/api/staff-chats/staff').then(r => r.data),
  conversations: () => axiosClient.get('/api/staff-chats/conversations').then(r => r.data),
  start: (userId: number) => axiosClient.post('/api/staff-chats/conversations', { user_id: userId }, { headers: { 'X-Suppress-Success-Dialog': '1' } }).then(r => r.data),
  messages: (id: number) => axiosClient.get(`/api/staff-chats/conversations/${id}/messages`, { params: { per_page: 120 } }).then(r => r.data),
  send: (id: number, payload: FormData) => axiosClient.post(`/api/staff-chats/conversations/${id}/messages`, payload, { headers: { 'X-Suppress-Success-Dialog': '1' } }).then(r => r.data),
}
