<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '', kategori: '', toko: '', sort: 'newest', min: '', max: '' }) },
});

const { t } = useI18n();
const q = ref(props.filters.q || '');
const kategori = ref(props.filters.kategori || '');
const toko = ref(props.filters.toko || '');
const sort = ref(props.filters.sort || 'newest');
const min = ref(props.filters.min || '');
const max = ref(props.filters.max || '');

watch([kategori, sort, toko], () => apply());

function apply() {
    router.get(route('shop.index'), {
        q: q.value || undefined,
        kategori: kategori.value || undefined,
        toko: toko.value || undefined,
        sort: sort.value || undefined,
        min: min.value || undefined,
        max: max.value || undefined,
    }, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('shop_title')" />

        <section class="relative overflow-hidden bg-charcoal text-white">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -right-16 top-0 h-64 w-64 rounded-full bg-brand/25 blur-3xl" />
                <div class="absolute -bottom-20 left-10 h-40 w-40 rounded-full bg-white/10 blur-2xl" />
            </div>
            <div class="container-editorial relative z-10 py-14 lg:py-20">
                <p class="section-label text-brand">{{ t('shop_label') }}</p>
                <h1 class="font-display mt-3 max-w-3xl text-5xl tracking-[-0.04em] sm:text-6xl lg:text-7xl">{{ t('shop_title') }}</h1>
                <p class="mt-4 max-w-xl text-sm leading-relaxed text-white/60 sm:text-base">{{ t('shop_desc') }}</p>
            </div>
        </section>

        <section class="container-editorial py-10">
            <form class="mb-8 grid gap-3 rounded-2xl border border-[var(--line)] bg-white/80 p-4 shadow-soft sm:grid-cols-2 lg:grid-cols-7" @submit.prevent="apply">
                <input v-model="q" type="search" :placeholder="t('shop_search')" class="rounded-xl border-black/10 sm:col-span-2" />
                <select v-model="kategori" class="rounded-xl border-black/10">
                    <option value="">{{ t('shop_all_categories') }}</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
                </select>
                <select v-model="toko" class="rounded-xl border-black/10">
                    <option value="">{{ t('shop_all_stores') }}</option>
                    <option v-for="store in stores" :key="store.id" :value="store.slug">{{ store.name }}</option>
                </select>
                <select v-model="sort" class="rounded-xl border-black/10">
                    <option value="newest">{{ t('shop_sort_newest') }}</option>
                    <option value="price_asc">{{ t('shop_sort_price_asc') }}</option>
                    <option value="price_desc">{{ t('shop_sort_price_desc') }}</option>
                    <option value="name">{{ t('shop_sort_name') }}</option>
                </select>
                <input v-model="min" type="number" min="0" :placeholder="t('shop_min_price')" class="rounded-xl border-black/10" />
                <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white">{{ t('shop_filter') }}</button>
            </form>

            <div v-if="products.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
            </div>
            <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-16 text-center text-sm text-neutral-500">
                {{ t('shop_empty') }}
            </p>
            <SitePagination :links="products.links" />
        </section>
    </AppLayout>
</template>
