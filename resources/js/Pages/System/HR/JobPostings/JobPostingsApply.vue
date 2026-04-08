<template>
  <div class="mx-auto max-w-5xl space-y-6">
    <section class="rounded-3xl border border-surface-200 bg-white px-6 py-6 shadow-sm">
      <div class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-surface-500">Application Form</p>
        <h1 class="text-3xl font-semibold tracking-tight text-surface-900">{{ jobPosting?.title }}</h1>
        <p class="max-w-3xl text-sm leading-6 text-surface-600">
          {{ jobPosting?.description || 'Complete the required details and documents to submit your application.' }}
        </p>
      </div>
    </section>

    <form class="grid gap-6 xl:grid-cols-[1.35fr_0.85fr]" @submit.prevent="submitApplication">
      <div class="space-y-6">
        <Card class="border border-surface-200 shadow-sm">
          <template #title>Your Information</template>
          <template #content>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-surface-700">First Name</label>
                <InputText v-model="form.first_name" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium text-surface-700">Last Name</label>
                <InputText v-model="form.last_name" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium text-surface-700">Email</label>
                <InputText v-model="form.email" type="email" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium text-surface-700">Phone Number</label>
                <InputText v-model="form.phone" class="w-full" />
              </div>
            </div>

            <Divider />

            <div class="space-y-4">
              <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                <Checkbox v-model="form.is_internal" inputId="is_internal" binary />
                <label for="is_internal" class="text-sm font-medium text-surface-700">
                  I am applying as an internal employee
                </label>
              </div>

              <div v-if="form.is_internal" class="space-y-2">
                <label class="text-sm font-medium text-surface-700">Employee Profile</label>
                <Select
                  v-model="form.employee_id"
                  :options="internalEmployees"
                  optionLabel="full_name"
                  optionValue="id"
                  placeholder="Select employee profile"
                  class="w-full"
                />
              </div>
            </div>
          </template>
        </Card>

        <Card class="border border-surface-200 shadow-sm">
          <template #title>Required Documents</template>
          <template #subtitle>Upload the required files before submitting the application.</template>
          <template #content>
            <div class="space-y-4">
              <div
                v-for="docType in requiredDocuments"
                :key="docType"
                class="rounded-2xl border border-dashed border-surface-300 bg-slate-50/70 p-4"
              >
                <div class="mb-3 flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-surface-900">{{ docType }}</p>
                    <p class="text-xs text-surface-500">Accepted: PDF, DOC, DOCX, JPG, PNG up to 5MB</p>
                  </div>
                  <Tag
                    :value="uploadedDocuments[docType] ? 'Attached' : 'Required'"
                    :severity="uploadedDocuments[docType] ? 'success' : 'warn'"
                  />
                </div>

                <FileUpload
                  mode="basic"
                  customUpload
                  auto
                  chooseLabel="Choose File"
                  accept=".pdf,.doc,.docx,.jpg,.png"
                  :maxFileSize="5000000"
                  class="w-full"
                  @uploader="(event) => handleFileUpload(docType, event)"
                />

                <Message
                  v-if="uploadedDocuments[docType]"
                  severity="success"
                  :closable="false"
                  class="mt-3"
                >
                  {{ uploadedDocuments[docType].name }}
                </Message>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="space-y-6">
        <Card class="sticky top-6 border border-surface-200 shadow-sm">
          <template #title>Submission Review</template>
          <template #content>
            <div class="space-y-5">
              <div class="grid gap-4 rounded-2xl bg-slate-50 p-4">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Application Type</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">
                    {{ form.is_internal ? 'Internal Candidate' : 'External Candidate' }}
                  </p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Documents Attached</p>
                  <p class="mt-1 text-sm font-semibold text-surface-900">
                    {{ uploadedDocumentCount }} / {{ requiredDocuments.length }}
                  </p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Submission Status</p>
                  <Tag :value="canSubmit ? 'Ready' : 'Incomplete'" :severity="canSubmit ? 'success' : 'warn'" />
                </div>
              </div>

              <Message severity="secondary" :closable="false">
                Make sure the applicant information is complete and every required document is attached.
              </Message>

              <div class="flex flex-col gap-3">
                <Button
                  type="button"
                  label="Cancel"
                  severity="secondary"
                  outlined
                  @click="router.back()"
                />
                <Button
                  type="submit"
                  label="Submit Application"
                  icon="pi pi-send"
                  :loading="isSubmitting"
                  :disabled="!canSubmit || isSubmitting"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Divider from 'primevue/divider'
import FileUpload from 'primevue/fileupload'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../../../stores/auth'
import hrService from '../../../../services/hr.services'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const jobPosting = ref<any | null>(null)
const internalEmployees = ref<any[]>([])
const isSubmitting = ref(false)

const requiredDocuments = ['Resume', 'Cover Letter', 'ID', 'Certificate', 'Portfolio']

const form = ref({
  first_name: '',
  last_name: '',
  email: (authStore as any).user?.email || '',
  phone: '',
  is_internal: false,
  employee_id: null as number | null,
})

const uploadedDocuments = ref<Record<string, File>>({})

const uploadedDocumentCount = computed(() => Object.keys(uploadedDocuments.value).length)
const canSubmit = computed(() => {
  const hasBasicInfo =
    form.value.first_name &&
    form.value.last_name &&
    form.value.email &&
    form.value.phone &&
    (!form.value.is_internal || form.value.employee_id)

  return Boolean(hasBasicInfo && uploadedDocumentCount.value === requiredDocuments.length)
})

const loadJobPosting = async () => {
  try {
    const data = await hrService.getJobPosting(route.params.postingId as string)
    jobPosting.value = data
  } catch (error) {
    console.error('Failed to load job posting:', error)
  }
}

const loadInternalEmployees = async () => {
  try {
    const data = await hrService.getEmployees()
    const employees = data.data || data || []
    internalEmployees.value = employees.map((employee: any) => ({
      ...employee,
      full_name:
        employee.full_name ||
        `${employee.first_name || employee.fname || ''} ${employee.last_name || employee.lname || ''}`.trim(),
    }))
  } catch (error) {
    console.error('Failed to load employees:', error)
  }
}

const handleFileUpload = (docType: string, event: any) => {
  const file = event.files?.[0]
  if (file) {
    uploadedDocuments.value[docType] = file
  }
}

const submitApplication = async () => {
  if (!canSubmit.value) return

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('job_posting_id', route.params.postingId as string)
    formData.append('first_name', form.value.first_name)
    formData.append('last_name', form.value.last_name)
    formData.append('email', form.value.email)
    formData.append('phone', form.value.phone)
    formData.append('is_internal', String(form.value.is_internal))

    if (form.value.is_internal && form.value.employee_id) {
      formData.append('employee_id', String(form.value.employee_id))
    }

    Object.entries(uploadedDocuments.value).forEach(([docType, file]) => {
      formData.append('documents[]', file, file.name)
      formData.append(`document_types[${file.name}]`, docType)
    })

    await hrService.submitJobApplication(route.params.postingId as string, formData)
    router.push({ name: 'hr.job-postings' })
  } catch (error) {
    console.error('Failed to submit application:', error)
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  loadJobPosting()
  loadInternalEmployees()
})
</script>
