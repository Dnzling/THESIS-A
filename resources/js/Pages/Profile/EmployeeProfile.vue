<template>
  <div class="min-h-screen bg-slate-50/60 py-6">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Employee Profile</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your personal information and review your employment details.</p>
      </div>

      <div v-if="loading" class="flex min-h-72 items-center justify-center rounded-xl border border-slate-200 bg-white">
        <ProgressSpinner />
      </div>

      <div v-else class="grid items-start gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="space-y-4">
          <section class="rounded-xl border border-slate-200 bg-white p-5 text-center shadow-sm">
            <div class="relative mx-auto h-24 w-24">
              <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="`${fullName} photo`" class="h-24 w-24 rounded-full border border-slate-200 object-cover" />
              <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-orange-50 text-2xl font-semibold text-orange-600">{{ initials }}</div>
              <label class="absolute bottom-0 right-0 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-orange-500 text-white" title="Upload profile photo">
                <i class="pi pi-camera text-xs" /><input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" :disabled="uploadingPhoto" @change="uploadProfilePhoto" />
              </label>
            </div>
            <h2 class="mt-4 text-base font-semibold text-slate-900">{{ fullName }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ employeeSummary }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ employee?.department || displayBranch }}</p>
            <Tag v-if="employee?.status" class="mt-3" :value="statusLabel" :severity="statusSeverity" rounded />
          </section>

          <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between"><span class="text-slate-500">Upcoming shifts</span><span class="font-semibold text-slate-800">{{ shifts.length }}</span></div>
              <div class="flex items-center justify-between"><span class="text-slate-500">Leave requests</span><span class="font-semibold text-slate-800">{{ leaveRecords.length }}</span></div>
              <div v-if="!isSimplePlan" class="flex items-center justify-between"><span class="text-slate-500">Payslips</span><span class="font-semibold text-slate-800">{{ payslips.length }}</span></div>
            </div>
          </section>

          <section v-if="!isSimplePlan" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Quick Actions</p>
            <div class="flex flex-col gap-2">
              <Button label="Request Leave" icon="pi pi-calendar" severity="warn" outlined size="small" @click="showLeaveDialog = true" />
              <Button label="Request Shift Swap" icon="pi pi-share-alt" severity="secondary" outlined size="small" @click="openShiftSwapDialog" />
              <Button label="Request Overtime" icon="pi pi-clock" severity="secondary" outlined size="small" @click="openOvertimeDialog" />
            </div>
          </section>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <Tabs v-model:value="activeProfileTab">
            <TabList class="mb-5 flex flex-wrap border-b border-slate-200">
              <Tab value="account">Account Settings</Tab>
              <Tab value="employment">Employment</Tab>
              <Tab v-if="!isSimplePlan" value="documents">Documents</Tab>
              <Tab v-if="!isSimplePlan" value="activity">Requests &amp; Activity</Tab>
            </TabList>
            <TabPanels>
              <TabPanel value="account">
                <form class="space-y-6" @submit.prevent="saveProfile">
                  <section>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                      <div><h2 class="font-semibold text-slate-900">Personal Information</h2><p class="text-xs text-slate-500">Changes here update your employee profile.</p></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">First Name</label><InputText v-model="form.fname" class="w-full small-input" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Last Name</label><InputText v-model="form.lname" class="w-full small-input" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Phone Number</label><InputMask v-model="form.phone" mask="+63 999 9999 999" class="w-full small-input" placeholder="+63" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Email Address</label><div class="flex gap-2"><InputText :model-value="user?.email || ''" disabled class="w-full small-input" /><Button icon="pi pi-pencil" aria-label="Change email" v-tooltip.top="'Change email'" severity="secondary" outlined size="small" @click="showEmailDialog = true" /></div></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Province</label><Select v-model="locationSelection.provinceId" :options="provinceOptions" optionLabel="label" optionValue="value" filter placeholder="Select Province" class="w-full small-input" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">City / Municipality</label><Select v-model="locationSelection.cityId" :options="cityOptions" optionLabel="label" optionValue="value" filter placeholder="Select City" class="w-full small-input" :disabled="!locationSelection.provinceId" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Barangay</label><Select v-model="locationSelection.barangayCode" :options="barangayOptions" optionLabel="label" optionValue="value" filter placeholder="Select Barangay" class="w-full small-input" :disabled="!locationSelection.cityId" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Street / House / Lot</label><InputText v-model="form.address" class="w-full small-input" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Birth Date</label><DatePicker v-model="form.birthday" :maxDate="new Date()" dateFormat="yy-mm-dd" class="w-full small-input" showIcon /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Gender</label><Select v-model="form.gender" :options="[{label:'Male',value:'male'},{label:'Female',value:'female'},{label:'Other',value:'other'}]" optionLabel="label" optionValue="value" placeholder="Select gender" class="w-full small-input" /></div>
                    </div>
                  </section>

                  <section class="border-t border-slate-100 pt-5">
                    <h2 class="font-semibold text-slate-900">Emergency Contact</h2>
                    <div class="mt-3 grid gap-4 sm:grid-cols-3">
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Name</label><InputText v-model="form.emergency_contact_name" class="w-full small-input" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Relationship</label><InputText v-model="form.emergency_contact_relationship" class="w-full small-input" /></div>
                      <div class="space-y-1.5"><label class="text-xs font-medium text-slate-600">Phone</label><InputMask v-model="form.emergency_contact_phone" mask="+63 999 9999 999" class="w-full small-input" placeholder="+63" /></div>
                    </div>
                  </section>

                  <div class="flex justify-end border-t border-slate-100 pt-4"><Button type="submit" label="Save Changes" icon="pi pi-check" severity="warn" :loading="savingProfile" /></div>
                </form>

                <section class="mt-6 border-t border-slate-100 pt-5">
                  <div class="mb-3"><h2 class="font-semibold text-slate-900">Security</h2><p class="text-xs text-slate-500">Manage your password and email verification.</p></div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3"><div><p class="text-sm font-medium text-slate-800">Password</p><p class="text-xs text-slate-500">Keep your account secure.</p></div><Button label="Change" icon="pi pi-lock" severity="secondary" outlined size="small" @click="showPasswordDialog = true" /></div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3"><div><p class="text-sm font-medium text-slate-800">Email Status</p><p class="text-xs text-slate-500">Verification is required after an email change.</p></div><Tag :value="emailStatusLabel" :severity="emailStatusSeverity" rounded /></div>
                  </div>
                </section>
              </TabPanel>

              <TabPanel value="employment">
                <section class="space-y-5">
                  <div><h2 class="font-semibold text-slate-900">Employment Details</h2><p class="text-xs text-slate-500">Contact your manager or HR to request changes to these records.</p></div>
                  <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <InfoRow label="Employee Number" :value="employee?.employee_number || '-'" />
                    <InfoRow label="Department" :value="employee?.department || '-'" />
                    <InfoRow label="Role" :value="employeeRoleLabel" />
                    <InfoRow label="Employment Type" :value="formatStatus(employee?.employment_type || '-')" />
                    <InfoRow label="Hire Date" :value="formatDateShort(employee?.hire_date) || '-'" />
                    <InfoRow label="Branch" :value="displayBranch" />
                    <InfoRow v-if="!isSimplePlan" label="Monthly Salary" :value="formatCurrency(employee?.salary || 0)" />
                    <InfoRow v-if="!isSimplePlan" label="Bank Account" :value="employee?.bank_account || '-'" />
                    <InfoRow v-if="!isSimplePlan" label="TIN" :value="employee?.tax_id || '-'" />
                  </div>
                </section>
              </TabPanel>

              <TabPanel v-if="!isSimplePlan" value="documents">
                <section class="space-y-4">
                  <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-semibold text-slate-900">Payslips</h2><p class="text-xs text-slate-500">Paid payroll records and downloadable documents.</p></div><div v-if="paidPayslipTotal" class="text-sm font-semibold text-slate-700">Total Paid: {{ formatCurrency(paidPayslipTotal) }}</div></div>
                  <div v-if="loadingPayslips" class="space-y-3"><Skeleton v-for="row in 3" :key="row" height="4rem" /></div>
                  <div v-else-if="payslips.length" class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                    <div v-for="payslip in payslips" :key="payslip.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                      <div><p class="font-medium text-slate-900">{{ payslip.pay_period?.name || payslip.pay_period_name || payslip.pay_period_label || 'Pay Period' }}</p><p class="mt-1 text-xs text-slate-500">Net Pay: {{ formatCurrency(payslip.net_salary || payslip.net_pay || 0) }}<span v-if="payslip.payment_date"> · Paid {{ formatDateShort(payslip.payment_date) }}</span></p></div>
                      <div class="flex flex-wrap items-center gap-2"><Tag :value="formatStatus(payslip.status)" :severity="payslip.status === 'paid' ? 'success' : payslip.status === 'approved' ? 'info' : 'warn'" rounded /><Button label="View" icon="pi pi-eye" text size="small" @click="openPayslipDetails(payslip)" /><Button label="Download" icon="pi pi-download" severity="secondary" text size="small" @click="downloadPayslipPdf(payslip)" /><Button label="Print" icon="pi pi-print" severity="secondary" text size="small" @click="printPayslipPdf(payslip)" /></div>
                    </div>
                  </div>
                  <div v-else class="rounded-lg border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500">No payslips available yet.</div>
                </section>
              </TabPanel>

              <TabPanel v-if="!isSimplePlan" value="activity">
                <div class="grid gap-4 xl:grid-cols-2">
                  <section class="rounded-lg border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900">Upcoming Shifts</h2><p class="text-xs text-slate-500">Next 14 days.</p>
                    <div v-if="loadingShifts" class="mt-3 space-y-2"><Skeleton v-for="row in 3" :key="row" height="3rem" /></div>
                    <div v-else class="mt-3 space-y-2"><div v-for="shift in shifts" :key="shift.id" class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><div><p class="text-sm font-medium text-slate-800">{{ formatDateShort(shift.schedule_date) }}</p><p class="text-xs text-slate-500">{{ shift.shift?.name || 'Shift' }}<span v-if="shift.shift?.start_time && shift.shift?.end_time"> · {{ formatTimeRange(shift.shift.start_time, shift.shift.end_time) }}</span></p></div><Tag :value="formatStatus(shift.status)" severity="info" rounded /></div><p v-if="!shifts.length" class="py-4 text-sm text-slate-500">No upcoming shifts found.</p></div>
                  </section>
                  <section class="rounded-lg border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900">Attendance Summary</h2><p class="text-xs text-slate-500">Current month overview.</p>
                    <div v-if="loadingAttendance" class="mt-3 grid grid-cols-2 gap-2"><Skeleton v-for="row in 6" :key="row" height="3rem" /></div>
                    <div v-else class="mt-3"><div class="grid grid-cols-2 gap-2 sm:grid-cols-3"><StatChip label="Present" :value="attendanceSummary.present" /><StatChip label="Late" :value="attendanceSummary.late" /><StatChip label="Absent" :value="attendanceSummary.absent" /><StatChip label="On Leave" :value="attendanceSummary.on_leave" /><StatChip label="Half Day" :value="attendanceSummary.half_day" /><StatChip label="Total" :value="attendanceSummary.total" /></div><div class="mt-4 space-y-2"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Recent Records</p><div v-for="record in attendanceRecords" :key="record.id" class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><div><p class="text-sm font-medium text-slate-800">{{ formatDateShort(record.attendance_date) }}</p><p class="text-xs text-slate-500">{{ record.shift?.name || 'Shift' }}<span v-if="record.clock_in"> · In {{ formatTime(record.clock_in) }}</span><span v-if="record.clock_out"> · Out {{ formatTime(record.clock_out) }}</span></p></div><Tag :value="formatStatus(record.status)" severity="secondary" rounded /></div><p v-if="!attendanceRecords.length" class="text-sm text-slate-500">No attendance records yet.</p></div></div>
                  </section>
                  <section class="rounded-lg border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900">Leave Requests</h2>
                    <div v-if="loadingLeaves" class="mt-3 space-y-2"><Skeleton v-for="row in 3" :key="row" height="3rem" /></div>
                    <div v-else class="mt-3 space-y-2"><div v-for="leave in leaveRecords" :key="leave.id" class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><div><p class="text-sm font-medium text-slate-800">{{ leave.leave_type_label || formatLabel(leave.leave_type) }}</p><p class="text-xs text-slate-500">{{ leave.start_date_formatted || formatDateShort(leave.start_date) }} - {{ leave.end_date_formatted || formatDateShort(leave.end_date) }}<span v-if="leave.total_days"> · {{ leave.total_days }} day(s)</span></p></div><Tag :value="formatStatus(leave.status)" :severity="leave.status === 'approved' ? 'success' : leave.status === 'rejected' ? 'danger' : 'warn'" rounded /></div><p v-if="!leaveRecords.length" class="py-3 text-sm text-slate-500">No leave requests found.</p></div>
                  </section>
                  <section class="rounded-lg border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900">Shift Swap Requests</h2>
                    <div v-if="loadingSwaps" class="mt-3 space-y-2"><Skeleton v-for="row in 3" :key="row" height="3rem" /></div>
                    <div v-else class="mt-3 space-y-2"><div v-for="swap in swapRecords" :key="swap.id" class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><div><p class="text-sm font-medium text-slate-800">{{ formatSwapLabel(swap) }}</p><p class="text-xs text-slate-500">{{ formatDateShort(swap.requestor_schedule?.schedule_date || swap.requestorSchedule?.schedule_date) }}<span v-if="swap.swap_type"> · {{ formatLabel(swap.swap_type) }}</span></p></div><Tag :value="formatStatus(swap.status)" :severity="swap.status === 'approved' || swap.status === 'accepted' ? 'success' : swap.status === 'rejected' ? 'danger' : 'warn'" rounded /></div><p v-if="!swapRecords.length" class="py-3 text-sm text-slate-500">No swap requests found.</p></div>
                  </section>
                  <section class="rounded-lg border border-slate-200 p-4 xl:col-span-2">
                    <h2 class="font-semibold text-slate-900">Overtime Requests</h2>
                    <div v-if="loadingOvertime" class="mt-3 space-y-2"><Skeleton v-for="row in 3" :key="row" height="3rem" /></div>
                    <div v-else class="mt-3 space-y-2"><div v-for="ot in overtimeRecords" :key="ot.id" class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><div><p class="text-sm font-medium text-slate-800">{{ formatDateShort(ot.ot_start) }} · {{ formatTime(ot.ot_start) }} - {{ formatTime(ot.ot_end) }}</p><p class="text-xs text-slate-500">{{ formatStatus(ot.ot_type) }}<span v-if="ot.rate_multiplier"> · x{{ ot.rate_multiplier }}</span></p></div><Tag :value="formatStatus(ot.status)" :severity="ot.status === 'approved' ? 'success' : ot.status === 'rejected' ? 'danger' : 'warn'" rounded /></div><p v-if="!overtimeRecords.length" class="py-3 text-sm text-slate-500">No overtime requests yet.</p></div>
                  </section>
                </div>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </main>
      </div>
    </div>
  </div>
  <Dialog v-model:visible="showEmailDialog" header="Change Email" :style="{ width: '420px' }" modal>
    <div class="space-y-4">
      <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
        A verification code will be sent to the new email address.
      </div>
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">New Email</label>
        <InputText v-model="emailForm.email" class="w-full small-input" placeholder="name@example.com" />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" text @click="showEmailDialog = false" />
      <Button label="Send OTP" icon="pi pi-envelope" severity="info" class="small-pill"
        :loading="savingEmail" @click="submitEmailChange" />
    </template>
  </Dialog>



  <Dialog v-model:visible="showPasswordDialog" header="Change Password" :style="{ width: '420px' }" modal>
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Current Password</label>
        <Password v-model="passwordForm.current_password" toggleMask class="w-full small-input" inputClass="w-full" />
      </div>
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">New Password</label>
        <Password v-model="passwordForm.password" toggleMask class="w-full small-input" inputClass="w-full" />
        <ul class="space-y-1 rounded-lg bg-slate-50 px-3 py-2 text-xs">
          <li v-for="rule in passwordValidationRules" :key="rule.label" class="flex items-center gap-2" :class="rule.valid ? 'text-emerald-700' : 'text-slate-500'">
            <i :class="rule.valid ? 'pi pi-check-circle' : 'pi pi-circle'" />
            <span>{{ rule.label }}</span>
          </li>
        </ul>
      </div>
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Confirm Password</label>
        <Password v-model="passwordForm.password_confirmation" toggleMask class="w-full small-input" inputClass="w-full" />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" text @click="showPasswordDialog = false" />
      <Button label="Update Password" icon="pi pi-check" severity="warning" class="small-pill"
        :loading="savingPassword" @click="submitPasswordChange" />
    </template>
  </Dialog>

  <Dialog v-model:visible="showLeaveDialog" header="Request Leave" :style="{ width: '520px' }" modal>
    <div class="space-y-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Leave Type</label>
          <Select v-model="leaveForm.leave_type" :options="leaveTypeOptions" optionLabel="label" optionValue="value"
            placeholder="Select type" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Paid Leave</label>
          <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            <Checkbox v-model="leaveForm.is_paid" binary />
            <span>Deduct from balance</span>
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Start Date</label>
          <DatePicker v-model="leaveForm.start_date" :minDate="new Date()" dateFormat="yy-mm-dd" class="w-full small-input" showIcon />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">End Date</label>
          <DatePicker v-model="leaveForm.end_date" :minDate="leaveForm.start_date || new Date()" dateFormat="yy-mm-dd" class="w-full small-input" showIcon />
        </div>
      </div>
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Reason</label>
        <Textarea v-model="leaveForm.reason" rows="3" class="w-full small-input" />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" text @click="showLeaveDialog = false" />
      <Button label="Submit Request" icon="pi pi-send" severity="info" class="small-pill"
        :loading="savingLeave" @click="submitLeaveRequest" />
    </template>
  </Dialog>

  <Dialog v-model:visible="showShiftSwapDialog" header="Request Shift Swap" :style="{ width: '560px' }" modal>
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Your Shift</label>
        <Select v-model="swapForm.requestor_schedule_id" :options="myScheduleOptions" optionLabel="label"
          optionValue="value" placeholder="Select your shift" class="w-full small-input" @change="loadSwapSuggestions" />
      </div>
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Swap With</label>
        <Select v-model="swapForm.receiver_schedule_id" :options="candidateOptions" optionLabel="label"
          optionValue="value" placeholder="Select a teammate shift" class="w-full small-input"
          :disabled="!swapForm.requestor_schedule_id" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Swap Type</label>
          <Select v-model="swapForm.swap_type" :options="swapTypeOptions" optionLabel="label" optionValue="value"
            class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Reason (optional)</label>
          <InputText v-model="swapForm.reason" class="w-full small-input" />
        </div>
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" text @click="showShiftSwapDialog = false" />
      <Button label="Submit Swap" icon="pi pi-check" severity="secondary" class="small-pill"
        :loading="savingSwap" @click="submitShiftSwap" />
    </template>
  </Dialog>

  <Dialog v-model:visible="showOvertimeDialog" header="Overtime Request" :style="{ width: '560px' }" modal>
    <div class="space-y-4">
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Attendance Record</label>
        <Select v-model="overtimeForm.attendance_id" :options="attendanceOptions" optionLabel="label"
          optionValue="value" placeholder="Select attendance" class="w-full small-input" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">OT Start</label>
          <DatePicker v-model="overtimeForm.ot_start" showTime hourFormat="12" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">OT End</label>
          <DatePicker v-model="overtimeForm.ot_end" showTime hourFormat="12" class="w-full small-input" />
        </div>
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">OT Type</label>
          <Select v-model="overtimeForm.ot_type" :options="overtimeTypeOptions" optionLabel="label" optionValue="value"
            class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Rate Multiplier</label>
          <InputNumber v-model="overtimeForm.rate_multiplier" class="w-full small-input" :min="1" :max="3" :step="0.25" />
        </div>
      </div>
      <div class="space-y-2">
        <label class="text-xs font-semibold text-slate-500">Reason</label>
        <Textarea v-model="overtimeForm.reason" rows="3" class="w-full small-input" />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" text @click="showOvertimeDialog = false" />
      <Button label="Submit OT" icon="pi pi-check" severity="warning" class="small-pill"
        :loading="savingOvertime" @click="submitOvertimeRequest" />
    </template>
  </Dialog>

  <Dialog v-model:visible="showPayslipDialog" header="Payslip Details" :style="{ width: '760px' }" modal>
    <div v-if="selectedPayslip" class="space-y-4 text-sm">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
          <p><span class="font-semibold text-slate-700">Employee:</span> {{ fullName || '-' }}</p>
          <p><span class="font-semibold text-slate-700">Employee #:</span> {{ employee?.employee_number || '-' }}</p>
          <p><span class="font-semibold text-slate-700">Pay Period:</span> {{ getPayPeriodLabel(selectedPayslip) }}</p>
          <p><span class="font-semibold text-slate-700">Status:</span> {{ String(selectedPayslip.status || '-').toUpperCase() }}</p>
          <p><span class="font-semibold text-slate-700">Payment Date:</span> {{ formatDateShort(selectedPayslip.payment_date || selectedPayslip.paid_at || null) }}</p>
          <p><span class="font-semibold text-slate-700">Payment Method:</span> {{ selectedPayslip.payment_method || '-' }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
          <p class="mb-2 font-semibold text-emerald-800">Earnings</p>
          <div class="space-y-1">
            <p class="flex justify-between"><span>Base Salary</span><span>{{ formatCurrency(selectedPayslip.base_salary) }}</span></p>
            <p class="flex justify-between"><span>Overtime</span><span>{{ formatCurrency(selectedPayslip.overtime_amount) }}</span></p>
            <p class="flex justify-between"><span>Bonuses</span><span>{{ formatCurrency(selectedPayslip.bonuses_total) }}</span></p>
            <p class="flex justify-between"><span>Allowances</span><span>{{ formatCurrency(selectedPayslip.allowances_total) }}</span></p>
            <p class="flex justify-between border-t border-emerald-300 pt-1 font-semibold">
              <span>Gross Pay</span>
              <span>{{ formatCurrency(calculateGrossPay(selectedPayslip)) }}</span>
            </p>
          </div>
        </div>

        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
          <p class="mb-2 font-semibold text-rose-800">Deductions</p>
          <div v-if="getDeductionItems(selectedPayslip).length" class="space-y-1">
            <p v-for="item in getDeductionItems(selectedPayslip)" :key="`${selectedPayslip.id}-${item.id || item.name}`" class="flex justify-between">
              <span>{{ item.name }}</span>
              <span>-{{ formatCurrency(item.amount) }}</span>
            </p>
          </div>
          <p v-else class="text-slate-500">No itemized deductions</p>
          <p class="mt-1 flex justify-between"><span>Tax</span><span>-{{ formatCurrency(selectedPayslip.tax_amount) }}</span></p>
          <p class="flex justify-between border-t border-rose-300 pt-1 font-semibold">
            <span>Total Deductions</span>
            <span>-{{ formatCurrency(Number(selectedPayslip.deductions_total || 0) + Number(selectedPayslip.tax_amount || 0)) }}</span>
          </p>
        </div>
      </div>

      <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <p class="flex items-center justify-between text-base font-semibold text-blue-900">
          <span>Net Pay</span>
          <span>{{ formatCurrency(selectedPayslip.net_salary || selectedPayslip.net_pay || 0) }}</span>
        </p>
      </div>
    </div>

    <template #footer>
      <Button label="Close" text @click="showPayslipDialog = false" />
      <Button label="Download PDF" icon="pi pi-download" severity="secondary" @click="downloadPayslipPdf(selectedPayslip)" />
      <Button label="Print" icon="pi pi-print" severity="info" @click="printPayslipPdf(selectedPayslip)" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, defineComponent, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import hrService from '../../services/hr.services'
import ecommerceService from '../../services/ecommerce.service'
import { useAuthStore } from '../../stores/auth'
import { startCase } from 'lodash'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Skeleton from 'primevue/skeleton'

const toast = useToast()
const authStore = useAuthStore()

const loading = ref(true)
const savingProfile = ref(false)
const savingEmail = ref(false)
const savingPassword = ref(false)
const savingLeave = ref(false)
const savingSwap = ref(false)
const savingOvertime = ref(false)
const loadingAttendance = ref(false)
const loadingLeaves = ref(false)
const loadingShifts = ref(false)
const loadingSwaps = ref(false)
const loadingPayslips = ref(false)
const loadingOvertime = ref(false)
const activeProfileTab = ref('account')

const showEmailDialog = ref(false)
const showPasswordDialog = ref(false)
const showLeaveDialog = ref(false)
const showShiftSwapDialog = ref(false)
const showOvertimeDialog = ref(false)
const showPayslipDialog = ref(false)
const uploadingPhoto = ref(false)

const user = ref<any | null>(null)
const employee = ref<any | null>(null)
const planKey = ref('free')
const isSimplePlan = computed(() => ['free', 'simple'].includes(planKey.value))
const uploadProfilePhoto = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  const body = new FormData()
  body.append('avatar', file)
  uploadingPhoto.value = true
  try {
    const response = await hrService.api.post('/api/profile/avatar', body)
    if (user.value) user.value.avatar_url = response.data.data.avatar_url
    if (authStore.user) authStore.user.avatar_url = response.data.data.avatar_url
    toast.add({ severity: 'success', summary: 'Profile photo updated', life: 2500 })
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'Photo upload failed', detail: err.response?.data?.message || 'Please try again.', life: 3500 })
  } finally {
    uploadingPhoto.value = false
    input.value = ''
  }
}

