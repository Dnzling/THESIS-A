<template>
    <nav class="p-6 border-b-2 border-gray-200 bg-white shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Logo -->
            <Link href="/" class="flex items-center space-x-3 group" @click="closeMobileMenu">
                <div class="w-35">
                    <img src="/Logo Landscape.png" alt="FurniSync logo">
                </div>
            </Link>
    
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1">
                <Link href="/"
                    class="px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200"
                    :class="{ 
                'text-blue-600 bg-blue-50 shadow-sm': currentPath === '/',
                'hover:shadow': currentPath !== '/'
              }">
                    <i class="fas fa-home mr-2"></i>
                    Home
                </Link>
                <Link href="/about"
                    class="px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200"
                    :class="{ 
                'text-blue-600 bg-blue-50 shadow-sm': currentPath === '/about',
                'hover:shadow': currentPath !== '/about'
              }">
                    <i class="fas fa-info-circle mr-2"></i>
                    About
                </Link>
                <Link href="/pricing"
                    class="px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200"
                    :class="{ 
                'text-blue-600 bg-blue-50 shadow-sm': currentPath === '/pricing',
                'hover:shadow': currentPath !== '/pricing'
              }">
                    <i class="fas fa-tags mr-2"></i>
                    Pricing
                </Link>
            </div>
    
            <!-- Login Button -->
            <div class="hidden md:flex items-center space-x-4">
                <Link href="/login"
                    class="px-6 py-2.5 bg-linear-to-r from-blue-600 to-blue-500 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </Link>
            </div>
    
            <!-- Mobile Menu Button -->
            <button @click="toggleMobileMenu"
                class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200"
                :class="{ 'bg-gray-100': isMobileMenuOpen }">
                <div class="flex flex-col space-y-1.5">
                    <span class="w-6 h-0.5 bg-gray-700 transition-transform duration-300"
                        :class="{ 'rotate-45 translate-y-2': isMobileMenuOpen }"></span>
                    <span class="w-6 h-0.5 bg-gray-700 transition-opacity duration-300"
                        :class="{ 'opacity-0': isMobileMenuOpen }"></span>
                    <span class="w-6 h-0.5 bg-gray-700 transition-transform duration-300"
                        :class="{ '-rotate-45 -translate-y-2': isMobileMenuOpen }"></span>
                </div>
            </button>
        </div>
    
        <!-- Mobile Menu -->
        <div v-if="isMobileMenuOpen" class="md:hidden pt-4 animate-fadeIn">
            <div class="space-y-1">
                <Link href="/"
                    class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
                    @click="closeMobileMenu" :class="{ 'text-blue-600 bg-blue-50': currentPath === '/' }">
                    <i class="fas fa-home w-6 mr-3"></i>
                    Home
                </Link>
                <Link href="/about"
                    class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
                    @click="closeMobileMenu" :class="{ 'text-blue-600 bg-blue-50': currentPath === '/about' }">
                    <i class="fas fa-info-circle w-6 mr-3"></i>
                    About
                </Link>
                <Link href="/pricing"
                    class="flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
                    @click="closeMobileMenu" :class="{ 'text-blue-600 bg-blue-50': currentPath === '/pricing' }">
                    <i class="fas fa-tags w-6 mr-3"></i>
                    Pricing
                </Link>
    
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <Link href="/login"
                        class="flex items-center justify-center px-6 py-3 bg-linear-to-r from-blue-600 to-blue-500 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md"
                        @click="closeMobileMenu">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const isMobileMenuOpen = ref(false)
const currentPath = computed(() => String(page.url).split('?')[0] || '/')

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

// Close mobile menu on navigation
watch(() => page.url, closeMobileMenu)
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
</style>
