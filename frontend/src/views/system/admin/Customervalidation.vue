<!-- views/system/CustomerValidation.vue -->
<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white shadow rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Customer Validation Management</h1>
                </div>
                <div class="flex space-x-2">
                    <Button label="Export Report" icon="pi pi-download" severity="secondary" @click="exportReport" />
                    <!-- <Button 
                            label="Validation Rules" 
                            icon="pi pi-cog" 
                            severity="secondary"
                            @click="showSettingsDialog = true"
                        /> -->
                </div>
            </div>
        </div>
    
        <!-- Stats Cards -->
        <!-- <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white shadow rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-gray-500">Pending Verification</h6>
                        <i class="pi pi-user-clock text-yellow-500 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800 mt-2">{{ pendingCustomers.length }}</p>
                    <p class="text-sm text-yellow-500">Awaiting validation</p>
                </div>
            
                <div class="bg-white shadow rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-gray-500">Verified Today</h6>
                        <i class="pi pi-user-check text-green-500 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800 mt-2">{{ verifiedTodayCount }}</p>
                    <p class="text-sm text-green-500">New customers verified</p>
                </div>
            
                <div class="bg-white shadow rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-gray-500">Rejected Today</h6>
                        <i class="pi pi-user-times text-red-500 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800 mt-2">{{ rejectedTodayCount }}</p>
                    <p class="text-sm text-red-500">Applications rejected</p>
                </div>
            
                <div class="bg-white shadow rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-gray-500">Total Customers</h6>
                        <i class="pi pi-users text-blue-500 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800 mt-2">{{ totalCustomers }}</p>
                    <p class="text-sm text-blue-500">All registered customers</p>
                </div>
            </div> -->
    
        <!-- Main Content Area - Full Width -->
        <div class="space-y-6">
            <!-- Status Navigation Card -->
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Status Navigation -->
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-800 mr-4">Customer Status:</h3>
                        <Button @click="setActiveView('pending')"
                            :severity="activeView === 'pending' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'pending'">
                            <i class="pi pi-user-clock mr-2"></i>
                            Pending
                            <Badge v-if="pendingCustomers.length > 0" :value="pendingCustomers.length" severity="warning"
                                class="ml-2" />
                        </Button>
    
                        <Button @click="setActiveView('verified')"
                            :severity="activeView === 'verified' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'verified'">
                            <i class="pi pi-user-check mr-2"></i>
                            Verified
                            <Badge v-if="verifiedCustomers.length > 0" :value="verifiedCustomers.length" severity="success"
                                class="ml-2" />
                        </Button>
    
                        <Button @click="setActiveView('rejected')"
                            :severity="activeView === 'rejected' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'rejected'">
                            <i class="pi pi-user-times mr-2"></i>
                            Rejected
                            <Badge v-if="rejectedCustomers.length > 0" :value="rejectedCustomers.length" severity="danger"
                                class="ml-2" />
                        </Button>
    
                        <Button @click="setActiveView('all')" :severity="activeView === 'all' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'all'">
                            <i class="pi pi-users mr-2"></i>
                            All Customers
                            <Badge :value="totalCustomers" severity="info" class="ml-2" />
                        </Button>
                    </div>
    
                    <!-- Risk Level Filter -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">Risk Level:</span>
                        <Select v-model="riskFilter" :options="riskLevelOptions" optionLabel="name" placeholder="All levels"
                            class="w-40" />
                    </div>
                </div>
            </div>
    
            <!-- Main Content Card -->
            <div class="bg-white shadow rounded-xl p-6">
                <!-- Pending Verification View -->
                <div v-if="activeView === 'pending'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Pending Customer Verification</h3>
                            <p class="text-sm text-gray-500">{{ filteredPendingCustomers.length }} customers awaiting review
                            </p>
                        </div>
    
                        <!-- Pending View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
    
    
                            <div class="flex items-center gap-2">
                                <!-- Quick Actions -->
                                <div class="flex items-center gap-2">
                                    <Button icon="pi pi-check" severity="success" outlined size="small"
                                        @click="showBulkVerifyDialog = true" :disabled="selectedCustomers.length === 0" />
                                    <Button icon="pi pi-times" severity="danger" outlined size="small"
                                        @click="showBulkRejectDialog = true" :disabled="selectedCustomers.length === 0" />
                                    <Button icon="pi pi-id-card" severity="help" outlined size="small"
                                        @click="requestDocuments" :disabled="selectedCustomers.length === 0" />
                                </div>
                                <Select v-model="verificationLevelFilter" :options="verificationLevelOptions"
                                    optionLabel="name" placeholder="Verification Level" class="w-48" />
                                <MultiSelect v-model="documentStatusFilter" :options="customerDocStatusOptions"
                                    optionLabel="name" placeholder="Document Status" display="chip" class="w-48" />
                                <Button icon="pi pi-filter" severity="secondary" @click="togglePendingFilters" />
                            </div>
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search customers..." class="w-full" />
                                </IconField>
                            </div>
                        </div>
                    </div>
    
                    <!-- Additional Pending Filters -->
                    <div v-if="showPendingFilters" class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Type</label>
                                <MultiSelect v-model="customerTypeFilter" :options="customerTypeOptions" optionLabel="name"
                                    placeholder="All types" display="chip" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Registration Date</label>
                                <Select v-model="dateFilter" :options="dateFilterOptions" optionLabel="name"
                                    placeholder="All time" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Age</label>
                                <Select v-model="accountAgeFilter" :options="accountAgeOptions" optionLabel="name"
                                    placeholder="Any age" class="w-full" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Pending Customers Table -->
                    <DataTable :value="filteredPendingCustomers" v-model:selection="selectedCustomers" dataKey="id"
                        sortMode="multiple" tableStyle="min-width: 50rem" paginator :rows="10"
                        :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
    
                        <Column field="fullName" header="Customer" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.fullName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.customerId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="email" header="Contact" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.email }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.phone }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="registrationDate" header="Registered" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.registrationDate) }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.waitingTime }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="verificationLevel" header="Level" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.verificationLevel"
                                    :severity="getVerificationLevelSeverity(slotProps.data.verificationLevel)" rounded />
                            </template>
                        </Column>
    
                        <Column field="documentStatus" header="Documents" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div class="flex items-center">
                                    <i
                                        :class="`pi ${getDocumentIcon(slotProps.data.documentStatus)} mr-2 ${getDocumentColor(slotProps.data.documentStatus)}`"></i>
                                    <span>{{ slotProps.data.documentStatus }}</span>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="riskLevel" header="Risk" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.riskLevel"
                                    :severity="getRiskLevelSeverity(slotProps.data.riskLevel)" rounded />
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 18%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button label="Review" size="small" icon="pi pi-eye"
                                        @click="reviewCustomer(slotProps.data)" />
                                    <Button icon="pi pi-check" size="small" severity="success"
                                        @click="verifyCustomer(slotProps.data)" />
                                    <Button icon="pi pi-times" size="small" severity="danger"
                                        @click="rejectCustomer(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <!-- Verified Customers View -->
                <div v-if="activeView === 'verified'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Verified Customers</h3>
                            <p class="text-sm text-gray-500">{{ filteredVerifiedCustomers.length }} verified customers</p>
                        </div>
    
                        <!-- Verified View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search verified customers..."
                                        class="w-full" />
                                </IconField>
                            </div>
    
                            <div class="flex items-center gap-2">
                                <Select v-model="verificationDateFilter" :options="verificationDateOptions"
                                    optionLabel="name" placeholder="Verification Date" class="w-48" />
                                <MultiSelect v-model="customerTypeFilter" :options="customerTypeOptions" optionLabel="name"
                                    placeholder="Customer Type" display="chip" class="w-48" />
                                <Select v-model="riskFilter" :options="riskLevelOptions" optionLabel="name"
                                    placeholder="Risk Level" class="w-40" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Verified Customers Table -->
                    <DataTable :value="filteredVerifiedCustomers" dataKey="id" sortMode="multiple"
                        tableStyle="min-width: 50rem" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column field="fullName" header="Customer" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user-check text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.fullName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.customerId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="email" header="Contact" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.email }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.phone }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="verificationDate" header="Verified On" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.verificationDate) }}</p>
                                    <p class="text-xs text-gray-500">By: {{ slotProps.data.verifiedBy }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="verificationLevel" header="Level" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.verificationLevel" severity="success" rounded />
                            </template>
                        </Column>
    
                        <Column field="riskLevel" header="Risk" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.riskLevel"
                                    :severity="getRiskLevelSeverity(slotProps.data.riskLevel)" rounded />
                            </template>
                        </Column>
    
                        <Column field="totalOrders" header="Orders" sortable style="width: 10%">
                            <template #body="slotProps">
                                <div class="text-center">
                                    <p class="font-bold">{{ slotProps.data.totalOrders }}</p>
                                    <p class="text-xs text-gray-500">count</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 13%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" size="small" severity="info"
                                        @click="viewCustomer(slotProps.data)" />
                                    <Button icon="pi pi-history" size="small" severity="warning"
                                        @click="reverifyCustomer(slotProps.data)" />
                                    <Button icon="pi pi-ban" size="small" severity="danger"
                                        @click="suspendCustomer(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <!-- Rejected Customers View -->
                <div v-if="activeView === 'rejected'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Rejected Customers</h3>
                            <p class="text-sm text-gray-500">{{ filteredRejectedCustomers.length }} rejected applications
                            </p>
                        </div>
    
                        <!-- Rejected View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search rejected customers..."
                                        class="w-full" />
                                </IconField>
                            </div>
    
                            <div class="flex items-center gap-2">
                                <MultiSelect v-model="rejectionReasonFilter" :options="customerRejectionReasonOptions"
                                    optionLabel="name" placeholder="Rejection Reasons" display="chip" class="w-48" />
                                <MultiSelect v-model="customerTypeFilter" :options="customerTypeOptions" optionLabel="name"
                                    placeholder="Customer Type" display="chip" class="w-48" />
                                <Select v-model="dateFilter" :options="dateFilterOptions" optionLabel="name"
                                    placeholder="Registration Date" class="w-48" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Rejected Customers Table -->
                    <DataTable :value="filteredRejectedCustomers" dataKey="id" sortMode="multiple"
                        tableStyle="min-width: 50rem" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column field="fullName" header="Customer" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                        <i class="pi pi-user-times text-red-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.fullName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.customerId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="email" header="Contact" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.email }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.phone }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="rejectionDate" header="Rejected On" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.rejectionDate) }}</p>
                                    <p class="text-xs text-gray-500">By: {{ slotProps.data.rejectedBy }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="rejectionReason" header="Reason" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-start">
                                    <i class="pi pi-info-circle text-red-500 mt-1 mr-2"></i>
                                    <span class="text-sm">{{ slotProps.data.rejectionReason }}</span>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="riskLevel" header="Risk" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.riskLevel"
                                    :severity="getRiskLevelSeverity(slotProps.data.riskLevel)" rounded />
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 13%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" size="small" severity="info"
                                        @click="viewRejectedCustomer(slotProps.data)" />
                                    <Button label="Re-review" size="small" icon="pi pi-redo"
                                        @click="rereviewCustomer(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <!-- All Customers View -->
                <div v-if="activeView === 'all'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">All Customers</h3>
                            <p class="text-sm text-gray-500">{{ filteredAllCustomers.length }} total customers</p>
                        </div>
    
                        <!-- All View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search all customers..." class="w-full" />
                                </IconField>
                            </div>
    
                            <div class="flex items-center gap-2">
                                <Select v-model="statusFilter" :options="customerStatusOptions" optionLabel="name"
                                    placeholder="Status" class="w-40" />
                                <MultiSelect v-model="customerTypeFilter" :options="customerTypeOptions" optionLabel="name"
                                    placeholder="Customer Type" display="chip" class="w-48" />
                                <Select v-model="riskFilter" :options="riskLevelOptions" optionLabel="name"
                                    placeholder="Risk Level" class="w-40" />
                            </div>
                        </div>
                    </div>
    
                    <!-- All Customers Table -->
                    <DataTable :value="filteredAllCustomers" dataKey="id" sortMode="multiple" tableStyle="min-width: 50rem"
                        paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column field="fullName" header="Customer" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div
                                        :class="`w-10 h-10 rounded-full flex items-center justify-center ${getCustomerStatusColor(slotProps.data.status)}`">
                                        <i :class="`pi ${getCustomerStatusIcon(slotProps.data.status)}`"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.fullName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.customerId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="email" header="Contact" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.email }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.phone }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="status" header="Status" sortable style="width: 12%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.status"
                                    :severity="getCustomerStatusSeverity(slotProps.data.status)" rounded />
                            </template>
                        </Column>
    
                        <Column field="customerType" header="Type" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.customerType" severity="info" rounded />
                            </template>
                        </Column>
    
                        <Column field="registrationDate" header="Registered" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.registrationDate) }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.accountAge }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="totalOrders" header="Orders" sortable style="width: 10%">
                            <template #body="slotProps">
                                <div class="text-center">
                                    <p class="font-bold">{{ slotProps.data.totalOrders }}</p>
                                    <p class="text-xs text-gray-500">count</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 11%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" size="small" severity="info"
                                        @click="viewCustomer(slotProps.data)" />
                                    <Button v-if="slotProps.data.status === 'Pending'" icon="pi pi-check" size="small"
                                        severity="success" @click="verifyCustomer(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    
        <!-- Review Customer Dialog -->
        <Dialog v-model:visible="showReviewDialog" modal
            :header="selectedReviewCustomer ? `Review Customer: ${selectedReviewCustomer.fullName}` : 'Review Customer'"
            :style="{ width: '800px' }">
            <div v-if="selectedReviewCustomer" class="space-y-6">
                <!-- Customer Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-800 mb-3">Customer Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Full Name</p>
                            <p class="font-medium">{{ selectedReviewCustomer.fullName }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date of Birth</p>
                            <p class="font-medium">{{ formatDate(selectedReviewCustomer.dateOfBirth) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Gender</p>
                            <p class="font-medium">{{ selectedReviewCustomer.gender }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Nationality</p>
                            <p class="font-medium">{{ selectedReviewCustomer.nationality }}</p>
                        </div>
                    </div>
                </div>
    
                <!-- Contact Information -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Contact Information</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Email Address</p>
                                <p class="font-medium">{{ selectedReviewCustomer.email }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone Number</p>
                                <p class="font-medium">{{ selectedReviewCustomer.phone }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-medium">{{ selectedReviewCustomer.address }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Registration Date</p>
                                <p class="font-medium">{{ formatDate(selectedReviewCustomer.registrationDate) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Document Review -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Identity Verification</h4>
                    <div class="space-y-3">
                        <div v-for="doc in selectedReviewCustomer.documents" :key="doc.name"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div class="flex items-center space-x-3">
                                <i :class="`pi ${getDocumentTypeIcon(doc.type)} ${getDocumentTypeColor(doc.type)}`"></i>
                                <div>
                                    <p class="font-medium">{{ doc.name }}</p>
                                    <p class="text-xs text-gray-500">{{ doc.status }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <Button label="View" size="small" icon="pi pi-eye" @click="viewDocument(doc)" />
                                <Select v-model="doc.verificationStatus" :options="verificationStatusOptions"
                                    optionLabel="name" placeholder="Verify" class="w-32" />
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Risk Assessment -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Risk Assessment</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Risk Level</p>
                                <p class="font-medium">{{ selectedReviewCustomer.riskLevel }}</p>
                                <p class="text-xs text-gray-500">{{ selectedReviewCustomer.riskScore }} / 100</p>
                            </div>
                            <div>
                                <Tag :value="selectedReviewCustomer.riskLevel"
                                    :severity="getRiskLevelSeverity(selectedReviewCustomer.riskLevel)" class="text-lg" />
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Review Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Review Notes</label>
                    <Textarea v-model="reviewNotes" placeholder="Enter review notes or comments..." rows="3"
                        class="w-full" />
                </div>
            </div>
    
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showReviewDialog = false" />
                <Button label="Request More Info" icon="pi pi-question-circle" @click="requestMoreInfo" />
                <Button label="Reject Customer" icon="pi pi-times" severity="danger"
                    @click="rejectCustomer(selectedReviewCustomer)" />
                <Button label="Verify Customer" icon="pi pi-check" @click="verifyCustomer(selectedReviewCustomer)" />
            </template>
        </Dialog>
    
        <!-- Reject Customer Dialog -->
        <Dialog v-model:visible="showRejectDialog" header="Reject Customer Application" :style="{ width: '600px' }">
            <div class="space-y-4">
                <div v-if="customerToReject">
                    <p class="text-gray-600 mb-4">You are about to reject the customer verification for <span
                            class="font-bold">{{ customerToReject.fullName }}</span>.</p>
                </div>
    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason *</label>
                    <Select v-model="rejectionReason" :options="customerRejectionReasonOptions" optionLabel="name"
                        placeholder="Select reason" class="w-full" />
                </div>
    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                    <Textarea v-model="rejectionNotes" placeholder="Provide additional details for rejection..." rows="3"
                        class="w-full" />
                </div>
    
                <div class="flex items-center p-3 bg-yellow-50 rounded">
                    <i class="pi pi-exclamation-triangle text-yellow-500 mr-3"></i>
                    <p class="text-sm text-yellow-800">This action cannot be undone. The customer will be notified.</p>
                </div>
            </div>
    
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showRejectDialog = false" />
                <Button label="Confirm Reject" severity="danger" @click="confirmReject" />
            </template>
        </Dialog>
    
        <!-- Bulk Actions Dialogs -->
        <Dialog v-model:visible="showBulkVerifyDialog" header="Bulk Verify Customers" :style="{ width: '500px' }">
            <div class="space-y-4">
                <p class="text-gray-600">You are about to verify {{ selectedCustomers.length }} customer(s).</p>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-800">
                        <i class="pi pi-info-circle mr-2"></i>
                        This action will verify all selected customers and send verification notifications.
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showBulkVerifyDialog = false" />
                <Button label="Verify All" severity="success" @click="bulkVerify" />
            </template>
        </Dialog>
    
        <Dialog v-model:visible="showBulkRejectDialog" header="Bulk Reject Customers" :style="{ width: '500px' }">
            <div class="space-y-4">
                <p class="text-gray-600">You are about to reject {{ selectedCustomers.length }} customer(s).</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                    <Select v-model="bulkRejectionReason" :options="customerRejectionReasonOptions" optionLabel="name"
                        placeholder="Select reason" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showBulkRejectDialog = false" />
                <Button label="Reject All" severity="danger" @click="bulkReject" />
            </template>
        </Dialog>
    
        <!-- Settings Dialog -->
        <Dialog v-model:visible="showSettingsDialog" header="Customer Validation Settings" :style="{ width: '700px' }">
            <div class="space-y-6">
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Verification Settings</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Enable Auto-Verification</p>
                                <p class="text-sm text-gray-500">Automatically verify low-risk customers</p>
                            </div>
                            <InputSwitch v-model="autoVerificationEnabled" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Verification Threshold</label>
                            <Select v-model="verificationThreshold" :options="thresholdOptions" optionLabel="name"
                                placeholder="Select threshold" class="w-full" />
                        </div>
                    </div>
                </div>
    
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Risk Assessment Settings</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">High Risk Threshold</label>
                            <InputNumber v-model="highRiskThreshold" :min="0" :max="100" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medium Risk Threshold</label>
                            <InputNumber v-model="mediumRiskThreshold" :min="0" :max="100" class="w-full" />
                        </div>
                    </div>
                </div>
    
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Document Requirements</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Require ID Verification</p>
                                <p class="text-sm text-gray-500">Mandatory ID document upload</p>
                            </div>
                            <InputSwitch v-model="requireIdVerification" />
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Require Address Proof</p>
                                <p class="text-sm text-gray-500">Mandatory address verification</p>
                            </div>
                            <InputSwitch v-model="requireAddressProof" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Review Time (Days)</label>
                            <InputNumber v-model="maxReviewDays" :min="1" :max="30" class="w-full" />
                        </div>
                    </div>
                </div>
            </div>
    
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showSettingsDialog = false" />
                <Button label="Save Settings" @click="saveSettings" />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Badge from 'primevue/badge'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'

// State
const activeView = ref('pending')
const loading = ref(false)
const searchTerm = ref('')
const showReviewDialog = ref(false)
const showRejectDialog = ref(false)
const showBulkVerifyDialog = ref(false)
const showBulkRejectDialog = ref(false)
const showSettingsDialog = ref(false)
const showPendingFilters = ref(false)
const selectedCustomers = ref<any[]>([])
const selectedReviewCustomer = ref<any>(null)
const customerToReject = ref<any>(null)
const reviewNotes = ref('')
const rejectionReason = ref(null)
const rejectionNotes = ref('')
const bulkRejectionReason = ref(null)

// Filters
const dateFilter = ref(null)
const customerTypeFilter = ref<any[]>([])
const verificationLevelFilter = ref(null)
const documentStatusFilter = ref<any[]>([])
const accountAgeFilter = ref(null)
const verificationDateFilter = ref(null)
const rejectionReasonFilter = ref<any[]>([])
const statusFilter = ref(null)
const riskFilter = ref(null)

// Settings
const autoVerificationEnabled = ref(false)
const verificationThreshold = ref(null)
const requireIdVerification = ref(true)
const requireAddressProof = ref(true)
const maxReviewDays = ref(7)
const highRiskThreshold = ref(70)
const mediumRiskThreshold = ref(40)
const emailNotifications = ref(true)

// Customer Data
const pendingCustomers = ref([
  {
    id: 1,
    customerId: 'CUST-2024-001',
    fullName: 'Juan Dela Cruz',
    email: 'juan@email.com',
    phone: '+639123456789',
    dateOfBirth: '1990-05-15',
    gender: 'Male',
    nationality: 'Filipino',
    address: '123 Main St, Manila',
    registrationDate: '2024-01-15',
    waitingTime: '2 days',
    verificationLevel: 'Basic',
    documentStatus: 'Complete',
    riskLevel: 'Low',
    riskScore: 25,
    customerType: 'Individual',
    documents: [
      { name: 'Government ID', type: 'id', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Proof of Address', type: 'address', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Selfie Photo', type: 'photo', status: 'Pending', verificationStatus: 'pending' }
    ]
  },
  {
    id: 2,
    customerId: 'CUST-2024-002',
    fullName: 'Maria Santos',
    email: 'maria@email.com',
    phone: '+639234567890',
    dateOfBirth: '1985-08-22',
    gender: 'Female',
    nationality: 'Filipino',
    address: '456 Oak Ave, Quezon City',
    registrationDate: '2024-01-16',
    waitingTime: '1 day',
    verificationLevel: 'Enhanced',
    documentStatus: 'Incomplete',
    riskLevel: 'Medium',
    riskScore: 55,
    customerType: 'Business',
    documents: [
      { name: 'Government ID', type: 'id', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Proof of Address', type: 'address', status: 'Missing', verificationStatus: 'missing' }
    ]
  },
  {
    id: 3,
    customerId: 'CUST-2024-003',
    fullName: 'Robert Lim',
    email: 'robert@email.com',
    phone: '+639345678901',
    dateOfBirth: '1978-12-10',
    gender: 'Male',
    nationality: 'Chinese',
    address: '789 Luxury Blvd, Makati',
    registrationDate: '2024-01-14',
    waitingTime: '3 days',
    verificationLevel: 'Full',
    documentStatus: 'Complete',
    riskLevel: 'High',
    riskScore: 85,
    customerType: 'VIP',
    documents: [
      { name: 'Government ID', type: 'id', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Proof of Address', type: 'address', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Income Proof', type: 'financial', status: 'Verified', verificationStatus: 'verified' }
    ]
  },
  {
    id: 4,
    customerId: 'CUST-2024-004',
    fullName: 'Sarah Chen',
    email: 'sarah@email.com',
    phone: '+639456789012',
    dateOfBirth: '1992-03-30',
    gender: 'Female',
    nationality: 'Chinese-Filipino',
    address: '101 Corporate St, Taguig',
    registrationDate: '2024-01-17',
    waitingTime: 'Just now',
    verificationLevel: 'Basic',
    documentStatus: 'Pending Review',
    riskLevel: 'Low',
    riskScore: 20,
    customerType: 'Individual',
    documents: [
      { name: 'Government ID', type: 'id', status: 'Pending', verificationStatus: 'pending' },
      { name: 'Proof of Address', type: 'address', status: 'Pending', verificationStatus: 'pending' }
    ]
  },
  {
    id: 5,
    customerId: 'CUST-2024-005',
    fullName: 'David Green',
    email: 'david@email.com',
    phone: '+639567890123',
    dateOfBirth: '1988-07-18',
    gender: 'Male',
    nationality: 'American',
    address: '202 Green St, Pasig',
    registrationDate: '2024-01-13',
    waitingTime: '4 days',
    verificationLevel: 'Enhanced',
    documentStatus: 'Complete',
    riskLevel: 'Medium',
    riskScore: 60,
    customerType: 'Business',
    documents: [
      { name: 'Passport', type: 'id', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Visa', type: 'id', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Proof of Address', type: 'address', status: 'Verified', verificationStatus: 'verified' }
    ]
  }
])

const verifiedCustomers = ref([
  {
    id: 6,
    customerId: 'CUST-2023-101',
    fullName: 'James Wilson',
    email: 'james@email.com',
    phone: '+639678901234',
    customerType: 'VIP',
    address: '303 Heritage Rd, Cebu',
    registrationDate: '2023-12-10',
    verificationDate: '2023-12-15',
    verifiedBy: 'Admin 1',
    verificationLevel: 'Full',
    riskLevel: 'Low',
    riskScore: 15,
    status: 'Active',
    totalOrders: 45,
    totalSpent: 1250000
  },
  {
    id: 7,
    customerId: 'CUST-2023-102',
    fullName: 'Lisa Garcia',
    email: 'lisa@email.com',
    phone: '+639789012345',
    customerType: 'Individual',
    address: '404 Modern Ave, Davao',
    registrationDate: '2023-11-25',
    verificationDate: '2023-11-30',
    verifiedBy: 'Admin 2',
    verificationLevel: 'Enhanced',
    riskLevel: 'Medium',
    riskScore: 45,
    status: 'Active',
    totalOrders: 32,
    totalSpent: 980000
  },
  {
    id: 8,
    customerId: 'CUST-2023-103',
    fullName: 'Michael Tan',
    email: 'michael@email.com',
    phone: '+639890123456',
    customerType: 'Business',
    address: '505 Playground St, Iloilo',
    registrationDate: '2023-12-05',
    verificationDate: '2023-12-10',
    verifiedBy: 'Admin 1',
    verificationLevel: 'Full',
    riskLevel: 'Low',
    riskScore: 20,
    status: 'Active',
    totalOrders: 67,
    totalSpent: 750000
  },
  {
    id: 9,
    customerId: 'CUST-2024-006',
    fullName: 'Anna Lee',
    email: 'anna@email.com',
    phone: '+639901234567',
    customerType: 'Individual',
    address: '606 Garden St, Baguio',
    registrationDate: '2024-01-05',
    verificationDate: '2024-01-10',
    verifiedBy: 'Admin 3',
    verificationLevel: 'Basic',
    riskLevel: 'Medium',
    riskScore: 55,
    status: 'Active',
    totalOrders: 12,
    totalSpent: 560000
  },
  {
    id: 10,
    customerId: 'CUST-2024-007',
    fullName: 'Paul Rivera',
    email: 'paul@email.com',
    phone: '+639012345678',
    customerType: 'VIP',
    address: '707 Tech Blvd, Pasay',
    registrationDate: '2024-01-08',
    verificationDate: '2024-01-12',
    verifiedBy: 'Admin 2',
    verificationLevel: 'Full',
    riskLevel: 'Low',
    riskScore: 10,
    status: 'Active',
    totalOrders: 89,
    totalSpent: 420000
  }
])

const rejectedCustomers = ref([
  {
    id: 11,
    customerId: 'CUST-2023-201',
    fullName: 'John Doe',
    email: 'john@email.com',
    phone: '+639123987654',
    customerType: 'Individual',
    address: '808 Fast St, Mandaluyong',
    registrationDate: '2023-11-20',
    rejectionDate: '2023-11-25',
    rejectedBy: 'Admin 1',
    status: 'Rejected',
    rejectionReason: 'Fake Documents',
    riskLevel: 'High',
    riskScore: 90,
    notes: 'Submitted forged identification documents'
  },
  {
    id: 12,
    customerId: 'CUST-2023-202',
    fullName: 'Jane Smith',
    email: 'jane@email.com',
    phone: '+639234876543',
    customerType: 'Business',
    address: '909 Budget Rd, Paranaque',
    registrationDate: '2023-12-01',
    rejectionDate: '2023-12-05',
    rejectedBy: 'Admin 2',
    status: 'Rejected',
    rejectionReason: 'Suspicious Activity',
    riskLevel: 'High',
    riskScore: 85,
    notes: 'Multiple failed verification attempts'
  },
  {
    id: 13,
    customerId: 'CUST-2024-008',
    fullName: 'Carlos Reyes',
    email: 'carlos@email.com',
    phone: '+639345765432',
    customerType: 'Individual',
    address: '1010 Sleep St, Alabang',
    registrationDate: '2024-01-10',
    rejectionDate: '2024-01-14',
    rejectedBy: 'Admin 3',
    status: 'Rejected',
    rejectionReason: 'Incomplete Information',
    riskLevel: 'Medium',
    riskScore: 60,
    notes: 'Missing required personal information'
  }
])

// Filter Options
const dateFilterOptions = ref([
  { name: 'Today', value: 'today' },
  { name: 'Last 7 days', value: '7days' },
  { name: 'Last 30 days', value: '30days' },
  { name: 'Last 90 days', value: '90days' },
  { name: 'This year', value: 'year' },
  { name: 'All time', value: 'all' }
])

const customerTypeOptions = ref([
  { name: 'Individual', value: 'individual' },
  { name: 'Business', value: 'business' },
  { name: 'VIP', value: 'vip' },
  { name: 'Corporate', value: 'corporate' },
  { name: 'Reseller', value: 'reseller' }
])

const verificationLevelOptions = ref([
  { name: 'Basic', value: 'basic' },
  { name: 'Enhanced', value: 'enhanced' },
  { name: 'Full', value: 'full' },
  { name: 'Premium', value: 'premium' }
])

const customerDocStatusOptions = ref([
  { name: 'Complete', value: 'complete' },
  { name: 'Incomplete', value: 'incomplete' },
  { name: 'Pending Review', value: 'pending' },
  { name: 'Missing Documents', value: 'missing' }
])

const accountAgeOptions = ref([
  { name: 'New (0-7 days)', value: 'new' },
  { name: 'Recent (8-30 days)', value: 'recent' },
  { name: 'Established (1-6 months)', value: 'established' },
  { name: 'Long-term (6+ months)', value: 'long-term' }
])

const verificationDateOptions = ref([
  { name: 'Today', value: 'today' },
  { name: 'This week', value: 'week' },
  { name: 'This month', value: 'month' },
  { name: 'Last month', value: 'last-month' },
  { name: 'All time', value: 'all' }
])

const customerRejectionReasonOptions = ref([
  { name: 'Fake Documents', value: 'fake-docs' },
  { name: 'Incomplete Information', value: 'incomplete-info' },
  { name: 'Suspicious Activity', value: 'suspicious' },
  { name: 'High Risk Profile', value: 'high-risk' },
  { name: 'Duplicate Account', value: 'duplicate' },
  { name: 'Policy Violation', value: 'policy' },
  { name: 'Other', value: 'other' }
])

const customerStatusOptions = ref([
  { name: 'Pending', value: 'pending' },
  { name: 'Verified', value: 'verified' },
  { name: 'Rejected', value: 'rejected' },
  { name: 'Suspended', value: 'suspended' },
  { name: 'Active', value: 'active' }
])

const riskLevelOptions = ref([
  { name: 'Low', value: 'low' },
  { name: 'Medium', value: 'medium' },
  { name: 'High', value: 'high' }
])

const verificationStatusOptions = ref([
  { name: 'Verified', value: 'verified' },
  { name: 'Pending', value: 'pending' },
  { name: 'Missing', value: 'missing' },
  { name: 'Invalid', value: 'invalid' }
])

const thresholdOptions = ref([
  { name: 'Low (0-30)', value: 'low' },
  { name: 'Medium (31-60)', value: 'medium' },
  { name: 'High (61-100)', value: 'high' }
])

// Computed Properties
const filteredPendingCustomers = computed(() => {
  let filtered = pendingCustomers.value

  if (searchTerm.value && activeView.value === 'pending') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(customer =>
      customer.fullName.toLowerCase().includes(term) ||
      customer.email.toLowerCase().includes(term) ||
      customer.customerId.toLowerCase().includes(term)
    )
  }

  if (verificationLevelFilter.value) {
    filtered = filtered.filter(customer => customer.verificationLevel === verificationLevelFilter.value.name)
  }

  if (documentStatusFilter.value.length > 0) {
    const statuses = documentStatusFilter.value.map(s => s.value)
    filtered = filtered.filter(customer => statuses.includes(customer.documentStatus.toLowerCase().replace(/ /g, '-')))
  }

  if (riskFilter.value) {
    filtered = filtered.filter(customer => customer.riskLevel === riskFilter.value.name)
  }

  return filtered
})

const filteredVerifiedCustomers = computed(() => {
  let filtered = verifiedCustomers.value

  if (searchTerm.value && activeView.value === 'verified') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(customer =>
      customer.fullName.toLowerCase().includes(term) ||
      customer.email.toLowerCase().includes(term) ||
      customer.customerId.toLowerCase().includes(term)
    )
  }

  if (verificationDateFilter.value) {
    // Implement verification date filtering logic
  }

  if (riskFilter.value) {
    filtered = filtered.filter(customer => customer.riskLevel === riskFilter.value.name)
  }

  return filtered
})

const filteredRejectedCustomers = computed(() => {
  let filtered = rejectedCustomers.value

  if (searchTerm.value && activeView.value === 'rejected') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(customer =>
      customer.fullName.toLowerCase().includes(term) ||
      customer.email.toLowerCase().includes(term) ||
      customer.customerId.toLowerCase().includes(term)
    )
  }

  if (rejectionReasonFilter.value.length > 0) {
    const reasons = rejectionReasonFilter.value.map(r => r.value)
    filtered = filtered.filter(customer => reasons.includes(customer.rejectionReason.toLowerCase().replace(/ /g, '-')))
  }

  if (riskFilter.value) {
    filtered = filtered.filter(customer => customer.riskLevel === riskFilter.value.name)
  }

  return filtered
})

const filteredAllCustomers = computed(() => {
  const allCustomers = [...pendingCustomers.value, ...verifiedCustomers.value, ...rejectedCustomers.value]
  let filtered = allCustomers

  if (searchTerm.value && activeView.value === 'all') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(customer =>
      customer.fullName.toLowerCase().includes(term) ||
      customer.email.toLowerCase().includes(term) ||
      customer.customerId.toLowerCase().includes(term)
    )
  }

  if (statusFilter.value) {
    filtered = filtered.filter(customer => customer.status === statusFilter.value.name)
  }

  if (riskFilter.value) {
    filtered = filtered.filter(customer => customer.riskLevel === riskFilter.value.name)
  }

  return filtered
})

const verifiedTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return verifiedCustomers.value.filter(customer => customer.verificationDate === today).length
})

const rejectedTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return rejectedCustomers.value.filter(customer => customer.rejectionDate === today).length
})

const totalCustomers = computed(() => {
  return pendingCustomers.value.length + verifiedCustomers.value.length + rejectedCustomers.value.length
})

// Helper Functions
const formatDate = (dateString: string) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch (e) {
    return dateString
  }
}

const getCustomerStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'warning'
    case 'verified':
    case 'active': return 'success'
    case 'rejected': return 'danger'
    case 'suspended': return 'secondary'
    default: return 'info'
  }
}

