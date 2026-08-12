<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import FollowBrandButton from '@/Components/Site/FollowBrandButton.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    brands: { type: Array, default: () => [] },
    articles: { type: Array, default: () => [] },
    ids: { type: Array, default: () => [] },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('brands_my_feed')" />
        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="section-label">{{ t('brands_label') }}</p>
                <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('brands_my_feed') }}</h1>
                <p class="mt-3 max-w-2xl text-neutral-600">{{ t('brands_feed_desc') }}</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <div
                        v-for="brand in brands"
                        :key="brand.id"
                        class="inline-flex items-center gap-2 rounded-full border border-[var(--line)] bg-white px-3 py-1.5"
                    >
                        <Link :href="brand.url" class="text-sm font-semibold">{{ brand.name }}</Link>
                        <FollowBrandButton :brand-id="brand.id" />
                    </div>
                </div>
            </div>
        </section>
        <section class="container-editorial py-10">
            <div v-if="articles.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard v-for="article in articles" :key="article.id" :article="article" />
            </div>
            <p v-else class="text-sm text-neutral-500">{{ t('brands_feed_empty') }}</p>
            <div class="mt-8">
                <Link :href="route('brands.index')" class="text-sm font-semibold text-brand hover:underline">
                    {{ t('brands_see_all') }} →
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