const form = reactive({
  fname: '',
  lname: '',
  birthday: null as Date | null,
  gender: '',
  phone: '',
  province: '',
  city: '',
  barangay: '',
  address: '',
  emergency_contact_name: '',
  emergency_contact_relationship: '',
  emergency_contact_phone: '',
})

const locationSelection = reactive({
  provinceId: '' as string | number | null,
  cityId: '' as string | number | null,
  barangayCode: '' as string | number | null,
})

const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})
const isInitializing = ref(true)

const emailForm = reactive({
  email: '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordValidationRules = computed(() => [
  { label: 'At least 8 characters', valid: passwordForm.password.length >= 8 },
  { label: 'Different from your current password', valid: Boolean(passwordForm.password) && passwordForm.password !== passwordForm.current_password },
  { label: 'Confirmation matches', valid: Boolean(passwordForm.password_confirmation) && passwordForm.password === passwordForm.password_confirmation },
])

const leaveForm = reactive({
  leave_type: '',
  start_date: null as Date | null,
  end_date: null as Date | null,
  reason: '',
  is_paid: true,
})

const swapForm = reactive({
  requestor_schedule_id: null as number | null,
  receiver_schedule_id: null as number | null,
  swap_type: 'full_swap',
  reason: '',
})

const overtimeForm = reactive({
  attendance_id: null as number | null,
  ot_start: null as Date | null,
  ot_end: null as Date | null,
  ot_type: 'regular',
  rate_multiplier: 1.25,
  reason: '',
})

