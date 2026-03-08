<!-- views/system/shifts/ShiftView.vue -->
<template>
  <div class="space-y-6 text-sm">
    <!-- Header with HR-focused actions -->
    <div class="flex justify-between items-center">
      <div class="flex gap-2 ml-auto">
        <Button label="Export Report" icon="pi pi-download" severity="secondary" outlined @click="exportReport" />
        <Button v-if="activeTab === 'assignments'" :loading="assignmentDialog" label="New Assignment" icon="pi pi-plus"
          severity="info" @click="openAssignmentDialog" />
        <Button v-if="activeTab === 'shiftswap'" label="Request Swap" icon="pi pi-share-alt" severity="info"
          @click="openSwapDialog" />
        <Button v-if="activeTab !== 'coverage'" label="Create Shift" icon="pi pi-plus" severity="info"
          @click="goToCreateShift" />
      </div>
    </div>
  
    <!-- HR Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-sm text-gray-500">Total Employees</div>
            <div class="text-2xl font-semibold mt-1">{{ dashboardStats.totalEmployees }}</div>
          </div>
          <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-users text-blue-500"></i>
          </div>
        </div>
        <div class="text-xs text-gray-400 mt-2">Active: {{ dashboardStats.activeEmployees }}</div>
      </div>
  
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-sm text-gray-500">Today's Shifts</div>
            <div class="text-2xl font-semibold mt-1">{{ dashboardStats.todayShifts }}</div>
          </div>
          <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-calendar text-green-500"></i>
          </div>
        </div>
        <div class="text-xs text-gray-400 mt-2">{{ dashboardStats.onLeave }} on leave</div>
      </div>
  
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-sm text-gray-500">Pending Swaps</div>
            <div class="text-2xl font-semibold mt-1">{{ dashboardStats.pendingSwaps }}</div>
          </div>
          <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-clock text-orange-500"></i>
          </div>
        </div>
        <div class="text-xs text-orange-400 mt-2">Action required</div>
      </div>
  
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-sm text-gray-500">Night Diff</div>
            <div class="text-2xl font-semibold mt-1">{{ dashboardStats.nightDiffEmployees }}</div>
          </div>
          <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-moon text-purple-500"></i>
          </div>
        </div>
        <div class="text-xs text-gray-400 mt-2">Eligible employees</div>
      </div>
  
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-sm text-gray-500">Unfilled Shifts</div>
            <div class="text-2xl font-semibold mt-1 text-red-500">{{ dashboardStats.unfilledShifts }}</div>
          </div>
          <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-exclamation-triangle text-red-500"></i>
          </div>
        </div>
        <div class="text-xs text-red-400 mt-2">Needs attention</div>
      </div>
    </div>
  
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <i class="pi pi-spin pi-spinner text-3xl text-blue-500"></i>
    </div>
  
    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      <div class="flex items-center gap-2">
        <i class="pi pi-exclamation-triangle"></i>
        <span>{{ error }}</span>
      </div>
      <Button label="Retry" icon="pi pi-refresh" severity="danger" outlined size="small" class="mt-2"
        @click="fetchData" />
    </div>
  
    <!-- Main Content -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <Tabs v-model:value="activeTab">
        <TabList class="px-4 pt-2 border-b border-gray-100">
          <Tab value="coverage">Coverage View</Tab>
          <Tab value="assignments">Shift Assignment</Tab>
          <Tab value="shiftswap">Shift Swap</Tab>
          <Tab value="shifts">All Shifts</Tab>
        </TabList>
  
        <TabPanels class="p-4">
          <!-- COVERAGE VIEW -->
          <TabPanel value="coverage">
            <div class="space-y-4">
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                  <Button icon="pi pi-chevron-left" text rounded size="small" @click="previousDay" />
                  <DatePicker v-model="selectedDate" dateFormat="MM dd, yy" class="w-40"
                    @date-select="fetchCoverageData" />
                  <Button icon="pi pi-chevron-right" text rounded size="small" @click="nextDay" />
                </div>
                <Tag value="Today" severity="info" v-if="isToday" />
              </div>
  
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="dept in departments" :key="dept.id" class="border border-gray-100 rounded-lg p-4">
                  <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold">{{ dept.name }}</h3>
                    <div>
                      <span class="text-sm font-medium">{{ dept.scheduled }}/{{ dept.totalEmployees }}</span>
                      <span class="text-xs text-gray-400 ml-1">scheduled</span>
                    </div>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
                    <div class="bg-blue-500 h-2 rounded-full" :style="{ width: dept.coveragePercentage + '%' }"></div>
                  </div>
                  <div class="space-y-2 mt-3">
                    <div v-for="emp in dept.scheduledEmployees" :key="emp.id"
                      class="flex items-center justify-between text-sm">
                      <div class="flex items-center gap-2">
                        <Avatar :label="getInitials(emp.name)" size="small" class="bg-blue-100 text-blue-600" />
                        <span>{{ emp.name }}</span>
                      </div>
                      <Tag :value="emp.shiftType" :severity="getShiftSeverity(emp.shiftType)" size="small" />
                    </div>
                    <div v-for="n in dept.unfilledCount" :key="'unfilled-'+n"
                      class="flex items-center gap-2 text-sm text-gray-400">
                      <i class="pi pi-plus-circle text-xs"></i>
                      <span>Unfilled slot {{ n }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>
  
          <!-- SHIFT ASSIGNMENT TAB -->
          <TabPanel value="assignments">
            <div class="space-y-4">
              <div class="flex gap-3 mb-4">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="assignmentFilters.search" placeholder="Search Employee..." size="small"
                    @input="fetchAssignments" />
                </IconField>
                <Select v-model="assignmentFilters.type" :options="assignmentTypeOptions" option-label="label"
                  option-value="value" size="small"
                  placeholder="Assignment Type" showClear @change="fetchAssignments" />
              </div>
  
              <DataTable :value="assignments" :paginator="true" :rows="10" :loading="assignmentsLoading"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="text-sm">
                <Column field="employee.full_name" header="Employee" sortable>
                  <template #body="{ data }">
                    <div class="flex items-center gap-2">
  
                      {{ data.employee?.fname || 'N/A' }} {{ data.employee?.lname || 'N/A' }}
                    </div>
                  </template>
                </Column>
                <Column field="shift.name" header="Shift" sortable>
                  <template #body="{ data }">
                    <Tag :value="data.shift?.name" :severity="getShiftSeverity(data.shift?.name)" rounded />
                  </template>
                </Column>
                <Column field="start_date" header="Start Date" sortable>
                  <template #body="{ data }">
                    {{ formatDate(data.start_date) }}
                  </template>
                </Column>
                <Column field="end_date" header="End Date" sortable>
                  <template #body="{ data }">
                    {{ data.end_date ? formatDate(data.end_date) : 'Permanent' }}
                  </template>
                </Column>
                <Column field="assignment_type" header="Type">
                  <template #body="{ data }">
                    <Tag :value="data.assignment_type"
                      :severity="data.assignment_type === 'permanent' ? 'success' : 'warning'" rounded />
                  </template>
                </Column>
                <Column header="Actions" style="width: 100px">
                  <template #body="{ data }">
                    <Button icon="pi pi-trash" text rounded severity="danger" size="small"
                      @click="confirmDeleteAssignment(data)" />
                  </template>
                </Column>
              </DataTable>
            </div>
          </TabPanel>
  
          <!-- SHIFT SWAP TAB -->
          <TabPanel value="shiftswap">
            <div class="space-y-4">
              <div class="flex gap-3 mb-4">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="swapFilters.search" placeholder="Search..." size="small"
                    @input="fetchSwapRequests" />
                </IconField>
                <Select v-model="swapFilters.status" :options="swapStatusOptions" option-label="label"
                  option-value="value" size="small" placeholder="Status" showClear @change="fetchSwapRequests" />
              </div>
  
              <DataTable :value="swapRequests" :paginator="true" :rows="10" :loading="swapLoading"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="text-sm">
                <Column header="Requestor">
                  <template #body="{ data }">
                    <div class="flex items-center gap-2">
                      <Avatar :label="(getInitials(data.requestor?.fname)+ getInitials(data.requestor?.lname)) "
                        size="small" class="bg-blue-100 text-blue-600" />
                      {{ data.requestor?.fname || 'N/A' }} {{ data.requestor?.lname || 'N/A' }}
                    </div>
                  </template>
                </Column>
                <Column header="Receiver">
                  <template #body="{ data }">
                    <div class="flex items-center gap-2">
                      <Avatar :label="(getInitials(data.receiver?.fname)+ getInitials(data.receiver?.lname)) "
                        size="small" class="bg-green-100 text-green-600" />
                      {{ data.receiver?.fname || 'N/A' }} {{ data.receiver?.lname || 'N/A' }}
                    </div>
                  </template>
                </Column>
                <Column header="Swap Details">
                  <template #body="{ data }">
                    <div class="text-xs">
                      <div><strong>Type:</strong> {{ data.swap_type }}</div>
                      <div><strong>Date:</strong> {{ formatDate(data.requestorSchedule?.schedule_date) }}</div>
                    </div>
                  </template>
                </Column>
                <Column field="status" header="Status">
                  <template #body="{ data }">
                    <Tag :value="data.status.toUpperCase()" :severity="getSwapStatusSeverity(data.status)" rounded />
                  </template>
                </Column>
                <Column header="Actions">
                  <template #body="{ data }">
                    <div class="flex gap-2" v-if="data.status === 'pending' && !isMyRequest(data)">
                      <Button label="Accept" icon="pi pi-check" size="small" severity="success"
                        @click="confirmSwapAction(data, 'accept')" />
                      <Button label="Reject" icon="pi pi-times" size="small" severity="danger" outlined
                        @click="confirmSwapAction(data, 'reject')" />
                    </div>
                    <div v-else-if="data.status === 'pending' && isMyRequest(data)">
                      <Button label="Cancel" icon="pi pi-ban" size="small" severity="warning" outlined
                        @click="confirmSwapAction(data, 'cancel')" />
                    </div>
                    <span v-else class="text-gray-400 text-xs">No actions</span>
                  </template>
                </Column>
              </DataTable>
            </div>
          </TabPanel>
  
          <!-- ALL SHIFTS VIEW — Shift Definitions -->
          <TabPanel value="shifts">
            <div class="space-y-4">
              <div class="flex gap-3">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="shiftDefFilters.search" placeholder="Search shifts..." size="small" />
                </IconField>
                <Select v-model="shiftDefFilters.type"
                  :options="[{label:'Fixed',value:'fixed'},{label:'Rotating',value:'rotating'},{label:'Flexible',value:'flexible'}]"
                  optionLabel="label" optionValue="value" size="small" placeholder="Shift Type" showClear />
              </div>

              <DataTable :value="filteredShiftDefinitions" :paginator="true" :rows="10" :loading="shiftDefsLoading"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="text-sm">
                <Column field="name" header="Name" sortable>
                  <template #body="{ data }">
                    <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full inline-block" :style="{ background: data.color || '#3b82f6' }"></span>
                      <span class="font-medium">{{ data.name }}</span>
                    </div>
                  </template>
                </Column>
                <Column field="code" header="Code" sortable />
                <Column header="Schedule">
                  <template #body="{ data }">
                    {{ data.start_time ? String(data.start_time).substring(0,5) : '--' }}
                    –
                    {{ data.end_time ? String(data.end_time).substring(0,5) : '--' }}
                  </template>
                </Column>
                <Column field="total_hours" header="Hours">
                  <template #body="{ data }">{{ data.total_hours }}h</template>
                </Column>
                <Column field="shift_type" header="Type" sortable>
                  <template #body="{ data }">
                    <Tag :value="data.shift_type" severity="info" rounded />
                  </template>
                </Column>
                <Column field="grace_period_minutes" header="Grace (min)" />
                <Column field="is_active" header="Status">
                  <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                      :severity="data.is_active ? 'success' : 'secondary'" rounded />
                  </template>
                </Column>
                <Column header="Actions" style="width: 110px">
                  <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded severity="info" size="small"
                      @click="openEditShiftDialog(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" size="small"
                      @click="confirmDeleteShift(data)" />
                  </template>
                </Column>
              </DataTable>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  
    <!-- Create Assignment Dialog -->
    <Dialog v-model:visible="assignmentDialogVisible" modal header="Create Assignment" :style="{ width: '50vw' }">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Employee</label>
            <Select v-model="assignmentForm.employee_id" :options="employeeOptions" optionLabel="label"
              optionValue="value" placeholder="Select Employee" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Shift</label>
            <Select v-model="assignmentForm.shift_id" :options="shiftOptions" optionLabel="label" optionValue="value"
              placeholder="Select Shift" class="w-full" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Start Date</label>
            <DatePicker v-model="assignmentForm.start_date" showIcon class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">End Date (Optional)</label>
            <DatePicker v-model="assignmentForm.end_date" showIcon class="w-full" />
          </div>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Assignment Type</label>
          <Select v-model="assignmentForm.assignment_type" :options="assignmentTypeOptions" option-label="label"
            option-value="value" placeholder="Select Type" class="w-full" />
  
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Notes</label>
          <Textarea v-model="assignmentForm.notes" rows="3" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="assignmentDialogVisible = false" />
        <Button label="Create" icon="pi pi-check" @click="createAssignment" :loading="formLoading" />
      </template>
    </Dialog>
  
    <!-- Create Swap Request Dialog -->
    <Dialog v-model:visible="swapDialogVisible" modal header="Request Shift Swap" :style="{ width: '50vw' }">
      <div class="space-y-4">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Receiver (Employee to swap with)</label>
          <Select v-model="swapForm.receiver_id" :options="employeeOptions" optionLabel="label" optionValue="value"
            placeholder="Select Employee" class="w-full" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Your Shift</label>
            <Select v-model="swapForm.requestor_schedule_id" :options="myShiftOptions" optionLabel="label"
              optionValue="value" placeholder="Select Your Shift" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Receiver's Shift</label>
            <Select v-model="swapForm.receiver_schedule_id" :options="receiverShiftOptions" optionLabel="label"
              optionValue="value" placeholder="Select Receiver's Shift" class="w-full" />
          </div>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Swap Type</label>
          <Select v-model="swapForm.swap_type" :options="swapTypeOptions" optionLabel="label" optionValue="value"
            placeholder="Select Swap Type" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Reason</label>
          <Textarea v-model="swapForm.reason" rows="3" class="w-full" placeholder="Explain why you want to swap..." />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="swapDialogVisible = false" />
        <Button label="Submit Request" icon="pi pi-send" @click="createSwapRequest" :loading="formLoading" />
      </template>
    </Dialog>
  
    <!-- Error Modal -->
    <Dialog v-model:visible="errorDialogVisible" modal header="Error" :style="{ width: '40vw' }" :closable="true">
      <div class="flex items-center gap-3 p-4">
        <i class="pi pi-times-circle text-red-500 text-3xl"></i>
        <div>
          <p class="font-semibold text-red-500">An error occurred</p>
          <p class="mt-1">{{ errorMessage }}</p>
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="errorDialogVisible = false" />
      </template>
    </Dialog>
  
    <!-- Confirmation Modal -->
    <Dialog v-model:visible="confirmDialogVisible" modal header="Confirm Action" :style="{ width: '40vw' }"
      :closable="true">
      <div class="flex items-center gap-3 p-4">
        <i class="pi pi-exclamation-triangle text-orange-500 text-3xl"></i>
        <div>
          <p class="font-semibold">{{ confirmTitle }}</p>
          <p class="mt-1">{{ confirmMessage }}</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="confirmDialogVisible = false" />
        <Button :label="confirmButtonLabel" :severity="confirmButtonSeverity" @click="executeConfirmedAction"
          :loading="actionLoading" />
      </template>
    </Dialog>

    <!-- Edit Shift Dialog -->
    <Dialog v-model:visible="editShiftDialogVisible" modal header="Edit Shift" :style="{ width: '55vw' }">
      <div class="space-y-4 p-1">
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Shift Name <span class="text-red-500">*</span></label>
            <InputText v-model="editShiftForm.name" class="w-full" :class="{ 'p-invalid': editShiftErrors.name }" />
            <small class="text-red-500" v-if="editShiftErrors.name">{{ editShiftErrors.name[0] }}</small>
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Shift Code <span class="text-red-500">*</span></label>
            <InputText v-model="editShiftForm.code" class="w-full" :class="{ 'p-invalid': editShiftErrors.code }" />
            <small class="text-red-500" v-if="editShiftErrors.code">{{ editShiftErrors.code[0] }}</small>
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Shift Type</label>
            <Select v-model="editShiftForm.shift_type"
              :options="[{label:'Fixed',value:'fixed'},{label:'Rotating',value:'rotating'},{label:'Flexible',value:'flexible'}]"
              optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Color</label>
            <div class="flex gap-2 items-center">
              <input type="color" v-model="editShiftForm.color" class="h-9 w-14 rounded border border-gray-300 cursor-pointer" />
              <InputText v-model="editShiftForm.color" class="flex-1" />
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Start Time</label>
            <InputText v-model="editShiftForm.start_time" type="time" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">End Time</label>
            <InputText v-model="editShiftForm.end_time" type="time" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Break Start</label>
            <InputText v-model="editShiftForm.break_start" type="time" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Break End</label>
            <InputText v-model="editShiftForm.break_end" type="time" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Total Hours</label>
            <InputText v-model="editShiftForm.total_hours" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Grace Period (min)</label>
            <InputNumber v-model="editShiftForm.grace_period_minutes" :min="0" :max="60" class="w-full" />
          </div>
        </div>
        <!-- Working Days -->
        <div class="field">
          <label class="block text-sm font-medium mb-2">Working Days</label>
          <div class="flex gap-2">
            <div v-for="day in weekDayOptions" :key="day.value" class="flex-1">
              <div class="border rounded-lg p-2 text-center cursor-pointer transition-colors text-xs"
                :class="editShiftForm.week_days.includes(day.value) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'hover:bg-gray-50 border-gray-200'"
                @click="toggleEditShiftDay(day.value)">
                <div class="font-medium">{{ day.label }}</div>
                <div class="text-gray-400 mt-0.5">{{ day.full.substring(0,3) }}</div>
              </div>
            </div>
          </div>
        </div>
        <!-- Night Diff -->
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Min Employees Required</label>
            <InputNumber v-model="editShiftForm.min_employees_required" :min="1" class="w-full" />
          </div>
          <div class="field flex items-center gap-3 pt-6">
            <Checkbox v-model="editShiftForm.has_night_diff" inputId="editNightDiff" binary />
            <label for="editNightDiff" class="text-sm">Night Differential</label>
          </div>
        </div>
        <div v-if="editShiftForm.has_night_diff" class="field">
          <label class="block text-sm font-medium mb-1">Night Diff Rate</label>
          <InputNumber v-model="editShiftForm.night_diff_rate" :min="1" :max="3" :step="0.01"
            :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
        </div>
        <div class="field flex items-center gap-3">
          <Checkbox v-model="editShiftForm.is_active" inputId="editIsActive" binary />
          <label for="editIsActive" class="text-sm font-medium">Active</label>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Description</label>
          <Textarea v-model="editShiftForm.description" rows="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="editShiftDialogVisible = false" />
        <Button label="Save Changes" icon="pi pi-check" severity="info" @click="updateShift" :loading="editShiftSaving" />
      </template>
    </Dialog>

    <!-- Delete Shift Confirmation -->
    <Dialog v-model:visible="deleteShiftDialogVisible" modal header="Delete Shift" :style="{ width: '35vw' }">
      <div class="flex items-center gap-3 p-4">
        <i class="pi pi-exclamation-triangle text-red-500 text-3xl"></i>
        <div>
          <p class="font-semibold">Delete "{{ selectedShiftForDelete?.name }}"?</p>
          <p class="text-sm text-gray-500 mt-1">This cannot be undone. Shifts with active assignments cannot be deleted.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="deleteShiftDialogVisible = false" />
        <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteShift" :loading="deletingShift" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()

// --- General State ---
const activeTab = ref('coverage')
const selectedDate = ref(new Date())
const loading = ref(true)
const assignmentDialog = ref(false)
const error = ref('')
const shiftsData = ref<any[]>([])
const authStore = useAuthStore()

// --- Modal States ---
const errorDialogVisible = ref(false)
const errorMessage = ref('')
const confirmDialogVisible = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmButtonLabel = ref('Confirm')
const confirmButtonSeverity = ref('danger')
const actionLoading = ref(false)
const formLoading = ref(false)
const pendingAction = ref<{ type: string; data?: any; action?: string } | null>(null)

// --- Shifts State ---
const shiftsLoading = ref(false)
const shiftFilters = ref({
  search: '',
  department: null as string | null,
  shiftType: null as string | null,
  date: null as Date | null
})
const departmentOptions = ref<{ label: string; value: string }[]>([])
const shiftTypeOptions = ref<{ label: string; value: string }[]>([])
const shiftOptions = ref<{ label: string; value: number }[]>([])

// --- Assignment State ---
const assignments = ref<any[]>([])
const assignmentsLoading = ref(false)
const assignmentFilters = ref({ search: '', type: null as string | null })
const assignmentTypeOptions = ref([
  { label: 'Permanent', value: 'permanent' },
  { label: 'Temporary', value: 'temporary' },
  { label: 'Cover', value: 'cover' }
])
const assignmentDialogVisible = ref(false)
const assignmentForm = ref({
  employee_id: null as number | null,
  shift_id: null as number | null,
  start_date: null as Date | null,
  end_date: null as Date | null,
  assignment_type: null as string | null,
  notes: ''
})
const employeeOptions = ref<{ label: string; value: number }[]>([])
const myShiftOptions = ref<{ label: string; value: number }[]>([])
const receiverShiftOptions = ref<{ label: string; value: number }[]>([])

// --- Swap State ---
const swapRequests = ref<any[]>([])
const swapLoading = ref(false)
const swapFilters = ref({ search: '', status: null as string | null })
const swapStatusOptions = ref([
  { label: 'Pending', value: 'pending' },
  { label: 'Accepted', value: 'accepted' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' }
])
const swapDialogVisible = ref(false)
const swapForm = ref({
  receiver_id: null as number | null,
  requestor_schedule_id: null as number | null,
  receiver_schedule_id: null as number | null,
  swap_type: null as string | null,
  reason: ''
})
const swapTypeOptions = ref([
  { label: 'Full Swap (Same Date)', value: 'full_swap' },
  { label: 'Give Away (Transfer shift)', value: 'give_away' },
  { label: 'Pick Up (Take extra shift)', value: 'pick_up' }
])

// --- Dashboard Stats ---
const dashboardStats = ref({
  totalEmployees: 0,
  activeEmployees: 0,
  todayShifts: 0,
  onLeave: 0,
  pendingSwaps: 0,
  nightDiffEmployees: 0,
  unfilledShifts: 0
})

const departments = ref<any[]>([])

// --- Shift Definitions State ---
const shiftDefinitions = ref<any[]>([])
const shiftDefsLoading = ref(false)
const shiftDefFilters = ref({ search: '', type: null as string | null })
const editShiftDialogVisible = ref(false)
const deleteShiftDialogVisible = ref(false)
const selectedShiftForDelete = ref<any>(null)
const editShiftSaving = ref(false)
const deletingShift = ref(false)
const editShiftErrors = ref<Record<string, string[]>>({})
const editShiftForm = ref({
  id: null as number | null,
  name: '',
  code: '',
  shift_type: 'fixed' as string,
  start_time: '09:00',
  end_time: '18:00',
  break_start: '' as string,
  break_end: '' as string,
  total_hours: '8',
  week_days: [] as string[],
  grace_period_minutes: 15 as number,
  has_night_diff: false,
  night_diff_rate: 1.10 as number,
  min_employees_required: 1 as number,
  color: '#3b82f6',
  description: '',
  is_active: true
})

const weekDayOptions = [
  { label: 'M', full: 'Monday', value: 'monday' },
  { label: 'T', full: 'Tuesday', value: 'tuesday' },
  { label: 'W', full: 'Wednesday', value: 'wednesday' },
  { label: 'T', full: 'Thursday', value: 'thursday' },
  { label: 'F', full: 'Friday', value: 'friday' },
  { label: 'S', full: 'Saturday', value: 'saturday' },
  { label: 'S', full: 'Sunday', value: 'sunday' }
]

// --- Computed: Filtered Shift Definitions ---
const filteredShiftDefinitions = computed(() => {
  let filtered = shiftDefinitions.value
  if (shiftDefFilters.value.search) {
    const s = shiftDefFilters.value.search.toLowerCase()
    filtered = filtered.filter((sh: any) =>
      sh.name?.toLowerCase().includes(s) || sh.code?.toLowerCase().includes(s)
    )
  }
  if (shiftDefFilters.value.type) {
    filtered = filtered.filter((sh: any) => sh.shift_type === shiftDefFilters.value.type)
  }
  return filtered
})

// --- Watchers ---
watch(activeTab, (newTab) => {
  if (newTab === 'assignments' && assignments.value.length === 0) {
    fetchAssignments()
  }
  if (newTab === 'shiftswap' && swapRequests.value.length === 0) {
    fetchSwapRequests()
  }
  if (newTab === 'coverage') {
    fetchCoverageData()
  }
  if (newTab === 'shifts' && shiftDefinitions.value.length === 0) {
    fetchShiftDefinitions()
  }
})

// Watch receiver selection to load their schedules
watch(() => swapForm.value.receiver_id, async (receiverId) => {
  receiverShiftOptions.value = []
  if (receiverId) {
    await fetchReceiverScheduleOptions(receiverId)
  }
})

// --- Helper Functions ---
const formatDateForAPI = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const formatDate = (date: string | null): string => {
  if (!date) return ''
  try {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  } catch { return date }
}

const formatTime = (time: string | null): string => time || '--:--'

const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getShiftSeverity = (type: string): string => {
  const map: Record<string, string> = {
    'Morning': 'info', 'Mid': 'warning', 'Evening': 'help', 'Night': 'secondary',
    'Morning Shift': 'info', 'Afternoon Shift': 'warning', 'Evening Shift': 'help', 'Night Shift': 'secondary'
  }
  return map[type] || 'info'
}

const getShiftSeverityFromColor = (color: string | null): string => {
  if (!color) return 'info'
  if (color.includes('3b82f6') || color.includes('1e40af')) return 'info'
  if (color.includes('f59e0b') || color.includes('b45309')) return 'warning'
  if (color.includes('7c3aed') || color.includes('6d28d9')) return 'help'
  if (color.includes('10b981')) return 'success'
  return 'info'
}

const getStatusSeverity = (status: string | null): string => {
  const map: Record<string, string> = {
    'scheduled': 'info', 'completed': 'success', 'cancelled': 'danger', 'absent': 'danger', 'pending': 'warning'
  }
  return map[status?.toLowerCase()] || 'secondary'
}

const getSwapStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    'pending': 'warning', 'accepted': 'success', 'rejected': 'danger', 'cancelled': 'secondary'
  }
  return map[status] || 'secondary'
}

