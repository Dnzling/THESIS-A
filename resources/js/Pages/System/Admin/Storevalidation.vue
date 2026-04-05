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
    
                    <div class="space-y-4">
                        <div
                            v-for="store in filteredPendingStores"
                            :key="store.id"
                            class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex flex-wrap justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ store.storeType }}</p>
                                    <h4 class="text-xl font-semibold text-slate-900">{{ store.storeName }}</h4>
                                    <p class="text-xs text-slate-400">ID: {{ store.storeId }}</p>
                                </div>
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                                    :class="getStoreStatusColor(store.status)"
                                >
                                    {{ store.status }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
                                <div>
                                    <p class="font-semibold text-slate-800">{{ store.ownerName }}</p>
                                    <p class="truncate">{{ store.ownerEmail }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ formatDate(store.registrationDate) }}</p>
                                    <p class="text-xs text-slate-500">{{ store.waitingTime }} waiting</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="flex items-center gap-1 text-xs">
                                        <i :class="`pi ${getDocumentIcon(store.documentStatus)} ${getDocumentColor(store.documentStatus)}`"></i>
                                        {{ store.documentStatus }}
                                    </span>
                                    <span class="text-xs text-slate-500">Documents: {{ store.documents.length }}</span>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap justify-between items-center gap-2 text-xs text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-map-marker"></i>{{ store.address }}
                                </span>
                                <Button
                                    label="View Details"
                                    icon="pi pi-arrow-right"
                                    text
                                    size="small"
                                    severity="info"
                                    @click="viewStore(store)"
                                />
                            </div>
                        </div>
                    </div>
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
    
                    <div class="space-y-4">
                        <div
                            v-for="store in filteredApprovedStores"
                            :key="store.id"
                            class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex flex-wrap justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ store.storeType }}</p>
                                    <h4 class="text-xl font-semibold text-slate-900">{{ store.storeName }}</h4>
                                    <p class="text-xs text-slate-400">ID: {{ store.storeId }}</p>
                                </div>
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-semibold text-slate-600 bg-green-50 border-green-100"
                                >
                                    {{ store.status }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
                                <div>
                                    <p class="font-semibold text-slate-800">{{ store.ownerName }}</p>
                                    <p class="truncate">{{ store.ownerEmail }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ formatDate(store.approvalDate) }}</p>
                                    <p class="text-xs text-slate-500">Approved by {{ store.approvedBy }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-500">
                                        <i class="pi pi-check-circle text-green-500"></i>
                                        Products: {{ store.productsCount }}
                                    </span>
                                    <span class="text-xs text-slate-500">Revenue: PHP {{ store.revenue.toLocaleString() }}</span>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap justify-between items-center gap-2 text-xs text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-map-marker"></i>{{ store.address }}
                                </span>
                                <Button
                                    label="View Details"
                                    icon="pi pi-arrow-right"
                                    text
                                    size="small"
                                    severity="info"
                                    @click="viewStore(store)"
                                />
                            </div>
                        </div>
                    </div>
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
    
                    <div class="space-y-4">
                        <div
                            v-for="store in filteredRejectedStores"
                            :key="store.id"
                            class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex flex-wrap justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ store.storeType }}</p>
                                    <h4 class="text-xl font-semibold text-slate-900">{{ store.storeName }}</h4>
                                    <p class="text-xs text-slate-400">ID: {{ store.storeId }}</p>
                                </div>
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-semibold text-slate-600 bg-red-50 border-red-100"
                                >
                                    {{ store.status }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
                                <div>
                                    <p class="font-semibold text-slate-800">{{ store.ownerName }}</p>
                                    <p class="truncate">{{ store.ownerEmail }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ formatDate(store.rejectionDate) }}</p>
                                    <p class="text-xs text-slate-500">By: {{ store.rejectedBy }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-500">Reason: {{ store.rejectionReason || '—' }}</span>
                                    <span class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="pi pi-times-circle text-red-500"></i>
                                        {{ store.documentStatus }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap justify-between items-center gap-2 text-xs text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-map-marker"></i>{{ store.address }}
                                </span>
                                <Button
                                    label="View Details"
                                    icon="pi pi-arrow-right"
                                    text
                                    size="small"
                                    severity="info"
                                    @click="viewRejectedStore(store)"
                                />
                            </div>
                        </div>
                    </div>
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
    
                    <div class="space-y-4">
                        <div
                            v-for="store in filteredAllStores"
                            :key="store.id"
                            class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex flex-wrap justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ store.storeType }}</p>
                                    <h4 class="text-xl font-semibold text-slate-900">{{ store.storeName }}</h4>
                                    <p class="text-xs text-slate-400">ID: {{ store.storeId }}</p>
                                </div>
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-semibold text-slate-600"
                                    :class="getStoreStatusColor(store.status)"
                                >
                                    {{ store.status }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
                                <div>
                                    <p class="font-semibold text-slate-800">{{ store.ownerName }}</p>
                                    <p class="truncate">{{ store.ownerEmail }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ formatDate(store.registrationDate) }}</p>
                                    <p class="text-xs text-slate-500">{{ store.waitingTime || store.age || '—' }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-500">
                                        <i class="pi pi-store text-slate-500"></i> Products: {{ store.productsCount }}
                                    </span>
                                    <span class="text-xs text-slate-500">Revenue: PHP {{ store.revenue.toLocaleString() }}</span>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap justify-between items-center gap-2 text-xs text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="pi pi-map-marker"></i>{{ store.address }}
                                </span>
                                <Button
                                    label="View Details"
                                    icon="pi pi-arrow-right"
                                    text
                                    size="small"
                                    severity="info"
                                    @click="viewStore(store)"
                                />
                            </div>
                        </div>
                    </div>
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

        <!-- View Store Dialog -->
        <Dialog v-model:visible="showViewDialog" modal
            :header="selectedViewStore ? `Store Details: ${selectedViewStore.storeName}` : 'Store Details'"
            :style="{ width: '800px' }">
            <div v-if="selectedViewStore" class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-800 mb-3">Store Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Store Name</p>
                            <p class="font-medium">{{ selectedViewStore.storeName }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Store Type</p>
                            <p class="font-medium">{{ selectedViewStore.storeType }}</p>
                        </div>
                        <div>
                          <p class="text-sm text-gray-500">Store Code</p>
                          <p class="font-medium">{{ selectedViewStore.storeCode || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Address</p>
                            <p class="font-medium">{{ selectedViewStore.address }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Contact Number</p>
                            <p class="font-medium">{{ selectedViewStore.contactNumber }}</p>
                        </div>
                        <div>
                          <p class="text-sm text-gray-500">City / Province</p>
                          <p class="font-medium">{{ selectedViewStore.city || 'N/A' }} / {{ selectedViewStore.province || 'N/A' }}</p>
                        </div>
                        <div>
                          <p class="text-sm text-gray-500">Coordinates</p>
                          <p class="font-medium">{{ selectedViewStore.coordinates || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Owner Information</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Owner Name</p>
                                <p class="font-medium">{{ selectedViewStore.ownerName }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Owner Email</p>
                                <p class="font-medium">{{ selectedViewStore.ownerEmail }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Owner Phone</p>
                                <p class="font-medium">{{ selectedViewStore.ownerPhone }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Registration Date</p>
                                <p class="font-medium">{{ formatDate(selectedViewStore.registrationDate) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                      <div>
                        <h4 class="font-medium text-gray-800 mb-3">Submitted Verification Details</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                          <div class="grid grid-cols-2 gap-4">
                            <div>
                              <p class="text-sm text-gray-500">Business Registration Number</p>
                              <p class="font-medium">{{ selectedViewStore.businessRegistrationNumber || 'N/A' }}</p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Business Registration Date</p>
                              <p class="font-medium">{{ selectedViewStore.businessRegistrationDate ? formatDate(selectedViewStore.businessRegistrationDate) : 'N/A' }}</p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Government ID Type</p>
                              <p class="font-medium">{{ selectedViewStore.govIdType || 'N/A' }}</p>
                            </div>
                            <div>
                              <p class="text-sm text-gray-500">Government ID Number</p>
                              <p class="font-medium">{{ selectedViewStore.govIdNumber || 'N/A' }}</p>
                            </div>
                          </div>
                        </div>
                      </div>

                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Documents</h4>
                    <div v-if="selectedViewStore.documents?.length" class="space-y-3">
                        <div v-for="doc in selectedViewStore.documents" :key="doc.name"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div class="flex items-center space-x-3">
                                <i :class="`pi ${getDocumentTypeIcon(doc.type)} ${getDocumentTypeColor(doc.type)}`"></i>
                                <div>
                                    <p class="font-medium">{{ doc.name }}</p>
                                    <p class="text-xs text-gray-500">Status: {{ doc.status || 'Pending' }}</p>
                                </div>
                            </div>
                            <Button label="View" size="small" icon="pi pi-eye" @click="viewDocument(doc)" />
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">No documents uploaded.</div>
                </div>
            </div>
            <template #footer>
                <Button
                    v-if="selectedViewStore && selectedViewStore.verificationId && selectedViewStore.status && selectedViewStore.status.toLowerCase() === 'pending'"
                    label="Reject"
                    icon="pi pi-times"
                    severity="danger"
                    @click="rejectStore(selectedViewStore)"
                />
                <Button
                    v-if="selectedViewStore && selectedViewStore.verificationId && selectedViewStore.status && selectedViewStore.status.toLowerCase() === 'pending'"
                    label="Approve"
                    icon="pi pi-check"
                    severity="success"
                    @click="approveStore(selectedViewStore)"
                />
                <Button label="Close" severity="secondary" @click="showViewDialog = false" />
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
import axiosClient from '../../../axios'
import { useToast } from 'primevue/usetoast'
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
const pendingStores = ref<any[]>([])
const approvedStores = ref<any[]>([])
const rejectedStores = ref<any[]>([])
const showReviewDialog = ref(false)
const showViewDialog = ref(false)
const showRejectDialog = ref(false)
const showBulkApproveDialog = ref(false)
const showBulkRejectDialog = ref(false)
const showSettingsDialog = ref(false)

const toast = useToast()
const showPendingFilters = ref(false)
const selectedStores = ref<any[]>([])
const selectedReviewStore = ref<any>(null)
const selectedViewStore = ref<any>(null)
const storeToReject = ref<any>(null)
const reviewNotes = ref('')
const rejectionReason = ref<{ name?: string; value?: string } | null>(null)
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

const apiBaseUrl = String(axiosClient.defaults.baseURL || '').replace(/\/api\/?$/, '')

const buildFileUrl = (path: string | null) => {
  if (!path) return ''
  return `${apiBaseUrl}/storage/${path}`
}

const mapVerification = (verification: any) => {
  const store = verification.store || {}
  const docs = [
    { name: 'Business Registration', path: verification.business_registration_file },
    { name: 'Business Permit', path: verification.business_permit_file },
    { name: 'Tax Certificate', path: verification.tax_certificate_file },
    { name: 'Gov ID Front', path: verification.gov_id_front_file },
    { name: 'Gov ID Back', path: verification.gov_id_back_file },
    { name: 'Selfie with ID', path: verification.selfie_with_id_file },
  ].filter(d => d.path).map(d => ({
    name: d.name,
    status: 'Submitted',
    verificationStatus: 'pending',
    path: d.path,
    url: buildFileUrl(d.path)
  }))

  if (Array.isArray(verification.other_documents)) {
    verification.other_documents.forEach((path: string, index: number) => {
      docs.push({
        name: `Other Document ${index + 1}`,
        status: 'Submitted',
        verificationStatus: 'pending',
        path,
        url: buildFileUrl(path)
      })
    })
  }

  const requiredFields = [
    verification.business_registration_file,
    verification.gov_id_front_file,
    verification.gov_id_back_file,
    verification.selfie_with_id_file
  ]
  const documentStatus = requiredFields.every(Boolean) ? 'Complete' : 'Incomplete'

    const rawStatus = String(
        verification.status
            || (verification.rejection_reason ? 'rejected' : verification.reviewed_at ? 'approved' : 'pending')
    ).toLowerCase()
    const normalizedStatus = rawStatus.charAt(0).toUpperCase() + rawStatus.slice(1)

    return {
    id: verification.id,
    verificationId: verification.id,
    storeId: store.id ? `STORE-${String(store.id).padStart(6, '0')}` : `STORE-${verification.store_id}`,
    storeName: store.name || store.store_name || 'Unknown Store',
    ownerName: store.settings?.contact_person || store.contact_person || 'N/A',
    ownerEmail: store.email || 'N/A',
    ownerPhone: store.phone || store.contact_number || 'N/A',
    storeType: store.type || store.settings?.store_type || 'General',
    storeCode: store.store_code || '',
    address: store.address || 'N/A',
    city: store.city || '',
    province: store.province || '',
    coordinates: store.latitude && store.longitude ? `${store.latitude}, ${store.longitude}` : '',
    contactNumber: store.phone || store.contact_number || 'N/A',
    registrationDate: verification.submitted_at || store.created_at,
    waitingTime: verification.submitted_at ? `${Math.max(0, Math.floor((Date.now() - new Date(verification.submitted_at).getTime()) / 86400000))} days` : 'N/A',
    businessRegistrationNumber: verification.business_registration_number || '',
    businessRegistrationDate: verification.business_registration_date || '',
    govIdType: verification.gov_id_type || '',
    govIdNumber: verification.gov_id_number || '',
    documentStatus,
    priority: 'Medium',
    documents: docs,
    approvalDate: verification.reviewed_at || null,
    approvedBy: verification.reviewer ? `${verification.reviewer.fname} ${verification.reviewer.lname}` : '—',
    rejectionDate: verification.reviewed_at || null,
    rejectedBy: verification.reviewer ? `${verification.reviewer.fname} ${verification.reviewer.lname}` : '—',
    rejectionReason: verification.rejection_reason || '',
    status: normalizedStatus,
    productsCount: store.products_count || 0,
    revenue: 0
  }
}

const loadStoreVerifications = async () => {
  try {
    const [pendingRes, approvedRes, rejectedRes] = await Promise.all([
      axiosClient.get('/api/pending-verification', { params: { per_page: 100 } }),
      axiosClient.get('/api/store-verifications', { params: { status: 'approved', per_page: 100 } }),
      axiosClient.get('/api/store-verifications', { params: { status: 'rejected', per_page: 100 } }),
    ])

    pendingStores.value = (pendingRes.data?.data?.data || pendingRes.data?.data || []).map(mapVerification)
    approvedStores.value = (approvedRes.data?.data?.data || approvedRes.data?.data || []).map(mapVerification)
    rejectedStores.value = (rejectedRes.data?.data?.data || rejectedRes.data?.data || []).map(mapVerification)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load store verifications', life: 3000 })
  }
}

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

const getStoreStatusIcon = (status: string | undefined | null) => {
  const normalized = String(status || '').toLowerCase()
  switch (normalized) {
    case 'pending': return 'pi-clock'
    case 'approved':
    case 'active': return 'pi-check-circle'
    case 'rejected': return 'pi-times-circle'
    case 'suspended': return 'pi-pause-circle'
    default: return 'pi-store'
  }
}

const getStoreStatusColor = (status: string | undefined | null) => {
  const normalized = String(status || '').toLowerCase()
  switch (normalized) {
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

const getDocumentTypeIcon = (_type: string) => 'pi-file'
const getDocumentTypeColor = (_type: string) => 'text-gray-500'

const applyFilters = () => {
  // Filters are reactive via computed lists.
}

const clearFilters = () => {
  waitingTimeFilter.value = null
  documentStatusFilter.value = []
  priorityFilter.value = null
  storeTypeFilter.value = []
  dateFilter.value = null
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

const approveStore = async (store: any) => {
  if (!store?.verificationId) return
  try {
    await axiosClient.post(`/api/store-verification/${store.verificationId}/review`, {
      action: 'approve'
    })
    toast.add({ severity: 'success', summary: 'Approved', detail: 'Store approved', life: 3000 })
        showReviewDialog.value = false
        showViewDialog.value = false
        selectedReviewStore.value = null
        selectedViewStore.value = null
    await loadStoreVerifications()
        activeView.value = 'approved'
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to approve store', life: 3000 })
  }
}

const rejectStore = (store: any) => {
  storeToReject.value = store
  showRejectDialog.value = true
}

const confirmReject = async () => {
  if (!storeToReject.value?.verificationId) return

    const selectedReason = rejectionReason.value?.name || rejectionReason.value?.value || ''
    const detailedReason = String(rejectionNotes.value || '').trim()
    const combinedReason = [selectedReason, detailedReason].filter(Boolean).join(' - ')

    if (!combinedReason) {
        toast.add({ severity: 'warn', summary: 'Reason Required', detail: 'Please select or enter a rejection reason.', life: 3000 })
        return
    }

  try {
    await axiosClient.post(`/api/store-verification/${storeToReject.value.verificationId}/review`, {
      action: 'reject',
            rejection_reason: combinedReason
    })
    toast.add({ severity: 'success', summary: 'Rejected', detail: 'Store rejected', life: 3000 })
    await loadStoreVerifications()
        activeView.value = 'rejected'
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to reject store', life: 3000 })
  } finally {
    showRejectDialog.value = false
        showViewDialog.value = false
        showReviewDialog.value = false
    rejectionReason.value = null
    rejectionNotes.value = ''
    storeToReject.value = null
        selectedReviewStore.value = null
        selectedViewStore.value = null
  }
}

const viewStore = (store: any) => {
  selectedViewStore.value = store
  showViewDialog.value = true
}

const suspendStore = (store: any) => {
  console.log('Suspend store:', store)
  // Implement suspension logic
}

const viewRejectedStore = (store: any) => {
  selectedViewStore.value = store
  showViewDialog.value = true
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
  if (doc?.url) {
    window.open(doc.url, '_blank')
    return
  }
  toast.add({ severity: 'info', summary: 'No Document', detail: 'Document not available', life: 2000 })
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

onMounted(async () => {
  pendingStores.value = []
  approvedStores.value = []
  rejectedStores.value = []
  await loadStoreVerifications()
})
</script>