const mySchedules = ref<any[]>([])
const swapCandidates = ref<any[]>([])
const shifts = ref<any[]>([])
const attendanceRecords = ref<any[]>([])
const attendanceSummary = ref<any>({
  present: 0,
  late: 0,
  absent: 0,
  on_leave: 0,
  half_day: 0,
  total: 0,
})
const leaveRecords = ref<any[]>([])
const swapRecords = ref<any[]>([])
const payslips = ref<any[]>([])
const overtimeRecords = ref<any[]>([])
const payslipSummary = ref<any>({})
const paidPayslipTotal = computed(() => payslips.value.reduce((total, row) => total + Number(row.net_salary || row.net_pay || 0), 0))
const selectedPayslip = ref<any | null>(null)

const initials = computed(() => {
  const first = user.value?.fname?.[0] || ''
  const last = user.value?.lname?.[0] || ''
  return `${first}${last}`.toUpperCase()
})

const fullName = computed(() => `${user.value?.fname || ''} ${user.value?.lname || ''}`.trim() || '—')
const employeeRoleLabel = computed(() => {
  const role = authStore.user?.role
  if (typeof role === 'string') return startCase(role)
  return startCase(authStore.user?.role_name || role?.display_name || role?.name || 'User')
})

const employeeSummary = computed(() => employee.value
  ? `${employeeRoleLabel.value} · ${employee.value?.employee_number || '—'}`
  : '—')