const isToday = computed(() => new Date().toDateString() === selectedDate.value.toDateString())

// --- Data Transformers ---
const transformShiftData = (records: any[]): any[] => {
  return records.map((item: any) => {
    const employee = item.employee || {}
    const shift = item.shift || {}
    return {
      ...item,
      employee: {
        ...employee,
        full_name: employee.fname && employee.lname ? `${employee.fname} ${employee.lname}`.trim() : 'Unknown'
      },
      shift: { ...shift },
      schedule_time: `${formatTime(shift.start_time)} - ${formatTime(shift.end_time)}`,
      status_badge: { label: item.status?.toUpperCase() || 'UNKNOWN', color: getStatusSeverity(item.status) }
    }
  })
}

// --- Computed Properties ---
const filteredShifts = computed(() => {
  let filtered = shiftsData.value
  if (shiftFilters.value.search) {
    const search = shiftFilters.value.search.toLowerCase()
    filtered = filtered.filter(s =>
      s.employee.full_name.toLowerCase().includes(search) ||
      s.shift.name?.toLowerCase().includes(search)
    )
  }
  if (shiftFilters.value.department) filtered = filtered.filter(s => s.employee.department === shiftFilters.value.department)
  if (shiftFilters.value.shiftType) filtered = filtered.filter(s => s.shift.name === shiftFilters.value.shiftType)
  if (shiftFilters.value.date) {
    const filterDate = formatDateForAPI(shiftFilters.value.date)
    filtered = filtered.filter(s => s.schedule_date.startsWith(filterDate))
  }
  return filtered
})

