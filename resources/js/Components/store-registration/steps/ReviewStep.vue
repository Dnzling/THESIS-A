<template>
  <div class="space-y-6">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Step 3</p>
      <h2 class="mt-1 text-2xl font-bold text-slate-950">Review and submit</h2>
      <p class="mt-2 text-sm text-slate-600">Confirm the owner ID and business documents before sending this for admin review.</p>
    </div>

    <ReviewSection title="Owner Primary ID" :canEdit="true" @edit="$emit('edit-step', 1)">
      <div class="grid gap-4 md:grid-cols-2">
        <InfoItem label="ID Type" :value="idLabel(formData.primaryIdType)" />
        <InfoItem label="ID Number" :value="formData.primaryIdNumber" />
        <FilePreview :file="formData.primaryIdFront" label="Front photo" />
        <FilePreview :file="formData.primaryIdBack" label="Back photo" />
      </div>
    </ReviewSection>

    <ReviewSection title="Business Documents" :canEdit="true" @edit="$emit('edit-step', 2)">
      <InfoItem label="Business registration number" :value="formData.businessRegistrationNumber" />
      <div class="grid gap-4 md:grid-cols-3">
        <FilePreview :file="formData.registrationPermit" label="Registration permit" />
        <FilePreview :file="formData.taxCertificate" label="BIR tax certificate" />
        <FilePreview :file="formData.mayorPermit" label="Mayor's/business permit" />
      </div>
      <InfoItem v-if="formData.additionalNotes" label="Additional Notes" :value="formData.additionalNotes" />
    </ReviewSection>

    <div class="rounded-2xl border border-orange-100 bg-orange-50/50 p-5">
      <h3 class="font-semibold text-slate-950">Consent and confirmation</h3>
      <div class="mt-4 space-y-3">
        <label class="flex items-start gap-3 text-sm text-slate-700">
          <input v-model="localForm.termsAccepted" type="checkbox" class="mt-1 rounded border-orange-300 text-orange-500" @change="syncForm" />
          <span>I confirm that the submitted owner ID and business documents are accurate and complete.</span>
        </label>
        <label class="flex items-start gap-3 text-sm text-slate-700">
          <input v-model="localForm.privacyAccepted" type="checkbox" class="mt-1 rounded border-orange-300 text-orange-500" @change="syncForm" />
          <span>I consent to processing these documents for store verification and fraud prevention.</span>
        </label>
      </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-between">
      <Button type="button" label="Back" severity="secondary" outlined @click="$emit('prev')" />
      <Button type="button" label="Submit Verification" icon="pi pi-check" severity="warn" :disabled="!isStepValid || isSubmitting" @click="submitVerification" />
    </div>
  </div>

  <Dialog v-model:visible="isSubmitting" modal :closable="false" :showHeader="false" :style="{ width: '300px' }">
    <div class="flex flex-col items-center justify-center p-6">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" fill="transparent" animationDuration=".5s" />
      <p class="mt-4 text-lg font-medium text-slate-700">Submitting verification...</p>
      <p class="text-sm text-slate-500">Please wait a moment</p>
    </div>
  </Dialog>

  <Dialog class="text-center" v-model:visible="responseModal.visible" modal :closable="!responseModal.success" :style="{ width: '28rem' }" :header="responseModal.title">
    <div class="space-y-4">
      <div class="flex items-start gap-3">
        <div
          class="flex h-11 w-12 shrink-0 items-center justify-center rounded-2xl"
          :class="responseModal.success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
        >
          <i :class="responseModal.success ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'" class="text-xl"></i>
        </div>
        <p class="text-sm leading-6 text-slate-600">{{ responseModal.message }}</p>
      </div>

      <div class="flex justify-center gap-2 border-t border-slate-100 pt-4">
        <Button
          v-if="responseModal.success"
          label="Ok"
          severity="warn"
          iconPos="right"
          @click="goToSettings"
        />
        <Button
          v-else
          label="OK"
          severity="secondary"
          outlined
          @click="responseModal.visible = false"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import ReviewSection from '../shared/ReviewSection.vue'
