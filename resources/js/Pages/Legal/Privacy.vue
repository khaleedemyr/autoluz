<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    page: { type: Object, required: true },
});

const { t } = useI18n();
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
                    {{ page.intro }}
                </p>
                <p class="mt-3 text-xs uppercase tracking-[0.16em] text-neutral-400">{{ page.updated }}</p>
            </div>
        </section>

        <section class="container-editorial py-10 lg:py-14">
            <div class="grid gap-10 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-16">
                <aside class="hidden lg:block">
                    <div class="sticky top-28">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand">
                            {{ page.toc_title }}
                        </p>
                        <nav class="mt-4 space-y-2">
                            <a
                                v-for="section in page.sections"
                                :key="section.id"
                                :href="`#${section.id}`"
                                class="block text-sm leading-snug text-neutral-500 transition hover:text-charcoal"
                            >
                                {{ section.title }}
                            </a>
                        </nav>
                        <Link
                            :href="route('legal.faq')"
                            class="mt-8 inline-flex text-sm font-medium text-brand hover:underline"
                        >
                            {{ t('footer_faq') }}
                        </Link>
                    </div>
                </aside>

                <article class="max-w-3xl space-y-12">
                    <section
                        v-for="section in page.sections"
                        :id="section.id"
                        :key="section.id"
                        class="scroll-mt-28"
                    >
                        <h2 class="font-display text-2xl tracking-[-0.03em] sm:text-3xl">
                            {{ section.title }}
                        </h2>

                        <p
                            v-for="(paragraph, index) in section.paragraphs || []"
                            :key="`p-${index}`"
                            class="mt-4 text-sm leading-relaxed text-neutral-600 sm:text-[15px]"
                        >
                            {{ paragraph }}
                        </p>

                        <div v-if="section.groups?.length" class="mt-5 space-y-5">
                            <div
                                v-for="group in section.groups"
                                :key="group.title"
                                class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft"
                            >
                                <h3 class="text-sm font-semibold tracking-[-0.01em]">{{ group.title }}</h3>
                                <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-neutral-600">
                                    <li v-for="item in group.items" :key="item">{{ item }}</li>
                                </ul>
                            </div>
                        </div>

                        <ul
                            v-if="section.items?.length"
                            class="mt-4 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-neutral-600 sm:text-[15px]"
                        >
                            <li v-for="item in section.items" :key="item">{{ item }}</li>
                        </ul>

                        <p
                            v-for="(paragraph, index) in section.paragraphs_after || []"
                            :key="`pa-${index}`"
                            class="mt-4 text-sm leading-relaxed text-neutral-600 sm:text-[15px]"
                        >
                            {{ paragraph }}
                        </p>
                    </section>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
