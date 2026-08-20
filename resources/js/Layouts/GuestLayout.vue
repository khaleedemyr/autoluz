<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import logoUrl from '@/brand/logo.png';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-[#f3f4f6] px-4 pt-8 sm:justify-center sm:pt-0">
        <Head>
            <link rel="icon" :href="logoUrl" type="image/png" />
            <link rel="apple-touch-icon" :href="logoUrl" />
        </Head>
        <div>
            <Link href="/" class="block transition hover:opacity-90">
                <ApplicationLogo />
            </Link>
        </div>

        <div class="mt-8 w-full overflow-hidden rounded-2xl border border-black/5 bg-white px-6 py-6 shadow-sm sm:max-w-md">
            <slot />
        </div>
    </div>
</template>