import InfoItem from '../shared/InfoItem.vue'
import FilePreview from '../shared/FilePreview.vue'
import axiosClient from '@/axios'

const props = defineProps<{ formData: any }>()
const emit = defineEmits<{
  (e: 'update:formData', data: any): void
  (e: 'prev'): void
  (e: 'verification-submitted'): void
  (e: 'edit-step', step: number): void
}>()

const isSubmitting = ref(false)
const responseModal = ref({
  visible: false,
  success: false,
  title: '',
  message: '',
})

const idLabels: Record<string, string> = {
  national_id: 'PhilSys National ID',
  umid: 'UMID',
  sss: 'SSS ID',
  tin: 'TIN ID',
  driver_license: "Driver's License",
  passport: 'Philippine Passport',
}

const localForm = ref({
  ...props.formData,
  termsAccepted: props.formData.termsAccepted || false,
  privacyAccepted: props.formData.privacyAccepted || false,
})

const idLabel = (value: string) => idLabels[value] || value || 'Not provided'
const syncForm = () => emit('update:formData', { ...localForm.value })

const hasRequiredData = computed(() =>
  Boolean(props.formData.storeId)
  && Boolean(props.formData.primaryIdType)
  && Boolean(String(props.formData.primaryIdNumber || '').trim())
  && props.formData.primaryIdFront instanceof File
  && props.formData.registrationPermit instanceof File
  && Boolean(String(props.formData.businessRegistrationNumber || '').trim())
  && props.formData.taxCertificate instanceof File
  && props.formData.mayorPermit instanceof File
)

const isStepValid = computed(() => hasRequiredData.value && localForm.value.termsAccepted && localForm.value.privacyAccepted)

const appendFile = (payload: FormData, key: string, file: unknown) => {
  if (file instanceof File) {
    payload.append(key, file)
  }
}

const showResponseModal = (success: boolean, title: string, message: string) => {
  responseModal.value = {
    visible: true,
    success,
    title,
    message,
  }
}

const goToSettings = () => {
  responseModal.value.visible = false
  emit('verification-submitted')
}

const submitVerification = async () => {
  if (!isStepValid.value) {
    showResponseModal(false, 'Review Required', 'Please complete the owner ID, business documents, and consent checks before submitting.')
    return
  }

  isSubmitting.value = true

  try {
    const payload = new FormData()
    payload.append('gov_id_type', props.formData.primaryIdType)
    payload.append('gov_id_number', props.formData.primaryIdNumber)
    payload.append('business_registration_number', props.formData.businessRegistrationNumber || '')
    payload.append('business_registration_date', new Date().toISOString().slice(0, 10))
    appendFile(payload, 'gov_id_front_file', props.formData.primaryIdFront)
    appendFile(payload, 'gov_id_back_file', props.formData.primaryIdBack)
    appendFile(payload, 'business_registration_file', props.formData.registrationPermit)
    appendFile(payload, 'tax_certificate_file', props.formData.taxCertificate)
    appendFile(payload, 'business_permit_file', props.formData.mayorPermit)

    const response = await axiosClient.post(`/api/stores/${props.formData.storeId}/verification/submit`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    showResponseModal(true, 'Verification Submitted', response?.data?.message || 'Your store verification is now under review.')
  } catch (error: any) {
    const errors = error?.response?.data?.errors
    const firstError = errors && typeof errors === 'object' ? Object.values(errors)[0] : null
    showResponseModal(
      false,
      error?.response?.status === 422 ? 'Validation Error' : 'Submission Failed',
      Array.isArray(firstError) ? String(firstError[0]) : (error?.response?.data?.message || 'Please try again later.')
    )
  } finally {
    isSubmitting.value = false
  }
}
</script>