const displayBirthDate = computed(() => formatDateShort(employee.value?.date_of_birth || user.value?.birthday))
const displayGender = computed(() => employee.value?.gender ? formatStatus(employee.value.gender) : '-')
const displayPhone = computed(() => employee.value?.phone || user.value?.phone_number || user.value?.phone || '-')
const displayAddress = computed(() => {
  const parts = [employee.value?.address, employee.value?.barangay, employee.value?.city, employee.value?.province].filter(Boolean)
  return parts.length ? parts.join(', ') : '-'
})
const displayEmergencyName = computed(() => employee.value?.emergency_contact_name || '-')
const displayEmergencyRelationship = computed(() => employee.value?.emergency_contact_relationship || '-')
const displayEmergencyPhone = computed(() => employee.value?.emergency_contact_phone || '-')
const displayBranch = computed(() => employee.value?.branch?.name || employee.value?.user?.branch?.name || 'Assigned Branch')

const nextShiftLabel = computed(() => {
  const first = shifts.value[0]
  if (!first) return ''
  const date = formatDateShort(first.schedule_date)
  const shiftName = first.shift?.name || 'Shift'
  return `${date} · ${shiftName}`
})

const statusLabel = computed(() => {
  const status = employee.value?.status || 'active'
  return status.toString().replace('_', ' ').toUpperCase()
})

