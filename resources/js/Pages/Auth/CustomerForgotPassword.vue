<template>
  <Toast />
  <Head title="Forgot Password | Furnisync Shop" />

  <div class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">
      <main class="flex items-center justify-center px-6 py-12 sm:px-10 lg:px-16">
        <div class="w-full max-w-md">
          <div class="mb-8 text-center">
            <Link href="/customer/login" class="portal-title text-2xl font-bold text-orange-500">Furnisync Shop</Link>
            <p class="mt-2 text-sm text-slate-500 sm:text-base">Get back to discovering furniture you love.</p>
          </div>

          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
              <i class="pi pi-lock text-xl" />
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Forgot your password?</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
              Enter the email address on your customer account. We’ll send you a secure link to reset your password.
            </p>

            <Message v-if="status" severity="success" :closable="false" class="mt-5">
              {{ status }}
            </Message>

            <form class="mt-6 space-y-5" @submit.prevent="submit">
              <div class="space-y-2">
                <label for="customer-reset-email" class="text-sm font-semibold text-slate-800">Email address</label>
                <InputText
                  id="customer-reset-email"
                  v-model="form.email"
                  type="email"
                  autocomplete="email"
                  placeholder="you@example.com"
                  fluid
                  autofocus
                  :invalid="Boolean(form.errors.email)"
                />
                <small v-if="form.errors.email" class="text-red-600">{{ form.errors.email }}</small>
              </div>

              <Button
                type="submit"
                label="Send Reset Link"
                icon="pi pi-send"
                severity="warn"
                fluid
                :loading="form.processing"
              />
            </form>

            <div class="mt-6 border-t border-slate-100 pt-5 text-center">
              <Link href="/customer/login" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-600 hover:text-orange-700">
                <i class="pi pi-arrow-left text-xs" />
                Back to customer login
              </Link>
            </div>
          </div>
        </div>
      </main>

      <CustomerAuth3DHero
        class="hidden min-h-screen lg:block"
        title="A fresh start"
        subtitle="Reset your password and return to exploring furniture in 3D."
        footer="Your next favorite piece is waiting at Furnisync Shop."
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Toast from 'primevue/toast'
import CustomerAuth3DHero from '@/Components/auth/CustomerAuth3DHero.vue'

defineProps<{ status?: string | null }>()

const form = useForm({ email: '' })

const submit = () => {
  form.post('/customer/forgot-password', {
    preserveScroll: true,
    onSuccess: () => form.reset('email'),
  })
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

.portal-title {
  font-family: 'Space Grotesk', sans-serif;
}
</style>
