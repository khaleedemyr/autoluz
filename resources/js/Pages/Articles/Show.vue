<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import ShareButtons from '@/Components/Site/ShareButtons.vue';
import CommentSection from '@/Components/Site/CommentSection.vue';
import { useI18n } from '@/composables/useI18n';

const { t, formatDate, formatNumber } = useI18n();

const props = defineProps({
    article: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    comments: { type: Array, default: () => [] },
});

const jsonLd = computed(() => {
    const seo = props.article.seo || {};
    const data = {
        '@context': 'https://schema.org',
        '@type': 'NewsArticle',
        headline: seo.meta_title || props.article.title,
        description: seo.meta_description || props.article.excerpt,
        image: props.article.featured_image_url ? [props.article.featured_image_url] : undefined,
        datePublished: props.article.published_at,
        dateModified: props.article.updated_at || props.article.published_at,
        mainEntityOfPage: seo.canonical_url || props.article.url,
        articleSection: props.article.category?.name,
        keywords: props.article.seo?.focus_keyword || undefined,
        publisher: {
            '@type': 'Organization',
            name: 'Autoluz',
        },
    };

    return JSON.stringify(data);
});

const pageTitle = computed(() => props.article.seo?.meta_title || props.article.title);
const pageDescription = computed(() => props.article.seo?.meta_description || props.article.excerpt || props.article.title);
const ogTitle = computed(() => props.article.seo?.og_title || pageTitle.value);
const ogDescription = computed(() => props.article.seo?.og_description || pageDescription.value);
const canonicalUrl = computed(() => props.article.seo?.canonical_url || props.article.url);
const shareUrl = computed(() => props.article.url || canonicalUrl.value);
</script>

<template>
    <AppLayout>
        <Head :title="pageTitle">
            <meta name="description" :content="pageDescription" />
            <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="article" />
            <meta property="og:title" :content="ogTitle" />
            <meta property="og:description" :content="ogDescription" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta v-if="article.featured_image_url" property="og:image" :content="article.featured_image_url" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="ogTitle" />
            <meta name="twitter:description" :content="ogDescription" />
            <component :is="'script'" type="application/ld+json" v-html="jsonLd" />
        </Head>

        <article class="container-editorial py-10 lg:py-14">
            <nav class="mb-6 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                <Link :href="route('home')" class="hover:text-brand">{{ t('footer_home') }}</Link>
                <span class="mx-2 opacity-40">/</span>
                <Link
                    v-if="article.category"
                    :href="route('categories.show', article.category.slug)"
                    class="hover:text-brand"
                >
                    {{ article.category.name }}
                </Link>
            </nav>

            <header class="max-w-4xl">
                <p
                    v-if="article.category"
                    class="section-label"
                >
                    {{ article.category.name }}
                </p>
                <h1 class="mt-4 font-display text-4xl leading-[1.05] tracking-[-0.04em] sm:text-6xl lg:text-7xl">
                    {{ article.title }}
                </h1>
                <p class="mt-5 max-w-3xl font-editorial text-lg text-neutral-600">{{ article.excerpt }}</p>
                <p class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                    <span>{{ formatDate(article.published_at, { month: 'long' }) }}</span>
                    <span>{{ t('views_times', { count: formatNumber(article.views_count || 0) }) }}</span>
                    <span>{{ t('shares_times', { count: formatNumber(article.shares_count || 0) }) }}</span>
                </p>
                <div v-if="article.brands?.length" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="brand in article.brands"
                        :key="brand.id"
                        :href="brand.url || route('brands.show', brand.slug)"
                        class="rounded-full border border-charcoal/15 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-600 transition hover:border-brand hover:text-brand"
                    >
                        {{ brand.name }}
                    </Link>
                </div>
            </header>

            <div
                v-if="article.featured_image_url"
                class="media-frame mt-10 bg-charcoal-mute shadow-lift"
            >
                <img
                    :src="article.featured_image_url"
                    :alt="article.title"
                    class="max-h-[34rem] w-full object-cover"
                />
            </div>

            <div class="prose-article mx-auto mt-12 max-w-3xl" v-html="article.content_html" />

            <div class="mx-auto mt-10 max-w-3xl">
                <ShareButtons
                    :slug="article.slug"
                    :url="shareUrl"
                    :title="article.title"
                    :views-count="article.views_count || 0"
                    :shares-count="article.shares_count || 0"
                />
            </div>

            <div class="mx-auto mt-10 max-w-3xl">
                <CommentSection :article-slug="article.slug" :comments="comments" />
            </div>
        </article>

        <section v-if="related.length" class="container-editorial pb-20">
            <p class="section-label">{{ t('related_label') }}</p>
            <h2 class="font-display mb-7 mt-3 text-4xl tracking-[-0.04em]">{{ t('related_title') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ArticleCard v-for="item in related" :key="item.id" :article="item" />
            </div>
        </section>
    </AppLayout>
</template>
