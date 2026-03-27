import type { RouteRecordRaw } from 'vue-router'

const SystemLayout = () => import('../../layouts/SystemLayout.vue')

const logisticsRoutes: RouteRecordRaw[] = [
  {
    path: '/logistics',
    component: SystemLayout,
    name: 'logistics',
    redirect: '/logistics/deliveries',
    meta: {
      requiresAuth: true,
      module: 'logistics',
      permission: 'logistics.deliveries.view',
    },
    children: [
      {
        path: 'deliveries',
        name: 'logistics.deliveries',
        component: () => import('../../views/system/logistics/Deliveries/DeliveryIndex.vue'),
        meta: {
          title: 'Deliveries',
          permission: 'logistics.deliveries.view',
          breadcrumb: [
            { label: 'Logistics', to: '/logistics' },
            { label: 'Deliveries' },
          ],
        },
      },
      {
        path: 'deliveries/create',
        name: 'logistics.deliveries.create',
        component: () => import('../../views/system/logistics/Deliveries/DeliveryCreate.vue'),
        meta: {
          title: 'Create Delivery',
          permission: 'logistics.deliveries.manage',
          breadcrumb: [
            { label: 'Logistics', to: '/logistics' },
            { label: 'Deliveries', to: '/logistics/deliveries' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'deliveries/:source/:orderId',
        name: 'logistics.deliveries.detail',
        component: () => import('../../views/system/logistics/Deliveries/DeliveryDetail.vue'),
        meta: {
          title: 'Delivery Detail',
          permission: 'logistics.deliveries.view',
          breadcrumb: [
            { label: 'Logistics', to: '/logistics' },
            { label: 'Deliveries', to: '/logistics/deliveries' },
            { label: 'Detail' },
          ],
        },
      },
      {
        path: 'vehicles',
        name: 'logistics.vehicles',
        component: () => import('../../views/system/inventory/Deliveries/DeliveryVehicles.vue'),
        meta: {
          title: 'Fleet',
          permission: 'logistics.fleet.view',
          breadcrumb: [
            { label: 'Logistics', to: '/logistics' },
            { label: 'Fleet' },
          ],
        },
      },
      {
        path: 'zones',
        name: 'logistics.zones',
        component: () => import('../../views/system/logistics/Zones/DeliveryZonesIndex.vue'),
        meta: {
          title: 'Delivery Zones',
          permission: 'logistics.zones.view',
          breadcrumb: [
            { label: 'Logistics', to: '/logistics' },
            { label: 'Delivery Zones' },
          ],
        },
      },
    ],
  },
]

export default logisticsRoutes

