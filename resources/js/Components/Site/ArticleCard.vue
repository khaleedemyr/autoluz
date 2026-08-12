<script setup>
import { Link } from '@inertiajs/vue3';
import ArticleStats from '@/Components/Site/ArticleStats.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    article: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

const { formatDate } = useI18n();
</script>

<template>
    <Link
        :href="article.url || route('articles.show', article.slug)"
        class="group block overflow-hidden rounded-2xl border border-[var(--line)] bg-white/80 shadow-soft transition duration-300 ease-editorial hover:-translate-y-0.5 hover:shadow-lift"
        :class="compact ? 'flex gap-3 p-2' : ''"
    >
        <div
            class="media-frame overflow-hidden bg-charcoal-mute"
            :class="compact ? 'h-24 w-32 shrink-0' : 'aspect-[16/10]'"
        >
            <img
                v-if="article.featured_image_url"
                :src="article.featured_image_url"
                :alt="article.title"
                class="h-full w-full object-cover"
                loading="lazy"
            />
        </div>
        <div :class="compact ? 'min-w-0 py-1 pr-1' : 'p-4'">
            <p
                v-if="article.category"
                class="text-[10px] font-semibold uppercase tracking-[0.16em] text-brand"
            >
                {{ article.category.name }}
            </p>
            <h3
                class="mt-1 font-semibold tracking-[-0.02em] text-charcoal transition group-hover:text-brand"
                :class="compact ? 'line-clamp-2 text-sm leading-snug' : 'line-clamp-3 text-lg'"
            >
                {{ article.title }}
            </h3>
            <p v-if="!compact && article.excerpt" class="mt-2 line-clamp-2 text-sm text-neutral-600">
                {{ article.excerpt }}
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                    {{ formatDate(article.published_at) }}
                </p>
                <ArticleStats :views="article.views_count" :comments="article.comments_count" />
            </div>
        </div>
    </Link>
</template>
