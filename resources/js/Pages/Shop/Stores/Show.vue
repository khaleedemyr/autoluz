<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import ShopFilterBar from '@/Components/Site/ShopFilterBar.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    store: { type: Object, required: true },
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="store.name" />

        <section class="relative overflow-hidden bg-charcoal text-white">
            <img
                v-if="store.cover_url"
                :src="store.cover_url"
                alt=""
                class="absolute inset-0 h-full w-full scale-110 object-cover opacity-30 blur-xl"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-charcoal via-charcoal/80 to-charcoal/40" />
            <div class="container-editorial relative z-10 py-10 lg:py-14">
                <Link :href="route('shop.index')" class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/45 hover:text-white">
                    ← {{ t('shop_nav') }}
                </Link>
                <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-end">
                    <div class="h-24 w-24 shrink-0 overflow-hidden rounded-[1.4rem] bg-white/10 ring-1 ring-white/15 sm:h-28 sm:w-28">
                        <img v-if="store.logo_url" :src="store.logo_url" :alt="store.name" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em]" :class="store.is_official ? 'bg-brand text-white' : 'bg-white/10 text-white/80'">
                                {{ store.is_official ? t('shop_official') : t('shop_partner') }}
                            </span>
                            <span v-if="store.origin_city_name" class="text-[11px] uppercase tracking-[0.14em] text-white/40">
                                {{ t('shop_ship_from') }} {{ store.origin_city_name }}
                            </span>
                        </div>
                        <h1 class="font-display mt-2 text-4xl tracking-[-0.05em] sm:text-6xl">{{ store.name }}</h1>
                        <p v-if="store.tagline" class="mt-2 text-sm text-white/60">{{ store.tagline }}</p>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/40">
                        {{ t('shop_products_count', { count: store.products_count || products.total || 0 }) }}
                    </p>
                </div>
                <p v-if="store.description" class="mt-5 max-w-2xl text-sm leading-relaxed text-white/50">{{ store.description }}</p>
            </div>
        </section>

        <ShopFilterBar
            :categories="categories"
            :filters="filters"
            :total="products.total || 0"
            :show-stores="false"
            route-name="shop.stores.show"
            :route-params="store.slug"
        />

        <section class="container-editorial py-8 lg:py-10">
            <div v-if="products.data?.length" class="grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 sm:gap-y-10 xl:grid-cols-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" hide-store />
            </div>
            <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ filters.q || filters.kategori || filters.min || filters.max ? t('shop_no_results') : t('shop_empty') }}
            </p>
            <SitePagination :links="products.links" />
        </section>
    </AppLayout>
</template>
