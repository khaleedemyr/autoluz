<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    q: { type: String, default: '' },
    articles: { type: Object, required: true },
});

const { t } = useI18n();
const term = ref(props.q);

watch(
    () => props.q,
    (value) => {
        term.value = value;
    },
);

function submit() {
    router.get(route('search'), { q: term.value }, { preserveState: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('search')" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="font-oswald text-xs uppercase tracking-[0.28em] text-brand">{{ t('search_eyebrow') }}</p>
                <h1 class="mt-2 font-display text-5xl tracking-[0.06em]">{{ t('search_title') }}</h1>
                <form class="mt-6 flex max-w-xl items-end gap-3" @submit.prevent="submit">
                    <input
                        v-model="term"
                        type="search"
                        :placeholder="t('search_placeholder')"
                        class="flex-1 border-0 border-b border-charcoal/20 bg-transparent px-0 py-2 text-lg focus:border-brand focus:ring-0"
                    />
                    <button
                        type="submit"
                        class="bg-brand px-5 py-2 font-oswald text-xs uppercase tracking-[0.2em] text-white transition hover:bg-brand-dark"
                    >
                        {{ t('search') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="container-editorial py-10">
            <p v-if="q" class="mb-6 text-sm text-neutral-500">
                {{ t('search_results_for') }} <span class="font-medium text-charcoal">"{{ q }}"</span>
            </p>

            <div v-if="articles.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard v-for="article in articles.data" :key="article.id" :article="article" />
            </div>
            <p v-else class="text-sm text-neutral-500">{{ t('search_empty') }}</p>

            <SitePagination :links="articles.links" />
        </section>
    </AppLayout>
</template>
