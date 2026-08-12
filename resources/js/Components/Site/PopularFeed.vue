<script setup>
import { Link } from '@inertiajs/vue3';
import ArticleStats from '@/Components/Site/ArticleStats.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    articles: { type: Array, default: () => [] },
});

const { t, formatDate } = useI18n();
</script>

<template>
    <section class="min-w-0">
        <div class="mb-7 flex items-end justify-between">
            <div>
                <p class="section-label">{{ t('latest_label') }}</p>
                <h2 class="font-display mt-3 text-4xl tracking-[-0.04em]">{{ t('latest_title') }}</h2>
            </div>
        </div>

        <div v-if="articles.length" class="space-y-4">
            <Link
                v-for="article in articles"
                :key="article.id"
                :href="route('articles.show', article.slug)"
                class="group grid gap-4 rounded-2xl border border-[var(--line)] bg-white/70 p-3 shadow-soft transition duration-300 ease-editorial hover:-translate-y-0.5 hover:border-brand/20 hover:shadow-lift sm:grid-cols-[13rem_minmax(0,1fr)] sm:p-3.5"
            >
                <div class="media-frame aspect-[16/10] bg-charcoal-mute sm:aspect-auto sm:h-32">
                    <img
                        v-if="article.featured_image_url"
                        :src="article.featured_image_url"
                        :alt="article.title"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div class="min-w-0 self-center py-1 pr-1">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span
                            v-if="article.category"
                            class="rounded-full bg-charcoal px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white"
                        >
                            {{ article.category.name }}
                        </span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                            {{ formatDate(article.published_at, { month: 'long' }) }}
                        </span>
                        <ArticleStats :views="article.views_count" :comments="article.comments_count" />
                    </div>
                    <h3 class="font-display text-xl leading-snug tracking-[-0.03em] transition group-hover:text-brand sm:text-2xl">
                        {{ article.title }}
                    </h3>
                    <p v-if="article.excerpt" class="mt-2 line-clamp-2 text-sm leading-relaxed text-neutral-600">
                        {{ article.excerpt }}
                    </p>
                </div>
            </Link>
        </div>
        <p v-else class="py-8 text-sm text-neutral-500">{{ t('latest_empty') }}</p>

        <div class="mt-8">
            <Link
                :href="route('articles.index')"
                class="inline-flex items-center gap-2 rounded-full border border-charcoal bg-charcoal px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white transition hover:bg-brand hover:border-brand"
            >
                {{ t('see_all_articles') }}
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </Link>
        </div>
    </section>
</template>
