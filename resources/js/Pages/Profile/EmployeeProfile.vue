<template>
  <div class="min-h-screen">
    <div class="mx-auto max-w-6xl px-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">My Profile</p>
          <h1 class="text-2xl font-semibold text-slate-900">Employee Profile</h1>
          <p class="text-sm text-slate-500">Update personal info, security, and requests.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button label="Request Leave" icon="pi pi-calendar" severity="info" outlined class="small-pill"
            @click="showLeaveDialog = true" />
          <Button label="Shift Swap" icon="pi pi-share-alt" severity="secondary" outlined class="small-pill"
            @click="openShiftSwapDialog" />
          <Button label="Overtime" icon="pi pi-clock" severity="warning" outlined class="small-pill"
            @click="openOvertimeDialog" />
        </div>
      </div>

      <div v-if="loading" class="mt-10 flex items-center justify-center">
        <ProgressSpinner />
      </div>

      <template v-else>
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1.1fr_1fr]">
          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-center gap-4">
                <Avatar :label="initials" size="xlarge" class="bg-emerald-50 text-emerald-600 text-2xl font-semibold" />
                <div>
                  <p class="text-lg font-semibold text-slate-900">{{ fullName }}</p>
                  <p class="text-xs text-slate-500">{{ employeeSummary }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Tag v-if="employee?.status" :value="statusLabel" :severity="statusSeverity" rounded />
                <Button label="Edit Personal Info" severity="secondary" outlined class="small-pill" @click="showEditProfileDialog = true" />
              </div>
            </div>

            <div class="mt-6 space-y-6 text-sm">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Personal</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <InfoRow label="Full Name" :value="fullName || '-'" />
                  <InfoRow label="Birth Date" :value="displayBirthDate" />
                  <InfoRow label="Gender" :value="displayGender" />
                </div>
              </div>

              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contact</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <InfoRow label="Phone" :value="displayPhone" />
                  <InfoRow label="Address" :value="displayAddress" />
                </div>
              </div>

              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Emergency Contact</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <InfoRow label="Name" :value="displayEmergencyName" />
                  <InfoRow label="Relationship" :value="displayEmergencyRelationship" />
                  <InfoRow label="Phone" :value="displayEmergencyPhone" />
                </div>
              </div>

              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Employment</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <InfoRow label="Employee Number" :value="employee?.employee_number || '-'" />
                  <InfoRow label="Department" :value="employee?.department || '-'" />
                  <InfoRow label="Role" :value="employeeRoleLabel" />
                  <InfoRow label="Employment Type" :value="formatStatus(employee?.employment_type || '-')" />
                  <InfoRow label="Hire Date" :value="formatDateShort(employee?.hire_date) || '-'" />
                  <InfoRow label="Branch" :value="displayBranch" />
                </div>
              </div>

              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Compensation</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <InfoRow label="Monthly Salary" :value="formatCurrency(employee?.salary || 0)" />
                  <InfoRow label="Bank Account" :value="employee?.bank_account || '-'" />
                </div>
              </div>

              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Government IDs</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <InfoRow label="TIN" :value="employee?.tax_id || '-'" />
                </div>
              </div>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm space-y-6">
            <div>
              <p class="text-sm font-semibold text-slate-800">Security</p>
              <p class="text-xs text-slate-500">Change password or email verification.</p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700">Password</p>
                  <p class="text-xs text-slate-500">Keep your account secure.</p>
                </div>
                <Button label="Change Password" icon="pi pi-lock" severity="warning" outlined class="small-pill"
                  @click="showPasswordDialog = true" />
              </div>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700">Email Status</p>
                  <p class="text-xs text-slate-500">Verification required after change.</p>
                </div>
                <Tag :value="emailStatusLabel" :severity="emailStatusSeverity" rounded />
              </div>
            </div>

            <div>
              <p class="text-sm font-semibold text-slate-800">Requests</p>
              <p class="text-xs text-slate-500">Submit leave or shift swap requests.</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <Button label="Request Leave" icon="pi pi-calendar" severity="info" class="small-pill"
                  @click="showLeaveDialog = true" />
                <Button label="Shift Swap" icon="pi pi-share-alt" severity="secondary" class="small-pill"
                  @click="openShiftSwapDialog" />
                <Button label="Overtime" icon="pi pi-clock" severity="warning" class="small-pill"
                  @click="openOvertimeDialog" />
              </div>
            </div>
          </section>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">Upcoming Shifts</p>
                <p class="text-xs text-slate-500">Next 14 days schedule.</p>
              </div>
              <Tag v-if="nextShiftLabel" :value="nextShiftLabel" severity="info" rounded />
            </div>
            <div v-if="loadingShifts" class="mt-4 flex justify-center">
              <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
            </div>
            <div v-else class="mt-4 space-y-3">
              <div v-for="shift in shifts" :key="shift.id" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="flex items-center justify-between">
                  <div class="text-sm font-semibold text-slate-900">{{ formatDateShort(shift.schedule_date) }}</div>
                  <Tag :value="formatStatus(shift.status)" severity="secondary" rounded />
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ shift.shift?.name || 'Shift' }}
                  <span v-if="shift.shift?.start_time && shift.shift?.end_time"> · {{ formatTimeRange(shift.shift?.start_time, shift.shift?.end_time) }}</span>
                </div>
              </div>
              <p v-if="!shifts.length" class="text-sm text-slate-400">No upcoming shifts found.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">Attendance Summary</p>
                <p class="text-xs text-slate-500">Current month overview.</p>
              </div>
            </div>
            <div v-if="loadingAttendance" class="mt-4 flex justify-center">
              <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
            </div>
            <div v-else class="mt-4 space-y-4">
              <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <StatChip label="Present" :value="attendanceSummary.present" />
                <StatChip label="Late" :value="attendanceSummary.late" />
                <StatChip label="Absent" :value="attendanceSummary.absent" />
                <StatChip label="On Leave" :value="attendanceSummary.on_leave" />
                <StatChip label="Half Day" :value="attendanceSummary.half_day" />
                <StatChip label="Total" :value="attendanceSummary.total" />
              </div>
              <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Recent Records</p>
                <div v-for="record in attendanceRecords" :key="record.id" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-sm">
                  <div class="flex items-center justify-between">
                    <span class="font-semibold text-slate-800">{{ formatDateShort(record.attendance_date) }}</span>
                    <Tag :value="formatStatus(record.status)" severity="secondary" rounded />
                  </div>
                  <div class="mt-1 text-xs text-slate-500">
                    {{ record.shift?.name || 'Shift' }}
                    <span v-if="record.clock_in"> · In {{ formatTime(record.clock_in) }}</span>
                    <span v-if="record.clock_out"> · Out {{ formatTime(record.clock_out) }}</span>
                  </div>
                </div>
                <p v-if="!attendanceRecords.length" class="text-sm text-slate-400">No attendance records yet.</p>
              </div>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">Leave Records</p>
                <p class="text-xs text-slate-500">Recent leave requests.</p>
              </div>
            </div>
            <div v-if="loadingLeaves" class="mt-4 flex justify-center">
              <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
            </div>
            <div v-else class="mt-4 space-y-3">
              <div v-for="leave in leaveRecords" :key="leave.id" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-sm">
                <div class="flex items-center justify-between">
                  <span class="font-semibold text-slate-800">{{ leave.leave_type_label || formatLabel(leave.leave_type) }}</span>
                  <Tag :value="formatStatus(leave.status)" :severity="leave.status === 'approved' ? 'success' : leave.status === 'rejected' ? 'danger' : 'warning'" rounded />
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ leave.start_date_formatted || formatDateShort(leave.start_date) }} - {{ leave.end_date_formatted || formatDateShort(leave.end_date) }}
                  <span v-if="leave.total_days"> · {{ leave.total_days }} day(s)</span>
                </div>
              </div>
              <p v-if="!leaveRecords.length" class="text-sm text-slate-400">No leave requests found.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">Shift Swap Requests</p>
                <p class="text-xs text-slate-500">Recent swap activity.</p>
              </div>
            </div>
            <div v-if="loadingSwaps" class="mt-4 flex justify-center">
              <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
            </div>
            <div v-else class="mt-4 space-y-3">
              <div v-for="swap in swapRecords" :key="swap.id" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-sm">
                <div class="flex items-center justify-between">
                  <span class="font-semibold text-slate-800">{{ formatSwapLabel(swap) }}</span>
                  <Tag :value="formatStatus(swap.status)" :severity="swap.status === 'approved' || swap.status === 'accepted' ? 'success' : swap.status === 'rejected' ? 'danger' : 'warning'" rounded />
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ formatDateShort(swap.requestor_schedule?.schedule_date || swap.requestorSchedule?.schedule_date) }}
                  <span v-if="swap.swap_type"> · {{ formatLabel(swap.swap_type) }}</span>
                </div>
              </div>
              <p v-if="!swapRecords.length" class="text-sm text-slate-400">No swap requests found.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">Overtime Requests</p>
                <p class="text-xs text-slate-500">Recent overtime submissions.</p>
              </div>
            </div>
            <div v-if="loadingOvertime" class="mt-4 flex justify-center">
              <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
            </div>
            <div v-else class="mt-4 space-y-3">
              <div v-for="ot in overtimeRecords" :key="ot.id" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-sm">
                <div class="flex items-center justify-between">
                  <span class="font-semibold text-slate-800">
                    {{ formatDateShort(ot.ot_start) }} · {{ formatTime(ot.ot_start) }} - {{ formatTime(ot.ot_end) }}
                  </span>
                  <Tag :value="formatStatus(ot.status)" :severity="ot.status === 'approved' ? 'success' : ot.status === 'rejected' ? 'danger' : 'warning'" rounded />
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ formatStatus(ot.ot_type) }}
                  <span v-if="ot.rate_multiplier"> · x{{ ot.rate_multiplier }}</span>
                </div>
              </div>
              <p v-if="!overtimeRecords.length" class="text-sm text-slate-400">No overtime requests yet.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">Payslips</p>
                <p class="text-xs text-slate-500">Latest payroll entries.</p>
              </div>
              <div class="text-xs text-slate-500" v-if="payslipSummary.total_amount">
                Total Net: {{ formatCurrency(payslipSummary.total_amount) }}
              </div>
            </div>
            <div v-if="loadingPayslips" class="mt-4 flex justify-center">
              <ProgressSpinner style="width: 28px; height: 28px" strokeWidth="4" />
            </div>
            <div v-else class="mt-4 space-y-3">
              <div v-for="payslip in payslips" :key="payslip.id" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 text-sm">
                <div class="flex items-center justify-between">
                  <span class="font-semibold text-slate-800">{{ payslip.pay_period?.name || payslip.pay_period_name || payslip.pay_period_label || 'Pay Period' }}</span>
                  <Tag :value="formatStatus(payslip.status)" :severity="payslip.status === 'paid' ? 'success' : payslip.status === 'approved' ? 'info' : 'warning'" rounded />
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Net Pay: {{ formatCurrency(payslip.net_salary || payslip.net_pay || 0) }}
                  <span v-if="payslip.payment_date"> · Paid {{ formatDateShort(payslip.payment_date) }}</span>
                </div>
              </div>
              <p v-if="!payslips.length" class="text-sm text-slate-400">No payslips available yet.</p>
            </div>
          </section>
        </div>
      </template>
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

  <Dialog v-model:visible="showEditProfileDialog" header="Edit Personal Info" :style="{ width: 'min(48rem, 94vw)' }" modal>
    <div class="space-y-6">
      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">First Name</label>
          <InputText v-model="form.fname" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Last Name</label>
          <InputText v-model="form.lname" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Birthday</label>
          <DatePicker v-model="form.birthday" :maxDate="new Date()" dateFormat="yy-mm-dd" class="w-full small-input" showIcon />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Gender</label>
          <div class="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            <div class="flex items-center gap-2">
              <RadioButton v-model="form.gender" inputId="gender-male" value="male" />
              <label for="gender-male" class="text-sm text-slate-700">Male</label>
            </div>
            <div class="flex items-center gap-2">
              <RadioButton v-model="form.gender" inputId="gender-female" value="female" />
              <label for="gender-female" class="text-sm text-slate-700">Female</label>
            </div>
            <div class="flex items-center gap-2">
              <RadioButton v-model="form.gender" inputId="gender-other" value="other" />
              <label for="gender-other" class="text-sm text-slate-700">Other</label>
            </div>
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Email</label>
          <InputText :model-value="user?.email || ''" disabled class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Phone Number</label>
          <InputMask mask="+63 999 9999 999"  v-model="form.phone" class="w-full small-input" placeholder="+63" />
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Province</label>
          <Select v-model="locationSelection.provinceId" :options="provinceOptions" optionLabel="label"
            optionValue="value" filter placeholder="Select Province" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">City / Municipality</label>
          <Select v-model="locationSelection.cityId" :options="cityOptions" optionLabel="label" optionValue="value"
            filter placeholder="Select City" class="w-full small-input"
            :disabled="!locationSelection.provinceId" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Barangay</label>
          <Select v-model="locationSelection.barangayCode" :options="barangayOptions" optionLabel="label"
            optionValue="value" filter placeholder="Select Barangay" class="w-full small-input"
            :disabled="!locationSelection.cityId" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Street / House / Lot</label>
          <Textarea v-model="form.address" rows="2" class="w-full small-input" />
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Emergency Contact Name</label>
          <InputText v-model="form.emergency_contact_name" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Relationship</label>
          <InputText v-model="form.emergency_contact_relationship" class="w-full small-input" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-500">Emergency Contact Phone</label>
          <InputMask mask="+63 999 9999 999" v-model="form.emergency_contact_phone" class="w-full small-input" placeholder="+63" />
        </div>
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" text @click="showEditProfileDialog = false" />
      <Button label="Save Profile" icon="pi pi-check" severity="success" class="small-pill"
        :loading="savingProfile" @click="saveProfile" />
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
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, defineComponent, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import hrService from '../../services/hr.services'
import ecommerceService from '../../services/ecommerce.service'
import { useAuthStore } from '../../stores/auth'
import { startCase } from 'lodash'

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

