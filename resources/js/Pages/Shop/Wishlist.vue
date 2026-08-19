<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    products: { type: Array, default: () => [] },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('shop_wishlist')" />

        <section class="container-editorial py-10 lg:py-14">
            <p class="section-label">{{ t('shop_label') }}</p>
            <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('shop_wishlist') }}</h1>
            <p class="mt-3 max-w-lg text-sm text-neutral-500">{{ t('shop_wishlist_desc') }}</p>

            <div v-if="products.length" class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 sm:gap-y-10 xl:grid-cols-4">
                <ProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>

            <p v-else class="mt-10 rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ t('shop_wishlist_empty') }}
                <Link :href="route('shop.index')" class="ml-1 text-brand">{{ t('shop_see_all') }}</Link>
            </p>
        </section>
    </AppLayout>
</template>