const statusSeverity = computed(() => {
  const status = (employee.value?.status || '').toLowerCase()
  if (status === 'active') return 'success'
  if (status === 'on_leave') return 'info'
  if (status === 'suspended') return 'warning'
  if (status === 'terminated') return 'danger'
  return 'secondary'
})

const emailStatusLabel = computed(() => user.value?.email_verified_at ? 'Verified' : 'Unverified')
const emailStatusSeverity = computed(() => user.value?.email_verified_at ? 'success' : 'warning')

const provinceOptions = computed(() => provinces.value.map((p: any) => ({
  label: p.name || p.province_name || '—',
  value: p.province_id || p.id || p.code,
})))

const cityOptions = computed(() => cities.value.map((c: any) => ({
  label: c.name || c.city_name || '—',
  value: c.city_id || c.id || c.code,
})))

const barangayOptions = computed(() => barangays.value.map((b: any) => ({
  label: b.name || b.barangay_name || '—',
  value: b.code || b.id,
})))

const leaveTypeOptions = [
  { label: 'Sick Leave', value: 'sick' },
  { label: 'Vacation Leave', value: 'vacation' },
  { label: 'Personal Leave', value: 'personal' },
  { label: 'Maternity Leave', value: 'maternity' },
  { label: 'Paternity Leave', value: 'paternity' },
  { label: 'Bereavement Leave', value: 'bereavement' },
  { label: 'Other', value: 'others' },
]

const swapTypeOptions = [
  { label: 'Full Swap', value: 'full_swap' },
  { label: 'Give Away', value: 'give_away' },
  { label: 'Pick Up', value: 'pick_up' },
]

const overtimeTypeOptions = [
  { label: 'Regular', value: 'regular' },
  { label: 'Holiday', value: 'holiday' },
  { label: 'Rest Day', value: 'rest_day' },
]

const myScheduleOptions = computed(() => mySchedules.value.map((schedule: any) => ({
  label: formatScheduleLabel(schedule),
  value: schedule.id,
})))

const candidateOptions = computed(() => swapCandidates.value.map((candidate: any) => ({
  label: formatCandidateLabel(candidate),
  value: candidate.schedule?.id || candidate.id,
})))

const attendanceOptions = computed(() => attendanceRecords.value.map((record: any) => ({
  label: `${formatDateShort(record.attendance_date)} · ${record.shift?.name || 'Shift'}`,
  value: record.id,
})))

const normalize = (value: string | null | undefined) => (value || '').toString().trim().toLowerCase()

