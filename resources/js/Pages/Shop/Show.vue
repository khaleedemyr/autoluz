<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import ProductReviews from '@/Components/Site/ProductReviews.vue';
import StarRating from '@/Components/Site/StarRating.vue';
import { useI18n } from '@/composables/useI18n';
import { swalError, swalToast } from '@/utils/swal';
import { useShopActions } from '@/composables/useShopActions';

const props = defineProps({
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    reviews: { type: Object, default: () => ({ count: 0, items: [] }) },
});

const { t } = useI18n();
const { isWished, toggleWishlist } = useShopActions();
const gallery = computed(() => {
    const items = [];
    const seen = new Set();
    const push = (url, caption) => {
        if (!url || seen.has(url)) return;
        seen.add(url);
        items.push({ image_url: url, caption });
    };
    if (props.product.cover_image_url) push(props.product.cover_image_url, props.product.name);
    (props.product.images || []).forEach((img) => push(img.image_url, img.caption));
    return items;
});
const activeIndex = ref(0);
const current = computed(() => gallery.value[activeIndex.value] || null);
const selectedSize = ref(props.product.sizes?.[0] || '');
const selectedColor = ref(props.product.colors?.[0] || '');
const qty = ref(1);

const matching = computed(() => {
    const variants = props.product.variants || [];
    return variants.filter((row) => {
        if (selectedSize.value && row.size && row.size !== selectedSize.value) return false;
        if (selectedColor.value && row.color && row.color !== selectedColor.value) return false;
        return true;
    });
});

const selected = computed(() => matching.value[0] || props.product.variants?.[0] || null);
const adding = ref(false);

function addToCart() {
    if (!selected.value) return;
    adding.value = true;
    router.post(route('shop.cart.store'), {
        variant_id: selected.value.id,
        qty: qty.value,
    }, {
        preserveScroll: true,
        onSuccess: () => swalToast(t('shop_added')),
        onError: (errors) => swalError(errors.qty || errors.variant_id || t('shop_add_failed')),
        onFinish: () => { adding.value = false; },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="product.name" />

        <section class="container-editorial py-8 lg:py-14">
            <p class="mb-6 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                <Link :href="route('shop.index')" class="hover:text-brand">{{ t('shop_nav') }}</Link>
                <span v-if="product.store"> · <Link :href="product.store.url" class="hover:text-brand">{{ product.store.name }}</Link></span>
                <span v-if="product.category"> · {{ product.category.name }}</span>
            </p>

            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <div class="media-frame aspect-[4/5] overflow-hidden bg-neutral-100 sm:aspect-[5/4]">
                        <img v-if="current" :src="current.image_url" :alt="current.caption || product.name" class="h-full w-full object-cover" />
                    </div>
                    <div v-if="gallery.length > 1" class="mt-3 grid grid-cols-5 gap-2">
                        <button
                            v-for="(img, idx) in gallery"
                            :key="img.image_url"
                            type="button"
                            class="overflow-hidden rounded-xl border"
                            :class="idx === activeIndex ? 'border-brand' : 'border-[var(--line)]'"
                            @click="activeIndex = idx"
                        >
                            <img :src="img.image_url" alt="" class="aspect-square w-full object-cover" />
                        </button>
                    </div>
                </div>

                <div>
                    <p class="section-label">{{ product.category?.name || t('shop_label') }}</p>
                    <h1 class="font-display mt-3 text-4xl tracking-[-0.04em] sm:text-5xl">{{ product.name }}</h1>
                    <a v-if="reviews.count" href="#ulasan" class="mt-3 inline-flex items-center gap-2 text-sm text-neutral-600 hover:text-brand">
                        <StarRating :model-value="reviews.avg" />
                        <span class="font-semibold text-charcoal">{{ reviews.avg_label }}</span>
                        <span>({{ t('shop_reviews_count', { count: reviews.count }) }})</span>
                    </a>
                    <p class="mt-4 font-display text-3xl text-brand">{{ selected?.price_label || product.price_label }}</p>
                    <p v-if="product.excerpt" class="mt-4 text-neutral-600">{{ product.excerpt }}</p>

                    <div v-if="product.sizes?.length" class="mt-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ t('shop_size') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="size in product.sizes"
                                :key="size"
                                type="button"
                                class="min-w-12 rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em]"
                                :class="selectedSize === size ? 'bg-charcoal text-white' : 'border border-[var(--line)]'"
                                @click="selectedSize = size"
                            >
                                {{ size }}
                            </button>
                        </div>
                    </div>

                    <div v-if="product.colors?.length" class="mt-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ t('shop_color') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="color in product.colors"
                                :key="color"
                                type="button"
                                class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em]"
                                :class="selectedColor === color ? 'bg-charcoal text-white' : 'border border-[var(--line)]'"
                                @click="selectedColor = color"
                            >
                                {{ color }}
                            </button>
                        </div>
                    </div>

                    <p class="mt-5 text-sm text-neutral-500">
                        {{ selected?.in_stock ? t('shop_stock', { count: selected.stock }) : t('shop_out') }}
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <input v-model.number="qty" type="number" min="1" :max="selected?.stock || 1" class="w-20 rounded-xl border-black/10" />
                        <button
                            type="button"
                            class="rounded-full bg-brand px-6 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-white disabled:opacity-50"
                            :disabled="adding || !selected?.in_stock"
                            @click="addToCart"
                        >
                            {{ t('shop_add_cart') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-[var(--line)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] transition hover:border-brand hover:text-brand"
                            :class="isWished(product.id) ? 'border-brand text-brand' : ''"
                            @click="toggleWishlist(product)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" :fill="isWished(product.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-4.35-9.33-8.5C.8 9.7 1.6 5.9 5 4.6c1.9-.72 3.9-.1 5 1.4 1.1-1.5 3.1-2.12 5-1.4 3.4 1.3 4.2 5.1 2.33 7.9C18.5 16.65 12 21 12 21z" />
                            </svg>
                            {{ isWished(product.id) ? t('shop_wishlist_remove') : t('shop_wishlist_add') }}
                        </button>
                    </div>
                </div>
            </div>

            <article v-if="product.description_html" class="prose prose-neutral mt-12 max-w-none" v-html="product.description_html" />

            <ProductReviews :product="product" :reviews="reviews" />

            <div v-if="related.length" class="mt-16">
                <h2 class="font-display text-3xl tracking-[-0.04em]">{{ t('shop_related') }}</h2>
                <div class="mt-6 grid grid-cols-2 gap-x-3 gap-y-8 sm:grid-cols-3 sm:gap-x-5 sm:gap-y-10 xl:grid-cols-4">
                    <ProductCard v-for="item in related" :key="item.id" :product="item" />
                </div>
            </div>
        </section>
    </AppLayout>
</template>
