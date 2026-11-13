<template>
  <Head title="Reset Password | Furnisync">
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <div class="min-h-screen bg-slate-50">
    <div class="grid min-h-screen lg:grid-cols-2">
      <template v-if="isCustomer">
        <main class="order-2 flex items-center justify-center px-5 py-10 sm:px-10 lg:order-1 lg:px-16">
          <section class="w-full max-w-md">
            <div class="mb-7 text-center lg:text-left">
              <Link href="/customer/login" class="portal-title text-2xl font-bold text-orange-500">Furnisync Shop</Link>
              <p class="mt-2 text-sm text-slate-500">Secure your account with a new password.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
              <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                <i class="pi pi-lock text-xl" />
              </div>
              <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Create a new password</h1>
              <p class="mt-2 text-sm leading-6 text-slate-600">Enter and confirm your new password to regain access to your account.</p>

              <Message v-if="status" severity="success" :closable="false" class="mt-5">{{ status }}</Message>

              <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                  <label for="reset-email" class="text-sm font-semibold text-slate-800">Email address</label>
                  <InputText id="reset-email" v-model="form.email" type="email" autocomplete="email" placeholder="you@example.com" fluid autofocus :invalid="Boolean(form.errors.email)" />
                  <small v-if="form.errors.email" class="text-red-600">{{ form.errors.email }}</small>
                </div>

                <div class="space-y-2">
                  <label for="reset-password" class="text-sm font-semibold text-slate-800">New password</label>
                  <Password id="reset-password" v-model="form.password" autocomplete="new-password" placeholder="Enter a new password" toggleMask :feedback="false" fluid :invalid="Boolean(form.errors.password)" />
                  <small v-if="form.errors.password" class="text-red-600">{{ form.errors.password }}</small>
                </div>

                <div class="space-y-2">
                  <label for="reset-password-confirmation" class="text-sm font-semibold text-slate-800">Confirm new password</label>
                  <Password id="reset-password-confirmation" v-model="form.password_confirmation" autocomplete="new-password" placeholder="Re-enter your new password" toggleMask :feedback="false" fluid :invalid="Boolean(form.errors.password_confirmation)" />
                  <small v-if="form.errors.password_confirmation" class="text-red-600">{{ form.errors.password_confirmation }}</small>
                </div>

                <Button type="submit" label="Reset Password" icon="pi pi-check" severity="warn" fluid :loading="form.processing" :disabled="form.processing" />
              </form>

              <div class="mt-6 border-t border-slate-100 pt-5 text-center">
                <Link href="/customer/login" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-600 hover:text-orange-700">
                  <i class="pi pi-arrow-left text-xs" /> Back to customer login
                </Link>
              </div>
            </div>
          </section>
        </main>

        <CustomerAuth3DHero class="order-1 h-56 lg:order-2 lg:h-screen" title="A fresh start" subtitle="Reset your password and return to exploring furniture in 3D." footer="Your next favorite piece is waiting at Furnisync Shop." />
      </template>

      <template v-else>
        <Auth3DHero theme="light" visual="furniture" title="Welcome back to your workspace" subtitle="Set a new password to securely return to your business or supplier account." brand="Furnisync" footer="Built for furniture businesses and suppliers" class="h-56 lg:h-screen" />

        <main class="flex items-center justify-center px-5 py-10 sm:px-10 lg:px-16">
          <section class="w-full max-w-md">
            <div class="mb-7">
              <p class="mb-2 text-sm font-semibold uppercase text-orange-600">Business Account</p>
              <h1 class="text-3xl font-bold text-orange-500">Create a new password</h1>
              <p class="mt-2 text-sm leading-6 text-slate-600">Enter and confirm your new password to regain access to your account.</p>
            </div>

            <Message v-if="status" severity="success" :closable="false" class="mb-5">{{ status }}</Message>

            <form class="space-y-5" @submit.prevent="submit">
              <div class="space-y-2">
                <label for="business-reset-email" class="block text-sm font-medium text-slate-700">Email address</label>
                <InputText id="business-reset-email" v-model="form.email" type="email" placeholder="you@business.com" autocomplete="email" autofocus fluid :invalid="Boolean(form.errors.email)" />
                <small v-if="form.errors.email" class="block text-sm text-red-600">{{ form.errors.email }}</small>
              </div>

              <div class="space-y-2">
                <label for="business-reset-password" class="block text-sm font-medium text-slate-700">New password</label>
                <Password id="business-reset-password" v-model="form.password" placeholder="Enter a new password" autocomplete="new-password" toggleMask :feedback="false" fluid :invalid="Boolean(form.errors.password)" />
                <small v-if="form.errors.password" class="block text-sm text-red-600">{{ form.errors.password }}</small>
              </div>

              <div class="space-y-2">
                <label for="business-reset-password-confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
                <Password id="business-reset-password-confirmation" v-model="form.password_confirmation" placeholder="Re-enter your new password" autocomplete="new-password" toggleMask :feedback="false" fluid :invalid="Boolean(form.errors.password_confirmation)" />
                <small v-if="form.errors.password_confirmation" class="block text-sm text-red-600">{{ form.errors.password_confirmation }}</small>
              </div>

              <Button type="submit" label="Reset Password" icon="pi pi-check" severity="warn" fluid :loading="form.processing" :disabled="form.processing" />
            </form>

            <div class="mt-6 border-t border-slate-100 pt-5">
              <Link href="/login" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-600 hover:text-orange-700">
                <i class="pi pi-arrow-left text-xs" /> Back to business login
              </Link>
            </div>
          </section>
        </main>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'
import Auth3DHero from '@/Components/auth/Auth3DHero.vue'
import CustomerAuth3DHero from '@/Components/auth/CustomerAuth3DHero.vue'

const props = defineProps<{
  email: string
  token: string
  isCustomer?: boolean
  status?: string | null
}>()

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

.portal-title {
  font-family: 'Space Grotesk', sans-serif;
}
</style>
