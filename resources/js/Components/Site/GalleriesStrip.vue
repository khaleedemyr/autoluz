<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

defineProps({
    galleries: { type: Array, default: () => [] },
});

const { t } = useI18n();
</script>

<template>
    <section v-if="galleries.length" class="border-y border-[var(--line)] bg-white/60">
        <div class="container-editorial py-12">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-label">{{ t('galleries_label') }}</p>
                    <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('galleries_home_title') }}</h2>
                    <p class="mt-2 max-w-xl text-sm text-neutral-600">{{ t('galleries_home_desc') }}</p>
                </div>
                <Link
                    :href="route('galleries.index')"
                    class="rounded-full border border-charcoal/20 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                >
                    {{ t('galleries_see_all') }}
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="gallery in galleries"
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
                    <div class="p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ gallery.images_count || 0 }} {{ t('galleries_photos') }}
                        </p>
                        <h3 class="mt-1 text-sm font-semibold tracking-[-0.01em] group-hover:text-brand">{{ gallery.title }}</h3>
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>
