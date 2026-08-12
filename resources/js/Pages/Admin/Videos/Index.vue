<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    videos: { type: Array, default: () => [] },
    channel: {
        type: Object,
        default: () => ({
            name: 'apih mototv',
            url: 'https://youtube.com/@apihmototv',
        }),
    },
    cacheTtlMinutes: { type: Number, default: 30 },
});

function refreshFeed() {
    router.post(route('admin.videos.refresh'), {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout title="Video">
        <Head title="Admin Video" />

        <div class="mb-5 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-xl tracking-[-0.03em]">Live dari YouTube</h2>
                    <p class="mt-1 max-w-2xl text-sm text-neutral-500">
                        Video tidak disimpan ke database. Homepage embed langsung dari channel
                        <a :href="channel.url" target="_blank" rel="noopener" class="font-medium text-brand hover:underline">
                            {{ channel.name }}
                        </a>.
                        Cache refresh otomatis tiap {{ cacheTtlMinutes }} menit; video baru akan muncul sendiri.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
                    @click="refreshFeed"
                >
                    Refresh Sekarang
                </button>
            </div>
        </div>

        <div v-if="videos.length" class="space-y-3">
            <div
                v-for="video in videos"
                :key="video.youtube_id"
                class="rounded-2xl border border-black/5 bg-white p-4 shadow-sm"
            >
                <div class="flex flex-wrap items-center gap-4">
                    <img :src="video.thumbnail_url" class="h-16 w-28 rounded-xl object-cover" alt="" />
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">{{ video.title }}</div>
                        <div class="text-xs text-neutral-500">
                            {{ video.video_type }} · {{ video.youtube_id }}
                        </div>
                    </div>
                    <a
                        :href="video.youtube_url"
                        target="_blank"
                        rel="noopener"
                        class="text-xs font-semibold uppercase tracking-[0.12em] text-brand hover:underline"
                    >
                        Buka
                    </a>
                </div>
            </div>
        </div>
        <p v-else class="rounded-2xl border border-dashed border-black/10 bg-white px-5 py-10 text-center text-sm text-neutral-500">
            Belum ada video dari feed. Klik Refresh atau cek channel YouTube.
        </p>
    </AdminLayout>
</template>
