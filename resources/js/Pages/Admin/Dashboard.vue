<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: { type: Object, required: true },
    latestArticles: { type: Array, default: () => [] },
});
</script>

<template>
    <AdminLayout title="Dashboard">
        <Head title="Admin Dashboard" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="card in [
                { label: 'Total Artikel', value: stats.articles },
                { label: 'Published', value: stats.published },
                { label: 'Kategori', value: stats.categories },
                { label: 'Video Channel', value: stats.videos },
            ]" :key="card.label" class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-400">{{ card.label }}</p>
                <p class="mt-2 font-display text-4xl tracking-[-0.04em]">{{ card.value }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <Link
                :href="route('admin.articles.create')"
                class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
            >
                + Artikel Baru
            </Link>
            <Link
                :href="route('admin.categories.index')"
                class="rounded-full border border-black/10 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]"
            >
                Kelola Kategori
            </Link>
            <Link
                :href="route('admin.videos.index')"
                class="rounded-full border border-black/10 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]"
            >
                Video YouTube
            </Link>
        </div>

        <section class="mt-8 overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <div class="border-b border-black/5 px-5 py-4">
                <h2 class="font-semibold">Artikel Terbaru Diubah</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist/60 text-xs uppercase tracking-[0.12em] text-neutral-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Judul</th>
                            <th class="px-5 py-3 font-semibold">Kategori</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Updated</th>
                            <th class="px-5 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="article in latestArticles" :key="article.id" class="border-t border-black/5">
                            <td class="px-5 py-3 font-medium">{{ article.title }}</td>
                            <td class="px-5 py-3 text-neutral-500">{{ article.category || '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-mist px-2 py-1 text-[11px] font-semibold uppercase">
                                    {{ article.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-neutral-500">{{ article.updated_at }}</td>
                            <td class="px-5 py-3 text-right">
                                <Link :href="route('admin.articles.edit', article.id)" class="text-brand hover:underline">
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
