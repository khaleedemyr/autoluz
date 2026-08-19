<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    product: { type: Object, required: true },
});
</script>

<template>
    <Link
        :href="product.url || route('shop.show', product.slug)"
        class="group flex h-full flex-col overflow-hidden rounded-[1.35rem] border border-[var(--line)] bg-white shadow-soft transition duration-500 ease-editorial hover:-translate-y-1 hover:border-brand/25 hover:shadow-lift"
    >
        <div class="media-frame relative aspect-[4/5] overflow-hidden bg-neutral-100">
            <img
                v-if="product.cover_image_url"
                :src="product.cover_image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex h-full items-center justify-center text-sm text-neutral-400">{{ product.name }}</div>
            <span
                v-if="product.category?.name"
                class="absolute left-3 top-3 rounded-full bg-charcoal/85 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white"
            >
                {{ product.category.name }}
            </span>
            <span
                v-if="!product.in_stock"
                class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-charcoal"
            >
                Sold out
            </span>
        </div>
        <div class="flex flex-1 flex-col p-4 sm:p-5">
            <h3 class="font-display text-xl tracking-[-0.03em] text-charcoal transition group-hover:text-brand">{{ product.name }}</h3>
            <p v-if="product.excerpt" class="mt-2 line-clamp-2 text-sm text-neutral-500">{{ product.excerpt }}</p>
            <p class="mt-auto pt-4 text-sm font-semibold text-charcoal">{{ product.price_label || '—' }}</p>
        </div>
    </Link>
</template>
