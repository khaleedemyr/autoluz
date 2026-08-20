<script setup>
import AutoluzMark from '@/Components/Site/AutoluzMark.vue';

defineProps({
    variant: {
        type: String,
        default: 'full', // full | mark
    },
});
</script>

<template>
    <AutoluzMark
        v-if="variant === 'mark'"
        class="h-full w-full object-contain"
    />

    <div v-else class="inline-flex flex-col items-center text-center" aria-label="Autoluz">
        <AutoluzMark alt="" class="h-16 w-16 rounded-[1.1rem] shadow-soft" />
        <span class="mt-3 font-display text-3xl font-bold tracking-[-0.06em] text-charcoal">
            AUTO<span class="text-brand">LUZ</span>
        </span>
        <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.28em] text-neutral-400">
            Car &amp; Moto
        </span>
    </div>
</template>
