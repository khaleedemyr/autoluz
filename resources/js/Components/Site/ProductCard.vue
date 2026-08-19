<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { useShopActions } from '@/composables/useShopActions';
import StarRating from '@/Components/Site/StarRating.vue';

const props = defineProps({
    product: { type: Object, required: true },
    hideStore: { type: Boolean, default: false },
});

const { t } = useI18n();
const { busy, isWished, toggleWishlist, addToCart } = useShopActions();

function onWish(event) {
    event.preventDefault();
    event.stopPropagation();
    toggleWishlist(props.product);
}

function onCart(event) {
    event.preventDefault();
    event.stopPropagation();
    addToCart(props.product);
}
</script>

<template>
    <article class="group flex flex-col">
        <div class="media-frame relative aspect-[4/5] bg-[#111] shadow-soft">
            <Link :href="product.url || route('shop.show', product.slug)" class="absolute inset-0 block">
                <img
                    v-if="product.cover_image_url"
                    :src="product.cover_image_url"
                    :alt="product.name"
                    class="h-full w-full object-cover transition duration-700 ease-editorial group-hover:scale-[1.05]"
                    loading="lazy"
                />
                <div v-else class="flex h-full items-center justify-center px-4 text-center text-sm text-white/35">
                    {{ product.name }}
                </div>
            </Link>

            <span
                v-if="product.category?.name"
                class="pointer-events-none absolute left-3 top-3 rounded-full bg-white/92 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-charcoal backdrop-blur-sm"
            >
                {{ product.category.name }}
            </span>
            <span
                v-if="!product.in_stock"
                class="pointer-events-none absolute left-3 top-10 rounded-full bg-charcoal/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white"
            >
                {{ t('shop_out') }}
            </span>

            <div class="absolute right-3 top-3 z-10 flex flex-col gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-charcoal shadow-soft backdrop-blur-sm transition hover:scale-105 hover:text-brand"
                    :class="isWished(product.id) ? 'text-brand' : ''"
                    :disabled="busy[`wish-${product.id}`]"
                    :aria-label="isWished(product.id) ? t('shop_wishlist_remove') : t('shop_wishlist_add')"
                    @click="onWish"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" :fill="isWished(product.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.5-4.35-9.33-8.5C.8 9.7 1.6 5.9 5 4.6c1.9-.72 3.9-.1 5 1.4 1.1-1.5 3.1-2.12 5-1.4 3.4 1.3 4.2 5.1 2.33 7.9C18.5 16.65 12 21 12 21z" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white shadow-soft transition hover:scale-105 disabled:cursor-not-allowed disabled:bg-neutral-300"
                    :disabled="!product.in_stock || !product.default_variant_id || busy[`cart-${product.id}`]"
                    :aria-label="t('shop_add_cart')"
                    @click="onCart"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M10 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <Link :href="product.url || route('shop.show', product.slug)" class="flex flex-1 flex-col pt-3.5">
            <p
                v-if="!hideStore && product.store?.name"
                class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-neutral-400"
            >
                <img
                    v-if="product.store.logo_url"
                    :src="product.store.logo_url"
                    alt=""
                    class="h-4 w-4 rounded-full object-cover ring-1 ring-black/10"
                />
                <span class="truncate">{{ product.store.name }}</span>
            </p>
            <h3 class="mt-1 font-display text-[1.05rem] leading-[1.15] tracking-[-0.03em] text-charcoal transition group-hover:text-brand sm:text-xl">
                {{ product.name }}
            </h3>
            <p v-if="product.reviews_count" class="mt-1.5 flex items-center gap-1.5 text-xs text-neutral-500">
                <StarRating :model-value="product.rating_avg" size="sm" />
                <span class="font-semibold text-charcoal">{{ Number(product.rating_avg).toFixed(1) }}</span>
                <span>({{ product.reviews_count }})</span>
            </p>
            <p class="mt-2 font-display text-lg tracking-[-0.03em]" :class="product.in_stock ? 'text-brand' : 'text-neutral-400'">
                {{ product.price_label || '—' }}
            </p>
        </Link>
    </article>
</template>
