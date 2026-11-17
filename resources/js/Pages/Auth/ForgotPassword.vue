<template>
  <Head title="Forgot Password | FurniSync Business">
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <div class="min-h-screen bg-slate-50">
    <div class="h-screen w-full bg-white">
      <div class="grid h-full gap-0 lg:grid-cols-2">
        <Auth3DHero
          theme="light"
          visual="furniture"
          title="Your business, beautifully connected"
          subtitle="Manage your furniture store or supplier operations from one connected workspace."
          brand="Furnisync"
          footer="Built for furniture businesses and suppliers"
          class="h-full"
        />

        <main class="flex flex-col justify-center p-8 lg:p-12">
          <div class="w-full">
            <div class="mb-7">
              <p class="mb-2 text-sm font-semibold uppercase text-orange-600">Business Account</p>
              <h1 class="text-3xl font-bold text-orange-500">Reset your password</h1>
              <p class="mt-2 text-sm leading-6 text-slate-600">
                Enter the email address on your account and we will send you a secure password reset link.
              </p>
            </div>

            <Message v-if="status" severity="success" :closable="false" class="mb-5">
              {{ status }}
            </Message>

            <form class="space-y-5" @submit.prevent="submit">
              <div class="space-y-2">
                <label for="business-reset-email" class="block text-sm font-medium text-slate-700">Email address</label>
                <InputText
                  id="business-reset-email"
                  v-model="form.email"
                  type="email"
                  placeholder="you@business.com"
                  autocomplete="email"
                  autofocus
                  fluid
                  :invalid="Boolean(form.errors.email)"
                />
                <small v-if="form.errors.email" class="block text-sm text-red-600">{{ form.errors.email }}</small>
              </div>

              <Button
                type="submit"
                label="Send Reset Link"
                icon="pi pi-send"
                severity="warn"
                fluid
                :loading="form.processing"
                :disabled="form.processing"
              />
            </form>

            <div class="mt-6 border-t border-slate-100 pt-5">
              <Link href="/login" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-600 hover:text-orange-700">
                <i class="pi pi-arrow-left text-xs" />
                Back to business login
              </Link>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Auth3DHero from '@/Components/auth/Auth3DHero.vue'

defineProps<{ status?: string | null }>()

const form = useForm({ email: '' })

const submit = () => {
  form.post(route('password.email'), {
    preserveScroll: true,
    onSuccess: () => form.reset('email'),
  })
}
</script>