const loadProfile = async () => {
  loading.value = true
  try {
    const response = await hrService.api.get('/api/profile')
    const data = response?.data?.data || {}
    planKey.value = String(data.plan_key || 'free').toLowerCase()
    user.value = data.user || null
    employee.value = data.employee || null

    form.fname = user.value?.fname || ''
    form.lname = user.value?.lname || ''
    form.birthday = parseDateInput(employee.value?.date_of_birth || user.value?.birthday)
    form.gender = employee.value?.gender || ''
    form.phone = employee.value?.phone || user.value?.phone_number || user.value?.phone || ''
    form.address = employee.value?.address || ''
    form.province = employee.value?.province || ''
    form.city = employee.value?.city || ''
    form.barangay = employee.value?.barangay || ''
    form.emergency_contact_name = employee.value?.emergency_contact_name || ''
    form.emergency_contact_relationship = employee.value?.emergency_contact_relationship || ''
    form.emergency_contact_phone = employee.value?.emergency_contact_phone || ''

    await syncLocationSelection()
    await loadMySchedules()
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to load profile.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const syncLocationSelection = async () => {
  if (!provinces.value.length) return

  const matchedProvince = provinces.value.find((p: any) => normalize(p.name) === normalize(form.province))
  if (matchedProvince) {
    locationSelection.provinceId = matchedProvince.province_id || matchedProvince.id || matchedProvince.code
    await fetchCities(String(locationSelection.provinceId))
  }

  const matchedCity = cities.value.find((c: any) => normalize(c.name) === normalize(form.city))
  if (matchedCity) {
    locationSelection.cityId = matchedCity.city_id || matchedCity.id || matchedCity.code
    await fetchBarangays(String(locationSelection.cityId))
  }

  const matchedBarangay = barangays.value.find((b: any) => normalize(b.name) === normalize(form.barangay))
  if (matchedBarangay) {
    locationSelection.barangayCode = matchedBarangay.code || matchedBarangay.id
  }
}

const fetchProvinces = async () => {
  const response = await ecommerceService.getProvinces()
  provinces.value = response.data || []
}

const fetchCities = async (provinceId: string) => {
  if (!provinceId) {
    cities.value = []
    return
  }
  const cacheKey = String(provinceId)
  if (citiesCache.value[cacheKey]) {
    cities.value = citiesCache.value[cacheKey]
    return
  }
  const response = await ecommerceService.getCities(String(provinceId))
  citiesCache.value[cacheKey] = response.data || []
  cities.value = citiesCache.value[cacheKey]
}

const fetchBarangays = async (cityId: string) => {
  if (!cityId) {
    barangays.value = []
    return
  }
  const response = await ecommerceService.getBarangays(String(cityId))
  barangays.value = response.data || []
}

const saveProfile = async () => {
  savingProfile.value = true
  try {
    const payload = {
      fname: form.fname,
      lname: form.lname,
      birthday: formatDate(form.birthday),
      phone_number: String(form.phone || '').trim() || null,
      gender: form.gender || null,
      address: form.address,
      province: form.province,
      city: form.city,
      barangay: form.barangay,
      emergency_contact_name: form.emergency_contact_name || null,
      emergency_contact_relationship: form.emergency_contact_relationship || null,
      emergency_contact_phone: form.emergency_contact_phone || null,
    }
    const response = await hrService.api.put('/api/profile', payload)
    const data = response?.data?.data || {}
    user.value = data.user || user.value
    employee.value = data.employee || employee.value
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Profile updated.', life: 2500 })
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to update profile.',
      life: 3000,
    })
  } finally {
    savingProfile.value = false
  }
}

const submitEmailChange = async () => {
  if (!emailForm.email) return
  savingEmail.value = true
  try {
    const response = await hrService.api.put('/api/profile', { email: emailForm.email })
    const requiresVerification = response?.data?.requires_verification
    showEmailDialog.value = false
    emailForm.email = ''

    if (requiresVerification) {
      const token = localStorage.getItem('auth_token') || localStorage.getItem('access_token')
      if (token) {
        localStorage.setItem('register_token', token)
      }
      localStorage.setItem('otp_context', 'profile_email_change')
      router.visit('/verify-otp')
      return
    }

    toast.add({ severity: 'success', summary: 'Updated', detail: 'Email updated successfully.', life: 2500 })
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to update email.',
      life: 3000,
    })
  } finally {
    savingEmail.value = false
  }
}

const submitPasswordChange = async () => {
  savingPassword.value = true
  try {
    await hrService.api.post('/api/auth/change-password', passwordForm)
    showPasswordDialog.value = false
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to change password.',
      life: 3000,
    })
  } finally {
    savingPassword.value = false
  }
}

const submitLeaveRequest = async () => {
  if (!employee.value?.id) return
  savingLeave.value = true
  try {
    const payload = {
      employee_id: employee.value.id,
      leave_type: leaveForm.leave_type,
      start_date: formatDate(leaveForm.start_date),
      end_date: formatDate(leaveForm.end_date),
      reason: leaveForm.reason,
      is_paid: leaveForm.is_paid,
    }
    await hrService.api.post('/api/leaves', payload)
    showLeaveDialog.value = false
    leaveForm.leave_type = ''
    leaveForm.start_date = null
    leaveForm.end_date = null
    leaveForm.reason = ''
    leaveForm.is_paid = true
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to submit leave request.',
      life: 3000,
    })
  } finally {
    savingLeave.value = false
  }
}

const openShiftSwapDialog = async () => {
  await loadMySchedules()
  showShiftSwapDialog.value = true
}

const openOvertimeDialog = async () => {
  await loadAttendance({ perPage: 20 })
  showOvertimeDialog.value = true
}

const loadMySchedules = async () => {
  if (!employee.value?.id) return
  const fromDate = formatDate(new Date())
  const toDate = formatDate(addDays(new Date(), 30))
  const response = await hrService.api.get('/api/shift-schedules', {
    params: { employee_id: employee.value.id, status: 'scheduled', from_date: fromDate, to_date: toDate },
  })
  mySchedules.value = response?.data?.data?.data || response?.data?.data || []
}

const loadAttendance = async ({ perPage = 5 }: { perPage?: number } = {}) => {
  if (!employee.value?.id) return
  loadingAttendance.value = true
  try {
    const startDate = new Date()
    startDate.setDate(1)
    const response = await hrService.api.get('/api/attendances', {
      params: {
        employee_id: employee.value.id,
        start_date: formatDate(startDate),
        end_date: formatDate(new Date()),
        per_page: perPage,
      },
    })
    const payload = response?.data || {}
    const list = payload?.data?.data || payload?.data || []
    attendanceRecords.value = Array.isArray(list) ? list : []
    attendanceSummary.value = payload?.summary || attendanceSummary.value
  } catch (err) {
    attendanceRecords.value = []
  } finally {
    loadingAttendance.value = false
  }
}

const loadLeaves = async () => {
  if (!employee.value?.id) return
  loadingLeaves.value = true
  try {
    const response = await hrService.api.get(`/api/users/${employee.value.id}/leaves`, {
      params: { per_page: 5 },
    })
    const payload = response?.data?.data || {}
    const leaves = payload?.leaves?.data || payload?.leaves || []
    leaveRecords.value = Array.isArray(leaves) ? leaves : []
  } catch (err) {
    leaveRecords.value = []
  } finally {
    loadingLeaves.value = false
  }
}

const loadShifts = async () => {
  if (!employee.value?.id) return
  loadingShifts.value = true
  try {
    const fromDate = formatDate(new Date())
    const toDate = formatDate(addDays(new Date(), 14))
    const response = await hrService.api.get('/api/shift-schedules', {
      params: { employee_id: employee.value.id, status: 'scheduled', from_date: fromDate, to_date: toDate, per_page: 5 },
    })
    const payload = response?.data?.data
    const list = payload?.data || payload || []
    shifts.value = Array.isArray(list) ? list : []
  } catch (err) {
    shifts.value = []
  } finally {
    loadingShifts.value = false
  }
}

