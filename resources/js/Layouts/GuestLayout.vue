<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const pageTitle = computed(() => page.props?.title || '');
</script>

<template>
    <Head v-if="pageTitle" :title="pageTitle" />
    <div class="min-h-screen bg-gray-100">
        <div class="w-full">
            <slot />
        </div>
    </div>
</template>
