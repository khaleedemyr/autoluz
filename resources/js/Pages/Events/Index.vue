<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EventCountdown from '@/Components/Site/EventCountdown.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
    hero: { type: Object, default: null },
});

const { t, formatEventDate, locale } = useI18n();

const eventTz = { timeZone: 'Asia/Jakarta' };

function dayPart(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', { day: '2-digit', ...eventTz }).format(new Date(value));
}

function monthPart(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', { month: 'short', ...eventTz }).format(new Date(value));
}

function timePart(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        ...eventTz,
    }).format(new Date(value));
}
</script>

<template>
    <AppLayout>
        <Head :title="t('events_title')" />

        <section class="relative overflow-hidden bg-charcoal text-white">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <img
                    v-if="hero?.cover_image_url"
                    :src="hero.cover_image_url"
                    alt=""
                    class="h-full w-full scale-110 object-cover blur-2xl brightness-75 saturate-125"
                />
                <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-charcoal/80 to-paper" />
            </div>

            <div class="container-editorial relative z-10 pb-16 pt-14 lg:pb-20 lg:pt-20">
                <p class="section-label text-brand">{{ t('events_label') }}</p>
                <h1 class="mt-3 max-w-3xl font-display text-5xl tracking-[-0.05em] sm:text-7xl">
                    {{ t('events_title') }}
                </h1>
                <p class="mt-4 max-w-2xl text-base text-white/65">{{ t('events_page_desc') }}</p>

                <Link
                    v-if="hero"
                    :href="hero.url || route('events.show', hero.slug)"
                    class="group mt-10 grid overflow-hidden rounded-[1.75rem] border border-white/10 bg-black/35 shadow-lift backdrop-blur-md lg:grid-cols-[1.2fr_1fr]"
                >
                    <div class="relative min-h-[16rem] overflow-hidden lg:min-h-[24rem]">
                        <img
                            v-if="hero.cover_image_url"
                            :src="hero.cover_image_url"
                            :alt="hero.title"
                            class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                        />
                        <div v-else class="h-full w-full bg-charcoal-mute" />
                    </div>
                    <div class="flex flex-col justify-between p-6 sm:p-8">
                        <div>
                            <span class="inline-flex rounded-full bg-brand px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]">
                                {{ t('events_next') }}
                            </span>
                            <h2 class="mt-4 font-display text-3xl leading-[1.05] tracking-[-0.04em] sm:text-4xl">
                                {{ hero.title }}
                            </h2>
                            <p v-if="hero.excerpt" class="mt-3 text-sm leading-relaxed text-white/65 line-clamp-3">
                                {{ hero.excerpt }}
                            </p>
                        </div>
                        <div class="mt-8 space-y-5">
                            <EventCountdown v-if="hero.starts_at" :target="hero.starts_at" />
                            <div class="flex flex-wrap gap-6 text-[11px] font-semibold uppercase tracking-[0.14em] text-white/50">
                                <span>{{ formatEventDate(hero.starts_at, { month: 'long', hour: '2-digit', minute: '2-digit' }) }}</span>
                                <span v-if="hero.city || hero.location">{{ hero.city || hero.location }}</span>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </section>

        <section class="container-editorial -mt-6 pb-16">
            <div class="mb-7 flex items-end justify-between gap-4">
                <div>
                    <p class="section-label">{{ t('events_upcoming_label') }}</p>
                    <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('events_upcoming_title') }}</h2>
                </div>
            </div>

            <div v-if="upcoming.length" class="space-y-4">
                <Link
                    v-for="event in upcoming"
                    :key="event.id"
                    :href="event.url || route('events.show', event.slug)"
                    class="group grid gap-4 overflow-hidden rounded-[1.35rem] border border-[var(--line)] bg-white/85 p-3 shadow-soft transition duration-300 hover:-translate-y-0.5 hover:shadow-lift sm:grid-cols-[6.5rem_minmax(0,1fr)_auto] sm:p-4"
                >
                    <div class="flex aspect-square flex-col items-center justify-center rounded-2xl bg-charcoal text-white sm:aspect-auto sm:h-full">
                        <span class="font-display text-3xl leading-none tracking-[-0.04em]">{{ dayPart(event.starts_at) }}</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-brand">{{ monthPart(event.starts_at) }}</span>
                        <span class="mt-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-white/45">{{ timePart(event.starts_at) }}</span>
                    </div>
                    <div class="min-w-0 self-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ [event.venue, event.city || event.location].filter(Boolean).join(' · ') || '—' }}
                        </p>
                        <h3 class="mt-1 font-display text-2xl tracking-[-0.03em] transition group-hover:text-brand">
                            {{ event.title }}
                        </h3>
                        <p v-if="event.excerpt" class="mt-2 line-clamp-2 text-sm text-neutral-600">{{ event.excerpt }}</p>
                    </div>
                    <div class="hidden items-center pr-2 sm:flex">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand">
                            {{ t('events_detail') }} →
                        </span>
                    </div>
                </Link>
            </div>
            <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-12 text-center text-sm text-neutral-500">
                {{ t('events_empty') }}
            </p>
        </section>

        <section v-if="past.length" class="border-t border-[var(--line)] bg-white/60 py-14">
            <div class="container-editorial">
                <p class="section-label">{{ t('events_past_label') }}</p>
                <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('events_past_title') }}</h2>
                <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="event in past"
                        :key="event.id"
                        :href="event.url || route('events.show', event.slug)"
                        class="group overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift"
                    >
                        <div class="media-frame aspect-[16/10] bg-charcoal-mute">
                            <img
                                v-if="event.cover_image_url"
                                :src="event.cover_image_url"
                                :alt="event.title"
                                class="h-full w-full object-cover grayscale transition duration-500 group-hover:grayscale-0"
                                loading="lazy"
                            />
                        </div>
                        <div class="p-4">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                {{ formatEventDate(event.starts_at) }}
                            </p>
                            <h3 class="mt-1 line-clamp-2 text-sm font-semibold leading-snug group-hover:text-brand">
                                {{ event.title }}
                            </h3>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
