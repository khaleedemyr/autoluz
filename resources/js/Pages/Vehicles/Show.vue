<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BrandLogo from '@/Components/Site/BrandLogo.vue';
import VehicleCard from '@/Components/Site/VehicleCard.vue';
import CompareToggleButton from '@/Components/Site/CompareToggleButton.vue';
import CreditSimCalculator from '@/Components/Site/CreditSimCalculator.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    brand: { type: Object, required: true },
    vehicle: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    creditDefaults: {
        type: Object,
        default: () => ({
            dp_percent: 20,
            tenor: 36,
            rate: 5.5,
            method: 'flat',
            tenor_options: [12, 24, 36, 48, 60],
        }),
    },
});

const vehicleCreditMeta = computed(() =>
    [props.brand?.name, props.vehicle?.body_type, props.vehicle?.model_year].filter(Boolean).join(' · '),
);

const { t } = useI18n();
const activeIndex = ref(0);
const lightbox = ref(false);

const gallery = computed(() => {
    const seen = new Set();
    const items = [];

    const push = (item) => {
        if (!item?.image_url || seen.has(item.image_url)) return;
        seen.add(item.image_url);
        items.push(item);
    };

    (props.vehicle.images || []).forEach((image) => push(image));
    if (props.vehicle.cover_image_url) {
        push({
            id: 'cover',
            image_url: props.vehicle.cover_image_url,
            caption: props.vehicle.name,
        });
    }

    // Keep cover first when present in list.
    const cover = props.vehicle.cover_image_url;
    if (cover && items.length > 1) {
        items.sort((a, b) => {
            if (a.image_url === cover) return -1;
            if (b.image_url === cover) return 1;
            return 0;
        });
    }

    return items;
});

const current = computed(() => gallery.value[activeIndex.value] || null);

watch(
    () => props.vehicle.id,
    () => {
        activeIndex.value = 0;
        lightbox.value = false;
    },
);

function prev() {
    if (!gallery.value.length) return;
    activeIndex.value = (activeIndex.value - 1 + gallery.value.length) % gallery.value.length;
}

function next() {
    if (!gallery.value.length) return;
    activeIndex.value = (activeIndex.value + 1) % gallery.value.length;
}

