<script setup>
defineProps({
    brand: { type: Object, required: true },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const sizeClass = {
    sm: 'h-10 w-10 p-1.5',
    md: 'h-14 w-14 p-2.5',
    lg: 'h-20 w-20 p-3',
};
</script>

<template>
    <div
        class="flex shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft"
        :class="sizeClass[size] || sizeClass.md"
    >
        <img
            v-if="brand.logo_url"
            :src="brand.logo_url"
            :alt="brand.name"
            class="h-full w-full object-contain"
            loading="lazy"
        />
        <span
            v-else
            class="text-sm font-bold uppercase tracking-wide text-charcoal"
        >
            {{ (brand.name || '?').slice(0, 1) }}
        </span>
    </div>
</template>
