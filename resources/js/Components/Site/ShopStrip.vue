<script setup>
import { Link } from '@inertiajs/vue3';
import ProductCard from '@/Components/Site/ProductCard.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    products: { type: Array, default: () => [] },
});

const { t } = useI18n();
</script>

<template>
    <section v-if="products.length" class="container-editorial py-10 lg:py-14">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="section-label">{{ t('shop_label') }}</p>
                <h2 class="font-display mt-3 text-4xl tracking-[-0.04em] sm:text-5xl">{{ t('shop_home_title') }}</h2>
                <p class="mt-2 max-w-xl text-sm text-neutral-600">{{ t('shop_home_desc') }}</p>
            </div>
            <Link
                :href="route('shop.index')"
                class="inline-flex items-center gap-2 rounded-full border border-charcoal px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.16em] transition hover:border-brand hover:bg-brand hover:text-white"
            >
                {{ t('shop_see_all') }}
                <span aria-hidden="true">→</span>
            </Link>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>
    </section>
</template>
