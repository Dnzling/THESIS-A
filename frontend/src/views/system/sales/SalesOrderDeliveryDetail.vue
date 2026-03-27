<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Delivery {{ delivery?.tracking_number || '#' + deliveryId }}</h1>
          <p class="text-sm text-gray-500">Order {{ delivery?.order?.order_number || '-' }}</p>
        </div>
      </div>
    </div>

    <Card v-if="delivery">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="text-xs text-gray-500">Status</p>
            <Tag :value="delivery.status" :severity="statusSeverity(delivery.status)" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Customer</p>
            <p class="font-semibold">{{ delivery.order?.customer_name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Phone</p>
            <p class="font-semibold">{{ delivery.order?.customer_phone || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Email</p>
            <p class="font-semibold">{{ delivery.order?.delivery_email || '-' }}</p>
          </div>
        </div>

        <Divider class="my-4" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500">Delivery Address</p>
            <p class="font-semibold">{{ delivery.order?.delivery_address || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Coordinates</p>
            <p class="font-semibold">
              {{ delivery.order?.delivery_latitude || '-' }}, {{ delivery.order?.delivery_longitude || '-' }}
            </p>
          </div>
        </div>
      </template>
    </Card>

  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'

const route = useRoute()
const router = useRouter()
const deliveryId = Number(route.params.id)
const delivery = ref<any>(null)

const loadDelivery = async () => {
  const res = await salesService.getOrderDelivery(deliveryId)
  delivery.value = res?.data || res
}


const statusSeverity = (value: string) => {
  if (value === 'delivered') return 'success'
  if (value === 'out_for_delivery' || value === 'in_transit') return 'info'
  if (value === 'failed_delivery' || value === 'cancelled') return 'danger'
  return 'warning'
}

const goBack = () => router.push({ name: 'sales.deliveries' })

onMounted(async () => {
  await loadDelivery()
})
</script>
