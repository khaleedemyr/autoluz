<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import EventCountdown from '@/Components/Site/EventCountdown.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    events: { type: Array, default: () => [] },
});

const { t, formatEventDate, locale } = useI18n();

const nearestId = computed(() => {
    const upcoming = [...(props.events || [])]
        .filter((event) => event.starts_at && new Date(event.starts_at).getTime() >= Date.now() - 60_000)
        .sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at));

    return upcoming[0]?.id ?? props.events?.[0]?.id ?? null;
});

const eventTz = { timeZone: 'Asia/Jakarta' };

function dayPart(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', {
        day: '2-digit',
        ...eventTz,
    }).format(new Date(value));
}

function monthPart(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', {
        month: 'short',
        ...eventTz,
    }).format(new Date(value));
}
</script>

<template>
    <section class="container-editorial pb-4 pt-4 lg:pb-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="section-label">{{ t('events_label') }}</p>
                <h2 class="font-display mt-3 text-4xl tracking-[-0.04em] sm:text-5xl">{{ t('events_title') }}</h2>
                <p class="mt-2 max-w-xl text-sm text-neutral-600">{{ t('events_home_desc') }}</p>
            </div>
            <Link
                :href="route('events.index')"
                class="inline-flex items-center gap-2 rounded-full border border-charcoal px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-charcoal transition hover:border-brand hover:bg-brand hover:text-white"
            >
                {{ t('events_see_all') }}
                <span aria-hidden="true">→</span>
            </Link>
        </div>

        <div v-if="events.length" class="grid gap-4 md:grid-cols-3">
            <Link
                v-for="(event, index) in events"
                :key="event.id"
                :href="event.url || route('events.show', event.slug)"
                class="group relative overflow-hidden rounded-[1.35rem] border border-[var(--line)] bg-charcoal text-white shadow-soft transition duration-500 ease-editorial hover:-translate-y-1 hover:shadow-lift"
                :class="index === 0 || event.id === nearestId ? 'md:col-span-2 md:min-h-[22rem]' : 'min-h-[18rem]'"
            >
                <img
                    v-if="event.cover_image_url"
                    :src="event.cover_image_url"
                    :alt="event.title"
                    class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105"
                    loading="lazy"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/55 to-black/10" />
                <div class="relative z-10 flex h-full flex-col justify-between p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="inline-flex w-fit flex-col items-center rounded-2xl bg-white/95 px-3 py-2 text-charcoal shadow-soft">
                            <span class="font-display text-2xl leading-none tracking-[-0.04em]">{{ dayPart(event.starts_at) }}</span>
                            <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-brand">{{ monthPart(event.starts_at) }}</span>
                        </div>
                        <EventCountdown
                            v-if="event.id === nearestId && event.starts_at"
                            :target="event.starts_at"
                            compact
                        />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55">
                            {{ event.city || event.location || formatEventDate(event.starts_at) }}
                        </p>
                        <h3
                            class="mt-2 font-display leading-[1.05] tracking-[-0.03em] transition group-hover:text-brand"
                            :class="event.id === nearestId || index === 0 ? 'text-3xl sm:text-4xl' : 'text-2xl'"
                        >
                            {{ event.title }}
                        </h3>
                        <p v-if="event.excerpt && (event.id === nearestId || index === 0)" class="mt-3 max-w-lg text-sm text-white/70 line-clamp-2">
                            {{ event.excerpt }}
                        </p>
                    </div>
                </div>
            </Link>
        </div>
        <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-12 text-center text-sm text-neutral-500">
            {{ t('events_empty') }}
        </p>
    </section>
</template>
