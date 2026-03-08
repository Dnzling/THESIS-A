<!-- views/system/StoreValidation.vue -->
<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white shadow rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Store Validation Management</h1>
                </div>
                <div class="flex space-x-2">
                    <Button label="Export Report" icon="pi pi-download" severity="secondary" @click="exportReport" />
                    <!-- <Button 
                        label="Validation Settings" 
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
                            <h6 class="text-sm font-semibold text-gray-500">Pending Review</h6>
                            <i class="pi pi-clock text-yellow-500 text-lg"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ pendingStores.length }}</p>
                        <p class="text-sm text-yellow-500">Awaiting validation</p>
                    </div>
            
                    <div class="bg-white shadow rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <h6 class="text-sm font-semibold text-gray-500">Approved Today</h6>
                            <i class="pi pi-check-circle text-green-500 text-lg"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ approvedTodayCount }}</p>
                        <p class="text-sm text-green-500">New stores approved</p>
                    </div>
            
                    <div class="bg-white shadow rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <h6 class="text-sm font-semibold text-gray-500">Rejected Today</h6>
                            <i class="pi pi-times-circle text-red-500 text-lg"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ rejectedTodayCount }}</p>
                        <p class="text-sm text-red-500">Applications rejected</p>
                    </div>
            
                    <div class="bg-white shadow rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <h6 class="text-sm font-semibold text-gray-500">Total Stores</h6>
                            <i class="pi pi-building text-blue-500 text-lg"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ totalStores }}</p>
                        <p class="text-sm text-blue-500">All registered stores</p>
                    </div>
                </div> -->
    
        <!-- Main Content Area - Full Width -->
        <div class="space-y-6">
            <!-- Status Navigation Card -->
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Status Navigation -->
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-800 mr-4">Store Status:</h3>
                        <Button @click="setActiveView('pending')"
                            :severity="activeView === 'pending' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'pending'">
                            <i class="pi pi-clock mr-2"></i>
                            Pending
                            <Badge v-if="pendingStores.length > 0" :value="pendingStores.length" severity="warning"
                                class="ml-2" />
                        </Button>
    
                        <Button @click="setActiveView('approved')"
                            :severity="activeView === 'approved' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'approved'">
                            <i class="pi pi-check-circle mr-2"></i>
                            Approved
                            <Badge v-if="approvedStores.length > 0" :value="approvedStores.length" severity="success"
                                class="ml-2" />
                        </Button>
    
                        <Button @click="setActiveView('rejected')"
                            :severity="activeView === 'rejected' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'rejected'">
                            <i class="pi pi-times-circle mr-2"></i>
                            Rejected
                            <Badge v-if="rejectedStores.length > 0" :value="rejectedStores.length" severity="danger"
                                class="ml-2" />
                        </Button>
    
                        <Button @click="setActiveView('all')" :severity="activeView === 'all' ? 'primary' : 'secondary'"
                            :outlined="activeView !== 'all'">
                            <i class="pi pi-list mr-2"></i>
                            All Stores
                            <Badge :value="totalStores" severity="info" class="ml-2" />
                        </Button>
                    </div>
                </div>
            </div>
    
            <!-- Main Content Card -->
            <div class="bg-white shadow rounded-xl p-6">
                <!-- Pending Registrations View -->
                <div v-if="activeView === 'pending'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Pending Store Registrations</h3>
                            <p class="text-sm text-gray-500">{{ filteredPendingStores.length }} stores awaiting review</p>
                        </div>
    
                        <!-- Pending View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                          
    
                            <div class="flex items-center gap-2">
                                <!-- Quick Actions -->
                                <div class="flex items-center gap-2">
                                    <Button icon="pi pi-check" severity="success" outlined size="small"
                                        @click="showBulkApproveDialog = true" :disabled="selectedStores.length === 0" />
                                    <Button icon="pi pi-times" severity="danger" outlined size="small"
                                        @click="showBulkRejectDialog = true" :disabled="selectedStores.length === 0" />
                                    <Button icon="pi pi-envelope" severity="help" outlined size="small"
                                        @click="sendReminders" />
                                </div>
                                <Select v-model="waitingTimeFilter" :options="waitingTimeOptions" optionLabel="name"
                                    placeholder="Waiting Time" class="w-40" />
                                <MultiSelect v-model="documentStatusFilter" :options="documentStatusOptions"
                                    optionLabel="name" placeholder="Doc Status" display="chip" class="w-48" />
                                <!-- <Select v-model="priorityFilter" :options="priorityOptions" optionLabel="name"
                                        placeholder="Priority" class="w-32" /> -->
                                <Button icon="pi pi-filter" severity="secondary" @click="togglePendingFilters" />
                            </div>  <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search" class="w-full" />
                                </IconField>
                            </div>
                        </div>
                    </div>
    
                    <!-- Additional Pending Filters -->
                    <div v-if="showPendingFilters" class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Store Type</label>
                                <MultiSelect v-model="storeTypeFilter" :options="storeTypeOptions" optionLabel="name"
                                    placeholder="All types" display="chip" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Registration Date</label>
                                <Select v-model="dateFilter" :options="dateFilterOptions" optionLabel="name"
                                    placeholder="All time" class="w-full" />
                            </div>
                            <div class="flex items-end gap-2">
                                <Button label="Apply Filters" size="small" @click="applyFilters" />
                                <Button label="Clear" severity="secondary" size="small" @click="clearFilters" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Pending Stores Table -->
                    <DataTable :value="filteredPendingStores" v-model:selection="selectedStores" dataKey="id"
                        sortMode="multiple" tableStyle="min-width: 50rem" paginator :rows="10"
                        :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
    
                        <Column field="storeName" header="Store Name" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="pi pi-store text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.storeName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.storeId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="owner" header="Owner" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.ownerName }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.ownerEmail }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="registrationDate" header="Submitted" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.registrationDate) }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.waitingTime }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="storeType" header="Type" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.storeType" severity="info" rounded />
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
    
                        <Column header="Actions" style="width: 18%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button label="Review" size="small" icon="pi pi-eye"
                                        @click="reviewStore(slotProps.data)" />
                                    <Button icon="pi pi-check" size="small" severity="success"
                                        @click="approveStore(slotProps.data)" />
                                    <Button icon="pi pi-times" size="small" severity="danger"
                                        @click="rejectStore(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <!-- Approved Stores View -->
                <div v-if="activeView === 'approved'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Approved Stores</h3>
                            <p class="text-sm text-gray-500">{{ filteredApprovedStores.length }} approved stores</p>
                        </div>
    
                        <!-- Approved View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search approved stores..."
                                        class="w-full" />
                                </IconField>
                            </div>
    
                            <div class="flex items-center gap-2">
                                <Select v-model="approvalDateFilter" :options="approvalDateOptions" optionLabel="name"
                                    placeholder="Approval Date" class="w-48" />
                                <MultiSelect v-model="storeTypeFilter" :options="storeTypeOptions" optionLabel="name"
                                    placeholder="Store Type" display="chip" class="w-48" />
                                <Select v-model="dateFilter" :options="dateFilterOptions" optionLabel="name"
                                    placeholder="Registration Date" class="w-48" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Approved Stores Table -->
                    <DataTable :value="filteredApprovedStores" dataKey="id" sortMode="multiple"
                        tableStyle="min-width: 50rem" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column field="storeName" header="Store Name" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="pi pi-check-circle text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.storeName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.storeId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="owner" header="Owner" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.ownerName }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.ownerEmail }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="approvalDate" header="Approved On" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.approvalDate) }}</p>
                                    <p class="text-xs text-gray-500">By: {{ slotProps.data.approvedBy }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="storeType" header="Type" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.storeType" severity="info" rounded />
                            </template>
                        </Column>
    
                        <Column field="status" header="Status" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.status" severity="success" rounded />
                            </template>
                        </Column>
    
                        <Column field="productsCount" header="Products" sortable style="width: 10%">
                            <template #body="slotProps">
                                <div class="text-center">
                                    <p class="font-bold">{{ slotProps.data.productsCount }}</p>
                                    <p class="text-xs text-gray-500">items</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 13%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" size="small" severity="info"
                                        @click="viewStore(slotProps.data)" />
                                    <Button icon="pi pi-ban" size="small" severity="danger"
                                        @click="suspendStore(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <!-- Rejected Stores View -->
                <div v-if="activeView === 'rejected'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Rejected Stores</h3>
                            <p class="text-sm text-gray-500">{{ filteredRejectedStores.length }} rejected applications</p>
                        </div>
    
                        <!-- Rejected View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search rejected stores..."
                                        class="w-full" />
                                </IconField>
                            </div>
    
                            <div class="flex items-center gap-2">
                                <MultiSelect v-model="rejectionReasonFilter" :options="rejectionReasonOptions"
                                    optionLabel="name" placeholder="Rejection Reasons" display="chip" class="w-48" />
                                <MultiSelect v-model="storeTypeFilter" :options="storeTypeOptions" optionLabel="name"
                                    placeholder="Store Type" display="chip" class="w-48" />
                                <Select v-model="dateFilter" :options="dateFilterOptions" optionLabel="name"
                                    placeholder="Registration Date" class="w-48" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Rejected Stores Table -->
                    <DataTable :value="filteredRejectedStores" dataKey="id" sortMode="multiple"
                        tableStyle="min-width: 50rem" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column field="storeName" header="Store Name" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                        <i class="pi pi-times-circle text-red-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.storeName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.storeId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="owner" header="Owner" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.ownerName }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.ownerEmail }}</p>
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
    
                        <Column field="status" header="Status" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.status" severity="danger" rounded />
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 13%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" size="small" severity="info"
                                        @click="viewRejectedStore(slotProps.data)" />
                                    <Button label="Re-review" size="small" icon="pi pi-redo"
                                        @click="rereviewStore(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <!-- All Stores View -->
                <div v-if="activeView === 'all'">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">All Stores</h3>
                            <p class="text-sm text-gray-500">{{ filteredAllStores.length }} total stores</p>
                        </div>
    
                        <!-- All View Filters -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-64">
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="searchTerm" placeholder="Search all stores..." class="w-full" />
                                </IconField>
                            </div>
    
                            <div class="flex items-center gap-2">
                                <Select v-model="statusFilter" :options="allStatusOptions" optionLabel="name"
                                    placeholder="Status" class="w-40" />
                                <MultiSelect v-model="storeTypeFilter" :options="storeTypeOptions" optionLabel="name"
                                    placeholder="Store Type" display="chip" class="w-48" />
                                <Select v-model="dateFilter" :options="dateFilterOptions" optionLabel="name"
                                    placeholder="Registration Date" class="w-48" />
                            </div>
                        </div>
                    </div>
    
                    <!-- All Stores Table -->
                    <DataTable :value="filteredAllStores" dataKey="id" sortMode="multiple" tableStyle="min-width: 50rem"
                        paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                        <Column field="storeName" header="Store Name" sortable style="width: 20%">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <div
                                        :class="`w-10 h-10 rounded-lg flex items-center justify-center ${getStoreStatusColor(slotProps.data.status)}`">
                                        <i :class="`pi ${getStoreStatusIcon(slotProps.data.status)}`"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ slotProps.data.storeName }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ slotProps.data.storeId }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="owner" header="Owner" sortable style="width: 15%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ slotProps.data.ownerName }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.ownerEmail }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="status" header="Status" sortable style="width: 12%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)"
                                    rounded />
                            </template>
                        </Column>
    
                        <Column field="storeType" header="Type" sortable style="width: 10%">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.storeType" severity="info" rounded />
                            </template>
                        </Column>
    
                        <Column field="registrationDate" header="Registered" sortable style="width: 12%">
                            <template #body="slotProps">
                                <div>
                                    <p class="font-medium">{{ formatDate(slotProps.data.registrationDate) }}</p>
                                    <p class="text-xs text-gray-500">{{ slotProps.data.age }}</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column field="productsCount" header="Products" sortable style="width: 10%">
                            <template #body="slotProps">
                                <div class="text-center">
                                    <p class="font-bold">{{ slotProps.data.productsCount }}</p>
                                    <p class="text-xs text-gray-500">items</p>
                                </div>
                            </template>
                        </Column>
    
                        <Column header="Actions" style="width: 11%">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button icon="pi pi-eye" size="small" severity="info"
                                        @click="viewStore(slotProps.data)" />
                                    <Button v-if="slotProps.data.status === 'Pending'" icon="pi pi-check" size="small"
                                        severity="success" @click="approveStore(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
        <!-- Review Store Dialog -->
        <Dialog v-model:visible="showReviewDialog" modal
            :header="selectedReviewStore ? `Review Store: ${selectedReviewStore.storeName}` : 'Review Store'"
            :style="{ width: '800px' }">
            <div v-if="selectedReviewStore" class="space-y-6">
                <!-- Store Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-800 mb-3">Store Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Store Name</p>
                            <p class="font-medium">{{ selectedReviewStore.storeName }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Store Type</p>
                            <p class="font-medium">{{ selectedReviewStore.storeType }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Business Address</p>
                            <p class="font-medium">{{ selectedReviewStore.address }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Contact Number</p>
                            <p class="font-medium">{{ selectedReviewStore.contactNumber }}</p>
                        </div>
                    </div>
                </div>
    
                <!-- Owner Information -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Owner Information</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Full Name</p>
                                <p class="font-medium">{{ selectedReviewStore.ownerName }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email Address</p>
                                <p class="font-medium">{{ selectedReviewStore.ownerEmail }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone Number</p>
                                <p class="font-medium">{{ selectedReviewStore.ownerPhone }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Registration Date</p>
                                <p class="font-medium">{{ formatDate(selectedReviewStore.registrationDate) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Document Review -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Document Review</h4>
                    <div class="space-y-3">
                        <div v-for="doc in selectedReviewStore.documents" :key="doc.name"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div class="flex items-center space-x-3">
                                <i class="pi pi-file-pdf text-red-500"></i>
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
                <Button label="Reject Store" icon="pi pi-times" severity="danger"
                    @click="rejectStore(selectedReviewStore)" />
                <Button label="Approve Store" icon="pi pi-check" @click="approveStore(selectedReviewStore)" />
            </template>
        </Dialog>
    
        <!-- Reject Store Dialog -->
        <Dialog v-model:visible="showRejectDialog" header="Reject Store Application" :style="{ width: '600px' }">
            <div class="space-y-4">
                <div v-if="storeToReject">
                    <p class="text-gray-600 mb-4">You are about to reject the store application for <span
                            class="font-bold">{{ storeToReject.storeName }}</span>.</p>
                </div>
    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason *</label>
                    <Select v-model="rejectionReason" :options="rejectionReasonOptions" optionLabel="name"
                        placeholder="Select reason" class="w-full" />
                </div>
    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                    <Textarea v-model="rejectionNotes" placeholder="Provide additional details for rejection..." rows="3"
                        class="w-full" />
                </div>
    
                <div class="flex items-center p-3 bg-yellow-50 rounded">
                    <i class="pi pi-exclamation-triangle text-yellow-500 mr-3"></i>
                    <p class="text-sm text-yellow-800">This action cannot be undone. The store owner will be notified.</p>
                </div>
            </div>
    
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showRejectDialog = false" />
                <Button label="Confirm Reject" severity="danger" @click="confirmReject" />
            </template>
        </Dialog>
    
        <!-- Bulk Actions Dialogs -->
        <Dialog v-model:visible="showBulkApproveDialog" header="Bulk Approve Stores" :style="{ width: '500px' }">
            <div class="space-y-4">
                <p class="text-gray-600">You are about to approve {{ selectedStores.length }} store(s).</p>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="pi pi-info-circle mr-2"></i>
                        This action will approve all selected stores and send approval notifications.
                    </p>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showBulkApproveDialog = false" />
                <Button label="Approve All" severity="success" @click="bulkApprove" />
            </template>
        </Dialog>
    
        <Dialog v-model:visible="showBulkRejectDialog" header="Bulk Reject Stores" :style="{ width: '500px' }">
            <div class="space-y-4">
                <p class="text-gray-600">You are about to reject {{ selectedStores.length }} store(s).</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                    <Select v-model="bulkRejectionReason" :options="rejectionReasonOptions" optionLabel="name"
                        placeholder="Select reason" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showBulkRejectDialog = false" />
                <Button label="Reject All" severity="danger" @click="bulkReject" />
            </template>
        </Dialog>
    
        <!-- Settings Dialog -->
        <Dialog v-model:visible="showSettingsDialog" header="Validation Settings" :style="{ width: '700px' }">
            <div class="space-y-6">
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Auto-Approval Settings</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Enable Auto-Approval</p>
                                <p class="text-sm text-gray-500">Automatically approve stores after verification</p>
                            </div>
                            <InputSwitch v-model="autoApprovalEnabled" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Auto-Approval Delay</label>
                            <Select v-model="autoApprovalDelay" :options="delayOptions" optionLabel="name"
                                placeholder="Select delay" class="w-full" />
                        </div>
                    </div>
                </div>
    
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Notification Settings</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Email Notifications</p>
                                <p class="text-sm text-gray-500">Send email notifications to store owners</p>
                            </div>
                            <InputSwitch v-model="emailNotifications" />
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">SMS Notifications</p>
                                <p class="text-sm text-gray-500">Send SMS notifications for urgent updates</p>
                            </div>
                            <InputSwitch v-model="smsNotifications" />
                        </div>
                    </div>
                </div>
    
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Validation Rules</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Documents Required</label>
                            <InputNumber v-model="minDocuments" :min="1" :max="10" class="w-full" />
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
const showBulkApproveDialog = ref(false)
const showBulkRejectDialog = ref(false)
const showSettingsDialog = ref(false)
const showPendingFilters = ref(false)
const selectedStores = ref<any[]>([])
const selectedReviewStore = ref<any>(null)
const storeToReject = ref<any>(null)
const reviewNotes = ref('')
const rejectionReason = ref(null)
const rejectionNotes = ref('')
const bulkRejectionReason = ref(null)

// Filters
const dateFilter = ref(null)
const storeTypeFilter = ref<any[]>([])
const waitingTimeFilter = ref(null)
const documentStatusFilter = ref<any[]>([])
const priorityFilter = ref(null)
const approvalDateFilter = ref(null)
const rejectionReasonFilter = ref<any[]>([])
const statusFilter = ref(null)

// Settings
const autoApprovalEnabled = ref(false)
const autoApprovalDelay = ref(null)
const emailNotifications = ref(true)
const smsNotifications = ref(false)
const minDocuments = ref(3)
const maxReviewDays = ref(7)

// Store Data
const pendingStores = ref([
  {
    id: 1,
    storeId: 'STORE-2024-001',
    storeName: 'Modern Furniture Hub',
    ownerName: 'Juan Dela Cruz',
    ownerEmail: 'juan@email.com',
    ownerPhone: '+639123456789',
    storeType: 'Furniture Retail',
    address: '123 Main St, Manila',
    contactNumber: '+6328123456',
    registrationDate: '2024-01-15',
    waitingTime: '2 days',
    documentStatus: 'Complete',
    priority: 'High',
    documents: [
      { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Mayor\'s Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Tax Certificate', status: 'Pending', verificationStatus: 'pending' }
    ]
  },
  {
    id: 2,
    storeId: 'STORE-2024-002',
    storeName: 'Wood Crafts Studio',
    ownerName: 'Maria Santos',
    ownerEmail: 'maria@email.com',
    ownerPhone: '+639234567890',
    storeType: 'Furniture Manufacturing',
    address: '456 Oak Ave, Quezon City',
    contactNumber: '+6328234567',
    registrationDate: '2024-01-16',
    waitingTime: '1 day',
    documentStatus: 'Incomplete',
    priority: 'Medium',
    documents: [
      { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Mayor\'s Permit', status: 'Missing', verificationStatus: 'missing' }
    ]
  },
  {
    id: 3,
    storeId: 'STORE-2024-003',
    storeName: 'Luxury Home Decor',
    ownerName: 'Robert Lim',
    ownerEmail: 'robert@email.com',
    ownerPhone: '+639345678901',
    storeType: 'Home Decor',
    address: '789 Luxury Blvd, Makati',
    contactNumber: '+6328345678',
    registrationDate: '2024-01-14',
    waitingTime: '3 days',
    documentStatus: 'Complete',
    priority: 'Low',
    documents: [
      { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Mayor\'s Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Tax Certificate', status: 'Verified', verificationStatus: 'verified' }
    ]
  },
  {
    id: 4,
    storeId: 'STORE-2024-004',
    storeName: 'Office Solutions Inc',
    ownerName: 'Sarah Chen',
    ownerEmail: 'sarah@email.com',
    ownerPhone: '+639456789012',
    storeType: 'Office Furniture',
    address: '101 Corporate St, Taguig',
    contactNumber: '+6328456789',
    registrationDate: '2024-01-17',
    waitingTime: 'Just now',
    documentStatus: 'Pending Review',
    priority: 'High',
    documents: [
      { name: 'Business Permit', status: 'Pending', verificationStatus: 'pending' },
      { name: 'Mayor\'s Permit', status: 'Pending', verificationStatus: 'pending' }
    ]
  },
  {
    id: 5,
    storeId: 'STORE-2024-005',
    storeName: 'Eco Furniture Co',
    ownerName: 'David Green',
    ownerEmail: 'david@email.com',
    ownerPhone: '+639567890123',
    storeType: 'Sustainable Furniture',
    address: '202 Green St, Pasig',
    contactNumber: '+6328567890',
    registrationDate: '2024-01-13',
    waitingTime: '4 days',
    documentStatus: 'Complete',
    priority: 'Medium',
    documents: [
      { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Mayor\'s Permit', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Tax Certificate', status: 'Verified', verificationStatus: 'verified' },
      { name: 'Environmental Permit', status: 'Verified', verificationStatus: 'verified' }
    ]
  }
])

const approvedStores = ref([
  {
    id: 6,
    storeId: 'STORE-2023-101',
    storeName: 'Classic Furniture Gallery',
    ownerName: 'James Wilson',
    ownerEmail: 'james@email.com',
    storeType: 'Antique Furniture',
    address: '303 Heritage Rd, Cebu',
    registrationDate: '2023-12-10',
    approvalDate: '2023-12-15',
    approvedBy: 'Admin 1',
    status: 'Active',
    productsCount: 145,
    revenue: 1250000
  },
  {
    id: 7,
    storeId: 'STORE-2023-102',
    storeName: 'Modern Living Spaces',
    ownerName: 'Lisa Garcia',
    ownerEmail: 'lisa@email.com',
    storeType: 'Modern Furniture',
    address: '404 Modern Ave, Davao',
    registrationDate: '2023-11-25',
    approvalDate: '2023-11-30',
    approvedBy: 'Admin 2',
    status: 'Active',
    productsCount: 89,
    revenue: 980000
  },
  {
    id: 8,
    storeId: 'STORE-2023-103',
    storeName: 'Kids Furniture World',
    ownerName: 'Michael Tan',
    ownerEmail: 'michael@email.com',
    storeType: 'Kids Furniture',
    address: '505 Playground St, Iloilo',
    registrationDate: '2023-12-05',
    approvalDate: '2023-12-10',
    approvedBy: 'Admin 1',
    status: 'Active',
    productsCount: 67,
    revenue: 750000
  },
  {
    id: 9,
    storeId: 'STORE-2024-006',
    storeName: 'Outdoor Living Co',
    ownerName: 'Anna Lee',
    ownerEmail: 'anna@email.com',
    storeType: 'Outdoor Furniture',
    address: '606 Garden St, Baguio',
    registrationDate: '2024-01-05',
    approvalDate: '2024-01-10',
    approvedBy: 'Admin 3',
    status: 'Active',
    productsCount: 42,
    revenue: 560000
  },
  {
    id: 10,
    storeId: 'STORE-2024-007',
    storeName: 'Smart Furniture Tech',
    ownerName: 'Paul Rivera',
    ownerEmail: 'paul@email.com',
    storeType: 'Smart Furniture',
    address: '707 Tech Blvd, Pasay',
    registrationDate: '2024-01-08',
    approvalDate: '2024-01-12',
    approvedBy: 'Admin 2',
    status: 'Active',
    productsCount: 31,
    revenue: 420000
  }
])

const rejectedStores = ref([
  {
    id: 11,
    storeId: 'STORE-2023-201',
    storeName: 'Quick Furniture Mart',
    ownerName: 'John Doe',
    ownerEmail: 'john@email.com',
    storeType: 'Furniture Retail',
    address: '808 Fast St, Mandaluyong',
    registrationDate: '2023-11-20',
    rejectionDate: '2023-11-25',
    rejectedBy: 'Admin 1',
    status: 'Rejected',
    rejectionReason: 'Incomplete Documentation',
    notes: 'Missing required permits and identification'
  },
  {
    id: 12,
    storeId: 'STORE-2023-202',
    storeName: 'Budget Furniture Store',
    ownerName: 'Jane Smith',
    ownerEmail: 'jane@email.com',
    storeType: 'Budget Furniture',
    address: '909 Budget Rd, Paranaque',
    registrationDate: '2023-12-01',
    rejectionDate: '2023-12-05',
    rejectedBy: 'Admin 2',
    status: 'Rejected',
    rejectionReason: 'Business Location Issues',
    notes: 'Registered address does not match business location'
  },
  {
    id: 13,
    storeId: 'STORE-2024-008',
    storeName: 'Luxury Bedroom Sets',
    ownerName: 'Carlos Reyes',
    ownerEmail: 'carlos@email.com',
    storeType: 'Bedroom Furniture',
    address: '1010 Sleep St, Alabang',
    registrationDate: '2024-01-10',
    rejectionDate: '2024-01-14',
    rejectedBy: 'Admin 3',
    status: 'Rejected',
    rejectionReason: 'Duplicate Registration',
    notes: 'Multiple applications detected from same owner'
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

const storeTypeOptions = ref([
  { name: 'Furniture Retail', value: 'retail' },
  { name: 'Furniture Manufacturing', value: 'manufacturing' },
  { name: 'Home Decor', value: 'decor' },
  { name: 'Office Furniture', value: 'office' },
  { name: 'Sustainable Furniture', value: 'sustainable' },
  { name: 'Antique Furniture', value: 'antique' },
  { name: 'Kids Furniture', value: 'kids' },
  { name: 'Outdoor Furniture', value: 'outdoor' },
  { name: 'Smart Furniture', value: 'smart' }
])

const waitingTimeOptions = ref([
  { name: 'Just now', value: 'now' },
  { name: 'Within 1 day', value: '1day' },
  { name: '1-3 days', value: '1-3days' },
  { name: '3-7 days', value: '3-7days' },
  { name: 'Over 7 days', value: '7+days' }
])

const documentStatusOptions = ref([
  { name: 'Complete', value: 'complete' },
  { name: 'Incomplete', value: 'incomplete' },
  { name: 'Pending Review', value: 'pending' },
  { name: 'Missing Documents', value: 'missing' }
])

const priorityOptions = ref([
  { name: 'High', value: 'high' },
  { name: 'Medium', value: 'medium' },
  { name: 'Low', value: 'low' }
])

const approvalDateOptions = ref([
  { name: 'Today', value: 'today' },
  { name: 'This week', value: 'week' },
  { name: 'This month', value: 'month' },
  { name: 'Last month', value: 'last-month' },
  { name: 'All time', value: 'all' }
])

const rejectionReasonOptions = ref([
  { name: 'Incomplete Documentation', value: 'incomplete-docs' },
  { name: 'Business Location Issues', value: 'location' },
  { name: 'Duplicate Registration', value: 'duplicate' },
  { name: 'Invalid Business Type', value: 'invalid-type' },
  { name: 'Suspicious Activity', value: 'suspicious' },
  { name: 'Policy Violation', value: 'policy' },
  { name: 'Other', value: 'other' }
])

const allStatusOptions = ref([
  { name: 'Pending', value: 'pending' },
  { name: 'Approved', value: 'approved' },
  { name: 'Rejected', value: 'rejected' },
  { name: 'Suspended', value: 'suspended' },
  { name: 'Active', value: 'active' }
])

const verificationStatusOptions = ref([
  { name: 'Verified', value: 'verified' },
  { name: 'Pending', value: 'pending' },
  { name: 'Missing', value: 'missing' },
  { name: 'Invalid', value: 'invalid' }
])

const delayOptions = ref([
  { name: 'Immediately', value: '0' },
  { name: '1 hour', value: '1' },
  { name: '6 hours', value: '6' },
  { name: '24 hours', value: '24' },
  { name: '3 days', value: '72' }
])

// Computed Properties
const filteredPendingStores = computed(() => {
  let filtered = pendingStores.value

  if (searchTerm.value && activeView.value === 'pending') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(store =>
      store.storeName.toLowerCase().includes(term) ||
      store.ownerName.toLowerCase().includes(term) ||
      store.storeId.toLowerCase().includes(term)
    )
  }

  // Additional filters for pending view
  if (waitingTimeFilter.value) {
    // Implement waiting time filtering logic
  }

  if (documentStatusFilter.value.length > 0) {
    const statuses = documentStatusFilter.value.map(s => s.value)
    filtered = filtered.filter(store => statuses.includes(store.documentStatus.toLowerCase().replace(/ /g, '-')))
  }

  if (priorityFilter.value) {
    filtered = filtered.filter(store => store.priority === priorityFilter.value.name)
  }

  return filtered
})

const filteredApprovedStores = computed(() => {
  let filtered = approvedStores.value

  if (searchTerm.value && activeView.value === 'approved') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(store =>
      store.storeName.toLowerCase().includes(term) ||
      store.ownerName.toLowerCase().includes(term) ||
      store.storeId.toLowerCase().includes(term)
    )
  }

  // Additional filters for approved view
  if (approvalDateFilter.value) {
    // Implement approval date filtering logic
  }

  return filtered
})

const filteredRejectedStores = computed(() => {
  let filtered = rejectedStores.value

  if (searchTerm.value && activeView.value === 'rejected') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(store =>
      store.storeName.toLowerCase().includes(term) ||
      store.ownerName.toLowerCase().includes(term) ||
      store.storeId.toLowerCase().includes(term)
    )
  }

  // Additional filters for rejected view
  if (rejectionReasonFilter.value.length > 0) {
    const reasons = rejectionReasonFilter.value.map(r => r.value)
    filtered = filtered.filter(store => reasons.includes(store.rejectionReason.toLowerCase().replace(/ /g, '-')))
  }

  return filtered
})

const filteredAllStores = computed(() => {
  const allStores = [...pendingStores.value, ...approvedStores.value, ...rejectedStores.value]
  let filtered = allStores

  if (searchTerm.value && activeView.value === 'all') {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(store =>
      store.storeName.toLowerCase().includes(term) ||
      store.ownerName.toLowerCase().includes(term) ||
      store.storeId.toLowerCase().includes(term)
    )
  }

  // Status filter for all stores
  if (statusFilter.value) {
    filtered = filtered.filter(store => store.status === statusFilter.value.name)
  }

  return filtered
})

const approvedTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return approvedStores.value.filter(store => store.approvalDate === today).length
})

const rejectedTodayCount = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return rejectedStores.value.filter(store => store.rejectionDate === today).length
})

const totalStores = computed(() => {
  return pendingStores.value.length + approvedStores.value.length + rejectedStores.value.length
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

const getStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'warning'
    case 'approved':
    case 'active': return 'success'
    case 'rejected': return 'danger'
    case 'suspended': return 'secondary'
    default: return 'info'
  }
}

const getStoreStatusIcon = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'pi-clock'
    case 'approved':
    case 'active': return 'pi-check-circle'
    case 'rejected': return 'pi-times-circle'
    case 'suspended': return 'pi-pause-circle'
    default: return 'pi-store'
  }
}

const getStoreStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending': return 'bg-yellow-100 text-yellow-600'
    case 'approved':
    case 'active': return 'bg-green-100 text-green-600'
    case 'rejected': return 'bg-red-100 text-red-600'
    case 'suspended': return 'bg-gray-100 text-gray-600'
    default: return 'bg-blue-100 text-blue-600'
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

// Action Functions
const setActiveView = (view: string) => {
  activeView.value = view
  selectedStores.value = []
  searchTerm.value = ''
}

const togglePendingFilters = () => {
  showPendingFilters.value = !showPendingFilters.value
}

const reviewStore = (store: any) => {
  selectedReviewStore.value = store
  showReviewDialog.value = true
}

const approveStore = (store: any) => {
  if (!store) return

  // Move from pending to approved
  const pendingIndex = pendingStores.value.findIndex(s => s.id === store.id)
  if (pendingIndex !== -1) {
    const approvedStore = { ...pendingStores.value[pendingIndex] }
    approvedStore.approvalDate = new Date().toISOString().split('T')[0]
    approvedStore.approvedBy = 'Current Admin'
    approvedStore.status = 'Active'
    approvedStore.productsCount = 0
    approvedStore.revenue = 0

    pendingStores.value.splice(pendingIndex, 1)
    approvedStores.value.unshift(approvedStore)
  }

  showReviewDialog.value = false
}

const rejectStore = (store: any) => {
  storeToReject.value = store
  showRejectDialog.value = true
}

const confirmReject = () => {
  if (!storeToReject.value) return

  const pendingIndex = pendingStores.value.findIndex(s => s.id === storeToReject.value.id)
  if (pendingIndex !== -1) {
    const rejectedStore = { ...pendingStores.value[pendingIndex] }
    rejectedStore.rejectionDate = new Date().toISOString().split('T')[0]
    rejectedStore.rejectedBy = 'Current Admin'
    rejectedStore.status = 'Rejected'
    rejectedStore.rejectionReason = rejectionReason.value?.name || 'Other'
    rejectedStore.notes = rejectionNotes.value

    pendingStores.value.splice(pendingIndex, 1)
    rejectedStores.value.unshift(rejectedStore)
  }

  showRejectDialog.value = false
  rejectionReason.value = null
  rejectionNotes.value = ''
  storeToReject.value = null
}

const viewStore = (store: any) => {
  console.log('View store:', store)
  // Navigate to store details page
}

const suspendStore = (store: any) => {
  console.log('Suspend store:', store)
  // Implement suspension logic
}

const viewRejectedStore = (store: any) => {
  console.log('View rejected store:', store)
}

const rereviewStore = (store: any) => {
  // Move from rejected to pending
  const rejectedIndex = rejectedStores.value.findIndex(s => s.id === store.id)
  if (rejectedIndex !== -1) {
    const pendingStore = { ...rejectedStores.value[rejectedIndex] }
    delete pendingStore.rejectionDate
    delete pendingStore.rejectedBy
    delete pendingStore.rejectionReason
    delete pendingStore.notes
    pendingStore.status = 'Pending'
    pendingStore.documentStatus = 'Pending Review'

    rejectedStores.value.splice(rejectedIndex, 1)
    pendingStores.value.push(pendingStore)
  }
}

const viewDocument = (doc: any) => {
  console.log('View document:', doc)
  // Open document viewer
}

const requestMoreInfo = () => {
  console.log('Request more info for store:', selectedReviewStore.value)
  // Implement request more info logic
}

const bulkApprove = () => {
  selectedStores.value.forEach(store => {
    approveStore(store)
  })
  selectedStores.value = []
  showBulkApproveDialog.value = false
}

const bulkReject = () => {
  selectedStores.value.forEach(store => {
    storeToReject.value = store
    confirmReject()
  })
  selectedStores.value = []
  showBulkRejectDialog.value = false
}

const sendReminders = () => {
  console.log('Sending reminders to pending stores')
  // Implement reminder logic
}

const exportReport = () => {
  console.log('Exporting validation report')
  // Implement export logic
}

const saveSettings = () => {
  console.log('Saving validation settings')
  showSettingsDialog.value = false
}

onMounted(() => {
  console.log('Store Validation Management loaded')
})
</script>