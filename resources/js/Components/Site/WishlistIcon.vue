<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const { t } = useI18n();
const count = computed(() => Number(page.props.wishlist?.count || 0));
</script>

<template>
    <Link
        :href="route('shop.wishlist')"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-white/75 transition hover:bg-white/10 hover:text-white"
        :aria-label="t('shop_wishlist')"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-4.35-9.33-8.5C.8 9.7 1.6 5.9 5 4.6c1.9-.72 3.9-.1 5 1.4 1.1-1.5 3.1-2.12 5-1.4 3.4 1.3 4.2 5.1 2.33 7.9C18.5 16.65 12 21 12 21z" />
        </svg>
        <span
            v-if="count"
            class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[9px] font-bold leading-none text-white"
        >
            {{ count > 9 ? '9+' : count }}
        </span>
    </Link>
</template>
