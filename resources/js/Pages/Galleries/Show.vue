<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    gallery: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const { t } = useI18n();
const activeIndex = ref(null);

function openAt(index) {
    activeIndex.value = index;
}

function close() {
    activeIndex.value = null;
}

function prev(total) {
    if (activeIndex.value === null) return;
    activeIndex.value = (activeIndex.value - 1 + total) % total;
}

function next(total) {
    if (activeIndex.value === null) return;
    activeIndex.value = (activeIndex.value + 1) % total;
}
</script>

<template>
    <AppLayout>
        <Head :title="gallery.title" />

        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <nav class="mb-4 text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">
                    <Link :href="route('galleries.index')" class="hover:text-brand">{{ t('galleries_title') }}</Link>
                </nav>
                <h1 class="font-display text-5xl tracking-[-0.04em]">{{ gallery.title }}</h1>
                <p v-if="gallery.excerpt" class="mt-3 max-w-2xl text-neutral-600">{{ gallery.excerpt }}</p>
            </div>
        </section>

        <section class="container-editorial py-10">
            <div class="columns-1 gap-4 sm:columns-2 lg:columns-3">
                <button
                    v-for="(image, index) in gallery.images"
                    :key="image.id"
                    type="button"
                    class="mb-4 block w-full break-inside-avoid overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft"
                    @click="openAt(index)"
                >
                    <img :src="image.image_url" :alt="image.caption || gallery.title" class="w-full object-cover" loading="lazy" />
                    <p v-if="image.caption" class="px-3 py-2 text-left text-xs text-neutral-500">{{ image.caption }}</p>
                </button>
            </div>
        </section>

        <Teleport to="body">
            <div
                v-if="activeIndex !== null && gallery.images[activeIndex]"
                class="fixed inset-0 z-[80] flex items-center justify-center bg-black/85 p-4"
                @click.self="close"
            >
                <button type="button" class="absolute right-4 top-4 rounded-full bg-white/10 px-3 py-1 text-sm text-white" @click="close">
                    {{ t('galleries_close') }}
                </button>
                <button type="button" class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 px-3 py-2 text-white" @click="prev(gallery.images.length)">
                    ‹
                </button>
                <div class="max-h-[85vh] max-w-5xl">
                    <img
                        :src="gallery.images[activeIndex].image_url"
                        :alt="gallery.images[activeIndex].caption || gallery.title"
                        class="max-h-[75vh] w-full object-contain"
                    />
                    <p v-if="gallery.images[activeIndex].caption" class="mt-3 text-center text-sm text-white/70">
                        {{ gallery.images[activeIndex].caption }}
                    </p>
                </div>
                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 px-3 py-2 text-white" @click="next(gallery.images.length)">
                    ›
                </button>
            </div>
        </Teleport>

        <section v-if="related.length" class="container-editorial pb-20">
            <h2 class="font-display text-3xl tracking-[-0.04em]">{{ t('galleries_more') }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="item in related"
                    :key="item.id"
                    :href="item.url"
                    class="overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-soft"
                >
                    <div class="media-frame aspect-[16/10] bg-charcoal-mute">
                        <img v-if="item.cover_image_url" :src="item.cover_image_url" :alt="item.title" class="h-full w-full object-cover" loading="lazy" />
                    </div>
                    <p class="p-3 text-sm font-semibold">{{ item.title }}</p>
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
