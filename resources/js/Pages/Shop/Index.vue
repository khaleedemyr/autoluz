<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import ShopFilterBar from '@/Components/Site/ShopFilterBar.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('shop_title')" />

        <section class="border-b border-[var(--line)] bg-charcoal text-white">
            <div class="container-editorial flex flex-col justify-end gap-6 py-10 lg:flex-row lg:items-end lg:justify-between lg:py-14">
                <div class="max-w-2xl">
                    <p class="section-label text-brand">{{ t('shop_label') }}</p>
                    <h1 class="font-display mt-3 text-4xl tracking-[-0.05em] sm:text-6xl">{{ t('shop_title') }}</h1>
                    <p class="mt-3 max-w-lg text-sm leading-relaxed text-white/55">{{ t('shop_desc') }}</p>
                </div>
                <p class="hidden max-w-xs text-right text-xs leading-relaxed text-white/40 lg:block">{{ t('shop_split_hint') }}</p>
            </div>
        </section>

        <ShopFilterBar
            :categories="categories"
            :stores="stores"
            :filters="filters"
            :total="products.total || 0"
            route-name="shop.index"
        />

        <section class="container-editorial py-8 lg:py-10">
            <div v-if="products.data?.length" class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4 lg:gap-5">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ filters.q || filters.kategori || filters.toko || filters.min || filters.max ? t('shop_no_results') : t('shop_empty') }}
            </p>
            <SitePagination :links="products.links" />
        </section>
    </AppLayout>
</template>