const getCustomerStatusIcon = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'pi-user-clock'
    case 'verified':
    case 'active': return 'pi-user-check'
    case 'rejected': return 'pi-user-times'
    case 'suspended': return 'pi-user-minus'
    default: return 'pi-user'
  }
}

const getCustomerStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'bg-yellow-100 text-yellow-600'
    case 'verified':
    case 'active': return 'bg-green-100 text-green-600'
    case 'rejected': return 'bg-red-100 text-red-600'
    case 'suspended': return 'bg-gray-100 text-gray-600'
    default: return 'bg-blue-100 text-blue-600'
  }
}

const getVerificationLevelSeverity = (level: string) => {
  switch (level.toLowerCase()) {
    case 'basic': return 'info'
    case 'enhanced': return 'warning'
    case 'full': return 'success'
    case 'premium': return 'help'
    default: return 'secondary'
  }
}

const getRiskLevelSeverity = (level: string) => {
  switch (level.toLowerCase()) {
    case 'low': return 'success'
    case 'medium': return 'warning'
    case 'high': return 'danger'
    default: return 'info'
  }
}

const getDocumentIcon = (status: string) => {
  switch (status) {
    case 'Complete': return 'pi-check-circle'
    case 'Incomplete': return 'pi-exclamation-circle'
    case 'Pending Review': return 'pi-clock'
    case 'Missing Documents': return 'pi-times-circle'
    default: return 'pi-file'
  }
}

