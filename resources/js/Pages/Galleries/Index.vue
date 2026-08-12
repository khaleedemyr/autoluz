<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SitePagination from '@/Components/Site/SitePagination.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    galleries: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('galleries_title')" />
        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="section-label">{{ t('galleries_label') }}</p>
                <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('galleries_title') }}</h1>
                <p class="mt-3 max-w-2xl text-neutral-600">{{ t('galleries_page_desc') }}</p>
            </div>
        </section>
        <section class="container-editorial py-10">
            <div v-if="galleries.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="gallery in galleries.data"
                    :key="gallery.id"
                    :href="gallery.url"
                    class="group overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift"
                >
                    <div class="media-frame aspect-[16/10] bg-charcoal-mute">
                        <img
                            v-if="gallery.cover_image_url"
                            :src="gallery.cover_image_url"
                            :alt="gallery.title"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                        />
                    </div>
                    <div class="p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ gallery.images_count || 0 }} {{ t('galleries_photos') }}
                        </p>
                        <h2 class="mt-1 font-semibold tracking-[-0.01em] group-hover:text-brand">{{ gallery.title }}</h2>
                        <p v-if="gallery.excerpt" class="mt-1 line-clamp-2 text-sm text-neutral-600">{{ gallery.excerpt }}</p>
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-neutral-500">{{ t('galleries_empty') }}</p>
            <SitePagination :links="galleries.links" />
        </section>
    </AppLayout>
</template>