const isMyRequest = (data: any): boolean => {
  const currentUserId = authStore.user?.id
  return data.requestor?.user_id === currentUserId || data.requestor_id === currentUserId
}

// --- Error Handling ---
const showError = (message: string) => {
  errorMessage.value = message
  errorDialogVisible.value = true
}

// --- Confirmation Dialog ---
const showConfirmation = (title: string, message: string, buttonLabel: string, buttonSeverity: string = 'danger') => {
  confirmTitle.value = title
  confirmMessage.value = message
  confirmButtonLabel.value = buttonLabel
  confirmButtonSeverity.value = buttonSeverity
  confirmDialogVisible.value = true
}

const confirmDeleteAssignment = (data: any) => {
  pendingAction.value = { type: 'assignment', data }
  showConfirmation(
    'Delete Assignment',
    `Are you sure you want to delete the assignment for ${data.employee?.full_name}?`,
    'Delete',
    'danger'
  )
}

const confirmSwapAction = (data: any, action: string) => {
  pendingAction.value = { type: 'swap', data, action }

  const actionMessages: Record<string, { title: string; message: string; button: string }> = {
    accept: {
      title: 'Accept Swap Request',
      message: `Are you sure you want to accept the shift swap request from ${data.requestor?.full_name}?`,
      button: 'Accept'
    },
    reject: {
      title: 'Reject Swap Request',
      message: `Are you sure you want to reject the shift swap request from ${data.requestor?.full_name}?`,
      button: 'Reject'
    },
    cancel: {
      title: 'Cancel Swap Request',
      message: 'Are you sure you want to cancel this shift swap request?',
      button: 'Cancel'
    }
  }

  const config = actionMessages[action]
  showConfirmation(config.title, config.message, config.button, action === 'accept' ? 'success' : 'danger')
}