const loadSwaps = async () => {
  if (!employee.value?.id) return
  loadingSwaps.value = true
  try {
    const response = await hrService.api.get('/api/shift-swaps', {
      params: { employee_id: employee.value.id, per_page: 5 },
    })
    const payload = response?.data?.data
    const list = payload?.data || payload || []
    swapRecords.value = Array.isArray(list) ? list : []
  } catch (err) {
    swapRecords.value = []
  } finally {
    loadingSwaps.value = false
  }
}

const loadPayslips = async () => {
  if (!employee.value?.id) return
  loadingPayslips.value = true
  try {
    const response = await hrService.api.get(`/api/payroll/basic/employee/${employee.value.id}/payslips`)
    const payload = response?.data || {}
    const list = payload?.data?.data || payload?.data || []
    payslips.value = Array.isArray(list) ? list.filter((row: any) => row.status === 'paid') : []
    payslipSummary.value = payload?.summary || {}
  } catch (err) {
    payslips.value = []
    payslipSummary.value = {}
  } finally {
    loadingPayslips.value = false
  }
}

const getPayPeriodLabel = (payslip: any) => {
  return payslip?.pay_period?.name || payslip?.pay_period_name || payslip?.pay_period_label || 'Pay Period'
}

const calculateGrossPay = (payslip: any) => {
  const base = Number(payslip?.base_salary || 0)
  const overtime = Number(payslip?.overtime_amount || 0)
  const bonuses = Number(payslip?.bonuses_total || 0)
  const allowances = Number(payslip?.allowances_total || 0)
  return base + overtime + bonuses + allowances
}

const getDeductionItems = (payslip: any) => {
  const items = payslip?.items || payslip?.deduction_items || []
  if (!Array.isArray(items)) return []
  return items.filter((item: any) => String(item?.type || '').toLowerCase() === 'deduction')
}

const openPayslipDetails = async (payslip: any) => {
  if (!payslip?.id) return
  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip`)
    selectedPayslip.value = {
      ...payslip,
      ...(response?.data?.payslip || {}),
    }
  } catch {
    selectedPayslip.value = payslip
  }
  showPayslipDialog.value = true
}

const downloadPayslipPdf = async (payslip: any) => {
  if (!payslip?.id) return
  try {
    const response = await hrService.api.get(`/api/payroll/basic/${payslip.id}/payslip/print`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const periodLabel = String(getPayPeriodLabel(payslip)).replace(/\s+/g, '_')
    link.setAttribute('download', `payslip_${periodLabel || payslip.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Download Failed',
      detail: 'Unable to download payslip PDF.',
      life: 2500,
    })
  }
}

const printPayslipPdf = async (payslip: any) => {
  if (!payslip?.id) return
  try {
    const response = await hrService.api.get(`/api/payroll/basic/${payslip.id}/payslip/print`, { responseType: 'blob' })
    const file = new Blob([response.data], { type: 'application/pdf' })
    const fileURL = window.URL.createObjectURL(file)
    window.open(fileURL, '_blank')
    setTimeout(() => window.URL.revokeObjectURL(fileURL), 60_000)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Print Failed',
      detail: 'Unable to open payslip print preview.',
      life: 2500,
    })
  }
}

const printPayrollRundown = async () => {
  if (!employee.value?.id) return
  try {
    const response = await hrService.api.get(`/api/payroll/payslip/${employee.value.id}/rundown/print`, {
      responseType: 'blob',
    })

    const file = new Blob([response.data], { type: 'application/pdf' })
    const fileURL = window.URL.createObjectURL(file)
    window.open(fileURL, '_blank')
    setTimeout(() => window.URL.revokeObjectURL(fileURL), 60_000)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to print payroll rundown',
      life: 3000,
    })
  }
}

const loadOvertime = async () => {
  if (!employee.value?.id) return
  loadingOvertime.value = true
  try {
    const response = await hrService.api.get('/api/overtime-requests', {
      params: { employee_id: employee.value.id, per_page: 5 },
    })
    const payload = response?.data?.data
    const list = payload?.data || payload || []
    overtimeRecords.value = Array.isArray(list) ? list : []
  } catch (err) {
    overtimeRecords.value = []
  } finally {
    loadingOvertime.value = false
  }
}

const loadSwapSuggestions = async () => {
  swapCandidates.value = []
  swapForm.receiver_schedule_id = null
  if (!swapForm.requestor_schedule_id) return
  try {
    // ensure schedules are loaded before using them (select change may fire early)
    if (!mySchedules.value || !mySchedules.value.length) {
      await loadMySchedules()
    }
    // call suggestions without duplicating the /api prefix (hrService.api already uses /api)
    const requestorSchedule = (mySchedules.value || []).find((s: any) => s.id == swapForm.requestor_schedule_id)
    const params: any = { requestor_schedule_id: swapForm.requestor_schedule_id }
    if (requestorSchedule?.shift?.id) params.shift_id = requestorSchedule.shift.id
    if (employee.value?.role_id) params.role_id = employee.value.role_id
    if (employee.value?.branch_id) params.branch_id = employee.value.branch_id
    if (employee.value?.store_id) params.store_id = employee.value.store_id

    const response = await hrService.api.get('/api/shift-swaps/suggestions', { params })
    const list = response?.data?.data || []

    // determine requestor shift id and current employee branch/store
    const requestorShiftId = requestorSchedule?.shift?.id || null
    const myBranchId = employee.value?.branch_id || null
    const myStoreId = employee.value?.store_id || null

    // filter candidates: same shift id AND same branch (fallback to same store)
    swapCandidates.value = Array.isArray(list)
      ? list.filter((c: any) => {
          const candidateShiftId = c?.schedule?.shift?.id || c?.schedule?.shift_id || null
          const candidateBranchId = c?.employee?.branch_id || null
          const candidateStoreId = c?.employee?.store_id || null
          const candidateRoleId = c?.employee?.role_id || c?.employee?.role?.id || null
          const requestorRoleId = requestorSchedule?.role_id || requestorSchedule?.role?.id || employee.value?.role_id || null

          if (requestorShiftId && candidateShiftId !== requestorShiftId) return false
          // require same role id as well
          if (requestorRoleId && candidateRoleId !== requestorRoleId) return false

          if (myBranchId) return candidateBranchId === myBranchId
          if (myStoreId) return candidateStoreId === myStoreId
          return true
        })
      : []
  } catch (err: any) {
    swapCandidates.value = []
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err?.response?.data?.message || 'Unable to load swap suggestions.',
      life: 3000,
    })
  }
}