const getDocumentColor = (status: string) => {
  switch (status) {
    case 'Complete': return 'text-green-500'
    case 'Incomplete': return 'text-yellow-500'
    case 'Pending Review': return 'text-blue-500'
    case 'Missing Documents': return 'text-red-500'
    default: return 'text-gray-500'
  }
}

const getDocumentTypeIcon = (type: string) => {
  switch (type) {
    case 'id': return 'pi-id-card'
    case 'address': return 'pi-home'
    case 'photo': return 'pi-camera'
    case 'financial': return 'pi-credit-card'
    default: return 'pi-file'
  }
}

const getDocumentTypeColor = (type: string) => {
  switch (type) {
    case 'id': return 'text-blue-500'
    case 'address': return 'text-green-500'
    case 'photo': return 'text-purple-500'
    case 'financial': return 'text-orange-500'
    default: return 'text-gray-500'
  }
}

// Action Functions
const setActiveView = (view: string) => {
  activeView.value = view
  selectedCustomers.value = []
  searchTerm.value = ''
}

const togglePendingFilters = () => {
  showPendingFilters.value = !showPendingFilters.value
}

const reviewCustomer = (customer: any) => {
  selectedReviewCustomer.value = customer
  showReviewDialog.value = true
}

const verifyCustomer = (customer: any) => {
  if (!customer) return

  // Move from pending to verified
  const pendingIndex = pendingCustomers.value.findIndex(c => c.id === customer.id)
  if (pendingIndex !== -1) {
    const verifiedCustomer = { ...pendingCustomers.value[pendingIndex] }
    verifiedCustomer.verificationDate = new Date().toISOString().split('T')[0]
    verifiedCustomer.verifiedBy = 'Current Admin'
    verifiedCustomer.status = 'Active'
    verifiedCustomer.totalOrders = 0
    verifiedCustomer.totalSpent = 0

    pendingCustomers.value.splice(pendingIndex, 1)
    verifiedCustomers.value.unshift(verifiedCustomer)
  }

  showReviewDialog.value = false
}

