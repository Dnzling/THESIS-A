import { ref, watch } from 'vue'
import axios from '@/axios'

export type TrendPeriod = 'week' | 'month' | 'year'

export const trendPeriods: { label: string; value: TrendPeriod }[] = [
  { label: '7 days', value: 'week' },
  { label: 'Per month', value: 'month' },
  { label: 'Per year', value: 'year' },
]

export function useDashboardTrend(url: string) {
  const period = ref<TrendPeriod>('week')
  const points = ref<any[]>([])
  const loading = ref(true)
  const error = ref('')
  let currentRequest = 0

  const reload = async () => {
    const request = ++currentRequest
    loading.value = true
    error.value = ''
    try {
      const response = await axios.get(url, { params: { period: period.value } })
      if (request === currentRequest) points.value = response.data?.data?.points || []
    } catch (cause: any) {
      if (request === currentRequest) error.value = cause?.response?.data?.message || 'Could not load this trend.'
    } finally {
      if (request === currentRequest) loading.value = false
    }
  }

  watch(period, reload, { immediate: true })
  return { period, points, loading, error, reload }
}