const executeConfirmedAction = () => {
  confirmDialogVisible.value = false

  if (pendingAction.value?.type === 'assignment') {
    deleteAssignment(pendingAction.value.data)
  } else if (pendingAction.value?.type === 'swap') {
    executeSwapAction(pendingAction.value.action!, pendingAction.value.data)
  }
}

// --- API Calls: Shifts ---
const fetchData = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await axios.get('api/shift-schedules', {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })
    if (response.data.success) {
      const records = response.data.data.data || response.data.data || []
      shiftsData.value = transformShiftData(records)
      processDashboardStats()
      processDepartmentsData()
      extractFilterOptions()
    } else {
      error.value = 'Failed to load data'
    }
  } catch (err: any) {
    console.error(err)
    error.value = err.response?.data?.message || 'Error connecting to server'
  } finally {
    loading.value = false
  }
}

const processDashboardStats = () => {
  const today = new Date().toISOString().split('T')[0]
  const todayShifts = shiftsData.value.filter((s: any) => s.schedule_date?.startsWith(today))
  const uniqueEmployees = [...new Set(shiftsData.value.map((s: any) => s.employee.id))]
  const nightShifts = shiftsData.value.filter((s: any) => s.shift.name?.toLowerCase().includes('night'))

  dashboardStats.value = {
    totalEmployees: uniqueEmployees.length,
    activeEmployees: uniqueEmployees.length,
    todayShifts: todayShifts.length,
    onLeave: 0,
    pendingSwaps: dashboardStats.value.pendingSwaps,
    nightDiffEmployees: nightShifts.length,
    unfilledShifts: 0
  }
}

