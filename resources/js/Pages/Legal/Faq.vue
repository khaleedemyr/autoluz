<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    page: { type: Object, required: true },
});

const { t } = useI18n();
const query = ref('');

const filteredGroups = computed(() => {
    const term = query.value.trim().toLowerCase();
    if (!term) return props.page.groups || [];

    return (props.page.groups || [])
        .map((group) => ({
            ...group,
            items: (group.items || []).filter((item) => {
                const haystack = `${item.q || ''} ${item.a || ''}`.toLowerCase();
                return haystack.includes(term);
            }),
        }))
        .filter((group) => group.items.length > 0);
});
</script>

<template>
    <AppLayout>
        <Head :title="page.title">
            <meta name="description" :content="page.desc" />
        </Head>

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="section-label">{{ page.eyebrow }}</p>
                <h1 class="font-display mt-3 max-w-3xl text-4xl tracking-[-0.04em] sm:text-5xl lg:text-6xl">
                    {{ page.title }}
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-neutral-600 sm:text-base">
                    {{ page.desc }}
                </p>
                <div class="mt-8 max-w-xl">
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="page.search_ph"
                        class="w-full border-0 border-b border-charcoal/20 bg-transparent px-0 py-2 text-lg focus:border-brand focus:ring-0"
                    />
                </div>
            </div>
        </section>

        <section class="container-editorial py-10 lg:py-14">
            <div v-if="filteredGroups.length" class="space-y-12">
                <div v-for="group in filteredGroups" :key="group.title">
                    <h2 class="font-display text-2xl tracking-[-0.03em] sm:text-3xl">{{ group.title }}</h2>
                    <div class="mt-5 divide-y divide-[var(--line)] overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft">
                        <details
                            v-for="item in group.items"
                            :key="item.q"
                            class="group/item px-5 py-1"
                        >
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-left text-sm font-semibold tracking-[-0.01em] sm:text-[15px] [&::-webkit-details-marker]:hidden"
                            >
                                <span>{{ item.q }}</span>
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-[var(--line)] text-neutral-400 transition group-open/item:rotate-45 group-open/item:border-brand group-open/item:text-brand"
                                    aria-hidden="true"
                                >
                                    +
                                </span>
                            </summary>
                            <div class="pb-5">
                                <p class="max-w-3xl text-sm leading-relaxed text-neutral-600">
                                    {{ item.a }}
                                </p>
                                <Link
                                    v-if="item.cta?.route"
                                    :href="route(item.cta.route)"
                                    class="mt-3 inline-flex text-sm font-medium text-brand hover:underline"
                                >
                                    {{ item.cta.label }}
                                </Link>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-neutral-500">{{ page.empty }}</p>

            <div class="mt-12 rounded-2xl border border-[var(--line)] bg-[#0a0b0d] px-6 py-7 text-white sm:px-8">
                <p class="text-sm leading-relaxed text-white/70">
                    {{ page.contact }}
                    <a
                        :href="`mailto:${page.contact_email}`"
                        class="font-medium text-white underline decoration-brand/70 underline-offset-4 hover:text-brand"
                    >
                        {{ page.contact_email }}
                    </a>
                </p>
                <Link
                    :href="route('legal.privacy')"
                    class="mt-4 inline-flex text-sm font-medium text-brand hover:underline"
                >
                    {{ t('footer_privacy') }}
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
