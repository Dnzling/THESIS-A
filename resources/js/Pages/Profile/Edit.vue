<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import SystemLayout from '@/Layouts/SystemLayout.vue'
import EmployeeProfile from './EmployeeProfile.vue'

defineOptions({ layout: SystemLayout })

const authStore = useAuthStore()
const role = computed(() => String(authStore.user?.role || '').toLowerCase())
const isCustomer = computed(() => role.value.includes('customer'))
const isSupplier = computed(() => role.value.includes('supplier'))

onMounted(() => {
    if (isCustomer.value) {
        router.visit('/shop/profile')
        return
    }
    if (isSupplier.value) {
        router.visit('/supplier-portal/profile')
    }
})
</script>

<template>
    <Head title="Profile" />
    <div v-if="!isCustomer && !isSupplier">
        <EmployeeProfile />
    </div>
    <div v-else class="min-h-screen flex items-center justify-center text-slate-500">
        Redirecting to your profile...
    </div>
</template>