const processDepartmentsData = () => {
  const deptMap = new Map<string, any>()
  shiftsData.value.forEach((shift: any) => {
    const dept = shift.employee.department || 'Unassigned'
    if (!deptMap.has(dept)) {
      deptMap.set(dept, { id: dept, name: dept, totalEmployees: 0, scheduled: 0, scheduledEmployees: [], unfilledCount: 0, coveragePercentage: 0 })
    }
    const d = deptMap.get(dept)
    if (shift.status === 'scheduled') {
      d.scheduled++
      d.scheduledEmployees.push({ id: shift.employee.id, name: shift.employee.full_name, shiftType: shift.shift.name })
    }
  })
  deptMap.forEach((d: any) => d.coveragePercentage = d.totalEmployees > 0 ? (d.scheduled / d.totalEmployees) * 100 : 0)
  departments.value = Array.from(deptMap.values())
}

const extractFilterOptions = () => {
  const depts = [...new Set(shiftsData.value.map((s: any) => s.employee.department).filter(Boolean))]
  departmentOptions.value = depts.map((d: string) => ({ label: d, value: d }))
  const shifts = [...new Set(shiftsData.value.map((s: any) => s.shift.name).filter(Boolean))]
  shiftTypeOptions.value = shifts.map((s: string) => ({ label: s, value: s }))
  shiftOptions.value = shifts.map((s: string, index: number) => ({ label: s, value: index + 1 }))
}

