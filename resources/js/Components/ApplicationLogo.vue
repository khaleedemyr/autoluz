<script setup>
defineProps({
    variant: {
        type: String,
        default: 'full', // full | mark
    },
});
</script>

<template>
    <svg
        v-if="variant === 'mark'"
        viewBox="0 0 64 64"
        xmlns="http://www.w3.org/2000/svg"
        aria-label="Autoluz"
        role="img"
    >
        <rect width="64" height="64" rx="14" fill="#16181d" />
        <path
            d="M18 46 L32 16 L46 46"
            fill="none"
            stroke="#FF1E2D"
            stroke-width="6"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M24 36 H40"
            fill="none"
            stroke="#ffffff"
            stroke-width="5"
            stroke-linecap="round"
        />
    </svg>

    <div v-else class="inline-flex flex-col items-center text-center" aria-label="Autoluz">
        <svg
            class="h-14 w-14"
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <rect width="64" height="64" rx="14" fill="#16181d" />
            <path
                d="M18 46 L32 16 L46 46"
                fill="none"
                stroke="#FF1E2D"
                stroke-width="6"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path
                d="M24 36 H40"
                fill="none"
                stroke="#ffffff"
                stroke-width="5"
                stroke-linecap="round"
            />
        </svg>
        <span class="mt-3 font-display text-3xl font-bold tracking-[-0.06em] text-charcoal">
            AUTO<span class="text-brand">LUZ</span>
        </span>
        <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.28em] text-neutral-400">
            Car &amp; Moto
        </span>
    </div>
</template>
