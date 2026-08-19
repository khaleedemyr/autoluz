<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const { t } = useI18n();
const count = computed(() => Number(page.props.cart?.count || 0));
</script>

<template>
    <Link
        :href="route('shop.cart')"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white"
        :aria-label="t('shop_cart')"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M10 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
        </svg>
        <span
            v-if="count"
            class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[9px] font-bold leading-none text-white"
        >
            {{ count > 9 ? '9+' : count }}
        </span>
    </Link>
</template>