const fetchCoverageData = async () => {
  // Filter shifts by selected date
  const dateStr = formatDateForAPI(selectedDate.value)
  const filtered = shiftsData.value.filter((s: any) => s.schedule_date?.startsWith(dateStr))

  // Reprocess departments with filtered data
  const deptMap = new Map<string, any>()
  filtered.forEach((shift: any) => {
    const dept = shift.employee.department || 'Unassigned'
    if (!deptMap.has(dept)) {
      deptMap.set(dept, { id: dept, name: dept, totalEmployees: 0, scheduled: 0, scheduledEmployees: [], unfilledCount: 0, coveragePercentage: 0 })
    }
    const d = deptMap.get(dept)
    if (shift.status === 'scheduled') {
      d.scheduled++
      d.scheduledEmployees.push({ id: shift.employee.id, name: shift.employee.full_name, shiftType: shift.shift.name })
    }
  })
  deptMap.forEach((d: any) => d.coveragePercentage = d.totalEmployees > 0 ? (d.scheduled / d.totalEmployees) * 100 : 0)
  departments.value = Array.from(deptMap.values())
}

const filterShifts = () => {
  // Filtering is handled by computed property
}

// --- API Calls: Assignments ---
const fetchAssignments = async () => {
  assignmentsLoading.value = true
  try {
    const params: any = {}
    if (assignmentFilters.value.type) params.assignment_type = assignmentFilters.value.type
    if (assignmentFilters.value.search) params.fname = assignmentFilters.value.search

    const response = await axios.get('api/shift-assignments', {
      headers: { 'Authorization': `Bearer ${authStore.token}` }, params
    })
    if (response.data.success) {
      assignments.value = response.data.data.data || response.data.data
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to load assignments')
  } finally {
    assignmentsLoading.value = false
  }
}

const createAssignment = async () => {
  if (!assignmentForm.value.employee_id || !assignmentForm.value.shift_id || !assignmentForm.value.start_date || !assignmentForm.value.assignment_type) {
    showError('Please fill in all required fields')
    return
  }

  formLoading.value = true
  try {
    const payload = {
      employee_id: assignmentForm.value.employee_id,
      shift_id: assignmentForm.value.shift_id,
      start_date: formatDateForAPI(assignmentForm.value.start_date),
      end_date: assignmentForm.value.end_date ? formatDateForAPI(assignmentForm.value.end_date) : null,
      assignment_type: assignmentForm.value.assignment_type,
      notes: assignmentForm.value.notes
    }

    const response = await axios.post('api/shift-assignments', payload, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })

    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'Assignment created successfully', life: 3000 })
      assignmentDialogVisible.value = false
      resetAssignmentForm()
      fetchAssignments()
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to create assignment')
  } finally {
    formLoading.value = false
  }
}

