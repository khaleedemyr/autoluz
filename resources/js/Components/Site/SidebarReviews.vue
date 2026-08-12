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
    <aside class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white/80 shadow-soft backdrop-blur-sm">
        <div class="border-b border-[var(--line)] px-5 py-4">
            <p class="section-label">{{ t('reviews_label') }}</p>
            <h2 class="font-display mt-2 text-3xl tracking-[-0.03em]">{{ t('reviews_title') }}</h2>
        </div>

        <div v-if="articles.length" class="divide-y divide-[var(--line)]">
            <Link
                v-for="article in articles"
                :key="article.id"
                :href="route('articles.show', article.slug)"
                class="group flex gap-3 p-3.5 transition hover:bg-mist/70"
            >
                <div class="media-frame h-[4.25rem] w-[5.5rem] shrink-0 bg-charcoal-mute">
                    <img
                        v-if="article.featured_image_url"
                        :src="article.featured_image_url"
                        :alt="article.title"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div class="min-w-0">
                    <h3 class="line-clamp-2 text-sm font-semibold leading-snug tracking-[-0.01em] text-charcoal group-hover:text-brand">
                        {{ article.title }}
                    </h3>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                            {{ formatDate(article.published_at) }}
                        </p>
                        <ArticleStats :views="article.views_count" :comments="article.comments_count" />
                    </div>
                </div>
            </Link>
        </div>
        <p v-else class="p-5 text-sm text-neutral-500">{{ t('reviews_empty') }}</p>
    </aside>
</template>
