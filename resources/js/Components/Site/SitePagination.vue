<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    links: { type: Array, default: () => [] },
});

const { paginationLabel } = useI18n();

const items = computed(() =>
    (props.links || []).map((link) => ({
        ...link,
        display: paginationLabel(link.label),
    })),
);
</script>

<template>
    <div v-if="items.length > 3" class="mt-10 flex flex-wrap gap-2">
        <component
            :is="link.url ? Link : 'span'"
            v-for="(link, index) in items"
            :key="`${link.display}-${index}`"
            :href="link.url || undefined"
            class="border px-3 py-1.5 text-sm transition"
            :class="[
                link.active ? 'border-brand bg-brand text-white' : 'border-[var(--line)]',
                link.url ? 'hover:border-brand' : 'cursor-not-allowed opacity-40',
            ]"
            preserve-scroll
        >
            {{ link.display }}
        </component>
    </div>
</template>
