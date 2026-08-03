<template>
  <nav class="border-b border-slate-200/70 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
      <router-link to="/" class="flex items-center gap-3" @click="closeMobileMenu">
           <span class="portal-brand text-orange-500 text-xl">FURNISYNC</span>
      </router-link>

      <div class="hidden items-center gap-6 md:flex">
        <router-link to="/" class="nav-link" :class="{ 'nav-link-active': route.path === '/' }">
          Home
        </router-link>
        <router-link to="/about" class="nav-link" :class="{ 'nav-link-active': route.path === '/about' }">
          About
        </router-link>
        <router-link to="/pricing" class="nav-link" :class="{ 'nav-link-active': route.path === '/pricing' }">
          Pricing
        </router-link>
      </div>

      <div class="hidden items-center gap-4 md:flex">
        <router-link to="/login" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
          Log In
        </router-link>
        <router-link
          to="/register"
          class="rounded-full bg-gradient-to-r from-orange-500 to-orange-400 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-orange-600 hover:to-orange-500"
        >
          Get Started
        </router-link>
      </div>

      <button
        @click="toggleMobileMenu"
        class="rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-50 md:hidden"
        :class="{ 'bg-slate-50': isMobileMenuOpen }"
      >
        <div class="flex flex-col space-y-1.5">
          <span class="h-0.5 w-6 bg-slate-700 transition-transform duration-300" :class="{ 'translate-y-2 rotate-45': isMobileMenuOpen }"></span>
          <span class="h-0.5 w-6 bg-slate-700 transition-opacity duration-300" :class="{ 'opacity-0': isMobileMenuOpen }"></span>
          <span class="h-0.5 w-6 bg-slate-700 transition-transform duration-300" :class="{ '-translate-y-2 -rotate-45': isMobileMenuOpen }"></span>
        </div>
      </button>
    </div>

    <div v-if="isMobileMenuOpen" class="border-t border-slate-200/70 bg-white px-6 py-5 md:hidden animate-fadeIn">
      <div class="flex flex-col gap-2">
        <router-link to="/" class="mobile-link" :class="{ 'mobile-link-active': route.path === '/' }" @click="closeMobileMenu">
          Home
        </router-link>
        <router-link to="/about" class="mobile-link" :class="{ 'mobile-link-active': route.path === '/about' }" @click="closeMobileMenu">
          About
        </router-link>
        <router-link to="/pricing" class="mobile-link" :class="{ 'mobile-link-active': route.path === '/pricing' }" @click="closeMobileMenu">
          Pricing
        </router-link>
        <router-link to="/login" class="mobile-link" @click="closeMobileMenu">Sign In</router-link>
        <router-link
          to="/register?plan=simple&trial=1"
          class="mt-2 rounded-full bg-gradient-to-r from-orange-500 to-orange-400 px-5 py-2 text-center text-sm font-semibold text-white shadow-sm"
          @click="closeMobileMenu"
        >
          Sign Up
        </router-link>
      </div>
    </div>
  </nav>
  <router-view></router-view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

// Close mobile menu on route change
import { watch } from 'vue'
watch(() => route.path, closeMobileMenu)
</script>

<style>


.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.nav-link {
  font-size: 0.95rem;
  font-weight: 600;
  color: #475569;
  transition: color 0.2s ease;
}

.nav-link:hover {
  color: #0f172a;
}

.nav-link-active {
  color: #0f172a;
}

.mobile-link {
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 600;
  color: #475569;
  transition: background 0.2s ease, color 0.2s ease;
}

.mobile-link:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.mobile-link-active {
  background: #ecfeff;
  color: #0f172a;
}

.portal-brand {
  font-family: 'Barabara', sans-serif;

}
</style>
