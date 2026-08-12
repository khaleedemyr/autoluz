<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CompareToggleButton from '@/Components/Site/CompareToggleButton.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    vehicle: { type: Object, required: true },
});

const { t } = useI18n();

const keySpecs = computed(() => (props.vehicle.highlight_specs || []).slice(0, 4));
</script>

<template>
    <div class="group flex h-full flex-col overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft transition hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-lift">
        <div class="media-frame relative aspect-[16/10] shrink-0 overflow-hidden bg-neutral-100">
            <Link :href="vehicle.url" class="absolute inset-0 block">
                <img
                    v-if="vehicle.cover_image_url"
                    :src="vehicle.cover_image_url"
                    :alt="vehicle.name"
                    class="h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.03]"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex h-full items-center justify-center bg-gradient-to-br from-neutral-100 to-neutral-200 text-sm font-semibold text-neutral-400"
                >
                    {{ vehicle.name }}
                </div>
            </Link>
            <span
                v-if="vehicle.body_type"
                class="pointer-events-none absolute left-3 top-3 rounded-full bg-charcoal/85 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white"
            >
                {{ vehicle.body_type }}
            </span>
            <div class="absolute right-3 top-3">
                <CompareToggleButton :vehicle="vehicle" compact />
            </div>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-5">
            <Link :href="vehicle.url" class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                    {{ vehicle.brand?.name || '' }}
                    <span v-if="vehicle.model_year"> · {{ vehicle.model_year }}</span>
                </p>
                <h3 class="mt-1 font-display text-xl tracking-[-0.03em] text-charcoal group-hover:text-brand sm:text-2xl">
                    {{ vehicle.name }}
                </h3>
                <p v-if="vehicle.excerpt" class="mt-2 line-clamp-2 min-h-[2.5rem] text-sm leading-relaxed text-neutral-600">
                    {{ vehicle.excerpt }}
                </p>
            </Link>

            <ul
                v-if="keySpecs.length"
                class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-[var(--line)] bg-[var(--line)]"
            >
                <li
                    v-for="(row, idx) in keySpecs"
                    :key="idx"
                    class="bg-white px-3 py-2.5"
                >
                    <p class="truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                        {{ row.label }}
                    </p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-charcoal" :title="row.value">
                        {{ row.value }}
                    </p>
                </li>
            </ul>

            <div class="mt-auto flex items-center justify-between gap-3 border-t border-[var(--line)] pt-4 mt-4">
                <p v-if="vehicle.price_label" class="truncate text-sm font-semibold text-charcoal">
                    {{ t('vehicles_from') }} {{ vehicle.price_label }}
                </p>
                <span v-else class="text-sm text-neutral-400">—</span>
                <Link :href="vehicle.url" class="shrink-0 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand">
                    {{ t('vehicles_detail') }} →
                </Link>
            </div>
        </div>
    </div>
</template>