const deleteAssignment = async (assignment: any) => {
  actionLoading.value = true
  try {
    await axios.delete(`api/shift-assignments/${assignment.id}`, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Assignment removed successfully', life: 3000 })
    fetchAssignments()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to delete assignment')
  } finally {
    actionLoading.value = false
  }
}

const resetAssignmentForm = () => {
  assignmentForm.value = {
    employee_id: null,
    shift_id: null,
    start_date: null,
    end_date: null,
    assignment_type: null,
    notes: ''
  }
}



const openAssignmentDialog = async () => {
  assignmentDialog.value = true
  resetAssignmentForm()
  await fetchEmployeeOptions()
  await fetchShiftOptions()
  assignmentDialogVisible.value = true
  assignmentDialog.value = false
}

// --- API Calls: Swap Requests ---
const fetchSwapRequests = async () => {
  swapLoading.value = true
  try {
    const params: any = {}
    if (swapFilters.value.status) params.status = swapFilters.value.status

    const response = await axios.get('api/shift-swap-requests', {
      headers: { 'Authorization': `Bearer ${authStore.token}` }, params
    })
    if (response.data.success) {
      swapRequests.value = response.data.data.data || response.data.data
      dashboardStats.value.pendingSwaps = swapRequests.value.filter((r: any) => r.status === 'pending').length
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to load swap requests')
  } finally {
    swapLoading.value = false
  }
}

const executeSwapAction = async (action: string, data: any) => {
  actionLoading.value = true
  const headers = { 'Authorization': `Bearer ${authStore.token}` }

  try {
    if (action === 'accept') {
      await axios.put(`api/shift-swap-requests/${data.id}/accept`, {}, { headers })
      toast.add({ severity: 'success', summary: 'Accepted', detail: 'Swap request accepted successfully', life: 3000 })
    } else if (action === 'reject') {
      await axios.put(`api/shift-swap-requests/${data.id}/reject`, {}, { headers })
      toast.add({ severity: 'success', summary: 'Rejected', detail: 'Swap request rejected', life: 3000 })
    } else if (action === 'cancel') {
      await axios.put(`api/shift-swap-requests/${data.id}/cancel`, {}, { headers })
      toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Swap request cancelled', life: 3000 })
    }
    fetchSwapRequests()
  } catch (err: any) {
    showError(err.response?.data?.message || `Failed to ${action} swap request`)
  } finally {
    actionLoading.value = false
  }
}

const createSwapRequest = async () => {
  if (!swapForm.value.receiver_id || !swapForm.value.requestor_schedule_id || !swapForm.value.receiver_schedule_id || !swapForm.value.swap_type) {
    showError('Please fill in all required fields')
    return
  }

  formLoading.value = true
  try {
    const payload = {
      receiver_id: swapForm.value.receiver_id,
      requestor_schedule_id: swapForm.value.requestor_schedule_id,
      receiver_schedule_id: swapForm.value.receiver_schedule_id,
      swap_type: swapForm.value.swap_type,
      reason: swapForm.value.reason
    }

    const response = await axios.post('api/shift-swap-requests', payload, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })

    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Success', detail: 'Swap request submitted successfully', life: 3000 })
      swapDialogVisible.value = false
      resetSwapForm()
      fetchSwapRequests()
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to create swap request')
  } finally {
    formLoading.value = false
  }
}

const resetSwapForm = () => {
  swapForm.value = {
    receiver_id: null,
    requestor_schedule_id: null,
    receiver_schedule_id: null,
    swap_type: null,
    reason: ''
  }
  myShiftOptions.value = []
  receiverShiftOptions.value = []
}

const openSwapDialog = async () => {
  resetSwapForm()
  await fetchEmployeeOptions()
  await fetchMyScheduleOptions()
  swapDialogVisible.value = true
}

// --- Shift Definitions API Calls ---
const fetchShiftDefinitions = async () => {
  shiftDefsLoading.value = true
  try {
    const response = await axios.get('api/shifts', {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })
    if (response.data.success) {
      shiftDefinitions.value = response.data.data.data || response.data.data || []
      shiftOptions.value = shiftDefinitions.value.map((s: any) => ({
        label: s.name,
        value: s.id
      }))
    }
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to load shifts')
  } finally {
    shiftDefsLoading.value = false
  }
}

const openEditShiftDialog = (shift: any) => {
  editShiftErrors.value = {}
  editShiftForm.value = {
    id: shift.id,
    name: shift.name || '',
    code: shift.code || '',
    shift_type: shift.shift_type || 'fixed',
    start_time: shift.start_time ? String(shift.start_time).substring(0, 5) : '09:00',
    end_time: shift.end_time ? String(shift.end_time).substring(0, 5) : '18:00',
    break_start: shift.break_start ? String(shift.break_start).substring(0, 5) : '',
    break_end: shift.break_end ? String(shift.break_end).substring(0, 5) : '',
    total_hours: String(shift.total_hours || '8'),
    week_days: shift.week_days || [],
    grace_period_minutes: shift.grace_period_minutes ?? 15,
    has_night_diff: shift.has_night_diff || false,
    night_diff_rate: shift.night_diff_rate || 1.10,
    min_employees_required: shift.min_employees_required || 1,
    color: shift.color || '#3b82f6',
    description: shift.description || '',
    is_active: shift.is_active !== undefined ? shift.is_active : true
  }
  editShiftDialogVisible.value = true
}

const updateShift = async () => {
  if (!editShiftForm.value.id) return
  editShiftErrors.value = {}
  editShiftSaving.value = true
  try {
    const payload: Record<string, any> = {
      name: editShiftForm.value.name,
      code: editShiftForm.value.code,
      shift_type: editShiftForm.value.shift_type,
      start_time: editShiftForm.value.start_time,
      end_time: editShiftForm.value.end_time,
      total_hours: Number(editShiftForm.value.total_hours),
      week_days: editShiftForm.value.week_days.length > 0 ? editShiftForm.value.week_days : null,
      grace_period_minutes: editShiftForm.value.grace_period_minutes,
      has_night_diff: editShiftForm.value.has_night_diff,
      night_diff_rate: editShiftForm.value.has_night_diff ? editShiftForm.value.night_diff_rate : 1.10,
      min_employees_required: editShiftForm.value.min_employees_required,
      color: editShiftForm.value.color,
      description: editShiftForm.value.description || null,
      is_active: editShiftForm.value.is_active
    }
    if (editShiftForm.value.break_start) payload.break_start = editShiftForm.value.break_start
    if (editShiftForm.value.break_end) payload.break_end = editShiftForm.value.break_end

    const response = await axios.put(`api/shifts/${editShiftForm.value.id}`, payload, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })
    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Shift updated successfully', life: 3000 })
      editShiftDialogVisible.value = false
      fetchShiftDefinitions()
    }
  } catch (err: any) {
    if (err.response?.status === 422) {
      editShiftErrors.value = err.response.data.errors || {}
    } else {
      showError(err.response?.data?.message || 'Failed to update shift')
    }
  } finally {
    editShiftSaving.value = false
  }
}