const showEmailDialog = ref(false)
const showEditProfileDialog = ref(false)
const showPasswordDialog = ref(false)
const showLeaveDialog = ref(false)
const showShiftSwapDialog = ref(false)
const showOvertimeDialog = ref(false)

const user = ref<any | null>(null)
const employee = ref<any | null>(null)

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
      phone: form.phone || null,
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
    showEditProfileDialog.value = false
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

const resetProfile = () => {
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
  syncLocationSelection()
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
    const response = await hrService.api.get(`/api/payroll/payslip/${employee.value.id}`, {
      params: { per_page: 5 },
    })
    const payload = response?.data || {}
    const list = payload?.data?.data || payload?.data || []
    payslips.value = Array.isArray(list) ? list : []
    payslipSummary.value = payload?.summary || {}
  } catch (err) {
    payslips.value = []
    payslipSummary.value = {}
  } finally {
    loadingPayslips.value = false
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
  const response = await hrService.api.get('/api/shift-swaps/suggestions', {
    params: { requestor_schedule_id: swapForm.requestor_schedule_id },
  })
  swapCandidates.value = response?.data?.data || []
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
  const date = schedule?.schedule_date || '—'
  const shift = schedule?.shift?.name || 'Shift'
  const start = schedule?.shift?.start_time || ''
  const end = schedule?.shift?.end_time || ''
  const time = start && end ? `${start} - ${end}` : ''
  return `${date} · ${shift}${time ? ` (${time})` : ''}`
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
  await Promise.all([loadShifts(), loadAttendance(), loadLeaves(), loadSwaps(), loadPayslips(), loadOvertime()])
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
