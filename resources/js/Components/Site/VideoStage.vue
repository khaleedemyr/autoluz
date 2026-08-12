<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    videos: { type: Array, default: () => [] },
    backgroundImages: { type: Array, default: () => [] },
    channel: {
        type: Object,
        default: () => ({
            name: 'apih mototv',
            url: 'https://youtube.com/@apihmototv',
        }),
    },
});

const { t } = useI18n();
const activeId = ref(props.videos[0]?.id ?? null);
const playing = ref(false);

watch(
    () => props.videos,
    (list) => {
        if (!list.length) {
            activeId.value = null;
            playing.value = false;
            return;
        }
        if (!list.some((v) => v.id === activeId.value)) {
            activeId.value = list[0].id;
            playing.value = false;
        }
    },
    { immediate: true },
);

const active = computed(() => props.videos.find((v) => v.id === activeId.value) || props.videos[0] || null);

function selectVideo(id) {
    if (activeId.value !== id) {
        playing.value = false;
    }
    activeId.value = id;
}

function playActive() {
    playing.value = true;
}

const embedSrc = computed(() => {
    if (!active.value?.youtube_id) return null;
    return `https://www.youtube.com/embed/${active.value.youtube_id}?rel=0&autoplay=1`;
});
</script>

<template>
    <section class="relative overflow-hidden bg-[#0a0b0d] text-white">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div
                class="absolute inset-0"
                style="background: radial-gradient(ellipse 70% 80% at 15% 0%, rgba(255, 30, 45, 0.18), transparent 50%), linear-gradient(180deg, #101218 0%, #0a0b0d 100%);"
            />
            <div
                class="absolute inset-0 opacity-25"
                style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0); background-size: 24px 24px;"
            />
        </div>

        <div class="container-editorial relative z-10 py-10 lg:py-14">
            <div class="mb-7 flex flex-wrap items-end justify-between gap-4 reveal-up">
                <div>
                    <p class="section-label text-brand">{{ t('video_label') }}</p>
                    <h2 class="font-display mt-3 text-4xl tracking-[-0.04em] sm:text-5xl lg:text-6xl">
                        {{ t('video_title') }}
                    </h2>
                    <p class="mt-2 max-w-lg text-sm text-white/55">
                        {{ t('video_from') }}
                        <a :href="channel.url" target="_blank" rel="noopener" class="text-white hover:text-brand">
                            {{ channel.name }}
                        </a>
                    </p>
                </div>
                <a
                    :href="channel.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/80 transition hover:border-brand hover:bg-brand hover:text-white"
                >
                    {{ t('video_open_youtube') }}
                </a>
            </div>

            <div v-if="active" class="grid gap-5 lg:grid-cols-[1.55fr_1fr]">
                <div class="media-frame bg-black/80 shadow-lift ring-1 ring-white/10">
                    <div class="relative aspect-video bg-black">
                        <iframe
                            v-if="playing && embedSrc"
                            :key="active.youtube_id"
                            class="h-full w-full"
                            :src="embedSrc"
                            :title="active.title"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        />
                        <button
                            v-else
                            type="button"
                            class="group relative block h-full w-full"
                            @click="playActive"
                        >
                            <img
                                v-if="active.thumbnail_url"
                                :src="active.thumbnail_url"
                                :alt="active.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                decoding="async"
                            />
                            <div class="absolute inset-0 bg-black/35 transition group-hover:bg-black/25" />
                            <span
                                class="absolute left-1/2 top-1/2 inline-flex h-16 w-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-glow transition group-hover:scale-105"
                                aria-hidden="true"
                            >
                                <svg class="ml-1 h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </span>
                            <span class="sr-only">{{ t('video_play') }}</span>
                        </button>
                    </div>
                    <div class="border-t border-white/10 bg-black/50 px-5 py-4">
                        <h3 class="font-display text-xl tracking-[-0.02em] sm:text-2xl">{{ active.title }}</h3>
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/40">
                            {{ active.video_type }}
                        </p>
                    </div>
                </div>

                <div class="max-h-[28rem] space-y-2.5 overflow-y-auto overscroll-contain pr-1">
                    <button
                        v-for="(video, index) in videos"
                        :key="video.id"
                        type="button"
                        class="group flex w-full gap-3 rounded-2xl border p-2.5 text-left transition duration-300 ease-editorial"
                        :class="video.id === active?.id
                            ? 'border-brand/60 bg-brand/15 shadow-glow'
                            : 'border-white/10 bg-black/25 hover:border-white/25 hover:bg-black/40'"
                        @click="selectVideo(video.id)"
                    >
                        <div class="relative h-[4.25rem] w-[7.5rem] shrink-0 overflow-hidden rounded-xl bg-black">
                            <img
                                v-if="video.thumbnail_url"
                                :src="video.thumbnail_url"
                                :alt="video.title"
                                class="h-full w-full object-cover transition duration-500 ease-editorial group-hover:scale-105"
                                loading="lazy"
                                decoding="async"
                            />
                            <span class="absolute left-1.5 top-1.5 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold text-white/80">
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                        </div>
                        <div class="min-w-0 py-0.5">
                            <p class="line-clamp-2 text-sm font-semibold leading-snug tracking-[-0.01em]">{{ video.title }}</p>
                            <p class="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/35">
                                {{ video.video_type }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <p v-else class="text-sm text-white/50">
                {{ t('video_empty') }}
                <a :href="channel.url" target="_blank" rel="noopener" class="text-white hover:text-brand">YouTube</a>.
            </p>
        </div>
    </section>
</template>
