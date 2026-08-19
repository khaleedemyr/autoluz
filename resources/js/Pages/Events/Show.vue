<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShareButtons from '@/Components/Site/ShareButtons.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    event: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const { t, formatEventDate } = useI18n();

const shareUrl = computed(() => props.event.url || (typeof window !== 'undefined' ? window.location.href : ''));
const pageDescription = computed(() => props.event.excerpt || props.event.title);

const jsonLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Event',
        name: props.event.title,
        description: pageDescription.value,
        image: props.event.cover_image_url || undefined,
        startDate: props.event.starts_at,
        endDate: props.event.ends_at || undefined,
        eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
        location: {
            '@type': 'Place',
            name: props.event.venue || props.event.location || props.event.city || 'Autoluz',
            address: [props.event.location, props.event.city].filter(Boolean).join(', ') || undefined,
        },
        url: shareUrl.value,
    }),
);
</script>

<template>
    <AppLayout>
        <Head :title="event.title">
            <meta name="description" :content="pageDescription" />
            <link v-if="event.url" rel="canonical" :href="event.url" />
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="event.title" />
            <meta property="og:description" :content="pageDescription" />
            <meta v-if="event.url" property="og:url" :content="event.url" />
            <meta v-if="event.cover_image_url" property="og:image" :content="event.cover_image_url" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="event.title" />
            <meta name="twitter:description" :content="pageDescription" />
            <component :is="'script'" type="application/ld+json" v-html="jsonLd" />
        </Head>

        <article>
            <section class="relative overflow-hidden bg-charcoal text-white">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <img
                        v-if="event.cover_image_url"
                        :src="event.cover_image_url"
                        alt=""
                        class="h-full w-full scale-110 object-cover blur-xl brightness-75"
                    />
                    <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-charcoal/75 to-paper" />
                </div>

                <div class="container-editorial relative z-10 py-14 lg:py-20">
                    <nav class="mb-6 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/45">
                        <Link :href="route('home')" class="hover:text-white">{{ t('footer_home') }}</Link>
                        <span class="mx-2 opacity-40">/</span>
                        <Link :href="route('events.index')" class="hover:text-white">{{ t('events_title') }}</Link>
                    </nav>

                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
                        <div>
                            <p class="section-label text-brand">{{ t('events_label') }}</p>
                            <h1 class="mt-3 max-w-4xl font-display text-4xl leading-[1.05] tracking-[-0.04em] sm:text-6xl">
                                {{ event.title }}
                            </h1>
                            <p v-if="event.excerpt" class="mt-5 max-w-2xl text-lg text-white/70">{{ event.excerpt }}</p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <a
                                    v-if="event.registration_url"
                                    :href="event.registration_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="rounded-full bg-brand px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white transition hover:bg-brand-dark"
                                >
                                    {{ t('events_register') }}
                                </a>
                                <Link
                                    :href="route('events.index')"
                                    class="rounded-full border border-white/20 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80 transition hover:border-white hover:text-white"
                                >
                                    {{ t('events_see_all') }}
                                </Link>
                            </div>
                        </div>

                        <aside class="rounded-[1.5rem] border border-white/10 bg-black/35 p-5 backdrop-blur-md">
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/40">{{ t('events_when') }}</dt>
                                    <dd class="mt-1 font-semibold">
                                        {{ formatEventDate(event.starts_at, { month: 'long', hour: '2-digit', minute: '2-digit' }) }}
                                        <template v-if="event.ends_at">
                                            <br />
                                            <span class="font-normal text-white/55">
                                                – {{ formatEventDate(event.ends_at, { month: 'long', hour: '2-digit', minute: '2-digit' }) }}
                                            </span>
                                        </template>
                                    </dd>
                                </div>
                                <div v-if="event.venue || event.location || event.city">
                                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/40">{{ t('events_where') }}</dt>
                                    <dd class="mt-1 font-semibold">
                                        <span v-if="event.venue">{{ event.venue }}</span>
                                        <span v-if="event.location" class="block font-normal text-white/65">{{ event.location }}</span>
                                        <span v-if="event.city" class="block font-normal text-white/65">{{ event.city }}</span>
                                    </dd>
                                </div>
                            </dl>
                            <div class="mt-5 border-t border-white/10 pt-4">
                                <ShareButtons
                                    compact
                                    dark
                                    :slug="event.slug"
                                    :url="shareUrl"
                                    :title="event.title"
                                    share-route="events.share"
                                    :shares-count="event.shares_count || 0"
                                    :show-stats="false"
                                    :description="t('share_event_text')"
                                    :copied-message="t('link_copied_event')"
                                />
                            </div>
                        </aside>
                    </div>

                    <div
                        v-if="event.cover_image_url"
                        class="media-frame mt-10 overflow-hidden rounded-[1.5rem] bg-charcoal-mute shadow-lift"
                    >
                        <img :src="event.cover_image_url" :alt="event.title" class="max-h-[32rem] w-full object-cover" />
                    </div>
                </div>
            </section>

            <section v-if="event.body_html" class="container-editorial py-12">
                <div class="prose-article mx-auto max-w-3xl" v-html="event.body_html" />
            </section>

            <section class="container-editorial" :class="event.body_html ? 'pb-12' : 'py-12'">
                <div class="mx-auto max-w-3xl">
                    <ShareButtons
                        :slug="event.slug"
                        :url="shareUrl"
                        :title="event.title"
                        share-route="events.share"
                        :shares-count="event.shares_count || 0"
                        :description="t('share_event_text')"
                        :copied-message="t('link_copied_event')"
                    />
                </div>
            </section>
        </article>

        <section v-if="related.length" class="container-editorial pb-20">
            <p class="section-label">{{ t('events_label') }}</p>
            <h2 class="font-display mb-7 mt-3 text-3xl tracking-[-0.04em]">{{ t('events_more') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <Link
                    v-for="item in related"
                    :key="item.id"
                    :href="item.url || route('events.show', item.slug)"
                    class="group overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift"
                >
                    <div class="media-frame aspect-[16/10] bg-charcoal-mute">
                        <img
                            v-if="item.cover_image_url"
                            :src="item.cover_image_url"
                            :alt="item.title"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                    <div class="p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ formatEventDate(item.starts_at) }}
                        </p>
                        <h3 class="mt-1 line-clamp-2 font-semibold group-hover:text-brand">{{ item.title }}</h3>
                    </div>
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
