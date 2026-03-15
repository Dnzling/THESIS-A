<template>
  <div class="rounded-lg border border-gray-200 p-4 h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">{{ title }}</h3>
      <Skeleton v-if="loading" width="60px" height="20px" />
      <span v-else class="text-xs text-gray-500">
        {{ dataPoints }} data points
      </span>
    </div>

    <!-- Chart or Empty State -->
    <Skeleton v-if="loading" width="100%" height="300px" />
    <div v-else-if="hasData" class="relative h-80">
      <Chart :type="chartType" :data="chartData" :options="chartOptions" />
    </div>
    <div v-else class="flex items-center justify-center h-80 bg-gray-50 rounded">
      <div class="text-center">
        <i class="pi pi-inbox text-4xl text-gray-300 mb-2 block" />
        <p class="text-gray-500">No data available</p>
      </div>
    </div>

    <!-- Footer -->
    <div v-if="footer" class="mt-4 pt-4 border-t border-gray-200">
      <p class="text-sm text-gray-600">{{ footer }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Chart from 'primevue/chart'
import Skeleton from 'primevue/skeleton'

interface Props {
  title: string
  chartType: 'line' | 'bar' | 'doughnut' | 'pie'
  chartData: any
  chartOptions?: any
  loading?: boolean
  footer?: string
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  chartOptions: () => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
  }),
})

const dataPoints = computed(() => {
  if (!props.chartData || !props.chartData.labels) return 0
  return props.chartData.labels.length
})

const hasData = computed(() => {
  return dataPoints.value > 0 && props.chartData?.datasets?.length > 0
})
</script>
