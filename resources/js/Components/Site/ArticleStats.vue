<script setup>
defineProps({
    views: { type: Number, default: 0 },
    comments: { type: Number, default: 0 },
    light: { type: Boolean, default: false },
});

function formatCount(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
}
</script>

<template>
    <div
        class="inline-flex flex-wrap items-center gap-3 text-[10px] font-semibold uppercase tracking-[0.14em]"
        :class="light ? 'text-white/55' : 'text-neutral-400'"
    >
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            {{ formatCount(views) }}
        </span>
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
            </svg>
            {{ formatCount(comments) }}
        </span>
    </div>
</template>
