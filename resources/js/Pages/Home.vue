<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VideoStage from '@/Components/Site/VideoStage.vue';
import FeaturedStrip from '@/Components/Site/FeaturedStrip.vue';
import PopularFeed from '@/Components/Site/PopularFeed.vue';
import SidebarReviews from '@/Components/Site/SidebarReviews.vue';
import SocialLinks from '@/Components/Site/SocialLinks.vue';
import EventsStrip from '@/Components/Site/EventsStrip.vue';
import BrandsStrip from '@/Components/Site/BrandsStrip.vue';
import GalleriesStrip from '@/Components/Site/GalleriesStrip.vue';
import ShopStrip from '@/Components/Site/ShopStrip.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const props = defineProps({
    videos: { type: Array, default: () => [] },
    videosMeta: {
        type: Object,
        default: () => ({ total: 0, initial: 0, page_size: 8, has_more: false }),
    },
    featured: { type: Object, default: () => ({ main: null, side: [] }) },
    popular: { type: Array, default: () => [] },
    latestReviews: { type: Array, default: () => [] },
    ticker: { type: Array, default: () => [] },
    stageBackgrounds: { type: Array, default: () => [] },
    upcomingEvents: { type: Array, default: () => [] },
    brands: { type: Object, default: () => ({ cars: [], motos: [] }) },
    recentGalleries: { type: Array, default: () => [] },
    shopProducts: { type: Array, default: () => [] },
    youtubeChannel: {
        type: Object,
        default: () => ({
            name: 'apih mototv',
            url: 'https://youtube.com/@apihmototv',
        }),
    },
});

const socialCardImage = computed(() => {
    return (
        props.featured?.main?.featured_image_url
        || props.stageBackgrounds?.[0]
        || props.popular?.[0]?.featured_image_url
        || null
    );
});
</script>

<template>
    <AppLayout>
        <Head :title="t('home_title')" />

        <div v-if="ticker.length" class="border-b border-[var(--line)] bg-white/70 backdrop-blur-md">
            <div class="container-editorial flex items-center gap-3 py-3">
                <span class="shrink-0 rounded-full bg-brand px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-white shadow-glow">
                    {{ t('live') }}
                </span>
                <div class="ticker-mask min-w-0 flex-1 overflow-hidden">
                    <div class="ticker-track flex w-max gap-8">
                        <Link
                            v-for="item in [...ticker, ...ticker]"
                            :key="`${item.id}-${item.slug}`"
                            :href="route('articles.show', item.slug)"
                            class="shrink-0 text-[13px] font-medium text-charcoal/70 transition hover:text-brand"
                        >
                            {{ item.title }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <VideoStage
            :videos="videos"
            :videos-meta="videosMeta"
            :channel="youtubeChannel"
            :background-images="stageBackgrounds"
        />
        <FeaturedStrip :main="featured.main" :side="featured.side" />
        <EventsStrip :events="upcomingEvents" />
        <BrandsStrip :brands="brands" />
        <ShopStrip :products="shopProducts" />
        <GalleriesStrip :galleries="recentGalleries" />

        <section class="container-editorial pb-20">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.65fr)_minmax(280px,0.85fr)]">
                <PopularFeed :articles="popular" />

                <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
                    <SidebarReviews :articles="latestReviews" />
                    <div class="relative overflow-hidden rounded-[1.5rem] text-white shadow-lift ring-1 ring-black/5">
                        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                            <img
                                v-if="socialCardImage"
                                :src="socialCardImage"
                                alt=""
                                class="h-full w-full scale-125 object-cover blur-xl brightness-90 saturate-125"
                                loading="lazy"
                            />
                            <div
                                class="absolute inset-0"
                                :class="socialCardImage
                                    ? 'bg-gradient-to-br from-black/55 via-charcoal/70 to-brand/35'
                                    : 'bg-gradient-to-br from-[#17191f] via-[#101218] to-brand/40'"
                            />
                            <div class="absolute -right-8 -top-10 h-36 w-36 rounded-full bg-brand/25 blur-3xl" />
                            <div class="absolute -bottom-10 left-0 h-28 w-28 rounded-full bg-white/10 blur-2xl" />
                        </div>

                        <div class="relative z-10 p-6">
                            <p class="section-label text-brand">{{ t('social_label') }}</p>
                            <h2 class="font-display mt-3 text-3xl tracking-[-0.03em]">{{ t('follow_title') }}</h2>
                            <p class="mt-2 text-sm text-white/70">{{ t('follow_text') }}</p>
                            <div class="mt-6">
                                <SocialLinks />
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </AppLayout>
</template>
