import axiosClient from '@/axios'

export interface PhilippineHoliday {
  date: string
  name: string
}

export async function getPhilippineHolidays(from: string, to: string): Promise<PhilippineHoliday[]> {
  const response = await axiosClient.get('/api/public/holidays', { params: { from, to } })
  return response.data?.data || []
}
