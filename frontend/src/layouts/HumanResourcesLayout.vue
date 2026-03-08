<!-- layouts/SystemLayout.vue -->
<template>
    <div class="flex h-screen w-full max-w-[200vw] overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar w-64 flex flex-col z-30 overflow-y-auto" style="background-color: #f0f3fa;">
            <!-- Logo section -->
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <!-- Logo / Icon -->
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg">
                        <!-- Optional image -->
                        <img src="../../public/F.svg" alt="Furnisync" class="w-20 h-20">
                    </div>
    
                    <!-- Brand Text -->
                    <div class="leading-tight" style="font-family: 'Poppins'">
                        <h1 class="text-lg font-semibold text-gray-900">
                            Furnisync
                        </h1>
                    </div>
                </div>
            </div>
    
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4">
                <div class="px-4 space-y-1 pb-4">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Main Menu</div>
    
                    <!-- Regular menu items -->
                    <template v-for="item in menuItems" :key="item.to">
                        <router-link :to="item.to"
                            class="text-sm flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                            <i :class="item.icon"></i>
                            <span>{{ item.label }}</span>
                        </router-link>
                    </template>
    
                    <!-- Settings accordion item -->
                    <div class="space-y-1">
                        <button @click="toggleSettingAccordion"
                            class="w-full text-sm flex items-center justify-between space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                            <div class="flex items-center space-x-3">
                                <i class="pi pi-cog text-gray-500 w-5"></i>
                                <span>Settings</span>
                            </div>
                            <i
                                :class="['pi', settingsOpen ? 'pi-chevron-down' : 'pi-chevron-right', 'text-gray-400 text-xs']"></i>
                        </button>
    
                        <!-- Settings submenu -->
                        <div v-show="settingsOpen" class="ml-8 space-y-1">
                            <router-link to="/hr/settings"
                                class="text-sm flex items-center space-x-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                <i class="pi pi-sliders-h text-gray-400 w-5"></i>
                                <span>General</span>
                            </router-link>
    
                            <!-- <router-link to="/hr/settings/users"
                                    class="text-sm flex items-center space-x-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                    <i class="pi pi-users text-gray-400 w-5"></i>
                                    <span>Users</span>
                                </router-link>
                                <router-link to="/hr/settings/roles"
                                    class="text-sm flex items-center space-x-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                    <i class="pi pi-shield text-gray-400 w-5"></i>
                                    <span>Roles & Permissions</span>
                                </router-link> -->
                        </div>
                    </div>
    
                    <div class="mt-8 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Team Management
                    </div>
    
                    <!-- Regular team management items -->
                    <template v-for="item in teamManagementItems" :key="item.to || item.label">
                        <!-- Payroll accordion item -->
                        <div v-if="item.label === 'Payrolls'" class="space-y-1">
                            <button @click="togglePayrollAccordion"
                                class="w-full text-sm flex items-center justify-between space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                <div class="flex items-center space-x-3">
                                    <i :class="item.icon"></i>
                                    <span>{{ item.label }}</span>
                                </div>
                                <i
                                    :class="['pi', payrollOpen ? 'pi-chevron-down' : 'pi-chevron-right', 'text-gray-400 text-xs']"></i>
                            </button>
    
                            <!-- Payroll submenu -->
                            <div v-show="payrollOpen" class="ml-8 space-y-1">
                                <router-link to="/hr/payroll/overview"
                                    class="text-sm flex items-center space-x-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                    <i class="pi pi-chart-pie text-gray-400 w-5"></i>
                                    <span>Overview</span>
                                </router-link>
    
                                <router-link to="/hr/payroll/periods"
                                    class="text-sm flex items-center space-x-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                    <i class="pi pi-calendar text-gray-400 w-5"></i>
                                    <span>Pay Periods</span>
                                </router-link>
                                <router-link to="/hr/payroll/lists"
                                    class="text-sm flex items-center space-x-3 py-2 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                                    <i class="pi pi-list text-gray-400 w-5"></i>
                                    <span>Payroll Lists</span>
                                </router-link>
                            </div>
                        </div>
    
                        <!-- Regular team management item (non-payroll) -->
                        <router-link v-else :key="item.to" :to="item.to"
                            class="text-sm flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-white hover:shadow transition-colors">
                            <i :class="item.icon"></i>
                            <span>{{ item.label }}</span>
                        </router-link>
                    </template>
                </div>
            </nav>
        </aside>
    
        <!-- Main content -->
        <div class="flex-1 flex flex-col h-screen">
            <!-- Top header -->
            <header class="bg-white border-b border-gray-200 py-3 px-6 flex items-center justify-between sticky top-0 z-20">
                <div>
                    <h1 class="font-semibold text-lg text-gray-800">
                        {{ route.meta.title }}
                    </h1>
                    <p class="text-sm text-gray-400">
                        {{ route.meta.subtitle }}
                    </p>
                </div>
    
                <div class="flex items-center space-x-4">
                    <!-- CLICKABLE USER CARD -->
                    <div class="border-l border-gray-200 pl-4 cursor-pointer select-none" @click="openUserDialog">
                        <div class="flex items-center space-x-3 hover:bg-gray-50 px-2 py-1 rounded-lg transition">
                            <!-- Avatar -->
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-user text-blue-600"></i>
                            </div>
                            <!-- Name + Role -->
                            <div>
                                <h2 class="font-semibold text-gray-800">
                                    {{ firstName }} {{ lastName }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    {{ role }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
    
            <!-- Scrollable content -->
            <main class="flex-1 overflow-y-auto p-5 bg-white">
                <router-view />
            </main>
        </div>
    </div>
    
    <UserDialog ref="userDialogRef" />
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { startCase, toLower } from 'lodash'
import UserDialog from '../components/dialogs/UserDialog.vue';

const route = useRoute()
const userDialogRef = ref(null)

// Accordion state
const payrollOpen = ref(false)
const settingsOpen = ref(false)

const togglePayrollAccordion = () => {
  payrollOpen.value = !payrollOpen.value
}

const toggleSettingAccordion = () => {
  settingsOpen.value = !settingsOpen.value
}

const openUserDialog = (event: MouseEvent) => {
  if (userDialogRef.value) {
    (userDialogRef.value as any).toggle(event);
  }
}

type User = {
  id: number
  user_id: string
  first_name: string
  last_name: string
  role: string
  email: string
}

const userData = localStorage.getItem('user')
const user: User | null = userData ? JSON.parse(userData) as User : null

const firstName = startCase(toLower(user?.first_name)) || 'HR'
const lastName = startCase(toLower(user?.last_name)) || 'Manager'
const role = startCase(user?.role) || 'HR Manager'

// Updated menu for furniture management system
const menuItems = [
  {
    to: "/hr/index",
    label: "Dashboard",
    icon: "pi pi-home text-gray-500 w-5"  // More intuitive for dashboard home
    // Alternative: "pi pi-chart-pie" for analytical dashboard
  },
  {
    to: "/hr/attendance",
    label: "Attendance",
    icon: "pi pi-user text-gray-500 w-5"  // More specific to attendance tracking
    // Alternative: "pi pi-fingerprint" for biometric attendance
  },
  {
    to: "/hr/shifts",
    label: "Shifts",
    icon: "pi pi-clock text-gray-500 w-5"  // Perfect for shifts/scheduling
    // Alternative: "pi pi-calendar-plus" for schedule creation
  },
  //   {
  //     to: "/hr/analytics",
  //     label: "Analytics",
  //     icon: "pi pi-chart-line text-gray-500 w-5"  // Better than scatter for HR metrics
  //     // Alternative: "pi pi-chart-bar" for comparison data
  //   }
]

const teamManagementItems = [
  {
    to: "/hr/departments",
    label: "Departments",
    icon: "pi pi-building text-gray-500 w-5"  // ✅ Good for assets
  },
  {
    to: "/hr/employees",
    label: "Employees",
    icon: "pi pi-users text-gray-500 w-5"  // ✅ Perfect for employees
    // Alternative: "pi pi-id-card" for employee profiles
  },
  {
    to: "/hr/leave-management",
    label: "Leave Management",
    icon: "pi pi-calendar-times text-gray-500 w-5"  // More specific to leave/absence
    // Alternative: "pi pi-sun" for time off/vacation
  },
  {
    label: "Payrolls",
    icon: "pi pi-wallet text-gray-500 w-5",  // Better than receipt for payroll
    // Alternative: "pi pi-money-bill" for payments
  },
]
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.router-link-active {
    font-weight: 500;
    color: #000;
}

.router-link-active i {
    color: black;
}

.sidebar {
    transition: all 0.3s ease;
}

/* Optional: Add a smooth transition for the accordion */
[v-show] {
    transition: opacity 0.2s ease-in-out;
}

[v-show="false"] {
    opacity: 0;
}

[v-show="true"] {
    opacity: 1;
}
</style>