const rejectCustomer = (customer: any) => {
  customerToReject.value = customer
  showRejectDialog.value = true
}

const confirmReject = () => {
  if (!customerToReject.value) return

  const pendingIndex = pendingCustomers.value.findIndex(c => c.id === customerToReject.value.id)
  if (pendingIndex !== -1) {
    const rejectedCustomer = { ...pendingCustomers.value[pendingIndex] }
    rejectedCustomer.rejectionDate = new Date().toISOString().split('T')[0]
    rejectedCustomer.rejectedBy = 'Current Admin'
    rejectedCustomer.status = 'Rejected'
    rejectedCustomer.rejectionReason = rejectionReason.value?.name || 'Other'
    rejectedCustomer.notes = rejectionNotes.value

    pendingCustomers.value.splice(pendingIndex, 1)
    rejectedCustomers.value.unshift(rejectedCustomer)
  }

  showRejectDialog.value = false
  rejectionReason.value = null
  rejectionNotes.value = ''
  customerToReject.value = null
}

const viewCustomer = (customer: any) => {
  console.log('View customer:', customer)
  // Navigate to customer details page
}

const suspendCustomer = (customer: any) => {
  console.log('Suspend customer:', customer)
  // Implement suspension logic
}

const reverifyCustomer = (customer: any) => {
  console.log('Re-verify customer:', customer)
  // Move from verified to pending for re-verification
}