function onKey(event) {
    if (!gallery.value.length) return;
    if (event.key === 'ArrowLeft') prev();
    if (event.key === 'ArrowRight') next();
    if (event.key === 'Escape') lightbox.value = false;
}

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <AppLayout>
        <Head :title="`${vehicle.name} — ${brand.name}`" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-8 lg:py-12">
                <nav class="mb-4 flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                    <Link :href="route('brands.index')" class="hover:text-brand">{{ t('brands_title') }}</Link>
                    <span>/</span>
                    <Link :href="brand.url" class="hover:text-brand">{{ brand.name }}</Link>
                </nav>

                <div class="flex flex-wrap items-start gap-4">
                    <BrandLogo :brand="brand" size="md" />
                    <div class="min-w-0 flex-1">
                        <p class="section-label">
                            <span v-if="vehicle.body_type">{{ vehicle.body_type }}</span>
                            <span v-if="vehicle.model_year"> · {{ vehicle.model_year }}</span>
                        </p>
                        <h1 class="font-display mt-2 text-4xl tracking-[-0.04em] sm:text-5xl">{{ vehicle.name }}</h1>
                        <p v-if="vehicle.excerpt" class="mt-3 max-w-2xl text-neutral-600">{{ vehicle.excerpt }}</p>
                        <p v-if="vehicle.price_label" class="mt-4 text-lg font-semibold">
                            {{ t('vehicles_from') }} {{ vehicle.price_label }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <CompareToggleButton :vehicle="vehicle" />
                            <Link
                                v-if="vehicle.price_from"
                                :href="route('credit.simulate', { vehicle: vehicle.id })"
                                class="inline-flex items-center rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal transition hover:border-brand hover:text-brand"
                            >
                                {{ t('credit_nav') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-editorial py-10">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)]">
                <div>
                    <div v-if="gallery.length" class="space-y-3">
                        <div class="relative overflow-hidden rounded-2xl border border-[var(--line)] bg-neutral-100 shadow-soft">
                            <button
                                type="button"
                                class="block w-full"
                                @click="lightbox = true"
                            >
                                <img
                                    :src="current?.image_url"
                                    :alt="current?.caption || vehicle.name"
                                    class="max-h-[34rem] w-full object-cover"
                                />
                            </button>

                            <div
                                v-if="gallery.length > 1"
                                class="pointer-events-none absolute inset-x-0 top-1/2 flex -translate-y-1/2 justify-between px-3"
                            >
                                <button
                                    type="button"
                                    class="pointer-events-auto rounded-full bg-white/90 px-3 py-2 text-sm font-semibold text-charcoal shadow-soft hover:bg-white"
                                    @click.stop="prev"
                                >
                                    ‹
                                </button>
                                <button
                                    type="button"
                                    class="pointer-events-auto rounded-full bg-white/90 px-3 py-2 text-sm font-semibold text-charcoal shadow-soft hover:bg-white"
                                    @click.stop="next"
                                >
                                    ›
                                </button>
                            </div>

                            <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between gap-3">
                                <p v-if="current?.caption" class="rounded-full bg-charcoal/75 px-3 py-1 text-xs text-white">
                                    {{ current.caption }}
                                </p>
                                <p class="ml-auto rounded-full bg-charcoal/75 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-white">
                                    {{ activeIndex + 1 }} / {{ gallery.length }}
                                </p>
                            </div>
                        </div>

                        <div v-if="gallery.length > 1" class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                            <button
                                v-for="(image, index) in gallery"
                                :key="image.id || index"
                                type="button"
                                class="overflow-hidden rounded-xl border transition"
                                :class="activeIndex === index ? 'border-brand ring-1 ring-brand/30' : 'border-[var(--line)] opacity-80 hover:opacity-100'"
                                @click="activeIndex = index"
                            >
                                <img
                                    :src="image.image_url"
                                    :alt="image.caption || vehicle.name"
                                    class="aspect-[4/3] w-full object-cover"
                                    loading="lazy"
                                />
                            </button>
                        </div>

                        <p class="text-xs text-neutral-400">{{ t('vehicles_gallery_hint') }}</p>
                    </div>

                    <div v-if="vehicle.description_html" class="prose-article mt-10 max-w-3xl" v-html="vehicle.description_html" />
                </div>

                <aside class="space-y-4 lg:sticky lg:top-28 lg:self-start">
                    <div class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                        <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">{{ t('vehicles_specs') }}</h2>
                        <dl v-if="vehicle.specs?.length" class="mt-4 divide-y divide-[var(--line)]">
                            <div
                                v-for="(row, idx) in vehicle.specs"
                                :key="idx"
                                class="grid grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <dt class="text-sm text-neutral-500">{{ row.label }}</dt>
                                <dd class="text-right text-sm font-semibold leading-snug text-charcoal">{{ row.value }}</dd>
                            </div>
                        </dl>
                        <p v-else class="mt-3 text-sm text-neutral-500">{{ t('vehicles_specs_empty') }}</p>
                    </div>

                    <div v-if="vehicle.price_from" class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
                        <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                            {{ t('credit_widget_title') }}
                        </h2>
                        <div class="mt-4">
                            <CreditSimCalculator
                                compact
                                :initial-price="vehicle.price_from"
                                :vehicle-id="vehicle.id"
                                :vehicle-name="vehicle.name"
                                :vehicle-meta="vehicleCreditMeta"
                                :defaults="creditDefaults"
                            />
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section v-if="related.length" class="container-editorial pb-20">
            <h2 class="font-display text-3xl tracking-[-0.04em]">{{ t('vehicles_more') }}</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <VehicleCard v-for="item in related" :key="item.id" :vehicle="item" />
            </div>
        </section>

        <div
            v-if="lightbox && current"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
            @click.self="lightbox = false"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-full bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                @click="lightbox = false"
            >
                {{ t('vehicles_gallery_close') }}
            </button>
            <button
                v-if="gallery.length > 1"
                type="button"
                class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 px-3 py-2 text-white hover:bg-white/20"
                @click="prev"
            >
                ‹
            </button>
            <img
                :src="current.image_url"
                :alt="current.caption || vehicle.name"
                class="max-h-[90vh] max-w-full object-contain"
            />
            <button
                v-if="gallery.length > 1"
                type="button"
                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 px-3 py-2 text-white hover:bg-white/20"
                @click="next"
            >
                ›
            </button>
        </div>
    </AppLayout>
</template>