const submitShiftSwap = async () => {
  if (!swapForm.requestor_schedule_id || !swapForm.receiver_schedule_id) return
  const candidate = swapCandidates.value.find((c: any) => (c.schedule?.id || c.id) === swapForm.receiver_schedule_id)
  if (!candidate?.employee?.id) return

  savingSwap.value = true
  try {
    const payload = {
      receiver_id: candidate.employee.id,
      requestor_schedule_id: swapForm.requestor_schedule_id,
      receiver_schedule_id: candidate.schedule?.id || swapForm.receiver_schedule_id,
      swap_type: swapForm.swap_type,
      reason: swapForm.reason,
    }
    await hrService.api.post('/api/shift-swaps', payload)
    showShiftSwapDialog.value = false
    swapForm.requestor_schedule_id = null
    swapForm.receiver_schedule_id = null
    swapForm.swap_type = 'full_swap'
    swapForm.reason = ''
    swapCandidates.value = []
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to submit shift swap request.',
      life: 3000,
    })
  } finally {
    savingSwap.value = false
  }
}

const submitOvertimeRequest = async () => {
  if (!employee.value?.id || !overtimeForm.attendance_id || !overtimeForm.ot_start || !overtimeForm.ot_end) return
  savingOvertime.value = true
  try {
    const payload = {
      employee_id: employee.value.id,
      attendance_id: overtimeForm.attendance_id,
      ot_start: formatDateTime(overtimeForm.ot_start),
      ot_end: formatDateTime(overtimeForm.ot_end),
      ot_type: overtimeForm.ot_type,
      rate_multiplier: overtimeForm.rate_multiplier || 1,
      reason: overtimeForm.reason,
    }
    await hrService.api.post('/api/overtime-requests', payload)
    showOvertimeDialog.value = false
    overtimeForm.attendance_id = null
    overtimeForm.ot_start = null
    overtimeForm.ot_end = null
    overtimeForm.ot_type = 'regular'
    overtimeForm.rate_multiplier = 1.25
    overtimeForm.reason = ''
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Unable to submit overtime request.',
      life: 3000,
    })
  } finally {
    savingOvertime.value = false
  }
}

const parseDateInput = (value: string | Date | null | undefined) => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return null
  return date
}

const formatDate = (value: string | Date | null) => {
  if (!value) return null
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return null
  return date.toISOString().split('T')[0]
}

const formatDateTime = (value: string | Date | null) => {
  if (!value) return null
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return null
  return date.toISOString()
}

const formatDateShort = (value: string | Date | null) => {
  if (!value) return '—'
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatTime = (value: string | Date | null) => {
  if (!value) return '—'
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

const formatTimeRange = (start?: string, end?: string) => {
  if (!start || !end) return ''
  return `${start} - ${end}`
}

const formatStatus = (value: string) => {
  if (!value) return 'Unknown'
  return value.toString().replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const formatLabel = (value: string) => formatStatus(value)

const formatCurrency = (value: number | string) => {
  const amount = typeof value === 'string' ? Number(value) : value
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

const formatSwapLabel = (swap: any) => {
  const requestor = swap.requestor?.fname ? `${swap.requestor.fname} ${swap.requestor.lname}` : 'You'
  const receiver = swap.receiver?.fname ? `${swap.receiver.fname} ${swap.receiver.lname}` : 'Teammate'
  return `${requestor} → ${receiver}`
}

const addDays = (date: Date, days: number) => {
  const next = new Date(date)
  next.setDate(next.getDate() + days)
  return next
}

const formatScheduleLabel = (schedule: any) => {
  const shift = schedule?.shift || schedule?.shift || {}
  const name = shift?.name || 'Shift'
  const start = shift?.start_time || ''
  const end = shift?.end_time || ''
  const time = start && end ? `${start} - ${end}` : start || end || ''
  return `${name}${time ? ` (${time})` : ''}`
}

const formatCandidateLabel = (candidate: any) => {
  const schedule = candidate?.schedule || candidate
  const employeeName = `${candidate?.employee?.fname || ''} ${candidate?.employee?.lname || ''}`.trim()
  return `${employeeName || 'Employee'} · ${formatScheduleLabel(schedule)}`
}

const StatChip = defineComponent({
  name: 'StatChip',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
  },
  setup(props) {
    return () => h('div', { class: 'rounded-xl border border-slate-100 bg-slate-50/80 p-3 text-center' }, [
      h('div', { class: 'text-xs text-slate-500' }, props.label),
      h('div', { class: 'mt-1 text-base font-semibold text-slate-900' }, String(props.value)),
    ])
  },
})

const InfoRow = defineComponent({
  name: 'InfoRow',
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
  },
  setup(props) {
    return () => h('div', { class: 'rounded-xl border border-slate-100 bg-slate-50/80 p-4' }, [
      h('div', { class: 'text-xs text-slate-500' }, props.label),
      h('div', { class: 'mt-1 text-sm font-semibold text-slate-900' }, props.value),
    ])
  },
})

onMounted(async () => {
  await loadProfile()
  await fetchProvinces()
  await syncLocationSelection()
  isInitializing.value = false
  if (!isSimplePlan.value) {
    await Promise.all([loadShifts(), loadAttendance(), loadLeaves(), loadSwaps(), loadPayslips(), loadOvertime()])
  }
})

watch(() => locationSelection.provinceId, async (value) => {
  if (!value) {
    cities.value = []
    locationSelection.cityId = null
    if (!isInitializing.value) form.province = ''
    return
  }
  const province = provinces.value.find((item) => String(item.province_id) === String(value))
  if (province) form.province = province.name
  await fetchCities(String(value))
  if (!isInitializing.value) {
    locationSelection.cityId = null
    barangays.value = []
    locationSelection.barangayCode = null
  }
})

watch(() => locationSelection.cityId, async (value) => {
  if (!value) {
    barangays.value = []
    locationSelection.barangayCode = null
    if (!isInitializing.value) form.city = ''
    return
  }
  const city = cities.value.find((item) => String(item.city_id) === String(value))
  if (city) form.city = city.name
  await fetchBarangays(String(value))
  if (!isInitializing.value) {
    locationSelection.barangayCode = null
  }
})

watch(() => locationSelection.barangayCode, (value) => {
  if (!value) {
    if (!isInitializing.value) form.barangay = ''
    return
  }
  const barangay = barangays.value.find((item) => String(item.code) === String(value))
  if (barangay) form.barangay = barangay.name
})
</script>

<style scoped>
.small-input :deep(.p-inputtext),
.small-input :deep(.p-dropdown),
.small-input :deep(.p-select),
.small-input :deep(.p-password),
.small-input :deep(.p-calendar) {
  border-radius: 0.75rem;
  padding: 0.55rem 0.75rem;
  font-size: 0.9rem;
}

.small-pill {
  border-radius: 999px;
  font-size: 0.85rem;
  padding: 0.45rem 0.9rem;
}
</style>
