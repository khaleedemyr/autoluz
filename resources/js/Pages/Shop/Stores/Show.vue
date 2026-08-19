<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    store: { type: Object, required: true },
    products: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="store.name" />

        <section class="relative overflow-hidden bg-charcoal text-white">
            <div class="container-editorial relative z-10 py-14 lg:py-20">
                <p class="section-label text-brand">{{ store.is_official ? t('shop_official') : t('shop_partner') }}</p>
                <div class="mt-4 flex items-center gap-4">
                    <img v-if="store.logo_url" :src="store.logo_url" :alt="store.name" class="h-16 w-16 rounded-2xl object-cover" />
                    <div>
                        <h1 class="font-display text-5xl tracking-[-0.04em] sm:text-6xl">{{ store.name }}</h1>
                        <p v-if="store.tagline" class="mt-2 text-sm text-white/60">{{ store.tagline }}</p>
                    </div>
                </div>
                <p v-if="store.description" class="mt-4 max-w-xl text-sm leading-relaxed text-white/60">{{ store.description }}</p>
                <p v-if="store.origin_city_name" class="mt-3 text-xs uppercase tracking-[0.14em] text-white/40">{{ store.origin_city_name }}</p>
            </div>
        </section>

        <section class="container-editorial py-10">
            <div v-if="products.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ t('shop_empty') }}
            </p>
            <SitePagination :links="products.links" />
            <Link :href="route('shop.index')" class="mt-8 inline-block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500 hover:text-brand">
                ← {{ t('shop_see_all') }}
            </Link>
        </section>
    </AppLayout>
</template>