const confirmDeleteShift = (shift: any) => {
  selectedShiftForDelete.value = shift
  deleteShiftDialogVisible.value = true
}

const deleteShift = async () => {
  if (!selectedShiftForDelete.value) return
  deletingShift.value = true
  try {
    await axios.delete(`api/shifts/${selectedShiftForDelete.value.id}`, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Shift deleted successfully', life: 3000 })
    deleteShiftDialogVisible.value = false
    selectedShiftForDelete.value = null
    fetchShiftDefinitions()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Failed to delete shift')
  } finally {
    deletingShift.value = false
  }
}

const toggleEditShiftDay = (day: string) => {
  const index = editShiftForm.value.week_days.indexOf(day)
  if (index === -1) {
    editShiftForm.value.week_days.push(day)
  } else {
    editShiftForm.value.week_days.splice(index, 1)
  }
}

// --- Helper API Calls ---
const fetchMyScheduleOptions = async () => {
  try {
    const response = await axios.get('api/shift-schedules', {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      params: { status: 'scheduled' }
    })
    if (response.data.success) {
      const schedules = response.data.data?.data || response.data.data || []
      // Filter to current user's employee schedules
      const userId = authStore.user?.id
      const mySchedules = schedules.filter((s: any) => s.employee?.user_id === userId)
      myShiftOptions.value = mySchedules.map((s: any) => ({
        label: `${s.shift?.name || 'Shift'} — ${formatDate(s.schedule_date)}`,
        value: s.id
      }))
    }
  } catch (err) {
    console.error('Failed to fetch my schedules', err)
  }
}

const fetchReceiverScheduleOptions = async (receiverId: number) => {
  try {
    const response = await axios.get('api/shift-schedules', {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      params: { employee_id: receiverId, status: 'scheduled' }
    })
    if (response.data.success) {
      const schedules = response.data.data?.data || response.data.data || []
      receiverShiftOptions.value = schedules.map((s: any) => ({
        label: `${s.shift?.name || 'Shift'} — ${formatDate(s.schedule_date)}`,
        value: s.id
      }))
    }
  } catch (err) {
    console.error('Failed to fetch receiver schedules', err)
  }
}

const fetchEmployeeOptions = async () => {
  try {
    const response = await axios.get('api/employees', {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      params: { store_id: authStore.user?.store_id }
    })
    if (response.data.success) {
      const employees = response.data.data.data || response.data.data || []
      employeeOptions.value = employees.map((e: any) => ({
        label: `${e.fname} ${e.lname}`,
        value: e.id
      }))
    }
  } catch (err) {
    console.error('Failed to fetch employees', err)
  }
}

const fetchShiftOptions = async () => {
  try {
    const response = await axios.get('api/shifts', {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      params: { store_id: authStore.user?.store_id }
    })
    if (response.data.success) {
      const shifts = response.data.data.data || response.data.data || []
      shiftOptions.value = shifts.map((s: any) => ({
        label: s.name,
        value: s.id
      }))
    }
  } catch (err) {
    console.error('Failed to fetch shifts', err)
  }
}

// --- Navigation ---
const goToCreateShift = () => router.push({ name: 'hr.shifts.create' })
const previousDay = () => {
  selectedDate.value = new Date(selectedDate.value.setDate(selectedDate.value.getDate() - 1))
  fetchCoverageData()
}
const nextDay = () => {
  selectedDate.value = new Date(selectedDate.value.setDate(selectedDate.value.getDate() + 1))
  fetchCoverageData()
}
const viewShiftDetails = (shift: any) => openEditShiftDialog(shift)
const exportReport = () => {
  toast.add({ severity: 'info', summary: 'Export', detail: 'Generating report...', life: 3000 })
  // Implement export logic
}

// --- Lifecycle ---
onMounted(() => {
  fetchData()
  fetchAssignments()
  fetchSwapRequests()
  fetchShiftDefinitions()
})
</script>