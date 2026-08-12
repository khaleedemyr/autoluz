<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    category: { type: Object, required: true },
    articles: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="category.name" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="font-oswald text-xs uppercase tracking-[0.28em] text-brand">{{ t('category_eyebrow') }}</p>
                <h1 class="mt-2 font-display text-5xl tracking-[0.06em] sm:text-6xl">{{ category.name }}</h1>
                <p v-if="category.description" class="mt-3 max-w-2xl text-neutral-600">
                    {{ category.description }}
                </p>
            </div>
        </section>

        <section class="container-editorial py-10">
            <div v-if="articles.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard v-for="article in articles.data" :key="article.id" :article="article" />
            </div>
            <p v-else class="text-sm text-neutral-500">{{ t('category_empty') }}</p>

            <SitePagination :links="articles.links" />
        </section>
    </AppLayout>
</template>