const viewRejectedCustomer = (customer: any) => {
  console.log('View rejected customer:', customer)
}

const rereviewCustomer = (customer: any) => {
  // Move from rejected to pending
  const rejectedIndex = rejectedCustomers.value.findIndex(c => c.id === customer.id)
  if (rejectedIndex !== -1) {
    const pendingCustomer = { ...rejectedCustomers.value[rejectedIndex] }
    delete pendingCustomer.rejectionDate
    delete pendingCustomer.rejectedBy
    delete pendingCustomer.rejectionReason
    delete pendingCustomer.notes
    pendingCustomer.status = 'Pending'
    pendingCustomer.documentStatus = 'Pending Review'

    rejectedCustomers.value.splice(rejectedIndex, 1)
    pendingCustomers.value.push(pendingCustomer)
  }
}

const viewDocument = (doc: any) => {
  console.log('View document:', doc)
  // Open document viewer
}

const requestMoreInfo = () => {
  console.log('Request more info for customer:', selectedReviewCustomer.value)
  // Implement request more info logic
}

const requestDocuments = () => {
  console.log('Request documents from selected customers:', selectedCustomers.value)
  // Implement document request logic
}

const bulkVerify = () => {
  selectedCustomers.value.forEach(customer => {
    verifyCustomer(customer)
  })
  selectedCustomers.value = []
  showBulkVerifyDialog.value = false
}

const bulkReject = () => {
  selectedCustomers.value.forEach(customer => {
    customerToReject.value = customer
    confirmReject()
  })
  selectedCustomers.value = []
  showBulkRejectDialog.value = false
}

const exportReport = () => {
  console.log('Exporting customer validation report')
  // Implement export logic
}

const saveSettings = () => {
  console.log('Saving customer validation settings')
  showSettingsDialog.value = false
}

onMounted(() => {
  console.log('Customer Validation Management loaded')
})
</script>