<script setup>
import { Link } from '@inertiajs/vue3';
import ArticleStats from '@/Components/Site/ArticleStats.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    main: { type: Object, default: null },
    side: { type: Array, default: () => [] },
});

const { t, formatDate } = useI18n();
</script>

<template>
    <section class="container-editorial py-12 lg:py-16">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <p class="section-label">{{ t('featured_label') }}</p>
                <h2 class="font-display mt-3 text-4xl tracking-[-0.04em] sm:text-5xl">{{ t('featured_title') }}</h2>
            </div>
        </div>

        <div v-if="main" class="grid gap-5 lg:grid-cols-[1.45fr_1fr]">
            <Link
                :href="main.url || route('articles.show', main.slug)"
                class="group relative block overflow-hidden rounded-[1.5rem] bg-charcoal text-white shadow-lift"
            >
                <div class="aspect-[16/11] overflow-hidden sm:aspect-[16/10]">
                    <img
                        v-if="main.featured_image_url"
                        :src="main.featured_image_url"
                        :alt="main.title"
                        class="h-full w-full object-cover transition duration-700 ease-editorial group-hover:scale-105"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    />
                    <div v-else class="h-full w-full bg-charcoal-mute" />
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/35 to-transparent" />
                <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                    <span
                        v-if="main.category"
                        class="inline-flex rounded-full bg-brand px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]"
                    >
                        {{ main.category.name }}
                    </span>
                    <h3 class="mt-3 max-w-3xl font-display text-3xl leading-[1.05] tracking-[-0.03em] sm:text-5xl">
                        {{ main.title }}
                    </h3>
                    <p class="mt-3 max-w-2xl text-sm text-white/70 line-clamp-2">{{ main.excerpt }}</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/40">
                            {{ formatDate(main.published_at) }}
                        </p>
                        <ArticleStats :views="main.views_count" :comments="main.comments_count" light />
                    </div>
                </div>
            </Link>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <Link
                    v-for="article in side"
                    :key="article.id"
                    :href="article.url || route('articles.show', article.slug)"
                    class="group grid grid-cols-[7.5rem_minmax(0,1fr)] gap-3 rounded-2xl border border-[var(--line)] bg-white/80 p-2.5 shadow-soft transition duration-300 ease-editorial hover:-translate-y-0.5 hover:shadow-lift sm:grid-cols-[8.5rem_minmax(0,1fr)]"
                >
                    <div class="media-frame h-24 bg-charcoal-mute sm:h-28">
                        <img
                            v-if="article.featured_image_url"
                            :src="article.featured_image_url"
                            :alt="article.title"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                    <div class="min-w-0 py-1 pr-1">
                        <p v-if="article.category" class="text-[10px] font-semibold uppercase tracking-[0.16em] text-brand">
                            {{ article.category.name }}
                        </p>
                        <h3 class="mt-1 line-clamp-3 text-sm font-semibold leading-snug tracking-[-0.01em] transition group-hover:text-brand sm:text-[15px]">
                            {{ article.title }}
                        </h3>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                {{ formatDate(article.published_at) }}
                            </p>
                            <ArticleStats :views="article.views_count" :comments="article.comments_count" />
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <p v-else class="rounded-2xl border border-dashed border-[var(--line)] py-12 text-center text-sm text-muted">
            {{ t('featured_empty') }}
        </p>
    </section>
</template>
