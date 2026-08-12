<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreditSimCalculator from '@/Components/Site/CreditSimCalculator.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    vehicle: { type: Object, default: null },
    defaults: {
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

const { t } = useI18n();

const vehicleMeta = computed(() => {
    if (!props.vehicle) return '';
    return [props.vehicle.brand?.name, props.vehicle.body_type, props.vehicle.model_year]
        .filter(Boolean)
        .join(' · ');
});
</script>

<template>
    <AppLayout>
        <Head :title="t('credit_title')" />

        <section class="relative overflow-hidden border-b border-[var(--line)] bg-[#0a0b0d] text-white">
            <div
                class="pointer-events-none absolute inset-0"
                style="background:
                    radial-gradient(ellipse 55% 70% at 0% 0%, rgba(255,30,45,0.22), transparent 50%),
                    radial-gradient(ellipse 40% 50% at 100% 100%, rgba(255,255,255,0.06), transparent 45%),
                    linear-gradient(180deg, #12141a 0%, #0a0b0d 100%);"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-20"
                style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.06) 1px, transparent 0); background-size: 22px 22px;"
            />

            <div class="container-editorial relative z-10 py-12 lg:py-16">
                <p class="section-label text-brand">{{ t('credit_label') }}</p>
                <h1 class="font-display mt-3 max-w-3xl text-4xl tracking-[-0.04em] sm:text-5xl lg:text-6xl">
                    {{ t('credit_title') }}
                </h1>
                <p class="mt-4 max-w-xl text-sm leading-relaxed text-white/55 sm:text-base">
                    {{ t('credit_desc') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a
                        href="#kredit-calculator"
                        class="inline-flex items-center rounded-full bg-brand px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white transition hover:bg-[var(--brand-red-dark)]"
                    >
                        {{ t('credit_cta') }}
                    </a>
                    <Link
                        :href="route('brands.index')"
                        class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80 transition hover:border-white/30 hover:bg-white/10"
                    >
                        {{ t('brands_nav') }}
                    </Link>
                </div>
            </div>
        </section>

        <section id="kredit-calculator" class="container-editorial scroll-mt-24 py-10 lg:py-14">
            <CreditSimCalculator
                :show-picker="true"
                :initial-price="vehicle?.price_from || 0"
                :vehicle-id="vehicle?.id || null"
                :vehicle-name="vehicle?.name || ''"
                :vehicle-meta="vehicleMeta"
                :vehicle-url="vehicle?.url || null"
                :cover-image-url="vehicle?.cover_image_url || null"
                :defaults="defaults"
            />
        </section>
    </AppLayout>
</template>
