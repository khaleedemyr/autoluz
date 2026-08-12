<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    articles: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('all_articles_title')" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="section-label">{{ t('all_articles_eyebrow') }}</p>
                <h1 class="font-display mt-3 text-5xl tracking-[-0.04em] sm:text-6xl">{{ t('all_articles_title') }}</h1>
                <p class="mt-3 max-w-2xl text-neutral-600">
                    {{ t('all_articles_desc') }}
                </p>
            </div>
        </section>

        <section class="container-editorial py-10">
            <div v-if="articles.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard v-for="article in articles.data" :key="article.id" :article="article" />
            </div>
            <p v-else class="text-sm text-neutral-500">{{ t('all_articles_empty') }}</p>

            <SitePagination :links="articles.links" />
        </section>
    </AppLayout>
</template>
