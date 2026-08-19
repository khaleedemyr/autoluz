<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

defineProps({
    product: { type: Object, required: true },
    hideStore: { type: Boolean, default: false },
});

const { t } = useI18n();
</script>

<template>
    <Link
        :href="product.url || route('shop.show', product.slug)"
        class="group flex h-full flex-col overflow-hidden rounded-[1.15rem] bg-white ring-1 ring-black/[0.06] transition duration-300 hover:-translate-y-0.5 hover:ring-black/15 hover:shadow-[0_18px_40px_-24px_rgba(10,11,13,0.45)]"
    >
        <div class="relative aspect-[3/4] overflow-hidden bg-[#111]">
            <img
                v-if="product.cover_image_url"
                :src="product.cover_image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]"
                loading="lazy"
            />
            <div v-else class="flex h-full items-center justify-center px-4 text-center text-sm text-white/40">{{ product.name }}</div>
            <span
                v-if="!product.in_stock"
                class="absolute right-2.5 top-2.5 rounded-full bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-charcoal"
            >
                {{ t('shop_out') }}
            </span>
        </div>
        <div class="flex flex-1 flex-col px-3.5 py-3.5">
            <p v-if="!hideStore && product.store?.name" class="truncate text-[11px] font-medium text-neutral-400">
                {{ product.store.name }}
            </p>
            <h3 class="mt-0.5 line-clamp-2 min-h-[2.6em] text-[15px] font-semibold leading-snug tracking-[-0.02em] text-charcoal">
                {{ product.name }}
            </h3>
            <p class="mt-auto pt-2.5 text-[15px] font-semibold tracking-[-0.02em] text-charcoal">{{ product.price_label || '—' }}</p>
        </div>
    </Link>
</template>
