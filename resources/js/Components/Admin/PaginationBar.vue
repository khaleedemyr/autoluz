<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    links: { type: Array, default: () => [] },
    from: { type: Number, default: null },
    to: { type: Number, default: null },
    total: { type: Number, default: null },
});

const { t, paginationLabel } = useI18n();

const items = computed(() =>
    (props.links || []).map((link) => ({
        ...link,
        display: paginationLabel(link.label),
    })),
);
</script>

<template>
    <div
        v-if="items.length > 3 || total != null"
        class="flex flex-wrap items-center justify-between gap-3 border-t border-black/5 px-4 py-3"
    >
        <p v-if="total != null" class="text-xs text-neutral-500">
            {{ t('pagination_showing', { from: from || 0, to: to || 0, total }) }}
        </p>
        <div v-if="items.length > 3" class="flex flex-wrap gap-1.5">
            <component
                :is="link.url ? Link : 'span'"
                v-for="(link, idx) in items"
                :key="`${link.display}-${idx}`"
                :href="link.url || undefined"
                class="rounded-lg px-3 py-1 text-xs"
                :class="[
                    link.active ? 'bg-brand text-white' : 'bg-mist text-neutral-600',
                    !link.url ? 'cursor-not-allowed opacity-40' : 'hover:opacity-90',
                ]"
                preserve-scroll
            >
                {{ link.display }}
            </component>
        </div>
    </div>
</template>
